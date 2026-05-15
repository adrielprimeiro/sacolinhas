<?php

namespace App\Services;

use App\Models\TransacaoExtrato;
use App\Models\Lancamento;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

class ConciliacaoService
{
    /**
     * Importar transações de um arquivo OFX
     */
    public function importarOfx(UploadedFile $file, ?int $contaBancariaId = null)
    {
        $content = file_get_contents($file->getRealPath());
        
        // Regex para capturar blocos de transação <STMTTRN>
        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $content, $matches);
        
        $count = 0;
        foreach ($matches[1] as $trn) {
            $data = $this->parseOfxTransaction($trn);
            
            if ($data) {
                TransacaoExtrato::updateOrCreate(
                    ['fitid' => $data['fitid']],
                    [
                        'data' => $data['data'],
                        'descricao' => $data['descricao'],
                        'valor' => abs($data['valor']),
                        'tipo' => $data['valor'] >= 0 ? 'entrada' : 'saida',
                        'origem' => 'banco',
                        'conta_bancaria_id' => $contaBancariaId,
                        'payload_original' => $data['raw']
                    ]
                );
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Sincronizar transações do Mercado Pago via API
     */
    public function sincronizarMercadoPago(string $startDate, string $endDate)
    {
        $accessToken = config('services.mercadopago.access_token');
        
        // Format date properly for MP API (ISO 8601 with timezone)
        $beginDate = Carbon::parse($startDate)->startOfDay()->format('Y-m-d\TH:i:s.000P');
        $endDate = Carbon::parse($endDate)->endOfDay()->format('Y-m-d\TH:i:s.000P');
        
        // Added sort=date_created and criteria=desc to get latest first
        $url = "https://api.mercadopago.com/v1/payments/search?range=date_created&begin_date={$beginDate}&end_date={$endDate}&sort=date_created&criteria=desc&limit=100&offset=0";
        
        Log::info('Mercado Pago attempt', [
            'url' => $url,
            'token_prefix' => substr($accessToken, 0, 8) . '...'
        ]);

        $response = Http::withoutVerifying()->withToken($accessToken)->get($url);

        Log::info('Mercado Pago response', [
            'status' => $response->status(),
            'body_preview' => substr($response->body(), 0, 200)
        ]);

        if (!$response->successful()) {
            Log::error('Mercado Pago API error', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            throw new \Exception("Erro ao buscar dados do Mercado Pago (Status {$response->status()}): " . $response->body());
        }

        $data = $response->json();
        $results = $data['results'] ?? [];
        
        Log::info('Mercado Pago sync details', [
            'total_found_in_range' => $data['paging']['total'] ?? 0,
            'count_this_page' => count($results)
        ]);
        
        $count = $this->processarPayments($results);
        
        return $count;
    }
    
    private function processarPayments(array $payments): int
    {
        $count = 0;
        foreach ($payments as $payment) {
            // Aceitar pagamentos approved ou accredited
            $statusValidos = ['approved', 'accredited'];
            if (!in_array($payment['status'], $statusValidos)) continue;

            // Extrair e calcular taxas
            $valorBruto = (float) $payment['transaction_amount'];
            $valorTaxa = 0;
            
            if (isset($payment['fee_details']) && is_array($payment['fee_details'])) {
                foreach ($payment['fee_details'] as $fee) {
                    $valorTaxa += (float) ($fee['amount'] ?? 0);
                }
            }
            
            $valorLiquido = $valorBruto - $valorTaxa;

            $transacao = TransacaoExtrato::updateOrCreate(
                ['fitid' => (string) $payment['id']],
                [
                    'data' => Carbon::parse($payment['date_created'])->toDateString(),
                    'descricao' => $payment['description'] ?? 'Pagamento Mercado Pago',
                    'valor' => $valorBruto, // Mantemos o bruto no campo principal para compatibilidade com a conciliação do pedido
                    'valor_bruto' => $valorBruto,
                    'valor_taxa' => $valorTaxa,
                    'valor_liquido' => $valorLiquido,
                    'tipo' => 'entrada', // Pagamento recebido
                    'origem' => 'mercadopago',
                    'payload_original' => $payment
                ]
            );

            // Auto-conciliação: Se o MP enviou o ID do pedido no external_reference
            $pedidoId = $payment['external_reference'] ?? null;
            if ($pedidoId && is_numeric($pedidoId)) {
                $pedido = \App\Models\Pedido::find($pedidoId);
                // Se o pedido existe e já está aprovado no sistema, podemos tentar auto-conciliar
                if ($pedido && $pedido->status_pagamento === 'aprovado' && $transacao->status === 'pendente') {
                    $this->autoConciliarPedido($transacao, $pedido);
                }
            }

            $count++;
        }

        return $count;
    }

    /**
     * Tenta conciliar automaticamente uma transação com um pedido já aprovado
     */
    private function autoConciliarPedido(TransacaoExtrato $transacao, \App\Models\Pedido $pedido)
    {
        try {
            // 1. Localizar o Lançamento do pedido
            $lancamento = \App\Models\Lancamento::where('referencia_tipo', 'pedido')
                ->where('referencia_id', $pedido->id)
                ->first();

            if (!$lancamento) return;

            // 2. Verificar se já existe uma movimentação para este lançamento com este valor
            $movimentacao = $lancamento->movimentacoes()
                ->where('valor_pago', $transacao->valor_bruto ?? $transacao->valor)
                ->whereDoesntHave('transacaoExtrato') // Que ainda não esteja vinculada
                ->first();

            if (!$movimentacao) {
                // Se não existe a movimentação (baixa), criamos uma agora
                $movimentacao = \App\Models\Movimentacao::create([
                    'lancamento_id' => $lancamento->id,
                    'conta_bancaria_id' => 1, // Default para Mercado Pago
                    'data_pagamento' => $transacao->data,
                    'valor_pago' => $transacao->valor_bruto ?? $transacao->valor,
                    'forma_pagamento' => 'pix', // Assumindo pix para MP moderno
                ]);
            }

            // 3. Vincular a transação do extrato à movimentação
            $transacao->update([
                'status' => 'conciliado',
                'movimentacao_id' => $movimentacao->id
            ]);

            // 4. Registrar a Despesa da Taxa (se houver)
            if ($transacao->valor_taxa > 0) {
                $this->registrarTaxaMercadoPago($transacao);
            }

            Log::info("Auto-conciliação realizada: Pedido #{$pedido->id} -> Transação {$transacao->fitid}");

        } catch (\Exception $e) {
            Log::error("Erro na auto-conciliação do Pedido #{$pedido->id}: " . $e->getMessage());
        }
    }

    /**
     * Vincular uma transação do extrato a um lançamento existente
     */
    public function vincular(int $transacaoId, int $lancamentoId, string $formaPagamento = 'transferencia')
    {
        return \DB::transaction(function () use ($transacaoId, $lancamentoId, $formaPagamento) {
            $transacao = TransacaoExtrato::findOrFail($transacaoId);
            $lancamento = Lancamento::findOrFail($lancamentoId);

            // 1. Criar a Movimentação (Baixa) para o valor bruto (Total do Pedido/Lançamento)
            $movimentacao = \App\Models\Movimentacao::create([
                'lancamento_id' => $lancamento->id,
                'conta_bancaria_id' => $transacao->conta_bancaria_id ?? 1, // Default para 1 se nulo
                'data_pagamento' => $transacao->data,
                'valor_pago' => $transacao->valor_bruto ?? $transacao->valor,
                'forma_pagamento' => $this->mapFormaPagamento($transacao->origem, $formaPagamento),
            ]);

            // 2. Atualizar Status do Lançamento
            $valorTotalPago = $lancamento->movimentacoes()->sum('valor_pago');
            if ($valorTotalPago >= $lancamento->valor_total) {
                $lancamento->update(['status' => 'pago']);
            } else {
                $lancamento->update(['status' => 'pago_parcial']);
            }

            // 3. Atualizar Status da Transação do Extrato
            $transacao->update([
                'status' => 'conciliado',
                'movimentacao_id' => $movimentacao->id
            ]);

            // 4. Registrar a Despesa da Taxa (se houver)
            if ($transacao->valor_taxa > 0) {
                $this->registrarTaxaMercadoPago($transacao);
            }

            return $movimentacao;
        });
    }

    /**
     * Registra automaticamente uma despesa referente à taxa do Mercado Pago
     */
    private function registrarTaxaMercadoPago(TransacaoExtrato $transacao)
    {
        // Tenta encontrar uma classificação financeira para taxas
        $classificacao = \App\Models\ClassificacaoFinanceira::where('nome', 'like', '%Taxa%')
            ->orWhere('nome', 'like', '%Tarifa%')
            ->where('tipo_natureza', 'despesa')
            ->first();

        // Se não encontrar, cria uma padrão
        if (!$classificacao) {
            $classificacao = \App\Models\ClassificacaoFinanceira::create([
                'nome' => 'Taxas e Tarifas Bancárias',
                'tipo_natureza' => 'despesa',
                'descricao' => 'Taxas cobradas por processadores de pagamento como Mercado Pago'
            ]);
        }

        // Criar o Lançamento de Despesa para a Taxa
        $lancamentoTaxa = \App\Models\Lancamento::create([
            'tipo' => 'despesa',
            'status' => 'pago',
            'pessoa_id' => 1, // Pode ser o Mercado Pago se houver um cadastro específico
            'classificacao_financeira_id' => $classificacao->id,
            'data_emissao' => $transacao->data,
            'data_vencimento' => $transacao->data,
            'valor_total' => $transacao->valor_taxa,
            'descricao' => 'Taxa Mercado Pago - Ref. Transação ' . $transacao->fitid,
        ]);

        // Criar a Movimentação (Baixa) da Taxa
        \App\Models\Movimentacao::create([
            'lancamento_id' => $lancamentoTaxa->id,
            'conta_bancaria_id' => $transacao->conta_bancaria_id ?? 1,
            'data_pagamento' => $transacao->data,
            'valor_pago' => $transacao->valor_taxa,
            'forma_pagamento' => 'pix',
        ]);
    }

    /**
     * Criar um lançamento rápido e já vincular à transação
     */
    public function vincularNovoLancamento(int $transacaoId, int $classificacaoId, ?int $pessoaId = null, ?int $contaBancariaId = null)
    {
        return \DB::transaction(function () use ($transacaoId, $classificacaoId, $pessoaId, $contaBancariaId) {
            $transacao = TransacaoExtrato::findOrFail($transacaoId);

            // Atualizar conta bancária se fornecida
            if ($contaBancariaId) {
                $transacao->update(['conta_bancaria_id' => $contaBancariaId]);
            }

            // Buscar cliente do pedido se existir
            $pessoaIdFinal = $pessoaId;
            if (!$pessoaIdFinal && $transacao->origem === 'mercadopago') {
                $pedidoId = $transacao->getPedidoId();
                if ($pedidoId) {
                    $pedido = \App\Models\Pedido::find($pedidoId);
                    if ($pedido && $pedido->cliente_id) {
                        $pessoaIdFinal = $pedido->cliente_id;
                        // Atualizar descrição com info do pedido
                        $transacao->descricao = 'Pedido #' . $pedido->id . ' - ' . ($pedido->cliente?->nome ?? $transacao->descricao);
                        $transacao->save();
                    }
                }
            }

            // Criar o Lançamento
            $lancamento = Lancamento::create([
                'tipo' => $transacao->tipo === 'entrada' ? 'receita' : 'despesa',
                'status' => 'pendente',
                'pessoa_id' => $pessoaIdFinal ?? 1,
                'classificacao_financeira_id' => $classificacaoId,
                'data_emissao' => $transacao->data,
                'data_vencimento' => $transacao->data,
                'valor_total' => $transacao->valor,
                'descricao' => $transacao->descricao,
            ]);

            return $this->vincular($transacao->id, $lancamento->id);
        });
    }

    /**
     * Mapeia a origem para uma forma de pagamento do sistema
     */
    private function mapFormaPagamento(string $origem, string $manual)
    {
        if ($origem === 'mercadopago') return 'pix';
        return $manual;
    }

    /**
     * Parser manual simples para campos OFX
     */
    private function parseOfxTransaction($trn)
    {
        $fields = [
            'TRNTYPE' => '/<TRNTYPE>(.*)/',
            'DTPOSTED' => '/<DTPOSTED>(.*)/',
            'TRNAMT' => '/<TRNAMT>(.*)/',
            'FITID' => '/<FITID>(.*)/',
            'MEMO' => '/<MEMO>(.*)/'
        ];

        $data = [];
        foreach ($fields as $key => $pattern) {
            if (preg_match($pattern, $trn, $match)) {
                $data[$key] = trim($match[1]);
            }
        }

        if (!isset($data['FITID'])) return null;

        // Formatar data (YYYYMMDD...)
        $dateStr = substr($data['DTPOSTED'] ?? '', 0, 8);
        $date = Carbon::createFromFormat('Ymd', $dateStr)->toDateString();

        return [
            'fitid' => $data['FITID'],
            'data' => $date,
            'descricao' => $data['MEMO'] ?? 'Transação sem descrição',
            'valor' => (float) ($data['TRNAMT'] ?? 0),
            'raw' => $data
        ];
    }
}

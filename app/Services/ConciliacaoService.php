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
        $seenFitids = [];
        foreach ($matches[1] as $trn) {
            $data = $this->parseOfxTransaction($trn);
            
            if ($data) {
                $baseFitid = $data['fitid'];
                if (!isset($seenFitids[$baseFitid])) {
                    $seenFitids[$baseFitid] = 0;
                    $fitid = $baseFitid;
                } else {
                    $seenFitids[$baseFitid]++;
                    $fitid = $baseFitid . '_' . $seenFitids[$baseFitid];
                }

                TransacaoExtrato::updateOrCreate(
                    ['fitid' => $fitid],
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
        
        try {
            $this->autoConciliarTransacoesPendentes($contaBancariaId);
        } catch (\Exception $e) {
            Log::error("Erro na auto-conciliação pós-OFX: " . $e->getMessage());
        }
        
        return $count;
    }

    /**
     * Importar transações de um arquivo CSV do Mercado Pago enviado manualmente
     */
    public function importarCsvMercadoPago(UploadedFile $file): int
    {
        $content = file_get_contents($file->getRealPath());
        return $this->processarCsvRelatorio($content);
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

        // Sincronizar relatórios de extrato completo (bank_report: entradas/tarifas)
        $countReports = $this->sincronizarRelatoriosMercadoPago($startDate, $endDate);

        // Sincronizar Account Money Report (settlement_report: saídas, compras ML, transferências)
        $countMoney = $this->sincronizarAccountMoneyReport($startDate, $endDate);

        Log::info('Mercado Pago full sync completed', [
            'payments'       => $count,
            'bank_reports'   => $countReports,
            'money_reports'  => $countMoney,
        ]);
        
        try {
            $this->autoConciliarTransacoesPendentes();
        } catch (\Exception $e) {
            Log::error("Erro na auto-conciliação pós-sincronização MP: " . $e->getMessage());
        }
        
        return $count + $countReports + $countMoney;
    }
    
    private function processarPayments(array $payments): int
    {
        $count = 0;
        $contaMp = \App\Models\ContaBancaria::where('nome', 'like', '%Mercado Pago%')->first();
        $contaBancariaId = $contaMp ? $contaMp->id : 2;

        foreach ($payments as $payment) {
            $status       = $payment['status'] ?? '';
            $paymentType  = $payment['payment_type_id'] ?? '';
            $operationType = $payment['operation_type'] ?? '';

            /*
             * Regras de inclusão:
             *  - 'approved' ou 'accredited' = recebimentos de clientes (entradas)
             *  - 'authorized' + type='account_money' = pagamentos feitos PELO usuário
             *    (compras em estabelecimentos via saldo MP) → saídas
             */
            $isEntrada = in_array($status, ['approved', 'accredited']);
            $isSaida   = ($status === 'authorized' && $paymentType === 'account_money');

            if (!$isEntrada && !$isSaida) {
                continue;
            }

            $tipo        = $isSaida ? 'saida' : 'entrada';
            $valorBruto  = (float) $payment['transaction_amount'];
            $valorTaxa   = 0;

            if (isset($payment['fee_details']) && is_array($payment['fee_details'])) {
                foreach ($payment['fee_details'] as $fee) {
                    $valorTaxa += (float) ($fee['amount'] ?? 0);
                }
            }

            $valorLiquido = $valorBruto - $valorTaxa;

            // Descrição: usa o nome do estabelecimento / beneficiário quando disponível
            $descricao = $payment['description']
                ?? $payment['additional_info']['items'][0]['title']
                ?? ($isSaida ? 'Pagamento via Mercado Pago' : 'Recebimento Mercado Pago');

            $transacao = TransacaoExtrato::updateOrCreate(
                ['fitid' => (string) $payment['id']],
                [
                    'data'             => Carbon::parse($payment['date_created'])->toDateString(),
                    'descricao'        => $descricao,
                    'valor'            => $valorBruto,
                    'valor_bruto'      => $valorBruto,
                    'valor_taxa'       => $valorTaxa,
                    'valor_liquido'    => $valorLiquido,
                    'tipo'             => $tipo,
                    'origem'           => 'mercadopago',
                    'conta_bancaria_id'=> $contaBancariaId,
                    'payload_original' => $payment,
                ]
            );

            // Auto-conciliação apenas para entradas com external_reference de pedido
            if ($isEntrada) {
                $pedidoId = $payment['external_reference'] ?? null;
                if ($pedidoId && is_numeric($pedidoId)) {
                    $pedido = \App\Models\Pedido::find($pedidoId);
                    if ($pedido && $transacao->status === 'pendente') {
                        if ($pedido->status_pagamento !== 'aprovado') {
                            $pedido->status_pagamento = 'aprovado';
                            $pedido->status_pedido    = 'pago';
                            $pedido->save();
                        }
                        $this->autoConciliarPedido($transacao, $pedido);
                    }
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
            \DB::transaction(function () use ($transacao, $pedido) {
                // Bloqueia a linha da transação para evitar conciliação concorrente
                $transacaoLock = TransacaoExtrato::lockForUpdate()->find($transacao->id);
                if (!$transacaoLock || $transacaoLock->status === 'conciliado') {
                    return;
                }

                // 1. Localizar o Lançamento do pedido
                $lancamento = \App\Models\Lancamento::where('referencia_tipo', 'pedido')
                    ->where('referencia_id', $pedido->id)
                    ->first();

                if (!$lancamento) return;

                $movimentacao = $lancamento->movimentacoes()
                    ->where('valor_pago', $transacaoLock->valor_bruto ?? $transacaoLock->valor)
                    ->whereDoesntHave('transacaoExtrato') // Que ainda não esteja vinculada
                    ->first();

                if (!$movimentacao) {
                    // Buscar dinamicamente a conta Mercado Pago
                    $contaMp = \App\Models\ContaBancaria::where('nome', 'like', '%Mercado Pago%')->first();
                    $contaBancariaId = $contaMp ? $contaMp->id : 2;

                    // Se não existe a movimentação (baixa), criamos uma agora
                    $movimentacao = \App\Models\Movimentacao::create([
                        'lancamento_id' => $lancamento->id,
                        'conta_bancaria_id' => $contaBancariaId, 
                        'data_pagamento' => $transacaoLock->data,
                        'valor_pago' => $transacaoLock->valor_bruto ?? $transacaoLock->valor,
                        'forma_pagamento' => 'pix', // Assumindo pix para MP moderno
                    ]);
                }

                // 3. Vincular a transação do extrato à movimentação
                $transacaoLock->update([
                    'status' => 'conciliado',
                    'movimentacao_id' => $movimentacao->id
                ]);

                // Atualizar o estado da transação na memória para evitar processamento em loops externos
                $transacao->status = 'conciliado';
                $transacao->movimentacao_id = $movimentacao->id;

                // 4. Registrar a Despesa da Taxa (se houver)
                if ($transacaoLock->valor_taxa > 0) {
                    $this->registrarTaxaMercadoPago($transacaoLock);
                }
            });

            Log::info("Auto-conciliação realizada: Pedido #{$pedido->id} -> Transação {$transacao->fitid}");

        } catch (\Exception $e) {
            Log::error("Erro na auto-conciliação do Pedido #{$pedido->id}: " . $e->getMessage());
        }
    }

    public function vincular(int $transacaoId, int $lancamentoId, string $formaPagamento = 'transferencia')
    {
        return \DB::transaction(function () use ($transacaoId, $lancamentoId, $formaPagamento) {
            // Bloqueia a linha da transação para evitar conciliação concorrente
            $transacao = TransacaoExtrato::lockForUpdate()->findOrFail($transacaoId);
            if ($transacao->status === 'conciliado') {
                throw new \Exception("Esta transação já foi conciliada.");
            }

            $lancamento = Lancamento::findOrFail($lancamentoId);

            $defaultContaId = 1;
            if ($transacao->origem === 'mercadopago') {
                $contaMp = \App\Models\ContaBancaria::where('nome', 'like', '%Mercado Pago%')->first();
                $defaultContaId = $contaMp ? $contaMp->id : 2;
            }

            $valorDesejado = $transacao->valor_bruto ?? $transacao->valor;

            // 1. Procurar Movimentação (Baixa) existente para o mesmo valor que ainda não esteja vinculada
            $movimentacao = $lancamento->movimentacoes()
                ->where('valor_pago', $valorDesejado)
                ->whereDoesntHave('transacaoExtrato')
                ->first();

            if (!$movimentacao) {
                // Criar nova movimentação apenas se não existir uma correspondente
                $movimentacao = \App\Models\Movimentacao::create([
                    'lancamento_id' => $lancamento->id,
                    'conta_bancaria_id' => $transacao->conta_bancaria_id ?? $defaultContaId, 
                    'data_pagamento' => $transacao->data,
                    'valor_pago' => $valorDesejado,
                    'forma_pagamento' => $this->mapFormaPagamento($transacao->origem, $formaPagamento),
                    'transacao_extrato_id' => $transacao->id,
                ]);
            } else {
                // Se já existe, vincula a transação do extrato a ela
                $movimentacao->update([
                    'transacao_extrato_id' => $transacao->id,
                    'conta_bancaria_id' => $transacao->conta_bancaria_id ?? $defaultContaId,
                ]);
            }

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
     * Vincular uma transação do extrato a múltiplos lançamentos com valores específicos
     */
    public function vincularMultiplos(int $transacaoId, array $lancamentosDados, string $formaPagamento = 'transferencia')
    {
        return \DB::transaction(function () use ($transacaoId, $lancamentosDados, $formaPagamento) {
            // Bloqueia a linha da transação para evitar conciliação concorrente
            $transacao = TransacaoExtrato::lockForUpdate()->findOrFail($transacaoId);
            if ($transacao->status === 'conciliado') {
                throw new \Exception("Esta transação já foi conciliada.");
            }

            $defaultContaId = 1;
            if ($transacao->origem === 'mercadopago') {
                $contaMp = \App\Models\ContaBancaria::where('nome', 'like', '%Mercado Pago%')->first();
                $defaultContaId = $contaMp ? $contaMp->id : 2;
            }

            $movimentacoesCriadas = [];
            $totalVinculado = 0;

            foreach ($lancamentosDados as $dado) {
                $lancamentoId = $dado['lancamento_id'];
                $valorVinculo = (float) $dado['valor_vinculo'];

                if ($valorVinculo <= 0) {
                    continue;
                }

                $lancamento = Lancamento::findOrFail($lancamentoId);

                // 1. Criar a Movimentação (Baixa) com o valor específico do vínculo
                $movimentacao = \App\Models\Movimentacao::create([
                    'lancamento_id' => $lancamento->id,
                    'conta_bancaria_id' => $transacao->conta_bancaria_id ?? $defaultContaId, 
                    'data_pagamento' => $transacao->data,
                    'valor_pago' => $valorVinculo,
                    'forma_pagamento' => $this->mapFormaPagamento($transacao->origem, $formaPagamento),
                    'transacao_extrato_id' => $transacao->id,
                ]);

                // 2. Atualizar Status do Lançamento
                $valorTotalPago = $lancamento->movimentacoes()->sum('valor_pago');
                if ($valorTotalPago >= $lancamento->valor_total) {
                    $lancamento->update(['status' => 'pago']);
                } else {
                    $lancamento->update(['status' => 'pago_parcial']);
                }

                $movimentacoesCriadas[] = $movimentacao;
                $totalVinculado += $valorVinculo;
            }

            // 3. Atualizar Status da Transação do Extrato
            // Nota: Para manter compatibilidade com o banco, salvamos o ID da primeira movimentação
            $primeiraMovId = count($movimentacoesCriadas) > 0 ? $movimentacoesCriadas[0]->id : null;
            $transacao->update([
                'status' => 'conciliado',
                'movimentacao_id' => $primeiraMovId
            ]);

            // 4. Registrar a Despesa da Taxa (se houver e origem for MP)
            if ($transacao->valor_taxa > 0) {
                $this->registrarTaxaMercadoPago($transacao);
            }

            return $movimentacoesCriadas;
        });
    }

    /**
     * Registra automaticamente uma despesa referente à taxa do Mercado Pago
     */
    private function registrarTaxaMercadoPago(TransacaoExtrato $transacao)
    {
        // Tenta encontrar uma classificação financeira para taxas (Despesas Financeiras analítica)
        $classificacao = \App\Models\ClassificacaoFinanceira::where(function ($query) {
                $query->where('nome', 'like', '%Taxa%')
                      ->orWhere('nome', 'like', '%Tarifa%')
                      ->orWhere('nome', 'like', '%Despesas Financeiras%');
            })
            ->where('tipo_natureza', 'despesa')
            ->where('nivel', 'analitico')
            ->first();

        // Se não encontrar, cria uma padrão com todos os campos obrigatórios
        if (!$classificacao) {
            $userId = \Illuminate\Support\Facades\Auth::id() ?? 2;
            
            // Tenta encontrar a despesa financeira sintética para ser o pai
            $pai = \App\Models\ClassificacaoFinanceira::where('nome', 'like', '%Despesas Financeiras%')
                ->where('nivel', 'sintetico')
                ->first();
                
            $idPai = $pai ? $pai->id : 12; // fallback para 12
            $codigoPai = $pai ? $pai->codigo_contabil : '2.06';
            
            // Acha o maior código filho atual
            $ultimoFilho = \App\Models\ClassificacaoFinanceira::where('id_pai', $idPai)
                ->orderBy('codigo_contabil', 'desc')
                ->first();
                
            if ($ultimoFilho) {
                $partes = explode('.', $ultimoFilho->codigo_contabil);
                $ultimaParte = (int) end($partes);
                $novaParte = str_pad($ultimaParte + 1, 2, '0', STR_PAD_LEFT);
                $novoCodigo = $codigoPai . '.' . $novaParte;
            } else {
                $novoCodigo = $codigoPai . '.01';
            }

            $classificacao = \App\Models\ClassificacaoFinanceira::create([
                'user_id' => $userId,
                'nome' => 'Taxas e Tarifas Bancárias',
                'codigo_contabil' => $novoCodigo,
                'tipo_natureza' => 'despesa',
                'nivel' => 'analitico',
                'id_pai' => $idPai,
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

        $defaultContaId = 1;
        if ($transacao->origem === 'mercadopago') {
            $contaMp = \App\Models\ContaBancaria::where('nome', 'like', '%Mercado Pago%')->first();
            $defaultContaId = $contaMp ? $contaMp->id : 2;
        }

        // Criar a Movimentação (Baixa) da Taxa
        \App\Models\Movimentacao::create([
            'lancamento_id' => $lancamentoTaxa->id,
            'conta_bancaria_id' => $transacao->conta_bancaria_id ?? $defaultContaId,
            'data_pagamento' => $transacao->data,
            'valor_pago' => $transacao->valor_taxa,
            'forma_pagamento' => 'pix',
        ]);
    }

    /**
     * Criar um lançamento rápido e já vincular à transação
     */
    public function vincularNovoLancamento(int $transacaoId, int $classificacaoId, ?int $pessoaId = null, ?int $contaBancariaId = null, ?string $observacoes = null)
    {
        return \DB::transaction(function () use ($transacaoId, $classificacaoId, $pessoaId, $contaBancariaId, $observacoes) {
            // Bloqueia a linha da transação para evitar conciliação concorrente
            $transacao = TransacaoExtrato::lockForUpdate()->findOrFail($transacaoId);
            if ($transacao->status === 'conciliado') {
                throw new \Exception("Esta transação já foi conciliada.");
            }

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
                'pessoa_id' => $pessoaIdFinal,
                'classificacao_financeira_id' => $classificacaoId,
                'data_emissao' => $transacao->data,
                'data_vencimento' => $transacao->data,
                'valor_total' => $transacao->valor,
                'descricao' => $transacao->descricao,
                'observacoes' => $observacoes,
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

    /**
     * Sincronizar relatórios de extrato completo (incluindo saídas/tarifas) do Mercado Pago
     */
    public function sincronizarRelatoriosMercadoPago(string $startDate, string $endDate): int
    {
        $accessToken = config('services.mercadopago.access_token');
        if (empty($accessToken)) {
            Log::warning('Token do Mercado Pago não configurado. Sincronização de relatórios ignorada.');
            return 0;
        }

        $count = 0;

        try {
            // 1. Listar relatórios existentes (Relatórios de Liquidação / Settlement Report & Bank Report)
            $listUrls = [
                "https://api.mercadopago.com/v1/account/settlement_report/list",
                "https://api.mercadopago.com/v1/account/bank_report/list"
            ];
            
            $reports = [];
            foreach ($listUrls as $listUrl) {
                $response = Http::withoutVerifying()->withToken($accessToken)->get($listUrl);

                if ($response->status() === 404 && str_contains($response->body(), 'config_not_found_for_user')) {
                    Log::info('Configuração de relatórios de liberação não encontrada no Mercado Pago. Criando...');
                    $this->criarConfiguracaoRelatorioMP($accessToken);
                    $response = Http::withoutVerifying()->withToken($accessToken)->get($listUrl);
                }

                if ($response->successful() && is_array($response->json())) {
                    $reports = array_merge($reports, $response->json());
                }
            }

            if (!empty($reports)) {
                $reqStart = Carbon::parse($startDate)->startOfDay();
                $reqEnd = Carbon::parse($endDate)->endOfDay();

                $processedReports = array_filter($reports, function ($report) use ($reqStart, $reqEnd) {
                    if (!isset($report['status']) || !in_array($report['status'], ['processed', 'enabled']) || !isset($report['file_name'])) {
                        return false;
                    }
                    
                    $repStart = isset($report['begin_date']) ? Carbon::parse($report['begin_date']) : null;
                    $repEnd = isset($report['end_date']) ? Carbon::parse($report['end_date']) : null;
                    
                    if ($repStart && $repEnd) {
                        return $repStart->lte($reqEnd) && $repEnd->gte($reqStart);
                    }
                    
                    return true;
                });

                usort($processedReports, function ($a, $b) {
                    return strcmp($b['date_created'] ?? '', $a['date_created'] ?? '');
                });

                $processedReports = array_slice($processedReports, 0, 10);

                foreach ($processedReports as $report) {
                    $fileName = $report['file_name'];
                    Log::info("Processando relatório de extrato MP: {$fileName}");
                    
                    $downloadUrls = [
                        "https://api.mercadopago.com/v1/account/settlement_report/{$fileName}",
                        "https://api.mercadopago.com/v1/account/bank_report/{$fileName}"
                    ];

                    foreach ($downloadUrls as $downloadUrl) {
                        $downloadResponse = Http::withoutVerifying()->withToken($accessToken)->get($downloadUrl);
                        if ($downloadResponse->successful()) {
                            $csvContent = $downloadResponse->body();
                            $count += $this->processarCsvRelatorio($csvContent);
                            break;
                        }
                    }
                }
            }

            // 2. Sincronizar pagamentos em tempo real via API /v1/payments/search (para transações do dia / instantâneas)
            try {
                $searchUrl = "https://api.mercadopago.com/v1/payments/search?sort=date_created&criteria=desc&limit=100";
                $searchResponse = Http::withoutVerifying()->withToken($accessToken)->get($searchUrl);

                if ($searchResponse->successful()) {
                    $results = $searchResponse->json('results') ?? [];
                    $contaMp = \App\Models\ContaBancaria::where('nome', 'like', '%Mercado%Pago%')->first();
                    $contaMpId = $contaMp ? $contaMp->id : 2;

                    foreach ($results as $p) {
                        $status = $p['status'] ?? '';
                        if (!in_array($status, ['approved', 'authorized'])) {
                            continue;
                        }

                        $idStr = (string) ($p['id'] ?? '');
                        if (empty($idStr)) continue;

                        $dateCreated = isset($p['date_created']) ? Carbon::parse($p['date_created'])->toDateString() : now()->toDateString();
                        $amount = (float) ($p['transaction_amount'] ?? 0);
                        if ($amount <= 0) continue;

                        $desc = $p['description'] ?? '';
                        if (empty($desc)) {
                            $desc = $p['statement_descriptor'] ?? '';
                        }
                        if (empty($desc)) {
                            $desc = $p['point_of_interaction']['transaction_data']['bank_info']['collector']['account_holder_name'] ?? '';
                        }
                        if (empty($desc)) {
                            $desc = 'Pagamento Mercado Pago';
                        }

                        $netReceived = $p['transaction_details']['net_received_amount'] ?? $amount;
                        $tipo = 'entrada';
                        if ($netReceived < 0 || str_contains(strtolower($desc), 'ifood') || str_contains(strtolower($desc), 'supermercado') || str_contains(strtolower($desc), 'farmacia')) {
                            $tipo = 'saida';
                        }

                        $exists = TransacaoExtrato::where('fitid', $idStr)->exists();
                        if (!$exists) {
                            TransacaoExtrato::create([
                                'fitid' => $idStr,
                                'data' => $dateCreated,
                                'descricao' => $desc,
                                'valor_bruto' => $amount,
                                'valor_taxa' => 0.00,
                                'valor_liquido' => $amount,
                                'valor' => $amount,
                                'tipo' => $tipo,
                                'status' => 'pendente',
                                'origem' => 'mercadopago',
                                'conta_bancaria_id' => $contaMpId,
                                'payload_original' => json_encode($p),
                            ]);
                            $count++;
                        }
                    }
                }
            } catch (\Exception $eEx) {
                Log::warning("Aviso na busca em tempo real de pagamentos MP: " . $eEx->getMessage());
            }

            // 3. Solicitar a geração de um novo relatório de liberação para o período (processamento assíncrono)
            $beginDate = Carbon::parse($startDate)->startOfDay()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z');
            $endDate = Carbon::parse($endDate)->endOfDay()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z');

            $generateUrl = "https://api.mercadopago.com/v1/account/release_report";
            $generateResponse = Http::withoutVerifying()
                ->withToken($accessToken)
                ->post($generateUrl, [
                    'begin_date' => $beginDate,
                    'end_date' => $endDate
                ]);

            if ($generateResponse->successful()) {
                Log::info("Novo relatório de liberação solicitado com sucesso ao MP para o período {$startDate} a {$endDate}.");
            } else {
                Log::info("Aviso ao solicitar novo relatório de liberação MP (pode já existir uma solicitação em andamento): " . $generateResponse->body());
            }

        } catch (\Exception $e) {
            Log::error("Erro ao processar relatórios de liberação do Mercado Pago: " . $e->getMessage());
        }

        return $count;
    }

    /**
     * Processa o conteúdo CSV de um relatório de liberação do Mercado Pago
     */
    private function processarCsvRelatorio(string $csvContent): int
    {
        // Converter codificação para UTF-8 se necessário
        $encoding = mb_detect_encoding($csvContent, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $csvContent = mb_convert_encoding($csvContent, 'UTF-8', $encoding);
        }

        $lines = explode("\n", str_replace("\r", "", $csvContent));
        if (count($lines) < 2) {
            return 0;
        }

        // 1. Procurar a linha de cabeçalho varrendo as primeiras 30 linhas
        $headerLineIndex = -1;
        $headers = [];
        $delimiter = ',';
        
        $maxHeaderScan = min(count($lines), 30);
        for ($i = 0; $i < $maxHeaderScan; $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;
            
            // Auto-detectar delimitador (, ou ;)
            $delim = ',';
            if (strpos($line, ';') !== false && strpos($line, ',') === false) {
                $delim = ';';
            } else if (strpos($line, ';') !== false && strpos($line, ',') !== false) {
                $delim = substr_count($line, ';') > substr_count($line, ',') ? ';' : ',';
            }
            
            $cols = str_getcsv($line, $delim);
            $colsLower = array_map(function ($h) {
                return trim(mb_strtolower(str_replace(['"', "'"], '', $h), 'UTF-8'));
            }, $cols);
            
            // Candidatos a cabeçalho da transação: deve conter alguma coluna identificadora de ID
            $hasId = false;
            $idCandidates = [
                'source_id', 'id', 'operation_id', 'transaction_id', 'operacion_id', 
                'id_transacao', 'id_transação', 'id da transacao', 'id da transação', 
                'payment_id', 'payment', 'transacao_id', 'transação_id', 'reference_id',
                'reference', 'referencia', 'referência'
            ];
            foreach ($idCandidates as $cand) {
                if (in_array($cand, $colsLower)) {
                    $hasId = true;
                    break;
                }
            }
            
            // E deve conter alguma coluna de data ou valor
            $hasDateOrVal = false;
            $dateValCandidates = [
                'date', 'generation_date', 'data', 'fecha_liberacion', 'date_released', 
                'date_created', 'date_approved', 'fecha', 'data_liberacao', 'data_liberação',
                'release_date', 'net_credit_amount', 'net_debit_amount', 'amount', 'valor',
                'valor_liquido', 'net_received_amount', 'valor_liquido_recebido', 'valor_recebido',
                'transaction_net_amount', 'net_amount', 'settlement_net_amount'
            ];
            foreach ($dateValCandidates as $cand) {
                if (in_array($cand, $colsLower)) {
                    $hasDateOrVal = true;
                    break;
                }
            }
            
            if ($hasId && $hasDateOrVal) {
                $headerLineIndex = $i;
                $headers = $colsLower;
                $delimiter = $delim;
                break;
            }
        }

        // Se colunas fundamentais não forem encontradas, abortar processamento
        if ($headerLineIndex === -1) {
            $preview = array_slice($lines, 0, 10);
            Log::error('Cabeçalho do CSV do Mercado Pago inválido. Colunas cruciais não encontradas.', [
                'first_lines_preview' => $preview
            ]);
            return 0;
        }

        // Mapeamento de colunas importantes baseado no cabeçalho encontrado
        $colIndex = [
            'date' => $this->findHeaderIndex($headers, [
                'date', 'generation_date', 'data', 'fecha_liberacion', 'date_released', 
                'date_created', 'date_approved', 'fecha', 'data_liberacao', 'data_liberação',
                'release_date'
            ]),
            'source_id' => $this->findHeaderIndex($headers, [
                'source_id', 'id', 'operation_id', 'transaction_id', 'operacion_id', 
                'id_transacao', 'id_transação', 'id da transacao', 'id da transação', 
                'payment_id', 'payment', 'transacao_id', 'transação_id', 'reference_id',
                'reference', 'referencia', 'referência'
            ]),
            'description' => $this->findHeaderIndex($headers, [
                'description', 'descricao', 'descrição', 'concept', 'concepto', 'detail', 
                'record_type', 'concept_desc', 'motivo', 'tipo_movimentacao', 'tipo_movimentação',
                'transaction_type', 'tipo_transacao', 'tipo_transação'
            ]),
            'net_credit' => $this->findHeaderIndex($headers, [
                'net_credit_amount', 'net_credit', 'credit', 'credito', 'crédito', 
                'amount_credited', 'valor_bruto', 'transaction_amount'
            ]),
            'net_debit' => $this->findHeaderIndex($headers, [
                'net_debit_amount', 'net_debit', 'debit', 'debito', 'débito', 'amount_debited'
            ]),
            'value_signed' => $this->findHeaderIndex($headers, [
                'net_received_amount', 'valor_liquido', 'valor_liquido_recebido', 'valor_líquido', 
                'valor_líquido_recebido', 'valor', 'amount', 'net_amount', 'settlement_net_amount', 
                'valor_recebido', 'transaction_net_amount'
            ]),
            'external_reference' => $this->findHeaderIndex($headers, [
                'external_reference', 'id_externo', 'referencia_externa', 'referência_externa', 'purchase_order'
            ]),
            'saldo' => $this->findHeaderIndex($headers, [
                'saldo', 'balance', 'saldo_atual', 'saldo atual', 'saldo_total', 'saldo total', 'valor_saldo'
            ]),
        ];

        // Se por acaso as colunas principais mapeadas de data ou id forem -1, abortar
        if ($colIndex['source_id'] === -1 || $colIndex['date'] === -1) {
            Log::error('Erro ao mapear colunas essenciais após encontrar linha de cabeçalho.', [
                'headers' => $headers,
                'colIndex' => $colIndex
            ]);
            return 0;
        }

        $count = 0;
        $seenIds = [];

        // Processar registros reais a partir de $headerLineIndex + 1
        for ($i = $headerLineIndex + 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;

            $row = str_getcsv($line, $delimiter);
            if (count($row) < count($headers)) {
                continue;
            }

            $sourceId = trim($row[$colIndex['source_id']] ?? '');
            if (empty($sourceId) || !is_numeric($sourceId)) {
                continue;
            }

            $dateStr = trim($row[$colIndex['date']] ?? '');
            $description = trim($row[$colIndex['description']] ?? 'Operação Mercado Pago');
            $externalReference = $colIndex['external_reference'] !== -1 ? trim($row[$colIndex['external_reference']] ?? '') : null;

            // Ignorar lançamentos de retenção/reserva interna do Mercado Pago (ex: reserve_for_payment, reserve_for_payout)
            if (str_contains(strtolower($description), 'reserve_for_')) {
                continue;
            }

            try {
                // Tratar data no formato brasileiro DD-MM-YYYY ou DD/MM/YYYY
                if (preg_match('/^(\d{2})[\/-](\d{2})[\/-](\d{4})(.*)$/', $dateStr, $matches)) {
                    $date = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
                } else {
                    $date = Carbon::parse($dateStr)->toDateString();
                }
            } catch (\Exception $e) {
                $date = now()->toDateString();
            }

            $netCredit = $colIndex['net_credit'] !== -1 ? $this->parseMoneyValue($row[$colIndex['net_credit']] ?? '0') : 0.0;
            $netDebit = $colIndex['net_debit'] !== -1 ? $this->parseMoneyValue($row[$colIndex['net_debit']] ?? '0') : 0.0;

            $tipo = 'entrada';
            $valor = 0.0;

            if ($netDebit > 0) {
                $tipo = 'saida';
                $valor = $netDebit;
            } else if ($netCredit > 0) {
                $tipo = 'entrada';
                $valor = $netCredit;
            } else if ($colIndex['value_signed'] !== -1) {
                // Caso use coluna única de valor com sinal positivo/negativo
                $rawVal = $row[$colIndex['value_signed']] ?? '0';
                $floatVal = $this->parseMoneyValueRaw($rawVal);
                $valor = abs($floatVal);
                $tipo = $floatVal >= 0 ? 'entrada' : 'saida';
            } else {
                continue;
            }

            $contaMp = \App\Models\ContaBancaria::where('nome', 'like', '%Mercado Pago%')->first();
            $contaBancariaId = $contaMp ? $contaMp->id : 2;

            $payloadOriginal = [];
            foreach ($colIndex as $key => $index) {
                if ($index !== -1) {
                    $payloadOriginal[$key] = $row[$index] ?? null;
                }
            }
            if (!empty($externalReference)) {
                $payloadOriginal['external_reference'] = $externalReference;
            }

            if (!isset($seenIds[$sourceId])) {
                $seenIds[$sourceId] = 0;
                $fitid = (string) $sourceId;
            } else {
                $seenIds[$sourceId]++;
                $fitid = $sourceId . '_' . $seenIds[$sourceId];
            }

            $transacao = TransacaoExtrato::updateOrCreate(
                ['fitid' => (string) $fitid],
                [
                    'data' => $date,
                    'descricao' => $description,
                    'valor' => $valor,
                    'valor_bruto' => $valor,
                    'valor_taxa' => 0,
                    'valor_liquido' => $valor,
                    'tipo' => $tipo,
                    'origem' => 'mercadopago',
                    'conta_bancaria_id' => $contaBancariaId,
                    'payload_original' => $payloadOriginal
                ]
            );

            if ($tipo === 'entrada' && $externalReference && is_numeric($externalReference)) {
                $pedido = \App\Models\Pedido::find($externalReference);
                if ($pedido && $transacao->status === 'pendente') {
                    if ($pedido->status_pagamento !== 'aprovado') {
                        $pedido->status_pagamento = 'aprovado';
                        $pedido->status_pedido = 'pago';
                        $pedido->save();
                    }
                    $this->autoConciliarPedido($transacao, $pedido);
                }
            }

            $count++;
        }

        return $count;
    }

    /**
     * Auxiliar para buscar índice correspondente de cabeçalhos candidatos
     */
    private function findHeaderIndex(array $headers, array $candidates): int
    {
        foreach ($candidates as $candidate) {
            $index = array_search(strtolower($candidate), $headers);
            if ($index !== false) {
                return (int) $index;
            }
        }
        return -1;
    }

    /**
     * Auxiliar para converter valores monetários do CSV em float (preservando valor absoluto)
     */
    private function parseMoneyValue(string $val): float
    {
        $clean = preg_replace('/[^0-9\.,-]/', '', $val);
        if (empty($clean)) return 0.0;

        if (strpos($clean, ',') !== false) {
            if (strpos($clean, '.') !== false) {
                $clean = str_replace('.', '', $clean);
            }
            $clean = str_replace(',', '.', $clean);
        }

        return abs((float) $clean);
    }

    /**
     * Auxiliar para converter valores monetários preservando o sinal negativo
     */
    private function parseMoneyValueRaw(string $val): float
    {
        $clean = preg_replace('/[^0-9\.,-]/', '', $val);
        if (empty($clean)) return 0.0;

        if (strpos($clean, ',') !== false) {
            if (strpos($clean, '.') !== false) {
                $clean = str_replace('.', '', $clean);
            }
            $clean = str_replace(',', '.', $clean);
        }

        return (float) $clean;
    }

    /**
     * Cria a configuração padrão para geração de relatórios de liquidação no Mercado Pago
     */
    private function criarConfiguracaoRelatorioMP(string $accessToken): bool
    {
        try {
            $configUrl = "https://api.mercadopago.com/v1/account/release_report/config";
            $response = Http::withoutVerifying()
                ->withToken($accessToken)
                ->post($configUrl, [
                    'file_name_prefix' => 'mp-liberacoes',
                    'execute_after_withdrawal' => false,
                    'frequency' => [
                        'type' => 'daily',
                        'hour' => 0
                    ],
                    'columns' => [
                        ['key' => 'DATE'],
                        ['key' => 'SOURCE_ID'],
                        ['key' => 'EXTERNAL_REFERENCE'],
                        ['key' => 'RECORD_TYPE'],
                        ['key' => 'DESCRIPTION'],
                        ['key' => 'NET_CREDIT_AMOUNT'],
                        ['key' => 'NET_DEBIT_AMOUNT']
                    ]
                ]);

            if ($response->successful()) {
                Log::info('Configuração de relatórios de liberação criada com sucesso no Mercado Pago.');
                return true;
            }

            Log::error('Erro ao criar configuração de relatórios de liberação no Mercado Pago', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        } catch (\Exception $e) {
            Log::error('Exceção ao criar configuração de relatórios no Mercado Pago: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Sincroniza o Account Money Report do Mercado Pago.
     * Esse relatório contém TODAS as movimentações da conta: entradas, saídas,
     * compras no Mercado Livre, transferências, tarifas, etc.
     * É a fonte correta para capturar débitos como "Compra - Mercado Livre".
     */
    public function sincronizarAccountMoneyReport(string $startDate, string $endDate): int
    {
        $accessToken = config('services.mercadopago.access_token');
        if (empty($accessToken)) {
            Log::warning('Token do Mercado Pago não configurado. Account Money Report ignorado.');
            return 0;
        }

        $count = 0;

        try {
            // 1. Listar relatórios disponíveis do tipo settlement_report (Account Money)
            $listUrl = 'https://api.mercadopago.com/v1/account/settlement_report/list';
            $response = Http::withoutVerifying()->withToken($accessToken)->get($listUrl);

            if (!$response->successful()) {
                Log::warning('Account Money Report: não foi possível listar relatórios.', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                // Tenta solicitar a geração mesmo assim
                $this->solicitarAccountMoneyReport($accessToken, $startDate, $endDate);
                return 0;
            }

            $reports = $response->json();
            if (!is_array($reports)) {
                Log::warning('Account Money Report: resposta inesperada (não é array).', ['body' => $response->body()]);
                return 0;
            }

            $reqStart = Carbon::parse($startDate)->startOfDay();
            $reqEnd   = Carbon::parse($endDate)->endOfDay();

            // 2. Filtrar relatórios que intersectam o período solicitado
            $relevantes = array_filter($reports, function ($report) use ($reqStart, $reqEnd) {
                $status = $report['status'] ?? '';
                if (!in_array($status, ['processed', 'enabled', 'ready'])) {
                    return false;
                }
                $repStart = !empty($report['begin_date']) ? Carbon::parse($report['begin_date']) : null;
                $repEnd   = !empty($report['end_date'])   ? Carbon::parse($report['end_date'])   : null;

                if ($repStart && $repEnd) {
                    return $repStart->lte($reqEnd) && $repEnd->gte($reqStart);
                }
                return true; // Se não tiver datas, inclui por precaução
            });

            // Ordena por data de criação decrescente e pega os 5 mais recentes
            usort($relevantes, fn($a, $b) => strcmp($b['date_created'] ?? '', $a['date_created'] ?? ''));
            $relevantes = array_slice($relevantes, 0, 5);

            foreach ($relevantes as $report) {
                $fileName = $report['file_name'] ?? null;
                if (!$fileName) continue;

                Log::info("Account Money Report: baixando {$fileName}");
                $downloadUrl = "https://api.mercadopago.com/v1/account/settlement_report/{$fileName}";
                $download = Http::withoutVerifying()->withToken($accessToken)->get($downloadUrl);

                if ($download->successful()) {
                    // Reutiliza o mesmo parser de CSV que já existe
                    $count += $this->processarCsvRelatorio($download->body());
                } else {
                    Log::error("Account Money Report: erro ao baixar {$fileName}", [
                        'status' => $download->status(),
                    ]);
                }
            }

            // 3. Solicitar geração de um novo relatório para o período (assíncrono)
            $this->solicitarAccountMoneyReport($accessToken, $startDate, $endDate);

        } catch (\Exception $e) {
            Log::error('Erro ao processar Account Money Report do Mercado Pago: ' . $e->getMessage());
        }

        return $count;
    }

    /**
     * Solicita a geração assíncrona de um novo Account Money Report no MP
     */
    private function solicitarAccountMoneyReport(string $accessToken, string $startDate, string $endDate): void
    {
        $beginDate = Carbon::parse($startDate)->startOfDay()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z');
        $endDate   = Carbon::parse($endDate)->endOfDay()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z');

        $response = Http::withoutVerifying()
            ->withToken($accessToken)
            ->post('https://api.mercadopago.com/v1/account/settlement_report', [
                'begin_date' => $beginDate,
                'end_date'   => $endDate,
            ]);

        if ($response->successful()) {
            Log::info("Account Money Report: geração solicitada para {$startDate} a {$endDate}.");
        } else {
            Log::info('Account Money Report: aviso ao solicitar geração (pode já existir): ' . $response->body());
        }
    }

    /**
     * Sincroniza as transações do extrato do Banco Inter via API Banking v2
     */
    public function sincronizarBancoInter(string $startDate, string $endDate): int
    {
        $config = config('services.banco_inter');
        if (empty($config['client_id']) || empty($config['client_secret'])) {
            throw new \Exception("Credenciais do Banco Inter (Client ID / Client Secret) não configuradas no arquivo .env.");
        }

        $certPath = $config['cert_path'];
        $keyPath = $config['key_path'];

        // Resolve caminhos relativos ao diretório storage se não forem absolutos
        if (!str_starts_with($certPath, '/') && !str_starts_with($certPath, '\\') && !preg_match('/^[a-zA-Z]:/', $certPath)) {
            $certPath = storage_path($certPath);
        }
        if (!str_starts_with($keyPath, '/') && !str_starts_with($keyPath, '\\') && !preg_match('/^[a-zA-Z]:/', $keyPath)) {
            $keyPath = storage_path($keyPath);
        }

        // Simulador de Sandbox para facilitar testes locais sem certificados reais configurados
        if ($config['sandbox'] && (!file_exists($certPath) || !file_exists($keyPath))) {
            Log::warning('Banco Inter em modo Sandbox sem certificados mTLS configurados. Simulando resposta para o período selecionado.');
            return $this->simularResponseSandboxBancoInter($startDate, $endDate);
        }

        if (!file_exists($certPath) || !file_exists($keyPath)) {
            throw new \Exception("Arquivos de certificado mTLS do Banco Inter não foram encontrados. Certificado esperado em: {$certPath}. Chave esperada em: {$keyPath}");
        }

        $baseUrl = $config['sandbox'] ? 'https://cdpj-sandbox.partners.uatinter.co' : 'https://cdpj.partners.bancointer.com.br';
        $tokenUrl = $config['sandbox'] ? 'https://cdpj-sandbox.partners.uatinter.co/oauth/v2/token' : 'https://cdpj.partners.bancointer.com.br/oauth/v2/token';

        Log::info('Solicitando token OAuth para Banco Inter...', ['url' => $tokenUrl]);

        // 1. Obter Token OAuth com mTLS
        $tokenResponse = Http::withoutVerifying()
            ->withOptions([
                'cert' => $certPath,
                'ssl_key' => $keyPath,
            ])
            ->asForm()
            ->post($tokenUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'scope' => 'extrato.read'
            ]);

        if (!$tokenResponse->successful()) {
            Log::error('Erro na autenticação OAuth2 do Banco Inter', [
                'status' => $tokenResponse->status(),
                'body' => $tokenResponse->body()
            ]);
            throw new \Exception("Erro ao autenticar no Banco Inter (Status {$tokenResponse->status()}): " . $tokenResponse->body());
        }

        $accessToken = $tokenResponse->json()['access_token'] ?? null;
        if (empty($accessToken)) {
            throw new \Exception("Token de acesso não encontrado na resposta do Banco Inter.");
        }

        // 2. Chamar API de Extrato
        $beginDate = Carbon::parse($startDate)->toDateString();
        $endDate = Carbon::parse($endDate)->toDateString();
        
        $extratoUrl = "{$baseUrl}/banking/v2/extrato?dataInicio={$beginDate}&dataFim={$endDate}";
        Log::info('Buscando extrato da conta Banco Inter...', ['url' => $extratoUrl]);

        $response = Http::withoutVerifying()
            ->withToken($accessToken)
            ->withOptions([
                'cert' => $certPath,
                'ssl_key' => $keyPath,
            ])
            ->get($extratoUrl);

        if (!$response->successful()) {
            Log::error('Erro ao buscar extrato do Banco Inter', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception("Erro ao obter extrato do Banco Inter (Status {$response->status()}): " . $response->body());
        }

        $data = $response->json();
        $transacoes = $data['transacoes'] ?? [];

        Log::info("Processando " . count($transacoes) . " transações retornadas pelo Banco Inter.");

        // Localiza a conta do Banco Inter pelo nome ou ID
        $contaInter = \App\Models\ContaBancaria::where('nome', 'like', '%Inter%')->first();
        $contaBancariaId = $contaInter ? $contaInter->id : 1;

        $count = 0;
        $seenFitids = [];
        foreach ($transacoes as $item) {
            $tipo = isset($item['tipoOperacao']) && strtolower($item['tipoOperacao']) === 'd' ? 'saida' : 'entrada';
            $valor = abs((float) ($item['valor'] ?? 0));
            if ($valor <= 0) continue;

            $date = Carbon::parse($item['dataEntrada'] ?? now())->toDateString();
            $descricao = $item['descricao'] ?? $item['titulo'] ?? 'Movimentação Banco Inter';

            // Garante um FITID único. Se CPMF/NSU não forem retornados, criamos um hash determinístico.
            $baseFitid = $item['cpmf'] ?? $item['nsu'] ?? 'inter_' . md5($date . $tipo . $valor . $descricao);

            if (!isset($seenFitids[$baseFitid])) {
                $seenFitids[$baseFitid] = 0;
                $fitid = $baseFitid;
            } else {
                $seenFitids[$baseFitid]++;
                $fitid = $baseFitid . '_' . $seenFitids[$baseFitid];
            }

            TransacaoExtrato::updateOrCreate(
                ['fitid' => (string) $fitid],
                [
                    'data' => $date,
                    'descricao' => $descricao,
                    'valor' => $valor,
                    'valor_bruto' => $valor,
                    'valor_taxa' => 0,
                    'valor_liquido' => $valor,
                    'tipo' => $tipo,
                    'origem' => 'bancointer',
                    'conta_bancaria_id' => $contaBancariaId,
                    'payload_original' => $item
                ]
            );

            $count++;
        }

        try {
            $this->autoConciliarTransacoesPendentes($contaBancariaId);
        } catch (\Exception $e) {
            Log::error("Erro na auto-conciliação pós-sincronização Banco Inter: " . $e->getMessage());
        }

        return $count;
    }

    /**
     * Simula transações do Banco Inter para o ambiente de Sandbox sem certificados
     */
    private function simularResponseSandboxBancoInter(string $startDate, string $endDate): int
    {
        $contaInter = \App\Models\ContaBancaria::where('nome', 'like', '%Inter%')->first();
        $contaBancariaId = $contaInter ? $contaInter->id : 1;

        $mockTransacoes = [
            [
                'dataEntrada' => Carbon::parse($startDate)->toDateString(),
                'tipoTransacao' => 'PIX RECEBIDO',
                'tipoOperacao' => 'C',
                'valor' => '120.50',
                'titulo' => 'Pix Recebido',
                'descricao' => 'Pix recebido de Maria Oliveira Silva'
            ],
            [
                'dataEntrada' => Carbon::parse($startDate)->addDays(1)->toDateString(),
                'tipoTransacao' => 'TARIFA',
                'tipoOperacao' => 'D',
                'valor' => '5.90',
                'titulo' => 'Tarifa de Conta',
                'descricao' => 'Tarifa Mensalidade Conta PJ Banco Inter'
            ],
            [
                'dataEntrada' => Carbon::parse($endDate)->toDateString(),
                'tipoTransacao' => 'PIX ENVIADO',
                'tipoOperacao' => 'D',
                'valor' => '45.00',
                'titulo' => 'Pix Enviado',
                'descricao' => 'Pix enviado para Distribuidora de Sacolas LTDA'
            ]
        ];

        $count = 0;
        $seenFitids = [];
        foreach ($mockTransacoes as $item) {
            $tipo = strtolower($item['tipoOperacao']) === 'd' ? 'saida' : 'entrada';
            $valor = (float) $item['valor'];
            $date = $item['dataEntrada'];
            $descricao = $item['descricao'];
            
            $baseFitid = 'mock_inter_' . md5($date . $item['tipoOperacao'] . $item['valor'] . $descricao);

            if (!isset($seenFitids[$baseFitid])) {
                $seenFitids[$baseFitid] = 0;
                $fitid = $baseFitid;
            } else {
                $seenFitids[$baseFitid]++;
                $fitid = $baseFitid . '_' . $seenFitids[$baseFitid];
            }

            TransacaoExtrato::updateOrCreate(
                ['fitid' => (string) $fitid],
                [
                    'data' => $date,
                    'descricao' => $descricao,
                    'valor' => $valor,
                    'valor_bruto' => $valor,
                    'valor_taxa' => 0,
                    'valor_liquido' => $valor,
                    'tipo' => $tipo,
                    'origem' => 'bancointer',
                    'conta_bancaria_id' => $contaBancariaId,
                    'payload_original' => $item
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Tenta conciliar automaticamente transações pendentes que já possuem movimentações correspondentes no sistema
     */
    public function autoConciliarTransacoesPendentes(?int $contaBancariaId = null): int
    {
        $query = TransacaoExtrato::where('status', 'pendente');
        if ($contaBancariaId) {
            $query->where('conta_bancaria_id', $contaBancariaId);
        }
        $transacoes = $query->get();

        $count = 0;
        foreach ($transacoes as $transacao) {
            $matched = \DB::transaction(function () use ($transacao) {
                // Lock row
                $tLock = TransacaoExtrato::lockForUpdate()->find($transacao->id);
                if (!$tLock || $tLock->status === 'conciliado') {
                    return false;
                }

                $valor = $tLock->valor_bruto ?? $tLock->valor;
                $dataMin = Carbon::parse($tLock->data)->subDays(3)->toDateString();
                $dataMax = Carbon::parse($tLock->data)->addDays(3)->toDateString();

                // Busca movimentações pendentes de vinculação com mesmo valor e conta
                $movs = \App\Models\Movimentacao::where('valor_pago', $valor)
                    ->where('conta_bancaria_id', $tLock->conta_bancaria_id)
                    ->whereDoesntHave('transacaoExtrato')
                    ->whereBetween('data_pagamento', [$dataMin, $dataMax])
                    ->with('lancamento.pessoa')
                    ->get();

                foreach ($movs as $mov) {
                    $lancamento = $mov->lancamento;
                    if (!$lancamento) continue;

                    $match = false;

                    // 1. Tenta casar pelo nome da pessoa
                    if ($lancamento->pessoa && $lancamento->pessoa->nome) {
                        $nomePessoa = mb_strtolower($lancamento->pessoa->nome, 'UTF-8');
                        $descTransacao = mb_strtolower($tLock->descricao, 'UTF-8');

                        // Remove acentos/caracteres especiais
                        $nomeNormalizado = preg_replace('/[^a-z0-9 ]/i', '', iconv('UTF-8', 'ASCII//TRANSLIT', $nomePessoa));
                        $descNormalizada = preg_replace('/[^a-z0-9 ]/i', '', iconv('UTF-8', 'ASCII//TRANSLIT', $descTransacao));

                        $partes = explode(' ', $nomeNormalizado);
                        $partesValidas = array_filter($partes, function($p) { return strlen($p) > 2; });

                        if (!empty($partesValidas)) {
                            $matchCount = 0;
                            foreach ($partesValidas as $parte) {
                                if (str_contains($descNormalizada, $parte)) {
                                    $matchCount++;
                                }
                            }
                            if ($matchCount >= max(1, count($partesValidas) / 2)) {
                                $match = true;
                            }
                        }
                    }

                    // 2. Tenta casar se for pedido e a descrição contiver o ID do pedido
                    if (!$match && $lancamento->referencia_tipo === 'pedido') {
                        $pedidoId = $lancamento->referencia_id;
                        $descTransacao = mb_strtolower($tLock->descricao, 'UTF-8');
                        if (str_contains($descTransacao, "ped-" . str_pad($pedidoId, 6, '0', STR_PAD_LEFT)) || str_contains($descTransacao, (string)$pedidoId)) {
                            $match = true;
                        }
                    }

                    if ($match) {
                        // Vincula!
                        $mov->update(['transacao_extrato_id' => $tLock->id]);
                        $tLock->update([
                            'status' => 'conciliado',
                            'movimentacao_id' => $mov->id
                        ]);
                        Log::info("Auto-conciliação automática de extrato realizada: Movimentacao #{$mov->id} -> Transacao {$tLock->fitid}");
                        return true;
                    }
                }

                return false;
            });

            if ($matched) {
                $count++;
            }
        }

        return $count;
    }
}

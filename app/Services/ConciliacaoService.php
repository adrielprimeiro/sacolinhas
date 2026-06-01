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

        // Sincronizar relatórios de extrato completo (incluindo saídas/tarifas)
        $countReports = $this->sincronizarRelatoriosMercadoPago($startDate, $endDate);

        Log::info('Mercado Pago full sync completed', [
            'payments' => $count,
            'reports' => $countReports
        ]);
        
        return $count + $countReports;
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

            $movimentacao = $lancamento->movimentacoes()
                ->where('valor_pago', $transacao->valor_bruto ?? $transacao->valor)
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

            $defaultContaId = 1;
            if ($transacao->origem === 'mercadopago') {
                $contaMp = \App\Models\ContaBancaria::where('nome', 'like', '%Mercado Pago%')->first();
                $defaultContaId = $contaMp ? $contaMp->id : 2;
            }

            // 1. Criar a Movimentação (Baixa) para o valor bruto (Total do Pedido/Lançamento)
            $movimentacao = \App\Models\Movimentacao::create([
                'lancamento_id' => $lancamento->id,
                'conta_bancaria_id' => $transacao->conta_bancaria_id ?? $defaultContaId, 
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
            // 1. Listar relatórios existentes (Relatórios de Liberação / Bank Report)
            $listUrl = "https://api.mercadopago.com/v1/account/bank_report/list";
            $response = Http::withoutVerifying()->withToken($accessToken)->get($listUrl);

            // Auto-configurar caso não exista a configuração de relatórios de liberação
            if ($response->status() === 404 && str_contains($response->body(), 'config_not_found_for_user')) {
                Log::info('Configuração de relatórios de liberação não encontrada no Mercado Pago. Criando...');
                $this->criarConfiguracaoRelatorioMP($accessToken);
                $response = Http::withoutVerifying()->withToken($accessToken)->get($listUrl);
            }

            if ($response->successful()) {
                $reports = $response->json();
                
                $reqStart = Carbon::parse($startDate)->startOfDay();
                $reqEnd = Carbon::parse($endDate)->endOfDay();

                // Filtrar relatórios habilitados (enabled) ou processados (processed) que sobreponham o período solicitado
                $processedReports = array_filter($reports, function ($report) use ($reqStart, $reqEnd) {
                    if (!isset($report['status']) || !in_array($report['status'], ['processed', 'enabled']) || !isset($report['file_name'])) {
                        return false;
                    }
                    
                    // Verificar se o período do relatório sobrepõe o período solicitado
                    $repStart = isset($report['begin_date']) ? Carbon::parse($report['begin_date']) : null;
                    $repEnd = isset($report['end_date']) ? Carbon::parse($report['end_date']) : null;
                    
                    if ($repStart && $repEnd) {
                        return $repStart->lte($reqEnd) && $repEnd->gte($reqStart);
                    }
                    
                    return true;
                });

                // Ordenar por data de criação decrescente (mais recentes primeiro)
                usort($processedReports, function ($a, $b) {
                    return strcmp($b['date_created'] ?? '', $a['date_created'] ?? '');
                });

                // Processar no máximo os 5 relatórios de liberação mais recentes que sobrepõem o período
                $processedReports = array_slice($processedReports, 0, 5);

                foreach ($processedReports as $report) {
                    $fileName = $report['file_name'];
                    Log::info("Processando relatório de extrato MP: {$fileName}");
                    
                    $downloadUrl = "https://api.mercadopago.com/v1/account/bank_report/{$fileName}";
                    $downloadResponse = Http::withoutVerifying()->withToken($accessToken)->get($downloadUrl);
                    
                    if ($downloadResponse->successful()) {
                        $csvContent = $downloadResponse->body();
                        $count += $this->processarCsvRelatorio($csvContent);
                    } else {
                        Log::error("Erro ao baixar relatório MP: {$fileName}", [
                            'status' => $downloadResponse->status()
                        ]);
                    }
                }
            } else {
                Log::error('Erro ao listar relatórios do Mercado Pago', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

            // 2. Solicitar a geração de um novo relatório de liberação para o período (processamento assíncrono)
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

            $transacao = TransacaoExtrato::updateOrCreate(
                ['fitid' => (string) $sourceId],
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
                if ($pedido && $pedido->status_pagamento === 'aprovado' && $transacao->status === 'pendente') {
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
}

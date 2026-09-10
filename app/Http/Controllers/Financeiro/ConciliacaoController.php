<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\Lancamento;
use App\Models\TransacaoExtrato;
use App\Models\ClassificacaoFinanceira;
use App\Models\Pessoa;
use App\Services\ConciliacaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConciliacaoController extends Controller
{
    protected $service;

    public function __construct(ConciliacaoService $service)
    {
        $this->service = $service;
    }

    public function autoConciliarTransferencias()
    {
        try {
            $qtd = $this->service->autoConciliarTransferenciasEntreContas();
            if ($qtd > 0) {
                return back()->with('success', "✅ {$qtd} transferência(s) entre contas identificada(s) e conciliada(s) automaticamente!");
            }
            return back()->with('info', "Nenhuma nova transferência entre contas pendente foi identificada no momento.");
        } catch (\Exception $e) {
            Log::error("Erro ao conciliar transferências: " . $e->getMessage());
            return back()->with('error', "Erro ao conciliar transferências: " . $e->getMessage());
        }
    }

    public function index()
    {
        // 0. Auto-sincronização automática do extrato bancário (Banco Inter e Mercado Pago) a cada 15 minutos ao abrir a tela ou via ?sync=1
        if (request()->has('sync') || !\Illuminate\Support\Facades\Cache::has('last_extrato_auto_synced_at')) {
            try {
                $startDate = now()->subDays(7)->toDateString();
                $endDate = now()->toDateString();

                $this->service->sincronizarBancoInter($startDate, $endDate);
                $this->service->sincronizarMercadoPago($startDate, $endDate);
                
                \Illuminate\Support\Facades\Cache::put('last_extrato_auto_synced_at', now(), 900); // 15 minutos
            } catch (\Exception $e) {
                Log::warning("Aviso na sincronização automática de extratos bancários: " . $e->getMessage());
            }
        }

        // Auto-conciliação automática para transações pendentes
        try {
            $this->service->autoConciliarTransacoesPendentes();
        } catch (\Exception $e) {
            Log::error("Erro na auto-conciliação automática ao abrir Conciliação: " . $e->getMessage());
        }

        $extrato = TransacaoExtrato::where('status', 'pendente')
            ->orderBy('data', 'desc')
            ->get();

        $descricoes = $extrato->pluck('descricao')->filter()->unique()->values();

        // Batch Query 1: Histórico de lançamentos por descrição
        $historicosGrouped = Lancamento::whereIn('descricao', $descricoes)
            ->whereNotNull('classificacao_financeira_id')
            ->where(function ($q) {
                $q->whereIn('status', ['pago', 'pago_parcial'])
                  ->orWhereHas('movimentacoes');
            })
            ->get(['descricao', 'pessoa_id', 'classificacao_financeira_id'])
            ->groupBy('descricao');

        // Batch Query 2: Transações conciliadas passadas por descrição
        $transacoesConciliadasGrouped = TransacaoExtrato::where('status', 'conciliado')
            ->whereIn('descricao', $descricoes)
            ->whereHas('movimentacao.lancamento')
            ->with('movimentacao.lancamento:id,pessoa_id,classificacao_financeira_id')
            ->get(['id', 'descricao', 'movimentacao_id'])
            ->groupBy('descricao');

        // Janela de datas para filtrar lançamentos de candidatos (últimos 90 dias ou pendentes)
        $minDataExtrato = $extrato->min('data') ? \Carbon\Carbon::parse($extrato->min('data'))->subDays(30) : now()->subDays(90);

        $lancamentos = Lancamento::with(['pessoa:id,nome', 'classificacaoFinanceira:id,nome'])
            ->where(function ($q) use ($minDataExtrato) {
                $q->where('status', 'pendente')
                    ->orWhere(function ($q2) use ($minDataExtrato) {
                        $q2->where('status', 'pago')
                            ->where('data_vencimento', '>=', $minDataExtrato)
                            ->whereDoesntHave('movimentacoes', function ($q3) {
                                $q3->whereHas('transacaoExtrato');
                            });
                    });
            })
            ->orderBy('data_vencimento', 'asc')
            ->get();

        $regrasRaw = \DB::table('configuracoes')->where('chave', 'regras_conciliacao')->value('valor');
        $regras = json_decode($regrasRaw, true) ?: [];

        $extratoComSugestoes = $extrato->map(function ($t) use ($lancamentos, $regras, $historicosGrouped, $transacoesConciliadasGrouped) {
            $pedidoIdRef = $t->getPedidoId();
            
            // 1. Procurar regra correspondente para a descrição (priorizando match de valor exato)
            $regraCorrespondente = null;
            $tDescLower = mb_strtolower($t->descricao, 'UTF-8');
            $candidateRules = [];
            foreach ($regras as $r) {
                if (($r['tipo'] ?? 'sugestao') === 'sugestao') {
                    $ruleDescLower = mb_strtolower($r['descricao_banco'], 'UTF-8');
                    if (str_contains($tDescLower, $ruleDescLower)) {
                        $candidateRules[] = $r;
                    }
                }
            }

            // 1.1 Tentar regra com valor específico primeiro
            foreach ($candidateRules as $r) {
                if (isset($r['valor']) && $r['valor'] !== '' && $r['valor'] !== null) {
                    if (abs((float)$r['valor'] - (float)$t->valor) < 0.05) {
                        $regraCorrespondente = $r;
                        break;
                    }
                }
            }

            // 1.2 Fallback para regra geral (sem valor)
            if (!$regraCorrespondente) {
                foreach ($candidateRules as $r) {
                    if (!isset($r['valor']) || $r['valor'] === '' || $r['valor'] === null) {
                        $regraCorrespondente = $r;
                        break;
                    }
                }
            }

            // 1.3. Filtrar regras de exclusão correspondentes para esta descrição
            $exclusoes = [];
            foreach ($regras as $r) {
                if (($r['tipo'] ?? 'sugestao') === 'exclusao') {
                    $ruleDescLower = mb_strtolower($r['descricao_banco'], 'UTF-8');
                    if (str_contains($tDescLower, $ruleDescLower)) {
                        if (isset($r['valor']) && $r['valor'] !== '' && $r['valor'] !== null) {
                            if (abs((float)$r['valor'] - (float)$t->valor) >= 0.05) {
                                continue;
                            }
                        }
                        $exclusoes[] = [
                            'pessoa_id' => (int) $r['pessoa_id'],
                            'classificacao_financeira_id' => (int) $r['classificacao_financeira_id']
                        ];
                    }
                }
            }

            // 2. Buscar histórico utilizando as coleções pré-carregadas (sem N+1 queries)
            $paresHistoricos = [];
            
            $historicos = $historicosGrouped->get($t->descricao) ?? collect();
            foreach ($historicos as $h) {
                if ($h->pessoa_id && $h->classificacao_financeira_id) {
                    $key = $h->pessoa_id . '-' . $h->classificacao_financeira_id;
                    $paresHistoricos[$key] = [
                        'pessoa_id' => $h->pessoa_id,
                        'classificacao_id' => $h->classificacao_financeira_id
                    ];
                }
            }

            $transacoesConciliadas = $transacoesConciliadasGrouped->get($t->descricao) ?? collect();
            foreach ($transacoesConciliadas as $tc) {
                $l = $tc->movimentacao?->lancamento;
                if ($l && $l->pessoa_id && $l->classificacao_financeira_id) {
                    $key = $l->pessoa_id . '-' . $l->classificacao_financeira_id;
                    $paresHistoricos[$key] = [
                        'pessoa_id' => $l->pessoa_id,
                        'classificacao_id' => $l->classificacao_financeira_id
                    ];
                }
            }

            $sugestoesFinal = collect();

            // A. Adicionar Lançamentos em Aberto correspondentes ao Histórico (Score 100)
            foreach ($paresHistoricos as $par) {
                $matchedOpen = $lancamentos->filter(function ($l) use ($par, $t) {
                    $tTipoMapped = ($t->tipo === 'entrada') ? 'receita' : 'despesa';
                    if ($l->tipo !== $tTipoMapped 
                        || $l->pessoa_id != $par['pessoa_id'] 
                        || $l->classificacao_financeira_id != $par['classificacao_id']) {
                        return false;
                    }
                    
                    // Se o lançamento já estiver pago, só sugere se a data de vencimento for próxima à data da transação (limite de 10 dias)
                    if ($l->status === 'pago') {
                        $diferencaDias = abs(\Illuminate\Support\Carbon::parse($t->data)->diffInDays(\Illuminate\Support\Carbon::parse($l->data_vencimento)));
                        if ($diferencaDias > 10) {
                            return false;
                        }
                    }
                    
                    // Apenas sugere lançamentos cujo valor total ou saldo restante coincida com o da transação
                    $valorPagoTotal = (float) $l->movimentacoes->sum('valor_pago');
                    $saldoRestante = max(0.00, (float) $l->valor_total - $valorPagoTotal);
                    
                    $valMatch = abs((float)$t->valor - (float)$l->valor_total) < 0.05;
                    $saldoMatch = abs((float)$t->valor - $saldoRestante) < 0.05;
                    
                    return $valMatch || $saldoMatch;
                });
                
                foreach ($matchedOpen as $l) {
                    $lClone = clone $l;
                    $lClone->score = 100;
                    $lClone->motivos_match = ['Histórico de descrição'];
                    $lClone->is_valid_suggestion = true;
                    $sugestoesFinal->push($lClone);
                }
            }

            // B. Adicionar Lançamentos em Aberto correspondentes à Regra de Conciliação (Score 150)
            if ($regraCorrespondente) {
                $matchedRuleOpen = $lancamentos->filter(function ($l) use ($regraCorrespondente, $t) {
                    $tTipoMapped = ($t->tipo === 'entrada') ? 'receita' : 'despesa';
                    if ($l->tipo !== $tTipoMapped 
                        || $l->pessoa_id != $regraCorrespondente['pessoa_id'] 
                        || $l->classificacao_financeira_id != $regraCorrespondente['classificacao_financeira_id']) {
                        return false;
                    }
                    
                    // Para regras, também limitamos 10 dias se já estiver pago
                    if ($l->status === 'pago') {
                        $diferencaDias = abs(\Illuminate\Support\Carbon::parse($t->data)->diffInDays(\Illuminate\Support\Carbon::parse($l->data_vencimento)));
                        if ($diferencaDias > 10) {
                            return false;
                        }
                    }
                    
                    $valorPagoTotal = (float) $l->movimentacoes->sum('valor_pago');
                    $saldoRestante = max(0.00, (float) $l->valor_total - $valorPagoTotal);
                    
                    $valMatch = abs((float)$t->valor - (float)$l->valor_total) < 0.05;
                    $saldoMatch = abs((float)$t->valor - $saldoRestante) < 0.05;
                    
                    return $valMatch || $saldoMatch;
                });

                foreach ($matchedRuleOpen as $l) {
                    if (!$sugestoesFinal->contains('id', $l->id)) {
                        $lClone = clone $l;
                        $lClone->score = 150;
                        $lClone->motivos_match = ['Regra padrão de conciliação'];
                        $lClone->is_valid_suggestion = true;
                        $sugestoesFinal->push($lClone);
                    } else {
                        // Se já existia, atualiza o score para dar prioridade máxima
                        $existing = $sugestoesFinal->firstWhere('id', $l->id);
                        if ($existing) {
                            $existing->score = 150;
                            $existing->motivos_match = ['Regra padrão de conciliação'];
                        }
                    }
                }
            }

            // C. Adicionar Sugestões Virtuais de Criação Rápida por Regra (Score 140)
            if ($regraCorrespondente) {
                $pessoaModel = !empty($regraCorrespondente['pessoa_id']) ? \App\Models\Pessoa::find($regraCorrespondente['pessoa_id']) : null;
                $classificacaoModel = !empty($regraCorrespondente['classificacao_financeira_id']) ? \App\Models\ClassificacaoFinanceira::find($regraCorrespondente['classificacao_financeira_id']) : null;
                
                if ($classificacaoModel) {
                    $virtualRule = (object) [
                        'id' => null,
                        'is_virtual' => true,
                        'descricao' => 'Criar Lançamento Rápido',
                        'tipo' => ($t->tipo === 'entrada') ? 'receita' : 'despesa',
                        'valor_total' => $t->valor,
                        'pessoa_id' => $pessoaModel?->id,
                        'pessoa' => $pessoaModel,
                        'classificacao_financeira_id' => $classificacaoModel->id,
                        'classificacaoFinanceira' => $classificacaoModel,
                        'score' => 140,
                        'motivos_match' => ['Regra padrão'],
                        'is_valid_suggestion' => true
                    ];
                    $sugestoesFinal->push($virtualRule);
                }
            }

            // D. Adicionar Sugestões Virtuais de Criação Rápida por Histórico (Score 90)
            foreach ($paresHistoricos as $par) {
                // Se já adicionamos essa mesma combinação pela regra, não duplica com score baixo
                if ($regraCorrespondente 
                    && $par['pessoa_id'] == $regraCorrespondente['pessoa_id'] 
                    && $par['classificacao_id'] == $regraCorrespondente['classificacao_financeira_id']) {
                    continue;
                }

                $pessoaModel = \App\Models\Pessoa::find($par['pessoa_id']);
                $classificacaoModel = \App\Models\ClassificacaoFinanceira::find($par['classificacao_id']);
                
                if ($pessoaModel && $classificacaoModel) {
                    $virtual = (object) [
                        'id' => null,
                        'is_virtual' => true,
                        'descricao' => 'Criar Lançamento Rápido',
                        'tipo' => ($t->tipo === 'entrada') ? 'receita' : 'despesa',
                        'valor_total' => $t->valor,
                        'pessoa_id' => $pessoaModel->id,
                        'pessoa' => $pessoaModel,
                        'classificacao_financeira_id' => $classificacaoModel->id,
                        'classificacaoFinanceira' => $classificacaoModel,
                        'score' => 90,
                        'motivos_match' => ['Histórico de classificação'],
                        'is_valid_suggestion' => true
                    ];
                    $sugestoesFinal->push($virtual);
                }
            }

            // E. Adicionar Sugestões por Nome do Cliente na Descrição (Score 110)
            $matchedByPessoa = $lancamentos->filter(function ($l) use ($t) {
                $tTipoMapped = ($t->tipo === 'entrada') ? 'receita' : 'despesa';
                if ($l->tipo !== $tTipoMapped) {
                    return false;
                }
                
                // Limite de 10 dias para pagos
                if ($l->status === 'pago') {
                    $diferencaDias = abs(\Illuminate\Support\Carbon::parse($t->data)->diffInDays(\Illuminate\Support\Carbon::parse($l->data_vencimento)));
                    if ($diferencaDias > 10) {
                        return false;
                    }
                }

                $valorPagoTotal = (float) $l->movimentacoes->sum('valor_pago');
                $saldoRestante = max(0.00, (float) $l->valor_total - $valorPagoTotal);
                $valMatch = abs((float)$t->valor - (float)$l->valor_total) < 0.05;
                $saldoMatch = abs((float)$t->valor - $saldoRestante) < 0.05;
                
                if (!$valMatch && !$saldoMatch) {
                    return false;
                }
                
                if ($l->pessoa && $l->pessoa->nome) {
                    $nomePessoa = mb_strtolower($l->pessoa->nome, 'UTF-8');
                    $descTransacao = mb_strtolower($t->descricao, 'UTF-8');
                    
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
                        return $matchCount >= max(1, count($partesValidas) / 2);
                    }
                }
                
                return false;
            });
            
            foreach ($matchedByPessoa as $l) {
                if (!$sugestoesFinal->contains('id', $l->id)) {
                    $lClone = clone $l;
                    $lClone->score = 110;
                    $lClone->motivos_match = ['Nome do cliente na descrição do banco'];
                    $lClone->is_valid_suggestion = true;
                    $sugestoesFinal->push($lClone);
                }
            }

            // Filtrar exclusões
            $sugestoesFinal = $sugestoesFinal->reject(function ($sug) use ($exclusoes) {
                foreach ($exclusoes as $exc) {
                    if ($exc['pessoa_id'] == $sug->pessoa_id && $exc['classificacao_financeira_id'] == $sug->classificacao_financeira_id) {
                        return true;
                    }
                }
                return false;
            });

            // Garantir que removemos qualquer duplicado de ID de sugestão
            $sugestoesUnicas = collect();
            foreach ($sugestoesFinal as $sug) {
                if ($sug->id !== null) {
                    if (!$sugestoesUnicas->contains('id', $sug->id)) {
                        $sugestoesUnicas->push($sug);
                    }
                } else {
                    $exists = $sugestoesUnicas->contains(function ($value) use ($sug) {
                        return $value->id === null 
                            && $value->pessoa_id == $sug->pessoa_id 
                            && $value->classificacao_financeira_id == $sug->classificacao_financeira_id;
                    });
                    if (!$exists) {
                        $sugestoesUnicas->push($sug);
                    }
                }
            }

            $sugestoes = $sugestoesUnicas
                ->sortByDesc('score')
                ->take(5)
                ->values();

            return [
                'transacao' => $t,
                'sugestoes' => $sugestoes,
                'regra_correspondente' => $regraCorrespondente
            ];
        });

        $lancamentosReceita = $lancamentos->where('tipo', 'receita')->values();
        $lancamentosDespesa = $lancamentos->where('tipo', 'despesa')->values();

        $classificacoes = ClassificacaoFinanceira::all();
        $pessoas = Pessoa::all(['id', 'nome']);
        $contas = \App\Models\ContaBancaria::all(['id', 'nome']);

        $ignorados = TransacaoExtrato::where('status', 'ignorado')
            ->orderBy('data', 'desc')
            ->limit(50)
            ->get();

        return view('admin.financeiro.conciliacao', compact(
            'extratoComSugestoes',
            'lancamentosReceita',
            'lancamentosDespesa',
            'classificacoes',
            'pessoas',
            'contas',
            'ignorados'
        ));
    }

    public function sincronizarMp(Request $request)
    {
        try {
            $count = $this->service->sincronizarMercadoPago($request->start_date, $request->end_date);
            return back()->with('success', "{$count} transações sincronizadas do Mercado Pago. Nota: Recebimentos são importados na hora, mas saídas (Pix enviados, retiradas e tarifas) dependem de relatórios que o Mercado Pago gera de forma assíncrona. Se tiver saídas no período, clique em Sincronizar novamente em alguns minutos para importá-las.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function importarOfx(Request $request)
    {
        $request->validate(['arquivo_ofx' => 'required|file']);
        
        try {
            $file = $request->file('arquivo_ofx');
            $extension = strtolower($file->getClientOriginalExtension());
            
            if ($extension === 'csv') {
                $count = $this->service->importarCsvMercadoPago($file);
                return back()->with('success', "{$count} transações importadas do arquivo CSV do Mercado Pago.");
            }
            
            $count = $this->service->importarOfx($file, $request->conta_bancaria_id);
            return back()->with('success', "{$count} transações importadas do arquivo OFX.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function conciliar(Request $request)
    {
        $request->validate([
            'transacao_id' => 'required|exists:transacoes_extrato,id',
            'lancamento_id' => 'required|exists:lancamentos,id',
        ]);

        try {
            $this->service->vincular($request->transacao_id, $request->lancamento_id);
            return back()->with('success', 'Conciliação realizada com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function vincular(Request $request)
    {
        return $this->conciliar($request);
    }

    public function vincularMultiplos(Request $request)
    {
        $request->validate([
            'transacao_id' => 'required|exists:transacoes_extrato,id',
            'vinculos' => 'required|array|min:1',
            'vinculos.*.lancamento_id' => 'required|exists:lancamentos,id',
            'vinculos.*.valor_vinculo' => 'required|numeric|min:0.01',
        ]);

        try {
            $this->service->vincularMultiplos(
                $request->transacao_id,
                $request->vinculos
            );
            return back()->with('success', 'Conciliação múltipla realizada com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function criarRapido(Request $request)
    {
        $request->validate([
            'transacao_id' => 'required|exists:transacoes_extrato,id',
            'classificacao_financeira_id' => 'required|exists:classificacao_financeira,id',
        ]);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
                // Cria e concilia
                $conciliacao = $this->service->vincularNovoLancamento(
                    $request->transacao_id,
                    $request->classificacao_financeira_id,
                    $request->pessoa_id,
                    $request->conta_bancaria_id,
                    $request->observacoes
                );

                // Integração com o Clube
                $classificacao = \App\Models\ClassificacaoFinanceira::find($request->classificacao_financeira_id);
                if ($classificacao && (trim($classificacao->codigo_contabil) === '1.03' || strtolower($classificacao->nome) === 'clube mania' || $classificacao->id == 82)) {
                    $transacao = \App\Models\TransacaoExtrato::find($request->transacao_id);
                    
                    // Pegar o pessoa_id do lançamento gerado para garantir que seja o correto
                    $lancamento = \App\Models\Lancamento::find($conciliacao->lancamento_id);
                    $pessoaId = $lancamento ? $lancamento->pessoa_id : $request->pessoa_id;

                    if (!$pessoaId) {
                        throw new \Exception('Para conciliar um pagamento do Clube, é obrigatório selecionar a Pessoa (Contato).');
                    }

                    $pessoa = \App\Models\Pessoa::find($pessoaId);
                    if (!$pessoa || !$pessoa->user_id) {
                        throw new \Exception('O contato selecionado não está vinculado a um usuário do sistema. Edite o contato e vincule-o ao usuário correto para poder registrar o Clube.');
                    }

                    $mesAno = $request->competencia ?? date('Y-m');
                    [$ano, $mes] = array_map('intval', explode('-', $mesAno));

                    $assinaturaId = \Illuminate\Support\Facades\DB::table('clube_assinaturas')
                        ->where('user_id', $pessoa->user_id)
                        ->value('id');

                    if (!$assinaturaId) {
                        $assinaturaId = \Illuminate\Support\Facades\DB::table('clube_assinaturas')->insertGetId([
                            'user_id' => $pessoa->user_id,
                            'status' => 'ativa',
                            'inicio_em' => now()->toDateString(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        \Illuminate\Support\Facades\DB::table('clube_assinaturas')
                            ->where('id', $assinaturaId)
                            ->update(['status' => 'ativa', 'updated_at' => now()]);
                    }

                    \Illuminate\Support\Facades\DB::table('clube_mensalidades')->updateOrInsert(
                        [
                            'user_id' => $pessoa->user_id,
                            'competencia_ano' => $ano,
                            'competencia_mes' => $mes
                        ],
                        [
                            'assinatura_id' => $assinaturaId,
                            'status_pagamento' => 'pago',
                            'pago_em' => $transacao->data ?? now()->toDateString(),
                            'valor' => $transacao->valor ?? 0,
                        ]
                    );

                    // Recalcular pontos
                    \Illuminate\Support\Facades\DB::unprepared("CALL atualizar_pontuacoes_user({$pessoa->user_id}, '{$mesAno}')");
                }
            });

            return back()->with('success', 'Lançamento criado e conciliado!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Desmembrar uma transação do extrato em múltiplos lançamentos rápidos e conciliar.
     */
    public function desmembrarCriarRapido(Request $request)
    {
        $request->validate([
            'transacao_id' => 'required|exists:transacoes_extrato,id',
            'itens' => 'required|array|min:2',
            'itens.*.valor' => 'required|numeric|min:0.01',
            'itens.*.classificacao_financeira_id' => 'required|exists:classificacao_financeira,id',
            'itens.*.pessoa_id' => 'nullable|exists:pessoas,id',
        ]);

        $transacao = TransacaoExtrato::findOrFail($request->transacao_id);
        if ($transacao->status === 'conciliado') {
            return back()->with('error', 'Esta transação já foi conciliada.');
        }

        $somaItens = 0;
        foreach ($request->itens as $item) {
            $somaItens += (float) $item['valor'];
        }

        $valorTransacao = (float) ($transacao->valor_bruto ?? $transacao->valor);
        if (abs($somaItens - $valorTransacao) > 0.05) {
            return back()->with('error', 'A soma das partes (R$ ' . number_format($somaItens, 2, ',', '.') . ') não bate com o valor total da transação (R$ ' . number_format($valorTransacao, 2, ',', '.') . ').');
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request, $transacao) {
                $lancamentosVinculo = [];

                foreach ($request->itens as $item) {
                    $valorPart = (float) $item['valor'];
                    $classificacaoId = (int) $item['classificacao_financeira_id'];
                    $pessoaId = !empty($item['pessoa_id']) ? (int) $item['pessoa_id'] : null;
                    $classificacao = \App\Models\ClassificacaoFinanceira::find($classificacaoId);
                    
                    $isClube = ($classificacao && (trim($classificacao->codigo_contabil) === '1.03' || strtolower($classificacao->nome) === 'clube mania' || $classificacao->id == 82));
                    $isRecarga = ($classificacao && (trim($classificacao->codigo_contabil) === '1.04' || strtolower($classificacao->nome) === 'recarga de carteira' || $classificacao->id == 84));

                    if ($isClube && !$pessoaId) {
                        throw new \Exception('Para conciliar uma parte como Clube Mania, é obrigatório selecionar o Contato (Pessoa).');
                    }

                    $descricao = !empty($item['descricao'])
                        ? $item['descricao']
                        : ($isClube ? 'Clube Mania' : ($isRecarga ? 'Recarga de Carteira' : $transacao->descricao));

                    $referenciaTipo = $isRecarga ? 'recarga_carteira' : null;

                    // Criar o Lançamento da parte
                    $lancamento = Lancamento::create([
                        'tipo' => $transacao->tipo === 'entrada' ? 'receita' : 'despesa',
                        'status' => 'pendente',
                        'pessoa_id' => $pessoaId,
                        'classificacao_financeira_id' => $classificacaoId,
                        'data_emissao' => $transacao->data,
                        'data_vencimento' => $transacao->data,
                        'valor_total' => $valorPart,
                        'descricao' => $descricao,
                        'referencia_tipo' => $referenciaTipo,
                    ]);

                    // Se for Clube e houver competência informada no item
                    if ($isClube && !empty($item['competencia']) && $pessoaId) {
                        $pessoa = \App\Models\Pessoa::find($pessoaId);
                        if ($pessoa && $pessoa->user_id) {
                            $mesAno = $item['competencia'];
                            [$ano, $mes] = array_map('intval', explode('-', $mesAno));
                            
                            $assinaturaId = \Illuminate\Support\Facades\DB::table('clube_assinaturas')
                                ->where('user_id', $pessoa->user_id)
                                ->value('id');

                            if (!$assinaturaId) {
                                $assinaturaId = \Illuminate\Support\Facades\DB::table('clube_assinaturas')->insertGetId([
                                    'user_id' => $pessoa->user_id,
                                    'status' => 'ativa',
                                    'inicio_em' => now()->toDateString(),
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            } else {
                                \Illuminate\Support\Facades\DB::table('clube_assinaturas')
                                    ->where('id', $assinaturaId)
                                    ->update(['status' => 'ativa', 'updated_at' => now()]);
                            }

                            \Illuminate\Support\Facades\DB::table('clube_mensalidades')->updateOrInsert(
                                [
                                    'user_id' => $pessoa->user_id,
                                    'competencia_ano' => $ano,
                                    'competencia_mes' => $mes
                                ],
                                [
                                    'assinatura_id' => $assinaturaId,
                                    'status_pagamento' => 'pago',
                                    'pago_em' => $transacao->data ?? now()->toDateString(),
                                    'valor' => $valorPart,
                                ]
                            );
                        }
                    }

                    $lancamentosVinculo[] = [
                        'lancamento_id' => $lancamento->id,
                        'valor_vinculo' => $valorPart
                    ];
                }

                // Conciliar a transação com todos os lançamentos criados de uma vez
                $this->service->vincularMultiplos($transacao->id, $lancamentosVinculo);
            });

            return back()->with('success', 'Transação desmembrada e conciliada com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function ignorar(TransacaoExtrato $transacao)
    {
        $transacao->update(['status' => 'ignorado']);
        return back()->with('success', 'Transação ignorada.');
    }

    public function ignorarTransacao(Request $request, $id)
    {
        $transacao = TransacaoExtrato::findOrFail($id);
        
        $transacao->update([
            'status' => 'ignorado'
        ]);

        return response()->json(['success' => true]);
    }

    public function restaurarTransacao(Request $request, $id)
    {
        $transacao = TransacaoExtrato::findOrFail($id);
        
        $transacao->update([
            'status' => 'pendente'
        ]);

        return back()->with('success', 'Transação restaurada com sucesso.');
    }

    public function conciliarTransferencia(Request $request)
    {
        $request->validate([
            'transacao_id' => 'nullable|exists:transacoes_extrato,id',
            'transacao_saida_id' => 'nullable|exists:transacoes_extrato,id',
            'transacao_entrada_id' => 'nullable|exists:transacoes_extrato,id',
            'conta_contrapartida_id' => 'nullable|exists:contas_bancarias,id',
        ]);

        $catTransferencia = ClassificacaoFinanceira::where('nome', 'Transferência entre Contas')->first();
        if (!$catTransferencia) {
            return back()->with('error', 'Categoria "Transferência entre Contas" não encontrada.');
        }

        // Cenário 1: Duas transações do extrato (Saída + Entrada)
        if ($request->filled('transacao_saida_id') && $request->filled('transacao_entrada_id')) {
            $saida = TransacaoExtrato::where('status', 'pendente')->findOrFail($request->transacao_saida_id);
            $entrada = TransacaoExtrato::where('status', 'pendente')->findOrFail($request->transacao_entrada_id);

            if ($saida->tipo !== 'saida' || $entrada->tipo !== 'entrada') {
                return back()->with('error', 'Uma transação deve ser de saída e a outra de entrada.');
            }

            \DB::transaction(function () use ($saida, $entrada, $catTransferencia) {
                $lancSaida = Lancamento::create([
                    'tipo' => 'despesa',
                    'status' => 'pago',
                    'pessoa_id' => null,
                    'classificacao_financeira_id' => $catTransferencia->id,
                    'data_emissao' => $saida->data,
                    'data_vencimento' => $saida->data,
                    'valor_total' => $saida->valor,
                    'descricao' => 'Transferência enviada - ' . $saida->descricao,
                ]);
                $this->service->vincular($saida->id, $lancSaida->id);

                $lancEntrada = Lancamento::create([
                    'tipo' => 'receita',
                    'status' => 'pago',
                    'pessoa_id' => null,
                    'classificacao_financeira_id' => $catTransferencia->id,
                    'data_emissao' => $entrada->data,
                    'data_vencimento' => $entrada->data,
                    'valor_total' => $entrada->valor,
                    'descricao' => 'Transferência recebida - ' . $entrada->descricao,
                ]);
                $this->service->vincular($entrada->id, $lancEntrada->id);
            });

            return back()->with('success', 'Transferência entre extratos conciliada com sucesso!');
        }

        // Cenário 2: Transferência Unilateral / Direta (Uma transação + Conta de Contrapartida)
        $transId = $request->transacao_id ?? $request->transacao_saida_id ?? $request->transacao_entrada_id;
        if ($transId && $request->filled('conta_contrapartida_id')) {
            $trans = TransacaoExtrato::where('status', 'pendente')->findOrFail($transId);
            $contaContrapartidaId = (int) $request->conta_contrapartida_id;

            \DB::transaction(function () use ($trans, $contaContrapartidaId, $catTransferencia) {
                // 1. Vincular a transação existente
                $tipoLanc = $trans->tipo === 'entrada' ? 'receita' : 'despesa';
                $descLanc = ($trans->tipo === 'entrada' ? 'Transferência recebida - ' : 'Transferência enviada - ') . $trans->descricao;

                $lancPrincipal = Lancamento::create([
                    'tipo' => $tipoLanc,
                    'status' => 'pago',
                    'pessoa_id' => null,
                    'classificacao_financeira_id' => $catTransferencia->id,
                    'data_emissao' => $trans->data,
                    'data_vencimento' => $trans->data,
                    'valor_total' => $trans->valor,
                    'descricao' => $descLanc,
                ]);
                $this->service->vincular($trans->id, $lancPrincipal->id);

                // 2. Gerar a contrapartida na outra conta bancária
                $tipoContra = $trans->tipo === 'entrada' ? 'despesa' : 'receita';
                $descContra = ($trans->tipo === 'entrada' ? 'Transferência enviada - ' : 'Transferência recebida - ') . $trans->descricao;

                $lancContra = Lancamento::create([
                    'tipo' => $tipoContra,
                    'status' => 'pago',
                    'pessoa_id' => null,
                    'classificacao_financeira_id' => $catTransferencia->id,
                    'data_emissao' => $trans->data,
                    'data_vencimento' => $trans->data,
                    'valor_total' => $trans->valor,
                    'descricao' => $descContra,
                ]);

                // Criar Movimentação direta na conta de destino/origem
                \App\Models\Movimentacao::create([
                    'lancamento_id' => $lancContra->id,
                    'conta_bancaria_id' => $contaContrapartidaId,
                    'data_pagamento' => $trans->data,
                    'valor_pago' => $trans->valor,
                    'forma_pagamento' => 'transferencia',
                ]);
            });

            return back()->with('success', 'Transferência registrada e conciliada com sucesso!');
        }

        return back()->with('error', 'Selecione uma transação correspondente ou a conta bancária de contrapartida para conciliar.');
    }

    public function getSugestaoPessoa(TransacaoExtrato $transacao)
    {
        $pedidoId = $transacao->getPedidoId();
        if (!$pedidoId) {
            return response()->json(['success' => false, 'message' => 'Sem referência de pedido']);
        }

        $pedido = \App\Models\Pedido::with('user.perfilFinanceiro')->find($pedidoId);
        if (!$pedido || !$pedido->user) {
            return response()->json(['success' => false, 'message' => 'Pedido ou usuário não encontrado']);
        }

        $user = $pedido->user;
        $pessoa = $user->perfilFinanceiro;

        if (!$pessoa) {
            // Criar perfil financeiro automaticamente se não existir
            $pessoa = \App\Models\Pessoa::create([
                'user_id' => $user->id,
                'nome' => $user->name,
                'documento' => $user->cpf ?? $user->whatsapp ?? $user->phone,
                'tipo' => 'cliente_circular',
            ]);
            Log::info("Perfil financeiro criado automaticamente para User #{$user->id}");
        }

        return response()->json([
            'success' => true,
            'pessoa' => [
                'id' => $pessoa->id,
                'text' => "[#{$pessoa->id}] {$pessoa->nome}" . ($pessoa->documento ? " - {$pessoa->documento}" : '')
            ]
        ]);
    }

    public function buscarPessoas(Request $request)
    {
        $search = trim($request->get('q', ''));
        
        $pessoas = \App\Models\Pessoa::with('user')
            ->when($search, function($q) use ($search) {
                $q->where(function($sub) use ($search) {
                    $sub->where('nome', 'like', "%{$search}%")
                        ->orWhere('documento', 'like', "%{$search}%")
                        ->orWhere('id', $search)
                        ->orWhereHas('user', function($uQ) use ($search) {
                            $uQ->where('apelido', 'like', "%{$search}%")
                               ->orWhere('instagram', 'like', "%{$search}%")
                               ->orWhere('tiktok', 'like', "%{$search}%")
                               ->orWhere('name', 'like', "%{$search}%")
                               ->orWhere('nome_cliente', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%")
                               ->orWhere('cpf', 'like', "%{$search}%")
                               ->orWhere('whatsapp', 'like', "%{$search}%")
                               ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('nome')
            ->limit(30)
            ->get();
            
        $formatted = $pessoas->map(function($p) {
            $infoParts = [];
            if ($p->user?->apelido) {
                $infoParts[] = "Apelido: " . $p->user->apelido;
            }
            if ($p->user?->instagram) {
                $ig = $p->user->instagram;
                $infoParts[] = "IG: " . (str_starts_with($ig, '@') ? $ig : '@' . $ig);
            }
            if ($p->documento) {
                $infoParts[] = $p->documento;
            } elseif ($p->user?->cpf) {
                $infoParts[] = $p->user->cpf;
            }

            $infoStr = implode(' | ', $infoParts);

            return [
                'id' => $p->id,
                'nome' => $p->nome,
                'documento' => $p->documento,
                'apelido' => $p->user?->apelido,
                'instagram' => $p->user?->instagram,
                'info' => $infoStr,
                'text' => "[#{$p->id}] {$p->nome}" . ($infoStr ? " ({$infoStr})" : '')
            ];
        });

        return response()->json($formatted);
    }

    public function sincronizarInter(Request $request)
    {
        try {
            $count = $this->service->sincronizarBancoInter($request->start_date, $request->end_date);
            return back()->with('success', "{$count} transações sincronizadas do Banco Inter.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function buscarLancamentos(Request $request)
    {
        $search = $request->get('q', '');
        $tipo = $request->get('tipo', 'receita');

        $lancamentos = \App\Models\Lancamento::with('pessoa')
            ->where('tipo', $tipo)
            ->whereIn('status', ['pendente', 'pago_parcial'])
            ->when($search, function($q) use ($search) {
                $q->where(function($sub) use ($search) {
                    $sub->where('descricao', 'like', "%{$search}%")
                        ->orWhere('id', $search)
                        ->orWhere('valor_total', 'like', "%{$search}%")
                        ->orWhereHas('pessoa', function($pQ) use ($search) {
                            $pQ->where('nome', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('data_vencimento', 'asc')
            ->limit(30)
            ->get();

        $formatted = $lancamentos->map(function($l) {
            $totalPago = (float) $l->movimentacoes()->sum('valor_pago');
            $saldoRestante = max(0.00, (float) $l->valor_total - $totalPago);

            $valorFormatado = number_format($l->valor_total, 2, ',', '.');
            $restanteFormatado = number_format($saldoRestante, 2, ',', '.');
            $dataVencFormatado = $l->data_vencimento ? $l->data_vencimento->format('d/m/Y') : '';
            $contato = $l->pessoa?->nome ?? 'Sem Contato';
            return [
                'id' => $l->id,
                'descricao' => $l->descricao,
                'valor' => $l->valor_total,
                'saldo_restante' => $saldoRestante,
                'text' => "Venc: {$dataVencFormatado} | R$ {$valorFormatado} (Restam: R$ {$restanteFormatado}) | {$l->descricao} ({$contato})"
            ];
        });

        return response()->json($formatted);
    }

    public function auditoria(Request $request)
    {
        $contas = \App\Models\ContaBancaria::all();
        
        $selectedContaId = $request->get('conta_bancaria_id');
        if (!$selectedContaId) {
            $firstConta = $contas->first(fn($c) => !str_contains(strtolower($c->nome), 'carteira'));
            $selectedContaId = $firstConta ? $firstConta->id : ($contas->first()->id ?? null);
        }

        $conta = \App\Models\ContaBancaria::find($selectedContaId);

        if (!$conta) {
            return redirect()->route('financeiro.conciliacao.index')->with('error', 'Conta bancária não encontrada.');
        }

        // Determinar datas
        if ($request->has('data_inicio') || $request->has('data_fim')) {
            $dataInicio = $request->get('data_inicio');
            $dataFim = $request->get('data_fim');
        } else {
            $dataInicio = \Carbon\Carbon::now()->startOfMonth()->toDateString();
            $dataFim = \Carbon\Carbon::now()->endOfMonth()->toDateString();
        }

        $saldoInicial = (float) $conta->saldo_inicial;
        $saldoSistema = (float) $conta->saldo_atual;

        $transacoesQuery = \Illuminate\Support\Facades\DB::table('transacoes_extrato')
            ->where('conta_bancaria_id', $conta->id);

        $entradasConciliadas = (float) (clone $transacoesQuery)
            ->where('status', 'conciliado')
            ->where('tipo', 'entrada')
            ->sum('valor');

        $saidasConciliadas = (float) (clone $transacoesQuery)
            ->where('status', 'conciliado')
            ->where('tipo', 'saida')
            ->sum('valor');

        $saldoExtratoConciliado = $saldoInicial + $entradasConciliadas - $saidasConciliadas;

        $entradasTotal = (float) (clone $transacoesQuery)
            ->whereIn('status', ['conciliado', 'pendente'])
            ->where('tipo', 'entrada')
            ->sum('valor');

        $saidasTotal = (float) (clone $transacoesQuery)
            ->whereIn('status', ['conciliado', 'pendente'])
            ->where('tipo', 'saida')
            ->sum('valor');

        $saldoExtratoTotal = $saldoInicial + $entradasTotal - $saidasTotal;

        // 1. Transações Órfãs
        $transacoesOrfasQuery = \App\Models\TransacaoExtrato::where('conta_bancaria_id', $conta->id)
            ->where('status', 'conciliado')
            ->where(function($q) {
                $q->whereNull('movimentacao_id')
                  ->orWhereNotExists(function($sub) {
                      $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                          ->from('movimentacoes')
                          ->whereColumn('movimentacoes.id', 'transacoes_extrato.movimentacao_id');
                  });
            });

        if ($dataInicio) {
            $transacoesOrfasQuery->where('data', '>=', $dataInicio);
        }
        if ($dataFim) {
            $transacoesOrfasQuery->where('data', '<=', $dataFim);
        }
        $transacoesOrfas = $transacoesOrfasQuery->get();

        // 2. Divergências de Valores
        $divergenciasQuery = \App\Models\TransacaoExtrato::where('conta_bancaria_id', $conta->id)
            ->where('status', 'conciliado')
            ->whereNotNull('movimentacao_id')
            ->whereExists(function($sub) {
                $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('movimentacoes')
                    ->whereColumn('movimentacoes.id', 'transacoes_extrato.movimentacao_id');
            });

        if ($dataInicio) {
            $divergenciasQuery->where('data', '>=', $dataInicio);
        }
        if ($dataFim) {
            $divergenciasQuery->where('data', '<=', $dataFim);
        }

        $divergenciasValores = $divergenciasQuery->with('movimentacao')
            ->get()
            ->filter(function($t) {
                return $t->movimentacao && abs((float)$t->valor - (float)$t->movimentacao->valor_pago) >= 0.01;
            });

        // 3. Movimentações Manuais
        $movimentacoesQuery = \App\Models\Movimentacao::where('conta_bancaria_id', $conta->id)
            ->whereNull('transacao_extrato_id');

        if ($dataInicio) {
            $movimentacoesQuery->where('data_pagamento', '>=', $dataInicio);
        }
        if ($dataFim) {
            $movimentacoesQuery->where('data_pagamento', '<=', $dataFim);
        }

        $movimentacoesManuais = $movimentacoesQuery->with(['lancamento.pessoa', 'lancamento.classificacaoFinanceira'])
            ->orderBy('data_pagamento', 'desc')
            ->get();

        // 4. Transações Pendentes
        $pendentesQuery = \App\Models\TransacaoExtrato::where('conta_bancaria_id', $conta->id)
            ->where('status', 'pendente');

        if ($dataInicio) {
            $pendentesQuery->where('data', '>=', $dataInicio);
        }
        if ($dataFim) {
            $pendentesQuery->where('data', '<=', $dataFim);
        }

        $transacoesPendentes = $pendentesQuery->orderBy('data', 'desc')->get();

        return view('admin.financeiro.auditoria', compact(
            'contas',
            'conta',
            'saldoInicial',
            'saldoSistema',
            'saldoExtratoConciliado',
            'saldoExtratoTotal',
            'transacoesOrfas',
            'divergenciasValores',
            'movimentacoesManuais',
            'transacoesPendentes',
            'dataInicio',
            'dataFim'
        ));
    }

    public function desvincular(\App\Models\TransacaoExtrato $transacao)
    {
        try {
            \DB::transaction(function() use ($transacao) {
                if ($transacao->movimentacao_id) {
                    $mov = \App\Models\Movimentacao::find($transacao->movimentacao_id);
                    if ($mov) {
                        $mov->delete();
                    }
                }
                $transacao->update([
                    'status' => 'pendente',
                    'movimentacao_id' => null
                ]);
            });

            return back()->with('success', 'Transação desvinculada com sucesso! O lançamento e a transação correspondentes voltaram a ficar pendentes.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao desvincular transação: ' . $e->getMessage());
        }
    }

    public function salvarRegra(Request $request)
    {
        $request->validate([
            'id' => 'nullable|string',
            'descricao_banco' => 'required|string',
            'classificacao_financeira_id' => 'required|exists:classificacao_financeira,id',
            'pessoa_id' => 'nullable|exists:pessoas,id',
            'tipo' => 'nullable|string|in:sugestao,exclusao',
            'valor' => 'nullable',
        ]);

        $tipo = $request->input('tipo', 'sugestao');

        $valorInput = $request->input('valor');
        $valorFinal = null;
        if ($valorInput !== null && $valorInput !== '') {
            if (is_numeric($valorInput)) {
                $valorFinal = (float) $valorInput;
            } else {
                $cleanValor = str_replace('R$', '', (string) $valorInput);
                $cleanValor = trim($cleanValor);
                if (str_contains($cleanValor, ',')) {
                    $cleanValor = str_replace('.', '', $cleanValor);
                    $cleanValor = str_replace(',', '.', $cleanValor);
                }
                if (is_numeric($cleanValor)) {
                    $valorFinal = (float) $cleanValor;
                }
            }
        }

        try {
            \DB::transaction(function() use ($request, $tipo, $valorFinal) {
                $config = \DB::table('configuracoes')->where('chave', 'regras_conciliacao')->first();
                $regras = $config ? json_decode($config->valor, true) : [];
                if (!is_array($regras)) {
                    $regras = [];
                }

                $ruleId = $request->input('id');
                $descricaoBanco = trim($request->descricao_banco);
                $pessoaId = $request->pessoa_id ? (int) $request->pessoa_id : null;
                $classificacaoId = (int) $request->classificacao_financeira_id;

                $updated = false;

                if (!empty($ruleId)) {
                    foreach ($regras as &$r) {
                        if (($r['id'] ?? null) === $ruleId) {
                            $r['descricao_banco'] = $descricaoBanco;
                            $r['classificacao_financeira_id'] = $classificacaoId;
                            $r['pessoa_id'] = $pessoaId;
                            $r['tipo'] = $tipo;
                            $r['valor'] = $valorFinal;
                            $updated = true;
                            break;
                        }
                    }
                }

                if (!$updated) {
                    foreach ($regras as &$r) {
                        $rValor = isset($r['valor']) && $r['valor'] !== '' && $r['valor'] !== null ? (float)$r['valor'] : null;
                        $sameValor = ($rValor === null && $valorFinal === null) || ($rValor !== null && $valorFinal !== null && abs($rValor - $valorFinal) < 0.001);

                        if (mb_strtolower($r['descricao_banco'], 'UTF-8') === mb_strtolower($descricaoBanco, 'UTF-8')
                            && ($r['tipo'] ?? 'sugestao') === $tipo
                            && $sameValor
                            && ($tipo === 'sugestao' || ( ($r['pessoa_id'] ?? null) == $pessoaId && ($r['classificacao_financeira_id'] ?? null) == $classificacaoId ))
                        ) {
                            $r['classificacao_financeira_id'] = $classificacaoId;
                            $r['pessoa_id'] = $pessoaId;
                            $r['valor'] = $valorFinal;
                            $updated = true;
                            break;
                        }
                    }
                }

                if (!$updated) {
                    $regras[] = [
                        'id' => $ruleId ?: uniqid(),
                        'tipo' => $tipo,
                        'descricao_banco' => $descricaoBanco,
                        'classificacao_financeira_id' => $classificacaoId,
                        'pessoa_id' => $pessoaId,
                        'valor' => $valorFinal,
                    ];
                }

                \DB::table('configuracoes')->updateOrInsert(
                    ['chave' => 'regras_conciliacao'],
                    ['valor' => json_encode($regras), 'updated_at' => now()]
                );
            });

            // Limpa o cache para que a auto-conciliação aplique a nova regra imediatamente
            \Illuminate\Support\Facades\Cache::forget('last_auto_conciliacao_at');
            $this->service->autoConciliarTransacoesPendentes();

            // Se um ID de transação foi enviado, concilia ela diretamente agora caso ainda esteja pendente
            $conciliadaAgora = false;
            if ($request->filled('transacao_id') && $tipo !== 'exclusao') {
                $trans = TransacaoExtrato::find($request->transacao_id);
                if ($trans && $trans->status === 'pendente') {
                    $this->service->vincularNovoLancamento(
                        $trans->id,
                        (int) $request->classificacao_financeira_id,
                        $request->pessoa_id ? (int) $request->pessoa_id : null,
                        $trans->conta_bancaria_id
                    );
                    $conciliadaAgora = true;
                }
            }

            if ($tipo === 'exclusao') {
                $mensagem = 'Sugestão bloqueada com sucesso!';
            } elseif ($conciliadaAgora) {
                $mensagem = '✅ Regra salva como Padrão e transação conciliada com sucesso!';
            } else {
                $mensagem = 'Regra de conciliação padrão salva com sucesso!';
            }

            return back()->with('success', $mensagem);
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao salvar regra: ' . $e->getMessage());
        }
    }

    public function excluirRegra($id)
    {
        try {
            \DB::transaction(function() use ($id) {
                $config = \DB::table('configuracoes')->where('chave', 'regras_conciliacao')->first();
                if ($config) {
                    $regras = json_decode($config->valor, true) ?: [];
                    $regrasFiltradas = array_filter($regras, function($r) use ($id) {
                        return $r['id'] !== $id;
                    });
                    
                    \DB::table('configuracoes')->where('chave', 'regras_conciliacao')->update([
                        'valor' => json_encode(array_values($regrasFiltradas)),
                        'updated_at' => now()
                    ]);
                }
            });

            return back()->with('success', 'Regra de conciliação padrão excluída com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao excluir regra: ' . $e->getMessage());
        }
    }
}

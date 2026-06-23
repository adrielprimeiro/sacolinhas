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

    public function index()
    {
        $extrato = TransacaoExtrato::where('status', 'pendente')
            ->orderBy('data', 'desc')
            ->get();

        $lancamentos = Lancamento::with(['pessoa', 'classificacaoFinanceira'])
            ->where(function ($q) {
                $q->where('status', 'pendente')
                    ->orWhere(function ($q2) {
                        // Também mostra os pagos que ainda não foram vinculados a nenhuma transação do extrato
                        $q2->where('status', 'pago')
                            ->whereDoesntHave('movimentacoes', function ($q3) {
                                $q3->whereHas('transacaoExtrato');
                            });
                    });
            })
            ->orderBy('data_vencimento', 'asc')
            ->get();

        $extratoComSugestoes = $extrato->map(function ($t) use ($lancamentos) {
            $pedidoIdRef = $t->getPedidoId();
            
            // 1. Buscar histórico para a descrição exata do banco
            $paresHistoricos = [];
            
            // Lançamentos pagos/com movimentação no passado com essa descrição
            $historicos = \App\Models\Lancamento::where('descricao', $t->descricao)
                ->whereNotNull('classificacao_financeira_id')
                ->where(function ($q) {
                    $q->whereIn('status', ['pago', 'pago_parcial'])
                      ->orWhereHas('movimentacoes');
                })
                ->get(['pessoa_id', 'classificacao_financeira_id']);
                
            foreach ($historicos as $h) {
                if ($h->pessoa_id && $h->classificacao_financeira_id) {
                    $key = $h->pessoa_id . '-' . $h->classificacao_financeira_id;
                    $paresHistoricos[$key] = [
                        'pessoa_id' => $h->pessoa_id,
                        'classificacao_id' => $h->classificacao_financeira_id
                    ];
                }
            }

            // Transações já conciliadas no passado com essa descrição
            $transacoesConciliadas = \App\Models\TransacaoExtrato::where('status', 'conciliado')
                ->where('descricao', $t->descricao)
                ->whereHas('movimentacao.lancamento')
                ->with('movimentacao.lancamento')
                ->get();
                
            foreach ($transacoesConciliadas as $tc) {
                $l = $tc->movimentacao->lancamento;
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

            // B. Adicionar Sugestões Virtuais de Criação Rápida (Score 90)
            foreach ($paresHistoricos as $par) {
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

            $sugestoes = $sugestoesFinal
                ->sortByDesc('score')
                ->take(5)
                ->values();

            return [
                'transacao' => $t,
                'sugestoes' => $sugestoes
            ];
        });

        $lancamentosReceita = $lancamentos->where('tipo', 'receita')->values();
        $lancamentosDespesa = $lancamentos->where('tipo', 'despesa')->values();

        $classificacoes = ClassificacaoFinanceira::all();
        $pessoas = Pessoa::all(['id', 'nome']);
        $contas = \App\Models\ContaBancaria::all(['id', 'nome']);

        return view('admin.financeiro.conciliacao', compact(
            'extratoComSugestoes',
            'lancamentosReceita',
            'lancamentosDespesa',
            'classificacoes',
            'pessoas',
            'contas'
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
            $this->service->vincularNovoLancamento(
                $request->transacao_id,
                $request->classificacao_financeira_id,
                $request->pessoa_id,
                $request->conta_bancaria_id
            );
            return back()->with('success', 'Lançamento criado e conciliado!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function ignorar(TransacaoExtrato $transacao)
    {
        $transacao->update(['status' => 'ignorado']);
        return back()->with('success', 'Transação ignorada.');
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
        $search = $request->get('q', '');
        
        $pessoas = \App\Models\Pessoa::when($search, function($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('documento', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            })
            ->orderBy('nome')
            ->limit(30)
            ->get(['id', 'nome', 'documento']);
            
        return response()->json($pessoas);
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
}

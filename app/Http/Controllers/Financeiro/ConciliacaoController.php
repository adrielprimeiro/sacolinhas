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
            
            // Limpa e divide a descrição em termos de busca (palavras >= 3 letras)
            $cleanDesc = preg_replace('/[^A-Za-z0-9\s]/', '', $t->descricao);
            $descWords = array_filter(explode(' ', $cleanDesc), function ($w) {
                return strlen($w) >= 3;
            });

            $sugestoes = $lancamentos->filter(function ($l) use ($t) {
                // Filtra pelo tipo correspondente: entrada -> receita, saida -> despesa
                $tTipoMapped = ($t->tipo === 'entrada') ? 'receita' : 'despesa';
                return $l->tipo === $tTipoMapped;
            })->map(function ($l) use ($t, $pedidoIdRef, $descWords) {
                $score = 0;
                $motivos = [];

                // 1. Coincidência de Valor (Bruto ou Líquido)
                $valorMatches = false;
                if (abs((float)$t->valor - (float)$l->valor_total) < 0.01) {
                    $score += 15;
                    $valorMatches = true;
                    $motivos[] = 'Valor exato';
                } elseif ($t->valor_liquido && abs((float)$t->valor_liquido - (float)$l->valor_total) < 0.01) {
                    $score += 12;
                    $valorMatches = true;
                    $motivos[] = 'Valor líquido exato';
                }

                // 2. Coincidência de Referência/Pedido
                $refMatches = false;
                if ($pedidoIdRef && $l->referencia_tipo === 'pedido' && $l->referencia_id == $pedidoIdRef) {
                    $score += 25;
                    $refMatches = true;
                    $motivos[] = 'Pedido correspondente';
                }

                // 3. Proximidade de Data de Vencimento
                $diffInDays = abs($t->data->diffInDays($l->data_vencimento));
                if ($diffInDays == 0) {
                    $score += 6;
                    $motivos[] = 'Vencimento hoje';
                } elseif ($diffInDays <= 3) {
                    $score += 4;
                    $motivos[] = 'Vencimento próximo (até 3 dias)';
                } elseif ($diffInDays <= 7) {
                    $score += 2;
                    $motivos[] = 'Vencimento próximo (até 7 dias)';
                }

                // 4. Coincidência Textual
                $textMatches = false;
                $lDesc = strtolower($l->descricao);
                $lPessoaNome = $l->pessoa ? strtolower($l->pessoa->nome) : '';
                
                foreach ($descWords as $word) {
                    $wordLower = strtolower($word);
                    // Evitar termos genéricos como "PIX", "TED", "PAGAMENTO"
                    if (in_array($wordLower, ['pix', 'ted', 'doc', 'pag', 'pagamento', 'recebido', 'enviado'])) {
                        continue;
                    }
                    if (str_contains($lDesc, $wordLower)) {
                        $score += 3;
                        $textMatches = true;
                        if (!in_array('Descrição semelhante', $motivos)) {
                            $motivos[] = 'Descrição semelhante';
                        }
                    }
                    if ($lPessoaNome && str_contains($lPessoaNome, $wordLower)) {
                        $score += 5;
                        $textMatches = true;
                        if (!in_array('Nome semelhante', $motivos)) {
                            $motivos[] = 'Nome semelhante';
                        }
                    }
                }

                $l->score = $score;
                $l->motivos_match = $motivos;

                // Para sugerir, o registro deve coincidir no valor, na referência ou no texto
                // Evitamos sugerir registros que apenas calham de ter vencimento próximo
                $l->is_valid_suggestion = $valorMatches || $refMatches || $textMatches;

                return $l;
            })
            ->filter(function ($l) {
                return $l->is_valid_suggestion && $l->score > 0;
            })
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
}

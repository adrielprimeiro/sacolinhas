<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\Lancamento;
use App\Models\Pessoa;
use App\Models\ClassificacaoFinanceira;
use App\Models\ContaBancaria;
use App\Services\LancamentoBaixaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class LancamentoController extends Controller
{
    public function __construct(private LancamentoBaixaService $baixaService) {}

    /**
     * Lista lançamentos com filtros e Eager Loading (sem N+1).
     */
    public function index(Request $request)
    {
        $query = Lancamento::with(['pessoa', 'classificacaoFinanceira', 'movimentacoes'])
            ->orderBy('data_vencimento');

        // Aba / filtro de tipo
        $aba = $request->get('aba', 'todos');

        match ($aba) {
            'pagar'     => $query->where('tipo', 'despesa')->whereIn('status', ['pendente', 'pago_parcial']),
            'receber'   => $query->where('tipo', 'receita')->whereIn('status', ['pendente', 'pago_parcial']),
            'atrasados' => $query->whereIn('status', ['pendente', 'pago_parcial'])
                                 ->whereDate('data_vencimento', '<', Carbon::today()),
            default     => null,
        };

        // Filtros adicionais
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('pessoa_id')) {
            $query->where('pessoa_id', $request->pessoa_id);
        }

        if ($request->filled('classificacao_id')) {
            $query->where('classificacao_financeira_id', $request->classificacao_id);
        }

        if ($request->filled('de')) {
            $query->whereDate('data_vencimento', '>=', $request->de);
        }

        if ($request->filled('ate')) {
            $query->whereDate('data_vencimento', '<=', $request->ate);
        }

        $lancamentos = $query->paginate(25)->withQueryString();

        // Dados para os selects do formulário
        $contas = ContaBancaria::orderBy('nome')->get();

        return view('admin.financeiro.lancamentos.index', compact('lancamentos', 'aba', 'contas'));
    }

    /**
     * Salva um novo lançamento.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo'                       => ['required', Rule::in(['receita', 'despesa'])],
            'pessoa_id'                  => ['required', 'exists:pessoas,id'],
            'classificacao_financeira_id' => ['required', 'exists:classificacao_financeira,id'],
            'data_emissao'               => ['required', 'date'],
            'data_vencimento'            => ['required', 'date'],
            'valor_total'                => ['required', 'numeric', 'min:0.01'],
            'descricao'                  => ['nullable', 'string', 'max:255'],
        ]);

        Lancamento::create($data);

        return response()->json(['success' => true, 'message' => 'Lançamento criado com sucesso.']);
    }

    /**
     * Retorna dados de um lançamento para edição (via AJAX).
     */
    public function show(Lancamento $lancamento)
    {
        $lancamento->load(['pessoa', 'classificacaoFinanceira', 'movimentacoes.contaBancaria']);
        return response()->json($lancamento);
    }

    /**
     * Atualiza um lançamento (somente campos permitidos).
     */
    public function update(Request $request, Lancamento $lancamento)
    {
        if ($lancamento->status === 'pago') {
            // Para lançamentos já pagos, permitimos editar a categoria, descrição, pessoa e datas,
            // mas mantemos o tipo e o valor inalterados por segurança.
            $data = $request->validate([
                'pessoa_id'                  => ['required', 'exists:pessoas,id'],
                'classificacao_financeira_id' => ['required', 'exists:classificacao_financeira,id'],
                'data_emissao'               => ['required', 'date'],
                'data_vencimento'            => ['required', 'date'],
                'descricao'                  => ['nullable', 'string', 'max:255'],
            ]);
            
            $data['valor_total'] = $lancamento->valor_total;
            $data['tipo'] = $lancamento->tipo;
        } else {
            $data = $request->validate([
                'tipo'                       => ['required', Rule::in(['receita', 'despesa'])],
                'pessoa_id'                  => ['required', 'exists:pessoas,id'],
                'classificacao_financeira_id' => ['required', 'exists:classificacao_financeira,id'],
                'data_emissao'               => ['required', 'date'],
                'data_vencimento'            => ['required', 'date'],
                'valor_total'                => ['required', 'numeric', 'min:0.01'],
                'descricao'                  => ['nullable', 'string', 'max:255'],
            ]);
        }

        \DB::transaction(function() use ($lancamento, $data) {
            $lancamento->update($data);

            // Se for vinculado a um crédito de carteira (Conta Corrente), atualiza a classificação na carteira
            if ($lancamento->referencia_tipo === 'carteira_credito') {
                $cc = \App\Models\ContaCorrente::find($lancamento->referencia_id);
                if ($cc) {
                    \App\Models\ContaCorrente::withoutEvents(function() use ($cc, $lancamento) {
                        $cc->update([
                            'classificacao_id' => $lancamento->classificacao_financeira_id,
                            'descricao'        => str_replace("Crédito Cliente: ", "", $lancamento->descricao)
                        ]);
                    });
                }
            }

            // Sincronizar carteira para cada movimentação do lançamento
            foreach ($lancamento->movimentacoes as $movimentacao) {
                $movimentacao->unsetRelation('lancamento');
                $movimentacao->sincronizarCarteira();
            }
        });

        return response()->json(['success' => true, 'message' => 'Lançamento atualizado com sucesso.']);
    }

    /**
     * Exclui um lançamento (somente se sem movimentações).
     */
    public function destroy(Lancamento $lancamento)
    {
        if ($lancamento->movimentacoes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir um lançamento com movimentações. Cancele-o primeiro.',
            ], 422);
        }

        $lancamento->delete();

        return response()->json(['success' => true, 'message' => 'Lançamento excluído.']);
    }

    /**
     * Realiza a baixa (pagamento) de um lançamento.
     */
    public function baixar(Request $request, Lancamento $lancamento)
    {
        $data = $request->validate([
            'data_pagamento'   => ['required', 'date'],
            'valor_pago'       => ['required', 'numeric', 'min:0.01'],
            'conta_bancaria_id' => ['required', 'exists:contas_bancarias,id'],
            'forma_pagamento'  => ['required', Rule::in(['pix', 'boleto', 'cartao_credito', 'dinheiro', 'transferencia'])],
        ]);

        try {
            $movimentacao = $this->baixaService->baixar(
                lancamento:      $lancamento,
                dataPagamento:   $data['data_pagamento'],
                valorPago:       (float) $data['valor_pago'],
                contaBancariaId: (int)   $data['conta_bancaria_id'],
                formaPagamento:  $data['forma_pagamento'],
            );

            return response()->json([
                'success'     => true,
                'message'     => 'Baixa registrada com sucesso!',
                'novo_status' => $lancamento->fresh()->status,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Cancela um lançamento via AJAX.
     */
    public function cancelar(Lancamento $lancamento)
    {
        try {
            $this->baixaService->cancelar($lancamento);
            return response()->json(['success' => true, 'message' => 'Lançamento cancelado.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Busca Pessoas para o select2 assíncrono.
     */
    public function searchPessoas(Request $request)
    {
        $term = $request->get('q', '');

        $pessoas = Pessoa::where(function($q) use ($term) {
                $q->where('nome', 'like', "%{$term}%")
                  ->orWhere('documento', 'like', "%{$term}%")
                  ->orWhere('id', $term);
            })
            ->limit(30)
            ->get(['id', 'nome', 'tipo', 'documento']);

        return response()->json($pessoas->map(fn ($p) => [
            'id'   => $p->id,
            'text' => "[#{$p->id}] {$p->nome}" . ($p->documento ? " - {$p->documento}" : ''),
            'tipo' => $p->tipo,
        ]));
    }

    /**
     * Busca Classificações para o select2 assíncrono.
     */
    public function searchClassificacoes(Request $request)
    {
        $term = $request->get('q', '');

        $classificacoes = ClassificacaoFinanceira::where('nome', 'like', "%{$term}%")
            ->orWhere('codigo_contabil', 'like', "%{$term}%")
            ->limit(50)
            ->get(['id', 'nome', 'codigo_contabil', 'tipo_natureza']);

        return response()->json($classificacoes->map(fn ($c) => [
            'id'   => $c->id,
            'text' => ($c->codigo_contabil ? "[{$c->codigo_contabil}] " : '') . $c->nome,
            'tipo' => $c->tipo_natureza,
        ]));
    }

    // --- Helpers ---

    private function abortSeJaPago(Lancamento $lancamento): void
    {
        if ($lancamento->status === 'pago') {
            abort(422, 'Não é possível editar um lançamento já pago integralmente.');
        }
    }
}

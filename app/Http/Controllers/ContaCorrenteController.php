<?php

namespace App\Http\Controllers;

use App\Models\ContaCorrente;
use App\Models\ClassificacaoFinanceira;
use App\Models\User; 
use App\Jobs\RecalcularSaldosJob; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 
use Illuminate\Validation\Rule;

class ContaCorrenteController extends Controller
{
    /**
     * Exibe uma lista paginada de todos os lançamentos de conta corrente.
     */

	public function index(Request $request)
	{
		$query = \App\Models\ContaCorrente::query()
			->with(['classificacaoFinanceira', 'user']);

		$query->when($request->filled('q'), function ($q) use ($request) {
			$term = $request->input('q');
			$q->where('descricao', 'like', '%' . $term . '%');
		});

		$query->when($request->filled('tipo'), function ($q) use ($request) {
			$q->where('tipo_movimentacao', $request->input('tipo'));
		});

		$query->when($request->filled('user_id'), function ($q) use ($request) {
			$q->where('user_id', (int) $request->input('user_id'));
		});

		$query->when($request->filled('de'), function ($q) use ($request) {
			$q->whereDate('data_movimentacao', '>=', $request->input('de'));
		});

		$query->when($request->filled('ate'), function ($q) use ($request) {
			$q->whereDate('data_movimentacao', '<=', $request->input('ate'));
		});

		$movimentacoes = $query
			->orderByDesc('data_movimentacao')
			->orderByDesc('id')
			->paginate(10);

		// Carrega apenas o usuário selecionado no filtro (se houver), para exibir o nome pré-preenchido
		$selectedUser = $request->filled('user_id')
			? \App\Models\User::find((int) $request->input('user_id'))
			: null;

		return view('admin.financeiro.bkp.index', compact('movimentacoes', 'selectedUser'));
	}


    /**
     * Exibe o formulário para adicionar um novo lançamento.
	 * Inclui a seleção de usuário.
     */
    public function create()
    {
        $classificacoes = ClassificacaoFinanceira::all();
        $tiposMovimentacao = ['debito', 'credito'];
        $referenciaTipos = ['sacolinha', 'pagamento', 'pedido', 'ajuste', 'desconto'];

        // Carrega apenas o usuário do old() input se houver (para re-preencher após erro de validação)
        $selectedUser = old('user_id') ? User::find((int) old('user_id')) : null;

        return view('admin.financeiro.bkp.create', compact('classificacoes', 'tiposMovimentacao', 'referenciaTipos', 'selectedUser'));
    }

    /**
     * Salva um novo lançamento no banco de dados.
     */
    public function store(Request $request)
    {
        $request->validate($this->rules());

        DB::transaction(function () use ($request) {
            $data = $request->except(['_token']); // Não precisa mais de 'classificacao_financeira_id' no except
            $data['user_id'] = $request->input('user_id');

            $data['classificacao_id'] = $request->input('classificacao_id'); // Usando a nova coluna
            $data['referencia_tipo'] = $request->input('referencia_tipo', null); // Padrão null se não enviado
            $data['referencia_id'] = $request->input('referencia_id', null);     // Padrão null se não enviado

            // Saldo anterior e atual serão calculados pelo Job
            $data['saldo_anterior'] = 0;
            $data['saldo_atual'] = 0;

            $movimentacao = ContaCorrente::create($data);

            RecalcularSaldosJob::dispatch($movimentacao->user_id, $movimentacao->data_movimentacao->toDateString());
        });

        return redirect()->route('admin.conta_corrente.index')->with('success', 'Lançamento criado com sucesso!');
    }


    /**
     * Exibe os detalhes de um lançamento específico.
     */
    public function show(ContaCorrente $financeiro) // Usando $financeiro para corresponder ao nome da rota 'financeiro'
    {
        $financeiro->load('classificacaoFinanceira', 'user');
        return view('admin.financeiro.bkp.show', compact('financeiro'));
    }

    /**
     * Exibe o formulário para editar um lançamento existente.
     */
    public function edit(ContaCorrente $financeiro) // Usando $financeiro para corresponder ao nome da rota 'financeiro'
    {
        $classificacoes = ClassificacaoFinanceira::all();
        $tiposMovimentacao = ['debito', 'credito'];
        $referenciaTipos = ['sacolinha', 'pagamento', 'pedido', 'ajuste', 'desconto', 'classificacao'];

        return view('admin.financeiro.bkp.edit', compact('financeiro', 'classificacoes', 'tiposMovimentacao', 'referenciaTipos'));
    }

    /**
     * Atualiza um lançamento existente no banco de dados.
     */
    public function update(Request $request, ContaCorrente $financeiro)
    {
        $request->validate($this->rules($financeiro->id));

        DB::transaction(function () use ($request, $financeiro) {
            $data = $request->except(['_token', '_method']); 
			
            $data['user_id'] = $request->input('user_id');
            
			$data['classificacao_id'] = $request->input('classificacao_id'); 
            $data['referencia_tipo'] = $request->input('referencia_tipo', null);
            $data['referencia_id'] = $request->input('referencia_id', null);

            $financeiro->update($data);

            RecalcularSaldosJob::dispatch($financeiro->user_id, $financeiro->data_movimentacao->toDateString());
        });

        return redirect()->route('admin.conta_corrente.index')->with('success', 'Lançamento atualizado com sucesso!');
    }


    /**
     * Exclui um lançamento do banco de dados.
     */
        public function destroy(ContaCorrente $financeiro)
        {
            DB::transaction(function () use ($financeiro) {
                $userId = $financeiro->user_id;
                $dataMovimentacao = $financeiro->data_movimentacao->toDateString(); 
				
                $financeiro->delete();

                // Despacha o Job para recalcular os saldos em segundo plano
                RecalcularSaldosJob::dispatch($userId, $dataMovimentacao);
            });

            return redirect()->route('admin.conta_corrente.index')->with('success', 'Lançamento excluído com sucesso!');
        }

    /**
     * Regras de validação para o lançamento de conta corrente.
     */
    protected function rules(?int $ignoreId = null): array
    {
        return [
			'user_id' => ['required', 'exists:users,id'],
            'tipo_movimentacao' => ['required', Rule::in(['debito', 'credito'])],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'classificacao_id' => ['required', 'exists:classificacao_financeira,id'], // <--- NOVO: classif. é obrigatória
            'referencia_tipo' => ['nullable', Rule::in(['sacolinha', 'pagamento', 'pedido', 'ajuste', 'desconto', 'movimentacao'])], // <--- AJUSTADO: nullable
            'referencia_id' => ['nullable', 'numeric'], // <--- AJUSTADO: nullable
            'data_movimentacao' => ['required', 'date'],
            'observacoes' => ['nullable', 'string'],
        ];
    }


    /**
     * Autoriza que apenas o proprietário da movimentação possa visualizá-la/editá-la/excluí-la.
     */
    protected function authorizeUser(ContaCorrente $financeiro)
    {
        if ($financeiro->user_id !== Auth::id()) {
            abort(403, 'Acesso não autorizado.');
        }
    }
	
   /**
     * Registra um lançamento de débito na Conta Corrente ao concluir um pedido.
     * Acessado via AJAX.
     */
    public function registrarDebitoConclusao(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'pedido_id' => ['required', 'exists:pedidos,id'], // Assumindo que 'pedidos' é o nome da sua tabela
            'pedido_numero' => ['required', 'string'],
        ]);

        try {
            DB::transaction(function () use ($request) {
                $data = [
                    'user_id' => $request->input('user_id'),
                    'tipo_movimentacao' => 'debito',
                    'valor' => $request->input('valor'),
                    'descricao' => 'Conclusão de Pedido: ' . $request->input('pedido_numero'),
                    'classificacao_id' => 1, // ID da Classificação Financeira para 'Conclusão de Pedido'
                                              // Ajuste este ID conforme sua base de dados
                    'referencia_tipo' => 'pedido',
                    'referencia_id' => $request->input('pedido_id'),
                    'data_movimentacao' => now(), // Data e hora atual
                    'observacoes' => 'Débito automático pela conclusão do pedido.',
                    'saldo_anterior' => 0, // Será recalculado pelo Job
                    'saldo_atual' => 0,    // Será recalculado pelo Job
                ];

                $movimentacao = ContaCorrente::updateOrCreate(
                    [
                        'referencia_tipo' => 'pedido',
                        'referencia_id' => $request->input('pedido_id'),
                        'tipo_movimentacao' => 'debito',
                    ],
                    $data
                );

                // Despacha o Job para recalcular os saldos a partir da data da nova movimentação
                RecalcularSaldosJob::dispatch($movimentacao->user_id, $movimentacao->data_movimentacao->toDateString());
            });

            return response()->json(['success' => true, 'message' => 'Lançamento de débito do pedido criado com sucesso!']);

        } catch (\Exception $e) {
            Log::error('Erro ao registrar débito de conclusão de pedido: ' . $e->getMessage(), [
                'user_id' => $request->input('user_id'),
                'pedido_id' => $request->input('pedido_id'),
                'valor' => $request->input('valor'),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao registrar débito.'], 500);
        }
    }	
}
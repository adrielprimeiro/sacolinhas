<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminPedidoController extends Controller
{
	public function index(Request $request)
	{
		$query = Pedido::query()->with('user');

		$query->when($request->filled('numero_pedido'), function ($q) use ($request) {
			$q->where('numero_pedido', 'like', '%' . $request->input('numero_pedido') . '%');
		});

		$query->when($request->filled('cliente'), function ($q) use ($request) {
			$term = $request->input('cliente');

			$q->whereHas('user', function ($uq) use ($term) {
				$uq->where('name', 'like', '%' . $term . '%')
				   ->orWhere('email', 'like', '%' . $term . '%');
			});
		});

		$query->when($request->filled('status_pedido'), function ($q) use ($request) {
			$q->where('status_pedido', $request->input('status_pedido'));
		});

		$query->when($request->filled('origem_pedido'), function ($q) use ($request) {
			$q->where('origem_pedido', $request->input('origem_pedido'));
		});

		$query->when($request->filled('status_pagamento'), function ($q) use ($request) {
			$q->where('status_pagamento', $request->input('status_pagamento'));
		});

		$query->when($request->filled('de'), function ($q) use ($request) {
			$q->whereDate('data_pedido', '>=', $request->input('de'));
		});

		$query->when($request->filled('ate'), function ($q) use ($request) {
			$q->whereDate('data_pedido', '<=', $request->input('ate'));
		});

		$pedidos = $query
			->orderByDesc('data_pedido')
			->orderByDesc('id')
			->paginate(15);

		return view('admin.pedidos.index', compact('pedidos'));
	}

    public function create()
    {
        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->limit(200)
            ->get();

        return view('admin.pedidos.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        DB::transaction(function () use ($validated) {
            Pedido::create($validated);
        });

        return redirect()
            ->route('admin.pedido.index')
            ->with('success', 'Pedido criado com sucesso.');
    }

    public function show(Pedido $pedido)
    {
        $pedido->load('user');

        return view('admin.pedidos.show', compact('pedido'));
    }

    public function edit(Pedido $pedido)
    {
        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->limit(200)
            ->get();

        return view('admin.pedidos.edit', compact('pedido', 'users'));
    }

    public function update(Request $request, Pedido $pedido)
    {
        $validated = $request->validate($this->rules($pedido->id));

        DB::transaction(function () use ($validated, $pedido) {
            $pedido->update($validated);
        });

        return redirect()
            ->route('admin.pedido.index')
            ->with('success', 'Pedido atualizado com sucesso.');
    }

    public function destroy(Pedido $pedido)
    {
        DB::transaction(function () use ($pedido) {
            $pedido->delete();
        });

        return redirect()
            ->route('admin.pedido.index')
            ->with('success', 'Pedido excluído com sucesso.');
    }

    private function rules(?int $id = null): array
    {
        return [
            'numero_pedido' => [
                'required',
                'string',
                'max:20',
                Rule::unique('pedidos', 'numero_pedido')->ignore($id),
            ],

            'user_id' => ['required', 'exists:users,id'],
            'live_id' => ['nullable', 'integer'],

            'data_pedido' => ['required', 'date'],

            'status_pedido' => [
                'required',
                Rule::in(['pendente', 'confirmado', 'processando', 'enviado', 'entregue', 'cancelado']),
            ],

            'valor_total' => ['required', 'numeric', 'min:0'],
            'valor_frete' => ['required', 'numeric', 'min:0'],
            'valor_desconto' => ['required', 'numeric', 'min:0'],

            'forma_pagamento' => [
                'nullable',
                Rule::in(['pix', 'cartao_credito', 'cartao_debito', 'boleto', 'dinheiro', 'transferencia']),
            ],

            'status_pagamento' => [
                'required',
                Rule::in(['pendente', 'aprovado', 'rejeitado', 'estornado']),
            ],

            'endereco_entrega' => ['nullable', 'string'],
            'cep_entrega' => ['nullable', 'string', 'max:10'],
            'cidade_entrega' => ['nullable', 'string', 'max:255'],
            'estado_entrega' => ['nullable', 'string', 'size:2'],

            'codigo_rastreamento' => ['nullable', 'string', 'max:50'],

            'data_envio' => ['nullable', 'date'],
            'data_entrega_prevista' => ['nullable', 'date'],
            'data_entrega_realizada' => ['nullable', 'date'],

            'observacoes' => ['nullable', 'string'],
            'cupom_desconto' => ['nullable', 'string', 'max:50'],

            'origem_pedido' => [
                'required',
                Rule::in(['live', 'site', 'whatsapp', 'instagram']),
            ],
        ];
    }

	public function devolucao(Request $request, Pedido $pedido)
	{
		$validated = $request->validate([
			'itens_devolver' => ['required', 'array', 'min:1'],
			'itens_devolver.*' => ['integer'],
		]);

		$ids = $validated['itens_devolver'];

		DB::transaction(function () use ($pedido, $ids) {
			// Pega itens do pedido (somente os selecionados e pertencentes ao pedido)
			$itens = DB::table('items_pedido')
				->where('pedido_id', $pedido->id)
				->whereIn('id', $ids)
				->select(['id', 'valor_total', 'status_item'])
				->lockForUpdate()
				->get();

			if ($itens->isEmpty()) {
				throw new \RuntimeException('Nenhum item válido selecionado para devolução.');
			}

			// Opcional: impedir devolução duplicada
			$idsValidos = $itens
				->filter(fn($i) => $i->status_item !== 'devolvido')
				->pluck('id')
				->values()
				->all();

			if (empty($idsValidos)) {
				return; // nada a fazer
			}

			$valorDevolver = (float) $itens
				->filter(fn($i) => in_array($i->id, $idsValidos))
				->sum(fn($i) => (float) ($i->valor_total ?? 0));

			// Atualiza status dos itens
			DB::table('items_pedido')
				->where('pedido_id', $pedido->id)
				->whereIn('id', $idsValidos)
				->update([
					'status_item' => 'devolvido',
					'updated_at' => now(),
				]);

			// Saldo atual do usuário (último movimento)
			$ultimo = DB::table('conta_corrente')
				->where('user_id', $pedido->user_id)
				->orderByDesc('id')
				->lockForUpdate()
				->first();

			$saldoAnterior = $ultimo ? (float) $ultimo->saldo_atual : 0.0;
			$saldoAtual = $saldoAnterior + $valorDevolver;

			// Insere crédito na conta_corrente
			DB::table('conta_corrente')->insert([
				'user_id' => $pedido->user_id,
				'tipo_movimentacao' => 'credito',
				'valor' => $valorDevolver,
				'descricao' => 'Crédito por devolução de itens do pedido ' . ($pedido->numero_pedido ?? $pedido->id),
				'referencia_tipo' => 'pedido',
				'referencia_id' => $pedido->id,
				'live_id' => $pedido->live_id,
				'saldo_anterior' => $saldoAnterior,
				'saldo_atual' => $saldoAtual,
				'data_movimentacao' => now(),
				'observacoes' => 'Itens devolvidos: ' . implode(',', $idsValidos),
				'classificacao_id' => 81, // ajuste para o ID correto no seu sistema
				'created_at' => now(),
				'updated_at' => now(),
			]);
		});

		return redirect()
			->route('admin.pedido.edit', $pedido->id)
			->with('success', 'Devolução registrada e crédito lançado com sucesso.');
	}
}
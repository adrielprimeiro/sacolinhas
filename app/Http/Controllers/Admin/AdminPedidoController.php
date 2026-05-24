<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\MelhorEnvioService;

class AdminPedidoController extends Controller
{
	public function index(Request $request)
	{
		$query = Pedido::query()->with('user');

		$query->when($request->filled('numero_pedido'), function ($q) use ($request) {
			$q->where('numero_pedido', 'like', '%' . $request->input('numero_pedido') . '%');
		});

		if ($request->filled('user_id')) {
			$query->where('user_id', $request->input('user_id'));
		} else {
			$query->when($request->filled('cliente'), function ($q) use ($request) {
				$term = $request->input('cliente');

				$q->whereHas('user', function ($uq) use ($term) {
					$uq->where('name', 'like', '%' . $term . '%')
					   ->orWhere('email', 'like', '%' . $term . '%');
				});
			});
		}

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

        $proximoNumero = Pedido::max('id') + 1;
        $numeroPedido = 'PED-' . str_pad($proximoNumero, 6, '0', STR_PAD_LEFT);

        return view('admin.pedidos.create', compact('users', 'numeroPedido'));
    }

    public function store(Request $request)
    {
        $rules = $this->rules();
        $rules['items'] = ['required', 'array', 'min:1'];
        $rules['items.*.item_id'] = ['required', 'exists:items,id'];
        $rules['items.*.preco_unitario'] = ['required', 'numeric', 'min:0'];
        $rules['items.*.quantidade'] = ['required', 'integer', 'min:1'];

        $validated = $request->validate($rules);

        if (empty($validated['numero_pedido'])) {
            $proximoNumero = Pedido::max('id') + 1;
            $validated['numero_pedido'] = 'PED-' . str_pad($proximoNumero, 6, '0', STR_PAD_LEFT);
        }

        if (empty($validated['origem_pedido'])) {
            $validated['origem_pedido'] = 'admin';
        }

        $validated['valor_frete'] = $validated['valor_frete'] ?? 0;
        $validated['valor_desconto'] = $validated['valor_desconto'] ?? 0;
        $validated['valor_saldo_utilizado'] = $validated['valor_saldo_utilizado'] ?? 0;

        $pedidoData = collect($validated)->except('items')->toArray();

        DB::transaction(function () use ($pedidoData, $request) {
            $pedido = Pedido::create($pedidoData);
            
            foreach ($request->input('items', []) as $itemData) {
                DB::table('items_pedido')->insert([
                    'pedido_id'      => $pedido->id,
                    'item_id'        => $itemData['item_id'],
                    'quantidade'     => $itemData['quantidade'],
                    'preco_unitario' => $itemData['preco_unitario'],
                    'status_item'    => 'ativo',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            $pedido->refresh();
            $pedido->touch();
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

    public function pdf($id)
    {
        $pedido = Pedido::findOrFail($id);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.pedidos.pdf', compact('pedido'));
        return $pdf->stream("pedido-{$pedido->numero_pedido}.pdf");
    }

    public function edit(Pedido $pedido)
    {
        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->limit(200)
            ->get();

        // Subtotal: soma dos itens do pedido
        $subtotal = DB::table('items_pedido')
            ->where('pedido_id', $pedido->id)
            ->sum('valor_total');

        // Saldo atual da carteira do cliente (ordem cronológica correta)
        $saldoCarteira = (float) (DB::table('conta_corrente')
            ->where('user_id', $pedido->user_id)
            ->orderByDesc('data_movimentacao')
            ->orderByDesc('id')
            ->value('saldo_atual') ?? 0);

        // Saldo já alocado neste pedido (pode ser negativo = dívida embutida)
        $saldoJaAlocado = (float) ($pedido->valor_saldo_utilizado ?? 0);

        // Saldo disponível SEM considerar o que já está alocado neste pedido
        // (adicionamos de volta o saldoJaAlocado pois ele já foi debitado da carteira)
        $saldoDisponivel = $saldoCarteira + $saldoJaAlocado;

        // Manter compatibilidade com qualquer referência antiga
        $saldoMaximoPermitido = max(0, $saldoDisponivel);

        return view('admin.pedidos.edit', compact('pedido', 'users', 'subtotal', 'saldoCarteira', 'saldoDisponivel', 'saldoJaAlocado', 'saldoMaximoPermitido'));
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

    public function adicionarItem(Request $request, Pedido $pedido)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'preco_unitario' => 'required|numeric|min:0',
            'quantidade' => 'nullable|integer|min:1',
        ]);

        $quantidade = $validated['quantidade'] ?? 1;
        $valorTotal = $validated['preco_unitario'] * $quantidade;

        DB::table('items_pedido')->insert([
            'pedido_id'      => $pedido->id,
            'item_id'        => $validated['item_id'],
            'quantidade'     => $quantidade,
            'preco_unitario' => $validated['preco_unitario'],
            'status_item'    => 'ativo',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $pedido->refresh();
        $pedido->touch();

        return response()->json(['success' => true, 'message' => 'Item adicionado com sucesso.']);
    }

    public function removerItem(Request $request, Pedido $pedido, $itemId)
    {
        DB::table('items_pedido')
            ->where('id', $itemId)
            ->where('pedido_id', $pedido->id)
            ->delete();

        $pedido->refresh();
        $pedido->touch();

        return response()->json(['success' => true, 'message' => 'Item removido com sucesso.']);
    }

    public function buscarItem(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['success' => false, 'data' => []]);
        }

        $items = \App\Models\Item::where(function ($query) use ($q) {
                $query->where('codigo', 'like', "%{$q}%")
                      ->orWhere('nome_do_produto', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get(['id', 'codigo', 'nome_do_produto', 'marca', 'estado', 'cor', 'tamanho', 'preco', 'image', 'status']);

        $data = $items->map(fn($i) => [
            'id'            => $i->id,
            'codigo'        => $i->codigo,
            'nome_do_produto' => $i->nome_do_produto,
            'marca'         => $i->marca,
            'estado'        => $i->estado,
            'cor'           => $i->cor,
            'tamanho'       => $i->tamanho,
            'preco'         => $i->preco,
            'status'        => $i->status,
            'image_url'     => $i->image ? asset('storage/' . $i->image) : asset('images/no-image.png'),
        ]);

        return response()->json(['success' => true, 'data' => $data]);
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
                Rule::in(['pendente', 'confirmado', 'processando', 'embalado', 'enviado', 'entregue', 'pago', 'concluido', 'cancelado']),
            ],

            'valor_total' => ['required', 'numeric', 'min:0'],
            'valor_saldo_utilizado' => ['nullable', 'numeric', 'min:0'],
            'valor_frete' => ['nullable', 'numeric', 'min:0'],
            'valor_frete_real' => ['nullable', 'numeric', 'min:0'],
            'valor_desconto' => ['nullable', 'numeric', 'min:0'],

            'forma_pagamento' => [
                'nullable',
                Rule::in(['pix', 'cartao_credito', 'cartao_debito', 'boleto', 'dinheiro', 'transferencia', 'saldo_carteira']),
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
                Rule::in(['live', 'site', 'whatsapp', 'instagram', 'admin']),
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

    public function freteOpcoes(Request $request, Pedido $pedido)
    {
        $request->validate([
            'weight' => 'required|numeric|min:0.1',
            'width' => 'required|numeric|min:1',
            'height' => 'required|numeric|min:1',
            'length' => 'required|numeric|min:1',
        ]);

        $pedido->load('user');
        $cepDestino = $pedido->cep_entrega ?? $pedido->user->cep ?? null;

        if (!$cepDestino) {
            return response()->json(['error' => 'CEP de destino não encontrado no pedido ou cliente.'], 400);
        }

        $service = new MelhorEnvioService();
        $result = $service->calculateShipping($cepDestino, $request->all());

        if (!$result['success'] ?? false) {
            return response()->json(['error' => $result['message'] ?? 'Erro ao calcular frete'], 400);
        }

        return response()->json($result['options']);
    }

    public function gerarEtiqueta(Request $request, Pedido $pedido)
    {
        $request->validate([
            'service_id' => 'required',
            'weight' => 'required|numeric',
            'width' => 'required|numeric',
            'height' => 'required|numeric',
            'length' => 'required|numeric',
        ]);

        $pedido->load('user');
        if (!$pedido->user) {
            return response()->json(['success' => false, 'message' => 'Pedido sem cliente associado.']);
        }

        $service = new MelhorEnvioService();
        
        // 1. Add to cart
        $cartResult = $service->createCart($pedido, $pedido->user, $request->only(['weight', 'width', 'height', 'length']), $request->service_id);
        if (!$cartResult['success']) {
            return response()->json($cartResult);
        }
        $cartOrderId = $cartResult['order_id'];

        // 2. Checkout
        $checkoutResult = $service->checkout($cartOrderId);
        if (!$checkoutResult['success']) {
            return response()->json($checkoutResult);
        }

        // 3. Generate
        $generateResult = $service->generateLabel($cartOrderId);
        if (!$generateResult['success']) {
            return response()->json($generateResult);
        }

        // 4. Print
        $printResult = $service->printLabel($cartOrderId);
        if (!$printResult['success']) {
            return response()->json($printResult);
        }

        // 5. Get Tracking Code
        $trackingCode = $service->getTrackingCode($cartOrderId);

        // Save tracking/url
        $pedido->update([
            'status_pedido' => 'embalado',
            'observacoes' => trim($pedido->observacoes . "\n\nEtiqueta Melhor Envio: " . $printResult['url']),
            'codigo_rastreamento' => $trackingCode,
        ]);

        return response()->json([
            'success' => true,
            'url' => $printResult['url'],
            'message' => 'Etiqueta gerada com sucesso!'
        ]);
    }

    public function saldoMelhorEnvio()
    {
        $service = new MelhorEnvioService();
        $result = $service->getBalance();

        if ($result['success']) {
            return response()->json(['saldo' => $result['balance']]);
        }

        return response()->json(['error' => 'Não foi possível carregar o saldo.'], 400);
    }
}
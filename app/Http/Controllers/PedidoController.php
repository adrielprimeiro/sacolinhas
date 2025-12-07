<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PedidoController extends Controller
{
    public function index()
    {
        return view('admin.pedidos.pedidos');
    }

    public function buscarClientes(Request $request)
    {
        $termo = $request->get('termo');
        
        if (!$termo || strlen($termo) < 2) {
            return response()->json([]);
        }

        try {
            $clientes = User::where('name', 'LIKE', "%{$termo}%")
                          ->orWhere('email', 'LIKE', "%{$termo}%")
                          ->limit(8)
                          ->get(['id', 'name', 'email']);

            return response()->json($clientes);

        } catch (\Exception $e) {
            Log::error('Erro buscar clientes: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    public function itensSacolinha(Request $request)
    {
        $userId = $request->get('user_id');
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'ID obrigatório'
            ]);
        }

        try {
            // ✅ Items na sacolinha (items sem pedido)
            $itens = DB::table('items')
                ->join('sacolinhas', 'items.id', '=', 'sacolinhas.item_id')
                ->where('sacolinhas.user_id', $userId)
                ->whereNull('items.pedido')  // ✅ CORRETO: items.pedido NULL
                ->where('items.status', '!=', 'enviado')
                ->select([
                    'sacolinhas.id as sacola_id',
                    'items.id as item_id',
                    'items.codigo',
                    'items.nome_do_produto',
                    'items.preco',
					'items.marca',           
					'items.modelo',          
					'items.estado',          
					'items.cor',             
					'items.tamanho',         			
                    'sacolinhas.price',
                    'sacolinhas.quantity',
                    'sacolinhas.add_at',
                    'items.status',
                    'sacolinhas.obs'
                ])
                ->orderBy('sacolinhas.add_at', 'desc')
                ->get();

            $totalItens = $itens->sum('quantity');
            $valorTotal = $itens->sum(function($item) {
                return $item->price * $item->quantity;
            });

            // ✅ Buscar pedido pendente
            $pedidoPendente = DB::table('pedidos')
                ->where('user_id', $userId)
                ->where('status_pedido', 'pendente')
                ->first();

            $itensPedido = [];
            $valorTotalPedido = 0;

            if ($pedidoPendente) {
                // ✅ Items que pertencem ao pedido (items.pedido = numero_pedido)
                $itensPedido = DB::table('items')
                    ->where('pedido', $pedidoPendente->numero_pedido)  // ✅ CORRETO: items.pedido
                    ->select([
                        'id as item_id',
                        'codigo',
                        'nome_do_produto',
                        'preco',
						'marca',
						'estado',    
						'cor',     
						'tamanho',						
                        'status',
                        'created_at'
                    ])
                    ->orderBy('created_at', 'desc')
                    ->get();

                // ✅ Calcular valor total do pedido
                $valorTotalPedido = $itensPedido->sum(function($item) {
                    return $item->preco;
                });
            }

            return response()->json([
                'success' => true,
                'itens_sacolinha' => $itens,
                'itens_pedido' => $itensPedido,
                'pedido_numero' => $pedidoPendente ? $pedidoPendente->numero_pedido : null,
                'pedido_id' => $pedidoPendente ? $pedidoPendente->id : null,
                'resumo' => [
                    'total_itens' => $totalItens,
                    'valor_total' => number_format($valorTotal, 2, ',', '.'),
                    'quantidade_produtos' => $itens->count(),
                    'tem_pedido_pendente' => $pedidoPendente ? true : false,
                    'valor_total_pedido' => number_format($valorTotalPedido, 2, ',', '.')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro itens sacolinha: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar itens'
            ]);
        }
    }

    // ✅ Mover item da sacolinha para o pedido

	public function moverParaPedido(Request $request)
	{
		$sacolaId = $request->get('sacola_id');
		$pedidoNumero = $request->get('pedido_numero');
		$userId = $request->get('user_id');

		if (!$sacolaId || !$pedidoNumero) {
			return response()->json([
				'success' => false,
				'message' => 'Dados obrigatórios faltando'
			]);
		}

		try {
			DB::beginTransaction();

			// ✅ Buscar a sacolinha com o preço correto
			$sacolinha = DB::table('sacolinhas')
				->where('id', $sacolaId)
				->where('user_id', $userId)
				->first();

			if (!$sacolinha) {
				return response()->json([
					'success' => false,
					'message' => 'Item da sacolinha não encontrado'
				]);
			}

			// ✅ Buscar o item
			$item = DB::table('items')
				->where('id', $sacolinha->item_id)
				->first();

			if (!$item) {
				return response()->json([
					'success' => false,
					'message' => 'Item não encontrado'
				]);
			}

			// ✅ Atualizar o item com o preço da SACOLINHA, não do items.preco
			DB::table('items')
				->where('id', $item->id)
				->update([
					'pedido' => $pedidoNumero,
					'preco' => $sacolinha->price,  // ✅ Usar price da sacolinha
					'status' => 'no_pedido',
					'updated_at' => now()
				]);

			// ✅ Deletar da sacolinha
			DB::table('sacolinhas')
				->where('id', $sacolaId)
				->delete();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Item movido para o pedido com sucesso'
			]);

		} catch (\Exception $e) {
			DB::rollBack();
			\Log::error('Erro ao mover para pedido: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Erro ao mover item: ' . $e->getMessage()
			], 500);
		}
	}    

    // ✅ Devolver item do pedido para sacolinha
    public function devolverParaSacolinha(Request $request)
    {
        $itemId = $request->get('item_id');
        $userId = $request->get('user_id');

        if (!$itemId || !$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Dados obrigatórios faltando'
            ]);
        }

        try {
            DB::beginTransaction();

            // ✅ Buscar item do pedido
            $item = DB::table('items')
                ->where('id', $itemId)
                ->whereNotNull('pedido')
                ->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item não encontrado no pedido'
                ]);
            }

            // ✅ Limpar items.pedido
            DB::table('items')
                ->where('id', $itemId)
                ->update(['pedido' => null]);

            // ✅ Verificar se já existe na sacolinha
            $jaExiste = DB::table('sacolinhas')
                ->where('user_id', $userId)
                ->where('item_id', $itemId)
                ->first();

            if (!$jaExiste) {
                // ✅ Adicionar na sacolinha com TODOS os campos
                DB::table('sacolinhas')->insert([
                    'user_id' => $userId,
                    'item_id' => $itemId,
                    'price' => $item->preco,
                    'quantity' => 1,
                    'live_id' => 1,
                    'status' => 'ativo',
                    'obs' => null,
                    'add_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item devolvido para a sacolinha'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao devolver item: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao devolver item'
            ]);
        }
    }

    // ✅ Criar novo pedido
    public function criarPedido(Request $request)
    {
        $userId = $request->get('user_id');

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'ID do usuário obrigatório'
            ]);
        }

        try {
            DB::beginTransaction();

            // ✅ Gerar número sequencial
			$ultimoPedido = DB::table('pedidos')->latest('id')->first();
			$numero = ($ultimoPedido ? $ultimoPedido->id + 1 : 1);
			$numeroPedido = 'PED-' . str_pad($numero, 6, '0', STR_PAD_LEFT);


            $pedidoId = DB::table('pedidos')->insertGetId([
                'numero_pedido' => $numeroPedido,
                'user_id' => $userId,
                'status_pedido' => 'pendente',
                'data_pedido' => now(),
                'valor_total' => 0,
                'valor_frete' => 0,
                'valor_desconto' => 0,
                'status_pagamento' => 'pendente',
                'origem_pedido' => 'site',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'pedido_id' => $pedidoId,
                'pedido_numero' => $numeroPedido,
                'message' => 'Pedido criado com sucesso'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar pedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar pedido'
            ]);
        }
    }
		
	public function dadosCliente($clienteId)
	{
		try {
			$sacolinha = DB::table('sacolinhas')
				->join('items', 'sacolinhas.item_id', '=', 'items.id')
				->where('sacolinhas.user_id', $clienteId)
				->select(
					'sacolinhas.id',
					'sacolinhas.price',
					'sacolinhas.created_at',
					'items.nome as produto_nome'
				)
				->get();

			return response()->json([
				'sacolinha' => $sacolinha
			]);

		} catch (\Exception $e) {
			\Log::error('Erro ao buscar dados: ' . $e->getMessage());
			return response()->json([], 500);
		}
	}	
	
	
}
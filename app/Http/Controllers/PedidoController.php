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

        if (!$sacolaId || !$pedidoNumero || !$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Dados obrigatórios faltando'
            ]);
        }

        try {
            DB::beginTransaction();

            // ✅ Buscar item da sacolinha
            $sacolaItem = DB::table('sacolinhas')
                ->join('items', 'sacolinhas.item_id', '=', 'items.id')
                ->where('sacolinhas.id', $sacolaId)
                ->select('items.id as item_id', 'sacolinhas.price')
                ->first();

            if (!$sacolaItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item não encontrado'
                ]);
            }

            // ✅ Atualizar items.pedido = numero_pedido
            DB::table('items')
                ->where('id', $sacolaItem->item_id)
                ->update(['pedido' => $pedidoNumero]);

            // ✅ Remover da sacolinha
            DB::table('sacolinhas')->where('id', $sacolaId)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item movido para o pedido'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao mover item: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao mover item'
            ]);
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
}
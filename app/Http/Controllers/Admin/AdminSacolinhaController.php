<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sacolinhas;
use App\Models\User;
use App\Models\Item;
use App\Models\ContaCorrente;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminSacolinhaController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('sacolinhas as s')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->select([
                'u.id as user_id',
                'u.name',
                DB::raw('MIN(s.add_at) as aberto_em'),
                DB::raw('SUM(s.price) as total_valor'),
                DB::raw('COUNT(s.item_id) as total_itens')
            ]);

        if ($request->filled('cliente')) {
            $query->where('u.name', 'like', '%' . $request->cliente . '%');
        }

        $sacolinhas = $query->groupBy('u.id', 'u.name')
            ->orderBy('aberto_em', 'asc')
            ->paginate(15);

        return view('admin.sacolinhas.index', compact('sacolinhas'));
    }

    public function show(User $user)
    {
        // Verificar se tem itens com status 'Em Analise'
        $temEmAnalise = DB::table('sacolinhas')
            ->where('user_id', $user->id)
            ->where('status', 'em analise')
            ->exists();

        // Calcular total dos itens em análise
        $totalItensEmAnalise = DB::table('sacolinhas')
            ->where('user_id', $user->id)
            ->where('status', 'em analise')
            ->sum('price');

        $excedente = $totalItensEmAnalise;

        // Buscar dados do limite
        $limitesRow = DB::table('cliente_limites')
            ->where('user_id', $user->id)
            ->first();

        $valorLimite = (float) ($limitesRow->limite_credito ?? 0);
        $utilizado   = (float) ($limitesRow->limite_utilizado ?? 0);
        
        // Buscar saldo
        $ultima = ContaCorrente::where('user_id', $user->id)
            ->orderByDesc('data_movimentacao')
            ->orderByDesc('id')
            ->first();
        $saldo = $ultima?->saldo_atual ?? 0;
        
        $valorPago   = (float) ($saldo ?? 0);
        $disponivelUI = max(0, $valorLimite + $valorPago - $utilizado);

        // Buscar itens da sacolinha
        $itens = DB::table('sacolinhas as s')
            ->join('items as i', 'i.id', '=', 's.item_id')
            ->where('s.user_id', $user->id)
            ->orderBy('s.add_at', 'asc')
            ->select([
                's.id as sacolinha_id',
                's.item_id',
                's.price',
                's.add_at',
                's.status as sacolinha_status',
                's.obs',
                'i.codigo',
                'i.nome_do_produto',
                'i.estado',
                'i.cor',
                'i.tamanho',
                'i.image',
                'i.marca',
            ])
            ->get();

        $total = (float) $itens->sum('price');

        return view('admin.sacolinhas.show', compact(
            'user', 
            'itens', 
            'total',
            'temEmAnalise',
            'totalItensEmAnalise',
            'excedente',
            'valorLimite',
            'utilizado',
            'valorPago',
            'disponivelUI'
        ));
    }

    public function searchItem(Request $request)
    {
        $codigo = $request->query('codigo');
        $item = Item::where('codigo', $codigo)->first();

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item não encontrado']);
        }

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $item->id,
                'nome_do_produto' => $item->nome_do_produto,
                'codigo' => $item->codigo,
                'preco' => $item->preco,
                'image_url' => $item->image ? asset('storage/' . ltrim($item->image, '/')) : asset('images/no-image.png'),
                'marca' => $item->marca,
                'tamanho' => $item->tamanho,
            ]
        ]);
    }

    public function addItem(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'item_id' => 'required|exists:items,id',
            'price' => 'required|numeric|min:0',
            'obs' => 'nullable|string',
        ]);

        $item = Item::findOrFail($validated['item_id']);

        DB::transaction(function () use ($validated, $item) {
            $sacolinha = Sacolinhas::updateOrCreate(
                [
                    'user_id' => $validated['user_id'],
                    'item_id' => $validated['item_id'],
                    'live_id' => 1 // Padrão
                ],
                [
                    'price' => $validated['price'],
                    'add_at' => now(),
                    'obs' => $validated['obs'] ?? null,
                    'quantity' => 1,
                    'status' => 'live' // Status padrão admin
                ]
            );

            // Atualiza status do item
            $item->update(['status' => 'sacolinha']);
        });

        return response()->json(['success' => true, 'message' => 'Item adicionado com sucesso']);
    }

    public function removeItem($id)
    {
        $sacolinha = Sacolinhas::findOrFail($id);
        $itemId = $sacolinha->item_id;

        DB::transaction(function () use ($sacolinha, $itemId) {
            $sacolinha->delete();
            
            $outras = Sacolinhas::where('item_id', $itemId)->exists();
            if (!$outras) {
                Item::where('id', $itemId)->update(['status' => 'disponivel']);
            }
        });

        return back()->with('success', 'Item removido da sacolinha.');
    }

    public function fecharSacolinha(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'valor_frete' => 'nullable|numeric|min:0',
            'itens' => 'required|array',
            'itens.*' => 'integer|exists:sacolinhas,id'
        ]);

        try {
            $pedidoId = DB::transaction(function () use ($request) {
                $userId = $request->user_id;
                $user = User::findOrFail($userId);
                $valorFrete = (float) ($request->valor_frete ?? 0);
                
                // 1. Criar o número do pedido
                $ultimoPedido = DB::table('pedidos')->latest('id')->first();
                $numero = $ultimoPedido ? $ultimoPedido->id + 1 : 1;
                $numeroPedido = 'PED-' . str_pad($numero, 6, '0', STR_PAD_LEFT);

                // 2. Criar o Pedido via Eloquent (para disparar Observers)
                $pedido = Pedido::create([
                    'numero_pedido'   => $numeroPedido,
                    'user_id'         => $userId,
                    'status_pedido'   => 'pendente',
                    'data_pedido'     => now(),
                    'valor_total'     => 0,
                    'valor_frete'     => $valorFrete,
                    'valor_desconto'  => 0,
                    'status_pagamento'=> 'pendente',
                    'origem_pedido'   => 'site', 
                    
                    // Auto-preencher dados de entrega do cliente
                    'endereco_entrega' => trim(($user->endereco ?? '') . ' ' . ($user->numero_endereco ?? '') . ' ' . ($user->complemento ?? '') . ' ' . ($user->bairro ?? '')),
                    'cep_entrega'      => $user->cep ?? null,
                    'cidade_entrega'   => $user->cidade ?? null,
                    'estado_entrega'   => $user->estado ?? null,
                ]);

                // 3. Mover itens da sacolinha para o pedido
                $itensSacolinha = DB::table('sacolinhas')
                    ->whereIn('id', $request->itens)
                    ->where('user_id', $userId)
                    ->get();

                $subtotal = 0;
                foreach ($itensSacolinha as $sacola) {
                    DB::table('items_pedido')->insert([
                        'pedido_id' => $pedido->id,
                        'item_id' => $sacola->item_id,
                        'quantidade' => 1,
                        'preco_unitario' => $sacola->price,
                        'status_item' => 'ativo',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    $subtotal += $sacola->price;
                    
                    // Remover da sacolinha
                    DB::table('sacolinhas')->where('id', $sacola->id)->delete();
                }

                // 4. Atualizar valor total do pedido (Subtotal + Frete) via Eloquent
                $pedido->update([
                    'valor_total' => $subtotal + $valorFrete
                ]);

                return $pedido->id;
            });

            return response()->json([
                'success' => true,
                'message' => 'Sacolinha fechada com sucesso!',
                'redirect' => route('admin.pedido.show', $pedidoId)
            ]);

        } catch (\Throwable $e) {
            Log::error('Erro ao fechar sacolinha via Admin: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar o fechamento: ' . $e->getMessage()
            ], 500);
        }
    }
}

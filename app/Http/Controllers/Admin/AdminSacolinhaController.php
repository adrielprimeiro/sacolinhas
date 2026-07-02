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
            ->where('s.status', '!=', 'pedido')
            ->select([
                'u.id as user_id',
                'u.name',
                DB::raw('MIN(s.add_at) as aberto_em'),
                DB::raw('SUM(s.price) as total_valor'),
                DB::raw('COUNT(s.item_id) as total_itens')
            ]);

        if ($request->filled('user_id')) {
            $query->where('u.id', $request->user_id);
        } elseif ($request->filled('cliente')) {
            $query->where('u.name', 'like', '%' . $request->cliente . '%');
        }

        $sacolinhas = $query->groupBy('u.id', 'u.name')
            ->orderBy('aberto_em', 'asc')
            ->paginate(15)
            ->withQueryString();

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
            ->where('s.status', '!=', 'pedido')
            ->orderBy('s.add_at', 'asc')
            ->select([
                's.id as sacolinha_id',
                's.item_id',
                's.live_id',
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
            'itens.*' => 'integer|exists:sacolinhas,id',
            'autorizar_fechamento' => 'nullable|boolean'
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

                // 2. Buscar dados da sacolinha para calcular valores ANTES de criar o pedido
                $itensSacolinha = DB::table('sacolinhas')
                    ->whereIn('id', $request->itens)
                    ->where('user_id', $userId)
                    ->get();

                $subtotal = 0;
                foreach ($itensSacolinha as $sacola) {
                    $subtotal += ($sacola->price * ($sacola->quantity ?? 1));
                }

                // 3. Buscar saldo atual
                $ultimaTransacao = ContaCorrente::where('user_id', $userId)
                    ->orderByDesc('data_movimentacao')
                    ->orderByDesc('id')
                    ->first();
                $saldoAtual = (float) ($ultimaTransacao?->saldo_atual ?? 0);

                // 4. Calcular total do pedido e aplicar trava de segurança rígida
                $totalItensFrete = $subtotal + $valorFrete;
                $isToleranceAuthorized = false;
                $valorFaltante = 0.00;
                $adminName = null;
                $toleranceObs = null;

                if ($saldoAtual < $totalItensFrete) {
                    if (!empty($user->sacolinha_autorizada_por)) {
                        $valorFaltante = $totalItensFrete - $saldoAtual;
                        $isToleranceAuthorized = true;
                        $adminName = $user->sacolinha_autorizada_por;
                        $toleranceObs = $user->sacolinha_autorizada_obs;
                        $saldoUtilizadoNoPedido = $totalItensFrete;
                        $totalFinal = 0.00;
                    } else {
                        throw new \Exception("Saldo insuficiente na carteira para realizar a operação. O pedido totaliza R$ " . number_format($totalItensFrete, 2, ',', '.') . ", mas o cliente possui apenas R$ " . number_format($saldoAtual, 2, ',', '.') . ". É necessária a autorização do fechamento.");
                    }
                } else {
                    $saldoUtilizadoNoPedido = $totalItensFrete;
                    $totalFinal = 0.00; // Pedido nasce 100% quitado usando o saldo da carteira
                }

                // 5. Definir Status (Se zerou com saldo, já nasce aprovado)
                $statusPedido = 'pendente';
                $statusPagamento = 'pendente';
                
                if ($totalFinal <= 0) {
                    $statusPedido = 'pago'; 
                    $statusPagamento = 'aprovado';
                }

                // 6. Criar o Pedido
                $pedido = Pedido::create([
                    'numero_pedido'   => $numeroPedido,
                    'user_id'         => $userId,
                    'status_pedido'   => $statusPedido,
                    'data_pedido'     => now(),
                    'valor_total'     => $totalFinal,
                    'valor_frete'     => $valorFrete,
                    'valor_desconto'  => 0,
                    'valor_saldo_utilizado' => $saldoUtilizadoNoPedido,
                    'forma_pagamento' => ($totalFinal <= 0 && $saldoUtilizadoNoPedido > 0) ? 'saldo_carteira' : null,
                    'status_pagamento'=> $statusPagamento,
                    'origem_pedido'   => 'admin', 
                    
                    'endereco_entrega' => trim(($user->endereco ?? '') . ' ' . ($user->numero_endereco ?? '') . ' ' . ($user->complemento ?? '') . ' ' . ($user->bairro ?? '')),
                    'cep_entrega'      => $user->cep ?? null,
                    'cidade_entrega'   => $user->cidade ?? null,
                    'estado_entrega'   => $user->estado ?? null,
                ]);

                // Create tolerance records after the Pedido is created, so we can link them
                if ($isToleranceAuthorized && $valorFaltante > 0) {
                    $authName = $adminName ?: 'Administrador';

                    // 1. Criar Crédito de Tolerância na carteira
                    ContaCorrente::create([
                        'user_id' => $userId,
                        'tipo_movimentacao' => 'credito',
                        'valor' => $valorFaltante,
                        'descricao' => "Crédito de Tolerância Autorizado Gerência: Pedido {$numeroPedido}",
                        'classificacao_id' => 14, // Outras Despesas / Ajustes
                        'data_movimentacao' => now(),
                        'observacoes' => "Autorizado por: {$authName}" . ($toleranceObs ? " - Motivo: {$toleranceObs}" : ""),
                        'referencia_tipo' => 'tolerancia',
                        'referencia_id' => $pedido->id,
                    ]);

                    // 2. Criar Débito de Tolerância (Saldo não pago) na carteira
                    ContaCorrente::create([
                        'user_id' => $userId,
                        'tipo_movimentacao' => 'debito',
                        'valor' => $valorFaltante,
                        'descricao' => "Débito de Tolerância (Saldo não pago): Pedido {$numeroPedido}",
                        'classificacao_id' => 14, // Outras Despesas / Ajustes
                        'data_movimentacao' => now(),
                        'observacoes' => "Autorizado por: {$authName}" . ($toleranceObs ? " - Motivo: {$toleranceObs}" : ""),
                        'referencia_tipo' => 'tolerancia',
                        'referencia_id' => $pedido->id,
                    ]);
                }

                // 7. Mover itens da sacolinha para o pedido e remover da sacola
                $itemIds = [];
                foreach ($itensSacolinha as $sacola) {
                    $itemIds[] = $sacola->item_id;
                    DB::table('items_pedido')->insert([
                        'pedido_id' => $pedido->id,
                        'item_id' => $sacola->item_id,
                        'quantidade' => $sacola->quantity ?? 1,
                        'preco_unitario' => $sacola->price,
                        'status_item' => 'ativo',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    // Atualizar status na sacolinha em vez de deletar
                    DB::table('sacolinhas')->where('id', $sacola->id)->update([
                        'status' => 'pedido',
                        'obs' => 'Pedido ' . $pedido->numero_pedido,
                        'updated_at' => now()
                    ]);
                }

                // 8. Se já nasceu aprovado, dar baixa no estoque
                if ($statusPagamento === 'aprovado' && !empty($itemIds)) {
                    DB::table('items')->whereIn('id', $itemIds)->update([
                        'status' => 'vendido',
                        'updated_at' => now()
                    ]);
                }

                // Recarregar o pedido do banco para obter o valor_total atualizado pelo trigger e disparar o PedidoObserver com os dados finais corretos
                $pedidoAtualizado = Pedido::find($pedido->id);
                if ($pedidoAtualizado) {
                    $pedidoAtualizado->touch();
                }

                // 9. Consumir/limpar a autorização do usuário
                $user->update([
                    'sacolinha_autorizada_por' => null,
                    'sacolinha_autorizada_obs' => null,
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

    public function autorizarFechamento(Request $request, User $user)
    {
        $validated = $request->validate([
            'autorizado_por' => 'required|string|max:255',
            'observacoes' => 'required|string',
        ]);

        $user->update([
            'sacolinha_autorizada_por' => $validated['autorizado_por'],
            'sacolinha_autorizada_obs' => $validated['observacoes'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fechamento autorizado com sucesso!'
        ]);
    }

    public function revogarAutorizacao(User $user)
    {
        $user->update([
            'sacolinha_autorizada_por' => null,
            'sacolinha_autorizada_obs' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Autorização removida com sucesso!'
        ]);
    }

    public function pdf(User $user)
    {
        $itens = DB::table('sacolinhas as s')
            ->join('items as i', 'i.id', '=', 's.item_id')
            ->where('s.user_id', $user->id)
            ->where('s.status', '!=', 'pedido')
            ->orderBy('s.add_at', 'asc')
            ->select([
                's.price',
                's.add_at',
                's.status as sacolinha_status',
                'i.codigo',
                'i.nome_do_produto',
                'i.tamanho',
                'i.marca',
            ])
            ->get();

        $total = (float) $itens->sum('price');

        // Buscar saldo
        $ultima = ContaCorrente::where('user_id', $user->id)
            ->orderByDesc('data_movimentacao')
            ->orderByDesc('id')
            ->first();
        $saldo = $ultima?->saldo_atual ?? 0;
        $valorPago = (float) $saldo;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.sacolinhas.pdf', compact('user', 'itens', 'total', 'valorPago'));
        return $pdf->stream("sacolinha-{$user->name}.pdf");
    }

    public function updateItemPrice(Request $request)
    {
        $validated = $request->validate([
            'sacolinha_id' => 'required|exists:sacolinhas,id',
            'price' => 'required|numeric|min:0',
        ]);

        $sacolinha = Sacolinhas::findOrFail($validated['sacolinha_id']);
        $sacolinha->update(['price' => $validated['price']]);

        return response()->json(['success' => true, 'message' => 'Preço atualizado com sucesso!']);
    }
}

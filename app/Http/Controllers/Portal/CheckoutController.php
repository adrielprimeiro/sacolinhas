<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Pedido;
use App\Models\Sacolinhas;
use App\Services\MelhorEnvioService;
use App\Services\ShippingCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    protected $shippingCalculator;
    protected $melhorEnvio;

    public function __construct(ShippingCalculatorService $shippingCalculator, MelhorEnvioService $melhorEnvio)
    {
        $this->shippingCalculator = $shippingCalculator;
        $this->melhorEnvio = $melhorEnvio;
    }

    /**
     * Inicia o processo de checkout criando um pedido com os itens selecionados.
     */
    public function iniciar(Request $request)
    {
        $request->validate([
            'itens' => 'required|array',
            'itens.*' => 'integer|exists:sacolinhas,id'
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $user = auth()->user();
                
                // 1. Criar o número do pedido
                $ultimoPedido = DB::table('pedidos')->latest('id')->first();
                $numero = $ultimoPedido ? $ultimoPedido->id + 1 : 1;
                $numeroPedido = 'PED-' . str_pad($numero, 6, '0', STR_PAD_LEFT);

                // 2. Criar o Pedido via Eloquent
                $pedido = Pedido::create([
                    'numero_pedido'   => $numeroPedido,
                    'user_id'         => $user->id,
                    'status_pedido'   => 'pendente', 
                    'data_pedido'     => now(),
                    'valor_total'     => 0,
                    'valor_frete'     => 0,
                    'valor_desconto'  => 0,
                    'status_pagamento'=> 'pendente',
                    'origem_pedido'   => 'site',

                    // Auto-preencher dados de entrega do cliente
                    'endereco_entrega' => trim(($user->endereco ?? '') . ' ' . ($user->numero_endereco ?? '') . ' ' . ($user->complemento ?? '') . ' ' . ($user->bairro ?? '')),
                    'cep_entrega'      => $user->cep ?? null,
                    'cidade_entrega'   => $user->cidade ?? null,
                    'estado_entrega'   => $user->estado ?? null,
                ]);

                $pedidoId = $pedido->id;

                // 3. Mover itens da sacolinha para o pedido
                $itensSacolinha = DB::table('sacolinhas')
                    ->whereIn('id', $request->itens)
                    ->where('user_id', $user->id)
                    ->get();

                foreach ($itensSacolinha as $sacola) {
                    DB::table('items_pedido')->insert([
                        'pedido_id' => $pedidoId,
                        'item_id' => $sacola->item_id,
                        'quantidade' => 1,
                        'preco_unitario' => $sacola->price,
                        'status_item' => 'ativo',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    // Remover da sacolinha
                    DB::table('sacolinhas')->where('id', $sacola->id)->delete();
                }

                return response()->json([
                    'success' => true,
                    'redirect' => route('portal.checkout.show', $pedidoId)
                ]);
            });

        } catch (\Throwable $e) {
            Log::error('Erro ao iniciar checkout: ' . $e->getMessage() . ' - Linha: ' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar checkout. Tente novamente.'
            ], 500);
        }
    }

    /**
     * Exibe a página de revisão do checkout.
     */
    public function show($pedidoId)
    {
        $pedido = DB::table('pedidos')
            ->where('id', $pedidoId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$pedido || $pedido->status_pedido !== 'pendente') {
            return redirect()->route('sacolinhas.index')->with('error', 'Pedido não encontrado ou já finalizado.');
        }

        $itens = DB::table('items_pedido')
            ->join('items', 'items_pedido.item_id', '=', 'items.id')
            ->where('items_pedido.pedido_id', $pedidoId)
            ->select('items.*', 'items_pedido.preco_unitario', 'items_pedido.id as item_pedido_id')
            ->get();

        // Calcular frete inicial se o usuário tiver CEP
        $shippingOptions = [];
        $packageData = $this->shippingCalculator->calculateForItems($itens->pluck('id')->toArray());
        
        $cep = auth()->user()->cep ?? null; // Assume que o user tem campo 'cep'
        if ($cep) {
            $result = $this->melhorEnvio->calculateShipping($cep, $packageData);
            if ($result['success']) {
                $shippingOptions = $result['options'];
            }
        }

        return view('portal.cliente.checkout', compact('pedido', 'itens', 'shippingOptions', 'packageData'));
    }

    /**
     * Cancela o checkout e devolve os itens para a sacolinha.
     */
    public function cancelar($pedidoId)
    {
        try {
            DB::transaction(function () use ($pedidoId) {
                $pedido = DB::table('pedidos')
                    ->where('id', $pedidoId)
                    ->where('user_id', auth()->id())
                    ->first();

                if (!$pedido || $pedido->status_pedido !== 'pendente') {
                    throw new \Exception('Pedido não elegível para cancelamento.');
                }

                $itensPedido = DB::table('items_pedido')->where('pedido_id', $pedidoId)->get();

                foreach ($itensPedido as $itemPedido) {
                    DB::table('sacolinhas')->insert([
                        'user_id' => auth()->id(),
                        'item_id' => $itemPedido->item_id,
                        'price' => $itemPedido->preco_unitario,
                        'quantity' => 1,
                        'live_id' => 1,
                        'status' => 'pendente',
                        'add_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                DB::table('items_pedido')->where('pedido_id', $pedidoId)->delete();
                DB::table('pedidos')->where('id', $pedidoId)->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Checkout cancelado e itens devolvidos à sacolinha.'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao cancelar checkout: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finaliza a revisão, grava o frete e redireciona para o Mercado Pago.
     */
    public function finalizarRevision(Request $request, $pedidoId)
    {
        Log::info('Tentativa de finalizar checkout', [
            'pedido_id' => $pedidoId,
            'request' => $request->all()
        ]);

        $request->validate([
            'shipping_id' => 'required|string',
            'shipping_price' => 'required|numeric',
            'shipping_name' => 'required|string'
        ]);

        try {
            $pedido = Pedido::where('id', $pedidoId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // Calcular o subtotal dos itens no pedido
            $subtotal = DB::table('items_pedido')
                ->where('pedido_id', $pedidoId)
                ->select(DB::raw('SUM(preco_unitario * quantidade) as total'))
                ->first()->total ?? 0;

            $pedido->valor_frete = $request->shipping_price;
            $pedido->valor_total = $subtotal + $request->shipping_price;
            $pedido->status_pedido = 'pendente'; 
            
            $pedido->save();

            return response()->json([
                'success' => true,
                'redirect' => route('portal.mercadopago.checkout', $pedido->id)
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao finalizar revisão de checkout: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar frete: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Acesso ao checkout via Token (sem login manual).
     */
    public function pagamentoToken($token)
    {
        $pedido = Pedido::where('payment_token', $token)->first();

        if (!$pedido) {
            abort(404, 'Link de pagamento inválido ou expirado.');
        }

        // Login silencioso para permitir acesso às rotas protegidas do portal
        \Illuminate\Support\Facades\Auth::login($pedido->user);

        if ($pedido->status_pedido !== 'pendente') {
            return redirect()->route('portal.pedidos')->with('info', 'Este pedido já foi finalizado ou cancelado.');
        }

        // Se já tiver forma de pagamento e frete definidos, pula a escolha e vai pro checkout
        if (!empty($pedido->forma_pagamento) && (float)$pedido->valor_total > 0) {
            return redirect()->route('portal.mercadopago.checkout', $pedido->id);
        }

        return redirect()->route('portal.checkout.show', $pedido->id);
    }
}

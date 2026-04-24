<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercadoPagoController extends Controller
{
    /**
     * Exibe a página de checkout com o formulário transparente (Payment Brick)
     */
    public function checkout(Pedido $pedido)
    {
        if ($pedido->user_id !== auth()->id()) {
            abort(403, 'Acesso não autorizado.');
        }

        if ($pedido->status_pagamento === 'pago') {
            return redirect()->route('portal.pedidos')->with('info', 'Este pedido já está pago.');
        }

        $publicKey = config('services.mercadopago.public_key');

        if (empty($publicKey)) {
            return redirect()->route('portal.pedidos')->with('error', 'Chave pública do Mercado Pago não configurada.');
        }

        return view('portal.cliente.checkout-mp', compact('pedido', 'publicKey'));
    }

    /**
     * Processa o pagamento transparente via API (cartão, pix, boleto)
     */
    public function processPayment(Request $request, Pedido $pedido)
    {
        if ($pedido->user_id !== auth()->id()) {
            return response()->json(['error' => 'Não autorizado'], 403);
        }

        $accessToken = config('services.mercadopago.access_token');
        $url = 'https://api.mercadopago.com/v1/payments';

        // Pega os dados enviados pelo Brick
        $data = $request->all();

        // O valor tem que ser exatamente o do pedido para segurança
        $data['transaction_amount'] = (float) $pedido->valor_total;
        $data['description'] = 'Pedido #' . $pedido->numero_pedido;
        $data['external_reference'] = (string) $pedido->id;
        
        // Assegura que o e-mail do pagador seja o do usuário logado (opcional, caso queira forçar)
        if (!isset($data['payer']['email'])) {
            $data['payer']['email'] = auth()->user()->email;
        }

        // Gera uma chave de idempotência para evitar cobranças duplicadas
        $idempotencyKey = $pedido->id . '_' . time();

        try {
            $response = Http::withoutVerifying()
                ->withToken($accessToken)
                ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
                ->post($url, $data);

            if ($response->successful()) {
                $paymentInfo = $response->json();
                $status = $paymentInfo['status'] ?? null;

            // Se for PIX, o status inicial será 'pending' e os dados do QR code estarão em point_of_interaction
            if ($status === 'approved') {
                $pedido->status_pagamento = 'pago';
            } elseif ($status === 'rejected' || $status === 'cancelled') {
                $pedido->status_pagamento = 'cancelado';
            } elseif ($status === 'in_process' || $status === 'pending') {
                $pedido->status_pagamento = 'pendente';
            }

            // Salva a forma de pagamento, ex: credit_card, pix, ticket
            $pedido->forma_pagamento = $paymentInfo['payment_method_id'] ?? null;
            $pedido->save();

            return response()->json([
                'success' => true,
                'status' => $status,
                'status_detail' => $paymentInfo['status_detail'] ?? null,
                'payment_id' => $paymentInfo['id'] ?? null,
                // Dados do PIX
                'qr_code_base64' => $paymentInfo['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null,
                'qr_code' => $paymentInfo['point_of_interaction']['transaction_data']['qr_code'] ?? null,
                // Dados Boleto
                'ticket_url' => $paymentInfo['transaction_details']['external_resource_url'] ?? null,
            ]);
        }

        Log::error('Erro ao processar pagamento Mercado Pago: ' . $response->body());
        
            return response()->json([
                'error' => 'Falha ao processar o pagamento.',
                'details' => $response->json()
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('Exceção ao processar pagamento Mercado Pago: ' . $e->getMessage());
            return response()->json([
                'error' => 'Falha na comunicação com o Mercado Pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recebe as notificações de pagamento via Webhook (IPN).
     */
    public function webhook(Request $request)
    {
        $topic = $request->query('topic') ?? $request->input('type');
        $paymentId = $request->query('id') ?? $request->input('data.id');

        if (str_starts_with($topic, 'payment') && $paymentId) {
            
            $accessToken = config('services.mercadopago.access_token');
            
            $response = Http::withoutVerifying()
                ->withToken($accessToken)
                ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

            if ($response->successful()) {
                $paymentInfo = $response->json();
                
                $status = $paymentInfo['status'] ?? null;
                $pedidoId = $paymentInfo['external_reference'] ?? null;

                if ($pedidoId && $status) {
                    $pedido = Pedido::find($pedidoId);
                    
                    if ($pedido) {
                        if ($status === 'approved') {
                            $pedido->status_pagamento = 'pago';
                        } elseif ($status === 'rejected' || $status === 'cancelled') {
                            $pedido->status_pagamento = 'cancelado';
                        } elseif ($status === 'pending' || $status === 'in_process') {
                            $pedido->status_pagamento = 'pendente';
                        }
                        
                        $pedido->save();
                    }
                }
            }
        }

        return response()->json(['status' => 'success'], 200);
    }
}

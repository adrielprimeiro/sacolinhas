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

        // Limpeza de campos vazios que causam erro 500 no MP
        if (empty($data['issuer_id'])) {
            unset($data['issuer_id']);
        }
        if (empty($data['payer']['entity_type'])) {
            unset($data['payer']['entity_type']);
        }
        if (isset($data['payer']['entity_type']) && !in_array($data['payer']['entity_type'], ['individual', 'association'])) {
            $data['payer']['entity_type'] = 'individual'; // Corrige aviso do console
        }

        // O valor tem que ser exatamente o do pedido para segurança
        $data['transaction_amount'] = (float) $pedido->valor_total;
        $data['description'] = 'Pedido #' . $pedido->numero_pedido;
        $data['external_reference'] = (string) $pedido->id;
        
        // Assegura que o e-mail do pagador seja o do usuário logado (opcional, caso queira forçar)
        if (!isset($data['payer']['email'])) {
            $data['payer']['email'] = auth()->user()->email;
        }

        // Gera uma chave de idempotência para evitar cobranças duplicadas
        // IMPORTANTE: Se time() for usado, retentativas legítimas falham.
        // Vamos usar uniqid() para garantir que seja sempre único.
        $idempotencyKey = $pedido->id . '_' . uniqid();

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
                    
                    // Baixa automática de estoque
                    $this->darBaixaEstoque($pedido);

                } elseif ($status === 'rejected' || $status === 'cancelled') {
                    $pedido->status_pagamento = 'cancelado';
                } elseif ($status === 'in_process' || $status === 'pending') {
                    $pedido->status_pagamento = 'pendente';
                }

                // Mapeia o tipo de pagamento do MP para o nosso ENUM do banco de dados
                // ENUM: 'pix','cartao_credito','cartao_debito','boleto','dinheiro','transferencia'
                $paymentTypeId = $paymentInfo['payment_type_id'] ?? null;
                $paymentMethodId = $paymentInfo['payment_method_id'] ?? null;
                
                $formaPagamento = null;
                if ($paymentTypeId === 'credit_card') {
                    $formaPagamento = 'cartao_credito';
                } elseif ($paymentTypeId === 'debit_card') {
                    $formaPagamento = 'cartao_debito';
                } elseif ($paymentTypeId === 'ticket') {
                    $formaPagamento = 'boleto';
                } elseif ($paymentTypeId === 'bank_transfer' || $paymentMethodId === 'pix') {
                    $formaPagamento = 'pix';
                }

                $pedido->forma_pagamento = $formaPagamento;
                $pedido->save();

                // Traduz o status_detail do Mercado Pago para mensagem amigável em português
                $statusDetail = $paymentInfo['status_detail'] ?? null;
                $mensagemRejeicao = match($statusDetail) {
                    'cc_rejected_bad_filled_card_number' => 'Número do cartão inválido. Por favor, verifique e tente novamente.',
                    'cc_rejected_bad_filled_date'        => 'Data de vencimento inválida. Por favor, verifique e tente novamente.',
                    'cc_rejected_bad_filled_other'       => 'Dados do cartão inválidos. Por favor, verifique e tente novamente.',
                    'cc_rejected_bad_filled_security_code' => 'Código de segurança (CVV) inválido. Por favor, verifique e tente novamente.',
                    'cc_rejected_blacklist'              => 'Não foi possível processar o pagamento com este cartão. Por favor, utilize outro cartão.',
                    'cc_rejected_call_for_authorize'     => 'O banco recusou a transação. Por favor, entre em contato com seu banco ou utilize outro cartão.',
                    'cc_rejected_card_disabled'          => 'O cartão está inativo. Por favor, ative-o pelo aplicativo do seu banco ou utilize outro cartão.',
                    'cc_rejected_card_error'             => 'Não foi possível processar o pagamento. Por favor, utilize outro cartão.',
                    'cc_rejected_duplicated_payment'     => 'Este pagamento já foi processado anteriormente.',
                    'cc_rejected_high_risk'              => 'Pagamento recusado pelos controles de segurança. Por favor, utilize outro cartão.',
                    'cc_rejected_insufficient_amount'    => 'Saldo insuficiente. Por favor, verifique o limite do seu cartão.',
                    'cc_rejected_invalid_installments'   => 'O número de parcelas não é aceito por este cartão.',
                    'cc_rejected_max_attempts'           => 'Número máximo de tentativas atingido. Por favor, utilize outro cartão.',
                    'cc_rejected_other_reason'           => 'Pagamento recusado pelo banco. Por favor, utilize outro cartão.',
                    default                              => null,
                };

                return response()->json([
                    'success'           => true,
                    'status'            => $status,
                    'status_detail'     => $statusDetail,
                    'mensagem_rejeicao' => $mensagemRejeicao,
                    'payment_id'        => $paymentInfo['id'] ?? null,
                    // Dados do PIX
                    'qr_code_base64' => $paymentInfo['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null,
                    'qr_code'        => $paymentInfo['point_of_interaction']['transaction_data']['qr_code'] ?? null,
                    // Dados Boleto
                    'ticket_url' => $paymentInfo['transaction_details']['external_resource_url'] ?? null,
                ]);
            }

            Log::error('Erro ao processar pagamento Mercado Pago: ' . $response->body());
            
            return response()->json([
                'error' => 'Falha ao processar o pagamento.',
                'details' => $response->json(),
                'payload_sent' => $data
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('Exceção ao processar pagamento Mercado Pago: ' . $e->getMessage());
            return response()->json([
                'error' => 'Falha na comunicação com o Mercado Pago: ' . $e->getMessage(),
                'payload_sent' => $data
            ], 500);
        }
    }

    /**
     * Recebe as notificações de pagamento via Webhook (IPN).
     */
    public function webhook(Request $request)
    {
        Log::info('Webhook Mercado Pago recebido', [
            'query' => $request->query(),
            'body' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        $topic = $request->query('topic') ?? $request->input('type');
        $paymentId = $request->query('id') ?? $request->input('data.id');

        if (str_starts_with($topic, 'payment') && $paymentId) {
            
            $accessToken = config('services.mercadopago.access_token');
            
            $response = Http::withoutVerifying()
                ->withToken($accessToken)
                ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

            if ($response->successful()) {
                $paymentInfo = $response->json();
                Log::info('Dados do pagamento consultados na API do MP', ['paymentInfo' => $paymentInfo]);
                
                $status = $paymentInfo['status'] ?? null;
                $pedidoId = $paymentInfo['external_reference'] ?? null;

                Log::info('Tentando localizar pedido para atualização', [
                    'external_reference' => $pedidoId,
                    'status_mp' => $status
                ]);

                if ($pedidoId && $status) {
                    $pedido = Pedido::find($pedidoId);
                    
                    if ($pedido) {
                        Log::info("Pedido #{$pedido->id} localizado. Status atual: {$pedido->status_pagamento}. Novo status: {$status}");
                        
                        if ($status === 'approved') {
                            $pedido->status_pagamento = 'pago';
                            
                            // Baixa automática de estoque via Webhook
                            $this->darBaixaEstoque($pedido);

                        } elseif ($status === 'rejected' || $status === 'cancelled') {
                            $pedido->status_pagamento = 'cancelado';
                        } elseif ($status === 'pending' || $status === 'in_process') {
                            $pedido->status_pagamento = 'pendente';
                        }
                        
                        $pedido->save();
                        Log::info("Pedido #{$pedido->id} atualizado com sucesso para {$pedido->status_pagamento}");
                    } else {
                        Log::warning("Pedido ID {$pedidoId} não encontrado no banco de dados.");
                    }
                }
            } else {
                Log::error('Erro ao consultar pagamento no Mercado Pago via Webhook', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Altera o status dos itens vinculados ao pedido para 'vendido'.
     */
    private function darBaixaEstoque(Pedido $pedido)
    {
        // Busca os IDs dos itens através da tabela items_pedido
        $itemIds = DB::table('items_pedido')
            ->where('pedido_id', $pedido->id)
            ->pluck('item_id');

        if ($itemIds->count() > 0) {
            // Atualiza o status na tabela items
            DB::table('items')
                ->whereIn('id', $itemIds)
                ->update([
                    'status' => 'vendido',
                    'updated_at' => now()
                ]);

            Log::info("Estoque baixado para o pedido #{$pedido->id}", ['items' => $itemIds]);
        }
    }
}

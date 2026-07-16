<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos';

    protected $fillable = [
        'numero_pedido',
        'payment_token',
        'user_id',
        'live_id',
        'data_pedido',
        'status_pedido',
        'valor_total',
        'valor_frete',
        'valor_frete_real',
        'valor_desconto',
        'valor_saldo_utilizado',
        'forma_pagamento',
        'status_pagamento',
        'endereco_entrega',
        'cep_entrega',
        'cidade_entrega',
        'estado_entrega',
        'codigo_rastreamento',
        'melhor_envio_id',
        'data_envio',
        'data_entrega_prevista',
        'data_entrega_realizada',
        'observacoes',
        'cupom_desconto',
        'origem_pedido',
        'inter_txid',
        'pontos_creditados',
    ];

    protected $casts = [
        'data_pedido' => 'datetime',
        'data_envio' => 'datetime',
        'data_entrega_prevista' => 'date',
        'data_entrega_realizada' => 'datetime',
        'pontos_creditados' => 'boolean',

        'valor_total' => 'decimal:2',
        'valor_frete' => 'decimal:2',
        'valor_frete_real' => 'decimal:2',
        'valor_desconto' => 'decimal:2',
        'valor_saldo_utilizado' => 'decimal:2',
    ];

    // Se você quiser padronizar ordenação em listagens
    protected $castsDefault = [];

    /** Relacionamentos */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Se existe tabela "lives" e Model Live no projeto, descomente:
    // public function live()
    // {
    //     return $this->belongsTo(Live::class, 'live_id');
    // }
    /**
     * Gera ou retorna a URL de pagamento seguro (sem login).
     */
    public function getPaymentUrl()
    {
        if (empty($this->payment_token)) {
            $this->payment_token = bin2hex(random_bytes(32));
            $this->save();
        }

        return route('portal.checkout.pagamento', $this->payment_token);
    }

    public function rastreamentos()
    {
        return $this->hasMany(\App\Models\PedidoRastreamento::class, 'pedido_id')->orderBy('data_hora', 'desc');
    }

    public function lancamento()
    {
        return $this->hasOne(\App\Models\Lancamento::class, 'referencia_id')->where('referencia_tipo', 'pedido');
    }

    public function movimentacoes()
    {
        return $this->hasManyThrough(
            \App\Models\Movimentacao::class,
            \App\Models\Lancamento::class,
            'referencia_id', // Foreign key on Lancamento table
            'lancamento_id', // Foreign key on Movimentacao table
            'id',            // Local key on Pedido table
            'id'             // Local key on Lancamento table
        )->where('lancamentos.referencia_tipo', 'pedido');
    }

    /**
     * Verifica e sincroniza os detalhes de rastreamento do Melhor Envio se necessário.
     * Retorna true em caso de sucesso na obtenção dos dados e false em caso de falha.
     */
    public function checkAndSyncTracking($force = false)
    {
        // Se o pedido já estiver concluído, entregue ou cancelado, não precisa mais sincronizar de forma automática
        if (!$force && in_array(strtolower($this->status_pedido ?? ''), ['entregue', 'concluido', 'cancelado'])) {
            return false;
        }

        $cartOrderId = null;
        if (!empty($this->melhor_envio_id)) {
            $cartOrderId = $this->melhor_envio_id;
        } else {
            // Tenta extrair das observações (etiqueta URL) para compatibilidade com pedidos anteriores
            if (preg_match('/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/', $this->observacoes, $matches)) {
                $cartOrderId = $matches[0];
            }
        }

        if (!$force && !$cartOrderId && !$this->codigo_rastreamento) {
            return false;
        }

        if (!$force) {
            $cacheKey = "tracking_sync_{$this->id}";
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                return false;
            }
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addMinutes(30));
        }

        try {
            $service = new \App\Services\MelhorEnvioService();
            $trackingData = null;
            $orderData = null;
            $details = null;

            if ($cartOrderId) {
                $details = $service->getTrackingDetails($cartOrderId);
                $searchRes = $service->searchOrder($cartOrderId);
                if ($searchRes && !empty($searchRes['data'])) {
                    $orderData = $searchRes['data'][0];
                }
            }

            if (!$details && $this->codigo_rastreamento) {
                $trackingData = $service->searchOrder($this->codigo_rastreamento);
            }

            if ((!$trackingData || empty($trackingData['data'])) && !$details) {
                $trackingData = $service->searchOrder($this->numero_pedido);
            }

            if ((!$trackingData || empty($trackingData['data'])) && !$details && $this->user && $this->user->cpf) {
                $cpfClean = preg_replace('/[^0-9]/', '', $this->user->cpf);
                if ($cpfClean) {
                    $trackingData = $service->searchOrder($cpfClean);
                }
            }

            if ((!$trackingData || empty($trackingData['data'])) && !$details && $this->user && $this->user->email) {
                $trackingData = $service->searchOrder($this->user->email);
            }

            if ((!$trackingData || empty($trackingData['data'])) && !$details && $this->user && $this->user->name) {
                $trackingData = $service->searchOrder($this->user->name);
            }

            if ($trackingData && !empty($trackingData['data']) && !$details) {
                if (count($trackingData['data']) === 1) {
                    $orderData = $trackingData['data'][0];
                } else {
                    foreach ($trackingData['data'] as $option) {
                        if (
                            (isset($option['reference']) && $option['reference'] === $this->numero_pedido) ||
                            (isset($option['to']['postal_code']) && preg_replace('/[^0-9]/', '', $option['to']['postal_code']) === preg_replace('/[^0-9]/', '', $this->cep_entrega))
                        ) {
                            $orderData = $option;
                            break;
                        }
                    }
                    if (!$orderData) {
                        $orderData = $trackingData['data'][0];
                    }
                }
                $cartOrderId = $orderData['id'];
                $details = $service->getTrackingDetails($cartOrderId);
            }

            if (!$details) {
                return false;
            }

            $updates = [];

            // Atualizar o código de rastreamento no banco se o obtivemos agora
            $trackingCode = $orderData['tracking'] ?? $details['tracking'] ?? null;
            if ($trackingCode && $this->codigo_rastreamento !== $trackingCode) {
                $updates['codigo_rastreamento'] = $trackingCode;
            }
            if ($cartOrderId && $this->melhor_envio_id !== $cartOrderId) {
                $updates['melhor_envio_id'] = $cartOrderId;
            }

            // Verifica data de postagem (envio)
            if (isset($details['posted_at'])) {
                $updates['data_envio'] = \Carbon\Carbon::parse($details['posted_at'])->tz('America/Sao_Paulo');
            }
            
            if (isset($orderData['price'])) {
                $updates['valor_frete_real'] = $orderData['price'];
            }

            $events = [];

            // 1. Criado / Pendente
            if (!empty($details['created_at'])) {
                $events[] = [
                    'status' => 'Pendente',
                    'descricao' => 'Aguardando pagamento ou liberação da etiqueta.',
                    'data_hora' => \Carbon\Carbon::parse($details['created_at'])->tz('America/Sao_Paulo'),
                ];
            }

            // 2. Liberado para envio
            if (!empty($details['generated_at'])) {
                $events[] = [
                    'status' => 'Liberado para envio',
                    'descricao' => 'A etiqueta foi liberada e está pronta para postagem.',
                    'data_hora' => \Carbon\Carbon::parse($details['generated_at'])->tz('America/Sao_Paulo'),
                ];
            } elseif (!empty($details['paid_at'])) {
                $events[] = [
                    'status' => 'Liberado para envio',
                    'descricao' => 'A etiqueta foi liberada e está pronta para postagem.',
                    'data_hora' => \Carbon\Carbon::parse($details['paid_at'])->tz('America/Sao_Paulo'),
                ];
            }

            // 3. Postado
            if (!empty($details['posted_at'])) {
                $events[] = [
                    'status' => 'Postado',
                    'descricao' => 'O pacote foi entregue na agência ou transportadora.',
                    'data_hora' => \Carbon\Carbon::parse($details['posted_at'])->tz('America/Sao_Paulo'),
                ];
            }

            // 4. Em trânsito / Saiu para entrega
            $currentStatus = strtolower($details['status'] ?? '');
            if ($currentStatus === 'in transit') {
                $events[] = [
                    'status' => 'Em trânsito',
                    'descricao' => 'Seu pacote está viajando para a próxima unidade de distribuição.',
                    'data_hora' => !empty($details['posted_at']) ? \Carbon\Carbon::parse($details['posted_at'])->addMinutes(30)->tz('America/Sao_Paulo') : now(),
                ];
            } elseif ($currentStatus === 'out for delivery') {
                $events[] = [
                    'status' => 'Saiu para entrega',
                    'descricao' => 'O entregador já saiu com seu pacote. Prepare-se para receber!',
                    'data_hora' => now(),
                ];
            }

            // 5. Entregue
            if (!empty($details['delivered_at'])) {
                $events[] = [
                    'status' => 'Entregue',
                    'descricao' => 'O pacote foi entregue no destino.',
                    'data_hora' => \Carbon\Carbon::parse($details['delivered_at'])->tz('America/Sao_Paulo'),
                ];
            }

            // 6. Cancelado
            if (!empty($details['canceled_at'])) {
                $events[] = [
                    'status' => 'Cancelado',
                    'descricao' => 'O envio foi cancelado.',
                    'data_hora' => \Carbon\Carbon::parse($details['canceled_at'])->tz('America/Sao_Paulo'),
                ];
            }

            // 7. Eventos detalhados
            if (isset($details['tracking']) && is_array($details['tracking']) && isset($details['tracking']['events']) && is_array($details['tracking']['events'])) {
                foreach ($details['tracking']['events'] as $event) {
                    $local = $event['location'] ?? '';
                    $detalhes = $event['message'] ?? '';
                    $descricao = trim(($local ? "[$local] " : "") . $detalhes);
                    if (empty($descricao)) {
                        $descricao = 'Atualização de rastreamento recebida.';
                    }
                    $events[] = [
                        'status' => $event['status'],
                        'descricao' => $descricao,
                        'data_hora' => \Carbon\Carbon::parse($event['date_time'])->tz('America/Sao_Paulo'),
                    ];
                }
            }

            // Registrar os eventos na tabela de rastreamentos
            foreach ($events as $event) {
                \App\Models\PedidoRastreamento::firstOrCreate([
                    'pedido_id' => $this->id,
                    'status' => $event['status'],
                    'data_hora' => $event['data_hora'],
                ], [
                    'descricao' => $event['descricao'],
                ]);

                // Atualizar status do pedido com base nos eventos
                $eventStatus = strtolower($event['status']);
                if ($eventStatus === 'postado' && !isset($updates['data_envio'])) {
                    $updates['data_envio'] = $event['data_hora'];
                }
                if ($eventStatus === 'entregue') {
                    $updates['data_entrega_realizada'] = $event['data_hora'];
                    $updates['status_pedido'] = 'entregue';
                }
            }

            if (isset($orderData['estimated_delivery'])) {
                $updates['data_entrega_prevista'] = \Carbon\Carbon::parse($orderData['estimated_delivery'])->tz('America/Sao_Paulo');
            } elseif (isset($details['estimated_date'])) {
                $updates['data_entrega_prevista'] = \Carbon\Carbon::parse($details['estimated_date'])->tz('America/Sao_Paulo');
            }

            if (!empty($updates)) {
                $this->update($updates);
            }

            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erro ao sincronizar rastreio do pedido {$this->id}: " . $e->getMessage());
            return false;
        }
    }

    public function getValorTotalOriginalAttribute()
    {
        // Como o valor_total do pedido agora não diminui nas devoluções (modelo completo),
        // o próprio valor_total já representa o valor bruto original do pedido.
        return (float) $this->valor_total;
    }
}
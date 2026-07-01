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
     */
    public function checkAndSyncTracking()
    {
        if (!$this->codigo_rastreamento) {
            return;
        }

        // Se o pedido já estiver concluído, entregue ou cancelado, não precisa mais sincronizar
        if (in_array(strtolower($this->status_pedido ?? ''), ['entregue', 'concluido', 'cancelado'])) {
            return;
        }

        // Verifica a última sincronização para evitar sobrecarga de requisições (mínimo de 30 minutos)
        $ultimoRastreio = \Illuminate\Support\Facades\DB::table('pedido_rastreamentos')
            ->where('pedido_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($ultimoRastreio) {
            $lastSync = \Carbon\Carbon::parse($ultimoRastreio->created_at);
            if ($lastSync->diffInMinutes(now()) < 30) {
                return;
            }
        }

        try {
            $service = new \App\Services\MelhorEnvioService();
            $trackingData = $service->searchOrder($this->codigo_rastreamento);

            if (!$trackingData || empty($trackingData['data'])) {
                return;
            }

            $orderData = $trackingData['data'][0];
            $cartOrderId = $orderData['id'];

            $details = $service->getTrackingDetails($cartOrderId);

            if (!$details) {
                return;
            }

            $updates = [];
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
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erro ao sincronizar rastreio do pedido {$this->id}: " . $e->getMessage());
        }
    }

    public function getValorTotalOriginalAttribute()
    {
        // Como o valor_total do pedido agora não diminui nas devoluções (modelo completo),
        // o próprio valor_total já representa o valor bruto original do pedido.
        return (float) $this->valor_total;
    }
}
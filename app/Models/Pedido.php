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
    ];

    protected $casts = [
        'data_pedido' => 'datetime',
        'data_envio' => 'datetime',
        'data_entrega_prevista' => 'date',
        'data_entrega_realizada' => 'datetime',

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
}
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
        'user_id',
        'live_id',
        'data_pedido',
        'status_pedido',
        'valor_total',
        'valor_frete',
        'valor_desconto',
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
        'valor_desconto' => 'decimal:2',
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
}
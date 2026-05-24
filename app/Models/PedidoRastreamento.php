<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoRastreamento extends Model
{
    protected $table = 'pedido_rastreamentos';

    protected $fillable = [
        'pedido_id',
        'status',
        'descricao',
        'data_hora',
    ];

    protected $casts = [
        'data_hora' => 'datetime',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }
}

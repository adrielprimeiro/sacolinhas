<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PontuacoesCliente extends Model
{
    use HasFactory;

    protected $table = 'pontuacoes_clientes';
    protected $primaryKey = ['user_id', 'mes_ano'];
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 'mes_ano', 'pontos_mensalidade', 'pontos_itens',
        'pontos_desafios', 'pontos_bonus_grupo'
    ];

    protected $casts = [
        'pontos_mensalidade' => 'decimal:2',
        'pontos_itens' => 'decimal:2',
        'pontos_desafios' => 'decimal:2',
        'pontos_bonus_grupo' => 'decimal:2',
    ];
}
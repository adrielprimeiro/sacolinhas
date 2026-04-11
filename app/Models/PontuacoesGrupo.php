<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PontuacoesGrupo extends Model
{
    use HasFactory;

    protected $table = 'pontuacoes_grupos';
    protected $primaryKey = ['grupo_id', 'mes_ano'];
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'grupo_id', 'mes_ano', 'pontos_mensalidades', 'pontos_itens'
    ];

    protected $casts = [
        'pontos_mensalidades' => 'decimal:2',
        'pontos_itens' => 'decimal:1',
    ];
}
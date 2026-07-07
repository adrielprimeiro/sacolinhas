<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marca extends Model
{
    use HasFactory;

    protected $table = 'marcas';

    protected $fillable = [
        'nome',
        'porcentagem_valor'
    ];

    protected $casts = [
        'porcentagem_valor' => 'decimal:2',
    ];

    /**
     * Relacionamento com as peças avaliadas que usam esta marca.
     */
    public function items()
    {
        return $this->hasMany(AvaliacaoItem::class, 'marca_id');
    }

    /**
     * Formatar a porcentagem de valor para visualização (ex: 350.00 -> 350%).
     */
    public function getFormattedPorcentagemAttribute()
    {
        return number_format((float) $this->porcentagem_valor, 0) . '%';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConferenciaInventario extends Model
{
    use HasFactory;

    protected $table = 'conferencias_inventario';

    protected $fillable = [
        'user_id',
        'localizacao',
        'status_aplicado',
        'cor_aplicada',
        'total_esperado',
        'total_lido',
        'total_encontrados',
        'total_faltantes',
        'total_sobrando',
        'acuracia_percentual',
        'detalhes_json',
    ];

    protected $casts = [
        'detalhes_json' => 'array',
        'acuracia_percentual' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteLimite extends Model
{
    use HasFactory;

    protected $table = 'cliente_limites';

    protected $fillable = [
        'user_id',
        'limite_credito',
        'limite_utilizado',
        'limite_disponivel',
        'data_ultimo_ajuste',
        'motivo_ultimo_ajuste',
        'ativo',
    ];

    protected $casts = [
        'limite_credito' => 'decimal:2',
        'limite_utilizado' => 'decimal:2',
        'limite_disponivel' => 'decimal:2',
        'data_ultimo_ajuste' => 'datetime',
        'ativo' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
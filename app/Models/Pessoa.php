<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pessoa extends Model
{
    protected $fillable = [
        'user_id',
        'nome',
        'documento',
        'tipo',
    ];

    /**
     * O usuário vinculado a esta pessoa (pode ser nulo).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Todos os lançamentos financeiros vinculados a esta pessoa.
     */
    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class);
    }
}

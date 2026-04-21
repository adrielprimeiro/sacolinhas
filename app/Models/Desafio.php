<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Desafio extends Model
{
    protected $table = 'desafios';

    protected $fillable = [
        'nome',
        'descricao',
        'pontos',
        'inicio_em',
        'fim_em',
        'status',
    ];

    protected $casts = [
        'inicio_em' => 'date',
        'fim_em'    => 'date',
    ];

    public function scopeAtivos($query)
    {
        return $query->where('status', 'ativo');
    }

    public function estaVigente(): bool
    {
        $hoje = now()->toDateString();
        $aposInicio = !$this->inicio_em || $this->inicio_em->lte(now());
        $antesDoFim = !$this->fim_em   || $this->fim_em->gte(now());
        return $this->status === 'ativo' && $aposInicio && $antesDoFim;
    }
}

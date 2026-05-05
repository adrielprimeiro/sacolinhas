<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassificacaoFinanceira extends Model
{
    protected $table = 'classificacao_financeira';

    protected $fillable = [
        'user_id',
        'nome',
        'codigo_contabil',
        'tipo_natureza',
        'nivel',
        'id_pai',
        'area_finalidade',
        'frequencia',
        'descricao',
    ];

    public function pai(): BelongsTo
    {
        return $this->belongsTo(self::class, 'id_pai');
    }

    public function filhos(): HasMany
    {
        return $this->hasMany(self::class, 'id_pai');
    }

    /**
     * Os lançamentos financeiros classificados nesta categoria.
     */
    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class, 'classificacao_financeira_id');
    }

    /**
     * Os orçamentos mensais desta categoria.
     */
    public function orcamentos(): HasMany
    {
        return $this->hasMany(Orcamento::class, 'classificacao_financeira_id');
    }
}
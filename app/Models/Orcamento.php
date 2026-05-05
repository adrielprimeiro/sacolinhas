<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Orcamento extends Model
{
    protected $fillable = [
        'classificacao_financeira_id',
        'periodo',
        'valor_previsto',
    ];

    protected $casts = [
        'periodo'         => 'date',
        'valor_previsto'  => 'decimal:2',
    ];

    /**
     * A categoria do plano de contas a qual este orçamento pertence.
     */
    public function classificacaoFinanceira(): BelongsTo
    {
        return $this->belongsTo(ClassificacaoFinanceira::class, 'classificacao_financeira_id');
    }
}

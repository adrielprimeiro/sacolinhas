<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lancamento extends Model
{
    protected $table = 'lancamentos';

    protected $fillable = [
        'tipo',
        'status',
        'pessoa_id',
        'classificacao_financeira_id',
        'data_emissao',
        'data_vencimento',
        'valor_total',
        'descricao',
        'observacoes',
        'referencia_tipo',
        'referencia_id',
        'payment_token',
        'inter_txid',
    ];

    protected $casts = [
        'data_emissao'    => 'date',
        'data_vencimento' => 'date',
        'valor_total'     => 'decimal:2',
    ];

    /**
     * A pessoa (fornecedor, cliente etc.) vinculada ao lançamento.
     */
    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class);
    }

    /**
     * A categoria do plano de contas vinculada ao lançamento.
     */
    public function classificacaoFinanceira(): BelongsTo
    {
        return $this->belongsTo(ClassificacaoFinanceira::class, 'classificacao_financeira_id');
    }

    /**
     * As movimentações de caixa (baixas parciais ou totais) deste lançamento.
     */
    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Movimentacao::class);
    }

    /**
     * Verifica se o lançamento está atrasado (vencido e não pago).
     */
    public function getAtrasadoAttribute(): bool
    {
        return !in_array($this->status, ['pago', 'cancelado'])
            && $this->data_vencimento->isPast();
    }

    /**
     * Total já liquidado (soma das movimentações).
     */
    public function getValorPagoTotalAttribute(): float
    {
        return (float) $this->movimentacoes->sum('valor_pago');
    }
}

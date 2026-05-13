<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimentacao extends Model
{
    protected $table = 'movimentacoes';

    protected $fillable = [
        'lancamento_id',
        'conta_bancaria_id',
        'data_pagamento',
        'valor_pago',
        'forma_pagamento',
    ];

    protected $casts = [
        'data_pagamento' => 'date',
        'valor_pago'     => 'decimal:2',
    ];

    /**
     * O lançamento (competência) ao qual esta movimentação de caixa pertence.
     */
    public function lancamento(): BelongsTo
    {
        return $this->belongsTo(Lancamento::class);
    }

    /**
     * A conta bancária que recebeu ou debitou este valor.
     */
    public function contaBancaria(): BelongsTo
    {
        return $this->belongsTo(ContaBancaria::class);
    }

    /**
     * A transação do extrato vinculada a esta movimentação.
     */
    public function transacaoExtrato()
    {
        return $this->hasOne(TransacaoExtrato::class, 'movimentacao_id');
    }
}

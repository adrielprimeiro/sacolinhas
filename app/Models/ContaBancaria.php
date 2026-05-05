<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContaBancaria extends Model
{
    protected $table = 'contas_bancarias';

    protected $fillable = [
        'nome',
        'tipo',
        'saldo_inicial',
    ];

    protected $casts = [
        'saldo_inicial' => 'decimal:2',
    ];

    /**
     * Todas as movimentações (pagamentos/recebimentos) registradas nesta conta.
     */
    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Movimentacao::class);
    }

    /**
     * Calcula o saldo atual da conta: saldo inicial + entradas - saídas das movimentações.
     * Leva em conta o tipo do lançamento vinculado.
     */
    public function getSaldoAtualAttribute(): float
    {
        $entradas = $this->movimentacoes()
            ->whereHas('lancamento', fn ($q) => $q->where('tipo', 'receita'))
            ->sum('valor_pago');

        $saidas = $this->movimentacoes()
            ->whereHas('lancamento', fn ($q) => $q->where('tipo', 'despesa'))
            ->sum('valor_pago');

        return (float) $this->saldo_inicial + $entradas - $saidas;
    }
}

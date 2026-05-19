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
     * Calcula o saldo atual da conta.
     * Para a conta virtual "Carteira Cliente", o saldo atual é a soma em tempo real do saldo de todos os clientes.
     * Para as demais contas, calcula: saldo inicial + entradas - saídas das movimentações.
     */
    public function getSaldoAtualAttribute(): float
    {
        if (str_contains(strtolower($this->nome), 'carteira')) {
            $subQueryMaxDate = \DB::table('conta_corrente')
                ->select('user_id', \DB::raw('MAX(data_movimentacao) as max_date'))
                ->groupBy('user_id');

            $subQueryMaxId = \DB::table('conta_corrente as cc')
                ->joinSub($subQueryMaxDate, 'tm', function($join) {
                    $join->on('cc.user_id', '=', 'tm.user_id')
                         ->on('cc.data_movimentacao', '=', 'tm.max_date');
                })
                ->select('cc.user_id', \DB::raw('MAX(cc.id) as max_id'))
                ->groupBy('cc.user_id');

            return (float) \DB::table('conta_corrente as cc')
                ->joinSub($subQueryMaxId, 'mi', function($join) {
                    $join->on('cc.id', '=', 'mi.max_id');
                })
                ->sum('cc.saldo_atual');
        }

        $entradas = $this->movimentacoes()
            ->whereHas('lancamento', fn ($q) => $q->where('tipo', 'receita'))
            ->sum('valor_pago');

        $saidas = $this->movimentacoes()
            ->whereHas('lancamento', fn ($q) => $q->where('tipo', 'despesa'))
            ->sum('valor_pago');

        return (float) $this->saldo_inicial + $entradas - $saidas;
    }
}

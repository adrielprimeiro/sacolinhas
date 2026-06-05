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

    protected static function boot()
    {
        parent::boot();

        static::created(function ($movimentacao) {
            $movimentacao->sincronizarCarteira();
            $movimentacao->sincronizarPedidoStatus();
        });

        static::updated(function ($movimentacao) {
            $movimentacao->sincronizarCarteira();
            $movimentacao->sincronizarPedidoStatus();
        });

        static::deleted(function ($movimentacao) {
            \App\Models\ContaCorrente::where('referencia_tipo', 'movimentacao')
                ->where('referencia_id', $movimentacao->id)
                ->delete();
        });
    }

    /**
     * Sincroniza esta movimentação com a carteira (conta corrente) do cliente
     */
    public function sincronizarCarteira()
    {
        $lancamento = $this->lancamento;
        if (!$lancamento || !$lancamento->pessoa_id) return;

        // Se o lançamento for do tipo 'carteira_credito', ele foi gerado a partir de um crédito na carteira.
        // Nunca devemos sincronizar de volta para a carteira para não gerar duplicidade ou débitos indevidos.
        if ($lancamento->referencia_tipo === 'carteira_credito') {
            return;
        }

        // Se a forma de pagamento for o próprio saldo da carteira, não espelha para evitar duplicidade de crédito
        if ($this->forma_pagamento === 'saldo_carteira') {
            return;
        }

        $pessoa = $lancamento->pessoa;
        if (!$pessoa->user_id) return;

        $tipoMov = ($lancamento->tipo === 'receita') ? 'credito' : 'debito';
        
        $data = [
            'user_id' => $pessoa->user_id,
            'tipo_movimentacao' => $tipoMov,
            'valor' => $this->valor_pago,
            'descricao' => "Pagamento: " . ($lancamento->descricao ?: 'S/D'),
            'classificacao_id' => $lancamento->classificacao_financeira_id,
            'referencia_tipo' => 'movimentacao',
            'referencia_id' => $this->id,
            'data_movimentacao' => $this->data_pagamento,
        ];

        $contaCorrente = \App\Models\ContaCorrente::updateOrCreate(
            ['referencia_tipo' => 'movimentacao', 'referencia_id' => $this->id],
            $data
        );

        // Despachar Job para recalcular saldo se disponível
        if (class_exists(\App\Jobs\RecalcularSaldosJob::class)) {
            \App\Jobs\RecalcularSaldosJob::dispatch($pessoa->user_id, $this->data_pagamento->toDateString());
        }
    }

    /**
     * Sincroniza o status de pagamento do Pedido associado a esta movimentação
     */
    public function sincronizarPedidoStatus()
    {
        $lancamento = $this->lancamento;
        if (!$lancamento || $lancamento->referencia_tipo !== 'pedido') {
            return;
        }

        $pedido = \App\Models\Pedido::find($lancamento->referencia_id);
        if (!$pedido) {
            return;
        }

        // Se o pedido já está aprovado, não há nada a fazer
        if ($pedido->status_pagamento === 'aprovado') {
            return;
        }

        // Calcula a soma de pagamentos em dinheiro/banco (excluindo a baixa virtual de carteira)
        $totalPagoDinheiro = (float) $lancamento->movimentacoes()
            ->where('forma_pagamento', '!=', 'saldo_carteira')
            ->sum('valor_pago');

        $saldoUtilizado = max(0.00, (float) $pedido->valor_saldo_utilizado);
        $totalGeral = $totalPagoDinheiro + $saldoUtilizado;

        // Se a soma de pagamentos reais + saldo de carteira cobrir o valor total (com tolerância de R$ 0.01)
        if ($totalGeral >= ((float)$pedido->valor_total - 0.01)) {
            $pedido->update(['status_pagamento' => 'aprovado']);
        }
    }

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

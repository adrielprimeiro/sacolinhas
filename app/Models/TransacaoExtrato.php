<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransacaoExtrato extends Model
{
    protected $table = 'transacoes_extrato';

    protected $fillable = [
        'fitid',
        'data',
        'descricao',
        'valor',
        'valor_bruto',
        'valor_taxa',
        'valor_liquido',
        'tipo',
        'status',
        'origem',
        'conta_bancaria_id',
        'movimentacao_id',
        'payload_original'
    ];

    protected $casts = [
        'data' => 'date',
        'valor' => 'decimal:2',
        'payload_original' => 'array'
    ];

    public function contaBancaria(): BelongsTo
    {
        return $this->belongsTo(ContaBancaria::class);
    }

    public function movimentacao(): BelongsTo
    {
        return $this->belongsTo(Movimentacao::class);
    }

    public function movimentacoes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Movimentacao::class, 'transacao_extrato_id');
    }

    /**
     * Tenta extrair o ID do pedido do payload original
     */
    public function getPedidoId()
    {
        if ($this->origem === 'mercadopago' && isset($this->payload_original['external_reference'])) {
            return $this->payload_original['external_reference'];
        }
        return null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContaCorrente extends Model
{
    use HasFactory;

    protected $table = 'conta_corrente';

    protected $fillable = [
        'user_id',
        'tipo_movimentacao',
        'valor',
        'descricao',
        'referencia_tipo',   // Manter se ainda for usado para outros tipos (sacolinha, pedido)
        'referencia_id',     // Manter se ainda for usado para outros tipos (sacolinha, pedido)
        'classificacao_id',  // <--- NOVO: Adicionar esta coluna
        'saldo_anterior',
        'saldo_atual',
        'data_movimentacao',
        'observacoes',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'saldo_anterior' => 'decimal:2',
        'saldo_atual' => 'decimal:2',
        'data_movimentacao' => 'datetime',
    ];

    /**
     * Relacionamento com o usuário.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento direto com a ClassificacaoFinanceira usando a nova coluna.
     */
    public function classificacaoFinanceira()
    {
        return $this->belongsTo(ClassificacaoFinanceira::class, 'classificacao_id'); // <--- AJUSTADO AQUI
    }

    // Se você tiver outros relacionamentos polimórficos para referencia_tipo/referencia_id,
    // eles permaneceriam aqui.
}
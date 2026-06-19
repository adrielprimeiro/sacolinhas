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

    protected static function boot()
    {
        parent::boot();

        static::created(function ($movimentacao) {
            $movimentacao->sincronizarFinanceiro();
        });

        static::updated(function ($movimentacao) {
            $movimentacao->sincronizarFinanceiro();
        });

        static::deleted(function ($movimentacao) {
            $lancamento = \App\Models\Lancamento::where('referencia_tipo', 'carteira_credito')
                ->where('referencia_id', $movimentacao->id)
                ->first();
            if ($lancamento) {
                $lancamento->movimentacoes()->delete();
                $lancamento->delete();
            }

            // Deleção bidirecional se for vinculado a uma movimentação de conciliação
            if ($movimentacao->referencia_tipo === 'movimentacao' && $movimentacao->referencia_id) {
                $mov = \App\Models\Movimentacao::find($movimentacao->referencia_id);
                if ($mov) {
                    $mov->delete();
                }
            }
        });
    }

    public function sincronizarFinanceiro()
    {
        // Sob a regra do regime de caixa estrito, créditos e débitos internos na carteira (devoluções, avaliações, etc.)
        // são puramente virtuais e não correspondem a fluxo de caixa real. Portanto, nunca devem gerar
        // lançamentos e movimentações financeiras de 'carteira_credito'.
        $lancamento = \App\Models\Lancamento::where('referencia_tipo', 'carteira_credito')
            ->where('referencia_id', $this->id)
            ->first();
        if ($lancamento) {
            $lancamento->movimentacoes()->delete();
            $lancamento->delete();
        }
    }
}
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

        static::creating(function ($movimentacao) {
            if (empty($movimentacao->classificacao_id)) {
                $desc = mb_strtoupper($movimentacao->descricao, 'UTF-8');
                if (str_contains($desc, 'AVALIA')) {
                    $movimentacao->classificacao_id = 19;
                } elseif (str_contains($desc, 'DEVOLU')) {
                    $movimentacao->classificacao_id = 81;
                } else {
                    $movimentacao->classificacao_id = 19;
                }
            }
        });

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
        // Se a movimentação na carteira não for originada de um fluxo que já gera lançamentos (como um pedido)
        if (!in_array($this->referencia_tipo, ['movimentacao', 'pedido', 'desconto', 'tolerancia'])) {
            $user = $this->user;
            if ($user) {
                $pessoa = $user->perfilFinanceiro;
                if (!$pessoa) {
                    $pessoa = \App\Models\Pessoa::create([
                        'user_id'   => $user->id,
                        'nome'      => $user->name,
                        'documento' => $user->cpf ?? $user->whatsapp ?? $user->phone,
                        'tipo'      => 'cliente_circular',
                    ]);
                }

                $tipoLancamento = $this->tipo_movimentacao === 'credito' ? 'despesa' : 'receita';
                $descricaoLancamento = $this->tipo_movimentacao === 'credito' 
                    ? "Crédito Cliente (Avaliação/Devolução/Manual): " . ($this->descricao ?: 'Crédito em Carteira')
                    : "Débito Cliente (Ajuste/Manual): " . ($this->descricao ?: 'Débito em Carteira');

                $lancamento = \App\Models\Lancamento::updateOrCreate(
                    [
                        'referencia_tipo' => 'carteira_credito',
                        'referencia_id' => $this->id,
                    ],
                    [
                        'tipo'                        => $tipoLancamento,
                        'status'                      => 'pago',
                        'pessoa_id'                   => $pessoa->id,
                        'classificacao_financeira_id' => $this->classificacao_id,
                        'data_emissao'                => $this->data_movimentacao,
                        'data_vencimento'             => $this->data_movimentacao,
                        'valor_total'                 => $this->valor,
                        'descricao'                   => $descricaoLancamento,
                    ]
                );

                $contaCarteira = \App\Models\ContaBancaria::where('nome', 'like', '%carteira%')->first();
                $contaBancariaId = $contaCarteira ? $contaCarteira->id : 3;

                \App\Models\Movimentacao::updateOrCreate(
                    [
                        'lancamento_id' => $lancamento->id,
                    ],
                    [
                        'conta_bancaria_id' => $contaBancariaId,
                        'data_pagamento' => $this->data_movimentacao,
                        'valor_pago' => $this->valor,
                        'forma_pagamento' => 'saldo_carteira',
                    ]
                );
            }
        } else {
            $lancamento = \App\Models\Lancamento::where('referencia_tipo', 'carteira_credito')
                ->where('referencia_id', $this->id)
                ->first();
            if ($lancamento) {
                $lancamento->movimentacoes()->delete();
                $lancamento->delete();
            }
        }
    }
}
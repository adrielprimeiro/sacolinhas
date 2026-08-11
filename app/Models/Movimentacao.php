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
        'transacao_extrato_id',
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
            $movimentacao->sincronizarClube();
            $movimentacao->sincronizarPedidoStatus();
            $movimentacao->sincronizarLancamentoStatus();
        });

        static::updated(function ($movimentacao) {
            $movimentacao->sincronizarCarteira();
            $movimentacao->sincronizarClube();
            $movimentacao->sincronizarPedidoStatus();
            $movimentacao->sincronizarLancamentoStatus();
        });

        static::deleted(function ($movimentacao) {
            \App\Models\ContaCorrente::where('referencia_tipo', 'movimentacao')
                ->where('referencia_id', $movimentacao->id)
                ->delete();

            $movimentacao->reverterClube();

            // Desvincular transação de extrato associada
            \DB::table('transacoes_extrato')
                ->where('movimentacao_id', $movimentacao->id)
                ->orWhere('id', $movimentacao->transacao_extrato_id)
                ->update([
                    'status' => 'pendente',
                    'movimentacao_id' => null
                ]);

            $movimentacao->sincronizarLancamentoStatus();
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

        // Se for Clube Mania (ID 82 ou código 1.03), não registrar na Carteira do cliente
        $isClubeMania = false;
        if ($lancamento->classificacao_financeira_id == 82) {
            $isClubeMania = true;
        } else {
            $classificacao = $lancamento->classificacaoFinanceira;
            if ($classificacao && ($classificacao->codigo_contabil === '1.03' || strtolower($classificacao->nome) === 'clube mania')) {
                $isClubeMania = true;
            }
        }

        if ($isClubeMania) {
            \App\Models\ContaCorrente::where('referencia_tipo', 'movimentacao')
                ->where('referencia_id', $this->id)
                ->delete();
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

        // Se for despesa de Fornecedor/Avaliados (ID 19), gera o débito e o crédito separadamente
        if ($lancamento->tipo === 'despesa' && $lancamento->classificacao_financeira_id == 19) {
            // 1. O Débito (o pagamento em si)
            \App\Models\ContaCorrente::updateOrCreate(
                [
                    'referencia_tipo' => 'movimentacao',
                    'referencia_id' => $this->id,
                    'tipo_movimentacao' => 'debito',
                ],
                [
                    'user_id' => $pessoa->user_id,
                    'valor' => $this->valor_pago,
                    'descricao' => "Pagamento: " . ($lancamento->descricao ?: 'S/D'),
                    'classificacao_id' => $lancamento->classificacao_financeira_id,
                    'data_movimentacao' => $this->data_pagamento,
                ]
            );

            // 2. O Crédito (a entrada dos avaliados)
            \App\Models\ContaCorrente::updateOrCreate(
                [
                    'referencia_tipo' => 'movimentacao',
                    'referencia_id' => $this->id,
                    'tipo_movimentacao' => 'credito',
                ],
                [
                    'user_id' => $pessoa->user_id,
                    'valor' => $this->valor_pago,
                    'descricao' => "Crédito de Avaliados (Entrada de Itens): " . ($lancamento->descricao ?: 'S/D'),
                    'classificacao_id' => $lancamento->classificacao_financeira_id,
                    'data_movimentacao' => $this->data_pagamento,
                ]
            );
        } else {
            // Para outros tipos de movimentação, o comportamento padrão de uma única via
            \App\Models\ContaCorrente::updateOrCreate(
                [
                    'referencia_tipo' => 'movimentacao',
                    'referencia_id' => $this->id,
                    'tipo_movimentacao' => $tipoMov
                ],
                $data
            );

            // Limpar a contrapartida oposta se existir
            $tipoOposto = $tipoMov === 'credito' ? 'debito' : 'credito';
            \App\Models\ContaCorrente::where('referencia_tipo', 'movimentacao')
                ->where('referencia_id', $this->id)
                ->where('tipo_movimentacao', $tipoOposto)
                ->delete();
        }

        // Despachar Job para recalcular saldo se disponível
        if (class_exists(\App\Jobs\RecalcularSaldosJob::class)) {
            \App\Jobs\RecalcularSaldosJob::dispatch($pessoa->user_id, $this->data_pagamento->toDateString());
        }
    }

    /**
     * Sincroniza o pagamento com o Clube Mania (mensalidades) se for essa classificação
     */
    public function sincronizarClube()
    {
        $lancamento = $this->lancamento;
        if (!$lancamento) return;

        $isClubeMania = false;
        if ($lancamento->classificacao_financeira_id == 82) {
            $isClubeMania = true;
        } else {
            $classificacao = $lancamento->classificacaoFinanceira;
            if ($classificacao && ($classificacao->codigo_contabil === '1.03' || strtolower($classificacao->nome) === 'clube mania')) {
                $isClubeMania = true;
            }
        }
        if (!$isClubeMania) return;

        $pessoa = $lancamento->pessoa;
        if (!$pessoa || !$pessoa->user_id) return;
        $userId = $pessoa->user_id;

        $dataPagamento = $this->data_pagamento;
        if (!$dataPagamento) return;
        
        $ano = (int) $dataPagamento->format('Y');
        $mes = (int) $dataPagamento->format('m');

        \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $ano, $mes, $dataPagamento) {
            // 1) Garante assinatura ativa para o usuário
            $assinatura = \Illuminate\Support\Facades\DB::table('clube_assinaturas')
                ->where('user_id', $userId)
                ->first(['id', 'status']);

            if (!$assinatura) {
                // Se não existe, cria uma nova ativa
                $assinaturaId = \Illuminate\Support\Facades\DB::table('clube_assinaturas')->insertGetId([
                    'user_id'    => $userId,
                    'status'     => 'ativa',
                    'inicio_em'  => now()->toDateString(),
                    'fim_em'     => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $assinaturaId = $assinatura->id;

                // Se existe mas não está ativa, ativa agora
                if ($assinatura->status !== 'ativa') {
                    \Illuminate\Support\Facades\DB::table('clube_assinaturas')
                        ->where('id', $assinaturaId)
                        ->update([
                            'status'     => 'ativa',
                            'updated_at' => now(),
                        ]);
                }
            }

            // 2) Grava ou Atualiza a mensalidade como PAGA
            $mensalidade = \Illuminate\Support\Facades\DB::table('clube_mensalidades')
                ->where('user_id', $userId)
                ->where('competencia_ano', $ano)
                ->where('competencia_mes', $mes)
                ->first(['id']);

            $payloadMensalidade = [
                'assinatura_id'    => $assinaturaId,
                'status_pagamento' => 'pago',
                'pago_em'          => $dataPagamento->toDateString(),
                'valor'            => $this->valor_pago,
            ];

            if ($mensalidade) {
                \Illuminate\Support\Facades\DB::table('clube_mensalidades')
                    ->where('id', $mensalidade->id)
                    ->update($payloadMensalidade);
            } else {
                $payloadMensalidade['user_id']         = $userId;
                $payloadMensalidade['competencia_ano'] = $ano;
                $payloadMensalidade['competencia_mes'] = $mes;
                $payloadMensalidade['created_at']      = now();

                \Illuminate\Support\Facades\DB::table('clube_mensalidades')->insert($payloadMensalidade);
            }
        });

        // 3) Recalcular pontos e nível no Clube do usuário
        $mesAno = sprintf('%04d-%02d', $ano, $mes);
        try {
            \Illuminate\Support\Facades\DB::unprepared("CALL atualizar_pontuacoes_user({$userId}, '{$mesAno}')");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erro ao chamar atualizar_pontuacoes_user no sincronizarClube: " . $e->getMessage());
        }

        // 4) Recalcular indicadores (Job assíncrono)
        if (class_exists(\App\Domains\Clube\Jobs\RecalcularIndicadoresClienteJob::class)) {
            \App\Domains\Clube\Jobs\RecalcularIndicadoresClienteJob::dispatch((int) $userId)->afterCommit();
        }
    }

    /**
     * Reverte a mensalidade do clube para não pago (deleta registro) se a movimentação for excluída
     */
    public function reverterClube()
    {
        $lancamento = $this->lancamento;
        if (!$lancamento) return;

        $isClubeMania = false;
        if ($lancamento->classificacao_financeira_id == 82) {
            $isClubeMania = true;
        } else {
            $classificacao = $lancamento->classificacaoFinanceira;
            if ($classificacao && ($classificacao->codigo_contabil === '1.03' || strtolower($classificacao->nome) === 'clube mania')) {
                $isClubeMania = true;
            }
        }
        if (!$isClubeMania) return;

        $pessoa = $lancamento->pessoa;
        if (!$pessoa || !$pessoa->user_id) return;
        $userId = $pessoa->user_id;

        $dataPagamento = $this->data_pagamento;
        if (!$dataPagamento) return;
        
        $ano = (int) $dataPagamento->format('Y');
        $mes = (int) $dataPagamento->format('m');

        \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $ano, $mes) {
            \Illuminate\Support\Facades\DB::table('clube_mensalidades')
                ->where('user_id', $userId)
                ->where('competencia_ano', $ano)
                ->where('competencia_mes', $mes)
                ->delete();
        });

        // Recalcular indicadores (Job assíncrono)
        if (class_exists(\App\Domains\Clube\Jobs\RecalcularIndicadoresClienteJob::class)) {
            \App\Domains\Clube\Jobs\RecalcularIndicadoresClienteJob::dispatch((int) $userId)->afterCommit();
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
        return $this->belongsTo(TransacaoExtrato::class, 'transacao_extrato_id');
    }

    /**
     * Recalcula e sincroniza o status do lançamento associado.
     */
    public function sincronizarLancamentoStatus()
    {
        $lancamento = $this->lancamento ?: \App\Models\Lancamento::find($this->lancamento_id);
        if ($lancamento) {
            $pago = $lancamento->movimentacoes()->sum('valor_pago');
            if ($pago >= $lancamento->valor_total) {
                $lancamento->update(['status' => 'pago']);
            } elseif ($pago > 0) {
                $lancamento->update(['status' => 'pago_parcial']);
            } else {
                $lancamento->update(['status' => 'pendente']);
            }
        }
    }
}

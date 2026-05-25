<?php

namespace App\Observers;

use App\Models\Pedido;
use App\Models\Lancamento;
use App\Models\Pessoa;
use Illuminate\Support\Facades\Log;

class PedidoObserver
{
    /**
     * Handle the Pedido "saved" event.
     */
    public function saved(Pedido $pedido): void
    {
        // O valor_total do pedido no banco já representa o valor bruto total (itens + frete - descontos)
        // recalculado pelo trigger após inserção dos itens_pedido.
        $valorBruto = (float) $pedido->valor_total;

        // valor_saldo_utilizado:
        //   > 0 → saldo positivo abatido do pedido (reduz valor a pagar)
        //   < 0 → dívida anterior embutida no pedido (aumenta valor a pagar)
        //   = 0 → sem saldo envolvido
        $saldoUtilizado = (float) $pedido->valor_saldo_utilizado;

        // Valor líquido que entra no caixa da empresa (exclui o saldo de carteira usado)
        // Se saldo é positivo: liquidoFinanceiro = bruto - saldoUsado (menos o que foi saldo)
        // Se saldo é negativo (dívida embutida): liquidoFinanceiro = valor_total (inclui a dívida)
        $valorLiquido = max(0.00, $valorBruto - $saldoUtilizado);

        // Valor a debitar na carteira do cliente (o que ele deve ao total)
        // Quando há dívida embutida, valor_total já inclui ela; usamos valor_total como débito.
        $valorDebitoCarteira = $valorBruto; // valor_total já é o bruto dos itens após trigger

        // Só gera registros se houver valor faturado
        if ($valorDebitoCarteira <= 0) {
            return;
        }

        try {
            $user = $pedido->user;
            if (!$user) return;

            // 1. Garantir que o usuário tenha um perfil financeiro (Pessoa)
            $pessoa = $user->perfilFinanceiro;
            if (!$pessoa) {
                $pessoa = Pessoa::create([
                    'user_id'   => $user->id,
                    'nome'      => $user->name,
                    'documento' => $user->cpf ?? $user->whatsapp ?? $user->phone,
                    'tipo'      => 'cliente_circular',
                ]);
            }

            // 2. Buscar, criar ou deletar o lançamento vinculado a este pedido
            if ($valorLiquido > 0) {
                $lancamento = Lancamento::where('referencia_tipo', 'pedido')
                    ->where('referencia_id', $pedido->id)
                    ->first();

                $dadosLancamento = [
                    'tipo'                        => 'receita',
                    'status'                      => $pedido->status_pagamento === 'aprovado' ? 'pago' : 'pendente',
                    'pessoa_id'                   => $pessoa->id,
                    'classificacao_financeira_id' => 1, // Venda
                    'data_emissao'                => $pedido->data_pedido ?? $pedido->created_at,
                    'data_vencimento'             => $pedido->data_pedido ?? $pedido->created_at,
                    'valor_total'                 => $valorLiquido, // Apenas o valor financeiro líquido a receber em caixa
                    'descricao'                   => "Pedido " . $pedido->numero_pedido,
                    'referencia_tipo'             => 'pedido',
                    'referencia_id'               => $pedido->id,
                ];

                if ($lancamento) {
                    // Só atualiza se o valor ou status mudou para evitar recursão infinita se houver outros observers
                    $lancamento->update($dadosLancamento);
                } else {
                    $lancamento = Lancamento::create($dadosLancamento);
                }
            } else {
                // Se o pedido foi 100% pago com saldo da carteira (valor líquido = 0), não deve haver lançamento a receber no financeiro
                Lancamento::where('referencia_tipo', 'pedido')
                    ->where('referencia_id', $pedido->id)
                    ->delete();
            }

            // Sincronizar Débito e Crédito da Compra na Conta Corrente do Cliente (Ledger)
            if ($pessoa->user_id) {
                $valorNetDebito = max(0.00, $valorBruto - $saldoUtilizado);

                // 1. Débito da compra pelo valor LÍQUIDO do pedido (sem incluir o saldo utilizado)
                \App\Models\ContaCorrente::updateOrCreate(
                    [
                        'referencia_tipo' => 'pedido',
                        'referencia_id' => $pedido->id,
                        'tipo_movimentacao' => 'debito',
                    ],
                    [
                        'user_id' => $pessoa->user_id,
                        'valor' => $valorNetDebito,
                        'descricao' => "Compra: Pedido {$pedido->numero_pedido}",
                        'classificacao_id' => 1,
                        'data_movimentacao' => $pedido->data_pedido ?? $pedido->created_at,
                    ]
                );

                // 2. Se houver saldo utilizado (desconto ou dívida embutida), cria/atualiza o lançamento correspondente
                if ($saldoUtilizado != 0) {
                    $tipoMovSaldo = $saldoUtilizado > 0 ? 'debito' : 'credito';
                    $valorMovSaldo = abs($saldoUtilizado);
                    $descMovSaldo = $saldoUtilizado > 0 
                        ? "Desconto Carteira: Pedido {$pedido->numero_pedido}" 
                        : "Ajuste Dívida Embutida: Pedido {$pedido->numero_pedido}";

                    \App\Models\ContaCorrente::updateOrCreate(
                        [
                            'referencia_tipo' => 'desconto',
                            'referencia_id' => $pedido->id,
                        ],
                        [
                            'user_id' => $pessoa->user_id,
                            'tipo_movimentacao' => $tipoMovSaldo,
                            'valor' => $valorMovSaldo,
                            'descricao' => $descMovSaldo,
                            'classificacao_id' => 1,
                            'data_movimentacao' => $pedido->data_pedido ?? $pedido->created_at,
                        ]
                    );
                } else {
                    \App\Models\ContaCorrente::where('referencia_tipo', 'desconto')
                        ->where('referencia_id', $pedido->id)
                        ->delete();
                }

                // Limpar qualquer registro antigo de crédito de uso de saldo para este pedido na ContaCorrente
                \App\Models\ContaCorrente::where('referencia_tipo', 'pedido')
                    ->where('referencia_id', $pedido->id)
                    ->where('tipo_movimentacao', 'credito')
                    ->delete();

                // Disparar recálculo de saldo da carteira da cliente
                if (class_exists(\App\Jobs\RecalcularSaldosJob::class)) {
                    $dataParaRecalculo = $pedido->data_pedido ? \Carbon\Carbon::parse($pedido->data_pedido) : $pedido->created_at;
                    \App\Jobs\RecalcularSaldosJob::dispatch($pessoa->user_id, $dataParaRecalculo->toDateString());
                }
            }

        } catch (\Throwable $e) {
            Log::error("Erro no PedidoObserver ao processar lançamento/carteira: " . $e->getMessage());
        }
    }

    /**
     * Handle the Pedido "deleted" event.
     */
    public function deleted(Pedido $pedido): void
    {
        try {
            Lancamento::where('referencia_tipo', 'pedido')
                ->where('referencia_id', $pedido->id)
                ->delete();

            \App\Models\ContaCorrente::whereIn('referencia_tipo', ['pedido', 'desconto'])
                ->where('referencia_id', $pedido->id)
                ->delete();

            if ($pedido->user_id && class_exists(\App\Jobs\RecalcularSaldosJob::class)) {
                $dataParaRecalculo = $pedido->data_pedido ? \Carbon\Carbon::parse($pedido->data_pedido) : $pedido->created_at;
                \App\Jobs\RecalcularSaldosJob::dispatch($pedido->user_id, $dataParaRecalculo->toDateString());
            }
        } catch (\Throwable $e) {
            Log::error("Erro no PedidoObserver ao deletar registros: " . $e->getMessage());
        }
    }
}

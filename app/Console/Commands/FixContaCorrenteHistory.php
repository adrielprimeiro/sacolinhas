<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pedido;
use App\Models\ContaCorrente;
use App\Models\Lancamento;
use App\Models\Movimentacao;
use App\Jobs\RecalcularSaldosJob;
use Illuminate\Support\Facades\DB;

class FixContaCorrenteHistory extends Command
{
    protected $signature = 'financeiro:fix-carteira';
    protected $description = 'Corrige o histórico da carteira para atuar como um Extrato Ledger completo.';

    public function handle()
    {
        $this->info("Iniciando migração de dados da Carteira...");

        DB::transaction(function () {
            // 1. Deletar apenas os débitos antigos de 'pedido' e 'desconto' na ContaCorrente (preservando créditos de devoluções!)
            ContaCorrente::whereIn('referencia_tipo', ['pedido', 'desconto'])
                ->where('tipo_movimentacao', 'debito')
                ->delete();

            // 1.5 Deletar créditos indevidos gerados por Movimentações de 'saldo_carteira' (bug anterior)
            $movimentacoesCarteira = Movimentacao::where('forma_pagamento', 'saldo_carteira')->pluck('id');
            if ($movimentacoesCarteira->isNotEmpty()) {
                ContaCorrente::where('referencia_tipo', 'movimentacao')
                    ->whereIn('referencia_id', $movimentacoesCarteira)
                    ->delete();
            }

            // 1.7 Deletar lançamentos e movimentações de 'carteira_credito' (ajustes virtuais de carteira)
            $carteiraCreditoIds = Lancamento::where('referencia_tipo', 'carteira_credito')->pluck('id');
            if ($carteiraCreditoIds->isNotEmpty()) {
                Movimentacao::whereIn('lancamento_id', $carteiraCreditoIds)->delete();
                Lancamento::whereIn('id', $carteiraCreditoIds)->delete();
            }

            // Pegar todos os pedidos aprovados ou pendentes que têm valor
            $pedidos = Pedido::where('valor_total', '>', 0)->get();

            $bar = $this->output->createProgressBar(count($pedidos));

            $userIds = [];

            foreach ($pedidos as $pedido) {
                if (!$pedido->user_id) continue;
                if (str_starts_with($pedido->numero_pedido, 'REC-')) continue;
                $userIds[$pedido->user_id] = true;

                $saldoUtilizado = max(0.00, (float) $pedido->valor_saldo_utilizado);
                $valorNetDebito = max(0.00, (float) $pedido->valor_total - $saldoUtilizado);

                // 2. Garantir que o Débito Líquido do Pedido exista (apenas se for maior que 0)
                if ($valorNetDebito > 0) {
                    ContaCorrente::updateOrCreate(
                        [
                            'referencia_tipo' => 'pedido',
                            'referencia_id' => $pedido->id,
                            'tipo_movimentacao' => 'debito',
                        ],
                        [
                            'user_id' => $pedido->user_id,
                            'valor' => $valorNetDebito,
                            'descricao' => "Compra: Pedido {$pedido->numero_pedido}",
                            'classificacao_id' => 1,
                            'data_movimentacao' => $pedido->data_pedido ?? $pedido->created_at,
                        ]
                    );
                }

                // 2.5. Se houver saldo utilizado (desconto), cria/atualiza o lançamento correspondente
                if ($saldoUtilizado > 0) {
                    ContaCorrente::updateOrCreate(
                        [
                            'referencia_tipo' => 'desconto',
                            'referencia_id' => $pedido->id,
                        ],
                        [
                            'user_id' => $pedido->user_id,
                            'tipo_movimentacao' => 'debito',
                            'valor' => $saldoUtilizado,
                            'descricao' => "Desconto Carteira: Pedido {$pedido->numero_pedido}",
                            'classificacao_id' => 1,
                            'data_movimentacao' => $pedido->data_pedido ?? $pedido->created_at,
                        ]
                    );
                }

                $valorBruto = (float) $pedido->valor_total;
                
                // 3. Ajustar o valor do lançamento financeiro do pedido para o valor Bruto
                $lancamento = Lancamento::where('referencia_tipo', 'pedido')
                    ->where('referencia_id', $pedido->id)
                    ->first();

                if ($lancamento) {
                    if ($valorBruto > 0) {
                        $lancamento->update(['valor_total' => $valorBruto]);

                        if ($saldoUtilizado > 0) {
                            $contaCarteira = \App\Models\ContaBancaria::where('nome', 'like', '%carteira%')->first();
                            $contaBancariaId = $contaCarteira ? $contaCarteira->id : 3;

                            \App\Models\Movimentacao::updateOrCreate(
                                [
                                    'lancamento_id' => $lancamento->id,
                                    'forma_pagamento' => 'saldo_carteira',
                                ],
                                [
                                    'conta_bancaria_id' => $contaBancariaId,
                                    'data_pagamento' => $pedido->data_pedido ?? $pedido->created_at,
                                    'valor_pago' => $saldoUtilizado,
                                ]
                            );
                        } else {
                            \App\Models\Movimentacao::where('lancamento_id', $lancamento->id)
                                ->where('forma_pagamento', 'saldo_carteira')
                                ->delete();
                        }
                    } else {
                        $lancamento->movimentacoes()->delete();
                        $lancamento->delete();
                    }
                }

                $bar->advance();
            }

            $bar->finish();
            $this->info("\nMigração de dados concluída. Recalculando saldos...");

            // 4. Recalcular saldos para todos os usuários afetados
            $usersBar = $this->output->createProgressBar(count($userIds));
            foreach (array_keys($userIds) as $userId) {
                // Ao instanciar diretamente o Job e chamar handle(), rodamos síncrono para garantir que termina.
                $job = new RecalcularSaldosJob($userId, '2020-01-01');
                $job->handle();
                $usersBar->advance();
            }
            $usersBar->finish();

        });

        $this->info("\nRecriando Lançamentos Financeiros de Créditos/Avaliações...");
        // Pegar movimentações de ContaCorrente que não são de fluxo normal de vendas para gerar lançamentos
        $ccMovimentos = ContaCorrente::where(function ($q) {
            $q->whereNotIn('referencia_tipo', ['pedido', 'desconto', 'movimentacao', 'tolerancia'])
              ->orWhereNull('referencia_tipo');
        })->get();

        $ccBar = $this->output->createProgressBar(count($ccMovimentos));
        foreach ($ccMovimentos as $mov) {
            // Se a classificação não existir, tentamos adivinhar com base na descrição,
            // que é exatamente o que a trigger boot creating() agora faz para novas entradas.
            if (empty($mov->classificacao_id)) {
                $desc = mb_strtoupper($mov->descricao, 'UTF-8');
                if (str_contains($desc, 'AVALIA')) {
                    $mov->classificacao_id = 19;
                } elseif (str_contains($desc, 'DEVOLU')) {
                    $mov->classificacao_id = 81;
                } else {
                    $mov->classificacao_id = 19;
                }
                $mov->save(); // disparará boot -> updated -> sincronizarFinanceiro
            } else {
                $mov->sincronizarFinanceiro();
            }
            $ccBar->advance();
        }
        $ccBar->finish();

        $this->info("\nHistórico da Carteira corrigido com sucesso!");
    }
}

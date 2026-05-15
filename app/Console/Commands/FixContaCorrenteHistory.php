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
            // 1. Deletar todos os lançamentos manuais antigos de 'pedido' na ContaCorrente
            ContaCorrente::where('referencia_tipo', 'pedido')->delete();

            // 1.5 Deletar créditos indevidos gerados por Movimentações de 'saldo_carteira' (bug anterior)
            $movimentacoesCarteira = Movimentacao::where('forma_pagamento', 'saldo_carteira')->pluck('id');
            if ($movimentacoesCarteira->isNotEmpty()) {
                ContaCorrente::where('referencia_tipo', 'movimentacao')
                    ->whereIn('referencia_id', $movimentacoesCarteira)
                    ->delete();
            }

            // Pegar todos os pedidos aprovados ou pendentes que têm valor
            $pedidos = Pedido::where('valor_total', '>', 0)->get();

            $bar = $this->output->createProgressBar(count($pedidos));

            $userIds = [];

            foreach ($pedidos as $pedido) {
                if (!$pedido->user_id) continue;
                $userIds[$pedido->user_id] = true;

                // 2. Garantir que o Débito Integral do Pedido exista
                ContaCorrente::updateOrCreate(
                    [
                        'referencia_tipo' => 'pedido',
                        'referencia_id' => $pedido->id,
                    ],
                    [
                        'user_id' => $pedido->user_id,
                        'tipo_movimentacao' => 'debito',
                        'valor' => $pedido->valor_total,
                        'descricao' => "Compra: Pedido {$pedido->numero_pedido}",
                        'classificacao_id' => 1,
                        'data_movimentacao' => $pedido->data_pedido ?? $pedido->created_at,
                    ]
                );

                // 3. Se o pedido usou saldo da carteira, converter isso em uma Movimentacao (se já não existir)
                if ($pedido->valor_saldo_utilizado > 0) {
                    $lancamento = Lancamento::where('referencia_tipo', 'pedido')
                        ->where('referencia_id', $pedido->id)
                        ->first();

                    if ($lancamento) {
                        $movExiste = Movimentacao::where('lancamento_id', $lancamento->id)
                            ->where('forma_pagamento', 'saldo_carteira')
                            ->exists();

                        if (!$movExiste) {
                            $movimentacao = Movimentacao::create([
                                'lancamento_id' => $lancamento->id,
                                'conta_bancaria_id' => 1,
                                'data_pagamento' => $pedido->data_pedido ?? $pedido->created_at,
                                'valor_pago' => $pedido->valor_saldo_utilizado,
                                'forma_pagamento' => 'saldo_carteira',
                            ]);
                            // A criação dessa movimentacao já vai gerar o 'credito' correspondente na ContaCorrente automaticamente!

                            // Atualizar status do lancamento
                            $valorPagoTotal = $lancamento->movimentacoes()->sum('valor_pago');
                            if ($valorPagoTotal >= $lancamento->valor_total) {
                                $lancamento->update(['status' => 'pago']);
                            }
                        }
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

        $this->info("\nHistórico da Carteira corrigido com sucesso!");
    }
}

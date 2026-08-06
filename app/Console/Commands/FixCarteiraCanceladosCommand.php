<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pedido;
use App\Models\ContaCorrente;
use App\Models\Lancamento;
use App\Jobs\RecalcularSaldosJob;

class FixCarteiraCanceladosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carteira:fix-cancelados';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove débitos fantasmas de pedidos cancelados na Conta Corrente dos clientes e recalcula saldos.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Buscando pedidos cancelados que ainda possuem lançamentos ou débitos na carteira...");

        // Buscar pedidos cancelados
        $pedidosCancelados = Pedido::where('status_pedido', 'cancelado')->get();
        $this->info("Encontrados {$pedidosCancelados->count()} pedidos cancelados no total.");

        $usersToRecalculate = [];
        $fixedCount = 0;

        foreach ($pedidosCancelados as $pedido) {
            $hasGhosts = false;

            // 1. Verificar se existe lançamento
            $lancamento = Lancamento::where('referencia_tipo', 'pedido')
                ->where('referencia_id', $pedido->id)
                ->first();

            if ($lancamento) {
                $hasGhosts = true;
                $lancamento->movimentacoes()->delete();
                $lancamento->delete();
            }

            // 2. Verificar se existe registro em Conta Corrente
            $deletedRows = ContaCorrente::whereIn('referencia_tipo', ['pedido', 'desconto', 'tolerancia'])
                ->where('referencia_id', $pedido->id)
                ->delete();

            if ($deletedRows > 0) {
                $hasGhosts = true;
            }

            // 3. Limpar crédito de uso de saldo (como no Observer)
            $deletedCreditos = ContaCorrente::where('referencia_tipo', 'pedido')
                ->where('referencia_id', $pedido->id)
                ->where('tipo_movimentacao', 'credito')
                ->where('classificacao_id', '!=', 81)
                ->where('descricao', 'not like', '%devolu%')
                ->delete();

            if ($deletedCreditos > 0) {
                $hasGhosts = true;
            }

            if ($hasGhosts) {
                $fixedCount++;
                if ($pedido->user_id) {
                    if (!isset($usersToRecalculate[$pedido->user_id])) {
                        $usersToRecalculate[$pedido->user_id] = $pedido->data_pedido ?? $pedido->created_at;
                    } else {
                        // Pegar a data mais antiga para o recálculo
                        $existingDate = \Carbon\Carbon::parse($usersToRecalculate[$pedido->user_id]);
                        $newDate = \Carbon\Carbon::parse($pedido->data_pedido ?? $pedido->created_at);
                        if ($newDate->lt($existingDate)) {
                            $usersToRecalculate[$pedido->user_id] = $newDate;
                        }
                    }
                }
            }
        }

        $this->info("Limpeza concluída! {$fixedCount} pedidos cancelados tinham registros fantasmas removidos.");

        if (count($usersToRecalculate) > 0) {
            $this->info("Recalculando saldo para " . count($usersToRecalculate) . " clientes...");
            
            foreach ($usersToRecalculate as $userId => $dataInicial) {
                $dataParaRecalculo = \Carbon\Carbon::parse($dataInicial)->toDateString();
                // Executar síncrono para garantir o log instantâneo
                RecalcularSaldosJob::dispatchSync($userId, $dataParaRecalculo);
            }
            
            $this->info("Recálculos finalizados com sucesso.");
        }

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Jobs\RecalcularSaldosJob;

class RestoreDevolucoes extends Command
{
    protected $signature = 'financeiro:restore-devolucoes {--dry-run : Apenas simular a restauração}';
    protected $description = 'Restaura créditos de devolução apagados na tabela conta_corrente com base nos itens de pedidos devolvidos';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn("[MODO SIMULAÇÃO] Nenhuma alteração será feita no banco de dados.");
        }

        $this->info("Buscando itens devolvidos na tabela items_pedido...");

        $devolvidos = DB::table('items_pedido')
            ->join('pedidos', 'items_pedido.pedido_id', '=', 'pedidos.id')
            ->where('items_pedido.status_item', 'devolvido')
            ->select([
                'items_pedido.id as item_id',
                'items_pedido.pedido_id',
                'items_pedido.valor_total',
                'items_pedido.updated_at',
                'pedidos.user_id',
                'pedidos.numero_pedido',
                'pedidos.live_id'
            ])
            ->get();

        if ($devolvidos->isEmpty()) {
            $this->info("Nenhum item com status 'devolvido' encontrado.");
            return;
        }

        $this->info("Total de itens devolvidos encontrados: " . $devolvidos->count());

        // Agrupar por pedido_id e data de atualização exata
        $groups = $devolvidos->groupBy(function ($item) {
            return $item->pedido_id . '_' . $item->updated_at;
        });

        $this->info("Agrupados em " . $groups->count() . " lotes de devolução distintos.");

        $restoredCount = 0;
        $skippedCount = 0;
        $usersToRecalculate = [];

        foreach ($groups as $key => $items) {
            $first = $items->first();
            $pedidoId = $first->pedido_id;
            $userId = $first->user_id;
            $numeroPedido = $first->numero_pedido;
            $liveId = $first->live_id;
            $updatedAt = $first->updated_at;

            $valorTotalDevolvido = $items->sum(function ($i) {
                return (float) ($i->valor_total ?? 0);
            });

            $idsValidos = $items->pluck('item_id')->toArray();

            // Verificar se já existe lançamento de crédito para este pedido por volta desse horário
            $exists = DB::table('conta_corrente')
                ->where('user_id', $userId)
                ->where('tipo_movimentacao', 'credito')
                ->where('referencia_tipo', 'pedido')
                ->where('referencia_id', $pedidoId)
                ->whereBetween('data_movimentacao', [
                    Carbon::parse($updatedAt)->subSeconds(10),
                    Carbon::parse($updatedAt)->addSeconds(10)
                ])
                ->exists();

            if ($exists) {
                $skippedCount++;
                continue;
            }

            $restoredCount++;
            $usersToRecalculate[$userId] = true;

            $descricao = 'Crédito por devolução de itens do pedido ' . ($numeroPedido ?? $pedidoId);
            $observacoes = 'Itens devolvidos: ' . implode(',', $idsValidos);

            $this->line("Restaurando lote: Pedido ID: {$pedidoId} | Cliente ID: {$userId} | Valor: R$ " . number_format($valorTotalDevolvido, 2) . " | Data: {$updatedAt}");

            if (!$dryRun) {
                DB::table('conta_corrente')->insert([
                    'user_id' => $userId,
                    'tipo_movimentacao' => 'credito',
                    'valor' => $valorTotalDevolvido,
                    'descricao' => $descricao,
                    'referencia_tipo' => 'pedido',
                    'referencia_id' => $pedidoId,
                    'live_id' => $liveId,
                    'saldo_anterior' => 0.00,
                    'saldo_atual' => 0.00,
                    'data_movimentacao' => $updatedAt,
                    'observacoes' => $observacoes,
                    'classificacao_id' => 81,
                    'created_at' => $updatedAt,
                    'updated_at' => $updatedAt,
                ]);
            }
        }

        $this->info("\n--- Resumo ---");
        $this->info("Lotes de devolução ignorados (já existem no banco): {$skippedCount}");
        $this->info("Lotes de devolução restaurados: {$restoredCount}");

        if ($restoredCount > 0 && !$dryRun) {
            $this->info("\nRecalculando os saldos para os clientes afetados...");
            $bar = $this->output->createProgressBar(count($usersToRecalculate));
            foreach (array_keys($usersToRecalculate) as $userId) {
                $job = new RecalcularSaldosJob($userId, '2020-01-01');
                $job->handle();
                $bar->advance();
            }
            $bar->finish();
            $this->info("\nSaldos recalculados com sucesso!");
        }

        $this->info("Operação concluída!");
    }
}

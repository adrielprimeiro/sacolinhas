<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pedido;
use Illuminate\Support\Facades\Log;

class SincronizarMelhorEnvio extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'me:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza automaticamente as informações de rastreio dos pedidos ativos com o Melhor Envio';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Iniciando a sincronização automática de rastreamento do Melhor Envio...");
        Log::info("Cron/Artisan: Iniciando me:sync para pedidos ativos");

        try {
            // Busca pedidos que não estão finalizados (entregues/concluídos/cancelados)
            $pedidos = Pedido::whereNotIn('status_pedido', [
                    'entregue', 'concluido', 'cancelado',
                    'Entregue', 'Concluido', 'Cancelado',
                    'ENTREGUE', 'CONCLUIDO', 'CANCELADO'
                ])
                ->where(function ($query) {
                    $query->whereNotNull('melhor_envio_id')
                        ->orWhereNotNull('codigo_rastreamento')
                        ->orWhere('observacoes', 'LIKE', '%melhorenvio.com.br%');
                })
                ->get();

            $count = 0;
            $successCount = 0;

            foreach ($pedidos as $pedido) {
                $count++;
                // Passamos force = true para garantir que ele atualize de fato da API
                if ($pedido->checkAndSyncTracking(true)) {
                    $successCount++;
                }
            }

            $this->info("Sincronização concluída! Processados: {$count}, Atualizados com sucesso: {$successCount}.");
            Log::info("Cron/Artisan: me:sync concluído. Processados: {$count}, Atualizados: {$successCount}.");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Erro na sincronização: " . $e->getMessage());
            Log::error("Cron/Artisan: Erro em me:sync: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

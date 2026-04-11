<?php
namespace App\Domains\Clube\Console\Commands;

use Illuminate\Console\Command;
use App\Domains\Clube\Services\ClubeIndicadoresService;

class RecalcularIndicadoresClubeCommand extends Command
{
    protected $signature = 'clube:recalcular-indicadores {--user-id= : Recalcular apenas um usuário}';
    protected $description = 'Recalcula indicadores do clube para todos os clientes';

    public function handle(ClubeIndicadoresService $service)
    {
        $userId = $this->option('user-id');
        
        if ($userId) {
            $this->info("Recalculando indicadores para usuário {$userId}...");
            $service->recalcularParaUsuario($userId);
            $this->info("✅ Concluído.");
        } else {
            $this->info("Recalculando indicadores para todos os clientes...");
            $service->recalcularParaTodos();
            $this->info("✅ Concluído.");
        }
    }
}
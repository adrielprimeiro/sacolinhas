<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ConciliacaoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SincronizarBancoInter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inter:sync {--days=7 : Numero de dias retroativos para sincronizar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza transacoes de extrato (entradas e saidas) do Banco Inter automaticamente';

    /**
     * @var ConciliacaoService
     */
    protected $service;

    /**
     * Create a new command instance.
     *
     * @param ConciliacaoService $service
     */
    public function __construct(ConciliacaoService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        
        $startDate = Carbon::now()->subDays($days)->toDateString();
        $endDate = Carbon::now()->toDateString();

        $this->info("Iniciando sincronizacao do Banco Inter de {$startDate} ate {$endDate} (ultimos {$days} dias)...");
        Log::info("Cron/Artisan: Iniciando inter:sync de {$startDate} ate {$endDate}");

        try {
            $count = $this->service->sincronizarBancoInter($startDate, $endDate);
            $this->info("Sincronizacao do Banco Inter concluida! {$count} transacoes processadas.");
            Log::info("Cron/Artisan: inter:sync concluido com sucesso. {$count} transacoes processadas.");

            // Roda auto-conciliação automática após puxar o extrato
            $autoCount = $this->service->autoConciliarTransacoesPendentes();
            if ($autoCount > 0) {
                $this->info("Auto-conciliacao concluida! {$autoCount} transacoes conciliadas automaticamente.");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Erro ao sincronizar Banco Inter: " . $e->getMessage());
            Log::error("Cron/Artisan: Erro em inter:sync: " . $e->getMessage());
            return 1;
        }
    }
}

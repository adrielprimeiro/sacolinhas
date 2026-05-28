<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ConciliacaoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SincronizarMercadoPago extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mp:sync {--days=7 : Numero de dias retroativos para sincronizar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza transacoes e relatorios de extrato (entradas e saidas) do Mercado Pago automaticamente';

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

        $this->info("Iniciando sincronizacao do Mercado Pago de {$startDate} ate {$endDate} (ultimos {$days} dias)...");
        Log::info("Cron/Artisan: Iniciando mp:sync de {$startDate} ate {$endDate}");

        try {
            $count = $this->service->sincronizarMercadoPago($startDate, $endDate);
            $this->info("Sincronizacao concluida com sucesso! {$count} transacoes processadas.");
            Log::info("Cron/Artisan: mp:sync concluido com sucesso. {$count} transacoes processadas.");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Erro na sincronizacao: " . $e->getMessage());
            Log::error("Cron/Artisan: Erro em mp:sync: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

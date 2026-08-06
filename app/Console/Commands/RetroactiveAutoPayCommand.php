<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pedido;
use App\Services\WalletAutoPayService;
use Illuminate\Support\Facades\Log;

class RetroactiveAutoPayCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financeiro:autopay-retroativo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executa o auto-pagamento retroativo para todos os usuários com pedidos pendentes e saldo na carteira';

    /**
     * Execute the console command.
     */
    public function handle(WalletAutoPayService $autoPayService)
    {
        $this->info('Iniciando processamento retroativo de baixas automáticas via carteira...');

        // Busca todos os IDs de usuários que possuem pedidos pendentes ou parciais
        $userIds = Pedido::whereIn('status_pagamento', ['pendente', 'parcial'])
                         ->distinct()
                         ->pluck('user_id');

        if ($userIds->isEmpty()) {
            $this->info('Nenhum pedido pendente encontrado.');
            return 0;
        }

        $this->info("Encontrados {$userIds->count()} usuários com pedidos pendentes. Processando...");

        $totalAbatidos = 0;
        $bar = $this->output->createProgressBar($userIds->count());
        $bar->start();

        foreach ($userIds as $userId) {
            try {
                $abatidos = $autoPayService->process($userId);
                $totalAbatidos += $abatidos;
            } catch (\Exception $e) {
                Log::error("Erro no processamento retroativo do usuário {$userId}: " . $e->getMessage());
                $this->error("\nErro no processamento retroativo do usuário {$userId}. Verifique os logs.");
            }

            $bar->advance();
        }

        $bar->finish();
        
        $this->newLine(2);
        $this->info("Processamento concluído com sucesso!");
        $this->info("Total de pedidos que sofreram baixa (total ou parcial): {$totalAbatidos}");

        return 0;
    }
}

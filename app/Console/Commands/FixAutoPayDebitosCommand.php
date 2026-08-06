<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ContaCorrente;

class FixAutoPayDebitosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:autopay-debitos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove débitos incorretos gerados pelo WalletAutoPayService e recalcula os saldos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Buscando débitos incorretos gerados pelo AutoPay...');

        $debitosRemover = ContaCorrente::where('descricao', 'like', 'Pagamento Automático do Pedido%')
                                       ->where('tipo_movimentacao', 'debito')
                                       ->get();

        if ($debitosRemover->isEmpty()) {
            $this->info('Nenhum débito incorreto encontrado.');
            return 0;
        }

        $this->info("Encontrados {$debitosRemover->count()} débitos incorretos. Removendo...");

        $userIdsAfetados = [];

        foreach ($debitosRemover as $debito) {
            $this->line("Removendo debito ID {$debito->id} de R$ {$debito->valor} para User {$debito->user_id}");
            $userIdsAfetados[] = $debito->user_id;
            $debito->delete();
        }

        $userIdsAfetados = array_unique($userIdsAfetados);

        $this->info('Recalculando saldos dos usuários afetados...');
        foreach ($userIdsAfetados as $userId) {
            if (class_exists(\App\Jobs\RecalcularSaldosJob::class)) {
                \App\Jobs\RecalcularSaldosJob::dispatch($userId, '2020-01-01');
                $this->line("Job de recálculo despachado para User {$userId}");
            }
        }

        $this->newLine();
        $this->info('Correção concluída com sucesso!');
        return 0;
    }
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$debitosRemover = App\Models\ContaCorrente::where('descricao', 'like', 'Pagamento Automático do Pedido%')
                                          ->where('tipo_movimentacao', 'debito')
                                          ->get();

echo "Encontrados " . $debitosRemover->count() . " débitos incorretos do AutoPay.\n";

foreach ($debitosRemover as $debito) {
    echo "Removendo debito ID {$debito->id} de {$debito->valor} para User {$debito->user_id}\n";
    $debito->delete();
    
    // Disparar recálculo para arrumar o saldo_atual do usuário
    if (class_exists(\App\Jobs\RecalcularSaldosJob::class)) {
        \App\Jobs\RecalcularSaldosJob::dispatch($debito->user_id, '2020-01-01');
    }
}

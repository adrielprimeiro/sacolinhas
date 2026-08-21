<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;

echo "=== Testando Execução da Auto-Conciliação por Regra Padrão Única ===\n\n";

$service = app(\App\Services\ConciliacaoService::class);
$count = $service->autoConciliarTransacoesPendentes();

echo "Transações auto-conciliadas nesta execução: {$count}\n";

$pendentes = TransacaoExtrato::where('status', 'pendente')->get();
echo "Transações ainda pendentes no extrato: " . $pendentes->count() . "\n";
foreach ($pendentes as $tp) {
    echo "   ID: {$tp->id} | Data: {$tp->data->format('Y-m-d')} | R$ {$tp->valor} | Desc: '{$tp->descricao}'\n";
}

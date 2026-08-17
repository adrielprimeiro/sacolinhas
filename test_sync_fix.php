<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ConciliacaoService;
use App\Models\TransacaoExtrato;

echo "=== Testando sincronização do Mercado Pago com a nova lógica ===\n\n";

$service = app(ConciliacaoService::class);
$importedCount = $service->sincronizarMercadoPago(now()->subDays(30)->toDateString(), now()->toDateString());

echo "Importadas/Processadas {$importedCount} transações!\n\n";

echo "=== Transações do Mercado Pago nos últimos 3 dias ===\n";
$latest = TransacaoExtrato::where('origem', 'mercadopago')
    ->where('data', '>=', now()->subDays(3)->toDateString())
    ->orderByDesc('data')
    ->orderByDesc('id')
    ->get();

foreach ($latest as $t) {
    echo "ID: {$t->id} | Data: {$t->data->format('Y-m-d')} | FITID: {$t->fitid} | Tipo: {$t->tipo} | Valor: R$ {$t->valor} | Status: {$t->status} | Desc: {$t->descricao}\n";
}

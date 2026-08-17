<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;

echo "=== Searching ALL transacoes_extrato for any iFood / Food / Rest / Delivery in any account ===\n\n";

$results = TransacaoExtrato::where('descricao', 'like', '%ifood%')
    ->orWhere('descricao', 'like', '%food%')
    ->orWhere('descricao', 'like', '%restaurante%')
    ->orWhere('descricao', 'like', '%delivery%')
    ->orWhere('payload_original', 'like', '%ifood%')
    ->orderByDesc('data')
    ->get();

echo "Encontradas " . $results->count() . " transações:\n";
foreach ($results as $t) {
    echo "ID: {$t->id} | Data: {$t->data->format('Y-m-d')} | Origem: {$t->origem} | Tipo: {$t->tipo} | Valor: R$ {$t->valor} | Status: {$t->status} | Desc: {$t->descricao}\n";
}

echo "\n=== Procurando transacoes de hoje (2026-08-17) ou ontem (2026-08-16) em TODAS AS CONTAS ===\n";
$recentAll = TransacaoExtrato::where('data', '>=', '2026-08-16')
    ->orderByDesc('data')
    ->orderByDesc('id')
    ->get();

echo "Transações recentes (16/08 e 17/08): " . $recentAll->count() . "\n";
foreach ($recentAll as $t) {
    echo "ID: {$t->id} | Data: {$t->data->format('Y-m-d')} | Origem: {$t->origem} | Tipo: {$t->tipo} | Valor: R$ {$t->valor} | Status: {$t->status} | Desc: {$t->descricao}\n";
}

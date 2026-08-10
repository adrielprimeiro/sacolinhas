<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Verificar se a compra do Mercado Livre de 08/08 apareceu
echo "=== Transacoes de 08/08 ===\n";
$transacoes = App\Models\TransacaoExtrato::where('data', '2026-08-08')
    ->orderByDesc('id')
    ->get();

foreach ($transacoes as $t) {
    echo "ID {$t->id} | {$t->tipo} | R$ {$t->valor} | {$t->descricao} | origem: {$t->origem} | status: {$t->status}\n";
}

echo "\n=== Transacoes de saida recentes (ultimos 7 dias) ===\n";
$saidas = App\Models\TransacaoExtrato::where('tipo', 'saida')
    ->where('data', '>=', '2026-08-03')
    ->orderByDesc('data')
    ->get();

foreach ($saidas as $t) {
    echo "ID {$t->id} | {$t->data} | R$ {$t->valor} | {$t->descricao} | {$t->origem} | {$t->status}\n";
}

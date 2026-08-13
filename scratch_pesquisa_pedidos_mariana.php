<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Pedido;

$user = User::where('name', 'LIKE', '%Mariana Holman%')->first();
if (!$user) { echo "Cliente não encontrada.\n"; exit; }

echo "Cliente: " . $user->name . " (ID: " . $user->id . ")\n\n";

$pedidos = Pedido::where('user_id', $user->id)
    ->where('data_pedido', '>', '2026-06-19')
    ->orderBy('data_pedido')
    ->get();

echo "Total de Pedidos da Cliente após 19/06/2026: " . $pedidos->count() . "\n";
foreach ($pedidos as $p) {
    echo "  ID: {$p->id} | Data: {$p->data_pedido} | Status Pgt: {$p->status_pagamento} | Total: R$ {$p->valor_total}\n";
}

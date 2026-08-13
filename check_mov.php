<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pedido = App\Models\Pedido::with('lancamento.movimentacoes')->find(756);
if ($pedido && $pedido->lancamento) {
    echo "Lancamento ID: {$pedido->lancamento->id}\n";
    echo "Movimentacoes Count: {$pedido->lancamento->movimentacoes->count()}\n";
    foreach ($pedido->lancamento->movimentacoes as $mov) {
        echo "Mov: {$mov->id}, Valor: {$mov->valor_pago}, Conta: {$mov->conta_bancaria_id}, Forma: {$mov->forma_pagamento}\n";
    }
}

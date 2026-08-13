<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Encontrar o usuário e o pedido
$userId = 1254;
$pedido = App\Models\Pedido::where('user_id', $userId)->where('id', 756)->first();
if (!$pedido) die("Pedido not found\n");
echo "Pedido {$pedido->id} status: {$pedido->status_pagamento}\n";

$lancamento = App\Models\Lancamento::where('referencia_tipo', 'pedido')->where('referencia_id', $pedido->id)->first();
if (!$lancamento) die("Lancamento not found\n");
echo "Lancamento {$lancamento->id} status: {$lancamento->status}\n";

echo "Movimentacoes: {$lancamento->movimentacoes()->count()}\n";

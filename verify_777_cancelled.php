<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Support\Facades\DB;

$pedido = Pedido::find(777);
echo "=== Pedido 777 ===\n";
echo "Numero: {$pedido->numero_pedido}\n";
echo "Status Pedido: {$pedido->status_pedido}\n";
echo "Status Pagamento: {$pedido->status_pagamento}\n";
echo "User: ID {$pedido->user_id} ({$pedido->cliente?->nome})\n";

$sacolinhas = DB::table('sacolinhas')
    ->where('user_id', $pedido->user_id)
    ->where('status', 'sacolinha')
    ->get();

echo "\nItens na Sacolinha Ativa do Cliente (" . $sacolinhas->count() . " itens):\n";
foreach ($sacolinhas as $s) {
    echo "  Sacolinha #{$s->id} | Item #{$s->item_id} | Preco: R$ {$s->price} | Status: {$s->status}\n";
}

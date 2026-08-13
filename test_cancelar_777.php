<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pedido;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

$hours = 24;
$limitDate = Carbon::now()->subHours($hours);

echo "Limit Date (now - 24h): {$limitDate->toDateTimeString()}\n";

$pedido = Pedido::find(777);

echo "Pedido 777 data_pedido: {$pedido->data_pedido}\n";
echo "Pedido 777 created_at: {$pedido->created_at}\n";
echo "Pedido 777 origem_pedido: '{$pedido->origem_pedido}'\n";
echo "Pedido 777 status_pagamento: '{$pedido->status_pagamento}'\n";
echo "Pedido 777 status_pedido: '{$pedido->status_pedido}'\n";

$isOrigemOk = in_array($pedido->origem_pedido, ['portal', 'site']);
$isStatusPagOk = $pedido->status_pagamento !== 'aprovado';
$isStatusPedOk = !in_array($pedido->status_pedido, ['cancelado', 'pago', 'entregue', 'concluido']);
$isDateOk = ($pedido->data_pedido && $pedido->data_pedido <= $limitDate) || (!$pedido->data_pedido && $pedido->created_at <= $limitDate);

echo "Origem OK? " . ($isOrigemOk ? 'Sim' : 'Nao') . "\n";
echo "Status Pagamento OK? " . ($isStatusPagOk ? 'Sim' : 'Nao') . "\n";
echo "Status Pedido OK? " . ($isStatusPedOk ? 'Sim' : 'Nao') . "\n";
echo "Date OK? " . ($isDateOk ? 'Sim' : 'Nao') . "\n";

// Checar itens em items_pedido vs itens
$itemsPedido = DB::table('items_pedido')->where('pedido_id', 777)->get();
echo "\nItems Pedido no DB (tabela items_pedido) (" . $itemsPedido->count() . "):\n";
foreach ($itemsPedido as $ip) {
    echo "  Item ID: {$ip->item_id} | Preco: R$ {$ip->preco_unitario} | Qtd: {$ip->quantidade}\n";
}

$items = DB::table('items')->where('pedido_id', 777)->get();
echo "\nItems na tabela items com pedido_id = 777 (" . $items->count() . "):\n";
foreach ($items as $it) {
    echo "  Item ID: {$it->id} | Status: {$it->status} | Titulo: {$it->title}\n";
}

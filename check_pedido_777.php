<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pedido;
use Illuminate\Support\Facades\DB;

$pedido = Pedido::find(777) ?: Pedido::where('numero_pedido', 'like', '%777%')->first();

if (!$pedido) {
    echo "Pedido 777 nao encontrado.\n";
    exit;
}

echo "=== Pedido #{$pedido->id} ===\n";
echo "Numero: {$pedido->numero_pedido}\n";
echo "Cliente ID: {$pedido->cliente_id} | User ID: {$pedido->user_id}\n";
echo "Status Pedido: {$pedido->status_pedido}\n";
echo "Status Pagamento: {$pedido->status_pagamento}\n";
echo "Origem Pedido: " . ($pedido->origem_pedido ?? $pedido->origem ?? 'N/A') . "\n";
echo "Forma Pagamento: {$pedido->forma_pagamento}\n";
echo "Total: R$ {$pedido->total}\n";
echo "Created At: {$pedido->created_at}\n";
echo "Updated At: {$pedido->updated_at}\n";

$attributes = $pedido->getAttributes();
echo "\n=== Todos os Atributos do Pedido 777 ===\n";
print_r($attributes);

echo "\n=== Itens do Pedido 777 (" . $pedido->itens->count() . ") ===\n";
foreach ($pedido->itens as $item) {
    echo "Item #{$item->id} | Produto: {$item->produto_id} | Subtotal: R$ {$item->subtotal}\n";
}

echo "\n=== Lancamentos vinculados ===\n";
$lancamentos = DB::table('lancamentos')->where('descricao', 'like', '%777%')->orWhere('referencia_id', 777)->get();
foreach ($lancamentos as $l) {
    echo "Lanc #{$l->id} | Tipo: {$l->tipo} | Status: {$l->status} | RefTipo: {$l->referencia_tipo} | Valor: R$ {$l->valor_total}\n";
}

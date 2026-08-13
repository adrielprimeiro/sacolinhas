<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pedido;
use Illuminate\Support\Facades\DB;

$pedido = Pedido::find(478) ?: Pedido::where('numero_pedido', 'like', '%478%')->first();

if (!$pedido) {
    echo "Pedido 478 nao encontrado.\n";
    exit;
}

echo "=== Pedido #{$pedido->id} ===\n";
echo "Numero: {$pedido->numero_pedido}\n";
echo "Cliente ID: {$pedido->cliente_id} | User ID: {$pedido->user_id} ({$pedido->cliente?->nome})\n";
echo "Status Pedido: {$pedido->status_pedido}\n";
echo "Status Pagamento: {$pedido->status_pagamento}\n";
echo "Origem Pedido: " . ($pedido->origem_pedido ?? 'N/A') . "\n";
echo "Forma Pagamento: {$pedido->forma_pagamento}\n";
echo "Valor Total: R$ {$pedido->valor_total}\n";
echo "Created At: {$pedido->created_at}\n";
echo "Updated At: {$pedido->updated_at}\n";

echo "\n=== Conta Corrente vinculada (RefTipo: pedido, RefID: {$pedido->id}) ===\n";
$cc = DB::table('conta_corrente')->where('referencia_tipo', 'pedido')->where('referencia_id', $pedido->id)->get();
foreach ($cc as $c) {
    echo "CC #{$c->id} | User: {$c->user_id} | Tipo: {$c->tipo_movimentacao} | Valor: R$ {$c->valor} | Desc: {$c->descricao}\n";
}

echo "\n=== Lancamentos vinculados (RefTipo: pedido, RefID: {$pedido->id}) ===\n";
$lancamentos = DB::table('lancamentos')->where('referencia_tipo', 'pedido')->where('referencia_id', $pedido->id)->get();
foreach ($lancamentos as $l) {
    echo "Lanc #{$l->id} | Tipo: {$l->tipo} | Status: {$l->status} | Valor: R$ {$l->valor_total} | Desc: {$l->descricao}\n";
}

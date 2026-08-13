<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pedido;
use Illuminate\Support\Facades\DB;

$pedidoIds = [577, 548, 611, 545, 525, 492];

echo "=== Lote de Pedidos a Atualizar: " . implode(', ', $pedidoIds) . " ===\n\n";

$ccBeforeCount = DB::table('conta_corrente')->count();
$movBeforeCount = DB::table('movimentacoes')->count();

foreach ($pedidoIds as $id) {
    $pedido = Pedido::find($id);
    if (!$pedido) {
        echo "ALERTA: Pedido #{$id} nao encontrado no banco.\n";
        continue;
    }

    echo "Antes: Pedido #{$pedido->id} ({$pedido->numero_pedido}) | Status Pedido: {$pedido->status_pedido} | Status Pagamento: {$pedido->status_pagamento}\n";

    DB::table('pedidos')
        ->where('id', $id)
        ->update([
            'status_pagamento' => 'aprovado',
            'updated_at' => now(),
        ]);

    $pAfter = Pedido::find($id);
    echo "Depois: Pedido #{$pAfter->id} ({$pAfter->numero_pedido}) | Status Pedido: {$pAfter->status_pedido} | Status Pagamento: {$pAfter->status_pagamento}\n\n";
}

$ccAfterCount = DB::table('conta_corrente')->count();
$movAfterCount = DB::table('movimentacoes')->count();

echo "=== Verificacao de Integridade Global ===\n";
echo "Conta Corrente Total Registros: Antes = {$ccBeforeCount} | Depois = {$ccAfterCount}\n";
echo "Movimentacoes Total Registros: Antes = {$movBeforeCount} | Depois = {$movAfterCount}\n";

if ($ccBeforeCount === $ccAfterCount && $movBeforeCount === $movAfterCount) {
    echo "SUCESSO: Todos os pedidos tiveram status_pagamento alterados para 'aprovado' sem NENHUMA alteracao em carteira ou financeiro!\n";
} else {
    echo "ALERTA: Houve alteracao nos registros de carteira ou financeiro!\n";
}

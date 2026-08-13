<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pedido;
use Illuminate\Support\Facades\DB;

$pedido = Pedido::find(490) ?: Pedido::where('numero_pedido', 'like', '%490%')->first();

if (!$pedido) {
    echo "Pedido 490 nao encontrado.\n";
    exit;
}

echo "=== Antes da Atualizacao ===\n";
echo "Pedido #{$pedido->id} ({$pedido->numero_pedido}) | Status Pedido: {$pedido->status_pedido} | Status Pagamento: {$pedido->status_pagamento}\n";

$ccBeforeCount = DB::table('conta_corrente')->where('user_id', $pedido->user_id)->count();
$movBeforeCount = DB::table('movimentacoes')->count();

echo "Atualizando status_pagamento do Pedido #{$pedido->id} para 'aprovado' (direto via DB query para nao alterar carteira ou financeiro)...\n";

DB::table('pedidos')
    ->where('id', $pedido->id)
    ->update([
        'status_pagamento' => 'aprovado',
        'updated_at' => now(),
    ]);

echo "=== Apos a Atualizacao ===\n";
$pAfter = Pedido::find($pedido->id);
echo "Pedido #{$pAfter->id} ({$pAfter->numero_pedido}) | Status Pedido: {$pAfter->status_pedido} | Status Pagamento: {$pAfter->status_pagamento}\n";

$ccAfterCount = DB::table('conta_corrente')->where('user_id', $pAfter->user_id)->count();
$movAfterCount = DB::table('movimentacoes')->count();

echo "Conta Corrente Registros: Antes = {$ccBeforeCount} | Depois = {$ccAfterCount}\n";
echo "Movimentacoes Registros: Antes = {$movBeforeCount} | Depois = {$movAfterCount}\n";

if ($ccBeforeCount === $ccAfterCount && $movBeforeCount === $movAfterCount && $pAfter->status_pagamento === 'aprovado') {
    echo "SUCESSO: Status alterado para 'aprovado' sem NENHUMA alteracao em carteira ou financeiro!\n";
} else {
    echo "ALERTA: Houve alguma alteracao nos registros!\n";
}

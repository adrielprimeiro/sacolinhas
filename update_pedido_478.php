<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pedido;
use Illuminate\Support\Facades\DB;

echo "=== Antes da Atualizacao ===\n";
$pBefore = Pedido::find(478);
echo "Pedido #{$pBefore->id} ({$pBefore->numero_pedido}) | Status Pedido: {$pBefore->status_pedido} | Status Pagamento: {$pBefore->status_pagamento}\n";

$ccBeforeCount = DB::table('conta_corrente')->where('user_id', $pBefore->user_id)->count();
$movBeforeCount = DB::table('movimentacoes')->count();

echo "Atualizando status_pagamento do Pedido #478 para 'aprovado' (direto via DB query para nao alterar carteira ou financeiro)...\n";

DB::table('pedidos')
    ->where('id', 478)
    ->update([
        'status_pagamento' => 'aprovado',
        'updated_at' => now(),
    ]);

echo "=== Apos a Atualizacao ===\n";
$pAfter = Pedido::find(478);
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

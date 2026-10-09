<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$itemIds = range(74315, 74378);

$soldInOtherLives = \Illuminate\Support\Facades\DB::table('live_items')
    ->whereIn('item_id', $itemIds)
    ->whereNotIn('live_id', [342, 343])
    ->get();
    
echo "In live_items (other lives): " . $soldInOtherLives->count() . "\n";
if ($soldInOtherLives->count() > 0) {
    echo json_encode($soldInOtherLives, JSON_PRETTY_PRINT) . "\n";
}

$inSacolinhas = \Illuminate\Support\Facades\DB::table('sacolinhas')
    ->whereIn('item_id', $itemIds)
    ->whereDate('created_at', '>=', '2026-10-04')
    ->get();
    
echo "In sacolinhas (after Oct 4): " . $inSacolinhas->count() . "\n";
if ($inSacolinhas->count() > 0) {
    echo json_encode($inSacolinhas, JSON_PRETTY_PRINT) . "\n";
}

$inPedidos = \Illuminate\Support\Facades\DB::table('items')
    ->whereIn('id', $itemIds)
    ->whereNotNull('pedido')
    ->get(['id', 'pedido']);

echo "With pedido not null: " . $inPedidos->count() . "\n";
if ($inPedidos->count() > 0) {
    echo json_encode($inPedidos, JSON_PRETTY_PRINT) . "\n";
}

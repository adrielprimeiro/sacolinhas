<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$lives = \App\Models\Live::whereDate('data', '2026-10-03')->get();
foreach($lives as $live) {
    echo "Live ID: {$live->id}...\n";
    $compras = \App\Models\LiveCompra::where('live_id', $live->id)->get();
    echo "Compras na live: {$compras->count()}\n";
    foreach($compras as $compra) {
        // dump
        echo "Compra ID: {$compra->id}, Item ID: {$compra->item_id}, Valor: {$compra->valor}\n";
    }
}

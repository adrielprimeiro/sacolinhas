<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$itemIds = range(74315, 74378);

$inSacolinhas = \Illuminate\Support\Facades\DB::table('sacolinhas')
    ->whereIn('item_id', $itemIds)
    ->whereDate('created_at', '<', '2026-10-04')
    ->get();
    
echo "In sacolinhas (before Oct 4): " . $inSacolinhas->count() . "\n";

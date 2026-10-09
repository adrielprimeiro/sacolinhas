<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$itemIds = range(74315, 74378);

$sacolinhasOld = \Illuminate\Support\Facades\DB::table('sacolinhas')
    ->whereIn('item_id', $itemIds)
    ->whereDate('created_at', '<', '2026-10-04')
    ->pluck('item_id')->toArray();
    
$sacolinhasNew = \Illuminate\Support\Facades\DB::table('sacolinhas')
    ->whereIn('item_id', $itemIds)
    ->whereDate('created_at', '>=', '2026-10-04')
    ->pluck('item_id')->toArray();
    
$intersect = array_intersect($sacolinhasOld, $sacolinhasNew);
echo "Items sold BOTH before and after Oct 4: " . count($intersect) . "\n";
print_r($intersect);

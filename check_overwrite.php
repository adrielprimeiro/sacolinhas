<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = \Illuminate\Support\Facades\Schema::getColumnListing('live_items');
echo json_encode($columns, JSON_PRETTY_PRINT) . "\n";

$items = \Illuminate\Support\Facades\DB::table('live_items')->whereIn('live_id', [342, 343])->get();
echo "Live_items counts for 342, 343: " . $items->count() . "\n";
if ($items->count() > 0) {
    echo json_encode($items->first(), JSON_PRETTY_PRINT) . "\n";
}

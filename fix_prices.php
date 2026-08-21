<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$items = \Illuminate\Support\Facades\DB::table('sacolinhas')->where('price', '>', 500)->get();
echo "Found " . $items->count() . " items with high price.\n";
foreach ($items as $item) {
    if ($item->price % 100 == 0) {
        $newPrice = $item->price / 100;
        echo "Fixing item {$item->id} from {$item->price} to {$newPrice}\n";
        \Illuminate\Support\Facades\DB::table('sacolinhas')->where('id', $item->id)->update(['price' => $newPrice]);
    }
}

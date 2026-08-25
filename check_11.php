<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$items = \App\Models\Item::where('localizacao', 'like', '%11%')->select('localizacao', \DB::raw('count(*) as total'))->groupBy('localizacao')->get();
foreach ($items as $i) {
    echo $i->localizacao . " -> " . $i->total . "\n";
}

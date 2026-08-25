<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$a = \App\Models\Avaliacao::find(19760);
echo "Avaliacao: " . ($a ? "Yes" : "No") . "\n";

$i = \App\Models\Item::where('codigo', 'like', '%1976%')->get();
echo "Items with 1976: " . count($i) . "\n";
foreach($i as $x) {
    echo " -> " . $x->codigo . "\n";
}

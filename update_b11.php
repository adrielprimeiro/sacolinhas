<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$count = \App\Models\Item::where('localizacao', 'B11')->count();
echo "Found {$count} items in B11.\n";

if ($count > 0) {
    \App\Models\Item::where('localizacao', 'B11')->update(['localizacao' => 'B21']);
    echo "Updated {$count} items from B11 to B21.\n";
} else {
    echo "No items to update.\n";
}

<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$itemsFromLive = \App\Models\Item::whereDate('created_at', '2026-10-03')->get();
echo "Itens criados em 2026-10-03: " . $itemsFromLive->count() . "\n";

if ($itemsFromLive->count() > 0) {
    $sample = $itemsFromLive->first();
    echo "Sample (ID: {$sample->id}): Codigo={$sample->codigo}, Nome={$sample->nome_do_produto}\n";
    echo "Created: {$sample->created_at}, Updated: {$sample->updated_at}\n";
    
    // Check if updated_at is later than Oct 3rd
    $overwrittenCount = 0;
    foreach($itemsFromLive as $it) {
        if ($it->updated_at > '2026-10-04 00:00:00') {
            $overwrittenCount++;
        }
    }
    echo "Itens que foram atualizados depois do dia 03: " . $overwrittenCount . "\n";
}

$lives = \App\Models\Live::whereDate('data', '2026-10-03')->get();
echo "Lives em 2026-10-03: " . $lives->count() . "\n";
foreach($lives as $live) {
    echo "Live ID: {$live->id}, Titulo: {$live->titulo}\n";
}

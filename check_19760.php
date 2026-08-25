<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$av = \App\Models\AvaliacaoItem::find(19760);
if ($av) {
    echo "AvaliacaoItem found. item_id: " . $av->item_id . "\n";
    if ($av->item_id) {
        $item = \App\Models\Item::find($av->item_id);
        echo "Item found: " . ($item ? "Yes (id: {$item->id}, code: {$item->codigo})" : "No (soft deleted?)") . "\n";
    }
} else {
    echo "AvaliacaoItem 19760 NOT found.\n";
}

$itemByCode = \App\Models\Item::where('codigo', 'like', '%19760%')->first();
if ($itemByCode) {
    echo "Item by code found: " . $itemByCode->codigo . " (id: " . $itemByCode->id . ")\n";
} else {
    echo "Item by code NOT found.\n";
}

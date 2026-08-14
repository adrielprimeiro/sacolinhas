<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$item = \App\Models\AvaliacaoItem::with('item')->where('avaliacao_id', 22)->first();
echo "Item_ID: " . $item->item_id . "\n";
echo "Localizacao: " . ($item->item ? $item->item->localizacao : 'null') . "\n";

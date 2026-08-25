<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$items = \App\Models\Item::where('nome_do_produto', 'like', '%Blazer%')->orderBy('id', 'desc')->take(5)->get();
foreach($items as $i) {
    echo "Item ID: {$i->id} - Cod: {$i->codigo} - Name: {$i->nome_do_produto} - AvaliacaoItem: {$i->avaliacao_item_id}\n";
}

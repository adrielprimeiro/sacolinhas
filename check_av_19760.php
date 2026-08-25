<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$av = \App\Models\AvaliacaoItem::find(19760);
if ($av) {
    echo "AvaliacaoItem 19760:\n";
    echo "Status: " . $av->status . "\n";
    echo "Avaliacao ID: " . $av->avaliacao_id . "\n";
    echo "Nome: " . $av->nome . "\n";
}

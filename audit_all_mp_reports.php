<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;

$allMp = TransacaoExtrato::where('origem', 'mercadopago')->get();

echo "Total transacoes MP no banco: " . $allMp->count() . "\n";

$descriptions = [];
foreach ($allMp as $t) {
    $desc = $t->descricao;
    $descriptions[$desc] = ($descriptions[$desc] ?? 0) + 1;
}

echo "Descricoes encontradas:\n";
foreach ($descriptions as $desc => $c) {
    echo " - '{$desc}': {$c} registros\n";
}

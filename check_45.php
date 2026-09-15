<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$avaliacao = \App\Models\Avaliacao::find(45);
if($avaliacao) {
    echo json_encode($avaliacao->toArray(), JSON_PRETTY_PRINT);
    echo "\n";
    $item = $avaliacao->items->first();
    echo json_encode($item->toArray(), JSON_PRETTY_PRINT);
}

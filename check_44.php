<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$avaliacao = \App\Models\Avaliacao::find(44);
$item = $avaliacao->items->first();
print_r($item->toArray());

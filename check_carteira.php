<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$conta = App\Models\ContaBancaria::where('nome', 'like', '%Carteira%')->first();
echo "Conta Carteira: " . ($conta ? $conta->id . " - " . $conta->nome : "Not found") . "\n";

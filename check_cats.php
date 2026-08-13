<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = App\Models\ClassificacaoFinanceira::where('nome', 'like', '%Pedido%')->orWhere('nome', 'like', '%Venda%')->get();
foreach ($c as $item) {
    echo "{$item->id}: {$item->nome}\n";
}

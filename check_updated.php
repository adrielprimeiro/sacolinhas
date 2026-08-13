<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pedido = App\Models\Pedido::find(756);
echo "Pedido 756 atualizado em: {$pedido->updated_at}\n";

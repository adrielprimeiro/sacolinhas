<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pedido = App\Models\Pedido::find(401);
if ($pedido) {
    $pedido->valor_total = 82.00;
    $pedido->save();
    echo "SUCESSO: Pedido 401 atualizado para 82.00\n";
} else {
    echo "ERRO: Pedido 401 não encontrado\n";
}

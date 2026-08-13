<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pedidos = App\Models\Pedido::where('user_id', 1254)->get();
foreach ($pedidos as $p) {
    echo "Pedido {$p->id} (Num {$p->numero_pedido}): Status {$p->status_pagamento}, Total: {$p->valor_total}\n";
}

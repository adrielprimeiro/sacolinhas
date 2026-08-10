<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pedido;

$pedidos = Pedido::where('status_pagamento', '!=', 'aprovado')
    ->whereNotIn('status_pedido', ['cancelado', 'pago', 'entregue', 'concluido'])
    ->get();

echo "Total de pedidos nao pagos e nao cancelados: " . $pedidos->count() . "\n\n";

foreach ($pedidos as $p) {
    $data = $p->data_pedido ?? $p->created_at;
    $horas = $data ? \Carbon\Carbon::parse($data)->diffInHours(now()) : 999;
    echo "ID: {$p->id} | Num: {$p->numero_pedido} | Origem: {$p->origem_pedido} | User: {$p->user_id} | StatusPedido: {$p->status_pedido} | StatusPag: {$p->status_pagamento} | Data: {$data} ({$horas}h atras)\n";
}

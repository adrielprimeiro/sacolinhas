<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pedido;
use Illuminate\Support\Facades\DB;

$pedidosPortal = Pedido::whereIn('origem_pedido', ['portal', 'site'])
    ->where('status_pagamento', '!=', 'aprovado')
    ->whereNotIn('status_pedido', ['cancelado', 'pago', 'entregue', 'concluido'])
    ->get();

echo "Encontrados " . $pedidosPortal->count() . " pedidos do portal/site pendentes de pagamento:\n";

foreach ($pedidosPortal as $p) {
    $data = $p->data_pedido ?? $p->created_at;
    $horas = $data ? \Carbon\Carbon::parse($data)->diffInHours(now()) : 999;
    echo "ID: {$p->id} | Num: {$p->numero_pedido} | User: {$p->user_id} | Status: {$p->status_pedido} | Pagamento: {$p->status_pagamento} | Data: {$data} ({$horas}h atras)\n";
}

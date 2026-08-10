<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pedido;
use Illuminate\Support\Facades\DB;

$pedidoIds = [479, 499, 720];

foreach ($pedidoIds as $id) {
    $p = Pedido::find($id);
    if (!$p) continue;
    echo "--- Pedido {$p->id} ({$p->numero_pedido}) - User {$p->user_id} - Origem: {$p->origem_pedido} ---\n";
    $items = DB::table('items_pedido as ip')
        ->join('items as i', 'i.id', '=', 'ip.item_id')
        ->where('ip.pedido_id', $p->id)
        ->select('ip.*', 'i.nome_do_produto', 'i.status as item_status')
        ->get();
    foreach ($items as $it) {
        $sacola = DB::table('sacolinhas')->where('user_id', $p->user_id)->where('item_id', $it->item_id)->first();
        $sacolaStatus = $sacola ? $sacola->status : 'NAO_EXISTE';
        echo "  Item ID {$it->item_id}: {$it->nome_do_produto} | Preco: R$ {$it->preco_unitario} | ItemStatus: {$it->item_status} | SacolaStatus: {$sacolaStatus}\n";
    }
}

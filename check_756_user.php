<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p = App\Models\Pedido::with('user')->find(756);
if ($p) {
    echo "Pedido user: {$p->user->name}\n";
    $ultima = App\Models\ContaCorrente::where('user_id', $p->user_id)->orderBy('id', 'desc')->take(4)->get();
    foreach($ultima as $c) {
        echo "ContaCorrente: {$c->id} Tipo: {$c->tipo_movimentacao} Valor: {$c->valor} Saldo: {$c->saldo_atual} Ref: {$c->referencia_tipo} RefID: {$c->referencia_id} Data: {$c->data_movimentacao}\n";
    }
}

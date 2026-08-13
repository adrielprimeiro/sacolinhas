<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \DB::table('conta_corrente as cc')->select('cc.user_id', \DB::raw('MAX(cc.id) as max_id'))->groupBy('cc.user_id')->pluck('max_id');
$saldos = \DB::table('conta_corrente')->whereIn('id', $users)->where('saldo_atual', '<', 0)->get(['user_id', 'saldo_atual']);
$sum = \DB::table('conta_corrente')->whereIn('id', $users)->sum('saldo_atual');

echo "Soma Geral: " . $sum . PHP_EOL;

foreach($saldos as $s) {
    echo "User: " . $s->user_id . " | Saldo: " . $s->saldo_atual . PHP_EOL;
    $pedidos = \App\Models\Pedido::where('user_id', $s->user_id)->whereIn('status_pedido', ['pendente', 'confirmado'])->get();
    foreach($pedidos as $p) {
        echo "  - Pedido " . $p->status_pedido . ": ID " . $p->id . " Valor: " . $p->valor_total . PHP_EOL;
    }
}

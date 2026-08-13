<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$userId = 2058; // Assuming Marta Jany

$p = App\Models\Pedido::with('lancamento.movimentacoes')->find(756);
if ($p) {
    echo "Pedido 756: Valor={$p->valor_total}, Status={$p->status_pedido}, Pagamento={$p->status_pagamento}\n";
    $l = $p->lancamento;
    if ($l) {
        echo "  Lanc: {$l->id}, Tipo: {$l->tipo}, Valor: {$l->valor_total}, Status: {$l->status}\n";
        foreach($l->movimentacoes as $m) {
            echo "    Mov: {$m->id}, Valor: {$m->valor_pago}, Conta: {$m->conta_bancaria_id}\n";
        }
    }
} else {
    echo "Pedido 756 não encontrado.\n";
}

$u = App\Models\User::where('name', 'like', '%Marta Jany%')->first();
if ($u) {
    $ultima = App\Models\ContaCorrente::where('user_id', $u->id)->orderBy('id', 'desc')->take(5)->get();
    foreach($ultima as $c) {
        echo "ContaCorrente: {$c->id} Tipo: {$c->tipo_movimentacao} Valor: {$c->valor} Saldo: {$c->saldo_atual} Ref: {$c->referencia_tipo} RefID: {$c->referencia_id} Data: {$c->data_movimentacao}\n";
    }
}

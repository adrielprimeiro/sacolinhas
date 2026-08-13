<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p = \App\Models\Pedido::find(499); 
if($p) {
    echo 'Status Pedido: ' . $p->status_pedido . ' | Status Pagamento: ' . $p->status_pagamento . PHP_EOL; 
    $l = \App\Models\Lancamento::where('referencia_tipo', 'pedido')->where('referencia_id', 499)->first(); 
    echo 'Lancamento Status: ' . ($l ? $l->status : 'NULL') . PHP_EOL; 
    $movs = $l ? $l->movimentacoes : []; 
    foreach($movs as $m) {
        echo 'Mov: ' . $m->valor_pago . ' Forma: ' . $m->forma_pagamento . PHP_EOL;
    }
}

$cc = \App\Models\ContaCorrente::where('user_id', 2144)->get();
foreach($cc as $c) {
    echo "ID: {$c->id} | {$c->tipo_movimentacao} | {$c->valor} | {$c->descricao} | Ref: {$c->referencia_tipo}-{$c->referencia_id} | Saldo: {$c->saldo_atual}\n";
}

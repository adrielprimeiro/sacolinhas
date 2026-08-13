<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pedidos = App\Models\Pedido::with('lancamento.movimentacoes')->where('numero_pedido', 'like', '%763%')->get();
foreach ($pedidos as $p) {
    echo "Pedido ID={$p->id}: Numero={$p->numero_pedido}, Valor={$p->valor_total}, Status={$p->status_pedido}\n";
    $l = $p->lancamento;
    if ($l) {
        echo "  Lanc: {$l->id}, Tipo: {$l->tipo}, Valor: {$l->valor_total}, Status: {$l->status}\n";
        foreach($l->movimentacoes as $m) {
            echo "    Mov: {$m->id}, Valor: {$m->valor_pago}, Conta: {$m->conta_bancaria_id}\n";
        }
    }
}
$lanc = App\Models\Lancamento::with('movimentacoes')->where('descricao', 'like', '%763%')->get();
foreach ($lanc as $l) {
    echo "Lanc by desc: {$l->id}, Desc: {$l->descricao}, Valor: {$l->valor_total}\n";
}

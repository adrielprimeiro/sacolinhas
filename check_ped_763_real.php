<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p = App\Models\Pedido::with('lancamentos.movimentacoes')->where('numero_pedido', 'PED-000763')->first();
if ($p) {
    echo "Pedido 763: Numero={$p->numero_pedido}, Valor={$p->valor_total}, Status={$p->status_pedido}\n";
    foreach($p->lancamentos as $l) {
        echo "Lanc: {$l->id}, Tipo: {$l->tipo}, Valor: {$l->valor_total}, Status: {$l->status}\n";
        foreach($l->movimentacoes as $m) {
            echo "  Mov: {$m->id}, Valor: {$m->valor_pago}, Conta: {$m->conta_bancaria_id}\n";
        }
    }
} else {
    echo "Pedido nao encontrado\n";
}

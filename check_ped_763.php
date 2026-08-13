<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pedido = App\Models\Pedido::where('id', 763)->with(['lancamentos.movimentacoes'])->first();
if (!$pedido) {
    echo "Pedido not found.\n";
} else {
    echo "Pedido ID: {$pedido->id}, Status: {$pedido->status_pedido}\n";
    foreach ($pedido->lancamentos as $l) {
        echo "Lançamento {$l->id}: Tipo={$l->tipo}, Valor={$l->valor_total}, Status={$l->status}\n";
        foreach ($l->movimentacoes as $m) {
            echo "  Movimentacao {$m->id}: Valor={$m->valor_pago}, Conta={$m->conta_bancaria_id}\n";
        }
    }
}

$extratos = App\Models\TransacaoExtrato::where('descricao', 'like', '%763%')->orWhere('referencia_id', 763)->get();
echo "\nExtrato:\n";
foreach ($extratos as $e) {
    echo "ID: {$e->id}, Tipo: {$e->tipo}, Valor: {$e->valor}, Desc: {$e->descricao}, Status: {$e->status}, Origem: {$e->origem}\n";
}

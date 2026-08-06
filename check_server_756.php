<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Verificar o tipo da coluna status_pagamento
$col = DB::select("SHOW COLUMNS FROM pedidos LIKE 'status_pagamento'");
echo "Tipo da coluna: " . $col[0]->Type . "\n\n";

// 2. Pedido 756 - estado atual
$pedido = App\Models\Pedido::find(756);
echo "Pedido 756 status_pagamento atual: {$pedido->status_pagamento}\n";

$lancamento = App\Models\Lancamento::where('referencia_tipo', 'pedido')->where('referencia_id', 756)->first();
if ($lancamento) {
    echo "Lancamento {$lancamento->id} status: {$lancamento->status}\n";
    $movs = DB::table('movimentacoes')->where('lancamento_id', $lancamento->id)->get();
    echo "Movimentacoes: {$movs->count()}\n";
    foreach ($movs as $m) {
        echo "  - ID {$m->id} | R\$ {$m->valor_pago} | {$m->forma_pagamento} | {$m->data_pagamento}\n";
    }
}

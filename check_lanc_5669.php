<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$l = App\Models\Lancamento::with('movimentacoes')->find(5669);
echo "Lanc 5669: Ref={$l->referencia_tipo} ID={$l->referencia_id}, Valor={$l->valor_total}\n";
foreach($l->movimentacoes as $m) {
    echo "Mov: {$m->id}, Valor: {$m->valor_pago}, Conta: {$m->conta_bancaria_id}\n";
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$lancTaxa = App\Models\Lancamento::where('referencia_tipo', 'taxa_pagamento')->where('referencia_id', 5072)->first();
if ($lancTaxa) {
    echo "Taxa registrada! Lanc {$lancTaxa->id}, Valor {$lancTaxa->valor_total}\n";
    foreach ($lancTaxa->movimentacoes as $m) {
        echo "  Mov taxa: {$m->id}, Valor: {$m->valor_pago}, Conta: {$m->conta_bancaria_id}\n";
    }
} else {
    echo "Nenhuma taxa registrada para a mov 5072.\n";
}

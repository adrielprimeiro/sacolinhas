<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Movimentacao;
use App\Models\Lancamento;

$movs = Movimentacao::where('transacao_extrato_id', 919)->get();
echo "Movimentacoes da Transacao 919 (" . $movs->count() . "):\n";
foreach ($movs as $m) {
    $l = Lancamento::find($m->lancamento_id);
    $c = $l?->classificacaoFinanceira;
    echo "Mov #{$m->id} | Lanc #{$m->lancamento_id} | ClassID: {$l?->classificacao_financeira_id} ({$c?->nome}) | DataPag: {$m->data_pagamento} | Valor: R$ {$m->valor_pago}\n";
}

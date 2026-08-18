<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use App\Models\Movimentacao;
use App\Models\Lancamento;

echo "=== Searching for transactions of R$ 66.08 or related in database ===\n\n";

$diffTrans = TransacaoExtrato::whereBetween('valor', [66.00, 66.15])->get();
echo "Encontradas " . $diffTrans->count() . " transações no extrato perto de R$ 66.08:\n";
foreach ($diffTrans as $t) {
    echo "ID: {$t->id} | Data: {$t->data->format('Y-m-d')} | Origem: {$t->origem} | Tipo: {$t->tipo} | Valor: R$ {$t->valor} | Status: {$t->status} | Desc: {$t->descricao}\n";
}

echo "\n=== Verificando movimentações perto de R$ 66.08 ===\n";
$movs = Movimentacao::whereBetween('valor_pago', [66.00, 66.15])->get();
foreach ($movs as $m) {
    echo "Mov ID: {$m->id} | ContaID: {$m->conta_bancaria_id} | Data: {$m->data_pagamento} | Valor: R$ {$m->valor_pago} | TransExtratoID: {$m->transacao_extrato_id}\n";
}

echo "\n=== Verificando se há alguma diferença no Saldo Inicial da Conta Mercado Pago ===\n";
$contaMp = \App\Models\ContaBancaria::find(2);
echo "Saldo Inicial Mercado Pago: R$ {$contaMp->saldo_inicial}\n";
echo "Saldo Atual Mercado Pago no BD: R$ {$contaMp->saldo_atual}\n";
echo "Diferença exata entre BD e Real (R$ 70,58 - R$ 4,50): R$ " . (70.58 - 4.50) . "\n";

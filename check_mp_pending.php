<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use Illuminate\Support\Facades\DB;

$pendingMp = TransacaoExtrato::where('origem', 'mercadopago')
    ->where('status', 'pendente')
    ->orderByDesc('id')
    ->get();

echo "=== Transacoes Pendentes do Mercado Pago (" . $pendingMp->count() . ") ===\n\n";

foreach ($pendingMp as $t) {
    echo "ID: {$t->id} | FITID: {$t->fitid} | Data: {$t->data} | Tipo: {$t->tipo} | Valor: R$ {$t->valor} | ValorLiquido: R$ {$t->valor_liquido} | Desc: {$t->descricao} | CreatedAt: {$t->created_at}\n";
    if (!empty($t->payload_original)) {
        $payload = is_array($t->payload_original) ? $t->payload_original : json_decode($t->payload_original, true);
        echo "   Payload Keys: " . implode(', ', array_keys((array)$payload)) . "\n";
        if (isset($payload['external_reference'])) echo "   ExternalRef: {$payload['external_reference']}\n";
        if (isset($payload['status'])) echo "   MP Status: {$payload['status']}\n";
        if (isset($payload['operation_type'])) echo "   OperationType: {$payload['operation_type']}\n";
        if (isset($payload['payment_type_id'])) echo "   PaymentTypeId: {$payload['payment_type_id']}\n";
    }
    echo "---------------------------------------------------------\n";
}

echo "\n=== Checando se existem Movimentacoes/Lancamentos existentes com esses valores ou no mesmo dia ===\n";
foreach ($pendingMp as $t) {
    $movs = DB::table('movimentacoes')->where('valor_pago', $t->valor)->get();
    echo "Transacao ID {$t->id} (R$ {$t->valor}): encontrou " . $movs->count() . " movimentacoes com valor R$ {$t->valor}:\n";
    foreach ($movs as $m) {
        $l = DB::table('lancamentos')->where('id', $m->lancamento_id)->first();
        echo "  Mov #{$m->id} | Data: {$m->data_pagamento} | Conta: {$m->conta_bancaria_id} | RefExtratoID: {$m->transacao_extrato_id} | LancDesc: " . ($l?->descricao) . "\n";
    }
}

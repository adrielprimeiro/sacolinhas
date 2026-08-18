<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use App\Models\Movimentacao;

$t975 = TransacaoExtrato::find(975);
echo "=== Transação ID 975 ===\n";
if ($t975) {
    echo "ID: {$t975->id} | Data: {$t975->data} | Origem: {$t975->origem} | Tipo: {$t975->tipo} | Valor: R$ {$t975->valor} | Status: {$t975->status} | Desc: {$t975->descricao}\n";
    echo "Payload original: " . print_r($t975->payload_original, true) . "\n";
}

$mov = Movimentacao::with('lancamento')->where('transacao_extrato_id', 975)->first();
echo "\n=== Movimentação vinculada ao ID 975 ===\n";
if ($mov) {
    echo "Mov ID: {$mov->id} | ContaID: {$mov->conta_bancaria_id} | Data: {$mov->data_pagamento} | Valor Pago: R$ {$mov->valor_pago}\n";
    if ($mov->lancamento) {
        echo "   Lançamento ID: {$mov->lancamento->id} | Tipo: {$mov->lancamento->tipo} | CategoriaID: {$mov->lancamento->categoria_id} | Valor: R$ {$mov->lancamento->valor} | Desc: {$mov->lancamento->descricao}\n";
    }
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pedido = App\Models\Pedido::where('numero_pedido', 'PED-000756')->first();
if (!$pedido) {
    echo "Pedido 756 não encontrado!\n";
    exit;
}

$lancamento = App\Models\Lancamento::where('referencia_tipo', 'pedido')->where('referencia_id', $pedido->id)->first();
if (!$lancamento) {
    echo "Lancamento não encontrado!\n";
    exit;
}

// Apaga qualquer movimentação incorreta (tipo a de 300) para começar limpo
$lancamento->movimentacoes()->delete();

// Registra apenas os 225 corretos
App\Models\Movimentacao::create([
    'lancamento_id'    => $lancamento->id,
    'conta_bancaria_id' => 3, // Carteira Cliente
    'data_pagamento'   => now()->toDateString(),
    'valor_pago'       => 225.00,
    'forma_pagamento'  => 'saldo_carteira',
]);

// Atualiza os status para Parcial
$lancamento->update(['status' => 'pago_parcial']);
$pedido->update(['status_pagamento' => 'parcial']);

echo "Pedido 756 corrigido com sucesso! Agora tem 1 pagamento de R$ 225,00 e o status é Parcial.\n";

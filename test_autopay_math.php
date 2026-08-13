<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::where('name', 'like', '%Marta Jany%')->first();
if (!$u) die("User not found");
$userId = $u->id;

// Pega o saldo final atual do usuário
$ultimaMov = App\Models\ContaCorrente::where('user_id', $userId)
                          ->orderByDesc('data_movimentacao')
                          ->orderByDesc('id')
                          ->first();

$saldoDisponivelReal = $ultimaMov ? $ultimaMov->saldo_atual : 0;

$pedidosPendentes = App\Models\Pedido::where('user_id', $userId)
                          ->whereIn('status_pagamento', ['pendente', 'parcial'])
                          ->orderBy('created_at', 'asc')
                          ->get();

$totalPendentes = 0;
$totalPago = 0;

foreach ($pedidosPendentes as $pedido) {
    $lancamento = App\Models\Lancamento::where('referencia_tipo', 'pedido')
                            ->where('referencia_id', $pedido->id)
                            ->where('tipo', 'receita')
                            ->whereIn('status', ['pendente', 'pago_parcial'])
                            ->first();
    if ($lancamento) {
        $totalPendentes += $lancamento->valor_total;
        $totalPago += $lancamento->movimentacoes()->sum('valor_pago');
    }
}

// A mágica matemática:
$saldoCalculado = ($totalPendentes + $saldoDisponivelReal) - $totalPago;

echo "Saldo Real da Carteira: $saldoDisponivelReal\n";
echo "Total em Pedidos Pendentes: $totalPendentes\n";
echo "Total Já Pago: $totalPago\n";
echo "Saldo Disponível para Abater: $saldoCalculado\n";

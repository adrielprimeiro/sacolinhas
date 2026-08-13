<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$userId = 1254;
$ultimaMov = App\Models\ContaCorrente::where('user_id', $userId)
                          ->orderByDesc('data_movimentacao')
                          ->orderByDesc('id')
                          ->first();

$saldoDisponivelReal = $ultimaMov->saldo_atual;
$pedidosPendentes = App\Models\Pedido::where('user_id', $userId)
                          ->whereIn('status_pagamento', ['pendente', 'parcial'])
                          ->orderBy('created_at', 'asc')
                          ->get();

$totalPendentes = 0;
$totalPago = 0;
$lancamentosProcessar = collect();

foreach ($pedidosPendentes as $pedido) {
    $lancamento = App\Models\Lancamento::where('referencia_tipo', 'pedido')
                            ->where('referencia_id', $pedido->id)
                            ->where('tipo', 'receita')
                            ->whereIn('status', ['pendente', 'pago_parcial'])
                            ->first();
    if ($lancamento) {
        $totalPendentes += $lancamento->valor_total;
        $jaPago = $lancamento->movimentacoes()->sum('valor_pago');
        $totalPago += $jaPago;
        
        $lancamentosProcessar->push([
            'pedido' => $pedido,
            'lancamento' => $lancamento,
            'valor_restante' => max(0, $lancamento->valor_total - $jaPago)
        ]);
        echo "Lancamento {$lancamento->id} added. Total {$lancamento->valor_total}, JaPago $jaPago\n";
    }
}

$saldoDisponivel = ($totalPendentes + $saldoDisponivelReal) - $totalPago;
echo "Saldo Disponivel calculado: $saldoDisponivel\n";

foreach ($lancamentosProcessar as $item) {
    $pedido = $item['pedido'];
    $lancamento = $item['lancamento'];
    $valorRestante = $item['valor_restante'];
    $valorParaAbater = min($saldoDisponivel, $valorRestante);
    echo "Abatendo: $valorParaAbater\n";
}

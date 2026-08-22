<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== Auditando Faturamento do Clube do Mês Vigente (08/2026) e Geral ===\n\n";

$inicioMes = Carbon::now()->startOfMonth()->toDateTimeString();
$fimMes    = Carbon::now()->endOfMonth()->toDateTimeString();

// Clientes do Clube: possuem registros em avaliações, conta_corrente ou pontos
$usersClubeIds = DB::table('avaliacoes')->whereNotNull('user_id')->pluck('user_id')->toArray();
$usersCCIds    = DB::table('conta_corrente')->whereNotNull('user_id')->pluck('user_id')->toArray();

$clubeIds = array_unique(array_merge($usersClubeIds, $usersCCIds));

// Faturamento Mês Atual
$fatClubeMes = DB::table('pedidos')
    ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
    ->whereBetween('created_at', [$inicioMes, $fimMes])
    ->whereIn('user_id', $clubeIds)
    ->sum('valor_total');

$fatOutrosMes = DB::table('pedidos')
    ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
    ->whereBetween('created_at', [$inicioMes, $fimMes])
    ->whereNotIn('user_id', $clubeIds)
    ->sum('valor_total');

$fatTotalMes = DB::table('pedidos')
    ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
    ->whereBetween('created_at', [$inicioMes, $fimMes])
    ->sum('valor_total');

echo "--- MÊS ATUAL (" . date('m/Y') . ") --- \n";
echo "Faturamento Clientes do Clube no Mês: R$ " . number_format($fatClubeMes, 2, ',', '.') . " (" . ($fatTotalMes > 0 ? round(($fatClubeMes / $fatTotalMes) * 100, 1) : 0) . "%)\n";
echo "Faturamento Outros Clientes no Mês: R$ " . number_format($fatOutrosMes, 2, ',', '.') . " (" . ($fatTotalMes > 0 ? round(($fatOutrosMes / $fatTotalMes) * 100, 1) : 0) . "%)\n";
echo "Faturamento Total do Mês: R$ " . number_format($fatTotalMes, 2, ',', '.') . "\n\n";

// Pedidos por origem ou canal se houver
$pedidosPorOrigem = DB::table('pedidos')
    ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
    ->whereBetween('created_at', [$inicioMes, $fimMes])
    ->select('origem_pedido', DB::raw('COUNT(*) as qtd'), DB::raw('SUM(valor_total) as total'))
    ->groupBy('origem_pedido')
    ->get();

echo "Origem dos Pedidos no Mês:\n";
foreach ($pedidosPorOrigem as $p) {
    echo "   - Origem '" . ($p->origem_pedido ?? 'Indefinido') . "': {$p->qtd} pedidos | R$ " . number_format($p->total, 2, ',', '.') . "\n";
}

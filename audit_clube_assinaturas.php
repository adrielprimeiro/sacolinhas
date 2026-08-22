<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== Auditando clube_assinaturas e Faturamento Real do Clube no Mês ===\n\n";

$inicioMes = Carbon::now()->startOfMonth()->toDateTimeString();
$fimMes    = Carbon::now()->endOfMonth()->toDateTimeString();

// 1. Assinantes do Clube em clube_assinaturas
$assinantesAtivos = DB::table('clube_assinaturas')->where('status', 'ativa')->pluck('user_id')->toArray();
$todosAssinantes  = DB::table('clube_assinaturas')->pluck('user_id')->toArray();

echo "1. Total de Assinantes com status 'ativa' em clube_assinaturas: " . count($assinantesAtivos) . " membros\n";
echo "   Total de Assinantes históricos (qualquer status) em clube_assinaturas: " . count($todosAssinantes) . " membros\n\n";

// 2. Pedidos do Mês Vigente (08/2026)
$fatClubeAtivosMes = DB::table('pedidos')
    ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
    ->whereBetween('created_at', [$inicioMes, $fimMes])
    ->whereIn('user_id', $assinantesAtivos)
    ->sum('valor_total');

$fatClubeTodosMes = DB::table('pedidos')
    ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
    ->whereBetween('created_at', [$inicioMes, $fimMes])
    ->whereIn('user_id', $todosAssinantes)
    ->sum('valor_total');

$fatTotalMes = DB::table('pedidos')
    ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
    ->whereBetween('created_at', [$inicioMes, $fimMes])
    ->sum('valor_total');

$fatOutrosMes = $fatTotalMes - $fatClubeAtivosMes;

echo "--- FATURAMENTO DO MÊS VIGENTE (08/2026) --- \n";
echo "Faturamento Total do Mês: R$ " . number_format($fatTotalMes, 2, ',', '.') . "\n";
echo "Faturamento Membros Ativos do Clube: R$ " . number_format($fatClubeAtivosMes, 2, ',', '.') . " (" . ($fatTotalMes > 0 ? round(($fatClubeAtivosMes / $fatTotalMes) * 100, 1) : 0) . "%)\n";
echo "Faturamento Outros Clientes (Fora do Clube): R$ " . number_format($fatOutrosMes, 2, ',', '.') . " (" . ($fatTotalMes > 0 ? round(($fatOutrosMes / $fatTotalMes) * 100, 1) : 0) . "%)\n";
echo "Faturamento Membros Históricos (qualquer status): R$ " . number_format($fatClubeTodosMes, 2, ',', '.') . "\n";

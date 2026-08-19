<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Análise Completa de Ticket Médio do Sistema ===\n\n";

$pedCols = Schema::getColumnListing('pedidos');
$valCol = in_array('valor_total', $pedCols) ? 'valor_total' : (in_array('total', $pedCols) ? 'total' : 'valor');

// 1. Ticket Médio por PEDIDO FECHADO (Tabela `pedidos`)
$pedidos = DB::table('pedidos')
    ->whereNotIn('status', ['cancelado', 'rascunho'])
    ->select(DB::raw("COUNT(*) as total_pedidos"), DB::raw("SUM({$valCol}) as faturamento_total"), DB::raw("AVG({$valCol}) as ticket_medio_pedido"))
    ->first();

echo "--- 1. TICKET MÉDIO POR PEDIDO FECHADO (Geral) ---\n";
echo "Total de Pedidos Validados: " . number_format($pedidos->total_pedidos, 0, ',', '.') . "\n";
echo "Faturamento Total dos Pedidos: R$ " . number_format($pedidos->faturamento_total, 2, ',', '.') . "\n";
echo "Ticket Médio por Pedido: R$ " . number_format($pedidos->ticket_medio_pedido, 2, ',', '.') . "\n\n";

// Ticket Médio por Pedido em 2026
$pedidosRecentes = DB::table('pedidos')
    ->whereNotIn('status', ['cancelado', 'rascunho'])
    ->where('created_at', '>=', '2026-01-01')
    ->select(DB::raw("COUNT(*) as total_pedidos"), DB::raw("SUM({$valCol}) as faturamento_total"), DB::raw("AVG({$valCol}) as ticket_medio_pedido"))
    ->first();

echo "--- 2. TICKET MÉDIO POR PEDIDO EM 2026 ---\n";
echo "Total de Pedidos em 2026: " . number_format($pedidosRecentes->total_pedidos, 0, ',', '.') . "\n";
echo "Faturamento 2026: R$ " . number_format($pedidosRecentes->faturamento_total, 2, ',', '.') . "\n";
echo "Ticket Médio por Pedido em 2026: R$ " . number_format($pedidosRecentes->ticket_medio_pedido, 2, ',', '.') . "\n\n";

// 3. Ticket Médio por SACOLINHA DE CLIENTE EM LIVES RECENTES (Sacolinhas por cliente por live)
$sacolasPorClienteLive = DB::table('sacolinhas')
    ->whereIn('status', ['live', 'em analise', 'sacolinha', 'pedido'])
    ->select('live_id', 'user_id', DB::raw('SUM(price * quantity) as total_cliente'), DB::raw('SUM(quantity) as qtd_itens'))
    ->groupBy('live_id', 'user_id')
    ->get();

$totalSacolasLive = $sacolasPorClienteLive->count();
$somaValorSacolas = $sacolasPorClienteLive->sum('total_cliente');
$somaItensSacolas = $sacolasPorClienteLive->sum('qtd_itens');

$ticketMedioSacolaLive = $totalSacolasLive > 0 ? $somaValorSacolas / $totalSacolasLive : 0;
$qtdMediaItensPorSacola = $totalSacolasLive > 0 ? $somaItensSacolas / $totalSacolasLive : 0;
$precoMedioPorPeca = $somaItensSacolas > 0 ? $somaValorSacolas / $somaItensSacolas : 0;

echo "--- 3. TICKET MÉDIO POR CLIENTE POR LIVE (Geral) ---\n";
echo "Total de Sacolinhas/Compradores em Lives: " . number_format($totalSacolasLive, 0, ',', '.') . "\n";
echo "Ticket Médio por Cliente na Live: R$ " . number_format($ticketMedioSacolaLive, 2, ',', '.') . "\n";
echo "Quantidade Média de Peças por Sacolinha: " . number_format($qtdMediaItensPorSacola, 1, ',', '.') . " peças/cliente\n";
echo "Preço Médio por Peça/Item Vendido: R$ " . number_format($precoMedioPorPeca, 2, ',', '.') . "\n\n";

// 4. Ticket Médio da Live Mais Recente (Live 310 - 18/08)
$sacolas310 = DB::table('sacolinhas')
    ->where('live_id', 310)
    ->whereIn('status', ['live', 'em analise', 'sacolinha', 'pedido'])
    ->select('user_id', DB::raw('SUM(price * quantity) as total_cliente'), DB::raw('SUM(quantity) as qtd_itens'))
    ->groupBy('user_id')
    ->get();

$numClientes310 = $sacolas310->count();
$valTotal310   = $sacolas310->sum('total_cliente');
$qtdItens310   = $sacolas310->sum('qtd_itens');
$ticketMedio310 = $numClientes310 > 0 ? $valTotal310 / $numClientes310 : 0;
$itensPorCliente310 = $numClientes310 > 0 ? $qtdItens310 / $numClientes310 : 0;
$precoMedioPeca310 = $qtdItens310 > 0 ? $valTotal310 / $qtdItens310 : 0;

echo "--- 4. TICKET MÉDIO DA ÚLTIMA LIVE (LIVE #310 - 18/08) ---\n";
echo "Total de Clientes Compradores: {$numClientes310}\n";
echo "Faturamento Total da Live: R$ " . number_format($valTotal310, 2, ',', '.') . "\n";
echo "Ticket Médio por Cliente na Live #310: R$ " . number_format($ticketMedio310, 2, ',', '.') . "\n";
echo "Média de Peças por Cliente: " . number_format($itensPorCliente310, 1, ',', '.') . " peças/cliente\n";
echo "Preço Médio por Peça na Live #310: R$ " . number_format($precoMedioPeca310, 2, ',', '.') . "\n";

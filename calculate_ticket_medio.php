<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Colunas da tabela pedidos:\n";
print_r(Schema::getColumnListing('pedidos'));

$pedCols = Schema::getColumnListing('pedidos');
$valCol = in_array('valor_total', $pedCols) ? 'valor_total' : (in_array('total', $pedCols) ? 'total' : (in_array('valor', $pedCols) ? 'valor' : null));
$statusCol = in_array('status', $pedCols) ? 'status' : (in_array('status_pedido', $pedCols) ? 'status_pedido' : null);

echo "\n=== 1. TICKET MÉDIO POR PEDIDO FECHADO ===\n";
if ($valCol) {
    $q = DB::table('pedidos');
    if ($statusCol) {
        $q->whereNotIn($statusCol, ['cancelado', 'rascunho']);
    }
    $pedidos = $q->select(DB::raw("COUNT(*) as total_pedidos"), DB::raw("SUM({$valCol}) as faturamento_total"), DB::raw("AVG({$valCol}) as ticket_medio_pedido"))->first();
    echo "Total de Pedidos: " . number_format($pedidos->total_pedidos, 0, ',', '.') . "\n";
    echo "Faturamento Total dos Pedidos: R$ " . number_format($pedidos->faturamento_total, 2, ',', '.') . "\n";
    echo "Ticket Médio por Pedido: R$ " . number_format($pedidos->ticket_medio_pedido, 2, ',', '.') . "\n\n";
} else {
    echo "Coluna de valor não encontrada na tabela pedidos.\n\n";
}

// 2. Ticket Médio por SACOLINHA DE CLIENTE EM LIVES
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

echo "=== 2. TICKET MÉDIO POR CLIENTE POR LIVE (Histórico de Lives) ===\n";
echo "Total de Sacolinhas/Compradores em Lives: " . number_format($totalSacolasLive, 0, ',', '.') . "\n";
echo "Ticket Médio por Compradora na Live: R$ " . number_format($ticketMedioSacolaLive, 2, ',', '.') . "\n";
echo "Quantidade Média de Peças por Sacola: " . number_format($qtdMediaItensPorSacola, 1, ',', '.') . " peças/cliente\n";
echo "Preço Médio por Peça/Item Vendido: R$ " . number_format($precoMedioPorPeca, 2, ',', '.') . "\n\n";

// 3. Ticket Médio da Live Mais Recente (Live 310 - 18/08)
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

echo "=== 3. TICKET MÉDIO DA ÚLTIMA LIVE (LIVE #310 - 18/08) ===\n";
echo "Total de Clientes Compradoras na Live 310: {$numClientes310}\n";
echo "Faturamento Total da Live 310: R$ " . number_format($valTotal310, 2, ',', '.') . "\n";
echo "Ticket Médio por Cliente na Live #310: R$ " . number_format($ticketMedio310, 2, ',', '.') . "\n";
echo "Média de Peças por Cliente: " . number_format($itensPorCliente310, 1, ',', '.') . " peças/cliente\n";
echo "Preço Médio por Peça na Live #310: R$ " . number_format($precoMedioPeca310, 2, ',', '.') . "\n";

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

echo "=== Auditando Tabelas de Pedidos e Itens Vendidos no Mês ===\n\n";

$inicioMes = Carbon::now()->startOfMonth()->toDateTimeString();
$fimMes    = Carbon::now()->endOfMonth()->toDateTimeString();

// 1. Tabela 'items' com pedido_id ou status 'vendido'
$colsItems = Schema::getColumnListing('items');
echo "Colunas de 'items': " . implode(', ', $colsItems) . "\n\n";

// Itens com status vendido atualizados no mês
$itensVendidosMes = DB::table('items')
    ->where('status', 'vendido')
    ->whereBetween('updated_at', [$inicioMes, $fimMes])
    ->count();

echo "1. Itens com status 'vendido' atualizados no mês (08/2026): {$itensVendidosMes} peças\n";

// Itens vinculados a pedidos criados este mês
if (in_array('pedido_id', $colsItems)) {
    $itensEmPedidosMes = DB::table('items')
        ->whereIn('pedido_id', function($q) use ($inicioMes, $fimMes) {
            $q->select('id')->from('pedidos')
              ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
              ->whereBetween('created_at', [$inicioMes, $fimMes]);
        })
        ->count();
    echo "2. Itens vinculados a pedidos validados deste mês (pedido_id em items): {$itensEmPedidosMes} peças\n";
}

// 2. Sacolinhas do mês
$sacolinhasPedVendidas = DB::table('sacolinhas')
    ->whereIn('status', ['pedido', 'vendido', 'fechado'])
    ->whereBetween('updated_at', [$inicioMes, $fimMes])
    ->sum('quantity');

echo "3. Sacolinhas convertidas em pedidos/vendidas este mês (sum quantity): {$sacolinhasPedVendidas} peças\n";

// 3. Tabela 'pedidos' - soma de itens se existir coluna total_itens ou similar
$colsPedidos = Schema::getColumnListing('pedidos');
echo "\nColunas de 'pedidos': " . implode(', ', $colsPedidos) . "\n";

$pedidosMesCount = DB::table('pedidos')
    ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
    ->whereBetween('created_at', [$inicioMes, $fimMes])
    ->count();

echo "Total de Pedidos Validados no mês: {$pedidosMesCount} pedidos\n";

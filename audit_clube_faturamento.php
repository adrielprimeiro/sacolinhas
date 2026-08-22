<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Auditando Clientes do Clube e Faturamento ===\n\n";

// 1. Colunas da tabela clientes / users
$colsClientes = Schema::getColumnListing('clientes');
echo "Colunas de 'clientes': " . implode(', ', $colsClientes) . "\n\n";

$colsUsers = Schema::getColumnListing('users');
echo "Colunas de 'users': " . implode(', ', $colsUsers) . "\n\n";

// Checar como clientes do clube são marcados nas tabelas
$clubeClientesCount = DB::table('clientes')
    ->where(function($q) use ($colsClientes) {
        if (in_array('is_clube', $colsClientes)) $q->orWhere('is_clube', 1);
        if (in_array('clube', $colsClientes)) $q->orWhere('clube', 1);
        if (in_array('clube_desapego', $colsClientes)) $q->orWhere('clube_desapego', 1);
        if (in_array('clube_subscriber', $colsClientes)) $q->orWhere('clube_subscriber', 1);
    })->count();

echo "Count Clientes do Clube (na tabela clientes): {$clubeClientesCount}\n";

// Checar na tabela users ou conta_corrente ou assinaturas se houver
if (Schema::hasTable('assinaturas')) {
    echo "Existe tabela 'assinaturas'\n";
}
if (Schema::hasTable('clube_assinantes')) {
    echo "Existe tabela 'clube_assinantes'\n";
}

// Check pedidos table columns & status
$colsPedidos = Schema::getColumnListing('pedidos');
echo "Colunas de 'pedidos': " . implode(', ', $colsPedidos) . "\n\n";

// Auditar faturamento total e divisão por clube
$fatTotal = DB::table('pedidos')
    ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
    ->sum('valor_total');

echo "Faturamento Total Acumulado nos Pedidos Validados: R$ " . number_format($fatTotal, 2, ',', '.') . "\n";

// Faturamento por user_id vinculando com clientes / users
// Vamos verificar como clientes do clube são associados

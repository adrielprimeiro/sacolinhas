<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Cliente;
use App\Models\User;

echo "=== Auditando Como os Clientes do Clube São Identificados ===\n\n";

// 1. Distinct valores de tipo_cliente
$tipos = DB::table('users')
    ->select('tipo_cliente', DB::raw('COUNT(*) as qtd'))
    ->groupBy('tipo_cliente')
    ->get();

echo "1. Valores distintos de 'tipo_cliente' na tabela 'users':\n";
foreach ($tipos as $t) {
    echo "   - '" . ($t->tipo_cliente ?? 'NULL') . "': {$t->qtd} usuários\n";
}

// 2. Clientes com avaliações enviadas (Clube de Desapego)
$usersComAvaliacoes = DB::table('avaliacoes')
    ->distinct('cliente_id')
    ->count('cliente_id');

echo "\n2. Usuários únicos com avaliações criadas (Clube de Desapego): {$usersComAvaliacoes}\n";

// 3. Clientes com créditos em conta_corrente
$usersComContaCorrente = DB::table('conta_corrente')
    ->distinct('cliente_id')
    ->count('cliente_id');

echo "3. Usuários únicos com movimentação em conta_corrente: {$usersComContaCorrente}\n";

// 4. Checar faturamento dos pedidos (pedidos.user_id) vinculados a cada grupo de clientes!
$faturamentoPorTipoCliente = DB::table('pedidos')
    ->join('users', 'pedidos.user_id', '=', 'users.id')
    ->whereNotIn('pedidos.status_pedido', ['cancelado', 'rascunho'])
    ->select('users.tipo_cliente', DB::raw('COUNT(pedidos.id) as qtd_pedidos'), DB::raw('SUM(pedidos.valor_total) as fat_total'))
    ->groupBy('users.tipo_cliente')
    ->get();

echo "\n4. Faturamento por 'tipo_cliente' nos pedidos validados:\n";
foreach ($faturamentoPorTipoCliente as $f) {
    echo "   - Tipo '" . ($f->tipo_cliente ?? 'NULL') . "': {$f->qtd_pedidos} pedidos | R$ " . number_format($f->fat_total, 2, ',', '.') . "\n";
}

// 5. Faturamento de clientes que já fizeram avaliações (Clube de Desapego) vs outros
$fatClientesComAvaliacao = DB::table('pedidos')
    ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
    ->whereIn('user_id', function($q) {
        $q->select('cliente_id')->from('avaliacoes');
    })
    ->sum('valor_total');

$fatOutrosClientes = DB::table('pedidos')
    ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
    ->whereNotIn('user_id', function($q) {
        $q->select('cliente_id')->from('avaliacoes');
    })
    ->sum('valor_total');

$fatTotalGeral = DB::table('pedidos')
    ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
    ->sum('valor_total');

echo "\n5. Faturamento por Clientes do Clube de Desapego (Fizeram Avaliação) vs Outros:\n";
echo "   - Clientes do Clube (Avaliaram Desapegos): R$ " . number_format($fatClientesComAvaliacao, 2, ',', '.') . " (" . ($fatTotalGeral > 0 ? round(($fatClientesComAvaliacao / $fatTotalGeral) * 100, 1) : 0) . "%)\n";
echo "   - Outros Clientes: R$ " . number_format($fatOutrosClientes, 2, ',', '.') . " (" . ($fatTotalGeral > 0 ? round(($fatOutrosClientes / $fatTotalGeral) * 100, 1) : 0) . "%)\n";
echo "   - Faturamento Total Acumulado nos Pedidos: R$ " . number_format($fatTotalGeral, 2, ',', '.') . "\n";

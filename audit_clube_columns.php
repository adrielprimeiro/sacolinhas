<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Auditando Colunas de Avaliações, Conta Corrente e Pontos ===\n\n";

$colsAvaliacoes = Schema::getColumnListing('avaliacoes');
echo "Colunas de 'avaliacoes': " . implode(', ', $colsAvaliacoes) . "\n\n";

$colsContaCorrente = Schema::getColumnListing('conta_corrente');
echo "Colunas de 'conta_corrente': " . implode(', ', $colsContaCorrente) . "\n\n";

// Count users in avaliacoes
$userColAvaliacoes = in_array('user_id', $colsAvaliacoes) ? 'user_id' : (in_array('cliente_id', $colsAvaliacoes) ? 'cliente_id' : null);
$userColCC = in_array('user_id', $colsContaCorrente) ? 'user_id' : (in_array('cliente_id', $colsContaCorrente) ? 'cliente_id' : null);

echo "userColAvaliacoes: {$userColAvaliacoes}\n";
echo "userColCC: {$userColCC}\n\n";

// Auditar faturamento por clientes do clube
// Opção A: Clientes do Clube = quem tem avaliações / conta corrente ou tipo_cliente em ('1', 'vip', 'clube') ou sacolinhas
$usersClubeIds = DB::table('avaliacoes')->distinct($userColAvaliacoes)->pluck($userColAvaliacoes)->filter()->toArray();

$usersContaCorrenteIds = DB::table('conta_corrente')->distinct($userColCC)->pluck($userColCC)->filter()->toArray();

$usersClubeTodos = array_unique(array_merge($usersClubeIds, $usersContaCorrenteIds));

echo "Usuários do Clube (Avaliações ou Crédito Conta Corrente): " . count($usersClubeTodos) . " clientes\n\n";

$fatClube = DB::table('pedidos')
    ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
    ->whereIn('user_id', $usersClubeTodos)
    ->sum('valor_total');

$fatOutros = DB::table('pedidos')
    ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
    ->whereNotIn('user_id', $usersClubeTodos)
    ->sum('valor_total');

$fatTotal = DB::table('pedidos')
    ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
    ->sum('valor_total');

echo "--- FATURAMENTO DOS PEDIDOS VALIDADOS --- \n";
echo "Faturamento Clientes do Clube: R$ " . number_format($fatClube, 2, ',', '.') . " (" . ($fatTotal > 0 ? round(($fatClube / $fatTotal) * 100, 1) : 0) . "%)\n";
echo "Faturamento Outros Clientes: R$ " . number_format($fatOutros, 2, ',', '.') . " (" . ($fatTotal > 0 ? round(($fatOutros / $fatTotal) * 100, 1) : 0) . "%)\n";
echo "Faturamento Total: R$ " . number_format($fatTotal, 2, ',', '.') . "\n";

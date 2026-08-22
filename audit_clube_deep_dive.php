<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

echo "=== Deep Dive Audit: Clientes, Clube e Pedidos do Mês (08/2026) ===\n\n";

$inicioMes = Carbon::now()->startOfMonth()->toDateTimeString();
$fimMes    = Carbon::now()->endOfMonth()->toDateTimeString();

// 1. Pedidos do Mês Vigente (08/2026)
$pedidosMes = DB::table('pedidos')
    ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
    ->whereBetween('created_at', [$inicioMes, $fimMes])
    ->get(['id', 'numero_pedido', 'user_id', 'valor_total', 'created_at', 'origem_pedido']);

echo "1. Pedidos Validados no Mês Vigente (08/2026): " . $pedidosMes->count() . " pedidos | Total: R$ " . number_format($pedidosMes->sum('valor_total'), 2, ',', '.') . "\n\n";

$userIDsMes = $pedidosMes->pluck('user_id')->unique()->filter()->values()->toArray();
echo "Usuários Únicos que Compraram Este Mês: " . count($userIDsMes) . " clientes únicos\n\n";

// Listar os usuários que compraram no mês e seus atributos na tabela users
$usersMes = DB::table('users')
    ->whereIn('id', $userIDsMes)
    ->get(['id', 'name', 'email', 'telefone_principal', 'tipo_cliente', 'pontos_creditados', 'created_at']);

echo "Detalhes dos Clientes que Compraram Este Mês:\n";
foreach ($usersMes as $u) {
    $pedidosUser = $pedidosMes->where('user_id', $u->id);
    $totalGasto = $pedidosUser->sum('valor_total');
    $hasAvaliacoes = DB::table('avaliacoes')->where('user_id', $u->id)->exists();
    $hasContaCorrente = DB::table('conta_corrente')->where('user_id', $u->id)->exists();

    echo "   - ID {$u->id} | {$u->name} | tipo_cliente: '{$u->tipo_cliente}' | Avaliações: " . ($hasAvaliacoes ? 'SIM' : 'NÃO') . " | ContaCorrente: " . ($hasContaCorrente ? 'SIM' : 'NÃO') . " | Total Gasto no Mês: R$ " . number_format($totalGasto, 2, ',', '.') . "\n";
}

// 2. Procurar tabelas ou colunas relacionadas a Clube nas tabelas do banco
$tables = DB::select('SHOW TABLES');
$dbName = DB::getDatabaseName();
$tableKey = "Tables_in_{$dbName}";

echo "\n2. Tabelas do Banco de Dados Relacionadas a Clube / Assinaturas / Pontos:\n";
foreach ($tables as $t) {
    $name = $t->$tableKey;
    if (str_contains($name, 'clube') || str_contains($name, 'assin') || str_contains($name, 'pontos') || str_contains($name, 'nivel') || str_contains($name, 'plano')) {
        echo "   - Tabela encontrada: '{$name}' (" . DB::table($name)->count() . " registros)\n";
    }
}

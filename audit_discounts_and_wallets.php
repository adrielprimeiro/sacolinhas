<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Auditando Conta Corrente / Carteiras com centavos ===\n\n";

$ccCents = DB::table('conta_corrente')
    ->whereRaw("MOD(saldo_atual, 1) != 0 OR MOD(valor, 1) != 0")
    ->orderByDesc('id')
    ->limit(20)
    ->get();

echo "Linhas na conta corrente com centavos: " . $ccCents->count() . "\n";
foreach ($ccCents as $c) {
    echo "ID: {$c->id} | UserID: {$c->user_id} | Valor: {$c->valor} | SaldoAtual: {$c->saldo_atual} | Desc: {$c->descricao}\n";
}

echo "\n=== Auditando Limites de Clientes com centavos ===\n";
$limCents = DB::table('cliente_limites')
    ->whereRaw("MOD(limite_disponivel, 1) != 0 OR MOD(limite_utilizado, 1) != 0 OR MOD(limite_concedido, 1) != 0")
    ->limit(20)
    ->get();

echo "Limites com centavos: " . $limCents->count() . "\n";
foreach ($limCents as $l) {
    echo "UserID: {$l->user_id} | Concedido: {$l->limite_concedido} | Utilizado: {$l->limite_utilizado} | Disponível: {$l->limite_disponivel}\n";
}

echo "\n=== Verificando a soma de Sacolinhas da Live 310 agrupadas por Status ===\n";

$statusSums = DB::table('sacolinhas')
    ->where('live_id', 310)
    ->select('status', DB::raw('COUNT(*) as total_items'), DB::raw('SUM(price * quantity) as total_val'))
    ->groupBy('status')
    ->get();

foreach ($statusSums as $ss) {
    echo "Status '{$ss->status}': {$ss->total_items} itens | Soma: R$ " . number_format($ss->total_val, 2, ',', '.') . "\n";
}

echo "\n=== Verificando combinações de status (ex: 'live' + 'em analise', etc.) ===\n";
$liveOnly = DB::table('sacolinhas')->where('live_id', 310)->where('status', 'live')->sum(DB::raw('price * quantity'));
$emAnaliseOnly = DB::table('sacolinhas')->where('live_id', 310)->where('status', 'em analise')->sum(DB::raw('price * quantity'));
$sacolinhaOnly = DB::table('sacolinhas')->where('live_id', 310)->where('status', 'sacolinha')->sum(DB::raw('price * quantity'));

echo "Somente 'live': R$ " . number_format($liveOnly, 2, ',', '.') . "\n";
echo "Somente 'em analise': R$ " . number_format($emAnaliseOnly, 2, ',', '.') . "\n";
echo "Somente 'sacolinha': R$ " . number_format($sacolinhaOnly, 2, ',', '.') . "\n";
echo "'live' + 'em analise': R$ " . number_format($liveOnly + $emAnaliseOnly, 2, ',', '.') . "\n";

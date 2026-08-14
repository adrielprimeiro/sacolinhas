<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

$subQueryMaxDate = DB::table('conta_corrente')
    ->select('user_id', DB::raw('MAX(data_movimentacao) as max_date'))
    ->groupBy('user_id');

$subQueryMaxId = DB::table('conta_corrente as cc')
    ->joinSub($subQueryMaxDate, 'tm', function($join) {
        $join->on('cc.user_id', '=', 'tm.user_id')
             ->on('cc.data_movimentacao', '=', 'tm.max_date');
    })
    ->select('cc.user_id', DB::raw('MAX(cc.id) as max_id'))
    ->groupBy('cc.user_id');

$latestSaldos = DB::table('conta_corrente as cc')
    ->joinSub($subQueryMaxId, 'mi', function($join) {
        $join->on('cc.id', '=', 'mi.max_id');
    })
    ->select('cc.user_id', 'cc.saldo_atual', 'cc.data_movimentacao', 'cc.id as last_cc_id')
    ->get();

echo "Total de clientes com historico em conta_corrente: " . $latestSaldos->count() . "\n";
echo "Soma total dos saldos atuais no Portal: R$ " . number_format($latestSaldos->sum('saldo_atual'), 2, ',', '.') . "\n\n";

$positivos = $latestSaldos->where('saldo_atual', '>', 0);
$negativos = $latestSaldos->where('saldo_atual', '<', 0);
$zeros     = $latestSaldos->where('saldo_atual', '==', 0);

echo "Clientes com saldo POSITIVO: " . $positivos->count() . " (Soma: R$ " . number_format($positivos->sum('saldo_atual'), 2, ',', '.') . ")\n";
echo "Clientes com saldo ZERO: " . $zeros->count() . "\n";
echo "Clientes com saldo NEGATIVO: " . $negativos->count() . " (Soma: R$ " . number_format($negativos->sum('saldo_atual'), 2, ',', '.') . ")\n\n";

echo "=== Top 15 Clientes com Maior Saldo NEGATIVO ===\n";
foreach ($negativos->sortBy('saldo_atual')->take(15) as $row) {
    $u = User::find($row->user_id);
    $nome = $u ? $u->name : "User #{$row->user_id}";
    echo "User #{$row->user_id} - {$nome}: Saldo R$ " . number_format($row->saldo_atual, 2, ',', '.') . " (Ultima mov em {$row->data_movimentacao})\n";
}

echo "\n=== Top 15 Clientes com Maior Saldo POSITIVO ===\n";
foreach ($positivos->sortByDesc('saldo_atual')->take(15) as $row) {
    $u = User::find($row->user_id);
    $nome = $u ? $u->name : "User #{$row->user_id}";
    echo "User #{$row->user_id} - {$nome}: Saldo R$ " . number_format($row->saldo_atual, 2, ',', '.') . " (Ultima mov em {$row->data_movimentacao})\n";
}

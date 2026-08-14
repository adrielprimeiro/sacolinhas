<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ContaCorrente;
use App\Models\User;
use Illuminate\Support\Facades\DB;

$excludedClassIds = [34, 40, 21, 35, 26, 27, 37, 4, 9, 83];

DB::transaction(function() use ($excludedClassIds) {
    echo "=== 1. Removendo registros de despesas operacionais da conta_corrente ===\n";
    $deletedCount = ContaCorrente::whereIn('classificacao_id', $excludedClassIds)->delete();
    echo "Removidos {$deletedCount} registros indevidos de conta_corrente.\n\n";

    echo "=== 2. Recalculando saldos acumulados de todos os usuarios em conta_corrente ===\n";
    $userIds = ContaCorrente::distinct()->pluck('user_id');

    foreach ($userIds as $uid) {
        $records = ContaCorrente::where('user_id', $uid)
            ->orderBy('data_movimentacao')
            ->orderBy('id')
            ->get();

        $saldoAcumulado = 0.0;
        foreach ($records as $r) {
            if ($r->tipo_movimentacao === 'credito') {
                $saldoAcumulado += (float) $r->valor;
            } else {
                $saldoAcumulado -= (float) $r->valor;
            }
            DB::table('conta_corrente')
                ->where('id', $r->id)
                ->update(['saldo_atual' => round($saldoAcumulado, 2)]);
        }
    }
    echo "Saldos recalculados com sucesso para " . $userIds->count() . " usuarios!\n";
});

// Posição consolidada final da conta "Carteira Cliente"
$carteiraConta = \App\Models\ContaBancaria::where('nome', 'like', '%Carteira%')->first();
$saldoFinal = $carteiraConta ? $carteiraConta->saldo_atual : 0;

echo "\n=== POSICAO FINAL CONSOLIDADA DA CARTEIRA CLIENTE ===\n";
echo "Saldo Consolidado da Carteira Cliente: R$ " . number_format($saldoFinal, 2, ',', '.') . "\n";

echo "\nVerificando saldos dos colaboradores:\n";
foreach ([227, 2, 4] as $uid) {
    $u = User::find($uid);
    $lastCC = ContaCorrente::where('user_id', $uid)->orderByDesc('id')->first();
    $saldoU = $lastCC ? $lastCC->saldo_atual : 0;
    echo " - User #{$uid} (" . ($u ? $u->name : '') . "): R$ " . number_format($saldoU, 2, ',', '.') . "\n";
}

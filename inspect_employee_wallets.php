<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ContaCorrente;
use App\Models\User;
use Illuminate\Support\Facades\DB;

$userIds = [227, 4, 2];

foreach ($userIds as $uid) {
    $user = User::find($uid);
    echo "=========================================================\n";
    echo "USER #{$uid} - " . ($user ? $user->name : "N/A") . "\n";
    echo "=========================================================\n";

    $ccEntries = ContaCorrente::where('user_id', $uid)
        ->orderBy('data_movimentacao')
        ->orderBy('id')
        ->get();

    echo "Total de registros na carteira: " . $ccEntries->count() . "\n";

    $byClass = [];
    foreach ($ccEntries as $cc) {
        $classId = $cc->classificacao_id;
        $classObj = \App\Models\ClassificacaoFinanceira::find($classId);
        $className = $classObj ? $classObj->nome : "Sem Categoria (ID {$classId})";

        if (!isset($byClass[$className])) {
            $byClass[$className] = ['credito' => 0, 'debito' => 0, 'count' => 0];
        }
        if ($cc->tipo_movimentacao === 'credito') {
            $byClass[$className]['credito'] += (float)$cc->valor;
        } else {
            $byClass[$className]['debito'] += (float)$cc->valor;
        }
        $byClass[$className]['count']++;
    }

    echo "\nResumo por Categoria/Classificacao na Carteira:\n";
    foreach ($byClass as $cName => $info) {
        echo " - {$cName}: Credito R$ " . number_format($info['credito'], 2, ',', '.') . " | Debito R$ " . number_format($info['debito'], 2, ',', '.') . " ({$info['count']} registros)\n";
    }

    echo "\nUltimos 10 registros na carteira:\n";
    foreach ($ccEntries->take(-10) as $cc) {
        echo "  #{$cc->id} | {$cc->data_movimentacao} | Tipo: {$cc->tipo_movimentacao} | Valor: R$ {$cc->valor} | SaldoResultante: R$ {$cc->saldo_atual} | Desc: {$cc->descricao} | RefType: {$cc->referencia_tipo} | RefID: {$cc->referencia_id}\n";
    }
    echo "\n";
}

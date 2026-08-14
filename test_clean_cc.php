<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ContaCorrente;
use App\Models\ClassificacaoFinanceira;
use Illuminate\Support\Facades\DB;

// IDs ou codigos de classificacoes que sao DESPESAS OPERACIONAIS DA EMPRESA (nao compras/creditos de cliente)
// ex: Funcionarios (34), Pro-labore (40), Logistica (21), Cursos (35), Energia (26), Telefonia (27), Taxas MP (37), Despesas (4), Despesas Marketing (9), Anuncios (83)

$excludedClassIds = [34, 40, 21, 35, 26, 27, 37, 4, 9, 83];

echo "=== Simulando limpeza de lancamentos operacionais de conta_corrente ===\n\n";

$entriesToIgnore = ContaCorrente::whereIn('classificacao_id', $excludedClassIds)->get();

echo "Encontrados " . $entriesToIgnore->count() . " registros operacionais a ignorar na carteira:\n";
foreach ($entriesToIgnore as $e) {
    $classObj = ClassificacaoFinanceira::find($e->classificacao_id);
    $cName = $classObj ? $classObj->nome : "ID {$e->classificacao_id}";
    echo "  - CC #{$e->id} | User #{$e->user_id} | Data: {$e->data_movimentacao} | Categoria: {$cName} | Tipo: {$e->tipo_movimentacao} | Valor: R$ {$e->valor} | Desc: {$e->descricao}\n";
}

echo "\nCalculando saldos dos usuarios se esses registros forem removidos...\n";

// Pegar todos os user_id que possuem registros validos
$users = ContaCorrente::whereNotIn('classificacao_id', $excludedClassIds)
    ->pluck('user_id')
    ->unique();

$totalPositivo = 0;
$totalNegativo = 0;

foreach ($users as $uid) {
    $records = ContaCorrente::where('user_id', $uid)
        ->whereNotIn('classificacao_id', $excludedClassIds)
        ->orderBy('data_movimentacao')
        ->orderBy('id')
        ->get();

    $cred = $records->where('tipo_movimentacao', 'credito')->sum('valor');
    $deb  = $records->where('tipo_movimentacao', 'debito')->sum('valor');
    $saldo = $cred - $deb;

    if ($saldo > 0) $totalPositivo += $saldo;
    if ($saldo < 0) $totalNegativo += $saldo;

    if (in_array($uid, [227, 2, 4])) {
        $u = \App\Models\User::find($uid);
        echo "User #{$uid} - " . ($u ? $u->name : '') . ": Novo Saldo Calculado R$ " . number_format($saldo, 2, ',', '.') . "\n";
    }
}

$saldoConsolidadoNovo = $totalPositivo + $totalNegativo;
echo "\nNovo Saldo Positivo Total: R$ " . number_format($totalPositivo, 2, ',', '.') . "\n";
echo "Novo Saldo Negativo Total: R$ " . number_format($totalNegativo, 2, ',', '.') . "\n";
echo "Novo Saldo Consolidado da Carteira Cliente: R$ " . number_format($saldoConsolidadoNovo, 2, ',', '.') . "\n";

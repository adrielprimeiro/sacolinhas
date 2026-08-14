<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ContaCorrente;
use App\Models\ClassificacaoFinanceira;
use Illuminate\Support\Facades\DB;

$allCC = ContaCorrente::select('classificacao_id', 'tipo_movimentacao', DB::raw('SUM(valor) as total_valor'), DB::raw('COUNT(*) as qtd'))
    ->groupBy('classificacao_id', 'tipo_movimentacao')
    ->get();

echo "=== Categorias presentes em conta_corrente ===\n\n";

foreach ($allCC as $cc) {
    $classObj = ClassificacaoFinanceira::find($cc->classificacao_id);
    $cName = $classObj ? "{$classObj->nome} (código: {$classObj->codigo_contabil})" : "Sem Categoria / Nulo (ID {$cc->classificacao_id})";
    echo "Classificacao ID {$cc->classificacao_id}: {$cName}\n";
    echo "   TipoMov: {$cc->tipo_movimentacao} | Qtd: {$cc->qtd} | Total: R$ " . number_format($cc->total_valor, 2, ',', '.') . "\n";
    echo "---------------------------------------------------------\n";
}

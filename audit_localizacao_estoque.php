<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Auditando Locais Físicos de Estoque (coluna localizacao) ===\n\n";

$locais = DB::table('items')
    ->select('localizacao', DB::raw('COUNT(*) as qtd'), DB::raw('SUM(preco) as valor_total'))
    ->whereNotNull('localizacao')
    ->where('localizacao', '!=', '')
    ->groupBy('localizacao')
    ->orderBy('localizacao', 'asc')
    ->get();

echo "Total de Locais Físicos Distintos Encontrados: " . $locais->count() . "\n\n";

foreach ($locais as $loc) {
    echo "Local '{$loc->localizacao}': {$loc->qtd} peças | Valor Total Venda: R$ " . number_format($loc->valor_total, 2, ',', '.') . "\n";
}

$semLocal = DB::table('items')
    ->where(function($q) {
        $q->whereNull('localizacao')->orWhere('localizacao', '');
    })
    ->select(DB::raw('COUNT(*) as qtd'), DB::raw('SUM(preco) as valor_total'))
    ->first();

echo "\nItens Sem Localização Definida: {$semLocal->qtd} peças | Valor Total: R$ " . number_format($semLocal->valor_total, 2, ',', '.') . "\n";

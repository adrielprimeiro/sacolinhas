<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Item;
use Illuminate\Support\Facades\DB;

echo "=== Análise Completa do Preço Médio dos Itens ===\n\n";

// 1. Preço médio geral no cadastro de itens
$geral = DB::table('items')
    ->select(
        DB::raw('COUNT(*) as total_itens'),
        DB::raw('AVG(preco) as preco_medio'),
        DB::raw('MIN(preco) as preco_minimo'),
        DB::raw('MAX(preco) as preco_maximo'),
        DB::raw('AVG(custo) as custo_medio'),
        DB::raw('SUM(preco) as valor_total_estoque')
    )
    ->first();

echo "--- 1. VISÃO GERAL DE TODOS OS ITENS CADASTRADOS ---\n";
echo "Total de Itens no Banco: " . number_format($geral->total_itens, 0, ',', '.') . " itens\n";
echo "Preço Médio por Item: R$ " . number_format($geral->preco_medio, 2, ',', '.') . "\n";
echo "Custo Médio por Item: R$ " . number_format($geral->custo_medio ?? 0, 2, ',', '.') . "\n";
echo "Menor Preço Cadastrado: R$ " . number_format($geral->preco_minimo, 2, ',', '.') . "\n";
echo "Maior Preço Cadastrado: R$ " . number_format($geral->preco_maximo, 2, ',', '.') . "\n";
echo "Valor Total do Catálogo (Preço): R$ " . number_format($geral->valor_total_estoque, 2, ',', '.') . "\n\n";

// 2. Preço médio por STATUS dos itens
$porStatus = DB::table('items')
    ->select('status', DB::raw('COUNT(*) as qtd'), DB::raw('AVG(preco) as preco_medio'), DB::raw('SUM(preco) as valor_total'))
    ->groupBy('status')
    ->orderByDesc('qtd')
    ->get();

echo "--- 2. PREÇO MÉDIO POR STATUS DO ITEM ---\n";
foreach ($porStatus as $st) {
    echo "Status '" . ($st->status ?: 'Sem Status') . "': " . number_format($st->qtd, 0, ',', '.') . " itens | Preço Médio: R$ " . number_format($st->preco_medio, 2, ',', '.') . " | Valor Total: R$ " . number_format($st->valor_total, 2, ',', '.') . "\n";
}

echo "\n--- 3. PREÇO MÉDIO POR ESTADO (Novo vs Usado/Desapego) ---\n";
$porEstado = DB::table('items')
    ->select('estado', DB::raw('COUNT(*) as qtd'), DB::raw('AVG(preco) as preco_medio'))
    ->groupBy('estado')
    ->get();

foreach ($porEstado as $est) {
    echo "Estado '" . ($est->estado ?: 'Não especificado') . "': " . number_format($est->qtd, 0, ',', '.') . " itens | Preço Médio: R$ " . number_format($est->preco_medio, 2, ',', '.') . "\n";
}

echo "\n--- 4. FAIXAS DE PREÇO DOS ITENS ---\n";
$faixas = DB::select("
    SELECT 
        CASE 
            WHEN preco < 15 THEN 'Até R$ 14,99'
            WHEN preco BETWEEN 15 AND 29.99 THEN 'R$ 15,00 a R$ 29,99'
            WHEN preco BETWEEN 30 AND 49.99 THEN 'R$ 30,00 a R$ 49,99'
            WHEN preco BETWEEN 50 AND 99.99 THEN 'R$ 50,00 a R$ 99,99'
            ELSE 'R$ 100,00 ou mais'
        END as faixa,
        COUNT(*) as qtd,
        AVG(preco) as preco_medio
    FROM items
    GROUP BY faixa
    ORDER BY MIN(preco) ASC
");

foreach ($faixas as $f) {
    echo "Faixa {$f->faixa}: " . number_format($f->qtd, 0, ',', '.') . " itens | Média na faixa: R$ " . number_format($f->preco_medio, 2, ',', '.') . "\n";
}

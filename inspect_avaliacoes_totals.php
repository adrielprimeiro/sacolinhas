<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Análise Completa de Avaliações de Desapego ===\n\n";

// 1. Tabela avaliacoes
$summary = DB::table('avaliacoes')
    ->select(
        DB::raw('COUNT(*) as total_avaliacoes'),
        DB::raw('SUM(total_payout) as total_payout_pago'),
        DB::raw('SUM(total_venda) as total_venda_projetado'),
        DB::raw('SUM(frete) as total_frete')
    )
    ->first();

echo "--- 1. RESUMO DA TABELA 'avaliacoes' ---\n";
echo "Total de Registros de Avaliações: " . number_format($summary->total_avaliacoes, 0, ',', '.') . "\n";
echo "Custo Total Pago/Creditado (total_payout): R$ " . number_format($summary->total_payout_pago, 2, ',', '.') . "\n";
echo "Valor Total Estimado de Venda (total_venda): R$ " . number_format($summary->total_venda_projetado, 2, ',', '.') . "\n";
echo "Total de Fretes das Avaliações: R$ " . number_format($summary->total_frete, 2, ',', '.') . "\n\n";

// 2. Tabela avaliacao_items (quantidade total de itens e custos)
if (Schema::hasTable('avaliacao_items')) {
    echo "--- 2. RESUMO DA TABELA 'avaliacao_items' ---\n";
    $colsAi = Schema::getColumnListing('avaliacao_items');
    echo "Colunas: " . implode(', ', $colsAi) . "\n";

    $totalItens = DB::table('avaliacao_items')->count();
    $somaCusto  = DB::table('avaliacao_items')->sum('valor_payout') ?? DB::table('avaliacao_items')->sum('custo') ?? 0;
    $somaPreco  = DB::table('avaliacao_items')->sum('preco_venda') ?? DB::table('avaliacao_items')->sum('preco') ?? 0;

    echo "Quantidade Total de Itens Avaliados em 'avaliacao_items': " . number_format($totalItens, 0, ',', '.') . " itens\n";
    if ($somaCusto > 0) {
        echo "Soma do Custo/Payout em 'avaliacao_items': R$ " . number_format($somaCusto, 2, ',', '.') . "\n";
    }
    if ($somaPreco > 0) {
        echo "Soma do Preço de Venda em 'avaliacao_items': R$ " . number_format($somaPreco, 2, ',', '.') . "\n";
    }
}

// 3. Verificação de itens cadastrados no estoque vinculados a avaliações (ou de lote)
$itensAvaliacao = DB::table('items')
    ->whereNotNull('custo')
    ->where('custo', '>', 0)
    ->select(
        DB::raw('COUNT(*) as total_itens_com_custo'),
        DB::raw('SUM(custo) as custo_total_estoque'),
        DB::raw('SUM(preco) as preco_total_estoque')
    )
    ->first();

echo "\n--- 3. VISÃO DO ESTOQUE COM CUSTO REGISTRADO ---\n";
echo "Itens Cadastrados no Estoque com Custo Preenchido: " . number_format($itensAvaliacao->total_itens_com_custo, 0, ',', '.') . " itens\n";
echo "Custo Total do Estoque: R$ " . number_format($itensAvaliacao->custo_total_estoque, 2, ',', '.') . "\n";
echo "Preço de Venda Total do Estoque: R$ " . number_format($itensAvaliacao->preco_total_estoque, 2, ',', '.') . "\n";

// 4. Detalhamento por Status das Avaliações
$porStatusAval = DB::table('avaliacoes')
    ->select('status', DB::raw('COUNT(*) as qtd'), DB::raw('SUM(total_payout) as total_payout'), DB::raw('SUM(total_venda) as total_venda'))
    ->groupBy('status')
    ->get();

echo "\n--- 4. DETALHAMENTO POR STATUS DA AVALIAÇÃO ---\n";
foreach ($porStatusAval as $sa) {
    echo "Status '" . ($sa->status ?: 'N/A') . "': {$sa->qtd} avaliações | Custo Payout: R$ " . number_format($sa->total_payout, 2, ',', '.') . " | Venda Prevista: R$ " . number_format($sa->total_venda, 2, ',', '.') . "\n";
}

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

echo "--- 1. REGISTROS DA TABELA 'avaliacoes' (Lotes Fechados) ---\n";
echo "Total de Avaliações Cadastradas: " . number_format($summary->total_avaliacoes, 0, ',', '.') . "\n";
echo "Custo Total Pago/Creditado (total_payout): R$ " . number_format($summary->total_payout_pago, 2, ',', '.') . "\n";
echo "Valor Total Projetado de Venda (total_venda): R$ " . number_format($summary->total_venda_projetado, 2, ',', '.') . "\n";
echo "Total de Fretes das Avaliações: R$ " . number_format($summary->total_frete, 2, ',', '.') . "\n\n";

// 2. Tabela avaliacao_items (quantidade total de itens e custos)
if (Schema::hasTable('avaliacao_items')) {
    echo "--- 2. DETALHAMENTO DOS ITENS EM 'avaliacao_items' ---\n";
    $totalItens = DB::table('avaliacao_items')->count();
    $somaPayoutCredito = DB::table('avaliacao_items')->sum('payout_credito');
    $somaPayoutDinheiro = DB::table('avaliacao_items')->sum('payout_dinheiro');
    $somaPrecoVenda = DB::table('avaliacao_items')->sum('preco_venda');

    echo "Quantidade Total de Itens Avaliados: " . number_format($totalItens, 0, ',', '.') . " peças\n";
    echo "Soma Payout em Crédito na Carteira: R$ " . number_format($somaPayoutCredito, 2, ',', '.') . "\n";
    echo "Soma Payout em Dinheiro/Pix: R$ " . number_format($somaPayoutDinheiro, 2, ',', '.') . "\n";
    echo "Soma Payout Total dos Itens: R$ " . number_format($somaPayoutCredito + $somaPayoutDinheiro, 2, ',', '.') . "\n";
    echo "Soma Preço de Venda Projetado dos Itens: R$ " . number_format($somaPrecoVenda, 2, ',', '.') . "\n\n";
}

// 3. Extrato da Conta Corrente (Créditos por Avaliação concedidos às clientes)
$ccAvaliacao = DB::table('conta_corrente')
    ->where('descricao', 'like', '%Avalia%')
    ->select(
        DB::raw('COUNT(*) as qtd_lancamentos'),
        DB::raw('SUM(valor) as total_credito_lancado')
    )
    ->first();

echo "--- 3. CRÉDITOS DE AVALIAÇÃO LANÇADOS NA CARTEIRA DAS CLIENTES (Histórico Completo) ---\n";
echo "Total de Lançamentos na Carteira: " . number_format($ccAvaliacao->qtd_lancamentos, 0, ',', '.') . " lançamentos\n";
echo "Total Creditado em Carteira por Avaliações: R$ " . number_format($ccAvaliacao->total_credito_lancado, 2, ',', '.') . "\n\n";

// 4. Detalhamento por Status das Avaliações
$porStatusAval = DB::table('avaliacoes')
    ->select('status', DB::raw('COUNT(*) as qtd'), DB::raw('SUM(total_payout) as total_payout'), DB::raw('SUM(total_venda) as total_venda'))
    ->groupBy('status')
    ->get();

echo "--- 4. DETALHAMENTO POR STATUS DA AVALIAÇÃO ---\n";
foreach ($porStatusAval as $sa) {
    echo "Status '" . ($sa->status ?: 'Sem Status') . "': {$sa->qtd} avaliações | Custo Payout: R$ " . number_format($sa->total_payout, 2, ',', '.') . " | Venda Prevista: R$ " . number_format($sa->total_venda, 2, ',', '.') . "\n";
}

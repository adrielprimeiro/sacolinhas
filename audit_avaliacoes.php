<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Auditando Avaliações de Desapego ===\n\n";

$tables = DB::select('SHOW TABLES');
$dbName = env('DB_DATABASE', 'sacolinhas');
$colKey = "Tables_in_" . $dbName;

$hasAvaliacoes = false;
foreach ($tables as $tObj) {
    $t = $tObj->$colKey;
    if (str_contains($t, 'avaliac')) {
        echo "Tabela de avaliação encontrada: '{$t}'\n";
        $hasAvaliacoes = true;
    }
}

if (Schema::hasTable('avaliacoes')) {
    echo "\n--- Resumo da tabela 'avaliacoes' ---\n";
    $cols = Schema::getColumnListing('avaliacoes');
    print_r($cols);

    $totalAvaliacoes = DB::table('avaliacoes')->count();
    
    $valCol = in_array('valor_total', $cols) ? 'valor_total' : (in_array('total', $cols) ? 'total' : (in_array('valor', $cols) ? 'valor' : null));
    $qtdCol = in_array('total_itens', $cols) ? 'total_itens' : (in_array('qtd_itens', $cols) ? 'qtd_itens' : (in_array('quantidade', $cols) ? 'quantidade' : null));

    echo "Total de Avaliações Registradas: {$totalAvaliacoes}\n";

    if ($valCol) {
        $somaValor = DB::table('avaliacoes')->sum($valCol);
        echo "Valor Total pago/creditado nas avaliações: R$ " . number_format($somaValor, 2, ',', '.') . "\n";
    }

    if ($qtdCol) {
        $somaItens = DB::table('avaliacoes')->sum($qtdCol);
        echo "Quantidade Total de Itens Avaliados: " . number_format($somaItens, 0, ',', '.') . " peças\n";
    }
}

// Verificar se existe tabela de itens de avaliação (ex: avaliacao_itens ou avaliacoes_itens ou items)
if (Schema::hasTable('avaliacao_itens')) {
    $totalItensTabela = DB::table('avaliacao_itens')->count();
    $somaValorItens = DB::table('avaliacao_itens')->sum('valor') ?? DB::table('avaliacao_itens')->sum('preco') ?? 0;
    echo "\n--- Resumo da tabela 'avaliacao_itens' ---\n";
    echo "Total de linhas em avaliacao_itens: {$totalItensTabela}\n";
    echo "Soma de valor em avaliacao_itens: R$ " . number_format($somaValorItens, 2, ',', '.') . "\n";
}

// Verificar movimentações na conta corrente vinculadas a Avaliação
$ccAvaliacao = DB::table('conta_corrente')
    ->where('descricao', 'like', '%Avalia%')
    ->select(DB::raw('COUNT(*) as qtd'), DB::raw('SUM(valor) as total_creditado'))
    ->first();

echo "\n--- Créditos por Avaliação lançados na Conta Corrente das Clientes ---\n";
echo "Total de lançamentos de avaliação na carteira: {$ccAvaliacao->qtd}\n";
echo "Total creditado nas carteiras por avaliações: R$ " . number_format($ccAvaliacao->total_creditado ?? 0, 2, ',', '.') . "\n";

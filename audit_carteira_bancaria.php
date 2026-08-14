<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ContaBancaria;
use App\Models\Movimentacao;
use App\Models\Lancamento;
use App\Models\ContaCorrente;
use Illuminate\Support\Facades\DB;

$contas = ContaBancaria::all();
echo "=== Contas Bancarias ===\n";
foreach ($contas as $c) {
    echo "ID: {$c->id} | Nome: {$c->nome} | Saldo Inicial: {$c->saldo_inicial} | Saldo Atual: {$c->saldo_atual}\n";
}

$carteiraConta = ContaBancaria::where('nome', 'like', '%Carteira%')->first();
if (!$carteiraConta) {
    echo "Conta de Carteira nao encontrada por nome.\n";
    exit;
}

echo "\n=== Detalhes da Conta '{$carteiraConta->nome}' (ID {$carteiraConta->id}) ===\n";
echo "Saldo Inicial: R$ {$carteiraConta->saldo_inicial}\n";
echo "Saldo Atual no banco de dados: R$ {$carteiraConta->saldo_atual}\n";

$movs = Movimentacao::where('conta_bancaria_id', $carteiraConta->id)
    ->with('lancamento')
    ->get();

echo "\nTotal de movimentacoes registradas na conta bancaria Carteira Cliente (ID {$carteiraConta->id}): " . $movs->count() . "\n";

$somaReceitas = 0;
$somaDespesas = 0;

$byCategory = [];

foreach ($movs as $m) {
    $l = $m->lancamento;
    $tipo = $l ? $l->tipo : 'receita';
    $valor = (float) $m->valor_pago;
    $cat = $l && $l->classificacaoFinanceira ? $l->classificacaoFinanceira->nome : 'Sem Categoria';

    if ($tipo === 'receita') {
        $somaReceitas += $valor;
    } else {
        $somaDespesas += $valor;
    }

    if (!isset($byCategory[$cat])) {
        $byCategory[$cat] = ['tipo' => $tipo, 'total' => 0, 'count' => 0];
    }
    $byCategory[$cat]['total'] += $valor;
    $byCategory[$cat]['count']++;
}

echo "\nSoma de Entradas/Receitas na Conta Carteira Cliente: R$ " . number_format($somaReceitas, 2, ',', '.') . "\n";
echo "Soma de Saidas/Despesas na Conta Carteira Cliente: R$ " . number_format($somaDespesas, 2, ',', '.') . "\n";
echo "Resultado Calculado (Inicial + Entradas - Saidas): R$ " . number_format($carteiraConta->saldo_inicial + $somaReceitas - $somaDespesas, 2, ',', '.') . "\n\n";

echo "=== Resumo por Categoria/Classificacao na Conta Carteira Cliente ===\n";
foreach ($byCategory as $cat => $info) {
    echo " - [{$info['tipo']}] {$cat}: R$ " . number_format($info['total'], 2, ',', '.') . " ({$info['count']} movimentacoes)\n";
}

echo "\n=== Comparando com a Tabela conta_corrente (Extrato de Carteiras dos Clientes no Portal) ===\n";
$creditosPortal = ContaCorrente::where('tipo_movimentacao', 'credito')->sum('valor');
$debitosPortal = ContaCorrente::where('tipo_movimentacao', 'debito')->sum('valor');
$saldoPortal = $creditosPortal - $debitosPortal;

echo "Total Creditos aos Clientes no Portal: R$ " . number_format($creditosPortal, 2, ',', '.') . "\n";
echo "Total Debitos dos Clientes no Portal: R$ " . number_format($debitosPortal, 2, ',', '.') . "\n";
echo "Saldo Consolidade dos Clientes no Portal: R$ " . number_format($saldoPortal, 2, ',', '.') . "\n";

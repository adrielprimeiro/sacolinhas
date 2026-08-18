<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ContaBancaria;
use App\Models\Movimentacao;
use App\Models\TransacaoExtrato;

echo "=== Auditando Saldo do Mercado Pago ===\n\n";

$contaMp = ContaBancaria::where('nome', 'like', '%Mercado%Pago%')->first();

if (!$contaMp) {
    echo "Conta do Mercado Pago não encontrada!\n";
    exit;
}

echo "ID Conta: {$contaMp->id}\n";
echo "Nome Conta: {$contaMp->nome}\n";
echo "Saldo Inicial no Banco de Dados: R$ {$contaMp->saldo_inicial}\n";

$entradas = Movimentacao::join('lancamentos', 'movimentacoes.lancamento_id', '=', 'lancamentos.id')
    ->where('movimentacoes.conta_bancaria_id', $contaMp->id)
    ->where('lancamentos.tipo', 'receita')
    ->sum('movimentacoes.valor_pago');

$saidas = Movimentacao::join('lancamentos', 'movimentacoes.lancamento_id', '=', 'lancamentos.id')
    ->where('movimentacoes.conta_bancaria_id', $contaMp->id)
    ->where('lancamentos.tipo', 'despesa')
    ->sum('movimentacoes.valor_pago');

$saldoMov = $contaMp->saldo_inicial + $entradas - $saidas;

echo "Total Receitas Conciliadas (Entradas): R$ " . number_format($entradas, 2, ',', '.') . "\n";
echo "Total Despesas Conciliadas (Saídas): R$ " . number_format($saidas, 2, ',', '.') . "\n";
echo "Saldo Atual Calculado (Inicial + Receitas - Despesas): R$ " . number_format($saldoMov, 2, ',', '.') . "\n\n";

echo "=== Procurando as 3 transações do Mercado Pago do dia 17/08 da imagem ===\n";
$targetVals = [6.49, 14.63, 33.96];
foreach ($targetVals as $v) {
    $t = TransacaoExtrato::where('origem', 'mercadopago')
        ->whereBetween('valor', [$v - 0.01, $v + 0.01])
        ->get();
    echo "Valor R$ {$v}: Encontradas " . $t->count() . " no extrato.\n";
    foreach ($t as $item) {
        echo "   ID: {$item->id} | Data: {$item->data->format('Y-m-d')} | Desc: {$item->descricao} | Status: {$item->status}\n";
    }
}

echo "\n=== Verificando todas as transações pendentes no extrato do Mercado Pago ===\n";
$pendentes = TransacaoExtrato::where('origem', 'mercadopago')
    ->where('status', 'pendente')
    ->get();
echo "Pendentes no extrato Mercado Pago: " . $pendentes->count() . "\n";
foreach ($pendentes as $p) {
    echo "   ID: {$p->id} | Data: {$p->data->format('Y-m-d')} | Tipo: {$p->tipo} | Valor: R$ {$p->valor} | Desc: {$p->descricao}\n";
}

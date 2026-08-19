<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ContaBancaria;
use App\Models\Movimentacao;
use App\Models\TransacaoExtrato;
use Illuminate\Support\Facades\DB;

echo "=== Auditando Saldo da Conta Banco Inter ===\n\n";

$contaInter = ContaBancaria::where('nome', 'like', '%Inter%')->first();

if (!$contaInter) {
    echo "Conta Banco Inter não encontrada!\n";
    exit;
}

echo "ID Conta: {$contaInter->id}\n";
echo "Nome Conta: {$contaInter->nome}\n";
echo "Saldo Inicial no Banco de Dados: R$ {$contaInter->saldo_inicial}\n";
echo "Saldo Calculado Atual no BD (Dynamic Accessor): R$ " . number_format($contaInter->saldo_atual, 2, ',', '.') . "\n";
echo "Saldo Informado pelo Usuário no Banco Real: R$ 586,65\n";

$diferencaExata = 586.65 - $contaInter->saldo_atual;
echo "Diferença Exata (Banco Real - BD): R$ " . number_format($diferencaExata, 2, ',', '.') . "\n\n";

echo "=== Resumo das Movimentações da Conta Inter ===\n";
$entradas = Movimentacao::join('lancamentos', 'movimentacoes.lancamento_id', '=', 'lancamentos.id')
    ->where('movimentacoes.conta_bancaria_id', $contaInter->id)
    ->where('lancamentos.tipo', 'receita')
    ->sum('movimentacoes.valor_pago');

$saidas = Movimentacao::join('lancamentos', 'movimentacoes.lancamento_id', '=', 'lancamentos.id')
    ->where('movimentacoes.conta_bancaria_id', $contaInter->id)
    ->where('lancamentos.tipo', 'despesa')
    ->sum('movimentacoes.valor_pago');

echo "Total Receitas (Entradas): R$ " . number_format($entradas, 2, ',', '.') . "\n";
echo "Total Despesas (Saídas): R$ " . number_format($saidas, 2, ',', '.') . "\n";
echo "Cálculo Manuais (Inicial + Entradas - Saídas): R$ " . number_format($contaInter->saldo_inicial + $entradas - $saidas, 2, ',', '.') . "\n\n";

echo "=== Verificando Transação 975 (que alteramos mais cedo de MP para Inter) ===\n";
$t975 = TransacaoExtrato::find(975);
if ($t975) {
    echo "TransacaoExtrato 975 | Origem: {$t975->origem} | ContaID: {$t975->conta_bancaria_id} | Tipo: {$t975->tipo} | Valor: R$ {$t975->valor} | Status: {$t975->status}\n";
}
$m5747 = Movimentacao::with('lancamento')->find(5747);
if ($m5747) {
    echo "Movimentacao 5747 | ContaID: {$m5747->conta_bancaria_id} | Valor: R$ {$m5747->valor_pago} | Tipo Lancamento: " . ($m5747->lancamento->tipo ?? 'N/A') . "\n\n";
}

echo "=== Verificando se no Banco Inter já existia a transação de R$ 66,08 ou similar (Evitar Duplicidade) ===\n";
$transInter66 = TransacaoExtrato::where('origem', 'bancointer')
    ->whereBetween('valor', [66.00, 66.15])
    ->get();

foreach ($transInter66 as $ti) {
    echo "   ID: {$ti->id} | Data: {$ti->data->format('Y-m-d')} | Tipo: {$ti->tipo} | Valor: R$ {$ti->valor} | Status: {$ti->status} | Desc: {$ti->descricao}\n";
}

echo "\n=== Procurando no Banco de Dados valores próximos à diferença exata R$ " . number_format($diferencaExata, 2, ',', '.') . " ===\n";
$transDiff = TransacaoExtrato::whereBetween('valor', [$diferencaExata - 0.50, $diferencaExata + 0.50])->get();
echo "Encontradas " . $transDiff->count() . " transações no extrato perto de R$ " . number_format($diferencaExata, 2, ',', '.') . ":\n";
foreach ($transDiff as $td) {
    echo "   ID: {$td->id} | Data: {$td->data->format('Y-m-d')} | Origem: {$td->origem} | Tipo: {$td->tipo} | Valor: R$ {$td->valor} | Status: {$td->status} | Desc: {$td->descricao}\n";
}

echo "\n=== Verificando Transações Pendentes no Extrato do Banco Inter ===\n";
$pendentesInter = TransacaoExtrato::where('origem', 'bancointer')
    ->where('status', 'pendente')
    ->get();
echo "Total Pendentes Banco Inter: " . $pendentesInter->count() . "\n";
foreach ($pendentesInter as $pi) {
    echo "   ID: {$pi->id} | Data: {$pi->data->format('Y-m-d')} | Tipo: {$pi->tipo} | Valor: R$ {$pi->valor} | Desc: {$pi->descricao}\n";
}

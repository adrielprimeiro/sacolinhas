<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ContaBancaria;
use App\Models\Movimentacao;
use App\Models\TransacaoExtrato;
use Illuminate\Support\Facades\Schema;

echo "Colunas de movimentacoes:\n";
print_r(Schema::getColumnListing('movimentacoes'));

echo "\n=== Auditando Saldo do Mercado Pago ===\n\n";

$contaMp = ContaBancaria::where('nome', 'like', '%Mercado%Pago%')->first();

if (!$contaMp) {
    echo "Conta do Mercado Pago não encontrada!\n";
    exit;
}

echo "ID Conta: {$contaMp->id}\n";
echo "Nome Conta: {$contaMp->nome}\n";
echo "Saldo Inicial: R$ {$contaMp->saldo_inicial}\n";

$entradas = Movimentacao::where('conta_bancaria_id', $contaMp->id)->where('tipo', 'receita')->sum('valor_total');
$saidas   = Movimentacao::where('conta_bancaria_id', $contaMp->id)->where('tipo', 'despesa')->sum('valor_total');
$saldoMov = $contaMp->saldo_inicial + $entradas - $saidas;

echo "Total Receitas em movimentacoes: R$ {$entradas}\n";
echo "Total Despesas em movimentacoes: R$ {$saidas}\n";
echo "Saldo Calculado (Inicial + Entradas - Saídas): R$ {$saldoMov}\n\n";

echo "=== Procurando as 3 transações de 17/ago da imagem no extrato ===\n";
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

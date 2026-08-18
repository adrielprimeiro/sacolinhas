<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use App\Models\Movimentacao;
use App\Models\Lancamento;
use App\Models\ContaBancaria;

echo "=== Fixing Mercado Pago Balance (R$ 66,08 Label Rolls Purchase Discrepancy) ===\n\n";

$t975 = TransacaoExtrato::find(975);
$mov5747 = Movimentacao::find(5747);
$lanc5860 = Lancamento::find(5860);

$contaInter = ContaBancaria::where('nome', 'like', '%Inter%')->first();
$contaInterId = $contaInter ? $contaInter->id : 4;

if ($t975) {
    echo "1. Corrigindo TransacaoExtrato ID 975...\n";
    $t975->update([
        'conta_bancaria_id' => $contaInterId,
        'origem' => 'bancointer',
        'tipo' => 'saida'
    ]);
}

if ($lanc5860) {
    echo "2. Corrigindo Lançamento ID 5860 (Receita -> Despesa)...\n";
    $lanc5860->update([
        'tipo' => 'despesa',
        'descricao' => 'Compra 10 Rolos Etiqueta 60x30 Térmica Elgin L42'
    ]);
}

if ($mov5747) {
    echo "3. Corrigindo Movimentação ID 5747 (Conta MP -> Conta Banco Inter)...\n";
    $mov5747->update([
        'conta_bancaria_id' => $contaInterId
    ]);
}

echo "\n=== Verificação Final dos Saldos no Banco de Dados ===\n";
$contaMp = ContaBancaria::find(2);
echo "Conta: {$contaMp->nome}\n";
echo "Saldo Inicial: R$ " . number_format($contaMp->saldo_inicial, 2, ',', '.') . "\n";
echo "Saldo Atual no BD: R$ " . number_format($contaMp->saldo_atual, 2, ',', '.') . "\n";

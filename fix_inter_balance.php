<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use App\Models\Movimentacao;
use App\Models\Lancamento;
use App\Models\ContaBancaria;

echo "=== Corrigindo Saldo do Banco Inter (Duplicidade R$ 66,08 + Pix Pendente R$ 20,00) ===\n\n";

// 1. Remover movimentação duplicada 5747 e deletar transação 975
$mov5747 = Movimentacao::find(5747);
$t975 = TransacaoExtrato::find(975);
$lanc5860 = Lancamento::find(5860);

if ($mov5747) {
    echo "1. Removendo movimentação duplicada ID 5747...\n";
    $mov5747->delete();
}

if ($lanc5860) {
    echo "2. Removendo lançamento duplicado ID 5860...\n";
    $lanc5860->delete();
}

if ($t975) {
    echo "3. Removendo transação extrato duplicada ID 975...\n";
    $t975->delete();
}

// 2. Conciliar transação pendente 1039 (Pix Recebido R$ 20,00)
$t1039 = TransacaoExtrato::find(1039);
if ($t1039 && $t1039->status === 'pendente') {
    echo "4. Conciliando transação pendente ID 1039 (Pix R$ 20,00 de MANIA DE MELISSA)...\n";
    
    $lanc = Lancamento::create([
        'user_id' => 1,
        'descricao' => $t1039->descricao,
        'valor' => $t1039->valor,
        'tipo' => 'receita',
        'status' => 'pago',
        'data_vencimento' => $t1039->data,
        'data_pagamento' => $t1039->data
    ]);

    Movimentacao::create([
        'lancamento_id' => $lanc->id,
        'conta_bancaria_id' => 4, // Inter
        'data_pagamento' => $t1039->data,
        'valor_pago' => $t1039->valor,
        'forma_pagamento' => 'pix',
        'transacao_extrato_id' => $t1039->id,
        'observacoes' => 'Conciliado automaticamente'
    ]);

    $t1039->update(['status' => 'conciliado']);
}

echo "\n=== Verificação Final do Saldo do Banco Inter ===\n";
$contaInter = ContaBancaria::find(4);
echo "Conta: {$contaInter->nome}\n";
echo "Saldo Inicial: R$ " . number_format($contaInter->saldo_inicial, 2, ',', '.') . "\n";
echo "Saldo Atual no BD: R$ " . number_format($contaInter->saldo_atual, 2, ',', '.') . "\n";
echo "Saldo Real no Banco: R$ 586,65\n";

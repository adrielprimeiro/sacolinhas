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
echo "Saldo Inicial: R$ {$contaMp->saldo_inicial}\n";
echo "Saldo Calculado Atual no BD: R$ {$contaMp->saldo_atual}\n\n";

echo "=== Verificando movimentações confirmadas na conta MP ===\n";
$entradas = Movimentacao::where('conta_bancaria_id', $contaMp->id)->where('tipo', 'receita')->sum('valor');
$saidas   = Movimentacao::where('conta_bancaria_id', $contaMp->id)->where('tipo', 'despesa')->sum('valor');
$saldoMov = $contaMp->saldo_inicial + $entradas - $saidas;

echo "Total Receitas: R$ {$entradas}\n";
echo "Total Despesas: R$ {$saidas}\n";
echo "Saldo Inicial + Receitas - Despesas: R$ {$saldoMov}\n\n";

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

echo "\n=== Verificando via Mercado Pago API em tempo real ===\n";
use Illuminate\Support\Facades\Http;
use App\Models\Configuracao;

$tokenConfig = Configuracao::where('chave', 'mercadopago_access_token')->first();
$token = $tokenConfig ? $tokenConfig->valor : env('MERCADOPAGO_ACCESS_TOKEN');

// Consultar saldo real via API do MP se houver endpoint
$respAccount = Http::withoutVerifying()->withToken($token)->get('https://api.mercadopago.com/users/me');
if ($respAccount->successful()) {
    echo "ID Usuário MP: " . $respAccount->json('id') . "\n";
}

// Consultar transações recentes via API do MP
$respPayments = Http::withoutVerifying()->withToken($token)->get('https://api.mercadopago.com/v1/payments/search?sort=date_created&criteria=desc&limit=30');
if ($respPayments->successful()) {
    $results = $respPayments->json('results') ?? [];
    echo "Pagamentos recentes na API MP: " . count($results) . "\n";
    foreach (array_slice($results, 0, 10) as $p) {
        $date = $p['date_created'] ?? '';
        $amount = $p['transaction_amount'] ?? '';
        $desc = $p['description'] ?? ($p['statement_descriptor'] ?? '');
        $status = $p['status'] ?? '';
        $net = $p['transaction_details']['net_received_amount'] ?? '';
        echo "   ID: {$p['id']} | Data: {$date} | Status: {$status} | R$ {$amount} (net: {$net}) | Desc: {$desc}\n";
    }
}

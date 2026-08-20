<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use App\Models\Configuracao;
use Illuminate\Support\Facades\Http;

echo "=== Auditando Problemas na Sincronização do Mercado Pago ===\n\n";

// 1. Verificar a transação do 10 Rolos de Etiqueta (ID 975 ou similar) em transacoes_extrato
$t975 = TransacaoExtrato::where('descricao', 'like', '%10 Rolos Etiqueta%')->get();
echo "Transações de 10 Rolos de Etiqueta encontradas no BD: " . $t975->count() . "\n";
foreach ($t975 as $item) {
    echo "   ID: {$item->id} | Data: {$item->data->format('Y-m-d')} | Origem: {$item->origem} | Status: {$item->status} | Tipo: {$item->tipo} | Valor: R$ {$item->valor} | Desc: {$item->descricao}\n";
}

// 2. Verificar as transações recentes com descrição 'payment' ou similares
$tPayment = TransacaoExtrato::where('origem', 'mercadopago')
    ->where('descricao', 'like', '%payment%')
    ->get();
echo "\nTransações com descrição 'payment' em transacoes_extrato: " . $tPayment->count() . "\n";
foreach ($tPayment as $tp) {
    echo "   ID: {$tp->id} | Data: {$tp->data->format('Y-m-d')} | Status: {$tp->status} | Tipo: {$tp->tipo} | Valor: R$ {$tp->valor} | Desc: {$tp->descricao}\n";
}

// 3. Testar chamada de API em tempo real no Mercado Pago para analisar a estrutura dos pagamentos recentes (Supermercado Mercocentr, Panificadora Biguacu, Pix Banco Inter)
$tokenConfig = Configuracao::where('chave', 'mercadopago_access_token')->first();
$token = $tokenConfig ? $tokenConfig->valor : env('MERCADOPAGO_ACCESS_TOKEN');

echo "\n=== Testando API /v1/payments/search do Mercado Pago em tempo real ===\n";
$resp = Http::withoutVerifying()->withToken($token)->get('https://api.mercadopago.com/v1/payments/search?sort=date_created&criteria=desc&limit=50');

if ($resp->successful()) {
    $results = $resp->json('results') ?? [];
    echo "Total de pagamentos recentes retornados pela API: " . count($results) . "\n\n";

    foreach (array_slice($results, 0, 15) as $p) {
        $id = $p['id'] ?? '';
        $date = substr($p['date_created'] ?? '', 0, 10);
        $amount = $p['transaction_amount'] ?? 0;
        $desc = $p['description'] ?? '';
        $statementDesc = $p['statement_descriptor'] ?? '';
        $paymentTypeId = $p['payment_type_id'] ?? '';
        $paymentMethodId = $p['payment_method_id'] ?? '';
        $payerName = trim(($p['payer']['first_name'] ?? '') . ' ' . ($p['payer']['last_name'] ?? ''));
        $payerEmail = $p['payer']['email'] ?? '';

        $collectorName = $p['point_of_interaction']['transaction_data']['bank_info']['collector']['account_holder_name'] ?? '';
        $payerBank = $p['point_of_interaction']['transaction_data']['bank_info']['payer']['long_name'] ?? '';

        echo "ID: {$id} | Data: {$date} | R$ {$amount} | Type: {$paymentTypeId} | Method: {$paymentMethodId}\n";
        echo "   Desc: '{$desc}' | StatementDesc: '{$statementDesc}' | Payer: '{$payerName}' ({$payerEmail}) | PayerBank: '{$payerBank}'\n";
        echo "   ------------------------------------------------------------------\n";
    }
} else {
    echo "Erro na chamada API MP: " . $resp->status() . " - " . $resp->body() . "\n";
}

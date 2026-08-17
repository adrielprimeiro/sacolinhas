<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\Configuracao;

$tokenConfig = Configuracao::where('chave', 'mercadopago_access_token')->first();
$token = $tokenConfig ? $tokenConfig->valor : env('MERCADOPAGO_ACCESS_TOKEN');

if (!$token) {
    echo "Mercado Pago Access Token nao encontrado.\n";
    exit;
}

echo "=== Consultando Mercado Pago API de Pagamentos / Transacoes ===\n\n";

// 1. Consultar /v1/payments/search (Pagamentos recebidos/enviados)
$urlPayments = "https://api.mercadopago.com/v1/payments/search?sort=date_created&criteria=desc&limit=30";
$response = Http::withoutVerifying()->withToken($token)->get($urlPayments);

if ($response->successful()) {
    $results = $response->json('results') ?? [];
    echo "Encontrados " . count($results) . " registros via /v1/payments/search:\n";
    foreach ($results as $p) {
        $id = $p['id'] ?? '';
        $date = $p['date_created'] ?? '';
        $status = $p['status'] ?? '';
        $amount = $p['transaction_amount'] ?? '';
        $desc = $p['description'] ?? ($p['payment_method_id'] ?? '');
        $payer = $p['payer']['email'] ?? ($p['payer']['first_name'] ?? '');
        $extRef = $p['external_reference'] ?? '';

        echo "ID: {$id} | Data: {$date} | Status: {$status} | R$ {$amount} | Desc: {$desc} | Payer: {$payer} | ExtRef: {$extRef}\n";
    }
} else {
    echo "Erro ao consultar /v1/payments/search: " . $response->status() . " - " . $response->body() . "\n";
}

echo "\n=========================================================\n";
// 2. Consultar /v1/account/settlement_report/list (Relatorios de Liquidação/Extrato da Conta)
$urlSettlement = "https://api.mercadopago.com/v1/account/settlement_report/list";
$respSet = Http::withoutVerifying()->withToken($token)->get($urlSettlement);

if ($respSet->successful()) {
    $reports = $respSet->json() ?? [];
    echo "Encontrados " . count($reports) . " relatórios em /v1/account/settlement_report/list:\n";
    foreach (array_slice($reports, 0, 5) as $r) {
        $fn = $r['file_name'] ?? '';
        $created = $r['date_created'] ?? '';
        $status = $r['status'] ?? '';
        echo "File: {$fn} | Created: {$created} | Status: {$status}\n";
    }
} else {
    echo "Erro ao consultar settlement_report/list: " . $respSet->status() . " - " . $respSet->body() . "\n";
}

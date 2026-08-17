<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\Configuracao;

$tokenConfig = Configuracao::where('chave', 'mercadopago_access_token')->first();
$token = $tokenConfig ? $tokenConfig->valor : env('MERCADOPAGO_ACCESS_TOKEN');

echo "=== Search Mercado Pago payments / transactions for recent days ===\n\n";

$url = "https://api.mercadopago.com/v1/payments/search?sort=date_created&criteria=desc&limit=50";
$resp = Http::withoutVerifying()->withToken($token)->get($url);

if ($resp->successful()) {
    $results = $resp->json('results') ?? [];
    echo "Total de pagamentos no /v1/payments/search: " . count($results) . "\n\n";
    foreach ($results as $p) {
        $id = $p['id'];
        $date = $p['date_created'];
        $amount = $p['transaction_amount'];
        $status = $p['status'];
        $desc = $p['description'] ?? '';
        $opType = $p['operation_type'] ?? '';
        $payType = $p['payment_type_id'] ?? '';
        $payMethod = $p['payment_method_id'] ?? '';
        $statement = $p['statement_descriptor'] ?? '';
        $counterpart = $p['point_of_interaction']['transaction_data']['bank_info']['collector']['account_holder_name'] ?? '';

        echo "ID: {$id} | Data: {$date} | Status: {$status} | R$ {$amount} | Desc: '{$desc}' | OpType: {$opType} | PayType: {$payType} | Method: {$payMethod} | Stmt: {$statement} | Counterpart: {$counterpart}\n";
    }
} else {
    echo "Erro API: " . $resp->status() . " - " . $resp->body() . "\n";
}

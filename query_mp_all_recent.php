<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\Configuracao;

$tokenConfig = Configuracao::where('chave', 'mercadopago_access_token')->first();
$token = $tokenConfig ? $tokenConfig->valor : env('MERCADOPAGO_ACCESS_TOKEN');

echo "=== Searching ALL MP payments/transactions in the last 7 days ===\n\n";

$from = now()->subDays(7)->startOfDay()->format('Y-m-d\TH:i:s.000P');
$to = now()->endOfDay()->format('Y-m-d\TH:i:s.000P');

$url = "https://api.mercadopago.com/v1/payments/search?sort=date_created&criteria=desc&limit=100&begin_date={$from}&end_date={$to}";
$resp = Http::withoutVerifying()->withToken($token)->get($url);

if ($resp->successful()) {
    $results = $resp->json('results') ?? [];
    echo "Encontrados " . count($results) . " pagamentos entre {$from} e {$to}:\n\n";
    foreach ($results as $p) {
        $id = $p['id'];
        $date = $p['date_created'];
        $amount = $p['transaction_amount'];
        $status = $p['status'];
        $desc = $p['description'] ?? '';
        $opType = $p['operation_type'] ?? '';
        $payType = $p['payment_type_id'] ?? '';
        $payMethod = $p['payment_method_id'] ?? '';

        echo "ID: {$id} | Data: {$date} | Status: {$status} | R$ {$amount} | Desc: '{$desc}' | Op: {$opType} | Type: {$payType} | Method: {$payMethod}\n";
    }
} else {
    echo "Erro API: " . $resp->status() . " - " . $resp->body() . "\n";
}

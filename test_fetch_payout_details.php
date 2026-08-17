<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\Configuracao;

$tokenConfig = Configuracao::where('chave', 'mercadopago_access_token')->first();
$token = $tokenConfig ? $tokenConfig->valor : env('MERCADOPAGO_ACCESS_TOKEN');

$sourceIds = ['173150623607', '172828204187', '172228451652', '169060751949'];

foreach ($sourceIds as $id) {
    echo "=== Fetching details for source_id / payment_id {$id} ===\n";
    $url = "https://api.mercadopago.com/v1/payments/{$id}";
    $resp = Http::withoutVerifying()->withToken($token)->get($url);
    echo "URL: {$url} => Status: " . $resp->status() . "\n";
    if ($resp->successful()) {
        $p = $resp->json();
        $desc = $p['description'] ?? '';
        $opType = $p['operation_type'] ?? '';
        $payType = $p['payment_type_id'] ?? '';
        $payMethod = $p['payment_method_id'] ?? '';
        $stmt = $p['statement_descriptor'] ?? '';
        $counterpart = $p['point_of_interaction']['transaction_data']['bank_info']['collector']['account_holder_name'] 
                    ?? ($p['point_of_interaction']['transaction_data']['bank_info']['payer']['account_holder_name'] ?? '');
        $extRef = $p['external_reference'] ?? '';

        echo "   Description: {$desc}\n";
        echo "   OpType: {$opType} | PayType: {$payType} | Method: {$payMethod}\n";
        echo "   Statement Descriptor: {$stmt}\n";
        echo "   Counterpart Name: {$counterpart}\n";
        echo "   External Reference: {$extRef}\n";
        echo "   Keys: " . implode(', ', array_keys($p)) . "\n";
    } else {
        echo "   Response Body: " . substr($resp->body(), 0, 250) . "\n";
    }
    echo "\n";
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\Configuracao;

$tokenConfig = Configuracao::where('chave', 'mercadopago_access_token')->first();
$token = $tokenConfig ? $tokenConfig->valor : env('MERCADOPAGO_ACCESS_TOKEN');

echo "=== Exploring Mercado Pago additional endpoints ===\n\n";

$endpoints = [
    "https://api.mercadopago.com/merchant_orders/search?limit=30",
    "https://api.mercadopago.com/v1/advanced_payments/search?limit=30",
    "https://api.mercadopago.com/v1/disbursements/search?limit=30",
    "https://api.mercadopago.com/v1/payment_intents/search?limit=30",
    "https://api.mercadopago.com/v1/money_transfers/search?limit=30",
];

foreach ($endpoints as $url) {
    $resp = Http::withoutVerifying()->withToken($token)->get($url);
    echo "URL: {$url} => Status: " . $resp->status() . "\n";
    if ($resp->successful()) {
        $data = $resp->json();
        if (is_array($data)) {
            $elements = $data['elements'] ?? ($data['results'] ?? $data);
            echo "   Retornou " . count($elements) . " elementos.\n";
        }
    }
}

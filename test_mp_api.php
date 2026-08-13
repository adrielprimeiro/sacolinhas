<?php
// Teste direto na API do Mercado Pago para descobrir o endpoint correto
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = config('services.mercadopago.access_token');

// Testar endpoint de user info
$response = Illuminate\Support\Facades\Http::withoutVerifying()
    ->withToken($token)
    ->get('https://api.mercadopago.com/v1/account/settlement_report/list');

echo "=== settlement_report/list ===\n";
echo "Status: " . $response->status() . "\n";
echo substr($response->body(), 0, 500) . "\n\n";

// Testar payment search com mais tipos
$response2 = Illuminate\Support\Facades\Http::withoutVerifying()
    ->withToken($token)
    ->get('https://api.mercadopago.com/v1/payments/search?range=date_created&begin_date=2026-08-08T00:00:00.000-03:00&end_date=2026-08-10T23:59:59.000-03:00&limit=20');

echo "=== payments/search (todos status) ===\n";
echo "Status: " . $response2->status() . "\n";
$data = $response2->json();
echo "Total: " . ($data['paging']['total'] ?? 0) . "\n";
foreach ($data['results'] ?? [] as $p) {
    echo "ID: {$p['id']} | status: {$p['status']} | type: {$p['payment_type_id']} | desc: {$p['description']} | valor: {$p['transaction_amount']}\n";
}

// Testar merchant_orders
$response3 = Illuminate\Support\Facades\Http::withoutVerifying()
    ->withToken($token)
    ->get('https://api.mercadopago.com/merchant_orders/search?q=2026-08-08&sort=date_created&criteria=desc&limit=10');

echo "\n=== merchant_orders/search ===\n";
echo "Status: " . $response3->status() . "\n";
echo substr($response3->body(), 0, 300) . "\n";

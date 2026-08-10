<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = config('services.mercadopago.access_token');

// Buscar os pagamentos authorized do tipo account_money para ver o payload completo
$response = Illuminate\Support\Facades\Http::withoutVerifying()
    ->withToken($token)
    ->get('https://api.mercadopago.com/v1/payments/search?range=date_created&begin_date=2026-08-08T00:00:00.000-03:00&end_date=2026-08-10T23:59:59.000-03:00&limit=20');

$data = $response->json();
foreach ($data['results'] ?? [] as $p) {
    if ($p['status'] === 'authorized' && $p['payment_type_id'] === 'account_money') {
        echo "=== ID: {$p['id']} | R$ {$p['transaction_amount']} ===\n";
        echo "description: " . ($p['description'] ?? 'NULL') . "\n";
        echo "statement_descriptor: " . ($p['statement_descriptor'] ?? 'NULL') . "\n";
        echo "operation_type: " . ($p['operation_type'] ?? 'NULL') . "\n";
        echo "payment_method_id: " . ($p['payment_method_id'] ?? 'NULL') . "\n";
        // Ver additional_info
        if (!empty($p['additional_info'])) {
            echo "additional_info.items: " . json_encode($p['additional_info']['items'] ?? []) . "\n";
        }
        // Ver payer
        if (!empty($p['payer'])) {
            echo "payer.identification.number: " . ($p['payer']['identification']['number'] ?? 'NULL') . "\n";
        }
        echo "\n";
    }
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$token = config('services.mercadopago.access_token');
$paymentId = '175108006926';

echo "=== Testando Busca de Detalhes no Mercado Pago para ID {$paymentId} ===\n\n";

$response = Http::withoutVerifying()->withToken($token)->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

echo "Status: " . $response->status() . "\n";
$data = $response->json();
print_r($data);

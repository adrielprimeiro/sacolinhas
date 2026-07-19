<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Configuracao;
use Illuminate\Support\Facades\Http;

$token = Configuracao::get('melhor_envio_access_token') ?: env('MELHOR_ENVIO_TOKEN', '');
$cepOrigem = env('MELHOR_ENVIO_CEP_ORIGEM', '88160152');
$cepDestino = '01001000'; // São Paulo, Sé

$payload = [
    'from' => [
        'postal_code' => $cepOrigem
    ],
    'to' => [
        'postal_code' => $cepDestino
    ],
    'package' => [
        'weight' => 0.5,
        'width'  => 15,
        'height' => 15,
        'length' => 15,
    ]
];

$baseUrl = env('MELHOR_ENVIO_URL', 'https://melhorenvio.com.br');

$response = Http::withHeaders([
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
    'Authorization' => 'Bearer ' . $token,
])->post($baseUrl . '/api/v2/me/shipment/calculate', $payload);

echo "HTTP Status: " . $response->status() . "\n";
echo "Response Body:\n";
print_r($response->json());

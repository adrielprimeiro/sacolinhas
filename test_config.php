<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Configuracao;
use Illuminate\Support\Facades\Http;

$token = Configuracao::get('melhor_envio_access_token') ?: env('MELHOR_ENVIO_TOKEN', '');
$cepOrigem = env('MELHOR_ENVIO_CEP_ORIGEM', '88160152');

$payload = [
    'from' => [ 'postal_code' => $cepOrigem ],
    'to' => [ 'postal_code' => '14170160' ], // User's CEP
    'package' => [ 'weight' => 0.2, 'width' => 11, 'height' => 5, 'length' => 10 ]
];

$baseUrl = env('MELHOR_ENVIO_URL', 'https://melhorenvio.com.br');
$response = Http::withHeaders([
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
    'Authorization' => 'Bearer ' . $token,
])->post($baseUrl . '/api/v2/me/shipment/calculate', $payload);

$options = $response->json();
$validOptions = array_values(array_filter($options, function($o) {
    return !isset($o['error']) && isset($o['price']);
}));

echo "Valid Options Count: " . count($validOptions) . "\n";
foreach ($validOptions as $i => $opt) {
    echo "Option {$i}: {$opt['name']} (Price: {$opt['price']})\n";
    if (!isset($opt['company'])) {
        echo "  MISSING COMPANY!\n";
    } else {
        echo "  Company: " . ($opt['company']['name'] ?? 'NULL') . "\n";
        echo "  Picture: " . ($opt['company']['picture'] ?? 'NULL') . "\n";
    }
}

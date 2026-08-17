<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\Configuracao;

$tokenConfig = Configuracao::where('chave', 'mercadopago_access_token')->first();
$token = $tokenConfig ? $tokenConfig->valor : env('MERCADOPAGO_ACCESS_TOKEN');

echo "=== Exploring Mercado Pago API for User & Movements ===\n\n";

// 1. Get Me (User info)
$userResp = Http::withoutVerifying()->withToken($token)->get('https://api.mercadopago.com/users/me');
if ($userResp->successful()) {
    $user = $userResp->json();
    $userId = $user['id'] ?? '';
    echo "User ID: {$userId} | Email: " . ($user['email'] ?? '') . " | Name: " . ($user['first_name'] ?? '') . "\n";
} else {
    echo "Erro users/me: " . $userResp->status() . "\n";
    $userId = '';
}

// 2. Test various activity & movements endpoints
$testEndpoints = [
    "https://api.mercadopago.com/v1/account/settlement_report/list",
    "https://api.mercadopago.com/v1/account/release_report/list",
    "https://api.mercadopago.com/v1/account/bank_report/list",
    "https://api.mercadopago.com/mercadopago_account/movements/search?limit=30",
    "https://api.mercadopago.com/v1/account/activity/search?limit=30",
    "https://api.mercadopago.com/v1/transfers/search?limit=30",
];

foreach ($testEndpoints as $ep) {
    $resp = Http::withoutVerifying()->withToken($token)->get($ep);
    echo "Endpoint: {$ep} => Status: " . $resp->status() . "\n";
    if ($resp->successful()) {
        $data = $resp->json();
        if (is_array($data)) {
            $results = $data['results'] ?? $data;
            echo "   Sucesso! Retornou " . count($results) . " itens.\n";
            if (count($results) > 0 && is_array($results[0])) {
                echo "   Sample item keys: " . implode(', ', array_keys($results[0])) . "\n";
            }
        }
    }
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\Configuracao;

$tokenConfig = Configuracao::where('chave', 'mercadopago_access_token')->first();
$token = $tokenConfig ? $tokenConfig->valor : env('MERCADOPAGO_ACCESS_TOKEN');

$id = '173150623607';

$urls = [
    "https://api.mercadopago.com/v1/payouts/{$id}",
    "https://api.mercadopago.com/v1/payouts/search?id={$id}",
    "https://api.mercadopago.com/v1/payouts",
    "https://api.mercadopago.com/v1/withdrawals/{$id}",
    "https://api.mercadopago.com/v1/bank_transfers/{$id}",
    "https://api.mercadopago.com/v1/transfers/{$id}",
];

foreach ($urls as $url) {
    $resp = Http::withoutVerifying()->withToken($token)->get($url);
    echo "URL: {$url} => Status: " . $resp->status() . " (Body: " . substr($resp->body(), 0, 200) . ")\n";
}

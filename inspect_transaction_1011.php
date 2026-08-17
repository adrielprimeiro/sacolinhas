<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use Illuminate\Support\Facades\Http;
use App\Models\Configuracao;

$tokenConfig = Configuracao::where('chave', 'mercadopago_access_token')->first();
$token = $tokenConfig ? $tokenConfig->valor : env('MERCADOPAGO_ACCESS_TOKEN');

$t = TransacaoExtrato::find(1011);
echo "=== Transação ID 1011 ===\n";
if ($t) {
    echo "ID: {$t->id} | Data: {$t->data} | Valor: R$ {$t->valor} | FITID: {$t->fitid} | Desc: {$t->descricao}\n";
    echo "Payload: " . print_r($t->payload_original, true) . "\n";
}

echo "\n=== Buscando ref 'edec568a37be4ca7afff89d36946ad13' na API do MP ===\n";
$ref = 'edec568a37be4ca7afff89d36946ad13';

$endpoints = [
    "https://api.mercadopago.com/merchant_orders/search?external_reference={$ref}",
    "https://api.mercadopago.com/v1/payments/search?external_reference={$ref}",
    "https://api.mercadopago.com/merchant_orders/{$ref}",
    "https://api.mercadopago.com/v1/payments/search?id={$t->fitid}",
];

foreach ($endpoints as $url) {
    $resp = Http::withoutVerifying()->withToken($token)->get($url);
    echo "URL: {$url} => Status: " . $resp->status() . "\n";
    if ($resp->successful()) {
        $body = $resp->body();
        echo "   Body: " . substr($body, 0, 300) . "\n";
    }
}

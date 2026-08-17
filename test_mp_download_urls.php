<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\Configuracao;

$tokenConfig = Configuracao::where('chave', 'mercadopago_access_token')->first();
$token = $tokenConfig ? $tokenConfig->valor : env('MERCADOPAGO_ACCESS_TOKEN');

$fileName = 'conciliacao-mp-manual-2026-08-17-065843.csv';

$testUrls = [
    "https://api.mercadopago.com/v1/account/bank_report/{$fileName}",
    "https://api.mercadopago.com/v1/account/bank_report/file/{$fileName}",
    "https://api.mercadopago.com/v1/account/release_report/{$fileName}",
    "https://api.mercadopago.com/v1/account/release_report/file/{$fileName}",
    "https://api.mercadopago.com/v1/account/settlement_report/{$fileName}",
    "https://api.mercadopago.com/v1/account/settlement_report/file/{$fileName}",
];

foreach ($testUrls as $url) {
    $resp = Http::withoutVerifying()->withToken($token)->get($url);
    echo "URL: {$url} => Status: " . $resp->status() . " (Tamanho: " . strlen($resp->body()) . " bytes)\n";
    if ($resp->successful()) {
        echo "   CONSEGUIU BAIXAR! Primeiras linhas:\n";
        $lines = explode("\n", str_replace("\r", "", $resp->body()));
        foreach (array_slice($lines, 0, 5) as $l) {
            echo "   " . substr($l, 0, 150) . "\n";
        }
        break;
    }
}

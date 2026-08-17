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
$url = "https://api.mercadopago.com/v1/account/settlement_report/{$fileName}";
$resp = Http::withoutVerifying()->withToken($token)->get($url);

if ($resp->successful()) {
    $csv = $resp->body();
    $lines = explode("\n", str_replace("\r", "", $csv));
    echo "=== Conteudo completo do relatorio {$fileName} (" . count($lines) . " linhas) ===\n\n";
    foreach ($lines as $i => $l) {
        if (trim($l)) {
            echo "Linha #{$i}: {$l}\n";
        }
    }
} else {
    echo "Erro ao baixar: " . $resp->status() . "\n";
}

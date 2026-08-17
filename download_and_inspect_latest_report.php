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
$downloadUrl = "https://api.mercadopago.com/v1/account/bank_report/{$fileName}";
$response = Http::withoutVerifying()->withToken($token)->get($downloadUrl);

if ($response->successful()) {
    $csv = $response->body();
    echo "=== Relatorio {$fileName} (Tamanho: " . strlen($csv) . " bytes) ===\n\n";
    $lines = explode("\n", str_replace("\r", "", $csv));
    echo "Total de linhas no CSV: " . count($lines) . "\n";
    echo "Primeiras 15 linhas do CSV:\n";
    foreach (array_slice($lines, 0, 15) as $l) {
        echo $l . "\n";
    }

    echo "\nProcurando por 'ifood' ou 'pix' nas linhas do CSV:\n";
    foreach ($lines as $idx => $l) {
        if (stripos($l, 'ifood') !== false || stripos($l, 'food') !== false || stripos($l, 'pix') !== false) {
            echo "Linha #{$idx}: {$l}\n";
        }
    }
} else {
    echo "Erro ao baixar relatorio: " . $response->status() . "\n";
}

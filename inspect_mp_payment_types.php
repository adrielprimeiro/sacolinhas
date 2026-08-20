<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Configuracao;
use Illuminate\Support\Facades\Http;

$tokenConfig = Configuracao::where('chave', 'mercadopago_access_token')->first();
$token = $tokenConfig ? $tokenConfig->valor : env('MERCADOPAGO_ACCESS_TOKEN');

$resp = Http::withoutVerifying()->withToken($token)->get('https://api.mercadopago.com/v1/payments/search?sort=date_created&criteria=desc&limit=50');

if ($resp->successful()) {
    $results = $resp->json('results') ?? [];
    echo "=== Analisando Estrutura Completa de Pagamentos da API MP ===\n\n";

    foreach ($results as $p) {
        $id = $p['id'];
        $date = substr($p['date_created'] ?? '', 0, 10);
        $amount = $p['transaction_amount'] ?? 0;
        $desc = $p['description'] ?? '';
        $type = $p['payment_type_id'] ?? '';
        $method = $p['payment_method_id'] ?? '';
        $operationType = $p['operation_type'] ?? '';
        $pointType = $p['point_of_interaction']['type'] ?? '';
        $subType = $p['point_of_interaction']['sub_type'] ?? '';

        $payerBank = $p['point_of_interaction']['transaction_data']['bank_info']['payer']['long_name'] ?? '';
        $collectorBank = $p['point_of_interaction']['transaction_data']['bank_info']['collector']['account_holder_name'] ?? '';

        echo "ID: {$id} | Data: {$date} | R$ {$amount}\n";
        echo "   type: '{$type}' | method: '{$method}' | operation_type: '{$operationType}' | pointType: '{$pointType}' | subType: '{$subType}'\n";
        echo "   Desc: '{$desc}' | PayerBank: '{$payerBank}' | Collector: '{$collectorBank}'\n";
        echo "------------------------------------------------------------------\n";
    }
}

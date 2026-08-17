<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;

echo "=== Inspecting transactions with description 'payout' or 'PAYOUTS' ===\n\n";

$payouts = TransacaoExtrato::where('descricao', 'like', '%payout%')
    ->orWhere('descricao', 'like', '%PAYOUTS%')
    ->get();

echo "Encontradas " . $payouts->count() . " transações de payout:\n\n";

foreach ($payouts as $p) {
    echo "ID: {$p->id} | Data: {$p->data->format('Y-m-d')} | FITID: {$p->fitid} | Valor: R$ {$p->valor} | Desc: '{$p->descricao}'\n";
    echo "   Payload original:\n";
    $payload = json_decode($p->payload_original, true);
    if (is_array($payload)) {
        print_r($payload);
    } else {
        echo "   " . substr($p->payload_original, 0, 300) . "\n";
    }
    echo "---------------------------------------------------------\n";
}

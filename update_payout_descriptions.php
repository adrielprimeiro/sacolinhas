<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;

echo "=== Updating payout descriptions in transacoes_extrato ===\n\n";

$payouts = TransacaoExtrato::whereIn('descricao', ['payout', 'PAYOUTS', 'reserve_for_payout'])->get();

echo "Encontradas " . $payouts->count() . " transações com descrição genérica 'payout'.\n";

$updated = 0;
foreach ($payouts as $p) {
    $payload = is_array($p->payload_original) ? $p->payload_original : json_decode($p->payload_original ?? '', true);
    $ref = $payload['external_reference'] ?? null;
    $refStr = ($ref && strlen($ref) > 5) ? " (Ref: " . substr($ref, 0, 12) . ")" : '';

    $novaDesc = "Transferência / Retirada Mercado Pago" . $refStr;
    $p->update(['descricao' => $novaDesc]);
    $updated++;
}

echo "Atualizadas {$updated} transações com a nova descrição!\n\n";

echo "Exemplo das transações atualizadas:\n";
$samples = TransacaoExtrato::where('descricao', 'like', '%Transferência / Retirada Mercado Pago%')->limit(10)->get();
foreach ($samples as $s) {
    echo "ID: {$s->id} | Data: {$s->data->format('Y-m-d')} | Valor: R$ {$s->valor} | Nova Desc: '{$s->descricao}'\n";
}

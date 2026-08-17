<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;

echo "=== Updating descriptions cleanly ===\n\n";

// 1. Update ID 1011 to 'Ifood.com Agencia De Restaurantes Online S.A.'
$t1011 = TransacaoExtrato::find(1011);
if ($t1011) {
    $t1011->update(['descricao' => 'Ifood.com Agencia De Restaurantes Online S.A.']);
    echo "Transação ID 1011 atualizada para: 'Ifood.com Agencia De Restaurantes Online S.A.'\n";
}

// 2. Update all other 'Transferência / Retirada Mercado Pago' or 'payout' or 'PAYOUTS' to 'Pagamento com Pix'
$payouts = TransacaoExtrato::where('descricao', 'like', '%Transferência / Retirada%')
    ->orWhereIn('descricao', ['payout', 'PAYOUTS', 'reserve_for_payout'])
    ->where('id', '!=', 1011)
    ->get();

echo "Encontradas " . $payouts->count() . " transações para atualizar para 'Pagamento com Pix'.\n";

foreach ($payouts as $p) {
    $p->update(['descricao' => 'Pagamento com Pix']);
}

echo "Atualização concluída com sucesso!\n";

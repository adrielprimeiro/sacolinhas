<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;

echo "=== Buscando Transação do Ifood / R$ 99,75 no Banco ===\n\n";

$transacoes = TransacaoExtrato::where('valor', 99.75)
    ->orWhere('valor_bruto', 99.75)
    ->orWhere('descricao', 'like', '%ifood%')
    ->orWhere('descricao', 'like', '%Pagamento com Pix%')
    ->get();

foreach ($transacoes as $t) {
    echo "ID: #{$t->id} | FitID: {$t->fitid} | Data: {$t->data} | Tipo: {$t->tipo} | Valor: R$ {$t->valor} | Desc: {$t->descricao}\n";
    echo "Payload Original:\n";
    print_r(is_string($t->payload_original) ? json_decode($t->payload_original, true) : $t->payload_original);
    echo "---------------------------------------------------------\n";
}

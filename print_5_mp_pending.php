<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;

$pendingMp = TransacaoExtrato::where('origem', 'mercadopago')
    ->where('status', 'pendente')
    ->orderByDesc('id')
    ->get();

echo "Total Pendentes MP: " . $pendingMp->count() . "\n\n";

foreach ($pendingMp as $t) {
    echo "ID: {$t->id}\n";
    echo "FITID: {$t->fitid}\n";
    echo "Data: {$t->data}\n";
    echo "Tipo: {$t->tipo}\n";
    echo "Valor: R$ {$t->valor}\n";
    echo "Valor Bruto: R$ {$t->valor_bruto}\n";
    echo "Valor Taxa: R$ {$t->valor_taxa}\n";
    echo "Valor Liquido: R$ {$t->valor_liquido}\n";
    echo "Descricao: {$t->descricao}\n";
    echo "CreatedAt: {$t->created_at}\n";
    echo "Payload: " . json_encode($t->payload_original, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo "=========================================================\n\n";
}

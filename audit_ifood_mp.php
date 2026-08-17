<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use Illuminate\Support\Facades\DB;

echo "=== Searching transacoes_extrato for 'ifood' or recent MP transactions ===\n\n";

$ifoodTrans = TransacaoExtrato::where('descricao', 'like', '%ifood%')
    ->orWhere('payload', 'like', '%ifood%')
    ->get();

echo "Transações encontradas com 'ifood' no extrato: " . $ifoodTrans->count() . "\n";
foreach ($ifoodTrans as $t) {
    echo "ID: {$t->id} | Data: {$t->data} | Origem: {$t->origem} | Tipo: {$t->tipo} | Valor: R$ {$t->valor} | Status: {$t->status} | Desc: {$t->descricao}\n";
}

echo "\n=== Ultimas 20 transacoes do Mercado Pago no banco ===\n";
$latestMp = TransacaoExtrato::where('origem', 'mercadopago')
    ->orderByDesc('data')
    ->orderByDesc('id')
    ->limit(20)
    ->get();

foreach ($latestMp as $t) {
    echo "ID: {$t->id} | Data: {$t->data->format('Y-m-d H:i')} | Tipo: {$t->tipo} | Valor: R$ {$t->valor} | Status: {$t->status} | Desc: {$t->descricao}\n";
}

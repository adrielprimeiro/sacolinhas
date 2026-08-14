<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;

$reserves = TransacaoExtrato::where('origem', 'mercadopago')
    ->where(function($q) {
        $q->where('descricao', 'like', '%reserve_for_%')
          ->orWhere('fitid', 'like', '%reserve_for_%');
    })
    ->get();

echo "Total transacoes de reserva no banco: " . $reserves->count() . "\n\n";

foreach ($reserves as $r) {
    echo "ID: {$r->id} | FITID: {$r->fitid} | Data: {$r->data} | Tipo: {$r->tipo} | Valor: R$ {$r->valor} | Status: {$r->status} | MovID: {$r->movimentacao_id} | Desc: {$r->descricao}\n";
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use App\Models\ContaBancaria;
use Illuminate\Support\Facades\DB;

echo "=== Ultimas 20 Transacoes no Extrato (Origem MP ou Inter ou data >= 2026-08-11) ===\n";
$transacoes = TransacaoExtrato::where('data', '>=', '2026-08-10')
    ->orWhere('origem', 'mercadopago')
    ->orderByDesc('data')
    ->orderByDesc('id')
    ->take(30)
    ->get();

foreach ($transacoes as $t) {
    echo "ID: {$t->id} | Data: {$t->data} | Origem: {$t->origem} | Tipo: {$t->tipo} | Valor: R$ {$t->valor} | Status: {$t->status} | Desc: {$t->descricao}\n";
}

echo "\n=== Contas Bancarias ===\n";
$contas = ContaBancaria::all();
foreach ($contas as $c) {
    echo "Conta #{$c->id} | Nome: {$c->nome} | Banco: {$c->banco} | Agencia: {$c->agencia} | Conta: {$c->conta}\n";
}

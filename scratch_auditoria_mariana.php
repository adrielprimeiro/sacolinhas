<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Sacolinhas;
use App\Models\ContaCorrente;

$user = User::where('name', 'LIKE', '%Mariana Holman%')->first();
if (!$user) { echo "Cliente não encontrada.\n"; exit; }

echo "Cliente: " . $user->name . " (ID: " . $user->id . ")\n\n";

// 1. Investigar as Sacolinhas (Totais)
$sacolinhas = Sacolinhas::where('user_id', $user->id)
    ->where('status', '!=', 'pedido') // Todas que estão na sacolinha
    ->get();

$totalItens = 0;
$valorTotalSacolinha = 0;
echo "=== ITENS NA SACOLINHA ===\n";
foreach ($sacolinhas as $s) {
    $totalItem = $s->price * $s->quantity;
    echo "Sacolinha #{$s->id} | Live ID: {$s->live_id} | Status: {$s->status} | Qtd: {$s->quantity} | Valor Unit: R$ {$s->price} | Total: R$ {$totalItem}\n";
    $totalItens += $s->quantity;
    $valorTotalSacolinha += $totalItem;
}
echo "-> TOTAL DE ITENS: {$totalItens} | VALOR TOTAL: R$ {$valorTotalSacolinha}\n\n";

// 2. Investigar a Conta Corrente (Carteira)
$movimentacoes = ContaCorrente::where('user_id', $user->id)
    ->orderBy('data_movimentacao')
    ->orderBy('id')
    ->get();

echo "=== HISTÓRICO DA CARTEIRA ===\n";
$saldoCalculado = 0;
foreach ($movimentacoes as $mov) {
    if ($mov->tipo_movimentacao === 'credito') {
        $saldoCalculado += $mov->valor;
    } else {
        $saldoCalculado -= $mov->valor;
    }
    echo "ID: {$mov->id} | Data: {$mov->data_movimentacao} | Tipo: {$mov->tipo_movimentacao} | Valor: R$ {$mov->valor} | Saldo Após: R$ {$saldoCalculado} | Desc: {$mov->descricao}\n";
}
echo "-> SALDO CALCULADO DA CARTEIRA: R$ {$saldoCalculado}\n";

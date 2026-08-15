<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

$user = User::where('name', 'like', '%Marisa%Agostinho%')->first();
if (!$user) {
    echo "Usuario Marisa nao encontrado pelo nome completo, buscando por Marisa...\n";
    $users = User::where('name', 'like', '%Marisa%')->get();
    foreach ($users as $u) {
        echo "ID: {$u->id} | Nome: {$u->name} | Email: {$u->email}\n";
    }
    if ($users->count() > 0) $user = $users->first();
}

if (!$user) {
    echo "Marisa nao encontrada.\n";
    exit;
}

echo "=== Marisa Agostinho de Souza (User ID {$user->id}) ===\n\n";

// 1) Registro em cliente_limites
$limiteRow = DB::table('cliente_limites')->where('user_id', $user->id)->first();
echo "--- Registro em cliente_limites ---\n";
print_r($limiteRow);

// 2) Sacolinha ativa
$sacolinhaItems = DB::table('sacolinhas')
    ->where('user_id', $user->id)
    ->where('status', '!=', 'pedido')
    ->get();

echo "\n--- Sacolinha Ativa (" . $sacolinhaItems->count() . " registros de itens) ---\n";
$somaSacolinha = $sacolinhaItems->sum('price');
echo "Soma dos preços dos itens na sacolinha ativa: R$ " . number_format($somaSacolinha, 2, ',', '.') . "\n";

// 3) Todos os itens na sacolinha (por status)
$byStatus = DB::table('sacolinhas')
    ->where('user_id', $user->id)
    ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(price) as total'))
    ->groupBy('status')
    ->get();

echo "\n--- Resumo de Itens por Status na Tabela sacolinhas ---\n";
foreach ($byStatus as $bs) {
    echo "Status '{$bs->status}': {$bs->count} itens | Total: R$ " . number_format($bs->total, 2, ',', '.') . "\n";
}

// 4) Pedidos de Marisa
$pedidos = DB::table('pedidos')
    ->where('user_id', $user->id)
    ->get();

echo "\n--- Pedidos de Marisa (" . $pedidos->count() . " pedidos) ---\n";
foreach ($pedidos as $p) {
    echo "Pedido #{$p->id} | Numero: {$p->numero_pedido} | Status: {$p->status_pedido} | Pagamento: {$p->status_pagamento} | Total: R$ {$p->valor_total} | AbertoEm: {$p->created_at}\n";
}

// 5) Ultimas movimentacoes da Conta Corrente (Carteira)
$ultimaCC = DB::table('conta_corrente')
    ->where('user_id', $user->id)
    ->orderByDesc('id')
    ->first();
echo "\n--- Ultima Movimentacao na Conta Corrente ---\n";
print_r($ultimaCC);

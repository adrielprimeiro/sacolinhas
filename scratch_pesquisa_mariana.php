<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Live;
use App\Models\Pedido;
use App\Models\Sacolinhas;

$user = User::where('name', 'LIKE', '%Mariana Holman%')->first();
if (!$user) { echo "Cliente não encontrada.\n"; exit; }

echo "Cliente: " . $user->name . " (ID: " . $user->id . ")\n\n";

$lives = Live::where('data', '>', '2026-06-19')->orderBy('data')->get();

foreach ($lives as $live) {
    $pedidos = Pedido::where('user_id', $user->id)->where('live_id', $live->id)->get();
    
    // Pegando sacolinhas sem o global scope active para ver até as que viraram pedido
    $sacolinhas = Sacolinhas::withoutGlobalScope('active')->where('user_id', $user->id)->where('live_id', $live->id)->get();
    
    if ($pedidos->count() > 0 || $sacolinhas->count() > 0) {
        echo "Live: " . $live->nome . " (ID: " . $live->id . ") - Data: " . $live->data . "\n";
        echo "  - Pedidos vinculados diretamente (" . $pedidos->count() . "):\n";
        foreach ($pedidos as $p) {
             echo "    ID: {$p->id} | Status Pgt: {$p->status_pagamento} | Total: R$ {$p->valor_total}\n";
        }
        echo "  - Itens Sacolinha (" . $sacolinhas->count() . "):\n";
        $soma = 0;
        foreach ($sacolinhas as $s) {
             echo "    ID: {$s->id} | Status: {$s->status} | Preço: {$s->price} | Qtd: {$s->quantity}\n";
             $soma += ($s->price * $s->quantity);
        }
        echo "    Soma Itens: R$ {$soma}\n";
        echo "--------------------------\n";
    }
}

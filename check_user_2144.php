<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user_id = 2144;
$pessoa = \App\Models\Pessoa::where('user_id', $user_id)->first();
if ($pessoa) {
    echo "Pessoa ID: {$pessoa->id}\n";
    $lancamentos = \App\Models\Lancamento::where('pessoa_id', $pessoa->id)->get();
    foreach($lancamentos as $l) {
        echo "Lançamento ID: {$l->id} | Tipo: {$l->tipo} | Valor: {$l->valor_total} | Classificacao: {$l->classificacao_financeira_id} | Status: {$l->status}\n";
        foreach($l->movimentacoes as $m) {
            echo "  - Mov: {$m->id} | Valor: {$m->valor_pago} | Forma: {$m->forma_pagamento}\n";
        }
    }
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use App\Models\Movimentacao;
use App\Models\Lancamento;
use Illuminate\Support\Facades\DB;

echo "=== Ultimas 10 Transacoes de Extrato Conciliadas ===\n";
$transacoes = TransacaoExtrato::where('status', 'conciliado')
    ->orderByDesc('updated_at')
    ->take(10)
    ->get();

foreach ($transacoes as $t) {
    echo "Transacao #{$t->id} | Data: {$t->data} | Valor: R$ {$t->valor} | Tipo: {$t->tipo} | Desc: {$t->descricao}\n";
    $movs = Movimentacao::where('transacao_extrato_id', $t->id)->orWhere('id', $t->movimentacao_id)->get();
    echo "  Movimentacoes (" . $movs->count() . "):\n";
    foreach ($movs as $m) {
        $l = $m->lancamento;
        $classificacaoNome = $l?->classificacaoFinanceira?->nome ?? 'S/C';
        $pessoaNome = $l?->pessoa?->nome ?? 'S/P';
        echo "    Mov #{$m->id} | Valor: R$ {$m->valor_pago} | Lanc #{$m->lancamento_id} (Tipo: {$l?->tipo}, Class: {$classificacaoNome}, Pessoa: {$pessoaNome}, RefTipo: {$l?->referencia_tipo})\n";
    }
}

echo "\n=== Ultimas 5 Mensalidades do Clube ===\n";
$mensalidades = DB::table('clube_mensalidades as cm')
    ->join('users as u', 'u.id', '=', 'cm.user_id')
    ->select('cm.*', 'u.name')
    ->orderByDesc('cm.created_at')
    ->take(5)
    ->get();

foreach ($mensalidades as $m) {
    echo "Mensalidade #{$m->id} | User: {$m->name} (#{$m->user_id}) | Competencia: {$m->competencia_mes}/{$m->competencia_ano} | Status: {$m->status_pagamento} | Valor: R$ {$m->valor} | PagoEm: {$m->pago_em}\n";
}

echo "\n=== Ultimas 10 Movimentacoes de Conta Corrente (Carteira) ===\n";
$cc = DB::table('conta_corrente as cc')
    ->join('users as u', 'u.id', '=', 'cc.user_id')
    ->select('cc.*', 'u.name')
    ->orderByDesc('cc.id')
    ->take(10)
    ->get();

foreach ($cc as $c) {
    echo "CC #{$c->id} | User: {$c->name} (#{$c->user_id}) | Tipo: {$c->tipo_movimentacao} | Valor: R$ {$c->valor} | RefTipo: {$c->referencia_tipo} | RefId: {$c->referencia_id} | Desc: {$c->descricao}\n";
}

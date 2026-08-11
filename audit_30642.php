<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Movimentacao;
use App\Models\Lancamento;
use Illuminate\Support\Facades\DB;

$movs = Movimentacao::where('valor_pago', 306.42)->get();

echo "=== Movimentacoes de R$ 306.42 (" . $movs->count() . ") ===\n";
foreach ($movs as $m) {
    echo "Mov #{$m->id} | Valor: R$ {$m->valor_pago} | Forma: {$m->forma_pagamento} | ContaID: {$m->conta_bancaria_id} | CreatedAt: {$m->created_at} | UpdatedAt: {$m->updated_at}\n";
    $l = Lancamento::find($m->lancamento_id);
    if ($l) {
        echo "  Lanc #{$l->id} | Tipo: {$l->tipo} | Status: {$l->status} | ClassID: {$l->classificacao_financeira_id} | PessoaID: {$l->pessoa_id} ({$l->pessoa?->nome}) | UserID (Pessoa): {$l->pessoa?->user_id} | Desc: {$l->descricao} | RefTipo: {$l->referencia_tipo} | RefID: {$l->referencia_id} | CreatedAt: {$l->created_at}\n";
    }
}

// Tambem checar em conta_corrente se houve transferencia entre carteiras ou algo do tipo
$cc = DB::table('conta_corrente')->where('valor', 306.42)->get();
echo "\n=== Conta Corrente (Carteira) com R$ 306.42 (" . $cc->count() . ") ===\n";
foreach ($cc as $c) {
    echo "CC #{$c->id} | UserID: {$c->user_id} | Tipo: {$c->tipo_movimentacao} | Valor: R$ {$c->valor} | RefTipo: {$c->referencia_tipo} | RefID: {$c->referencia_id} | Desc: {$c->descricao} | CreatedAt: {$c->created_at}\n";
}

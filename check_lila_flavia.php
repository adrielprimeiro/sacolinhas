<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Pessoa;
use Illuminate\Support\Facades\DB;

$pessoa = Pessoa::where('nome', 'like', '%Lila Flavia%')->first();
if (!$pessoa) {
    echo "Pessoa Lila Flavia nao encontrada.\n";
    exit;
}

echo "Pessoa: ID {$pessoa->id} | Nome: {$pessoa->nome} | UserID: {$pessoa->user_id}\n";

$user = User::find($pessoa->user_id);
if ($user) {
    echo "User: {$user->name} | Email: {$user->email}\n";
    echo "Saldo Carteira Atual: R$ {$user->saldo_carteira}\n";
}

$mensalidades = DB::table('clube_mensalidades')
    ->where('user_id', $pessoa->user_id)
    ->get();

echo "\nMensalidades do Clube (" . $mensalidades->count() . "):\n";
foreach ($mensalidades as $m) {
    echo "  Competencia: {$m->competencia_mes}/{$m->competencia_ano} | Status: {$m->status_pagamento} | Valor: R$ {$m->valor} | PagoEm: {$m->pago_em}\n";
}

$cc = DB::table('conta_corrente')
    ->where('user_id', $pessoa->user_id)
    ->orderByDesc('id')
    ->take(10)
    ->get();

echo "\nConta Corrente (" . $cc->count() . " registros):\n";
foreach ($cc as $c) {
    echo "  CC #{$c->id} | Tipo: {$c->tipo_movimentacao} | Valor: R$ {$c->valor} | RefTipo: {$c->referencia_tipo} | RefId: {$c->referencia_id} | Desc: {$c->descricao}\n";
}

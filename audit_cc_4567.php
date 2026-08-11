<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Informacoes sobre CC #4567 (andreia paola de souza) ===\n";
$cc1 = DB::table('conta_corrente')->where('id', 4567)->first();
print_r($cc1);

echo "\n=== Informacoes sobre CC #4568 (Gabriela Agapito) ===\n";
$cc2 = DB::table('conta_corrente')->where('id', 4568)->first();
print_r($cc2);

echo "\n=== Historico de lancamentos em conta_corrente das 08:00 as 09:00 hoje ===\n";
$ccs = DB::table('conta_corrente')
    ->whereBetween('created_at', ['2026-08-11 08:00:00', '2026-08-11 09:00:00'])
    ->get();

foreach ($ccs as $c) {
    echo "ID: {$c->id} | User: {$c->user_id} | Tipo: {$c->tipo_movimentacao} | Valor: R$ {$c->valor} | RefTipo: {$c->referencia_tipo} | RefID: {$c->referencia_id} | Desc: {$c->descricao} | CreatedAt: {$c->created_at}\n";
}

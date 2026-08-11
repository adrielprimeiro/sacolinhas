<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ClassificacaoFinanceira;

$classificacoes = ClassificacaoFinanceira::where('nome', 'like', '%clube%')
    ->orWhere('nome', 'like', '%recarga%')
    ->orWhere('nome', 'like', '%carteira%')
    ->orWhere('nome', 'like', '%aporte%')
    ->get();

foreach ($classificacoes as $c) {
    echo "ID: {$c->id} | Cod: {$c->codigo_contabil} | Nome: {$c->nome} | Tipo: {$c->tipo_natureza}\n";
}

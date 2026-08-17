<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pessoa;
use App\Models\User;

echo "=== Exemplo de Pessoas com User, Apelido e Instagram ===\n";
$pessoas = Pessoa::with('user')->has('user')->limit(10)->get();

foreach ($pessoas as $p) {
    echo "Pessoa #{$p->id} | Nome: {$p->nome} | User ID: {$p->user_id}\n";
    if ($p->user) {
        echo "   User Name: {$p->user->name} | Apelido: {$p->user->apelido} | IG: {$p->user->instagram} | CPF: {$p->user->cpf}\n";
    }
    echo "--------------------------------------------------\n";
}

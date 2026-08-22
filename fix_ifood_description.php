<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;

echo "=== Atualizando Descrição da Transação #1086 (Ifood R$ 99,75) ===\n\n";

$t = TransacaoExtrato::find(1086);
if ($t) {
    echo "Descrição anterior: {$t->descricao}\n";
    $t->update([
        'descricao' => 'Ifood.com Agencia De Restaurantes Online S.A.'
    ]);
    echo "Nova descrição: {$t->descricao}\n";
} else {
    echo "Transação #1086 não encontrada.\n";
}

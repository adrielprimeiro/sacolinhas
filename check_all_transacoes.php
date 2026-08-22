<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;

echo "=== Resumo de Transações no Banco de Dados ===\n\n";

echo "Total em TransacaoExtrato: " . TransacaoExtrato::count() . "\n";
echo "Pendentes: " . TransacaoExtrato::where('status', 'pendente')->count() . "\n";
echo "Conciliados: " . TransacaoExtrato::where('status', 'conciliado')->count() . "\n\n";

$ultimas = TransacaoExtrato::orderBy('data', 'desc')->take(20)->get();

echo "Últimas 20 Transações:\n";
foreach ($ultimas as $t) {
    echo "#{$t->id} | Conta {$t->conta_bancaria_id} | Data: {$t->data} | Tipo: {$t->tipo} | Status: {$t->status} | Valor: R$ " . number_format($t->valor, 2, ',', '.') . " | Desc: {$t->descricao}\n";
}

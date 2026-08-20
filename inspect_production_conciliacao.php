<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;

echo "=== INSPEÇÃO DA TELA DE CONCILIAÇÃO (transacoes_extrato com status = pendente) ===\n\n";

$pendentes = TransacaoExtrato::where('status', 'pendente')->orderBy('data', 'desc')->get();

echo "Total de transações pendentes no extrato: " . $pendentes->count() . "\n\n";

foreach ($pendentes as $p) {
    echo "ID: {$p->id} | Data: {$p->data->format('Y-m-d')} | Origem: {$p->origem} | Tipo: {$p->tipo} | Valor: R$ {$p->valor} | Descrição: '{$p->descricao}'\n";
}

echo "\n=== Verificando todas as transações com origem = mercadopago recente ===\n";
$mpRecentes = TransacaoExtrato::where('origem', 'mercadopago')->orderBy('id', 'desc')->limit(15)->get();

foreach ($mpRecentes as $mp) {
    echo "ID: {$mp->id} | Data: {$mp->data->format('Y-m-d')} | Status: {$mp->status} | Tipo: {$mp->tipo} | Valor: R$ {$mp->valor} | Descrição: '{$mp->descricao}'\n";
}

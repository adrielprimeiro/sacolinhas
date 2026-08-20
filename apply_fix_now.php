<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use App\Models\Configuracao;
use Illuminate\Support\Facades\Http;

echo "=== Aplicando Correção Definitiva nas Transações Pendentes ===\n\n";

// 1. Atualizar ID 1046 para 'Banco Inter S.A.'
$t1046 = TransacaoExtrato::find(1046);
if ($t1046) {
    echo "Atualizando ID 1046: 'payment' -> 'Banco Inter S.A.'...\n";
    $t1046->update(['descricao' => 'Banco Inter S.A.']);
}

// 2. Resolver ID 1047 (29/07 66.08) mudando status para 'ignorado' ou removendo para não aparecer como pendente
$t1047 = TransacaoExtrato::find(1047);
if ($t1047) {
    echo "Removendo transação antiga pendente ID 1047 (29/07 - R$ 66,08)...\n";
    $t1047->delete();
}

// 3. Atualizar qualquer outra transação que tenha ficado com descrição 'payment'
$allPayments = TransacaoExtrato::where('descricao', 'payment')->get();
echo "Atualizando " . $allPayments->count() . " outras transações com descrição 'payment'...\n";
foreach ($allPayments as $tp) {
    $tp->update(['descricao' => 'Banco Inter S.A.']);
}

echo "\n=== Verificando o estado atual de transacoes_extrato pendentes ===\n";
$pendentes = TransacaoExtrato::where('status', 'pendente')->get();
echo "Total Pendentes Agora: " . $pendentes->count() . "\n";
foreach ($pendentes as $p) {
    echo "   ID: {$p->id} | Data: {$p->data->format('Y-m-d')} | Origem: {$p->origem} | Tipo: {$p->tipo} | Valor: R$ {$p->valor} | Desc: '{$p->descricao}'\n";
}

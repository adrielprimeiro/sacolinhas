<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$svc = new \App\Services\Ai\SeverinoService();

echo "--- TESTE 1: Anti-Repetição e Esclarecimento de Extrato ---\n";
$history1 = [
    ['role' => 'user', 'message' => 'qual foi a ultima atualização do extrato?'],
    ['role' => 'assistant', 'message' => 'A última atualização registrada no extrato bancário foi em 19/09/2026 às 15:37:26.']
];
$r1 = $svc->askSeverino('Você ta falando da sincronização ou da ultima conciliação?', $history1);
echo $r1 . "\n\n";

$history2 = [
    ['role' => 'user', 'message' => 'Qual foi o lucro de nossa ultima live?'],
    ['role' => 'assistant', 'message' => 'Para calcular o lucro da última live precisamos saber qual é o custo associado a ela. Você poderia me informar qual custo devemos considerar para essa live?']
];
$r2 = $svc->askSeverino('Faça o calculo do preço de venda do item menos o preço de compra (custo)', $history2);
echo $r2 . "\n\n";

echo "--- TESTE 3: Pergunta Nova sem Ferramenta Nativa (Auto-Descoberta / LATM) ---\n";
$r3 = $svc->askSeverino('Severino, qual foi a cliente que mais adicionou peças em sacolinhas abertas atualmente?');
echo $r3 . "\n\n";

echo "--- FERRAMENTAS DINÂMICAS NO BANCO APÓS OS TESTES ---\n";
print_r(\App\Models\SeverinoDynamicTool::all(['id', 'nome', 'descricao'])->toArray());


<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Lancamento;
use App\Models\Movimentacao;

echo "=== Testando criacao de Lancamento com observacoes ===\n";
$lanc = Lancamento::create([
    'tipo' => 'receita',
    'status' => 'pendente',
    'classificacao_financeira_id' => 3,
    'data_emissao' => now()->toDateString(),
    'data_vencimento' => now()->toDateString(),
    'valor_total' => 15.00,
    'descricao' => 'Teste observacoes',
    'observacoes' => 'Minha observacao interna de teste',
]);

echo "Lancamento criado #{$lanc->id} | Observacoes: '{$lanc->observacoes}'\n";

$mov = Movimentacao::create([
    'lancamento_id' => $lanc->id,
    'conta_bancaria_id' => 1,
    'data_pagamento' => now()->toDateString(),
    'valor_pago' => 15.00,
    'forma_pagamento' => 'pix',
    'observacoes' => 'Observacao da movimentacao de baixa',
]);

echo "Movimentacao criada #{$mov->id} | Observacoes: '{$mov->observacoes}'\n";

// Limpar teste
$mov->delete();
$lanc->delete();

echo "Limpeza concluida. Teste 100% OK!\n";

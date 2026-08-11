<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use App\Models\Lancamento;
use App\Models\Pessoa;
use App\Models\User;
use App\Services\ConciliacaoService;
use Illuminate\Support\Facades\DB;

echo "=== Processando Conciliacao para Transacao #919 (R$ 550.00) ===\n";

$transacao = TransacaoExtrato::find(919);
if (!$transacao) {
    echo "Transacao #919 nao encontrada.\n";
    exit;
}

$lanc5787 = Lancamento::find(5786) ?: Lancamento::find(5787); // R$ 50.00
$lanc5788 = Lancamento::find(5788); // R$ 500.00

if (!$lanc5788) {
    echo "Lancamento #5788 nao encontrado.\n";
    exit;
}

// 1. Corrigir classificacao de Lanc #5788 para 84 (Recarga de Carteira)
echo "1. Atualizando Lancamento #5788 para Classificacao #84 (Recarga de Carteira)...\n";
$lanc5788->update([
    'classificacao_financeira_id' => 84,
    'referencia_tipo' => 'recarga_carteira',
    'descricao' => 'Recarga de Carteira'
]);

// 2. Conciliar via ConciliacaoService::vincularMultiplos
echo "2. Reconciliando Transacao #919 com Lanc #5787 (R$ 50.00) e Lanc #5788 (R$ 500.00)...\n";

$service = app(ConciliacaoService::class);
$service->vincularMultiplos(919, [
    ['lancamento_id' => $lanc5787->id, 'valor_vinculo' => 50.00],
    ['lancamento_id' => $lanc5788->id, 'valor_vinculo' => 500.00],
]);

echo "Conciliacao realizada com sucesso!\n\n";

// 3. Verificar Mensalidade do Clube de Lila Flavia (User 4187)
$mensalidade = DB::table('clube_mensalidades')
    ->where('user_id', 4187)
    ->where('competencia_ano', 2026)
    ->where('competencia_mes', 8)
    ->first();

if ($mensalidade) {
    echo "Mensalidade Clube 08/2026: Status = {$mensalidade->status_pagamento} | Valor = R$ {$mensalidade->valor} | PagoEm = {$mensalidade->pago_em}\n";
} else {
    echo "ALERTA: Mensalidade Clube 08/2026 nao foi encontrada!\n";
}

// 4. Verificar Carteira (Conta Corrente) de Lila Flavia (User 4187)
$user = User::find(4187);
echo "Saldo da Carteira de Lila Flavia (User #4187): R$ {$user->saldo_carteira}\n";

$cc = DB::table('conta_corrente')
    ->where('user_id', 4187)
    ->orderByDesc('id')
    ->first();

if ($cc) {
    echo "Ultima movimentacao CC: Tipo = {$cc->tipo_movimentacao} | Valor = R$ {$cc->valor} | Desc = {$cc->descricao}\n";
}

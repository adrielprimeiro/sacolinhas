<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use App\Models\Lancamento;
use App\Models\Movimentacao;
use App\Models\User;
use App\Services\ConciliacaoService;
use Illuminate\Support\Facades\DB;

echo "=== Reprocessando Conciliacao para Transacao #919 (Lila Flavia) ===\n";

$transacao = TransacaoExtrato::find(919);
if (!$transacao) {
    echo "Transacao #919 nao encontrada.\n";
    exit;
}

// Resetar status da transacao se ja conciliada
DB::table('transacoes_extrato')->where('id', 919)->update(['status' => 'pendente', 'movimentacao_id' => null]);

// Excluir movimentacoes antigas da transacao 919
$movsAntigas = Movimentacao::where('transacao_extrato_id', 919)->get();
foreach ($movsAntigas as $m) {
    $m->delete();
}

$lanc5787 = Lancamento::find(5787); // R$ 50.00 - Lila Flavia - Clube Mania
$lanc5788 = Lancamento::find(5788); // R$ 500.00 - Lila Flavia - Recarga de Carteira

if (!$lanc5787 || !$lanc5788) {
    echo "Lancamentos #5787 ou #5788 nao encontrados.\n";
    exit;
}

// 1. Garantir classificacoes e pessoas corretas
$lanc5787->update([
    'classificacao_financeira_id' => 82, // Clube Mania
    'pessoa_id' => 19, // Lila Flavia
    'descricao' => 'Clube Mania - Lila Flavia'
]);

$lanc5788->update([
    'classificacao_financeira_id' => 84, // Recarga de Carteira
    'pessoa_id' => 19, // Lila Flavia
    'referencia_tipo' => 'recarga_carteira',
    'descricao' => 'Recarga de Carteira - Lila Flavia'
]);

// 2. Conciliar via ConciliacaoService::vincularMultiplos
echo "Vincular Transacao 919 com Lanc 5787 (R$ 50) e Lanc 5788 (R$ 500)...\n";
$service = app(ConciliacaoService::class);
$service->vincularMultiplos(919, [
    ['lancamento_id' => $lanc5787->id, 'valor_vinculo' => 50.00],
    ['lancamento_id' => $lanc5788->id, 'valor_vinculo' => 500.00],
]);

echo "Conciliacao concluida com sucesso!\n\n";

// 3. Verificar Mensalidade do Clube de Lila Flavia (User 4187)
$mensalidade = DB::table('clube_mensalidades')
    ->where('user_id', 4187)
    ->where('competencia_ano', 2026)
    ->where('competencia_mes', 8)
    ->first();

if ($mensalidade) {
    echo "✅ Mensalidade Clube 08/2026: Status = {$mensalidade->status_pagamento} | Valor = R$ {$mensalidade->valor} | PagoEm = {$mensalidade->pago_em}\n";
} else {
    echo "❌ Mensalidade Clube 08/2026 nao foi encontrada!\n";
}

// 4. Verificar Carteira de Lila Flavia (User 4187)
$user = User::find(4187);
echo "✅ Saldo da Carteira de Lila Flavia (User #4187): R$ {$user->saldo_carteira}\n";

$cc = DB::table('conta_corrente')
    ->where('user_id', 4187)
    ->orderByDesc('id')
    ->first();

if ($cc) {
    echo "✅ Ultima movimentacao CC: Tipo = {$cc->tipo_movimentacao} | Valor = R$ {$cc->valor} | Desc = {$cc->descricao}\n";
}

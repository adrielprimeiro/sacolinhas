<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use App\Models\Lancamento;
use Illuminate\Support\Facades\DB;

echo "=== Ultimas 20 Transacoes de Extrato (todas) ===\n";
$transacoes = TransacaoExtrato::orderByDesc('updated_at')->take(20)->get();

foreach ($transacoes as $t) {
    echo "Transacao #{$t->id} | Status: {$t->status} | Data: {$t->data} | Valor: R$ {$t->valor} | Tipo: {$t->tipo} | Desc: {$t->descricao}\n";
    $movs = DB::table('movimentacoes')->where('transacao_extrato_id', $t->id)->orWhere('id', $t->movimentacao_id)->get();
    foreach ($movs as $m) {
        $l = Lancamento::find($m->lancamento_id);
        $classificacaoNome = $l?->classificacaoFinanceira?->nome ?? 'S/C';
        $pessoaNome = $l?->pessoa?->nome ?? 'S/P';
        echo "  -> Mov #{$m->id} | Valor: R$ {$m->valor_pago} | Lanc #{$m->lancamento_id} (Class: {$classificacaoNome}, Pessoa: {$pessoaNome})\n";
    }
}

echo "\n=== Ultimos 10 Lancamentos Pendentes ===\n";
$lancamentos = Lancamento::where('status', 'pendente')->orderByDesc('id')->take(10)->get();
foreach ($lancamentos as $l) {
    $classificacaoNome = $l->classificacaoFinanceira?->nome ?? 'S/C';
    $pessoaNome = $l->pessoa?->nome ?? 'S/P';
    echo "Lanc #{$l->id} | Status: {$l->status} | Valor: R$ {$l->valor_total} | Class: {$classificacaoNome} | Pessoa: {$pessoaNome} | Desc: {$l->descricao}\n";
}

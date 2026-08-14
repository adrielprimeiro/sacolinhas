<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use App\Models\ContaBancaria;
use App\Models\Lancamento;
use App\Models\ClassificacaoFinanceira;
use App\Services\ConciliacaoService;
use Illuminate\Support\Facades\DB;

echo "=== Processando Transferencia de R$ 30.00 (Mercado Pago -> Inter) ===\n";

$contaMp = ContaBancaria::where('nome', 'like', '%Mercado Pago%')->first();
$contaMpId = $contaMp ? $contaMp->id : 2;

// 1. Criar ou buscar a transação de SAÍDA no Mercado Pago
$transSaidaMp = TransacaoExtrato::updateOrCreate(
    ['fitid' => 'mp_pix_sent_20260814_3000'],
    [
        'data'              => '2026-08-14',
        'descricao'         => 'Pix enviado - Eloalef Comercio Ltda',
        'valor'             => 30.00,
        'valor_bruto'       => 30.00,
        'valor_taxa'        => 0.00,
        'valor_liquido'     => 30.00,
        'tipo'              => 'saida',
        'origem'            => 'mercadopago',
        'status'            => 'pendente',
        'conta_bancaria_id' => $contaMpId,
    ]
);

echo "Transacao Saida Mercado Pago: ID #{$transSaidaMp->id} | Status: {$transSaidaMp->status} | Valor: R$ {$transSaidaMp->valor}\n";

// 2. Buscar a transação de ENTRADA no Banco Inter (ID 966)
$transEntradaInter = TransacaoExtrato::find(966);
if (!$transEntradaInter) {
    echo "Transacao Entrada Inter ID #966 nao encontrada.\n";
    exit;
}

echo "Transacao Entrada Banco Inter: ID #{$transEntradaInter->id} | Status: {$transEntradaInter->status} | Valor: R$ {$transEntradaInter->valor}\n";

// Resetar status caso necessario para poder conciliar via controller/service
$transSaidaMp->update(['status' => 'pendente', 'movimentacao_id' => null]);
$transEntradaInter->update(['status' => 'pendente', 'movimentacao_id' => null]);

// 3. Executar a conciliação de Transferência entre Contas
$catTransferencia = ClassificacaoFinanceira::where('nome', 'Transferência entre Contas')->first();
if (!$catTransferencia) {
    echo "Categoria Transferencia entre Contas nao encontrada.\n";
    exit;
}

$service = app(ConciliacaoService::class);

DB::transaction(function () use ($transSaidaMp, $transEntradaInter, $catTransferencia, $service) {
    // Lançamento de Saída (Mercado Pago)
    $lancSaida = Lancamento::create([
        'tipo' => 'despesa',
        'status' => 'pago',
        'pessoa_id' => null,
        'classificacao_financeira_id' => $catTransferencia->id,
        'data_emissao' => $transSaidaMp->data,
        'data_vencimento' => $transSaidaMp->data,
        'valor_total' => $transSaidaMp->valor,
        'descricao' => 'Transferência enviada - ' . $transSaidaMp->descricao,
    ]);
    $service->vincular($transSaidaMp->id, $lancSaida->id);

    // Lançamento de Entrada (Banco Inter)
    $lancEntrada = Lancamento::create([
        'tipo' => 'receita',
        'status' => 'pago',
        'pessoa_id' => null,
        'classificacao_financeira_id' => $catTransferencia->id,
        'data_emissao' => $transEntradaInter->data,
        'data_vencimento' => $transEntradaInter->data,
        'valor_total' => $transEntradaInter->valor,
        'descricao' => 'Transferência recebida - ' . $transEntradaInter->descricao,
    ]);
    $service->vincular($transEntradaInter->id, $lancEntrada->id);
});

echo "✅ Transferencia de R$ 30,00 entre Mercado Pago e Banco Inter CONCILIADA COM SUCESSO!\n";

$ts = TransacaoExtrato::find($transSaidaMp->id);
$te = TransacaoExtrato::find($transEntradaInter->id);

echo "Status final Mercado Pago (Saida): {$ts->status}\n";
echo "Status final Banco Inter (Entrada): {$te->status}\n";

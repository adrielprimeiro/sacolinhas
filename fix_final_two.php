<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use App\Models\Movimentacao;
use Illuminate\Support\Facades\DB;

DB::transaction(function() {
    echo "=== 1. Tratando ID 974 (R$ 8.97) ===\n";
    $t974 = TransacaoExtrato::find(974);
    $mov5649 = Movimentacao::find(5649);
    if ($t974 && $mov5649) {
        $t974->update([
            'status' => 'conciliado',
            'movimentacao_id' => $mov5649->id
        ]);
        $mov5649->update(['transacao_extrato_id' => $t974->id]);
        echo "ID 974 conciliado com Mov #5649 OK!\n";

        // Se sobrou a 970 antiga sem uso, removemos
        $t970 = TransacaoExtrato::find(970);
        if ($t970 && $t970->status === 'pendente') {
            $t970->delete();
            echo "Transacao sobressalente #970 removida OK!\n";
        }
    }

    echo "\n=== 2. Tratando ID 975 (R$ 66.08) ===\n";
    $t975 = TransacaoExtrato::find(975);
    if ($t975 && $t975->status === 'pendente') {
        // Criar lançamento e movimentação para o pagamento do Marketplace Mercado Livre R$ 66.08
        $conciliacaoService = app(\App\Services\ConciliacaoService::class);
        $conciliacaoService->vincularNovoLancamento(
            transacaoId: 975,
            classificacaoId: 3, // Receitas de Vendas / Marketplace
            pessoaId: null,
            contaBancariaId: 2,
            observacoes: 'Recebimento Pix Mercado Livre / Marketplace'
        );
        echo "ID 975 conciliado com sucesso via vincularNovoLancamento!\n";
    }
});

echo "\n=== Verificando se restou QUALQUER pendencia no MP ===\n";
$count = TransacaoExtrato::where('origem', 'mercadopago')->where('status', 'pendente')->count();
echo "Total de pendencias MP no banco agora: {$count}\n";

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use App\Models\Movimentacao;
use Illuminate\Support\Facades\DB;

DB::transaction(function() {
    echo "=== 1. Tratando R$ 8.97 (ID 909, 969, 970) ===\n";
    // ID 909: reserve_for_payment (saida R$ 8.97, conciliado com Mov #5649)
    // ID 969: reserve_for_payment (entrada R$ 8.97, pendente)
    // ID 970: payment (saida R$ 8.97, pendente)
    $t909 = TransacaoExtrato::find(909);
    $t969 = TransacaoExtrato::find(969);
    $t970 = TransacaoExtrato::find(970);

    if ($t970 && $t909) {
        $movId = $t909->movimentacao_id;
        echo "Vinculando Movimentacao #{$movId} da transacao dummy #909 para a real #970 (payment)...\n";
        
        $t970->update([
            'status' => 'conciliado',
            'movimentacao_id' => $movId
        ]);
        if ($movId) {
            Movimentacao::where('id', $movId)->update(['transacao_extrato_id' => 970]);
        }

        echo "Removendo transacoes de reserva dummy #909 e #969...\n";
        $t909->delete();
        if ($t969) $t969->delete();
    }

    echo "\n=== 2. Tratando R$ 30.00 (ID 968, 971, 972, 973) ===\n";
    // ID 968: Transacao manual criada anteriormente (conciliada com Mov #5744)
    // ID 971: reserve_for_payout (saida R$ 30.00, pendente)
    // ID 972: reserve_for_payout (entrada R$ 30.00, pendente)
    // ID 973: payout (saida R$ 30.00, pendente) - Esta eh a real oficial do MP!
    $t968 = TransacaoExtrato::find(968);
    $t971 = TransacaoExtrato::find(971);
    $t972 = TransacaoExtrato::find(972);
    $t973 = TransacaoExtrato::find(973);

    if ($t973 && $t968) {
        $movId = $t968->movimentacao_id;
        echo "Vinculando Movimentacao #{$movId} da transacao manual #968 para a oficial #973 (payout)...\n";
        
        $t973->update([
            'status' => 'conciliado',
            'movimentacao_id' => $movId
        ]);
        if ($movId) {
            Movimentacao::where('id', $movId)->update(['transacao_extrato_id' => 973]);
        }

        echo "Removendo transacao manual #968 e reservas dummy #971 e #972...\n";
        $t968->delete();
        if ($t971) $t971->delete();
        if ($t972) $t972->delete();
    }
});

echo "\n=== Verificando pendencias restantes do MP ===\n";
$remaining = TransacaoExtrato::where('origem', 'mercadopago')->where('status', 'pendente')->get();
echo "Restantes pendentes do MP: " . $remaining->count() . "\n";
foreach ($remaining as $r) {
    echo "  - ID {$r->id} | {$r->fitid} | R$ {$r->valor} | {$r->descricao}\n";
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use App\Models\Movimentacao;
use Illuminate\Support\Facades\DB;

DB::transaction(function() {
    $t976 = TransacaoExtrato::find(976);
    $mov5744 = Movimentacao::find(5744);
    $t973 = TransacaoExtrato::find(973);

    if ($t976 && $mov5744) {
        $t976->update([
            'status' => 'conciliado',
            'movimentacao_id' => $mov5744->id
        ]);
        $mov5744->update(['transacao_extrato_id' => $t976->id]);
        echo "ID 976 (FITID 172828204187) conciliado com Mov #5744 OK!\n";

        if ($t973) {
            $t973->delete();
            echo "Transacao com sufixo antigo #973 (FITID 172828204187_2) deletada OK!\n";
        }
    }
});

echo "Pendencias MP restantes: " . TransacaoExtrato::where('origem', 'mercadopago')->where('status', 'pendente')->count() . "\n";

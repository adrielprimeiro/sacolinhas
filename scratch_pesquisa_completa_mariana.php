<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Live;
use App\Models\Sacolinhas;
use App\Models\ContaCorrente;

$user = User::where('name', 'LIKE', '%Mariana Holman%')->first();
if (!$user) { echo "Cliente não encontrada.\n"; exit; }

echo "Cliente: " . $user->name . " (ID: " . $user->id . ")\n";

// Buscar recargas / conta corrente após 19/06/2026
$movimentacoes = ContaCorrente::where('user_id', $user->id)
    ->where('data_movimentacao', '>=', '2026-06-19')
    ->orderBy('data_movimentacao')
    ->get();

echo "\n--- HISTÓRICO DE RECARGAS / CARTEIRA APÓS 19/06/2026 ---\n";
if ($movimentacoes->count() == 0) {
    echo "Nenhuma movimentação de carteira encontrada.\n";
}
foreach ($movimentacoes as $mov) {
    echo "Data: {$mov->data_movimentacao} | Tipo: {$mov->tipo_movimentacao} | Valor: R$ {$mov->valor} | Desc: {$mov->descricao}\n";
}
echo "--------------------------------------------------------\n\n";

$lives = Live::where('data', '>', '2026-06-19')->orderBy('data')->get();

echo "--- RESUMO DE LIVES E SACOLINHAS ---\n";
foreach ($lives as $live) {
    $sacolinhas = Sacolinhas::withoutGlobalScope('active')->where('user_id', $user->id)->where('live_id', $live->id)->get();
    
    if ($sacolinhas->count() > 0) {
        $dataLiveCarbon = \Carbon\Carbon::parse($live->data);
        echo "\n[Live: " . $live->nome . " ID: " . $live->id . " | Data: " . $dataLiveCarbon->format('d/m/Y') . "]\n";
        
        $soma = 0;
        foreach ($sacolinhas as $s) {
             echo "  - Sacolinha #{$s->id} | {$s->status} | R$ " . ($s->price * $s->quantity) . "\n";
             $soma += ($s->price * $s->quantity);
        }
        echo "  > Total Consumido na Live: R$ {$soma}\n";

        // Procurar recargas que aconteceram em até 5 dias após a live
        $dataFim = $dataLiveCarbon->copy()->addDays(5);
        $recargasRelacionadas = $movimentacoes->filter(function($mov) use ($dataLiveCarbon, $dataFim) {
            $dt = \Carbon\Carbon::parse($mov->data_movimentacao);
            return $dt->between($dataLiveCarbon, $dataFim) && $mov->tipo_movimentacao === 'credito';
        });

        if ($recargasRelacionadas->count() > 0) {
            echo "  > Recargas encontradas logo após a live:\n";
            foreach ($recargasRelacionadas as $r) {
                $dtR = \Carbon\Carbon::parse($r->data_movimentacao)->format('d/m/Y H:i');
                echo "      * Data: {$dtR} | Valor: R$ {$r->valor} | Desc: {$r->descricao}\n";
            }
        } else {
            echo "  > Nenhuma recarga localizada imediatamente (até 5 dias) após esta live.\n";
        }
    }
}

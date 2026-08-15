<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

echo "=== Simulando recalculação de cliente_limites filtrando status != 'pedido' ===\n\n";

$users = DB::table('cliente_limites')->pluck('user_id');

foreach ($users as $uid) {
    $totalAtivo = DB::table('sacolinhas')
        ->where('user_id', $uid)
        ->where('status', '!=', 'pedido')
        ->sum(DB::raw('price * quantity'));

    $limiteRow = DB::table('cliente_limites')->where('user_id', $uid)->first();
    if (!$limiteRow) continue;

    $limiteCredito = (float) $limiteRow->limite_credito;
    $novoUtilizado = round((float) $totalAtivo, 2);
    $novoDisponivel = round($limiteCredito - $novoUtilizado, 2);

    if ($uid == 1916) { // Marisa
        $u = User::find($uid);
        echo "USER #{$uid} - " . ($u ? $u->name : '') . ":\n";
        echo "   Limite Credito: R$ {$limiteCredito}\n";
        echo "   Utilizado ANTERIOR: R$ {$limiteRow->limite_utilizado}\n";
        echo "   Utilizado NOVO (apenas sacolinha ativa): R$ {$novoUtilizado}\n";
        echo "   Disponivel NOVO: R$ {$novoDisponivel}\n";
        echo "--------------------------------------------------\n";
    }
}

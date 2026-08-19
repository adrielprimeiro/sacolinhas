<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Live;
use App\Models\Sacolinhas;
use Illuminate\Support\Facades\DB;

echo "=== Inspecting Live 310 (Current Active Live) ===\n\n";

$live = Live::find(310);
if (!$live) {
    // Pegar a live mais recente
    $live = Live::orderByDesc('id')->first();
}

echo "Live ID: {$live->id} | Title: {$live->title} | Status: {$live->status}\n";

$sacolinhas = Sacolinhas::where('live_id', $live->id)->get();
echo "Total de Sacolinhas na Live 310: " . $sacolinhas->count() . "\n\n";

$byStatus = DB::table('sacolinhas')
    ->where('live_id', $live->id)
    ->select('status', DB::raw('COUNT(*) as qtd'), DB::raw('SUM(price * quantity) as total'))
    ->groupBy('status')
    ->get();

echo "--- Resumo por Status ---\n";
foreach ($byStatus as $b) {
    echo "Status '{$b->status}': {$b->qtd} itens | Total: R$ " . number_format($b->total, 2, ',', '.') . "\n";
}

echo "\n--- Verificando se existe alguma Sacolinha com centavos na Live 310 ---\n";
$withCents = Sacolinhas::where('live_id', $live->id)
    ->whereRaw('(MOD(price, 1) != 0 OR MOD(price * quantity, 1) != 0)')
    ->get();

echo "Sacolinhas com centavos na Live 310: " . $withCents->count() . "\n";
foreach ($withCents as $wc) {
    echo "ID: {$wc->id} | UserID: {$wc->user_id} | Price: {$wc->price} | Qty: {$wc->quantity} | Status: {$wc->status} | Code: {$wc->code}\n";
}

echo "\n--- Verificando Sacolinhas excluídas / apagadas ou modificadas hoje ---\n";
$deleted = DB::table('sacolinhas')
    ->where('live_id', $live->id)
    ->whereNotNull('deleted_at')
    ->get();
echo "Sacolinhas de-letadas: " . $deleted->count() . "\n";

echo "\n--- Verificando como o controller da Live calcula o total na view ---\n";
// Buscar onde a tela da live exibe o valor total

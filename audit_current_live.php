<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Live;
use App\Models\Sacolinhas;
use Illuminate\Support\Facades\DB;

echo "=== Lista de TODAS as Lives recentes (IDs 300 em diante) ===\n\n";

$lives = Live::where('id', '>=', 300)->orderByDesc('id')->get();

foreach ($lives as $l) {
    $count = Sacolinhas::where('live_id', $l->id)->count();
    $soma  = Sacolinhas::where('live_id', $l->id)->selectRaw('SUM(price * quantity) as total')->value('total') ?? 0;
    
    // Status das sacolinhas
    $byStatus = Sacolinhas::where('live_id', $l->id)
        ->select('status', DB::raw('COUNT(*) as total_items'), DB::raw('SUM(price * quantity) as total_val'))
        ->groupBy('status')
        ->get();

    echo "Live #{$l->id} | Title: '{$l->title}' | Status: '{$l->status}' | Date: {$l->created_at}\n";
    echo "   Total Sacolinhas: {$count} | Soma Total: R$ " . number_format($soma, 2, ',', '.') . "\n";
    foreach ($byStatus as $st) {
        echo "      -> Status '{$st->status}': {$st->total_items} itens | R$ " . number_format($st->total_val, 2, ',', '.') . "\n";
    }
    echo "---------------------------------------------------------\n";
}

echo "\n=== Verificando se existe alguma live ativa sem live_id ou com live_id nulo recente ===\n";
$nuloRecent = Sacolinhas::whereNull('live_id')->where('created_at', '>=', now()->subDays(7))->get();
echo "Sacolinhas sem live_id nos últimos 7 dias: " . $nuloRecent->count() . "\n";

echo "\n=== Verificando todas as sacolinhas criadas HOJE ou ONTEM (18/08 e 19/08) ===\n";
$todaySacolinhas = Sacolinhas::where('created_at', '>=', '2026-08-18')->orderByDesc('id')->get();
echo "Sacolinhas criadas em 18/08 ou 19/08: " . $todaySacolinhas->count() . "\n";

$somaToday = 0;
foreach ($todaySacolinhas as $ts) {
    $sub = $ts->price * $ts->quantity;
    $somaToday += $sub;
    echo "ID: {$ts->id} | LiveID: {$ts->live_id} | UserID: {$ts->user_id} | Price: {$ts->price} | Qty: {$ts->quantity} | Status: {$ts->status} | Code: '{$ts->code}' | Desc: '{$ts->product_name}' | CreatedAt: {$ts->created_at}\n";
}
echo "Soma total das sacolinhas de 18/08 e 19/08: R$ " . number_format($somaToday, 2, ',', '.') . "\n";

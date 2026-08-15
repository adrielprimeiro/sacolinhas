<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$statuses = DB::table('sacolinhas')
    ->select('status', DB::raw('COUNT(*) as total_items'), DB::raw('SUM(price * quantity) as total_valor'))
    ->groupBy('status')
    ->get();

echo "=== Todos os Statuses na Tabela sacolinhas ===\n\n";

foreach ($statuses as $s) {
    echo "Status: '{$s->status}' | Itens: {$s->total_items} | Total Valor: R$ " . number_format($s->total_valor, 2, ',', '.') . "\n";
}

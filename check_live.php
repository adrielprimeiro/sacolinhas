<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$live = DB::table('lives')->where('ativo', 1)->orderByDesc('id')->first();
if ($live) {
    echo "Live Ativa ID: {$live->id} | Brecho: {$live->brecho_id}\n";
    print_r($live);
} else {
    echo "Nenhuma live ativa no momento.\n";
}

$lastSacolinhas = DB::table('sacolinhas')
    ->orderByDesc('id')
    ->limit(15)
    ->get();

echo "\nUltimas 15 sacolinhas:\n";
foreach ($lastSacolinhas as $s) {
    echo "Sacola #{$s->id} | User: {$s->user_id} | Item: {$s->item_id} | Live: {$s->live_id} | Qtd: {$s->quantity} | Status: {$s->status} | Data: {$s->add_at}\n";
}

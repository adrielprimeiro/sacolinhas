<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$movs = Illuminate\Support\Facades\DB::table('movimentacoes')->where('lancamento_id', 5630)->get();
echo "Movimentacoes in DB for Lanc 5630:\n";
foreach($movs as $m) {
    echo "ID: {$m->id}, Valor: {$m->valor_pago}\n";
}

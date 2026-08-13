<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$movs = Illuminate\Support\Facades\DB::table('movimentacoes')->get();
echo "Total Movimentacoes: {$movs->count()}\n";
$lanc = App\Models\Lancamento::find(5630);
if ($lanc) {
    echo "Lancamento {$lanc->id} found.\n";
    $movs = Illuminate\Support\Facades\DB::table('movimentacoes')->where('lancamento_id', 5630)->get();
    echo "Movimentacoes for this lancamento: {$movs->count()}\n";
} else {
    echo "Lancamento 5630 NOT FOUND\n";
}

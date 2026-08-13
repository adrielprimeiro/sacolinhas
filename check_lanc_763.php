<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$l = App\Models\Lancamento::find(763);
if ($l) {
    echo "Lancamento 763: Ref={$l->referencia_tipo}, Valor={$l->valor_total}, Desc={$l->descricao}\n";
} else {
    echo "Lancamento 763 not found.\n";
}

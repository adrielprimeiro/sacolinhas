<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sacolinhas;

$sacolinhasIds = [10702, 12342];

$sacolinhas = Sacolinhas::with('item')->whereIn('id', $sacolinhasIds)->get();

foreach ($sacolinhas as $s) {
    if ($s->item) {
        echo "Sacolinha #" . $s->id . " | Código do Item: " . $s->item->codigo . " | Produto: " . $s->item->nome_do_produto . " | Preço: R$ " . $s->price . "\n";
    } else {
        echo "Sacolinha #" . $s->id . " | Item não encontrado no banco de dados.\n";
    }
}

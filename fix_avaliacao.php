<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$avaliacao = \App\Models\Avaliacao::find(44);

if ($avaliacao) {
    echo "Avaliação 44 encontrada.\n";
    echo "Status: " . $avaliacao->status . "\n";
    
    $items = $avaliacao->items;
    $totalVenda = 0;
    foreach ($items as $item) {
        $item->preco_venda = 25.00;
        $item->save();
        $totalVenda += $item->preco_venda;

        if ($item->item_id) {
            $estoqueItem = \App\Models\Item::find($item->item_id);
            if ($estoqueItem) {
                $estoqueItem->preco = 25.00;
                $estoqueItem->save();
            }
        }
    }
    $avaliacao->total_venda = $totalVenda;
    $avaliacao->save();
    echo "Total venda atualizado para: " . $totalVenda . "\n";
}

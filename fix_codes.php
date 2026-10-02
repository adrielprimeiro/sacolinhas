<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$avaliacoes = \App\Models\Avaliacao::whereIn('id', [44, 45])->get();
foreach ($avaliacoes as $avaliacao) {
    echo "Atualizando avaliação {$avaliacao->id}...\n";
    $items = $avaliacao->items;
    $seqIndex = 1;
    foreach ($items as $item) {
        if ($item->item_id) {
            $estoqueItem = \App\Models\Item::find($item->item_id);
            if ($estoqueItem) {
                // Novo formato: DES-{id_avaliacao}-{sequencial_3_digitos}
                $novoCodigo = 'DES-' . $avaliacao->id . '-' . str_pad($seqIndex, 3, '0', STR_PAD_LEFT);
                echo "Alterando de {$estoqueItem->codigo} para {$novoCodigo}\n";
                $estoqueItem->codigo = $novoCodigo;
                $estoqueItem->save();
            }
        }
        $seqIndex++;
    }
}
echo "Concluído.\n";

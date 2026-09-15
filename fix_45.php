<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$avaliacao = \App\Models\Avaliacao::find(45);
if($avaliacao) {
    echo "Alterando tipo_compra de 'avaliados' para 'direta'...\n";
    $avaliacao->tipo_compra = 'direta';
    
    $items = $avaliacao->items;
    $totalVenda = 0;
    $totalPayout = 0;
    foreach ($items as $item) {
        $item->payout_credito = $item->preco_base;
        $item->payout_dinheiro = $item->preco_base;
        $item->save();

        $totalVenda += $item->preco_venda;
        $totalPayout += $item->payout_dinheiro; // pois foi pago em dinheiro
        
        // Atualiza o custo no item (estoque)
        if ($item->item_id) {
            $estoqueItem = \App\Models\Item::find($item->item_id);
            if ($estoqueItem) {
                $estoqueItem->custo = $item->payout_dinheiro;
                $estoqueItem->save();
            }
        }
    }

    $avaliacao->total_payout = $totalPayout;
    $avaliacao->total_venda = $totalVenda;
    $avaliacao->save();
    
    // Atualiza o lançamento financeiro associado
    $lancamento = \App\Models\Lancamento::where('referencia_tipo', 'avaliacao')
        ->where('referencia_id', 45)
        ->first();
        
    if ($lancamento) {
        $lancamento->valor_total = $totalPayout;
        $lancamento->save();
        echo "Lançamento financeiro #{$lancamento->id} atualizado para {$totalPayout}\n";
    }

    echo "Avaliação 45 corrigida com sucesso! Novo Payout Total: {$totalPayout}\n";
}

<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $sql = file_get_contents('items_restore_mod.sql');
    \Illuminate\Support\Facades\DB::unprepared($sql);
    echo "SQL importado com sucesso para a tabela items_backup!\n";
    
    // Now cross-reference and restore
    $liveItems = \Illuminate\Support\Facades\DB::table('live_items')->whereIn('live_id', [342, 343])->get();
    echo "Itens de live encontrados: " . $liveItems->count() . "\n";
    
    $restored = 0;
    foreach ($liveItems as $li) {
        $backupItem = \Illuminate\Support\Facades\DB::table('items_backup')->where('id', $li->item_id)->first();
        if ($backupItem) {
            \Illuminate\Support\Facades\DB::table('items')->where('id', $li->item_id)->update([
                'nome_do_produto' => $backupItem->nome_do_produto,
                'preco' => $backupItem->preco,
                'custo' => $backupItem->custo,
                'descricao' => $backupItem->descricao,
                'marca' => $backupItem->marca,
                'modelo' => $backupItem->modelo,
                'cor' => $backupItem->cor,
                'tamanho' => $backupItem->tamanho,
                'codigo_da_categoria' => $backupItem->codigo_da_categoria,
                'estado' => $backupItem->estado
            ]);
            $restored++;
        }
    }
    
    echo "Total de itens restaurados: {$restored}\n";
    
} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

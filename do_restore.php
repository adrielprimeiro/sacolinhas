<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sql = file_get_contents('items_restore.sql');

$liveItems = \Illuminate\Support\Facades\DB::table('live_items')->whereIn('live_id', [342, 343])->get();
$restored = 0;

foreach ($liveItems as $li) {
    $itemId = $li->item_id;
    // Regex para encontrar a tupla do item
    // Procura por (itemId, ... ) 
    $pattern = "/\({$itemId},.*?\),/s"; // simplificado
    
    // Como a tupla pode estar no meio ou no fim, vamos fazer um strpos e capturar os próximos caracteres até fechar a tupla correta.
    $pos = strpos($sql, "({$itemId},");
    if ($pos !== false) {
        $endPos = $pos;
        $inString = false;
        $escaped = false;
        $parenCount = 0;
        
        for ($i = $pos; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            if ($char == "'" && !$escaped) {
                $inString = !$inString;
            }
            if ($char == '\\' && !$escaped) {
                $escaped = true;
            } else {
                $escaped = false;
            }
            
            if (!$inString) {
                if ($char == '(') $parenCount++;
                if ($char == ')') {
                    $parenCount--;
                    if ($parenCount == 0) {
                        $endPos = $i;
                        break;
                    }
                }
            }
        }
        
        if ($endPos > $pos) {
            $tuple = substr($sql, $pos + 1, $endPos - $pos - 1);
            // Agora temos os valores separados por vírgula. Vamos fazer um parser simples de CSV.
            $values = str_getcsv($tuple, ",", "'", "\\");
            
            // indices:
            // 0: id
            // 1: brecho_id
            // 2: codigo
            // 3: nome_do_produto
            // 4: descricao
            // 5: custo
            // 6: preco
            // 7: pedido
            // 8: codigo_da_categoria
            // 9: marca
            // 10: modelo
            // 11: estado
            // 12: cor
            // 13: tamanho
            // 14: image
            
            if (isset($values[3]) && isset($values[6])) {
                $nome = trim($values[3]);
                if ($nome === 'NULL') $nome = null;
                $preco = trim($values[6]);
                if ($preco === 'NULL') $preco = null;
                $custo = trim($values[5]);
                if ($custo === 'NULL') $custo = null;
                $marca = trim($values[9]);
                if ($marca === 'NULL') $marca = null;
                
                // Update
                \Illuminate\Support\Facades\DB::table('items')->where('id', $itemId)->update([
                    'nome_do_produto' => $nome,
                    'preco' => $preco,
                    'custo' => $custo,
                    'marca' => $marca
                ]);
                echo "Restaurado ID {$itemId}: {$nome} - R$ {$preco}\n";
                $restored++;
            }
        }
    }
}

echo "Total restaurados: {$restored}\n";

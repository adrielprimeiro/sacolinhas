<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sql = file_get_contents('items_oct9.sql');

$itemIds = range(74315, 74378);

$sacolinhasNew = \Illuminate\Support\Facades\DB::table('sacolinhas')
    ->whereIn('item_id', $itemIds)
    ->whereDate('created_at', '>=', '2026-10-04')
    ->pluck('item_id')->toArray();

// Some items might be in live_items but not in sacolinhas yet
$liveItemsNew = \Illuminate\Support\Facades\DB::table('live_items')
    ->whereIn('item_id', $itemIds)
    ->whereNotIn('live_id', [342, 343])
    ->pluck('item_id')->toArray();

$itemsToFix = array_unique(array_merge($sacolinhasNew, $liveItemsNew));

$count = 0;
foreach ($itemsToFix as $itemId) {
    // Procura no SQL do dia 9 (que tinha os dados da planilha)
    $pattern = "/\({$itemId},.*?\),/s";
    $pos = strpos($sql, "({$itemId},");
    if ($pos !== false) {
        $endPos = $pos;
        $inString = false;
        $escaped = false;
        $parenCount = 0;
        for ($i = $pos; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            if ($char == "'" && !$escaped) { $inString = !$inString; }
            if ($char == '\\' && !$escaped) { $escaped = true; } else { $escaped = false; }
            if (!$inString) {
                if ($char == '(') $parenCount++;
                if ($char == ')') {
                    $parenCount--;
                    if ($parenCount == 0) { $endPos = $i; break; }
                }
            }
        }
        
        if ($endPos > $pos) {
            $tuple = substr($sql, $pos + 1, $endPos - $pos - 1);
            $values = str_getcsv($tuple, ",", "'", "\\");
            
            if (isset($values[3]) && isset($values[6])) {
                $brechoId = trim($values[1]);
                $codigo = trim($values[2]);
                $nomePlanilha = trim($values[3]); if ($nomePlanilha === 'NULL') $nomePlanilha = null;
                $descricao = trim($values[4]); if ($descricao === 'NULL') $descricao = null;
                $custoPlanilha = trim($values[5]); if ($custoPlanilha === 'NULL') $custoPlanilha = null;
                $precoPlanilha = trim($values[6]); if ($precoPlanilha === 'NULL') $precoPlanilha = null;
                
                $marcaPlanilha = trim($values[9]); if ($marcaPlanilha === 'NULL') $marcaPlanilha = null;
                
                // insert a new item
                $newItemId = \Illuminate\Support\Facades\DB::table('items')->insertGetId([
                    'brecho_id' => $brechoId,
                    'codigo' => $codigo . '-B', // append -B to avoid unique constraint if any
                    'nome_do_produto' => $nomePlanilha,
                    'preco' => $precoPlanilha,
                    'custo' => $custoPlanilha,
                    'marca' => $marcaPlanilha,
                    'descricao' => $descricao,
                    'estado' => 'Seminovo',
                    'status' => 'vendido',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // update sacolinhas
                \Illuminate\Support\Facades\DB::table('sacolinhas')
                    ->where('item_id', $itemId)
                    ->whereDate('created_at', '>=', '2026-10-04')
                    ->update(['item_id' => $newItemId]);
                    
                // update live_items
                \Illuminate\Support\Facades\DB::table('live_items')
                    ->where('item_id', $itemId)
                    ->whereNotIn('live_id', [342, 343])
                    ->update(['item_id' => $newItemId]);
                
                echo "Fixed ID $itemId -> $newItemId ($nomePlanilha)\n";
                $count++;
            }
        }
    }
}

echo "Total items fixed: $count\n";

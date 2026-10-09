<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sql = file_get_contents('items_oct9.sql');
$liveItems = \Illuminate\Support\Facades\DB::table('live_items')->whereIn('live_id', [342, 343])->get();

$csv = fopen('spreadsheet_lost_items.csv', 'w');
fputcsv($csv, ['ID', 'Codigo', 'Nome (Planilha)', 'Preco (Planilha)', 'Custo (Planilha)', 'Marca (Planilha)']);

$count = 0;
foreach ($liveItems as $li) {
    $itemId = $li->item_id;
    
    // Pega o item atual (que foi restaurado para a versão da live)
    $currentItem = \Illuminate\Support\Facades\DB::table('items')->where('id', $itemId)->first();
    
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
                $nomePlanilha = trim($values[3]); if ($nomePlanilha === 'NULL') $nomePlanilha = null;
                $precoPlanilha = trim($values[6]); if ($precoPlanilha === 'NULL') $precoPlanilha = null;
                $custoPlanilha = trim($values[5]); if ($custoPlanilha === 'NULL') $custoPlanilha = null;
                $marcaPlanilha = trim($values[9]); if ($marcaPlanilha === 'NULL') $marcaPlanilha = null;
                
                // Se o nome for diferente, significa que foi sobrescrito pela planilha
                if ($nomePlanilha !== $currentItem->nome_do_produto) {
                    fputcsv($csv, [
                        $itemId, 
                        $currentItem->codigo, 
                        $nomePlanilha, 
                        $precoPlanilha, 
                        $custoPlanilha, 
                        $marcaPlanilha
                    ]);
                    $count++;
                }
            }
        }
    }
}
fclose($csv);
echo "Encontrados $count itens que haviam sido inseridos pela planilha e que foram restaurados.\n";

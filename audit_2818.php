<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Searching for 2818 or 2818.01 in all database tables ===\n\n";

$tables = DB::select('SHOW TABLES');
$dbName = env('DB_DATABASE', 'sacolinhas');
$colKey = "Tables_in_" . $dbName;

foreach ($tables as $tObj) {
    $t = $tObj->$colKey;
    try {
        $cols = DB::getSchemaBuilder()->getColumnListing($t);
        foreach ($cols as $c) {
            $cCount = DB::table($t)->whereRaw("CAST(`{$c}` AS CHAR) LIKE '%2818%'")->count();
            if ($cCount > 0) {
                echo "Tabela '{$t}' | Coluna '{$c}' -> Encontrados {$cCount} registros com 2818!\n";
                $rows = DB::table($t)->whereRaw("CAST(`{$c}` AS CHAR) LIKE '%2818%'")->get();
                foreach ($rows as $r) {
                    print_r($r);
                }
            }
        }
    } catch (\Exception $e) {
        // Ignorar
    }
}

echo "\n=== Verificando sacolinhas da Live Ativa / Live 310 por Status e Usuários ===\n";

$sacolinhas = DB::table('sacolinhas as s')
    ->leftJoin('items as i', 's.item_id', '=', 'i.id')
    ->leftJoin('users as u', 's.user_id', '=', 'u.id')
    ->where('s.live_id', 310)
    ->select('s.id', 's.user_id', 'u.name', 's.item_id', 'i.nome_do_produto', 'i.preco as item_preco', 's.price as sacola_price', 's.quantity', 's.status as sacola_status', 'i.status as item_status')
    ->get();

echo "Total de itens na Live 310: " . $sacolinhas->count() . "\n";

$somaSacolaPrice = 0;
$somaItemPreco = 0;

foreach ($sacolinhas as $sc) {
    $p1 = (float) $sc->sacola_price;
    $p2 = (float) $sc->item_preco;
    $qty = (int) ($sc->quantity ?? 1);
    
    $somaSacolaPrice += ($p1 * $qty);
    $somaItemPreco   += ($p2 * $qty);

    if ($p1 != $p2) {
        echo "   [DIFERENÇA DE PREÇO] Sacolinha ID {$sc->id} | User: {$sc->name} | Price na Sacola: R$ {$p1} | Preço no Item: R$ {$p2}\n";
    }
}

echo "\nSoma usando sacolinhas.price: R$ " . number_format($somaSacolaPrice, 2, ',', '.') . "\n";
echo "Soma usando items.preco: R$ " . number_format($somaItemPreco, 2, ',', '.') . "\n";

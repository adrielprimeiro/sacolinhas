<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Searching for any table where a price/total/balance ends in .01 or ,01 ===\n\n";

$tables = ['sacolinhas', 'pedidos', 'cliente_limites', 'conta_corrente', 'items', 'users'];

foreach ($tables as $t) {
    try {
        $cols = DB::getSchemaBuilder()->getColumnListing($t);
        foreach ($cols as $c) {
            $rows = DB::table($t)
                ->whereRaw("`{$c}` LIKE '%.01%' OR `{$c}` LIKE '%,01%'")
                ->get();
            if ($rows->count() > 0) {
                echo "Tabela '{$t}' | Coluna '{$c}' -> Encontradas {$rows->count()} linhas com .01:\n";
                foreach ($rows as $r) {
                    echo "   ";
                    print_r($r);
                }
            }
        }
    } catch (\Exception $e) {
        echo "Erro na tabela {$t}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Verificando soma de sacolinhas por CLIENTE na Live 310 ===\n";

$byClient = DB::table('sacolinhas as s')
    ->join('users as u', 's.user_id', '=', 'u.id')
    ->where('s.live_id', 310)
    ->select('s.user_id', 'u.name', DB::raw('COUNT(*) as qtd'), DB::raw('SUM(s.price * s.quantity) as total'))
    ->groupBy('s.user_id', 'u.name')
    ->get();

echo "Total de clientes na Live 310: " . $byClient->count() . "\n";
$somaClientes = 0;
foreach ($byClient as $cli) {
    $somaClientes += $cli->total;
    echo "Cliente: ID {$cli->user_id} - {$cli->name} | Qtd: {$cli->qtd} itens | Total: R$ " . number_format($cli->total, 2, ',', '.') . "\n";
}

echo "\nSoma de todos os clientes na Live 310: R$ " . number_format($somaClientes, 2, ',', '.') . "\n";

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

echo "=== AUDITORIA DETALHADA: ADRIANA SILVA DO HERVAL ===\n\n";

$users = User::where('name', 'like', '%Adriana%Herval%')
    ->orWhere('instagram', 'like', '%adrisilva%')
    ->get();

echo "1. USUÁRIOS ENCONTRADOS:\n";
foreach ($users as $u) {
    echo "   - ID #{$u->id} | Nome: {$u->name} | Insta: @{$u->instagram} | Whats: {$u->whatsapp}\n";
}

echo "\n2. TODOS OS REGISTROS NA TABELA conta_corrente PARA ESSES USUÁRIOS:\n";
foreach ($users as $u) {
    $rows = DB::table('conta_corrente')->where('user_id', $u->id)->get();
    echo "   --- User ID #{$u->id} ({$u->name}) : Total de registros = " . $rows->count() . " ---\n";
    $sumValor = 0;
    foreach ($rows as $r) {
        $sumValor += $r->valor;
        echo "       [ID #{$r->id}] Data: {$r->created_at} | Valor: R$ " . number_format($r->valor, 2, ',', '.') . " | Descrição: {$r->descricao}\n";
    }
    echo "       -> SUM(valor): R$ " . number_format($sumValor, 2, ',', '.') . "\n";
    echo "       -> MAX(valor): R$ " . number_format($rows->max('valor'), 2, ',', '.') . "\n";
    echo "       -> Último valor registrado: R$ " . number_format($rows->last()->valor ?? 0, 2, ',', '.') . "\n\n";
}

echo "3. VERIFICANDO ESTRUTURA DA TABELA conta_corrente:\n";
$cols = DB::getSchemaBuilder()->getColumnListing('conta_corrente');
echo "   Colunas: " . implode(', ', $cols) . "\n";

echo "\n=======================================================\n";

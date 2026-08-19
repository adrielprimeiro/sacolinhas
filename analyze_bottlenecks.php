<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sacolinhas;
use App\Models\Live;
use Illuminate\Support\Facades\DB;

echo "=== Análise Estratégica dos Gargalos Reais no Banco de Dados ===\n\n";

// 1. Sacolinhas paradas por status
$sacolasPorStatus = DB::table('sacolinhas')
    ->select('status', DB::raw('COUNT(*) as total_itens'), DB::raw('COUNT(DISTINCT user_id) as total_clientes'), DB::raw('SUM(price * quantity) as valor_total'))
    ->groupBy('status')
    ->get();

echo "--- 1. RAIO-X DAS SACOLINHAS ABERTAS / PARADAS ---\n";
$totalGeralPendente = 0;
foreach ($sacolasPorStatus as $st) {
    echo "Status '{$st->status}': {$st->total_itens} itens | {$st->total_clientes} clientes | R$ " . number_format($st->valor_total, 2, ',', '.') . "\n";
    if (in_array($st->status, ['live', 'em analise', 'reservado', 'pendente'])) {
        $totalGeralPendente += $st->valor_total;
    }
}
echo "=> TOTAL DE DINHEIRO PARADO EM SACOLINHAS ABERTAS: R$ " . number_format($totalGeralPendente, 2, ',', '.') . "\n\n";

// 2. Tempo médio entre inserção na sacola e encerramento/pagamento
$sacolasAntigas = DB::table('sacolinhas')
    ->whereIn('status', ['live', 'em analise'])
    ->where('created_at', '<', now()->subDays(3))
    ->select(DB::raw('COUNT(*) as qtd'), DB::raw('SUM(price * quantity) as valor'))
    ->first();

echo "--- 2. SACOLINHAS 'ABERTAS' HÁ MAIS DE 3 DIAS (DINHEIRO ESQUECIDO) ---\n";
echo "Sacolinhas com +3 dias paradas: {$sacolasAntigas->qtd} itens | R$ " . number_format($sacolasAntigas->valor ?? 0, 2, ',', '.') . "\n\n";

// 3. Status das mensagens de WhatsApp
$waStatus = DB::table('whatsapp_messages')
    ->select('status', DB::raw('COUNT(*) as qtd'))
    ->groupBy('status')
    ->get();

echo "--- 3. STATUS DAS MENSAGENS DE WHATSAPP --- \n";
foreach ($waStatus as $ws) {
    echo "Status WhatsApp '{$ws->status}': {$ws->qtd} mensagens\n";
}

echo "\n--- 4. MENSAGENS PENDENTES OU COM ERRO NO WHATSAPP ---\n";
$waFailed = DB::table('whatsapp_messages')->whereIn('status', ['failed', 'pending'])->count();
echo "Total de mensagens com falha/pendentes: {$waFailed}\n";

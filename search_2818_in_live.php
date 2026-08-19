<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Sacolinhas;
use App\Models\User;

echo "=== Auditando sacolinhas da Live 310 e sacolinhas abertas por cliente ===\n\n";

// 1. Todas as sacolinhas ativas da Live 310
$live310Users = Sacolinhas::where('live_id', 310)
    ->whereIn('status', ['live', 'em analise', 'sacolinha', 'pendente'])
    ->distinct()
    ->pluck('user_id');

echo "Total de clientes com sacolinha na Live 310: " . $live310Users->count() . "\n\n";

foreach ($live310Users as $uid) {
    $u = User::find($uid);
    $userName = $u ? $u->name : "User {$uid}";

    // Soma dos itens na Live 310
    $somaLive310 = Sacolinhas::where('live_id', 310)
        ->where('user_id', $uid)
        ->whereIn('status', ['live', 'em analise', 'sacolinha', 'pendente'])
        ->sum(DB::raw('price * quantity'));

    // Soma de TODAS as sacolinhas abertas deste cliente (todas as lives)
    $somaTodasSacolas = Sacolinhas::where('user_id', $uid)
        ->whereIn('status', ['live', 'em analise', 'sacolinha', 'pendente'])
        ->sum(DB::raw('price * quantity'));

    // Saldo na carteira (conta corrente) do cliente
    $saldoCarteira = DB::table('conta_corrente')
        ->where('user_id', $uid)
        ->orderByDesc('data_movimentacao')
        ->orderByDesc('id')
        ->value('saldo_atual') ?? 0.0;

    // Total líquido (Sacolas - Carteira)
    $totalLiquido = $somaTodasSacolas - $saldoCarteira;

    echo "Cliente ID {$uid} - {$userName}:\n";
    echo "   -> Soma Live 310: R$ " . number_format($somaLive310, 2, ',', '.') . "\n";
    echo "   -> Soma Todas Sacolas Abertas: R$ " . number_format($somaTodasSacolas, 2, ',', '.') . "\n";
    echo "   -> Saldo na Carteira (Crédito): R$ " . number_format($saldoCarteira, 2, ',', '.') . "\n";
    echo "   -> Total Líquido (Sacolas - Carteira): R$ " . number_format($totalLiquido, 2, ',', '.') . "\n";

    if (abs($somaLive310 - 2818.01) < 100 || abs($somaTodasSacolas - 2818.01) < 100 || abs($totalLiquido - 2818.01) < 100) {
        echo "   [*** ATENÇÃO: VALOR PRÓXIMO DE 2818! ***]\n";
    }
    echo "------------------------------------------------------------------\n";
}

echo "\n=== Verificando a soma GERAL de TODAS as sacolinhas abertas do sistema ===\n";
$somaGeralLive = Sacolinhas::where('status', 'live')->sum(DB::raw('price * quantity'));
$somaGeralEmAnalise = Sacolinhas::where('status', 'em analise')->sum(DB::raw('price * quantity'));
$somaGeralSacolinha = Sacolinhas::where('status', 'sacolinha')->sum(DB::raw('price * quantity'));

echo "Soma Geral 'live': R$ " . number_format($somaGeralLive, 2, ',', '.') . "\n";
echo "Soma Geral 'em analise': R$ " . number_format($somaGeralEmAnalise, 2, ',', '.') . "\n";
echo "Soma Geral 'sacolinha': R$ " . number_format($somaGeralSacolinha, 2, ',', '.') . "\n";
echo "Soma 'live' + 'em analise': R$ " . number_format($somaGeralLive + $somaGeralEmAnalise, 2, ',', '.') . "\n";
echo "Soma 'live' + 'sacolinha': R$ " . number_format($somaGeralLive + $somaGeralSacolinha, 2, ',', '.') . "\n";
echo "Soma 'live' + 'em analise' + 'sacolinha': R$ " . number_format($somaGeralLive + $somaGeralEmAnalise + $somaGeralSacolinha, 2, ',', '.') . "\n";

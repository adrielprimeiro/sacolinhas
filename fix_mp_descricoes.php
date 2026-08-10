<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = config('services.mercadopago.access_token');

// Buscar todos os pagamentos authorized account_money dos últimos 30 dias
$response = Illuminate\Support\Facades\Http::withoutVerifying()
    ->withToken($token)
    ->get('https://api.mercadopago.com/v1/payments/search?range=date_created&begin_date=2026-07-10T00:00:00.000-03:00&end_date=2026-08-10T23:59:59.000-03:00&limit=100');

$data = $response->json();
$corrigidos = 0;

foreach ($data['results'] ?? [] as $p) {
    if ($p['status'] !== 'authorized' || $p['payment_type_id'] !== 'account_money') continue;

    $descricao = $p['description'] ?? null;
    if (!$descricao || $descricao === 'reserve_for_payment') continue;

    // Procurar transações com description = 'reserve_for_payment' e valor igual, mesma data
    $data_pag = \Carbon\Carbon::parse($p['date_created'])->toDateString();
    $valor = (float) $p['transaction_amount'];

    // Atualizar transações com descrição genérica para o nome real
    $atualizadas = \Illuminate\Support\Facades\DB::table('transacoes_extrato')
        ->where('descricao', 'reserve_for_payment')
        ->whereDate('data', $data_pag)
        ->where('valor', $valor)
        ->where('origem', 'mercadopago')
        ->update(['descricao' => $descricao]);

    if ($atualizadas > 0) {
        echo "Corrigido: R$ {$valor} em {$data_pag} -> '{$descricao}' ({$atualizadas} registro(s))\n";
        $corrigidos += $atualizadas;
    }
}

echo "\nTotal corrigido: {$corrigidos} registros\n";

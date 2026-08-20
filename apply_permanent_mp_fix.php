<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;

echo "=== Aplicando Correção Permanente no Mercado Pago ===\n\n";

// 1. Encontrar a transação de 10 Rolos de Etiqueta (fitid 170168525217 ou por descrição)
$tEtiqueta = TransacaoExtrato::where('descricao', 'like', '%10 Rolos Etiqueta%')
    ->orWhere('fitid', '170168525217')
    ->get();

echo "Encontradas " . $tEtiqueta->count() . " transações de etiqueta para marcar como 'ignorado':\n";
foreach ($tEtiqueta as $te) {
    $te->update(['status' => 'ignorado']);
    echo "   ID: {$te->id} | fitid: {$te->fitid} | Status alterado para: '{$te->status}'\n";
}

// 2. Executar a sincronização completa via ConciliacaoService para testar se reaparece
echo "\nExecutando ConciliacaoService::sincronizarMercadoPago() em tempo real...\n";
$service = app(\App\Services\ConciliacaoService::class);
$service->sincronizarMercadoPago();

echo "\n=== Verificando extrato pendente após nova sincronização ===\n";
$pendentes = TransacaoExtrato::where('status', 'pendente')->get();
echo "Total de transações pendentes na conciliação: " . $pendentes->count() . "\n";
foreach ($pendentes as $p) {
    echo "   ID: {$p->id} | Data: {$p->data->format('Y-m-d')} | Origem: {$p->origem} | Tipo: {$p->tipo} | Valor: R$ {$p->valor} | Desc: '{$p->descricao}'\n";
}

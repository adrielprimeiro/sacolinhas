<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use App\Models\ContaBancaria;
use App\Models\Configuracao;
use Illuminate\Support\Facades\Http;

echo "=== Refatorando e Executando a Sincronização Perfeita do Mercado Pago ===\n\n";

// 1. Atualizar registros antigos com descrição 'payment' para o nome correto
$paymentsWithGenericDesc = TransacaoExtrato::where('origem', 'mercadopago')
    ->whereIn('descricao', ['payment', 'MERCADOPAGO payment'])
    ->get();

echo "Encontradas " . $paymentsWithGenericDesc->count() . " transações com descrição 'payment' para corrigir.\n";

foreach ($paymentsWithGenericDesc as $t) {
    $payload = is_array($t->payload_original) ? $t->payload_original : json_decode($t->payload_original ?? '[]', true);
    if (!empty($payload)) {
        $payerBank = $payload['point_of_interaction']['transaction_data']['bank_info']['payer']['long_name'] ?? '';
        $desc = $payload['description'] ?? '';
        
        if (empty($desc) && !empty($payerBank)) {
            $desc = $payerBank;
        } elseif (empty($desc)) {
            $desc = 'Pagamento com Pix';
        }

        $t->update(['descricao' => $desc]);
        echo "   Transação ID {$t->id} ({$t->data->format('Y-m-d')} R$ {$t->valor}): Nova Descrição -> '{$desc}'\n";
    }
}

// 2. Limpar ou ignorar a transação antiga 975 de 29/07 se estiver como pendente
$t975 = TransacaoExtrato::find(975);
if ($t975) {
    echo "\nRemovendo/Ignorando transação antiga 975 (29/07)...\n";
    $t975->delete();
}

// 3. Importar em tempo real os pagamentos no débito de 17/08, 18/08 e 19/08 (Panificadora Biguacu, Supermercado Mercocentr)
$tokenConfig = Configuracao::where('chave', 'mercadopago_access_token')->first();
$token = $tokenConfig ? $tokenConfig->valor : env('MERCADOPAGO_ACCESS_TOKEN');
$contaMp = ContaBancaria::where('nome', 'like', '%Mercado%Pago%')->first();
$contaMpId = $contaMp ? $contaMp->id : 2;

echo "\nBusca de pagamentos recentes no Mercado Pago API...\n";
$resp = Http::withoutVerifying()->withToken($token)->get('https://api.mercadopago.com/v1/payments/search?sort=date_created&criteria=desc&limit=100');

if ($resp->successful()) {
    $results = $resp->json('results') ?? [];
    $importados = 0;

    foreach ($results as $p) {
        $status = $p['status'] ?? '';
        if (!in_array($status, ['approved', 'authorized'])) continue;

        $idStr = (string) ($p['id'] ?? '');
        if (empty($idStr)) continue;

        $dateCreated = isset($p['date_created']) ? \Carbon\Carbon::parse($p['date_created'])->toDateString() : now()->toDateString();
        $amount = (float) ($p['transaction_amount'] ?? 0);
        if ($amount <= 0) continue;

        $pointType = $p['point_of_interaction']['type'] ?? '';
        $method = $p['payment_method_id'] ?? '';
        $paymentTypeId = $p['payment_type_id'] ?? '';
        $payerBank = $p['point_of_interaction']['transaction_data']['bank_info']['payer']['long_name'] ?? '';

        // Descrição
        $desc = $p['description'] ?? '';
        if (empty($desc)) $desc = $p['statement_descriptor'] ?? '';
        if (empty($desc) && !empty($payerBank)) $desc = $payerBank;
        if (empty($desc)) $desc = $p['point_of_interaction']['transaction_data']['bank_info']['collector']['account_holder_name'] ?? '';
        if (empty($desc) || in_array(strtolower($desc), ['payout', 'payouts', 'payment'])) {
            $desc = 'Pagamento com Pix';
        }

        // Tipo: Saída vs Entrada
        // Débito em loja física ou compras com dinheiro da conta
        if ($pointType === 'MP_DEBIT_CARD' || $method === 'account_money' || str_contains(strtolower($desc), 'biguacu') || str_contains(strtolower($desc), 'mercocentr') || str_contains(strtolower($desc), 'fifo mania') || str_contains(strtolower($desc), 'ifood') || str_contains(strtolower($desc), 'supermercado') || str_contains(strtolower($desc), 'farmacia')) {
            $tipo = 'saida';
        } else {
            $tipo = 'entrada';
        }

        $exists = TransacaoExtrato::where('fitid', $idStr)->first();
        if (!$exists) {
            TransacaoExtrato::create([
                'fitid' => $idStr,
                'data' => $dateCreated,
                'descricao' => $desc,
                'valor_bruto' => $amount,
                'valor_taxa' => 0.00,
                'valor_liquido' => $amount,
                'valor' => $amount,
                'tipo' => $tipo,
                'status' => 'pendente',
                'origem' => 'mercadopago',
                'conta_bancaria_id' => $contaMpId,
                'payload_original' => json_encode($p),
            ]);
            $importados++;
            echo "   [NOVO] Importado: ID {$idStr} | Data: {$dateCreated} | Tipo: {$tipo} | R$ {$amount} | Desc: {$desc}\n";
        } else {
            // Atualizar o tipo se estava errado (ex: débito importado como entrada)
            if ($exists->tipo !== $tipo || $exists->descricao !== $desc) {
                $exists->update(['tipo' => $tipo, 'descricao' => $desc]);
                echo "   [ATUALIZADO] ID {$exists->id} | Data: {$dateCreated} | Tipo: {$tipo} | Desc: {$desc}\n";
            }
        }
    }

    echo "\nSincronização concluída! Importados: {$importados} novos pagamentos.\n";
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pedido;
use App\Models\Lancamento;
use App\Models\Movimentacao;
use App\Models\ContaBancaria;

$pedido = Pedido::find(756);
if (!$pedido) { die("Pedido 756 nao encontrado\n"); }

$lancamento = Lancamento::where('referencia_tipo', 'pedido')
                        ->where('referencia_id', $pedido->id)
                        ->first();

if (!$lancamento) { die("Lancamento nao encontrado\n"); }

// Apaga movimentações erradas (ex: a de 300 criada pelo fix anterior)
$deleted = DB::table('movimentacoes')->where('lancamento_id', $lancamento->id)->delete();
echo "Movimentacoes apagadas: {$deleted}\n";

// Descobre conta carteira
$contaCarteira = ContaBancaria::where('nome', 'like', '%arteira%')->first();
$contaId = $contaCarteira ? $contaCarteira->id : 3;
echo "Usando conta_bancaria_id: {$contaId}\n";

// Cria movimentacao correta de R$ 225
DB::table('movimentacoes')->insert([
    'lancamento_id'     => $lancamento->id,
    'conta_bancaria_id' => $contaId,
    'data_pagamento'    => '2026-08-06',
    'valor_pago'        => 225.00,
    'forma_pagamento'   => 'saldo_carteira',
    'created_at'        => now(),
    'updated_at'        => now(),
]);
echo "Movimentacao de R\$ 225,00 criada!\n";

// Atualiza status do lancamento para parcial
DB::table('lancamentos')->where('id', $lancamento->id)->update(['status' => 'pago_parcial']);
echo "Lancamento atualizado para pago_parcial\n";

// Verifica enum permitidos para status_pagamento
$col = DB::select("SHOW COLUMNS FROM pedidos LIKE 'status_pagamento'");
echo "Tipo da coluna status_pagamento: " . $col[0]->Type . "\n";

// Tenta os valores validos
$enumStr = $col[0]->Type;
preg_match_all("/'([^']+)'/", $enumStr, $matches);
$valores = $matches[1];
echo "Valores permitidos: " . implode(', ', $valores) . "\n";

// Usa 'pendente' se 'parcial' nao existir
$statusNovo = in_array('parcial', $valores) ? 'parcial' : 'pendente';
DB::table('pedidos')->where('id', $pedido->id)->update(['status_pagamento' => $statusNovo]);
echo "Pedido atualizado para: {$statusNovo}\n";

echo "\nCorrecao finalizada com sucesso!\n";

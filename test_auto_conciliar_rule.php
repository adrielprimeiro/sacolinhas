<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransacaoExtrato;
use Illuminate\Support\Facades\DB;

echo "=== Verificando Regras de Conciliação Salvas e Transações Pendentes ===\n\n";

$regrasRaw = DB::table('configuracoes')->where('chave', 'regras_conciliacao')->value('valor');
$regras = json_decode($regrasRaw, true) ?: [];

echo "Total de Regras Padrão de Conciliação Salvas: " . count($regras) . "\n";
foreach ($regras as $idx => $r) {
    $p = DB::table('pessoas')->where('id', $r['pessoa_id'] ?? 0)->value('nome') ?? 'N/A';
    $c = DB::table('classificacao_financeira')->where('id', $r['classificacao_financeira_id'] ?? 0)->value('nome') ?? 'N/A';
    echo "   Rule #" . ($idx + 1) . " | Tipo: " . ($r['tipo'] ?? 'sugestao') . " | Banco Desc: '{$r['descricao_banco']}' -> Pessoa: {$p} | Classificação: {$c}\n";
}

echo "\n--- Transações Pendentes no Extrato ---\n";
$pendentes = TransacaoExtrato::where('status', 'pendente')->get();
echo "Total de Pendentes: " . $pendentes->count() . "\n";
foreach ($pendentes as $tp) {
    echo "   ID: {$tp->id} | Data: {$tp->data->format('Y-m-d')} | R$ {$tp->valor} | Origem: {$tp->origem} | Desc: '{$tp->descricao}'\n";
}

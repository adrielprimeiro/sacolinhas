<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$extratos = App\Models\TransacaoExtrato::where('descricao', 'like', '%763%')->orWhere('payload_original', 'like', '%763%')->get();
echo "Extratos 763:\n";
foreach ($extratos as $e) {
    echo "ID: {$e->id}, Tipo: {$e->tipo}, Valor: {$e->valor}, Desc: {$e->descricao}, Status: {$e->status}, Origem: {$e->origem}\n";
    if ($e->movimentacao_id) {
        $m = App\Models\Movimentacao::find($e->movimentacao_id);
        if ($m) {
            echo "  Vinculado à mov: {$m->id}, Lanc: {$m->lancamento_id}, ValorPago: {$m->valor_pago}\n";
        }
    }
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$conta = App\Models\ContaBancaria::find(2);
echo "Conta 2 (MP) Saldo={$conta->saldo}\n";

$movs = App\Models\Movimentacao::where('conta_bancaria_id', 2)->with('lancamento')->get();
$receitas = 0;
$despesas = 0;
foreach ($movs as $m) {
    if ($m->lancamento->tipo === 'receita') {
        $receitas += $m->valor_pago;
    } else {
        $despesas += $m->valor_pago;
    }
}
echo "Calculado: Receitas=$receitas, Despesas=$despesas, Saldo=".($receitas - $despesas)."\n";

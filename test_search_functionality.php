<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Financeiro\ConciliacaoController;
use App\Http\Controllers\Financeiro\LancamentoController;

$conciliacaoCtrl = app(ConciliacaoController::class);
$lancamentoCtrl  = app(LancamentoController::class);

echo "=== Testando busca por Apelido ('Cliente1') ===\n";
$req1 = Request::create('/financeiro/conciliacao/buscar-pessoas', 'GET', ['q' => 'Cliente1']);
$res1 = $conciliacaoCtrl->buscarPessoas($req1);
echo $res1->getContent() . "\n\n";

echo "=== Testando busca por Instagram ('andy.xmf') ===\n";
$req2 = Request::create('/financeiro/conciliacao/buscar-pessoas', 'GET', ['q' => 'andy.xmf']);
$res2 = $conciliacaoCtrl->buscarPessoas($req2);
echo $res2->getContent() . "\n\n";

echo "=== Testando busca no Select2 de Lançamentos ('marisaa_agostinho') ===\n";
$req3 = Request::create('/financeiro/search/pessoas', 'GET', ['q' => 'marisaa_agostinho']);
$res3 = $lancamentoCtrl->searchPessoas($req3);
echo $res3->getContent() . "\n\n";

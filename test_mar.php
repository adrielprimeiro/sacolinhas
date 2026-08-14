<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new App\Http\Controllers\Admin\AvaliacaoController();
$request = Illuminate\Http\Request::create('/admin/avaliacoes/search-fornecedor-cliente', 'GET', ['q' => 'mar']);
try {
    $resp = $controller->searchFornecedorCliente($request);
    dump($resp->getContent());
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\SacolinhaController;
use Illuminate\Http\Request;
use App\Models\Item;

$item = Item::first();
if (!$item) {
    echo "No items found in database!\n";
    exit;
}

$request = Request::create('/frete/simular', 'POST', [
    'cep' => '01001000',
    'itens' => [$item->id]
]);

$controller = app(SacolinhaController::class);
try {
    $response = $controller->simularFrete(
        $request,
        app(\App\Services\ShippingCalculatorService::class),
        app(\App\Services\MelhorEnvioService::class)
    );
    echo "Status: " . $response->status() . "\n";
    echo "Content:\n";
    print_r(json_decode($response->content(), true));
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Item;
use App\Services\ShippingCalculatorService;
use App\Services\MelhorEnvioService;

$item = Item::first();
if (!$item) {
    echo "No items found in database!\n";
    exit;
}
echo "Found item: " . $item->nome_do_produto . " (ID: " . $item->id . ")\n";

$calculator = new ShippingCalculatorService();
$packageData = $calculator->calculateForItems([$item->id]);
echo "Package data:\n";
print_r($packageData);

$melhorEnvio = new MelhorEnvioService();
$result = $melhorEnvio->calculateShipping('01001000', $packageData);

echo "Result:\n";
print_r($result);

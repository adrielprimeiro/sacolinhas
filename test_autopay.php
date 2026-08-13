<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $service = app(\App\Services\WalletAutoPayService::class);
    $result = $service->process(1254);
    echo "Service processed. Rows abated: $result\n";
} catch (\Exception $e) {
    echo "Error processing service: " . $e->getMessage() . "\n";
}

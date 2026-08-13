<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Pesquisando storage/logs/laravel.log ===\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    foreach ($lines as $line) {
        if (str_contains($line, '306') || str_contains($line, 'conta_corrente') || str_contains($line, '4515') || str_contains($line, '3781')) {
            echo substr($line, 0, 300) . "\n";
        }
    }
} else {
    echo "laravel.log nao existe.\n";
}

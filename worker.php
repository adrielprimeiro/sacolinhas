<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Simular comando artisan
$kernel->call('queue:work', [
    '--queue' => 'whatsapp',
    '--tries' => '3',
    '--sleep' => '3',
    '--timeout' => '60'
]);
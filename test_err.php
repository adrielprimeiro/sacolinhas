<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$logs = file_get_contents('storage/logs/laravel.log');
$lines = explode("\n", $logs);
$exceptions = array_filter($lines, function($l) { return strpos($l, 'local.ERROR:') !== false; });
print_r(array_slice($exceptions, -10));

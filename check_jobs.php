<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$jobs = Illuminate\Support\Facades\DB::table('jobs')->get();
echo "Jobs pending: " . $jobs->count() . "\n";

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$isNullable = \Illuminate\Support\Facades\Schema::getConnection()->getDoctrineColumn('avaliacoes', 'user_id')->getNotnull() === false;
echo "user_id is nullable: " . ($isNullable ? "YES" : "NO") . "\n";

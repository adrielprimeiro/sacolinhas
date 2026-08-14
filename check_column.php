<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$hasColumn = \Illuminate\Support\Facades\Schema::hasColumn('avaliacoes', 'pessoa_id');
echo "Has pessoa_id: " . ($hasColumn ? "YES" : "NO") . "\n";

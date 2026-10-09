<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
foreach ($tables as $table) {
    $vals = array_values((array)$table);
    $tableName = $vals[0];
    if (str_contains($tableName, 'live')) {
        echo $tableName . "\n";
    }
}

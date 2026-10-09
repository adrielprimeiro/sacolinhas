<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = \Illuminate\Support\Facades\Schema::getColumnListing('sacolinhas');
echo "Sacolinhas columns:\n";
echo json_encode($columns, JSON_PRETTY_PRINT) . "\n";

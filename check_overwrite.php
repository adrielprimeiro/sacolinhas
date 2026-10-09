<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$hasAudits = \Illuminate\Support\Facades\Schema::hasTable('audits');
$hasActivityLog = \Illuminate\Support\Facades\Schema::hasTable('activity_log');
$hasHistory = \Illuminate\Support\Facades\Schema::hasTable('histories');

echo "Audits: " . ($hasAudits ? 'yes' : 'no') . "\n";
echo "Activity Log: " . ($hasActivityLog ? 'yes' : 'no') . "\n";
echo "Histories: " . ($hasHistory ? 'yes' : 'no') . "\n";

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$live = App\Models\Live::where('ativo', true)->first();
if ($live) {
    echo "Tiktok account: " . $live->tiktok_account . "\n";
    echo "Live ID: " . $live->id . "\n";
} else {
    echo "No active live found.\n";
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

try {
    $count = User::where('tiktok', 'like', '%a%')->count();
    echo "Count tiktok: $count\n";
    $countInsta = User::where('instagram', 'like', '%a%')->count();
    echo "Count instagram: $countInsta\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

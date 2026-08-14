<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

echo "Checking 'users' table columns...\n";

if (!Schema::hasColumn('users', 'tiktok')) {
    echo "Adding 'tiktok' column...\n";
    Schema::table('users', function (Blueprint $table) {
        $table->string('tiktok')->nullable()->after('remember_token');
    });
} else {
    echo "'tiktok' column already exists.\n";
}

if (!Schema::hasColumn('users', 'instagram')) {
    echo "Adding 'instagram' column...\n";
    Schema::table('users', function (Blueprint $table) {
        $table->string('instagram')->nullable()->after('tiktok');
    });
} else {
    echo "'instagram' column already exists.\n";
}

echo "Done.\n";

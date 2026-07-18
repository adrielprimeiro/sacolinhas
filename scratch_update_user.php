<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = App\Models\User::find(4);
if ($user) {
    $user->role = 'admin_master';
    $user->save();
    echo "User {$user->name} (ID 4) updated to admin_master.\n";
} else {
    echo "User ID 4 not found.\n";
}

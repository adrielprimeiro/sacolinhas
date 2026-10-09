<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Support\Facades\DB::statement("DROP TABLE IF EXISTS items_backup");
\Illuminate\Support\Facades\DB::statement("CREATE TABLE items_backup LIKE items");

// Substituir INSERT INTO `items` por INSERT INTO `items_backup` no arquivo
$content = file_get_contents('items_restore.sql');
$content = str_replace("INSERT INTO `items`", "INSERT INTO `items_backup`", $content);
file_put_contents('items_restore_mod.sql', $content);

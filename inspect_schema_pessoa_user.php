<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "=== Colunas da Tabela 'pessoas' ===\n";
print_r(Schema::getColumnListing('pessoas'));

echo "\n=== Colunas da Tabela 'users' ===\n";
print_r(Schema::getColumnListing('users'));

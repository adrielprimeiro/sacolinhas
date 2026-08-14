<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "=== Colunas da tabela lancamentos ===\n";
print_r(Schema::getColumnListing('lancamentos'));

echo "\n=== Colunas da tabela movimentacoes ===\n";
print_r(Schema::getColumnListing('movimentacoes'));

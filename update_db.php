<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
\App\Models\AvaliacaoItem::whereNull('motivo_curadoria')->update(['motivo_curadoria' => 'Cadastro']);
echo "Updated.\n";

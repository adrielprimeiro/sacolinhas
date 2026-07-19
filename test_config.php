<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Configuracao;
echo "Token: " . (Configuracao::get('melhor_envio_access_token') ? 'Has token' : 'No token') . "\n";
echo "Expires: " . Configuracao::get('melhor_envio_expires_at') . "\n";

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Jobs\RecalcularSaldosJob;

RecalcularSaldosJob::dispatchSync(4187, '2026-01-01');

$user = User::find(4187);
echo "Saldo Carteira Recalculado para Lila Flavia: R$ " . number_format($user->saldo_carteira, 2, ',', '.') . "\n";

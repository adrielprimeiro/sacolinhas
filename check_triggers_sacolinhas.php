<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$triggers = DB::select("SHOW TRIGGERS WHERE `Table` = 'sacolinhas'");

echo "=== Triggers na tabela 'sacolinhas' ===\n\n";

foreach ($triggers as $t) {
    echo "Trigger: {$t->Trigger} | Event: {$t->Event} | Timing: {$t->Timing}\n";
    echo "Statement:\n{$t->Statement}\n";
    echo "---------------------------------------------------------\n";
}

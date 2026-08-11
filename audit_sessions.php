<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Tabela sessions ===\n";
if (DB::getSchemaBuilder()->hasTable('sessions')) {
    $sessions = DB::table('sessions')->get();
    foreach ($sessions as $s) {
        $u = DB::table('users')->where('id', $s->user_id)->first();
        $userName = $u ? $u->name . " ({$u->email})" : 'Visitante/Nao autenticado';
        $lastActivity = date('Y-m-d H:i:s', $s->last_activity);
        echo "IP: {$s->ip_address} | UserID: {$s->user_id} ({$userName}) | LastActivity: {$lastActivity} | UserAgent: " . substr($s->user_agent, 0, 80) . "\n";
    }
} else {
    echo "Tabela sessions nao existe no banco (pode usar driver file/redis).\n";
}

echo "\n=== Admins cadastrados no sistema ===\n";
$admins = DB::table('users')->whereIn('role', ['admin', 'administrador', 'gerente', 'atendente'])->get();
if ($admins->isEmpty()) {
    $admins = DB::table('users')->where('is_admin', 1)->orWhere('role', 'like', '%admin%')->get();
}
if ($admins->isEmpty()) {
    // Pegar usuarios do painel
    $admins = DB::table('users')->take(10)->get();
}

foreach ($admins as $a) {
    echo "ID: {$a->id} | Nome: {$a->name} | Email: {$a->email} | Role: " . ($a->role ?? $a->tipo ?? 'N/A') . " | UpdatedAt: {$a->updated_at}\n";
}

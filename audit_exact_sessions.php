<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$sessionDir = storage_path('framework/sessions');
if (is_dir($sessionDir)) {
    $files = glob($sessionDir.'/*');
    foreach ($files as $f) {
        $mtime = filemtime($f);
        if ($mtime >= strtotime('2026-08-11 08:45:00') && $mtime <= strtotime('2026-08-11 08:50:00')) {
            $content = file_get_contents($f);
            $data = @unserialize($content);
            echo "Sessao " . basename($f) . " | Modificado em: " . date('Y-m-d H:i:s', $mtime) . "\n";
            if (is_array($data)) {
                foreach ($data as $k => $v) {
                    if (is_string($k) && (str_contains($k, 'login') || str_contains($k, 'user') || str_contains($k, 'auth'))) {
                        echo "  Chave: {$k} => " . print_r($v, true) . "\n";
                        if (is_numeric($v)) {
                            $u = User::find($v);
                            if ($u) echo "    -> User: {$u->name} ({$u->email}) | Role: {$u->role}\n";
                        }
                    }
                }
            }
        }
    }
}

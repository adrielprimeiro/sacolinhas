<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sessionDir = storage_path('framework/sessions');
if (is_dir($sessionDir)) {
    $files = glob($sessionDir.'/*');
    echo "Arquivos de sessao encontrados: " . count($files) . "\n";
    foreach ($files as $f) {
        $mtime = filemtime($f);
        if ($mtime > strtotime('2026-08-11 00:00:00')) {
            $content = file_get_contents($f);
            $data = @unserialize($content);
            echo "Sessao " . basename($f) . " | Modificado em: " . date('Y-m-d H:i:s', $mtime) . "\n";
            if (is_array($data)) {
                foreach ($data as $k => $v) {
                    if (str_contains($k, 'login') || str_contains($k, 'user') || str_contains($k, 'auth')) {
                        echo "  {$k} => " . print_r($v, true) . "\n";
                    }
                }
            }
        }
    }
} else {
    echo "Diretorio framework/sessions nao existe.\n";
}

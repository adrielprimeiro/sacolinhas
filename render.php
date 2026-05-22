<?php
use Illuminate\Support\Facades\Request;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::create('/conta-corrente', 'GET')
);

// We need to act as user 2 (admin)
$user = \App\Models\User::find(2);
Auth::login($user);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::create('/conta-corrente', 'GET')
);

$html = $response->getContent();
file_put_contents(__DIR__ . '/rendered.html', $html);
echo "Rendered successfully! Saved to rendered.html\n";

<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // REGISTRO DOS MIDDLEWARES AQUI DENTRO
        $middleware->alias([
			'check.admin' => \App\Http\Middleware\CheckAdmin::class, // Novo para portal admin
			'check.client' => \App\Http\Middleware\CheckClient::class, 
            // O 'admin' original continua funcionando
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
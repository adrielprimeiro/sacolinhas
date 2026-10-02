<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'admin/obs-relay/*',
            'api/*',
            'twilio-in',
            'twilio-out',
            'twilio-status',
            'mercadopago/webhook'
        ]);

        // REGISTRO DOS MIDDLEWARES AQUI DENTRO
        $middleware->alias([
			'check.admin' => \App\Http\Middleware\CheckAdmin::class, // Novo para portal admin
			'check.client' => \App\Http\Middleware\CheckClient::class, 
			'track.portal.access' => \App\Http\Middleware\TrackPortalAccess::class,
            // O 'admin' original continua funcionando
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
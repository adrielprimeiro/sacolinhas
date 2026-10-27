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
        $middleware->web(append: [
            \App\Http\Middleware\TrackLastVisitedUrl::class,
        ]);

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
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Sua sessão expirou. Recarregue a página e faça login novamente.'], 419);
            }

            $referer = $request->headers->get('referer') ?: url()->previous();
            $normalizedReferer = null;

            if ($referer && \App\Http\Middleware\TrackLastVisitedUrl::isNavigableUrl($referer, $request)) {
                $normalizedReferer = \App\Http\Middleware\TrackLastVisitedUrl::normalizeRelativeUrl($referer, $request);
                if ($normalizedReferer && $request->hasSession()) {
                    $request->session()->put('url.intended', $normalizedReferer);
                    $request->session()->put('last_visited_url', $normalizedReferer);
                }
            }

            if ($request->is('login')) {
                $redirectTo = $request->input('redirect_to') ?: ($request->hasSession() ? $request->session()->get('url.intended') : null);
                return redirect()->route('login', array_filter(['redirect_to' => $redirectTo]))
                    ->withInput($request->only('email', 'redirect_to'))
                    ->withErrors(['email' => 'A página de login ficou inativa e expirou. Por favor, clique em Entrar novamente.']);
            }

            if (auth()->check() && $normalizedReferer) {
                return redirect($normalizedReferer)
                    ->withInput($request->except('password', 'password_confirmation', '_token'))
                    ->with('error', 'O formulário expirou por inatividade. Tente novamente.');
            }

            return redirect()->route('login', array_filter(['redirect_to' => $normalizedReferer]))
                ->with('error', 'Sua sessão expirou. Faça login novamente para voltar exatamente para onde estava.');
        });
    })->create();
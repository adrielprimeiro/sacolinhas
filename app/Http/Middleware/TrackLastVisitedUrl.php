<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackLastVisitedUrl
{
    public const COOKIE_NAME = 'sacolinhas_last_url';
    public const CACHE_PREFIX = 'user_last_url_';

    /**
     * Handle an incoming request and record the last navigable URL visited by the user.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 1. Se o usuário está autenticado e acessou uma página HTML real via GET
        if (
            auth()->check()
            && $request->isMethod('GET')
            && !$request->ajax()
            && !$request->expectsJson()
            && !$request->wantsJson()
            && $request->header('Sec-Purpose') !== 'prefetch'
            && $request->header('Purpose') !== 'prefetch'
            && $response->getStatusCode() === 200
            && str_contains(strtolower((string) $response->headers->get('Content-Type', '')), 'text/html')
        ) {
            $user = auth()->user();
            $fullUrl = $request->fullUrl();

            if (self::isUrlAllowedForUser($fullUrl, $user, $request)) {
                $relativeUrl = self::normalizeRelativeUrl($fullUrl, $request);

                if ($relativeUrl) {
                    $request->session()->put('last_visited_url', $relativeUrl);
                    Cache::put(self::CACHE_PREFIX . $user->id, $relativeUrl, now()->addDays(7));
                    $response->headers->setCookie(cookie(self::COOKIE_NAME, $relativeUrl, 60 * 24 * 7));
                }
            }

            return $response;
        }

        // 2. Se a resposta for um redirecionamento para a tela de login (sessão expirada)
        if ($response->isRedirection() && method_exists($response, 'getTargetUrl')) {
            $targetPath = trim(parse_url($response->getTargetUrl(), PHP_URL_PATH) ?? '', '/');

            if ($targetPath === 'login') {
                if (
                    $request->isMethod('GET')
                    && !$request->ajax()
                    && !$request->expectsJson()
                    && !$request->wantsJson()
                    && self::isNavigableUrl($request->fullUrl(), $request)
                ) {
                    $relativeUrl = self::normalizeRelativeUrl($request->fullUrl(), $request);
                    if ($relativeUrl) {
                        $request->session()->put('url.intended', $relativeUrl);
                        $request->session()->put('last_visited_url', $relativeUrl);
                        $response->headers->setCookie(cookie(self::COOKIE_NAME, $relativeUrl, 60 * 24 * 7));
                    }
                } else {
                    // Requisição AJAX/polling ou POST redirecionada ao login:
                    // Evita que endpoints JSON/API sobrescrevam a url.intended real da página em que o usuário estava
                    $currentIntended = $request->session()->get('url.intended');
                    if (!$currentIntended || !self::isNavigableUrl($currentIntended, $request)) {
                        $referer = $request->headers->get('referer');
                        $cookieUrl = $request->cookie(self::COOKIE_NAME);

                        if ($referer && self::isNavigableUrl($referer, $request)) {
                            $normReferer = self::normalizeRelativeUrl($referer, $request);
                            $request->session()->put('url.intended', $normReferer);
                            $request->session()->put('last_visited_url', $normReferer);
                            $response->headers->setCookie(cookie(self::COOKIE_NAME, $normReferer, 60 * 24 * 7));
                        } elseif ($cookieUrl && self::isNavigableUrl($cookieUrl, $request)) {
                            $normCookie = self::normalizeRelativeUrl($cookieUrl, $request);
                            $request->session()->put('url.intended', $normCookie);
                        } else {
                            $request->session()->forget('url.intended');
                        }
                    }
                }
            }
        }

        return $response;
    }

    /**
     * Normaliza uma URL completa ou relativa para o formato "/caminho?query",
     * garantindo que pertença ao mesmo host da aplicação.
     */
    public static function normalizeRelativeUrl(?string $url, ?Request $request = null): ?string
    {
        if (!$url || !is_string($url)) {
            return null;
        }

        $url = trim($url);
        if ($url === '') {
            return null;
        }

        $parsed = parse_url($url);
        if ($parsed === false) {
            return null;
        }

        if (isset($parsed['host'])) {
            $expectedHost = $request ? $request->getHost() : parse_url((string) config('app.url'), PHP_URL_HOST);
            if ($expectedHost && strtolower($parsed['host']) !== strtolower($expectedHost)) {
                return null;
            }
        }

        $path = '/' . ltrim($parsed['path'] ?? '/', '/');
        if (str_starts_with($path, '//')) {
            return null;
        }

        return $path . (!empty($parsed['query']) ? '?' . $parsed['query'] : '');
    }

    /**
     * Verifica se a URL corresponde a uma página navegável real (excluindo APIs, polling, PDFs, auth, etc.).
     */
    public static function isNavigableUrl(?string $url, ?Request $request = null): bool
    {
        $normalized = self::normalizeRelativeUrl($url, $request);
        if (!$normalized) {
            return false;
        }

        $cleanPath = trim(parse_url($normalized, PHP_URL_PATH) ?? '', '/');

        if ($cleanPath === '' || $cleanPath === 'home') {
            return false;
        }

        $exactExclusions = [
            'login',
            'logout',
            'register',
            'forgot-password',
            'reset-password',
            'verify-email',
            'confirm-password',
            'up',
            'lives',
            'privacy',
            'terms',
            'politica-de-privacidade',
            'termos-de-servico',
            'inventario/itens-local',
        ];

        if (in_array($cleanPath, $exactExclusions, true)) {
            return false;
        }

        $patternExclusions = [
            'login/*',
            'logout/*',
            'register/*',
            'password/*',
            'reset-password/*',
            'verify-email/*',
            'portal/autologin/*',
            'checkout/pagamento/*',
            'api/*',
            '*/api/*',
            '*/search*',
            '*/buscar-*',
            '*/get-sugestao-pessoa*',
            '*/auto-sync*',
            'sacolinhas/live/*',
            'sacolinhas/limites*',
            '*/chat-data*',
            '*/contador-data*',
            '*/live-contador*',
            '*/video-cuts*',
            '*/obs-relay/*',
            '*/video-download-status*',
            '*/transcribe-status*',
            '*/youtube/connect*',
            '*/youtube/callback*',
            '*/youtube/status*',
            '*/tiktok/connect*',
            '*/tiktok/callback*',
            '*/tiktok/status*',
            'tiktok*.txt',
            '*/instagram/connect*',
            '*/instagram/callback*',
            '*/instagram/status*',
            '*/melhor-envio/auth*',
            '*/melhor-envio/callback*',
            '*/melhorenvio-saldo*',
            '*/live/*/status*',
            '*/inter/*/status*',
            '*/inter/checkout-lancamento/*/status*',
            '*/pdf',
            '*/pdf/*',
            '*/relatorio-pdf*',
            '*/etiqueta*',
            '*/danfe*',
            '*/xml*',
            '*/download*',
            '*/export*',
            '*/clientes/estatisticas*',
            '*/clientes/*/historico*',
            'build/*',
            'storage/*',
            'css/*',
            'js/*',
            'images/*',
            'favicon*',
        ];

        if (Str::is($patternExclusions, $cleanPath)) {
            return false;
        }

        return true;
    }

    /**
     * Verifica se a URL é permitida para o perfil/role do usuário autenticado.
     */
    public static function isUrlAllowedForUser(?string $url, $user, ?Request $request = null): bool
    {
        if (!$user || !self::isNavigableUrl($url, $request)) {
            return false;
        }

        $normalized = self::normalizeRelativeUrl($url, $request);
        $cleanPath = trim(parse_url($normalized, PHP_URL_PATH) ?? '', '/');

        $isAdmin = in_array($user->role, ['admin', 'admin_master', 'brecho_admin'], true) || !empty($user->is_admin);

        if ($user->role === 'client' && !$isAdmin) {
            return Str::is([
                'portal/*',
                'checkout/*',
                'loja*',
                'dashboard-pontuacoes*',
                'sacolinhas/consultar*',
            ], $cleanPath);
        }

        if ($isAdmin) {
            // Administradores não devem ser redirecionados para rotas exclusivas de clientes (CheckClient desloga quem não é client)
            if (Str::is(['portal/*'], $cleanPath)) {
                return false;
            }

            // Se for operador de brechó parceiro, respeita o isolamento de rotas da Matriz
            if (method_exists($user, 'isBrechoParceiro') && $user->isBrechoParceiro()) {
                if (Str::is([
                    'admin/financeiro*',
                    'financeiro*',
                    'admin/users*',
                    'admin/equipe*',
                    'admin/brechos*',
                    'admin/knowledge-base*',
                    'classificacao_financeira*',
                    'severino*',
                ], $cleanPath)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Determina a melhor URL de retorno para o usuário após realizar login.
     */
    public static function resolveRedirectUrlForUser(Request $request, $user): string
    {
        $candidates = [
            $request->session()->pull('url.intended'),
            $request->input('redirect_to'),
            $request->session()->pull('last_visited_url'),
            Cache::get(self::CACHE_PREFIX . $user->id),
            $request->cookie(self::COOKIE_NAME),
        ];

        foreach ($candidates as $candidate) {
            if (!empty($candidate) && self::isUrlAllowedForUser($candidate, $user, $request)) {
                $normalized = self::normalizeRelativeUrl($candidate, $request);
                if ($normalized) {
                    return $normalized;
                }
            }
        }

        if (in_array($user->role, ['admin', 'admin_master', 'brecho_admin'], true) || !empty($user->is_admin)) {
            return '/dashboard';
        }

        if ($user->role === 'client') {
            return '/portal/dashboard';
        }

        return '/dashboard';
    }
}

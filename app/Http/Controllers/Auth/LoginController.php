<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\TrackLastVisitedUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if (auth()->check()) {
            $user = auth()->user();
            return redirect(TrackLastVisitedUrl::resolveRedirectUrlForUser($request, $user));
        }

        $candidates = [
            $request->query('redirect_to'),
            $request->session()->get('url.intended'),
            $request->session()->get('last_visited_url'),
            $request->headers->get('referer'),
            $request->cookie(TrackLastVisitedUrl::COOKIE_NAME),
        ];

        $redirectTo = null;
        foreach ($candidates as $candidate) {
            if (!empty($candidate) && TrackLastVisitedUrl::isNavigableUrl($candidate, $request)) {
                $redirectTo = TrackLastVisitedUrl::normalizeRelativeUrl($candidate, $request);
                break;
            }
        }

        if ($redirectTo) {
            $request->session()->put('url.intended', $redirectTo);
        }

        return view('auth.login', compact('redirectTo'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Adicionar verificação de bloqueado
        $credentials = array_merge($credentials, ['bloqueado' => 0]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $targetUrl = TrackLastVisitedUrl::resolveRedirectUrlForUser($request, $user);

            $request->session()->regenerate();

            // Atualiza o cookie de última URL para o destino resolvido, limpando qualquer resquício antigo
            $cookie = cookie(TrackLastVisitedUrl::COOKIE_NAME, $targetUrl, 60 * 24 * 7);

            return redirect($targetUrl)->withCookie($cookie);
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não coincidem com nossos registros.',
        ])->onlyInput('email', 'redirect_to');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
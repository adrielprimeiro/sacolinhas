<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\TrackLastVisitedUrl;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): View
    {
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

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();
        $targetUrl = TrackLastVisitedUrl::resolveRedirectUrlForUser($request, $user);

        $request->session()->regenerate();

        return redirect($targetUrl);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}

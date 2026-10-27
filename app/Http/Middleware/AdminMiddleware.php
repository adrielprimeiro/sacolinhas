<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->guest(route('login'))->with('error', 'Você precisa estar logado.');
        }

        if (!auth()->user()->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Acesso negado. Área restrita para administradores.');
        }

        return $next($request);
    }
}
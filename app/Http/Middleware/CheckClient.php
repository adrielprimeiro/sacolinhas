<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckClient
{
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se está logado
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Faça login no portal do cliente.');
        }

        $user = auth()->user();
        
        // Verifica se é cliente
        if ($user->role !== 'client') {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Área exclusiva para clientes.');
        }

        // Verifica se não está bloqueado
        if ($user->bloqueado) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Conta bloqueada.');
        }

        return $next($request);
    }
}
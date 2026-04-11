<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se está logado
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Você precisa estar logado.');
        }

        $user = auth()->user();
        
        // Verifica se é admin (usando role OU is_admin)
        if (!in_array($user->role, ['admin', 'admin_master']) && !$user->is_admin) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Acesso restrito a administradores.');
        }

        // Verifica se não está bloqueado
        if ($user->bloqueado) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Conta bloqueada.');
        }

        return $next($request);
    }
}
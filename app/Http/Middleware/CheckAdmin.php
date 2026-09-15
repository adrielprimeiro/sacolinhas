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
        
        // Verifica se é admin ou brecho parceiro (usando role OU is_admin)
        if (!in_array($user->role, ['admin', 'admin_master', 'brecho_admin']) && !$user->is_admin) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Acesso restrito a administradores.');
        }

        // Verifica se não está bloqueado
        if ($user->bloqueado) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Conta bloqueada.');
        }

        // Se for operador/admin de brechó parceiro, bloqueia áreas restritas da matriz
        if ($user->isBrechoParceiro()) {
            // Se o brechó estiver desativado, bloqueia o acesso
            if ($user->brecho && !$user->brecho->ativo) {
                auth()->logout();
                return redirect()->route('login')->with('error', 'Acesso suspenso. Este brechó está inativo.');
            }

            // Bloqueia acesso ao módulo Financeiro, DRE, Bancos e Usuários do sistema
            if ($request->is('admin/financeiro*') || 
                $request->is('financeiro*') || 
                $request->is('admin/users*') || 
                $request->is('classificacao_financeira*')) {
                abort(403, 'Acesso restrito à administração da Matriz.');
            }
        }

        return $next($request);
    }
}
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
            return redirect()->guest(route('login'))->with('error', 'Você precisa estar logado.');
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

            // Bloqueia acesso ao módulo Financeiro, DRE, Bancos, Equipe, Relatórios e Configuração de Brechós
            if ($request->is('admin/financeiro*') || 
                $request->is('financeiro*') || 
                $request->is('admin/relatorios*') || 
                $request->is('admin/users*') || 
                $request->is('admin/equipe*') || 
                $request->is('admin/brechos*') || 
                $request->is('admin/knowledge-base*') || 
                $request->is('classificacao_financeira*')) {
                abort(403, 'Acesso restrito à administração da Matriz.');
            }
        }

        // Controle granular de permissões por módulo (leitura vs edição)
        if ($user->role !== 'admin_master' && method_exists($user, 'hasCustomPermissions') && $user->hasCustomPermissions()) {
            if (!\App\Models\User::isSharedLookupRequest($request)) {
                $module = \App\Models\User::resolveModuleForPath($request->path());

                if ($module) {
                    $permission = $user->getModulePermission($module);

                    if ($permission === 'none') {
                        // Se não tem acesso ao dashboard, redireciona automaticamente para o primeiro módulo liberado
                        if (in_array($module, ['dashboard'], true)) {
                            $fallbackUrl = $user->firstAccessibleAdminUrl();
                            if ($fallbackUrl && $fallbackUrl !== '/dashboard') {
                                return redirect($fallbackUrl);
                            }
                        }

                        if ($request->expectsJson() || $request->ajax()) {
                            return response()->json([
                                'success' => false,
                                'error'   => 'Você não possui permissão de acesso a este módulo.',
                                'message' => 'Você não possui permissão de acesso a este módulo.',
                            ], 403);
                        }

                        abort(403, 'Você não possui permissão de acesso a este módulo do sistema.');
                    }

                    if ($permission === 'read' && !\App\Models\User::isReadActionRequest($request)) {
                        if ($request->expectsJson() || $request->ajax()) {
                            return response()->json([
                                'success' => false,
                                'error'   => 'Modo Somente Leitura: seu perfil não tem permissão para criar, editar ou excluir registros neste módulo.',
                                'message' => 'Modo Somente Leitura: seu perfil não tem permissão para criar, editar ou excluir registros neste módulo.',
                            ], 403);
                        }

                        $modules = \App\Models\User::availableModules();
                        $fallback = $modules[$module]['entry_url'] ?? $user->firstAccessibleAdminUrl();
                        $referer = $request->headers->get('referer');

                        if ($referer) {
                            return redirect()->back()->with('error', 'Modo Somente Leitura: seu perfil possui permissão apenas de visualização neste módulo e não pode realizar alterações.');
                        }

                        return redirect($fallback)->with('error', 'Modo Somente Leitura: seu perfil possui permissão apenas de visualização neste módulo.');
                    }
                }
            }
        }

        return $next($request);
    }
}
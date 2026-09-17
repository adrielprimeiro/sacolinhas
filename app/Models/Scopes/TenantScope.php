<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Aplica o escopo para restringir consultas ao brechó do usuário logado.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Se estiver rodando via console (artisan) ou sem usuário logado, não aplica restrição
        if (app()->runningInConsole() || !Auth::check()) {
            return;
        }

        $user = Auth::user();

        // Usuários com role admin_master possuem visão global
        if ($user->role === 'admin_master') {
            return;
        }

        // Se o usuário pertencer a um brechó específico (ou for brecho_admin), restringe àquele brechó
        if (!empty($user->brecho_id)) {
            $builder->where($model->getTable() . '.brecho_id', $user->brecho_id);
        }
    }
}

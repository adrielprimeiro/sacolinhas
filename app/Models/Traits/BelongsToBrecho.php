<?php

namespace App\Models\Traits;

use App\Models\Brecho;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToBrecho
{
    /**
     * O "boot" da trait BelongsToBrecho.
     */
    public static function bootBelongsToBrecho(): void
    {
        // Aplica o TenantScope para isolamento automático de consultas
        static::addGlobalScope(new TenantScope());

        // Ao criar um novo registro, preenche brecho_id automaticamente caso o usuário logado pertença a um brechó
        static::creating(function ($model) {
            if (empty($model->brecho_id) && Auth::check()) {
                $user = Auth::user();
                if (!empty($user->brecho_id)) {
                    $model->brecho_id = $user->brecho_id;
                }
            }
        });
    }

    /**
     * Relacionamento com o Brechó proprietário.
     */
    public function brecho(): BelongsTo
    {
        return $this->belongsTo(Brecho::class, 'brecho_id');
    }
}

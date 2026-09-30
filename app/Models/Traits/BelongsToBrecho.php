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

        // Ao criar um novo registro, preenche brecho_id automaticamente
        static::creating(function ($model) {
            if (empty($model->brecho_id)) {
                if (Auth::check() && !empty(Auth::user()->brecho_id)) {
                    $model->brecho_id = Auth::user()->brecho_id;
                } else {
                    $model->brecho_id = 1;
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Brecho extends Model
{
    use HasFactory;

    protected $table = 'brechos';

    protected $fillable = [
        'nome',
        'slug',
        'documento',
        'telefone',
        'whatsapp',
        'chave_pix',
        'tipo_chave_pix',
        'ativo',
        'configuracoes',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'configuracoes' => 'array',
    ];

    /**
     * Operadores e administradores vinculados a este brechó
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'brecho_id');
    }

    /**
     * Itens / Peças do estoque pertencentes a este brechó
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'brecho_id');
    }

    /**
     * Transmissões e lives realizadas por este brechó
     */
    public function lives(): HasMany
    {
        return $this->hasMany(Live::class, 'brecho_id');
    }

    /**
     * Sacolinhas geradas para este brechó
     */
    public function sacolinhas(): HasMany
    {
        return $this->hasMany(Sacolinhas::class, 'brecho_id');
    }

    /**
     * Pedidos finalizados deste brechó
     */
    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'brecho_id');
    }

    /**
     * Clientes vinculados a este brechó
     */
    public function clientes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'brecho_clientes', 'brecho_id', 'user_id')
            ->withPivot('origem')
            ->withTimestamps();
    }
}

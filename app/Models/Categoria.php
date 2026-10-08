<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Categoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'brecho_id',
        'name',
        'slug',
        'parent_id',
        'valor_desconto',
        'tipo_desconto',
        'altura',
        'largura',
        'comprimento',
        'peso',
        'preco_base'
    ];

    protected $casts = [
        'brecho_id' => 'integer',
        'valor_desconto' => 'decimal:2',
        'altura' => 'decimal:2',
        'largura' => 'decimal:2',
        'comprimento' => 'decimal:2',
        'peso' => 'decimal:3',
        'preco_base' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($categoria) {
            if (!$categoria->slug) {
                $categoria->slug = Str::slug($categoria->name);
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(Categoria::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Categoria::class, 'parent_id');
    }

    public function items()
    {
        return $this->belongsToMany(Item::class);
    }

    public function brecho()
    {
        return $this->belongsTo(Brecho::class, 'brecho_id');
    }

    public function isPadrao(): bool
    {
        return is_null($this->brecho_id);
    }

    /**
     * Escopo para filtrar categorias visíveis para o brechó (padrão global + brechó específico)
     */
    public function scopeForBrecho($query, ?int $brechoId = null)
    {
        $brechoId = $brechoId ?? (auth()->check() && !empty(auth()->user()->brecho_id) ? (int) auth()->user()->brecho_id : 1);
        return $query->where(function ($q) use ($brechoId) {
            $q->whereNull('brecho_id')
              ->orWhere('brecho_id', $brechoId);
        });
    }

    /**
     * Busca o desconto efetivo percorrendo a árvore para cima.
     * Retorna o primeiro desconto encontrado diferente de zero.
     */
    public function getEffectiveDiscount()
    {
        if ($this->valor_desconto > 0) {
            return [
                'type' => $this->tipo_desconto,
                'value' => (float) $this->valor_desconto
            ];
        }

        if ($this->parent) {
            return $this->parent->getEffectiveDiscount();
        }

        return null;
    }
}

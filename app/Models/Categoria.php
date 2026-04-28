<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Categoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'valor_desconto',
        'tipo_desconto',
        'altura',
        'largura',
        'comprimento',
        'peso'
    ];

    protected $casts = [
        'valor_desconto' => 'decimal:2',
        'altura' => 'decimal:2',
        'largura' => 'decimal:2',
        'comprimento' => 'decimal:2',
        'peso' => 'decimal:3',
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

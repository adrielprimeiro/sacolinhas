<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'nome_do_produto',
        'descricao',
        'custo',
        'preco',
        'pedido',
        'codigo_da_categoria',
        'marca',
        'modelo',
        'estado',
        'cor',
        'tamanho',
        'image',
        'status'
    ];

    protected $casts = [
        'preco' => 'decimal:2',
        'custo' => 'decimal:2'
    ];

    // Accessor para nome
    public function getNameAttribute()
    {
        return $this->nome_do_produto;
    }

    // Accessor para preço
    public function getPriceAttribute()
    {
        return (float) $this->preco;
    }

    // Accessor para SKU
    public function getSkuAttribute()
    {
        return $this->codigo;
    }

    // Accessor para URL da imagem
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return $this->image;
        }
        
        return "https://ui-avatars.com/api/?name=" . urlencode($this->nome_do_produto) . "&background=28a745&color=fff&size=128";
    }

    // Accessor para preço formatado
    public function getFormattedPriceAttribute()
    {
        return 'R\$ ' . number_format((float) $this->preco, 2, ',', '.');
    }

    // Scope para itens disponíveis
    public function scopeAvailable($query)
    {
        return $query->where('status', 'disponivel');
    }
}
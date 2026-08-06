<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Sacolinhas;
use App\Models\ItemMedia;

class Item extends Model
{
    use HasFactory;
	
	protected $table = 'items';

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
        'status',
        'localizacao'
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
    public function sacolinha()
    {
       return $this->hasOne(Sacolinhas::class, 'item_id');
    }	
	
	// relação com ItemMedia (ordenada)
	public function medias()
	{
		return $this->hasMany(ItemMedia::class, 'item_id')
			->orderBy('position')
			->orderBy('id');
	}

    /**
     * Relacionamento com Categorias
     */
    public function categorias()
    {
        return $this->belongsToMany(Categoria::class);
    }

    /**
     * Accessor para o Preço Final (dinâmico com base nos descontos de categorias)
     */
    public function getFinalPriceAttribute()
    {
        $originalPrice = (float) $this->preco;
        $maxDiscountValue = 0;

        foreach ($this->categorias as $categoria) {
            $effectiveDiscount = $categoria->getEffectiveDiscount();
            
            if ($effectiveDiscount) {
                $discountValue = 0;
                
                if ($effectiveDiscount['type'] === 'porcentagem') {
                    $discountValue = $originalPrice * ($effectiveDiscount['value'] / 100);
                } else if ($effectiveDiscount['type'] === 'fixo') {
                    $discountValue = $effectiveDiscount['value'];
                }

                // Mantemos o maior desconto absoluto economizado
                if ($discountValue > $maxDiscountValue) {
                    $maxDiscountValue = $discountValue;
                }
            }
        }

        $finalPrice = $originalPrice - $maxDiscountValue;
        
        return $finalPrice < 0 ? 0 : (float) $finalPrice;
    }

    /**
     * Accessor para Preço Final formatado
     */
    public function getFormattedFinalPriceAttribute()
    {
        return 'R$ ' . number_format((float) $this->final_price, 2, ',', '.');
    }

    /**
     * Sincroniza a imagem principal do item com a primeira mídia da galeria.
     * Define is_cover = true para a primeira e false para as demais.
     */
    public function syncMainImage()
    {
        // Pega todas as mídias ordenadas
        $medias = $this->medias()->orderBy('position')->orderBy('id')->get();
        
        if ($medias->isEmpty()) {
            return;
        }

        $first = $medias->first();
        
        // Atualiza o campo image do item
        // Preferimos o thumbnail_url se disponível para performance, ou a url original
        $this->image = $first->thumbnail_url ?: $first->url;
        $this->save();

        // Atualiza is_cover em lote para otimizar
        foreach ($medias as $index => $media) {
            $isCover = ($index === 0);
            if ($media->is_cover !== (bool) $isCover) {
                $media->update(['is_cover' => $isCover]);
            }
        }
    }
}
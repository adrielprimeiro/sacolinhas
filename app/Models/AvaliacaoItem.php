<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvaliacaoItem extends Model
{
    use HasFactory;

    protected $table = 'avaliacao_items';

    protected $fillable = [
        'avaliacao_id',
        'categoria_id',
        'marca_id',
        'nome',
        'marca',
        'estado',
        'nota_curadoria',
        'motivo_curadoria',
        'taxa_curadoria',
        'preco_base',
        'preco_venda',
        'payout_credito',
        'payout_dinheiro',
        'cor',
        'tamanho',
        'item_id',
        'is_fixed_price',
    ];

    protected $casts = [
        'taxa_curadoria' => 'decimal:2',
        'preco_base' => 'decimal:2',
        'preco_venda' => 'decimal:2',
        'payout_credito' => 'decimal:2',
        'payout_dinheiro' => 'decimal:2',
    ];

    /**
     * Relacionamento com a avaliação pai.
     */
    public function avaliacao()
    {
        return $this->belongsTo(Avaliacao::class, 'avaliacao_id');
    }

    /**
     * Relacionamento com a categoria.
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * Relacionamento com a marca cadastrada.
     */
    public function marcaRel()
    {
        return $this->belongsTo(Marca::class, 'marca_id');
    }

    /**
     * Relacionamento com o item gerado no estoque.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    /**
     * Recalcula a precificação, taxa de curadoria e repasse (payout) de créditos ou dinheiro.
     *
     * @param float $fretePorItem
     * @param string $tipoCliente
     * @return void
     */
    public function recalculate(float $fretePorItem = 0.00, string $tipoCliente = 'fora_clube')
    {
        // 1. Ajuste de marca dinâmico
        $porcentagem = 100.00;
        if ($this->marca_id) {
            $marcaObj = $this->marcaRel ?: Marca::find($this->marca_id);
            if ($marcaObj) {
                $porcentagem = (float) $marcaObj->porcentagem_valor;
            }
        } else {
            // Fallback para string legada 'marca'
            if ($this->marca === 'de_marca') {
                $porcentagem = 140.00;
            } elseif ($this->marca === 'farm') {
                $porcentagem = 180.00;
            }
        }

        // 2. Preço de venda = Base * (Porcentagem / 100), arredondado para múltiplo de 5 (se não for preço fixo)
        if ($this->is_fixed_price) {
            $this->preco_venda = (float) $this->preco_base;
        } else {
            $preco_calculado = ((float) $this->preco_base * ($porcentagem / 100.00));
            $this->preco_venda = round($preco_calculado / 5) * 5;
        }

        // 3. Taxa de curadoria (10 é 0, 1 é 10, outros são 10 - nota)
        $nota = (int) $this->nota_curadoria;
        if ($nota === 10) {
            $this->taxa_curadoria = 0.00;
        } elseif ($nota === 1) {
            $this->taxa_curadoria = 10.00;
        } else {
            $this->taxa_curadoria = (float) (10 - $nota);
        }

        // 4. Repasses (Payout)
        if ($tipoCliente === 'clube') {
            // Clube: 50% credito, 40% dinheiro
            $payoutCredito = ($this->preco_venda * 0.50) - $fretePorItem - $this->taxa_curadoria;
            $payoutDinheiro = ($this->preco_venda * 0.40) - $fretePorItem - $this->taxa_curadoria;

            $this->payout_credito = max(0.00, $payoutCredito);
            $this->payout_dinheiro = max(0.00, $payoutDinheiro);
        } else {
            // Fora do clube: 40% credito, 30% dinheiro
            $payoutCredito = ($this->preco_venda * 0.40) - $fretePorItem - $this->taxa_curadoria;
            $payoutDinheiro = ($this->preco_venda * 0.30) - $fretePorItem - $this->taxa_curadoria;

            $this->payout_credito = max(0.00, $payoutCredito);
            $this->payout_dinheiro = max(0.00, $payoutDinheiro);
        }
    }
}

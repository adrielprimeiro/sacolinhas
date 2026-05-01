<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Support\Collection;

class ShippingCalculatorService
{
    /**
     * Calcula as dimensões finais e peso para envio.
     * 
     * @param array|Collection $itemIds IDs dos itens ou Coleção de modelos Item
     * @return array Array com weight, width, height, length
     */
    public function calculateForItems($items)
    {
        if (is_array($items) || $items instanceof \Illuminate\Support\Collection && is_numeric($items->first())) {
            $items = Item::with('categorias')->whereIn('id', $items)->get();
        }

        $totalWeight = 0;
        $maxLength = 0;
        $maxWidth = 0;
        $totalHeight = 0;

        foreach ($items as $item) {
            // Regra da Herança de Categorias: pegar o maior valor dentre as categorias do item
            $itemLength = 0;
            $itemWidth = 0;
            $itemHeight = 0;
            $itemWeight = 0;
            $isRoupa = false;

            // Se o item não tiver categoria, usamos os mínimos exigidos para não zerar
            if ($item->categorias->isEmpty()) {
                $itemLength = 16;
                $itemWidth = 11;
                $itemHeight = 2;
                $itemWeight = 0.1;
            } else {
                foreach ($item->categorias as $cat) {
                    $itemLength = max($itemLength, (float) $cat->comprimento);
                    $itemWidth = max($itemWidth, (float) $cat->largura);
                    $itemHeight = max($itemHeight, (float) $cat->altura);
                    $itemWeight = max($itemWeight, (float) $cat->peso);

                    // Verificar se pertence à categoria de roupas/vestuário para o fator de compactação
                    $catName = strtolower(trim($cat->name));
                    if (str_contains($catName, 'roupa') || str_contains($catName, 'vestuário') || str_contains($catName, 'vestuario')) {
                        $isRoupa = true;
                    }
                }
            }

            // Aplicar Fator de Compactação (Regra 5): Redutor de 20% na altura se for roupa
            if ($isRoupa) {
                $itemHeight = $itemHeight * 0.8;
            }

            // Regra 1: Peso = Soma
            $totalWeight += $itemWeight;

            // Regra 2: Comprimento = Maior
            $maxLength = max($maxLength, $itemLength);

            // Regra 3: Largura = Maior
            $maxWidth = max($maxWidth, $itemWidth);

            // Regra 4: Altura = Soma
            $totalHeight += $itemHeight;
        }

        // Regra 6: Limites Mínimos dos Correios/Transportadoras
        $finalLength = max($maxLength, 16);
        $finalWidth = max($maxWidth, 11);
        $finalHeight = max($totalHeight, 2);
        
        // Garantir peso mínimo razoável (100g)
        $finalWeight = max($totalWeight, 0.1);

        return [
            'weight' => round($finalWeight, 3), // Em kg
            'length' => round($finalLength, 1), // Em cm
            'width'  => round($finalWidth, 1),  // Em cm
            'height' => round($finalHeight, 1)  // Em cm
        ];
    }
}

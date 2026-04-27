<?php

namespace App\Services;

use App\Models\Categoria;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    /**
     * Mapeamento de palavras-chave para slugs de categorias.
     * Inspirado no script scratch/auto_categorizar_itens.php
     */
    protected array $keywordMap = [
        // Feminino / Bolsas
        'shoulder bag'      => 'feminino-bolsas-bolsas-de-ombro',
        'shouder bag'       => 'feminino-bolsas-bolsas-de-ombro',
        'bolsa de ombro'    => 'feminino-bolsas-bolsas-de-ombro',
        'clutch'            => 'feminino-bolsas-clutches',
        'mochila'           => 'feminino-bolsas-mochilas',
        'bolsa'             => 'feminino-bolsas',
        'bag'               => 'feminino-bolsas',

        // Feminino / Calçados
        'scarpin'           => 'feminino-calcados-scarpins',
        'sandalia'          => 'feminino-calcados-sandalias',
        'sandália'          => 'feminino-calcados-sandalias',
        'rasteira'          => 'feminino-calcados-sandalias',
        'bota'              => 'feminino-calcados-botas',
        'sneaker'           => 'feminino-calcados-tenis',
        'tenis'             => 'feminino-calcados-tenis',
        'tênis'             => 'feminino-calcados-tenis',
        'sapatilha'         => 'feminino-calcados-sandalias',
        'melissa'           => 'feminino-calcados',

        // Feminino / Roupas
        'vestido'           => 'feminino-roupas-vestidos',
        'saia'              => 'feminino-roupas-saias',
        'blusa'             => 'feminino-roupas-blusas',
        'casaco'            => 'feminino-roupas-casacos',
        'camiseta'          => 'feminino-roupas-camisetas',
        't-shirt'           => 'feminino-roupas-camisetas',
        'tshirt'            => 'feminino-roupas-camisetas',
        'cropped'           => 'feminino-roupas-blusas',
        'macaquinho'        => 'feminino-roupas-vestidos',
        'moletom'           => 'feminino-roupas-casacos',
        'jaqueta'           => 'feminino-roupas-casacos',
        'blazer'            => 'feminino-roupas-casacos',
        'calça'             => 'feminino-roupas-calcas',
        'calca'             => 'feminino-roupas-calcas',
        'short'             => 'feminino-roupas-calcas',

        // Masculino
        'camisa'            => 'masculino-roupas-camisas',
        'polo'              => 'masculino-roupas-polos',
        'bermuda'           => 'masculino-roupas-bermudas',
        'relogio'           => 'masculino-acessorios-relogios',
        'bone'              => 'masculino-acessorios-bones',
        'boné'              => 'masculino-acessorios-bones',

        // Infantil
        'pijama'            => 'infantil-menina-roupas-pijamas',
        'body'              => 'infantil-menino-roupas-bodys',
        'macacão'           => 'infantil-menino-roupas-macacoes',
        'papete'            => 'infantil-menino-calcados-papetes',
    ];

    /**
     * Sugere uma categoria baseada no nome do produto
     */
    public function suggestCategory(string $productName): ?int
    {
        $name = mb_strtolower($productName);
        
        foreach ($this->keywordMap as $keyword => $slug) {
            if (str_contains($name, mb_strtolower($keyword))) {
                $category = Cache::remember("cat_slug_{$slug}", 3600, function () use ($slug) {
                    return Categoria::where('slug', $slug)->first();
                });

                if ($category) {
                    return $category->id;
                }
            }
        }

        return null;
    }

    /**
     * Retorna a lista completa de categorias para o seletor (hierárquico)
     */
    public function getCategoryOptions(): array
    {
        return Cache::remember('category_options_list', 3600, function () {
            $categories = Categoria::with('parent')->get();
            $options = [];

            foreach ($categories as $cat) {
                $path = $this->getCategoryPath($cat);
                $options[] = [
                    'id' => $cat->id,
                    'path' => $path,
                    'name' => $cat->name
                ];
            }

            // Ordena pelo path para ficar bonito no select
            usort($options, fn($a, $b) => strcmp($a['path'], $b['path']));

            return $options;
        });
    }

    protected function getCategoryPath(Categoria $category): string
    {
        $parts = [$category->name];
        $current = $category;

        while ($current->parent) {
            $current = $current->parent;
            array_unshift($parts, $current->name);
        }

        return implode(' > ', $parts);
    }
}

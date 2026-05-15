<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemSearchController extends Controller
{
    public function search(Request $request)
    {
        $searchTerm = $request->get('q', '');
        
        if (empty($searchTerm)) {
            return response()->json([
                'success' => false,
                'message' => 'Termo de busca é obrigatório'
            ]);
        }

        try {
            $items = DB::table('items')
                        ->where('nome_do_produto', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('descricao', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('codigo', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('marca', 'LIKE', "%{$searchTerm}%")
                        ->where('status', 'disponivel') // Apenas itens disponíveis
                        ->limit(10)
                        ->get();

            $result = $items->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->nome_do_produto,
                    'nome_do_produto' => $item->nome_do_produto,
                    'codigo' => $item->codigo ?? '',
                    'description' => $item->descricao ?? '',
                    'sku' => $item->codigo ?? '',
                    'price' => (float) $item->preco,
                    'formatted_price' => 'R\$ ' . number_format((float) $item->preco, 2, ',', '.'),
                    'category' => $item->codigo_da_categoria ?? 'Sem categoria',
                    'brand' => $item->marca ?? '',
                    'color' => $item->cor ?? '',
                    'size' => $item->tamanho ?? '',
                    'condition' => $item->estado ?? 'novo',
                    'stock' => 1,
                    'display_name' => $item->nome_do_produto . ($item->codigo ? ' (Cód: ' . $item->codigo . ')' : ''),
                    'image_url' => $item->image ?? "https://ui-avatars.com/api/?name=" . urlencode($item->nome_do_produto) . "&background=28a745&color=fff&size=128",
                    'created_at' => $item->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $result,
                'count' => $result->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ]);
        }
    }

    public function getItem($id)
    {
        try {
            $item = DB::table('items')->where('id', $id)->first();
            
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item não encontrado'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $item->id,
                    'name' => $item->nome_do_produto,
                    'description' => $item->descricao ?? '',
                    'sku' => $item->codigo ?? '',
                    'price' => (float) $item->preco,
                    'formatted_price' => 'R\$ ' . number_format((float) $item->preco, 2, ',', '.'),
                    'category' => $item->codigo_da_categoria ?? 'Sem categoria',
                    'brand' => $item->marca ?? '',
                    'color' => $item->cor ?? '',
                    'size' => $item->tamanho ?? '',
                    'condition' => $item->estado ?? 'novo',
                    'stock' => 1,
                    'display_name' => $item->nome_do_produto . ($item->codigo ? ' (Cód: ' . $item->codigo . ')' : ''),
                    'image_url' => $item->image ?? "https://ui-avatars.com/api/?name=" . urlencode($item->nome_do_produto) . "&background=28a745&color=fff&size=128"
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Item não encontrado'
            ], 404);
        }
    }
}
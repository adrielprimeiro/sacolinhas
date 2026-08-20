<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Item;
use App\Models\Sacolinhas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LojaController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::query()
            ->whereIn('status', ['loja', 'estoque'])
            ->whereHas('medias', function ($q) {
                $q->where('media_type', 'image');
            });

        // Busca geral
        if ($request->filled('q')) {
            $busca = trim($request->string('q')->toString());

            $query->where(function ($q) use ($busca) {
                $q->where('nome_do_produto', 'like', "%{$busca}%")
                    ->orWhere('codigo', 'like', "%{$busca}%")
                    ->orWhere('marca', 'like', "%{$busca}%")
                    ->orWhere('modelo', 'like', "%{$busca}%")
                    ->orWhere('descricao', 'like', "%{$busca}%");
            });
        }

        // Categoria por slug
        if ($request->filled('categoria')) {
            $slug = trim($request->string('categoria')->toString());
            $categoria = Categoria::where('slug', $slug)->first();
            if ($categoria) {
                // Filtra pelos itens que têm esta categoria (ou subcategorias)
                $ids = $this->collectCategoryIds($categoria);
                $query->where(function ($q) use ($ids, $categoria) {
                    $q->whereHas('categorias', function ($sq) use ($ids) {
                        $sq->whereIn('categorias.id', $ids);
                    })
                    ->orWhereIn('codigo_da_categoria', $ids)
                    ->orWhere('codigo_da_categoria', $categoria->name)
                    ->orWhere('codigo_da_categoria', $categoria->slug);
                });
            }
        }

        // Categoria (legado: codigo_da_categoria)
        if ($request->filled('codigo_da_categoria')) {
            $query->where('codigo_da_categoria', trim($request->string('codigo_da_categoria')->toString()));
        }

        // Marca
        if ($request->filled('marca')) {
            $marca = trim($request->string('marca')->toString());
            $query->where('marca', 'like', "%{$marca}%");
        }

        // Cor
        if ($request->filled('cor')) {
            $cor = trim($request->string('cor')->toString());
            $query->where('cor', 'like', "%{$cor}%");
        }

        // Tamanho
        if ($request->filled('tamanho')) {
            $tamanho = trim($request->string('tamanho')->toString());
            $query->where('tamanho', 'like', "%{$tamanho}%");
        }

        // Estado
        if ($request->filled('estado')) {
            $query->where('estado', trim($request->string('estado')->toString()));
        }

        // Preço mínimo
        if ($request->filled('preco_min')) {
            $precoMin = (float) $request->input('preco_min');
            $query->where('preco', '>=', $precoMin);
        }

        // Preço máximo
        if ($request->filled('preco_max')) {
            $precoMax = (float) $request->input('preco_max');
            $query->where('preco', '<=', $precoMax);
        }

        $items = $query
            ->with([
                'medias' => function ($q) {
                    $q->where('media_type', 'image')
                        ->orderByDesc('is_cover')
                        ->orderBy('position')
                        ->orderBy('id');
                },
                'categorias'
            ])
            ->orderByDesc('created_at')
            ->paginate(24)
            ->withQueryString();

        // 1. Encontrar todos os IDs de categorias que possuem itens com status 'loja'
        $categoriesWithItems = DB::table('categoria_item')
            ->join('items', 'items.id', '=', 'categoria_item.item_id')
            ->where('items.status', 'loja')
            ->distinct()
            ->pluck('categoria_id')
            ->toArray();

        // 2. Encontrar todos os ancestrais dessas categorias para saber quais mostrar no menu
        $allVisibleCategoryIds = [];
        if (!empty($categoriesWithItems)) {
            $currentLevelIds = $categoriesWithItems;
            $allVisibleCategoryIds = $categoriesWithItems;

            while (!empty($currentLevelIds)) {
                $parents = Categoria::whereIn('id', $currentLevelIds)
                    ->whereNotNull('parent_id')
                    ->pluck('parent_id')
                    ->unique()
                    ->toArray();
                
                $allVisibleCategoryIds = array_unique(array_merge($allVisibleCategoryIds, $parents));
                $currentLevelIds = $parents;
            }
        }

        // 3. Carregar as categorias raiz que estão na lista de visíveis
        // Para uma ordenação precisa (contando filhos), carregamos a árvore e ordenamos via PHP
        $categorias = Categoria::whereNull('parent_id')
            ->whereIn('id', $allVisibleCategoryIds)
            ->with(['children' => function($query) use ($allVisibleCategoryIds) {
                $query->whereIn('id', $allVisibleCategoryIds)
                    ->withCount(['items' => function($q) { $q->where('status', 'loja'); }])
                    ->with(['children' => function($query) use ($allVisibleCategoryIds) {
                        $query->whereIn('id', $allVisibleCategoryIds)
                            ->withCount(['items' => function($q) { $q->where('status', 'loja'); }])
                            ->with(['children' => function($query) use ($allVisibleCategoryIds) {
                                $query->whereIn('id', $allVisibleCategoryIds)
                                    ->withCount(['items' => function($q) { $q->where('status', 'loja'); }]);
                            }]);
                    }]);
            }])
            ->withCount(['items' => function($q) { $q->where('status', 'loja'); }])
            ->get();

        // Função para calcular total de itens na árvore (recursivo) e ordenar filhos
        $processCategoryTree = function($cat) use (&$processCategoryTree) {
            $count = $cat->items_count ?? 0;
            
            // Se houver filhos, processa cada um primeiro
            if ($cat->children->isNotEmpty()) {
                foreach ($cat->children as $child) {
                    $count += $processCategoryTree($child);
                }
                
                // Ordena os filhos deste nível pelo total acumulado que acabamos de calcular
                $cat->setRelation('children', $cat->children->sortByDesc('total_recursive_items')->values());
            }
            
            $cat->total_recursive_items = $count;
            return $count;
        };

        // Processa e ordena toda a árvore recursivamente
        foreach ($categorias as $cat) {
            $processCategoryTree($cat);
        }

        // Ordena as raízes pelo total acumulado
        $categorias = $categorias->sortByDesc('total_recursive_items')->values();

        $categoriaAtiva = null;
        if ($request->filled('categoria')) {
            $categoriaAtiva = Categoria::where('slug', $request->string('categoria')->toString())->first();
        }

        return view('loja.index', compact('items', 'categorias', 'categoriaAtiva'));
    }

    /**
     * Coleta recursivamente os IDs de uma categoria e seus filhos.
     */
    private function collectCategoryIds(Categoria $categoria): array
    {
        $ids = [$categoria->id];
        foreach ($categoria->children as $child) {
            $ids = array_merge($ids, $this->collectCategoryIds($child));
        }
        return $ids;
    }

    public function show(Item $item)
    {
        if (!in_array($item->status, ['loja', 'estoque']) || !$item->medias()->where('media_type', 'image')->exists()) {
            abort(404);
        }

        $item->load([
            'medias' => function ($q) {
                $q->where('media_type', 'image')
                    ->orderByDesc('is_cover')
                    ->orderBy('position')
                    ->orderBy('id');
            },
            'categorias'
        ]);

        return view('loja.show', compact('item'));
    }

    public function adicionarItemSacola(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'item_id' => 'required|exists:items,id',
                'quantity' => 'nullable|integer|min:1',
                'obs' => 'nullable|string',
                'tray' => 'nullable|integer',
                'status' => 'nullable|string',
                'price' => 'nullable|numeric|min:0',
            ]);

            $result = DB::transaction(function () use ($validated) {
                $liveId = 1;
                $itemId = (int) $validated['item_id'];

                $userId = isset($validated['user_id'])
                    ? (int) $validated['user_id']
                    : (int) auth()->id();

                if (!$userId) {
                    return [
                        'success' => false,
                        'code' => 401,
                        'message' => 'Você precisa estar logado.'
                    ];
                }

                $quantity = (int) ($validated['quantity'] ?? 1);

                $price = $validated['price'] ?? DB::table('items')
                    ->where('id', $itemId)
                    ->value('preco');

                $sacolinhaItem = Sacolinhas::where('user_id', $userId)
                    ->where('item_id', $itemId)
                    ->where('live_id', $liveId)
                    ->lockForUpdate()
                    ->first();

                if ($sacolinhaItem) {
                    $sacolinhaItem->quantity += $quantity;

                    if (array_key_exists('obs', $validated)) {
                        $sacolinhaItem->obs = $validated['obs'];
                    }

                    if (array_key_exists('price', $validated) && $validated['price'] !== null) {
                        $sacolinhaItem->price = $price;
                    }

                    $sacolinhaItem->save();
                    $message = 'Quantidade atualizada.';
                } else {
                    $sacolinhaItem = Sacolinhas::create([
                        'user_id' => $userId,
                        'item_id' => $itemId,
                        'live_id' => $liveId,
                        'quantity' => $quantity,
                        'price' => $price,
                        'add_at' => now(),
                        'tray' => $validated['tray'] ?? null,
                        'status' => $validated['status'] ?? 'pendente',
                        'obs' => $validated['obs'] ?? null,
                    ]);

                    $message = 'Item adicionado à sacola.';
                }

                DB::table('items')
                    ->where('id', $itemId)
                    ->update([
                        'status' => 'solicitado na loja',
                        'updated_at' => now()
                    ]);

                return [
                    'success' => true,
                    'code' => 201,
                    'message' => $message,
                    'sacolinha' => $sacolinhaItem
                ];
            });

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], $result['code']);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['sacolinha']
            ], $result['code']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro adicionarItemSacola: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ], 500);
        }
    }
}
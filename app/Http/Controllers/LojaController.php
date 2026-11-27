<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Item;
use App\Models\Sacolinhas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LojaController extends Controller
{
    /**
     * Escopo base dos itens elegíveis na Loja Online da Minha Mania (Matriz).
     * Regras obrigatórias:
     * 1) Status elegível (loja, estoque, disponivel)
     * 2) Matriz Minha Mania (brecho_id 1 ou null)
     * 3) Preço maior que zero (trava preço zerado/nulo)
     * 4) Possui foto real ou thumbnail de vídeo (nunca mostra item sem thumbnail)
     * 5) Não bloqueado por corte de live reprovado/incompatível (quando só depende de mídia da live)
     */
    public static function applyEligibleScope($query)
    {
        $query->whereIn('items.status', ['loja', 'estoque', 'disponivel'])
            ->where(function ($q) {
                $q->where('items.brecho_id', 1)
                  ->orWhereNull('items.brecho_id');
            })
            ->whereNotNull('items.preco')
            ->where('items.preco', '>', 0)
            ->where(function ($q) {
                // Foto real em item_media
                $q->whereHas('medias', function ($mq) {
                    $mq->where('media_type', 'image')
                       ->whereNotNull('url')
                       ->where('url', '!=', '');
                })
                // OU vídeo que possui thumbnail válida
                ->orWhereHas('medias', function ($mq) {
                    $mq->where('media_type', 'video')
                       ->whereNotNull('thumbnail_url')
                       ->where('thumbnail_url', '!=', '');
                })
                // OU campo legado image preenchido
                ->orWhere(function ($sq) {
                    $sq->whereNotNull('items.image')
                       ->where('items.image', '!=', '');
                });
            });

        // Se existir auditoria de cortes, bloqueia itens cujo corte foi reprovado/incompatível
        // e que NÃO possuem foto própria de estúdio (dependiam só do corte da live).
        if (Schema::hasTable('live_items') && Schema::hasColumn('live_items', 'audit_status')) {
            $query->where(function ($q) {
                $q->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('live_items as li_bad')
                        ->whereColumn('li_bad.item_id', 'items.id')
                        ->whereIn('li_bad.audit_status', ['mismatch', 'rejected']);
                })->orWhereHas('medias', function ($mq) {
                    $mq->where('media_type', 'image')
                       ->whereNotNull('url')
                       ->where('url', '!=', '')
                       ->where('url', 'not like', '%live_cuts/%');
                });
            });
        }

        return $query;
    }

    protected function baseAvailableQuery()
    {
        return self::applyEligibleScope(Item::query());
    }

    /**
     * Retorna os tamanhos mais comuns ordenados de forma amigável para chips de filtro rápido.
     */
    public static function getPopularSizes(int $limit = 12): array
    {
        return Cache::remember('loja_popular_sizes_v2', now()->addMinutes(5), function () use ($limit) {
            $rows = self::applyEligibleScope(Item::query())
                ->whereNotNull('tamanho')
                ->where('tamanho', '!=', '')
                ->selectRaw('UPPER(TRIM(tamanho)) as label, COUNT(*) as total')
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit($limit)
                ->get();

            $letterOrder = ['PP' => 1, 'P' => 2, 'P/M' => 3, 'M' => 4, 'M/G' => 5, 'G' => 6, 'GG' => 7, 'XG' => 8, 'G1' => 9, 'G2' => 10, 'U' => 11, 'ÚNICO' => 12, 'UNICO' => 12];

            return $rows->sort(function ($a, $b) use ($letterOrder) {
                $la = $a->label;
                $lb = $b->label;
                $oa = $letterOrder[$la] ?? (is_numeric($la) ? (100 + (int) $la) : 500);
                $ob = $letterOrder[$lb] ?? (is_numeric($lb) ? (100 + (int) $lb) : 500);
                if ($oa !== $ob) {
                    return $oa <=> $ob;
                }
                return $b->total <=> $a->total;
            })->map(fn($r) => [
                'label' => $r->label,
                'total' => (int) $r->total,
            ])->values()->all();
        });
    }

    public function index(Request $request)
    {
        if (auth()->check() && method_exists(auth()->user(), 'isBrechoParceiro') && auth()->user()->isBrechoParceiro()) {
            return redirect()->route('dashboard');
        }

        $query = $this->baseAvailableQuery();

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

        // Tamanho (exato via chip ou parcial se digitado livremente)
        if ($request->filled('tamanho')) {
            $tamanho = strtoupper(trim($request->string('tamanho')->toString()));
            if ($request->boolean('tamanho_exato') || strlen($tamanho) <= 3) {
                $query->whereRaw('UPPER(TRIM(tamanho)) = ?', [$tamanho]);
            } else {
                $query->where('tamanho', 'like', "%{$tamanho}%");
            }
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

        // Apenas produtos com vídeo
        if ($request->boolean('com_video') || $request->input('com_video') === '1') {
            $query->whereHas('medias', function ($q) {
                $q->where('media_type', 'video');
            });
        }

        // Apenas sobras de lives (itens apresentados em live que não foram vendidos)
        if ($request->boolean('sobras') || $request->input('sobras') === '1') {
            if (Schema::hasTable('live_items')) {
                $sobraItemIds = DB::table('live_items')
                    ->whereNull('user_id')
                    ->where(function ($sub) {
                        $sub->whereNull('buyer_name')
                            ->orWhere('buyer_name', '')
                            ->orWhere('buyer_name', '0');
                    })
                    ->pluck('item_id')
                    ->unique()
                    ->values()
                    ->all();

                if (!empty($sobraItemIds)) {
                    $query->whereIn('items.id', $sobraItemIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
        }

        // Ordenação
        $ordem = $request->string('ordem', 'recentes')->toString();
        switch ($ordem) {
            case 'menor_preco':
                $query->orderBy('preco', 'asc')->orderByDesc('id');
                break;
            case 'maior_preco':
                $query->orderBy('preco', 'desc')->orderByDesc('id');
                break;
            default:
                $ordem = 'recentes';
                $query->orderByDesc('created_at')->orderByDesc('id');
                break;
        }

        $items = $query
            ->with([
                'medias' => function ($q) {
                    $q->orderByDesc('is_cover')
                        ->orderBy('position')
                        ->orderBy('id');
                },
                'categorias'
            ])
            ->paginate(24)
            ->withQueryString();

        // Contadores rápidos para pílulas no topo
        $quickStats = Cache::remember('loja_quick_stats_v2', now()->addMinutes(3), function () {
            $base = $this->baseAvailableQuery();
            $videoCount = (clone $base)->whereHas('medias', fn($q) => $q->where('media_type', 'video'))->count();

            $sobrasCount = 0;
            if (Schema::hasTable('live_items')) {
                $sobraIds = DB::table('live_items')
                    ->whereNull('user_id')
                    ->where(function ($sub) {
                        $sub->whereNull('buyer_name')
                            ->orWhere('buyer_name', '')
                            ->orWhere('buyer_name', '0');
                    })
                    ->pluck('item_id')
                    ->unique()
                    ->values()
                    ->all();

                if (!empty($sobraIds)) {
                    $sobrasCount = (clone $base)->whereIn('items.id', $sobraIds)->count();
                }
            }

            return [
                'video_count'  => $videoCount,
                'sobras_count' => $sobrasCount,
            ];
        });

        $popularSizes = self::getPopularSizes(12);

        // Contagem real de itens na sacolinha da cliente logada
        $sacolinhaCount = 0;
        if (auth()->check()) {
            $sacolinhaCount = (int) DB::table('sacolinhas')
                ->where('user_id', auth()->id())
                ->where('status', '!=', 'pedido')
                ->count();
        }

        // 1. Encontrar todos os IDs de categorias que possuem itens elegíveis (preço > 0, thumbnail e não reprovados)
        $eligibleItemIdsSub = $this->baseAvailableQuery()->select('items.id');
        $categoriesWithItems = DB::table('categoria_item')
            ->whereIn('item_id', $eligibleItemIdsSub)
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

        // 3. Carregar as categorias raiz visíveis
        $categorias = Categoria::whereNull('parent_id')
            ->whereIn('id', $allVisibleCategoryIds)
            ->with(['children' => function ($query) use ($allVisibleCategoryIds) {
                $query->whereIn('id', $allVisibleCategoryIds)
                    ->withCount(['items' => fn($q) => self::applyEligibleScope($q)])
                    ->with(['children' => function ($query) use ($allVisibleCategoryIds) {
                        $query->whereIn('id', $allVisibleCategoryIds)
                            ->withCount(['items' => fn($q) => self::applyEligibleScope($q)])
                            ->with(['children' => function ($query) use ($allVisibleCategoryIds) {
                                $query->whereIn('id', $allVisibleCategoryIds)
                                    ->withCount(['items' => fn($q) => self::applyEligibleScope($q)]);
                            }]);
                    }]);
            }])
            ->withCount(['items' => fn($q) => self::applyEligibleScope($q)])
            ->get();

        $processCategoryTree = function ($cat) use (&$processCategoryTree) {
            $count = $cat->items_count ?? 0;

            if ($cat->children->isNotEmpty()) {
                foreach ($cat->children as $child) {
                    $count += $processCategoryTree($child);
                }

                $cat->setRelation('children', $cat->children->sortByDesc('total_recursive_items')->values());
            }

            $cat->total_recursive_items = $count;
            return $count;
        };

        foreach ($categorias as $cat) {
            $processCategoryTree($cat);
        }

        $categorias = $categorias->sortByDesc('total_recursive_items')->values();

        $categoriaAtiva = null;
        if ($request->filled('categoria')) {
            $categoriaAtiva = Categoria::where('slug', $request->string('categoria')->toString())->first();
        }

        return view('loja.index', compact(
            'items',
            'categorias',
            'categoriaAtiva',
            'popularSizes',
            'quickStats',
            'ordem',
            'sacolinhaCount'
        ));
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
        if (auth()->check() && method_exists(auth()->user(), 'isBrechoParceiro') && auth()->user()->isBrechoParceiro()) {
            return redirect()->route('dashboard');
        }

        // Isolamento de brechó parceiro (nunca expõe peças de parceiros na loja da Matriz)
        if (!empty($item->brecho_id) && (int) $item->brecho_id > 1) {
            abort(404);
        }

        $isAvailable = in_array($item->status, ['loja', 'estoque', 'disponivel'], true)
            && ((float) ($item->preco ?? 0) > 0)
            && (
                $item->medias()->where('media_type', 'image')->whereNotNull('url')->where('url', '!=', '')->exists()
                || $item->medias()->where('media_type', 'video')->whereNotNull('thumbnail_url')->where('thumbnail_url', '!=', '')->exists()
                || !empty($item->image)
            );

        if ($isAvailable && Schema::hasTable('live_items') && Schema::hasColumn('live_items', 'audit_status')) {
            $hasBadCut = DB::table('live_items')
                ->where('item_id', $item->id)
                ->whereIn('audit_status', ['mismatch', 'rejected'])
                ->exists();
            if ($hasBadCut) {
                $hasStudioPhoto = $item->medias()
                    ->where('media_type', 'image')
                    ->whereNotNull('url')
                    ->where('url', '!=', '')
                    ->where('url', 'not like', '%live_cuts/%')
                    ->exists();
                if (!$hasStudioPhoto) {
                    $isAvailable = false;
                }
            }
        }

        $item->load([
            'medias' => function ($q) {
                $q->orderByDesc('is_cover')
                    ->orderBy('position')
                    ->orderBy('id');
            },
            'categorias'
        ]);

        // Itens relacionados do mesmo tamanho (ou recentes) para manter a cliente navegando
        $relatedQuery = $this->baseAvailableQuery()->where('items.id', '!=', $item->id);
        if (!empty($item->tamanho)) {
            $relatedItems = (clone $relatedQuery)
                ->whereRaw('UPPER(TRIM(tamanho)) = ?', [strtoupper(trim($item->tamanho))])
                ->with(['medias' => fn($q) => $q->orderByDesc('is_cover')->orderBy('position')->orderBy('id'), 'categorias'])
                ->inRandomOrder()
                ->limit(4)
                ->get();
        } else {
            $relatedItems = collect();
        }

        if ($relatedItems->count() < 4) {
            $excludeIds = $relatedItems->pluck('id')->push($item->id)->all();
            $extra = $this->baseAvailableQuery()
                ->whereNotIn('items.id', $excludeIds)
                ->with(['medias' => fn($q) => $q->orderByDesc('is_cover')->orderBy('position')->orderBy('id'), 'categorias'])
                ->inRandomOrder()
                ->limit(4 - $relatedItems->count())
                ->get();
            $relatedItems = $relatedItems->concat($extra);
        }

        $sacolinhaCount = 0;
        if (auth()->check()) {
            $sacolinhaCount = (int) DB::table('sacolinhas')
                ->where('user_id', auth()->id())
                ->where('status', '!=', 'pedido')
                ->count();
        }

        return view('loja.show', compact('item', 'isAvailable', 'relatedItems', 'sacolinhaCount'));
    }

    public function adicionarItemSacola(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id'  => 'nullable|exists:users,id',
                'item_id'  => 'required|exists:items,id',
                'quantity' => 'nullable|integer|min:1',
                'obs'      => 'nullable|string',
                'tray'     => 'nullable|integer',
                'status'   => 'nullable|string',
                'price'    => 'nullable|numeric|min:0',
            ]);

            if (!auth()->check()) {
                return response()->json([
                    'success'    => false,
                    'needs_auth' => true,
                    'message'    => 'Informe seu WhatsApp ou entre na sua conta para reservar esta peça na sacolinha.',
                ], 401);
            }

            $authUser = auth()->user();
            $isAdmin = in_array($authUser->role, ['admin', 'admin_master', 'brecho_admin'], true) || !empty($authUser->is_admin);

            // Segurança: clientes comuns só podem adicionar à própria sacolinha
            $userId = ($isAdmin && !empty($validated['user_id']))
                ? (int) $validated['user_id']
                : (int) $authUser->id;

            $result = DB::transaction(function () use ($validated, $userId, $isAdmin) {
                $liveId = 1;
                $itemId = (int) $validated['item_id'];
                $quantity = (int) ($validated['quantity'] ?? 1);

                // Bloqueia o item para evitar reserva duplicada simultânea de peça única
                $itemObj = Item::with('categorias')
                    ->where('id', $itemId)
                    ->lockForUpdate()
                    ->first();

                if (!$itemObj || (!empty($itemObj->brecho_id) && (int) $itemObj->brecho_id > 1)) {
                    return [
                        'success' => false,
                        'code'    => 403,
                        'message' => 'Este produto não está disponível para esta loja.',
                    ];
                }

                if (!in_array($itemObj->status, ['loja', 'estoque', 'disponivel'], true) || (float) ($itemObj->preco ?? 0) <= 0) {
                    return [
                        'success' => false,
                        'code'    => 409,
                        'message' => 'Ops! Esta peça única não está mais disponível para reserva.',
                    ];
                }

                // Preço sempre calculado no servidor para clientes (evita adulteração)
                $price = ($isAdmin && isset($validated['price']) && $validated['price'] !== null)
                    ? (float) $validated['price']
                    : (float) $itemObj->final_price;

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
                    $sacolinhaItem->price = $price;
                    $sacolinhaItem->save();
                    $message = 'Peça já reservada na sua sacolinha.';
                } else {
                    $sacolinhaItem = Sacolinhas::create([
                        'brecho_id' => 1,
                        'user_id'   => $userId,
                        'item_id'   => $itemId,
                        'live_id'   => $liveId,
                        'quantity'  => $quantity,
                        'price'     => $price,
                        'add_at'    => now(),
                        'tray'      => $validated['tray'] ?? null,
                        'status'    => $validated['status'] ?? 'pendente',
                        'obs'       => $validated['obs'] ?? 'Reservado pela Loja Online',
                    ]);

                    $message = 'Peça reservada com sucesso na sua sacolinha!';
                }

                DB::table('items')
                    ->where('id', $itemId)
                    ->update([
                        'status'     => 'solicitado na loja',
                        'updated_at' => now(),
                    ]);

                Cache::forget('loja_popular_sizes_v2');
                Cache::forget('loja_quick_stats_v2');
                Cache::forget('loja_sitemap_xml_v1');

                $sacolinhaCount = (int) DB::table('sacolinhas')
                    ->where('user_id', $userId)
                    ->where('status', '!=', 'pedido')
                    ->count();

                return [
                    'success'         => true,
                    'code'            => 201,
                    'message'         => $message,
                    'sacolinha'       => $sacolinhaItem,
                    'sacolinha_count' => $sacolinhaCount,
                ];
            });

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], $result['code']);
            }

            return response()->json([
                'success'         => true,
                'message'         => $result['message'],
                'data'            => $result['sacolinha'],
                'sacolinha_count' => $result['sacolinha_count'] ?? 1,
            ], $result['code']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro adicionarItemSacola: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao reservar a peça. Tente novamente.',
            ], 500);
        }
    }

    /**
     * Sitemap XML dinâmico para indexação no Google (Home, Loja e Produtos disponíveis da Minha Mania).
     */
    public function sitemap()
    {
        $xml = Cache::remember('loja_sitemap_xml_v1', now()->addHour(), function () {
            $urls = [];

            $urls[] = [
                'loc'        => url('/'),
                'lastmod'    => now()->toAtomString(),
                'changefreq' => 'daily',
                'priority'   => '1.0',
            ];

            $urls[] = [
                'loc'        => route('loja.index'),
                'lastmod'    => now()->toAtomString(),
                'changefreq' => 'hourly',
                'priority'   => '0.9',
            ];

            $items = $this->baseAvailableQuery()
                ->select(['id', 'updated_at'])
                ->orderByDesc('updated_at')
                ->limit(1000)
                ->get();

            foreach ($items as $item) {
                $urls[] = [
                    'loc'        => route('loja.show', $item->id),
                    'lastmod'    => ($item->updated_at ?: now())->toAtomString(),
                    'changefreq' => 'daily',
                    'priority'   => '0.8',
                ];
            }

            $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            foreach ($urls as $u) {
                $out .= "  <url>\n";
                $out .= '    <loc>' . e($u['loc']) . "</loc>\n";
                $out .= '    <lastmod>' . $u['lastmod'] . "</lastmod>\n";
                $out .= '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
                $out .= '    <priority>' . $u['priority'] . "</priority>\n";
                $out .= "  </url>\n";
            }
            $out .= '</urlset>';
            return $out;
        });

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=utf-8');
    }
}

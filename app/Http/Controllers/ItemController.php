<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver;
use App\Services\GeminiImageEditService;


class ItemController extends Controller
{
	public function index(Request $request)
	{
		$query = Item::query();

		if ($request->filled('codigo')) {
            $codigoPesquisa = trim($request->codigo);
            
            // Limpar URLs caso venha uma URL completa do QR Code
            if (filter_var($codigoPesquisa, FILTER_VALIDATE_URL)) {
                $path = parse_url($codigoPesquisa, PHP_URL_PATH);
                $parts = array_filter(explode('/', (string)$path));
                if (!empty($parts)) {
                    $codigoPesquisa = end($parts);
                }
            }

            if (preg_match('/^AV(\d+)$/i', $codigoPesquisa, $matches)) {
                $avItemId = $matches[1];
                $avItem = \App\Models\AvaliacaoItem::find($avItemId);
                if ($avItem) {
                    if ($avItem->item_id) {
                        $query->where('id', $avItem->item_id);
                    } else {
                        // Se encontrou na avaliação mas não virou item, redireciona
                        return redirect()->route('admin.avaliacoes.show', $avItem->avaliacao_id)
                            ->with('error', 'O item AV'.$avItemId.' ainda está em fase de avaliação e não foi transferido para o estoque.');
                    }
                } else {
                    $query->where('id', -1);
                }
            } else {
			    $query->where(function($q) use ($codigoPesquisa) {
                    $q->where('codigo', 'like', '%' . $codigoPesquisa . '%')
                      ->orWhere('nome_do_produto', 'like', '%' . $codigoPesquisa . '%')
                      ->orWhere('descricao', 'like', '%' . $codigoPesquisa . '%');
                });
            }
		}

		if ($request->filled('localizacao')) {
			$query->where('localizacao', 'like', '%' . $request->localizacao . '%');
		}

		if ($request->filled('status')) {
			$query->where('status', $request->status);
		}

        if ($request->filled('categoria_id')) {
            if ($request->categoria_id === 'none') {
                $query->whereDoesntHave('categorias');
            } else {
                $query->whereHas('categorias', function ($q) use ($request) {
                    $q->where('categorias.id', $request->categoria_id);
                });
            }
        }

		$items = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $treeCategories = \App\Models\Categoria::whereNull('parent_id')
            ->with($this->categoryTreeWith())
            ->orderBy('name')
            ->get();

		return view('admin.items.index', compact('items', 'treeCategories'));
	}
		

    public function create()
    {
        $treeCategories = \App\Models\Categoria::whereNull('parent_id')
            ->with($this->categoryTreeWith())
            ->orderBy('name')
            ->get();
        return view('admin.items.create', compact('treeCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|unique:items,codigo',
            'nome_do_produto' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'custo' => 'nullable|numeric|min:0',
            'preco' => 'required|numeric|min:0',
            'codigo_da_categoria' => 'nullable|string',
            'marca' => 'nullable|string',
            'modelo' => 'nullable|string',
            'estado' => 'required|in:novo,usado,semi-novo,recondicionado',
            'cor' => 'nullable|string',
            'tamanho' => 'nullable|string',
            'pedido' => 'nullable|string',
            'status' => 'required|in:indisponivel,disponivel,reservado,vendido,em_sacolinha,loja,estoque,live',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('items', 'public');
        }

        $item = Item::create($validated);
        
        if ($request->has('categorias')) {
            $item->categorias()->sync($request->categorias);
        }

        return redirect()->route('items.index')->with('success', 'Item criado com sucesso!');
    }

    public function show(Item $item)
    {
        return view('admin.items.show', compact('item'));
    }

	public function edit(Item $item)
	{
		// Carrega o item com suas mídias, ordenadas pela coluna 'position'
		$item->load(['medias' => function ($query) {
			$query->orderBy('position', 'asc');
		}, 'categorias']);

        $treeCategories = \App\Models\Categoria::whereNull('parent_id')
            ->with($this->categoryTreeWith())
            ->orderBy('name')
            ->get();

		return view('admin.items.edit', compact('item', 'treeCategories'));
	}

    /**
     * Helper para carregar a árvore de categorias
     */
    private function categoryTreeWith(): array
    {
        return [
            'children' => fn($q) => $q->orderBy('name')->with([
                'children' => fn($q2) => $q2->orderBy('name')->with([
                    'children' => fn($q3) => $q3->orderBy('name')->with([
                        'children' => fn($q4) => $q4->orderBy('name')
                    ])
                ])
            ])
        ];
    }
	
	
	public function update(Request $request, Item $item)
	{
		$validated = $request->validate([
			'codigo' => 'required|string|unique:items,codigo,' . $item->id,
			'nome_do_produto' => 'required|string|max:255',
			'descricao' => 'nullable|string',
			'custo' => 'nullable|numeric|min:0',
			'preco' => 'required|numeric|min:0',
			'codigo_da_categoria' => 'nullable|string',
			'marca' => 'nullable|string',
			'modelo' => 'nullable|string',
			'estado' => 'required|in:novo,usado,semi-novo,recondicionado',
			'cor' => 'nullable|string',
			'tamanho' => 'nullable|string',
			'pedido' => 'nullable|string',
			'status' => 'required|in:indisponivel,disponivel,reservado,vendido,em_sacolinha,loja,estoque,live,solicitado na loja,solicitado na live',
			'localizacao' => 'nullable|string|max:255',
			'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
		]);

		if ($request->hasFile('image')) {
			if ($item->image) {
				Storage::disk('public')->delete($item->image);
			}
			$validated['image'] = $request->file('image')->store('items', 'public');
		}

		$item->update($validated);

        if ($request->has('categorias')) {
            $item->categorias()->sync($request->categorias);
        } else {
            $item->categorias()->detach();
        }

		// Lógica para upload de novas mídias
		if ($request->hasFile('new_media')) {
			foreach ($request->file('new_media') as $file) {
				$mime = $file->getMimeType() ?? '';
				$isVideo = str_starts_with($mime, 'video/');

				if ($isVideo) {
					$path = $file->store("items/{$item->id}/videos", 'public');
					$item->medias()->create([
						'media_type' => 'video',
						'url' => $path,
						'thumbnail_url' => null,
						'metadata' => [
							'original_name' => $file->getClientOriginalName(),
							'mime' => $mime,
							'size_bytes' => $file->getSize(),
						],
					]);
					continue;
				}

				$out = $this->storeStandardizedImage($file, $item->id);
				$item->medias()->create([
					'media_type' => 'image',
					'url' => $out['url'],
					'thumbnail_url' => $out['thumbnail_url'],
					'metadata' => $out['metadata'],
				]);
			}
			$item->syncMainImage();
		}

		// ✨ A MÁGICA ESTÁ AQUI:
		
		// 1. Se for uma requisição AJAX ou esperar JSON (Modal do Image-Groups)
		if ($request->expectsJson() || $request->ajax()) {
			return response()->json([
				'success' => true,
				'message' => 'Item atualizado com sucesso!'
			]);
		}

		// 2. Se for uma submissão normal de formulário (Página edit.blade)
		return redirect()
			->route('items.index')
			->with('success', 'Item atualizado com sucesso!');
	}
	
	
	
	
    public function destroy(Item $item)
    {
        // Deletar imagem se existir
        if ($item->image) {
            Storage::disk('public')->delete($item->image);
        }

        $item->delete();

        return redirect()->route('items.index')->with('success', 'Item deletado com sucesso!');
    }
		
	public function destroyMedia(Item $item, ItemMedia $medias)
	{
		// Garante que a mídia pertence ao item (segurança)
		if ($medias->item_id !== $item->id) {
			return back()->with('error', 'Acesso negado.');
		}

		// Deleta o arquivo do disco
		Storage::disk('public')->delete($medias->url);
		if ($medias->thumbnail_url) {
			Storage::disk('public')->delete($medias->thumbnail_url);
		}

		// Deleta o registro do banco
		$medias->delete();

		// Sincroniza a imagem principal após remover
		$item->syncMainImage();

		return back()->with('success', 'Mídia removida com sucesso.');
	}	

    /**
     * Search items for API
     */
    public function search(Request $request)
    {
        $rawQuery = trim($request->get('q', ''));
        $query = $rawQuery;

        // Limpar URLs caso venha uma URL completa do QR Code
        if (filter_var($query, FILTER_VALIDATE_URL)) {
            $path = parse_url($query, PHP_URL_PATH);
            $parts = array_filter(explode('/', (string)$path));
            if (!empty($parts)) {
                $query = end($parts);
            }
        }
        
        $itemBuilder = Item::query();

        if (preg_match('/^AV(\d+)$/i', $query, $matches)) {
            $avItemId = $matches[1];
            $avItem = \App\Models\AvaliacaoItem::find($avItemId);
            if ($avItem && $avItem->item_id) {
                $itemBuilder->where('id', $avItem->item_id);
            } else {
                $itemBuilder->where('id', -1);
            }
        } else {
            $itemBuilder->where(function($q) use ($query) {
                $q->where('codigo', $query)
                  ->orWhere('codigo', mb_strtoupper($query, 'UTF-8'))
                  ->orWhere('codigo', mb_strtolower($query, 'UTF-8'))
                  ->orWhere('codigo', 'like', "%{$query}%")
                  ->orWhere('nome_do_produto', 'like', "%{$query}%")
                  ->orWhere('descricao', 'like', "%{$query}%");
            });
        }
        
        // Permite buscar itens tanto em estoque quanto disponíveis
        $items = $itemBuilder->limit(15)->get();
        
        // Formatar dados para o component
        $formattedItems = $items->map(function($item) {
            return [
                'id' => $item->id,
                'name' => $item->nome_do_produto ?: 'Sem Nome',
                'sku' => $item->codigo,
                'codigo' => $item->codigo,
                'price' => $item->preco,
                'formatted_price' => 'R$ ' . number_format($item->preco ?? 0, 2, ',', '.'),
                'image_url' => $item->image ? asset('storage/' . $item->image) : asset('images/no-image.png'),
                'stock' => ucfirst($item->status ?? 'Estoque'),
                'localizacao' => $item->localizacao ?? '-',
                'description' => $item->descricao ?? ''
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $formattedItems
        ]);
    }
	
	
    // INVENTÁRIO (Relatório de Locais Físicos e Resumo de Estoque)
    public function inventario(Request $request)
    {
        $buscaLocal = trim($request->input('localizacao', ''));

        // Query de Locais Físicos do Estoque (Agrupados por localizacao)
        $queryLocais = DB::table('items')
            ->whereNotNull('localizacao')
            ->where('localizacao', '!=', '');

        if (!empty($buscaLocal)) {
            $queryLocais->where('localizacao', 'like', '%' . $buscaLocal . '%');
        }

        $locaisEstoque = $queryLocais
            ->select(
                'localizacao',
                DB::raw('COUNT(*) as qtd_pecas'),
                DB::raw('SUM(preco) as valor_total_venda'),
                DB::raw('AVG(preco) as valor_medio_venda')
            )
            ->groupBy('localizacao')
            ->orderBy('localizacao', 'asc')
            ->get();

        // Resumo Geral do Estoque
        $itensEstoque = Item::where('status', 'estoque')->get();
        $estoqueInfo = [
            'quantidade' => $itensEstoque->count(),
            'valor_total' => $itensEstoque->sum('preco'),
            'valor_medio' => $itensEstoque->count() > 0 ? round($itensEstoque->sum('preco') / $itensEstoque->count(), 2) : 0
        ];

        // Itens sem localização
        $itensSemLocal = Item::where(function($q) {
            $q->whereNull('localizacao')->orWhere('localizacao', '');
        })->count();

        return view('admin.items.inventario_report', compact('locaisEstoque', 'estoqueInfo', 'buscaLocal', 'itensSemLocal'));
    }

    // INVENTÁRIO SCANNER (Interface de Escaneamento / Bipagem)
    public function inventarioScanner(Request $request)
    {
        $defaultLocal  = trim($request->input('localizacao', ''));
        $defaultStatus = trim($request->input('status', ''));
        $defaultCor    = trim($request->input('cor', ''));

        if (!empty($defaultLocal) && empty($defaultStatus)) {
            $defaultStatus = 'estoque';
        }

        $coresDisponiveis = [];
        if (!empty($defaultLocal)) {
            $coresDisponiveis = Item::where('localizacao', $defaultLocal)
                ->whereNotNull('cor')
                ->where('cor', '!=', '')
                ->distinct()
                ->pluck('cor')
                ->values()
                ->toArray();
        }

        return view('admin.items.inventario', compact('defaultLocal', 'defaultStatus', 'defaultCor', 'coresDisponiveis'));
    }

    // PÁGINA DE DETALHES DE UM LOCAL FÍSICO ESPECÍFICO
    public function inventarioLocalDetalhes($localizacao, Request $request)
    {
        $localizacao = trim(urldecode($localizacao));
        $search = trim($request->input('q', ''));
        $statusFiltro = $request->input('status');

        $query = Item::where('localizacao', $localizacao);

        if (!empty($search)) {
            if (preg_match('/^AV(\d+)$/i', $search, $matches)) {
                $avItemId = $matches[1];
                $avItem = \App\Models\AvaliacaoItem::find($avItemId);
                if ($avItem && $avItem->item_id) {
                    $query->where('id', $avItem->item_id);
                } else {
                    $query->where('id', -1);
                }
            } else {
                $query->where(function($q) use ($search) {
                    $q->where('codigo', 'like', "%{$search}%")
                      ->orWhere('nome_do_produto', 'like', "%{$search}%")
                      ->orWhere('marca', 'like', "%{$search}%")
                      ->orWhere('tamanho', 'like', "%{$search}%")
                      ->orWhere('cor', 'like', "%{$search}%");
                });
            }
        }

        if (!empty($statusFiltro)) {
            $query->where('status', $statusFiltro);
        }

        $itens = $query->orderBy('codigo', 'asc')->paginate(50)->withQueryString();

        // Estatísticas do Local
        $todosDoLocal = Item::where('localizacao', $localizacao)->get();
        $statsLocal = [
            'localizacao' => $localizacao,
            'total_itens' => $todosDoLocal->count(),
            'valor_total' => $todosDoLocal->sum('preco'),
            'valor_custo' => $todosDoLocal->sum('custo'),
            'valor_medio' => $todosDoLocal->count() > 0 ? round($todosDoLocal->sum('preco') / $todosDoLocal->count(), 2) : 0,
        ];

        return view('admin.items.inventario_local_detalhes', compact('localizacao', 'itens', 'statsLocal', 'search', 'statusFiltro'));
    }

    // OBTER LISTA DETALHADA DE ITENS DO LOCAL FÍSICO (AJAX)
    public function inventarioItensLocal(Request $request)
    {
        $localizacao = trim($request->input('localizacao', ''));

        if (empty($localizacao)) {
            return response()->json(['error' => 'Localização não informada.'], 400);
        }

        $itens = Item::where('localizacao', $localizacao)
            ->orderBy('codigo', 'asc')
            ->get([
                'id',
                'codigo',
                'nome_do_produto',
                'descricao',
                'tamanho',
                'cor',
                'marca',
                'modelo',
                'estado',
                'status',
                'preco',
                'custo',
                'localizacao',
                'updated_at'
            ]);

        $statusLabels = [
            'disponivel' => 'Disponível',
            'vendido'    => 'Vendido',
            'reservado'  => 'Reservado',
            'estoque'    => 'Em Estoque',
        ];

        $itensFormatados = $itens->map(function($item) use ($statusLabels) {
            return [
                'id'              => $item->id,
                'codigo'          => $item->codigo,
                'nome_do_produto' => $item->nome_do_produto ?: 'Sem Nome',
                'tamanho'         => $item->tamanho ?: '-',
                'cor'             => $item->cor ?: '-',
                'marca'           => $item->marca ?: '-',
                'modelo'          => $item->modelo ?: '-',
                'estado'          => $item->estado ?: '-',
                'status'          => $item->status,
                'status_label'    => $statusLabels[$item->status] ?? ucfirst($item->status),
                'preco'           => number_format($item->preco ?? 0, 2, ',', '.'),
                'custo'           => number_format($item->custo ?? 0, 2, ',', '.'),
                'edit_url'        => route('items.edit', $item->id),
                'show_url'        => route('items.show', $item->id),
            ];
        });

        return response()->json([
            'localizacao'   => $localizacao,
            'total_itens'   => $itens->count(),
            'valor_total'   => number_format($itens->sum('preco'), 2, ',', '.'),
            'valor_custo'   => number_format($itens->sum('custo'), 2, ',', '.'),
            'itens'         => $itensFormatados,
        ]);
    }

    public function inventarioProcessar(Request $request)
    {
        $request->validate([
            'codigos'     => 'required|array|min:1',
            'codigos.*'   => 'required|string',
            'status'      => 'nullable|string',
            'localizacao' => 'nullable|string|max:255',
            'cor'         => 'nullable|string|max:255',
        ]);

        $codigosLidos = array_values(array_unique(array_filter(array_map('trim', $request->codigos))));
        $novoStatus   = $request->status;
        $localizacao  = trim($request->localizacao ?? '');
        $cor          = trim($request->cor ?? '');

        // 1. Carregar itens esperados no local antes da atualização
        $itensEsperados = collect();
        if (!empty($localizacao)) {
            $itensEsperados = Item::where('localizacao', $localizacao)->get();
        }

        $encontrados = [];
        $sobrando    = [];
        $naoEncontradosBanco = [];
        $atualizados = 0;

        foreach ($codigosLidos as $rawCodigo) {
            $codigo = trim($rawCodigo);

            // Limpar URLs caso venha uma URL completa do QR Code
            if (filter_var($codigo, FILTER_VALIDATE_URL)) {
                $path = parse_url($codigo, PHP_URL_PATH);
                $parts = array_filter(explode('/', (string)$path));
                if (!empty($parts)) {
                    $codigo = end($parts);
                }
            }

            // Verifica se é código de avaliação (AV)
            if (preg_match('/^AV(\d+)$/i', $codigo, $matches)) {
                $avItemId = $matches[1];
                $avItem = \App\Models\AvaliacaoItem::find($avItemId);
                if ($avItem && $avItem->item_id) {
                    $item = Item::find($avItem->item_id);
                } else {
                    $item = null;
                }
            } else {
                // Busca por código exato, maiúsculo ou minúsculo
                $item = Item::where('codigo', $codigo)
                    ->orWhere('codigo', mb_strtoupper($codigo, 'UTF-8'))
                    ->orWhere('codigo', mb_strtolower($codigo, 'UTF-8'))
                    ->first();
            }

            if (!$item) {
                $naoEncontradosBanco[] = $codigo;
                continue;
            }

            $localAnterior = $item->localizacao;
            $dados = [];

            if ($novoStatus && $item->status !== $novoStatus) {
                $dados['status'] = $novoStatus;
            }
            if (!empty($localizacao)) {
                $dados['localizacao'] = $localizacao;
            }
            if (!empty($cor)) {
                $dados['cor'] = $cor;
            }

            if (!empty($dados)) {
                $dados['updated_at'] = now();
                $item->update($dados);
                $atualizados++;
            }

            $itemInfo = [
                'id'              => $item->id,
                'codigo'          => $item->codigo,
                'nome_do_produto' => $item->nome_do_produto ?: 'Sem Nome',
                'tamanho'         => $item->tamanho ?: '-',
                'cor'             => $item->cor ?: '-',
                'marca'           => $item->marca ?: '-',
                'preco'           => number_format($item->preco ?? 0, 2, ',', '.'),
                'local_anterior'  => $localAnterior ?: 'Sem Local',
            ];

            if (!empty($localizacao) && $localAnterior === $localizacao) {
                $encontrados[] = $itemInfo;
            } else {
                $sobrando[] = $itemInfo;
            }
        }

        // 2. Identificar faltantes (itens esperados no local que NÃO foram lidos no scanner)
        $faltantes = [];
        if (!empty($localizacao)) {
            foreach ($itensEsperados as $itemExp) {
                if (!in_array($itemExp->codigo, $codigosLidos)) {
                    $faltantes[] = [
                        'id'              => $itemExp->id,
                        'codigo'          => $itemExp->codigo,
                        'nome_do_produto' => $itemExp->nome_do_produto ?: 'Sem Nome',
                        'tamanho'         => $itemExp->tamanho ?: '-',
                        'cor'             => $itemExp->cor ?: '-',
                        'marca'           => $itemExp->marca ?: '-',
                        'preco'           => number_format($itemExp->preco ?? 0, 2, ',', '.'),
                        'status'          => $itemExp->status,
                    ];
                }
            }
        }

        // 3. Criar registro oficial de conferência de inventário se houver localização
        if (!empty($localizacao)) {
            $totalEsperado    = count($itensEsperados);
            $totalLido        = count($codigosLidos);
            $totalEncontrados = count($encontrados);
            $totalFaltantes   = count($faltantes);
            $totalSobrando    = count($sobrando);

            $acuracia = $totalEsperado > 0 
                ? round(($totalEncontrados / $totalEsperado) * 100, 2) 
                : 100.00;

            $conferencia = \App\Models\ConferenciaInventario::create([
                'user_id'             => auth()->id(),
                'localizacao'         => $localizacao,
                'status_aplicado'     => $novoStatus,
                'cor_aplicada'        => $cor,
                'total_esperado'      => $totalEsperado,
                'total_lido'          => $totalLido,
                'total_encontrados'   => $totalEncontrados,
                'total_faltantes'     => $totalFaltantes,
                'total_sobrando'      => $totalSobrando,
                'acuracia_percentual' => $acuracia,
                'detalhes_json'       => [
                    'encontrados'           => $encontrados,
                    'faltantes'             => $faltantes,
                    'sobrando'              => $sobrando,
                    'nao_encontrados_banco' => $naoEncontradosBanco,
                ]
            ]);

            return redirect()->route('inventario.conferencias.show', $conferencia->id)
                ->with('success', "✅ Conferência do Local {$localizacao} realizada com sucesso! Acurácia: {$acuracia}%");
        }

        $statusLabel = $this->getStatusLabel($novoStatus ?? '');
        $msg = "✅ {$atualizados} item(ns) atualizado(s)";
        if ($novoStatus)  $msg .= " → status: {$statusLabel}";
        if ($localizacao) $msg .= " | local: {$localizacao}";
        if ($cor)         $msg .= " | cor: {$cor}";

        return redirect()->route('inventario.scanner')->with('success', $msg);
    }

    // LISTAGEM DE HISTÓRICO DE CONFERÊNCIAS DE ESTOQUE
    public function inventarioConferenciasIndex(Request $request)
    {
        $localFiltro = trim($request->input('localizacao', ''));

        $query = \App\Models\ConferenciaInventario::with('user');

        if (!empty($localFiltro)) {
            $query->where('localizacao', 'like', "%{$localFiltro}%");
        }

        $conferencias = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $statsGerais = [
            'total_conferencias' => \App\Models\ConferenciaInventario::count(),
            'media_acuracia'     => round(\App\Models\ConferenciaInventario::avg('acuracia_percentual') ?? 0, 1),
            'total_faltantes'    => \App\Models\ConferenciaInventario::sum('total_faltantes'),
        ];

        return view('admin.items.inventario_conferencias_index', compact('conferencias', 'statsGerais', 'localFiltro'));
    }

    // RELATÓRIO DETALHADO DE UMA CONFERÊNCIA ESPECÍFICA
    public function inventarioConferenciaShow($id)
    {
        $conferencia = \App\Models\ConferenciaInventario::with('user')->findOrFail($id);

        return view('admin.items.inventario_conferencia_show', compact('conferencia'));
    }


	private function getStatusLabel($status)
	{
		$labels = [
			'disponivel' => 'Disponível',
			'vendido' => 'Vendido',
			'reservado' => 'Reservado'
		];

		return $labels[$status] ?? $status;
	}
	
	public function etiqueta()
    {
        // Aqui você pode adicionar lógica, como verificar permissões ou carregar dados iniciais
        // Exemplo: $bagItems = Bag::where('id', 0)->first(); // Se precisar de dados

        return view('admin.items.etiqueta');
    }


	private function storeStandardizedImage($file, int $itemId): array
	{
		$manager = new \Intervention\Image\ImageManager(new Driver());
		//$manager = new ImageManager(new Driver());

		$img = $manager->read($file->getRealPath());

		// 1) Normalizar orientação (se houver EXIF) e redimensionar (máx 1600px)
		$img = $img->scaleDown(width: 1600);

		// 2) Gerar nome base
		$base = Str::uuid()->toString();
		$dir = "items/{$itemId}";

		// 3) Salvar versão web (WEBP)
		$webPath = "{$dir}/{$base}.webp";
		Storage::disk('public')->put($webPath, (string) $img->toWebp(quality: 82));

		// 4) Thumb (400px)
		$thumb = $img->scaleDown(width: 400);
		$thumbPath = "{$dir}/thumb_{$base}.webp";
		Storage::disk('public')->put($thumbPath, (string) $thumb->toWebp(quality: 78));

		return [
			'url' => $webPath,
			'thumbnail_url' => $thumbPath,
			'metadata' => [
				'original_name' => $file->getClientOriginalName(),
				'original_mime' => $file->getMimeType(),
				'original_size' => $file->getSize(),
			],
		];
	}	
	

	public function uploadMedia(Request $request, Item $item)
	{
		$request->validate([
			'new_media' => 'required',
			'new_media.*' => 'file|max:51200', // 50MB por arquivo
		]);

		$created = [];

		foreach ($request->file('new_media') as $file) {
			$mime = $file->getMimeType() ?? '';
			$isVideo = str_starts_with($mime, 'video/');

			if ($isVideo) {
				$path = $file->store("items/{$item->id}/videos", 'public');

				$medias = $item->medias()->create([
					'media_type' => 'video',
					'url' => $path,
					'thumbnail_url' => null,
					'metadata' => [
						'original_name' => $file->getClientOriginalName(),
						'mime' => $mime,
						'size_bytes' => $file->getSize(),
					],
				]);
			} else {
				// Usa o método storeStandardizedImage que você já tem
				$out = $this->storeStandardizedImage($file, $item->id);

				$medias = $item->medias()->create([
					'media_type' => 'image',
					'url' => $out['url'],
					'thumbnail_url' => $out['thumbnail_url'],
					'metadata' => $out['metadata'],
				]);
			}

			$rawUrl = $medias->thumbnail_url ?: $medias->url;
			$finalUrl = $rawUrl ? asset('storage/' . ltrim($rawUrl, '/')) : null;

			$created[] = [
				'id' => $medias->id,
				'media_type' => $medias->media_type,
				'final_url' => $finalUrl,
				'is_cover' => (bool) ($medias->is_cover ?? false),
			];
		}

		// Sincroniza a imagem principal após o upload
		$item->syncMainImage();

		return response()->json([
			'ok' => true,
			'media' => $created,
		]);
	}	
		

	
	public function aiEditMedia(Request $request, Item $item)
	{
\Log::info('aiEditMedia called', [
  'item_id' => $item->id,
  'media_ids' => $request->input('media_ids'),
  'user_id' => auth()->id(),
]);			
		$data = $request->validate([
			'media_ids' => 'required|array|min:1',
			'media_ids.*' => 'integer',
		]);

		// Uma por vez (como você quer)
		$mediaId = (int) $data['media_ids'][0];

		$media = $item->medias()
			->where('id', $mediaId)
			->where('media_type', 'image')
			->first();

		if (!$media) {
			return response()->json([
				'ok' => false,
				'error' => 'Imagem não encontrada ou não é do tipo image.',
			], 404);
		}

		$gemini = app(\App\Services\GeminiImageEditService::class);

		$result = $gemini->editImage($media->url); // usa seu prompt padrão no service

		if (!$result['success']) {
			\Log::error('Gemini edit failed', [
				'media_id' => $media->id,
				'error' => $result['error'],
			]);

			return response()->json([
				'ok' => false,
				'error' => $result['error'],
			], 500);
		}

		// Criar NOVA versão (não sobrescrever)
		$new = $item->medias()->create([
			'media_type' => 'image',
			'url' => $result['path'],
			'thumbnail_url' => $result['path'], // ou gere thumb se quiser
			'metadata' => array_merge(($media->metadata ?? []), [
				'edited_from_id' => $media->id,
				'edited_at' => now()->toIso8601String(),
			]),
		]);

		$item->syncMainImage();

		return response()->json([
			'ok' => true,
			'updated' => [
				[
					'id' => $media->id,
					// Se você quer trocar a imagem do card selecionado:
					'final_url' => asset('storage/' . ltrim($new->url, '/')),
					// Se preferir inserir um NOVO card no front, devolva também new_id:
					'new_id' => $new->id,
				]
			],
		]);
	}
	
	public function scannerSacolinha()
	{
		// Certifique-se de que o arquivo físico é: resources/views/admin/sacolinhas/qrcode-sacolinha.blade.php
		return view('admin.sacolinhas.qrcode-sacolinha');
	}

	public function buscarPorCodigo(Request $request)
	{
		$codigo = trim($request->get('codigo'));
		
		$itemBuilder = \App\Models\Item::with([
			'medias' => function($q) {
				$q->where('media_type', 'image');
			}, 
			'sacolinha.user' // Relacionamento aninhado
		]);

		if (preg_match('/^AV(\d+)$/i', $codigo, $matches)) {
			$avItemId = $matches[1];
			$avItem = \App\Models\AvaliacaoItem::find($avItemId);
			if ($avItem && $avItem->item_id) {
				$item = $itemBuilder->where('id', $avItem->item_id)->first();
			} else {
				$item = null;
			}
		} else {
			$item = $itemBuilder->where('codigo', $codigo)->first();
		}

		if (!$item) {
			return response()->json(['success' => false, 'message' => 'Item não encontrado.'], 404);
		}

		$media = $item->medias->first();
		$imageUrl = $media ? asset('storage/' . $media->url) : asset('images/no-image.png');

		return response()->json([
			'success' => true,
			'data' => [
				'id' => $item->id,
				'codigo' => $item->codigo,
				'nome_do_produto' => $item->nome_do_produto,
				'marca' => $item->marca,
				'estado' => $item->estado,
				'cor' => $item->cor,
				'tamanho' => $item->tamanho,
				'status' => $item->status,
				'image_url' => $imageUrl,
				// Pega o nome do usuário através da sacolinha
				'cliente_nome' => $item->sacolinha && $item->sacolinha->user 
					? $item->sacolinha->user->name 
					: 'Sem cliente vinculado'
			]
		]);
	}


	public function atualizarStatusRapido(Request $request, \App\Models\Item $item)
	{
		$request->validate([
			'status' => [
				'required', 
				'in:indisponivel,disponivel,reservado,vendido,em_sacolinha,loja,estoque,live,solicitado na loja,solicitado na live'
			]
		]);

		$item->update(['status' => $request->status]);

		return response()->json(['success' => true, 'message' => 'Status atualizado!']);
	}
}

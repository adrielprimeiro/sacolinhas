<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
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
			$query->where('codigo', 'like', '%' . $request->codigo . '%');
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
        $query = $request->get('q');
      
       
        $items = Item::where('nome_do_produto', 'like', "%{$query}%")
                     ->orWhere('codigo', 'like', "%{$query}%")
                     ->orWhere('descricao', 'like', "%{$query}%")
                     ->where('status', 'disponivel')
                     ->limit(10)
                     ->get();
        
        // Formatar dados para o component
        $formattedItems = $items->map(function($item) {
            return [
                'id' => $item->id,
                'name' => $item->nome_do_produto,
                'sku' => $item->codigo,
                'price' => $item->preco,
                'formatted_price' => 'R$ ' . number_format($item->preco, 2, ',', '.'),
                'image_url' => $item->image ? asset('storage/' . $item->image) : asset('images/no-image.png'),
                'stock' => 'Disponível',
                'description' => $item->descricao ?? ''
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $formattedItems
        ]);
    }
	
	
    // INVENTÁRIO SCANNER
    public function inventario(Request $request)
    {
        return view('admin.items.inventario');
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

        $codigos     = array_unique(array_filter($request->codigos));
        $novoStatus  = $request->status;
        $localizacao = $request->localizacao;
        $cor         = $request->cor;

        $resultados = [];
        $atualizados = 0;
        $naoEncontrados = [];

        foreach ($codigos as $codigo) {
            $item = Item::where('codigo', $codigo)->first();
            if (!$item) {
                $naoEncontrados[] = $codigo;
                $resultados[] = ['codigo' => $codigo, 'ok' => false, 'msg' => 'Não encontrado'];
                continue;
            }

            $dados = [];
            if ($novoStatus && $item->status !== $novoStatus) {
                $dados['status'] = $novoStatus;
            }
            if ($localizacao !== null && $localizacao !== '') {
                $dados['localizacao'] = $localizacao;
            }
            if ($cor !== null && $cor !== '') {
                $dados['cor'] = $cor;
            }

            if (!empty($dados)) {
                $dados['updated_at'] = now();
                $item->update($dados);
                $atualizados++;
            }

            $resultados[] = [
                'codigo' => $codigo,
                'ok'     => true,
                'nome'   => $item->nome_do_produto,
                'status' => $item->fresh()->status,
            ];
        }

        $statusLabel = $this->getStatusLabel($novoStatus ?? '');

        $msg = "✅ {$atualizados} item(ns) atualizado(s)";
        if ($novoStatus)  $msg .= " → status: {$statusLabel}";
        if ($localizacao) $msg .= " | local: {$localizacao}";
        if ($cor)         $msg .= " | cor: {$cor}";
        if (count($naoEncontrados)) {
            $msg .= " | ⚠️ Não encontrados: " . implode(', ', $naoEncontrados);
        }

        return redirect()->route('inventario')->with('success', $msg);
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
		$codigo = $request->get('codigo');

		// Carrega mídias, a sacolinha e o usuário dono da sacolinha
		$item = \App\Models\Item::with([
			'medias' => function($q) {
				$q->where('media_type', 'image');
			}, 
			'sacolinha.user' // Relacionamento aninhado
		])->where('codigo', $codigo)->first();

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

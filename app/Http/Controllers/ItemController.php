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

		if ($request->filled('status')) {
			$query->where('status', $request->status);
		}

		$items = $query->paginate(15);

		return view('admin.items.index', compact('items'));
	}
		

    public function create()
    {
        return view('admin.items.create');
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

        Item::create($validated);

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
		}]);
		return view('admin.items.edit', compact('item'));
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
			'status' => 'required|in:indisponivel,disponivel,reservado,vendido,em_sacolinha,loja,estoque,live',
			'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
		]);

		if ($request->hasFile('image')) {
			if ($item->image) {
				Storage::disk('public')->delete($item->image);
			}
			$validated['image'] = $request->file('image')->store('items', 'public');
		}

		$item->update($validated);

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
	
	
	    // NOVO MÉTODO PARA INVENTÁRIO
	public function inventario(Request $request)
	{
		// 1. LÓGICA DO BOTÃO LIMPAR (RESET)
		if ($request->has('reset')) {
			session(['inventory_start_time' => now()]);
			session()->forget('last_status');
			return redirect()->route('inventario')
				->with('success', '🧹 Lista reiniciada! Mostrando apenas itens modificados a partir de agora.');
		}

		// 2. DEFINIR O INÍCIO DO FILTRO
		$dataInicio = session('inventory_start_time', now()->setTimezone('America/Sao_Paulo')->startOfDay()->setTimezone('UTC'));
		$query = Item::where('updated_at', '>=', $dataInicio);

		// 3. LÓGICA DE SCANNER E ATUALIZAÇÃO
		if ($request->filled('search')) {
			$codigoBuscado = trim($request->get('search'));
			
			// Busca no banco todo
			$itemEncontrado = Item::where('codigo', $codigoBuscado)->first();
			
			if ($itemEncontrado) {
				// ✅ ITEM ENCONTRADO
				if ($request->filled('status')) {
					$novoStatus = $request->get('status');
					
					// 🛑 VERIFICAÇÃO DE SEGURANÇA (AQUI ESTÁ A MUDANÇA)
					// Se o status atual for IGUAL ao novo status...
					if ($itemEncontrado->status === $novoStatus) {
						$statusLabel = $this->getStatusLabel($novoStatus);
						
						// Retorna com AVISO (Warning) e NÃO atualiza nada no banco
						return redirect()->route('inventario')
							->with('warning', "⚠️ Nenhuma alteração: O item '{$itemEncontrado->nome_do_produto}' já possui o status '{$statusLabel}'.")
							->with('last_status', $novoStatus); // Mantém o dropdown selecionado
					}

					// Se chegou aqui, é porque o status É DIFERENTE. Pode atualizar.
					$itemEncontrado->update([
						'status' => $novoStatus,
						'updated_at' => now() 
					]);
					
					$statusLabel = $this->getStatusLabel($novoStatus);
					$message = "✅ Item '{$itemEncontrado->nome_do_produto}' (código: {$codigoBuscado}) atualizado para '{$statusLabel}'!";
					
					return redirect()->route('inventario')
						->with('success', $message)
						->with('last_status', $novoStatus);
				}
				
				// Apenas encontrou (sem status selecionado no dropdown)
				$message = "ℹ️ Item verificado: '{$itemEncontrado->nome_do_produto}' (código: {$codigoBuscado})";
				return redirect()->route('inventario')->with('info', $message);
				
			} else {
				// ❌ NÃO ENCONTRADO
				return redirect()->route('inventario')
					->with('warning', "❌ Item não encontrado com o código: {$codigoBuscado}")
					->with('last_status', $request->get('status'));
			}
		}

		// 4. ORDENAÇÃO
		$items = $query->orderBy('updated_at', 'desc')->paginate(10);
		
		return view('admin.items.inventario', compact('items'));
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

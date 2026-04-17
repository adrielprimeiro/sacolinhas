<?php

namespace App\Http\Controllers;

use App\Models\ImageGroup;
use App\Models\Item;
use App\Models\ItemMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class ImageGroupController extends Controller
{
	public function index()
	{
		$groups = ImageGroup::with(['medias' => function ($q) {
				$q->orderBy('created_at', 'asc');
			}])
			->orderBy('created_at', 'desc')
			->get();

		$orphans = ItemMedia::whereNull('item_id')
			->whereNull('group_id')
			->orderBy('created_at', 'desc')
			->get();

		return view('image-groups.index', compact('groups', 'orphans'));
		
	}
	
	

    public function addMedia(Request $request, $id)
    {
        $group = ImageGroup::findOrFail($id);
        $media = ItemMedia::findOrFail($request->media_id);
        
        $media->update(['group_id' => $group->id]);
        
        return back()->with('success', 'Imagem adicionada ao grupo.');
    }

    public function removeMedia(Request $request, $id)
    {
        $media = ItemMedia::where('group_id', $id)
            ->where('id', $request->media_id)
            ->firstOrFail();
        
        $media->update(['group_id' => null]);
        
        return back()->with('success', 'Imagem removida do grupo.');
    }

    public function merge(Request $request)
    {
        $groupIds = $request->group_ids;
        $mainGroup = ImageGroup::findOrFail($groupIds[0]);
        
        foreach (array_slice($groupIds, 1) as $groupId) {
            $group = ImageGroup::findOrFail($groupId);
            foreach ($group->medias as $media) {
                $media->update(['group_id' => $mainGroup->id]);
            }
            $group->delete();
        }
        
        return back()->with('success', 'Grupos mesclados.');
    }


	public function groupOrphans(Request $request)
	{
		$limit = (int) $request->input('limit', 30);

		// Evita valores absurdos na UI
		$limit = max(1, min($limit, 100));

		Artisan::call('ai:group-orphans', [
			'--limit' => $limit,
		]);

		$output = Artisan::output();

		return back()->with('success', "Agrupamento executado (limit={$limit}).")
					 ->with('artisan_output', $output);
	}

	public function editGroup(\App\Models\ImageGroup $group)
	{
		// Chama o comando passando o ID do grupo para editar apenas ele
		Artisan::call('ai:edit-groups', [
			'--group_id' => $group->id,
			'--limit' => 1,
		]);

		return back()->with('success', "Processo de edição iniciado para o Grupo #{$group->id}.");
	}
	

	public function transferToItem(Request $request, ImageGroup $group)
	{
		$data = $request->validate([
			'codigo' => ['nullable', 'string', 'max:100'],
			'media_ids' => ['sometimes', 'array'],
			'media_ids.*' => ['integer'],
		]);

		$selectedIds = $data['media_ids'] ?? [];

		// CASO A: nenhuma imagem selecionada -> excluir tudo do grupo
		if (count($selectedIds) === 0) {
			$medias = ItemMedia::where('group_id', $group->id)->get();

			foreach ($medias as $media) {
				if ($media->url && Storage::disk('public')->exists($media->url)) {
					Storage::disk('public')->delete($media->url);
				}

				if ($media->thumbnail_url && Storage::disk('public')->exists($media->thumbnail_url)) {
					Storage::disk('public')->delete($media->thumbnail_url);
				}

				$media->delete();
			}

			$group->delete();

			if ($request->expectsJson()) {
				return response()->json([
					'success' => true,
					'deleted' => true,
					'message' => 'Nenhuma imagem selecionada. Grupo removido e arquivos excluídos.',
				]);
			}

			return back()->with('success', 'Nenhuma imagem selecionada. Grupo removido e arquivos excluídos.');
		}

		// CASO B: há imagens selecionadas -> exige código
		$request->validate([
			'codigo' => ['required', 'string', 'max:100'],
		]);

		$codigo = trim((string) $data['codigo']);

		$item = Item::query()->where('codigo', $codigo)->first();

		if (!$item) {
			if ($request->expectsJson()) {
				return response()->json([
					'success' => false,
					'message' => "Nenhum item encontrado com o código '{$codigo}'.",
					'errors' => [
						'codigo' => ["Nenhum item encontrado com o código '{$codigo}'."]
					]
				], 422);
			}

			return back()
				->withErrors(['codigo' => "Nenhum item encontrado com o código '{$codigo}'."])
				->withInput();
		}

		// Atualiza status
		$item->status = 'loja';

		// Atualiza descrição a partir do metadata
		$descricao = data_get($group->metadata, 'ai_catalog.payload.descricao');

		if (!$descricao) {
			$descricao = data_get($group->metadata, 'ai_catalog.payload.descricao_do_produto')
				?: data_get($group->metadata, 'ai_catalog.payload.descricao_anuncio')
				?: data_get($group->metadata, 'descricao');
		}

		if (is_string($descricao)) {
			$descricao = trim($descricao);

			if ($descricao !== '') {
				$item->descricao = $descricao;
			}
		}

		$item->save();

		// Move as selecionadas para o item respeitando a ordem enviada pelo frontend
		foreach ($selectedIds as $index => $mediaId) {
			ItemMedia::query()
				->where('id', $mediaId)
				->where('group_id', $group->id)
				->update([
					'item_id' => $item->id,
					'group_id' => null,
					'position' => $index + 1,
				]);
		}

		// Exclui as não selecionadas
		$toDelete = ItemMedia::query()
			->where('group_id', $group->id)
			->whereNotIn('id', $selectedIds)
			->get();

		foreach ($toDelete as $media) {
			if ($media->url && Storage::disk('public')->exists($media->url)) {
				Storage::disk('public')->delete($media->url);
			}

			if ($media->thumbnail_url && Storage::disk('public')->exists($media->thumbnail_url)) {
				Storage::disk('public')->delete($media->thumbnail_url);
			}

			$media->delete();
		}

		$group->delete();

		if ($request->expectsJson()) {
			return response()->json([
				'success' => true,
				'item_id' => $item->id,
				'codigo' => $item->codigo,
				'edit_url' => route('items.edit', $item),
				'message' => "Transferência concluída para o item {$item->codigo} e grupo removido.",
			]);
		}

		return back()->with('success', "Transferência concluída para o item {$item->codigo} e grupo removido.");
	}
	

	public function deleteOrphans(Request $request)
	{
		$data = $request->validate([
			'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
		]);

		$limit = (int) ($data['limit'] ?? 200);

		// CORREÇÃO CRÍTICA: 
		// Uma imagem só é órfã se NÃO tiver grupo E NÃO tiver item.
		$orphans = ItemMedia::query()
			->whereNull('group_id')
			->whereNull('item_id') // Esta linha impede de apagar fotos dos itens
			->orderByDesc('created_at')
			->limit($limit)
			->get();

		$deleted = 0;

		foreach ($orphans as $media) {
			// Verifica se o arquivo existe antes de tentar deletar
			if ($media->url && Storage::disk('public')->exists($media->url)) {
				Storage::disk('public')->delete($media->url);
			}
			
			if ($media->thumbnail_url && Storage::disk('public')->exists($media->thumbnail_url)) {
				Storage::disk('public')->delete($media->thumbnail_url);
			}

			$media->delete();
			$deleted++;
		}

		return back()->with('success', "Limpeza concluída. Foram removidas {$deleted} imagens que não pertenciam a nenhum grupo ou item.");
	}	
	

	public function buscarCodigo(Request $request)
	{
		Log::info("[OrphanDebug] Chamada buscarCodigo", ['codigo' => $request->codigo]);
		
		$request->validate([
			'codigo' => ['required', 'string']
		]);

		$codigo = trim($request->codigo);

		$item = Item::where('codigo', $codigo)->first();

		if (!$item) {
			return response()->json([
				'success' => false,
				'message' => 'Nenhum item encontrado para este código.'
			], 404);
		}

		return response()->json([
			'success' => true,
			'item' => [
				'id' => $item->id,
				'codigo' => $item->codigo,
				'nome_do_produto' => $item->nome_do_produto,
				'marca' => $item->marca ?? null,
				'cor' => $item->cor ?? null,
				'tamanho' => $item->tamanho ?? null,
				'estado' => $item->estado ?? null,
				'status' => $item->status ?? null,
			]
		]);
	}

	public function transferSelectedOrphans(Request $request)
	{
		Log::info("[OrphanDebug] Chamada transferSelectedOrphans RECIBIDA");

		$data = $request->validate([
			'codigo' => ['required', 'string', 'max:100'],
			'media_ids' => ['required', 'array'],
			'media_ids.*' => ['integer'],
		]);

		$codigo = trim((string) $data['codigo']);
		$selectedIds = $data['media_ids'];

		Log::info("[OrphanTransfer] Início da transferência", [
			'codigo' => $codigo,
			'num_fotos' => count($selectedIds),
			'media_ids' => $selectedIds
		]);

		$item = Item::query()->where('codigo', $codigo)->first();

		if (!$item) {
			Log::warning("[OrphanTransfer] Item não encontrado", ['codigo' => $codigo]);
			return response()->json([
				'success' => false,
				'message' => "Nenhum item encontrado com o código '{$codigo}'.",
				'errors' => [
					'codigo' => ["Nenhum item encontrado com o código '{$codigo}'."]
				]
			], 422);
		}

		Log::info("[OrphanTransfer] Item encontrado", ['item_id' => $item->id, 'status_atual' => $item->status]);

		// Atualiza o item para status loja se estiver disponível/null
		if (in_array($item->status, ['disponivel', null])) {
			$item->status = 'loja';
			$item->save();
			Log::info("[OrphanTransfer] Status do item atualizado para 'loja'");
		}

		// Determina a última posição das mídias já existentes no item
		$lastPosition = ItemMedia::where('item_id', $item->id)->max('position') ?? 0;
		Log::info("[OrphanTransfer] Posição inicial", ['last_position' => $lastPosition]);

		$updatedCount = 0;
		// Move as selecionadas para o item usando Eloquent (mais seguro que query builder direto)
		foreach ($selectedIds as $index => $mediaId) {
			$media = ItemMedia::find($mediaId);
			
			if ($media) {
				$media->item_id = $item->id;
				$media->group_id = null;
				$media->position = $lastPosition + $index + 1;
				
				if ($media->save()) {
					$updatedCount++;
				} else {
					Log::error("[OrphanTransfer] Falha ao salvar ItemMedia", ['id' => $mediaId]);
				}
			} else {
				Log::warning("[OrphanTransfer] ItemMedia não encontrado no banco", ['id' => $mediaId]);
			}
		}

		Log::info("[OrphanTransfer] Transferência finalizada", ['sucesso' => $updatedCount]);

		return response()->json([
			'success' => true,
			'item_id' => $item->id,
			'codigo' => $item->codigo,
			'edit_url' => route('items.edit', $item),
			'message' => "{$updatedCount} foto(s) associada(s) com sucesso ao item {$item->codigo}.",
		]);
	}

	public function deleteSelectedOrphans(Request $request)
	{
		$data = $request->validate([
			'media_ids' => ['required', 'array'],
			'media_ids.*' => ['integer'],
		]);

		$medias = ItemMedia::whereIn('id', $data['media_ids'])
			->whereNull('item_id')
			->whereNull('group_id')
			->get();

		$deleted = 0;
		foreach ($medias as $media) {
			if ($media->url && Storage::disk('public')->exists($media->url)) {
				Storage::disk('public')->delete($media->url);
			}
			if ($media->thumbnail_url && Storage::disk('public')->exists($media->thumbnail_url)) {
				Storage::disk('public')->delete($media->thumbnail_url);
			}
			$media->delete();
			$deleted++;
		}

		return response()->json([
			'success' => true,
			'message' => "{$deleted} imagens deletadas com sucesso."
		]);
	}
	
}
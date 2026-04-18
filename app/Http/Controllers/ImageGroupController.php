<?php

namespace App\Http\Controllers;

use App\Models\ImageGroup;
use App\Models\Item;
use App\Models\ItemMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


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

		$codigo = trim((string) $request->codigo);
		
		// Busca simples: tenta exato primeiro, depois LIKE
		$item = Item::where('codigo', $codigo)->first();
		
		if (!$item) {
			$item = Item::where('codigo', 'LIKE', $codigo)->first();
		}

		Log::info("[OrphanDebug] Resultado buscarCodigo", [
			'codigo_buscado' => $codigo,
			'encontrou' => $item ? true : false,
			'item_id' => $item ? $item->id : null,
			'sample_codigos' => Item::limit(5)->pluck('codigo')->toArray(),
		]);

		if (!$item) {
			return response()->json([
				'success' => false,
				'message' => "Código '{$codigo}' não encontrado."
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
			'status' => ['nullable', 'string', 'max:50'],
			'estado' => ['nullable', 'string', 'max:50'],
		]);

		$codigo = trim((string) $data['codigo']);
		$selectedIds = $data['media_ids'];

		Log::info("[OrphanTransfer] Início da transferência", [
			'codigo' => $codigo,
			'num_fotos' => count($selectedIds),
			'status_novo' => $data['status'] ?? 'não alterado',
			'estado_novo' => $data['estado'] ?? 'não alterado'
		]);

		// Busca simples e direta (mesma lógica do buscarCodigo)
		$item = Item::where('codigo', $codigo)->first();

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

		// Atualiza Status e Estado se enviados
		if (!empty($data['status'])) {
			$item->status = $data['status'];
		}
		if (!empty($data['estado'])) {
			$item->estado = $data['estado'];
		}
		
		if ($item->isDirty(['status', 'estado'])) {
			$item->save();
			Log::info("[OrphanTransfer] Item atualizado", ['id' => $item->id, 'status' => $item->status, 'estado' => $item->estado]);
		}

		// Determina a última posição das mídias já existentes no item
		$lastPosition = ItemMedia::where('item_id', $item->id)->max('position') ?? 0;
		Log::info("[OrphanTransfer] Posição inicial", ['last_position' => $lastPosition]);

		$updatedCount = 0;
		// Usamos DB::table direto para forçar a gravação e ignorar qualquer trava do Eloquent
		foreach ($selectedIds as $index => $mediaId) {
			$affected = DB::table('item_media')
				->where('id', $mediaId)
				->update([
					'item_id' => $item->id,
					'group_id' => null,
					'media_type' => 'image', // GARANTIA: Define como imagem para aparecer nos filtros
					'position' => $lastPosition + $index + 1,
					'is_cover' => ($lastPosition + $index === 0), // Define como capa se for a primeira do item
					'updated_at' => now(),
				]);
			
			if ($affected) {
				// VERIFICAÇÃO DE SEGURANÇA: Tenta ler o dado que acabou de ser gravado
				$verificacao = DB::table('item_media')->where('id', $mediaId)->where('item_id', $item->id)->first();
				
				if ($verificacao) {
					$updatedCount++;
					Log::info("[OrphanTransfer] Foto confirmada no banco", ['id' => $mediaId, 'item' => $item->id]);
				} else {
					Log::error("[OrphanTransfer] FALHA CRÍTICA: O banco retornou sucesso mas o dado não foi encontrado!", ['id' => $mediaId]);
				}
			} else {
				Log::error("[OrphanTransfer] Nenhuma linha afetada para o ID (Pode não existir mais)", ['id' => $mediaId]);
			}
		}

		Log::info("[OrphanTransfer] Transferência finalizada", ['sucesso' => $updatedCount]);

		// Se o item não tiver imagem principal e conseguimos vincular fotos, 
		// definimos a primeira delas como a imagem de capa do item.
		if ($updatedCount > 0 && empty($item->image)) {
			// Busca a imagem que acabamos de vincular para usar como capa principal do Item
			$first = DB::table('item_media')
				->where('item_id', $item->id)
				->where('media_type', 'image')
				->orderBy('position')
				->first();
			
			if ($first) {
				$item->image = $first->url;
				$item->save();
				Log::info("[OrphanTransfer] Item estava sem capa. URL definida: " . $item->image);
			}
		}

		// 'Toca' o item para limpar qualquer cache de visualização
		$item->touch();

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
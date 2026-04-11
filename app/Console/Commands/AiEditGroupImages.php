<?php

namespace App\Console\Commands;

use App\Models\ImageGroup;
use App\Models\ItemMedia;
use App\Services\GeminiImageEditService;
use Illuminate\Console\Command;

class AiEditGroupImages extends Command
{
    protected $signature = 'ai:edit-groups 
    {--limit=1} 
    {--group_id= : ID específico de um grupo para editar}
    {--dry-run}';
    protected $description = 'Edita imagens do grupo (exceto TAG) e salva no mesmo group_id';

    public function handle(GeminiImageEditService $editor)
    {
        $limit = (int)$this->option('limit');
        $dryRun = (bool)$this->option('dry-run');
				
		$groupId = $this->option('group_id');

		$query = ImageGroup::whereIn('status', ['pending', 'processing']);

		if ($groupId) {
			$query->where('id', $groupId);
		}

		$groups = $query->limit($limit)->get();
        //$groups = ImageGroup::whereIn('status', ['pending', 'processing'])->limit($limit)->get();

        foreach ($groups as $group) {
            $this->info("Processando Grupo #{$group->id}");
            $mediaIdTag = data_get($group->metadata, 'ai_catalog.media_id_tag');

            $medias = ItemMedia::where('group_id', $group->id)->get();

            foreach ($medias as $media) {
                if ($mediaIdTag && (int)$media->id === (int)$mediaIdTag) continue;
                if (data_get($media->metadata, 'edited')) continue;

                $this->line("  - Editando #{$media->id}...");
                if ($dryRun) continue;

                $r = $editor->editImage($media->url);

                if ($r['success']) {
                    ItemMedia::create([
                        'group_id' => $media->group_id,
                        'url' => $r['path'],
                        'media_type' => 'image',
                        'metadata' => ['edited' => true, 'original_media_id' => $media->id]
                    ]);
                    $this->info("    Salvo: {$r['path']}");
                } else {
                    $this->error("    Erro: {$r['error']}");
                }
            }
            if (!$dryRun) $group->update(['status' => 'completed']);
        }
    }
}
<?php

namespace App\Console\Commands;

use App\Models\ItemMedia;
use App\Models\ImageGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;

class GroupOrphanImages extends Command
{
    protected $signature = 'images:group {--method=hybrid} {--limit=500} {--threshold=15}';
    protected $description = 'Agrupa imagens órfãs por item usando metadados + similaridade visual';

    private $hasher;

    public function __construct()
    {
        parent::__construct();
        $this->hasher = new ImageHash(new DifferenceHash());
    }

    public function handle()
    {
        $method = $this->option('method');
        $limit = $this->option('limit');
        $threshold = (int) $this->option('threshold');
        
        $orphans = ItemMedia::whereNull('item_id')
            ->whereNull('group_id')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        if ($orphans->isEmpty()) {
            $this->info('Nenhuma imagem para agrupar.');
            return 0;
        }

        $this->info("Processando {$orphans->count()} imagens com método: {$method}");

        // Calcular hashes primeiro
        $this->info('Calculando hashes visuais...');
        $imagesWithHash = $this->calculateHashes($orphans);

        if (empty($imagesWithHash)) {
            $this->error('Nenhuma imagem válida encontrada.');
            return 1;
        }

        // Agrupar
        $groups = match($method) {
            'metadata' => $this->groupByMetadata($imagesWithHash),
            'similarity' => $this->groupBySimilarity($imagesWithHash, $threshold),
            'hybrid' => $this->groupHybrid($imagesWithHash, $threshold),
            default => $this->groupHybrid($imagesWithHash, $threshold),
        };

        $this->info("\nCriando grupos...");

        foreach ($groups as $groupImages) {
            $count = count($groupImages);
            $confidence = $this->calculateConfidence($groupImages, $method);
            
            if ($count >= 2 && $count <= 6) {
                $this->createGroup($groupImages, $method, $confidence);
            } elseif ($count > 6) {
                $selected = $this->selectBestImages($groupImages, 6);
                $this->createGroup($selected, $method, 0.7);
            } else {
                $this->warn("⚠️ Grupo com 1 imagem - necessita revisão manual");
            }
        }

        $this->info("\n✅ Agrupamento concluído.");
        return 0;
    }

    private function calculateHashes($images)
    {
        $result = [];
        
        foreach ($images as $image) {
            $path = Storage::disk('public')->path($image->url);
            
            if (!file_exists($path)) {
                $this->warn("Imagem não encontrada: {$image->url}");
                continue;
            }

            try {
                $hash = $this->hasher->hash($path);
                $result[] = [
                    'media' => $image,
                    'hash' => $hash, // Objeto Hash, não string
                    'timestamp' => strtotime($image->created_at),
                    'size' => filesize($path),
                ];
            } catch (\Exception $e) {
                $this->warn("Erro ao processar: {$image->url} - " . $e->getMessage());
            }
        }

        return $result;
    }

    private function groupByMetadata($images)
    {
        $groups = [];
        $currentGroup = [];
        $lastTimestamp = null;

        foreach ($images as $image) {
            if ($lastTimestamp && ($image['timestamp'] - $lastTimestamp) > 30) {
                if (!empty($currentGroup)) {
                    $groups[] = $currentGroup;
                }
                $currentGroup = [];
            }
            
            $currentGroup[] = $image;
            $lastTimestamp = $image['timestamp'];
        }

        if (!empty($currentGroup)) {
            $groups[] = $currentGroup;
        }

        return $groups;
    }

    private function groupBySimilarity($images, $threshold)
    {
        $groups = [];
        $used = array_fill(0, count($images), false);

        foreach ($images as $i => $image1) {
            if ($used[$i]) continue;

            $group = [$image1];
            $used[$i] = true;

            foreach ($images as $j => $image2) {
                if ($used[$j]) continue;

                $distance = $this->hammingDistance($image1['hash'], $image2['hash']);
                
                if ($distance < $threshold) {
                    $group[] = $image2;
                    $used[$j] = true;
                }
            }

            $groups[] = $group;
        }

        return $groups;
    }

    private function groupHybrid($images, $threshold)
    {
        // Primeiro: agrupar por timestamp (mais permissivo - 60s)
        $timeGroups = $this->groupByTimeWindow($images, 60);

        // Segundo: refinar grupos com similaridade visual
        $finalGroups = [];

        foreach ($timeGroups as $group) {
            if (count($group) <= 1) {
                $finalGroups[] = $group;
                continue;
            }

            // Verificar similaridade dentro do grupo temporal
            $subGroups = $this->splitBySimilarity($group, $threshold);
            
            foreach ($subGroups as $subGroup) {
                $finalGroups[] = $subGroup;
            }
        }

        return $finalGroups;
    }

    private function groupByTimeWindow($images, $seconds)
    {
        $groups = [];
        $currentGroup = [];
        $lastTimestamp = null;

        foreach ($images as $image) {
            if ($lastTimestamp && ($image['timestamp'] - $lastTimestamp) > $seconds) {
                if (!empty($currentGroup)) {
                    $groups[] = $currentGroup;
                }
                $currentGroup = [];
            }
            
            $currentGroup[] = $image;
            $lastTimestamp = $image['timestamp'];
        }

        if (!empty($currentGroup)) {
            $groups[] = $currentGroup;
        }

        return $groups;
    }

	private function splitBySimilarity($group, $threshold)
	{
		if (count($group) <= 1) {
			return [$group];
		}

		// Calcular distâncias entre todas as imagens do grupo
		$distances = [];
		for ($i = 0; $i < count($group); $i++) {
			for ($j = $i + 1; $j < count($group); $j++) {
				$dist = $this->hammingDistance($group[$i]['hash'], $group[$j]['hash']);
				$distances[] = $dist;
			}
		}

		// Threshold adaptativo: média + desvio padrão
		$avgDistance = count($distances) > 0 ? array_sum($distances) / count($distances) : 0;
		$adaptiveThreshold = max($threshold, $avgDistance * 1.5);

		$this->info("   Distância média: " . round($avgDistance, 1) . ", threshold adaptativo: " . round($adaptiveThreshold, 1));

		$subGroups = [];
		$used = array_fill(0, count($group), false);

		foreach ($group as $i => $image1) {
			if ($used[$i]) continue;

			$subGroup = [$image1];
			$used[$i] = true;

			foreach ($group as $j => $image2) {
				if ($used[$j] || $i === $j) continue;

				$distance = $this->hammingDistance($image1['hash'], $image2['hash']);
				
				// Usar threshold adaptativo OU threshold fixo (o menor)
				$effectiveThreshold = min($adaptiveThreshold, $threshold * 2);
				
				if ($distance < $effectiveThreshold) {
					$subGroup[] = $image2;
					$used[$j] = true;
				}
			}

			$subGroups[] = $subGroup;
		}

		return $subGroups;
	}

    private function hammingDistance($hash1, $hash2)
    {
        // Usar método da biblioteca para calcular distância
        // O objeto Hash tem método para comparar com outro Hash
        return $hash1->distance($hash2);
    }

    private function calculateConfidence($group, $method)
    {
        $count = count($group);
        
        // Base: confiança por quantidade de imagens
        $confidence = match(true) {
            $count >= 4 && $count <= 6 => 0.95,
            $count >= 2 && $count <= 3 => 0.85,
            default => 0.5,
        };

        // Ajustar por método
        if ($method === 'hybrid') {
            // Verificar consistência visual do grupo
            $avgDistance = $this->averageGroupDistance($group);
            if ($avgDistance < 10) {
                $confidence = min(1.0, $confidence + 0.1);
            }
        }

        return round($confidence, 2);
    }

    private function averageGroupDistance($group)
    {
        if (count($group) < 2) return 0;

        $distances = [];
        for ($i = 0; $i < count($group); $i++) {
            for ($j = $i + 1; $j < count($group); $j++) {
                $distances[] = $this->hammingDistance(
                    $group[$i]['hash'],
                    $group[$j]['hash']
                );
            }
        }

        return count($distances) > 0 ? array_sum($distances) / count($distances) : 0;
    }

	private function createGroup($images, $method, $confidence)
	{
		$group = ImageGroup::create([
			'grouping_method' => $method,
			'status' => 'pending',
			'confidence_score' => $confidence,
		]);

		foreach ($images as $image) {
			$media = $image['media'];
			$media->group_id = $group->id;
			$media->save();
		}

		$count = count($images);
		$avgDistance = $this->averageGroupDistance($images);
		$this->info("✅ Grupo {$group->id}: {$count} imagens (confiança: {$confidence}, distância média: " . round($avgDistance, 1) . ")");
	}

    private function selectBestImages($images, $count)
    {
        usort($images, function ($a, $b) {
            return $b['size'] <=> $a['size'];
        });

        return array_slice($images, 0, $count);
    }
}
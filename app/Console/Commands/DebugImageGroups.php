<?php

namespace App\Console\Commands;

use App\Models\ItemMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;

class DebugImageGroups extends Command
{
    protected $signature = 'images:debug';
    protected $description = 'Debug distâncias entre imagens órfãs';

    public function handle()
    {
        $orphans = ItemMedia::whereNull('group_id')
            ->orderBy('created_at')
            ->get();

        if ($orphans->isEmpty()) {
            $this->info('Nenhuma imagem órfã.');
            return 0;
        }

        $hasher = new ImageHash(new DifferenceHash());
        $images = [];

        foreach ($orphans as $media) {
            $path = Storage::disk('public')->path($media->url);
            if (file_exists($path)) {
                $images[] = [
                    'id' => $media->id,
                    'name' => $media->url,
                    'hash' => $hasher->hash($path),
                    'time' => strtotime($media->created_at),
                ];
            }
        }

        $this->info("\n📊 Matriz de Distâncias (Hamming):\n");

        // Mostrar distâncias entre todas as imagens
        for ($i = 0; $i < count($images); $i++) {
            for ($j = $i + 1; $j < count($images); $j++) {
                // Usar o método da biblioteca para comparar
                $distance = $images[$i]['hash']->distance($images[$j]['hash']);
                $timeDiff = abs($images[$j]['time'] - $images[$i]['time']);
                
                $this->line("{$images[$i]['id']} ↔ {$images[$j]['id']}: dist={$distance}, tempo={$timeDiff}s");
            }
        }

        // Mostrar estatísticas
        $this->info("\n📊 Estatísticas:");
        
        $distances = [];
        for ($i = 0; $i < count($images); $i++) {
            for ($j = $i + 1; $j < count($images); $j++) {
                $distances[] = $images[$i]['hash']->distance($images[$j]['hash']);
            }
        }

        if (!empty($distances)) {
            $min = min($distances);
            $max = max($distances);
            $avg = array_sum($distances) / count($distances);
            
            $this->line("Distância mínima: {$min}");
            $this->line("Distância máxima: {$max}");
            $this->line("Distância média: " . round($avg, 1));
            
            // Sugerir threshold
            $suggested = ceil($avg * 0.8);
            $this->info("\n💡 Threshold sugerido: {$suggested}");
        }

        return 0;
    }
}
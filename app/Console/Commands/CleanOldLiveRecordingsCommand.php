<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Live;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanOldLiveRecordingsCommand extends Command
{
    protected $signature = 'lives:clean-old-recordings 
                            {--days=15 : Idade minima em dias para limpar gravacoes brutas de lives} 
                            {--clean-duplicate-cuts : Limpar tambem cortes nao referenciados na tabela live_items} 
                            {--clean-sold-cuts : Limpar cortes de video de itens que ja foram vendidos}
                            {--dry-run : Apenas simular a limpeza sem remover arquivos ou alterar banco}';

    protected $description = 'Limpa gravacoes brutas de lives antigas (> 15 dias), remove arquivos duplicados ou orfaos e cortes de pecas ja vendidas para liberar espaco em disco.';

    public function handle()
    {
        $days = (int) ($this->option('days') ?? 15);
        $cleanDuplicateCuts = $this->option('clean-duplicate-cuts');
        $cleanSoldCuts = $this->option('clean-sold-cuts');
        $dryRun = (bool) $this->option('dry-run');

        $cutoffDate = Carbon::now()->subDays($days)->startOfDay();
        $this->info("Iniciando limpeza de gravacoes anteriores a: {$cutoffDate->format('Y-m-d')}");
        if ($dryRun) {
            $this->warn("MODO SIMULACAO (DRY RUN) ATIVO - Nenhum arquivo sera excluido.");
        }

        $totalBytesFreed = 0;
        $totalFilesDeleted = 0;
        $processedPaths = [];

        // 1. Gravacoes brutas registradas em lives anteriores ao cutoff
        $lives = Live::whereNotNull('recording_path')
            ->where('data', '<=', $cutoffDate)
            ->get();

        $this->info("Encontradas {$lives->count()} lives com gravacoes brutas a serem limpas.");

        foreach ($lives as $live) {
            $recordingPath = $live->recording_path;
            $fullPath = Storage::disk('public')->path($recordingPath);

            $fileSize = 0;
            if (File::exists($fullPath)) {
                $fileSize = File::size($fullPath);
                $this->line("Live #{$live->id} ({$live->data}): " . $this->formatBytes($fileSize) . " - {$recordingPath}");
                if (!$dryRun) {
                    File::delete($fullPath);
                }
                $totalBytesFreed += $fileSize;
                $totalFilesDeleted++;
                $processedPaths[$fullPath] = true;
            } else {
                $this->comment("Live #{$live->id} ({$live->data}): Arquivo fisico ja nao existia ({$recordingPath})");
            }

            if (!$dryRun) {
                $live->update([
                    'recording_path' => null,
                    'recording_filename' => null,
                ]);
            }
        }

        // 2. Arquivos orfaos, duplicados ou incompletos (.part) em storage/app/public/live_recordings
        $recordingsDir = Storage::disk('public')->path('live_recordings');
        if (File::isDirectory($recordingsDir)) {
            // Obter nomes de arquivos atualmente ativos no banco
            $activeRecordingFilenames = Live::whereNotNull('recording_path')
                ->pluck('recording_path')
                ->map(fn($p) => basename($p))
                ->toArray();

            $files = File::files($recordingsDir);
            foreach ($files as $file) {
                $pathname = $file->getPathname();
                if (isset($processedPaths[$pathname])) {
                    continue;
                }

                $filename = $file->getFilename();
                $extension = strtolower($file->getExtension());
                $mtime = Carbon::createFromTimestamp($file->getMTime());

                $isPartFile = str_ends_with($filename, '.part') || $extension === 'part';
                $isOlderThanCutoff = $mtime->lte($cutoffDate);
                $isOrphan = !in_array($filename, $activeRecordingFilenames) && $mtime->lt(Carbon::now()->subHours(12));

                if ($isPartFile || $isOlderThanCutoff || $isOrphan) {
                    $size = $file->getSize();
                    $reason = $isPartFile ? 'Arquivo .part temporario' : ($isOlderThanCutoff ? "Mais antigo que {$days} dias" : 'Arquivo nao referenciado no banco');
                    $this->line("Removendo em live_recordings: {$filename} (" . $this->formatBytes($size) . ") - Motivo: {$reason}");
                    if (!$dryRun) {
                        File::delete($pathname);
                    }
                    $totalBytesFreed += $size;
                    $totalFilesDeleted++;
                    $processedPaths[$pathname] = true;
                }
            }
        }

        // 3. Cortes duplicados / nao vinculados em storage/app/public/live_cuts (se solicitado)
        if ($cleanDuplicateCuts) {
            $cutsDir = Storage::disk('public')->path('live_cuts');
            if (File::isDirectory($cutsDir)) {
                $this->info("Verificando cortes orfaos ou duplicados em {$cutsDir}...");

                // Obter caminhos relativos normalizados registrados em live_items
                $activeCutPaths = DB::table('live_items')
                    ->whereNotNull('video_cut_path')
                    ->pluck('video_cut_path')
                    ->map(fn($p) => str_replace('\\', '/', trim($p, '/\\ ')))
                    ->flip()
                    ->toArray();

                $allCutFiles = File::allFiles($cutsDir);
                $this->info("Total de arquivos em live_cuts: " . count($allCutFiles));

                $orphanedCutsCount = 0;
                $orphanedCutsBytes = 0;

                foreach ($allCutFiles as $cutFile) {
                    if (strtolower($cutFile->getExtension()) !== 'mp4') {
                        continue;
                    }

                    $relative = 'live_cuts/' . str_replace('\\', '/', $cutFile->getRelativePathname());
                    $mtime = Carbon::createFromTimestamp($cutFile->getMTime());

                    // Ignorar arquivos criados nas ultimas 24 horas (podem ser processos em andamento)
                    if ($mtime->gt(Carbon::now()->subHours(24))) {
                        continue;
                    }

                    if (!isset($activeCutPaths[$relative])) {
                        $size = $cutFile->getSize();
                        if (!$dryRun) {
                            File::delete($cutFile->getPathname());
                        }
                        $orphanedCutsCount++;
                        $orphanedCutsBytes += $size;
                    }
                }

                $this->info("Cortes orfaos/duplicados removidos: {$orphanedCutsCount} (" . $this->formatBytes($orphanedCutsBytes) . ")");
                $totalBytesFreed += $orphanedCutsBytes;
                $totalFilesDeleted += $orphanedCutsCount;
            }
        }

        // 4. Cortes de itens ja vendidos (se solicitado)
        if ($cleanSoldCuts) {
            $this->info("Verificando cortes de itens vendidos para limpeza...");

            $soldItems = DB::table('live_items')
                ->join('lives', 'live_items.live_id', '=', 'lives.id')
                ->leftJoin('items', 'live_items.item_id', '=', 'items.id')
                ->whereNotNull('live_items.video_cut_path')
                ->where('live_items.video_cut_path', '!=', '')
                ->where(function ($q) {
                    // Nao mexer em live que esteja com status ativo no momento
                    $q->where('lives.ativo', 0)
                      ->orWhereNull('lives.ativo');
                })
                ->where(function ($q) {
                    $q->whereIn('items.status', ['vendido', 'sacolinha'])
                      ->orWhereNotNull('live_items.user_id')
                      ->orWhere(function ($sub) {
                          $sub->whereNotNull('live_items.buyer_name')
                              ->where('live_items.buyer_name', '!=', '')
                              ->where('live_items.buyer_name', '!=', '0');
                      })
                      ->orWhere(function ($sub) {
                          $sub->whereNotNull('live_items.buyer_username')
                              ->where('live_items.buyer_username', '!=', '');
                      });
                })
                ->select(
                    'live_items.id',
                    'live_items.video_cut_path',
                    'live_items.live_id',
                    'live_items.codigo_live',
                    'items.status as item_status',
                    'live_items.buyer_name',
                    'live_items.buyer_username'
                )
                ->get();

            $this->info("Encontrados {$soldItems->count()} itens com cortes vinculados a pecas vendidas.");

            $soldCutsDeleted = 0;
            $soldCutsBytes = 0;

            foreach ($soldItems as $item) {
                $relative = str_replace('\\', '/', trim($item->video_cut_path, '/\\ '));
                $fullPath = Storage::disk('public')->path($relative);

                $fileSize = 0;
                if (File::exists($fullPath)) {
                    $fileSize = File::size($fullPath);
                    if (!$dryRun) {
                        File::delete($fullPath);
                    }
                    $soldCutsBytes += $fileSize;
                    $soldCutsDeleted++;
                    $processedPaths[$fullPath] = true;
                }

                if (!$dryRun) {
                    DB::table('live_items')
                        ->where('id', $item->id)
                        ->update([
                            'video_cut_path' => null,
                            'video_cut_url' => null,
                            'video_cut_filename' => null,
                            'video_cut_status' => 'sold_cleaned',
                            'updated_at' => Carbon::now(),
                        ]);
                }
            }

            $this->info("Cortes de itens vendidos removidos: {$soldCutsDeleted} (" . $this->formatBytes($soldCutsBytes) . ")");
            $totalBytesFreed += $soldCutsBytes;
            $totalFilesDeleted += $soldCutsDeleted;
        }

        $this->info("Limpeza finalizada com sucesso!");
        $this->info("Total de arquivos removidos: {$totalFilesDeleted}");
        $this->info("Espaco total liberado: " . $this->formatBytes($totalBytesFreed));

        return Command::SUCCESS;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

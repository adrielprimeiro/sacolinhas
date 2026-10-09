<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Live;
use App\Models\LiveItem;
use Illuminate\Support\Facades\DB;

class DebugCutsLiveCommand extends Command
{
    protected $signature = 'app:debug-cuts-live {live_id=349}';
    protected $description = 'Debug cuts for live items';

    public function handle()
    {
        $liveId = $this->argument('live_id');
        $live = Live::find($liveId);
        if (!$live) {
            $this->error("Live {$liveId} não encontrada.");
            return Command::FAILURE;
        }

        $this->info("Live #{$live->id} - Recording path: " . $live->recording_path);

        $items = LiveItem::where('live_id', $liveId)
            ->orderByRaw('CAST(codigo_live AS UNSIGNED) ASC')
            ->get();

        $this->info("Total items: " . $items->count());

        foreach ($items as $item) {
            $code = $item->codigo_live ?: $item->id;
            $start = $item->cut_start_sec;
            $end = $item->cut_end_sec;
            $dur = ($start !== null && $end !== null) ? round($end - $start, 1) : null;
            $status = $item->video_cut_path ? 'PRONTO' : 'PENDENTE';

            if ($code >= 50 && $code <= 65) {
                $this->line("Peça #{$code} [{$status}] -> Start: {$start}s, End: {$end}s, Dur: {$dur}s | Video: {$item->video_cut_path}");
            }
        }

        // Testa o comando FFmpeg na peça 59
        $targetItem = LiveItem::where('live_id', $liveId)->where('codigo_live', '59')->first();
        if ($targetItem) {
            $this->warn("\n--- TESTANDO CORTE NA PEÇA #59 ---");
            $start = (float) $targetItem->cut_start_sec;
            $end = (float) $targetItem->cut_end_sec;
            $duration = max(1, round($end - $start, 2));

            $controller = new \App\Http\Controllers\Admin\LiveVideoCutsController();
            $inputPath = $controller->getLocalVideoPath($live);
            $this->line("Input path: {$inputPath}");
            $this->line("Start: {$start}s | End: {$end}s | Duration: {$duration}s");

            $outputDir = storage_path('app/public/live_cuts/live_' . $liveId);
            @mkdir($outputDir, 0777, true);
            $outputPath = $outputDir . '/test_cut_59.mp4';

            $cmd = sprintf(
                'ffmpeg -ss %s -i %s -t %s -c:v libx264 -preset veryfast -crf 22 -c:a aac -b:a 128k -avoid_negative_ts make_zero -movflags +faststart -y %s 2>&1',
                escapeshellarg($start),
                escapeshellarg($inputPath),
                escapeshellarg($duration),
                escapeshellarg($outputPath)
            );
            $this->line("Executando: {$cmd}");
            exec($cmd, $out, $ret);
            $this->line("Retorno: {$ret}");
            $this->line("Output: " . implode("\n", array_slice($out, -15)));
            $this->line("Tamanho do arquivo gerado: " . (file_exists($outputPath) ? filesize($outputPath) : 'NÃO EXISTE'));
            @unlink($outputPath);
        }

        return Command::SUCCESS;
    }
}

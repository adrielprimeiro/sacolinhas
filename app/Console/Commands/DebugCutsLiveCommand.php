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

        $items = DB::table('live_items')
            ->where('live_id', $liveId)
            ->orderByRaw('CAST(codigo_live AS UNSIGNED) ASC')
            ->get();

        $sentences = json_decode($live->transcription_raw, true) ?: [];

        $controller = new \App\Http\Controllers\Admin\LiveVideoCutsController();
        $class = new \ReflectionClass($controller);
        $method = $class->getMethod('performChronologicalHeuristicDetection');
        $method->setAccessible(true);

        $results = $method->invoke($controller, $live, $sentences, $items, 0.0);
        $this->info("\n--- RESULTADO DA DETECÇÃO PROGRESSIVA (Total: " . count($results) . ") ---");

        // Testa o comando FFmpeg na peça 1 com legendas
        $targetItem = DB::table('live_items')->where('live_id', $liveId)->where('codigo_live', '1')->first();
        if ($targetItem) {
            $this->warn("\n--- TESTANDO CORTE COM LEGENDA NA PEÇA #1 ---");
            $start = (float) $targetItem->cut_start_sec;
            $end = (float) $targetItem->cut_end_sec;
            $duration = max(1, round($end - $start, 2));

            $inputPath = $controller->getLocalVideoPath($live);
            $this->line("Input path: {$inputPath}");
            $this->line("Start: {$start}s | End: {$end}s | Duration: {$duration}s");

            $outputDir = storage_path('app/public/live_cuts/live_' . $liveId);
            @mkdir($outputDir, 0777, true);
            $outputPath = $outputDir . "/test_cut_1_sub.mp4";

            $methodSub = $class->getMethod('generateSubtitleFileForClip');
            $methodSub->setAccessible(true);
            $srtFile = $methodSub->invoke($controller, $live, $targetItem, $start, $duration, $outputDir, null);

            $this->line("SRT File: " . ($srtFile ?: 'NULO'));
            if ($srtFile && file_exists($srtFile)) {
                $this->line("Conteúdo do SRT:\n" . file_get_contents($srtFile));

                $escapedSrt = str_replace('\\', '/', $srtFile);
                $escapedSrt = str_replace(':', '\\:', $escapedSrt);

                // Testando o filtro
                $vf = sprintf(
                    "subtitles='%s':force_style='FontName=Arial,FontSize=12,Bold=1,PrimaryColour=&H00FFFFFF,OutlineColour=&H00000000,BorderStyle=1,Outline=2.2,Shadow=1.0,MarginV=26,Alignment=2'",
                    $escapedSrt
                );

                $cmd = sprintf(
                    'ffmpeg -ss %s -i %s -t %s -vf %s -c:v libx264 -preset veryfast -crf 22 -c:a aac -b:a 128k -avoid_negative_ts make_zero -movflags +faststart -y %s 2>&1',
                    escapeshellarg($start),
                    escapeshellarg($inputPath),
                    escapeshellarg($duration),
                    escapeshellarg($vf),
                    escapeshellarg($outputPath)
                );

                $this->line("Executando: {$cmd}");
                exec($cmd, $out, $ret);
                $this->line("Retorno: {$ret}");
                $this->line("Output completo:\n" . implode("\n", $out));
                $this->line("Tamanho gerado: " . (file_exists($outputPath) ? filesize($outputPath) . ' bytes' : 'NÃO GEROU'));
                @unlink($outputPath);
                @unlink($srtFile);
            }
        }



        return Command::SUCCESS;
    }
}

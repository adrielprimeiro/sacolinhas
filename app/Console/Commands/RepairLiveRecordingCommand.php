<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Live;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class RepairLiveRecordingCommand extends Command
{
    protected $signature = 'app:repair-live-recording {live_id : ID da live a ser reparada} {--force : Força o reparo mesmo se não detectar falhas}';
    protected $description = 'Verifica e repara falhas de sinal, congelamentos e dessincronia VFR na gravação da live via FFmpeg.';

    public function handle()
    {
        $liveId = (int) $this->argument('live_id');
        $force = (bool) $this->option('force');

        $live = Live::find($liveId);
        if (!$live || empty($live->recording_path)) {
            $this->error("Live #{$liveId} não encontrada ou sem vídeo de gravação.");
            return Command::FAILURE;
        }

        $inputPath = Storage::disk('public')->path($live->recording_path);
        if (!file_exists($inputPath) && file_exists($live->recording_path)) {
            $inputPath = $live->recording_path;
        }

        if (!file_exists($inputPath)) {
            $this->error("Arquivo de vídeo não encontrado em: {$inputPath}");
            return Command::FAILURE;
        }

        $this->info("Analisando integridade e sincronismo de sinal da Live #{$liveId}...");

        // 1. Escaneamento rápido de pacotes com ffprobe
        $probeCmd = sprintf(
            'ffprobe -v error -select_streams v:0 -show_entries packet=pts_time -of csv=p=0 %s',
            escapeshellarg($inputPath)
        );

        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $proc = proc_open($probeCmd, $descriptors, $pipes);

        $gaps = [];
        $lastPts = null;

        if (is_resource($proc)) {
            while (!feof($pipes[1])) {
                $line = trim(fgets($pipes[1]));
                if ($line === '' || !is_numeric($line)) continue;
                $pts = (float) $line;
                if ($lastPts !== null) {
                    $diff = $pts - $lastPts;
                    if ($diff > 0.8) {
                        $gaps[] = [
                            'start' => $lastPts,
                            'end' => $pts,
                            'duration' => $diff
                        ];
                    }
                }
                $lastPts = $pts;
            }
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($proc);
        }

        $gapCount = count($gaps);
        if ($gapCount > 0) {
            $this->warn("⚠️ Detectadas {$gapCount} interrupções de vídeo (congelamentos de sinal):");
            foreach ($gaps as $g) {
                $m = (int) ($g['start'] / 60);
                $s = (int) fmod($g['start'], 60);
                $this->line(sprintf("  - Aos %02d:%02d (%0.1fs): vídeo congelou por %0.2fs", $m, $s, $g['start'], $g['duration']));
            }
        } else {
            $this->info("✅ Nenhum congelamento de sinal grave detectado na gravação.");
            if (!$force) {
                $this->info("O vídeo já está íntegro. Use --force se desejar recompilar mesmo assim.");
                return Command::SUCCESS;
            }
        }

        $this->info("Iniciando normalização CFR (30 FPS constantes + recomposição labial)...");

        $dir = dirname($inputPath);
        $tempOutput = $dir . DIRECTORY_SEPARATOR . 'live_' . $liveId . '_repair_' . time() . '.mp4';

        $ffmpegCmd = sprintf(
            'ffmpeg -i %s -vf "fps=fps=30" -af "aresample=async=1000:first_pts=0" -c:v libx264 -preset veryfast -crf 22 -c:a aac -b:a 128k -movflags +faststart -y %s 2>&1',
            escapeshellarg($inputPath),
            escapeshellarg($tempOutput)
        );

        $output = [];
        $returnCode = 1;
        exec($ffmpegCmd, $output, $returnCode);

        if ($returnCode === 0 && file_exists($tempOutput) && filesize($tempOutput) > 10000000) {
            $backupPath = $inputPath . '.bak';
            @copy($inputPath, $backupPath);
            @rename($tempOutput, $inputPath);

            $live->touch();
            $this->info("🎉 Gravação da Live #{$liveId} reparada e sincronizada com sucesso!");
            Log::info("[LiveRepair] Live #{$liveId} reparada com sucesso. ({$gapCount} gaps normalizados)");
            return Command::SUCCESS;
        } else {
            $this->error("Falha ao reparar vídeo da Live #{$liveId} via FFmpeg.");
            Log::error("[LiveRepair] Falha no reparo da Live #{$liveId}: " . implode("\n", array_slice($output, -10)));
            if (file_exists($tempOutput)) @unlink($tempOutput);
            return Command::FAILURE;
        }
    }
}

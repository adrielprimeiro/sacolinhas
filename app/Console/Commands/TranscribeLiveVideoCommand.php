<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Live;
use App\Services\LiveVideoAutoProcessorService;
use App\Http\Controllers\Admin\LiveVideoCutsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TranscribeLiveVideoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:transcribe-live-video {--live_id= : ID da live a transcrever} {--auto_detect=1 : Se deve auto-detectar minutagem após transcrição}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transcreve o áudio de lives longas em segundo plano com chunking e Whisper.';

    /**
     * Execute the console command.
     */
    public function handle(LiveVideoAutoProcessorService $processor)
    {
        $liveId = (int) $this->option('live_id');
        $autoDetect = (bool) $this->option('auto_detect');

        $live = Live::find($liveId);
        if (!$live) {
            $this->error("Live #{$liveId} não encontrada.");
            Cache::put("live_transcription_status_{$liveId}", [
                'status' => 'error',
                'progress' => 0,
                'message' => 'Live não encontrada no banco de dados.'
            ], 3600);
            return Command::FAILURE;
        }

        $this->info("Iniciando transcrição assíncrona para Live #{$liveId}...");

        $destFolder = storage_path('app/public/live_recordings');
        $videoPath = null;
        if (!empty($live->recording_path)) {
            $candidates = [
                $live->recording_path,
                storage_path('app/public/' . ltrim($live->recording_path, '/')),
                \Illuminate\Support\Facades\Storage::disk('public')->path($live->recording_path)
            ];
            foreach ($candidates as $c) {
                if (file_exists($c) && filesize($c) > 100000) {
                    $videoPath = $c;
                    break;
                }
            }
        }

        if (!$videoPath) {
            $msg = 'Arquivo de vídeo não encontrado no servidor.';
            $this->error($msg);
            Cache::put("live_transcription_status_{$liveId}", [
                'status' => 'error',
                'progress' => 0,
                'message' => $msg
            ], 3600);
            return Command::FAILURE;
        }

        Cache::put("live_transcription_status_{$liveId}", [
            'status' => 'processing',
            'progress' => 10,
            'message' => 'Extraindo e segmentando áudio da transmissão (FFmpeg)...'
        ], 3600);

        try {
            $ok = $processor->transcribeVideoAudio($live, $videoPath, function ($pct, $msg) use ($liveId) {
                Cache::put("live_transcription_status_{$liveId}", [
                    'status' => 'processing',
                    'progress' => $pct,
                    'message' => $msg
                ], 3600);
            });

            if (!$ok) {
                Cache::put("live_transcription_status_{$liveId}", [
                    'status' => 'error',
                    'progress' => 0,
                    'message' => 'Falha ao processar áudio e transcrição Whisper.'
                ], 3600);
                return Command::FAILURE;
            }

            if ($autoDetect) {
                Cache::put("live_transcription_status_{$liveId}", [
                    'status' => 'processing',
                    'progress' => 90,
                    'message' => 'Detectando minutagem e vinculando códigos das peças...'
                ], 3600);

                $controller = new LiveVideoCutsController();
                $controller->autoDetectTimestamps(new Request(), $live->id);
            }

            $live->refresh();
            $sentences = json_decode($live->transcription_raw, true) ?: [];

            Cache::put("live_transcription_status_{$liveId}", [
                'status' => 'completed',
                'progress' => 100,
                'message' => "Transcrição e minutagens concluídas com sucesso! (" . count($sentences) . " trechos de fala transcritos)",
                'sentences_count' => count($sentences)
            ], 3600);

            $this->info("✅ Transcrição finalizada com sucesso!");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            Log::error("[TranscribeCommand] Erro: " . $e->getMessage());
            Cache::put("live_transcription_status_{$liveId}", [
                'status' => 'error',
                'progress' => 0,
                'message' => 'Erro durante a transcrição: ' . $e->getMessage()
            ], 3600);
            return Command::FAILURE;
        }
    }
}

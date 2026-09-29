<?php

namespace App\Services;

use App\Models\Live;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Admin\LiveVideoCutsController;
use Illuminate\Http\Request;

class LiveVideoAutoProcessorService
{
    /**
     * Verifica o Instagram por um novo vídeo da live, baixa, transcreve e gera os cortes
     * 
     * @param int|null $liveId
     * @param string $instagramHandle
     * @return array
     */
    public function processLiveVideo($liveId = null, $instagramHandle = 'de_minha_mania')
    {
        // 1. Obter a live
        if ($liveId) {
            $live = Live::find($liveId);
        } else {
            // Busca a live mais recente (últimas 48h) que tenha itens e ainda esteja pendente de processamento
            $live = Live::where('created_at', '>=', now()->subHours(48))
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('live_items')
                          ->whereColumn('live_items.live_id', 'lives.id');
                })
                ->where(function($q) {
                    $q->whereNull('recording_path')
                      ->orWhere('transcription_status', '!=', 'completed')
                      ->orWhereNotExists(function($subQuery) {
                          $subQuery->select(DB::raw(1))
                                   ->from('live_items')
                                   ->whereColumn('live_items.live_id', 'lives.id')
                                   ->whereNotNull('video_cut_path');
                      });
                })
                ->orderBy('id', 'desc')
                ->first();

            // Se não encontrou nenhuma das últimas 48h pendente, verifica se há live ativa
            if (!$live) {
                $live = Live::where('ativo', true)->orderBy('id', 'desc')->first();
            }
        }

        if (!$live) {
            Log::info("[AutoProcessor] Nenhuma live recente pendente de processamento encontrada.");
            return ['success' => false, 'message' => 'Nenhuma live encontrada para processar.'];
        }


        Log::info("[AutoProcessor] Iniciando verificação para Live #{$live->id} no Instagram @{$instagramHandle}");

        $destFolder = storage_path('app/public/live_recordings');
        if (!is_dir($destFolder)) {
            mkdir($destFolder, 0775, true);
        }

        // 2. Verificar se já tem vídeo local
        $videoPath = null;
        if (!empty($live->recording_path)) {
            $existingPath = Storage::disk('public')->path($live->recording_path);
            if (file_exists($existingPath) && filesize($existingPath) > 500000) {
                $videoPath = $existingPath;
            }
        }

        // 3. Buscar vídeo mais recente do Instagram via yt-dlp
        if (!$videoPath) {
            Log::info("[AutoProcessor] Buscando último vídeo do perfil @{$instagramHandle}...");
            $profileUrl = "https://www.instagram.com/{$instagramHandle}/reels/";
            $escapedProfile = escapeshellarg($profileUrl);

            // yt-dlp flat-playlist para obter URLs recentes
            $cmd = "yt-dlp --dump-json --flat-playlist --playlist-end 3 {$escapedProfile} 2>&1";
            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            $latestVideoUrl = null;
            foreach ($output as $line) {
                $json = json_decode($line, true);
                if (!empty($json['url'])) {
                    $latestVideoUrl = $json['url'];
                    break;
                } elseif (!empty($json['webpage_url'])) {
                    $latestVideoUrl = $json['webpage_url'];
                    break;
                }
            }

            if (!$latestVideoUrl) {
                return ['success' => false, 'message' => "Nenhum vídeo novo detectado no perfil @{$instagramHandle}."];
            }

            Log::info("[AutoProcessor] Novo vídeo detectado: {$latestVideoUrl}. Iniciando download...");

            $filename = 'live_' . $live->id . '_' . time() . '.mp4';
            $destPath = $destFolder . DIRECTORY_SEPARATOR . $filename;
            $escapedUrl = escapeshellarg($latestVideoUrl);
            $escapedDest = escapeshellarg($destPath);

            $dlCmd = "yt-dlp -f \"bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best\" --merge-output-format mp4 --no-warnings --no-playlist -o {$escapedDest} {$escapedUrl} 2>&1";
            exec($dlCmd, $dlOutput, $dlCode);

            if ($dlCode !== 0 || !file_exists($destPath) || filesize($destPath) < 100000) {
                $dlFallback = "yt-dlp --no-warnings --no-playlist -o {$escapedDest} {$escapedUrl} 2>&1";
                exec($dlFallback, $dlOutput, $dlCode);
            }

            if (!file_exists($destPath) || filesize($destPath) < 100000) {
                Log::error("[AutoProcessor] Falha ao baixar vídeo: " . implode("\n", $dlOutput));
                return ['success' => false, 'message' => 'Falha ao baixar vídeo do Instagram.'];
            }

            $storageRelPath = 'live_recordings/' . $filename;
            $live->recording_path = $storageRelPath;
            $live->recording_filename = $filename;
            $live->save();
            $videoPath = $destPath;
        }

        // 4. Transcrição de áudio com timestamps
        if (empty($live->transcription_raw) || $live->transcription_status !== 'completed') {
            Log::info("[AutoProcessor] Extraindo áudio e gerando transcrição com IA...");
            $this->transcribeVideoAudio($live, $videoPath);
        }

        // 5. Detectar Minutagem das Peças Automaticamente
        Log::info("[AutoProcessor] Executando detecção inteligente de minutagem das peças...");
        $controller = new LiveVideoCutsController();
        $controller->autoDetectTimestamps(new Request(), $live->id);

        // 6. Gerar Cortes em Lote com FFmpeg
        Log::info("[AutoProcessor] Gerando cortes de vídeo individuais com FFmpeg...");
        $batchResult = $controller->generateBatchClips(new Request(), $live->id);

        Log::info("[AutoProcessor] Pipeline concluído com sucesso para Live #{$live->id}!");

        return [
            'success' => true,
            'message' => "Vídeo baixado, transcrito e cortes gerados com sucesso para a Live #{$live->id}!",
            'live_id' => $live->id,
            'recording_path' => $live->recording_path,
            'batch_result' => $batchResult->getData(true)
        ];
    }

    /**
     * Extrai áudio e transcreve com timestamps usando Whisper / Groq (com suporte a lives longas via chunking)
     *
     * @param Live $live
     * @param string $videoPath
     * @param callable|null $progressCallback
     * @return bool
     */
    public function transcribeVideoAudio(Live $live, string $videoPath, ?callable $progressCallback = null)
    {
        $destFolder = storage_path('app/public/live_recordings');
        if (!is_dir($destFolder)) {
            mkdir($destFolder, 0775, true);
        }

        $chunksDir = $destFolder . DIRECTORY_SEPARATOR . 'chunks_' . $live->id . '_' . time();
        if (!is_dir($chunksDir)) {
            mkdir($chunksDir, 0775, true);
        }

        if ($progressCallback) {
            $progressCallback(15, 'Extraindo e segmentando faixas de áudio com FFmpeg...');
        }

        // Segmenta áudio em blocos de 600s (10 minutos) otimizados para voz (mono 16kHz 32kbps)
        $chunkPattern = $chunksDir . DIRECTORY_SEPARATOR . 'chunk_%03d.mp3';
        $ffmpegCmd = sprintf(
            'ffmpeg -i %s -vn -ar 16000 -ac 1 -b:a 32k -f segment -segment_time 600 -reset_timestamps 1 %s 2>&1',
            escapeshellarg($videoPath),
            escapeshellarg($chunkPattern)
        );
        exec($ffmpegCmd, $ffOutput, $ffCode);

        $chunkFiles = glob($chunksDir . DIRECTORY_SEPARATOR . 'chunk_*.mp3');
        sort($chunkFiles);

        if (empty($chunkFiles)) {
            Log::warning("[AutoProcessor] Falha ao extrair chunks de áudio com FFmpeg: " . implode("\n", $ffOutput ?? []));
            @rmdir($chunksDir);
            return false;
        }

        $groqKey = config('services.groq.api_key') ?: env('GROQ_API_KEY');
        if (empty($groqKey)) {
            Log::error("[AutoProcessor] GROQ_API_KEY não configurada.");
            return false;
        }

        $totalChunks = count($chunkFiles);
        $sentences = [];
        $currentOffset = 0.0;

        foreach ($chunkFiles as $idx => $chunkFile) {
            $chunkNumber = $idx + 1;
            $pct = round(20 + (($idx / $totalChunks) * 65));

            if ($progressCallback) {
                $progressCallback($pct, "Transcrevendo parte {$chunkNumber} de {$totalChunks} com IA (Groq Whisper)...");
            }

            Log::info("[AutoProcessor] Transcrevendo parte {$chunkNumber}/{$totalChunks} ({$chunkFile})...");

            try {
                $response = Http::withToken($groqKey)
                    ->timeout(240)
                    ->attach('file', file_get_contents($chunkFile), 'audio.mp3')
                    ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                        'model' => 'whisper-large-v3',
                        'response_format' => 'verbose_json',
                        'temperature' => 0,
                        'language' => 'pt',
                        'timestamp_granularities' => ['segment']
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data['segments'])) {
                        foreach ($data['segments'] as $seg) {
                            $start = round($currentOffset + (float) ($seg['start'] ?? 0), 2);
                            $end = round($currentOffset + (float) ($seg['end'] ?? 0), 2);
                            $text = trim($seg['text'] ?? '');
                            if (!empty($text)) {
                                $sentences[] = [
                                    'start' => $start,
                                    'end' => $end,
                                    'text' => $text
                                ];
                            }
                        }
                    }
                } else {
                    Log::warning("[AutoProcessor] Groq Whisper falhou no chunk {$chunkNumber}: " . $response->body());
                }
            } catch (\Exception $e) {
                Log::warning("[AutoProcessor] Erro na requisição do chunk {$chunkNumber}: " . $e->getMessage());
            }

            // Descobrir a duração exata do chunk para o offset do próximo
            $durOutput = [];
            exec(sprintf('ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s', escapeshellarg($chunkFile)), $durOutput);
            $duration = !empty($durOutput[0]) ? (float) trim($durOutput[0]) : 600.0;
            $currentOffset += $duration;

            @unlink($chunkFile);
        }

        @rmdir($chunksDir);

        if (!empty($sentences)) {
            $live->transcription_raw = json_encode($sentences, JSON_UNESCAPED_UNICODE);
            $live->transcription_status = 'completed';
            $live->save();

            Log::info("[AutoProcessor] Transcrição concluída: " . count($sentences) . " frases gravadas.");
            return true;
        }

        return false;
    }
}



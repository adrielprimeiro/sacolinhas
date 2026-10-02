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
     * Atualiza o cache de status e invoca callback de progresso
     */
    protected function updateStatus(Live $live, int $progress, string $message, string $status = 'processing', ?callable $progressCallback = null)
    {
        Log::info("[AutoProcessor][Live #{$live->id}][{$progress}%] {$message}");
        
        \Illuminate\Support\Facades\Cache::put("live_transcription_status_{$live->id}", [
            'status' => $status,
            'progress' => $progress,
            'message' => $message,
            'updated_at' => now()->toIso8601String()
        ], 3600);

        if ($progressCallback) {
            $progressCallback($progress, $message);
        }
    }

    /**
     * Verifica o Instagram por um novo vídeo da live, baixa, transcreve e gera os cortes
     * 
     * @param int|null $liveId
     * @param string $instagramHandle
     * @param string|null $directVideoUrl
     * @param callable|null $progressCallback
     * @return array
     */
    public function processLiveVideo($liveId = null, $instagramHandle = 'de_minha_mania', $directVideoUrl = null, ?callable $progressCallback = null)
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

        $this->updateStatus($live, 5, "Iniciando verificação para Live #{$live->id}...", 'processing', $progressCallback);

        $destFolder = storage_path('app/public/live_recordings');
        if (!is_dir($destFolder)) {
            @mkdir($destFolder, 0777, true);
            @chmod($destFolder, 0777);
        }

        // 2. Verificar se já tem vídeo local
        $videoPath = null;
        if (!empty($live->recording_path)) {
            $existingPath = Storage::disk('public')->path($live->recording_path);
            if (file_exists($existingPath) && filesize($existingPath) > 500000) {
                $videoPath = $existingPath;
            }
        }

        // 3. Buscar vídeo mais recente do Instagram via yt-dlp ou URL direta
        if (!$videoPath) {
            $latestVideoUrl = $directVideoUrl;

            if (!$latestVideoUrl) {
                $this->updateStatus($live, 10, "Buscando publicação de vídeo mais recente no perfil @{$instagramHandle}...", 'processing', $progressCallback);
                $profileUrl = "https://www.instagram.com/{$instagramHandle}/reels/";
                $escapedProfile = escapeshellarg($profileUrl);

                // yt-dlp flat-playlist para obter URLs recentes
                $cmd = "yt-dlp --dump-json --flat-playlist --playlist-end 3 {$escapedProfile} 2>&1";
                $output = [];
                $returnCode = 0;
                exec($cmd, $output, $returnCode);

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
            }

            if (!$latestVideoUrl) {
                $this->updateStatus($live, 0, "Nenhum vídeo novo detectado no perfil @{$instagramHandle}. Forneça o link da publicação.", 'error', $progressCallback);
                return ['success' => false, 'message' => "Nenhum vídeo novo detectado no perfil @{$instagramHandle}. Forneça a URL do post diretamente."];
            }

            $this->updateStatus($live, 15, "Baixando gravação do Instagram em alta resolução...", 'processing', $progressCallback);

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
                $this->updateStatus($live, 0, "Falha ao baixar vídeo do Instagram. Verifique o link.", 'error', $progressCallback);
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
            $this->updateStatus($live, 25, "Vídeo pronto. Extraindo áudio e transcrevendo com IA...", 'processing', $progressCallback);
            $transcribed = $this->transcribeVideoAudio($live, $videoPath, function($pct, $msg) use ($live, $progressCallback) {
                $this->updateStatus($live, $pct, $msg, 'processing', $progressCallback);
            });

            if (!$transcribed) {
                $this->updateStatus($live, 0, "Falha na transcrição do áudio com IA (Whisper).", 'error', $progressCallback);
                return ['success' => false, 'message' => 'Falha na transcrição de áudio com IA.'];
            }
        }

        // 5. Detectar Minutagem das Peças Automaticamente
        $this->updateStatus($live, 80, "Transcrição pronta! Detectando minutagem inteligente das peças...", 'processing', $progressCallback);
        $controller = new LiveVideoCutsController();
        $controller->autoDetectTimestamps(new Request(), $live->id);

        // 6. Gerar Cortes em Lote com FFmpeg
        $this->updateStatus($live, 90, "Gerando cortes de vídeo individuais com FFmpeg...", 'processing', $progressCallback);
        $batchResult = $controller->generateBatchClips(new Request(), $live->id);

        $this->updateStatus($live, 100, "Cortes de vídeo e minutagens gerados com sucesso!", 'completed', $progressCallback);

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

        // Segmenta áudio em blocos de 600s (10 minutos) com filtros avançados de limpeza vocal (ASR Audio Pre-processing):
        // 1. highpass=120, lowpass=3800: Filtra frequências fora da fala humana
        // 2. afftdn=nf=-25: Redução de ruído de fundo / chiado de microfone
        // 3. loudnorm: Normalização de volume EBU R128 para a voz ficar uniforme e clara
        $chunkPattern = $chunksDir . DIRECTORY_SEPARATOR . 'chunk_%03d.mp3';
        $ffmpegCmd = sprintf(
            'ffmpeg -y -i %s -vn -af "highpass=f=120,lowpass=f=3800,afftdn=nf=-25,loudnorm=I=-16:TP=-1.5:LRA=11" -ar 16000 -ac 1 -b:a 48k -f segment -segment_time 600 -reset_timestamps 1 %s 2>&1',
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

        $whisperPrompt = "Transcrição de Live Shopping de Brechó Minha Mania. Roupas, vestidos, calças, casacos, saias, croppeds, marcas (Farm, Zara, Shein, Animale, Colcci, Cantão, Le Lis Blanc, Renner, C&A, Marisa), tamanhos PP, P, M, G, GG, cores, valores em reais e códigos: código 1, código 2, código 3, código 4, código 5, peça 1, peça 2, peça 3, quem quer comenta eu, código.";

        foreach ($chunkFiles as $idx => $chunkFile) {
            $chunkNumber = $idx + 1;
            $pct = round(20 + (($idx / $totalChunks) * 65));

            if ($progressCallback) {
                $progressCallback($pct, "Transcrevendo parte {$chunkNumber} de {$totalChunks} com IA (Groq Whisper ASR)...");
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
                        'prompt' => $whisperPrompt,
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



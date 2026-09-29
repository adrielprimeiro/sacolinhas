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
     * Extrai áudio e transcreve com timestamps usando Whisper / Groq / Gemini
     */
    public function transcribeVideoAudio(Live $live, string $videoPath)
    {
        $audioPath = storage_path('app/public/live_recordings/audio_' . $live->id . '.mp3');

        // Extrai áudio comprimido otimizado para fala
        $ffmpegCmd = sprintf(
            'ffmpeg -i %s -vn -ar 16000 -ac 1 -b:a 48k -y %s 2>&1',
            escapeshellarg($videoPath),
            escapeshellarg($audioPath)
        );
        exec($ffmpegCmd);

        if (!file_exists($audioPath) || filesize($audioPath) < 1000) {
            Log::warning("[AutoProcessor] Falha ao extrair áudio com FFmpeg.");
            return false;
        }

        $groqKey = config('services.groq.api_key') ?: env('GROQ_API_KEY');
        $sentences = [];

        // 1. Tentar Groq Whisper (rápido e com timestamps por segmento)
        if (!empty($groqKey)) {
            try {
                if (filesize($audioPath) < 25000000) {
                    $response = Http::withToken($groqKey)
                        ->timeout(180)
                        ->attach('file', file_get_contents($audioPath), 'audio.mp3')
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
                                $sentences[] = [
                                    'start' => (float) ($seg['start'] ?? 0),
                                    'end' => (float) ($seg['end'] ?? 0),
                                    'text' => trim($seg['text'] ?? '')
                                ];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("[AutoProcessor] Groq Whisper falhou: " . $e->getMessage());
            }
        }

        if (!empty($sentences)) {
            $live->transcription_raw = json_encode($sentences, JSON_UNESCAPED_UNICODE);
            $live->transcription_status = 'completed';
            $live->save();

            @unlink($audioPath);
            return true;
        }

        return false;
    }
}

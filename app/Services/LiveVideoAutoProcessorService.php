<?php

namespace App\Services;

use App\Models\Live;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
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
        
        Cache::put("live_transcription_status_{$live->id}", [
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
     * Retorna diagnóstico detalhado e status de cada fase do pipeline (Fases 1 a 5)
     */
    public function getPipelineSummary(Live $live, ?string $directVideoUrl = null): array
    {
        $videoPath = $this->getLocalVideoPath($live);
        $phase1Done = !empty($videoPath) && file_exists($videoPath) && filesize($videoPath) > 100000;

        $phase2Done = !empty($live->transcription_raw) && $live->transcription_status === 'completed';

        $totalItems = DB::table('live_items')->where('live_id', $live->id)->count();

        $minutadosCount = DB::table('live_items')
            ->where('live_id', $live->id)
            ->whereNotNull('cut_start_sec')
            ->whereNotNull('cut_end_sec')
            ->count();
        $phase3Done = ($totalItems > 0 && $minutadosCount >= $totalItems);

        $fatiadosCount = DB::table('live_items')
            ->where('live_id', $live->id)
            ->whereNotNull('video_cut_path')
            ->where('video_cut_path', '!=', '')
            ->count();
        $phase4Done = ($totalItems > 0 && $fatiadosCount >= $totalItems);

        $editadosCount = DB::table('live_items')
            ->where('live_id', $live->id)
            ->where('video_cut_status', 'edited')
            ->count();
        $phase5Done = ($totalItems > 0 && $editadosCount >= $totalItems);

        $isAllCompleted = ($phase1Done && $phase2Done && $phase3Done && $phase4Done && $phase5Done);

        $pendingSteps = [];
        if (!$phase1Done) $pendingSteps[] = 'Passo 1 (Download da Gravação)';
        if (!$phase2Done) $pendingSteps[] = 'Passo 2 (Transcrição Whisper IA)';
        if (!$phase3Done) $pendingSteps[] = 'Passo 3 (Minutagem IA: ' . ($totalItems - $minutadosCount) . ' peças pendentes)';
        if (!$phase4Done) $pendingSteps[] = 'Passo 4 (Fatiamento FFmpeg: ' . ($totalItems - $fatiadosCount) . ' cortes pendentes)';
        if (!$phase5Done) $pendingSteps[] = 'Passo 5 (Selo & Marca d\'água: ' . ($totalItems - $editadosCount) . ' vídeos pendentes)';

        return [
            'is_all_completed' => $isAllCompleted,
            'total_items' => $totalItems,
            'phase1' => ['done' => $phase1Done, 'video_path' => $videoPath],
            'phase2' => ['done' => $phase2Done, 'status' => $live->transcription_status],
            'phase3' => ['done' => $phase3Done, 'completed' => $minutadosCount, 'total' => $totalItems, 'pending' => max(0, $totalItems - $minutadosCount)],
            'phase4' => ['done' => $phase4Done, 'completed' => $fatiadosCount, 'total' => $totalItems, 'pending' => max(0, $totalItems - $fatiadosCount)],
            'phase5' => ['done' => $phase5Done, 'completed' => $editadosCount, 'total' => $totalItems, 'pending' => max(0, $totalItems - $editadosCount)],
            'pending_steps' => $pendingSteps
        ];
    }

    /**
     * Executa comandos de console em paralelo com pool de processos controlados
     */
    protected function runParallelCommands(array $commands, int $concurrency = 2, ?callable $onProgress = null): array
    {
        $concurrency = max(1, min($concurrency, 3));
        $total = count($commands);
        if ($total === 0) {
            return ['success' => 0, 'failed' => 0, 'total' => 0];
        }

        $running = [];
        $queue = array_values($commands);
        $completed = 0;
        $success = 0;
        $failed = 0;

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        while (!empty($queue) || !empty($running)) {
            // Inicia novos processos até atingir o limite de concorrência
            while (count($running) < $concurrency && !empty($queue)) {
                $cmd = array_shift($queue);
                $proc = @proc_open($cmd, $descriptors, $pipes);
                if (is_resource($proc)) {
                    fclose($pipes[0]); // Sem entrada padrão
                    $running[] = [
                        'proc' => $proc,
                        'pipes' => $pipes,
                        'cmd' => $cmd
                    ];
                } else {
                    $completed++;
                    $failed++;
                    if ($onProgress) $onProgress($completed, $total);
                }
            }

            // Verifica processos em andamento
            foreach ($running as $key => $item) {
                $status = proc_get_status($item['proc']);
                if (!$status['running']) {
                    $output = stream_get_contents($item['pipes'][1]);
                    $errOutput = stream_get_contents($item['pipes'][2]);
                    fclose($item['pipes'][1]);
                    fclose($item['pipes'][2]);
                    $exitCode = proc_close($item['proc']);

                    $completed++;
                    if ($exitCode === 0) {
                        $success++;
                    } else {
                        $failed++;
                        Log::warning("[AutoProcessor][Parallel] Comando falhou (Exit {$exitCode}): {$item['cmd']} - " . trim($errOutput ?: $output));
                    }

                    unset($running[$key]);
                    if ($onProgress) $onProgress($completed, $total);
                }
            }

            // Pausa breve de 100ms para liberar ciclos de CPU
            usleep(100000);
        }

        return ['success' => $success, 'failed' => $failed, 'total' => $total];
    }

    /**
     * Executa o fluxo de cortes completo de ponta a ponta:
     * Fases 1 a 5 (Download, Transcrição Whisper, Minutagem IA, Fatiamento e Edição Selo/Marca)
     *
     * @param int|null $liveId
     * @param string $instagramHandle
     * @param string|null $directVideoUrl
     * @param callable|null $progressCallback
     * @return array
     */
    public function processLiveVideo($liveId = null, $instagramHandle = 'de_minha_mania', $directVideoUrl = null, ?callable $progressCallback = null, bool $force = false)
    {
        // 1. Obter a live
        if ($liveId) {
            $live = Live::find($liveId);
        } else {
            $live = Live::where('created_at', '>=', now()->subHours(48))
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('live_items')
                          ->whereColumn('live_items.live_id', 'lives.id');
                })
                ->orderBy('id', 'desc')
                ->first();

            if (!$live) {
                $live = Live::where('ativo', true)->orderBy('id', 'desc')->first();
            }
        }

        if (!$live) {
            Log::info("[AutoProcessor] Nenhuma live recente encontrada para processamento.");
            return ['success' => false, 'message' => 'Nenhuma live encontrada para processar.'];
        }

        // Sanitiza URL direta se informada
        if ($directVideoUrl) {
            $directVideoUrl = trim($directVideoUrl);
            if (preg_match('#https?://(?:www\.)?instagram\.com/(reel|p|tv)/([a-zA-Z0-9_-]+)#i', $directVideoUrl, $m)) {
                $directVideoUrl = "https://www.instagram.com/{$m[1]}/{$m[2]}/";
            }
        }

        $lastSourceUrl = Cache::get("live_{$live->id}_source_url");
        $isNewSourceUrl = $directVideoUrl && ($lastSourceUrl !== $directVideoUrl);
        $forceReprocess = $force || $isNewSourceUrl;

        // Diagnóstico Inteligente de Idempotência: só reaproveita se NÃO for forçado nem troca de URL
        if (!$forceReprocess) {
            $summary = $this->getPipelineSummary($live, $directVideoUrl);
            if ($summary['is_all_completed']) {
                $msg = "Esta live já está 100% processada! Todos os {$summary['total_items']} cortes já foram fatiados e editados com selo e marca d'água.";
                $this->updateStatus($live, 100, $msg, 'completed', $progressCallback);
                return [
                    'success' => true,
                    'already_completed' => true,
                    'message' => $msg,
                    'live_id' => $live->id,
                    'total_items' => $summary['total_items']
                ];
            }
        }

        $this->updateStatus($live, 5, "Iniciando automação inteligente para Live #{$live->id}...", 'processing', $progressCallback);

        $destFolder = storage_path('app/public/live_recordings');
        if (!is_dir($destFolder)) {
            @mkdir($destFolder, 0777, true);
            @chmod($destFolder, 0777);
        }

        // ==========================================
        // FASE 1: DOWNLOAD DO VÍDEO COMPLETO DA LIVE
        // ==========================================
        $videoPath = $this->getLocalVideoPath($live);
        $needsDownload = false;

        if ($forceReprocess) {
            $needsDownload = true;
        } elseif ($directVideoUrl) {
            // Se URL foi informada expressamente, só pula download se o arquivo local já existe e veio dessa mesma URL
            if (!$videoPath || $lastSourceUrl !== $directVideoUrl || !file_exists($videoPath) || filesize($videoPath) < 100000) {
                $needsDownload = true;
            }
        } elseif (!$videoPath || !file_exists($videoPath) || filesize($videoPath) < 100000) {
            $needsDownload = true;
        }

        if ($needsDownload) {
            $targetUrl = $directVideoUrl;

            if (!$targetUrl) {
                $this->updateStatus($live, 8, "Buscando publicação de vídeo mais recente no perfil @{$instagramHandle}...", 'processing', $progressCallback);
                $profileUrl = "https://www.instagram.com/{$instagramHandle}/reels/";
                $escapedProfile = escapeshellarg($profileUrl);

                $cmd = "yt-dlp --dump-json --flat-playlist --playlist-end 3 {$escapedProfile} 2>&1";
                $output = [];
                $returnCode = 0;
                exec($cmd, $output, $returnCode);

                foreach ($output as $line) {
                    $json = json_decode($line, true);
                    if (!empty($json['url'])) {
                        $targetUrl = $json['url'];
                        break;
                    } elseif (!empty($json['webpage_url'])) {
                        $targetUrl = $json['webpage_url'];
                        break;
                    }
                }
            }

            if (!$targetUrl) {
                $this->updateStatus($live, 0, "Nenhum vídeo novo detectado. Por favor, forneça o link da publicação do Instagram.", 'error', $progressCallback);
                return ['success' => false, 'message' => "Nenhum vídeo novo detectado. Forneça o link da publicação."];
            }

            $this->updateStatus($live, 12, "Baixando vídeo da live do Instagram em alta resolução (Passo 1)...", 'processing', $progressCallback);

            $filename = 'live_' . $live->id . '_' . time() . '.mp4';
            $destPath = $destFolder . DIRECTORY_SEPARATOR . $filename;
            $escapedUrl = escapeshellarg($targetUrl);
            $escapedDest = escapeshellarg($destPath);

            // Baixa via yt-dlp com headers anti-bloqueio
            if (!file_exists($destPath) || filesize($destPath) < 100000) {
                $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
                $referer = 'https://www.instagram.com/';

                $dlCmd = sprintf(
                    'yt-dlp --user-agent %s --referer %s --retries 5 --fragment-retries 5 --retry-sleep exp=2:10 -f "bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best" --merge-output-format mp4 --no-warnings --no-playlist -o %s %s 2>&1',
                    escapeshellarg($userAgent),
                    escapeshellarg($referer),
                    $escapedDest,
                    $escapedUrl
                );

                $dlOutput = [];
                $dlCode = 0;
                for ($attempt = 1; $attempt <= 3; $attempt++) {
                    $dlOutput = [];
                    $dlCode = 0;
                    exec($dlCmd, $dlOutput, $dlCode);

                    if ($dlCode === 0 && file_exists($destPath) && filesize($destPath) > 100000) {
                        break;
                    }

                    Log::warning("[AutoProcessor] Tentativa {$attempt}/3 falhou no download: " . implode("\n", array_slice($dlOutput, -10)));

                    if ($attempt < 3) {
                        $this->updateStatus($live, 12, "Instagram ocupado (429). Aguardando 5s para tentar novamente ({$attempt}/3)...", 'processing', $progressCallback);
                        sleep(5);
                    }
                }

                if (!file_exists($destPath) || filesize($destPath) < 100000) {
                    $dlFallback = sprintf(
                        'yt-dlp --user-agent %s --referer %s --retries 3 --no-warnings --no-playlist -o %s %s 2>&1',
                        escapeshellarg($userAgent),
                        escapeshellarg($referer),
                        $escapedDest,
                        $escapedUrl
                    );
                    exec($dlFallback, $dlOutput, $dlCode);
                }
            }

            if (!file_exists($destPath) || filesize($destPath) < 100000) {
                Log::error("[AutoProcessor] Falha ao baixar vídeo: " . implode("\n", $dlOutput));
                $this->updateStatus($live, 0, "Falha ao baixar gravação do Instagram. Verifique o link informado.", 'error', $progressCallback);
                return ['success' => false, 'message' => 'Falha ao baixar vídeo do Instagram.'];
            }

            $storageRelPath = 'live_recordings/' . $filename;
            $live->recording_path = $storageRelPath;
            $live->recording_filename = $filename;
            // Se baixou um vídeo novo, força nova transcrição e limpa cortes desatualizados
            $live->transcription_status = 'pending';
            $live->transcription_raw = null;
            $live->save();
            $videoPath = $destPath;
            Cache::put("live_{$live->id}_source_url", $targetUrl, 86400 * 30);

            // Reseta minutagens e cortes anteriores para garantir sincronia com a nova gravação
            DB::table('live_items')->where('live_id', $live->id)->update([
                'cut_start_sec' => null,
                'cut_end_sec' => null,
                'middle_cut_start_sec' => null,
                'middle_cut_end_sec' => null,
                'video_cut_path' => null,
                'video_cut_filename' => null,
                'video_cut_duration' => null,
                'video_cut_status' => null,
                'video_cut_url' => null,
                'thumbnail_candidates' => null,
                'transcription_snippet' => null,
            ]);
            
            $this->updateStatus($live, 20, "Vídeo baixado com sucesso da gravação!", 'processing', $progressCallback);
        } else {
            $this->updateStatus($live, 20, "Vídeo da gravação verificado no servidor.", 'processing', $progressCallback);
        }

        // ==========================================
        // FASE 2: TRANSCRIÇÃO DE ÁUDIO COM WHISPER IA (PARALELO)
        // ==========================================
        if (empty($live->transcription_raw) || $live->transcription_status !== 'completed') {
            $this->updateStatus($live, 25, "Extraindo áudio da gravação e preparando transcrição IA (Passo 2)...", 'processing', $progressCallback);
            $transcribed = $this->transcribeVideoAudio($live, $videoPath, function($pct, $msg) use ($live, $progressCallback) {
                $this->updateStatus($live, $pct, $msg, 'processing', $progressCallback);
            });

            if (!$transcribed) {
                $this->updateStatus($live, 0, "Falha na transcrição do áudio com IA (Whisper).", 'error', $progressCallback);
                return ['success' => false, 'message' => 'Falha na transcrição de áudio com IA.'];
            }
        } else {
            $this->updateStatus($live, 65, "Transcrição de áudio já existente e carregada.", 'processing', $progressCallback);
        }

        // ==========================================
        // FASE 3: DETECTAR MINUTAGEM DAS PEÇAS COM IA SEVERINO (SÍNCRONO)
        // ==========================================
        $controller = new LiveVideoCutsController();
        $totalItemsCount = DB::table('live_items')->where('live_id', $live->id)->count();
        $minutadosCount = DB::table('live_items')
            ->where('live_id', $live->id)
            ->whereNotNull('cut_start_sec')
            ->whereNotNull('cut_end_sec')
            ->count();

        if ($totalItemsCount > 0 && $minutadosCount < $totalItemsCount) {
            $this->updateStatus($live, 70, "Detectando minutagem e códigos das peças com Severino IA (Passo 3)...", 'processing', $progressCallback);
            $results = $controller->performAutoDetection($live, null, false, function($pct, $msg) use ($live, $progressCallback) {
                $scaledPct = (int) round(70 + (($pct / 100) * 5));
                $this->updateStatus($live, $scaledPct, $msg, 'processing', $progressCallback);
            });
            $count = count($results);
            $this->updateStatus($live, 75, "Minutagem IA concluída com sucesso! ({$count} peças mapeadas)", 'processing', $progressCallback);
        } else {
            $this->updateStatus($live, 75, "Passo 3 já executado: Todas as {$totalItemsCount} peças já estão minutadas.", 'processing', $progressCallback);
        }

        // ==========================================
        // FASE 4: FATIAMENTO EM LOTE COM FFMPEG EM PARALELO (2 WORKERS)
        // ==========================================
        $itemsToCut = DB::table('live_items')
            ->where('live_id', $live->id)
            ->whereNotNull('cut_start_sec')
            ->whereNotNull('cut_end_sec')
            ->where(function($q) {
                $q->whereNull('video_cut_path')
                  ->orWhere('video_cut_path', '');
            })
            ->orderBy('id', 'asc')
            ->get();

        $artisanPath = base_path('artisan');
        $cutCommands = [];
        foreach ($itemsToCut as $item) {
            $cutCommands[] = sprintf('php %s app:generate-live-item-cut %d %d', escapeshellarg($artisanPath), $live->id, $item->id);
        }

        $totalCuts = count($cutCommands);
        if ($totalCuts > 0) {
            $this->updateStatus($live, 76, "Fatiando {$totalCuts} cortes de vídeo com FFmpeg em paralelo (Passo 4)...", 'processing', $progressCallback);
            $cutResult = $this->runParallelCommands($cutCommands, 2, function($completed, $total) use ($live, $progressCallback) {
                $pct = (int) round(76 + (($completed / $total) * 12)); // 76% a 88%
                $this->updateStatus($live, $pct, "Fatiando corte {$completed} de {$total} com FFmpeg (Passo 4)...", 'processing', $progressCallback);
            });
            Log::info("[AutoProcessor] Fatiamento concluído: {$cutResult['success']} sucessos, {$cutResult['failed']} falhas de {$totalCuts}.");
        } else {
            $this->updateStatus($live, 88, "Passo 4 já executado: Todos os cortes já estavam fatiados com FFmpeg.", 'processing', $progressCallback);
        }

        // ==========================================
        // FASE 5: EDIÇÃO COM SELO E MARCA D'ÁGUA EM PARALELO (Passo 5)
        // ==========================================
        $itemsToEdit = DB::table('live_items')
            ->where('live_id', $live->id)
            ->whereNotNull('video_cut_path')
            ->where('video_cut_path', '!=', '')
            ->where(function($q) {
                $q->whereNull('video_cut_status')
                  ->orWhere('video_cut_status', '!=', 'edited');
            })
            ->orderBy('id', 'asc')
            ->get();

        $editCommands = [];
        foreach ($itemsToEdit as $item) {
            $editCommands[] = sprintf('php %s app:apply-live-item-edition %d %d --style=reel_canvas', escapeshellarg($artisanPath), $live->id, $item->id);
        }

        $totalEdits = count($editCommands);
        if ($totalEdits > 0) {
            $this->updateStatus($live, 89, "Aplicando selo de estado e marca d'água em {$totalEdits} cortes em paralelo (Passo 5)...", 'processing', $progressCallback);
            $editResult = $this->runParallelCommands($editCommands, 2, function($completed, $total) use ($live, $progressCallback) {
                $pct = (int) round(89 + (($completed / $total) * 11)); // 89% a 100%
                $this->updateStatus($live, $pct, "Aplicando selo e marca no vídeo {$completed} de {$total} (Passo 5)...", 'processing', $progressCallback);
            });
            Log::info("[AutoProcessor] Edição concluída: {$editResult['success']} sucessos, {$editResult['failed']} falhas de {$totalEdits}.");
        } else {
            $this->updateStatus($live, 99, "Passo 5 já executado: Todos os cortes já possuem selo e marca aplicados.", 'processing', $progressCallback);
        }

        // ==========================================
        // CONCLUSÃO COM SUCESSO (FASES 1 A 5 CONCLUÍDAS)
        // ==========================================
        $this->updateStatus($live, 100, "Processamento completo (Fases 1, 2, 3, 4 e 5) concluído com sucesso!", 'completed', $progressCallback);

        return [
            'success' => true,
            'message' => "Processamento completo da Live #{$live->id} concluído com sucesso (Fases 1 a 5)! Todos os cortes foram fatiados e editados com selo e marca d'água.",
            'live_id' => $live->id,
            'recording_path' => $live->recording_path,
            'total_items' => count($itemsToCut),
            'total_edited' => count($itemsToEdit)
        ];
    }

    /**
     * Extrai áudio e transcreve com timestamps usando Whisper / Groq (com envio paralelo de blocos de áudio)
     */
    public function transcribeVideoAudio(Live $live, string $videoPath, ?callable $progressCallback = null): bool
    {
        $destFolder = storage_path('app/public/live_recordings');
        if (!is_dir($destFolder)) {
            @mkdir($destFolder, 0775, true);
        }

        $chunksDir = $destFolder . DIRECTORY_SEPARATOR . 'chunks_' . $live->id . '_' . time();
        if (!is_dir($chunksDir)) {
            @mkdir($chunksDir, 0775, true);
        }

        if ($progressCallback) {
            $progressCallback(28, 'Segmentando áudio em blocos com FFmpeg...');
        }

        // Segmenta áudio em blocos de 1800s (30 minutos) em mono 16kHz 32k
        $segmentDuration = 1800;
        $chunkPattern = $chunksDir . DIRECTORY_SEPARATOR . 'chunk_%03d.mp3';
        $ffmpegCmd = sprintf(
            'ffmpeg -y -i %s -vn -af "highpass=f=100,lowpass=f=4000,afftdn=nf=-20" -ar 16000 -ac 1 -b:a 32k -f segment -segment_time %d -reset_timestamps 1 %s 2>&1',
            escapeshellarg($videoPath),
            $segmentDuration,
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
        $whisperPrompt = "Transcrição de Live Shopping de Brechó Minha Mania. Roupas, vestidos, calças, casacos, saias, croppeds, marcas (Farm, Zara, Shein, Animale, Colcci, Cantão, Le Lis Blanc, Renner, C&A, Marisa), tamanhos PP, P, M, G, GG, cores, valores em reais e códigos: código 1, código 2, código 3, código 4, código 5, peça 1, peça 2, peça 3, quem quer comenta eu, código.";

        if ($progressCallback) {
            $progressCallback(35, "Enviando {$totalChunks} bloco(s) de áudio em paralelo para Groq Whisper IA...");
        }

        // Envio paralelo de todos os blocos via Http::pool
        $poolResponses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($chunkFiles, $groqKey, $whisperPrompt) {
            $reqs = [];
            foreach ($chunkFiles as $idx => $chunkFile) {
                if (file_exists($chunkFile) && filesize($chunkFile) > 1000) {
                    $reqs[$idx] = $pool->as((string) $idx)
                        ->withToken($groqKey)
                        ->timeout(300)
                        ->attach('file', file_get_contents($chunkFile), 'audio.mp3')
                        ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                            'model' => 'whisper-large-v3',
                            'response_format' => 'verbose_json',
                            'temperature' => 0,
                            'language' => 'pt',
                            'prompt' => $whisperPrompt,
                            'timestamp_granularities' => ['segment', 'word']
                        ]);
                }
            }
            return $reqs;
        });

        $sentences = [];

        foreach ($chunkFiles as $idx => $chunkFile) {
            $currentOffset = $idx * (float) $segmentDuration;
            $res = $poolResponses[(string) $idx] ?? null;

            // Se falhou no pool, tenta fallback síncrono único
            if (!$res || !$res->successful()) {
                Log::warning("[AutoProcessor] Chunk {$idx} falhou no pool, tentando requisição direta...");
                try {
                    $res = Http::withToken($groqKey)
                        ->timeout(240)
                        ->attach('file', file_get_contents($chunkFile), 'audio.mp3')
                        ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                            'model' => 'whisper-large-v3',
                            'response_format' => 'verbose_json',
                            'temperature' => 0,
                            'language' => 'pt',
                            'prompt' => $whisperPrompt,
                            'timestamp_granularities' => ['segment', 'word']
                        ]);
                } catch (\Exception $e) {
                    Log::error("[AutoProcessor] Fallback falhou para chunk {$idx}: " . $e->getMessage());
                }
            }

            if ($res && $res->successful()) {
                $data = $res->json();
                if (!empty($data['segments'])) {
                    foreach ($data['segments'] as $seg) {
                        $start = round($currentOffset + (float) ($seg['start'] ?? 0), 2);
                        $end = round($currentOffset + (float) ($seg['end'] ?? 0), 2);
                        $text = trim($seg['text'] ?? '');
                        $segWords = [];
                        if (!empty($seg['words']) && is_array($seg['words'])) {
                            foreach ($seg['words'] as $w) {
                                $segWords[] = [
                                    'word' => trim($w['word'] ?? ''),
                                    'start' => round($currentOffset + (float) ($w['start'] ?? 0), 2),
                                    'end' => round($currentOffset + (float) ($w['end'] ?? 0), 2)
                                ];
                            }
                        }
                        if (!empty($text)) {
                            $entry = [
                                'start' => $start,
                                'end' => $end,
                                'text' => $text
                            ];
                            if (!empty($segWords)) {
                                $entry['words'] = $segWords;
                            }
                            $sentences[] = $entry;
                        }
                    }
                }
            }

            @unlink($chunkFile);
        }

        @rmdir($chunksDir);

        if (!empty($sentences)) {
            // Ordena frases cronologicamente
            usort($sentences, function($a, $b) {
                return ($a['start'] ?? 0) <=> ($b['start'] ?? 0);
            });

            $live->transcription_raw = json_encode($sentences, JSON_UNESCAPED_UNICODE);
            $live->transcription_status = 'completed';
            $live->save();

            Log::info("[AutoProcessor] Transcrição concluída: " . count($sentences) . " falas gravadas.");
            return true;
        }

        return false;
    }

    /**
     * Localiza o caminho absoluto do arquivo de vídeo no servidor
     */
    public function getLocalVideoPath(Live $live): ?string
    {
        if (empty($live->recording_path)) return null;

        $path = $live->recording_path;
        if (file_exists($path) && filesize($path) > 100000) {
            return $path;
        }

        $publicPath = Storage::disk('public')->path($path);
        if (file_exists($publicPath) && filesize($publicPath) > 100000) {
            return $publicPath;
        }

        $cleanRel = ltrim(preg_replace('/^(\/?storage\/)/i', '', $path), '/');

        $storageAppPublic = storage_path('app/public/' . $cleanRel);
        if (file_exists($storageAppPublic) && filesize($storageAppPublic) > 100000) {
            return $storageAppPublic;
        }

        $publicDir = public_path($cleanRel);
        if (file_exists($publicDir) && filesize($publicDir) > 100000) {
            return $publicDir;
        }

        $publicDirStorage = public_path('storage/' . $cleanRel);
        if (file_exists($publicDirStorage) && filesize($publicDirStorage) > 100000) {
            return $publicDirStorage;
        }

        return null;
    }
}

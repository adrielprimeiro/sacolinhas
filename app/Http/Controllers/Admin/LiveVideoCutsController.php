<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Live;
use App\Models\Item;
use App\Models\ItemMedia;

class LiveVideoCutsController extends Controller
{
    /**
     * Redireciona para a tela de cortes da live mais recente
     */
    public function indexLatest(Request $request)
    {
        $liveId = $request->query('live_id');
        if ($liveId) {
            return redirect()->route('admin.lives.cortes', ['liveId' => $liveId]);
        }
        $latest = Live::where('ativo', true)->orderBy('id', 'desc')->first()
            ?? Live::orderBy('id', 'desc')->first();
        if (!$latest) {
            abort(404, 'Nenhuma live cadastrada.');
        }
        return redirect()->route('admin.lives.cortes', ['liveId' => $latest->id]);
    }

    /**
     * Tela Principal de Gerenciamento e Revisão de Cortes da Live
     */
    public function index($liveId)
    {
        $live = Live::findOrFail($liveId);
        $lives = Live::orderBy('id', 'desc')->limit(40)->get();

        // Buscar itens vinculados a esta live
        $hasCandCol = Schema::hasColumn('live_items', 'thumbnail_candidates');
        $selectCols = [
            'live_items.id as live_item_id',
            'live_items.live_id',
            'live_items.item_id',
            'live_items.codigo_live',
            'live_items.user_id',
            'live_items.buyer_username',
            'live_items.buyer_name',
            'live_items.cut_start_sec',
            'live_items.cut_end_sec',
            'live_items.transcription_snippet',
            'live_items.video_cut_path',
            'live_items.video_cut_filename',
            'live_items.video_cut_url',
            'live_items.video_cut_duration',
            'live_items.video_cut_status',
            'live_items.created_at as linked_at',
            'items.nome_do_produto as item_nome',
            'items.descricao as item_descricao',
            'items.codigo as item_codigo',
            'items.preco as item_price',
            'items.image as item_image',
            'users.name as user_full_name'
        ];
        if ($hasCandCol) {
            $selectCols[] = 'live_items.thumbnail_candidates';
        }
        if (Schema::hasColumn('live_items', 'is_reviewed')) {
            $selectCols[] = 'live_items.is_reviewed';
        }
        if (Schema::hasColumn('live_items', 'review_quality')) {
            $selectCols[] = 'live_items.review_quality';
        }

        $query = DB::table('live_items')
            ->join('items', 'live_items.item_id', '=', 'items.id')
            ->leftJoin('users', 'live_items.user_id', '=', 'users.id')
            ->where('live_items.live_id', $liveId)
            ->select($selectCols)
            ->orderBy('live_items.id', 'asc');

        $liveItems = $query->get()->map(function ($row) {
            // Nome do produto
            $name = $row->item_nome ?: ($row->item_descricao ?: 'Produto #' . $row->item_id);

            // Foto / Imagem do item
            $rawImage = $row->item_image;
            $image = $rawImage;
            if ($image && !str_starts_with($image, 'http') && !str_starts_with($image, '/storage/')) {
                $image = '/storage/' . ltrim($image, '/');
            }

            // Candidatas de Thumbnail
            $rawCandidates = !empty($row->thumbnail_candidates) ? json_decode($row->thumbnail_candidates, true) : [];
            $candidates = [];
            if (is_array($rawCandidates)) {
                foreach ($rawCandidates as $cand) {
                    if (is_array($cand) && !empty($cand['path'])) {
                        $cand['url'] = str_starts_with($cand['path'], 'http') ? $cand['path'] : Storage::url($cand['path']);
                        $candidates[] = $cand;
                    } elseif (is_string($cand)) {
                        $candidates[] = [
                            'path' => $cand,
                            'url' => str_starts_with($cand, 'http') ? $cand : Storage::url($cand),
                            'label' => 'Opção',
                            'timestamp' => null
                        ];
                    }
                }
            }

            // URL do corte se existir
            $videoUrl = $row->video_cut_url;
            if (!$videoUrl && $row->video_cut_path) {
                if (str_starts_with($row->video_cut_path, 'http')) {
                    $videoUrl = $row->video_cut_path;
                } else {
                    $videoUrl = Storage::url($row->video_cut_path);
                }
            }

            return [
                'live_item_id' => $row->live_item_id,
                'item_id' => $row->item_id,
                'codigo_live' => $row->codigo_live ?: $row->item_codigo,
                'item_name' => $name,
                'item_sku' => $row->item_codigo,
                'item_codigo' => $row->item_codigo,
                'item_price' => number_format((float) ($row->item_price ?: 0), 2, ',', '.'),
                'item_image' => $image ?: 'https://placehold.co/100x100?text=Sem+Foto',
                'raw_image_path' => $rawImage,
                'buyer_name' => $row->buyer_name ?: ($row->user_full_name ?: ($row->buyer_username ? '@' . $row->buyer_username : null)),
                'buyer_username' => $row->buyer_username,
                'cut_start_sec' => $row->cut_start_sec !== null ? (float) $row->cut_start_sec : null,
                'cut_end_sec' => $row->cut_end_sec !== null ? (float) $row->cut_end_sec : null,
                'cut_start_formatted' => $row->cut_start_sec !== null ? $this->formatSecondsToTime($row->cut_start_sec) : '',
                'cut_end_formatted' => $row->cut_end_sec !== null ? $this->formatSecondsToTime($row->cut_end_sec) : '',
                'duration_sec' => ($row->cut_start_sec !== null && $row->cut_end_sec !== null) ? max(0, round($row->cut_end_sec - $row->cut_start_sec, 1)) : ($row->video_cut_duration ?: null),
                'transcription_snippet' => $row->transcription_snippet,
                'video_cut_url' => $videoUrl,
                'video_cut_status' => $row->video_cut_status ?: 'none',
                'thumbnail_candidates' => $candidates,
                'linked_at' => $row->linked_at
            ];
        });

        // Parse da transcrição se disponível
        $transcriptionData = [];
        if (!empty($live->transcription_raw)) {
            $transcriptionData = json_decode($live->transcription_raw, true) ?: [];
        }

        // Estatísticas
        $totalItems = $liveItems->count();
        $itemsWithCuts = $liveItems->whereNotNull('cut_start_sec')->whereNotNull('cut_end_sec')->count();
        $itemsRendered = $liveItems->where('video_cut_status', 'recorded')->count();

        // URL da gravação bruta
        $recordingUrl = null;
        if ($live->recording_path) {
            $recordingUrl = str_starts_with($live->recording_path, 'http')
                ? $live->recording_path
                : Storage::url($live->recording_path);
        }

        return view('admin.lives.video_cuts', [
            'live' => $live,
            'lives' => $lives,
            'liveItems' => $liveItems,
            'transcriptionData' => $transcriptionData,
            'recordingUrl' => $recordingUrl,
            'stats' => [
                'total_items' => $totalItems,
                'items_with_cuts' => $itemsWithCuts,
                'items_rendered' => $itemsRendered,
            ]
        ]);
    }

    /**
     * Upload ou Associação do Vídeo Completo da Live
     */
    public function uploadVideo(Request $request, $liveId)
    {
        $live = Live::findOrFail($liveId);

        if ($request->hasFile('video_file')) {
            $request->validate([
                'video_file' => 'required|file|mimes:mp4,mov,mkv,webm,avi|max:2097152' // até 2GB
            ]);

            $file = $request->file('video_file');
            $filename = 'live_' . $liveId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('live_recordings', $filename, 'public');

            $live->recording_path = $path;
            $live->recording_filename = $filename;
            $live->save();

            return response()->json([
                'success' => true,
                'message' => 'Vídeo da live enviado com sucesso!',
                'recording_path' => $path,
                'recording_url' => Storage::url($path)
            ]);
        }

        if ($request->filled('video_path_manual')) {
            $path = trim((string) $request->input('video_path_manual'));

            // Se for uma URL externa (Instagram, TikTok, YouTube, ou link web)
            if (preg_match('/^https?:\/\//i', $path)) {
                $isSocialOrWebUrl = preg_match('/instagram\.com|tiktok\.com|youtube\.com|youtu\.be|facebook\.com|fb\.watch|twitter\.com|x\.com/i', $path)
                    || !preg_match('/\.(mp4|webm|mov|mkv|avi)(\?.*)?$/i', $path);

                if ($isSocialOrWebUrl) {
                    try {
                        $filename = 'live_' . $liveId . '_' . time() . '.mp4';
                        $destFolder = storage_path('app/public/live_recordings');
                        if (!is_dir($destFolder)) {
                            mkdir($destFolder, 0775, true);
                        }
                        $destPath = $destFolder . DIRECTORY_SEPARATOR . $filename;
                        $logPath = $destFolder . DIRECTORY_SEPARATOR . 'download_' . $liveId . '.log';

                        // Limpa log anterior
                        if (file_exists($logPath)) {
                            @unlink($logPath);
                        }

                        // Grava estado inicial no cache
                        \Illuminate\Support\Facades\Cache::put("live_video_download_{$liveId}", [
                            'status' => 'downloading',
                            'filename' => $filename,
                            'dest_path' => $destPath,
                            'log_path' => $logPath,
                            'rel_path' => 'live_recordings/' . $filename,
                            'url' => $path,
                            'started_at' => now()->toDateTimeString()
                        ], 3600);

                        $escapedUrl = escapeshellarg($path);
                        $escapedDest = escapeshellarg($destPath);
                        $escapedLog = escapeshellarg($logPath);

                        // Dispara em background via nohup com --newline para log limpo de progresso
                        $cmd = "nohup yt-dlp -f \"bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best\" --merge-output-format mp4 --no-warnings --no-playlist --newline -o {$escapedDest} {$escapedUrl} > {$escapedLog} 2>&1 &";
                        exec($cmd);

                        return response()->json([
                            'success' => true,
                            'is_async' => true,
                            'message' => 'Download do vídeo iniciado em segundo plano!',
                            'status_url' => route('admin.lives.cortes.video-download-status', ['liveId' => $liveId])
                        ]);
                    } catch (\Exception $e) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Erro ao iniciar download: ' . $e->getMessage()
                        ], 422);
                    }
                }
            }

            $live->recording_path = $path;
            $live->recording_filename = basename($path);
            $live->save();

            return response()->json([
                'success' => true,
                'message' => 'Caminho do vídeo associado com sucesso!',
                'recording_path' => $path,
                'recording_url' => str_starts_with($path, 'http') ? $path : Storage::url($path)
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Nenhum arquivo ou caminho fornecido.'], 400);
    }

    /**
     * Consulta o status do download em background do vídeo
     */
    public function getVideoDownloadStatus($liveId)
    {
        $info = \Illuminate\Support\Facades\Cache::get("live_video_download_{$liveId}");
        if (!$info) {
            $live = Live::find($liveId);
            if ($live && $live->recording_path) {
                return response()->json([
                    'status' => 'completed',
                    'progress' => 100,
                    'message' => 'Vídeo já vinculado!',
                    'recording_url' => str_starts_with($live->recording_path, 'http') ? $live->recording_path : Storage::url($live->recording_path)
                ]);
            }
            return response()->json(['status' => 'idle', 'progress' => 0]);
        }

        $logPath = $info['log_path'] ?? null;
        $destPath = $info['dest_path'] ?? null;
        $relPath = $info['rel_path'] ?? null;
        $filename = $info['filename'] ?? null;

        $progressPct = 0;
        $statusText = 'Baixando vídeo...';
        $isCompleted = false;
        $isError = false;
        $errorMessage = null;

        if ($logPath && file_exists($logPath)) {
            $content = file_get_contents($logPath);
            $lines = array_filter(explode("\n", trim($content)));

            if (!empty($lines)) {
                $lastLines = array_slice($lines, -15);
                foreach (array_reverse($lastLines) as $line) {
                    // Erro
                    if (str_contains($line, 'ERROR:') || str_contains($line, 'Permission denied')) {
                        $isError = true;
                        $errorMessage = $line;
                        break;
                    }
                    // Concluído / Merging
                    if (str_contains($line, '[Merger] Merging formats into') || str_contains($line, '100% of')) {
                        $progressPct = 99;
                        $statusText = 'Finalizando junção de áudio e vídeo...';
                    }
                    // Progresso download
                    if (preg_match('/\[download\]\s+([\d\.]+)%\s+of\s+([^\s]+)\s+at\s+([^\s]+)\s+ETA\s+([^\s]+)/', $line, $m)) {
                        $progressPct = (float) $m[1];
                        $totalSize = $m[2];
                        $speed = $m[3];
                        $eta = $m[4];
                        $statusText = "Baixando: {$progressPct}% de {$totalSize} ({$speed} - Restam {$eta})";
                        break;
                    } elseif (preg_match('/\[download\]\s+([\d\.]+)%/', $line, $m)) {
                        $progressPct = (float) $m[1];
                        $statusText = "Baixando: {$progressPct}%...";
                        break;
                    }
                }
            }
        }

        // Verificar se arquivo final existe e foi mesclado
        if ($destPath && file_exists($destPath) && filesize($destPath) > 500000) {
            $logContent = file_exists($logPath) ? file_get_contents($logPath) : '';
            if (str_contains($logContent, '[Merger] Merging formats into') || str_contains($logContent, 'Deleting original file')) {
                $live = Live::find($liveId);
                if ($live) {
                    $live->recording_path = $relPath;
                    $live->recording_filename = $filename;
                    $live->save();
                }

                \Illuminate\Support\Facades\Cache::forget("live_video_download_{$liveId}");

                return response()->json([
                    'status' => 'completed',
                    'progress' => 100,
                    'message' => 'Vídeo baixado e vinculado com sucesso!',
                    'recording_url' => Storage::url($relPath)
                ]);
            }
        }

        if ($isError) {
            \Illuminate\Support\Facades\Cache::forget("live_video_download_{$liveId}");
            return response()->json([
                'status' => 'error',
                'progress' => 0,
                'message' => $errorMessage ?: 'Falha ao baixar vídeo do link.'
            ]);
        }

        return response()->json([
            'status' => 'downloading',
            'progress' => $progressPct,
            'message' => $statusText
        ]);
    }

    /**
     * Dispara o processamento automático completo (Download Instagram + Transcrição + Minutagem + Cortes)
     */
    public function autoProcessLive(Request $request, $liveId)
    {
        $live = Live::findOrFail($liveId);
        $username = $request->input('username', 'de_minha_mania');
        $url = $request->input('url');

        \Illuminate\Support\Facades\Cache::put("live_transcription_status_{$liveId}", [
            'status' => 'processing',
            'progress' => 5,
            'message' => 'Iniciando verificação e download do vídeo da live...'
        ], 3600);

        $artisan = base_path('artisan');
        $urlArg = $url ? sprintf('--url=%s', escapeshellarg($url)) : '';
        $userArg = sprintf('--username=%s', escapeshellarg($username));
        $cmd = sprintf('nohup php %s app:auto-process-live-video --live_id=%d %s %s > /dev/null 2>&1 &', escapeshellarg($artisan), $liveId, $userArg, $urlArg);
        exec($cmd);

        return response()->json([
            'success' => true,
            'is_async' => true,
            'message' => 'Processamento automático iniciado em segundo plano!',
            'status_url' => route('admin.lives.cortes.transcribe-status', ['liveId' => $liveId])
        ]);
    }

    /**
     * Salva ou Atualiza a Transcrição da Live (com timestamps)
     */
    public function saveTranscription(Request $request, $liveId)
    {
        $live = Live::findOrFail($liveId);

        $sentences = $request->input('sentences', []);
        if (is_string($sentences)) {
            $sentences = json_decode($sentences, true) ?: [];
        }

        $live->transcription_raw = json_encode($sentences, JSON_UNESCAPED_UNICODE);
        $live->transcription_status = 'completed';
        $live->save();

        return response()->json([
            'success' => true,
            'message' => 'Transcrição salva com sucesso!',
            'total_sentences' => count($sentences)
        ]);
    }

    /**
     * Localiza o caminho absoluto do arquivo de vídeo no servidor

     */
    protected function getLocalVideoPath(Live $live): ?string
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

        $storageAppPublic = storage_path('app/public/' . ltrim($path, '/'));
        if (file_exists($storageAppPublic) && filesize($storageAppPublic) > 100000) {
            return $storageAppPublic;
        }

        return null;
    }

    /**
     * Transcreve o áudio do vídeo da Live com IA (Whisper) - Disparo Assíncrono para suportar lives longas (3h+)
     */
    public function transcribeAudio(Request $request, $liveId)
    {
        $live = Live::findOrFail($liveId);
        $videoPath = $this->getLocalVideoPath($live);

        if (!$videoPath) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum arquivo de vídeo local encontrado para transcrição. Faça o upload ou download do vídeo primeiro.'
            ], 400);
        }

        \Illuminate\Support\Facades\Cache::put("live_transcription_status_{$liveId}", [
            'status' => 'processing',
            'progress' => 5,
            'message' => 'Iniciando extração e fatiamento do áudio...'
        ], 3600);

        $artisan = base_path('artisan');
        $cmd = sprintf('nohup php %s app:transcribe-live-video --live_id=%d --auto_detect=0 > /dev/null 2>&1 &', escapeshellarg($artisan), $liveId);
        exec($cmd);

        return response()->json([
            'success' => true,
            'is_async' => true,
            'message' => 'Transcrição de áudio iniciada em segundo plano!',
            'status_url' => route('admin.lives.cortes.transcribe-status', ['liveId' => $liveId])
        ]);
    }

    /**
     * Consulta o status do processamento da transcrição/detecção em tempo real
     */
    public function getTranscriptionStatus($liveId)
    {
        $info = \Illuminate\Support\Facades\Cache::get("live_transcription_status_{$liveId}");
        if (!$info) {
            $live = Live::find($liveId);
            if ($live && $live->transcription_status === 'completed') {
                return response()->json([
                    'status' => 'completed',
                    'progress' => 100,
                    'message' => 'Transcrição e minutagens concluídas!'
                ]);
            }
            return response()->json(['status' => 'idle', 'progress' => 0]);
        }
        return response()->json($info);
    }

    /**
     * Executa a lógica de auto-detecção em cima de uma Live já transcrita
     */
    /**
     * Converte número em representações textuais em Português
     */
    protected function numberToPortugueseWords(int $num): array
    {
        $units = [
            0 => 'zero', 1 => 'um', 2 => 'dois', 3 => 'três', 4 => 'quatro',
            5 => 'cinco', 6 => 'seis', 7 => 'sete', 8 => 'oito', 9 => 'nove',
            10 => 'dez', 11 => 'onze', 12 => 'doze', 13 => 'treze', 14 => 'quatorze',
            15 => 'quinze', 16 => 'dezesseis', 17 => 'dezessete', 18 => 'dezoito', 19 => 'dezenove'
        ];
        $tens = [
            20 => 'vinte', 30 => 'trinta', 40 => 'quarenta', 50 => 'cinquenta',
            60 => 'sessenta', 70 => 'setenta', 80 => 'oitenta', 90 => 'noventa'
        ];
        $hundreds = [
            100 => 'cem', 200 => 'duzentos', 300 => 'trezentos', 400 => 'quatrocentos',
            500 => 'quinhentos', 600 => 'seiscentos', 700 => 'setecentos', 800 => 'oitocentos', 900 => 'novecentos'
        ];

        $results = [];

        if ($num < 20) {
            $results[] = $units[$num] ?? (string)$num;
            if ($num === 1) { $results[] = 'uma'; }
            if ($num === 2) { $results[] = 'duas'; }
            if ($num === 3) { $results[] = 'tres'; }
            if ($num === 6) { $results[] = 'meia'; }
        } elseif ($num < 100) {
            $t = (int) (floor($num / 10) * 10);
            $u = $num % 10;
            if ($u === 0) {
                $results[] = $tens[$t] ?? (string)$num;
            } else {
                $uWords = $this->numberToPortugueseWords($u);
                foreach ($uWords as $uw) {
                    $results[] = ($tens[$t] ?? '') . ' e ' . $uw;
                }
            }
        } elseif ($num === 100) {
            $results[] = 'cem';
        } elseif ($num < 1000) {
            $h = (int) (floor($num / 100) * 100);
            $rest = $num % 100;
            $hPrefix = ($h === 100) ? 'cento' : ($hundreds[$h] ?? '');
            if ($rest === 0) {
                $results[] = $hPrefix;
            } else {
                $restWords = $this->numberToPortugueseWords($rest);
                foreach ($restWords as $rw) {
                    $results[] = $hPrefix . ' e ' . $rw;
                }
            }
        }

        return array_unique($results);
    }

    /**
     * Retorna todas as variações faladas e numéricas de um código
     */
    protected function getCodeSearchVariations(string $code): array
    {
        $clean = trim($code);
        $variations = [$clean];
        if (is_numeric($clean)) {
            $intVal = (int) $clean;
            $variations[] = (string) $intVal;
            $variations[] = sprintf('%02d', $intVal);
            $words = $this->numberToPortugueseWords($intVal);
            $variations = array_merge($variations, $words);
        }
        return array_unique(array_filter($variations));
    }

    /**
     * Segmentação de IA de Alta Precisão usando Gemini 2.5 Flash / Groq LLaMA 3.3 70B com Aprendizado Severino (Few-Shot Feedback)
     */
    protected function detectItemTimestampsWithAI(Live $live, array $sentences, $liveItems, ?int $startCode = null, bool $onlyUnreviewed = false, ?callable $progressCallback = null): array
    {
        $geminiKey = config('services.gemini.paid_api_key') ?: (config('services.gemini.api_key') ?: env('GEMINI_API_KEY', ''));
        $groqKey = config('services.groq.api_key') ?: env('GROQ_API_KEY');

        if (empty($geminiKey) && empty($groqKey)) {
            Log::warning("[LiveVideoCuts] Nenhuma chave de IA configurada para segmentação.");
            return [];
        }

        // Filtra itens se houver startCode ou onlyUnreviewed
        $targetItems = $liveItems;
        $anchorContext = '';
        $initialAnchorSec = null;

        if ($startCode !== null) {
            // Acha o último item anterior a startCode para servir como âncora de tempo inicial
            $priorItem = $liveItems->filter(function($li) use ($startCode) {
                return (int) $li->codigo_live < $startCode && !empty($li->cut_end_sec);
            })->sortByDesc('cut_end_sec')->first();

            if ($priorItem && !empty($priorItem->cut_end_sec)) {
                $initialAnchorSec = (float) $priorItem->cut_end_sec;
                $anchorContext = "\nÂNCORA TEMPORAL: Os itens anteriores já estão fixados. A peça #{$priorItem->codigo_live} encerrou em {$priorItem->cut_end_sec}s. Portanto, a peça #{$startCode} COMEÇA a partir de {$priorItem->cut_end_sec}s em diante.\n";
            }

            $targetItems = $liveItems->filter(function($li) use ($startCode) {
                return (int) $li->codigo_live >= $startCode;
            });
        }

        if ($onlyUnreviewed && Schema::hasColumn('live_items', 'is_reviewed')) {
            $targetItems = $targetItems->filter(function($li) {
                return empty($li->is_reviewed);
            });
        }

        // Formata itens do catálogo em ordem
        $itemsCatalog = [];
        foreach ($targetItems as $li) {
            $code = trim($li->codigo_live ?: '');
            if (!$code) continue;
            $itemsCatalog[] = [
                'live_item_id' => $li->id,
                'codigo_live' => $code,
                'nome' => $li->nome_do_produto ?? 'Peça',
                'preco' => !empty($li->preco) ? ('R$ ' . number_format($li->preco, 2, ',', '.')) : ''
            ];
        }

        if (empty($itemsCatalog)) {
            return [];
        }

        // Formata as frases da transcrição de forma compacta e indexada por tempo
        $transcriptFormatted = [];
        foreach ($sentences as $s) {
            $sStart = round((float) ($s['start'] ?? 0), 1);
            $sEnd = round((float) ($s['end'] ?? 0), 1);
            $text = trim($s['text'] ?? '');
            if ($text) {
                $transcriptFormatted[] = "[{$sStart}s - {$sEnd}s] {$text}";
            }
        }
        $transcriptText = implode("\n", $transcriptFormatted);

        // Busca na memória do Severino os cortes aprovados por humanos (Few-Shot Exemplars)
        $fewShotSection = '';
        if (Schema::hasTable('live_cut_feedbacks')) {
            $feedbacks = DB::table('live_cut_feedbacks')
                ->whereNotNull('start_sentence_snippet')
                ->where('cut_end_sec', '>', DB::raw('cut_start_sec'))
                ->orderBy('id', 'desc')
                ->limit(8)
                ->get();

            if ($feedbacks->isNotEmpty()) {
                $examples = [];
                foreach ($feedbacks as $fb) {
                    $prodName = $fb->product_name ?: 'Peça';
                    $cCode = $fb->codigo_live ?: '?';
                    $dur = round($fb->cut_end_sec - $fb->cut_start_sec, 1);
                    $startTxt = addslashes(trim(mb_substr($fb->start_sentence_snippet, 0, 120)));
                    $endTxt = addslashes(trim(mb_substr($fb->end_sentence_snippet, 0, 120)));
                    $examples[] = "- Exemplo (#{$cCode} - {$prodName}):\n  * Início da fala: \"{$startTxt}\"\n  * Fim da fala: \"{$endTxt}\"\n  * Duração: {$dur}s";
                }
                $fewShotSection = "\n\n### PADRÕES DE CORTES PADRÃO-OURO REVISADOS POR HUMANOS (APRENDA COM ESTE ESTILO REAL DA APRESENTADORA):\n" . implode("\n", $examples);
            }
        }

        $systemPrompt = <<<PROMPT
Você é o Severino, o especialista sênior em IA para análise e minutagem de Live Shopping de Brechó (Minha Mania).
Sua missão é identificar o intervalo EXATO de tempo (cut_start_sec e cut_end_sec) para o vídeo de apresentação de cada peça da live.

DIRETRIZES FUNDAMENTAIS:
1. ORDEM CRONOLÓGICA E MONOTONICIDADE: As peças são apresentadas sequencialmente na ordem dos códigos (#1, #2, ... #20, #21...). O início da peça K+1 DEVE ser posterior ou igual ao início da peça K.
2. INÍCIO EXATO (cut_start_sec): Momento exato em que a apresentadora COMEÇA a mostrar a peça no cabide/corpo (Ex: "Olha esse vestido código 20...", "Agora o 20...", "Próxima peça, essa lindeza...", "Vem pro 20...").
3. FIM EXATO (cut_end_sec): Momento exato em que ela ENCERRA a apresentação da peça e passa para a próxima (Ex: "Passando...", "Anotado pra @maria", "Vendido código 20", "Deixa eu pegar a próxima...", "Vou bipar").
4. DURAÇÃO TÍPICA: Cada peça dura em média entre 20 a 75 segundos.
5. CUIDADO COM FALSOS POSITIVOS: Não confunda valores de preço (ex: "R$ 20 reais"), medidas ou menções atrasadas com a apresentação da peça.{$fewShotSection}

Retorne APENAS um JSON válido no formato de lista:
[
  {
    "live_item_id": 123,
    "codigo_live": "20",
    "cut_start_sec": 1234.5,
    "cut_end_sec": 1278.0,
    "snippet": "Texto completo da fala durante a apresentação da peça"
  }
]
PROMPT;

        $results = [];
        $liveItemsById = $liveItems->keyBy('id');
        $itemChunks = array_chunk($itemsCatalog, 20);
        $totalChunks = count($itemChunks);
        $lastKnownEndSec = $initialAnchorSec;

        foreach ($itemChunks as $chunkIdx => $chunk) {
            $chunkNumber = $chunkIdx + 1;
            $currentAnchor = '';
            if ($lastKnownEndSec !== null && $lastKnownEndSec > 0) {
                $currentAnchor = "\nÂNCORA TEMPORAL: A peça anterior foi finalizada em {$lastKnownEndSec}s. Os itens desta lista começam a partir de {$lastKnownEndSec}s em diante.\n";
            }

            $userPrompt = "{$currentAnchor}Itens a Segmentar na Live (Lote {$chunkNumber}/{$totalChunks}):\n" . json_encode($chunk, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\nTranscrição da Live:\n" . $transcriptText;

            $rawResponse = null;

            // 1. Google Gemini 2.0 Flash / 1.5 Flash
            if (!empty($geminiKey)) {
                $geminiModels = ['gemini-2.0-flash', 'gemini-1.5-flash'];
                foreach ($geminiModels as $gModel) {
                    try {
                        $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$gModel}:generateContent?key=" . $geminiKey;
                        $geminiPayload = [
                            'contents' => [
                                [
                                    'role' => 'user',
                                    'parts' => [
                                        ['text' => $systemPrompt . "\n\n" . $userPrompt]
                                    ]
                                ]
                            ],
                            'generationConfig' => [
                                'response_mime_type' => 'application/json',
                                'temperature' => 0.1
                            ]
                        ];

                        $response = Http::timeout(60)->post($geminiUrl, $geminiPayload);
                        if ($response->successful()) {
                            $json = $response->json();
                            $rawResponse = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
                            if (!empty($rawResponse)) {
                                break;
                            }
                        } else {
                            Log::warning("[LiveVideoCuts] Gemini ({$gModel}) falhou no lote {$chunkNumber}: " . $response->status() . " - " . $response->body());
                        }
                    } catch (\Exception $e) {
                        Log::warning("[LiveVideoCuts] Exceção no Gemini ({$gModel}) (Lote {$chunkNumber}): " . $e->getMessage());
                    }
                }
            }

            // 2. Fallback Groq LLaMA 3.3 70B
            if (empty($rawResponse) && !empty($groqKey)) {
                try {
                    $groqPayload = [
                        'model' => 'llama-3.3-70b-versatile',
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $userPrompt]
                        ],
                        'response_format' => ['type' => 'json_object'],
                        'temperature' => 0.1
                    ];

                    $response = Http::withToken($groqKey)->timeout(60)->post('https://api.groq.com/openai/v1/chat/completions', $groqPayload);
                    if ($response->successful()) {
                        $json = $response->json();
                        $rawResponse = $json['choices'][0]['message']['content'] ?? null;
                    }
                } catch (\Exception $e) {
                    Log::warning("[LiveVideoCuts] Exceção no Groq (Lote {$chunkNumber}): " . $e->getMessage());
                }
            }

            $decoded = null;
            if (!empty($rawResponse)) {
                $decoded = json_decode($rawResponse, true);
                if (isset($decoded['items']) && is_array($decoded['items'])) {
                    $decoded = $decoded['items'];
                } elseif (isset($decoded['cuts']) && is_array($decoded['cuts'])) {
                    $decoded = $decoded['cuts'];
                }
            }

            if (is_array($decoded) && !empty($decoded)) {
                foreach ($decoded as $entry) {
                    $liveItemId = $entry['live_item_id'] ?? null;
                    if (!$liveItemId || !$liveItemsById->has($liveItemId)) {
                        $code = (string) ($entry['codigo_live'] ?? '');
                        $matchedItem = $liveItems->firstWhere('codigo_live', $code);
                        if ($matchedItem) {
                            $liveItemId = $matchedItem->id;
                        } else {
                            continue;
                        }
                    }

                    $start = round((float) ($entry['cut_start_sec'] ?? 0), 1);
                    $end = round((float) ($entry['cut_end_sec'] ?? 0), 1);

                    if ($start < 0 || $end <= $start) continue;

                    $snippet = trim($entry['snippet'] ?? '') ?: $this->getSnippetForTimeRange($live, $start, $end);

                    DB::table('live_items')
                        ->where('id', $liveItemId)
                        ->update([
                            'cut_start_sec' => $start,
                            'cut_end_sec' => $end,
                            'transcription_snippet' => $snippet,
                            'updated_at' => now()
                        ]);

                    $li = $liveItemsById->get($liveItemId);
                    $lastKnownEndSec = $end;

                    $results[] = [
                        'live_item_id' => $liveItemId,
                        'codigo_live' => $li ? $li->codigo_live : ($entry['codigo_live'] ?? ''),
                        'cut_start_sec' => $start,
                        'cut_end_sec' => $end,
                        'cut_start_formatted' => $this->formatSecondsToTime($start),
                        'cut_end_formatted' => $this->formatSecondsToTime($end),
                        'duration_sec' => round($end - $start, 1),
                        'snippet' => $snippet
                    ];
                }
            }

            if ($progressCallback) {
                $pct = min(95, 10 + (int) round(($chunkNumber / $totalChunks) * 85));
                $firstCode = $chunk[0]['codigo_live'] ?? '';
                $lastCode = end($chunk)['codigo_live'] ?? '';
                $progressCallback($pct, "Severino IA minutando peças #{$firstCode} a #{$lastCode} (Lote {$chunkNumber}/{$totalChunks})...");
            }
        }

        return $results;
    }

    /**
     * Algoritmo de Fallback: Detecção Cronológica Monotônica Sequencial
     */
    protected function performChronologicalHeuristicDetection(Live $live, array $sentences, $liveItems): array
    {
        $openingPatterns = [
            'olha essa', 'olha esse', 'olha que', 'meninas', 'agora vamos', 'vamos para',
            'próxima peça', 'próximo item', 'vou mostrar', 'essa daqui', 'esse daqui',
            'linda demais', 'maravilhosa', 'vestido', 'blusa', 'calça', 'conjunto', 'cropped',
            'camisa', 'jaqueta', 'saia', 'short', 'macacão'
        ];

        $closingPatterns = [
            'entregando', 'vou entregar', 'passando', 'próxima', 'próximo', 'anotou',
            'quem pegou', 'fechou', 'vendido', 'vai para', 'deixa eu passar'
        ];

        $results = [];
        $lastEndSec = 0.0;

        foreach ($liveItems as $li) {
            $code = trim(strtolower($li->codigo_live ?: ''));
            if (!$code) continue;

            $variations = $this->getCodeSearchVariations($code);
            $regexPatterns = [];
            foreach ($variations as $var) {
                $escaped = preg_quote($var, '/');
                $regexPatterns[] = '(?:c[oó]digo|pe[cç]a|n[uú]mero|item)\s*' . $escaped;
            }
            $combinedRegex = '/\b(?:' . implode('|', $regexPatterns) . ')\b/iu';

            $bestMention = null;
            foreach ($sentences as $idx => $s) {
                $sStart = (float) ($s['start'] ?? 0);
                if ($sStart < ($lastEndSec - 10)) continue; // Mantém ordem cronológica estrita!

                $text = $s['text'] ?? '';
                if (preg_match($combinedRegex, $text)) {
                    $bestMention = [
                        'index' => $idx,
                        'start' => $sStart,
                        'end' => (float) ($s['end'] ?? 0),
                        'text' => $text
                    ];
                    break;
                }
            }

            if (!$bestMention) {
                continue;
            }

            $mentionIndex = $bestMention['index'];
            $mentionTime = $bestMention['start'];

            // Busca início do bloco respeitando o fim da peça anterior
            $startIndex = max(0, $mentionIndex - 5);
            $startTime = $mentionTime;

            for ($i = $mentionIndex; $i >= $startIndex; $i--) {
                $sStart = (float) ($sentences[$i]['start'] ?? 0);
                if ($sStart < $lastEndSec) break; // Não invade a peça anterior!
                if (($mentionTime - $sStart) > 40) break;

                $sText = mb_strtolower($sentences[$i]['text'] ?? '');
                $startTime = $sStart;

                foreach ($openingPatterns as $pat) {
                    if (str_contains($sText, $pat)) {
                        $startTime = $sStart;
                        break 2;
                    }
                }
            }

            // Busca fim do bloco
            $endIndex = min(count($sentences) - 1, $mentionIndex + 6);
            $endTime = (float) ($sentences[$mentionIndex]['end'] ?? ($mentionTime + 25));

            for ($j = $mentionIndex; $j <= $endIndex; $j++) {
                $sText = mb_strtolower($sentences[$j]['text'] ?? '');
                $sEnd = (float) ($sentences[$j]['end'] ?? 0);

                if (($sEnd - $startTime) > 70) break;
                $endTime = $sEnd;

                foreach ($closingPatterns as $cpat) {
                    if (str_contains($sText, $cpat)) {
                        $endTime = $sEnd;
                        break 2;
                    }
                }
            }

            $finalStart = max(0, round($startTime - 0.5, 1));
            $finalEnd = round($endTime + 0.8, 1);
            $lastEndSec = $finalEnd;

            $snippet = $this->getSnippetForTimeRange($live, $finalStart, $finalEnd);

            DB::table('live_items')
                ->where('id', $li->id)
                ->update([
                    'cut_start_sec' => $finalStart,
                    'cut_end_sec' => $finalEnd,
                    'transcription_snippet' => $snippet,
                    'updated_at' => now()
                ]);

            $results[] = [
                'live_item_id' => $li->id,
                'codigo_live' => $li->codigo_live,
                'cut_start_sec' => $finalStart,
                'cut_end_sec' => $finalEnd,
                'cut_start_formatted' => $this->formatSecondsToTime($finalStart),
                'cut_end_formatted' => $this->formatSecondsToTime($finalEnd),
                'duration_sec' => round($finalEnd - $finalStart, 1),
                'snippet' => $snippet
            ];
        }

        return $results;
    }

    /**
     * Algoritmo inteligente de correspondência de transcrição fonética + IA
     */
    public function performAutoDetection(Live $live, ?int $startCode = null, bool $onlyUnreviewed = false, ?callable $progressCallback = null): array
    {
        $sentences = json_decode($live->transcription_raw, true) ?: [];
        if (empty($sentences)) {
            return [];
        }

        $liveItems = DB::table('live_items')
            ->leftJoin('items', 'live_items.item_id', '=', 'items.id')
            ->where('live_items.live_id', $live->id)
            ->select('live_items.*', 'items.nome_do_produto', 'items.preco')
            ->orderBy('live_items.id', 'asc')
            ->get();

        if ($liveItems->isEmpty()) {
            return [];
        }

        // 1. Segmentação global / em lote com IA (Gemini 2.5 Flash / Groq)
        Log::info("[LiveVideoCuts] Executando segmentação de minutagem com IA para Live #{$live->id} (startCode: " . ($startCode ?: 'todos') . ")...");
        $aiResults = $this->detectItemTimestampsWithAI($live, $sentences, $liveItems, $startCode, $onlyUnreviewed, $progressCallback);

        if (!empty($aiResults)) {
            Log::info("[LiveVideoCuts] Segmentação IA concluída com sucesso: " . count($aiResults) . " itens minutados.");
            return $aiResults;
        }

        // 2. Fallback: Detecção Heurística Monotônica Cronológica
        Log::info("[LiveVideoCuts] Utilizando fallback cronológico para minutagem da Live #{$live->id}...");
        return $this->performChronologicalHeuristicDetection($live, $sentences, $liveItems);
    }

    /**
     * Endpoint de Detecção Automática de Início e Fim por Código / Transcrição
     */
    public function autoDetectTimestamps(Request $request, $liveId)
    {
        $live = Live::findOrFail($liveId);
        $sentences = json_decode($live->transcription_raw, true) ?: [];
        $startCode = $request->input('start_code') ? (int) $request->input('start_code') : null;
        $onlyUnreviewed = (bool) $request->input('only_unreviewed');

        // Se a transcrição estiver vazia, dispara transcrição assíncrona do vídeo
        if (empty($sentences)) {
            $videoPath = $this->getLocalVideoPath($live);
            if ($videoPath) {
                Cache::put("live_transcription_status_{$liveId}", [
                    'status' => 'processing',
                    'progress' => 5,
                    'message' => 'Iniciando extração do áudio e transcrição da live...'
                ], 3600);

                $artisan = base_path('artisan');
                $cmd = sprintf('nohup php %s app:transcribe-live-video --live_id=%d --auto_detect=1 > /dev/null 2>&1 &', escapeshellarg($artisan), $liveId);
                exec($cmd);

                return response()->json([
                    'success' => true,
                    'is_async' => true,
                    'message' => 'Transcrição e detecção iniciadas em segundo plano!',
                    'status_url' => route('admin.lives.cortes.transcribe-status', ['liveId' => $liveId])
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Nenhum vídeo carregado para esta live. Carregue o vídeo primeiro ou clique em Auto-Processar Instagram.'
            ], 400);
        }

        // Se já possui transcrição, dispara a minutagem assíncrona com Severino IA
        Cache::put("live_transcription_status_{$liveId}", [
            'status' => 'processing',
            'progress' => 10,
            'message' => 'Severino IA iniciando análise de minutagem dos cortes...'
        ], 3600);

        $artisan = base_path('artisan');
        $cmd = sprintf(
            'nohup php %s app:auto-detect-live-cuts --live_id=%d %s %s > /dev/null 2>&1 &',
            escapeshellarg($artisan),
            $liveId,
            $startCode ? '--start_code=' . $startCode : '',
            $onlyUnreviewed ? '--only_unreviewed=1' : ''
        );
        exec($cmd);

        return response()->json([
            'success' => true,
            'is_async' => true,
            'message' => 'Minutagem com Severino IA iniciada em segundo plano!',
            'status_url' => route('admin.lives.cortes.transcribe-status', ['liveId' => $liveId])
        ]);
    }

    /**
     * Extrai trecho do texto da transcrição e limites de frases de início e fim
     */
    protected function extractBoundarySentences(Live $live, float $start, float $end): array
    {
        $sentences = json_decode($live->transcription_raw, true) ?: [];
        if (empty($sentences)) {
            return ['start_sentence' => '', 'end_sentence' => '', 'full_text' => ''];
        }

        $matchedTexts = [];
        $firstSentence = '';
        $lastSentence = '';

        foreach ($sentences as $s) {
            $sStart = (float) ($s['start'] ?? 0);
            $sEnd = (float) ($s['end'] ?? 0);

            if ($sEnd >= ($start - 1.0) && $sStart <= ($end + 1.0)) {
                $text = trim($s['text'] ?? '');
                if (!empty($text)) {
                    if (empty($firstSentence)) {
                        $firstSentence = $text;
                    }
                    $lastSentence = $text;
                    $matchedTexts[] = $text;
                }
            }
        }

        return [
            'start_sentence' => $firstSentence,
            'end_sentence' => $lastSentence,
            'full_text' => implode(' ', $matchedTexts)
        ];
    }

    /**
     * Extrai trecho do texto da transcrição correspondente ao intervalo de tempo especificado
     */
    protected function getSnippetForTimeRange(Live $live, float $start, float $end): string
    {
        $res = $this->extractBoundarySentences($live, $start, $end);
        return $res['full_text'];
    }

    /**
     * Salva Manualmente os Timestamps de um Item e Alimenta o Aprendizado do Severino
     */
    public function saveItemTimestamp(Request $request, $liveId, $liveItemId)
    {
        $live = Live::findOrFail($liveId);
        $startRaw = $request->input('cut_start_sec');
        $endRaw = $request->input('cut_end_sec');

        $start = $this->parseTimeToSeconds($startRaw);
        $end = $this->parseTimeToSeconds($endRaw);

        if ($start === null || $end === null || $end <= $start) {
            return response()->json([
                'success' => false,
                'message' => 'O tempo de fim deve ser maior que o tempo de início.'
            ], 422);
        }

        $boundaries = $this->extractBoundarySentences($live, $start, $end);

        $updateData = [
            'cut_start_sec' => round($start, 2),
            'cut_end_sec' => round($end, 2),
            'transcription_snippet' => $boundaries['full_text'],
            'updated_at' => now()
        ];

        if (Schema::hasColumn('live_items', 'is_reviewed')) {
            $updateData['is_reviewed'] = true;
        }
        if (Schema::hasColumn('live_items', 'review_quality')) {
            $updateData['review_quality'] = 'human_adjusted';
        }

        DB::table('live_items')
            ->where('id', $liveItemId)
            ->where('live_id', $liveId)
            ->update($updateData);

        // Alimenta a memória do Severino (Few-Shot Feedback)
        $liveItem = DB::table('live_items')
            ->leftJoin('items', 'live_items.item_id', '=', 'items.id')
            ->where('live_items.id', $liveItemId)
            ->select('live_items.*', 'items.nome_do_produto', 'items.preco')
            ->first();

        if ($liveItem && Schema::hasTable('live_cut_feedbacks')) {
            DB::table('live_cut_feedbacks')->updateOrInsert(
                ['live_item_id' => $liveItemId],
                [
                    'live_id' => $liveId,
                    'item_id' => $liveItem->item_id,
                    'codigo_live' => $liveItem->codigo_live,
                    'product_name' => $liveItem->nome_do_produto,
                    'product_price' => $liveItem->preco,
                    'cut_start_sec' => round($start, 2),
                    'cut_end_sec' => round($end, 2),
                    'start_sentence_snippet' => $boundaries['start_sentence'],
                    'end_sentence_snippet' => $boundaries['end_sentence'],
                    'full_transcription_snippet' => $boundaries['full_text'],
                    'feedback_type' => 'human_adjusted',
                    'updated_at' => now(),
                    'created_at' => now()
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Minutagem salva e memorizada pelo Severino!',
            'cut_start_sec' => round($start, 2),
            'cut_end_sec' => round($end, 2),
            'cut_start_formatted' => $this->formatSecondsToTime($start),
            'cut_end_formatted' => $this->formatSecondsToTime($end),
            'duration_sec' => round($end - $start, 1),
            'snippet' => $boundaries['full_text'],
            'is_reviewed' => true,
            'review_quality' => 'human_adjusted'
        ]);
    }

    /**
     * Aprova um Corte como Padrão-Ouro (Severino grava como exemplo de alta qualidade)
     */
    public function approveItemCut(Request $request, $liveId, $liveItemId)
    {
        $live = Live::findOrFail($liveId);
        $liveItem = DB::table('live_items')
            ->leftJoin('items', 'live_items.item_id', '=', 'items.id')
            ->where('live_items.id', $liveItemId)
            ->where('live_items.live_id', $liveId)
            ->select('live_items.*', 'items.nome_do_produto', 'items.preco')
            ->first();

        if (!$liveItem || empty($liveItem->cut_start_sec) || empty($liveItem->cut_end_sec)) {
            return response()->json(['success' => false, 'message' => 'Item não possui minutagem definida para aprovar.'], 422);
        }

        $start = (float) $liveItem->cut_start_sec;
        $end = (float) $liveItem->cut_end_sec;
        $boundaries = $this->extractBoundarySentences($live, $start, $end);

        $updateData = [
            'transcription_snippet' => $boundaries['full_text'],
            'updated_at' => now()
        ];
        if (Schema::hasColumn('live_items', 'is_reviewed')) {
            $updateData['is_reviewed'] = true;
        }
        if (Schema::hasColumn('live_items', 'review_quality')) {
            $updateData['review_quality'] = 'gold';
        }

        DB::table('live_items')->where('id', $liveItemId)->update($updateData);

        if (Schema::hasTable('live_cut_feedbacks')) {
            DB::table('live_cut_feedbacks')->updateOrInsert(
                ['live_item_id' => $liveItemId],
                [
                    'live_id' => $liveId,
                    'item_id' => $liveItem->item_id,
                    'codigo_live' => $liveItem->codigo_live,
                    'product_name' => $liveItem->nome_do_produto,
                    'product_price' => $liveItem->preco,
                    'cut_start_sec' => round($start, 2),
                    'cut_end_sec' => round($end, 2),
                    'start_sentence_snippet' => $boundaries['start_sentence'],
                    'end_sentence_snippet' => $boundaries['end_sentence'],
                    'full_transcription_snippet' => $boundaries['full_text'],
                    'feedback_type' => 'human_approved',
                    'updated_at' => now(),
                    'created_at' => now()
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => "Corte da Peça #{$liveItem->codigo_live} aprovado como Padrão-Ouro e memorizado pelo Severino!",
            'is_reviewed' => true,
            'review_quality' => 'gold'
        ]);
    }


    /**
     * Converte string HH:MM:SS, MM:SS ou valor numérico em segundos (float)
     */
    private function parseTimeToSeconds($time): ?float
    {
        if ($time === null || $time === '') return null;
        if (is_numeric($time)) return (float) $time;

        $time = str_replace(',', '.', trim((string) $time));
        $parts = explode(':', $time);

        if (count($parts) === 3) {
            return ((float) $parts[0] * 3600) + ((float) $parts[1] * 60) + (float) $parts[2];
        } elseif (count($parts) === 2) {
            return ((float) $parts[0] * 60) + (float) $parts[1];
        }

        return is_numeric($time) ? (float) $time : null;
    }


    /**
     * Gera o Corte de Vídeo de um Único Item via FFmpeg
     */
    public function generateSingleClip(Request $request, $liveId, $liveItemId)
    {
        $live = Live::findOrFail($liveId);
        $liveItem = DB::table('live_items')
            ->where('id', $liveItemId)
            ->where('live_id', $liveId)
            ->first();

        if (!$liveItem || $liveItem->cut_start_sec === null || $liveItem->cut_end_sec === null) {
            return response()->json(['success' => false, 'message' => 'Defina os tempos de início e fim antes de gerar o corte.'], 400);
        }

        if (!$live->recording_path) {
            return response()->json(['success' => false, 'message' => 'Gravação completa da live não encontrada.'], 400);
        }

        $inputPath = Storage::disk('public')->path($live->recording_path);
        if (!file_exists($inputPath) && file_exists($live->recording_path)) {
            $inputPath = $live->recording_path;
        }

        if (!file_exists($inputPath)) {
            return response()->json(['success' => false, 'message' => 'Arquivo de vídeo original não encontrado no servidor.'], 404);
        }

        // Criar diretório de saída com permissões adequadas
        $outputDir = storage_path('app/public/live_cuts/live_' . $liveId);
        if (!file_exists($outputDir)) {
            @mkdir($outputDir, 0777, true);
            @chmod($outputDir, 0777);
        }

        $codeClean = preg_replace('/[^a-zA-Z0-9_-]/', '_', $liveItem->codigo_live ?: 'item_' . $liveItem->item_id);
        $filename = 'corte_' . $codeClean . '_' . time() . '.mp4';
        $outputPath = $outputDir . '/' . $filename;
        $relativeStoragePath = 'live_cuts/live_' . $liveId . '/' . $filename;

        $start = (float) $liveItem->cut_start_sec;
        $duration = max(1, round($liveItem->cut_end_sec - $start, 2));

        // 1. Corte de vídeo frame-accurate e ultrarrápido (0.2s - 0.4s por corte) com preset veryfast
        $cmd = sprintf(
            'ffmpeg -ss %s -i %s -t %s -c:v libx264 -preset veryfast -crf 22 -c:a aac -b:a 128k -avoid_negative_ts make_zero -movflags +faststart -y %s 2>&1',
            escapeshellarg($start),
            escapeshellarg($inputPath),
            escapeshellarg($duration),
            escapeshellarg($outputPath)
        );

        exec($cmd, $output, $returnCode);

        // Fallback rápido sem re-encode se o anterior falhar
        if ($returnCode !== 0 || !file_exists($outputPath) || filesize($outputPath) < 5000) {
            $outputFallback = [];
            $cmdFallback = sprintf(
                'ffmpeg -ss %s -i %s -t %s -c:v copy -c:a aac -b:a 128k -avoid_negative_ts make_zero -movflags +faststart -y %s 2>&1',
                escapeshellarg($start),
                escapeshellarg($inputPath),
                escapeshellarg($duration),
                escapeshellarg($outputPath)
            );
            exec($cmdFallback, $outputFallback, $returnCode);
            $output = $outputFallback;
        }

        if ($returnCode !== 0 || !file_exists($outputPath) || filesize($outputPath) < 5000) {
            Log::error("[LiveVideoCuts] Erro no FFmpeg: " . implode("\n", $output));
            return response()->json([
                'success' => false,
                'message' => 'Falha ao processar corte de vídeo via FFmpeg.',
                'debug' => implode("\n", array_slice($output, -10))
            ], 500);
        }

        $videoUrl = Storage::url($relativeStoragePath);

        // 3. Extrai 3 Miniaturas Inteligentes com o filtro de Nitidez / Histograma do FFmpeg
        $smartThumbs = $this->extractSmartThumbnails($inputPath, $outputDir, $liveId, $liveItemId, $codeClean, $start, $duration);
        $candidates = $smartThumbs['candidates'];
        $primary = $smartThumbs['primary'];

        $primaryPath = $primary ? $primary['path'] : null;
        $primaryUrl = $primary ? $primary['url'] : null;

        $updateData = [
            'video_cut_path' => $relativeStoragePath,
            'video_cut_filename' => $filename,
            'video_cut_url' => $videoUrl,
            'video_cut_duration' => round($duration),
            'video_cut_status' => 'recorded',
            'video_cut_finished_at' => now(),
            'updated_at' => now()
        ];

        if (Schema::hasColumn('live_items', 'thumbnail_candidates')) {
            $updateData['thumbnail_candidates'] = json_encode($candidates);
        }

        DB::table('live_items')
            ->where('id', $liveItemId)
            ->update($updateData);

        // Vincula ou atualiza a mídia do tipo vídeo com thumbnail no cadastro do item
        if (!empty($liveItem->item_id)) {
            ItemMedia::updateOrCreate(
                [
                    'item_id' => $liveItem->item_id,
                    'media_type' => 'video'
                ],
                [
                    'url' => $relativeStoragePath,
                    'thumbnail_url' => $primaryPath,
                    'position' => 99,
                    'is_cover' => false,
                    'alt_text' => 'Vídeo do produto na Live'
                ]
            );

            // Se o item não tiver imagem de capa ou estiver com foto anterior, define essa thumbnail como foto principal
            $itemObj = Item::find($liveItem->item_id);
            if ($itemObj && (empty($itemObj->image) || str_contains($itemObj->image, 'live_cuts/')) && $primaryPath) {
                $itemObj->image = $primaryPath;
                $itemObj->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Corte e 3 Miniaturas Inteligentes geradas com sucesso!',
            'video_url' => $videoUrl,
            'thumbnail_url' => $primaryUrl,
            'candidates' => $candidates,
            'duration' => $duration
        ]);
    }

    /**
     * Extrai 3 miniaturas estratégicas em alta velocidade (Início, Centro e Fim da peça)
     */
    private function extractSmartThumbnails($inputPath, $outputDir, $liveId, $liveItemId, $codeClean, $start, $duration)
    {
        if (!file_exists($outputDir)) {
            @mkdir($outputDir, 0777, true);
        }

        $candidates = [];
        $timeBase = time();

        $windows = [
            [
                'index' => 1,
                'label' => 'Início (Entrada)',
                'offset' => max(0.2, round($duration * 0.20, 2))
            ],
            [
                'index' => 2,
                'label' => 'Centro (Destaque)',
                'offset' => max(0.5, round($duration * 0.50, 2))
            ],
            [
                'index' => 3,
                'label' => 'Fim (Caimento)',
                'offset' => max(0.8, round($duration * 0.75, 2))
            ],
        ];

        foreach ($windows as $win) {
            $winStart = round($start + $win['offset'], 2);
            $filename = sprintf('thumb_%s_%d_%d.jpg', $codeClean, $timeBase, $win['index']);
            $thumbPath = $outputDir . '/' . $filename;
            $relativeThumbPath = 'live_cuts/live_' . $liveId . '/' . $filename;

            // Extração instantânea por frame seek (50ms)
            $cmd = sprintf(
                'ffmpeg -ss %s -i %s -vframes 1 -q:v 2 -y %s 2>&1',
                escapeshellarg($winStart),
                escapeshellarg($inputPath),
                escapeshellarg($thumbPath)
            );
            exec($cmd);

            if (file_exists($thumbPath) && filesize($thumbPath) > 500) {
                $candidates[] = [
                    'path' => $relativeThumbPath,
                    'url' => Storage::url($relativeThumbPath),
                    'label' => $win['label'],
                    'timestamp' => $winStart,
                    'index' => $win['index']
                ];
            }
        }

        $primary = null;
        if (!empty($candidates)) {
            $primary = $candidates[1] ?? ($candidates[0] ?? null);
        }

        return [
            'candidates' => $candidates,
            'primary' => $primary
        ];
    }

    /**
     * Define uma das miniaturas candidatas como a foto de capa oficial do produto
     */
    public function selectThumbnail(Request $request, $liveId, $liveItemId)
    {
        $liveItem = DB::table('live_items')->where('id', $liveItemId)->where('live_id', $liveId)->first();
        if (!$liveItem) {
            return response()->json(['success' => false, 'message' => 'Item não encontrado.'], 404);
        }

        $thumbnailPath = $request->input('thumbnail_path');
        if (empty($thumbnailPath)) {
            return response()->json(['success' => false, 'message' => 'Caminho da miniatura não fornecido.'], 422);
        }

        // Normaliza o caminho relativo
        $cleanPath = ltrim(parse_url($thumbnailPath, PHP_URL_PATH) ?? '', '/');
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        }

        if (!empty($liveItem->item_id)) {
            ItemMedia::updateOrCreate(
                [
                    'item_id' => $liveItem->item_id,
                    'media_type' => 'video'
                ],
                [
                    'thumbnail_url' => $cleanPath
                ]
            );

            $itemObj = Item::find($liveItem->item_id);
            if ($itemObj) {
                $itemObj->image = $cleanPath;
                $itemObj->save();
            }
        }

        $fullUrl = Storage::url($cleanPath);

        return response()->json([
            'success' => true,
            'message' => 'Foto de capa atualizada com sucesso!',
            'thumbnail_url' => $fullUrl,
            'thumbnail_path' => $cleanPath
        ]);
    }

    /**
     * Gera sob demanda apenas as 3 miniaturas inteligentes para o item
     */
    public function generateSmartThumbnailsAction(Request $request, $liveId, $liveItemId)
    {
        $live = Live::findOrFail($liveId);
        $liveItem = DB::table('live_items')->where('id', $liveItemId)->where('live_id', $liveId)->first();

        if (!$liveItem || !$live->recording_path) {
            return response()->json(['success' => false, 'message' => 'Item ou gravação não encontrados.'], 404);
        }

        $inputPath = Storage::disk('public')->path($live->recording_path);
        if (!file_exists($inputPath) && file_exists($live->recording_path)) {
            $inputPath = $live->recording_path;
        }

        if (!file_exists($inputPath)) {
            return response()->json(['success' => false, 'message' => 'Arquivo de vídeo original não encontrado no servidor.'], 404);
        }

        $start = (float) $liveItem->cut_start_sec;
        $end = (float) $liveItem->cut_end_sec;
        $duration = max(1, round($end - $start, 2));

        if ($start < 0 || $end <= $start) {
            return response()->json(['success' => false, 'message' => 'Defina o tempo de início e fim antes de gerar as miniaturas.'], 422);
        }

        $outputDir = storage_path('app/public/live_cuts/live_' . $liveId);
        $codeClean = preg_replace('/[^a-zA-Z0-9_-]/', '_', $liveItem->codigo_live ?: 'item_' . $liveItem->item_id);

        $result = $this->extractSmartThumbnails($inputPath, $outputDir, $liveId, $liveItemId, $codeClean, $start, $duration);
        $candidates = $result['candidates'];
        $primary = $result['primary'];

        if (empty($candidates)) {
            return response()->json(['success' => false, 'message' => 'Não foi possível extrair os quadros do vídeo.'], 500);
        }

        if (Schema::hasColumn('live_items', 'thumbnail_candidates')) {
            DB::table('live_items')->where('id', $liveItemId)->update([
                'thumbnail_candidates' => json_encode($candidates),
                'updated_at' => now()
            ]);
        }

        if ($primary && !empty($liveItem->item_id)) {
            ItemMedia::updateOrCreate(
                [
                    'item_id' => $liveItem->item_id,
                    'media_type' => 'video'
                ],
                [
                    'thumbnail_url' => $primary['path']
                ]
            );

            $itemObj = Item::find($liveItem->item_id);
            if ($itemObj && (empty($itemObj->image) || str_contains($itemObj->image, 'live_cuts/'))) {
                $itemObj->image = $primary['path'];
                $itemObj->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => '3 miniaturas inteligentes geradas com sucesso!',
            'candidates' => $candidates,
            'primary_url' => $primary ? $primary['url'] : null
        ]);
    }

    /**
     * Captura um frame específico do vídeo no timestamp fornecido como Thumbnail do Item
     */
    public function captureFrame(Request $request, $liveId, $liveItemId)
    {
        $live = Live::findOrFail($liveId);
        $liveItem = DB::table('live_items')->where('id', $liveItemId)->where('live_id', $liveId)->first();

        if (!$liveItem || !$live->recording_path) {
            return response()->json(['success' => false, 'message' => 'Item ou gravação não encontrados.'], 404);
        }

        $inputPath = Storage::disk('public')->path($live->recording_path);
        if (!file_exists($inputPath) && file_exists($live->recording_path)) {
            $inputPath = $live->recording_path;
        }

        if (!file_exists($inputPath)) {
            return response()->json(['success' => false, 'message' => 'Arquivo de vídeo original não encontrado no servidor.'], 404);
        }

        $timestampRaw = $request->input('timestamp');
        $timestamp = $this->parseTimeToSeconds($timestampRaw);

        if ($timestamp === null || $timestamp < 0) {
            return response()->json(['success' => false, 'message' => 'Tempo do frame inválido.'], 422);
        }

        $outputDir = storage_path('app/public/live_cuts/live_' . $liveId);
        if (!file_exists($outputDir)) {
            @mkdir($outputDir, 0777, true);
        }

        $codeClean = preg_replace('/[^a-zA-Z0-9_-]/', '_', $liveItem->codigo_live ?: 'item_' . $liveItem->item_id);
        $thumbFilename = 'thumb_' . $codeClean . '_manual_' . time() . '.jpg';
        $thumbPath = $outputDir . '/' . $thumbFilename;
        $relativeThumbPath = 'live_cuts/live_' . $liveId . '/' . $thumbFilename;

        $cmd = sprintf(
            'ffmpeg -ss %s -i %s -vframes 1 -q:v 2 -y %s 2>&1',
            escapeshellarg($timestamp),
            escapeshellarg($inputPath),
            escapeshellarg($thumbPath)
        );
        exec($cmd, $output, $returnCode);

        if (!file_exists($thumbPath) || filesize($thumbPath) < 1000) {
            return response()->json(['success' => false, 'message' => 'Falha ao capturar o frame do vídeo.'], 500);
        }

        $thumbUrl = Storage::url($relativeThumbPath);

        // Atualiza a lista de candidatos salvando a captura manual junto
        $existingCandidates = [];
        if (!empty($liveItem->thumbnail_candidates)) {
            $existingCandidates = json_decode($liveItem->thumbnail_candidates, true) ?: [];
        }
        $existingCandidates[] = [
            'path' => $relativeThumbPath,
            'url' => $thumbUrl,
            'label' => '📸 Captura (' . $this->formatSecondsToTime($timestamp) . ')',
            'timestamp' => $timestamp,
            'index' => count($existingCandidates) + 1
        ];

        if (Schema::hasColumn('live_items', 'thumbnail_candidates')) {
            DB::table('live_items')->where('id', $liveItemId)->update([
                'thumbnail_candidates' => json_encode($existingCandidates),
                'updated_at' => now()
            ]);
        }

        if (!empty($liveItem->item_id)) {
            ItemMedia::updateOrCreate(
                [
                    'item_id' => $liveItem->item_id,
                    'media_type' => 'video'
                ],
                [
                    'thumbnail_url' => $relativeThumbPath
                ]
            );

            // Atualiza a imagem principal do item caso esteja vazia ou se solicitado
            $itemObj = Item::find($liveItem->item_id);
            if ($itemObj && (empty($itemObj->image) || $request->boolean('set_as_cover'))) {
                $itemObj->image = $relativeThumbPath;
                $itemObj->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Quadro capturado e definido como Capa!',
            'thumbnail_url' => $thumbUrl,
            'thumbnail_path' => $relativeThumbPath,
            'candidates' => $existingCandidates,
            'timestamp' => $timestamp,
            'timestamp_formatted' => $this->formatSecondsToTime($timestamp)
        ]);
    }

    /**
     * Gera Todos os Cortes da Live em Lote
     */
    public function generateBatchClips(Request $request, $liveId)
    {
        $live = Live::findOrFail($liveId);
        $startCode = $request->input('start_code') ? (int) $request->input('start_code') : null;
        $onlyUnreviewed = (bool) $request->input('only_unreviewed');

        $query = DB::table('live_items')
            ->where('live_id', $liveId)
            ->whereNotNull('cut_start_sec')
            ->whereNotNull('cut_end_sec');

        if ($startCode !== null) {
            $query->where('codigo_live', '>=', $startCode);
        }

        if ($onlyUnreviewed && Schema::hasColumn('live_items', 'is_reviewed')) {
            $query->where('is_reviewed', false);
        }

        $items = $query->orderBy('id', 'asc')->get();

        if ($items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Nenhum item com minutagem definida para cortar.'], 400);
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($items as $item) {
            $req = new Request();
            $res = $this->generateSingleClip($req, $liveId, $item->id);
            $data = $res->getData(true);
            if (!empty($data['success'])) {
                $successCount++;
            } else {
                $failedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Processamento concluído: {$successCount} cortes gerados com sucesso." . ($failedCount > 0 ? " ({$failedCount} falhas)" : ''),
            'success_count' => $successCount,
            'failed_count' => $failedCount
        ]);
    }

    /**
     * Helper para formatar segundos em HH:MM:SS ou MM:SS
     */
    private function formatSecondsToTime($seconds)
    {
        $sec = (int) $seconds;
        $hours = floor($sec / 3600);
        $minutes = floor(($sec % 3600) / 60);
        $remainingSeconds = $sec % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }
        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }
}

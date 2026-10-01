<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\Live;
use App\Models\Item;
use App\Models\ItemMedia;

class LiveVideoCutsController extends Controller
{
    /**
     * Tela Principal de Gerenciamento e Revisão de Cortes da Live
     */
    public function index($liveId)
    {
        $live = Live::findOrFail($liveId);

        // Buscar itens vinculados a esta live
        $query = DB::table('live_items')
            ->join('items', 'live_items.item_id', '=', 'items.id')
            ->leftJoin('users', 'live_items.user_id', '=', 'users.id')
            ->where('live_items.live_id', $liveId)
            ->select(
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
            )
            ->orderBy('live_items.id', 'asc');

        $liveItems = $query->get()->map(function ($row) {
            // Nome do produto
            $name = $row->item_nome ?: ($row->item_descricao ?: 'Produto #' . $row->item_id);

            // Foto / Imagem do item
            $image = $row->item_image;
            if ($image && !str_starts_with($image, 'http') && !str_starts_with($image, '/storage/')) {
                $image = '/storage/' . ltrim($image, '/');
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
        $username = $request->input('username', 'de_minha_mania');
        $url = $request->input('url');
        $processor = new \App\Services\LiveVideoAutoProcessorService();
        $result = $processor->processLiveVideo($liveId, $username, $url);

        return response()->json($result);
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
     * Algoritmo inteligente de correspondência de transcrição fonética + Timeline da Bipagem
     */
    public function performAutoDetection(Live $live): array
    {
        $sentences = json_decode($live->transcription_raw, true) ?: [];
        if (empty($sentences)) {
            return [];
        }

        $liveItems = DB::table('live_items')
            ->where('live_id', $live->id)
            ->orderBy('id', 'asc')
            ->get();

        if ($liveItems->isEmpty()) {
            return [];
        }

        // Determina momento inicial da live para ancoragem temporal
        $firstItemTime = null;
        foreach ($liveItems as $li) {
            if ($li->created_at) {
                $firstItemTime = strtotime($li->created_at);
                break;
            }
        }

        $updatedCount = 0;
        $results = [];

        $openingPatterns = [
            'olha essa', 'olha esse', 'olha que', 'meninas', 'agora vamos', 'vamos para',
            'próxima peça', 'próximo item', 'vou mostrar', 'essa daqui', 'esse daqui',
            'linda demais', 'maravilhosa', 'vestido', 'blusa', 'calça', 'conjunto', 'cropped',
            'camisa', 'jaqueta', 'saia', 'short', 'macacão', 'tamanho', 'tecido', 'marca', 'valor'
        ];

        $closingPatterns = [
            'entregando', 'vou entregar', 'passando', 'próxima', 'próximo', 'anotou',
            'quem pegou', 'fechou', 'vendido', 'vai para', 'deixa eu passar', 'comenta código',
            'um beijo', 'boa noite'
        ];

        foreach ($liveItems as $li) {
            $code = trim(strtolower($li->codigo_live ?: ''));
            if (!$code) continue;

            $variations = $this->getCodeSearchVariations($code);
            $regexPatterns = [];
            foreach ($variations as $var) {
                $escaped = preg_quote($var, '/');
                $regexPatterns[] = '(?:c[oó]digo|pe[cç]a|n[uú]mero|item)?\s*' . $escaped;
            }
            $combinedRegex = '/\b(?:' . implode('|', $regexPatterns) . ')\b/iu';

            // Estimativa de tempo no vídeo pelo horário que o operador bipou a peça
            $estimatedVideoSec = null;
            if ($firstItemTime && $li->created_at) {
                $itemBipTime = strtotime($li->created_at);
                $diff = $itemBipTime - $firstItemTime;
                if ($diff >= 0) {
                    $estimatedVideoSec = (float) $diff;
                }
            }

            $candidateMentions = [];
            foreach ($sentences as $idx => $s) {
                $text = $s['text'] ?? '';
                if (preg_match($combinedRegex, $text)) {
                    $candidateMentions[] = [
                        'index' => $idx,
                        'start' => (float) ($s['start'] ?? 0),
                        'end' => (float) ($s['end'] ?? 0),
                        'text' => $text
                    ];
                }
            }

            if (empty($candidateMentions)) {
                continue;
            }

            // Seleciona o mention mais próximo do horário da bipagem, ou o primeiro
            $chosenMention = $candidateMentions[0];
            if ($estimatedVideoSec !== null && count($candidateMentions) > 1) {
                $bestDiff = PHP_INT_MAX;
                foreach ($candidateMentions as $cand) {
                    $dist = abs($cand['start'] - $estimatedVideoSec);
                    if ($dist < $bestDiff) {
                        $bestDiff = $dist;
                        $chosenMention = $cand;
                    }
                }
            }

            $mentionIndex = $chosenMention['index'];
            $mentionTime = $chosenMention['start'];

            // Busca início do bloco (introdução da peça) até 45s antes
            $startIndex = max(0, $mentionIndex - 5);
            $startTime = (float) ($sentences[$mentionIndex]['start'] ?? 0);

            for ($i = $mentionIndex; $i >= $startIndex; $i--) {
                $sText = mb_strtolower($sentences[$i]['text'] ?? '');
                $sStart = (float) ($sentences[$i]['start'] ?? 0);

                if (($mentionTime - $sStart) > 50) break;

                $startTime = $sStart;

                foreach ($openingPatterns as $pat) {
                    if (str_contains($sText, $pat)) {
                        $startTime = $sStart;
                        break 2;
                    }
                }
            }

            // Busca fim do bloco (fechamento/transição) até 60s depois
            $endIndex = min(count($sentences) - 1, $mentionIndex + 6);
            $endTime = (float) ($sentences[$mentionIndex]['end'] ?? ($mentionTime + 25));

            for ($j = $mentionIndex; $j <= $endIndex; $j++) {
                $sText = mb_strtolower($sentences[$j]['text'] ?? '');
                $sEnd = (float) ($sentences[$j]['end'] ?? 0);

                if (($sEnd - $startTime) > 75) break;

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

            $snippetArr = [];
            for ($k = $startIndex; $k <= $endIndex; $k++) {
                if (isset($sentences[$k]['text'])) {
                    $snippetArr[] = $sentences[$k]['text'];
                }
            }
            $snippet = implode(' ', $snippetArr);

            DB::table('live_items')
                ->where('id', $li->id)
                ->update([
                    'cut_start_sec' => $finalStart,
                    'cut_end_sec' => $finalEnd,
                    'transcription_snippet' => $snippet,
                    'updated_at' => now()
                ]);

            $updatedCount++;
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
     * Endpoint de Detecção Automática de Início e Fim por Código / Transcrição
     */
    public function autoDetectTimestamps(Request $request, $liveId)
    {
        $live = Live::findOrFail($liveId);
        $sentences = json_decode($live->transcription_raw, true) ?: [];

        // Se a transcrição estiver vazia, dispara transcrição assíncrona com auto_detect
        if (empty($sentences)) {
            $videoPath = $this->getLocalVideoPath($live);
            if ($videoPath) {
                \Illuminate\Support\Facades\Cache::put("live_transcription_status_{$liveId}", [
                    'status' => 'processing',
                    'progress' => 5,
                    'message' => 'Iniciando extração do áudio e detecção inteligente...'
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

        $results = $this->performAutoDetection($live);

        return response()->json([
            'success' => true,
            'message' => 'Minutagem detectada automaticamente para ' . count($results) . ' itens!',
            'updated_count' => count($results),
            'items' => $results
        ]);
    }


    /**
     * Extrai trecho do texto da transcrição correspondente ao intervalo de tempo especificado
     */
    protected function getSnippetForTimeRange(Live $live, float $start, float $end): string
    {
        $sentences = json_decode($live->transcription_raw, true) ?: [];
        if (empty($sentences)) {
            return '';
        }

        $matchedTexts = [];
        foreach ($sentences as $s) {
            $sStart = (float) ($s['start'] ?? 0);
            $sEnd = (float) ($s['end'] ?? 0);

            // Verifica se a frase intercepta o intervalo (com 1.0s de margem de tolerância)
            if ($sEnd >= ($start - 1.0) && $sStart <= ($end + 1.0)) {
                $text = trim($s['text'] ?? '');
                if (!empty($text)) {
                    $matchedTexts[] = $text;
                }
            }
        }

        return implode(' ', $matchedTexts);
    }

    /**
     * Salva Manualmente os Timestamps de um Item e Atualiza o Texto da Fala Correspondente
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

        $snippet = $this->getSnippetForTimeRange($live, $start, $end);

        DB::table('live_items')
            ->where('id', $liveItemId)
            ->where('live_id', $liveId)
            ->update([
                'cut_start_sec' => round($start, 2),
                'cut_end_sec' => round($end, 2),
                'transcription_snippet' => $snippet,
                'updated_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Minutagem e texto da fala correspondente atualizados com sucesso!',
            'cut_start_sec' => round($start, 2),
            'cut_end_sec' => round($end, 2),
            'cut_start_formatted' => $this->formatSecondsToTime($start),
            'cut_end_formatted' => $this->formatSecondsToTime($end),
            'duration_sec' => round($end - $start, 1),
            'snippet' => $snippet
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

        // 1. Tentar corte ultrarrápido sem perda (Stream Copy - ~0.2 segundos)
        $cmd = sprintf(
            'ffmpeg -ss %s -i %s -t %s -c copy -avoid_negative_ts make_zero -movflags +faststart -y %s 2>&1',
            escapeshellarg($start),
            escapeshellarg($inputPath),
            escapeshellarg($duration),
            escapeshellarg($outputPath)
        );

        Log::info("Executando FFmpeg (Fast Copy): " . $cmd);
        exec($cmd, $output, $returnCode);

        // 2. Fallback caso stream copy falhe: reencodificação ultrafast (1 a 2 segundos)
        if ($returnCode !== 0 || !file_exists($outputPath) || filesize($outputPath) < 5000) {
            $outputFallback = [];
            $cmdFallback = sprintf(
                'ffmpeg -ss %s -i %s -t %s -c:v libx264 -preset ultrafast -crf 24 -c:a aac -b:a 128k -movflags +faststart -y %s 2>&1',
                escapeshellarg($start),
                escapeshellarg($inputPath),
                escapeshellarg($duration),
                escapeshellarg($outputPath)
            );
            Log::info("Executando FFmpeg (Fallback Ultrafast): " . $cmdFallback);
            exec($cmdFallback, $outputFallback, $returnCode);
            $output = $outputFallback;
        }

        if ($returnCode !== 0 || !file_exists($outputPath) || filesize($outputPath) < 5000) {
            Log::error("Erro no FFmpeg: " . implode("\n", $output));
            return response()->json([
                'success' => false,
                'message' => 'Falha ao processar corte de vídeo via FFmpeg.',
                'debug' => implode("\n", array_slice($output, -10))
            ], 500);
        }

        $videoUrl = Storage::url($relativeStoragePath);

        // 3. Extrai thumbnail nítida no ponto ótimo do corte (~35% da duração ou +2.0s)
        $optimalOffset = min(3.0, max(0.5, round($duration * 0.35, 2)));
        $thumbTimestamp = round($start + $optimalOffset, 2);
        $thumbFilename = 'thumb_' . $codeClean . '_' . time() . '.jpg';
        $thumbPath = $outputDir . '/' . $thumbFilename;
        $relativeThumbPath = 'live_cuts/live_' . $liveId . '/' . $thumbFilename;

        $cmdThumb = sprintf(
            'ffmpeg -ss %s -i %s -vframes 1 -q:v 2 -y %s 2>&1',
            escapeshellarg($thumbTimestamp),
            escapeshellarg($inputPath),
            escapeshellarg($thumbPath)
        );
        exec($cmdThumb);

        $hasThumb = file_exists($thumbPath) && filesize($thumbPath) > 1000;
        $thumbUrl = $hasThumb ? Storage::url($relativeThumbPath) : null;

        DB::table('live_items')
            ->where('id', $liveItemId)
            ->update([
                'video_cut_path' => $relativeStoragePath,
                'video_cut_filename' => $filename,
                'video_cut_url' => $videoUrl,
                'video_cut_duration' => round($duration),
                'video_cut_status' => 'recorded',
                'video_cut_finished_at' => now(),
                'updated_at' => now()
            ]);

        // Vincula ou atualiza a mídia do tipo vídeo com thumbnail no cadastro do item
        if (!empty($liveItem->item_id)) {
            ItemMedia::updateOrCreate(
                [
                    'item_id' => $liveItem->item_id,
                    'media_type' => 'video'
                ],
                [
                    'url' => $relativeStoragePath,
                    'thumbnail_url' => $hasThumb ? $relativeThumbPath : null,
                    'position' => 99,
                    'is_cover' => false,
                    'alt_text' => 'Vídeo do produto na Live'
                ]
            );

            // Se o item não tiver imagem de capa, define essa thumbnail como foto principal
            $itemObj = Item::find($liveItem->item_id);
            if ($itemObj && empty($itemObj->image) && $hasThumb) {
                $itemObj->image = $relativeThumbPath;
                $itemObj->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Corte e Thumbnail gerados com sucesso!',
            'video_url' => $videoUrl,
            'thumbnail_url' => $thumbUrl,
            'duration' => $duration
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
        $thumbFilename = 'thumb_' . $codeClean . '_' . time() . '.jpg';
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
            'message' => 'Quadro capturado com sucesso como Thumbnail!',
            'thumbnail_url' => $thumbUrl,
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
        $items = DB::table('live_items')
            ->where('live_id', $liveId)
            ->whereNotNull('cut_start_sec')
            ->whereNotNull('cut_end_sec')
            ->get();

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

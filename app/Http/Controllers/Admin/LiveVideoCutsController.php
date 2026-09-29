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
                'items.name as item_name',
                'items.sku as item_sku',
                'items.codigo as item_codigo',
                'items.price as item_price',
                'items.image as item_image',
                'items.foto as item_foto',
                'users.name as user_full_name'
            )
            ->orderBy('live_items.id', 'asc');

        $liveItems = $query->get()->map(function ($row) {
            // Foto / Imagem do item
            $image = $row->item_image ?: $row->item_foto;
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
                'codigo_live' => $row->codigo_live ?: $row->item_sku,
                'item_name' => $row->item_name,
                'item_sku' => $row->item_sku,
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
            $path = $request->input('video_path_manual');
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
     * Algoritmo de Detecção Automática de Início e Fim por Código / Transcrição
     */
    public function autoDetectTimestamps(Request $request, $liveId)
    {
        $live = Live::findOrFail($liveId);
        $sentences = json_decode($live->transcription_raw, true) ?: [];

        $liveItems = DB::table('live_items')
            ->where('live_id', $liveId)
            ->get();

        if (empty($sentences)) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma transcrição encontrada para esta live. Adicione ou importe a transcrição primeiro.'
            ], 400);
        }

        $updatedCount = 0;
        $results = [];

        // Padrões de fala para Início (Abertura de Peça)
        $openingPatterns = [
            'olha essa', 'olha esse', 'olha que', 'meninas', 'agora vamos', 'vamos para',
            'próxima peça', 'próximo item', 'vou mostrar', 'essa daqui', 'esse daqui',
            'linda demais', 'maravilhosa', 'vestido', 'blusa', 'calça', 'conjunto', 'cropped',
            'camisa', 'jaqueta', 'saia', 'short', 'macacão', 'tamanho', 'tecido'
        ];

        // Padrões de fala para Fim (Fechamento de Peça)
        $closingPatterns = [
            'entregando', 'vou entregar', 'passando', 'próxima', 'próximo', 'anotou',
            'quem pegou', 'fechou', 'vendido', 'vai para', 'deixa eu passar', 'comenta código',
            'um beijo', 'boa noite', 'de onde você é'
        ];

        foreach ($liveItems as $li) {
            $code = trim(strtolower($li->codigo_live ?: ''));
            if (!$code) continue;

            // Encontrar a frase que menciona o código
            $mentionIndex = null;
            $mentionTime = null;

            foreach ($sentences as $idx => $s) {
                $textLower = mb_strtolower($s['text'] ?? '');
                // Busca código exato ou menção
                if (str_contains($textLower, $code) || str_contains($textLower, 'código ' . $code) || str_contains($textLower, 'codigo ' . $code)) {
                    $mentionIndex = $idx;
                    $mentionTime = (float) ($s['start'] ?? 0);
                    break;
                }
            }

            if ($mentionIndex === null) {
                continue; // Código não encontrado na transcrição
            }

            // 1. DEFINIR O INÍCIO (Start): Varrer para trás a partir da menção (até 60s antes)
            $startIndex = max(0, $mentionIndex - 5);
            $startTime = (float) ($sentences[$mentionIndex]['start'] ?? 0);

            for ($i = $mentionIndex; $i >= $startIndex; $i--) {
                $sText = mb_strtolower($sentences[$i]['text'] ?? '');
                $sStart = (float) ($sentences[$i]['start'] ?? 0);

                // Se a diferença de tempo passar de 60s, para
                if (($mentionTime - $sStart) > 60) break;

                $startTime = $sStart;

                // Se encontrou gatilho forte de abertura, esse é o início!
                foreach ($openingPatterns as $pat) {
                    if (str_contains($sText, $pat)) {
                        $startTime = $sStart;
                        break 2;
                    }
                }
            }

            // 2. DEFINIR O FIM (End): Varrer para frente a partir da menção (até 50s depois)
            $endIndex = min(count($sentences) - 1, $mentionIndex + 6);
            $endTime = (float) ($sentences[$mentionIndex]['end'] ?? ($mentionTime + 25));

            for ($j = $mentionIndex; $j <= $endIndex; $j++) {
                $sText = mb_strtolower($sentences[$j]['text'] ?? '');
                $sEnd = (float) ($sentences[$j]['end'] ?? 0);

                // Se a diferença de tempo passar de 75s desde o início, limita
                if (($sEnd - $startTime) > 75) break;

                $endTime = $sEnd;

                // Se encontrou gatilho de fechamento, crava o fim
                foreach ($closingPatterns as $cpat) {
                    if (str_contains($sText, $cpat)) {
                        $endTime = $sEnd;
                        break 2;
                    }
                }
            }

            // Ajuste de margem de segurança (0.3s antes e 0.5s depois)
            $finalStart = max(0, round($startTime - 0.3, 1));
            $finalEnd = round($endTime + 0.5, 1);

            // Coletar snippet da transcrição
            $snippetArr = [];
            for ($k = $startIndex; $k <= $endIndex; $k++) {
                if (isset($sentences[$k]['text'])) {
                    $snippetArr[] = $sentences[$k]['text'];
                }
            }
            $snippet = implode(' ', $snippetArr);

            // Atualizar no banco
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

        return response()->json([
            'success' => true,
            'message' => "Minutagem detectada automaticamente para {$updatedCount} itens!",
            'updated_count' => $updatedCount,
            'items' => $results
        ]);
    }

    /**
     * Salva Manualmente os Timestamps de um Item
     */
    public function saveItemTimestamp(Request $request, $liveId, $liveItemId)
    {
        $request->validate([
            'cut_start_sec' => 'required|numeric|min:0',
            'cut_end_sec' => 'required|numeric|gt:cut_start_sec',
        ]);

        $start = (float) $request->input('cut_start_sec');
        $end = (float) $request->input('cut_end_sec');

        DB::table('live_items')
            ->where('id', $liveItemId)
            ->where('live_id', $liveId)
            ->update([
                'cut_start_sec' => $start,
                'cut_end_sec' => $end,
                'updated_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Minutagem do corte salva com sucesso!',
            'cut_start_sec' => $start,
            'cut_end_sec' => $end,
            'cut_start_formatted' => $this->formatSecondsToTime($start),
            'cut_end_formatted' => $this->formatSecondsToTime($end),
            'duration_sec' => round($end - $start, 1)
        ]);
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

        // Criar diretório de saída
        $outputDir = storage_path('app/public/live_cuts/live_' . $liveId);
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $codeClean = preg_replace('/[^a-zA-Z0-9_-]/', '_', $liveItem->codigo_live ?: 'item_' . $liveItem->item_id);
        $filename = 'corte_' . $codeClean . '_' . time() . '.mp4';
        $outputPath = $outputDir . '/' . $filename;
        $relativeStoragePath = 'live_cuts/live_' . $liveId . '/' . $filename;

        $start = (float) $liveItem->cut_start_sec;
        $duration = max(1, round($liveItem->cut_end_sec - $start, 2));

        // Comando FFmpeg com corte rápido e reencodificação otimizada
        $cmd = sprintf(
            'ffmpeg -ss %s -i %s -t %s -c:v libx264 -preset fast -crf 23 -c:a aac -b:a 128k -movflags +faststart -y %s 2>&1',
            escapeshellarg($start),
            escapeshellarg($inputPath),
            escapeshellarg($duration),
            escapeshellarg($outputPath)
        );

        Log::info("Executando FFmpeg: " . $cmd);
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($outputPath)) {
            Log::error("Erro no FFmpeg: " . implode("\n", $output));
            return response()->json([
                'success' => false,
                'message' => 'Falha ao processar corte de vídeo via FFmpeg.',
                'debug' => implode("\n", array_slice($output, -10))
            ], 500);
        }

        $videoUrl = Storage::url($relativeStoragePath);

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

        return response()->json([
            'success' => true,
            'message' => 'Corte gerado com sucesso!',
            'video_url' => $videoUrl,
            'duration' => $duration
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

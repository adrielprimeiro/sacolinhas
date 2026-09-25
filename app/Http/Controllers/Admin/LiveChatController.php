<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Live;
use App\Models\LiveMessage;
use App\Models\LiveCodeRequest;
use App\Models\User;
use App\Models\Item;
use App\Models\Sacolinhas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class LiveChatController extends Controller
{
    /**
     * Exibe o painel de controle do chat da live
     */
    public function dashboard(Request $request)
    {
        $lives = Live::orderBy('id', 'desc')->limit(30)->get();
        $activeLive = null;
        
        $liveId = $request->query('live_id');
        if ($liveId) {
            $activeLive = Live::find($liveId);
        } else {
            $activeLive = Live::where('ativo', true)->orderBy('id', 'desc')->first();
        }

        return view('admin.lives.chat_dashboard', compact('lives', 'activeLive'));
    }

    /**
     * Exibe a tela exclusiva de Bipagem Contínua de QR Code com Pessoas Online
     */
    public function bipagem(Request $request)
    {
        $lives = Live::orderBy('id', 'desc')->limit(30)->get();
        $activeLive = null;
        
        $liveId = $request->query('live_id');
        if ($liveId) {
            $activeLive = Live::find($liveId);
        } else {
            $activeLive = Live::where('ativo', true)->orderBy('id', 'desc')->first();
        }

        return view('admin.lives.operator_bipagem', compact('lives', 'activeLive'));
    }

    /**
     * Exibe a página exclusiva de visualização do Chat da Transmissão (Modo Leitura / Display)
     */
    public function feed(Request $request)
    {
        $lives = Live::orderBy('id', 'desc')->limit(30)->get();
        $activeLive = null;
        
        $liveId = $request->query('live_id');
        if ($liveId) {
            $activeLive = Live::find($liveId);
        } else {
            $activeLive = Live::where('ativo', true)->orderBy('id', 'desc')->first();
        }

        $linkedLiveItems = [];
        if ($activeLive && Schema::hasTable('live_items')) {
            $hasCodigoLiveCol = Schema::hasColumn('live_items', 'codigo_live');
            $query = DB::table('live_items')
                ->join('items', 'live_items.item_id', '=', 'items.id')
                ->where('live_items.live_id', $activeLive->id)
                ->orderBy('live_items.id', 'desc');

            $selects = [
                'live_items.id as live_item_id',
                'live_items.created_at as linked_at',
                'items.id as item_id',
                'items.codigo',
                'items.nome_do_produto',
                'items.descricao',
                'items.tamanho',
                'items.marca',
                'items.cor',
                'items.preco'
            ];
            $hasBuyerCols = Schema::hasColumn('live_items', 'buyer_username');
            $hasVideoCutCols = Schema::hasColumn('live_items', 'video_cut_path');
            if ($hasCodigoLiveCol) {
                $selects[] = 'live_items.codigo_live';
            }
            if ($hasBuyerCols) {
                $selects[] = 'live_items.user_id as buyer_user_id';
                $selects[] = 'live_items.buyer_username';
                $selects[] = 'live_items.buyer_name';
                $selects[] = 'live_items.live_message_id';
            }
            if ($hasVideoCutCols) {
                $selects[] = 'live_items.video_cut_path';
                $selects[] = 'live_items.video_cut_filename';
                $selects[] = 'live_items.video_cut_duration';
                $selects[] = 'live_items.video_cut_status';
                $selects[] = 'live_items.video_cut_started_at';
                $selects[] = 'live_items.video_cut_finished_at';
                $selects[] = 'live_items.video_cut_trigger';
            }

            $linkedLiveItems = $query->select($selects)->get()->map(function($row) use ($hasCodigoLiveCol, $hasBuyerCols, $hasVideoCutCols) {
                return [
                    'id' => 'scan_db_' . $row->live_item_id,
                    'itemId' => $row->item_id,
                    'code' => $row->codigo,
                    'liveCode' => $hasCodigoLiveCol ? ($row->codigo_live ?? '') : '',
                    'buyerUserId' => $hasBuyerCols ? ($row->buyer_user_id ?? null) : null,
                    'buyerUsername' => $hasBuyerCols ? ($row->buyer_username ?? null) : null,
                    'buyerName' => $hasBuyerCols ? ($row->buyer_name ?? null) : null,
                    'liveMessageId' => $hasBuyerCols ? ($row->live_message_id ?? null) : null,
                    'videoCutPath' => $hasVideoCutCols ? ($row->video_cut_path ?? null) : null,
                    'videoCutFilename' => $hasVideoCutCols ? ($row->video_cut_filename ?? null) : null,
                    'videoCutDuration' => $hasVideoCutCols ? ($row->video_cut_duration ?? null) : null,
                    'videoCutStatus' => $hasVideoCutCols ? ($row->video_cut_status ?? null) : null,
                    'videoCutStartedAt' => $hasVideoCutCols ? ($row->video_cut_started_at ?? null) : null,
                    'videoCutFinishedAt' => $hasVideoCutCols ? ($row->video_cut_finished_at ?? null) : null,
                    'videoCutTrigger' => $hasVideoCutCols ? ($row->video_cut_trigger ?? null) : null,
                    'productName' => $row->nome_do_produto ?: 'Sem Nome',
                    'productDetails' => $row->descricao ?? '',
                    'tamanho' => $row->tamanho ?? '',
                    'marca' => $row->marca ?? '',
                    'cor' => $row->cor ?? '',
                    'productPrice' => 'R$ ' . number_format($row->preco ?? 0, 2, ',', '.'),
                    'time' => $row->linked_at ? date('H:i:s', strtotime($row->linked_at)) : '',
                    'source' => 'live'
                ];
            });
        }

        return view('admin.lives.chat_feed', compact('lives', 'activeLive', 'linkedLiveItems'));
    }

    /**
     * Vincula um item bipado à live atual e registra o código específico da live
     */
    public function linkItemLive(Request $request)
    {
        $request->validate([
            'live_id' => 'required|exists:lives,id',
            'item_id' => 'nullable|exists:items,id',
            'code' => 'nullable|string',
            'codigo_live' => 'nullable|string'
        ]);

        $liveId = $request->input('live_id');
        $itemId = $request->input('item_id');
        $codigoLive = trim((string) $request->input('codigo_live'));

        if (!$itemId && $request->filled('code')) {
            $code = trim($request->input('code'));
            $item = Item::where('codigo', $code)
                ->orWhere('codigo', mb_strtoupper($code, 'UTF-8'))
                ->orWhere('codigo', mb_strtolower($code, 'UTF-8'))
                ->orWhere('id', is_numeric($code) ? (int)$code : -1)
                ->first();
            if ($item) {
                $itemId = $item->id;
            }
        }

        if (!$itemId) {
            return response()->json([
                'success' => false,
                'message' => 'Item não encontrado para vincular à live.'
            ], 404);
        }

        $item = Item::find($itemId);
        $origem = $item->localizacao;

        // Verificar se este codigo_live já existe em outro item desta mesma live
        $duplicateWarning = null;
        if (!empty($codigoLive) && Schema::hasColumn('live_items', 'codigo_live')) {
            $duplicate = DB::table('live_items')
                ->join('items', 'live_items.item_id', '=', 'items.id')
                ->where('live_items.live_id', $liveId)
                ->where('live_items.item_id', '!=', $itemId)
                ->whereRaw('LOWER(TRIM(live_items.codigo_live)) = ?', [mb_strtolower($codigoLive, 'UTF-8')])
                ->select('items.id as item_id', 'items.codigo', 'items.nome_do_produto', 'live_items.codigo_live')
                ->first();

            if ($duplicate) {
                $duplicateWarning = [
                    'item_id' => $duplicate->item_id,
                    'code' => $duplicate->codigo,
                    'name' => $duplicate->nome_do_produto,
                    'codigo_live' => $duplicate->codigo_live
                ];
            }
        }

        $updateData = [
            'status_movimentacao' => 'enviado',
            'updated_at' => now(),
            'created_at' => now()
        ];
        if (Schema::hasColumn('live_items', 'codigo_live')) {
            $updateData['codigo_live'] = $codigoLive ?: null;
        }
        if ($origem && strtolower($origem) !== 'live') {
            $updateData['localizacao_origem'] = $origem;
        }

        DB::table('live_items')->updateOrInsert(
            ['live_id' => $liveId, 'item_id' => $itemId],
            $updateData
        );

        if (strtolower($item->localizacao ?? '') !== 'live' && strtolower($item->localizacao ?? '') !== 'sacolinha') {
            $item->localizacao = 'Live';
            $item->save();
        }

        return response()->json([
            'success' => true,
            'message' => $duplicateWarning 
                ? "Atenção: O código de live '{$codigoLive}' já está em uso na peça #{$duplicateWarning['code']}!" 
                : 'Item vinculado à live com sucesso!',
            'duplicate_warning' => $duplicateWarning,
            'data' => [
                'item_id' => $itemId,
                'live_id' => $liveId,
                'codigo_live' => $codigoLive
            ]
        ]);
    }

    /**
     * Remove a vinculação de um item com a live atual
     */
    public function unlinkItemLive(Request $request)
    {
        $request->validate([
            'live_id' => 'required|exists:lives,id',
            'item_id' => 'nullable|exists:items,id',
            'code' => 'nullable|string'
        ]);

        $liveId = $request->input('live_id');
        $itemId = $request->input('item_id');

        if (!$itemId && $request->filled('code')) {
            $code = trim($request->input('code'));
            $item = Item::where('codigo', $code)
                ->orWhere('codigo', mb_strtoupper($code, 'UTF-8'))
                ->orWhere('codigo', mb_strtolower($code, 'UTF-8'))
                ->orWhere('id', is_numeric($code) ? (int)$code : -1)
                ->first();
            if ($item) {
                $itemId = $item->id;
            }
        }

        if ($itemId && Schema::hasTable('live_items')) {
            DB::table('live_items')
                ->where('live_id', $liveId)
                ->where('item_id', $itemId)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Item removido da live com sucesso.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Item não encontrado para desvincular.'
        ], 404);
    }

    /**
     * Vincula um item da live a um comprador (e opcionalmente a uma mensagem do chat)
     */
    public function linkItemBuyer(Request $request)
    {
        $request->validate([
            'live_id' => 'required|exists:lives,id',
            'item_id' => 'required|exists:items,id',
            'username' => 'required|string',
            'buyer_name' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'message_id' => 'nullable|integer'
        ]);

        $liveId = $request->input('live_id');
        $itemId = $request->input('item_id');
        $username = trim((string) $request->input('username'));
        $buyerName = trim((string) $request->input('buyer_name'));
        $userId = $request->input('user_id') ?: null;
        $messageId = $request->input('message_id') ?: null;

        $item = Item::find($itemId);
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item não encontrado.'], 404);
        }

        // Se user_id não foi passado, tenta encontrar cliente pelo username
        if (!$userId) {
            $cleanUser = trim(ltrim($username, '@'));
            $matchedUser = User::where(function($q) use ($cleanUser) {
                $q->where('instagram', $cleanUser)
                  ->orWhere('tiktok', $cleanUser)
                  ->orWhere('apelido', $cleanUser)
                  ->orWhere('nome_cliente', $cleanUser)
                  ->orWhere('name', $cleanUser);
            })->first();
            if ($matchedUser) {
                $userId = $matchedUser->id;
                if (!$buyerName) {
                    $buyerName = $matchedUser->name;
                }
            }
        }

        // 1. Atualizar ou inserir em live_items
        $updateData = [
            'status_movimentacao' => 'enviado',
            'updated_at' => now()
        ];
        if (Schema::hasColumn('live_items', 'user_id')) {
            $updateData['user_id'] = $userId;
        }
        if (Schema::hasColumn('live_items', 'buyer_username')) {
            $updateData['buyer_username'] = $username;
        }
        if (Schema::hasColumn('live_items', 'buyer_name')) {
            $updateData['buyer_name'] = $buyerName ?: null;
        }
        if (Schema::hasColumn('live_items', 'live_message_id')) {
            $updateData['live_message_id'] = $messageId;
        }

        DB::table('live_items')->updateOrInsert(
            ['live_id' => $liveId, 'item_id' => $itemId],
            $updateData
        );

        // 2. Se houver messageId e a tabela live_messages tiver os campos, atualiza a mensagem
        if ($messageId && Schema::hasTable('live_messages')) {
            $msgUpdate = [];
            if (Schema::hasColumn('live_messages', 'linked_item_id')) {
                $msgUpdate['linked_item_id'] = $itemId;
            }
            if (Schema::hasColumn('live_messages', 'linked_code')) {
                $msgUpdate['linked_code'] = $item->codigo;
            }
            if (!empty($msgUpdate)) {
                DB::table('live_messages')->where('id', $messageId)->update($msgUpdate);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Item vinculado ao comprador com sucesso!',
            'data' => [
                'item_id' => $itemId,
                'live_id' => $liveId,
                'user_id' => $userId,
                'buyer_username' => $username,
                'buyer_name' => $buyerName,
                'message_id' => $messageId
            ]
        ]);
    }

    /**
     * Remove o comprador vinculado a um item da live
     */
    public function unlinkItemBuyer(Request $request)
    {
        $request->validate([
            'live_id' => 'required|exists:lives,id',
            'item_id' => 'nullable|exists:items,id',
            'message_id' => 'nullable|integer'
        ]);

        $liveId = $request->input('live_id');
        $itemId = $request->input('item_id');
        $messageId = $request->input('message_id');

        if (!$itemId && $messageId) {
            $existing = DB::table('live_items')->where('live_id', $liveId)->where('live_message_id', $messageId)->first();
            if ($existing) {
                $itemId = $existing->item_id;
            } else {
                if (Schema::hasTable('live_messages')) {
                    DB::table('live_messages')->where('id', $messageId)->update([
                        'linked_item_id' => null,
                        'linked_code' => null
                    ]);
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Desvinculado com sucesso.'
                ]);
            }
        }

        $existing = DB::table('live_items')->where('live_id', $liveId)->where('item_id', $itemId)->first();
        $messageId = $messageId ?: ($existing ? ($existing->live_message_id ?? null) : null);

        $updateData = ['updated_at' => now()];
        if (Schema::hasColumn('live_items', 'user_id')) $updateData['user_id'] = null;
        if (Schema::hasColumn('live_items', 'buyer_username')) $updateData['buyer_username'] = null;
        if (Schema::hasColumn('live_items', 'buyer_name')) $updateData['buyer_name'] = null;
        if (Schema::hasColumn('live_items', 'live_message_id')) $updateData['live_message_id'] = null;

        DB::table('live_items')->where('live_id', $liveId)->where('item_id', $itemId)->update($updateData);

        if ($messageId && Schema::hasTable('live_messages')) {
            $msgUpdate = [];
            if (Schema::hasColumn('live_messages', 'linked_item_id')) $msgUpdate['linked_item_id'] = null;
            if (Schema::hasColumn('live_messages', 'linked_code')) $msgUpdate['linked_code'] = null;
            if (!empty($msgUpdate)) {
                DB::table('live_messages')->where('id', $messageId)->update($msgUpdate);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Comprador desvinculado do item com sucesso.'
        ]);
    }

    /**
     * Inicia o corte de vídeo para a peça atual na live
     */
    public function startVideoCut(Request $request)
    {
        $request->validate([
            'live_id' => 'required|exists:lives,id',
            'item_id' => 'nullable',
            'code' => 'nullable|string',
            'codigo_live' => 'nullable|string'
        ]);

        $liveId = $request->input('live_id');
        $itemId = $request->input('item_id');
        $code = trim((string) $request->input('code'));
        $codigoLive = trim((string) $request->input('codigo_live'));

        if (!$itemId && $code) {
            $item = DB::table('items')->where('codigo', $code)->first();
            if ($item) $itemId = $item->id;
        }

        if (!$itemId && $codigoLive) {
            $liveItem = DB::table('live_items')
                ->where('live_id', $liveId)
                ->whereRaw('LOWER(TRIM(codigo_live)) = ?', [mb_strtolower($codigoLive, 'UTF-8')])
                ->first();
            if ($liveItem) $itemId = $liveItem->item_id;
        }

        if (!$itemId) {
            return response()->json(['success' => false, 'message' => 'Peça não identificada para iniciar o corte.'], 404);
        }

        if (Schema::hasTable('live_items') && Schema::hasColumn('live_items', 'video_cut_status')) {
            DB::table('live_items')->where('live_id', $liveId)->where('item_id', $itemId)->update([
                'video_cut_status' => 'recording',
                'video_cut_started_at' => now(),
                'updated_at' => now()
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Gravação do corte iniciada para a peça #' . ($code ?: $itemId),
            'item_id' => $itemId
        ]);
    }

    /**
     * Finaliza o corte de vídeo no OBS e salva os metadados na tabela live_items
     */
    public function finishVideoCut(Request $request)
    {
        $request->validate([
            'live_id' => 'required|exists:lives,id',
            'item_id' => 'nullable',
            'code' => 'nullable|string',
            'codigo_live' => 'nullable|string',
            'video_path' => 'nullable|string',
            'video_filename' => 'nullable|string',
            'duration' => 'nullable|numeric',
            'trigger' => 'nullable|string'
        ]);

        $liveId = $request->input('live_id');
        $itemId = $request->input('item_id');
        $code = trim((string) $request->input('code'));
        $codigoLive = trim((string) $request->input('codigo_live'));
        $videoPath = $request->input('video_path');
        $videoFilename = $request->input('video_filename');
        if (!$videoFilename && $videoPath) {
            $videoFilename = basename(str_replace('\\', '/', $videoPath));
        }
        $duration = $request->input('duration') !== null ? (int) round($request->input('duration')) : null;
        $trigger = $request->input('trigger', 'voice_ok');

        if (!$itemId && $code) {
            $item = DB::table('items')->where('codigo', $code)->first();
            if ($item) $itemId = $item->id;
        }

        if (!$itemId && $codigoLive) {
            $liveItem = DB::table('live_items')
                ->where('live_id', $liveId)
                ->whereRaw('LOWER(TRIM(codigo_live)) = ?', [mb_strtolower($codigoLive, 'UTF-8')])
                ->first();
            if ($liveItem) $itemId = $liveItem->item_id;
        }

        if (!$itemId && Schema::hasColumn('live_items', 'video_cut_status')) {
            $lastRec = DB::table('live_items')
                ->where('live_id', $liveId)
                ->where('video_cut_status', 'recording')
                ->orderBy('id', 'desc')
                ->first();
            if ($lastRec) $itemId = $lastRec->item_id;
        }

        if (!$itemId) {
            return response()->json(['success' => false, 'message' => 'Peça não identificada para associar o corte.'], 404);
        }

        $updateData = [
            'updated_at' => now()
        ];
        if (Schema::hasColumn('live_items', 'video_cut_status')) {
            $updateData['video_cut_status'] = 'recorded';
        }
        if (Schema::hasColumn('live_items', 'video_cut_path')) {
            $updateData['video_cut_path'] = $videoPath;
        }
        if (Schema::hasColumn('live_items', 'video_cut_filename')) {
            $updateData['video_cut_filename'] = $videoFilename;
        }
        if (Schema::hasColumn('live_items', 'video_cut_duration') && $duration !== null) {
            $updateData['video_cut_duration'] = $duration;
        }
        if (Schema::hasColumn('live_items', 'video_cut_finished_at')) {
            $updateData['video_cut_finished_at'] = now();
        }
        if (Schema::hasColumn('live_items', 'video_cut_trigger')) {
            $updateData['video_cut_trigger'] = $trigger;
        }

        DB::table('live_items')->where('live_id', $liveId)->where('item_id', $itemId)->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Corte de vídeo salvo e associado com sucesso à peça!',
            'item_id' => $itemId,
            'video_path' => $videoPath,
            'video_filename' => $videoFilename,
            'duration' => $duration
        ]);
    }

    /**
     * Retorna a lista completa de cortes e status de gravação de todos os itens da live
     */
    public function getVideoCuts(Request $request)
    {
        $liveId = $request->query('live_id');
        if (!$liveId) {
            $activeLive = Live::where('ativo', true)->orderBy('id', 'desc')->first();
            $liveId = $activeLive ? $activeLive->id : null;
        }

        if (!$liveId) {
            return response()->json(['success' => false, 'message' => 'Nenhuma live encontrada.'], 404);
        }

        $hasVideoCutCols = Schema::hasColumn('live_items', 'video_cut_path');
        $hasCodigoLiveCol = Schema::hasColumn('live_items', 'codigo_live');
        $hasBuyerCols = Schema::hasColumn('live_items', 'buyer_username');

        $query = DB::table('live_items')
            ->join('items', 'live_items.item_id', '=', 'items.id')
            ->where('live_items.live_id', $liveId)
            ->orderBy('live_items.id', 'asc');

        $selects = [
            'live_items.id as live_item_id',
            'live_items.created_at as scanned_at',
            'items.id as item_id',
            'items.codigo',
            'items.nome_do_produto',
            'items.descricao',
            'items.preco'
        ];

        if ($hasCodigoLiveCol) $selects[] = 'live_items.codigo_live';
        if ($hasBuyerCols) {
            $selects[] = 'live_items.buyer_username';
            $selects[] = 'live_items.buyer_name';
        }
        if ($hasVideoCutCols) {
            $selects[] = 'live_items.video_cut_path';
            $selects[] = 'live_items.video_cut_filename';
            $selects[] = 'live_items.video_cut_duration';
            $selects[] = 'live_items.video_cut_status';
            $selects[] = 'live_items.video_cut_started_at';
            $selects[] = 'live_items.video_cut_finished_at';
            $selects[] = 'live_items.video_cut_trigger';
        }

        $items = $query->select($selects)->get()->map(function($row) use ($hasCodigoLiveCol, $hasBuyerCols, $hasVideoCutCols) {
            return [
                'live_item_id' => $row->live_item_id,
                'item_id' => $row->item_id,
                'code' => $row->codigo,
                'live_code' => $hasCodigoLiveCol ? ($row->codigo_live ?? '') : '',
                'buyer_username' => $hasBuyerCols ? ($row->buyer_username ?? null) : null,
                'buyer_name' => $hasBuyerCols ? ($row->buyer_name ?? null) : null,
                'product_name' => $row->nome_do_produto,
                'product_price' => $row->preco,
                'video_cut_path' => $hasVideoCutCols ? ($row->video_cut_path ?? null) : null,
                'video_cut_filename' => $hasVideoCutCols ? ($row->video_cut_filename ?? null) : null,
                'video_cut_duration' => $hasVideoCutCols ? ($row->video_cut_duration ?? null) : null,
                'video_cut_status' => $hasVideoCutCols ? ($row->video_cut_status ?? 'none') : 'none',
                'video_cut_finished_at' => $hasVideoCutCols ? ($row->video_cut_finished_at ?? null) : null,
                'video_cut_trigger' => $hasVideoCutCols ? ($row->video_cut_trigger ?? null) : null
            ];
        });

        $totalItems = $items->count();
        $totalCutsRecorded = $items->filter(fn($i) => $i['video_cut_status'] === 'recorded')->count();
        $totalDurationSec = $items->sum('video_cut_duration');

        return response()->json([
            'success' => true,
            'live_id' => $liveId,
            'total_items' => $totalItems,
            'total_cuts_recorded' => $totalCutsRecorded,
            'total_duration_seconds' => $totalDurationSec,
            'cuts' => $items
        ]);
    }

    /**
     * Recebe mensagens do script do navegador ou extensão
     */
    public function receiveMessage(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept, Origin');
        }

        if (Cache::get('live_capture_paused', false)) {
            return response()->json(['success' => true, 'paused' => true])
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept, Origin');
        }

        // Extração infalível de payload (JSON string, body bruto, json() ou all())
        $payload = [];
        $raw = $request->getContent();
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                $decoded = json_decode(stripslashes($raw), true);
            }
            if (!is_array($decoded)) {
                $cleaned = preg_replace('/^["\']|["\']$/', '', trim($raw));
                $decoded = json_decode(stripslashes($cleaned), true);
            }
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }
        if (empty($payload)) {
            $payload = $request->json()->all();
        }
        if (empty($payload)) {
            $payload = $request->all();
        }

        Log::info('[LiveChat receiveMessage]', [
            'raw_length' => strlen($raw ?? ''),
            'raw_preview' => substr($raw ?? '', 0, 100),
            'payload_keys' => array_keys($payload)
        ]);

        $liveIdInput = $payload['live_id'] ?? $request->input('live_id');
        if (!$liveIdInput || $liveIdInput === 'auto' || !\App\Models\Live::where('id', $liveIdInput)->exists()) {
            $activeLive = \App\Models\Live::where('ativo', true)->orderBy('id', 'desc')->first() ?? \App\Models\Live::orderBy('id', 'desc')->first();
            $liveId = $activeLive ? $activeLive->id : null;
        } else {
            $liveId = $liveIdInput;
        }

        $plat = strtolower((string) ($payload['platform'] ?? $payload['source'] ?? $payload['provider'] ?? $request->input('platform', 'instagram')));
        if (str_contains($plat, 'tiktok')) {
            $plat = 'tiktok';
        } else {
            $plat = 'instagram';
        }
        if ($plat === 'instagram' && Cache::get('instagram_capture_stopped', false)) {
            return response()->json(['success' => true, 'stopped' => true])
                ->header('Access-Control-Allow-Origin', '*');
        }
        if ($plat === 'instagram') {
            Cache::put('insta_capture_active', true, 86400);
        }

        // Mapeamento nativo para payloads do Social Stream Ninja e extensões
        $username = $payload['username'] ?? $payload['author'] ?? $payload['chatname'] ?? $request->input('username', '');
        $message = $payload['message'] ?? $payload['chatmessage'] ?? $payload['text'] ?? $request->input('message', '');
        $avatarUrl = $payload['avatar_url'] 
            ?? $payload['profile_picture'] 
            ?? $payload['chatpic'] 
            ?? $payload['chatimg'] 
            ?? $payload['avatar'] 
            ?? $payload['photo'] 
            ?? $request->input('avatar_url', null);

        $cleanUsername = trim((string) $username);
        $messageText = trim((string) $message);

        if (empty($cleanUsername) || empty($messageText)) {
            return response()->json([
                'success' => false,
                'error' => 'Nome de usuário e mensagem são obrigatórios',
                'debug' => [
                    'raw_received' => $raw,
                    'payload_received' => $payload,
                ]
            ], 422)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept, Origin');
        }

        if (!$liveId) {
            return response()->json([
                'success' => false,
                'error' => 'Nenhuma live ativa encontrada'
            ], 422)
            ->header('Access-Control-Allow-Origin', '*');
        }

        $platform = $plat;
        // Persistir avatar permanentemente no disco e recuperar se já existir
        $avatarUrl = $this->persistUserAvatar($cleanUsername, $platform, $avatarUrl);
        $timestamp = $payload['timestamp'] ?? $request->input('timestamp');

        try {
            return DB::transaction(function () use ($liveId, $platform, $cleanUsername, $messageText, $avatarUrl, $timestamp) {
                // Evitar duplicidade técnica de leitura do DOM (mesmo usuário e texto em menos de 2 segundos)
                $existing = LiveMessage::where('live_id', $liveId)
                    ->where('plataforma', $platform)
                    ->where('username', $cleanUsername)
                    ->where('message', $messageText)
                    ->where('created_at', '>=', now()->subSeconds(2))
                    ->first();
                if ($existing) {
                    if ($avatarUrl && empty($existing->avatar_url)) {
                        $existing->update(['avatar_url' => $avatarUrl]);
                    }
                    return response()->json(['success' => true, 'duplicate' => true, 'data' => $existing]);
                }

                // 1. Salvar a mensagem no chat
                $liveMessage = LiveMessage::create([
                    'live_id' => $liveId,
                    'plataforma' => $platform,
                    'username' => $cleanUsername,
                    'message' => $messageText,
                    'avatar_url' => $avatarUrl,
                    'captured_at' => ($timestamp) ? date('Y-m-d H:i:s', strtotime($timestamp)) : now()
                ]);

                // 2. Tentar encontrar usuário correspondente no banco e atualizar photo se necessário
                $user = $this->findUserByUsername($cleanUsername, $platform);
                if ($user && empty($user->photo) && !empty($avatarUrl)) {
                    $uLower = strtolower($cleanUsername);
                    $filename = "avatars/{$uLower}.jpg";
                    if (Storage::disk('public')->exists($filename)) {
                        $user->update(['photo' => $filename]);
                    }
                }

                return response()->json([
                    'success' => true,
                    'message_id' => $liveMessage->id,
                    'matched_user' => $user ? $user->name : null,
                    'matched_codes' => []
                ])
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization, X-CSRF-Token');
            });
        } catch (\Exception $e) {
            Log::error("Erro ao processar mensagem do chat: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization, X-CSRF-Token');
        }
    }

    public function receiveMessageBatch(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept, Origin');
        }

        if (Cache::get('live_capture_paused', false)) {
            return response()->json(['success' => true, 'paused' => true])
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept, Origin');
        }

        $messages = $request->input('messages', []);
        if (!is_array($messages) || empty($messages)) {
            return response()->json(['success' => false, 'message' => 'Lote vazio'])
                ->header('Access-Control-Allow-Origin', '*');
        }

        $activeLive = \App\Models\Live::where('ativo', true)->orderBy('id', 'desc')->first() 
                   ?? \App\Models\Live::orderBy('id', 'desc')->first();
        if (!$activeLive) {
            return response()->json(['success' => false, 'message' => 'Nenhuma live ativa'])
                ->header('Access-Control-Allow-Origin', '*');
        }
        $liveId = $activeLive->id;

        $createdCount = 0;
        try {
            DB::transaction(function () use ($messages, $liveId, &$createdCount) {
                foreach ($messages as $item) {
                    if (empty($item['username']) || empty($item['message'])) continue;

                    $cleanUsername = trim($item['username']);
                    $messageText = trim($item['message']);
                    $plat = strtolower((string) ($item['platform'] ?? 'instagram'));
                    $platform = str_contains($plat, 'tiktok') ? 'tiktok' : 'instagram';
                    if ($platform === 'instagram' && Cache::get('instagram_capture_stopped', false)) continue;
                    if ($platform === 'instagram') Cache::put('insta_capture_active', true, 86400);

                    $rawAvatar = $item['avatar_url']
                        ?? $item['profile_picture']
                        ?? $item['chatpic']
                        ?? $item['chatimg']
                        ?? $item['avatar']
                        ?? $item['photo']
                        ?? null;

                    $avatarUrl = $this->persistUserAvatar($cleanUsername, $platform, $rawAvatar);

                    $existing = LiveMessage::where('live_id', $liveId)
                        ->where('plataforma', $platform)
                        ->where('username', $cleanUsername)
                        ->where('message', $messageText)
                        ->where('created_at', '>=', now()->subSeconds(2))
                        ->first();
                    if ($existing) {
                        if ($avatarUrl && empty($existing->avatar_url)) {
                            $existing->update(['avatar_url' => $avatarUrl]);
                        }
                        continue;
                    }

                    LiveMessage::create([
                        'live_id' => $liveId,
                        'plataforma' => $platform,
                        'username' => $cleanUsername,
                        'message' => $messageText,
                        'avatar_url' => $avatarUrl,
                        'captured_at' => now()
                    ]);
                    $createdCount++;
                }
            });
        } catch (\Exception $e) {
            Log::error("Erro no lote do chat: " . $e->getMessage());
        }

        return response()->json(['success' => true, 'processed' => $createdCount])
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization');
    }

    /**
     * Retorna os dados em tempo real para atualização do painel
     */
    public function getChatData(Request $request, $liveId)
    {
        // 1. Mensagens recentes (últimas 200)
        $rawMessages = LiveMessage::where('live_id', $liveId)
            ->orderBy('id', 'desc')
            ->limit(200)
            ->get();

        $userCounts = LiveMessage::where('live_id', $liveId)
            ->select('username', DB::raw('COUNT(*) as total_msgs'), DB::raw('SUM(CASE WHEN is_marked = 1 THEN 1 ELSE 0 END) as marked_msgs'))
            ->groupBy('username')
            ->get()
            ->keyBy('username');

        // 2. Pessoas online (quem comentou, ordenado por data mais recente)
        $onlineRaw = LiveMessage::where('live_id', $liveId)
            ->select('username', 'plataforma', DB::raw('MAX(created_at) as last_seen'), DB::raw('MAX(id) as max_id'))
            ->groupBy('username', 'plataforma')
            ->orderByDesc('max_id')
            ->get();

        $allUsernames = $onlineRaw->pluck('username')->merge($rawMessages->pluck('username'))->unique()->filter()->values()->toArray();

        $matchedUsersCollection = !empty($allUsernames) ? User::where(function($q) use ($allUsernames) {
            $q->whereIn('tiktok', $allUsernames)
              ->orWhereIn('instagram', $allUsernames)
              ->orWhereIn('apelido', $allUsernames)
              ->orWhereIn('nome_cliente', $allUsernames)
              ->orWhereIn('name', $allUsernames);
        })->get() : collect([]);

        // Construir mapa de avatares com resolução inteligente e suporte a storage local permanente
        $avatarMap = [];
        foreach ($allUsernames as $u) {
            $uClean = trim($u);
            $uLower = strtolower($uClean);
            
            // 1. Verifica se usuário cadastrado possui photo
            $uMatched = $matchedUsersCollection->first(fn($usr) => 
                strtolower($usr->instagram ?? '') === $uLower ||
                strtolower($usr->tiktok ?? '') === $uLower ||
                strtolower($usr->apelido ?? '') === $uLower ||
                strtolower($usr->nome_cliente ?? '') === $uLower ||
                strtolower($usr->name ?? '') === $uLower
            );

            if ($uMatched && !empty($uMatched->photo)) {
                if (str_starts_with($uMatched->photo, 'http') || str_starts_with($uMatched->photo, '/storage/')) {
                    $avatarMap[$uLower] = $uMatched->photo;
                    continue;
                }
                $avatarMap[$uLower] = asset('storage/' . $uMatched->photo);
                continue;
            }

            // 2. Verifica se existe arquivo salvo no storage local
            $filename = "avatars/{$uLower}.jpg";
            if (Storage::disk('public')->exists($filename)) {
                $avatarMap[$uLower] = asset('storage/' . $filename);
                if ($uMatched && empty($uMatched->photo)) {
                    $uMatched->update(['photo' => $filename]);
                }
                continue;
            }

            // 3. Cache de avatar
            $cached = Cache::get("avatar_{$uLower}");
            if ($cached) {
                $avatarMap[$uLower] = $cached;
                continue;
            }

            // 4. Fallback TikTok scraping cache
            $ttCached = Cache::get("tt_avatar_{$uLower}");
            if ($ttCached) {
                $avatarMap[$uLower] = $ttCached;
                continue;
            }

            // 5. Última URL salva em live_messages
            $lastMsgAvatar = LiveMessage::where('username', $uClean)
                ->whereNotNull('avatar_url')
                ->where('avatar_url', '!=', '')
                ->orderByDesc('id')
                ->value('avatar_url');

            if ($lastMsgAvatar) {
                $avatarMap[$uLower] = $lastMsgAvatar;
            }
        }

        // Mapear itens vinculados por mensagem diretamente da tabela live_items
        $linkedByMessage = [];
        if (Schema::hasTable('live_items') && Schema::hasColumn('live_items', 'live_message_id')) {
            $hasCodigoLiveCol = Schema::hasColumn('live_items', 'codigo_live');
            $selects = [
                'live_items.live_message_id',
                'items.id as item_id',
                'items.codigo',
                'items.nome_do_produto',
                'items.descricao',
                'items.tamanho',
                'items.cor',
                'items.marca',
                'items.preco'
            ];
            if ($hasCodigoLiveCol) {
                $selects[] = 'live_items.codigo_live';
            }
            $linkedByMessage = DB::table('live_items')
                ->join('items', 'live_items.item_id', '=', 'items.id')
                ->where('live_items.live_id', $liveId)
                ->whereNotNull('live_items.live_message_id')
                ->select($selects)
                ->get()
                ->keyBy('live_message_id');
        }

        // Enriquecer mensagens com avatar, cadastro e peça vinculada
        $messages = $rawMessages->map(function($msg) use ($avatarMap, $matchedUsersCollection, $linkedByMessage) {
            $cleanUser = trim($msg->username);
            $uLower = strtolower($cleanUser);

            $matchedUser = null;
            if ($msg->plataforma === 'tiktok') {
                $matchedUser = $matchedUsersCollection->first(fn($u) => 
                    strtolower($u->tiktok ?? '') === $uLower ||
                    strtolower($u->apelido ?? '') === $uLower ||
                    strtolower($u->nome_cliente ?? '') === $uLower ||
                    strtolower($u->name ?? '') === $uLower
                );
            } else {
                $matchedUser = $matchedUsersCollection->first(fn($u) => 
                    strtolower($u->instagram ?? '') === $uLower ||
                    strtolower($u->apelido ?? '') === $uLower ||
                    strtolower($u->nome_cliente ?? '') === $uLower ||
                    strtolower($u->name ?? '') === $uLower
                );
            }

            $avatar = $avatarMap[$uLower] ?? $msg->avatar_url ?? null;
            if (!$avatar && $matchedUser && !empty($matchedUser->photo)) {
                $avatar = str_starts_with($matchedUser->photo, 'http') || str_starts_with($matchedUser->photo, '/storage/') 
                    ? $matchedUser->photo 
                    : asset('storage/' . $matchedUser->photo);
            }

            $msg->avatar_url = $avatar;
            $msg->user_id = $matchedUser ? $matchedUser->id : null;
            $msg->user_name = $matchedUser ? $matchedUser->name : null;
            $msg->user_apelido = $matchedUser ? $matchedUser->apelido : null;
            $msg->user_whatsapp = $matchedUser ? ($matchedUser->whatsapp ?: $matchedUser->phone) : null;

            if (isset($linkedByMessage[$msg->id])) {
                $li = $linkedByMessage[$msg->id];
                $msg->linked_item_id = $li->item_id;
                $msg->linked_code = $li->codigo;
                $msg->linked_live_code = !empty($li->codigo_live) ? $li->codigo_live : null;
                $msg->linked_product_name = $li->nome_do_produto ?: 'Peça';
                $msg->linked_product_details = $li->descricao ?? '';
                $msg->linked_product_tamanho = $li->tamanho ?? '';
                $msg->linked_product_cor = $li->cor ?? '';
                $msg->linked_product_preco = $li->preco ? ('R$ ' . number_format($li->preco, 2, ',', '.')) : '';
            } else if (!isset($msg->linked_code) || empty($msg->linked_code)) {
                $msg->linked_item_id = null;
                $msg->linked_code = null;
                $msg->linked_live_code = null;
                $msg->linked_product_name = null;
                $msg->linked_product_details = null;
                $msg->linked_product_tamanho = null;
                $msg->linked_product_cor = null;
                $msg->linked_product_preco = null;
            }

            return $msg;
        })->reverse()->values();

        $markedMessages = $messages->filter(fn($m) => (bool)$m->is_marked)->values();

        $onlineUsers = [];
        foreach ($onlineRaw as $online) {
            $cleanUsername = trim($online->username);
            $uLower = strtolower($cleanUsername);
            $matchedUser = null;

            if ($online->plataforma === 'tiktok') {
                $matchedUser = $matchedUsersCollection->first(fn($u) => 
                    strtolower($u->tiktok ?? '') === $uLower ||
                    strtolower($u->apelido ?? '') === $uLower ||
                    strtolower($u->nome_cliente ?? '') === $uLower ||
                    strtolower($u->name ?? '') === $uLower
                );
            } else {
                $matchedUser = $matchedUsersCollection->first(fn($u) => 
                    strtolower($u->instagram ?? '') === $uLower ||
                    strtolower($u->apelido ?? '') === $uLower ||
                    strtolower($u->nome_cliente ?? '') === $uLower ||
                    strtolower($u->name ?? '') === $uLower
                );
            }

            $userAvatar = $avatarMap[$uLower] ?? null;
            if (!$userAvatar && $matchedUser && !empty($matchedUser->photo)) {
                $userAvatar = str_starts_with($matchedUser->photo, 'http') || str_starts_with($matchedUser->photo, '/storage/') 
                    ? $matchedUser->photo 
                    : asset('storage/' . $matchedUser->photo);
            }

            $onlineUsers[] = [
                'username' => $cleanUsername,
                'plataforma' => $online->plataforma,
                'last_seen' => $online->last_seen ? date('H:i:s', strtotime($online->last_seen)) : '',
                'max_id' => $online->max_id,
                'avatar_url' => $userAvatar,
                'user_id' => $matchedUser ? $matchedUser->id : null,
                'user_name' => $matchedUser ? $matchedUser->name : null,
                'user_apelido' => $matchedUser ? $matchedUser->apelido : null,
                'user_whatsapp' => $matchedUser ? ($matchedUser->whatsapp ?: $matchedUser->phone) : null,
                'total_msgs' => (int) ($userCounts[$cleanUsername]->total_msgs ?? 0),
                'marked_msgs' => (int) ($userCounts[$cleanUsername]->marked_msgs ?? 0),
            ];
        }

        usort($onlineUsers, function($a, $b) {
            return $b['max_id'] <=> $a['max_id'];
        });

        // 3. Fila de códigos solicitados (pendentes), agrupados por código e ordenados por ordem de chegada
        $codeRequests = LiveCodeRequest::with(['user', 'item'])
            ->where('live_id', $liveId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        $groupedRequests = [];
        foreach ($codeRequests as $req) {
            if (!isset($groupedRequests[$req->codigo])) {
                $groupedRequests[$req->codigo] = [
                    'codigo' => $req->codigo,
                    'item_id' => $req->item_id,
                    'item_nome' => $req->item->nome_do_produto,
                    'item_preco' => $req->item->preco,
                    'item_status' => $req->item->status,
                    'queue' => []
                ];
            }
            $groupedRequests[$req->codigo]['queue'][] = [
                'id' => $req->id,
                'username' => $req->username,
                'user_id' => $req->user_id,
                'user_name' => $req->user ? $req->user->name : null,
                'user_apelido' => $req->user ? $req->user->apelido : null,
                'message_text' => $req->message_text,
                'created_at' => $req->created_at->timezone('America/Sao_Paulo')->format('H:i:s')
            ];
        }

        // Reindexar o array agrupado para JSON
        $groupedRequests = array_values($groupedRequests);

        // 4. Totalizadores globais da live inteira
        $platformCounts = LiveMessage::where('live_id', $liveId)
            ->select('plataforma', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN is_marked = 1 THEN 1 ELSE 0 END) as marked'))
            ->groupBy('plataforma')
            ->get();

        $totalMessages = (int) $platformCounts->sum('total');
        $totalInsta = (int) ($platformCounts->firstWhere('plataforma', 'instagram')->total ?? 0);
        $totalTiktok = (int) ($platformCounts->firstWhere('plataforma', 'tiktok')->total ?? 0);
        $totalMarked = (int) $platformCounts->sum('marked');
        $totalRegisteredOnline = count(array_filter($onlineUsers, fn($u) => !empty($u['user_id'])));
        $totalRegisteredRecent = $messages->filter(fn($m) => !empty($m->user_id))->count();

        // 5. Totalizadores das sacolinhas desta live
        $sacolinhasTotals = Sacolinhas::withoutGlobalScopes()
            ->where('live_id', $liveId)
            ->where('status', '!=', 'pedido')
            ->selectRaw('COUNT(*) as total_itens, COALESCE(SUM(price * quantity), 0) as total_valor')
            ->first();

        $totalSacolinhasItens = (int) ($sacolinhasTotals->total_itens ?? 0);
        $totalSacolinhasValor = (float) ($sacolinhasTotals->total_valor ?? 0);

        $tiktokActive = Cache::get('tiktok_capture_active', true) && !Cache::get('tiktok_capture_stopped', false);



        return response()->json([
            'success' => true,
            'is_paused' => Cache::get('live_capture_paused', false),
            'insta_active' => !Cache::get('instagram_capture_stopped', false),
            'tiktok_active' => !Cache::get('tiktok_capture_stopped', false),
            'messages' => $messages,
            'marked_messages' => $markedMessages,
            'online_users' => $onlineUsers,
            'code_requests' => $groupedRequests,
            'stats' => [
                'total_messages' => $totalMessages,
                'total_instagram' => $totalInsta,
                'total_tiktok' => $totalTiktok,
                'total_marked' => $totalMarked,
                'total_registered' => $totalRegisteredRecent,
                'total_online' => count($onlineUsers),
                'total_registered_online' => $totalRegisteredOnline,
                'sacolinhas_itens' => $totalSacolinhasItens,
                'sacolinhas_valor' => $totalSacolinhasValor,
            ]
        ]);
    }

    /**
     * Salva ou recupera o avatar permanentemente no disco local e vincula ao cliente
     */
    public function persistUserAvatar($username, $platform = 'instagram', $avatarUrl = null, $userId = null)
    {
        $cleanUsername = trim($username);
        if (empty($cleanUsername)) return null;

        $uLower = strtolower($cleanUsername);
        // 1. Se já recebemos um avatarUrl novo nesta requisição
        if (!empty($avatarUrl)) {
            // Se for Base64 (data:image)
            if (str_starts_with($avatarUrl, 'data:image')) {
                try {
                    $data = substr($avatarUrl, strpos($avatarUrl, ',') + 1);
                    $decoded = base64_decode($data);
                    if ($decoded !== false && strlen($decoded) > 100) {
                        Storage::disk('public')->put($filename, $decoded);
                        $localUrl = asset('storage/' . $filename);
                        Cache::forever("avatar_{$uLower}", $localUrl);
                        $this->linkAvatarToUserRecord($cleanUsername, $platform, $filename, $userId);
                        return $localUrl;
                    }
                } catch (\Exception $e) {}
            }

            // Se for URL remota (Instagram CDN, TikTok CDN, etc.), retorna direto sem travar o servidor com download síncrono
            if (str_starts_with($avatarUrl, 'http://') || str_starts_with($avatarUrl, 'https://')) {
                return $avatarUrl;
            }
        }

        // 2. Se não veio avatar na mensagem, buscar nas fontes permanentes:
        // A) Se o arquivo local já existe no disco
        if (Storage::disk('public')->exists($filename)) {
            $localUrl = asset('storage/' . $filename);
            Cache::forever("avatar_{$uLower}", $localUrl);
            $this->linkAvatarToUserRecord($cleanUsername, $platform, $filename, $userId);
            return $localUrl;
        }

        // B) Se o usuário associado tem foto no cadastro
        $user = $userId ? User::find($userId) : $this->findUserByUsername($cleanUsername, $platform);
        if ($user && !empty($user->photo)) {
            if (str_starts_with($user->photo, 'http') || str_starts_with($user->photo, '/storage/')) {
                return $user->photo;
            }
            return asset('storage/' . $user->photo);
        }

        // C) Cache
        $cached = Cache::get("avatar_{$uLower}");
        if ($cached) return $cached;

        // D) Fallback TikTok scraping cache
        if ($platform === 'tiktok') {
            $ttCached = Cache::get("tt_avatar_{$uLower}");
            if ($ttCached) return $ttCached;
        }

        return null;
    }

    protected function linkAvatarToUserRecord($username, $platform, $photoPath, $userId = null)
    {
        try {
            $user = $userId ? User::find($userId) : $this->findUserByUsername($username, $platform);
            if ($user && (empty($user->photo) || str_contains($user->photo, 'placeholder'))) {
                $user->update(['photo' => $photoPath]);
            }
        } catch (\Exception $e) {}
    }

    protected function findUserByUsername($username, $platform = 'instagram')
    {
        $clean = trim($username);
        $uLower = strtolower($clean);

        if ($platform === 'tiktok') {
            return User::whereRaw('LOWER(tiktok) = ?', [$uLower])
                ->orWhereRaw('LOWER(apelido) = ?', [$uLower])
                ->orWhereRaw('LOWER(nome_cliente) = ?', [$uLower])
                ->orWhereRaw('LOWER(name) = ?', [$uLower])
                ->first();
        }

        return User::whereRaw('LOWER(instagram) = ?', [$uLower])
            ->orWhereRaw('LOWER(apelido) = ?', [$uLower])
            ->orWhereRaw('LOWER(nome_cliente) = ?', [$uLower])
            ->orWhereRaw('LOWER(name) = ?', [$uLower])
            ->first();
    }

    /**
     * Adiciona o item solicitado à sacola do cliente
     */
    public function addToBag(Request $request)
    {
        if (!$request->input('live_id') || $request->input('live_id') === 'null' || $request->input('live_id') === 'undefined') {
            $activeLive = Live::where('ativo', true)->orderBy('id', 'desc')->first() ?? Live::orderBy('id', 'desc')->first();
            if ($activeLive) {
                $request->merge(['live_id' => $activeLive->id]);
            }
        }

        $validated = $request->validate([
            'code_request_id' => 'nullable|exists:live_code_requests,id',
            'user_id' => 'required|exists:users,id',
            'item_id' => 'required|exists:items,id',
            'live_id' => 'required|exists:lives,id'
        ]);

        try {
            $msg = DB::transaction(function () use ($validated) {
                $item = Item::withoutGlobalScopes()->lockForUpdate()->findOrFail($validated['item_id']);
                $live = Live::findOrFail($validated['live_id']);

                $liveBrechoId = $live->brecho_id ?: 1;
                $itemBrechoId = $item->brecho_id ?: 1;

                if ($liveBrechoId != $itemBrechoId) {
                    throw new \Exception("Este produto (#{$item->codigo}) pertence a outro brechó e não pode entrar na live deste brechó!");
                }

                // Prevenção estrita de duplicidade na live
                $existing = Sacolinhas::where('item_id', $validated['item_id'])
                    ->where('live_id', $validated['live_id'])
                    ->first();

                if ($existing) {
                    if ($existing->user_id == $validated['user_id']) {
                        return 'Item já está na sacola desta cliente!';
                    } else {
                        $otherUser = User::find($existing->user_id);
                        $otherName = $otherUser ? $otherUser->name : "outro cliente";
                        throw new \Exception("Esta peça já foi bipada e está na sacola de {$otherName}!");
                    }
                }

                $price = $item->preco;
                if ($live->tipo_live === 'precinho') {
                    $price = $price * 0.5;
                }

                $brechoId = $liveBrechoId;

                // 1. Criar a sacolinha do cliente nesta live
                Sacolinhas::create([
                    'user_id' => $validated['user_id'],
                    'item_id' => $validated['item_id'],
                    'live_id' => $validated['live_id'],
                    'brecho_id' => $brechoId,
                    'price' => $price,
                    'add_at' => now(),
                    'quantity' => 1,
                    'status' => 'live'
                ]);

                // 2. Vincular cliente a este brechó
                DB::table('brecho_clientes')->updateOrInsert(
                    [
                        'brecho_id' => $brechoId,
                        'user_id' => $validated['user_id']
                    ],
                    [
                        'origem' => 'live',
                        'updated_at' => now(),
                    ]
                );

                // 3. Atualizar status do item
                $item->update(['status' => 'sacolinha']);

                // 4. Atualizar status do pedido de código se informado
                if (!empty($validated['code_request_id'])) {
                    LiveCodeRequest::where('id', $validated['code_request_id'])->update(['status' => 'added']);
                }

                return 'Item adicionado à sacola com sucesso!';
            });

            return response()->json(['success' => true, 'message' => $msg]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Ignora uma solicitação de código
     */
    public function ignoreRequest(Request $request)
    {
        $validated = $request->validate([
            'code_request_id' => 'required|exists:live_code_requests,id'
        ]);

        try {
            LiveCodeRequest::where('id', $validated['code_request_id'])->update(['status' => 'ignored']);
            return response()->json(['success' => true, 'message' => 'Solicitação ignorada.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Associa manualmente um username da live a um cliente do sistema
     */
    public function linkUser(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'platform' => 'required|in:instagram,tiktok',
            'user_id' => 'required|exists:users,id'
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $user = User::findOrFail($validated['user_id']);
                $username = trim($validated['username']);
                $uLower = strtolower($username);

                // 1. Atualizar o cadastro do usuário
                if ($validated['platform'] === 'tiktok') {
                    $user->update(['tiktok' => $username]);
                } else {
                    $user->update(['instagram' => $username]);
                }

                // 2. Se o usuário ainda não tiver foto, tentar associar o avatar salvo deste username
                if (empty($user->photo) || str_contains($user->photo, 'placeholder')) {
                    $filename = "avatars/{$uLower}.jpg";
                    if (Storage::disk('public')->exists($filename)) {
                        $user->update(['photo' => $filename]);
                        Cache::forever("avatar_{$uLower}", asset('storage/' . $filename));
                    } else {
                        $lastMsgAvatar = LiveMessage::where('username', $username)
                            ->whereNotNull('avatar_url')
                            ->where('avatar_url', '!=', '')
                            ->orderByDesc('id')
                            ->value('avatar_url');
                        if ($lastMsgAvatar) {
                            $this->persistUserAvatar($username, $validated['platform'], $lastMsgAvatar, $user->id);
                        }
                    }
                }

                // 3. Associar retroativamente todas as requisições de código desse username
                LiveCodeRequest::where('username', $username)
                    ->whereNull('user_id')
                    ->whereHas('liveMessage', function ($q) use ($validated) {
                        $q->where('plataforma', $validated['platform']);
                    })
                    ->update(['user_id' => $user->id]);
            });

            return response()->json(['success' => true, 'message' => 'Cliente associado com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function togglePause(Request $request)
    {
        $current = Cache::get('live_capture_paused', false);
        $new = !$current;
        Cache::put('live_capture_paused', $new);
        return response()->json(['success' => true, 'is_paused' => $new]);
    }

    public function toggleInstagram(Request $request)
    {
        $action = $request->input('action');
        if ($action === 'stop') {
            Cache::put('instagram_capture_stopped', true, 86400);
            Cache::put('insta_capture_active', false);
        } else {
            Cache::forget('instagram_capture_stopped');
            Cache::put('insta_capture_active', true, 86400);
        }
        return response()->json([
            'success' => true,
            'insta_active' => Cache::get('insta_capture_active', false) && !Cache::get('instagram_capture_stopped', false)
        ]);
    }

    public function toggleTiktok(Request $request)
    {
        $action = $request->input('action');
        $username = $request->input('username', '_minhamania');

        if ($action === 'stop') {
            Cache::put('tiktok_capture_stopped', true, 86400);
            Cache::put('tiktok_capture_active', false);
            try {
                Http::timeout(3)->post('http://127.0.0.1:3001/disconnect');
            } catch (\Exception $e) {}
        } else {
            Cache::forget('tiktok_capture_stopped');
            Cache::put('tiktok_capture_active', true, 86400);
            try {
                Http::timeout(5)->post('http://127.0.0.1:3001/connect', [
                    'username' => $username
                ]);
            } catch (\Exception $e) {}
        }

        return response()->json([
            'success' => true,
            'tiktok_active' => Cache::get('tiktok_capture_active', false) && !Cache::get('tiktok_capture_stopped', false)
        ]);
    }

    /**
     * Retorna a live ativa para o serviço TikTok Listener (Node.js)
     */
    public function getActiveTiktokLives()
    {
        if (Cache::get('tiktok_capture_stopped', false) || Cache::get('live_capture_paused', false)) {
            return response()->json([
                'success' => true,
                'active_live' => null
            ])->header('Access-Control-Allow-Origin', '*');
        }

        $activeLive = Live::where('ativo', true)->orderBy('id', 'desc')->first() 
                   ?? Live::orderBy('id', 'desc')->first();

        if (!$activeLive) {
            return response()->json([
                'success' => true,
                'active_live' => null
            ])->header('Access-Control-Allow-Origin', '*');
        }

        $tiktokUsername = config('app.tiktok_username', '_minhamania');

        return response()->json([
            'success' => true,
            'active_live' => [
                'username' => $tiktokUsername,
                'live_id' => $activeLive->id
            ]
        ])->header('Access-Control-Allow-Origin', '*');
    }


    public function toggleMarkMessage(Request $request)
    {
        $validated = $request->validate([
            'message_id' => 'required|exists:live_messages,id'
        ]);

        $message = LiveMessage::findOrFail($validated['message_id']);
        $message->is_marked = !$message->is_marked;
        $message->save();

        return response()->json([
            'success' => true,
            'is_marked' => $message->is_marked,
            'message_id' => $message->id
        ]);
    }

    public function updateUserPhone(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'phone' => 'required|string|min:8'
        ]);

        $user = User::findOrFail($request->input('user_id'));
        $cleanPhone = preg_replace('/\D/', '', $request->input('phone'));

        $user->whatsapp = $cleanPhone;
        $user->phone = $cleanPhone;
        $user->save();

        return response()->json([
            'success' => true,
            'phone' => $cleanPhone,
            'message' => 'WhatsApp atualizado com sucesso!'
        ]);
    }

    /**
     * Resolve o avatar do TikTok em tempo real via scraper mobile
     */
    public static function resolveTikTokAvatar($username)
    {
        $cleanUser = ltrim(trim($username), '@');
        if (empty($cleanUser)) return null;

        $cacheKey = "tt_avatar_" . strtolower($cleanUser);
        return Cache::remember($cacheKey, 86400 * 7, function () use ($cleanUser, $username) {
            // 1. Verificar se já temos em alguma mensagem anterior
            $existing = LiveMessage::where('username', $username)
                ->whereNotNull('avatar_url')
                ->where('avatar_url', '!=', '')
                ->orderByDesc('id')
                ->value('avatar_url');
            if ($existing) return $existing;

            // 2. Tentar scraper mobile do TikTok
            try {
                $url = "https://www.tiktok.com/@" . $cleanUser;
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1');
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Upgrade-Insecure-Requests: 1'
                ]);
                $html = curl_exec($ch);
                curl_close($ch);

                $avatar = null;
                if (preg_match('/"avatarLarger"\s*:\s*"([^"]+)"/', $html, $m)) {
                    $avatar = json_decode('"' . $m[1] . '"');
                } elseif (preg_match('/"avatarMedium"\s*:\s*"([^"]+)"/', $html, $m)) {
                    $avatar = json_decode('"' . $m[1] . '"');
                } elseif (preg_match('/"avatarThumb"\s*:\s*"([^"]+)"/', $html, $m)) {
                    $avatar = json_decode('"' . $m[1] . '"');
                } elseif (preg_match('/<meta\s+property="og:image"\s+content="([^"]+)"/', $html, $m)) {
                    $avatar = $m[1];
                }

                if ($avatar) {
                    $avatar = html_entity_decode((string)$avatar, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    LiveMessage::where('username', $username)
                        ->where(function($q) {
                            $q->whereNull('avatar_url')->orWhere('avatar_url', '');
                        })
                        ->update(['avatar_url' => $avatar]);
                    return $avatar;
                }
            } catch (\Exception $e) {}

            return null;
        });
    }
}

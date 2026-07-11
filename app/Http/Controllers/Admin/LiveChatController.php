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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
     * Recebe mensagens do script do navegador
     */
    public function receiveMessage(Request $request)
    {
        if (Cache::get('live_capture_paused', false)) {
            return response()->json(['success' => true, 'paused' => true])
                ->header('Access-Control-Allow-Origin', '*');
        }

        if (!$request->input('live_id') || $request->input('live_id') === 'auto' || !\App\Models\Live::where('id', $request->input('live_id'))->exists()) {
            $activeLive = \App\Models\Live::where('ativo', true)->orderBy('id', 'desc')->first() ?? \App\Models\Live::orderBy('id', 'desc')->first();
            if ($activeLive) {
                $request->merge(['live_id' => $activeLive->id]);
            }
        }

        $plat = strtolower((string) $request->input('platform', 'instagram'));
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
        $request->merge(['platform' => $plat]);

        $validated = $request->validate([
            'live_id' => 'required|exists:lives,id',
            'platform' => 'required|in:instagram,tiktok',
            'username' => 'required|string',
            'message' => 'required|string',
            'timestamp' => 'nullable|string'
        ]);

        $cleanUsername = trim($validated['username']);
        $messageText = trim($validated['message']);
        $platform = $validated['platform'];
        $liveId = $validated['live_id'];

        try {
            return DB::transaction(function () use ($liveId, $platform, $cleanUsername, $messageText, $validated) {
                // Evitar duplicidade técnica de leitura do DOM (mesmo usuário e texto em menos de 2 segundos)
                $existing = LiveMessage::where('live_id', $liveId)
                    ->where('plataforma', $platform)
                    ->where('username', $cleanUsername)
                    ->where('message', $messageText)
                    ->where('created_at', '>=', now()->subSeconds(2))
                    ->first();
                if ($existing) {
                    return response()->json(['success' => true, 'duplicate' => true, 'data' => $existing]);
                }

                // 1. Salvar a mensagem no chat
                $liveMessage = LiveMessage::create([
                    'live_id' => $liveId,
                    'plataforma' => $platform,
                    'username' => $cleanUsername,
                    'message' => $messageText,
                    'captured_at' => (isset($validated['timestamp']) && $validated['timestamp']) ? date('Y-m-d H:i:s', strtotime($validated['timestamp'])) : now()
                ]);

                // 2. Tentar encontrar usuário correspondente no banco
                $user = null;
                if ($platform === 'tiktok') {
                    $user = User::where('tiktok', $cleanUsername)->first()
                        ?? User::where('apelido', $cleanUsername)->first()
                        ?? User::where('name', $cleanUsername)->first();
                } else {
                    $user = User::where('instagram', $cleanUsername)->first()
                        ?? User::where('apelido', $cleanUsername)->first()
                        ?? User::where('name', $cleanUsername)->first();
                }

                // 3. Escanear a mensagem buscando códigos de produtos (Desativado temporariamente conforme solicitação)
                $matchedCodes = [];
                /*
                preg_match_all('/#?([a-zA-Z0-9-]+)/', $messageText, $matches);
                if (!empty($matches[1])) {
                    $candidates = array_unique($matches[1]);
                    foreach ($candidates as $candidate) {
                        // Verifica se existe um item com esse código
                        $item = Item::where('codigo', $candidate)->first();
                        if ($item) {
                            // Registra o pedido de código
                            LiveCodeRequest::create([
                                'live_id' => $liveId,
                                'live_message_id' => $liveMessage->id,
                                'username' => $cleanUsername,
                                'user_id' => $user ? $user->id : null,
                                'item_id' => $item->id,
                                'codigo' => $item->codigo,
                                'message_text' => $messageText,
                                'status' => 'pending'
                            ]);
                            $matchedCodes[] = $item->codigo;
                        }
                    }
                }
                */

                return response()->json([
                    'success' => true,
                    'message_id' => $liveMessage->id,
                    'matched_user' => $user ? $user->name : null,
                    'matched_codes' => $matchedCodes
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
        if (Cache::get('live_capture_paused', false)) {
            return response()->json(['success' => true, 'paused' => true])
                ->header('Access-Control-Allow-Origin', '*');
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

                    $existing = LiveMessage::where('live_id', $liveId)
                        ->where('plataforma', $platform)
                        ->where('username', $cleanUsername)
                        ->where('message', $messageText)
                        ->where('created_at', '>=', now()->subSeconds(2))
                        ->first();
                    if ($existing) continue;

                    LiveMessage::create([
                        'live_id' => $liveId,
                        'plataforma' => $platform,
                        'username' => $cleanUsername,
                        'message' => $messageText,
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
        // 1. Mensagens recentes (últimas 100)
        $messages = LiveMessage::where('live_id', $liveId)
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get()
            ->reverse()
            ->values();

        // 2. Pessoas online (quem comentou, ordenado por data mais recente)
        $onlineRaw = LiveMessage::where('live_id', $liveId)
            ->select('username', 'plataforma', DB::raw('MAX(created_at) as last_seen'), DB::raw('MAX(id) as max_id'))
            ->groupBy('username', 'plataforma')
            ->orderByDesc('max_id')
            ->get();

        $allUsernames = $onlineRaw->pluck('username')->unique()->filter()->values()->toArray();
        $matchedUsersCollection = !empty($allUsernames) ? User::whereIn('tiktok', $allUsernames)
            ->orWhereIn('instagram', $allUsernames)
            ->orWhereIn('apelido', $allUsernames)
            ->orWhereIn('name', $allUsernames)
            ->get() : collect([]);

        $onlineUsers = [];
        foreach ($onlineRaw as $online) {
            $cleanUsername = trim($online->username);
            $matchedUser = null;

            if ($online->plataforma === 'tiktok') {
                $matchedUser = $matchedUsersCollection->firstWhere('tiktok', $cleanUsername)
                    ?? $matchedUsersCollection->firstWhere('apelido', $cleanUsername)
                    ?? $matchedUsersCollection->firstWhere('name', $cleanUsername);
            } else {
                $matchedUser = $matchedUsersCollection->firstWhere('instagram', $cleanUsername)
                    ?? $matchedUsersCollection->firstWhere('apelido', $cleanUsername)
                    ?? $matchedUsersCollection->firstWhere('name', $cleanUsername);
            }

            $onlineUsers[] = [
                'username' => $cleanUsername,
                'plataforma' => $online->plataforma,
                'last_seen' => $online->last_seen ? date('H:i:s', strtotime($online->last_seen)) : '',
                'max_id' => $online->max_id,
                'user_id' => $matchedUser ? $matchedUser->id : null,
                'user_name' => $matchedUser ? $matchedUser->name : null,
                'user_apelido' => $matchedUser ? $matchedUser->apelido : null,
                'user_whatsapp' => $matchedUser ? $matchedUser->whatsapp : null,
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

        return response()->json([
            'success' => true,
            'is_paused' => Cache::get('live_capture_paused', false),
            'insta_active' => Cache::get('insta_capture_active', false) && !Cache::get('instagram_capture_stopped', false),
            'messages' => $messages,
            'online_users' => $onlineUsers,
            'code_requests' => $groupedRequests
        ]);
    }

    /**
     * Adiciona o item solicitado à sacola do cliente
     */
    public function addToBag(Request $request)
    {
        $validated = $request->validate([
            'code_request_id' => 'nullable|exists:live_code_requests,id',
            'user_id' => 'required|exists:users,id',
            'item_id' => 'required|exists:items,id',
            'live_id' => 'required|exists:lives,id'
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $item = Item::findOrFail($validated['item_id']);

                // 1. Criar ou atualizar a sacolinha do cliente nesta live
                Sacolinhas::updateOrCreate(
                    [
                        'user_id' => $validated['user_id'],
                        'item_id' => $validated['item_id'],
                        'live_id' => $validated['live_id']
                    ],
                    [
                        'price' => $item->preco,
                        'add_at' => now(),
                        'quantity' => 1,
                        'status' => 'live'
                    ]
                );

                // 2. Atualizar status do item
                $item->update(['status' => 'sacolinha']);

                // 3. Atualizar status do pedido de código se informado
                if (!empty($validated['code_request_id'])) {
                    LiveCodeRequest::where('id', $validated['code_request_id'])->update(['status' => 'added']);
                }
            });

            return response()->json(['success' => true, 'message' => 'Item adicionado à sacola com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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

                // 1. Atualizar o cadastro do usuário
                if ($validated['platform'] === 'tiktok') {
                    $user->update(['tiktok' => $username]);
                } else {
                    $user->update(['instagram' => $username]);
                }

                // 2. Associar retroativamente todas as requisições de código desse username
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
}

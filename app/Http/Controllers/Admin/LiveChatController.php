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
                // 1. Salvar a mensagem no chat
                $liveMessage = LiveMessage::create([
                    'live_id' => $liveId,
                    'plataforma' => $platform,
                    'username' => $cleanUsername,
                    'message' => $messageText,
                    'captured_at' => $validated['timestamp'] ? date('Y-m-d H:i:s', strtotime($validated['timestamp'])) : now()
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

                // 3. Escanear a mensagem buscando códigos de produtos
                // Encontrar padrões como "#45" ou "45" ou "quero 45"
                preg_match_all('/#?([a-zA-Z0-9-]+)/', $messageText, $matches);
                $matchedCodes = [];

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

                return response()->json([
                    'success' => true,
                    'message_id' => $liveMessage->id,
                    'matched_user' => $user ? $user->name : null,
                    'matched_codes' => $matchedCodes
                ]);
            });
        } catch (\Exception $e) {
            Log::error("Erro ao processar mensagem do chat: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
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
            ->select('username', 'plataforma', DB::raw('MAX(created_at) as last_seen'))
            ->groupBy('username', 'plataforma')
            ->orderBy('last_seen', 'desc')
            ->get();

        $onlineUsers = [];
        foreach ($onlineRaw as $online) {
            $cleanUsername = $online->username;
            $matchedUser = null;

            if ($online->plataforma === 'tiktok') {
                $matchedUser = User::where('tiktok', $cleanUsername)->first()
                    ?? User::where('apelido', $cleanUsername)->first()
                    ?? User::where('name', $cleanUsername)->first();
            } else {
                $matchedUser = User::where('instagram', $cleanUsername)->first()
                    ?? User::where('apelido', $cleanUsername)->first()
                    ?? User::where('name', $cleanUsername)->first();
            }

            $onlineUsers[] = [
                'username' => $cleanUsername,
                'plataforma' => $online->plataforma,
                'last_seen' => $online->last_seen ? date('H:i:s', strtotime($online->last_seen)) : '',
                'user_id' => $matchedUser ? $matchedUser->id : null,
                'user_name' => $matchedUser ? $matchedUser->name : null,
                'user_apelido' => $matchedUser ? $matchedUser->apelido : null,
                'user_whatsapp' => $matchedUser ? $matchedUser->whatsapp : null,
            ];
        }

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
            'code_request_id' => 'required|exists:live_code_requests,id',
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

                // 3. Atualizar status do pedido de código para adicionado
                LiveCodeRequest::where('id', $validated['code_request_id'])->update(['status' => 'added']);
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
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvolutionMessage;
use App\Models\User;
use App\Services\Whatsapp\EvolutionApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EvolutionChatController extends Controller
{
    protected EvolutionApiService $evolution;

    public function __construct(EvolutionApiService $evolution)
    {
        $this->evolution = $evolution;
    }

    public function index()
    {
        return view('admin.chat_evolution');
    }

    /**
     * Retorna status da conexão e QR Code
     */
    public function getStatusAndQr()
    {
        $status = $this->evolution->getConnectionStatus();
        $state = $status['state'] ?? 'disconnected';

        $qrData = null;
        if ($state !== 'open' && $state !== 'connected') {
            $qrData = $this->evolution->getOrConnectQrCode();
        }

        return response()->json([
            'success' => true,
            'state' => $state,
            'qr' => $qrData,
        ]);
    }

    /**
     * Força geração de novo QR Code
     */
    public function refreshQr()
    {
        $qrData = $this->evolution->getOrConnectQrCode();
        return response()->json($qrData);
    }

    /**
     * Desconecta a sessão atual
     */
    public function logout()
    {
        $res = $this->evolution->logout();
        return response()->json($res);
    }

    /**
     * Lista conversas
     */
    public function getConversations()
    {
        $subLastMessage = DB::table('evolution_messages')
            ->select('user_id', DB::raw('MAX(id) as last_message_id'))
            ->whereNotNull('user_id')
            ->groupBy('user_id');

        $conversations = DB::table('evolution_messages as em')
            ->joinSub($subLastMessage, 'last_msg', 'em.id', '=', 'last_msg.last_message_id')
            ->join('users', 'users.id', '=', 'em.user_id')
            ->select(
                'em.user_id',
                'users.name as user_name',
                'users.whatsapp as user_whatsapp',
                'em.body as last_message_body',
                'em.created_at as last_message_at',
                'em.direction as last_message_direction',
                'em.status as last_message_status',
                DB::raw("CASE WHEN em.media_url IS NOT NULL AND em.media_url != '' THEN 1 ELSE 0 END as last_message_has_media")
            )
            ->orderBy('em.created_at', 'desc')
            ->get();

        $conversations->each(function ($conv) {
            $conv->unread_count = DB::table('evolution_messages')
                ->where('user_id', $conv->user_id)
                ->where('direction', 'inbound')
                ->where('status', '!=', 'read')
                ->count();
        });

        return response()->json($conversations);
    }

    /**
     * Retorna histórico de mensagens de um usuário
     */
    public function getMessages($userId)
    {
        $userId = (int) $userId;
        $limit = (int) request()->query('limit', 50);

        $messages = DB::table('evolution_messages')
            ->leftJoin('users as admin_user', 'evolution_messages.admin_id', '=', 'admin_user.id')
            ->where('evolution_messages.user_id', $userId)
            ->orderBy('evolution_messages.created_at', 'desc')
            ->take($limit)
            ->select('evolution_messages.*', 'admin_user.name as admin_name')
            ->get()
            ->reverse()
            ->values();

        // Marca como lidas
        DB::table('evolution_messages')
            ->where('user_id', $userId)
            ->where('direction', 'inbound')
            ->where('status', '!=', 'read')
            ->update(['status' => 'read']);

        return response()->json($messages);
    }

    /**
     * Envia mensagem via Evolution API
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'body' => 'nullable|string|max:4000',
            'file' => 'nullable|file|max:25600',
        ]);

        $userId = (int) $request->input('user_id');
        $body = trim((string) $request->input('body', ''));

        if ($body === '' && !$request->hasFile('file')) {
            return response()->json(['success' => false, 'error' => 'Mensagem ou arquivo obrigatório.'], 422);
        }

        $user = User::find($userId);
        if (!$user || !$user->whatsapp) {
            return response()->json(['success' => false, 'error' => 'Usuário sem telefone cadastrado.'], 404);
        }

        $adminId = auth()->id();
        $mediaUrl = null;
        $mediaType = null;
        $res = null;

        // Se tiver anexo
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $path = $file->store('evolution_media', 'public');
            $mediaUrl = asset('storage/' . $path);

            $mime = $file->getClientMimeType();
            $mediaType = match (true) {
                str_contains($mime, 'image') => 'image',
                str_contains($mime, 'audio') => 'audio',
                str_contains($mime, 'video') => 'video',
                default => 'document',
            };

            $res = $this->evolution->sendMedia($user->whatsapp, $mediaUrl, $body, $mediaType, $fileName);
        } else {
            $res = $this->evolution->sendText($user->whatsapp, $body);
        }

        $status = ($res['success'] ?? false) ? 'sent' : 'failed';
        $messageId = $res['message_id'] ?? null;
        $failedReason = ($status === 'failed') ? ($res['error'] ?? 'Falha no envio') : null;

        $msg = EvolutionMessage::create([
            'user_id' => $userId,
            'admin_id' => $adminId,
            'direction' => 'outbound',
            'status' => $status,
            'message_id' => $messageId,
            'from' => 'evolution',
            'to' => $user->whatsapp,
            'body' => $body,
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
            'failed_reason' => $failedReason,
            'raw_payload' => $res,
        ]);

        return response()->json([
            'success' => $status === 'sent',
            'message' => $msg,
            'error' => $failedReason,
        ]);
    }
}

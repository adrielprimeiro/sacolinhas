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
        $user = auth()->user();
        abort_if($user && !empty($user->brecho_id) && (int) $user->brecho_id > 1, 403, 'Funcionalidade exclusiva da Minha Mania (Matriz).');
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
            'severino_config' => \App\Services\Ai\SeverinoWhatsappAgentService::getConfig(),
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
     * Lista conversas (com atribuição igual ao outro chat — inclui Severino IA!)
     */
    public function getConversations()
    {
        if ($resp = $this->ensureMatrizOnly()) {
            return $resp;
        }

        $authAdmin = auth()->user();
        $isMaster = $authAdmin && $authAdmin->is_admin == 1 && $authAdmin->role === 'admin_master';
        $hasAssignTable = DB::getSchemaBuilder()->hasTable('evolution_chat_assignments');

        // Identificador único da conversa: remote_jid (ou fallback para user_id/from)
        $subLastMessage = DB::table('evolution_messages')
            ->select(
                DB::raw("COALESCE(remote_jid, CAST(user_id AS CHAR), `from`) as conv_key"),
                DB::raw('MAX(id) as last_message_id')
            )
            ->groupBy(DB::raw("COALESCE(remote_jid, CAST(user_id AS CHAR), `from`)"));

        $query = DB::table('evolution_messages as em')
            ->joinSub($subLastMessage, 'last_msg', 'em.id', '=', 'last_msg.last_message_id')
            ->leftJoin('users', 'users.id', '=', 'em.user_id');

        $selectCols = [
            'em.id as message_id',
            'em.user_id',
            'em.remote_jid',
            'em.push_name',
            'em.from',
            'em.to',
            'users.name as user_name',
            'users.whatsapp as user_whatsapp',
            'em.body as last_message_body',
            'em.created_at as last_message_at',
            'em.direction as last_message_direction',
            'em.status as last_message_status',
            DB::raw("CASE WHEN em.media_url IS NOT NULL AND em.media_url != '' THEN 1 ELSE 0 END as last_message_has_media"),
        ];

        if ($hasAssignTable) {
            $query->leftJoin('evolution_chat_assignments as eca', 'eca.remote_jid', '=', 'em.remote_jid')
                  ->leftJoin('users as assigned_admin', 'assigned_admin.id', '=', 'eca.assigned_admin_id');
            $selectCols[] = 'eca.assigned_type';
            $selectCols[] = 'eca.assigned_admin_id';
            $selectCols[] = 'assigned_admin.name as assigned_admin_name';

            // Atendente não-master só enxerga conversas atribuídas a ele (idêntico ao outro chat)
            if (!$isMaster) {
                $query->where('eca.assigned_type', 'admin')
                      ->where('eca.assigned_admin_id', $authAdmin->id);
            }
        } elseif (!$isMaster) {
            return response()->json([]);
        }

        $conversations = $query->select($selectCols)
            ->orderBy('em.created_at', 'desc')
            ->get();

        $conversations->each(function ($conv) {
            // Se ainda for LID, tenta resolver e migrar no banco
            if (str_contains($conv->remote_jid ?? '', '@lid')) {
                $resolved = $this->evolution->resolveLidToPhoneNumber($conv->remote_jid);
                if ($resolved) {
                    EvolutionMessage::where('remote_jid', $conv->remote_jid)->update(['remote_jid' => $resolved]);
                    $conv->remote_jid = $resolved;
                }
            }

            $rawPhone = !str_contains($conv->remote_jid ?? '', '@lid') 
                ? preg_replace('/\D+/', '', explode('@', $conv->remote_jid ?? '')[0] ?? '') 
                : '';

            // Nome do contato (nunca o nome do admin que enviou mensagem outbound)
            $contactName = $conv->user_name 
                ?: \App\Models\EvolutionContactMapping::where('remote_jid', $conv->remote_jid)->orWhere('phone', $rawPhone)->value('name')
                ?: DB::table('evolution_messages')
                    ->where('remote_jid', $conv->remote_jid)
                    ->where('direction', 'inbound')
                    ->whereNotNull('push_name')
                    ->where('push_name', '!=', '')
                    ->orderBy('id', 'desc')
                    ->value('push_name')
                ?: (($conv->last_message_direction ?? '') === 'inbound' ? $conv->push_name : null);

            $displayName = $contactName ?: ($rawPhone ? ('+' . $rawPhone) : 'Contato WhatsApp');

            $conv->display_name = $displayName;
            $conv->display_phone = $conv->user_whatsapp ?: $rawPhone;

            $unreadQuery = DB::table('evolution_messages')
                ->where('direction', 'inbound')
                ->where('status', '!=', 'read');

            if ($conv->remote_jid) {
                $unreadQuery->where('remote_jid', $conv->remote_jid);
            } elseif ($conv->user_id) {
                $unreadQuery->where('user_id', $conv->user_id);
            } else {
                $unreadQuery->where('from', $conv->from);
            }

            $conv->unread_count = $unreadQuery->count();

            $assignedType = in_array($conv->assigned_type ?? '', ['admin', 'severino'], true) ? $conv->assigned_type : 'none';
            $conv->assigned_type = $assignedType;
            $conv->assigned_admin_id = $assignedType === 'admin' && !empty($conv->assigned_admin_id) ? (int) $conv->assigned_admin_id : null;
            $conv->assigned_admin_name = $assignedType === 'severino'
                ? '🤖 Severino (IA)'
                : ($assignedType === 'admin' ? ($conv->assigned_admin_name ?? null) : null);
            $conv->assigned_value = $assignedType === 'severino'
                ? 'severino'
                : ($assignedType === 'admin' && $conv->assigned_admin_id ? (string) $conv->assigned_admin_id : '');
            $conv->supervision_mode = $assignedType === 'severino' ? 'severino' : 'human';
        });

        return response()->json($conversations);
    }

    /**
     * Lista atendentes disponíveis para atribuição no Chat Evolution (inclui Severino IA no topo!)
     */
    public function getAdmins()
    {
        if ($resp = $this->ensureMatrizOnly()) {
            return $resp;
        }

        $admins = User::where('is_admin', 1)
            ->whereIn('role', ['admin', 'admin_master'])
            ->where(function ($q) {
                $q->whereNull('brecho_id')->orWhere('brecho_id', 1);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'role'])
            ->map(fn($u) => [
                'id' => (string) $u->id,
                'name' => $u->name,
                'role' => $u->role,
                'is_severino' => false,
            ])
            ->values()
            ->toArray();

        array_unshift($admins, [
            'id' => 'severino',
            'name' => '🤖 Severino (IA)',
            'role' => 'ai_agent',
            'is_severino' => true,
        ]);

        return response()->json($admins);
    }

    /**
     * Atribui uma conversa do Chat Evolution a um atendente humano, ao Severino (IA) ou a ninguém
     */
    public function assignConversation(Request $request)
    {
        if ($resp = $this->ensureMatrizOnly()) {
            return $resp;
        }

        $authAdmin = auth()->user();
        if (!$authAdmin || $authAdmin->is_admin != 1 || $authAdmin->role !== 'admin_master') {
            return response()->json(['success' => false, 'error' => 'Apenas o administrador master pode atribuir conversas.'], 403);
        }

        $request->validate([
            'remote_jid' => 'required|string|max:120',
            'user_id' => 'nullable|integer',
            'assignee' => 'nullable|string|max:40',
        ]);

        $remoteJid = trim((string) $request->input('remote_jid'));
        $userId = $request->input('user_id') ? (int) $request->input('user_id') : null;
        $assignee = trim((string) $request->input('assignee', ''));

        if ($assignee === 'severino') {
            $result = \App\Services\Ai\SeverinoWhatsappAgentService::assignConversation(
                $remoteJid,
                $userId,
                'severino',
                null,
                $authAdmin->id
            );
        } elseif ($assignee !== '' && ctype_digit($assignee)) {
            $adminId = (int) $assignee;
            if (!User::where('id', $adminId)->exists()) {
                return response()->json(['success' => false, 'error' => 'Atendente não encontrado.'], 422);
            }
            $result = \App\Services\Ai\SeverinoWhatsappAgentService::assignConversation(
                $remoteJid,
                $userId,
                'admin',
                $adminId,
                $authAdmin->id
            );
        } else {
            $result = \App\Services\Ai\SeverinoWhatsappAgentService::assignConversation(
                $remoteJid,
                $userId,
                'none',
                null,
                $authAdmin->id
            );
        }

        return response()->json([
            'success' => true,
            'assignment' => $result,
            'message' => $result['assigned_type'] === 'severino'
                ? 'Conversa atribuída ao Severino (IA) — ele já pode responder automaticamente!'
                : 'Atribuição atualizada com sucesso.',
        ]);
    }

    /**
     * Retorna histórico de mensagens de uma conversa (por userId ou remoteJid)
     */
    public function getMessages(Request $request, $identifier = null)
    {
        $userId = $request->query('user_id');
        $remoteJid = $request->query('remote_jid');
        $limit = (int) $request->query('limit', 100);

        if (!$userId && !$remoteJid && $identifier) {
            if (is_numeric($identifier)) {
                $userId = (int) $identifier;
            } else {
                $remoteJid = $identifier;
            }
        }

        // Atendente não-master só pode ler conversas atribuídas a ele
        $authAdmin = auth()->user();
        $isMaster = $authAdmin && $authAdmin->is_admin == 1 && $authAdmin->role === 'admin_master';
        if (!$isMaster) {
            $assign = \App\Services\Ai\SeverinoWhatsappAgentService::getConversationAssignment($remoteJid, $userId ? (int) $userId : null);
            if ($assign['assigned_type'] !== 'admin' || (int) $assign['assigned_admin_id'] !== (int) $authAdmin->id) {
                return response()->json(['error' => 'Acesso negado. Conversa não atribuída a você.'], 403);
            }
        }

        $query = DB::table('evolution_messages')
            ->leftJoin('users as admin_user', 'evolution_messages.admin_id', '=', 'admin_user.id');

        if ($remoteJid) {
            $query->where('evolution_messages.remote_jid', $remoteJid);
        } elseif ($userId) {
            $query->where('evolution_messages.user_id', $userId);
        } else {
            return response()->json([]);
        }

        $messages = $query->orderBy('evolution_messages.created_at', 'desc')
            ->take($limit)
            ->select('evolution_messages.*', 'admin_user.name as admin_name')
            ->get()
            ->reverse()
            ->values();

        // Marca como lidas
        $updateQuery = DB::table('evolution_messages')
            ->where('direction', 'inbound')
            ->where('status', '!=', 'read');

        if ($remoteJid) {
            $updateQuery->where('remote_jid', $remoteJid)->update(['status' => 'read']);
        } elseif ($userId) {
            $updateQuery->where('user_id', $userId)->update(['status' => 'read']);
        }

        return response()->json($messages);
    }

    /**
     * Envia mensagem via Evolution API
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|integer',
            'remote_jid' => 'nullable|string',
            'body' => 'nullable|string|max:4000',
            'file' => 'nullable|file|max:25600',
        ]);

        $userId = $request->input('user_id') ? (int) $request->input('user_id') : null;
        $remoteJid = trim((string) $request->input('remote_jid', ''));
        $body = trim((string) $request->input('body', ''));

        if ($body === '' && !$request->hasFile('file')) {
            return response()->json(['success' => false, 'error' => 'Mensagem ou arquivo obrigatório.'], 422);
        }

        $user = $userId ? User::find($userId) : null;
        $destination = $remoteJid ?: $user?->whatsapp ?: $user?->phone;

        if (!$destination) {
            return response()->json(['success' => false, 'error' => 'Destinatário sem identificador ou telefone.'], 404);
        }

        // Se for LID, tenta resolver para @s.whatsapp.net
        $targetJid = $destination;
        if (str_contains($destination, '@lid')) {
            $resolved = $this->evolution->resolveLidToPhoneNumber($destination);
            if ($resolved) {
                // Atualiza mensagens antigas do @lid para o @s.whatsapp.net para unificar a conversa
                EvolutionMessage::where('remote_jid', $destination)->update(['remote_jid' => $resolved]);
                $destination = $resolved;
                $targetJid = $resolved;
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Não foi possível identificar o número WhatsApp deste contato para envio.'
                ], 422);
            }
        }

        $digits = preg_replace('/\D+/', '', explode('@', $targetJid)[0] ?? '');
        $pushName = $user?->name 
            ?: \App\Models\EvolutionContactMapping::where('remote_jid', $targetJid)->orWhere('phone', $digits)->value('name')
            ?: EvolutionMessage::where('remote_jid', $targetJid)->where('direction', 'inbound')->whereNotNull('push_name')->latest('id')->value('push_name');

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

            $res = $this->evolution->sendMedia($destination, $mediaUrl, $body, $mediaType, $fileName);
        } else {
            $res = $this->evolution->sendText($destination, $body);
        }

        $status = ($res['success'] ?? false) ? 'sent' : 'failed';
        $messageId = $res['message_id'] ?? null;
        $canonicalJid = $res['remote_jid'] ?? $targetJid;
        $failedReason = ($status === 'failed') ? ($res['error'] ?? 'Falha no envio') : null;

        // Ao enviar mensagem manual pelo painel, atribui a conversa ao operador que respondeu (retira do Severino)
        \App\Services\Ai\SeverinoWhatsappAgentService::assignConversation(
            $canonicalJid,
            $user?->id,
            $adminId ? 'admin' : 'none',
            $adminId ?: null,
            $adminId ?: null
        );

        $msg = EvolutionMessage::create([
            'user_id' => $user?->id,
            'admin_id' => $adminId,
            'instance_name' => 'sacolinhas',
            'remote_jid' => $canonicalJid,
            'push_name' => $pushName,
            'direction' => 'outbound',
            'status' => $status,
            'message_id' => $messageId,
            'from' => 'evolution',
            'to' => $destination,
            'body' => $body,
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
            'failed_reason' => $failedReason,
            'raw_payload' => array_merge((array) $res, ['sender_type' => 'human']),
        ]);

        return response()->json([
            'success' => $status === 'sent',
            'message' => $msg,
            'supervision_mode' => 'human',
            'error' => $failedReason,
        ]);
    }

    /**
     * Alterna modo de supervisão da conversa: 'severino' (IA ativa) vs 'human' (Humano assumiu)
     */
    public function toggleSupervision(Request $request)
    {
        $remoteJid = trim((string) $request->input('remote_jid', ''));
        $userId = $request->input('user_id') ? (int) $request->input('user_id') : null;
        $desiredMode = $request->input('mode');

        if (!$desiredMode) {
            $current = \App\Services\Ai\SeverinoWhatsappAgentService::getSupervisionMode($remoteJid, $userId);
            $desiredMode = ($current === 'severino') ? 'human' : 'severino';
        }

        $desiredType = ($desiredMode === 'severino') ? 'severino' : 'admin';
        $assignRes = \App\Services\Ai\SeverinoWhatsappAgentService::assignConversation(
            $remoteJid,
            $userId,
            $desiredType,
            $desiredType === 'admin' ? auth()->id() : null,
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'supervision_mode' => $desiredMode,
            'assignment' => $assignRes,
        ]);
    }

    /**
     * Obtém ou atualiza a configuração global do Severino Live WhatsApp (Modo Teste / Modo Live / Desativado)
     */
    public function severinoConfig(Request $request)
    {
        if ($request->isMethod('post')) {
            $config = \App\Services\Ai\SeverinoWhatsappAgentService::saveConfig($request->all());
            return response()->json(['success' => true, 'config' => $config]);
        }

        return response()->json([
            'success' => true,
            'config' => \App\Services\Ai\SeverinoWhatsappAgentService::getConfig(),
        ]);
    }

    /**
     * Dispara teste de "Peça Ganha na Live" via Severino / Evolution API
     */
    public function sendLiveItemTest(Request $request)
    {
        $request->validate([
            'live_id' => 'required|integer',
            'user_id' => 'required|integer',
            'item_id' => 'nullable|integer',
            'target_phone' => 'nullable|string',
        ]);

        $liveId = (int) $request->input('live_id');
        $userId = (int) $request->input('user_id');
        $itemId = $request->input('item_id') ? (int) $request->input('item_id') : null;
        $targetPhone = $request->filled('target_phone') ? trim((string) $request->input('target_phone')) : null;

        if (!$itemId) {
            $sacolaRow = DB::table('sacolinhas')
                ->where('live_id', $liveId)
                ->where('user_id', $userId)
                ->orderByDesc('id')
                ->first();

            if (!$sacolaRow) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum item encontrado na sacolinha desta cliente nesta live.',
                ], 404);
            }
            $itemId = (int) $sacolaRow->item_id;
            $price = (float) $sacolaRow->price;
        } else {
            $price = null;
        }

        $res = app(\App\Services\Ai\SeverinoWhatsappAgentService::class)->sendLiveItemWonNotification(
            $userId,
            $itemId,
            $liveId,
            $price,
            null,
            forceSend: true,
            overridePhone: $targetPhone
        );

        $balloonsInfo = !empty($res['sent_two_balloons'])
            ? ' (2 balões: abertura humana + peça)'
            : ' (1 balão curto: conversa já ativa)';

        return response()->json([
            'success' => $res['success'] ?? false,
            'message' => ($res['success'] ?? false)
                ? 'Mensagem de Peça Ganha na Live enviada com sucesso via Severino!' . $balloonsInfo
                : ('Erro ao enviar: ' . ($res['error'] ?? 'Falha desconhecida')),
            'data' => $res,
        ]);
    }

    /**
     * Dispara o Fechamento de Fim de Live (PDF + Mensagem enxuta) no WhatsApp via Severino / Evolution API
     */
    public function sendLiveClosing(Request $request)
    {
        if ($resp = $this->ensureMatrizOnly()) {
            return $resp;
        }

        $request->validate([
            'live_id' => 'required|integer',
            'user_id' => 'required|integer',
            'target_phone' => 'nullable|string',
            'use_test_phone' => 'nullable|boolean',
            'pdf_only' => 'nullable|boolean',
        ]);

        $liveId = (int) $request->input('live_id');
        $userId = (int) $request->input('user_id');
        $targetPhone = $request->filled('target_phone') ? trim((string) $request->input('target_phone')) : null;
        $useTestPhone = $request->boolean('use_test_phone', true);
        $pdfOnly = $request->boolean('pdf_only', false);

        $res = app(\App\Services\Ai\SeverinoWhatsappAgentService::class)->sendLiveClosingMessage(
            $userId,
            $liveId,
            overridePhone: $targetPhone,
            useTestPhone: $useTestPhone,
            pdfOnly: $pdfOnly
        );

        $pdfInfoTxt = !empty($res['pdf_sent'])
            ? 'PDF da sacolinha: Enviado ✅'
            : ('PDF da sacolinha: Não enviado (' . ($res['pdf_error'] ?? 'aviso') . ')');

        $successMessage = $pdfOnly
            ? "PDF de Fechamento da Live #{$liveId} enviado para {$res['target_phone']}!\nCliente: {$res['client_name']}\n{$pdfInfoTxt} (Modo Apenas PDF, sem texto/sequência do Severino)"
            : "Fechamento da Live enviado pelo Severino para {$res['target_phone']}!\nCliente: {$res['client_name']}\nPerfil: {$res['flow_label']}\n{$pdfInfoTxt}\n\nAgora você pode responder no WhatsApp (ou simular no chat) para testar o atendimento + gancho pós-fechamento!";

        return response()->json([
            'success' => $res['success'] ?? false,
            'flow' => $res['flow'] ?? null,
            'flow_label' => $res['flow_label'] ?? null,
            'target_phone' => $res['target_phone'] ?? null,
            'pdf_sent' => $res['pdf_sent'] ?? false,
            'pdf_only' => $pdfOnly,
            'message' => ($res['success'] ?? false)
                ? $successMessage
                : ('Erro ao enviar: ' . ($res['error'] ?? 'Falha desconhecida')),
            'data' => $res,
        ]);
    }

    /**
     * Inicia a fila cadenciada de fechamento (PDF + opcionalmente texto enxuto, intervalo aleatório 5s a 55s)
     */
    public function startClosingQueue(Request $request)
    {
        if ($resp = $this->ensureMatrizOnly()) {
            return $resp;
        }

        $request->validate([
            'live_id' => 'required|integer',
            'use_test_phone' => 'nullable|boolean',
            'target_phone' => 'nullable|string',
            'min_delay' => 'nullable|integer|min:5|max:55',
            'max_delay' => 'nullable|integer|min:5|max:120',
            'limit_test' => 'nullable|integer|min:1|max:50',
            'pdf_only' => 'nullable|boolean',
        ]);

        $res = app(\App\Services\Ai\SeverinoWhatsappAgentService::class)->startClosingQueue(
            (int) $request->input('live_id'),
            [
                'use_test_phone' => $request->boolean('use_test_phone', true),
                'target_phone' => $request->input('target_phone'),
                'min_delay' => $request->input('min_delay', 5),
                'max_delay' => $request->input('max_delay', 55),
                'limit_test' => $request->input('limit_test'),
                'pdf_only' => $request->boolean('pdf_only', true),
            ]
        );

        return response()->json($res, !empty($res['success']) ? 200 : 422);
    }

    /**
     * Consulta progresso da fila cadenciada de fechamento da live
     */
    public function closingQueueStatus(int $liveId)
    {
        if ($resp = $this->ensureMatrizOnly()) {
            return $resp;
        }

        return response()->json([
            'success' => true,
            'state' => \App\Services\Ai\SeverinoWhatsappAgentService::getClosingQueueStatus($liveId),
        ]);
    }

    /**
     * Pausa a fila cadenciada de fechamento
     */
    public function pauseClosingQueue(Request $request)
    {
        if ($resp = $this->ensureMatrizOnly()) {
            return $resp;
        }

        $request->validate(['live_id' => 'required|integer']);
        return response()->json(
            \App\Services\Ai\SeverinoWhatsappAgentService::pauseClosingQueue((int) $request->input('live_id'))
        );
    }

    /**
     * Retoma a fila cadenciada de fechamento pausada
     */
    public function resumeClosingQueue(Request $request)
    {
        if ($resp = $this->ensureMatrizOnly()) {
            return $resp;
        }

        $request->validate(['live_id' => 'required|integer']);
        return response()->json(
            \App\Services\Ai\SeverinoWhatsappAgentService::resumeClosingQueue((int) $request->input('live_id'))
        );
    }

    /**
     * Cancela a fila cadenciada de fechamento
     */
    public function cancelClosingQueue(Request $request)
    {
        if ($resp = $this->ensureMatrizOnly()) {
            return $resp;
        }

        $request->validate(['live_id' => 'required|integer']);
        return response()->json(
            \App\Services\Ai\SeverinoWhatsappAgentService::cancelClosingQueue((int) $request->input('live_id'))
        );
    }

    protected function ensureMatrizOnly()
    {
        $user = auth()->user();
        if ($user && !empty($user->brecho_id) && (int) $user->brecho_id > 1) {
            return response()->json([
                'success' => false,
                'error' => 'Funcionalidade exclusiva da Minha Mania (Matriz).',
            ], 403);
        }
        return null;
    }

    /**
     * Permite simular uma resposta da cliente diretamente no painel para testar a conversa com o Severino
     */
    public function simulateClientReply(Request $request)
    {
        $request->validate([
            'remote_jid' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'body' => 'required|string|max:2000',
        ]);

        $remoteJid = trim((string) $request->input('remote_jid', ''));
        $userId = $request->input('user_id') ? (int) $request->input('user_id') : null;
        $body = trim((string) $request->input('body'));

        $config = \App\Services\Ai\SeverinoWhatsappAgentService::getConfig();
        if (!$remoteJid) {
            $testDigits = preg_replace('/\D+/', '', $config['test_phone'] ?: '5548984136870');
            $remoteJid = $testDigits . '@s.whatsapp.net';
        }

        $testCtx = \App\Services\Ai\SeverinoWhatsappAgentService::getTestContextForPhone($remoteJid);
        if (!$userId && !empty($testCtx['user_id'])) {
            $userId = (int) $testCtx['user_id'];
        }

        $user = $userId ? User::find($userId) : null;
        $pushName = $user ? "🧪 [Teste: {$user->name}]" : 'Cliente (Simulação)';

        // 1. Grava a mensagem simulada como recebida da cliente (inbound)
        EvolutionMessage::create([
            'user_id' => $user?->id,
            'instance_name' => 'sacolinhas',
            'remote_jid' => $remoteJid,
            'push_name' => $pushName,
            'direction' => 'inbound',
            'status' => 'read',
            'message_id' => 'SIM_' . uniqid(),
            'from' => $remoteJid,
            'to' => 'evolution',
            'body' => $body,
            'raw_payload' => ['simulated' => true],
        ]);

        // 2. Força o modo Severino ativo e processa a resposta da IA
        \App\Services\Ai\SeverinoWhatsappAgentService::setSupervisionMode($remoteJid, $user?->id, 'severino');
        $agentRes = app(\App\Services\Ai\SeverinoWhatsappAgentService::class)->handleIncomingClientMessage(
            $remoteJid,
            $user?->id,
            $body,
            forceReplyEvenIfHumanMode: true
        );

        return response()->json([
            'success' => true,
            'agent' => $agentRes,
        ]);
    }
}

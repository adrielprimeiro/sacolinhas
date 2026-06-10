<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsappMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ChatController extends Controller
{
    public function index()
    {
        return view('admin.chat');
    }

	public function getConversations()
	{
		$authAdmin = auth()->user();
		$isMaster = $authAdmin && $authAdmin->is_admin == 1 && $authAdmin->role === 'admin_master';

		// Subquery para pegar a última mensagem de cada cliente
		$subLastMessage = DB::table('whatsapp_messages')
			->select('user_id', DB::raw('MAX(id) as last_message_id'))
			->whereNotNull('user_id')
			->groupBy('user_id');

		$query = DB::table('whatsapp_messages as wm')
			->joinSub($subLastMessage, 'last_msg', 'wm.id', '=', 'last_msg.last_message_id')
			->join('users', 'users.id', '=', 'wm.user_id')
			->leftJoin('chat_assignments as ca', 'ca.user_id', '=', 'wm.user_id')
			->leftJoin('users as admin_user', 'admin_user.id', '=', 'ca.assigned_admin_id') // Para pegar o nome do atendente
			->select(
				'wm.user_id',
				'users.name as user_name',
				'wm.body as last_message_body',
				'wm.created_at as last_message_at',
				DB::raw("CASE WHEN wm.media_url IS NOT NULL AND wm.media_url != '' THEN 1 ELSE 0 END as last_message_has_media"),
				'ca.assigned_admin_id',
				'admin_user.name as assigned_admin_name',
				'ca.expires_at as window_expires_at'  // ✅ ALTERADO: Usa o expires_at SALVO no chat_assignments (não recalcula)
			)
			->orderBy('wm.created_at', 'desc');

		// Se não for master, filtra pelas conversas atribuídas E com janela aberta
		if (!$isMaster) {
			$query->where('ca.assigned_admin_id', $authAdmin->id)
				  ->where('ca.expires_at', '>', now());  // ✅ ALTERADO: Usa expires_at do banco (mais preciso)
		}

		$conversations = $query->get();

		// Adiciona contagem de não lidas
		$conversations->each(function ($conv) {
			$conv->unread_count = DB::table('whatsapp_messages')
				->where('user_id', $conv->user_id)
				->where('direction', 'inbound')
				->where('status', '!=', 'read')
				->count();
		});

		return response()->json($conversations);
	}
	
	
	
	
	
	public function getMessages($userId)
	{
		$authAdmin = auth()->user();
		$isMaster = $authAdmin && $authAdmin->is_admin == 1 && $authAdmin->role === 'admin_master';
		$userId = (int) $userId;

		if (!$isMaster) {
			$assignment = DB::table('chat_assignments')->where('user_id', $userId)->first();
			$lastInboundAt = DB::table('whatsapp_messages')
				->where('user_id', $userId)
				->where('direction', 'inbound')
				->max('created_at');
			
			$isWindowOpen = $lastInboundAt && now()->diffInHours($lastInboundAt) < 24;

			if (!$assignment || $assignment->assigned_admin_id != $authAdmin->id || !$isWindowOpen) {
				return response()->json(['error' => 'Você não tem permissão para acessar esta conversa ou a janela de atendimento expirou.'], 403);
			}
		}

		$messages = DB::table('whatsapp_messages')
			->where('user_id', $userId)
			->orderBy('created_at', 'asc')
			->get()
			->map(function ($m) {
				$m->has_media = !empty($m->media_url);
				$m->download_url = $m->has_media ? route('admin.chat.download', ['id' => $m->id]) : null;
				return $m;
			});

		return response()->json($messages);
	}

	public function getAdmins()
	{
		$auth = auth()->user();

		// Permitir só admin logado (master e atendente). Se quiser só master, eu ajusto.
		if (!$auth || (int)$auth->is_admin !== 1) {
			return response()->json(['error' => 'Não autorizado'], 403);
		}

		// Lista apenas atendentes (role = admin). Master não precisa aparecer no select.
		$admins = User::where('is_admin', 1)
			->where('role', 'admin')
			->orderBy('name')
			->get(['id', 'name', 'role']);

		return response()->json($admins);
	}

	public function assignConversation(Request $request)
	{
		$authAdmin = auth()->user();
		if (!$authAdmin || $authAdmin->is_admin != 1 || $authAdmin->role !== 'admin_master') {
			return response()->json(['success' => false, 'error' => 'Apenas o administrador master pode atribuir conversas.'], 403);
		}

		$validated = $request->validate([
			'user_id' => 'required|integer|exists:users,id',
			'assigned_admin_id' => 'nullable|integer|exists:users,id',
		]);

		$userId = $validated['user_id'];
		$assignedAdminId = $validated['assigned_admin_id'];

		// Calcula a expiração da janela de 24h
		$lastInboundAt = DB::table('whatsapp_messages')
			->where('user_id', $userId)
			->where('direction', 'inbound')
			->max('created_at');

		$expiresAt = $lastInboundAt ? Carbon::parse($lastInboundAt)->addHours(24) : null;

		DB::table('chat_assignments')->updateOrInsert(
			['user_id' => $userId],
			[
				'assigned_admin_id' => $assignedAdminId,
				'assigned_by_admin_id' => $authAdmin->id,
				'assigned_at' => now(),
				'expires_at' => $expiresAt,
				'created_at' => now(),
				'updated_at' => now(),
			]
		);

		return response()->json(['success' => true, 'message' => 'Conversa atribuída com sucesso.']);
	}



	public function sendMessage(Request $request)
	{
		$request->validate([
			'user_id' => 'required|integer|exists:users,id',
			'body' => 'nullable|string|max:1600',
			'file' => 'nullable|file|max:20480',
		]);

		$userId = (int) $request->input('user_id');
		$body = trim((string) $request->input('body', ''));

		if ($body === '' && !$request->hasFile('file')) {
			return response()->json([
				'success' => false,
				'error' => 'Envie uma mensagem ou um anexo.',
			], 422);
		}

		// ✅ VERIFICAÇÃO PRÉVIA: Última mensagem inbound nas últimas 24h
		$lastInbound = DB::table('whatsapp_messages')
			->where('user_id', $userId)
			->where('direction', 'inbound')
			->where('created_at', '>=', now()->subHours(24))
			->orderBy('created_at', 'desc')
			->first();

		if (!$lastInbound) {
			return response()->json([
				'success' => false,
				'error_type' => 'outside_allowed_window',
				'error' => 'Este cliente não iniciou conversa nas últimas 24h. Aguarde ele mandar uma mensagem ou use um Template aprovado.',
			], 422);
		}

		$user = User::find($userId);
		if (!$user || !$user->whatsapp) {
			return response()->json(['success' => false, 'error' => 'Usuário ou WhatsApp não encontrado.'], 404);
		}

		$to = $this->normalizeToWhatsapp((string) $user->whatsapp);
		if (!$to) {
			return response()->json(['success' => false, 'error' => 'Usuário ou WhatsApp inválido.'], 404);
		}

		// Upload local do arquivo (se existir) - MESMO LUGAR DO PDF
		$publicMediaUrl = null;
		$mediaContentType = null;

		if ($request->hasFile('file')) {
			$file = $request->file('file');

			if (!$file || !$file->isValid()) {
				return response()->json(['success' => false, 'error' => 'Arquivo inválido.'], 422);
			}

			$mediaContentType = $file->getMimeType();

			// Salva no MESMO disco do PDF (public) e pasta similar
			$filename = 'chat_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
			$relativePath = "pedidos/chat_uploads/{$filename}";
			Storage::disk('public')->put($relativePath, file_get_contents($file->getRealPath()));

			// URL pública IGUAL ao padrão do PDF
			$publicMediaUrl = asset("storage/{$relativePath}");

		}

		$accountSid = (string) config('services.twilio.account_sid');
		$authToken  = (string) config('services.twilio.auth_token');
		$from       = trim((string) config('services.twilio.whatsapp_from'));

		$payload = [
			'From' => $from,
			'To'   => $to,
			'Body' => $body,
			'StatusCallback' => url('/twilio-status'),
		];

		if ($publicMediaUrl) {
			$payload['MediaUrl'] = $publicMediaUrl;
		}
		
		if ($accountSid === '' || $authToken === '' || $from === '') {
			Log::error('TWILIO_CONFIG_MISSING', [
				'account_sid_present' => $accountSid !== '',
				'auth_token_present'  => $authToken !== '',
				'from_present'        => $from !== '',
			]);

			return response()->json([
				'success' => false,
				'error' => 'Configuração do Twilio ausente no servidor.',
			], 500);
		}

		if (!str_starts_with($from, 'whatsapp:')) {
			$from = 'whatsapp:' . $from;
		}		

		$resp = Http::withBasicAuth($accountSid, $authToken)
			->asForm()
			->withoutVerifying()
			->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", $payload);

		$respJson = $resp->json();
		$sid = $respJson['sid'] ?? null;

		Log::info('TWILIO SEND RAW', [
			'http_status' => $resp->status(),
			'successful' => $resp->successful(),
			'sid' => $sid,
			'json' => $respJson,
			'body' => $resp->body(),
			'media_url' => $publicMediaUrl,
		]);

		if (!$sid) {
			$twilioCode = $respJson['error_code'] ?? $respJson['code'] ?? null;
			$twilioMessage = $respJson['message'] ?? 'Falha ao enviar mensagem no Twilio.';

			if ((int)$twilioCode === 63016) {
				return response()->json([
					'success' => false,
					'error_type' => 'outside_allowed_window',
					'error' => 'Não é possível enviar mensagem livre fora da janela de 24h. Envie um Template aprovado ou aguarde o cliente mandar uma mensagem para reabrir a janela.',
					'twilio_code' => (int)$twilioCode,
				], 422);
			}

			return response()->json([
				'success' => false,
				'error_type' => 'twilio_error',
				'error' => $twilioMessage,
				'twilio_code' => $twilioCode ? (int)$twilioCode : null,
				'debug_twilio' => $respJson,
			], 422);
		}

		DB::table('whatsapp_messages')->insert([
			'user_id' => $userId,
			'direction' => 'outbound',
			'status' => 'queued',
			'message_sid' => $sid,
			'account_sid' => $accountSid,
			'from' => $from,
			'to' => $to,
			'body' => $body,
			'message_type' => 'chat',
			'media_url' => $publicMediaUrl,
			'media_content_type' => $mediaContentType,
			'num_media' => $publicMediaUrl ? 1 : 0,
			'raw_payload' => json_encode($respJson ?? ['raw' => $resp->body()]),
			'status_updated_at' => now(),
			'created_at' => now(),
			'updated_at' => now(),
		]);

		return response()->json([
			'success' => true,
			'message' => 'Mensagem enviada.',
		]);
	}

    private function normalizeToWhatsapp(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw);
        if (!$digits) return null;

        if (!str_starts_with($digits, '55')) {
            $digits = '55' . $digits;
        }

        if (strlen($digits) < 12) return null;

        return 'whatsapp:+' . $digits;
    }

	public function downloadMedia($id)
	{
		$message = WhatsappMessage::findOrFail($id);

		if (!$message->media_url) {
			abort(404, 'Nenhuma mídia encontrada.');
		}

		$appUrl = rtrim((string) config('app.url'), '/');
		$mediaUrl = (string) $message->media_url;

		// Se for URL local (como o PDF), redireciona direto
		$path = (string) parse_url($mediaUrl, PHP_URL_PATH);
		if ($path !== '' && str_starts_with($path, '/storage/')) {
			return redirect()->away($mediaUrl);
		}

		// Se NÃO for Twilio, apenas redireciona (evita tentar BasicAuth à toa)
		$host = (string) parse_url($mediaUrl, PHP_URL_HOST);
		if ($host === '' || !str_contains($host, 'twilio.com')) {
			return redirect()->away($mediaUrl);
		}

		// Credenciais do Twilio via config/services.php (não use env() aqui)
		$sid = (string) config('services.twilio.account_sid');
		$token = (string) config('services.twilio.auth_token');

		if ($sid === '' || $token === '') {
			abort(500, 'Credenciais do Twilio não configuradas (services.twilio.account_sid/auth_token).');
		}

		$response = Http::timeout(30)
			->withBasicAuth($sid, $token)
			->withoutVerifying()
			->get($mediaUrl);

		if (!$response->successful()) {
			abort(502, 'Erro ao baixar do Twilio.');
		}

		$contentType = $message->media_content_type ?: $response->header('Content-Type');
		$ext = $this->guessExtension($contentType);
		$filename = "anexo-{$message->id}" . ($ext ? ".{$ext}" : "");

		return response($response->body(), 200)
			->header('Content-Type', $contentType ?: 'application/octet-stream')
			->header('Content-Disposition', "inline; filename=\"{$filename}\"");
	}

    private function guessExtension($mime)
    {
        return match (strtolower((string)$mime)) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            'video/mp4' => 'mp4',
            default => null,
        };
    }

    public function twilioStatus(Request $request)
    {
        // VALIDAÇÃO DESABILITADA - COMENTADO PARA NÃO USAR
        /*
        $authToken = env('TWILIO_AUTH_TOKEN');
        $signature = $request->header('X-Twilio-Signature');
        $url = $request->fullUrl();
        $params = $request->all();

        unset($params['X-Twilio-Signature']);

        if (!$this->validateTwilioSignature($url, $params, $signature, $authToken)) {
            Log::warning('Webhook Twilio: assinatura inválida', [
                'url' => $url,
                'params' => $params,
                'signature' => $signature
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }
        */

        Log::info('TWILIO_STATUS_WEBHOOK_RECEIVED', [
           'full_url' => $request->fullUrl(),
           'all_params' => $request->all(),
           'headers' => $request->headers->all(),
        ]);
	   
        $messageSid = $request->input('MessageSid');
        $messageStatus = $request->input('MessageStatus');
        $errorCode = $request->input('ErrorCode');
        $errorMessage = $request->input('ErrorMessage');

        if (!$messageSid || !$messageStatus) {
            Log::error('Webhook Twilio: dados obrigatórios ausentes', $request->all());
            return response()->json(['error' => 'MessageSid and MessageStatus required'], 400);
        }

        $message = DB::table('whatsapp_messages')
            ->where('message_sid', $messageSid)
            ->first();

        if (!$message) {
            Log::warning('Webhook Twilio: MessageSid não encontrado', [
                'message_sid' => $messageSid,
                'status' => $messageStatus
            ]);
            return response()->json(['error' => 'Message not found'], 404);
        }

        $updateData = [
            'status' => $messageStatus,
            'status_updated_at' => now(),
            'updated_at' => now(),
        ];

        if ($messageStatus === 'delivered') {
            $updateData['delivery_timestamp'] = now();
        } elseif ($messageStatus === 'read') {
            $updateData['read_timestamp'] = now();
        }

        if (in_array($messageStatus, ['failed', 'undelivered'])) {
            $updateData['failed_reason'] = $errorMessage ?: 'Erro Twilio';
            $updateData['error_code'] = $errorCode;
        }

        DB::table('whatsapp_messages')
            ->where('id', $message->id)
            ->update($updateData);

        Log::info('Webhook Twilio: status atualizado', [
            'message_id' => $message->id,
            'message_sid' => $messageSid,
            'old_status' => $message->status,
            'new_status' => $messageStatus,
            'user_id' => $message->user_id,
            'live_id' => $message->live_id, // ADICIONADO
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ]);

        return response()->json(['success' => true]);
    }

    private function validateTwilioSignature(string $url, array $params, ?string $signature, string $authToken): bool
    {
        if (!$signature || !is_string($signature)) {
            return false;
        }

        ksort($params);
        
        $data = $url;
        foreach ($params as $key => $value) {
            $data .= $key . $value;
        }
        
        $expected = base64_encode(hash_hmac('sha1', $data, $authToken, true));
        
        // LOGS PARA DEBUG
        Log::info('TWILIO_SIGNATURE_DEBUG', [
            'url' => $url,
            'params' => $params,
            'data_string' => $data,
            'signature_received' => $signature,
            'signature_expected' => $expected,
            'match' => hash_equals($expected, $signature),
        ]);
        
        return hash_equals($expected, $signature);
    }

	public function dashboard(Request $request)
	{
		$liveId = (int) $request->query('live_id');

		if (!$liveId) {
			// Pega a última live (maior id). Se você preferir "última encerrada", me avise.
			$liveId = (int) DB::table('lives')->max('id');
		}

		if (!$liveId) {
			abort(404, 'Nenhuma live encontrada.');
		}

		return view('admin.whatsapp_dashboard', compact('liveId'));
	}
	

	public function getDashboardStats(Request $request)
	{
		$liveId = (int) $request->query('live_id');
		if (!$liveId) {
			return response()->json(['error' => 'live_id é obrigatório'], 422);
		}

		// TOTAL SACOLINHAS
		$totalSacolinhas = DB::table('sacolinhas')
			->where('live_id', $liveId)
			->distinct()
			->count('user_id');

		// STATS MSG1
		$statsMsg1 = DB::table('whatsapp_messages')
			->where('direction', 'outbound')
			->where('live_id', $liveId)
			->where('message_type', 'first')
			->selectRaw("
				COUNT(*) as total_msg1,
				SUM(CASE WHEN status IN ('delivered','read') THEN 1 ELSE 0 END) as msg1_entregues,
				SUM(CASE WHEN status IN ('failed','undelivered') THEN 1 ELSE 0 END) as msg1_falhas
			")
			->first();

		// 1ª MSG RESPONDIDA
		$msg1Respondida = DB::table('whatsapp_messages')
			->where('direction', 'inbound')
			->where('live_id', $liveId)
			->whereNotNull('user_id')
			->whereRaw("TRIM(LOWER(body)) = 'revisar e confirmar'")
			->distinct()
			->count('user_id');

		// SUBQUERIES PARA TABELA
		$subMsg1 = DB::table('whatsapp_messages')
			->select('user_id', DB::raw('MAX(id) as msg1_id'))
			->where('live_id', $liveId)
			->where('direction', 'outbound')
			->where('message_type', 'first')
			->whereNotNull('user_id')
			->groupBy('user_id');

		$subMsg2 = DB::table('whatsapp_messages')
			->select('user_id', DB::raw('MAX(id) as msg2_id'))
			->where('live_id', $liveId)
			->where('direction', 'outbound')
			->where('message_type', 'second')
			->whereNotNull('user_id')
			->groupBy('user_id');

		$subConfirm = DB::table('whatsapp_messages')
			->select('user_id', DB::raw('MAX(id) as confirm_id'))
			->where('live_id', $liveId)
			->where('direction', 'inbound')
			->whereNotNull('user_id')
			->whereRaw("TRIM(LOWER(body)) = 'revisar e confirmar'")
			->groupBy('user_id');

		$subMsg3 = DB::table('whatsapp_messages')
			->select('user_id', DB::raw('MIN(id) as msg3_id'))
			->where('live_id', $liveId)
			->where('direction', 'inbound')
			->whereNotNull('user_id')
			->whereRaw("TRIM(LOWER(body)) <> 'revisar e confirmar'")
			->groupBy('user_id');

		$usersInLive = DB::query()
			->fromSub(function ($q) use ($subMsg1, $subMsg2, $subConfirm, $subMsg3) {
				$q->fromSub($subMsg1, 'a')->select('user_id')
				  ->union(DB::query()->fromSub($subMsg2, 'b')->select('user_id'))
				  ->union(DB::query()->fromSub($subConfirm, 'c')->select('user_id'))
				  ->union(DB::query()->fromSub($subMsg3, 'd')->select('user_id'));
			}, 'x');

		$rows = DB::query()
			->fromSub($usersInLive, 'x')
			->join('users as u', 'u.id', '=', 'x.user_id')
			->leftJoinSub($subMsg1, 'm1', fn($j) => $j->on('m1.user_id','=','x.user_id'))
			->leftJoinSub($subMsg2, 'm2', fn($j) => $j->on('m2.user_id','=','x.user_id'))
			->leftJoinSub($subConfirm, 'cf', fn($j) => $j->on('cf.user_id','=','x.user_id'))
			->leftJoinSub($subMsg3, 'm3', fn($j) => $j->on('m3.user_id','=','x.user_id'))
			->leftJoin('whatsapp_messages as w1', 'w1.id', '=', 'm1.msg1_id')
			->leftJoin('whatsapp_messages as w2', 'w2.id', '=', 'm2.msg2_id')
			->leftJoin('whatsapp_messages as w3', 'w3.id', '=', 'm3.msg3_id')
			->select([
				'u.id as user_id',
				'u.name as user_name',
				'u.whatsapp as user_whatsapp',
				DB::raw("(SELECT COUNT(*) FROM users u2 WHERE u2.whatsapp = u.whatsapp AND u2.id <> 1) as conflict_count"),
				DB::raw("LEFT(COALESCE(w1.body,''), 30) as msg1_preview"),
				DB::raw("COALESCE(w1.status,'') as msg1_status"),
				DB::raw("COALESCE(w1.created_at,'') as msg1_at"),
				DB::raw("LEFT(COALESCE(w2.body,''), 30) as msg2_preview"),
				DB::raw("COALESCE(w2.status,'') as msg2_status"),
				DB::raw("COALESCE(w2.created_at,'') as msg2_at"),
				DB::raw("LEFT(COALESCE(w3.body,''), 30) as msg3_preview"),
				DB::raw("COALESCE(w3.created_at,'') as msg3_at"),
				DB::raw("CASE WHEN cf.confirm_id IS NULL THEN 0 ELSE 1 END as has_confirm")
			])
			->orderByDesc(DB::raw('COALESCE(w1.id, 0)'))
			->get();

		// CONFLITOS PARA MOSTRAR NA TABELA (COM MSG2 = ERRO)
		$conflicts = DB::table('whatsapp_conflicts')
			->where('resolved', 0)
			->where(function ($q) use ($liveId) {
				$q->where('live_id', $liveId)->orWhereNull('live_id');
			})
			->get();

		// ADICIONA LINHAS DE CONFLITO NA TABELA (COM MSG2 = ERRO)
		foreach ($conflicts as $conflict) {
			$rows->push((object) [
				'user_id' => null,
				'user_name' => 'CONFLITO #' . $conflict->id,
				'user_whatsapp' => $conflict->digits,
				'conflict_count' => 0,
				'msg1_preview' => '',
				'msg1_status' => '',
				'msg1_at' => '',
				'msg2_preview' => 'ERRO: Telefone duplicado - ' . count(explode(',', $conflict->matched_user_ids)) . ' cadastros',
				'msg2_status' => '',
				'msg2_at' => $conflict->created_at,
				'msg3_preview' => '',
				'msg3_at' => '',
				'has_confirm' => 0
			]);
		}

		return response()->json([
			'live_id' => $liveId,
			'cards' => [
				'total_sacolinhas' => (int) $totalSacolinhas,
				'primeira_entregues' => (int) ($statsMsg1->msg1_entregues ?? 0),
				'primeira_respondidas' => (int) $msg1Respondida,
				'primeira_falhas' => (int) ($statsMsg1->msg1_falhas ?? 0),
			],
			'table' => $rows,
		]);
	}


	public function sendMsg2FromConflict(Request $request, $id)
	{
		$conflictId = (int) $id;

		$conflict = DB::table('whatsapp_conflicts')->where('id', $conflictId)->first();
		if (!$conflict) {
			return response()->json(['success' => false, 'error' => 'Conflito não encontrado.'], 404);
		}

		if ((int)$conflict->resolved === 1) {
			return response()->json(['success' => false, 'error' => 'Este conflito já foi resolvido.'], 422);
		}

		// digits pode estar "2199..." ou "+5521..." dependendo de como você gravou.
		$digits = preg_replace('/\D+/', '', (string)$conflict->digits);
		$without55 = str_starts_with($digits, '55') ? substr($digits, 2) : $digits;
		$with55 = '55' . $without55;

		// Opção A: só envia se agora existir 1 único usuário com esse whatsapp (exceto Sistema)
		$users = DB::table('users')
			->whereIn('whatsapp', [$digits, $with55, $without55])
			->where('id', '<>', 1)
			->get(['id', 'name', 'whatsapp']);

		if ($users->count() !== 1) {
			return response()->json([
				'success' => false,
				'error' => 'Ainda existe duplicidade. Resolva o cadastro (deixe somente 1 usuário com este WhatsApp) e tente novamente.',
				'debug' => ['matched_users' => $users->pluck('id')]
			], 422);
		}

		$user = $users->first();

		// Busca PDF ready para enviar Msg2
		$pdfRow = DB::table('live_pdfs')
			->where('user_id', $user->id)
			->where('status', 'ready')
			->orderByDesc('id')
			->first();

		if (!$pdfRow) {
			return response()->json([
				'success' => false,
				'error' => 'Não existe PDF com status ready para este usuário.'
			], 422);
		}

		$to = 'whatsapp:+' . (str_starts_with($digits, '55') ? $digits : ('55' . $digits));

		// Mensagem padrão Msg2 (pedido com anexo) — use a que você já usa no WhatsappController
		$msg = "📋 Checklist!\n"
			. "🔍 Dá uma espiadinha no pedido anexado\n"
			. "🔧 Se precisar ajustar → grita aqui que a gente conserta!\n"
			. "✅ Se bater tudo certinho → os itens serão incluídos em sua sacolinha.\n\n"
			. "Quando quiser o envio do pedido é só falar!";

		// Envia via Twilio (igual seu sendWhatsappMedia, mas aqui dentro do ChatController)
		$accountSid = (string) env('TWILIO_ACCOUNT_SID', '');
		$authToken  = (string) env('TWILIO_AUTH_TOKEN', '');
		$from       = trim((string) env('TWILIO_WHATSAPP_FROM', ''));

		if ($from !== '' && !str_starts_with($from, 'whatsapp:')) {
			$from = 'whatsapp:' . $from;
		}

		$resp = Http::withBasicAuth($accountSid, $authToken)
			->asForm()
			->withoutVerifying()
			->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
				'From' => $from,
				'To' => $to,
				'Body' => $msg,
				'MediaUrl' => (string) $pdfRow->pdf_url,
				'StatusCallback' => rtrim((string) config('app.url'), '/') . '/twilio-status',
			]);

		$respJson = $resp->json();
		$sid = $respJson['sid'] ?? null;

		DB::table('whatsapp_messages')->insert([
			'user_id' => $user->id,
			'live_id' => (int)$pdfRow->live_id,
			'direction' => 'outbound',
			'status' => ($resp->successful() && $sid) ? 'queued' : 'failed',
			'message_sid' => $sid,
			'account_sid' => $accountSid,
			'from' => $from,
			'to' => $to,
			'body' => $msg,
			'media_url' => (string) $pdfRow->pdf_url,
			'media_content_type' => 'application/pdf',
			'message_type' => 'second',
			'raw_payload' => json_encode($respJson ?? ['raw' => $resp->body()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
			'status_updated_at' => now(),
			'created_at' => now(),
			'updated_at' => now(),
		]);

		if (!$resp->successful() || !$sid) {
			return response()->json([
				'success' => false,
				'error' => 'Falha ao enviar Msg2 via Twilio.',
				'debug_twilio' => $respJson,
			], 422);
		}

		// marca conflito como resolvido
		DB::table('whatsapp_conflicts')->where('id', $conflictId)->update([
			'resolved' => 1,
			'updated_at' => now(),
		]);

		return response()->json(['success' => true]);
	}	
		
	public function markMessagesAsRead($userId)
	{
		DB::table('whatsapp_messages')
			->where('user_id', (int) $userId)
			->where('direction', 'inbound')
			->where('status', '!=', 'read')
			->update([
				'status' => 'read',
				'read_timestamp' => now(),
				'updated_at' => now()
			]);

		return response()->json(['success' => true]);
	}	
}
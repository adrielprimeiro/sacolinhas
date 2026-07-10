<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $phoneNumber;
    protected int $liveId;
    protected int $userId;
    protected string $messageType;

    public function __construct(string $phoneNumber, int $liveId, int $userId, ?string $messageType = 'first')
    {
        $this->phoneNumber = $phoneNumber;
        $this->liveId = $liveId;
        $this->userId = $userId;

        $messageType = trim((string) $messageType);
        $this->messageType = $messageType !== '' ? $messageType : 'first';
    }

    public function handle(): void
    {
        // 1) Normaliza telefone -> whatsapp:+55...
        $digits = preg_replace('/\D+/', '', $this->phoneNumber ?? '');
        if (!$digits || empty(trim($this->phoneNumber ?? ''))) {
            Log::warning('Job SendWhatsAppMessage: telefone ausente ou inválido', [
                'phone_provided' => $this->phoneNumber,
                'digits_extracted' => $digits,
                'user_id' => $this->userId,
                'live_id' => $this->liveId,
                'message_type' => $this->messageType,
            ]);
            
            DB::table('whatsapp_messages')->insert([
                'user_id' => $this->userId,
                'live_id' => $this->liveId,
                'direction' => 'outbound',
                'status' => 'failed',
                'message_type' => $this->messageType,
                'failed_reason' => 'Telefone ausente ou inválido',
                'retry_count' => 0,
                'status_updated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            return;
        }

        if (!str_starts_with($digits, '55')) {
            $digits = '55' . $digits;
        }

        $to = 'whatsapp:+' . $digits;

        // 2) Credenciais
		$accountSid = (string) config('services.twilio.account_sid', '');
		$authToken  = (string) config('services.twilio.auth_token', '');
		$from       = trim((string) config('services.twilio.whatsapp_from', ''));
        $from       = preg_replace('/\s+/', '', $from); // Remove espaços
        if ($from !== '' && !str_starts_with($from, 'whatsapp:')) {
            $from = 'whatsapp:' . $from;
        }
        // 3) Carrega template e variáveis de acordo com o tipo de mensagem
        $nome = (string) (DB::table('users')->where('id', $this->userId)->value('name') ?? '');
        $primeiroNome = trim(explode(' ', trim($nome))[0] ?? '');
        if ($primeiroNome === '') {
            $primeiroNome = 'amiga(o)';
        }

        if ($this->messageType === 'portal') {
            $contentSid = (string) config('services.twilio.portal_template', 'HX683ab296fb0256860ac186db30c9462c');
            
            // Calcula quantidades e valores para o template
            $dadosSacola = DB::table('sacolinhas')
                ->where('user_id', $this->userId)
                ->where('live_id', $this->liveId)
                ->where('status', '!=', 'pedido')
                ->selectRaw('COUNT(*) as num_items, SUM(price) as valor_total')
                ->first();

            $totalItens = (int) ($dadosSacola?->num_items ?? 0);
            $valorTotal = number_format((float) ($dadosSacola?->valor_total ?? 0), 2, ',', '.');

            // URL temporária assinada (valida por 30 dias)
            $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'portal.autologin', 
                now()->addDays(30), 
                ['user' => $this->userId]
            );
            
            // Extrai a parte variável da URL após /portal/autologin
            $basePath = url('/portal/autologin');
            $tail = str_replace($basePath, '', $url);
            $tail = ltrim($tail, '/');

            $vars = [
                '1' => $primeiroNome,
                '2' => (string) $totalItens,
                '3' => $valorTotal,
                '4' => $tail
            ];

            $contentVars = json_encode(
                $vars,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        } elseif ($this->messageType === 'avaliacao') {
            $contentSid = 'HX1adbad63d8d7d779e092cfffdbb10d18';
            
            $totalItens = (int) DB::table('avaliacao_items')
                ->where('avaliacao_id', $this->liveId)
                ->count();

            // URL temporária assinada (valida por 30 dias)
            $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'portal.autologin', 
                now()->addDays(30), 
                [
                    'user' => $this->userId,
                    'redirect' => route('portal.avaliacoes.ver', $this->liveId)
                ]
            );
            
            // Extrai a parte variável da URL após /portal/autologin
            $basePath = url('/portal/autologin');
            $tail = str_replace($basePath, '', $url);
            $tail = ltrim($tail, '/');

            $vars = [
                '1' => $primeiroNome,
                '2' => (string) $totalItens,
                '3' => $tail
            ];

            $contentVars = json_encode(
                $vars,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        } else {
            $contentSid = (string) config('services.twilio.initial_template', 'HX378937c73b703db60f41b0acfbd497e3');
            $contentVars = json_encode(
                ['1' => $primeiroNome],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        // 4) Validações
        if ($accountSid === '' || $authToken === '' || $from === '' || $contentSid === '' || $contentVars === false) {
            Log::error('Twilio Job: credenciais/template inválidos', [
                'has_accountSid' => $accountSid !== '',
                'has_authToken' => $authToken !== '',
                'has_from' => $from !== '',
                'has_contentSid' => $contentSid !== '',
                'contentVars_ok' => $contentVars !== false,
                'to' => $to,
                'user_id' => $this->userId,
                'live_id' => $this->liveId,
            ]);
            return;
        }

        // ✅ Verificação de duplicata (opcional)
        if ($this->messageType === 'first') {
            $existing = DB::table('whatsapp_messages')
                ->where('user_id', $this->userId)
                ->where('live_id', $this->liveId)
                ->where('message_type', 'first')
                ->where('direction', 'outbound')
                ->whereIn('status', ['queued', 'sent', 'delivered', 'read'])
                ->exists();

            if ($existing) {
                Log::info('Job SendWhatsAppMessage: mensagem já enviada, pulando', [
                    'user_id' => $this->userId,
                    'live_id' => $this->liveId,
                    'message_type' => $this->messageType,
                ]);
                return;
            }
        }

        // 5) URL do webhook de status
        $statusCallback = rtrim((string) config('app.url'), '/') . '/twilio-status';
        $hasLocalhost = str_contains($statusCallback, 'localhost') || str_contains($statusCallback, '127.0.0.1');

        // 6) Monta payload e envia
        $payload = [
            'From' => $from,
            'To' => $to,
            'ContentSid' => $contentSid,
            'ContentVariables' => $contentVars,
        ];

        // Só envia StatusCallback se não for localhost (evita erro HTTP 400 da Twilio)
        if (!$hasLocalhost) {
            $payload['StatusCallback'] = $statusCallback;
        }

        Log::info('Twilio Job: payload envio template', [
            'to' => $to,
            'from' => $from,
            'user_id' => $this->userId,
            'live_id' => $this->liveId,
            'message_type' => $this->messageType,
            'status_callback' => $statusCallback,
            'payload' => $payload,
        ]);

        $resp = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->withoutVerifying()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", $payload);

        $respJson = $resp->json();

        Log::info('Twilio Job: resposta envio template', [
            'http_status' => $resp->status(),
            'successful' => $resp->successful(),
            'json' => $respJson,
            'body' => $resp->body(),
        ]);

        $sid = $respJson['sid'] ?? null;
        $httpStatus = $resp->status();
        $responseBody = $resp->body();

        // 7) Determina status inicial baseado na resposta
        $status = 'failed';
        $failedReason = null;
        $retryCount = 0;

        if ($resp->successful() && $sid) {
            $status = 'queued';
        } else {
            $status = 'failed';
            
            // Tratamento específico de erro
            $twilioError = $resp->json();
            $errorCode = $twilioError['error_code'] ?? $twilioError['code'] ?? null;
            $errorMessage = $twilioError['message'] ?? $responseBody;

            if ((int)$errorCode === 63016) {
                $failedReason = 'Fora da janela de 24h - use template aprovado';
            } else {
                $failedReason = "HTTP {$httpStatus}: {$errorMessage}";
            }

            // Incrementa retry_count
            $existingRetry = DB::table('whatsapp_messages')
                ->where('user_id', $this->userId)
                ->where('live_id', $this->liveId)
                ->where('message_type', $this->messageType)
                ->where('direction', 'outbound')
                ->value('retry_count') ?? 0;
            
            $retryCount = $existingRetry + 1;
        }

        // 8) Insere na tabela de rastreamento
        DB::table('whatsapp_messages')->insert([
            'user_id' => $this->userId,
            'live_id' => $this->liveId,
            'direction' => 'outbound',
            'status' => $status,
            'message_sid' => $sid,
            'account_sid' => $accountSid,
            'from' => $from,
            'to' => $to,
            'message_type' => $this->messageType ?: 'first',
            'body' => "TEMPLATE {$contentSid} VARS {$contentVars}",
            'failed_reason' => $failedReason,
            'retry_count' => $retryCount,
            'status_updated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 9) Log de erro se falhou
        if (!$resp->successful()) {
            Log::error('Erro Twilio Job', [
                'http_status' => $httpStatus,
                'body' => $responseBody,
                'to' => $to,
                'from' => $from,
                'content_sid' => $contentSid,
                'content_vars' => $contentVars,
                'user_id' => $this->userId,
                'live_id' => $this->liveId,
                'message_type' => $this->messageType,
                'retry_count' => $retryCount,
                'status_callback' => $statusCallback, // Para debug
            ]);
        } else {
            Log::info('Job SendWhatsAppMessage: mensagem enviada com sucesso', [
                'user_id' => $this->userId,
                'live_id' => $this->liveId,
                'message_sid' => $sid,
                'status' => $status,
                'status_callback' => $statusCallback,
            ]);
        }
    }
}
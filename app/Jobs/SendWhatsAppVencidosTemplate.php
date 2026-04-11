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

class SendWhatsAppVencidosTemplate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $userId;
    protected string $phoneNumber;
    protected string $primeiroNome;
    protected string $vencimentoMaisAntigo;
    protected string $valorTotal;

    public function __construct(
        int $userId,
        string $phoneNumber,
        string $primeiroNome,
        string $vencimentoMaisAntigo,
        string $valorTotal
    ) {
        $this->userId = $userId;
        $this->phoneNumber = $phoneNumber;
        $this->primeiroNome = $primeiroNome;
        $this->vencimentoMaisAntigo = $vencimentoMaisAntigo;
        $this->valorTotal = $valorTotal;
    }

    public function handle(): void
    {
        // 1) Normaliza telefone
        $digits = preg_replace('/\D+/', '', $this->phoneNumber ?? '');
        if (!$digits) {
            Log::warning('Job SendWhatsAppVencidosTemplate: telefone inválido', [
                'phone_provided' => $this->phoneNumber,
                'user_id' => $this->userId,
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
        $from       = preg_replace('/\s+/', '', $from);

        if ($from !== '' && !str_starts_with($from, 'whatsapp:')) {
            $from = 'whatsapp:' . $from;
        }

        // 3) Template aprovado (fixo)
        $contentSid = 'HX0c43ce3c6cf6c9863deee9915965ea77';

        // 4) Variáveis {{1}}, {{2}}, {{3}}
        $vars = [
            '1' => $this->primeiroNome ?: 'amiga(o)',
            '2' => $this->vencimentoMaisAntigo,
            '3' => $this->valorTotal,
        ];

        $contentVars = json_encode($vars, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // 5) Validações
        if ($accountSid === '' || $authToken === '' || $from === '' || $contentSid === '' || $contentVars === false) {
            Log::error('Job SendWhatsAppVencidosTemplate: credenciais/template inválidos', [
                'has_accountSid' => $accountSid !== '',
                'has_authToken' => $authToken !== '',
                'has_from' => $from !== '',
                'has_contentSid' => $contentSid !== '',
                'contentVars_ok' => $contentVars !== false,
                'to' => $to,
                'user_id' => $this->userId,
                'vars' => $vars,
            ]);
            return;
        }

        // 6) Webhook status
        $statusCallback = rtrim((string) config('app.url'), '/') . '/twilio-status';

        // 7) Payload e envio
        $payload = [
            'From' => $from,
            'To' => $to,
            'ContentSid' => $contentSid,
            'ContentVariables' => $contentVars,
            'StatusCallback' => $statusCallback,
        ];

        Log::info('Twilio Vencidos: payload envio template', [
            'to' => $to,
            'from' => $from,
            'user_id' => $this->userId,
            'content_sid' => $contentSid,
            'content_vars' => $vars,
        ]);

        $resp = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", $payload);

        $respJson = $resp->json();
        $sid = $respJson['sid'] ?? null;

        $status = ($resp->successful() && $sid) ? 'queued' : 'failed';
        $failedReason = $resp->successful() ? null : $resp->body();

        // 8) Rastreamento
        DB::table('whatsapp_messages')->insert([
            'user_id' => $this->userId,
            'live_id' => 0,
            'direction' => 'outbound',
            'status' => $status,
            'message_sid' => $sid,
            'account_sid' => $accountSid,
            'from' => $from,
            'to' => $to,
            'message_type' => 'vencidos_template',
            'body' => "TEMPLATE {$contentSid} VARS {$contentVars}",
            'failed_reason' => $failedReason,
            'retry_count' => 0,
            'status_updated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!$resp->successful()) {
            Log::error('Twilio Vencidos: erro envio template', [
                'http_status' => $resp->status(),
                'body' => $resp->body(),
                'json' => $respJson,
                'user_id' => $this->userId,
                'to' => $to,
                'content_sid' => $contentSid,
                'vars' => $vars,
            ]);
        } else {
            Log::info('Twilio Vencidos: template enviado', [
                'user_id' => $this->userId,
                'sid' => $sid,
                'status' => $status,
            ]);
        }
    }
}
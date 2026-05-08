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

class SendWhatsAppVencidosPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $userId;
    protected string $phoneNumber;
    protected string $pdfUrl;
    protected string $messageBody;

    public function __construct(int $userId, string $phoneNumber, string $pdfUrl, string $messageBody)
    {
        $this->userId = $userId;
        $this->phoneNumber = $phoneNumber;
        $this->pdfUrl = $pdfUrl;
        $this->messageBody = $messageBody;
    }

    public function handle(): void
    {
        // 1) Normaliza telefone
        $digits = preg_replace('/\D+/', '', $this->phoneNumber ?? '');
        if (!$digits) {
            Log::warning('Job SendWhatsAppVencidosPdf: telefone inválido', [
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

        if ($accountSid === '' || $authToken === '' || $from === '') {
            Log::error('Job SendWhatsAppVencidosPdf: credenciais Twilio ausentes', [
                'has_accountSid' => $accountSid !== '',
                'has_authToken' => $authToken !== '',
                'has_from' => $from !== '',
                'user_id' => $this->userId,
                'to' => $to,
            ]);
            return;
        }

        // 3) Status callback
        $statusCallback = rtrim((string) config('app.url'), '/') . '/twilio-status';

        // 4) Envia com anexo
        $payload = [
            'From' => $from,
            'To' => $to,
            'Body' => $this->messageBody,
            'MediaUrl' => $this->pdfUrl,
            'StatusCallback' => $statusCallback,
        ];

        Log::info('Twilio Vencidos PDF: payload envio', [
            'user_id' => $this->userId,
            'to' => $to,
            'from' => $from,
            'media_url' => $this->pdfUrl,
        ]);

        $resp = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->withoutVerifying()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", $payload);

        $respJson = $resp->json();
        $sid = $respJson['sid'] ?? null;

        $status = ($resp->successful() && $sid) ? 'queued' : 'failed';
        $failedReason = $resp->successful() ? null : $resp->body();

        // 5) Log no whatsapp_messages
        DB::table('whatsapp_messages')->insert([
            'user_id' => $this->userId,
            'live_id' => 0,
            'direction' => 'outbound',
            'status' => $status,
            'message_sid' => $sid,
            'account_sid' => $accountSid,
            'from' => $from,
            'to' => $to,
            'body' => $this->messageBody,
            'media_url' => $this->pdfUrl,
            'media_content_type' => 'application/pdf',
            'message_type' => 'vencidos',
            'failed_reason' => $failedReason,
            'retry_count' => 0,
            'status_updated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!$resp->successful()) {
            Log::error('Twilio Vencidos PDF: erro envio', [
                'http_status' => $resp->status(),
                'body' => $resp->body(),
                'json' => $respJson,
                'user_id' => $this->userId,
                'to' => $to,
            ]);
        }
    }
}
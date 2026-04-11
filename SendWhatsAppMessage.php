<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phoneNumber;
    protected $liveId;
    protected $userId;

    public function __construct($phoneNumber, $liveId, $userId)
    {
        $this->phoneNumber = $phoneNumber;
        $this->liveId = $liveId;
        $this->userId = $userId;
    }

    public function handle()
    {
        // 1. Formata o número exatamente como no seu WhatsappController
        $digits = preg_replace('/\D+/', '', $this->phoneNumber);
        if (!$digits) return;
        if (!str_starts_with($digits, '55')) $digits = '55' . $digits;
        $to = 'whatsapp:+' . $digits;

        // 2. Pega as credenciais do seu .env
        $accountSid = env('TWILIO_ACCOUNT_SID');
        $authToken  = env('TWILIO_AUTH_TOKEN');
        $from       = env('TWILIO_WHATSAPP_FROM');

        // 3. Mensagem inicial padrão
        $body = "Olá! Sua sacolinha da live #{$this->liveId} está pronta! 🛍️\n\nResponda *CONFIRMAR* para receber o resumo do seu pedido em PDF.";

        // 4. Envia usando a mesma lógica do seu controller
        $resp = Http::withBasicAuth($accountSid, $authToken)->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $from, 
                'To' => $to, 
                'Body' => $body,
            ]);

        // 5. Grava no seu histórico de mensagens
        $sid = $resp->json('sid');
        DB::table('whatsapp_messages')->insert([
            'user_id' => $this->userId, 
            'live_id' => $this->liveId, 
            'direction' => 'outbound',
            'status' => $resp->successful() ? 'queued' : 'failed',
            'message_sid' => $sid, 
            'from' => $from, 
            'to' => $to, 
            'body' => $body,
            'created_at' => now(), 
            'updated_at' => now(),
        ]);

        if (!$resp->successful()) {
            Log::error("Erro Twilio Job: " . $resp->body());
        }
    }
}
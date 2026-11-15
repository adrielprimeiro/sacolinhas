<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EvolutionMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EvolutionWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();
        $event = $payload['event'] ?? '';

        Log::info('EVOLUTION_WEBHOOK_RECEIVED', ['event' => $event]);

        if ($event === 'qrcode.updated') {
            $this->handleQrCodeUpdated($payload['data'] ?? []);
        } elseif ($event === 'messages.upsert') {
            $this->handleMessageUpsert($payload['data'] ?? []);
        } elseif ($event === 'messages.update') {
            $this->handleMessageUpdate($payload['data'] ?? []);
        } elseif ($event === 'connection.update') {
            $this->handleConnectionUpdate($payload['data'] ?? []);
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleQrCodeUpdated(array $data)
    {
        $qrcode = $data['qrcode'] ?? [];
        $base64 = $qrcode['base64'] ?? null;
        $pairingCode = $qrcode['pairingCode'] ?? null;
        $instance = $qrcode['instance'] ?? 'sacolinhas';

        if ($base64) {
            \Illuminate\Support\Facades\Cache::put('evolution_qr_code_' . $instance, [
                'base64' => $base64,
                'pairingCode' => $pairingCode,
                'updated_at' => now()->toDateTimeString(),
            ], now()->addMinutes(2));
            Log::info('EVOLUTION_QR_CACHED', ['instance' => $instance]);
        }
    }

    protected function handleConnectionUpdate(array $data)
    {
        $state = $data['state'] ?? '';
        $instance = $data['instance'] ?? 'sacolinhas';
        if ($state === 'open' || $state === 'connected') {
            \Illuminate\Support\Facades\Cache::forget('evolution_qr_code_' . $instance);
        }
    }

    protected function handleMessageUpsert(array $data)
    {
        $key = $data['key'] ?? [];
        $fromMe = $key['fromMe'] ?? false;
        $remoteJid = $key['remoteJid'] ?? '';
        $messageId = $key['id'] ?? null;
        $pushName = $data['pushName'] ?? null;

        // Ignora status de broadcast/stories ou grupos
        if (str_contains($remoteJid, '@broadcast') || str_contains($remoteJid, '@g.us')) {
            return;
        }

        if (!$remoteJid) {
            return;
        }

        $originalJid = $remoteJid;
        if (str_contains($remoteJid, '@lid')) {
            $resolved = app(\App\Services\Whatsapp\EvolutionApiService::class)->resolveLidToPhoneNumber($remoteJid);
            if ($resolved) {
                $remoteJid = $resolved;
                // Unifica mensagens antigas que vieram com o @lid cru
                EvolutionMessage::where('remote_jid', $originalJid)->update(['remote_jid' => $remoteJid]);
            }
        }

        $digits = preg_replace('/\D+/', '', explode('@', $remoteJid)[0] ?? '');

        // Tenta achar usuário cadastrado pelo WhatsApp
        $user = null;
        if (strlen($digits) >= 8) {
            $user = $this->findUserByDigits($digits);
        }

        // Se o pushName veio vazio na mensagem (ex: outbound), tenta recuperar push_name conhecido
        if (empty($pushName)) {
            $pushName = EvolutionMessage::where('remote_jid', $remoteJid)
                ->whereNotNull('push_name')
                ->where('push_name', '!=', '')
                ->value('push_name');
        }

        // Extrai texto da mensagem
        $messageContent = $data['message'] ?? [];
        $body = $messageContent['conversation'] 
            ?? $messageContent['extendedTextMessage']['text'] 
            ?? $messageContent['imageMessage']['caption'] 
            ?? $messageContent['videoMessage']['caption'] 
            ?? $messageContent['documentMessage']['caption'] 
            ?? '';

        $mediaType = null;
        $mediaUrl = null;

        if (isset($messageContent['imageMessage'])) {
            $mediaType = 'image';
        } elseif (isset($messageContent['audioMessage'])) {
            $mediaType = 'audio';
        } elseif (isset($messageContent['videoMessage'])) {
            $mediaType = 'video';
        } elseif (isset($messageContent['documentMessage'])) {
            $mediaType = 'document';
        }

        // Evita duplicatas pelo messageId
        if ($messageId && EvolutionMessage::where('message_id', $messageId)->exists()) {
            return;
        }

        EvolutionMessage::create([
            'user_id' => $user?->id,
            'remote_jid' => $remoteJid,
            'push_name' => $pushName,
            'direction' => $fromMe ? 'outbound' : 'inbound',
            'status' => $fromMe ? 'sent' : 'received',
            'message_id' => $messageId,
            'from' => $fromMe ? 'evolution' : ($digits ?: $remoteJid),
            'to' => $fromMe ? ($digits ?: $remoteJid) : 'evolution',
            'body' => $body,
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
            'raw_payload' => $data,
        ]);
    }

    protected function handleMessageUpdate(array $data)
    {
        $key = $data['key'] ?? [];
        $messageId = $key['id'] ?? null;
        $status = strtolower($data['status'] ?? '');

        if (!$messageId) return;

        $mappedStatus = match ($status) {
            'delivery_ack', 'delivered' => 'delivered',
            'read', 'played' => 'read',
            'error' => 'failed',
            default => null,
        };

        if ($mappedStatus) {
            EvolutionMessage::where('message_id', $messageId)->update(['status' => $mappedStatus]);
        }
    }

    protected function findUserByDigits(string $digits): ?User
    {
        $without55 = str_starts_with($digits, '55') ? substr($digits, 2) : $digits;
        $with55 = '55' . $without55;

        $candidates = array_unique([$digits, $without55, $with55]);

        return User::whereIn('whatsapp', $candidates)
            ->orWhereIn('phone', $candidates)
            ->first();
    }
}

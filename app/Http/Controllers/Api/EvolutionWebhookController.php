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
        $altJid = $key['remoteJidAlt'] ?? ($key['senderPn'] ?? null);
        if (str_contains($remoteJid, '@lid')) {
            if (is_string($altJid) && str_ends_with($altJid, '@s.whatsapp.net')) {
                $remoteJid = $altJid;
                $altPhone = preg_replace('/\D+/', '', explode('@', $altJid)[0] ?? '');
                \App\Models\EvolutionContactMapping::updateOrCreate(
                    ['lid' => $originalJid],
                    ['phone' => $altPhone, 'remote_jid' => $altJid, 'name' => $pushName]
                );
                EvolutionMessage::where('remote_jid', $originalJid)->update(['remote_jid' => $remoteJid]);
            } else {
                $resolved = app(\App\Services\Whatsapp\EvolutionApiService::class)->resolveLidToPhoneNumber($remoteJid, $pushName);
                if ($resolved) {
                    $remoteJid = $resolved;
                    // Unifica mensagens antigas que vieram com o @lid cru
                    EvolutionMessage::where('remote_jid', $originalJid)->update(['remote_jid' => $remoteJid]);
                }
            }
        }

        $digits = preg_replace('/\D+/', '', explode('@', $remoteJid)[0] ?? '');

        // Tenta achar usuário cadastrado pelo WhatsApp
        $user = null;
        if (!str_contains($remoteJid, '@lid') && strlen($digits) >= 8) {
            $user = $this->findUserByDigits($digits);
        }

        if ($fromMe) {
            // Mensagem enviada pelo admin/brechó: o pushName no payload do WhatsApp é o nome do aparelho (ex: Adriel).
            // Recuperamos o nome real do contato/destinatário.
            $pushName = $user?->name 
                ?: \App\Models\EvolutionContactMapping::where('remote_jid', $remoteJid)->orWhere('phone', $digits)->value('name')
                ?: EvolutionMessage::where('remote_jid', $remoteJid)->where('direction', 'inbound')->whereNotNull('push_name')->latest('id')->value('push_name');
        } else {
            // Mensagem recebida do contato: salva/atualiza mapeamento com o nome do cliente (apenas se já for @s.whatsapp.net)
            if ($pushName && $remoteJid && !str_contains($remoteJid, '@lid')) {
                \App\Models\EvolutionContactMapping::updateOrCreate(
                    ['remote_jid' => $remoteJid],
                    ['name' => $user?->name ?: $pushName, 'phone' => $digits]
                );
            }
            if ($user?->name) {
                $pushName = $user->name;
            }
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

        // Ignora eventos internos de protocolo sem texto e sem mídia
        if (trim((string) $body) === '' && !$mediaType) {
            return;
        }

        // Evita duplicatas pelo messageId (ex: mensagens já gravadas pelo sistema/Severino)
        if ($messageId && (EvolutionMessage::where('message_id', $messageId)->exists() || \Illuminate\Support\Facades\Cache::has('evo_api_sent_' . $messageId))) {
            return;
        }

        // Verifica se há um contexto de live/teste ativo para este número
        $testCtx = \App\Services\Ai\SeverinoWhatsappAgentService::getTestContextForPhone($remoteJid);
        $isTestSession = !empty($testCtx) && ($testCtx['is_test'] ?? true);
        if ($testCtx && !empty($testCtx['user_id'])) {
            $testUser = User::find($testCtx['user_id']);
            if ($testUser) {
                $user = $testUser;
                $pushName = $isTestSession
                    ? "🧪 [Teste: {$testUser->name}]"
                    : ($pushName ?: $testUser->name);
            }
        }

        // Se a mensagem veio do próprio aparelho conectado (self-chat de teste) digitada manualmente no celular (não enviada pela API),
        // e existe um contexto de TESTE ativo, tratamos como resposta de teste da cliente para o Severino responder!
        $isSelfChatTestReply = false;
        if ($fromMe && $isTestSession && str_ends_with($digits, '84136870')) {
            $isSelfChatTestReply = true;
            $fromMe = false;
        }

        // Se um operador humano respondeu manualmente pelo celular para outro contato, pausa o Severino automaticamente
        if ($fromMe && !$isSelfChatTestReply) {
            \App\Services\Ai\SeverinoWhatsappAgentService::setSupervisionMode($remoteJid, $user?->id, 'human', 'Operador respondeu pelo celular');
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

        // Se é mensagem recebida da cliente (ou simulação de teste), aciona o Severino se estiver ativo na conversa
        if (!$fromMe && trim((string) $body) !== '') {
            try {
                app(\App\Services\Ai\SeverinoWhatsappAgentService::class)->handleIncomingClientMessage(
                    $remoteJid,
                    $user?->id,
                    trim((string) $body)
                );
            } catch (\Throwable $e) {
                Log::error('Erro ao acionar SeverinoWhatsappAgentService no webhook: ' . $e->getMessage());
            }
        }
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
        $digits = ltrim(preg_replace('/\D+/', '', $digits), '0');
        $without55 = (strlen($digits) >= 12 && str_starts_with($digits, '55')) ? substr($digits, 2) : $digits;
        $with55 = (strlen($without55) === 10 || strlen($without55) === 11) ? ('55' . $without55) : $digits;

        $candidates = array_values(array_unique(array_filter([$digits, $without55, $with55])));

        return User::whereIn('whatsapp', $candidates)
            ->orWhereIn('phone', $candidates)
            ->first();
    }
}

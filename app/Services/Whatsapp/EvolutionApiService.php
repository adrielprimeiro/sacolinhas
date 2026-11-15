<?php

namespace App\Services\Whatsapp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionApiService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $instanceName;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.evolution.base_url', env('EVOLUTION_API_URL', 'http://127.0.0.1:8085')), '/');
        $this->apiKey = (string) config('services.evolution.api_key', env('EVOLUTION_API_KEY', 'B65C47B090475C1104914E6E33EA643F'));
        $this->instanceName = (string) config('services.evolution.instance', env('EVOLUTION_INSTANCE_NAME', 'sacolinhas'));
    }

    protected function client()
    {
        return Http::withHeaders([
            'apikey' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(15)->withoutVerifying();
    }

    /**
     * Verifica status da conexão da instância
     */
    public function getConnectionStatus(): array
    {
        try {
            $resp = $this->client()->get("{$this->baseUrl}/instance/connectionState/{$this->instanceName}");
            if ($resp->successful()) {
                return [
                    'success' => true,
                    'state' => $resp->json('instance.state') ?? $resp->json('state') ?? 'disconnected',
                    'data' => $resp->json(),
                ];
            }
            return [
                'success' => false,
                'state' => 'error',
                'message' => $resp->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'state' => 'unreachable',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cria a instância se não existir e gera/retorna QR Code
     */
    public function getOrConnectQrCode(): array
    {
        // 1) Primeiro verifica se o Webhook recebeu o QR Code recente no Cache
        $cached = \Illuminate\Support\Facades\Cache::get('evolution_qr_code_' . $this->instanceName);
        if ($cached && !empty($cached['base64'])) {
            return [
                'success' => true,
                'base64' => $cached['base64'],
                'pairingCode' => $cached['pairingCode'] ?? null,
            ];
        }

        try {
            // Tenta conectar instância existente
            $connectResp = $this->client()->get("{$this->baseUrl}/instance/connect/{$this->instanceName}");
            if ($connectResp->successful()) {
                $data = $connectResp->json();
                if (!empty($data['base64'])) {
                    return [
                        'success' => true,
                        'base64' => $data['base64'],
                        'code' => $data['code'] ?? null,
                        'pairingCode' => $data['pairingCode'] ?? null,
                    ];
                }
            }

            // Se ainda não tem no cache nem na resposta direta, força restart da conexão para emitir novo QR
            $this->client()->post("{$this->baseUrl}/instance/restart/{$this->instanceName}");

            return [
                'success' => true,
                'base64' => null,
                'message' => 'Gerando QR Code... aguarde 2 segundos e atualize.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Desconecta (logout) da instância
     */
    public function logout(): array
    {
        try {
            $resp = $this->client()->delete("{$this->baseUrl}/instance/logout/{$this->instanceName}");
            return ['success' => $resp->successful(), 'body' => $resp->json()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Mapeia um LID do WhatsApp (@lid) para o número real (@s.whatsapp.net)
     */
    public function resolveLidToPhoneNumber(string $remoteJid): ?string
    {
        if (!str_contains($remoteJid, '@lid')) {
            return $remoteJid;
        }

        return \Illuminate\Support\Facades\Cache::remember('evolution_lid_map_' . $remoteJid, now()->addHours(24), function () use ($remoteJid) {
            try {
                $resp = $this->client()->post("{$this->baseUrl}/chat/findContacts/{$this->instanceName}", []);
                if (!$resp->successful()) {
                    return null;
                }

                $contacts = $resp->json() ?: [];
                $target = null;
                foreach ($contacts as $c) {
                    if (($c['remoteJid'] ?? '') === $remoteJid) {
                        $target = $c;
                        break;
                    }
                }
                if (!$target) {
                    return null;
                }

                $targetPic = $target['profilePicUrl'] ?? null;
                $targetName = $target['pushName'] ?? null;

                // 1) Match por profilePicUrl (foto idêntica no WhatsApp)
                if ($targetPic) {
                    foreach ($contacts as $c) {
                        $jid = $c['remoteJid'] ?? '';
                        if (str_ends_with($jid, '@s.whatsapp.net') && ($c['profilePicUrl'] ?? '') === $targetPic) {
                            return $jid;
                        }
                    }
                }

                // 2) Match por pushName (nome do perfil no WhatsApp)
                if ($targetName) {
                    foreach ($contacts as $c) {
                        $jid = $c['remoteJid'] ?? '';
                        if (str_ends_with($jid, '@s.whatsapp.net') && strcasecmp($c['pushName'] ?? '', $targetName) === 0) {
                            return $jid;
                        }
                    }
                }

                return null;
            } catch (\Throwable $e) {
                Log::warning('Error resolving LID to phone', ['lid' => $remoteJid, 'error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Configura Webhook da instância para apontar para o Laravel
     */
    public function setWebhook(string $webhookUrl): array
    {
        try {
            $payload = [
                'enabled' => true,
                'url' => $webhookUrl,
                'byEvents' => false,
                'base64' => false,
                'events' => [
                    'MESSAGES_UPSERT',
                    'MESSAGES_UPDATE',
                    'CONNECTION_UPDATE',
                ],
            ];

            $resp = $this->client()->post("{$this->baseUrl}/webhook/set/{$this->instanceName}", $payload);
            return [
                'success' => $resp->successful(),
                'data' => $resp->json(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envia mensagem de texto
     */
    public function sendText(string $to, string $text): array
    {
        $target = $to;
        if (str_contains($target, '@lid')) {
            $resolved = $this->resolveLidToPhoneNumber($target);
            if ($resolved) {
                $target = $resolved;
            }
        }

        $digits = preg_replace('/\D+/', '', explode('@', $target)[0] ?? '');
        if ($digits && !str_starts_with($digits, '55')) {
            $digits = '55' . $digits;
        }

        $number = $digits ?: $target;

        try {
            $payload = [
                'number' => $number,
                'text' => $text,
                'delay' => 1200, // delay de digitação em ms
                'linkPreview' => true,
            ];

            $resp = $this->client()->post("{$this->baseUrl}/message/sendText/{$this->instanceName}", $payload);
            $json = $resp->json();

            $errorMessage = null;
            if (!$resp->successful()) {
                $errorObj = $json['response']['message'] ?? $json['message'] ?? $json['error'] ?? null;
                if (is_array($errorObj)) {
                    $errorMessage = json_encode($errorObj, JSON_UNESCAPED_UNICODE);
                } else {
                    $errorMessage = (string) ($errorObj ?: $resp->body());
                }
            }

            return [
                'success' => $resp->successful(),
                'message_id' => $json['key']['id'] ?? null,
                'status' => $resp->successful() ? 'sent' : 'failed',
                'error' => $errorMessage,
                'raw' => $json ?? $resp->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('Evolution API sendText error', ['error' => $e->getMessage(), 'to' => $number]);
            return [
                'success' => false,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envia arquivo ou mídia (imagem, pdf, audio, doc)
     */
    public function sendMedia(string $to, string $mediaUrl, string $caption = '', string $mediaType = 'document', string $fileName = 'arquivo.pdf'): array
    {
        $target = $to;
        if (str_contains($target, '@lid')) {
            $resolved = $this->resolveLidToPhoneNumber($target);
            if ($resolved) {
                $target = $resolved;
            }
        }

        $digits = preg_replace('/\D+/', '', explode('@', $target)[0] ?? '');
        if ($digits && !str_starts_with($digits, '55')) {
            $digits = '55' . $digits;
        }

        $number = $digits ?: $target;

        try {
            $payload = [
                'number' => $number,
                'mediatype' => $mediaType,
                'mimetype' => $this->guessMimeType($fileName),
                'caption' => $caption,
                'media' => $mediaUrl,
                'fileName' => $fileName,
            ];

            $resp = $this->client()->post("{$this->baseUrl}/message/sendMedia/{$this->instanceName}", $payload);
            $json = $resp->json();

            $errorMessage = null;
            if (!$resp->successful()) {
                $errorObj = $json['response']['message'] ?? $json['message'] ?? $json['error'] ?? null;
                if (is_array($errorObj)) {
                    $errorMessage = json_encode($errorObj, JSON_UNESCAPED_UNICODE);
                } else {
                    $errorMessage = (string) ($errorObj ?: $resp->body());
                }
            }

            return [
                'success' => $resp->successful(),
                'message_id' => $json['key']['id'] ?? null,
                'status' => $resp->successful() ? 'sent' : 'failed',
                'error' => $errorMessage,
                'raw' => $json ?? $resp->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('Evolution API sendMedia error', ['error' => $e->getMessage(), 'to' => $number]);
            return [
                'success' => false,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function guessMimeType(string $fileName): string
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return match ($ext) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
            default => 'application/octet-stream',
        };
    }
}

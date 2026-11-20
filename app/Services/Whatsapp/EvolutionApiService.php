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
     * Mapeia um LID do WhatsApp (@lid) para o número real (@s.whatsapp.net) de forma SEGURA.
     * NUNCA chuta um contato aleatório da agenda só pelo primeiro nome!
     */
    public function resolveLidToPhoneNumber(string $remoteJid, ?string $fallbackPushName = null): ?string
    {
        if (!str_contains($remoteJid, '@lid')) {
            return $remoteJid;
        }

        // 1) Checa primeiro no banco persistente
        $existing = \App\Models\EvolutionContactMapping::where('lid', $remoteJid)->first();
        if ($existing && $existing->remote_jid && str_ends_with($existing->remote_jid, '@s.whatsapp.net')) {
            return $existing->remote_jid;
        }

        $cacheKey = 'evolution_lid_map_v2_' . $remoteJid;
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if (is_string($cached) && str_ends_with($cached, '@s.whatsapp.net')) {
            return $cached;
        }

        try {
            $resp = $this->client()->post("{$this->baseUrl}/chat/findContacts/{$this->instanceName}", []);
            $contacts = $resp->successful() ? ($resp->json() ?: []) : [];

            $target = null;
            foreach ($contacts as $c) {
                if (($c['remoteJid'] ?? '') === $remoteJid) {
                    $target = $c;
                    break;
                }
            }

            $targetPic = $target['profilePicUrl'] ?? null;
            $targetName = trim((string) (($target['pushName'] ?? '') ?: ($fallbackPushName ?? '')));
            $matchedJid = null;

            // 1) Match por PATH da profilePicUrl (identificador único real no WhatsApp)
            if ($targetPic && !empty($contacts)) {
                $targetPath = parse_url($targetPic, PHP_URL_PATH);
                if ($targetPath && strlen($targetPath) > 10) {
                    foreach ($contacts as $c) {
                        $jid = $c['remoteJid'] ?? '';
                        if (str_ends_with($jid, '@s.whatsapp.net')) {
                            $cPath = parse_url($c['profilePicUrl'] ?? '', PHP_URL_PATH);
                            if ($cPath && $cPath === $targetPath) {
                                $matchedJid = $jid;
                                break;
                            }
                        }
                    }
                }
            }

            // 2) Match seguro por CONVERSA OUTBOUND RECENTE (últimas 24h em evolution_messages):
            //    Ex: enviamos peças da live para "Aline Freitas de Oliveira" (5519...) e minutos depois chega resposta de @lid com pushName "Aline".
            //    Só aceita se houver match ÚNICO entre as pessoas contatadas recentemente!
            if (!$matchedJid && $targetName !== '') {
                $normTarget = mb_strtolower($targetName);
                $targetFirst = explode(' ', $normTarget)[0] ?? '';

                if (mb_strlen($targetFirst) >= 3) {
                    $recentOutbound = \App\Models\EvolutionMessage::where('direction', 'outbound')
                        ->where('status', '!=', 'failed')
                        ->where('remote_jid', 'like', '%@s.whatsapp.net')
                        ->where('created_at', '>=', now()->subHours(24))
                        ->orderByDesc('id')
                        ->get(['remote_jid', 'user_id', 'push_name', 'created_at']);

                    $candidates = [];
                    $veryRecentCandidates = [];
                    $threeMinAgo = now()->subMinutes(3);

                    $userIds = $recentOutbound->pluck('user_id')->filter()->unique()->values()->toArray();
                    $usersById = !empty($userIds)
                        ? \App\Models\User::whereIn('id', $userIds)->get(['id', 'name', 'apelido'])->keyBy('id')
                        : collect();

                    foreach ($recentOutbound as $row) {
                        $jid = (string) $row->remote_jid;
                        if (isset($candidates[$jid])) {
                            continue;
                        }
                        $u = $row->user_id ? $usersById->get($row->user_id) : null;
                        $namesToCheck = array_filter([
                            trim((string) ($row->push_name ?? '')),
                            trim((string) ($u?->name ?? '')),
                            trim((string) ($u?->apelido ?? '')),
                        ]);

                        $matchedThis = false;
                        foreach ($namesToCheck as $candName) {
                            $candLower = mb_strtolower($candName);
                            $candFirst = explode(' ', $candLower)[0] ?? '';
                            if ($candLower === $normTarget || $candFirst === $targetFirst || str_starts_with($candLower, $normTarget . ' ')) {
                                $matchedThis = true;
                                break;
                            }
                        }

                        if ($matchedThis) {
                            $candidates[$jid] = $row;
                            if ($row->created_at && $row->created_at->gte($threeMinAgo)) {
                                $veryRecentCandidates[$jid] = $row;
                            }
                        }
                    }

                    if (count($candidates) === 1) {
                        $matchedJid = array_key_first($candidates);
                    } elseif (count($veryRecentCandidates) === 1) {
                        $matchedJid = array_key_first($veryRecentCandidates);
                    }
                }
            }

            // 3) Match na agenda completa APENAS se o nome tiver sobrenome (>= 2 palavras) e for EXATO e ÚNICO!
            //    (Proibido chutar por primeiro nome em toda a agenda!)
            if (!$matchedJid && $targetName !== '' && str_contains($targetName, ' ') && !empty($contacts)) {
                $exactMatches = [];
                foreach ($contacts as $c) {
                    $jid = $c['remoteJid'] ?? '';
                    if (str_ends_with($jid, '@s.whatsapp.net')) {
                        $name = trim((string) ($c['pushName'] ?? ''));
                        if ($name !== '' && strcasecmp($name, $targetName) === 0) {
                            $exactMatches[$jid] = true;
                        }
                    }
                }
                if (count($exactMatches) === 1) {
                    $matchedJid = array_key_first($exactMatches);
                }
            }

            if ($matchedJid) {
                $phone = preg_replace('/\D+/', '', explode('@', $matchedJid)[0] ?? '');
                \App\Models\EvolutionContactMapping::updateOrCreate(
                    ['lid' => $remoteJid],
                    [
                        'phone' => $phone,
                        'remote_jid' => $matchedJid,
                        'name' => $targetName ?: null,
                    ]
                );
                \Illuminate\Support\Facades\Cache::put($cacheKey, $matchedJid, now()->addHours(24));
                return $matchedJid;
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('Error resolving LID to phone', ['lid' => $remoteJid, 'error' => $e->getMessage()]);
            return null;
        }
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
     * Normaliza telefone brasileiro garantindo o DDI 55 corretamente — inclusive para DDD 55 (interior do RS)!
     * - 10 ou 11 dígitos (DDD + número): sempre adiciona '55' (ex: 55991810652 -> 5555991810652)
     * - 12 ou 13 dígitos começando com 55: já possui DDI (ex: 5548984136870 ou 5555991810652)
     */
    public static function normalizeBrazilPhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', explode('@', $raw)[0] ?? '');
        if ($digits === '') {
            return '';
        }

        // Remove zeros à esquerda (ex: 055991810652 -> 55991810652)
        $digits = ltrim($digits, '0');

        if (strlen($digits) === 10 || strlen($digits) === 11) {
            return '55' . $digits;
        }

        return $digits;
    }

    /**
     * Envia mensagem de texto
     */
    public function sendText(string $to, string $text, int $delay = 1500): array
    {
        $target = $to;
        if (str_contains($target, '@lid')) {
            $resolved = $this->resolveLidToPhoneNumber($target);
            if ($resolved) {
                $target = $resolved;
            }
        }

        $digits = self::normalizeBrazilPhone($target);
        $number = $digits ?: $target;

        try {
            $payload = [
                'number' => $number,
                'text' => $text,
                'delay' => $delay, // delay de digitação em ms
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

            $msgId = $json['key']['id'] ?? null;
            if ($msgId) {
                \Illuminate\Support\Facades\Cache::put('evo_api_sent_' . $msgId, true, 600);
            }

            return [
                'success' => $resp->successful(),
                'message_id' => $msgId,
                'remote_jid' => $json['key']['remoteJid'] ?? ($digits ? $digits . '@s.whatsapp.net' : $target),
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
     * Envia arquivo ou mídia (imagem, pdf, audio, doc) via URL pública ou Base64 puro
     */
    public function sendMedia(
        string $to,
        string $mediaUrlOrBase64,
        string $caption = '',
        string $mediaType = 'document',
        string $fileName = 'arquivo.pdf',
        int $delay = 1500
    ): array {
        $target = $to;
        if (str_contains($target, '@lid')) {
            $resolved = $this->resolveLidToPhoneNumber($target);
            if ($resolved) {
                $target = $resolved;
            }
        }

        $digits = self::normalizeBrazilPhone($target);
        $number = $digits ?: $target;

        // Evolution API v2 aceita URL ou base64 puro (sem prefixo data:...;base64,)
        $mediaPayload = $mediaUrlOrBase64;
        if (str_starts_with($mediaPayload, 'data:') && str_contains($mediaPayload, ';base64,')) {
            $mediaPayload = substr($mediaPayload, strpos($mediaPayload, ';base64,') + 8);
        }

        try {
            $payload = [
                'number' => $number,
                'mediatype' => $mediaType,
                'mimetype' => $this->guessMimeType($fileName),
                'caption' => $caption,
                'media' => $mediaPayload,
                'fileName' => $fileName,
                'delay' => max(0, $delay),
            ];

            $resp = $this->client()->timeout(30)->post("{$this->baseUrl}/message/sendMedia/{$this->instanceName}", $payload);
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

            $msgId = $json['key']['id'] ?? null;
            if ($msgId) {
                \Illuminate\Support\Facades\Cache::put('evo_api_sent_' . $msgId, true, 600);
            }

            return [
                'success' => $resp->successful(),
                'message_id' => $msgId,
                'remote_jid' => $json['key']['remoteJid'] ?? ($digits ? $digits . '@s.whatsapp.net' : $target),
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

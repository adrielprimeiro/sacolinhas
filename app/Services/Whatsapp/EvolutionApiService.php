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
        try {
            // Tenta conectar instância existente
            $connectResp = $this->client()->get("{$this->baseUrl}/instance/connect/{$this->instanceName}");
            if ($connectResp->successful()) {
                $data = $connectResp->json();
                return [
                    'success' => true,
                    'base64' => $data['base64'] ?? null,
                    'code' => $data['code'] ?? null,
                    'pairingCode' => $data['pairingCode'] ?? null,
                ];
            }

            // Se retornar erro 404 (instância não existe), cria
            $createPayload = [
                'instanceName' => $this->instanceName,
                'token' => $this->apiKey,
                'qrcode' => true,
                'integration' => 'WHATSAPP-BAILEYS',
            ];

            $createResp = $this->client()->post("{$this->baseUrl}/instance/create", $createPayload);
            if ($createResp->successful()) {
                $data = $createResp->json();
                $qrcode = $data['qrcode'] ?? [];
                return [
                    'success' => true,
                    'base64' => $qrcode['base64'] ?? null,
                    'code' => $qrcode['code'] ?? null,
                    'pairingCode' => $qrcode['pairingCode'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => 'Erro ao criar/conectar instância: ' . $createResp->body(),
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
        $number = preg_replace('/\D+/', '', $to);
        if (!str_starts_with($number, '55')) {
            $number = '55' . $number;
        }

        try {
            $payload = [
                'number' => $number,
                'text' => $text,
                'delay' => 1200, // delay de digitação em ms
                'linkPreview' => true,
            ];

            $resp = $this->client()->post("{$this->baseUrl}/message/sendText/{$this->instanceName}", $payload);
            $json = $resp->json();

            return [
                'success' => $resp->successful(),
                'message_id' => $json['key']['id'] ?? null,
                'status' => $resp->successful() ? 'sent' : 'failed',
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
        $number = preg_replace('/\D+/', '', $to);
        if (!str_starts_with($number, '55')) {
            $number = '55' . $number;
        }

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

            return [
                'success' => $resp->successful(),
                'message_id' => $json['key']['id'] ?? null,
                'status' => $resp->successful() ? 'sent' : 'failed',
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

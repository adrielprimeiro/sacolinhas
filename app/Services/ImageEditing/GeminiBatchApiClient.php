<?php

namespace App\Services\ImageEditing;

use Illuminate\Support\Facades\Http;

class GeminiBatchApiClient
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.api_key');
        $this->baseUrl = rtrim((string) config('services.gemini.base_url'), '/');
    }

    public function uploadBatchFile(string $path): array
    {
        $url = str_replace('generativelanguage.googleapis.com/', 'generativelanguage.googleapis.com/upload/', $this->baseUrl) . '/files?key=' . $this->apiKey;

        $response = Http::withHeaders(['X-Goog-Upload-Protocol' => 'multipart'])
            ->attach('metadata', json_encode(['file' => ['displayName' => basename($path)]]), 'metadata.json')
            ->attach('file', file_get_contents($path), basename($path))
            ->post($url);

        $data = $response->json();
        return [
            'success' => $response->successful(),
            'file_name' => $data['name'] ?? null,
            'raw' => $data
        ];
    }

    public function createBatchJob(string $inputFileName): array
    {
        // 1. Usamos o endpoint de compatibilidade OpenAI do Gemini
        $url = "{$this->baseUrl}/openai/batches";

        // 2. O payload muda para o padrão exigido pela OpenAI
        $payload = [
            'input_file_id' => $inputFileName, // ex: "files/mlwsexkv573c"
            'endpoint' => '/v1/chat/completions',
            'completion_window' => '24h'
        ];

        // 3. A rota OpenAI do Gemini prefere autenticação via Bearer Token
        $response = Http::withToken($this->apiKey)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $payload);

        $data = $response->json();

        if (!$response->successful()) {
            return [
                'success' => false,
                'status_code' => $response->status(),
                'error' => $data['error']['message'] ?? 'Erro desconhecido',
                'raw' => $data ?: $response->body()
            ];
        }

        return [
            'success' => true,
            'batch_id' => $data['id'] ?? null, // No formato OpenAI, o ID vem direto no campo 'id'
            'raw' => $data
        ];
    }
}
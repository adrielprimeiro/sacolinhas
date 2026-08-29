<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.paid_api_key') ?: (config('services.gemini.api_key') ?: env('GEMINI_API_KEY', ''));
    }

    /**
     * Gera o embedding de um texto usando text-embedding-004 do Gemini API.
     * 
     * @param string $text
     * @return array|null Array de float (vetor 768 posições)
     */
    public function generateEmbedding(string $text): ?array
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API key não configurada.');
            return null;
        }

        try {
            $url = "{$this->baseUrl}/models/gemini-embedding-2:embedContent?key={$this->apiKey}";
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'model' => 'models/gemini-embedding-2',
                'content' => [
                    'parts' => [
                        ['text' => mb_substr($text, 0, 8000)]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['embedding']['values'] ?? null;
            }

            Log::error('Erro ao gerar embedding Gemini: ' . $response->body());
            return null;
        } catch (Exception $e) {
            Log::error('Exceção ao gerar embedding Gemini: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Gera uma resposta com o modelo Gemini usando prompt, instrução de sistema e histórico opcional.
     * 
     * @param string $userPrompt
     * @param string|null $systemInstruction
     * @param array $history Array de mensages no formato [['role' => 'user'|'model', 'parts' => [['text' => '...']]]]
     * @return string
     */
    public function generateAnswer(string $userPrompt, ?string $systemInstruction = null, array $history = []): string
    {
        $groqKey = env('GROQ_API_KEY');
        if (empty($groqKey)) {
            return "Chave da API da Groq não configurada.";
        }

        $messages = [];
        
        if ($systemInstruction) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemInstruction
            ];
        }

        foreach ($history as $msg) {
            $role = $msg['role'] === 'model' || $msg['role'] === 'assistant' ? 'assistant' : 'user';
            $text = $msg['text'] ?? $msg['message'] ?? '';
            $messages[] = [
                'role' => $role,
                'content' => $text
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userPrompt
        ];

        $payload = [
            'messages' => $messages,
            'temperature' => 0.4,
            'max_tokens' => 1024,
        ];

        $modelsToTry = ['qwen/qwen3.8-27b', 'openai/gpt-oss-120b', 'groq/compound'];

        try {
            foreach ($modelsToTry as $modelName) {
                $payload['model'] = $modelName;
                
                $response = Http::withToken($groqKey)
                    ->timeout(10)
                    ->post("https://api.groq.com/openai/v1/chat/completions", $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['choices'][0]['message']['content'] ?? null;
                    if ($text) {
                        return trim($text);
                    }
                }

                if ($response->status() == 429) {
                    Log::warning("Groq Rate Limit no modelo {$modelName}. Tentando próximo...");
                    continue; // Tenta o próximo
                }

                Log::warning("Groq modelo {$modelName} falhou ({$response->status()}): {$response->body()}");
            }
        } catch (Exception $e) {
            Log::warning("Exceção chamando Groq: " . $e->getMessage());
        }

        return "Desculpe, tive um problema ao tentar responder sua pergunta no momento. Por favor, tente novamente em instantes.";
    }
}

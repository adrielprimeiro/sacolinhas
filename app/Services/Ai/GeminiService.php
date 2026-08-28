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
        if (empty($this->apiKey)) {
            return "Desculpe, a chave da API do Gemini não está configurada no servidor.";
        }

        $modelsToTry = ['gemini-2.5-flash', 'gemini-2.5-flash', 'gemini-2.5-flash'];

        $contents = [];
        $lastRole = null;
        foreach ($history as $msg) {
            $role = $msg['role'] === 'assistant' ? 'model' : $msg['role'];
            $text = $msg['text'] ?? $msg['message'] ?? '';
            
            if ($lastRole === $role) {
                // Junta mensagens do mesmo role para evitar erro da API do Gemini
                $contents[count($contents) - 1]['parts'][0]['text'] .= "\n" . $text;
            } else {
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $text]]
                ];
                $lastRole = $role;
            }
        }
        
        // Se a última mensagem do histórico for user, a próxima seria user (o prompt). 
        // Vamos forçar que a última do histórico seja model, ou juntar o prompt.
        if ($lastRole === 'user') {
            $contents[count($contents) - 1]['parts'][0]['text'] .= "\n" . $userPrompt;
        } else {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $userPrompt]]
            ];
        }

        // A primeira mensagem DEVE ser 'user' para o Gemini
        if (!empty($contents) && $contents[0]['role'] === 'model') {
            array_shift($contents);
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 1024,
            ]
        ];

        if ($systemInstruction) {
            $payload['system_instruction'] = [
                'parts' => [['text' => $systemInstruction]]
            ];
        }

        foreach ($modelsToTry as $model) {
            try {
                $url = "{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}";
                $response = Http::timeout(15)->post($url, $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if ($text) {
                        return trim($text);
                    }
                }

                if ($response->status() == 429) {
                    sleep(2);
                }

                Log::warning("Gemini model {$model} falhou ({$response->status()}): {$response->body()}");
            } catch (Exception $e) {
                Log::warning("Exceção chamando Gemini modelo {$model}: " . $e->getMessage());
            }
        }

        return "Desculpe, tive um problema ao tentar responder sua pergunta no momento. Por favor, tente novamente em instantes.";
    }
}

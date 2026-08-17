<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class DemandParserService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.paid_api_key') ?: (config('services.gemini.api_key') ?: env('GEMINI_API_KEY', ''));
    }

    /**
     * Faz parsing do texto livre da cliente usando a API do Gemini com Structured Output (JSON)
     *
     * @param string $rawPrompt O texto digitado pela cliente
     * @return array|null Retorna o array de atributos extraídos ou null em caso de erro
     */
    public function parseDemand(string $rawPrompt): ?array
    {
        if (empty($this->apiKey)) {
            Log::error('DemandParserService: Chave de API do Gemini não configurada.');
            return null;
        }

        // System prompt estrito exigindo um JSON com os campos solicitados
        $systemInstruction = "Você é um assistente especialista em moda que extrai intenções de compra. " .
                             "Analise o texto da cliente e extraia os atributos. " .
                             "Retorne EXCLUSIVAMENTE um objeto JSON válido com os seguintes campos (use null se não encontrado): " .
                             "- category: string (ex: vestido, calça, casaco, blusa, sapato) " .
                             "- size: string (ex: P, M, G, GG, 38, 40) " .
                             "- max_price: number (valor numérico sem formatação, ex: 150.00) " .
                             "- colors: array of strings (ex: ['preto', 'azul']) " .
                             "- style: string (ex: casual, festa, social) " .
                             "- keywords: array of strings (termos importantes para busca extraídos do texto). " .
                             "IMPORTANTE: Retorne APENAS o JSON, sem markdown ou explicações adicionais.";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => "Texto da cliente: \"{$rawPrompt}\""]]
                ]
            ],
            'system_instruction' => [
                'parts' => [['text' => $systemInstruction]]
            ],
            'generationConfig' => [
                'temperature' => 0.1, // Baixa temperatura para ser determinístico
                'responseMimeType' => 'application/json', // Força o retorno em JSON nativo (se suportado pela versão da API)
            ]
        ];

        try {
            $url = "{$this->baseUrl}/models/gemini-3.6-flash:generateContent?key={$this->apiKey}";
            $response = Http::timeout(20)->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                
                if ($text) {
                    // Limpa possível markdown caso a API ignore o responseMimeType
                    $text = preg_replace('/```json/i', '', $text);
                    $text = preg_replace('/```/i', '', $text);
                    
                    $parsed = json_decode(trim($text), true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        return [
                            'category'  => $parsed['category'] ?? null,
                            'size'      => $parsed['size'] ?? null,
                            'max_price' => $parsed['max_price'] ?? null,
                            'colors'    => $parsed['colors'] ?? [],
                            'style'     => $parsed['style'] ?? null,
                            'keywords'  => $parsed['keywords'] ?? [],
                        ];
                    }
                    Log::error('DemandParserService: O retorno da IA não é um JSON válido', ['text' => $text]);
                }
            } else {
                Log::error("DemandParserService: Erro na API do Gemini", ['status' => $response->status(), 'body' => $response->body()]);
            }
        } catch (Exception $e) {
            Log::error('DemandParserService: Exceção ao chamar API da IA: ' . $e->getMessage());
        }

        return null;
    }
}

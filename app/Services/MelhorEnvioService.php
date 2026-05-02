<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MelhorEnvioService
{
    protected $baseUrl;
    protected $token;
    protected $cepOrigem;

    public function __construct()
    {
        // Define se usa Sandbox ou Produção baseado no .env
        $this->baseUrl = env('MELHOR_ENVIO_URL', 'https://sandbox.melhorenvio.com.br');
        $this->token = env('MELHOR_ENVIO_TOKEN', '');
        
        // CEP de origem do lojista (usado para o remetente)
        $this->cepOrigem = env('MELHOR_ENVIO_CEP_ORIGEM', '01001000'); // CEP padrão de exemplo (Sé, SP)
    }

    /**
     * Cota o frete baseado no CEP de destino e nas dimensões do pacote final.
     *
     * @param string $cepDestino
     * @param array $packageData ['weight', 'width', 'height', 'length']
     * @return array
     */
    public function calculateShipping($cepDestino, array $packageData)
    {
        if (empty($this->token)) {
            Log::error('Melhor Envio Token não configurado.');
            return ['error' => 'Configuração de frete ausente.'];
        }

        $cepDestino = preg_replace('/[^0-9]/', '', $cepDestino);
        $cepOrigem = preg_replace('/[^0-9]/', '', $this->cepOrigem);

        $payload = [
            'from' => [
                'postal_code' => $cepOrigem
            ],
            'to' => [
                'postal_code' => $cepDestino
            ],
            'package' => [
                'weight' => $packageData['weight'],
                'width'  => $packageData['width'],
                'height' => $packageData['height'],
                'length' => $packageData['length'],
            ]
        ];

        try {
            $request = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->token,
                'User-Agent' => 'ManiadeMelissa (' . env('MAIL_FROM_ADDRESS', 'contato@maniademelissa.com') . ')'
            ])
            ->timeout(15)
            ->retry(3, 500); // Tenta 3 vezes com 500ms de intervalo se falhar

            // Desabilitar verificação SSL no ambiente local (resolve o cURL error 60 no Windows)
            if (env('APP_ENV') === 'local') {
                $request = $request->withoutVerifying();
            }

            $response = $request->post($this->baseUrl . '/api/v2/me/shipment/calculate', $payload);

            if ($response->successful()) {
                $options = $response->json();
                
                // Filtrar apenas transportadoras que não deram erro e suportam o pacote
                $validOptions = array_filter($options, function($option) {
                    return !isset($option['error']) && isset($option['price']);
                });

                // Ordenar pelo preço (mais barato primeiro)
                usort($validOptions, function($a, $b) {
                    return (float)$a['price'] <=> (float)$b['price'];
                });

                return [
                    'success' => true,
                    'options' => array_values($validOptions)
                ];
            }

            Log::error('Erro Melhor Envio Calculate: ' . $response->body());
            return [
                'success' => false,
                'message' => 'Não foi possível cotar o frete no momento. Tente novamente mais tarde.'
            ];

        } catch (\Exception $e) {
            Log::error('Exceção Melhor Envio Calculate: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno ao consultar o frete.'
            ];
        }
    }
}

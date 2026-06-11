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
        $this->baseUrl = env('MELHOR_ENVIO_URL', 'https://melhorenvio.com.br');
        $this->token = \App\Models\Configuracao::get('melhor_envio_access_token') ?: env('MELHOR_ENVIO_TOKEN', '');
        
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
        if (!$this->refreshTokenIfNeeded()) {
            Log::error('Melhor Envio: Token de acesso inválido ou expirado.');
            if (env('APP_ENV') === 'local') {
                Log::info('Melhor Envio: Usando tarifas simuladas (mock) em ambiente local devido a falha na renovação do token.');
                return [
                    'success' => true,
                    'options' => $this->getMockShippingOptions($cepDestino, $packageData)
                ];
            }
            return ['success' => false, 'message' => 'Configuração de frete inválida ou ausente. Por favor, conecte ao Melhor Envio.'];
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
                'weight' => $packageData['weight'] ?? 1.0,
                'width'  => $packageData['width'] ?? 10.0,
                'height' => $packageData['height'] ?? 10.0,
                'length' => $packageData['length'] ?? 10.0,
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
            
            if (env('APP_ENV') === 'local' || $response->status() === 401) {
                Log::info('Melhor Envio: Usando tarifas simuladas (mock) devido a erro na resposta da API (status: ' . $response->status() . ').');
                return [
                    'success' => true,
                    'options' => $this->getMockShippingOptions($cepDestino, $packageData)
                ];
            }

            return [
                'success' => false,
                'message' => 'Não foi possível cotar o frete no momento. Tente novamente mais tarde.'
            ];

        } catch (\Exception $e) {
            Log::error('Exceção Melhor Envio Calculate: ' . $e->getMessage());
            
            if (env('APP_ENV') === 'local') {
                Log::info('Melhor Envio: Usando tarifas simuladas (mock) devido a exceção na API.');
                return [
                    'success' => true,
                    'options' => $this->getMockShippingOptions($cepDestino, $packageData)
                ];
            }

            return [
                'success' => false,
                'message' => 'Erro interno ao consultar o frete.'
            ];
        }
    }

    /**
     * Retorna opções de frete simuladas (mock) para desenvolvimento local.
     */
    protected function getMockShippingOptions($cepDestino, array $packageData)
    {
        $primeiroDigito = substr($cepDestino, 0, 1);
        
        $basePrice = 12.90;
        if (in_array($primeiroDigito, ['0', '1', '2', '3'])) {
            // Sudeste
            $basePrice = 14.50;
        } elseif (in_array($primeiroDigito, ['8', '9'])) {
            // Sul
            $basePrice = 16.90;
        } elseif (in_array($primeiroDigito, ['4', '5', '6', '7'])) {
            // Outras regiões
            $basePrice = 24.90;
        } else {
            $basePrice = 19.90;
        }

        $weightFactor = max(0.1, (float)($packageData['weight'] ?? 1.0)) * 2.50;
        $pricePAC = round($basePrice + $weightFactor, 2);
        $priceSedex = round(($basePrice + $weightFactor) * 1.6, 2);
        
        return [
            [
                'id' => 'correios_pac_mock',
                'name' => 'Correios PAC (Simulado)',
                'price' => (float)$pricePAC,
                'delivery_time' => 7,
                'company' => [
                    'name' => 'Correios',
                    'picture' => 'https://logodownload.org/wp-content/uploads/2014/05/correios-logo-1-1.png'
                ]
            ],
            [
                'id' => 'correios_sedex_mock',
                'name' => 'Correios SEDEX (Simulado)',
                'price' => (float)$priceSedex,
                'delivery_time' => 3,
                'company' => [
                    'name' => 'Correios',
                    'picture' => 'https://logodownload.org/wp-content/uploads/2014/05/correios-logo-1-1.png'
                ]
            ],
            [
                'id' => 'jadlog_package_mock',
                'name' => 'Jadlog Package (Simulado)',
                'price' => (float)round($pricePAC * 0.9, 2),
                'delivery_time' => 5,
                'company' => [
                    'name' => 'Jadlog',
                    'picture' => 'https://logodownload.org/wp-content/uploads/2019/12/jadlog-logo-0.png'
                ]
            ]
        ];
    }
    private function getClient()
    {
        $this->refreshTokenIfNeeded();

        $request = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token,
            'User-Agent' => 'ManiadeMelissa (' . env('MAIL_FROM_ADDRESS', 'contato@maniademelissa.com') . ')'
        ])->timeout(15);

        if (env('APP_ENV') === 'local') {
            $request = $request->withoutVerifying();
        }

        return $request;
    }

    public function createCart($pedido, $user, array $packageData, $serviceId)
    {
        if (!$this->refreshTokenIfNeeded()) {
            return ['success' => false, 'message' => 'Melhor Envio não conectado ou token expirado. Por favor, conecte ao Melhor Envio.'];
        }

        $cepDestino = preg_replace('/[^0-9]/', '', $user->cep ?? '');
        $cepOrigem = preg_replace('/[^0-9]/', '', $this->cepOrigem);
        
        $enderecoParts = explode(' ', $user->endereco ?? '');
        $rua = $enderecoParts[0] ?? 'Rua Padrão';
        $numero = $user->numero_endereco ?? 'S/N';
        $bairro = $user->bairro ?? 'Centro';
        $cidade = $user->cidade ?? 'São Paulo';
        $estado = $user->estado ?? 'SP';

        $payload = [
            'service' => $serviceId,
            'agency' => null, // Opcional, a não ser que seja Jadlog e não tenha coleta
            'from' => [
                'name' => config('app.name', 'Minha Mania'),
                'phone' => env('STORE_PHONE', '48999999999'),
                'email' => env('MAIL_FROM_ADDRESS', 'contato@loja.com'),
                'document' => env('STORE_DOCUMENT', '18560052321'), // CPF/CNPJ válido matemático de fallback
                'address' => env('STORE_ADDRESS', 'Rua Hermogenes Prazeres'), 
                'number' => env('STORE_NUMBER', '184'),
                'district' => env('STORE_DISTRICT', 'Centro'),
                'city' => env('STORE_CITY', 'Biguaçu'),
                'state_abbr' => env('STORE_STATE', 'SC'),
                'country_id' => 'BR',
                'postal_code' => $cepOrigem,
            ],
            'to' => [
                'name' => $user->name,
                'phone' => preg_replace('/[^0-9]/', '', $user->phone ?? $user->whatsapp ?? '48999999999'),
                'email' => $user->email,
                'document' => preg_replace('/[^0-9]/', '', $user->cpf ?? '') ?: '11983363154', // CPF matematicamente válido de fallback
                'address' => $rua,
                'number' => $numero,
                'complement' => $user->complemento ?? '',
                'district' => $bairro,
                'city' => $cidade,
                'state_abbr' => $estado,
                'country_id' => 'BR',
                'postal_code' => $cepDestino,
            ],
            'products' => [
                [
                    'name' => 'Roupas / Acessórios',
                    'quantity' => 1,
                    'unitary_value' => (float)($pedido->valor_total ?? 10.00),
                ]
            ],
            'volumes' => [
                [
                    'height' => $packageData['height'],
                    'width' => $packageData['width'],
                    'length' => $packageData['length'],
                    'weight' => $packageData['weight'],
                ]
            ],
            'options' => [
                'insurance_value' => (float)($pedido->valor_total ?? 10.00),
                'receipt' => false,
                'own_hand' => false,
                'reverse' => false,
                'non_commercial' => true,
            ]
        ];

        try {
            $response = $this->getClient()->post($this->baseUrl . '/api/v2/me/cart', $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true, 
                    'order_id' => $data['id'],
                    'price' => $data['price'] ?? 0
                ];
            }
            
            Log::error('Erro Melhor Envio Add Cart: ' . $response->body());
            return ['success' => false, 'message' => 'Erro ao adicionar ao carrinho: ' . $response->body()];
        } catch (\Exception $e) {
            Log::error('Exceção Add Cart: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno ao criar pedido no Melhor Envio.'];
        }
    }

    public function checkout($cartOrderId)
    {
        try {
            $payload = [
                'orders' => [$cartOrderId]
            ];
            
            $response = $this->getClient()->post($this->baseUrl . '/api/v2/me/shipment/checkout', $payload);
            
            if ($response->successful()) {
                return ['success' => true];
            }
            
            Log::error('Erro Melhor Envio Checkout: ' . $response->body());
            return ['success' => false, 'message' => 'Erro no pagamento do frete: ' . $response->body()];
        } catch (\Exception $e) {
            Log::error('Exceção Checkout: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao processar pagamento.'];
        }
    }

    public function generateLabel($cartOrderId)
    {
        try {
            $payload = [
                'orders' => [$cartOrderId]
            ];
            
            $response = $this->getClient()->post($this->baseUrl . '/api/v2/me/shipment/generate', $payload);
            
            if ($response->successful()) {
                return ['success' => true];
            }
            
            Log::error('Erro Melhor Envio Generate: ' . $response->body());
            return ['success' => false, 'message' => 'Erro ao gerar etiqueta: ' . $response->body()];
        } catch (\Exception $e) {
            Log::error('Exceção Generate: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao gerar etiqueta.'];
        }
    }

    public function printLabel($cartOrderId)
    {
        try {
            $payload = [
                'mode' => 'public',
                'orders' => [$cartOrderId]
            ];
            
            $response = $this->getClient()->post($this->baseUrl . '/api/v2/me/shipment/print', $payload);
            
            if ($response->successful()) {
                return ['success' => true, 'url' => $response->json()['url']];
            }
            
            Log::error('Erro Melhor Envio Print: ' . $response->body());
            return ['success' => false, 'message' => 'Erro ao solicitar impressão: ' . $response->body()];
        } catch (\Exception $e) {
            Log::error('Exceção Print: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao imprimir etiqueta.'];
        }
    }

    public function getBalance()
    {
        try {
            $response = $this->getClient()->get($this->baseUrl . '/api/v2/me/balance');
            
            if ($response->successful()) {
                $data = $response->json();
                return ['success' => true, 'balance' => $data['balance'] ?? 0.00];
            }
            
            Log::error('Erro Melhor Envio Balance: ' . $response->body());
            return ['success' => false, 'message' => 'Não foi possível carregar o saldo.'];
        } catch (\Exception $e) {
            Log::error('Exceção Balance: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno ao consultar saldo.'];
        }
    }

    /**
     * Renova o token se estiver expirado ou perto de expirar (nos próximos 5 minutos).
     * Retorna true se o token estiver válido ou foi renovado com sucesso.
     */
    public function refreshTokenIfNeeded()
    {
        $accessToken = \App\Models\Configuracao::get('melhor_envio_access_token');
        $refreshToken = \App\Models\Configuracao::get('melhor_envio_refresh_token');
        $expiresAt = \App\Models\Configuracao::get('melhor_envio_expires_at');

        if (!$accessToken || !$refreshToken) {
            $envToken = env('MELHOR_ENVIO_TOKEN');
            if ($envToken) {
                $this->token = $envToken;
                return true;
            }
            Log::warning('Melhor Envio: Token de acesso ou de atualização ausente no banco de dados e no arquivo .env.');
            return false;
        }

        if (!$expiresAt || \Carbon\Carbon::parse($expiresAt)->lte(now()->addMinutes(5))) {
            Log::info('Melhor Envio: Token expirando ou expirado. Iniciando renovação...');

            $clientId = env('MELHOR_ENVIO_CLIENT_ID');
            $clientSecret = env('MELHOR_ENVIO_CLIENT_SECRET');

            if (empty($clientId) || empty($clientSecret)) {
                $envToken = env('MELHOR_ENVIO_TOKEN');
                if ($envToken) {
                    Log::info('Melhor Envio: CLIENT_ID ou CLIENT_SECRET ausente no arquivo .env, caindo de volta para o token do .env.');
                    $this->token = $envToken;
                    return true;
                }
                Log::error('Melhor Envio: CLIENT_ID ou CLIENT_SECRET ausente no arquivo .env.');
                return false;
            }

            try {
                $payload = [
                    'grant_type' => 'refresh_token',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $refreshToken,
                ];

                $httpClient = Http::asJson()->timeout(15);
                if (env('APP_ENV') === 'local') {
                    $httpClient = $httpClient->withoutVerifying();
                }

                $response = $httpClient->post($this->baseUrl . '/oauth/token', $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    $newAccessToken = $data['access_token'] ?? null;
                    $newRefreshToken = $data['refresh_token'] ?? null;
                    $expiresIn = $data['expires_in'] ?? 2592000;

                    if ($newAccessToken && $newRefreshToken) {
                        \App\Models\Configuracao::set('melhor_envio_access_token', $newAccessToken);
                        \App\Models\Configuracao::set('melhor_envio_refresh_token', $newRefreshToken);
                        \App\Models\Configuracao::set('melhor_envio_expires_at', now()->addSeconds($expiresIn)->toDateTimeString());

                        $this->token = $newAccessToken;
                        Log::info('Melhor Envio: Token renovado com sucesso!');
                        return true;
                    }
                }

                Log::error('Melhor Envio: Falha ao renovar token. Resposta: ' . $response->body());
                
                // Fallback to env token if token refresh fails
                $envToken = env('MELHOR_ENVIO_TOKEN');
                if ($envToken) {
                    Log::info('Melhor Envio: Usando token estático do .env como fallback após falha de renovação.');
                    $this->token = $envToken;
                    return true;
                }
                return false;

            } catch (\Exception $e) {
                Log::error('Melhor Envio: Exceção ao renovar token: ' . $e->getMessage());
                
                // Fallback to env token if exception occurs
                $envToken = env('MELHOR_ENVIO_TOKEN');
                if ($envToken) {
                    Log::info('Melhor Envio: Usando token estático do .env como fallback após exceção de renovação.');
                    $this->token = $envToken;
                    return true;
                }
                return false;
            }
        }

        $this->token = $accessToken;
        return true;
    }

    /**
     * Obtém o código de rastreamento de uma etiqueta pelo ID do Melhor Envio.
     *
     * @param string $cartOrderId
     * @return string|null
     */
    public function getTrackingCode($cartOrderId)
    {
        try {
            $payload = [
                'orders' => [$cartOrderId]
            ];
            
            $response = $this->getClient()->post($this->baseUrl . '/api/v2/me/shipment/tracking', $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data[$cartOrderId]['tracking'])) {
                    return $data[$cartOrderId]['tracking'];
                }
                
                foreach ($data as $key => $val) {
                    if (is_array($val) && isset($val['id']) && $val['id'] === $cartOrderId && isset($val['tracking'])) {
                        return $val['tracking'];
                    }
                    if (is_array($val) && isset($val['tracking'])) {
                        return $val['tracking'];
                    }
                }
            }
            
            Log::error('Erro Melhor Envio Tracking Code Fetch: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Exceção ao buscar código de rastreamento: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca um pedido no Melhor Envio pelo termo (código de rastreio, ID do envio, etc).
     *
     * @param string $term
     * @return array|null
     */
    public function searchOrder($term)
    {
        try {
            $response = $this->getClient()->get($this->baseUrl . '/api/v2/me/orders/search', [
                'q' => $term
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Erro Melhor Envio Search Order: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Exceção ao buscar pedido no Melhor Envio: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtém os detalhes completos de rastreamento pelo ID da etiqueta do Melhor Envio.
     *
     * @param string $cartOrderId
     * @return array|null
     */
    public function getTrackingDetails($cartOrderId)
    {
        try {
            $payload = [
                'orders' => [$cartOrderId]
            ];
            
            $response = $this->getClient()->post($this->baseUrl . '/api/v2/me/shipment/tracking', $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data[$cartOrderId])) {
                    return $data[$cartOrderId];
                }
                
                foreach ($data as $key => $val) {
                    if (is_array($val) && isset($val['id']) && $val['id'] === $cartOrderId) {
                        return $val;
                    }
                }
                
                // Retorna o primeiro elemento se não achar chave direta
                if (is_array($data) && count($data) > 0) {
                    return reset($data);
                }
            }
            
            Log::error('Erro Melhor Envio Tracking Details Fetch: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Exceção ao buscar detalhes de rastreamento: ' . $e->getMessage());
            return null;
        }
    }
}

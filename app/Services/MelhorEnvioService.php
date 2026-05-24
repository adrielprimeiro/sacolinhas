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
    private function getClient()
    {
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
        if (empty($this->token)) return ['success' => false, 'message' => 'Token não configurado.'];

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
                'name' => env('APP_NAME', 'Loja'),
                'phone' => env('STORE_PHONE', '48999999999'),
                'email' => env('MAIL_FROM_ADDRESS', 'contato@loja.com'),
                'document' => env('STORE_DOCUMENT', '18560052321'), // CPF/CNPJ válido matemático de fallback
                'address' => 'Rua do Remetente', 
                'number' => '123',
                'district' => 'Centro',
                'city' => 'Biguaçu',
                'state_abbr' => 'SC',
                'country_id' => 'BR',
                'postal_code' => $cepOrigem,
            ],
            'to' => [
                'name' => $user->name,
                'phone' => preg_replace('/[^0-9]/', '', $user->phone ?? $user->whatsapp ?? '48999999999'),
                'email' => $user->email,
                'document' => preg_replace('/[^0-9]/', '', $user->cpf) ?: '73323864082', // CPF diferente do remetente
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
                return ['success' => true, 'order_id' => $response->json()['id']];
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
}

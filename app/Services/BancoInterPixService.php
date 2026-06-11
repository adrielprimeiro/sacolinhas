<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BancoInterPixService
{
    protected $config;
    protected $certPath;
    protected $keyPath;
    protected $baseUrl;
    protected $tokenUrl;

    public function __construct()
    {
        $this->config = config('services.banco_inter');
        $this->certPath = $this->config['cert_path'];
        $this->keyPath = $this->config['key_path'];

        // Resolve paths
        if (!str_starts_with($this->certPath, '/') && !str_starts_with($this->certPath, '\\') && !preg_match('/^[a-zA-Z]:/', $this->certPath)) {
            $this->certPath = storage_path($this->certPath);
        }
        if (!str_starts_with($this->keyPath, '/') && !str_starts_with($this->keyPath, '\\') && !preg_match('/^[a-zA-Z]:/', $this->keyPath)) {
            $this->keyPath = storage_path($this->keyPath);
        }

        $isSandbox = (bool) $this->config['sandbox'];
        $this->baseUrl = $isSandbox ? 'https://cdpj-sandbox.partners.uatinter.co' : 'https://cdpj.partners.bancointer.com.br';
        $this->tokenUrl = $isSandbox ? 'https://cdpj-sandbox.partners.uatinter.co/oauth/v2/token' : 'https://cdpj.partners.bancointer.com.br/oauth/v2/token';
    }

    /**
     * Verifica se o serviço está em modo simulação (Mock)
     */
    public function isMockMode(): bool
    {
        return $this->config['sandbox'] && (!file_exists($this->certPath) || !file_exists($this->keyPath));
    }

    /**
     * Obtém o Token de Acesso OAuth2 com escopo de Pix
     */
    protected function getAccessToken(): string
    {
        if ($this->isMockMode()) {
            return 'mock_token_12345';
        }

        if (!file_exists($this->certPath) || !file_exists($this->keyPath)) {
            throw new \Exception("Certificados mTLS do Banco Inter não encontrados para autenticação de Pix.");
        }

        Log::info('Solicitando token OAuth Pix para Banco Inter...');

        $response = Http::withoutVerifying()
            ->withOptions([
                'cert' => $this->certPath,
                'ssl_key' => $this->keyPath,
            ])
            ->asForm()
            ->post($this->tokenUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'scope' => 'cob.write cob.read webhook.write'
            ]);

        if (!$response->successful()) {
            Log::error('Erro na autenticação OAuth2 Pix do Banco Inter', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception("Erro de autenticação Pix no Banco Inter (Status {$response->status()})");
        }

        return $response->json()['access_token'] ?? '';
    }

    /**
     * Cria uma cobrança imediata (Pix Cob)
     */
    public function criarPixCob(string $numeroPedido, float $valor, string $nomeCliente, ?string $cpfCliente)
    {
        $txid = 'TXID' . strtoupper(Str::random(28)); // txid de 32 caracteres (alfanumérico)
        $chavePix = $this->config['chave_pix'] ?? 'mania@maniademelissa.com';

        if ($this->isMockMode()) {
            Log::info("Simulando criação de Pix Banco Inter para Pedido #{$numeroPedido} (TXID: {$txid})");
            
            // Retorna resposta mockada com Pix copia e cola simulado
            $copiaColaSimulado = "00020101021226850014br.gov.bcb.pix2563emv.pix.bancointer.com.br/pix/v2/cobv/{$txid}5204000053039865405" . number_format($valor, 2, '.', '') . "5802BR5916MANIA DE MELISSA6009Sao Paulo62070503***6304ABCD";

            return [
                'txid' => $txid,
                'pixCopiaECola' => $copiaColaSimulado,
                'status' => 'ATIVA',
                'valor' => $valor,
            ];
        }

        $accessToken = $this->getAccessToken();
        $cleanCpf = preg_replace('/\D/', '', $cpfCliente ?? '00000000000');
        if (strlen($cleanCpf) !== 11) {
            $cleanCpf = '00000000000'; // Fallback se não for CPF válido
        }

        $url = "{$this->baseUrl}/pix/v2/cob/{$txid}";
        
        $body = [
            'calendario' => [
                'expiracao' => 86400 // 24 horas
            ],
            'devedor' => [
                'cpf' => $cleanCpf,
                'nome' => substr($nomeCliente, 0, 80)
            ],
            'valor' => [
                'original' => number_format($valor, 2, '.', '')
            ],
            'chave' => $chavePix,
            'solicitacaoPagador' => "Pedido {$numeroPedido}"
        ];

        Log::info("Criando cobrança Pix no Banco Inter para Pedido #{$numeroPedido}...", ['txid' => $txid]);

        $response = Http::withoutVerifying()
            ->withToken($accessToken)
            ->withOptions([
                'cert' => $this->certPath,
                'ssl_key' => $this->keyPath,
            ])
            ->put($url, $body);

        if (!$response->successful()) {
            Log::error('Erro ao criar Pix no Banco Inter', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception("Erro ao gerar Pix no Banco Inter (Status {$response->status()})");
        }

        return $response->json();
    }

    /**
     * Registra o Webhook de Pix no Banco Inter
     */
    public function registrarWebhook(string $webhookUrl): bool
    {
        $chavePix = $this->config['chave_pix'] ?? 'mania@maniademelissa.com';

        if ($this->isMockMode()) {
            Log::info("Simulando registro de Webhook do Banco Inter para URL: {$webhookUrl}");
            return true;
        }

        $accessToken = $this->getAccessToken();
        $url = "{$this->baseUrl}/pix/v2/webhook/{$chavePix}";

        Log::info("Registrando Webhook Pix no Banco Inter para chave {$chavePix}...", ['url' => $webhookUrl]);

        $response = Http::withoutVerifying()
            ->withToken($accessToken)
            ->withOptions([
                'cert' => $this->certPath,
                'ssl_key' => $this->keyPath,
            ])
            ->put($url, [
                'webhookUrl' => $webhookUrl
            ]);

        if (!$response->successful()) {
            Log::error('Erro ao registrar Webhook no Banco Inter', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception("Erro ao registrar Webhook no Banco Inter (Status {$response->status()})");
        }

        return true;
    }
}

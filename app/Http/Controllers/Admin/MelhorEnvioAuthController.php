<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MelhorEnvioAuthController extends Controller
{
    protected $baseUrl;
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;

    public function __construct()
    {
        $this->baseUrl = env('MELHOR_ENVIO_URL', 'https://sandbox.melhorenvio.com.br');
        $this->clientId = env('MELHOR_ENVIO_CLIENT_ID');
        $this->clientSecret = env('MELHOR_ENVIO_CLIENT_SECRET');
        $this->redirectUri = env('MELHOR_ENVIO_REDIRECT_URI', url('/admin/melhor-envio/callback'));
    }

    /**
     * Redirect the user to the Melhor Envio authorization page.
     */
    public function redirect()
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            return redirect()->route('admin.pedido.index')
                ->with('error', 'Melhor Envio Client ID ou Secret não configurados no arquivo .env.');
        }

        $scopes = [
            'shipping-calculate',
            'cart-write',
            'shipping-checkout',
            'shipping-generate',
            'shipping-print',
            'users-read',
            'shipping-tracking'
        ];

        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
        ]);

        $url = $this->baseUrl . '/oauth/authorize?' . $query;

        return redirect()->away($url);
    }

    /**
     * Handle the callback from Melhor Envio.
     */
    public function callback(Request $request)
    {
        Log::info('Melhor Envio OAuth Callback params:', $request->all());

        $code = $request->query('code');
        $error = $request->query('error');
        $errorDescription = $request->query('error_description');

        if (!$code) {
            $msg = 'Autorização negada ou código de acesso ausente.';
            if ($error) {
                $msg .= ' Erro: ' . $error . ($errorDescription ? ' - ' . $errorDescription : '');
            }
            return redirect()->route('admin.pedido.index')
                ->with('error', $msg);
        }

        try {
            $payload = [
                'grant_type' => 'authorization_code',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
                'code' => $code,
            ];

            $httpClient = Http::asJson()->timeout(15);
            if (env('APP_ENV') === 'local') {
                $httpClient = $httpClient->withoutVerifying();
            }

            $response = $httpClient->post($this->baseUrl . '/oauth/token', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                $accessToken = $data['access_token'] ?? null;
                $refreshToken = $data['refresh_token'] ?? null;
                $expiresIn = $data['expires_in'] ?? 2592000; // padrão 30 dias

                if ($accessToken && $refreshToken) {
                    Configuracao::set('melhor_envio_access_token', $accessToken);
                    Configuracao::set('melhor_envio_refresh_token', $refreshToken);
                    Configuracao::set('melhor_envio_expires_at', now()->addSeconds($expiresIn)->toDateTimeString());

                    return redirect()->route('admin.pedido.index')
                        ->with('success', 'Conectado ao Melhor Envio com sucesso!');
                }
            }

            Log::error('Erro ao trocar código por token no Melhor Envio: ' . $response->body());
            return redirect()->route('admin.pedido.index')
                ->with('error', 'Erro ao obter token de acesso do Melhor Envio: ' . ($response->json()['message'] ?? 'Erro desconhecido.'));

        } catch (\Exception $e) {
            Log::error('Exceção no Callback do Melhor Envio: ' . $e->getMessage());
            return redirect()->route('admin.pedido.index')
                ->with('error', 'Exceção ao conectar com Melhor Envio: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Services\TikTok;

use App\Models\SocialChannelAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class TikTokService
{
    protected string $clientKey;
    protected string $clientSecret;
    protected string $redirectUri;

    public function __construct()
    {
        $this->clientKey = config('services.tiktok.client_key') ?: env('TIKTOK_CLIENT_KEY', 'awvda5r79f4yu1ui');
        $this->clientSecret = config('services.tiktok.client_secret') ?: env('TIKTOK_CLIENT_SECRET', 'NvSNLxyGR6fFCe7awlp9nrr1UzT42aqw');
        $this->redirectUri = config('services.tiktok.redirect_uri') ?: env('TIKTOK_REDIRECT_URI', 'https://minhamania.net/admin/tiktok/callback');
    }

    /**
     * Verifica se as credenciais do TikTok for Developers estão configuradas
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientKey) && !empty($this->clientSecret);
    }

    /**
     * Gera URL de autorização OAuth do TikTok (Login Kit v2)
     */
    public function getAuthUrl(?string $state = null): string
    {
        $scopes = [
            'user.info.basic',
            'video.upload',
            'video.publish'
        ];

        $scopeStr = implode(',', $scopes);
        $stateVal = $state ?: csrf_token();
        $redirectEncoded = urlencode($this->redirectUri);

        return "https://www.tiktok.com/v2/auth/authorize/?client_key={$this->clientKey}&scope={$scopeStr}&response_type=code&redirect_uri={$redirectEncoded}&state={$stateVal}";
    }

    /**
     * Troca o código de autorização pelos tokens de acesso do TikTok
     */
    public function handleCallback(string $code): array
    {
        $response = Http::asForm()->post('https://open.tiktokapis.com/v2/oauth/token/', [
            'client_key' => $this->clientKey,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
        ]);

        if (!$response->successful()) {
            Log::error('[TikTokService] Erro ao trocar código por token: ' . $response->body());
            return [
                'success' => false,
                'message' => 'Falha ao autenticar com o TikTok: ' . ($response->json('error_description') ?? $response->body()),
            ];
        }

        $tokenData = $response->json('data') ?? $response->json();
        $accessToken = $tokenData['access_token'] ?? null;
        $refreshToken = $tokenData['refresh_token'] ?? null;
        $expiresIn = (int) ($tokenData['expires_in'] ?? 86400);
        $openId = $tokenData['open_id'] ?? null;

        if (!$accessToken) {
            return ['success' => false, 'message' => 'Token de acesso não retornado pelo TikTok.'];
        }

        // Buscar dados do perfil no TikTok
        $profileInfo = $this->fetchUserProfile($accessToken);

        // Salvar ou atualizar a conta conectada no banco
        $account = SocialChannelAccount::where('provider', 'tiktok')
            ->where('channel_id', $openId ?: ($profileInfo['id'] ?? 'default'))
            ->first() ?? new SocialChannelAccount();

        $account->provider = 'tiktok';
        $account->channel_id = $openId ?: ($profileInfo['id'] ?? null);
        $account->channel_name = $profileInfo['display_name'] ?? 'Minha Mania TikTok';
        $account->channel_avatar = $profileInfo['avatar_url'] ?? null;
        $account->access_token = $accessToken;
        if ($refreshToken) {
            $account->refresh_token = $refreshToken;
        }
        $account->token_expires_at = now()->addSeconds($expiresIn - 60);
        $account->is_active = true;
        $account->settings = array_merge($account->settings ?? [], [
            'open_id' => $openId,
            'profile' => $profileInfo,
            'connected_at' => now()->toIso8601String(),
        ]);
        $account->save();

        // Desativar outras contas de tiktok antigas se houver
        SocialChannelAccount::where('provider', 'tiktok')
            ->where('id', '!=', $account->id)
            ->update(['is_active' => false]);

        Log::info("[TikTokService] Perfil '{$account->channel_name}' conectado com sucesso!");

        return [
            'success' => true,
            'message' => "Perfil TikTok '{$account->channel_name}' conectado com sucesso!",
            'account' => $account,
        ];
    }

    /**
     * Busca dados do perfil do usuário autenticado no TikTok
     */
    public function fetchUserProfile(string $accessToken): array
    {
        try {
            $res = Http::withToken($accessToken)
                ->get('https://open.tiktokapis.com/v2/user/info/', [
                    'fields' => 'open_id,union_id,avatar_url,display_name,profile_deep_link'
                ]);

            if ($res->successful()) {
                $user = $res->json('data.user') ?? [];
                return [
                    'id' => $user['open_id'] ?? null,
                    'display_name' => $user['display_name'] ?? 'Minha Mania',
                    'avatar_url' => $user['avatar_url'] ?? null,
                    'profile_deep_link' => $user['profile_deep_link'] ?? null,
                ];
            }
        } catch (\Throwable $e) {
            Log::error('[TikTokService] Falha ao buscar perfil: ' . $e->getMessage());
        }

        return ['id' => null, 'display_name' => 'Minha Mania TikTok', 'avatar_url' => null];
    }

    /**
     * Retorna token de acesso válido, renovando se expirado
     */
    public function getAccessToken(?SocialChannelAccount $account = null): ?string
    {
        $account = $account ?: SocialChannelAccount::getActiveAccount('tiktok');
        if (!$account) {
            Log::warning('[TikTokService] Nenhuma conta do TikTok conectada.');
            return null;
        }

        if ($account->token_expires_at && $account->token_expires_at->isPast()) {
            return $this->refreshToken($account);
        }

        return $account->access_token;
    }

    /**
     * Renova o access_token usando o refresh_token
     */
    public function refreshToken(SocialChannelAccount $account): ?string
    {
        if (!$account->refresh_token) {
            Log::error('[TikTokService] Refresh token ausente. Reconecte o perfil do TikTok.');
            return null;
        }

        $response = Http::asForm()->post('https://open.tiktokapis.com/v2/oauth/token/', [
            'client_key' => $this->clientKey,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $account->refresh_token,
        ]);

        if (!$response->successful()) {
            Log::error('[TikTokService] Falha ao renovar token de acesso do TikTok: ' . $response->body());
            return null;
        }

        $tokenData = $response->json('data') ?? $response->json();
        $accessToken = $tokenData['access_token'] ?? null;
        $refreshToken = $tokenData['refresh_token'] ?? $account->refresh_token;
        $expiresIn = (int) ($tokenData['expires_in'] ?? 86400);

        if ($accessToken) {
            $account->access_token = $accessToken;
            $account->refresh_token = $refreshToken;
            $account->token_expires_at = now()->addSeconds($expiresIn - 60);
            $account->save();
            return $accessToken;
        }

        return null;
    }

    /**
     * Publica o corte de vídeo no TikTok (Content Posting API)
     */
    public function publishVideo($liveItem, array $options = []): array
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Conta do TikTok não conectada ou token expirado.'];
        }

        // 1. Validar existência do arquivo de vídeo local
        $videoPath = null;
        if (!empty($liveItem->video_cut_path)) {
            $videoPath = Storage::disk('public')->path($liveItem->video_cut_path);
            if (!file_exists($videoPath)) {
                $videoPath = $liveItem->video_cut_path;
            }
        }

        if (!$videoPath || !file_exists($videoPath)) {
            return ['success' => false, 'message' => 'Arquivo de vídeo do corte não encontrado no servidor.'];
        }

        // 2. Montar Título / Legenda Dinâmica da Peça
        $code = $liveItem->codigo_live ?: $liveItem->item_id;
        $productName = $liveItem->item_nome ?: ($liveItem->item_descricao ?: "Peça #{$code}");
        $price = !empty($liveItem->item_price) ? 'R$ ' . number_format($liveItem->item_price, 2, ',', '.') : '';
        $brand = !empty($liveItem->marca) ? "Marca: {$liveItem->marca}" : '';
        $size = !empty($liveItem->tamanho) ? "Tam: {$liveItem->tamanho}" : '';

        $titleParts = ["✨ Peça #{$code}: {$productName}"];
        if ($price) $titleParts[] = "por {$price}";
        $title = implode(' ', $titleParts);

        $caption = "{$title}!\n";
        if ($brand || $size) {
            $caption .= implode(' | ', array_filter([$brand, $size])) . "\n";
        }
        $caption .= "Garanta pelo WhatsApp da Minha Mania ou no link da bio! #minhamania #brecho #modacircular #achadinhos #live";

        // TikTok title/caption max limit: 2200 chars
        if (mb_strlen($caption) > 2000) {
            $caption = mb_substr($caption, 0, 1995) . '...';
        }

        $privacy = $options['privacy'] ?? 'PUBLIC_TO_EVERYONE'; // 'PUBLIC_TO_EVERYONE', 'MUTUAL_FOLLOW_FRIENDS', 'SELF_ONLY'

        // 3. Montar URL pública absoluta do vídeo
        $publicVideoUrl = url(Storage::url($liveItem->video_cut_path));
        if (str_starts_with($publicVideoUrl, 'http://')) {
            $publicVideoUrl = str_replace('http://', 'https://', $publicVideoUrl);
        }

        DB::table('live_items')
            ->where('id', $liveItem->id)
            ->update([
                'tiktok_status' => 'uploading',
                'updated_at' => now()
            ]);

        try {
            // Tenta primeiro o método PULL_FROM_URL (TikTok baixa diretamente do nosso servidor verificado)
            $initPayload = [
                'post_info' => [
                    'title' => $caption,
                    'privacy_level' => $privacy,
                    'disable_duet' => false,
                    'disable_stitch' => false,
                    'disable_comment' => false,
                    'video_cover_timestamp_ms' => 1000
                ],
                'source_info' => [
                    'source' => 'PULL_FROM_URL',
                    'video_url' => $publicVideoUrl
                ]
            ];

            $initResponse = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json; charset=UTF-8'])
                ->post('https://open.tiktokapis.com/v2/post/publish/video/init/', $initPayload);

            $data = $initResponse->json('data') ?? [];
            $publishId = $data['publish_id'] ?? null;

            // Se PULL_FROM_URL falhar por permissão de domínio, faz fallback para FILE_UPLOAD direto
            if (!$initResponse->successful() || !$publishId) {
                Log::warning('[TikTokService] PULL_FROM_URL retornou erro, tentando FILE_UPLOAD direto: ' . $initResponse->body());

                $fileSize = filesize($videoPath);
                $fileInitPayload = [
                    'post_info' => [
                        'title' => $caption,
                        'privacy_level' => $privacy,
                        'disable_duet' => false,
                        'disable_stitch' => false,
                        'disable_comment' => false,
                        'video_cover_timestamp_ms' => 1000
                    ],
                    'source_info' => [
                        'source' => 'FILE_UPLOAD',
                        'video_size' => $fileSize,
                        'chunk_size' => $fileSize,
                        'total_chunk_count' => 1
                    ]
                ];

                $fileInitRes = Http::withToken($accessToken)
                    ->withHeaders(['Content-Type' => 'application/json; charset=UTF-8'])
                    ->post('https://open.tiktokapis.com/v2/post/publish/video/init/', $fileInitPayload);

                if (!$fileInitRes->successful()) {
                    Log::error('[TikTokService] Falha no init FILE_UPLOAD: ' . $fileInitRes->body());
                    throw new \Exception('Erro ao iniciar upload no TikTok: ' . ($fileInitRes->json('error.message') ?? $fileInitRes->body()));
                }

                $fileData = $fileInitRes->json('data') ?? [];
                $publishId = $fileData['publish_id'] ?? null;
                $uploadUrl = $fileData['upload_url'] ?? null;

                if (!$uploadUrl) {
                    throw new \Exception('TikTok não retornou a URL de upload.');
                }

                // Streaming dos bytes do vídeo para a upload_url fornecida pelo TikTok
                $videoBytes = file_get_contents($videoPath);
                $uploadRes = Http::withHeaders([
                    'Content-Range' => "bytes 0-" . ($fileSize - 1) . "/{$fileSize}",
                    'Content-Type' => 'video/mp4',
                    'Content-Length' => (string) $fileSize,
                ])->withBody($videoBytes, 'video/mp4')->put($uploadUrl);

                if (!$uploadRes->successful()) {
                    Log::error('[TikTokService] Falha no streaming do vídeo: ' . $uploadRes->body());
                    throw new \Exception('Erro no upload dos dados de vídeo para o TikTok.');
                }
            }

            if (!$publishId) {
                return ['success' => false, 'message' => 'TikTok não retornou o identificador da publicação (publish_id).'];
            }

            DB::table('live_items')
                ->where('id', $liveItem->id)
                ->update([
                    'tiktok_publish_id' => $publishId,
                    'tiktok_status' => 'published',
                    'tiktok_published_at' => now(),
                    'tiktok_error' => null,
                    'updated_at' => now()
                ]);

            Log::info("[TikTokService] Vídeo da peça #{$code} publicado com sucesso no TikTok! Publish ID: {$publishId}");

            return [
                'success' => true,
                'message' => "Vídeo enviado com sucesso para o TikTok!",
                'publish_id' => $publishId,
                'status' => 'published'
            ];

        } catch (\Throwable $e) {
            Log::error('[TikTokService] Exceção durante publicação no TikTok: ' . $e->getMessage());

            DB::table('live_items')
                ->where('id', $liveItem->id)
                ->update([
                    'tiktok_status' => 'error',
                    'tiktok_error' => $e->getMessage(),
                    'updated_at' => now()
                ]);

            return [
                'success' => false,
                'message' => 'Erro ao publicar no TikTok: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Consulta status de publicação de um vídeo
     */
    public function checkPublishStatus(string $publishId): array
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['status' => 'FAILED', 'fail_reason' => 'Token não disponível'];
        }

        try {
            $res = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json; charset=UTF-8'])
                ->post('https://open.tiktokapis.com/v2/post/publish/status/fetch/', [
                    'publish_id' => $publishId
                ]);

            if ($res->successful()) {
                return $res->json('data') ?? [];
            }
        } catch (\Throwable $e) {
            Log::error('[TikTokService] Erro ao checar status: ' . $e->getMessage());
        }

        return ['status' => 'UNKNOWN'];
    }
}

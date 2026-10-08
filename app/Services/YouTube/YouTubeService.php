<?php

namespace App\Services\YouTube;

use App\Models\SocialChannelAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class YouTubeService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;

    public function __construct()
    {
        $this->clientId = config('services.youtube.client_id') ?: env('YOUTUBE_CLIENT_ID', '');
        $this->clientSecret = config('services.youtube.client_secret') ?: env('YOUTUBE_CLIENT_SECRET', '');
        $this->redirectUri = config('services.youtube.redirect_uri') ?: route('admin.youtube.callback');
    }

    /**
     * Verifica se as credenciais do Google Cloud estão configuradas no .env
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Gera URL de autorização OAuth do Google
     */
    public function getAuthUrl(?string $state = null): string
    {
        $scopes = [
            'https://www.googleapis.com/auth/youtube.upload',
            'https://www.googleapis.com/auth/youtube',
            'https://www.googleapis.com/auth/youtube.force-ssl',
            'https://www.googleapis.com/auth/userinfo.profile',
        ];

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state ?: csrf_token(),
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Troca o código de autorização pelos tokens de acesso e refresh
     */
    public function handleCallback(string $code): array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
        ]);

        if (!$response->successful()) {
            Log::error('[YouTubeService] Erro ao trocar código por token: ' . $response->body());
            return [
                'success' => false,
                'message' => 'Falha ao autenticar com o Google: ' . ($response->json('error_description') ?? $response->body()),
            ];
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'] ?? null;
        $refreshToken = $tokenData['refresh_token'] ?? null;
        $expiresIn = (int) ($tokenData['expires_in'] ?? 3600);

        if (!$accessToken) {
            return ['success' => false, 'message' => 'Token de acesso não retornado pelo Google.'];
        }

        // Buscar dados do canal no YouTube
        $channelInfo = $this->fetchChannelProfile($accessToken);

        // Salvar ou atualizar a conta conectada no banco
        $account = SocialChannelAccount::where('provider', 'youtube')
            ->where('channel_id', $channelInfo['id'] ?? 'default')
            ->first() ?? new SocialChannelAccount();

        $account->provider = 'youtube';
        $account->channel_id = $channelInfo['id'] ?? null;
        $account->channel_name = $channelInfo['title'] ?? 'Canal Minha Mania';
        $account->channel_avatar = $channelInfo['avatar'] ?? null;
        $account->access_token = $accessToken;
        if ($refreshToken) {
            $account->refresh_token = $refreshToken;
        }
        $account->token_expires_at = now()->addSeconds($expiresIn - 60);
        $account->is_active = true;
        $account->settings = array_merge($account->settings ?: [], [
            'auto_mark_sold' => true,
            'default_privacy' => 'public',
            'connected_at' => now()->toIso8601String(),
        ]);
        $account->save();

        Log::info("[YouTubeService] Canal '{$account->channel_name}' conectado com sucesso!");

        return [
            'success' => true,
            'message' => "Canal YouTube '{$account->channel_name}' conectado com sucesso!",
            'account' => $account,
        ];
    }

    /**
     * Busca dados do perfil do canal do YouTube autenticado
     */
    protected function fetchChannelProfile(string $accessToken): array
    {
        $res = Http::withToken($accessToken)->get('https://www.googleapis.com/youtube/v3/channels', [
            'part' => 'snippet',
            'mine' => 'true',
        ]);

        if ($res->successful() && !empty($res->json('items.0'))) {
            $item = $res->json('items.0');
            return [
                'id' => $item['id'] ?? null,
                'title' => $item['snippet']['title'] ?? 'Minha Mania',
                'avatar' => $item['snippet']['thumbnails']['default']['url'] ?? null,
            ];
        }

        return ['id' => null, 'title' => 'Minha Mania YouTube', 'avatar' => null];
    }

    /**
     * Obtém um token de acesso válido, renovando automaticamente se expirado
     */
    public function getValidAccessToken(?SocialChannelAccount $account = null): ?string
    {
        $account = $account ?: SocialChannelAccount::getActiveAccount('youtube');
        if (!$account) {
            Log::warning('[YouTubeService] Nenhuma conta do YouTube conectada.');
            return null;
        }

        // Se ainda for válido por pelo menos 2 minutos, usa o atual
        if ($account->token_expires_at && $account->token_expires_at->isAfter(now()->addMinutes(2))) {
            return $account->access_token;
        }

        // Se não tiver refresh token, não é possível renovar
        if (empty($account->refresh_token)) {
            Log::error('[YouTubeService] Refresh token ausente. Reconecte o canal.');
            return null;
        }

        // Renova o token no Google
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $account->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            Log::error('[YouTubeService] Falha ao renovar token de acesso: ' . $response->body());
            return null;
        }

        $data = $response->json();
        $account->access_token = $data['access_token'];
        $expiresIn = (int) ($data['expires_in'] ?? 3600);
        $account->token_expires_at = now()->addSeconds($expiresIn - 60);
        $account->save();

        return $account->access_token;
    }

    /**
     * Faz upload do corte de vídeo da peça para o YouTube Shorts
     */
    public function uploadShort($liveItem, array $options = []): array
    {
        $accessToken = $this->getValidAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Canal do YouTube não conectado ou token inválido.'];
        }

        // 1. Localizar o arquivo de vídeo
        $videoPath = null;
        if (!empty($liveItem->video_cut_path)) {
            $videoPath = Storage::disk('public')->path($liveItem->video_cut_path);
        }

        if (!$videoPath || !file_exists($videoPath) || filesize($videoPath) < 50000) {
            return ['success' => false, 'message' => 'Arquivo de vídeo do corte não encontrado localmente.'];
        }

        // 2. Montar metadados do Short
        $productName = $liveItem->item_nome ?? ($liveItem->item_descricao ?? 'Peça');
        $code = $liveItem->codigo_live ?? $liveItem->id;
        $price = !empty($liveItem->item_price) ? 'R$ ' . number_format($liveItem->item_price, 2, ',', '.') : '';
        $brand = !empty($liveItem->marca) ? $liveItem->marca : '';
        $size = !empty($liveItem->tamanho) ? 'Tam ' . $liveItem->tamanho : '';

        // Título formatado com limite de 100 caracteres e tag #Shorts
        $titleParts = array_filter([$productName, $brand, $size, "#{$code}", '| Brechó Minha Mania #Shorts']);
        $title = implode(' ', $titleParts);
        if (mb_strlen($title) > 100) {
            $title = mb_substr($productName, 0, 45) . " #{$code} - Minha Mania #Shorts";
        }

        // Descrição rica com chamada para ação
        $description = "✨ {$productName}\n";
        if ($brand) $description .= "🏷️ Marca: {$brand}\n";
        if ($size) $description .= "📏 Tamanho: {$size}\n";
        if ($price) $description .= "💰 Valor: {$price}\n";
        $description .= "🔢 Código na Live: #{$code}\n\n";
        $description .= "🛍️ QUER GARANTIR ESSA PEÇA? Comente 'EU QUERO' ou chame no WhatsApp:\n";
        $description .= "👉 https://wa.me/5548984813589?text=" . urlencode("Olá! Vi a peça #{$code} ({$productName}) no YouTube Shorts e quero garantir!") . "\n\n";
        $description .= "🔗 Acesse nossa sacolinha e catálogo: https://minhamania.net\n\n";
        $description .= "#brecho #minhamania #modasustentavel #shorts #lookdodia #desapego";

        $privacyStatus = $options['privacy'] ?? 'public'; // 'public', 'unlisted', 'private'

        $metadata = [
            'snippet' => [
                'title' => $title,
                'description' => $description,
                'tags' => ['brecho', 'minha mania', 'live shopping', 'moda sustentavel', 'desapego', 'shorts', $productName, $brand],
                'categoryId' => '26', // Howto & Style
                'defaultLanguage' => 'pt-BR',
                'defaultAudioLanguage' => 'pt-BR',
            ],
            'status' => [
                'privacyStatus' => $privacyStatus,
                'selfDeclaredMadeForKids' => false,
            ]
        ];

        // 3. Upload Resumable no YouTube Data API v3
        try {
            $fileSize = filesize($videoPath);
            $mimeType = 'video/mp4';

            // Iniciar sessão de upload resumable
            $initResponse = Http::withToken($accessToken)
                ->withHeaders([
                    'X-Upload-Content-Type' => $mimeType,
                    'X-Upload-Content-Length' => $fileSize,
                    'Content-Type' => 'application/json; charset=UTF-8',
                ])
                ->post('https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status', $metadata);

            if (!$initResponse->successful() || !$initResponse->header('Location')) {
                Log::error('[YouTubeService] Falha ao iniciar upload: ' . $initResponse->body());
                return [
                    'success' => false,
                    'message' => 'Erro ao iniciar upload no YouTube: ' . ($initResponse->json('error.message') ?? $initResponse->body())
                ];
            }

            $uploadUrl = $initResponse->header('Location');

            // Enviar os bytes do vídeo
            $fileHandle = fopen($videoPath, 'rb');
            $uploadResponse = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => $mimeType,
                    'Content-Length' => $fileSize,
                ])
                ->withBody(stream_get_contents($fileHandle), $mimeType)
                ->put($uploadUrl);

            fclose($fileHandle);

            if (!$uploadResponse->successful()) {
                Log::error('[YouTubeService] Falha no streaming do vídeo: ' . $uploadResponse->body());
                return [
                    'success' => false,
                    'message' => 'Erro no upload do arquivo de vídeo para o YouTube.'
                ];
            }

            $videoData = $uploadResponse->json();
            $videoId = $videoData['id'] ?? null;

            if (!$videoId) {
                return ['success' => false, 'message' => 'YouTube não retornou o ID do vídeo publicado.'];
            }

            $youtubeUrl = "https://youtube.com/shorts/{$videoId}";

            // 4. Gravar dados no banco de dados (live_items)
            DB::table('live_items')
                ->where('id', $liveItem->id)
                ->update([
                    'youtube_video_id' => $videoId,
                    'youtube_url' => $youtubeUrl,
                    'youtube_status' => 'published',
                    'youtube_published_at' => now(),
                    'youtube_error' => null,
                    'updated_at' => now(),
                ]);

            Log::info("[YouTubeService] Vídeo publicado com sucesso! Short: {$youtubeUrl}");

            return [
                'success' => true,
                'message' => "Vídeo publicado com sucesso no YouTube Shorts!",
                'video_id' => $videoId,
                'url' => $youtubeUrl,
            ];

        } catch (\Throwable $e) {
            Log::error('[YouTubeService] Exceção durante upload para o YouTube: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            DB::table('live_items')
                ->where('id', $liveItem->id)
                ->update([
                    'youtube_status' => 'error',
                    'youtube_error' => $e->getMessage(),
                    'updated_at' => now(),
                ]);

            return ['success' => false, 'message' => 'Exceção no upload: ' . $e->getMessage()];
        }
    }

    /**
     * Atualiza o vídeo no YouTube quando o item é vendido (adiciona carimbo de VENDIDO no título, descrição e comentário)
     */
    public function markVideoAsSold($liveItem): array
    {
        $videoId = $liveItem->youtube_video_id ?? null;
        if (!$videoId) {
            return ['success' => false, 'message' => 'Peça não possui vídeo publicado no YouTube.'];
        }

        $accessToken = $this->getValidAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Token do YouTube não disponível.'];
        }

        try {
            // 1. Buscar metadados atuais do vídeo no YouTube
            $getRes = Http::withToken($accessToken)->get('https://www.googleapis.com/youtube/v3/videos', [
                'part' => 'snippet,status',
                'id' => $videoId,
            ]);

            if (!$getRes->successful() || empty($getRes->json('items.0'))) {
                return ['success' => false, 'message' => 'Vídeo não encontrado no YouTube.'];
            }

            $currentSnippet = $getRes->json('items.0.snippet');
            $currentStatus = $getRes->json('items.0.status');
            $currentTitle = $currentSnippet['title'] ?? '';

            // Se o título já tem VENDIDO / ESGOTADO, não precisa alterar título
            if (!str_contains($currentTitle, '[🔴 VENDIDO]') && !str_contains($currentTitle, '[🔴 ESGOTADO]')) {
                $newTitle = mb_substr("[🔴 ESGOTADO] " . $currentTitle, 0, 100);
                $currentSnippet['title'] = $newTitle;
            }

            // Atualiza descrição com aviso destacado
            $currentSnippet['description'] = "🚨 AVISO: ESTA PEÇA JÁ FOI VENDIDA / ARREMATADA NA LIVE! 🚨\n" .
                "👉 Para conferir as outras peças disponíveis em nosso catálogo, acesse: https://minhamania.net\n\n" .
                ($currentSnippet['description'] ?? '');

            // Atualiza vídeo via API
            $putRes = Http::withToken($accessToken)->put('https://www.googleapis.com/youtube/v3/videos?part=snippet,status', [
                'id' => $videoId,
                'snippet' => $currentSnippet,
                'status' => $currentStatus,
            ]);

            // 2. Publicar comentário avisando a venda
            $this->postSoldComment($videoId, $liveItem, $accessToken);

            DB::table('live_items')
                ->where('id', $liveItem->id)
                ->update([
                    'youtube_status' => 'sold_updated',
                    'updated_at' => now(),
                ]);

            Log::info("[YouTubeService] Vídeo '{$videoId}' atualizado com status de VENDIDO com sucesso!");

            return [
                'success' => true,
                'message' => "Vídeo do YouTube atualizado com o carimbo de VENDIDO!",
                'video_id' => $videoId,
            ];

        } catch (\Throwable $e) {
            Log::error('[YouTubeService] Erro ao marcar vídeo como vendido no YouTube: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao atualizar vídeo: ' . $e->getMessage()];
        }
    }

    /**
     * Posta comentário avisando que a peça foi arrematada
     */
    protected function postSoldComment(string $videoId, $liveItem, string $accessToken): void
    {
        try {
            $code = $liveItem->codigo_live ?? $liveItem->id;
            $productName = $liveItem->item_nome ?? 'esta peça';
            $buyer = $liveItem->buyer_name ?? ($liveItem->user_full_name ?? 'uma cliente');

            $commentText = "🔴 Peça #{$code} ({$productName}) ARREMATADA por {$buyer}!\n\n✨ Quer garantir as próximas novidades antes de todo mundo? Acesse nosso catálogo: https://minhamania.net ou chame no WhatsApp (48) 98481-3589.";

            Http::withToken($accessToken)->post('https://www.googleapis.com/youtube/v3/commentThreads?part=snippet', [
                'snippet' => [
                    'videoId' => $videoId,
                    'topLevelComment' => [
                        'snippet' => [
                            'textOriginal' => $commentText,
                        ]
                    ]
                ]
            ]);
        } catch (\Throwable $e) {
            Log::warning('[YouTubeService] Não foi possível postar comentário de venda: ' . $e->getMessage());
        }
    }
}

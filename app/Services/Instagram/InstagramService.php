<?php

namespace App\Services\Instagram;

use App\Models\SocialChannelAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class InstagramService
{
    protected string $appId;
    protected string $appSecret;
    protected string $redirectUri;
    protected string $graphVersion = 'v20.0';

    public function __construct()
    {
        $this->appId = trim((string) (config('services.instagram.app_id') ?: env('INSTAGRAM_APP_ID', '')));
        $this->appSecret = trim((string) (config('services.instagram.app_secret') ?: env('INSTAGRAM_APP_SECRET', '')));
        $this->redirectUri = trim((string) (config('services.instagram.redirect_uri') ?: env('INSTAGRAM_REDIRECT_URI', 'https://minhamania.net/admin/instagram/callback')));
    }

    /**
     * Verifica se as credenciais da Meta/Instagram estão configuradas
     */
    public function isConfigured(): bool
    {
        return !empty($this->appId) && !empty($this->appSecret);
    }

    /**
     * Gera URL de autorização OAuth da Meta (Facebook Login / Instagram Graph API)
     */
    public function getAuthUrl(?string $state = null): string
    {
        $scopes = [
            'instagram_basic',
            'instagram_content_publish',
            'instagram_manage_comments',
            'pages_show_list',
            'pages_read_engagement',
            'business_management',
        ];

        $params = [
            'client_id' => $this->appId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(',', $scopes),
            'response_type' => 'code',
            'state' => $state ?: csrf_token(),
        ];

        return "https://www.facebook.com/{$this->graphVersion}/dialog/oauth?" . http_build_query($params);
    }

    /**
     * Troca o código de autorização pelos tokens de acesso e localiza a conta do Instagram Business
     */
    public function handleCallback(string $code): array
    {
        Log::info('[InstagramService] Iniciando troca de código por token.', [
            'redirect_uri' => $this->redirectUri,
            'app_id' => $this->appId,
        ]);

        try {
            // 1. Troca code por short-lived user access token
            $tokenRes = Http::get("https://graph.facebook.com/{$this->graphVersion}/oauth/access_token", [
                'client_id' => $this->appId,
                'client_secret' => $this->appSecret,
                'redirect_uri' => $this->redirectUri,
                'code' => $code,
            ]);

            if (!$tokenRes->successful()) {
                Log::error('[InstagramService] Falha ao obter short-lived token: ' . $tokenRes->body());
                return [
                    'success' => false,
                    'message' => 'Erro ao obter token da Meta: ' . ($tokenRes->json('error.message') ?? $tokenRes->body()),
                ];
            }

            $shortToken = $tokenRes->json('access_token');

            // 2. Troca short-lived por long-lived user token (duração de 60 dias)
            $longTokenRes = Http::get("https://graph.facebook.com/{$this->graphVersion}/oauth/access_token", [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $this->appId,
                'client_secret' => $this->appSecret,
                'fb_exchange_token' => $shortToken,
            ]);

            $userToken = $longTokenRes->successful() ? $longTokenRes->json('access_token') : $shortToken;
            $expiresIn = (int) ($longTokenRes->json('expires_in') ?? 5184000); // 60 dias padrão

            // 3. Buscar as Páginas do Facebook vinculadas para encontrar o Instagram Business Account
            $accountsRes = Http::get("https://graph.facebook.com/{$this->graphVersion}/me/accounts", [
                'fields' => 'id,name,access_token,instagram_business_account{id,username,name,profile_picture_url}',
                'access_token' => $userToken,
            ]);

            if (!$accountsRes->successful()) {
                Log::error('[InstagramService] Falha ao listar contas/páginas: ' . $accountsRes->body());
                return [
                    'success' => false,
                    'message' => 'Erro ao listar páginas da Meta: ' . ($accountsRes->json('error.message') ?? $accountsRes->body()),
                ];
            }

            $pages = $accountsRes->json('data') ?? [];
            $targetPage = null;
            $igAccount = null;

            foreach ($pages as $p) {
                if (!empty($p['instagram_business_account'])) {
                    $targetPage = $p;
                    $igAccount = $p['instagram_business_account'];
                    break;
                }
            }

            if (!$igAccount || empty($igAccount['id'])) {
                Log::warning('[InstagramService] Nenhuma conta do Instagram Business encontrada vinculada às páginas: ' . json_encode($pages));
                return [
                    'success' => false,
                    'message' => 'Nenhuma conta do Instagram Comercial/Criador encontrada vinculada à Página do Facebook. Verifique se o perfil do Instagram está conectado a uma Página do Facebook.',
                ];
            }

            $igId = $igAccount['id'];
            $igUsername = $igAccount['username'] ?? 'minhamania';
            $pageAccessToken = $targetPage['access_token'] ?? $userToken;
            $avatar = $igAccount['profile_picture_url'] ?? null;

            // 4. Salvar ou atualizar conta em SocialChannelAccount
            $account = SocialChannelAccount::where('provider', 'instagram')->first() ?? new SocialChannelAccount();
            $account->provider = 'instagram';
            $account->channel_id = $igId;
            $account->channel_name = '@' . $igUsername;
            $account->channel_avatar = $avatar;
            $account->access_token = $pageAccessToken;
            $account->token_expires_at = now()->addSeconds($expiresIn);
            $account->is_active = true;
            $account->settings = [
                'user_token' => $userToken,
                'page_id' => $targetPage['id'] ?? null,
                'page_name' => $targetPage['name'] ?? null,
                'username' => $igUsername,
                'connected_at' => now()->toIso8601String(),
            ];
            $account->save();

            Log::info("[InstagramService] Conta @{$igUsername} (ID: {$igId}) conectada com sucesso!");

            return [
                'success' => true,
                'message' => "Conta do Instagram @{$igUsername} conectada com sucesso!",
                'account' => $account,
            ];

        } catch (\Throwable $e) {
            Log::error('[InstagramService] Exceção durante callback: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exceção ao autenticar com Instagram: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Retorna a conta ativa do Instagram
     */
    public function getActiveAccount(): ?SocialChannelAccount
    {
        return SocialChannelAccount::where('provider', 'instagram')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Publica o corte de vídeo no Instagram (Reels, Stories ou Ambos via Instagram Graph API)
     */
    public function publishVideo($liveItem, array $options = []): array
    {
        $account = $this->getActiveAccount();
        if (!$account || empty($account->access_token) || empty($account->channel_id)) {
            return ['success' => false, 'message' => 'Conta do Instagram não conectada ou sem token ativo.'];
        }

        $igUserId = $account->channel_id;
        $accessToken = $account->access_token;
        $igUsername = $account->settings['username'] ?? ltrim((string) ($account->channel_name ?? 'minhamania'), '@');

        $requestedType = strtoupper(trim((string) ($options['media_type'] ?? 'REELS')));
        if (!in_array($requestedType, ['REELS', 'STORIES', 'BOTH'], true)) {
            $requestedType = 'REELS';
        }

        // 1. Validar existência do arquivo local de vídeo
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

        // 2. Montar Título / Legenda Dinâmica da Peça (usada no Reels)
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
        $caption .= "Garanta pelo WhatsApp da Minha Mania ou no link da bio! #minhamania #brecho #modacircular #achadinhos #reels #live";

        // Limite de legenda do Instagram: 2200 caracteres
        if (mb_strlen($caption) > 2100) {
            $caption = mb_substr($caption, 0, 2095) . '...';
        }

        // 3. Montar URL pública absoluta HTTPS do vídeo (com cache-buster do arquivo editado)
        $publicVideoUrl = url(Storage::url($liveItem->video_cut_path));
        if (str_starts_with($publicVideoUrl, 'http://')) {
            $publicVideoUrl = str_replace('http://', 'https://', $publicVideoUrl);
        }
        $mtime = @filemtime($videoPath) ?: time();
        $publicVideoUrl .= (str_contains($publicVideoUrl, '?') ? '&' : '?') . 'v=' . $mtime;

        DB::table('live_items')
            ->where('id', $liveItem->id)
            ->update([
                'instagram_status' => 'uploading',
                'updated_at' => now(),
            ]);

        $typesToPublish = $requestedType === 'BOTH' ? ['REELS', 'STORIES'] : [$requestedType];
        $results = [];
        $lastMediaId = null;
        $lastPermalink = null;

        try {
            foreach ($typesToPublish as $mediaType) {
                $typeLabel = $mediaType === 'STORIES' ? 'Story' : 'Reel';

                // PASSO 1: Criar Container de Vídeo na Meta (REELS ou STORIES)
                Log::info("[InstagramService] Criando container de {$typeLabel} ({$mediaType}) para peça #{$code}...", [
                    'video_url' => $publicVideoUrl,
                    'ig_user_id' => $igUserId,
                ]);

                $containerPayload = [
                    'media_type' => $mediaType,
                    'video_url' => $publicVideoUrl,
                    'access_token' => $accessToken,
                ];

                if ($mediaType === 'REELS') {
                    $containerPayload['caption'] = $caption;
                    $containerPayload['share_to_feed'] = true;
                }

                $createRes = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$igUserId}/media", $containerPayload);

                if (!$createRes->successful() || empty($createRes->json('id'))) {
                    $err = $createRes->json('error.message') ?? $createRes->body();
                    Log::error("[InstagramService] Erro ao criar container de {$typeLabel}: {$err}");
                    throw new \Exception("Erro ao iniciar publicação de {$typeLabel} no Instagram: {$err}");
                }

                $containerId = $createRes->json('id');
                Log::info("[InstagramService] Container {$typeLabel} criado (ID: {$containerId}). Aguardando processamento da Meta...");

                // PASSO 2: Aguardar a Meta processar o vídeo (Polling até 75s)
                // Nota: A API da Meta frequentemente retorna status_code=ERROR (2207077) de forma transitória
                // nos primeiros 15-25s enquanto os workers baixam/transcodificam o vídeo, mudando depois para FINISHED.
                $isReady = false;
                $lastErrorMsg = null;
                $maxAttempts = 25;
                for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                    sleep(3);

                    $statusRes = Http::get("https://graph.facebook.com/{$this->graphVersion}/{$containerId}", [
                        'fields' => 'status_code,status',
                        'access_token' => $accessToken,
                    ]);

                    $statusCode = $statusRes->json('status_code');
                    $statusDetail = $statusRes->json('status') ?? '';
                    Log::info("[InstagramService] [{$typeLabel}] Tentativa {$attempt}/{$maxAttempts}: status={$statusCode} ({$statusDetail})");

                    if ($statusCode === 'FINISHED') {
                        $isReady = true;
                        break;
                    }

                    if ($statusCode === 'EXPIRED') {
                        throw new \Exception("Container do Instagram expirou ({$typeLabel}).");
                    }

                    if ($statusCode === 'ERROR') {
                        $lastErrorMsg = $statusDetail ?: 'Erro durante processamento do vídeo pela Meta.';
                        // Só aborta se continuar em ERROR após 14 tentativas (~42 segundos)
                        if ($attempt >= 14) {
                            throw new \Exception("Instagram falhou ao processar o vídeo ({$typeLabel}): {$lastErrorMsg}");
                        }
                    }
                }

                if (!$isReady) {
                    $extra = $lastErrorMsg ? " ({$lastErrorMsg})" : '';
                    throw new \Exception("O vídeo demorou para ser processado pelos servidores do Instagram ({$typeLabel}){$extra}. Tente novamente em instantes.");
                }

                // PASSO 3: Publicar o Container no Instagram
                Log::info("[InstagramService] Publicando container {$containerId} ({$typeLabel}) no Instagram...");
                $publishRes = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$igUserId}/media_publish", [
                    'creation_id' => $containerId,
                    'access_token' => $accessToken,
                ]);

                if (!$publishRes->successful() || empty($publishRes->json('id'))) {
                    $err = $publishRes->json('error.message') ?? $publishRes->body();
                    Log::error("[InstagramService] Erro no media_publish ({$typeLabel}): {$err}");
                    throw new \Exception("Erro ao concluir publicação de {$typeLabel} no Instagram: {$err}");
                }

                $mediaId = $publishRes->json('id');

                // PASSO 4: Obter permalink
                $permalink = $mediaType === 'STORIES'
                    ? "https://www.instagram.com/stories/{$igUsername}/"
                    : "https://www.instagram.com/reel/{$mediaId}/";

                try {
                    $infoRes = Http::get("https://graph.facebook.com/{$this->graphVersion}/{$mediaId}", [
                        'fields' => 'permalink',
                        'access_token' => $accessToken,
                    ]);
                    if ($infoRes->successful() && !empty($infoRes->json('permalink'))) {
                        $permalink = $infoRes->json('permalink');
                    }
                } catch (\Throwable $t) {
                    // mantém link fallback
                }

                // Se publicou ambos (REELS + STORIES), prioriza guardar o media_id e permalink do Reel (que recebe comentários públicos e não expira em 24h)
                if (!$lastMediaId || $mediaType === 'REELS') {
                    $lastMediaId = $mediaId;
                }
                if (!$lastPermalink || $mediaType === 'REELS') {
                    $lastPermalink = $permalink;
                }

                $results[$mediaType] = [
                    'media_id' => $mediaId,
                    'url' => $permalink,
                ];

                Log::info("[InstagramService] {$typeLabel} da peça #{$code} publicado com sucesso! Media ID: {$mediaId} | Link: {$permalink}");
            }

            DB::table('live_items')
                ->where('id', $liveItem->id)
                ->update([
                    'instagram_media_id' => $lastMediaId,
                    'instagram_url' => $lastPermalink,
                    'instagram_status' => 'published',
                    'instagram_published_at' => now(),
                    'instagram_error' => null,
                    'updated_at' => now(),
                ]);

            $msgLabel = match ($requestedType) {
                'STORIES' => "Story da peça #{$code} publicado com sucesso no Instagram!",
                'BOTH' => "Peça #{$code} publicada no Reels e nos Stories do Instagram!",
                default => "Reel da peça #{$code} publicado com sucesso no Instagram!",
            };

            return [
                'success' => true,
                'message' => $msgLabel,
                'media_type' => $requestedType,
                'media_id' => $lastMediaId,
                'url' => $lastPermalink,
                'results' => $results,
            ];

        } catch (\Throwable $e) {
            Log::error("[InstagramService] Exceção durante publicação de corte: " . $e->getMessage());

            DB::table('live_items')
                ->where('id', $liveItem->id)
                ->update([
                    'instagram_status' => 'error',
                    'instagram_error' => $e->getMessage(),
                    'updated_at' => now(),
                ]);

            return [
                'success' => false,
                'message' => 'Erro ao publicar no Instagram: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Busca comentários de um vídeo/Reel específico no Instagram
     */
    public function fetchMediaComments(string $mediaId, int $limit = 50): array
    {
        $account = $this->getActiveAccount();
        if (!$account || empty($account->access_token)) {
            return ['success' => false, 'message' => 'Conta do Instagram não conectada.', 'items' => []];
        }

        try {
            $res = Http::get("https://graph.facebook.com/{$this->graphVersion}/{$mediaId}/comments", [
                'fields' => 'id,text,username,timestamp,from',
                'limit' => $limit,
                'access_token' => $account->access_token,
            ]);

            if (!$res->successful()) {
                return [
                    'success' => false,
                    'message' => $res->json('error.message') ?? 'Falha ao buscar comentários do Reel.',
                    'items' => [],
                ];
            }

            return [
                'success' => true,
                'items' => $res->json('data') ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error("[InstagramService@fetchMediaComments] Erro no media {$mediaId}: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'items' => []];
        }
    }

    /**
     * Busca as publicações/Reels recentes da conta do Instagram que possuem comentários
     */
    public function fetchRecentAccountMediaWithComments(int $limit = 35): array
    {
        $account = $this->getActiveAccount();
        if (!$account || empty($account->access_token) || empty($account->channel_id)) {
            return ['success' => false, 'message' => 'Conta do Instagram não conectada.', 'items' => []];
        }

        try {
            $res = Http::get("https://graph.facebook.com/{$this->graphVersion}/{$account->channel_id}/media", [
                'fields' => 'id,caption,media_type,media_product_type,permalink,timestamp,comments_count',
                'limit' => $limit,
                'access_token' => $account->access_token,
            ]);

            if (!$res->successful()) {
                return [
                    'success' => false,
                    'message' => $res->json('error.message') ?? 'Falha ao listar Reels da conta.',
                    'items' => [],
                ];
            }

            $mediaList = $res->json('data') ?? [];
            $allComments = [];

            foreach ($mediaList as $media) {
                $commentsCount = (int) ($media['comments_count'] ?? 0);
                if ($commentsCount <= 0) {
                    continue;
                }

                $mediaId = $media['id'] ?? null;
                if (!$mediaId) continue;

                $cRes = $this->fetchMediaComments($mediaId, 50);
                if (!empty($cRes['success']) && !empty($cRes['items'])) {
                    foreach ($cRes['items'] as $comment) {
                        $comment['media_id'] = $mediaId;
                        $comment['media_permalink'] = $media['permalink'] ?? null;
                        $comment['media_caption'] = $media['caption'] ?? '';
                        $allComments[] = $comment;
                    }
                }
            }

            return [
                'success' => true,
                'items' => $allComments,
            ];
        } catch (\Throwable $e) {
            Log::error('[InstagramService@fetchRecentAccountMediaWithComments] Erro: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'items' => []];
        }
    }

    /**
     * Responde diretamente a um comentário de Reel/Post no Instagram
     */
    public function replyToComment(string $commentId, string $replyText): array
    {
        $account = $this->getActiveAccount();
        if (!$account || empty($account->access_token)) {
            return ['success' => false, 'message' => 'Conta do Instagram não conectada.'];
        }

        try {
            $res = Http::post("https://graph.facebook.com/{$this->graphVersion}/{$commentId}/replies", [
                'message' => $replyText,
                'access_token' => $account->access_token,
            ]);

            if (!$res->successful()) {
                $errMsg = $res->json('error.message') ?? $res->body();
                Log::warning("[InstagramService@replyToComment] Falha ao responder comentário {$commentId}: {$errMsg}");
                return [
                    'success' => false,
                    'message' => $errMsg,
                ];
            }

            return [
                'success' => true,
                'reply_id' => $res->json('id'),
            ];
        } catch (\Throwable $e) {
            Log::error("[InstagramService@replyToComment] Exceção: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\YouTube\YouTubeService;
use App\Models\YouTubeComment;
use App\Models\LiveItem;
use App\Models\LiveMessage;
use App\Models\LiveCodeRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckYouTubeShortsCommentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-youtube-shorts-comments {--video_id= : Filtrar apenas por um vídeo/short específico} {--auto_reply=1 : Responder automaticamente os comentários no YouTube com WhatsApp}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitora comentários em Shorts do YouTube, detecta intenção de compra (QUERO) e responde automaticamente com o WhatsApp da peça.';

    /**
     * Execute the console command.
     */
    public function handle(YouTubeService $youtubeService)
    {
        $this->info("Iniciando verificação de novos comentários nos YouTube Shorts...");

        $targetVideoId = $this->option('video_id');
        $autoReply = (bool) $this->option('auto_reply');

        $threads = [];

        if ($targetVideoId) {
            $this->line("Buscando comentários para o vídeo: {$targetVideoId}...");
            $res = $youtubeService->fetchVideoComments($targetVideoId, 50);
            if (!empty($res['success']) && !empty($res['items'])) {
                $threads = $res['items'];
            }
        } else {
            // 1. Tentar buscar todos os comentários recentes associados ao canal
            $this->line("Buscando comentários recentes de todo o canal...");
            $res = $youtubeService->fetchRecentChannelComments(100);
            if (!empty($res['success']) && !empty($res['items'])) {
                $threads = $res['items'];
            }

            // 2. Se a busca por canal não retornou comentários ou falhou, fazer varredura nos últimos shorts publicados
            if (empty($threads)) {
                $this->line("Consultando vídeos recentes publicados em live_items...");
                $recentItems = LiveItem::whereNotNull('youtube_video_id')
                    ->where('youtube_status', 'published')
                    ->orderBy('id', 'desc')
                    ->limit(20)
                    ->get();

                foreach ($recentItems as $item) {
                    $vidRes = $youtubeService->fetchVideoComments($item->youtube_video_id, 20);
                    if (!empty($vidRes['success']) && !empty($vidRes['items'])) {
                        foreach ($vidRes['items'] as $it) {
                            $threads[] = $it;
                        }
                    }
                }
            }
        }

        if (empty($threads)) {
            $this->line("Nenhum comentário recente encontrado no YouTube.");
            return Command::SUCCESS;
        }

        $this->info("Total de threads de comentários obtidas: " . count($threads));

        // Padrão regex para detecção de intenção de compra
        $intentPattern = '/\b(quero|eu\s+quero|quero\s+essa|quero\s+esse|reserva|reservar|tenho\s+interesse|comprar|ainda\s+tem|ta\s+disponivel|tá\s+disponivel|disponivel|disponível|qual\s+o\s+valor|compro|fica\s+comigo|minha|meu)\b/i';

        $newCount = 0;
        $intentCount = 0;
        $repliedCount = 0;

        foreach ($threads as $thread) {
            $topLevel = $thread['snippet']['topLevelComment'] ?? $thread;
            $commentId = $topLevel['id'] ?? null;
            if (!$commentId) continue;

            // Se já foi registrado anteriormente, pula
            if (YouTubeComment::where('comment_id', $commentId)->exists()) {
                continue;
            }

            $snippet = $topLevel['snippet'] ?? [];
            $videoId = $snippet['videoId'] ?? ($thread['snippet']['videoId'] ?? null);
            $text = trim((string) ($snippet['textDisplay'] ?? ($snippet['textOriginal'] ?? '')));
            $authorName = trim((string) ($snippet['authorDisplayName'] ?? 'Cliente YouTube'));
            $authorChannelId = $snippet['authorChannelId']['value'] ?? null;
            $authorProfileImage = $snippet['authorProfileImageUrl'] ?? null;
            $publishedAt = isset($snippet['publishedAt']) ? Carbon::parse($snippet['publishedAt']) : now();

            // Identifica se tem intenção de compra
            $isPurchaseIntent = (bool) preg_match($intentPattern, $text, $matches);
            $detectedKeyword = $matches[0] ?? null;

            // Tenta localizar a peça correspondente pelo ID do vídeo do YouTube
            $liveItem = null;
            if ($videoId) {
                $liveItem = LiveItem::with(['item', 'live'])
                    ->where('youtube_video_id', $videoId)
                    ->first();
            }

            $ytComment = YouTubeComment::create([
                'comment_id' => $commentId,
                'video_id' => $videoId ?: 'unknown',
                'live_item_id' => $liveItem ? $liveItem->id : null,
                'live_id' => $liveItem ? $liveItem->live_id : null,
                'author_name' => $authorName,
                'author_channel_id' => $authorChannelId,
                'author_profile_image' => $authorProfileImage,
                'comment_text' => $text,
                'is_purchase_intent' => $isPurchaseIntent,
                'detected_keyword' => $detectedKeyword,
                'published_at' => $publishedAt,
            ]);

            $newCount++;

            if ($isPurchaseIntent) {
                $intentCount++;
                $this->warn("🎯 [QUERO Detectado] Vídeo: {$videoId} | Autor: {$authorName} | Mensagem: \"{$text}\" (Palavra: {$detectedKeyword})");

                // 1. Gravar em live_messages para aparecer no feed da transmissão e relatórios
                if ($liveItem && $liveItem->live_id) {
                    $liveMessage = LiveMessage::create([
                        'live_id' => $liveItem->live_id,
                        'plataforma' => 'youtube',
                        'host_account' => 'Minha Mania',
                        'username' => $authorName,
                        'message' => $text,
                        'avatar_url' => $authorProfileImage,
                        'is_marked' => true,
                        'captured_at' => $publishedAt,
                    ]);

                    // 2. Criar LiveCodeRequest vinculando a peça
                    $codigo = $liveItem->codigo_live ?: ($liveItem->item ? $liveItem->item->codigo : $liveItem->item_id);
                    LiveCodeRequest::create([
                        'live_id' => $liveItem->live_id,
                        'live_message_id' => $liveMessage->id,
                        'username' => $authorName,
                        'user_id' => null,
                        'item_id' => $liveItem->item_id,
                        'codigo' => $codigo,
                        'message_text' => $text,
                        'status' => 'pending',
                    ]);
                }

                // 3. Responder automaticamente no YouTube Shorts com link do WhatsApp
                if ($autoReply) {
                    $codigo = $liveItem ? ($liveItem->codigo_live ?: ($liveItem->item ? $liveItem->item->codigo : $liveItem->item_id)) : '';
                    $isSold = $liveItem && (!empty($liveItem->buyer_name) || $liveItem->youtube_status === 'sold_updated');
                    $productName = $liveItem && $liveItem->item ? $liveItem->item->nome_do_produto : 'esta peça';

                    if ($isSold) {
                        $replyText = "Olá @{$authorName}! 🔴 A peça #{$codigo} ({$productName}) já foi vendida na live. Mas temos novidades incríveis parecidas todos os dias em nosso catálogo: https://minhamania.net ou chame no WhatsApp (48) 98481-3589 ✨";
                    } else {
                        $waText = urlencode("Olá! Vi a peça #{$codigo} ({$productName}) no YouTube Shorts e comentei '{$text}'. Quero garantir na minha sacolinha!");
                        $replyText = "Olá @{$authorName}! ✨ Quer garantir essa peça #{$codigo}? Clique no link do nosso WhatsApp oficial para colocar na sua sacolinha: https://wa.me/5548984813589?text={$waText} 🛍️";
                    }

                    $replyRes = $youtubeService->replyToComment($commentId, $replyText);
                    if (!empty($replyRes['success'])) {
                        $ytComment->update([
                            'reply_sent' => true,
                            'reply_text' => $replyText,
                            'reply_sent_at' => now(),
                        ]);
                        $repliedCount++;
                        $this->info("   ↳ ✅ Resposta automática enviada no YouTube com sucesso!");
                    } else {
                        $this->error("   ↳ ❌ Falha ao responder no YouTube: " . ($replyRes['message'] ?? 'Erro desconhecido'));
                    }
                }
            } else {
                $this->line("   Comentário comum registrado: {$authorName}: \"{$text}\"");
            }
        }

        $this->info("✅ Verificação finalizada!");
        $this->line("- Novos comentários gravados: {$newCount}");
        $this->line("- Intenções de compra (QUERO): {$intentCount}");
        $this->line("- Respostas enviadas no YouTube: {$repliedCount}");

        return Command::SUCCESS;
    }
}

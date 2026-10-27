<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Instagram\InstagramService;
use App\Models\SocialCutComment;
use App\Models\LiveItem;
use App\Models\LiveMessage;
use App\Models\LiveCodeRequest;
use Carbon\Carbon;

class CheckInstagramReelsCommentsCommand extends Command
{
    protected $signature = 'app:check-instagram-reels-comments {--media_id= : Filtrar apenas por um Reel/post específico} {--auto_reply=1 : Responder automaticamente comentários de intenção de compra}';

    protected $description = 'Monitora comentários em Reels do Instagram, detecta intenção de compra (QUERO) e vincula às peças.';

    public function handle(InstagramService $instagramService)
    {
        $this->info('Iniciando verificação de novos comentários nos Reels do Instagram...');

        $targetMediaId = $this->option('media_id');
        $autoReply = (bool) $this->option('auto_reply');

        $commentsList = [];

        if ($targetMediaId) {
            $res = $instagramService->fetchMediaComments($targetMediaId, 50);
            if (!empty($res['success']) && !empty($res['items'])) {
                foreach ($res['items'] as $c) {
                    $c['media_id'] = $targetMediaId;
                    $commentsList[] = $c;
                }
            }
        } else {
            // 1. Buscar comentários das mídias recentes da conta (@de_minha_mania) que possuem comments_count > 0
            $res = $instagramService->fetchRecentAccountMediaWithComments(35);
            if (!empty($res['success']) && !empty($res['items'])) {
                $commentsList = $res['items'];
            }

            // 2. Complementar verificando cortes publicados em live_items com instagram_media_id
            $recentCutItems = LiveItem::whereNotNull('instagram_media_id')
                ->where('instagram_status', 'published')
                ->orderByDesc('id')
                ->limit(25)
                ->get();

            $checkedMediaIds = array_unique(array_filter(array_column($commentsList, 'media_id')));

            foreach ($recentCutItems as $cutItem) {
                $mId = trim((string) $cutItem->instagram_media_id);
                if (!$mId || in_array($mId, $checkedMediaIds, true)) {
                    continue;
                }
                $checkedMediaIds[] = $mId;

                $mRes = $instagramService->fetchMediaComments($mId, 30);
                if (!empty($mRes['success']) && !empty($mRes['items'])) {
                    foreach ($mRes['items'] as $c) {
                        $c['media_id'] = $mId;
                        $c['live_item_id'] = $cutItem->id;
                        $commentsList[] = $c;
                    }
                }
            }
        }

        if (empty($commentsList)) {
            $this->line('Nenhum comentário encontrado nos Reels do Instagram.');
            return Command::SUCCESS;
        }

        $intentPattern = '/\b(quero|eu\s+quero|quero\s+essa|quero\s+esse|reserva|reservar|tenho\s+interesse|comprar|ainda\s+tem|ta\s+disponivel|tá\s+disponivel|disponivel|disponível|qual\s+o\s+valor|compro|fica\s+comigo|minha|meu)\b/iu';

        $newCount = 0;
        $intentCount = 0;
        $repliedCount = 0;

        foreach ($commentsList as $c) {
            $commentId = (string) ($c['id'] ?? '');
            if ($commentId === '') continue;

            if (SocialCutComment::where('platform', 'instagram')->where('comment_id', $commentId)->exists()) {
                continue;
            }

            $mediaId = (string) ($c['media_id'] ?? 'unknown');
            $text = trim((string) ($c['text'] ?? ''));
            $authorUsername = trim((string) ($c['username'] ?? ($c['from']['username'] ?? '')));
            $authorName = $authorUsername !== '' ? '@' . ltrim($authorUsername, '@') : 'Cliente Instagram';
            $authorId = $c['from']['id'] ?? null;
            $publishedAt = !empty($c['timestamp']) ? Carbon::parse($c['timestamp']) : now();

            $isPurchaseIntent = (bool) preg_match($intentPattern, $text, $matches);
            $detectedKeyword = $matches[0] ?? null;

            // Localizar a peça correspondente (pelo instagram_media_id, pelo permalink ou pelo #código na legenda do Reel)
            $liveItem = null;
            if (!empty($c['live_item_id'])) {
                $liveItem = LiveItem::with(['item', 'live'])->find($c['live_item_id']);
            }
            if (!$liveItem && $mediaId !== 'unknown') {
                $liveItem = LiveItem::with(['item', 'live'])
                    ->where('instagram_media_id', $mediaId)
                    ->first();
            }
            if (!$liveItem && !empty($c['media_permalink'])) {
                $liveItem = LiveItem::with(['item', 'live'])
                    ->where('instagram_url', $c['media_permalink'])
                    ->first();
            }
            if (!$liveItem && !empty($c['media_caption'])) {
                if (preg_match('/Pe[cç]a\s*#(\d+)/iu', $c['media_caption'], $capMatch)) {
                    $liveItem = LiveItem::with(['item', 'live'])
                        ->where('codigo_live', $capMatch[1])
                        ->orderByDesc('id')
                        ->first();
                }
            }

            $igComment = SocialCutComment::create([
                'platform' => 'instagram',
                'comment_id' => $commentId,
                'video_id' => $mediaId,
                'live_item_id' => $liveItem ? $liveItem->id : null,
                'live_id' => $liveItem ? $liveItem->live_id : null,
                'author_name' => $authorName,
                'author_channel_id' => $authorId,
                'author_profile_image' => null,
                'comment_text' => $text,
                'is_purchase_intent' => $isPurchaseIntent,
                'detected_keyword' => $detectedKeyword,
                'published_at' => $publishedAt,
            ]);

            $newCount++;

            if ($isPurchaseIntent) {
                $intentCount++;

                if ($liveItem && $liveItem->live_id) {
                    $liveMessage = LiveMessage::create([
                        'live_id' => $liveItem->live_id,
                        'plataforma' => 'instagram',
                        'host_account' => 'Minha Mania',
                        'username' => $authorName,
                        'message' => $text,
                        'avatar_url' => null,
                        'is_marked' => true,
                        'captured_at' => $publishedAt,
                    ]);

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

                if ($autoReply) {
                    $codigo = $liveItem ? ($liveItem->codigo_live ?: ($liveItem->item ? $liveItem->item->codigo : $liveItem->item_id)) : '';
                    $isSold = $liveItem && (!empty($liveItem->buyer_username) || !empty($liveItem->user_id));
                    $productName = $liveItem && $liveItem->item ? $liveItem->item->nome_do_produto : 'esta peça';

                    if ($isSold) {
                        $replyText = "Olá {$authorName}! 🔴 A peça #{$codigo} ({$productName}) já foi arrematada. Mas temos novidades parecidas todos os dias em https://minhamania.net ✨";
                    } else {
                        $replyText = "Olá {$authorName}! ✨ Para garantir a peça #{$codigo} ({$productName}) na sua sacolinha, chame agora no nosso WhatsApp oficial (48) 98481-3589 ou clique no link da bio! 🛍️";
                    }

                    $replyRes = $instagramService->replyToComment($commentId, $replyText);
                    if (!empty($replyRes['success'])) {
                        $igComment->update([
                            'reply_sent' => true,
                            'reply_text' => $replyText,
                            'reply_sent_at' => now(),
                        ]);
                        $repliedCount++;
                    }
                }
            }
        }

        $this->info("✅ Instagram Reels sincronizado: {$newCount} novos comentários ({$intentCount} intenções QUERO, {$repliedCount} respostas enviadas).");
        return Command::SUCCESS;
    }
}

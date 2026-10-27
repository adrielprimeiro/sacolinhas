<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Instagram\InstagramService;
use App\Models\SocialCutComment;
use App\Models\Live;
use App\Models\LiveItem;
use App\Models\LiveMessage;
use App\Models\LiveCodeRequest;
use Carbon\Carbon;

class CheckInstagramReelsCommentsCommand extends Command
{
    protected $signature = 'app:check-instagram-reels-comments {--media_id= : Filtrar apenas por um Reel/post específico} {--auto_reply=1 : Responder automaticamente comentários de intenção de compra}';

    protected $description = 'Monitora comentários em Reels do Instagram, detecta intenção de compra (QUERO) e vincula às peças e lives.';

    public function handle(InstagramService $instagramService)
    {
        $this->info('Iniciando verificação de comentários nos Reels do Instagram...');

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

            // 2. Complementar verificando cortes publicados em live_items com instagram_media_id (apenas Reels)
            $recentCutItems = LiveItem::whereNotNull('instagram_media_id')
                ->where('instagram_status', 'published')
                ->where(function ($q) {
                    $q->whereNull('instagram_url')
                      ->orWhere('instagram_url', 'not like', '%/stories/%');
                })
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
                        $c['media_permalink'] = $cutItem->instagram_url;
                        $commentsList[] = $c;
                    }
                }
            }
        }

        if (empty($commentsList)) {
            $this->line('Nenhum comentário encontrado nos Reels do Instagram.');
            return Command::SUCCESS;
        }

        $intentPattern = '/\b(quero|eu\s+quero|quero\s+essa|quero\s+esse|reserva|reservar|tenho\s+interesse|comprar|ainda\s+tem|ta\s+disponivel|tá\s+disponivel|disponivel|disponível|qual\s+o\s+valor|valor|pre[cç]o|quanto|tens|vc\s+tem|voc[eê]\s+tem|compro|fica\s+comigo|minha|meu)\b/iu';

        $newCount = 0;
        $updatedCount = 0;
        $intentCount = 0;
        $repliedCount = 0;

        foreach ($commentsList as $c) {
            $commentId = (string) ($c['id'] ?? '');
            if ($commentId === '') continue;

            $mediaId = (string) ($c['media_id'] ?? 'unknown');
            $text = trim((string) ($c['text'] ?? ''));
            $authorUsername = trim((string) ($c['username'] ?? ($c['from']['username'] ?? '')));
            $authorName = $authorUsername !== '' ? '@' . ltrim($authorUsername, '@') : 'Cliente Instagram';
            $authorId = $c['from']['id'] ?? null;
            $publishedAt = !empty($c['timestamp']) ? Carbon::parse($c['timestamp']) : now();

            $videoUrl = $c['media_permalink'] ?? null;
            $rawCaption = trim(preg_replace('/\s+/', ' ', (string) ($c['media_caption'] ?? '')));
            $videoTitle = $rawCaption !== '' ? mb_substr($rawCaption, 0, 140) : null;
            $videoThumb = $c['media_thumbnail'] ?? null;

            $isPurchaseIntent = (bool) preg_match($intentPattern, $text, $matches);
            $detectedKeyword = $matches[0] ?? null;

            // 1. Localizar a peça correspondente (pelo instagram_media_id, pelo permalink ou pelo #código na legenda do Reel)
            $liveItem = null;
            if (!empty($c['live_item_id'])) {
                $liveItem = LiveItem::with(['item', 'live'])->find($c['live_item_id']);
            }
            if (!$liveItem && $mediaId !== 'unknown') {
                $liveItem = LiveItem::with(['item', 'live'])
                    ->where('instagram_media_id', $mediaId)
                    ->first();
            }
            if (!$liveItem && $videoUrl) {
                $liveItem = LiveItem::with(['item', 'live'])
                    ->where('instagram_url', $videoUrl)
                    ->first();
            }
            if (!$liveItem && $rawCaption !== '') {
                if (preg_match('/Pe[cç]a\s*#(\d+)/iu', $rawCaption, $capMatch)) {
                    $liveItem = LiveItem::with(['item', 'live'])
                        ->where('codigo_live', $capMatch[1])
                        ->orderByDesc('id')
                        ->first();
                }
            }

            // 2. Se o Reel é a gravação de uma Live inteira, localizar a Live correspondente pela data do Reel
            $matchedLiveId = $liveItem ? $liveItem->live_id : null;
            if (!$matchedLiveId && !empty($c['media_timestamp'])) {
                $mediaDate = Carbon::parse($c['media_timestamp'])->timezone('America/Sao_Paulo');
                $candidateLive = Live::whereBetween('data', [
                    $mediaDate->copy()->subDays(2)->startOfDay(),
                    $mediaDate->copy()->endOfDay()
                ])->orderByDesc('data')->orderByDesc('id')->first();

                if ($candidateLive) {
                    $matchedLiveId = $candidateLive->id;
                }
            }

            // 3. Se o comentário cita um código numérico de peça (ex: "Tens esse 86", "98 vc tem", "Quero 105"), vincula à peça da Live!
            if (!$liveItem && preg_match('/\b(\d{2,3})\b/', $text, $codeMatch)) {
                $spokenCode = $codeMatch[1];
                $itemQuery = LiveItem::with(['item', 'live'])->where('codigo_live', $spokenCode);
                if ($matchedLiveId) {
                    $liveItem = (clone $itemQuery)->where('live_id', $matchedLiveId)->first();
                }
                if (!$liveItem) {
                    $liveItem = $itemQuery->orderByDesc('id')->first();
                }
                if ($liveItem) {
                    $matchedLiveId = $liveItem->live_id;
                    if (!$isPurchaseIntent) {
                        $isPurchaseIntent = true;
                        $detectedKeyword = "#{$spokenCode}";
                    }
                }
            }

            $existing = SocialCutComment::where('platform', 'instagram')
                ->where('comment_id', $commentId)
                ->first();

            if ($existing) {
                $updates = [];
                if ($authorUsername !== '' && ($existing->author_name === 'Cliente Instagram' || empty($existing->author_name))) {
                    $updates['author_name'] = $authorName;
                }
                if ($videoUrl && empty($existing->video_url)) {
                    $updates['video_url'] = $videoUrl;
                }
                if ($videoTitle && empty($existing->video_title)) {
                    $updates['video_title'] = $videoTitle;
                }
                if ($videoThumb && ($existing->video_thumbnail !== $videoThumb)) {
                    $updates['video_thumbnail'] = $videoThumb;
                }
                if ($liveItem && empty($existing->live_item_id)) {
                    $updates['live_item_id'] = $liveItem->id;
                }
                if ($matchedLiveId && empty($existing->live_id)) {
                    $updates['live_id'] = $matchedLiveId;
                }
                if ($isPurchaseIntent && !$existing->is_purchase_intent) {
                    $updates['is_purchase_intent'] = true;
                    $updates['detected_keyword'] = $detectedKeyword;
                }
                if (!empty($updates)) {
                    $existing->update($updates);
                    $updatedCount++;
                }
                continue;
            }

            $igComment = SocialCutComment::create([
                'platform' => 'instagram',
                'comment_id' => $commentId,
                'video_id' => $mediaId,
                'video_url' => $videoUrl,
                'video_title' => $videoTitle,
                'video_thumbnail' => $videoThumb,
                'live_item_id' => $liveItem ? $liveItem->id : null,
                'live_id' => $matchedLiveId,
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
                        $codeLabel = $codigo ? "a peça #{$codigo} ({$productName})" : "essa peça";
                        $replyText = "Olá {$authorName}! ✨ Para garantir {$codeLabel} na sua sacolinha, chame agora no nosso WhatsApp oficial (48) 98481-3589 ou clique no link da bio! 🛍️";
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

        $this->info("✅ Instagram Reels sincronizado: {$newCount} novos, {$updatedCount} enriquecidos ({$intentCount} intenções QUERO, {$repliedCount} respostas enviadas).");
        return Command::SUCCESS;
    }
}

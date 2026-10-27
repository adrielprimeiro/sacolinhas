<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SocialCutComment;
use App\Models\Live;
use App\Models\LiveItem;
use App\Models\Item;
use App\Models\Sacolinhas;
use App\Models\User;
use App\Services\YouTube\YouTubeService;
use App\Services\Instagram\InstagramService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SocialCutsCommentsController extends Controller
{
    protected YouTubeService $youtubeService;
    protected InstagramService $instagramService;

    public function __construct(YouTubeService $youtubeService, InstagramService $instagramService)
    {
        $this->youtubeService = $youtubeService;
        $this->instagramService = $instagramService;
    }

    /**
     * Tela principal de gerenciamento de comentários dos cortes sociais
     */
    public function index(Request $request)
    {
        $platform = $request->query('platform', 'all');
        $filter = $request->query('filter', 'all');
        $liveId = $request->query('live_id');
        $search = trim((string) $request->query('search', ''));

        // Query principal
        $query = SocialCutComment::with([
            'liveItem.item',
            'liveItem.live',
            'liveItem.user',
            'live'
        ])->orderByDesc('published_at')->orderByDesc('id');

        // Filtro por rede social
        if ($platform && $platform !== 'all') {
            $query->where('platform', $platform);
        }

        // Filtro por intenção ou status de resposta
        if ($filter === 'intent') {
            $query->where('is_purchase_intent', true);
        } elseif ($filter === 'pending_reply') {
            $query->where('is_purchase_intent', true)->where('reply_sent', false);
        } elseif ($filter === 'replied') {
            $query->where('reply_sent', true);
        }

        // Filtro por Live
        if ($liveId && is_numeric($liveId)) {
            $query->where(function($q) use ($liveId) {
                $q->where('live_id', $liveId)
                  ->orWhereHas('liveItem', fn($sub) => $sub->where('live_id', $liveId));
            });
        }

        // Busca de texto (nome, comentário ou código da peça)
        if ($search) {
            $query->where(function($q) use ($search) {
                $cleanSearch = ltrim($search, '#');
                $q->where('author_name', 'like', "%{$search}%")
                  ->orWhere('comment_text', 'like', "%{$search}%")
                  ->orWhereHas('liveItem', function($sub) use ($search, $cleanSearch) {
                      $sub->where('codigo_live', 'like', "%{$cleanSearch}%")
                          ->orWhereHas('item', function($itemSub) use ($search, $cleanSearch) {
                              $itemSub->where('codigo', 'like', "%{$cleanSearch}%")
                                      ->orWhere('nome_do_produto', 'like', "%{$search}%");
                          });
                  });
            });
        }

        // Estatísticas para os cards e contadores das abas
        $stats = [
            'total_all' => SocialCutComment::count(),
            'total_youtube' => SocialCutComment::where('platform', 'youtube')->count(),
            'total_instagram' => SocialCutComment::where('platform', 'instagram')->count(),
            'total_tiktok' => SocialCutComment::where('platform', 'tiktok')->count(),
            'total_intent' => SocialCutComment::where('is_purchase_intent', true)->count(),
            'total_pending_reply' => SocialCutComment::where('is_purchase_intent', true)->where('reply_sent', false)->count(),
            'total_replied' => SocialCutComment::where('reply_sent', true)->count(),
        ];

        $comments = $query->paginate(25)->withQueryString();

        // Lista de lives para o filtro
        $lives = Live::orderByDesc('id')->take(30)->get(['id', 'data', 'tipo_live']);

        if ($request->ajax() && $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'comments' => $comments
            ]);
        }

        return view('admin.lives.cortes_comentarios', compact(
            'comments',
            'stats',
            'lives',
            'platform',
            'filter',
            'liveId',
            'search'
        ));
    }

    /**
     * Dispara sincronização em segundo plano dos comentários (YouTube Shorts + Instagram Reels)
     */
    public function syncComments(Request $request)
    {
        try {
            $outputs = [];

            try {
                Artisan::call('app:check-youtube-shorts-comments');
                $outputs[] = trim(Artisan::output());
            } catch (\Throwable $ytErr) {
                Log::warning('[SocialCutsCommentsController@syncComments] Aviso YouTube: ' . $ytErr->getMessage());
            }

            try {
                Artisan::call('app:check-instagram-reels-comments');
                $outputs[] = trim(Artisan::output());
            } catch (\Throwable $igErr) {
                Log::warning('[SocialCutsCommentsController@syncComments] Aviso Instagram: ' . $igErr->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Sincronização de comentários (YouTube + Instagram) concluída com sucesso!',
                'output' => implode("\n", array_filter($outputs))
            ]);
        } catch (\Throwable $e) {
            Log::error('[SocialCutsCommentsController@syncComments] Erro: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao sincronizar comentários: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envia resposta direta para um comentário (manual ou automática)
     */
    public function sendReply(Request $request, $id)
    {
        $comment = SocialCutComment::with('liveItem.item')->findOrFail($id);

        $customText = trim((string) $request->input('reply_text', ''));

        // Se não forneceu texto customizado, gera o padrão de sacolinha
        if (!$customText) {
            $item = $comment->liveItem?->item;
            $code = $comment->liveItem?->codigo_live ?: ($item?->codigo ?: '');
            $isSold = $comment->liveItem && (!empty($comment->liveItem->buyer_username) || !empty($comment->liveItem->user_id));

            if ($isSold) {
                $customText = "Olá {$comment->author_name}! 🔴 A peça #{$code} já foi arrematada. Mas temos novidades parecidas todos os dias em nosso catálogo: https://minhamania.net ✨";
            } else {
                $whatsMsg = "Olá! Vi a peça #{$code} no vídeo e comentei QUERO. Gostaria de adicionar na minha sacolinha!";
                $encodedMsg = urlencode($whatsMsg);
                $whatsLink = "https://wa.me/5548984813589?text={$encodedMsg}";
                $customText = "Olá {$comment->author_name}! ✨ Quer garantir essa peça #{$code}? Clique no link do nosso WhatsApp oficial para colocar na sua sacolinha: {$whatsLink} 🛍️";
            }
        }

        try {
            if ($comment->platform === 'youtube') {
                $replyRes = $this->youtubeService->replyToComment($comment->comment_id, $customText);
                if (!$replyRes['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erro ao enviar resposta no YouTube: ' . ($replyRes['message'] ?? 'Falha na API.')
                    ], 400);
                }
            } elseif ($comment->platform === 'instagram') {
                $replyRes = $this->instagramService->replyToComment($comment->comment_id, $customText);
                if (!$replyRes['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erro ao enviar resposta no Instagram: ' . ($replyRes['message'] ?? 'Reconecte o Instagram com permissão de comentários.')
                    ], 400);
                }
            }

            $comment->update([
                'reply_sent' => true,
                'reply_text' => $customText,
                'reply_sent_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Resposta enviada com sucesso!',
                'reply_text' => $customText,
                'reply_sent_at' => now()->format('d/m/Y H:i')
            ]);
        } catch (\Throwable $e) {
            Log::error("[SocialCutsCommentsController@sendReply] Erro: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar resposta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Arrematar / Vincular peça à cliente diretamente a partir do comentário
     */
    public function linkItemBuyer(Request $request, $id)
    {
        $comment = SocialCutComment::with(['liveItem.item', 'live'])->findOrFail($id);
        $liveItem = $comment->liveItem;

        if (!$liveItem) {
            return response()->json([
                'success' => false,
                'message' => 'Este comentário não possui uma peça vinculada.'
            ], 400);
        }

        $username = trim((string) ($request->input('username') ?: $comment->author_name));
        $cleanUsername = ltrim($username, '@');
        $phone = trim((string) $request->input('phone', ''));
        $userId = $request->input('user_id');

        // Tentar localizar cliente pelo username ou telefone
        if (!$userId) {
            $user = User::where(function($q) use ($cleanUsername, $phone) {
                if ($cleanUsername) {
                    $q->where('instagram', $cleanUsername)
                      ->orWhere('tiktok', $cleanUsername)
                      ->orWhere('name', $cleanUsername)
                      ->orWhere('apelido', $cleanUsername);
                }
                if ($phone) {
                    $digits = preg_replace('/\D/', '', $phone);
                    $q->orWhere('phone', 'like', "%{$digits}%")
                      ->orWhere('whatsapp', 'like', "%{$digits}%");
                }
            })->first();

            if ($user) {
                $userId = $user->id;
            } elseif ($phone) {
                // Cadastrar cliente rápido se tiver telefone
                $user = User::create([
                    'name' => $cleanUsername ?: 'Cliente ' . $comment->platform,
                    'phone' => $phone,
                    'whatsapp' => $phone,
                    'instagram' => $comment->platform === 'instagram' ? $cleanUsername : null,
                    'tiktok' => $comment->platform === 'tiktok' ? $cleanUsername : null,
                    'brecho_id' => 1,
                    'password' => bcrypt(uniqid()),
                ]);
                $userId = $user->id;
            }
        }

        // Atualizar live_items
        $liveItem->update([
            'user_id' => $userId,
            'buyer_username' => $username,
            'buyer_name' => $userId ? (User::find($userId)?->name) : $username,
            'status_movimentacao' => 'enviado',
            'updated_at' => now(),
        ]);

        // Se tiver cadastro, insere na sacolinha
        if ($userId && $liveItem->item_id) {
            $item = Item::find($liveItem->item_id);
            if ($item) {
                $live = Live::find($liveItem->live_id);
                $price = $item->preco;
                if ($live && $live->tipo_live === 'precinho') {
                    $price = $price * 0.5;
                }

                Sacolinhas::withoutGlobalScopes()->updateOrCreate(
                    [
                        'user_id' => $userId,
                        'item_id' => $item->id,
                        'live_id' => $liveItem->live_id,
                    ],
                    [
                        'brecho_id' => $live ? ($live->brecho_id ?: 1) : 1,
                        'price' => $price,
                        'quantity' => 1,
                        'status' => 'sacola',
                        'origem' => 'social_cut_' . $comment->platform,
                    ]
                );

                $item->update(['status' => 'vendido']);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Peça arrematada e vinculada com sucesso!',
            'buyer_username' => $username,
            'buyer_user_id' => $userId
        ]);
    }

    /**
     * Vincula manualmente um comentário a uma peça específica (pelo código da live ou código do catálogo)
     */
    public function attachLiveItem(Request $request, $id)
    {
        $comment = SocialCutComment::findOrFail($id);
        $code = trim((string) $request->input('codigo', ''), '# ');
        $liveId = $request->input('live_id') ?: $comment->live_id;

        if ($code === '') {
            return response()->json([
                'success' => false,
                'message' => 'Informe o código da peça (ex: 105).'
            ], 422);
        }

        $query = LiveItem::with(['item', 'live'])
            ->where(function ($q) use ($code) {
                $q->where('codigo_live', $code)
                  ->orWhereHas('item', fn($sub) => $sub->where('codigo', $code));
            });

        if ($liveId) {
            $liveItem = (clone $query)->where('live_id', $liveId)->first();
        } else {
            $liveItem = null;
        }

        if (!$liveItem) {
            $liveItem = $query->orderByDesc('id')->first();
        }

        if (!$liveItem) {
            return response()->json([
                'success' => false,
                'message' => "Nenhuma peça encontrada com o código #{$code}."
            ], 404);
        }

        $comment->update([
            'live_item_id' => $liveItem->id,
            'live_id' => $liveItem->live_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Comentário vinculado à Peça #{$liveItem->codigo_live} (Live #{$liveItem->live_id}) com sucesso!"
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SocialChannelAccount;
use App\Models\Live;
use App\Services\Instagram\InstagramService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstagramIntegrationController extends Controller
{
    protected InstagramService $instagramService;

    public function __construct(InstagramService $instagramService)
    {
        $this->instagramService = $instagramService;
    }

    /**
     * Inicia a conexão OAuth com a conta do Instagram / Meta
     */
    public function connect(Request $request)
    {
        if (!$this->instagramService->isConfigured()) {
            return back()->with('error', 'Credenciais do Instagram (INSTAGRAM_APP_ID e INSTAGRAM_APP_SECRET) não configuradas no .env.');
        }

        if ($request->headers->get('referer')) {
            session(['instagram_return_url' => $request->headers->get('referer')]);
        }

        $authUrl = $this->instagramService->getAuthUrl();
        return redirect()->away($authUrl);
    }

    /**
     * Callback do OAuth do Instagram / Meta
     */
    public function callback(Request $request)
    {
        $code = $request->query('code');
        $error = $request->query('error');
        $errorDesc = $request->query('error_description');

        $latest = Live::where('ativo', true)->orderBy('id', 'desc')->first() ?? Live::orderBy('id', 'desc')->first();
        $defaultRoute = $latest ? route('admin.lives.cortes', ['liveId' => $latest->id]) : route('admin.lives.cortes.latest');
        $targetRoute = session()->pull('instagram_return_url', $defaultRoute) ?: $defaultRoute;

        if ($error) {
            Log::warning("[Instagram callback] Erro retornado na URL: {$error} - {$errorDesc}");
            return redirect($targetRoute)->with('error', "Erro na autorização do Instagram: {$error} - {$errorDesc}");
        }

        if (!$code) {
            Log::warning("[Instagram callback] Nenhum code recebido");
            return redirect($targetRoute)->with('error', 'Código de autorização não recebido do Instagram.');
        }

        $result = $this->instagramService->handleCallback($code);

        if ($result['success']) {
            return redirect($targetRoute)->with('success', $result['message']);
        }

        return redirect($targetRoute)->with('error', $result['message']);
    }

    /**
     * Desconecta a conta do Instagram
     */
    public function disconnect(Request $request)
    {
        SocialChannelAccount::where('provider', 'instagram')->update(['is_active' => false]);
        return back()->with('success', 'Conta do Instagram desconectada com sucesso.');
    }

    /**
     * Endpoint para publicar o corte de um item individual no Instagram Reels
     */
    public function uploadCut(Request $request, $liveItemId)
    {
        $liveItem = DB::table('live_items')
            ->join('items', 'live_items.item_id', '=', 'items.id')
            ->leftJoin('users', 'live_items.user_id', '=', 'users.id')
            ->where('live_items.id', $liveItemId)
            ->select([
                'live_items.*',
                'items.nome_do_produto as item_nome',
                'items.descricao as item_descricao',
                'items.preco as item_price',
                'items.marca',
                'items.tamanho',
                'items.cor',
                'users.name as user_full_name'
            ])
            ->first();

        if (!$liveItem) {
            return response()->json(['success' => false, 'message' => 'Item não encontrado.'], 404);
        }

        $mediaType = strtoupper(trim((string) $request->input('media_type', 'REELS')));
        $result = $this->instagramService->publishVideo($liveItem, [
            'media_type' => $mediaType,
        ]);

        return response()->json($result);
    }

    /**
     * Endpoint para publicar em lote todas as peças NÃO VENDIDAS no Instagram Reels
     */
    public function batchUploadUnsold(Request $request, $liveId)
    {
        $live = Live::findOrFail($liveId);
        $account = SocialChannelAccount::getActiveAccount('instagram');

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Conecte a conta do Instagram antes de publicar em lote.'
            ], 400);
        }

        // Buscar peças com corte de vídeo pronto e ainda NÃO vendidas
        $items = DB::table('live_items')
            ->join('items', 'live_items.item_id', '=', 'items.id')
            ->leftJoin('users', 'live_items.user_id', '=', 'users.id')
            ->where('live_items.live_id', $liveId)
            ->where(function ($q) {
                $q->whereNull('live_items.user_id')
                  ->where(function ($sub) {
                      $sub->whereNull('live_items.buyer_name')
                          ->orWhere('live_items.buyer_name', '')
                          ->orWhere('live_items.buyer_name', '0');
                  });
            })
            ->where(function ($q) {
                $q->whereNotNull('live_items.video_cut_path')
                  ->orWhere('live_items.video_cut_status', 'recorded');
            })
            ->where(function ($q) {
                $q->whereNull('live_items.instagram_media_id')
                  ->orWhere('live_items.instagram_status', 'error');
            })
            ->select([
                'live_items.*',
                'items.nome_do_produto as item_nome',
                'items.descricao as item_descricao',
                'items.preco as item_price',
                'items.marca',
                'items.tamanho',
                'items.cor',
                'users.name as user_full_name'
            ])
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Nenhuma peça pendente de postagem para o Instagram encontrada nesta live.',
                'uploaded_count' => 0,
            ]);
        }

        $successCount = 0;
        $failCount = 0;
        $errors = [];

        foreach ($items as $item) {
            $res = $this->instagramService->publishVideo($item);
            if ($res['success']) {
                $successCount++;
            } else {
                $failCount++;
                $code = $item->codigo_live ?: $item->item_id;
                $errors[] = "Peça #{$code}: " . ($res['message'] ?? 'Erro desconhecido');
            }
        }

        return response()->json([
            'success' => $successCount > 0,
            'message' => "Publicação em lote finalizada: {$successCount} publicadas, {$failCount} com erro.",
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'errors' => $errors,
        ]);
    }

    /**
     * Retorna o status de conexão da conta do Instagram
     */
    public function status(Request $request, $liveId = null)
    {
        $account = SocialChannelAccount::getActiveAccount('instagram');

        $counts = [
            'total' => 0,
            'published' => 0,
            'pending' => 0,
            'error' => 0,
        ];

        if ($liveId) {
            $query = DB::table('live_items')->where('live_id', $liveId);
            $counts['total'] = (clone $query)->whereNotNull('video_cut_path')->count();
            $counts['published'] = (clone $query)->where('instagram_status', 'published')->count();
            $counts['error'] = (clone $query)->where('instagram_status', 'error')->count();
            $counts['pending'] = max(0, $counts['total'] - $counts['published'] - $counts['error']);
        }

        return response()->json([
            'is_connected' => !empty($account),
            'account_name' => $account->channel_name ?? null,
            'channel_avatar' => $account->channel_avatar ?? null,
            'counts' => $counts,
        ]);
    }
}

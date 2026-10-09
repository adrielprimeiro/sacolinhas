<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SocialChannelAccount;
use App\Models\Live;
use App\Services\TikTok\TikTokService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TikTokIntegrationController extends Controller
{
    protected TikTokService $tiktokService;

    public function __construct(TikTokService $tiktokService)
    {
        $this->tiktokService = $tiktokService;
    }

    /**
     * Inicia a conexão OAuth com a conta do TikTok
     */
    public function connect(Request $request)
    {
        if (!$this->tiktokService->isConfigured()) {
            return back()->with('error', 'Credenciais do TikTok (TIKTOK_CLIENT_KEY e TIKTOK_CLIENT_SECRET) não configuradas.');
        }

        $authUrl = $this->tiktokService->getAuthUrl();
        return redirect()->away($authUrl);
    }

    /**
     * Callback do OAuth do TikTok
     */
    public function callback(Request $request)
    {
        $code = $request->query('code');
        $error = $request->query('error');
        $errorDesc = $request->query('error_description');

        if ($error) {
            return redirect()->route('admin.lives.cortes.latest')->with('error', "Erro na autorização do TikTok: {$error} - {$errorDesc}");
        }

        if (!$code) {
            return redirect()->route('admin.lives.cortes.latest')->with('error', 'Código de autorização não recebido do TikTok.');
        }

        $result = $this->tiktokService->handleCallback($code);

        if ($result['success']) {
            return redirect()->route('admin.lives.cortes.latest')->with('success', $result['message']);
        }

        return redirect()->route('admin.lives.cortes.latest')->with('error', $result['message']);
    }

    /**
     * Desconecta a conta do TikTok
     */
    public function disconnect(Request $request)
    {
        SocialChannelAccount::where('provider', 'tiktok')->update(['is_active' => false]);
        return back()->with('success', 'Conta do TikTok desconectada com sucesso.');
    }

    /**
     * Endpoint para publicar o corte de um item individual no TikTok
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

        $privacy = $request->input('privacy', 'PUBLIC_TO_EVERYONE');
        $result = $this->tiktokService->publishVideo($liveItem, ['privacy' => $privacy]);

        return response()->json($result);
    }

    /**
     * Endpoint para publicar em lote todas as peças NÃO VENDIDAS no TikTok
     */
    public function batchUploadUnsold(Request $request, $liveId)
    {
        $live = Live::findOrFail($liveId);
        $account = SocialChannelAccount::getActiveAccount('tiktok');

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Conecte a conta do TikTok antes de publicar em lote.'
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
                $q->whereNull('live_items.tiktok_publish_id')
                  ->orWhere('live_items.tiktok_status', 'error');
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
            ->orderByRaw('CAST(live_items.codigo_live AS UNSIGNED) ASC')
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Nenhuma peça não-vendida pendente de envio para o TikTok nesta live.',
                'count' => 0
            ]);
        }

        $results = [
            'total' => $items->count(),
            'published' => 0,
            'errors' => 0,
            'details' => []
        ];

        foreach ($items as $item) {
            $res = $this->tiktokService->publishVideo($item);
            if ($res['success']) {
                $results['published']++;
            } else {
                $results['errors']++;
            }
            $results['details'][] = [
                'id' => $item->id,
                'codigo' => $item->codigo_live,
                'success' => $res['success'],
                'message' => $res['message']
            ];
            // Pequeno sleep para respeitar rate limits da API do TikTok
            usleep(500000); // 0.5s
        }

        return response()->json([
            'success' => true,
            'message' => "Processamento concluído: {$results['published']} vídeos enviados para o TikTok com sucesso! ({$results['errors']} erros)",
            'results' => $results
        ]);
    }

    /**
     * Retorna status da conta e contadores de publicação da live
     */
    public function status(Request $request, $liveId = null)
    {
        $account = SocialChannelAccount::getActiveAccount('tiktok');
        $isConfigured = $this->tiktokService->isConfigured();

        $stats = [
            'connected' => !empty($account),
            'configured' => $isConfigured,
            'account' => $account ? [
                'name' => $account->channel_name,
                'avatar' => $account->channel_avatar,
                'connected_at' => $account->created_at?->format('d/m/Y H:i'),
            ] : null,
            'published_count' => 0,
            'pending_count' => 0,
        ];

        if ($liveId) {
            $stats['published_count'] = DB::table('live_items')
                ->where('live_id', $liveId)
                ->where('tiktok_status', 'published')
                ->count();

            $stats['pending_count'] = DB::table('live_items')
                ->where('live_id', $liveId)
                ->whereNotNull('video_cut_path')
                ->where(function ($q) {
                    $q->whereNull('tiktok_status')
                      ->orWhere('tiktok_status', 'none')
                      ->orWhere('tiktok_status', 'error');
                })
                ->count();
        }

        return response()->json($stats);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SocialChannelAccount;
use App\Models\Live;
use App\Services\YouTube\YouTubeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class YouTubeIntegrationController extends Controller
{
    protected YouTubeService $youtubeService;

    public function __construct(YouTubeService $youtubeService)
    {
        $this->youtubeService = $youtubeService;
    }

    /**
     * Inicia a conexão OAuth com a conta do Google / Canal do YouTube
     */
    public function connect(Request $request)
    {
        if (!$this->youtubeService->isConfigured()) {
            return back()->with('error', 'Credenciais do YouTube (YOUTUBE_CLIENT_ID e YOUTUBE_CLIENT_SECRET) não configuradas no .env.');
        }

        $authUrl = $this->youtubeService->getAuthUrl();
        return redirect()->away($authUrl);
    }

    /**
     * Callback do OAuth do Google
     */
    public function callback(Request $request)
    {
        $code = $request->query('code');
        $error = $request->query('error');

        if ($error) {
            return redirect()->route('admin.lives.cortes.latest')->with('error', "Erro na autorização do YouTube: {$error}");
        }

        if (!$code) {
            return redirect()->route('admin.lives.cortes.latest')->with('error', 'Código de autorização não recebido do Google.');
        }

        $result = $this->youtubeService->handleCallback($code);

        if ($result['success']) {
            return redirect()->route('admin.lives.cortes.latest')->with('success', $result['message']);
        }

        return redirect()->route('admin.lives.cortes.latest')->with('error', $result['message']);
    }

    /**
     * Desconecta a conta do YouTube
     */
    public function disconnect(Request $request)
    {
        SocialChannelAccount::where('provider', 'youtube')->update(['is_active' => false]);
        return back()->with('success', 'Canal do YouTube desconectado com sucesso.');
    }

    /**
     * Endpoint para publicar o corte de um item individual no YouTube Shorts
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

        $privacy = $request->input('privacy', 'public');
        $result = $this->youtubeService->uploadShort($liveItem, ['privacy' => $privacy]);

        return response()->json($result);
    }

    /**
     * Endpoint para publicar em lote todas as peças NÃO VENDIDAS no YouTube Shorts
     */
    public function batchUploadUnsold(Request $request, $liveId)
    {
        $live = Live::findOrFail($liveId);
        $account = SocialChannelAccount::getActiveAccount('youtube');

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Conecte o canal do YouTube antes de publicar em lote.'
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
                $q->whereNull('live_items.youtube_video_id')
                  ->orWhere('live_items.youtube_status', 'error');
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
                'success' => false,
                'message' => 'Nenhuma peça não-vendida pendente de publicação no YouTube Shorts.'
            ]);
        }

        // Dispara comando de background para respeitar limites do YouTube
        $artisan = base_path('artisan');
        $cmd = sprintf('nohup php %s app:publish-youtube-shorts --live_id=%d > /dev/null 2>&1 &', escapeshellarg($artisan), $liveId);
        exec($cmd);

        return response()->json([
            'success' => true,
            'is_async' => true,
            'count' => $items->count(),
            'message' => "Publicação de {$items->count()} peças no YouTube Shorts iniciada em segundo plano!"
        ]);
    }

    /**
     * Retorna o status da conta conectada e estatísticas de publicação
     */
    public function status(Request $request, $liveId = null)
    {
        $account = SocialChannelAccount::getActiveAccount('youtube');
        $isConfigured = $this->youtubeService->isConfigured();

        $stats = [
            'connected' => $account !== null,
            'is_configured' => $isConfigured,
            'channel_name' => $account ? $account->channel_name : null,
            'channel_avatar' => $account ? $account->channel_avatar : null,
            'published_count' => 0,
            'sold_updated_count' => 0,
        ];

        if ($liveId) {
            $stats['published_count'] = DB::table('live_items')
                ->where('live_id', $liveId)
                ->whereNotNull('youtube_video_id')
                ->count();

            $stats['sold_updated_count'] = DB::table('live_items')
                ->where('live_id', $liveId)
                ->where('youtube_status', 'sold_updated')
                ->count();
        }

        return response()->json($stats);
    }
}

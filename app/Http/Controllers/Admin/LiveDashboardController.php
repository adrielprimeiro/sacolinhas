<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Live;
use App\Models\Sacolinhas;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LiveDashboardController extends Controller
{
    /**
     * Exibe o Dashboard Completo e Detalhado das Lives
     */
    public function index(Request $request)
    {
        // Regra 6: Exclusivo para Minha Mania (Matriz)
        if (auth()->check() && method_exists(auth()->user(), 'isBrechoParceiro') && auth()->user()->isBrechoParceiro()) {
            abort(403, 'Acesso restrito à matriz Minha Mania.');
        }

        $brechoId = (auth()->check() && !empty(auth()->user()->brecho_id)) ? auth()->user()->brecho_id : 1;

        // 1. Lista de todas as lives disponíveis para o seletor (ordenadas da mais recente para a mais antiga)
        $livesList = Live::query()
            ->when($brechoId > 1, fn($q) => $q->where('brecho_id', $brechoId))
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get();

        if ($livesList->isEmpty()) {
            return view('admin.lives.dashboard', [
                'livesList' => collect(),
                'selectedLive' => null,
                'kpis' => null,
                'topBuyers' => collect(),
                'baggedItems' => collect(),
                'recentLivesComparison' => collect(),
                'channelStats' => null,
                'statusCounts' => [],
            ]);
        }

        // 2. Determinar a Live Selecionada
        $requestedLiveId = $request->query('live_id');
        if ($requestedLiveId) {
            $selectedLive = $livesList->firstWhere('id', (int)$requestedLiveId) ?: Live::find($requestedLiveId);
        } else {
            // Prioriza a live ativa no momento; se não houver, pega a mais recente
            $selectedLive = $livesList->firstWhere('ativo', true) ?: $livesList->first();
        }

        $liveId = $selectedLive ? $selectedLive->id : null;

        // 3. Cálculos e Métricas da Live Selecionada
        $kpis = [
            'faturamento' => 0.0,
            'total_pecas_sacoladas' => 0,
            'total_compradoras' => 0,
            'ticket_medio' => 0.0,
            'pecas_por_cliente' => 0.0,
            'preco_medio_peca' => 0.0,
            'total_itens_apresentados' => 0,
            'taxa_conversao_itens' => 0.0,
            'total_mensagens' => 0,
            'mensagens_instagram' => 0,
            'mensagens_tiktok' => 0,
            'total_participantes_chat' => 0,
            'duracao_formatada' => 'Em andamento',
        ];

        $topBuyers = collect();
        $baggedItems = collect();
        $statusCounts = [];
        $channelStats = [
            'instagram_messages' => 0,
            'tiktok_messages' => 0,
            'instagram_share' => 50,
            'tiktok_share' => 50,
        ];

        if ($selectedLive) {
            // Duração da Live
            if ($selectedLive->created_at) {
                $fim = $selectedLive->encerrada_em ?: ($selectedLive->ativo ? now() : $selectedLive->updated_at);
                $minutos = $selectedLive->created_at->diffInMinutes($fim);
                $horas = floor($minutos / 60);
                $restoMin = $minutos % 60;
                $kpis['duracao_formatada'] = $horas > 0 ? "{$horas}h {$restoMin}m" : "{$minutos}m";
                if ($selectedLive->ativo) {
                    $kpis['duracao_formatada'] .= ' (ao vivo)';
                }
            }

            // Totais de Sacolinhas da Live
            $sacolinhasTotals = DB::table('sacolinhas')
                ->where('live_id', $liveId)
                ->where('status', '!=', 'cancelada')
                ->selectRaw('COUNT(id) as total_rows, COALESCE(SUM(quantity), 0) as total_pecas, COALESCE(SUM(price * quantity), 0) as total_valor, COUNT(DISTINCT user_id) as total_compradoras')
                ->first();

            $faturamento = (float)($sacolinhasTotals->total_valor ?? 0);
            $totalPecas = (int)($sacolinhasTotals->total_pecas ?? 0);
            $totalCompradoras = (int)($sacolinhasTotals->total_compradoras ?? 0);

            $kpis['faturamento'] = $faturamento;
            $kpis['total_pecas_sacoladas'] = $totalPecas;
            $kpis['total_compradoras'] = $totalCompradoras;
            $kpis['ticket_medio'] = $totalCompradoras > 0 ? ($faturamento / $totalCompradoras) : 0.0;
            $kpis['pecas_por_cliente'] = $totalCompradoras > 0 ? round($totalPecas / $totalCompradoras, 1) : 0.0;
            $kpis['preco_medio_peca'] = $totalPecas > 0 ? ($faturamento / $totalPecas) : 0.0;

            // Total de itens apresentados (bipados) na live via live_items
            $totalApresentados = 0;
            if (Schema::hasTable('live_items')) {
                $totalApresentados = (int) DB::table('live_items')->where('live_id', $liveId)->count();
            }
            $kpis['total_itens_apresentados'] = $totalApresentados;
            $kpis['taxa_conversao_itens'] = $totalApresentados > 0 ? round(($totalPecas / $totalApresentados) * 100, 1) : 0.0;

            // Mensagens e Participantes no Chat
            if (Schema::hasTable('live_messages')) {
                $msgStats = DB::table('live_messages')
                    ->where('live_id', $liveId)
                    ->selectRaw('
                        COUNT(id) as total_msgs,
                        SUM(CASE WHEN plataforma = "instagram" THEN 1 ELSE 0 END) as total_insta,
                        SUM(CASE WHEN plataforma = "tiktok" THEN 1 ELSE 0 END) as total_tiktok,
                        COUNT(DISTINCT username) as total_participantes
                    ')
                    ->first();

                $totalMsgs = (int)($msgStats->total_msgs ?? 0);
                $instaMsgs = (int)($msgStats->total_insta ?? 0);
                $tiktokMsgs = (int)($msgStats->total_tiktok ?? 0);
                $participantes = (int)($msgStats->total_participantes ?? 0);

                $kpis['total_mensagens'] = $totalMsgs;
                $kpis['mensagens_instagram'] = $instaMsgs;
                $kpis['mensagens_tiktok'] = $tiktokMsgs;
                $kpis['total_participantes_chat'] = $participantes;

                $channelStats['instagram_messages'] = $instaMsgs;
                $channelStats['tiktok_messages'] = $tiktokMsgs;
                if ($totalMsgs > 0) {
                    $channelStats['instagram_share'] = round(($instaMsgs / $totalMsgs) * 100);
                    $channelStats['tiktok_share'] = round(($tiktokMsgs / $totalMsgs) * 100);
                }
            }

            // Status das Sacolinhas
            $statusCounts = DB::table('sacolinhas')
                ->where('live_id', $liveId)
                ->selectRaw('status, COUNT(id) as count, SUM(price * quantity) as total_val')
                ->groupBy('status')
                ->get()
                ->keyBy('status')
                ->toArray();

            // Top Compradoras da Live
            $topBuyers = DB::table('sacolinhas')
                ->join('users', 'sacolinhas.user_id', '=', 'users.id')
                ->where('sacolinhas.live_id', $liveId)
                ->where('sacolinhas.status', '!=', 'cancelada')
                ->select(
                    'users.id as user_id',
                    'users.name as client_name',
                    'users.instagram',
                    'users.tiktok',
                    'users.whatsapp',
                    'users.phone',
                    'users.telefone_principal',
                    DB::raw('COUNT(sacolinhas.id) as total_items'),
                    DB::raw('SUM(sacolinhas.price * sacolinhas.quantity) as total_spent'),
                    DB::raw('MAX(sacolinhas.created_at) as last_buy_at')
                )
                ->groupBy('users.id', 'users.name', 'users.instagram', 'users.tiktok', 'users.whatsapp', 'users.phone', 'users.telefone_principal')
                ->orderByDesc('total_spent')
                ->limit(15)
                ->get()
                ->map(function ($buyer) {
                    $buyer->contact_phone = $buyer->whatsapp ?: ($buyer->telefone_principal ?: $buyer->phone);
                    $buyer->clean_phone = preg_replace('/\D/', '', $buyer->contact_phone ?? '');
                    return $buyer;
                });

            // Peças Sacoladas na Live
            $baggedItems = DB::table('sacolinhas')
                ->join('items', 'sacolinhas.item_id', '=', 'items.id')
                ->join('users', 'sacolinhas.user_id', '=', 'users.id')
                ->where('sacolinhas.live_id', $liveId)
                ->select(
                    'sacolinhas.id as sacolinha_id',
                    'sacolinhas.status as sacolinha_status',
                    'sacolinhas.quantity',
                    'sacolinhas.price as sacolinha_price',
                    'sacolinhas.created_at as bagged_at',
                    'items.codigo as item_code',
                    'items.nome_do_produto as item_name',
                    'items.marca as item_brand',
                    'items.tamanho as item_size',
                    'users.id as buyer_id',
                    'users.name as buyer_name',
                    'users.instagram as buyer_ig',
                    'users.tiktok as buyer_tt'
                )
                ->orderBy('sacolinhas.id', 'desc')
                ->limit(100)
                ->get();
        }

        // 4. Comparativo Histórico das Últimas 15 Lives
        $recentLiveIds = $livesList->pluck('id')->take(15)->toArray();

        $comparisonData = DB::table('sacolinhas')
            ->whereIn('live_id', $recentLiveIds)
            ->where('status', '!=', 'cancelada')
            ->selectRaw('
                live_id,
                COUNT(id) as pecas_count,
                COALESCE(SUM(price * quantity), 0) as total_val,
                COUNT(DISTINCT user_id) as buyers_count
            ')
            ->groupBy('live_id')
            ->get()
            ->keyBy('live_id');

        $messagesCountByLive = collect();
        if (Schema::hasTable('live_messages')) {
            $messagesCountByLive = DB::table('live_messages')
                ->whereIn('live_id', $recentLiveIds)
                ->selectRaw('live_id, COUNT(id) as total_msgs')
                ->groupBy('live_id')
                ->pluck('total_msgs', 'live_id');
        }

        $itemsPresentedByLive = collect();
        if (Schema::hasTable('live_items')) {
            $itemsPresentedByLive = DB::table('live_items')
                ->whereIn('live_id', $recentLiveIds)
                ->selectRaw('live_id, COUNT(id) as total_items')
                ->groupBy('live_id')
                ->pluck('total_items', 'live_id');
        }

        $recentLivesComparison = $livesList->take(15)->map(function ($l) use ($comparisonData, $messagesCountByLive, $itemsPresentedByLive) {
            $stats = $comparisonData->get($l->id);
            $totalVal = (float)($stats->total_val ?? 0);
            $pecas = (int)($stats->pecas_count ?? 0);
            $buyers = (int)($stats->buyers_count ?? 0);
            $msgs = (int)($messagesCountByLive->get($l->id) ?? 0);
            $presented = (int)($itemsPresentedByLive->get($l->id) ?? 0);

            // Duração
            $duracao = 'N/A';
            if ($l->created_at) {
                $fim = $l->encerrada_em ?: ($l->ativo ? now() : $l->updated_at);
                $minutos = $l->created_at->diffInMinutes($fim);
                $horas = floor($minutos / 60);
                $resto = $minutos % 60;
                $duracao = $horas > 0 ? "{$horas}h {$resto}m" : "{$minutos}m";
            }

            return (object) [
                'id' => $l->id,
                'data' => $l->data ? $l->data->format('d/m/Y') : '',
                'tipo_formatado' => $l->tipo_live_formatado,
                'ativo' => (bool)$l->ativo,
                'duracao' => $duracao,
                'faturamento' => $totalVal,
                'pecas_vendidas' => $pecas,
                'compradoras' => $buyers,
                'ticket_medio' => $buyers > 0 ? ($totalVal / $buyers) : 0,
                'itens_apresentados' => $presented,
                'conversao' => $presented > 0 ? round(($pecas / $presented) * 100, 1) : 0,
                'mensagens' => $msgs,
            ];
        });

        return view('admin.lives.dashboard', [
            'livesList' => $livesList,
            'selectedLive' => $selectedLive,
            'kpis' => $kpis,
            'topBuyers' => $topBuyers,
            'baggedItems' => $baggedItems,
            'recentLivesComparison' => $recentLivesComparison,
            'channelStats' => $channelStats,
            'statusCounts' => $statusCounts,
        ]);
    }
}

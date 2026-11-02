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
     * Exibe o Dashboard Analítico de Lives com Base Mensal e Isolamento de Conversão
     */
    public function index(Request $request)
    {
        // Regra 6: Exclusivo para Minha Mania (Matriz)
        if (auth()->check() && method_exists(auth()->user(), 'isBrechoParceiro') && auth()->user()->isBrechoParceiro()) {
            abort(403, 'Acesso restrito à matriz Minha Mania.');
        }

        $brechoId = (auth()->check() && !empty(auth()->user()->brecho_id)) ? auth()->user()->brecho_id : 1;

        // 1. Obter todos os meses disponíveis com transmissões
        $availableMonthsRaw = DB::table('lives')
            ->when($brechoId > 1, fn($q) => $q->where('brecho_id', $brechoId))
            ->selectRaw('DATE_FORMAT(COALESCE(data, created_at), "%Y-%m") as mes, COUNT(id) as total_lives')
            ->whereNotNull(DB::raw('COALESCE(data, created_at)'))
            ->groupBy('mes')
            ->orderByDesc('mes')
            ->get();

        if ($availableMonthsRaw->isEmpty()) {
            return view('admin.lives.dashboard', [
                'availableMonths' => collect(),
                'selectedMonth' => now()->format('Y-m'),
                'selectedMonthLabel' => 'Nenhum mês disponível',
                'monthKpis' => null,
                'livesMonthlyList' => collect(),
                'selectedLive' => null,
                'kpis' => null,
                'topBuyers' => collect(),
                'baggedItems' => collect(),
                'channelStats' => null,
                'statusCounts' => [],
            ]);
        }

        $availableMonths = $availableMonthsRaw->map(function ($m) {
            $dt = Carbon::createFromFormat('Y-m', $m->mes);
            $nomeMes = ucfirst($dt->translatedFormat('F / Y'));
            return (object) [
                'mes' => $m->mes,
                'label' => "{$nomeMes} ({$m->total_lives} " . ($m->total_lives == 1 ? 'live' : 'lives') . ")",
                'short_label' => $nomeMes,
                'total_lives' => (int) $m->total_lives,
            ];
        });

        // 2. Determinar o Mês Selecionado
        $selectedLive = null;
        $requestedLiveId = $request->query('live_id');
        if ($requestedLiveId) {
            $selectedLive = Live::find($requestedLiveId);
        }

        $selectedMonth = $request->query('mes');
        if (!$selectedMonth) {
            if ($selectedLive && ($selectedLive->data || $selectedLive->created_at)) {
                $dtLive = Carbon::parse($selectedLive->data ?: $selectedLive->created_at);
                $selectedMonth = $dtLive->format('Y-m');
            } else {
                // Seleciona o mês mais recente disponível
                $selectedMonth = $availableMonths->first()->mes ?? now()->format('Y-m');
            }
        }

        $dtSelectedMonth = Carbon::createFromFormat('Y-m', $selectedMonth);
        $selectedMonthLabel = ucfirst($dtSelectedMonth->translatedFormat('F \d\e Y'));

        // 3. Buscar todas as lives do mês selecionado
        $monthLives = Live::query()
            ->when($brechoId > 1, fn($q) => $q->where('brecho_id', $brechoId))
            ->whereRaw('DATE_FORMAT(COALESCE(data, created_at), "%Y-%m") = ?', [$selectedMonth])
            ->orderByDesc('id')
            ->get();

        $monthLiveIds = $monthLives->pluck('id')->toArray();

        // 4. Sacolinhas do mês selecionado
        $monthSacolinhas = collect();
        if (!empty($monthLiveIds)) {
            $monthSacolinhas = DB::table('sacolinhas')
                ->whereIn('live_id', $monthLiveIds)
                ->where('status', '!=', 'cancelada')
                ->select('id', 'live_id', 'user_id', 'item_id', 'quantity', 'price', 'status')
                ->get();
        }

        // 5. Itens Bipados (live_items) por Live no mês
        $itemsBipadosByLive = collect();
        if (Schema::hasTable('live_items') && !empty($monthLiveIds)) {
            $itemsBipadosByLive = DB::table('live_items')
                ->whereIn('live_id', $monthLiveIds)
                ->selectRaw('live_id, COUNT(id) as total_bipados')
                ->groupBy('live_id')
                ->pluck('total_bipados', 'live_id');
        }

        // 6. Mensagens de Chat por Live no mês
        $messagesByLive = collect();
        if (Schema::hasTable('live_messages') && !empty($monthLiveIds)) {
            $messagesByLive = DB::table('live_messages')
                ->whereIn('live_id', $monthLiveIds)
                ->selectRaw('
                    live_id,
                    COUNT(id) as total_msgs,
                    SUM(CASE WHEN plataforma = "instagram" THEN 1 ELSE 0 END) as total_insta,
                    SUM(CASE WHEN plataforma = "tiktok" THEN 1 ELSE 0 END) as total_tiktok,
                    COUNT(DISTINCT username) as total_participantes
                ')
                ->groupBy('live_id')
                ->get()
                ->keyBy('live_id');
        }

        // 7. Mapeamento analítico de cada Live do Mês
        $livesMonthlyList = $monthLives->map(function ($l) use ($monthSacolinhas, $itemsBipadosByLive, $messagesByLive) {
            $liveSacs = $monthSacolinhas->where('live_id', $l->id);
            $totalVal = (float) $liveSacs->sum(fn($s) => $s->price * $s->quantity);
            $pecas = (int) $liveSacs->sum('quantity');
            $buyers = $liveSacs->pluck('user_id')->unique()->filter()->count();
            $bipados = (int) ($itemsBipadosByLive->get($l->id) ?? 0);
            $hasBipagem = ($bipados > 0);

            // REGRA FUNDAMENTAL: Conversão calculada estritamente se houver itens bipados!
            $conversao = $hasBipagem ? round(($pecas / $bipados) * 100, 1) : null;

            $msgRow = $messagesByLive->get($l->id);
            $msgs = (int) ($msgRow->total_msgs ?? 0);
            $instaMsgs = (int) ($msgRow->total_insta ?? 0);
            $tiktokMsgs = (int) ($msgRow->total_tiktok ?? 0);
            $participantes = (int) ($msgRow->total_participantes ?? 0);

            // Duração
            $duracao = 'N/A';
            if ($l->created_at) {
                $fim = $l->encerrada_em ?: ($l->ativo ? now() : $l->updated_at);
                $minutos = $l->created_at->diffInMinutes($fim);
                $horas = floor($minutos / 60);
                $resto = $minutos % 60;
                $duracao = $horas > 0 ? "{$horas}h {$resto}m" : "{$minutos}m";
                if ($l->ativo) {
                    $duracao .= ' (ao vivo)';
                }
            }

            return (object) [
                'id' => $l->id,
                'data' => $l->data ? $l->data->format('d/m/Y') : ($l->created_at ? $l->created_at->format('d/m/Y') : ''),
                'tipo_raw' => $l->tipo_live,
                'tipo_formatado' => $l->tipo_live_formatado,
                'ativo' => (bool) $l->ativo,
                'duracao' => $duracao,
                'faturamento' => $totalVal,
                'pecas_vendidas' => $pecas,
                'compradoras' => $buyers,
                'ticket_medio' => $buyers > 0 ? ($totalVal / $buyers) : 0,
                'has_bipagem' => $hasBipagem,
                'itens_bipados' => $bipados,
                'conversao' => $conversao,
                'mensagens' => $msgs,
                'mensagens_insta' => $instaMsgs,
                'mensagens_tiktok' => $tiktokMsgs,
                'participantes' => $participantes,
            ];
        });

        // 8. Cálculo dos KPIs Consolidados do Mês
        $totalFaturamentoMes = (float) $monthSacolinhas->sum(fn($s) => $s->price * $s->quantity);
        $totalPecasMes = (int) $monthSacolinhas->sum('quantity');
        $compradorasUnicasMes = $monthSacolinhas->pluck('user_id')->unique()->filter()->count();
        $ticketMedioMes = $compradorasUnicasMes > 0 ? ($totalFaturamentoMes / $compradorasUnicasMes) : 0;
        $precoMedioPecaMes = $totalPecasMes > 0 ? ($totalFaturamentoMes / $totalPecasMes) : 0;

        $totalLivesMes = $livesMonthlyList->count();
        $livesComBipagem = $livesMonthlyList->where('has_bipagem', true);
        $livesSemBipagem = $livesMonthlyList->where('has_bipagem', false);

        $totalLivesComBipagemMes = $livesComBipagem->count();
        $totalLivesSemBipagemMes = $livesSemBipagem->count();

        // Conversão Consolidada: ESTRITAMENTE sobre as lives com bipagem
        $totalItensBipadosMes = (int) $livesComBipagem->sum('itens_bipados');
        $totalPecasVendidasBipadasMes = (int) $livesComBipagem->sum('pecas_vendidas');
        $taxaConversaoMesBipadas = $totalItensBipadosMes > 0 
            ? round(($totalPecasVendidasBipadasMes / $totalItensBipadosMes) * 100, 1) 
            : 0.0;

        $totalMsgsMes = (int) $livesMonthlyList->sum('mensagens');
        $totalInstaMsgsMes = (int) $livesMonthlyList->sum('mensagens_insta');
        $totalTiktokMsgsMes = (int) $livesMonthlyList->sum('mensagens_tiktok');

        $monthKpis = [
            'faturamento' => $totalFaturamentoMes,
            'total_pecas' => $totalPecasMes,
            'compradoras_unicas' => $compradorasUnicasMes,
            'ticket_medio' => $ticketMedioMes,
            'preco_medio_peca' => $precoMedioPecaMes,
            'total_lives' => $totalLivesMes,
            'total_lives_com_bipagem' => $totalLivesComBipagemMes,
            'total_lives_sem_bipagem' => $totalLivesSemBipagemMes,
            'total_itens_bipados' => $totalItensBipadosMes,
            'total_pecas_bipadas_vendidas' => $totalPecasVendidasBipadasMes,
            'taxa_conversao_bipadas' => $taxaConversaoMesBipadas,
            'total_mensagens' => $totalMsgsMes,
            'mensagens_instagram' => $totalInstaMsgsMes,
            'mensagens_tiktok' => $totalTiktokMsgsMes,
        ];

        // 9. Seletor de Live Individual para Análise Detalhada
        if (!$selectedLive) {
            // Prioriza live ativa ou a primeira live da lista deste mês
            $selectedLive = $monthLives->firstWhere('ativo', true) ?: $monthLives->first();
        }

        // Se a live selecionada for de outro mês, busca os dados normalmente
        $liveId = $selectedLive ? $selectedLive->id : null;

        $kpis = [
            'faturamento' => 0.0,
            'total_pecas_sacoladas' => 0,
            'total_compradoras' => 0,
            'ticket_medio' => 0.0,
            'pecas_por_cliente' => 0.0,
            'preco_medio_peca' => 0.0,
            'has_bipagem' => false,
            'total_itens_apresentados' => 0,
            'taxa_conversao_itens' => null,
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
            $kpis['has_bipagem'] = ($totalApresentados > 0);
            // Se NÃO tem bipagem, conversão é NULL (não contaminar nem mostrar 0%)
            $kpis['taxa_conversao_itens'] = ($totalApresentados > 0) ? round(($totalPecas / $totalApresentados) * 100, 1) : null;

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

        return view('admin.lives.dashboard', [
            'availableMonths' => $availableMonths,
            'selectedMonth' => $selectedMonth,
            'selectedMonthLabel' => $selectedMonthLabel,
            'monthKpis' => $monthKpis,
            'livesMonthlyList' => $livesMonthlyList,
            'monthLives' => $monthLives,
            'selectedLive' => $selectedLive,
            'kpis' => $kpis,
            'topBuyers' => $topBuyers,
            'baggedItems' => $baggedItems,
            'channelStats' => $channelStats,
            'statusCounts' => $statusCounts,
        ]);
    }
}

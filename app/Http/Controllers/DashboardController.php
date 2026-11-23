<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Cliente;
use App\Models\Sacolinhas; // Importe o modelo Sacolinha
use Illuminate\Support\Facades\DB; // Importe o facade DB
use Illuminate\Support\Facades\Log; // Importe o facade Log
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $isParceiro = auth()->check() && auth()->user()->isBrechoParceiro();
            $brechoId = $isParceiro ? auth()->user()->brecho_id : null;

            // 1. Informações do estoque
            $itensEstoqueQuery = Item::query();
            if ($isParceiro) {
                $itensEstoqueQuery->where('brecho_id', $brechoId)->whereIn('status', ['estoque', 'disponivel']);
            } else {
                $itensEstoqueQuery->where('status', 'estoque')
                    ->where(function($q) {
                        $q->where('brecho_id', 1)->orWhereNull('brecho_id');
                    });
            }
            $itensEstoque = $itensEstoqueQuery->get();
            
            $estoqueInfo = [
                'quantidade' => $itensEstoque->count(),
                'valor_total' => (float) $itensEstoque->sum('preco'),
                'valor_medio' => $itensEstoque->count() > 0 ? 
                    round($itensEstoque->sum('preco') / $itensEstoque->count(), 2) : 0
            ];

            // 2. Locais Físicos do Estoque (Agrupados por localizacao)
            $locaisQuery = DB::table('items')
                ->whereNotNull('localizacao')
                ->where('localizacao', '!=', '');

            if ($brechoId) {
                $locaisQuery->where('brecho_id', $brechoId);
            } else {
                $locaisQuery->where(function($q) {
                    $q->where('brecho_id', 1)->orWhereNull('brecho_id');
                });
            }

            $locaisEstoque = $locaisQuery->select(
                    'localizacao',
                    DB::raw('COUNT(*) as qtd_pecas'),
                    DB::raw('SUM(preco) as valor_total_venda')
                )
                ->groupBy('localizacao')
                ->orderBy('localizacao', 'asc')
                ->get();

            $semLocalizacaoQuery = Item::where(function($q) {
                $q->whereNull('localizacao')->orWhere('localizacao', '');
            });
            if ($brechoId) {
                $semLocalizacaoQuery->where('brecho_id', $brechoId);
            } else {
                $semLocalizacaoQuery->where(function($q) {
                    $q->where('brecho_id', 1)->orWhereNull('brecho_id');
                });
            }
            $semLocalizacaoCount = $semLocalizacaoQuery->count();

            $estoqueResumoLocais = [
                'locais_cadastrados' => $locaisEstoque->count(),
                'pecas_enderecadas'  => $locaisEstoque->sum('qtd_pecas'),
                'valor_prateleiras'  => $locaisEstoque->sum('valor_total_venda'),
                'sem_localizacao'    => $semLocalizacaoCount,
            ];
            
            // 3. Informações das Sacolas
            $sacolasQuery = Sacolinhas::query()
                ->where('status', '!=', 'pedido')
                ->where(function ($q) {
                    $q->whereNull('obs')
                      ->orWhereRaw("LOWER(obs) NOT LIKE '%ped-%'");
                });

            if ($brechoId) {
                $sacolasQuery->where(function ($q) use ($brechoId) {
                    $q->where('sacolinhas.brecho_id', $brechoId)
                      ->orWhereExists(function ($sub) use ($brechoId) {
                          $sub->select(DB::raw(1))
                              ->from('items')
                              ->whereColumn('items.id', 'sacolinhas.item_id')
                              ->where('items.brecho_id', $brechoId);
                      });
                });
            } else {
                $sacolasQuery->where(function ($q) {
                    $q->where('sacolinhas.brecho_id', 1)
                      ->orWhereNull('sacolinhas.brecho_id');
                });
            }

            $sacolasInfo = [
                'total_sacolas' => (clone $sacolasQuery)->distinct('user_id')->count('user_id'),
                'total_itens'   => (int) (clone $sacolasQuery)->sum('quantity'),
                'valor_total'   => (float) ((clone $sacolasQuery)->selectRaw('COALESCE(SUM(quantity * price), 0) as total')->value('total') ?? 0),
            ];

            // 4. Movimentação do Mês Atual (Entradas por Avaliação e Saídas por Pedidos)
            $inicioMes = Carbon::now()->startOfMonth()->toDateTimeString();
            $fimMes    = Carbon::now()->endOfMonth()->toDateTimeString();

            $entradasMesAvaliacao = 0;
            $entradasMesProprias = 0;
            $entradasMesParcerias = 0;

            if (!$isParceiro) {
                // Entradas de Avaliações Próprias da Mania
                $entradasMesProprias = (int) DB::table('avaliacao_items')
                    ->join('avaliacoes', 'avaliacoes.id', '=', 'avaliacao_items.avaliacao_id')
                    ->whereBetween('avaliacao_items.created_at', [$inicioMes, $fimMes])
                    ->where('avaliacoes.tipo_compra', '!=', 'parceria')
                    ->count();

                // Entradas em Parceria / Consignação
                $entradasMesParcerias = (int) DB::table('avaliacao_items')
                    ->join('avaliacoes', 'avaliacoes.id', '=', 'avaliacao_items.avaliacao_id')
                    ->whereBetween('avaliacao_items.created_at', [$inicioMes, $fimMes])
                    ->where('avaliacoes.tipo_compra', '=', 'parceria')
                    ->count();

                $entradasMesAvaliacao = $entradasMesProprias + $entradasMesParcerias;
            }

            if ($entradasMesAvaliacao === 0) {
                $itensMesQuery = Item::whereBetween('created_at', [$inicioMes, $fimMes]);
                if ($brechoId) {
                    $itensMesQuery->where('brecho_id', $brechoId);
                } else {
                    $itensMesQuery->where(function($q) {
                        $q->where('brecho_id', 1)->orWhereNull('brecho_id');
                    });
                }
                $entradasMesProprias = (int) $itensMesQuery->count();
                $entradasMesAvaliacao = $entradasMesProprias;
            }

            // Vendas totais do mês
            $itensVendidosMesQuery = Item::where('status', 'vendido')
                ->whereBetween('updated_at', [$inicioMes, $fimMes]);
            if ($brechoId) {
                $itensVendidosMesQuery->where('brecho_id', $brechoId);
            } else {
                $itensVendidosMesQuery->where(function($q) {
                    $q->where('brecho_id', 1)->orWhereNull('brecho_id');
                });
            }
            $itensVendidosMes = (int) $itensVendidosMesQuery->count();

            // Vendas de Parceiros vs Próprios da Mania
            $vendasMesParcerias = (int) Item::where('status', 'vendido')
                ->whereBetween('updated_at', [$inicioMes, $fimMes])
                ->where(function($q) {
                    $q->whereNotNull('parceiro_id')->orWhereNotNull('origem_parceiro');
                })
                ->count();

            $itensVendidosMesProprios = max(0, $itensVendidosMes - $vendasMesParcerias);

            $sacolasVendidasQuery = DB::table('sacolinhas')
                ->whereIn('status', ['pedido', 'vendido', 'fechado'])
                ->whereBetween('updated_at', [$inicioMes, $fimMes]);

            if ($brechoId) {
                $sacolasVendidasQuery->where('brecho_id', $brechoId);
            } else {
                $sacolasVendidasQuery->where(function($q) {
                    $q->where('brecho_id', 1)->orWhereNull('brecho_id');
                });
            }

            $sacolasVendidasMes = (int) $sacolasVendidasQuery->sum('quantity');

            // Total de saídas e saídas do estoque próprio
            $saidasMesPedidos = max($itensVendidosMes, $sacolasVendidasMes);
            $saidasMesProprios = max(0, $saidasMesPedidos - $vendasMesParcerias);

            // Diferença foca no Estoque Próprio da Mania (Entradas Próprias - Saídas Próprias)
            $diferencaMes = $entradasMesProprias - $saidasMesProprios;

            // 5. Faturamento por Clientes do Clube vs Outros no Mês Vigente & Pedidos Gerais
            $clubeUserIds = [];
            if (!$isParceiro) {
                $clubeUserIds = DB::table('clube_assinaturas')
                    ->where('status', 'ativa')
                    ->pluck('user_id')
                    ->toArray();
            }

            $pedidosBase = DB::table('pedidos')
                ->whereNotIn('status_pedido', ['cancelado', 'rascunho'])
                ->whereBetween('created_at', [$inicioMes, $fimMes]);

            if ($brechoId) {
                $pedidosBase->where('brecho_id', $brechoId);
            } else {
                $pedidosBase->where(function($q) {
                    $q->where('brecho_id', 1)->orWhereNull('brecho_id');
                });
            }

            $fatClubeMes = !empty($clubeUserIds) ? (float) (clone $pedidosBase)->whereIn('user_id', $clubeUserIds)->sum('valor_total') : 0.0;
            $fatOutrosMes = (float) (clone $pedidosBase)->when(!empty($clubeUserIds), fn($q) => $q->whereNotIn('user_id', $clubeUserIds))->sum('valor_total');
            $fatTotalMes = (float) (clone $pedidosBase)->sum('valor_total');
            $totalPedidosMes = (int) (clone $pedidosBase)->count();
            $pedidosConcluidosMes = (int) (clone $pedidosBase)->whereIn('status_pedido', ['enviado', 'entregue', 'concluido'])->count();

            $pctClube  = $fatTotalMes > 0 ? round(($fatClubeMes / $fatTotalMes) * 100, 1) : 0.0;
            $pctOutros = $fatTotalMes > 0 ? round(($fatOutrosMes / $fatTotalMes) * 100, 1) : 0.0;

            $faturamentoClubeInfo = [
                'fat_clube_mes'      => $fatClubeMes,
                'fat_outros_mes'     => $fatOutrosMes,
                'fat_total_mes'      => $fatTotalMes,
                'pct_clube'          => $pctClube,
                'pct_outros'         => $pctOutros,
                'total_pedidos_mes'  => $totalPedidosMes,
                'pedidos_concluidos' => $pedidosConcluidosMes,
                'ticket_medio'       => $totalPedidosMes > 0 ? round($fatTotalMes / $totalPedidosMes, 2) : 0.0,
            ];

            // 6. Outras estatísticas gerais
            $totalClientes = $isParceiro
                ? DB::table('brecho_clientes')->where('brecho_id', $brechoId)->count()
                : Cliente::where('role', 'client')->count();

            $itensTotalQuery = Item::query();
            $itensDispQuery  = Item::query();
            if ($isParceiro) {
                $itensDispQuery->whereIn('status', ['disponivel', 'estoque']);
            } else {
                $itensDispQuery->where('status', 'disponivel');
            }
            $itensVendQuery  = Item::where('status', 'vendido');
            $itensResQuery   = Item::where('status', 'reservado');

            if ($brechoId) {
                $itensTotalQuery->where('brecho_id', $brechoId);
                $itensDispQuery->where('brecho_id', $brechoId);
                $itensVendQuery->where('brecho_id', $brechoId);
                $itensResQuery->where('brecho_id', $brechoId);
            } else {
                $filterMania = function($q) { $q->where('brecho_id', 1)->orWhereNull('brecho_id'); };
                $itensTotalQuery->where($filterMania);
                $itensDispQuery->where($filterMania);
                $itensVendQuery->where($filterMania);
                $itensResQuery->where($filterMania);
            }

            $estatisticas = [
                'total_clientes'          => $totalClientes,
                'total_itens'             => $itensTotalQuery->count(),
                'itens_disponiveis'       => $itensDispQuery->count(),
                'itens_vendidos'          => $itensVendQuery->count(),
                'itens_reservados'        => $itensResQuery->count(),
                'itens_estoque'           => $estoqueInfo['quantidade'],
                'entradas_mes_avaliacao'  => $entradasMesProprias,
                'entradas_mes_proprias'   => $entradasMesProprias,
                'entradas_mes_parcerias'  => $entradasMesParcerias,
                'vendas_mes_parcerias'    => $vendasMesParcerias,
                'saidas_mes_pedidos'      => $saidasMesProprios,
                'saidas_mes_total'        => $saidasMesPedidos,
                'diferenca_mes'           => $diferencaMes,
                'nome_mes'                => Carbon::now()->locale('pt_BR')->translatedFormat('F/Y'),
            ];

            // 7. Alertas de Vencimento
            // Regra: vence em add_at + 31 dias (vencidas até hoje)
            $hoje = Carbon::today()->toDateString();

            $alertaBase = Sacolinhas::query()
                ->whereNotNull('add_at')
                ->where('status', '!=', 'pedido')
                ->where(function ($q) {
                    $q->whereNull('obs')
                      ->orWhereRaw("LOWER(obs) NOT LIKE '%ped-%'");
                })
                ->whereRaw('DATE(DATE_ADD(add_at, INTERVAL 31 DAY)) <= ?', [$hoje]);

            if ($brechoId) {
                $alertaBase->where(function ($q) use ($brechoId) {
                    $q->where('sacolinhas.brecho_id', $brechoId)
                      ->orWhereExists(function ($sub) use ($brechoId) {
                          $sub->select(DB::raw(1))
                              ->from('items')
                              ->whereColumn('items.id', 'sacolinhas.item_id')
                              ->where('items.brecho_id', $brechoId);
                      });
                });
            } else {
                $alertaBase->where(function ($q) {
                    $q->where('sacolinhas.brecho_id', 1)->orWhereNull('sacolinhas.brecho_id');
                });
            }

            $sacolasVencemHoje = (clone $alertaBase)
                ->distinct('user_id')
                ->count('user_id');

            $itensVencemHoje = (clone $alertaBase)
                ->sum('quantity');

            $valorItensVencemHoje = (clone $alertaBase)
                ->selectRaw('COALESCE(SUM(quantity * price),0) as total')
                ->value('total');

            $alertasVencimento = [
                'sacolas_vencem_hoje'     => (int) $sacolasVencemHoje,
                'itens_vencem_hoje'       => (int) $itensVencemHoje,
                'valor_itens_vencem_hoje' => (float) $valorItensVencemHoje,
            ];

            return view('dashboard', compact(
                'estoqueInfo',
                'estatisticas',
                'sacolasInfo',
                'alertasVencimento',
                'locaisEstoque',
                'estoqueResumoLocais',
                'faturamentoClubeInfo'
            ));
            
        } catch (\Exception $e) {
            // Em caso de erro, log e valores padrão
            Log::error('Erro ao carregar dashboard: ' . $e->getMessage());
            
            $estoqueInfo = [
                'quantidade' => 0,
                'valor_total' => 0,
                'valor_medio' => 0
            ];
            
            $estatisticas = [
                'total_clientes' => 0,
                'total_itens' => 0,
                'itens_disponiveis' => 0,
                'itens_vendidos' => 0,
                'itens_reservados' => 0,
                'itens_estoque' => 0,
            ];

            $sacolasInfo = [
                'total_sacolas' => 0,
                'total_itens' => 0,
                'valor_total' => 0,
            ];
            $alertasVencimento = [
				'sacolas_vencem_hoje' => 0,
				'itens_vencem_hoje' => 0,
				'valor_itens_vencem_hoje' => 0,
			];

			return view('dashboard', compact('estoqueInfo', 'estatisticas', 'sacolasInfo', 'alertasVencimento'));

        }
    }
}
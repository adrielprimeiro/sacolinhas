<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OdsReportController extends Controller
{
    public function index(Request $request)
    {
        // Regra 6: Exclusivo para Minha Mania (Matriz)
        if (auth()->check() && method_exists(auth()->user(), 'isBrechoParceiro') && auth()->user()->isBrechoParceiro()) {
            abort(403, 'Acesso restrito à administração da Matriz (Minha Mania).');
        }

        // 1. Definir intervalo de datas
        $dataInicioInput = $request->input('data_inicio');
        $dataFimInput    = $request->input('data_fim');
        $escopoVendas    = $request->input('escopo_vendas', 'periodo'); // 'periodo' ou 'total'

        if (!$dataInicioInput || !$dataFimInput) {
            // Verifica se há clientes cadastrados nos últimos 30 dias da data atual;
            // caso contrário, usa os últimos 30 dias a partir do último cadastro existente para exibir dados reais de imediato.
            $inizioPadrao = Carbon::now()->startOfMonth()->toDateString();
            $fimPadrao    = Carbon::now()->toDateString();

            $countMesAtual = DB::table('users')
                ->where('role', 'client')
                ->whereRaw('COALESCE(data_cadastro, created_at) >= ?', [$inizioPadrao . ' 00:00:00'])
                ->count();

            if ($countMesAtual === 0) {
                $maxCreatedAt = DB::table('users')
                    ->where('role', 'client')
                    ->selectRaw('MAX(COALESCE(data_cadastro, created_at)) as max_dt')
                    ->value('max_dt');

                if ($maxCreatedAt) {
                    $dtMax = Carbon::parse($maxCreatedAt);
                    $inizioPadrao = $dtMax->copy()->subDays(30)->toDateString();
                    $fimPadrao    = $dtMax->toDateString();
                }
            }

            $dataInicioInput = $dataInicioInput ?: $inizioPadrao;
            $dataFimInput    = $dataFimInput ?: $fimPadrao;
        }

        $startDate = Carbon::parse($dataInicioInput)->startOfDay();
        $endDate   = Carbon::parse($dataFimInput)->endOfDay();

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $dataInicio = $startDate->toDateString();
        $dataFim    = $endDate->toDateString();
        $startStr   = $startDate->toDateTimeString();
        $endStr     = $endDate->toDateTimeString();

        // 2. Buscar Novos Clientes da Minha Mania nesse intervalo de tempo
        $novosClientes = DB::table('users')
            ->where('role', 'client')
            ->where(function ($q) {
                $q->where('is_admin', 0)->orWhereNull('is_admin');
            })
            ->where(function ($q) {
                $q->where('brecho_id', 1)->orWhereNull('brecho_id');
            })
            ->whereBetween(DB::raw('COALESCE(data_cadastro, created_at)'), [$startStr, $endStr])
            ->select(
                'id',
                'name',
                'nome_cliente',
                'apelido',
                'email',
                'whatsapp',
                'phone',
                'instagram',
                'tiktok',
                'cidade',
                'estado',
                DB::raw('COALESCE(data_cadastro, created_at) as data_entrada')
            )
            ->orderByDesc('data_entrada')
            ->orderByDesc('id')
            ->get();

        $newClientIds = $novosClientes->pluck('id')->toArray();
        $totalNovosClientes = count($newClientIds);

        // 3. Calcular Faturamento APENAS desses clientes novos
        $pedidosPorCliente = collect();
        $sacolinhasPorCliente = collect();

        $totalPedidosValor = 0.0;
        $totalPedidosAprovadosValor = 0.0;
        $totalPedidosPendentesValor = 0.0;
        $totalPedidosQtd = 0;

        $totalSacolinhasValor = 0.0;
        $totalSacolinhasPecas = 0;

        if ($totalNovosClientes > 0) {
            // 3.1. Pedidos gerados por esses clientes novos (Matriz: brecho_id = 1 ou NULL)
            $pedidosQuery = DB::table('pedidos')
                ->whereIn('user_id', $newClientIds)
                ->where(function ($q) {
                    $q->where('brecho_id', 1)->orWhereNull('brecho_id');
                })
                ->where('status_pedido', '!=', 'cancelado')
                ->where(function ($q) {
                    $q->whereNull('status_pagamento')
                      ->orWhere('status_pagamento', '!=', 'rejeitado');
                });

            if ($escopoVendas === 'periodo') {
                $pedidosQuery->whereBetween(DB::raw('COALESCE(data_pedido, created_at)'), [$startStr, $endStr]);
            }

            $pedidosRows = $pedidosQuery
                ->select(
                    'id',
                    'user_id',
                    'numero_pedido',
                    'valor_total',
                    'status_pedido',
                    'status_pagamento',
                    DB::raw('COALESCE(data_pedido, created_at) as dt_pedido')
                )
                ->get();

            $totalPedidosQtd = $pedidosRows->count();
            $totalPedidosValor = (float) $pedidosRows->sum('valor_total');
            $totalPedidosAprovadosValor = (float) $pedidosRows->filter(function ($p) {
                return $p->status_pagamento === 'aprovado'
                    || in_array($p->status_pedido, ['pago', 'concluido', 'enviado', 'entregue', 'embalado'], true);
            })->sum('valor_total');
            $totalPedidosPendentesValor = max(0, $totalPedidosValor - $totalPedidosAprovadosValor);

            $pedidosPorCliente = $pedidosRows->groupBy('user_id');

            // 3.2. Sacolinhas ativas (peças arrematadas em Live/Loja ainda não convertidas em pedido)
            $sacolinhasQuery = DB::table('sacolinhas')
                ->whereIn('user_id', $newClientIds)
                ->where(function ($q) {
                    $q->where('brecho_id', 1)->orWhereNull('brecho_id');
                })
                ->whereNotIn('status', ['pedido', 'cancelada'])
                ->where(function ($q) {
                    $q->whereNull('obs')
                      ->orWhereRaw("LOWER(obs) NOT LIKE '%ped-%'");
                });

            if ($escopoVendas === 'periodo') {
                $sacolinhasQuery->whereBetween(DB::raw('COALESCE(add_at, created_at)'), [$startStr, $endStr]);
            }

            $sacolinhasRows = $sacolinhasQuery
                ->select(
                    'user_id',
                    DB::raw('SUM(quantity) as qtd_pecas'),
                    DB::raw('SUM(quantity * price) as valor_sacola')
                )
                ->groupBy('user_id')
                ->get();

            $totalSacolinhasPecas = (int) $sacolinhasRows->sum('qtd_pecas');
            $totalSacolinhasValor = (float) $sacolinhasRows->sum('valor_sacola');
            $sacolinhasPorCliente = $sacolinhasRows->keyBy('user_id');
        }

        // 4. Consolidar dados por cliente novo
        $clientesDetalhados = $novosClientes->map(function ($cliente) use ($pedidosPorCliente, $sacolinhasPorCliente) {
            $cliPedidos = $pedidosPorCliente->get($cliente->id, collect());
            $cliSacola  = $sacolinhasPorCliente->get($cliente->id);

            $qtdPedidos   = $cliPedidos->count();
            $valorPedidos = (float) $cliPedidos->sum('valor_total');
            $qtdSacola    = (int) ($cliSacola->qtd_pecas ?? 0);
            $valorSacola  = (float) ($cliSacola->valor_sacola ?? 0.0);
            $faturamentoTotal = $valorPedidos + $valorSacola;

            $cliente->qtd_pedidos       = $qtdPedidos;
            $cliente->valor_pedidos     = $valorPedidos;
            $cliente->qtd_sacola        = $qtdSacola;
            $cliente->valor_sacola      = $valorSacola;
            $cliente->faturamento_total = $faturamentoTotal;
            $cliente->comprou           = ($faturamentoTotal > 0);

            return $cliente;
        })->sortByDesc('faturamento_total')->values();

        $faturamentoTotalNovosClientes = $totalPedidosValor + $totalSacolinhasValor;
        $novosClientesCompradores = $clientesDetalhados->where('comprou', true)->count();
        $taxaConversaoNovos = $totalNovosClientes > 0
            ? round(($novosClientesCompradores / $totalNovosClientes) * 100, 1)
            : 0.0;
        $ticketMedioPorComprador = $novosClientesCompradores > 0
            ? round($faturamentoTotalNovosClientes / $novosClientesCompradores, 2)
            : 0.0;
        $ticketMedioGeralNovos = $totalNovosClientes > 0
            ? round($faturamentoTotalNovosClientes / $totalNovosClientes, 2)
            : 0.0;

        // Atalhos de datas para os botões rápidos da view
        $hoje = Carbon::now();
        $atalhosPeriodo = [
            'hoje' => [
                'label' => 'Hoje',
                'inicio' => $hoje->toDateString(),
                'fim' => $hoje->toDateString(),
            ],
            '7dias' => [
                'label' => 'Últimos 7 dias',
                'inicio' => $hoje->copy()->subDays(6)->toDateString(),
                'fim' => $hoje->toDateString(),
            ],
            '30dias' => [
                'label' => 'Últimos 30 dias',
                'inicio' => $hoje->copy()->subDays(29)->toDateString(),
                'fim' => $hoje->toDateString(),
            ],
            'mes_atual' => [
                'label' => 'Mês Atual',
                'inicio' => $hoje->copy()->startOfMonth()->toDateString(),
                'fim' => $hoje->copy()->endOfMonth()->toDateString(),
            ],
            'mes_passado' => [
                'label' => 'Mês Passado',
                'inicio' => $hoje->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                'fim' => $hoje->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            '90dias' => [
                'label' => 'Últimos 90 dias',
                'inicio' => $hoje->copy()->subDays(89)->toDateString(),
                'fim' => $hoje->toDateString(),
            ],
            'ano_atual' => [
                'label' => 'Este Ano (' . $hoje->year . ')',
                'inicio' => $hoje->copy()->startOfYear()->toDateString(),
                'fim' => $hoje->toDateString(),
            ],
        ];

        return view('admin.relatorios.ods', compact(
            'dataInicio',
            'dataFim',
            'escopoVendas',
            'totalNovosClientes',
            'novosClientesCompradores',
            'taxaConversaoNovos',
            'faturamentoTotalNovosClientes',
            'totalPedidosValor',
            'totalPedidosAprovadosValor',
            'totalPedidosPendentesValor',
            'totalPedidosQtd',
            'totalSacolinhasValor',
            'totalSacolinhasPecas',
            'ticketMedioPorComprador',
            'ticketMedioGeralNovos',
            'clientesDetalhados',
            'atalhosPeriodo'
        ));
    }
}

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
            // Calcular informações do estoque
            $itensEstoque = Item::where('status', 'estoque')->get();
            
            $estoqueInfo = [
                'quantidade' => $itensEstoque->count(),
                'valor_total' => $itensEstoque->sum('preco'),
                'valor_medio' => $itensEstoque->count() > 0 ? 
                    round($itensEstoque->sum('preco') / $itensEstoque->count(), 2) : 0
            ];
            
            // Calcular informações das Sacolas (NOVAS ESTATÍSTICAS AQUI)
            $sacolasInfo = [
                'total_sacolas' => Sacolinhas::distinct('user_id')->count('user_id'),
                'total_itens' => Sacolinhas::count(),
                'valor_total' => Sacolinhas::sum('price'),
            ];

            // Movimentação do Mês Atual (Entradas por Avaliação e Saídas por Pedidos)
            $inicioMes = Carbon::now()->startOfMonth()->toDateTimeString();
            $fimMes    = Carbon::now()->endOfMonth()->toDateTimeString();

            $entradasMesAvaliacao = (int) DB::table('avaliacao_items')
                ->whereBetween('created_at', [$inicioMes, $fimMes])
                ->count();

            if ($entradasMesAvaliacao === 0) {
                $entradasMesAvaliacao = (int) Item::whereBetween('created_at', [$inicioMes, $fimMes])->count();
            }

            $itensVendidosMes = (int) Item::where('status', 'vendido')
                ->whereBetween('updated_at', [$inicioMes, $fimMes])
                ->count();

            $sacolasVendidasMes = (int) DB::table('sacolinhas')
                ->whereIn('status', ['pedido', 'vendido', 'fechado'])
                ->whereBetween('updated_at', [$inicioMes, $fimMes])
                ->sum('quantity');

            $saidasMesPedidos = max($itensVendidosMes, $sacolasVendidasMes);

            $diferencaMes = $entradasMesAvaliacao - $saidasMesPedidos;

            // Outras estatísticas úteis (opcional)
            $estatisticas = [
                'total_clientes' => Cliente::count(),
                'total_itens' => Item::count(),
                'itens_disponiveis' => Item::where('status', 'disponivel')->count(),
                'itens_vendidos' => Item::where('status', 'vendido')->count(),
                'itens_reservados' => Item::where('status', 'reservado')->count(),
                'itens_estoque' => $estoqueInfo['quantidade'],
                'entradas_mes_avaliacao' => $entradasMesAvaliacao,
                'saidas_mes_pedidos' => $saidasMesPedidos,
                'diferenca_mes' => $diferencaMes,
                'nome_mes' => Carbon::now()->locale('pt_BR')->translatedFormat('F/Y'),
            ];
            
            // Log para debug (remover depois)
            Log::info('Dashboard - Estoque Info:', $estoqueInfo);
            Log::info('Dashboard - Estatísticas:', $estatisticas);
            Log::info('Dashboard - Sacolas Info:', $sacolasInfo);


			// =========================
			// ALERTAS DE VENCIMENTO
			// Regra: vence em add_at + 90 dias
			// =========================
			$hoje = Carbon::today()->toDateString();
			$em3Dias = Carbon::today()->addDays(0)->toDateString();

			$alertaBase = Sacolinhas::query()
				->whereNotNull('add_at');

			// 1) Sacolinhas com vencimento hoje (add_at + 90 = hoje)
			$sacolasVencemHoje = (clone $alertaBase)
				->whereRaw('DATE(DATE_ADD(add_at, INTERVAL 90 DAY)) = ?', [$hoje])
				->distinct('user_id')
				->count('user_id');


			// 3) Número de itens vencendo hoje (somatório de quantity)
			$itensVencemHoje = (clone $alertaBase)
				->whereRaw('DATE(DATE_ADD(add_at, INTERVAL 90 DAY)) = ?', [$hoje])
				->sum('quantity');

			// 4) Valor dos itens vencendo hoje (somatório de quantity * price)
			$valorItensVencemHoje = (clone $alertaBase)
				->whereRaw('DATE(DATE_ADD(add_at, INTERVAL 90 DAY)) = ?', [$hoje])
				->selectRaw('COALESCE(SUM(quantity * price),0) as total')
				->value('total');

			$alertasVencimento = [
				'sacolas_vencem_hoje' => (int) $sacolasVencemHoje,
				'itens_vencem_hoje' => (int) $itensVencemHoje,
				'valor_itens_vencem_hoje' => (float) $valorItensVencemHoje,
			];


            return view('dashboard', compact('estoqueInfo', 'estatisticas', 'sacolasInfo', 'alertasVencimento'));
            
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
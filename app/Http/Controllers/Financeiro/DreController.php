<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\ClassificacaoFinanceira;
use App\Models\Lancamento;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DreController extends Controller
{
    public function index(Request $request)
    {
        $periodo = $request->filled('periodo')
            ? Carbon::createFromFormat('Y-m', $request->periodo)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $inicio = $periodo->copy()->startOfMonth();
        $fim    = $periodo->copy()->endOfMonth();

        // 1. RECEITA BRUTA
        // Faturamento comercial vindo de pedidos
        $receitaVendas = (float) Pedido::where('status_pedido', '!=', 'cancelado')
            ->whereBetween('data_pedido', [$inicio->toDateTimeString(), $fim->toDateTimeString()])
            ->sum('valor_total');

        // Outras receitas operacionais (lançamentos que não são pedidos e não são recargas de carteira)
        $outrasReceitas = (float) Lancamento::where('tipo', 'receita')
            ->where('referencia_tipo', '!=', 'pedido')
            ->whereNotIn('classificacao_financeira_id', [84]) // Ignora recargas de carteira
            ->whereDoesntHave('classificacaoFinanceira', function ($q) {
                $q->where('nome', 'Transferência entre Contas');
            })
            ->whereBetween('data_emissao', [$inicio->toDateString(), $fim->toDateString()])
            ->sum('valor_total');

        $receitaBruta = $receitaVendas + $outrasReceitas;

        // 2. DEDUÇÕES DA RECEITA BRUTA
        // Descontos aplicados nos pedidos
        $descontosConcedidos = (float) Pedido::where('status_pedido', '!=', 'cancelado')
            ->whereBetween('data_pedido', [$inicio->toDateTimeString(), $fim->toDateTimeString()])
            ->sum('valor_desconto');

        // Devoluções de Vendas (lançamentos classificados sob a categoria 81)
        $devolucoesVendas = (float) Lancamento::where('tipo', 'despesa')
            ->where('classificacao_financeira_id', 81)
            ->whereBetween('data_emissao', [$inicio->toDateString(), $fim->toDateString()])
            ->sum('valor_total');

        $totalDeducoes = $descontosConcedidos + $devolucoesVendas;

        // 3. RECEITA LÍQUIDA
        $receitaLiquida = $receitaBruta - $totalDeducoes;

        // 4. CUSTO DAS MERCADORIAS VENDIDAS (CMV)
        // Calculado somando o custo individual dos itens vendidos no período
        $cmv = (float) DB::table('pedidos')
            ->join('items_pedido', 'pedidos.id', '=', 'items_pedido.pedido_id')
            ->join('items', 'items_pedido.item_id', '=', 'items.id')
            ->where('pedidos.status_pedido', '!=', 'cancelado')
            ->where('items_pedido.status_item', '!=', 'devolvido')
            ->whereBetween('pedidos.data_pedido', [$inicio->toDateTimeString(), $fim->toDateTimeString()])
            ->sum(DB::raw('items_pedido.quantidade * COALESCE(items.custo, 0)'));

        // 5. LUCRO BRUTO
        $lucroBruto = $receitaLiquida - $cmv;

        // 6. DESPESAS OPERACIONAIS (Exclui Custo de Estoque/Fornecedor [19] e Devoluções [81])
        $lancamentosDespesas = Lancamento::with('classificacaoFinanceira')
            ->where('tipo', 'despesa')
            ->whereNotIn('classificacao_financeira_id', [19, 81])
            ->whereDoesntHave('classificacaoFinanceira', function ($q) {
                $q->where('nome', 'Transferência entre Contas');
            })
            ->whereBetween('data_emissao', [$inicio->toDateString(), $fim->toDateString()])
            ->get();

        // Agrupar despesas pelas categorias pai de nível 1
        $despesasPorCategoria = [];
        $totalDespesasOperacionais = 0.0;

        foreach ($lancamentosDespesas as $l) {
            $class = $l->classificacaoFinanceira;
            if ($class) {
                // Encontrar o pai de nível 1 (ex: código_contabil como 2.04, 2.05, etc.)
                $parentName = $this->getNomeCategoriaNivelUm($class);
                if (!isset($despesasPorCategoria[$parentName])) {
                    $despesasPorCategoria[$parentName] = 0.0;
                }
                $despesasPorCategoria[$parentName] += (float) $l->valor_total;
                $totalDespesasOperacionais += (float) $l->valor_total;
            }
        }

        // Ordenar as despesas por valor decrescente
        arsort($despesasPorCategoria);

        // 7. LUCRO LÍQUIDO DO EXERCÍCIO (LLE)
        $lucroLiquido = $lucroBruto - $totalDespesasOperacionais;

        // Indicadores percentuais
        $margemBrutaPercentual = $receitaLiquida > 0 ? round(($lucroBruto / $receitaLiquida) * 100, 1) : 0;
        $margemLiquidaPercentual = $receitaLiquida > 0 ? round(($lucroLiquido / $receitaLiquida) * 100, 1) : 0;

        return view('admin.financeiro.dre', compact(
            'periodo',
            'receitaVendas',
            'outrasReceitas',
            'receitaBruta',
            'descontosConcedidos',
            'devolucoesVendas',
            'totalDeducoes',
            'receitaLiquida',
            'cmv',
            'lucroBruto',
            'despesasPorCategoria',
            'totalDespesasOperacionais',
            'lucroLiquido',
            'margemBrutaPercentual',
            'margemLiquidaPercentual'
        ));
    }

    /**
     * Retorna o nome da categoria pai de primeiro nível (ex: 2.04 Administrativo)
     */
    private function getNomeCategoriaNivelUm(ClassificacaoFinanceira $class)
    {
        $codigo = $class->codigo_contabil;
        $partes = explode('.', $codigo);

        // Se tiver mais de 2 níveis (ex: 2.04.01), busca o pai do código 2.XX
        if (count($partes) > 2) {
            $codigoPai = $partes[0] . '.' . $partes[1];
            $pai = ClassificacaoFinanceira::where('codigo_contabil', $codigoPai)->first();
            return $pai ? $pai->nome : 'Outras Despesas Operacionais';
        }

        return $class->nome;
    }
}

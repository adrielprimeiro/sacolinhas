<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\ContaCorrente;
use App\Models\Movimentacao;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RelatorioGerencialController extends Controller
{
    public function index(Request $request)
    {
        $periodo = $request->filled('periodo')
            ? Carbon::createFromFormat('Y-m', $request->periodo)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $inicio = $periodo->copy()->startOfMonth();
        $fim    = $periodo->copy()->endOfMonth();

        // 1. FATURAMENTO COMERCIAL (Pedidos Fechados)
        $pedidosQuery = Pedido::where('status_pedido', '!=', 'cancelado')
            ->whereBetween('data_pedido', [$inicio->toDateTimeString(), $fim->toDateTimeString()]);

        $pedidosCount = $pedidosQuery->count();
        $faturamentoBruto = (float) $pedidosQuery->sum('valor_total');
        $saldoUtilizado = (float) $pedidosQuery->sum('valor_saldo_utilizado');
        
        // Liquidados diretamente (Pix/Dinheiro novo no dia do pedido)
        $liquidoDireto = $faturamentoBruto - $saldoUtilizado;

        // 2. DETALHAMENTO DA ORIGEM DE CRÉDITOS NA CARTEIRA (Entradas na Carteira)
        // Aportes de Caixa / Recargas (Dinheiro Real)
        $creditosAporte = (float) ContaCorrente::where('tipo_movimentacao', 'credito')
            ->where('referencia_tipo', 'movimentacao')
            ->whereBetween('data_movimentacao', [$inicio->toDateString(), $fim->toDateString()])
            ->sum('valor');

        // Créditos por Avaliação de Desapego
        $creditosAvaliacao = (float) ContaCorrente::where('tipo_movimentacao', 'credito')
            ->where('referencia_tipo', 'carteira_credito')
            ->where(function($q) {
                $q->where('descricao', 'like', '%avalia%')
                  ->orWhere('classificacao_id', 19);
            })
            ->whereBetween('data_movimentacao', [$inicio->toDateString(), $fim->toDateString()])
            ->sum('valor');

        // Créditos por Devoluções de Vendas / Ajustes
        $creditosDevolucao = (float) ContaCorrente::where('tipo_movimentacao', 'credito')
            ->where(function($q) {
                $q->where('classificacao_id', 81)
                  ->orWhere(function($sub) {
                      $sub->where('referencia_tipo', 'carteira_credito')
                          ->where('classificacao_id', '!=', 19)
                          ->where('descricao', 'not like', '%avalia%');
                  });
            })
            ->whereBetween('data_movimentacao', [$inicio->toDateString(), $fim->toDateString()])
            ->sum('valor');

        $totalCreditosGerados = $creditosAporte + $creditosAvaliacao + $creditosDevolucao;

        $faturamentoLiquido = $faturamentoBruto - $creditosDevolucao;

        // 3. COMPRAS E GASTOS COM ESTOQUE (Investimento)
        // Compras em dinheiro real para Fornecedores (Banco Inter/Mercado Pago para Categoria 19)
        $custoFornecedorReal = (float) Movimentacao::whereHas('lancamento', function($q) {
                $q->where('tipo', 'despesa')
                  ->where('classificacao_financeira_id', 19);
            })
            ->whereHas('contaBancaria', function($q) {
                $q->where('nome', 'not like', '%carteira%');
            })
            ->whereBetween('data_pagamento', [$inicio->toDateString(), $fim->toDateString()])
            ->sum('valor_pago');

        // O custo virtual do desapego é o próprio valor do crédito de avaliação gerado
        $custoDesapegoVirtual = $creditosAvaliacao;

        $investimentoTotalEstoque = $custoFornecedorReal + $custoDesapegoVirtual;

        // 4. RESULTADO OPERACIONAL
        $margemBruta = $faturamentoLiquido - $investimentoTotalEstoque;
        $lucratividadePercentual = $faturamentoLiquido > 0 
            ? round(($margemBruta / $faturamentoLiquido) * 100, 1) 
            : 0;

        return view('admin.financeiro.relatorio_gerencial', compact(
            'periodo',
            'pedidosCount',
            'faturamentoBruto',
            'faturamentoLiquido',
            'saldoUtilizado',
            'liquidoDireto',
            'creditosAporte',
            'creditosAvaliacao',
            'creditosDevolucao',
            'totalCreditosGerados',
            'custoFornecedorReal',
            'custoDesapegoVirtual',
            'investimentoTotalEstoque',
            'margemBruta',
            'lucratividadePercentual'
        ));
    }
}

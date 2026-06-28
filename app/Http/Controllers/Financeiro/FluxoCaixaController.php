<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\ClassificacaoFinanceira;
use App\Models\ContaBancaria;
use App\Models\Movimentacao;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FluxoCaixaController extends Controller
{
    public function index(Request $request)
    {
        $periodo = $request->filled('periodo')
            ? Carbon::createFromFormat('Y-m', $request->periodo)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $inicioPeriodo = $periodo->copy()->startOfMonth();
        $fimPeriodo    = $periodo->copy()->endOfMonth();

        // 1. Obter todas as contas bancárias para os filtros
        $contas = ContaBancaria::all();

        // Contas reais por padrão (exclui conta virtual da carteira)
        $defaultContasIds = $contas->filter(function ($c) {
            return !str_contains(strtolower($c->nome), 'carteira');
        })->pluck('id')->toArray();

        // Contas selecionadas
        $contasSelecionadas = $request->has('contas')
            ? array_map('intval', (array) $request->contas)
            : $defaultContasIds;

        // 2. Buscar movimentações do período para as contas selecionadas
        $movimentacoesQuery = Movimentacao::with(['lancamento.classificacaoFinanceira'])
            ->whereBetween('data_pagamento', [$inicioPeriodo->toDateString(), $fimPeriodo->toDateString()]);

        if (!empty($contasSelecionadas)) {
            $movimentacoesQuery->whereIn('conta_bancaria_id', $contasSelecionadas);
        }

        $movimentacoes = $movimentacoesQuery->get();

        // 3. Totais gerais
        $totalEntradas = 0;
        $totalSaidas = 0;

        foreach ($movimentacoes as $mov) {
            $lancamento = $mov->lancamento;
            if ($lancamento) {
                if ($lancamento->tipo === 'receita') {
                    $totalEntradas += (float) $mov->valor_pago;
                } elseif ($lancamento->tipo === 'despesa') {
                    $totalSaidas += (float) $mov->valor_pago;
                }
            }
        }

        $saldoLiquido = $totalEntradas - $totalSaidas;

        // 4. Agrupamento por Classificação Financeira (Árvore)
        $classificacoesRealizado = [];
        foreach ($movimentacoes as $mov) {
            $lancamento = $mov->lancamento;
            if ($lancamento && $lancamento->classificacao_financeira_id) {
                $classId = $lancamento->classificacao_financeira_id;
                if (!isset($classificacoesRealizado[$classId])) {
                    $classificacoesRealizado[$classId] = 0;
                }
                $classificacoesRealizado[$classId] += (float) $mov->valor_pago;
            }
        }

        $todasClassificacoes = ClassificacaoFinanceira::orderBy('codigo_contabil')->get();

        $classificacoesMapped = $todasClassificacoes->map(function ($c) use ($classificacoesRealizado) {
            $realizado = $classificacoesRealizado[$c->id] ?? 0.0;
            return [
                'id' => $c->id,
                'nome' => $c->nome,
                'codigo_contabil' => $c->codigo_contabil,
                'tipo_natureza' => $c->tipo_natureza,
                'id_pai' => $c->id_pai,
                'realizado' => (float) $realizado,
            ];
        });

        $receitasRaw = $classificacoesMapped->filter(fn($c) => $c['tipo_natureza'] === 'receita');
        $despesasRaw = $classificacoesMapped->filter(fn($c) => $c['tipo_natureza'] === 'despesa');

        $treeReceitas = $this->buildTree($receitasRaw);
        $treeDespesas = $this->buildTree($despesasRaw);

        // Agregar totais dos nós filhos para os pais
        $treeReceitas = $treeReceitas->map(function ($node) {
            $this->aggregateTotals($node);
            return $node;
        });

        $treeDespesas = $treeDespesas->map(function ($node) {
            $this->aggregateTotals($node);
            return $node;
        });

        // Filtrar nós que possuem valor > 0 (para exibir apenas categorias com movimento)
        $treeReceitas = $treeReceitas->filter(fn($n) => $n['realizado'] > 0);
        $treeDespesas = $treeDespesas->filter(fn($n) => $n['realizado'] > 0);

        // 5. Dados Diários para o Gráfico (Evolução de Caixa)
        $diasNoMes = $inicioPeriodo->daysInMonth;
        $chartLabels = [];
        $chartEntradas = [];
        $chartSaidas = [];
        $chartSaldoAcumulado = [];

        // Para saldo acumulado, precisamos do saldo anterior ao início do período nas contas selecionadas
        $saldoInicialPeriodo = 0.0;
        if (!empty($contasSelecionadas)) {
            $saldoInicialPeriodo = (float) ContaBancaria::whereIn('id', $contasSelecionadas)->sum('saldo_inicial');

            // Somar entradas anteriores nas contas selecionadas
            $entradasAnteriores = Movimentacao::whereIn('conta_bancaria_id', $contasSelecionadas)
                ->where('data_pagamento', '<', $inicioPeriodo->toDateString())
                ->whereHas('lancamento', fn($q) => $q->where('tipo', 'receita'))
                ->sum('valor_pago');

            // Subtrair saídas anteriores
            $saidasAnteriores = Movimentacao::whereIn('conta_bancaria_id', $contasSelecionadas)
                ->where('data_pagamento', '<', $inicioPeriodo->toDateString())
                ->whereHas('lancamento', fn($q) => $q->where('tipo', 'despesa'))
                ->sum('valor_pago');

            $saldoInicialPeriodo += ($entradasAnteriores - $saidasAnteriores);
        }

        $saldoAcumulado = $saldoInicialPeriodo;

        for ($dia = 1; $dia <= $diasNoMes; $dia++) {
            $dataDia = $inicioPeriodo->copy()->day($dia)->toDateString();
            $label = $inicioPeriodo->copy()->day($dia)->format('d/m');
            $chartLabels[] = $label;

            $movsDoDia = $movimentacoes->filter(fn($m) => $m->data_pagamento->toDateString() === $dataDia);

            $entradasDia = 0.0;
            $saidasDia = 0.0;

            foreach ($movsDoDia as $mov) {
                $lanc = $mov->lancamento;
                if ($lanc) {
                    if ($lanc->tipo === 'receita') {
                        $entradasDia += (float) $mov->valor_pago;
                    } elseif ($lanc->tipo === 'despesa') {
                        $saidasDia += (float) $mov->valor_pago;
                    }
                }
            }

            $chartEntradas[] = $entradasDia;
            $chartSaidas[] = $saidasDia;

            $saldoAcumulado += ($entradasDia - $saidasDia);
            $chartSaldoAcumulado[] = $saldoAcumulado;
        }

        $chartData = [
            'labels' => $chartLabels,
            'entradas' => $chartEntradas,
            'saidas' => $chartSaidas,
            'acumulado' => $chartSaldoAcumulado,
        ];

        return view('admin.financeiro.fluxo_caixa', compact(
            'contas',
            'contasSelecionadas',
            'totalEntradas',
            'totalSaidas',
            'saldoLiquido',
            'treeReceitas',
            'treeDespesas',
            'chartData',
            'periodo'
        ));
    }

    private function buildTree($items, $parentId = null)
    {
        $branch = collect();
        foreach ($items as $item) {
            if ($item['id_pai'] == $parentId) {
                $children = $this->buildTree($items, $item['id']);
                $item['children'] = $children;
                $branch->push($item);
            }
        }
        return $branch;
    }

    private function aggregateTotals(&$node)
    {
        $totalRealizado = $node['realizado'];

        if (!empty($node['children'])) {
            $node['children'] = $node['children']->map(function ($child) use (&$totalRealizado) {
                $this->aggregateTotals($child);
                $totalRealizado += $child['realizado'];
                return $child;
            });
        }

        $node['realizado'] = $totalRealizado;
    }
}

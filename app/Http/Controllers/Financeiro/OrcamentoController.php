<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\ClassificacaoFinanceira;
use App\Models\Orcamento;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrcamentoController extends Controller
{
    /**
     * Exibe a tela de Previsto x Realizado para um mês.
     */
    public function index(Request $request)
    {
        $periodo = $request->filled('periodo')
            ? Carbon::createFromFormat('Y-m', $request->periodo)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $inicioPeriodo = $periodo->copy()->startOfMonth();
        $fimPeriodo    = $periodo->copy()->endOfMonth();
        $periodoDate   = $inicioPeriodo->format('Y-m-d');

        // Busca todas as categorias com seus orçamentos e realizado do mês
        $classificacoes = ClassificacaoFinanceira::select(
                'classificacao_financeira.*',
                DB::raw("(
                    SELECT COALESCE(SUM(m.valor_pago), 0)
                    FROM movimentacoes m
                    INNER JOIN lancamentos l ON l.id = m.lancamento_id
                    WHERE l.classificacao_financeira_id = classificacao_financeira.id
                      AND m.data_pagamento BETWEEN ? AND ?
                ) AS realizado")
            )
            ->addBinding([$inicioPeriodo->toDateString(), $fimPeriodo->toDateString()], 'select')
            ->with(['orcamentos' => function ($q) use ($periodoDate) {
                $q->where('periodo', $periodoDate);
            }])
            ->orderBy('tipo_natureza')
            ->orderBy('nome')
            ->get()
            ->map(function ($c) use ($periodoDate) {
                $orcamento = $c->orcamentos->first();
                $previsto  = $orcamento ? (float) $orcamento->valor_previsto : 0;
                $realizado = (float) $c->realizado;
                $diferenca = $previsto - $realizado;
                $percentual = $previsto > 0 ? round(($realizado / $previsto) * 100, 1) : 0;

                return [
                    'id'                       => $c->id,
                    'nome'                     => $c->nome,
                    'codigo_contabil'          => $c->codigo_contabil,
                    'tipo_natureza'            => $c->tipo_natureza,
                    'orcamento_id'             => $orcamento?->id,
                    'previsto'                 => $previsto,
                    'realizado'                => $realizado,
                    'diferenca'                => $diferenca,
                    'percentual'               => $percentual,
                    'status_barra'             => $this->statusBarra($c->tipo_natureza, $percentual),
                ];
            });

        $totalPrevistoDespesa  = $classificacoes->where('tipo_natureza', 'despesa')->sum('previsto');
        $totalRealizadoDespesa = $classificacoes->where('tipo_natureza', 'despesa')->sum('realizado');
        $totalPrevistaReceita  = $classificacoes->where('tipo_natureza', 'receita')->sum('previsto');
        $totalRealizadaReceita = $classificacoes->where('tipo_natureza', 'receita')->sum('realizado');

        return view('admin.financeiro.orcamento.index', compact(
            'classificacoes',
            'periodo',
            'totalPrevistoDespesa',
            'totalRealizadoDespesa',
            'totalPrevistaReceita',
            'totalRealizadaReceita'
        ));
    }

    /**
     * Salva ou atualiza o valor previsto de um orçamento via AJAX (edição inline).
     */
    public function upsert(Request $request)
    {
        $data = $request->validate([
            'classificacao_financeira_id' => ['required', 'exists:classificacao_financeira,id'],
            'periodo'                     => ['required', 'date'],
            'valor_previsto'              => ['required', 'numeric', 'min:0'],
        ]);

        // Garante que o período é sempre dia 01 do mês
        $data['periodo'] = Carbon::parse($data['periodo'])->startOfMonth()->toDateString();

        $orcamento = Orcamento::updateOrCreate(
            [
                'classificacao_financeira_id' => $data['classificacao_financeira_id'],
                'periodo'                     => $data['periodo'],
            ],
            ['valor_previsto' => $data['valor_previsto']]
        );

        return response()->json(['success' => true, 'orcamento' => $orcamento]);
    }

    // ---- Helpers ----

    private function statusBarra(string $tipoNatureza, float $percentual): string
    {
        if ($tipoNatureza === 'despesa') {
            return $percentual >= 100 ? 'danger' : 'warning';
        }

        // Receita
        return $percentual >= 100 ? 'success' : 'info';
    }
}

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

        // Busca todas as categorias com seus orçamentos e realizado do mês ordenados por código contábil
        $classificacoes = ClassificacaoFinanceira::select(
                'classificacao_financeira.*',
                DB::raw("(
                    SELECT COALESCE(SUM(
                        CASE 
                            WHEN l.tipo = classificacao_financeira.tipo_natureza COLLATE utf8mb4_unicode_ci THEN l.valor_total 
                            ELSE -l.valor_total 
                        END
                    ), 0)
                    FROM lancamentos l
                    WHERE l.classificacao_financeira_id = classificacao_financeira.id
                      AND l.data_emissao BETWEEN ? AND ?
                ) AS realizado")
            )
            ->addBinding([$inicioPeriodo->toDateString(), $fimPeriodo->toDateString()], 'select')
            ->whereNotIn('classificacao_financeira.nome', ['Recarga de Carteira', 'Aporte de Carteira'])
            ->with(['orcamentos' => function ($q) use ($periodoDate) {
                $q->where('periodo', $periodoDate);
            }])
            ->orderBy('tipo_natureza')
            ->orderBy('codigo_contabil')
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
                    'id_pai'                   => $c->id_pai,
                ];
            });

        // Agrupar e montar árvore para receitas e despesas
        $itemsKeyed = $classificacoes->keyBy('id');
        
        $receitasRaw = $classificacoes->filter(fn($c) => $c['tipo_natureza'] === 'receita');
        $despesasRaw = $classificacoes->filter(fn($c) => $c['tipo_natureza'] === 'despesa');

        $treeReceitas = $this->buildTree($receitasRaw, $itemsKeyed);
        $treeDespesas = $this->buildTree($despesasRaw, $itemsKeyed);

        // Agregar totais recursivamente de baixo para cima
        $treeReceitas = $treeReceitas->map(function ($node) {
            $this->aggregateTotals($node);
            return $node;
        });

        $treeDespesas = $treeDespesas->map(function ($node) {
            $this->aggregateTotals($node);
            return $node;
        });

        $totalPrevistoDespesa  = $treeDespesas->sum('previsto');
        $totalRealizadoDespesa = $treeDespesas->sum('realizado');
        $totalPrevistaReceita  = $treeReceitas->sum('previsto');
        $totalRealizadaReceita = $treeReceitas->sum('realizado');

        return view('admin.financeiro.orcamento.index', compact(
            'treeReceitas',
            'treeDespesas',
            'periodo',
            'totalPrevistoDespesa',
            'totalRealizadoDespesa',
            'totalPrevistaReceita',
            'totalRealizadaReceita',
            'classificacoes'
        ));
    }

    private function buildTree($items, $itemsKeyed, $parentId = null)
    {
        $branch = collect();

        foreach ($items as $item) {
            $isMatch = ($parentId === null)
                ? ($item['id_pai'] === null || !$itemsKeyed->has($item['id_pai']))
                : ($item['id_pai'] == $parentId);

            if ($isMatch) {
                $children = $this->buildTree($items, $itemsKeyed, $item['id']);
                $item['children'] = $children;
                $branch->push($item);
            }
        }

        return $branch->sortBy('codigo_contabil');
    }

    private function aggregateTotals(&$node)
    {
        $totalPrevisto = $node['previsto'];
        $totalRealizado = $node['realizado'];

        if (!empty($node['children'])) {
            $node['children'] = $node['children']->map(function ($child) use (&$totalPrevisto, &$totalRealizado) {
                $this->aggregateTotals($child);
                $totalPrevisto += $child['previsto'];
                $totalRealizado += $child['realizado'];
                return $child;
            });
        }

        $node['previsto'] = $totalPrevisto;
        $node['realizado'] = $totalRealizado;
        $node['diferenca'] = $totalPrevisto - $totalRealizado;
        $node['percentual'] = $totalPrevisto > 0 ? round(($totalRealizado / $totalPrevisto) * 100, 1) : 0;
        $node['status_barra'] = $this->statusBarra($node['tipo_natureza'], $node['percentual']);
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

    /**
     * Transporta/replica os valores previstos do mês selecionado para o próximo mês.
     */
    public function replicar(Request $request)
    {
        $request->validate([
            'periodo_origem' => ['required', 'date_format:Y-m'],
        ]);

        $periodoOrigem = Carbon::createFromFormat('Y-m', $request->periodo_origem)->startOfMonth();
        $periodoDestino = $periodoOrigem->copy()->addMonth();

        $orcamentosOrigem = Orcamento::where('periodo', $periodoOrigem->toDateString())->get();

        if ($orcamentosOrigem->isEmpty()) {
            return redirect()->back()->with('error', 'Nenhum orçamento definido no mês de origem para ser transportado.');
        }

        foreach ($orcamentosOrigem as $orcamento) {
            Orcamento::updateOrCreate(
                [
                    'classificacao_financeira_id' => $orcamento->classificacao_financeira_id,
                    'periodo'                     => $periodoDestino->toDateString(),
                ],
                [
                    'valor_previsto' => $orcamento->valor_previsto,
                ]
            );
        }

        // Traduz o mês de destino (ex: "julho de 2026")
        $mesDestinoNome = $periodoDestino->translatedFormat('F/Y');
        
        return redirect()->route('financeiro.orcamento.index', ['periodo' => $periodoDestino->format('Y-m')])
            ->with('success', "Valores previstos transportados com sucesso para {$mesDestinoNome}!");
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

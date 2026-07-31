<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\ContaBancaria;
use App\Models\Lancamento;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FinanceiroDashboardController extends Controller
{
    public function index()
    {
        $hoje = Carbon::today();

        // Saldo de cada conta bancária (com movimentações já carregadas para o accessor)
        $contasRaw = ContaBancaria::with(['movimentacoes.lancamento'])->get();

        $orderMap = [
            'inter'            => 1,
            'mercado pago'     => 2,
            'caixinha'         => 3,
            'carteira cliente' => 4,
        ];

        $contas = $contasRaw->sortBy(function ($conta) use ($orderMap) {
            $nomeLower = trim(mb_strtolower($conta->nome));
            foreach ($orderMap as $nameKey => $priority) {
                if (str_contains($nomeLower, $nameKey)) {
                    return $priority;
                }
            }
            return 99; // Qualquer outra conta vai para o final
        })->values();

        // A Pagar Hoje: despesas pendentes/parciais com vencimento hoje
        $aPagarHoje = Lancamento::with(['pessoa', 'classificacaoFinanceira'])
            ->where('tipo', 'despesa')
            ->whereIn('status', ['pendente', 'pago_parcial'])
            ->whereDate('data_vencimento', $hoje)
            ->orderBy('valor_total', 'desc')
            ->get();

        // A Receber Hoje: receitas pendentes/parciais com vencimento hoje
        $aReceberHoje = Lancamento::with(['pessoa', 'classificacaoFinanceira'])
            ->where('tipo', 'receita')
            ->whereIn('status', ['pendente', 'pago_parcial'])
            ->whereDate('data_vencimento', $hoje)
            ->orderBy('valor_total', 'desc')
            ->get();

        // Totalizadores do mês
        $mesAtual = $hoje->format('Y-m');

        $totalDespesasMes = Lancamento::where('tipo', 'despesa')
            ->whereRaw("DATE_FORMAT(data_vencimento, '%Y-%m') = ?", [$mesAtual])
            ->whereNotIn('status', ['cancelado'])
            ->sum('valor_total');

        $totalReceitasMes = Lancamento::where('tipo', 'receita')
            ->whereRaw("DATE_FORMAT(data_vencimento, '%Y-%m') = ?", [$mesAtual])
            ->whereNotIn('status', ['cancelado'])
            ->where(function ($query) {
                $query->whereDoesntHave('classificacaoFinanceira', function ($q) {
                    $q->where('nome', 'Recarga de Carteira');
                });
            })
            ->sum('valor_total');

        $totalAtrasados = Lancamento::whereIn('status', ['pendente', 'pago_parcial'])
            ->whereDate('data_vencimento', '<', $hoje)
            ->count();

        return view('admin.financeiro.dashboard', compact(
            'contas',
            'aPagarHoje',
            'aReceberHoje',
            'totalDespesasMes',
            'totalReceitasMes',
            'totalAtrasados'
        ));
    }
}

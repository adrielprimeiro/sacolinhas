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
        $contas = ContaBancaria::with(['movimentacoes.lancamento'])->get();

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

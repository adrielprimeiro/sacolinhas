<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\ContaBancaria;
use App\Models\Lancamento;
use Illuminate\Http\Request;

class ConciliacaoController extends Controller
{
    /**
     * Tela de conciliação: extrato de uma conta vs. lançamentos pendentes.
     */
    public function index(Request $request)
    {
        $contaId = $request->get('conta_bancaria_id');

        $contas = ContaBancaria::orderBy('nome')->get();

        $movimentacoes = collect();
        $lancamentosPendentes = collect();
        $contaSelecionada = null;

        if ($contaId) {
            $contaSelecionada = ContaBancaria::findOrFail($contaId);

            // Extrato: movimentações mais recentes da conta selecionada
            $movimentacoes = $contaSelecionada->movimentacoes()
                ->with('lancamento.pessoa')
                ->orderBy('data_pagamento', 'desc')
                ->limit(50)
                ->get();

            // Lançamentos pendentes (sem movimentação ou parciais) para vincular
            $tipo = $request->get('tipo_lancamento'); // receita ou despesa

            $query = Lancamento::with(['pessoa', 'classificacaoFinanceira'])
                ->whereIn('status', ['pendente', 'pago_parcial'])
                ->orderBy('data_vencimento');

            if ($tipo) {
                $query->where('tipo', $tipo);
            }

            $lancamentosPendentes = $query->limit(50)->get();
        }

        return view('admin.financeiro.conciliacao.index', compact(
            'contas',
            'contaSelecionada',
            'movimentacoes',
            'lancamentosPendentes'
        ));
    }

    /**
     * Vincula uma movimentação existente a um lançamento (conciliação manual).
     */
    public function vincular(Request $request)
    {
        $data = $request->validate([
            'movimentacao_id' => ['required', 'exists:movimentacoes,id'],
            'lancamento_id'   => ['required', 'exists:lancamentos,id'],
        ]);

        $movimentacao = \App\Models\Movimentacao::findOrFail($data['movimentacao_id']);
        $movimentacao->update(['lancamento_id' => $data['lancamento_id']]);

        // Recalcular status do lançamento vinculado
        $lancamento = \App\Models\Lancamento::with('movimentacoes')->findOrFail($data['lancamento_id']);
        $totalPago  = $lancamento->movimentacoes->sum('valor_pago');

        $novoStatus = $totalPago >= ($lancamento->valor_total - 0.01) ? 'pago' : 'pago_parcial';
        $lancamento->update(['status' => $novoStatus]);

        return response()->json(['success' => true, 'novo_status' => $novoStatus]);
    }
}

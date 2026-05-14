<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\Movimentacao;
use App\Models\ContaBancaria;
use Illuminate\Http\Request;

class MovimentacaoController extends Controller
{
    public function index(Request $request)
    {
        $contas = ContaBancaria::orderBy('nome')->get();
        
        $query = Movimentacao::with(['lancamento.pessoa', 'contaBancaria'])
            ->orderBy('data_pagamento', 'desc');

        if ($request->filled('conta_bancaria_id')) {
            $query->where('conta_bancaria_id', $request->conta_bancaria_id);
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate('data_pagamento', '>=', $request->data_inicio);
        }

        if ($request->filled('data_fim')) {
            $query->whereDate('data_pagamento', '<=', $request->data_fim);
        }

        if ($request->filled('forma_pagamento')) {
            $query->where('forma_pagamento', $request->forma_pagamento);
        }

        $movimentacoes = $query->paginate(30);

        // Totais filtrados pela conta selecionada
        $totaisQuery = Movimentacao::query();
        if ($request->filled('conta_bancaria_id')) {
            $totaisQuery->where('conta_bancaria_id', $request->conta_bancaria_id);
        }
        
        $totalEntradas = (clone $totaisQuery)->whereHas('lancamento', function ($q) {
            $q->where('tipo', 'receita');
        })->sum('valor_pago');

        $totalSaidas = (clone $totaisQuery)->whereHas('lancamento', function ($q) {
            $q->where('tipo', 'despesa');
        })->sum('valor_pago');

        $contaSelecionada = $request->conta_bancaria_id 
            ? ContaBancaria::find($request->conta_bancaria_id) 
            : null;

        return view('admin.financeiro.movimentacoes', 
            compact('movimentacoes', 'totalEntradas', 'totalSaidas', 'contas', 'contaSelecionada'));
    }
}
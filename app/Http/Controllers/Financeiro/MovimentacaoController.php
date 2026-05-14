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

    public function transferir(Request $request)
    {
        $request->validate([
            'conta_origem_id' => 'required|exists:contas_bancarias,id',
            'conta_destino_id' => 'required|exists:contas_bancarias,id|different:conta_origem_id',
            'valor' => 'required|numeric|min:0.01',
            'data_pagamento' => 'required|date',
            'descricao' => 'nullable|string'
        ]);

        return \DB::transaction(function() use ($request) {
            $descricao = $request->descricao ?: "Transferência entre contas";
            
            // 1. Lançamento e Movimentação de SAÍDA
            $saida = \App\Models\Lancamento::create([
                'tipo' => 'despesa',
                'status' => 'pago',
                'pessoa_id' => 1, 
                'classificacao_financeira_id' => 4, // Despesas
                'data_emissao' => $request->data_pagamento,
                'data_vencimento' => $request->data_pagamento,
                'valor_total' => $request->valor,
                'descricao' => "[SAÍDA] $descricao",
            ]);

            Movimentacao::create([
                'lancamento_id' => $saida->id,
                'conta_bancaria_id' => $request->conta_origem_id,
                'data_pagamento' => $request->data_pagamento,
                'valor_pago' => $request->valor,
                'forma_pagamento' => 'transferencia',
            ]);

            // 2. Lançamento e Movimentação de ENTRADA
            $entrada = \App\Models\Lancamento::create([
                'tipo' => 'receita',
                'status' => 'pago',
                'pessoa_id' => 1,
                'classificacao_financeira_id' => 3, // Receitas
                'data_emissao' => $request->data_pagamento,
                'data_vencimento' => $request->data_pagamento,
                'valor_total' => $request->valor,
                'descricao' => "[ENTRADA] $descricao",
            ]);

            Movimentacao::create([
                'lancamento_id' => $entrada->id,
                'conta_bancaria_id' => $request->conta_destino_id,
                'data_pagamento' => $request->data_pagamento,
                'valor_pago' => $request->valor,
                'forma_pagamento' => 'transferencia',
            ]);

            return back()->with('success', 'Transferência realizada com sucesso!');
        });
    }

    public function update(Request $request, Movimentacao $movimentacao)
    {
        $request->validate([
            'conta_bancaria_id' => 'required|exists:contas_bancarias,id',
            'data_pagamento' => 'required|date',
            'valor_pago' => 'required|numeric|min:0',
            'forma_pagamento' => 'required|string',
        ]);

        \DB::transaction(function() use ($request, $movimentacao) {
            $movimentacao->update($request->only([
                'conta_bancaria_id',
                'data_pagamento',
                'valor_pago',
                'forma_pagamento'
            ]));

            // Atualizar status do lançamento pai
            if ($movimentacao->lancamento) {
                $lancamento = $movimentacao->lancamento;
                $pago = $lancamento->movimentacoes()->sum('valor_pago');
                
                if ($pago >= $lancamento->valor_total) {
                    $lancamento->update(['status' => 'pago']);
                } elseif ($pago > 0) {
                    $lancamento->update(['status' => 'pago_parcial']);
                } else {
                    $lancamento->update(['status' => 'pendente']);
                }
            }
        });

        return back()->with('success', 'Movimentação atualizada!');
    }

    public function destroy(Movimentacao $movimentacao)
    {
        \DB::transaction(function() use ($movimentacao) {
            $lancamento = $movimentacao->lancamento;
            $movimentacao->delete();

            if ($lancamento) {
                $pago = $lancamento->movimentacoes()->sum('valor_pago');
                if ($pago >= $lancamento->valor_total) {
                    $lancamento->update(['status' => 'pago']);
                } elseif ($pago > 0) {
                    $lancamento->update(['status' => 'pago_parcial']);
                } else {
                    $lancamento->update(['status' => 'pendente']);
                }
            }
        });

        return back()->with('success', 'Movimentação excluída!');
    }
}
<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\Movimentacao;
use App\Models\ContaBancaria;
use App\Models\ClassificacaoFinanceira;
use App\Models\Pessoa;
use Illuminate\Http\Request;

class MovimentacaoController extends Controller
{
    public function index(Request $request)
    {
        $contas = ContaBancaria::orderBy('nome')->get();
        $classificacoes = ClassificacaoFinanceira::orderBy('nome')->get();
        $pessoas = Pessoa::orderBy('nome')->get();
        
        $query = Movimentacao::with(['lancamento.pessoa', 'lancamento.classificacaoFinanceira', 'contaBancaria'])
            ->orderBy('data_pagamento', 'desc');

        // Aplicar filtros à query principal
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

        if ($request->filled('classificacao_financeira_id')) {
            $query->whereHas('lancamento', function ($q) use ($request) {
                $q->where('classificacao_financeira_id', $request->classificacao_financeira_id);
            });
        }

        if ($request->filled('tipo')) {
            $query->whereHas('lancamento', function ($q) use ($request) {
                $q->where('tipo', $request->tipo);
            });
        }

        if ($request->filled('pessoa_id')) {
            $query->whereHas('lancamento', function ($q) use ($request) {
                $q->where('pessoa_id', $request->pessoa_id);
            });
        }

        $movimentacoes = $query->paginate(30);

        // Totais filtrados de acordo com todos os filtros
        $totaisQuery = Movimentacao::query();
        if ($request->filled('conta_bancaria_id')) {
            $totaisQuery->where('conta_bancaria_id', $request->conta_bancaria_id);
        }
        if ($request->filled('data_inicio')) {
            $totaisQuery->whereDate('data_pagamento', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $totaisQuery->whereDate('data_pagamento', '<=', $request->data_fim);
        }
        if ($request->filled('forma_pagamento')) {
            $totaisQuery->where('forma_pagamento', $request->forma_pagamento);
        }
        if ($request->filled('classificacao_financeira_id')) {
            $totaisQuery->whereHas('lancamento', function ($q) use ($request) {
                $q->where('classificacao_financeira_id', $request->classificacao_financeira_id);
            });
        }
        if ($request->filled('tipo')) {
            $totaisQuery->whereHas('lancamento', function ($q) use ($request) {
                $q->where('tipo', $request->tipo);
            });
        }
        if ($request->filled('pessoa_id')) {
            $totaisQuery->whereHas('lancamento', function ($q) use ($request) {
                $q->where('pessoa_id', $request->pessoa_id);
            });
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
            compact('movimentacoes', 'totalEntradas', 'totalSaidas', 'contas', 'contaSelecionada', 'classificacoes', 'pessoas'));
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
        $rules = [
            'conta_bancaria_id' => 'required|exists:contas_bancarias,id',
            'data_pagamento' => 'required|date',
            'valor_pago' => 'required|numeric|min:0',
            'forma_pagamento' => 'required|string',
        ];

        if ($movimentacao->lancamento) {
            $rules['tipo'] = 'required|string|in:receita,despesa';
            $rules['classificacao_financeira_id'] = 'required|exists:classificacao_financeira,id';
            $rules['descricao'] = 'required|string';
            $rules['pessoa_id'] = 'required|exists:pessoas,id';
        }

        $request->validate($rules);

        \DB::transaction(function() use ($request, $movimentacao) {
            // 1. Atualizar lançamento pai primeiro se existir
            if ($movimentacao->lancamento) {
                $lancamento = $movimentacao->lancamento;
                
                $lancamentoData = $request->only([
                    'tipo',
                    'classificacao_financeira_id',
                    'descricao',
                    'pessoa_id'
                ]);

                // Se houver apenas 1 movimentacao vinculada a este lancamento,
                // podemos atualizar o valor_total do lançamento para bater com o valor_pago.
                if ($lancamento->movimentacoes()->count() === 1) {
                    $lancamentoData['valor_total'] = $request->valor_pago;
                }

                $lancamento->update($lancamentoData);
            }

            // 2. Atualizar movimentação (dispara hook static::updated e sincronizarCarteira)
            $movimentacao->update($request->only([
                'conta_bancaria_id',
                'data_pagamento',
                'valor_pago',
                'forma_pagamento'
            ]));

            // 3. Recalcular e atualizar status do lançamento, e forçar sincronização da carteira com dados atualizados
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

                $movimentacao->unsetRelation('lancamento');
                $movimentacao->sincronizarCarteira();
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
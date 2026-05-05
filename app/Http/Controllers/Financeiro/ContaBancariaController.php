<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\ContaBancaria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContaBancariaController extends Controller
{
    public function index()
    {
        $contas = ContaBancaria::withCount('movimentacoes')
            ->with(['movimentacoes.lancamento'])
            ->orderBy('nome')
            ->get();

        return view('admin.financeiro.contas.index', compact('contas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome'         => ['required', 'string', 'max:100'],
            'tipo'         => ['required', Rule::in(['corrente', 'poupanca', 'caixa', 'gateway'])],
            'saldo_inicial' => ['required', 'numeric', 'min:0'],
        ]);

        $conta = ContaBancaria::create($data);

        return response()->json(['success' => true, 'conta' => $conta]);
    }

    public function update(Request $request, ContaBancaria $contaBancaria)
    {
        $data = $request->validate([
            'nome'         => ['required', 'string', 'max:100'],
            'tipo'         => ['required', Rule::in(['corrente', 'poupanca', 'caixa', 'gateway'])],
            'saldo_inicial' => ['required', 'numeric', 'min:0'],
        ]);

        $contaBancaria->update($data);

        return response()->json(['success' => true]);
    }

    public function destroy(ContaBancaria $contaBancaria)
    {
        if ($contaBancaria->movimentacoes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta conta possui movimentações e não pode ser excluída.',
            ], 422);
        }

        $contaBancaria->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Extrato de uma conta: todas as movimentações ordenadas por data.
     */
    public function extrato(ContaBancaria $contaBancaria)
    {
        $movimentacoes = $contaBancaria->movimentacoes()
            ->with('lancamento.pessoa')
            ->orderBy('data_pagamento', 'desc')
            ->paginate(30);

        return view('admin.financeiro.contas.extrato', compact('contaBancaria', 'movimentacoes'));
    }
}

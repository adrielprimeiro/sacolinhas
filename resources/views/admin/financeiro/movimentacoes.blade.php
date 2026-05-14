@extends('layouts.app')

@section('title', 'Movimentações')
@section('brand_route', 'financeiro.dashboard')
@section('brand_icon', 'fas fa-balance-scale')

@section('content')

@php
$currentRoute = Route::currentRouteName();
@endphp
<div class="flex flex-wrap gap-1 mb-6 border-b border-gray-200 pb-3">
    <a href="{{ route('financeiro.dashboard') }}"
       class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'dashboard') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-home mr-1"></i> Dashboard
    </a>
    <a href="{{ route('financeiro.conciliacao.index') }}"
       class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'conciliacao') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-balance-scale mr-1"></i> Conciliação
    </a>
    <a href="{{ route('financeiro.movimentacoes.index') }}"
       class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'movimentacoes') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-exchange-alt mr-1"></i> Movimentações
    </a>
    <a href="{{ route('financeiro.lancamentos.index') }}"
       class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'lancamentos') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-file-invoice-dollar mr-1"></i> Lançamentos
    </a>
    <a href="{{ route('financeiro.contas.index') }}"
       class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'contas') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-university mr-1"></i> Contas
    </a>
    <a href="{{ route('financeiro.orcamento.index') }}"
       class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'orcamento') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-chart-bar mr-1"></i> Orçamento
    </a>
    <a href="{{ route('financeiro.pessoas.index') }}"
       class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'pessoas') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-users mr-1"></i> Contatos
    </a>
</div>

@if($contaSelecionada)
<div class="mb-4 bg-indigo-50 border border-indigo-200 rounded-xl p-3 flex items-center justify-between">
    <div>
        <p class="text-xs font-black text-indigo-600 uppercase tracking-widest">Conta Selecionada</p>
        <p class="text-sm font-bold text-indigo-800">{{ $contaSelecionada->nome }}</p>
    </div>
    <div class="text-right">
        <p class="text-xs font-black text-indigo-400 uppercase">Saldo Atual</p>
        <p class="text-lg font-black text-indigo-700">R$ {{ number_format($contaSelecionada->saldo_atual, 2, ',', '.') }}</p>
    </div>
</div>
@endif

<div class="mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
            <p class="text-xs font-black text-green-600 uppercase tracking-widest">Total Entradas</p>
            <p class="text-2xl font-black text-green-700">R$ {{ number_format($totalEntradas, 2, ',', '.') }}</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
            <p class="text-xs font-black text-red-600 uppercase tracking-widest">Total Saídas</p>
            <p class="text-2xl font-black text-red-700">R$ {{ number_format($totalSaidas, 2, ',', '.') }}</p>
        </div>
        <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4">
            <p class="text-xs font-black text-indigo-600 uppercase tracking-widest">Saldo no Período</p>
            <p class="text-2xl font-black {{ $totalEntradas - $totalSaidas >= 0 ? 'text-green-700' : 'text-red-700' }}">
                R$ {{ number_format($totalEntradas - $totalSaidas, 2, ',', '.') }}
            </p>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 bg-gray-50">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="min-w-[180px]">
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Conta</label>
                <select name="conta_bancaria_id" class="border border-gray-200 rounded-xl p-2 text-sm bg-white w-full">
                    <option value="">Todas as Contas</option>
                    @foreach($contas as $conta)
                        <option value="{{ $conta->id }}" {{ request('conta_bancaria_id') == $conta->id ? 'selected' : '' }}>
                            {{ $conta->nome }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Data Início</label>
                <input type="date" name="data_inicio" value="{{ request('data_inicio') }}" class="border border-gray-200 rounded-xl p-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Data Fim</label>
                <input type="date" name="data_fim" value="{{ request('data_fim') }}" class="border border-gray-200 rounded-xl p-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Forma</label>
                <select name="forma_pagamento" class="border border-gray-200 rounded-xl p-2 text-sm bg-white">
                    <option value="">Todas</option>
                    <option value="pix" {{ request('forma_pagamento') == 'pix' ? 'selected' : '' }}>PIX</option>
                    <option value="transferencia" {{ request('forma_pagamento') == 'transferencia' ? 'selected' : '' }}>Transferência</option>
                    <option value="dinheiro" {{ request('forma_pagamento') == 'dinheiro' ? 'selected' : '' }}>Dinheiro</option>
                    <option value="boleto" {{ request('forma_pagamento') == 'boleto' ? 'selected' : '' }}>Boleto</option>
                    <option value="cartao" {{ request('forma_pagamento') == 'cartao' ? 'selected' : '' }}>Cartão</option>
                </select>
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-indigo-700">
                <i class="fas fa-filter mr-1"></i> Filtrar
            </button>
            <a href="{{ route('financeiro.movimentacoes.index') }}" class="text-gray-500 text-sm hover:text-gray-700 px-2">
                Limpar
            </a>
        </form>
    </div>

    <table class="w-full text-left">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase">Data</th>
                <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase">Lançamento</th>
                <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase">Pessoa</th>
                <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase">Forma</th>
                <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase">Conta</th>
                <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase text-right">Valor</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($movimentacoes as $m)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-4 text-sm text-gray-500">{{ $m->data_pagamento->format('d/m/Y') }}</td>
                    <td class="px-5 py-4">
                        <div class="text-sm font-bold text-gray-800">{{ $m->lancamento->descricao ?? '---' }}</div>
                        <div class="text-[10px] text-gray-400">{{ $m->lancamento->tipo ?? '' }}</div>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ $m->lancamento->pessoa->nome ?? '---' }}</td>
                    <td class="px-5 py-4">
                        <span class="text-xs font-bold uppercase px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                            {{ $m->forma_pagamento }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-500">{{ $m->contaBancaria->nome ?? '---' }}</td>
                    <td class="px-5 py-4 text-right">
                        <span class="text-sm font-black {{ $m->lancamento->tipo === 'receita' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $m->lancamento->tipo === 'receita' ? '+' : '-' }} R$ {{ number_format($m->valor_pago, 2, ',', '.') }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-8 text-center text-gray-500">
                        Nenhuma movimentação encontrada.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="p-4 border-t border-gray-100">
        {{ $movimentacoes->links() }}
    </div>
</div>

@endsection
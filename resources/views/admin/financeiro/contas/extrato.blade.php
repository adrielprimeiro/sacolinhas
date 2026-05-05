@extends('layouts.app')
@section('title', 'Extrato — ' . $contaBancaria->nome)
@section('brand_route', 'financeiro.dashboard')
@section('brand_icon', 'fas fa-chart-line')

@section('content')

<div class="mb-6 flex items-center gap-3">
    <a href="{{ route('financeiro.contas.index') }}"
       class="w-9 h-9 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500 transition">
        <i class="fas fa-arrow-left text-sm"></i>
    </a>
    <div>
        <h1 class="text-2xl font-black text-gray-800">Extrato — {{ $contaBancaria->nome }}</h1>
        <p class="text-sm text-gray-400 mt-0.5">Movimentações registradas em ordem cronológica</p>
    </div>
</div>

{{-- Card saldo --}}
@php $saldo = $contaBancaria->saldo_atual; @endphp
<div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-5 mb-6 text-white flex items-center justify-between">
    <div>
        <p class="text-indigo-200 text-xs uppercase tracking-wider">Saldo Atual</p>
        <p class="text-3xl font-black mt-1 {{ $saldo >= 0 ? '' : 'text-red-300' }}">
            R$ {{ number_format($saldo, 2, ',', '.') }}
        </p>
    </div>
    <div class="text-right text-sm text-indigo-200">
        <p>Saldo inicial: R$ {{ number_format($contaBancaria->saldo_inicial, 2, ',', '.') }}</p>
        <p class="mt-1 capitalize">{{ $contaBancaria->tipo }}</p>
    </div>
</div>

{{-- Tabela extrato --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Data</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Descrição / Pessoa</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Forma</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Valor</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($movimentacoes as $mov)
                    @php $isReceita = $mov->lancamento?->tipo === 'receita'; @endphp
                    <tr class="hover:bg-gray-50/60 transition">
                        <td class="px-5 py-3.5 text-gray-500 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($mov->data_pagamento)->format('d/m/Y') }}
                        </td>
                        <td class="px-5 py-3.5">
                            <p class="font-semibold text-gray-800">
                                {{ $mov->lancamento?->descricao ?? 'Movimentação #'.$mov->id }}
                            </p>
                            <p class="text-xs text-gray-400">{{ $mov->lancamento?->pessoa?->nome ?? '—' }}</p>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-lg font-medium capitalize">
                                {{ str_replace('_', ' ', $mov->forma_pagamento) }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right font-black whitespace-nowrap
                                   {{ $isReceita ? 'text-green-700' : 'text-red-700' }}">
                            {{ $isReceita ? '+' : '−' }} R$ {{ number_format($mov->valor_pago, 2, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-16 text-center text-gray-400">
                            <i class="fas fa-inbox text-3xl text-gray-200 mb-2 block"></i>
                            Nenhuma movimentação registrada nesta conta.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($movimentacoes->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $movimentacoes->links() }}
        </div>
    @endif
</div>
@endsection

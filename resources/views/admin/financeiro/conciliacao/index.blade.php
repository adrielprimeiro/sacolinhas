@extends('layouts.app')
@section('title', 'Conciliação Bancária')
@section('brand_route', 'financeiro.dashboard')
@section('brand_icon', 'fas fa-chart-line')

@section('content')

{{-- Sub-nav --}}
@php
$currentRoute = Route::currentRouteName();
@endphp
<div class="flex flex-wrap gap-1 mb-6 border-b border-gray-200 pb-3">
    <a href="{{ route('financeiro.dashboard') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'dashboard') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-home mr-1"></i> Dashboard</a>
    <a href="{{ route('financeiro.conciliacao.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'conciliacao') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-balance-scale mr-1"></i> Conciliação</a>
    <a href="{{ route('financeiro.movimentacoes.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'movimentacoes') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-exchange-alt mr-1"></i> Movimentações</a>
    <a href="{{ route('financeiro.lancamentos.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'lancamentos') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-file-invoice-dollar mr-1"></i> Lançamentos</a>
    <a href="{{ route('financeiro.contas.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'contas') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-university mr-1"></i> Contas</a>
    <a href="{{ route('financeiro.orcamento.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'orcamento') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-chart-bar mr-1"></i> Orçamento</a>
    <a href="{{ route('financeiro.pessoas.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'pessoas') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-users mr-1"></i> Contatos</a>
</div>

{{-- Cabeçalho + seletor de conta --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-black text-gray-800">Conciliação Bancária</h1>
        <p class="text-sm text-gray-400 mt-0.5">Confronte movimentações registradas com lançamentos pendentes.</p>
    </div>
    <form method="GET" action="{{ route('financeiro.conciliacao.index') }}" class="flex items-center gap-2 flex-wrap">
        <select name="conta_bancaria_id" onchange="this.form.submit()"
                class="border border-gray-200 rounded-xl px-4 py-2 text-sm text-gray-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none min-w-[200px]">
            <option value="">Selecione uma conta...</option>
            @foreach ($contas as $c)
                <option value="{{ $c->id }}" {{ $contaSelecionada?->id == $c->id ? 'selected' : '' }}>
                    {{ $c->nome }} — {{ ucfirst($c->tipo) }}
                </option>
            @endforeach
        </select>
        @if ($contaSelecionada)
            <select name="tipo_lancamento" onchange="this.form.submit()"
                    class="border border-gray-200 rounded-xl px-4 py-2 text-sm text-gray-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                <option value="">Todos os tipos</option>
                <option value="receita" {{ request('tipo_lancamento') === 'receita' ? 'selected' : '' }}>Receitas</option>
                <option value="despesa" {{ request('tipo_lancamento') === 'despesa' ? 'selected' : '' }}>Despesas</option>
            </select>
        @endif
    </form>
</div>

@if (!$contaSelecionada)
    {{-- Estado vazio --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 py-24 text-center text-gray-400">
        <i class="fas fa-balance-scale text-5xl text-gray-200 mb-4 block"></i>
        <p class="text-lg font-semibold text-gray-500">Selecione uma conta bancária para iniciar a conciliação</p>
        <p class="text-sm mt-1">O extrato e os lançamentos pendentes serão exibidos lado a lado.</p>
    </div>

@else
    {{-- Info da conta selecionada --}}
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-5 mb-6 text-white flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center">
                <i class="fas fa-university text-xl"></i>
            </div>
            <div>
                <p class="font-black text-lg">{{ $contaSelecionada->nome }}</p>
                <p class="text-indigo-200 text-sm">{{ ucfirst($contaSelecionada->tipo) }} · Saldo Inicial: R$ {{ number_format($contaSelecionada->saldo_inicial, 2, ',', '.') }}</p>
            </div>
        </div>
        <div class="text-right">
            <p class="text-indigo-200 text-xs uppercase tracking-wider">Saldo Atual</p>
            @php $saldo = $contaSelecionada->saldo_atual; @endphp
            <p class="text-2xl font-black {{ $saldo >= 0 ? 'text-white' : 'text-red-300' }}">
                R$ {{ number_format($saldo, 2, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Layout dividido --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-data="conciliacao()">

        {{-- ===== LADO ESQUERDO: EXTRATO ===== --}}
        <div class="flex flex-col">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col flex-1">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                    <h2 class="font-bold text-gray-700 flex items-center gap-2">
                        <i class="fas fa-list-alt text-indigo-400"></i>
                        Extrato — Movimentações Registradas
                    </h2>
                    <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-full">
                        {{ $movimentacoes->count() }} registros
                    </span>
                </div>

                @if ($movimentacoes->isEmpty())
                    <div class="flex-1 py-16 text-center text-gray-400">
                        <i class="fas fa-inbox text-3xl text-gray-200 mb-2 block"></i>
                        <p class="text-sm">Nenhuma movimentação nesta conta.</p>
                    </div>
                @else
                    <ul class="divide-y divide-gray-50 flex-1">
                        @foreach ($movimentacoes as $mov)
                            @php
                                $isReceita = $mov->lancamento?->tipo === 'receita';
                                $jaVinculado = $mov->lancamento_id !== null;
                            @endphp
                            <li class="px-5 py-3.5 hover:bg-gray-50/80 transition"
                                :class="movSelecionada === {{ $mov->id }} ? 'bg-indigo-50 border-l-4 border-indigo-500' : ''">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center
                                                    {{ $isReceita ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                                            <i class="fas {{ $isReceita ? 'fa-arrow-down' : 'fa-arrow-up' }} text-sm"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-800 truncate">
                                                {{ $mov->lancamento?->descricao ?? 'Movimentação #'.$mov->id }}
                                            </p>
                                            <p class="text-xs text-gray-400">
                                                {{ $mov->lancamento?->pessoa?->nome ?? '—' }} ·
                                                {{ \Carbon\Carbon::parse($mov->data_pagamento)->format('d/m/Y') }} ·
                                                <span class="capitalize">{{ str_replace('_', ' ', $mov->forma_pagamento) }}</span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-sm font-black {{ $isReceita ? 'text-green-700' : 'text-red-700' }}">
                                            R$ {{ number_format($mov->valor_pago, 2, ',', '.') }}
                                        </p>
                                        <button
                                            @click="selecionarMovimentacao({{ $mov->id }})"
                                            :class="movSelecionada === {{ $mov->id }}
                                                ? 'bg-indigo-600 text-white'
                                                : 'bg-gray-100 text-gray-500 hover:bg-indigo-100 hover:text-indigo-600'"
                                            class="mt-1 text-xs px-2.5 py-1 rounded-lg font-semibold transition">
                                            <span x-show="movSelecionada !== {{ $mov->id }}">Selecionar</span>
                                            <span x-show="movSelecionada === {{ $mov->id }}" x-cloak>✓ Selecionada</span>
                                        </button>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- ===== LADO DIREITO: LANÇAMENTOS PENDENTES ===== --}}
        <div class="flex flex-col">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col flex-1">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                    <h2 class="font-bold text-gray-700 flex items-center gap-2">
                        <i class="fas fa-file-invoice-dollar text-orange-400"></i>
                        Lançamentos Pendentes
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-full">
                            {{ $lancamentosPendentes->count() }} lançamentos
                        </span>
                        <div x-show="movSelecionada" x-cloak
                             class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full font-semibold animate-pulse">
                            Escolha um para vincular
                        </div>
                    </div>
                </div>

                {{-- Aviso quando nenhuma movimentação selecionada --}}
                <div x-show="!movSelecionada" x-cloak
                     class="mx-5 mt-4 mb-2 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-xs text-amber-700 flex items-center gap-2">
                    <i class="fas fa-info-circle flex-shrink-0"></i>
                    <span>Selecione uma movimentação no extrato (esquerda) para habilitá o vínculo.</span>
                </div>

                @if ($lancamentosPendentes->isEmpty())
                    <div class="flex-1 py-16 text-center text-gray-400">
                        <i class="fas fa-check-circle text-3xl text-green-200 mb-2 block"></i>
                        <p class="text-sm">Nenhum lançamento pendente encontrado.</p>
                    </div>
                @else
                    <ul class="divide-y divide-gray-50 flex-1">
                        @foreach ($lancamentosPendentes as $lanc)
                            @php
                                $venc  = \Carbon\Carbon::parse($lanc->data_vencimento);
                                $atras = $venc->isPast() && !$venc->isToday();
                            @endphp
                            <li class="px-5 py-3.5 hover:bg-gray-50/80 transition">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center
                                                    {{ $lanc->tipo === 'receita' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                                            <i class="fas {{ $lanc->tipo === 'receita' ? 'fa-arrow-down' : 'fa-arrow-up' }} text-sm"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-800 truncate">
                                                {{ $lanc->descricao ?? ($lanc->classificacaoFinanceira?->nome ?? '—') }}
                                            </p>
                                            <p class="text-xs {{ $atras ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                                                {{ $lanc->pessoa?->nome ?? '—' }} ·
                                                Venc. {{ $venc->format('d/m/Y') }}
                                                @if ($atras) · <i class="fas fa-exclamation-circle"></i> Atrasado @endif
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-sm font-black {{ $lanc->tipo === 'receita' ? 'text-green-700' : 'text-red-700' }}">
                                            R$ {{ number_format($lanc->valor_total, 2, ',', '.') }}
                                        </p>
                                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                            {{ $lanc->status === 'pago_parcial' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $lanc->status === 'pago_parcial' ? 'Parcial' : 'Pendente' }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Botão vincular --}}
                                <div class="mt-2" x-show="movSelecionada" x-cloak>
                                    <button @click="vincular({{ $lanc->id }})"
                                            :disabled="vinculando"
                                            class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-2">
                                        <i class="fas fa-link"></i>
                                        <span x-show="!vinculando">Vincular esta movimentação a este lançamento</span>
                                        <span x-show="vinculando" x-cloak><i class="fas fa-spinner fa-spin mr-1"></i> Vinculando...</span>
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

    </div>{{-- /grid --}}

    {{-- Legenda --}}
    <div class="mt-5 flex flex-wrap gap-5 text-xs text-gray-500">
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded-md bg-indigo-50 border-l-4 border-indigo-500"></div>
            <span>Movimentação selecionada para vincular</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded-md bg-green-100 flex items-center justify-center">
                <i class="fas fa-arrow-down text-green-600" style="font-size:9px"></i>
            </div>
            <span>Entrada (Receita)</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded-md bg-red-100 flex items-center justify-center">
                <i class="fas fa-arrow-up text-red-600" style="font-size:9px"></i>
            </div>
            <span>Saída (Despesa)</span>
        </div>
    </div>

@endif {{-- /contaSelecionada --}}

@endsection

@push('scripts')
<style>[x-cloak] { display: none !important; }</style>
<script>
function conciliacao() {
    return {
        movSelecionada: null,
        vinculando: false,

        selecionarMovimentacao(id) {
            this.movSelecionada = (this.movSelecionada === id) ? null : id;
        },

        async vincular(lancamentoId) {
            if (!this.movSelecionada) return;
            if (!confirm('Vincular esta movimentação ao lançamento selecionado?')) return;

            this.vinculando = true;
            try {
                const res = await fetch('{{ route('financeiro.conciliacao.vincular') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        movimentacao_id: this.movSelecionada,
                        lancamento_id:   lancamentoId
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    // Feedback visual antes de recarregar
                    this.movSelecionada = null;
                    location.reload();
                } else {
                    let msg = data.message || 'Erro ao vincular.';
                    if (data.errors) {
                        msg = Object.values(data.errors).flat().join('\n');
                    }
                    alert(msg);
                }
            } catch(e) {
                alert('Erro de comunicação.');
            }
            this.vinculando = false;
        }
    }
}
</script>
@endpush

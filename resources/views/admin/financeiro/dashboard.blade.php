@extends('layouts.app')

@section('title', 'Financeiro')
@section('brand_route', 'financeiro.dashboard')
@section('brand_icon', 'fas fa-chart-line')

@section('content')

{{-- ===== SUB-NAV FINANCEIRO ===== --}}
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

{{-- ===== KPI CARDS - CONTAS BANCÁRIAS ===== --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    @foreach ($contas as $conta)
        @php
            $saldo = $conta->saldo_atual;
            $isPositivo = $saldo >= 0;
            $iconMap = [
                'corrente' => 'fas fa-university',
                'poupanca' => 'fas fa-piggy-bank',
                'caixa'    => 'fas fa-cash-register',
                'gateway'  => 'fas fa-credit-card',
            ];
            $colorMap = [
                'corrente' => 'indigo',
                'poupanca' => 'green',
                'caixa'    => 'yellow',
                'gateway'  => 'purple',
            ];
            $cor = $colorMap[$conta->tipo] ?? 'gray';
        @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col gap-2 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-xl bg-{{ $cor }}-100 flex items-center justify-center">
                    <i class="{{ $iconMap[$conta->tipo] ?? 'fas fa-wallet' }} text-{{ $cor }}-600 text-sm"></i>
                </div>
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ ucfirst($conta->tipo) }}</span>
            </div>
            <div class="mt-1">
                <p class="text-xs text-gray-400 truncate">{{ $conta->nome }}</p>
                <p class="text-2xl font-black {{ $isPositivo ? 'text-gray-800' : 'text-red-600' }} mt-0.5">
                    R$ {{ number_format($saldo, 2, ',', '.') }}
                </p>
            </div>
            <div class="flex items-center justify-between mt-1">
                <span class="text-xs text-gray-400">Saldo inicial: R$ {{ number_format($conta->saldo_inicial, 2, ',', '.') }}</span>
                <a href="{{ route('financeiro.contas.extrato', $conta) }}"
                   class="text-xs text-{{ $cor }}-600 hover:underline font-medium">
                    Extrato <i class="fas fa-arrow-right ml-0.5 text-xs"></i>
                </a>
            </div>
        </div>
    @endforeach

    {{-- Card Saldo Total --}}
    @php
        $saldoTotal = $contas->sum('saldo_atual');
    @endphp
    <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl shadow-md p-5 flex flex-col gap-2 text-white">
        <div class="flex items-center justify-between">
            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                <i class="fas fa-coins text-white text-sm"></i>
            </div>
            <span class="text-xs font-medium text-indigo-200 uppercase tracking-wider">Total Geral</span>
        </div>
        <div class="mt-1">
            <p class="text-xs text-indigo-200">Saldo consolidado</p>
            <p class="text-2xl font-black mt-0.5">R$ {{ number_format($saldoTotal, 2, ',', '.') }}</p>
        </div>
        <div class="flex gap-3 mt-1 text-xs text-indigo-200">
            <span><i class="fas fa-arrow-up mr-0.5"></i> Receitas: R$ {{ number_format($totalReceitasMes, 2, ',', '.') }}</span>
        </div>
    </div>
</div>

{{-- ===== RESUMO DO MÊS ===== --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-arrow-down text-green-600"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wider">Receitas do Mês</p>
            <p class="text-xl font-black text-green-700">R$ {{ number_format($totalReceitasMes, 2, ',', '.') }}</p>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-arrow-up text-red-600"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wider">Despesas do Mês</p>
            <p class="text-xl font-black text-red-700">R$ {{ number_format($totalDespesasMes, 2, ',', '.') }}</p>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-exclamation-triangle text-orange-600"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wider">Em Atraso</p>
            <p class="text-xl font-black text-orange-700">{{ $totalAtrasados }} lançamento(s)</p>
            @if ($totalAtrasados > 0)
                <a href="{{ route('financeiro.lancamentos.index', ['aba' => 'atrasados']) }}"
                   class="text-xs text-orange-600 hover:underline">Ver todos</a>
            @endif
        </div>
    </div>
</div>

{{-- ===== A PAGAR / A RECEBER HOJE ===== --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- A PAGAR HOJE --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-bold text-gray-700 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                A Pagar Hoje
                <span class="ml-1 text-xs bg-red-100 text-red-700 font-semibold px-2 py-0.5 rounded-full">
                    {{ $aPagarHoje->count() }}
                </span>
            </h2>
            <a href="{{ route('financeiro.lancamentos.index', ['aba' => 'pagar']) }}"
               class="text-xs text-indigo-600 hover:underline font-medium">Ver todos</a>
        </div>

        @if ($aPagarHoje->isEmpty())
            <div class="py-10 text-center text-gray-400">
                <i class="fas fa-check-circle text-3xl text-green-300 mb-2"></i>
                <p class="text-sm">Nada a pagar hoje!</p>
            </div>
        @else
            <ul class="divide-y divide-gray-50">
                @foreach ($aPagarHoje as $l)
                    <li class="px-5 py-3 flex items-center justify-between hover:bg-gray-50 transition">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">
                                {{ $l->descricao ?? ($l->classificacaoFinanceira->nome ?? '—') }}
                            </p>
                            <p class="text-xs text-gray-400 truncate">{{ $l->pessoa->nome ?? '—' }}</p>
                        </div>
                        <div class="ml-4 flex items-center gap-3 flex-shrink-0">
                            <span class="text-sm font-black text-red-600">
                                R$ {{ number_format($l->valor_total, 2, ',', '.') }}
                            </span>
                            <a href="{{ route('financeiro.lancamentos.index') }}#lancamento-{{ $l->id }}"
                               class="text-xs bg-red-50 text-red-600 border border-red-200 px-2 py-1 rounded-lg hover:bg-red-100 transition font-medium">
                                Pagar
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- A RECEBER HOJE --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-bold text-gray-700 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                A Receber Hoje
                <span class="ml-1 text-xs bg-green-100 text-green-700 font-semibold px-2 py-0.5 rounded-full">
                    {{ $aReceberHoje->count() }}
                </span>
            </h2>
            <a href="{{ route('financeiro.lancamentos.index', ['aba' => 'receber']) }}"
               class="text-xs text-indigo-600 hover:underline font-medium">Ver todos</a>
        </div>

        @if ($aReceberHoje->isEmpty())
            <div class="py-10 text-center text-gray-400">
                <i class="fas fa-calendar-check text-3xl text-gray-200 mb-2"></i>
                <p class="text-sm">Nenhum recebimento previsto para hoje.</p>
            </div>
        @else
            <ul class="divide-y divide-gray-50">
                @foreach ($aReceberHoje as $l)
                    <li class="px-5 py-3 flex items-center justify-between hover:bg-gray-50 transition">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">
                                {{ $l->descricao ?? ($l->classificacaoFinanceira->nome ?? '—') }}
                            </p>
                            <p class="text-xs text-gray-400 truncate">{{ $l->pessoa->nome ?? '—' }}</p>
                        </div>
                        <div class="ml-4 flex items-center gap-3 flex-shrink-0">
                            <span class="text-sm font-black text-green-600">
                                R$ {{ number_format($l->valor_total, 2, ',', '.') }}
                            </span>
                            <a href="{{ route('financeiro.lancamentos.index') }}#lancamento-{{ $l->id }}"
                               class="text-xs bg-green-50 text-green-600 border border-green-200 px-2 py-1 rounded-lg hover:bg-green-100 transition font-medium">
                                Receber
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

{{-- ===== AÇÃO RÁPIDA ===== --}}
<div class="mt-6 flex flex-wrap gap-3">
    <a href="{{ route('financeiro.lancamentos.index') }}?modal=novo&tipo=despesa"
       class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm transition">
        <i class="fas fa-plus"></i> Nova Despesa
    </a>
    <a href="{{ route('financeiro.lancamentos.index') }}?modal=novo&tipo=receita"
       class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm transition">
        <i class="fas fa-plus"></i> Nova Receita
    </a>
    <a href="{{ route('financeiro.contas.index') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm transition">
        <i class="fas fa-university"></i> Gerenciar Contas
    </a>
    <a href="{{ route('financeiro.orcamento.index') }}"
       class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm transition">
        <i class="fas fa-chart-bar"></i> Ver Orçamento
    </a>
    <a href="{{ route('financeiro.pessoas.index') }}"
       class="inline-flex items-center gap-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm transition">
        <i class="fas fa-users"></i> Gerenciar Contatos
    </a>
</div>

@endsection

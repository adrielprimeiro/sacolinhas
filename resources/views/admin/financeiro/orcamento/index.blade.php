@extends('layouts.app')

@section('title', 'Orçamento')
@section('brand_route', 'financeiro.dashboard')
@section('brand_icon', 'fas fa-chart-line')

@section('content')

{{-- ===== SUB-NAV FINANCEIRO ===== --}}
@php
$currentRoute = Route::currentRouteName();

$hoje = \Carbon\Carbon::today();
$inicioMes = $periodo->copy()->startOfMonth();
$fimMes = $periodo->copy()->endOfMonth();

if ($hoje->gt($fimMes)) {
    $pctMesPassou = 100;
} elseif ($hoje->lt($inicioMes)) {
    $pctMesPassou = 0;
} else {
    $totalDias = $inicioMes->daysInMonth;
    $diasPassados = $hoje->day;
    $pctMesPassou = round(($diasPassados / $totalDias) * 100);
}
@endphp
<div class="flex flex-wrap gap-1 mb-6 border-b border-gray-200 pb-3">
    <a href="{{ route('financeiro.dashboard') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'dashboard') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-home mr-1"></i> Dashboard
    </a>
    <a href="{{ route('financeiro.conciliacao.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'conciliacao') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-balance-scale mr-1"></i> Conciliação
    </a>
    <a href="{{ route('financeiro.movimentacoes.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'movimentacoes') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-exchange-alt mr-1"></i> Movimentações
    </a>
    <a href="{{ route('financeiro.lancamentos.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'lancamentos') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-file-invoice-dollar mr-1"></i> Lançamentos
    </a>
    <a href="{{ route('financeiro.contas.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'contas') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-university mr-1"></i> Contas
    </a>
    <a href="{{ route('financeiro.orcamento.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'orcamento') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-chart-bar mr-1"></i> Orçamento
    </a>
    <a href="{{ route('financeiro.pessoas.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'pessoas') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-users mr-1"></i> Contatos
    </a>
</div>

{{-- ===== CABEÇALHO + SELETOR DE MÊS ===== --}}
<div x-data="{ isEditing: false }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black text-gray-800">Orçamento Mensal</h1>
            <p class="text-sm text-gray-400 mt-0.5">
                Previsto × Realizado — clique em "Editar" para ajustar os previstos.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            {{-- Botão de Edição --}}
            <button 
                @click="isEditing = !isEditing" 
                class="px-4 py-2 rounded-xl text-sm font-bold border transition shadow-sm flex items-center gap-2"
                :class="isEditing ? 'bg-indigo-600 text-white border-indigo-600 hover:bg-indigo-700 shadow-md' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'"
            >
                <i class="fas" :class="isEditing ? 'fa-eye' : 'fa-edit'"></i>
                <span x-text="isEditing ? 'Visualizar' : 'Editar'"></span>
            </button>

            {{-- Botão de Copiar / Transportar para Próximo Mês --}}
            <form method="POST" action="{{ route('financeiro.orcamento.replicar') }}" class="inline" onsubmit="return confirm('Deseja copiar todos os valores previstos deste mês ({{ $periodo->translatedFormat('F/Y') }}) para o próximo mês ({{ $periodo->copy()->addMonth()->translatedFormat('F/Y') }})? Os valores existentes no próximo mês serão sobrescritos.')">
                @csrf
                <input type="hidden" name="periodo_origem" value="{{ $periodo->format('Y-m') }}">
                <button type="submit" class="px-4 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl text-sm font-bold transition flex items-center gap-2 shadow-sm border border-indigo-100">
                    <i class="fas fa-copy"></i>
                    <span>Transportar p/ Próx. Mês</span>
                </button>
            </form>

            <form method="GET" action="{{ route('financeiro.orcamento.index') }}" class="flex items-center gap-2">
                <label class="text-sm text-gray-500 font-medium">Mês:</label>
                <input type="month"
                       name="periodo"
                       value="{{ $periodo->format('Y-m') }}"
                       class="border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none"
                       onchange="this.form.submit()">
            </form>
        </div>
    </div>

    {{-- ===== KPI RESUMO ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @php
            $saldoPrevistaReceita  = $totalPrevistaReceita  - $totalRealizadaReceita;
            $saldoPrevistoDespesa  = $totalPrevistoDespesa  - $totalRealizadoDespesa;
            $resultadoPrevisto     = $totalPrevistaReceita  - $totalPrevistoDespesa;
            $resultadoRealizado    = $totalRealizadaReceita - $totalRealizadoDespesa;
        @endphp
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Receita Prevista</p>
            <p class="text-lg font-black text-green-700">R$ {{ number_format($totalPrevistaReceita, 2, ',', '.') }}</p>
            <p class="text-xs text-green-600 mt-0.5 font-bold">Realizado: R$ {{ number_format($totalRealizadaReceita, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Despesa Prevista</p>
            <p class="text-lg font-black text-red-700">R$ {{ number_format($totalPrevistoDespesa, 2, ',', '.') }}</p>
            <p class="text-xs text-red-600 mt-0.5 font-bold">Realizado: R$ {{ number_format($totalRealizadoDespesa, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Resultado Previsto</p>
            @php $rp = $resultadoPrevisto; @endphp
            <p class="text-lg font-black {{ $rp >= 0 ? 'text-indigo-700' : 'text-red-700' }}">
                R$ {{ number_format($rp, 2, ',', '.') }}
            </p>
            <p class="text-xs text-gray-400 mt-0.5 font-bold">Receita − Despesa</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Resultado Realizado</p>
            @php $rr = $resultadoRealizado; @endphp
            <p class="text-lg font-black {{ $rr >= 0 ? 'text-green-700' : 'text-red-700' }}">
                R$ {{ number_format($rr, 2, ',', '.') }}
            </p>
            <p class="text-xs {{ $rr >= 0 ? 'text-green-500' : 'text-red-500' }} mt-0.5 font-black uppercase tracking-wider text-[10px]">
                {{ $rr >= 0 ? '✓ Superávit' : '✗ Déficit' }}
            </p>
        </div>
    </div>

    {{-- ===== TABELA RECEITAS E DESPESAS ===== --}}
    @foreach (['receita' => ['label' => 'Receitas', 'color' => 'green', 'tree' => $treeReceitas], 'despesa' => ['label' => 'Despesas', 'color' => 'red', 'tree' => $treeDespesas]] as $tipo => $cfg)
    @php
        $linhas = $cfg['tree'];
        $sumPrevisto = $tipo === 'receita' ? $totalPrevistaReceita : $totalPrevistoDespesa;
        $sumRealizado = $tipo === 'receita' ? $totalRealizadaReceita : $totalRealizadoDespesa;
    @endphp

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        {{-- Header da seção --}}
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between
                    {{ $tipo === 'receita' ? 'bg-green-50' : 'bg-red-50' }}">
            <h2 class="font-bold text-gray-700 flex items-center gap-2">
                <i class="fas {{ $tipo === 'receita' ? 'fa-arrow-down text-green-600' : 'fa-arrow-up text-red-600' }}"></i>
                {{ $cfg['label'] }}
            </h2>
            <span class="text-sm font-black {{ $tipo === 'receita' ? 'text-green-700' : 'text-red-700' }}">
                R$ {{ number_format($sumRealizado, 2, ',', '.') }}
                <span class="text-xs font-normal text-gray-400"> / R$ {{ number_format($sumPrevisto, 2, ',', '.') }} prev.</span>
            </span>
        </div>

        @if ($linhas->isEmpty())
            <div class="py-8 text-center text-gray-400 text-sm">
                Nenhuma categoria de {{ $tipo }} cadastrada no plano de contas.
            </div>
        @else
            {{-- Cabeçalho da tabela --}}
            <div class="grid grid-cols-12 gap-4 pl-1 pr-3 py-3 text-xs font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 bg-gray-50/50">
                <div :class="isEditing ? 'col-span-8' : 'col-span-6'">Categoria</div>
                <div :class="isEditing ? 'col-span-4 text-right' : 'col-span-6'">
                    <span x-show="!isEditing">Diferença / Progresso</span>
                    <span x-show="isEditing" x-cloak>Valor Previsto (Editar)</span>
                </div>
            </div>

            {{-- Linhas --}}
            <div class="divide-y divide-gray-50">
                @foreach ($linhas as $linha)
                    @include('admin.financeiro.orcamento._node', ['node' => $linha, 'nivel' => 0, 'periodo' => $periodo, 'pctMesPassou' => $pctMesPassou])
                @endforeach
            </div>

            {{-- Totalizador da seção --}}
            <div class="grid grid-cols-12 gap-4 pl-1 pr-3 py-4 items-center border-t border-gray-100 font-bold text-gray-700
                        {{ $tipo === 'receita' ? 'bg-green-50/30' : 'bg-red-50/30' }}">
                <div :class="isEditing ? 'col-span-8' : 'col-span-6'" class="flex items-center gap-2">
                    <span class="w-5 h-5 flex-shrink-0"></span>
                    <span class="text-xs font-black text-gray-500 uppercase tracking-wider">TOTAL {{ $cfg['label'] }}</span>
                </div>
                <div :class="isEditing ? 'col-span-4 text-right' : 'col-span-6'">
                    {{-- Mode: View --}}
                    <div x-show="!isEditing" class="w-full">
                        @php
                            $sumDif = $sumPrevisto - $sumRealizado;
                            $sumPct = $sumPrevisto > 0 ? round(($sumRealizado / $sumPrevisto) * 100, 1) : 0;
                            
                            $sumPctClamped = min($sumPct, 100);
                            $sumStatus = $tipo === 'despesa' ? ($sumPct >= 100 ? 'danger' : 'warning') : ($sumPct >= 100 ? 'success' : 'info');
                            
                            $barColors = [
                                'success' => 'bg-green-500',
                                'danger'  => 'bg-red-500',
                                'warning' => 'bg-yellow-500',
                                'info'    => 'bg-blue-500',
                            ];
                            $barBg = $barColors[$sumStatus] ?? 'bg-gray-300';
                            $overflow = $sumPct > 100;
                            
                            $difClass = $tipo === 'despesa'
                                ? ($sumDif >= 0 ? 'text-green-600' : 'text-red-600')
                                : ($sumDif <= 0 ? 'text-green-600' : 'text-orange-600');
                        @endphp
                        <div class="flex flex-col w-full">
                            <div class="flex justify-between items-center text-xs font-black text-gray-700 mb-1">
                                <span>Real: R$ {{ number_format($sumRealizado, 2, ',', '.') }}</span>
                                <span>Prev: R$ {{ number_format($sumPrevisto, 2, ',', '.') }}</span>
                            </div>
                            <!-- Barra de Valor Alcançado -->
                            <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden mb-0.5">
                                <div class="h-1.5 rounded-full transition-all duration-500 {{ $barBg }} {{ $overflow ? 'animate-pulse' : '' }}"
                                     style="width: {{ $sumPctClamped }}%">
                                </div>
                            </div>
                            <!-- Barra de Dias Decorridos (Paralela) -->
                            <div class="w-full bg-gray-200 rounded-full overflow-hidden mb-1" style="height: 4px;">
                                <div class="h-full transition-all duration-500"
                                     style="width: {{ $pctMesPassou }}%; background-color: #818cf8;">
                                </div>
                            </div>
                            <div class="text-center text-[10px] text-gray-500 font-bold mt-0.5">
                                {{ $sumPct }}%
                            </div>
                        </div>
                    </div>
                    
                    {{-- Mode: Edit --}}
                    <div x-show="isEditing" x-cloak class="text-sm font-black text-gray-800 pr-3">
                        R$ {{ number_format($sumPrevisto, 2, ',', '.') }}
                    </div>
                </div>
            </div>
        @endif
    </div>
    @endforeach

    {{-- ===== LEGENDA ===== --}}
    <div class="flex flex-wrap gap-4 text-xs text-gray-400 mt-2 mb-8 font-semibold">
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span> Receita em andamento</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> Receita atingida ✓</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-yellow-500 inline-block"></span> Despesa em andamento</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500 inline-block animate-pulse"></span> Despesa estourada ⚠</span>
        <span class="flex items-center gap-1.5"><span class="w-3.5 h-1.5 rounded inline-block shadow-sm" style="background-color: #818cf8;"></span> Tempo decorrido do mês</span>
        <span class="flex items-center gap-1.5"><i class="fas fa-info-circle text-indigo-400"></i> Clique em "Editar" para alterar os valores previstos do orçamento</span>
    </div>
</div>
@endsection

@push('scripts')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

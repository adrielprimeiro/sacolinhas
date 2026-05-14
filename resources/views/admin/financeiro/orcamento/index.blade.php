@extends('layouts.app')

@section('title', 'Orçamento')
@section('brand_route', 'financeiro.dashboard')
@section('brand_icon', 'fas fa-chart-line')

@section('content')

{{-- ===== SUB-NAV FINANCEIRO ===== --}}
@php
$currentRoute = Route::currentRouteName();
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
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-black text-gray-800">Orçamento Mensal</h1>
        <p class="text-sm text-gray-400 mt-0.5">
            Previsto × Realizado — clique no valor previsto para editar.
        </p>
    </div>
    <form method="GET" action="{{ route('financeiro.orcamento.index') }}" class="flex items-center gap-2">
        <label class="text-sm text-gray-500 font-medium">Mês:</label>
        <input type="month"
               name="periodo"
               value="{{ $periodo->format('Y-m') }}"
               class="border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none"
               onchange="this.form.submit()">
    </form>
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
        <p class="text-xs text-green-600 mt-0.5">Realizado: R$ {{ number_format($totalRealizadaReceita, 2, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Despesa Prevista</p>
        <p class="text-lg font-black text-red-700">R$ {{ number_format($totalPrevistoDespesa, 2, ',', '.') }}</p>
        <p class="text-xs text-red-600 mt-0.5">Realizado: R$ {{ number_format($totalRealizadoDespesa, 2, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Resultado Previsto</p>
        @php $rp = $resultadoPrevisto; @endphp
        <p class="text-lg font-black {{ $rp >= 0 ? 'text-indigo-700' : 'text-red-700' }}">
            R$ {{ number_format($rp, 2, ',', '.') }}
        </p>
        <p class="text-xs text-gray-400 mt-0.5">Receita − Despesa</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Resultado Realizado</p>
        @php $rr = $resultadoRealizado; @endphp
        <p class="text-lg font-black {{ $rr >= 0 ? 'text-green-700' : 'text-red-700' }}">
            R$ {{ number_format($rr, 2, ',', '.') }}
        </p>
        <p class="text-xs {{ $rr >= 0 ? 'text-green-500' : 'text-red-500' }} mt-0.5 font-medium">
            {{ $rr >= 0 ? '✓ Superávit' : '✗ Déficit' }}
        </p>
    </div>
</div>

{{-- ===== TABELA RECEITAS ===== --}}
@foreach (['receita' => ['label' => 'Receitas', 'color' => 'green'], 'despesa' => ['label' => 'Despesas', 'color' => 'red']] as $tipo => $cfg)
@php
    $linhas = $classificacoes->filter(fn($c) => $c['tipo_natureza'] === $tipo)->values();
@endphp

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
    {{-- Header da seção --}}
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between
                {{ $tipo === 'receita' ? 'bg-green-50' : 'bg-red-50' }}">
        <h2 class="font-bold text-gray-700 flex items-center gap-2">
            <i class="fas {{ $tipo === 'receita' ? 'fa-arrow-down text-green-600' : 'fa-arrow-up text-red-600' }}"></i>
            {{ $cfg['label'] }}
            <span class="text-xs font-normal text-gray-400 ml-1">{{ $linhas->count() }} categorias</span>
        </h2>
        <span class="text-sm font-black {{ $tipo === 'receita' ? 'text-green-700' : 'text-red-700' }}">
            R$ {{ number_format($linhas->sum('realizado'), 2, ',', '.') }}
            <span class="text-xs font-normal text-gray-400"> / R$ {{ number_format($linhas->sum('previsto'), 2, ',', '.') }} prev.</span>
        </span>
    </div>

    @if ($linhas->isEmpty())
        <div class="py-8 text-center text-gray-400 text-sm">
            Nenhuma categoria de {{ $tipo }} cadastrada no plano de contas.
        </div>
    @else
        {{-- Cabeçalho da tabela --}}
        <div class="grid grid-cols-12 gap-2 px-5 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider border-b border-gray-50 bg-gray-50/50">
            <div class="col-span-4">Categoria</div>
            <div class="col-span-2 text-right">Previsto</div>
            <div class="col-span-2 text-right">Realizado</div>
            <div class="col-span-2 text-right">Diferença</div>
            <div class="col-span-2">Progresso</div>
        </div>

        {{-- Linhas --}}
        @foreach ($linhas as $linha)
        <div class="grid grid-cols-12 gap-2 px-5 py-3 items-center border-b border-gray-50 hover:bg-gray-50/60 transition group"
             x-data="{
                editing: false,
                saving: false,
                saved: false,
                valor: '{{ number_format($linha['previsto'], 2, ',', '.') }}',
                valorOriginal: '{{ number_format($linha['previsto'], 2, ',', '.') }}',
                async salvar() {
                    this.saving = true;
                    const valorFloat = parseFloat(this.valor.replace(',', '.'));
                    try {
                        const res = await fetch('{{ route('financeiro.orcamento.upsert') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify({
                                classificacao_financeira_id: {{ $linha['id'] }},
                                periodo: '{{ $periodo->format('Y-m-d') }}',
                                valor_previsto: valorFloat
                            })
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.valorOriginal = this.valor;
                            this.saved = true;
                            setTimeout(() => this.saved = false, 2000);
                        }
                    } catch(e) { alert('Erro ao salvar orçamento.'); }
                    this.saving = false;
                    this.editing = false;
                },
                cancelar() {
                    this.valor = this.valorOriginal;
                    this.editing = false;
                }
             }">

            {{-- Categoria --}}
            <div class="col-span-4">
                <p class="text-sm font-semibold text-gray-800 truncate">{{ $linha['nome'] }}</p>
                @if ($linha['codigo_contabil'])
                    <p class="text-xs text-gray-400 font-mono">{{ $linha['codigo_contabil'] }}</p>
                @endif
            </div>

            {{-- Previsto (editável inline) --}}
            <div class="col-span-2 text-right">
                {{-- Modo visualização --}}
                <div x-show="!editing" class="flex items-center justify-end gap-1">
                    <span class="text-sm font-semibold text-gray-700" x-text="'R$ ' + valor"></span>
                    <button @click="editing = true"
                            class="opacity-0 group-hover:opacity-100 transition ml-1 w-6 h-6 rounded-md bg-indigo-100 text-indigo-500 hover:bg-indigo-200 flex items-center justify-center"
                            title="Editar previsto">
                        <i class="fas fa-pencil-alt text-xs"></i>
                    </button>
                    <span x-show="saved" class="text-green-500 text-xs" x-transition>
                        <i class="fas fa-check"></i>
                    </span>
                </div>

                {{-- Modo edição --}}
                <div x-show="editing" class="flex items-center justify-end gap-1" x-cloak>
                    <input type="number"
                           x-model="valor"
                           step="0.01" min="0"
                           @keydown.enter="salvar()"
                           @keydown.escape="cancelar()"
                           @focus="$event.target.select()"
                           x-ref="inputPrevisto"
                           x-init="$watch('editing', v => v && $nextTick(() => $refs.inputPrevisto.focus()))"
                           class="w-24 text-right text-sm border border-indigo-300 rounded-lg px-2 py-1 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    <button @click="salvar()"
                            :disabled="saving"
                            class="w-7 h-7 rounded-lg bg-green-100 text-green-600 hover:bg-green-200 flex items-center justify-center transition">
                        <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-check'" style="font-size:11px"></i>
                    </button>
                    <button @click="cancelar()"
                            class="w-7 h-7 rounded-lg bg-gray-100 text-gray-500 hover:bg-gray-200 flex items-center justify-center transition">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>

            {{-- Realizado --}}
            <div class="col-span-2 text-right">
                <span class="text-sm font-semibold
                    {{ $linha['realizado'] > 0 ? ($tipo === 'receita' ? 'text-green-700' : 'text-red-700') : 'text-gray-400' }}">
                    R$ {{ number_format($linha['realizado'], 2, ',', '.') }}
                </span>
            </div>

            {{-- Diferença --}}
            <div class="col-span-2 text-right">
                @php
                    $dif = $linha['diferenca'];
                    // Para receita: positivo = bom (ficou abaixo do previsto = não recebeu); negativo = superou!
                    // Para despesa: positivo = bom (gastou menos); negativo = estourou
                    $difClass = $tipo === 'despesa'
                        ? ($dif >= 0 ? 'text-green-700' : 'text-red-700')
                        : ($dif <= 0 ? 'text-green-700' : 'text-orange-600');
                @endphp
                <span class="text-sm font-bold {{ $difClass }}">
                    {{ $dif >= 0 ? '' : '' }}R$ {{ number_format(abs($dif), 2, ',', '.') }}
                    <span class="text-xs font-normal">{{ $dif >= 0 ? '▼' : '▲' }}</span>
                </span>
            </div>

            {{-- Barra de progresso --}}
            <div class="col-span-2">
                @php
                    $pct = min($linha['percentual'], 200); // cap visual em 200%
                    $pctDisplay = $linha['percentual'];
                    $status = $linha['status_barra']; // success, danger, warning, info
                    $barColors = [
                        'success' => 'bg-green-500',
                        'danger'  => 'bg-red-500',
                        'warning' => 'bg-yellow-500',
                        'info'    => 'bg-blue-400',
                    ];
                    $barBg = $barColors[$status] ?? 'bg-gray-300';
                    $overflow = $pctDisplay > 100;
                @endphp
                <div class="flex items-center gap-2">
                    <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full transition-all duration-500 {{ $barBg }} {{ $overflow ? 'animate-pulse' : '' }}"
                             style="width: {{ min($pct, 100) }}%">
                        </div>
                    </div>
                    <span class="text-xs font-bold {{ $overflow ? ($tipo === 'despesa' ? 'text-red-600' : 'text-green-600') : 'text-gray-500' }} w-10 text-right tabular-nums">
                        {{ $pctDisplay }}%
                    </span>
                </div>
                @if ($overflow)
                    <p class="text-xs font-semibold mt-0.5
                               {{ $tipo === 'despesa' ? 'text-red-500' : 'text-green-600' }}">
                        {{ $tipo === 'despesa' ? '⚠ Estourado' : '✓ Superou' }}
                    </p>
                @endif
            </div>
        </div>
        @endforeach

        {{-- Totalizador da seção --}}
        <div class="grid grid-cols-12 gap-2 px-5 py-3 items-center
                    {{ $tipo === 'receita' ? 'bg-green-50' : 'bg-red-50' }}">
            <div class="col-span-4 text-xs font-bold text-gray-500 uppercase tracking-wider">TOTAL {{ $cfg['label'] }}</div>
            <div class="col-span-2 text-right text-sm font-black text-gray-700">
                R$ {{ number_format($linhas->sum('previsto'), 2, ',', '.') }}
            </div>
            <div class="col-span-2 text-right text-sm font-black {{ $tipo === 'receita' ? 'text-green-700' : 'text-red-700' }}">
                R$ {{ number_format($linhas->sum('realizado'), 2, ',', '.') }}
            </div>
            <div class="col-span-2 text-right text-sm font-black text-gray-600">
                @php $difTotal = $linhas->sum('previsto') - $linhas->sum('realizado'); @endphp
                R$ {{ number_format(abs($difTotal), 2, ',', '.') }}
                {{ $difTotal >= 0 ? '▼' : '▲' }}
            </div>
            <div class="col-span-2">
                @php
                    $pctTotal = $linhas->sum('previsto') > 0
                        ? round(($linhas->sum('realizado') / $linhas->sum('previsto')) * 100, 1)
                        : 0;
                @endphp
                <span class="text-sm font-black text-gray-700">{{ $pctTotal }}%</span>
            </div>
        </div>
    @endif
</div>
@endforeach

{{-- ===== LEGENDA ===== --}}
<div class="flex flex-wrap gap-4 text-xs text-gray-500 mt-2 mb-8">
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-400 inline-block"></span> Receita em andamento</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> Receita superada ✓</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-yellow-500 inline-block"></span> Despesa em andamento</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500 inline-block animate-pulse"></span> Despesa estourada ⚠</span>
    <span class="flex items-center gap-1.5"><i class="fas fa-pencil-alt text-indigo-400"></i> Passe o mouse no Previsto para editar inline</span>
</div>

@endsection

@push('scripts')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

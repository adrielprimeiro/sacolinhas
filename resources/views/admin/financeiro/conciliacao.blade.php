@extends('layouts.app')

@section('title', 'Conciliação Financeira')
@section('brand_route', 'financeiro.dashboard')
@section('brand_icon', 'fas fa-balance-scale')

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

<div x-data="conciliacaoApp()">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-black text-gray-800">Conciliação Financeira</h2>
        <div class="flex gap-2">
            <button @click="showModalOfx = true" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-xl text-sm font-bold hover:bg-gray-50 transition shadow-sm">
                <i class="fas fa-file-upload mr-2"></i>Importar Extrato (OFX/CSV)
            </button>
            <button @click="showModalMp = true" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-indigo-700 transition shadow-md">
                <i class="fab fa-amazon-pay mr-2"></i>Sincronizar Mercado Pago
            </button>
            <button @click="showModalInter = true" class="text-white px-4 py-2 rounded-xl text-sm font-bold shadow-md transition hover:opacity-90 flex items-center gap-2" style="background-color: #FF6200;">
                <i class="fas fa-university"></i>Sincronizar Banco Inter
            </button>
        </div>
    </div>

    <!-- Alertas globais gerenciados pelo layout -->

    <div class="space-y-6 mb-24">
        @forelse($extratoComSugestoes as $item)
            @php
                $t = $item['transacao'];
                $sugestoes = $item['sugestoes'];
                $isEntrada = $t->tipo === 'entrada';
            @endphp
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition duration-200">
                {{-- Transaction Header Row --}}
                <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/70 border-b border-gray-100">
                    <div class="flex items-start gap-3.5">
                        {{-- Icon Indicator --}}
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 {{ $isEntrada ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                            <i class="fas {{ $isEntrada ? 'fa-arrow-down' : 'fa-arrow-up' }} text-sm"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-bold text-gray-400">{{ $t->data->format('d/m/Y') }}</span>
                                @if($t->origem === 'bancointer')
                                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full" style="background-color: #FFEADB; color: #D15200;">
                                        banco inter
                                    </span>
                                @else
                                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full {{ $t->origem === 'mercadopago' ? 'bg-blue-100 text-blue-800' : 'bg-gray-200 text-gray-700' }}">
                                        {{ $t->origem }}
                                    </span>
                                @endif
                            </div>
                            <h4 class="text-base font-bold text-gray-800 mt-0.5 leading-snug">{{ $t->descricao }}</h4>
                        </div>
                    </div>

                    {{-- Right Area: Value and Action Buttons --}}
                    <div class="flex items-center justify-between md:justify-end gap-6 border-t md:border-t-0 pt-3 md:pt-0 border-gray-100">
                        {{-- Transaction Value --}}
                        <div class="text-left md:text-right">
                            @if($t->valor_taxa > 0)
                                <div class="flex flex-col items-start md:items-end">
                                    <span class="text-[10px] text-gray-400 line-through" title="Valor Bruto">
                                        R$ {{ number_format($t->valor_bruto ?? $t->valor, 2, ',', '.') }}
                                    </span>
                                    <span class="text-[9px] text-red-500 font-bold" title="Taxa Mercado Pago">
                                        - R$ {{ number_format($t->valor_taxa, 2, ',', '.') }}
                                    </span>
                                    <span class="text-lg font-black {{ $isEntrada ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $isEntrada ? '+' : '-' }} R$ {{ number_format($t->valor_liquido ?? $t->valor, 2, ',', '.') }}
                                    </span>
                                </div>
                            @else
                                <span class="text-lg font-black {{ $isEntrada ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $isEntrada ? '+' : '-' }} R$ {{ number_format($t->valor, 2, ',', '.') }}
                                </span>
                            @endif
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center gap-1.5">
                            <button @click='openQuickCreate({{ $t->id }}, {{ json_encode($t->descricao) }}, {{ $t->valor }}, "{{ $t->tipo }}", "{{ $t->origem }}")' 
                                    class="bg-white border border-gray-200 text-indigo-600 hover:bg-indigo-50 p-2.5 rounded-xl transition shadow-sm text-xs font-bold flex items-center gap-1.5"
                                    title="Criar lançamento rápido e conciliar">
                                <i class="fas fa-plus"></i> <span class="hidden sm:inline">Criar Novo</span>
                            </button>
                            <form action="{{ route('financeiro.conciliacao.ignorar', $t->id) }}" method="POST" onsubmit="return confirm('Ignorar esta transação?')">
                                @csrf
                                <button type="submit" 
                                        class="bg-white border border-gray-200 text-red-500 hover:bg-red-50 p-2.5 rounded-xl transition shadow-sm text-xs font-bold flex items-center gap-1.5"
                                        title="Ignorar transação do extrato">
                                    <i class="fas fa-times text-sm"></i> <span class="hidden sm:inline">Ignorar</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Suggestions Section --}}
                <div class="p-5 space-y-4">
                    <div>
                        <h5 class="text-xs font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2 mb-3">
                            <i class="fas fa-magic text-indigo-500"></i> Sugestões do Sistema
                        </h5>
                        
                        @if($sugestoes->isEmpty())
                            <p class="text-xs text-gray-400 italic bg-gray-50/50 p-3 rounded-xl border border-dashed border-gray-200">
                                Nenhuma sugestão automática encontrada. Utilize o seletor manual abaixo para vincular a um lançamento existente.
                            </p>
                        @else
                            <div class="space-y-2.5">
                                @foreach($sugestoes as $s)
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 bg-indigo-50/30 rounded-xl border border-indigo-100/40 hover:bg-indigo-50/50 transition">
                                        <div class="space-y-1.5">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-xs font-bold text-gray-800">{{ $s->descricao }}</span>
                                                <span class="bg-indigo-100 text-indigo-800 text-[9px] font-black px-2 py-0.5 rounded-full">
                                                    Match: {{ $s->score }} pts
                                                </span>
                                                @foreach($s->motivos_match as $motivo)
                                                    @php
                                                        $badgeColor = match($motivo) {
                                                            'Valor exato', 'Valor líquido exato' => 'bg-green-100 text-green-800 border-green-200',
                                                            'Pedido correspondente' => 'bg-blue-100 text-blue-800 border-blue-200',
                                                            'Vencimento hoje', 'Vencimento próximo (até 3 dias)', 'Vencimento próximo (até 7 dias)' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                                            default => 'bg-purple-100 text-purple-800 border-purple-200'
                                                        };
                                                    @endphp
                                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded border {{ $badgeColor }}">
                                                        {{ $motivo }}
                                                    </span>
                                                @endforeach
                                            </div>
                                            <div class="text-[10px] text-gray-400 font-bold space-x-3">
                                                <span><i class="far fa-user mr-1"></i>{{ $s->pessoa->nome ?? 'Sem Contato' }}</span>
                                                <span><i class="far fa-folder mr-1"></i>{{ $s->classificacaoFinanceira->nome ?? 'Sem Categoria' }}</span>
                                                <span><i class="far fa-calendar-alt mr-1"></i>Venc. {{ $s->data_vencimento->format('d/m/Y') }}</span>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between sm:justify-end gap-4 border-t sm:border-t-0 pt-2.5 sm:pt-0 border-indigo-100/40">
                                            <span class="text-sm font-black text-gray-700 sm:text-right">
                                                R$ {{ number_format($s->valor_total, 2, ',', '.') }}
                                            </span>
                                            <form action="{{ route('financeiro.conciliacao.vincular') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="transacao_id" value="{{ $t->id }}">
                                                <input type="hidden" name="lancamento_id" value="{{ $s->id }}">
                                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-xs font-black px-4 py-2 rounded-xl transition shadow-sm flex items-center gap-1.5">
                                                    <i class="fas fa-link"></i> Vincular
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Manual Selection Autocomplete Search --}}
                    <div class="pt-3 border-t border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="w-full md:max-w-3xl" x-data="manualLancamentoSearch('{{ $isEntrada ? 'receita' : 'despesa' }}')">
                            <form action="{{ route('financeiro.conciliacao.vincular') }}" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                @csrf
                                <input type="hidden" name="transacao_id" value="{{ $t->id }}">
                                
                                <div class="relative flex-grow">
                                    <input type="text" 
                                           x-model="search" 
                                           @input.debounce.300ms="buscar()"
                                           @focus="buscar()"
                                           placeholder="Buscar por descrição, valor ou contato..."
                                           class="w-full text-xs border border-gray-200 rounded-xl p-2.5 bg-white font-bold text-gray-700 transition-all focus:ring-2 focus:ring-indigo-400 focus:outline-none"
                                           :class="selected ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : ''"
                                           :readonly="selected"
                                           required>
                                    <input type="hidden" name="lancamento_id" :value="selectedId" required>
                                    
                                    <div x-show="loading" class="absolute right-9 top-3">
                                        <i class="fas fa-spinner fa-spin text-gray-400"></i>
                                    </div>
                                    
                                    <div x-show="results.length > 0" 
                                         class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-auto">
                                        <template x-for="item in results" :key="item.id">
                                            <div @click="selecionar(item)" 
                                                 class="p-3 hover:bg-indigo-50 cursor-pointer border-b border-gray-100 last:border-0">
                                                <div class="font-bold text-xs text-gray-800" x-text="item.text"></div>
                                            </div>
                                        </template>
                                    </div>
                                    
                                    <div x-show="selected" @click="limpar()" class="absolute right-3 top-3 cursor-pointer text-gray-400 hover:text-red-500">
                                        <i class="fas fa-times-circle"></i>
                                    </div>
                                </div>
                                
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-xs font-black px-4 py-2.5 rounded-xl transition shadow-sm flex items-center justify-center gap-1.5" :disabled="!selectedId">
                                    <i class="fas fa-link"></i> Vincular Selecionado
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-12 text-center max-w-lg mx-auto">
                <div class="w-16 h-16 rounded-2xl bg-green-50 text-green-500 flex items-center justify-center mx-auto mb-4 border border-green-100 shadow-sm">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
                <h3 class="text-lg font-black text-gray-800">Tudo Conciliado!</h3>
                <p class="text-sm text-gray-400 mt-1.5 leading-relaxed">Nenhuma transação de extrato pendente para conciliação. Excelente trabalho!</p>
            </div>
        @endforelse
    </div>

    <!-- Modal OFX -->
    <div x-show="showModalOfx" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50" x-cloak>
        <form action="{{ route('financeiro.conciliacao.importar') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl">
            @csrf
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-black text-gray-800">Importar Extrato Bancário</h3>
                <button type="button" @click="showModalOfx = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Arquivo (.ofx ou .csv do Mercado Pago)</label>
                    <input type="file" name="arquivo_ofx" class="w-full text-sm border border-gray-200 rounded-xl p-2" accept=".ofx,.csv" required>
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Conta Bancária Destino</label>
                    <select name="conta_bancaria_id" class="w-full text-sm border border-gray-200 rounded-xl p-2 bg-white">
                        <option value="1">Caixa Principal</option>
                    </select>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2">
                <button type="button" @click="showModalOfx = false" class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-700">Cancelar</button>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-xl text-sm font-black hover:bg-indigo-700 shadow-md">Processar</button>
            </div>
        </form>
    </div>

    <!-- Modal Mercado Pago -->
    <div x-show="showModalMp" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50" x-cloak>
        <form action="{{ route('financeiro.conciliacao.sincronizar-mp') }}" method="POST" class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl">
            @csrf
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-black text-gray-800">Sincronizar Mercado Pago</h3>
                <button type="button" @click="showModalMp = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Início</label>
                        <input type="date" name="start_date" class="w-full text-sm border border-gray-200 rounded-xl p-2" value="{{ date('Y-m-d', strtotime('-7 days')) }}" required>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Fim</label>
                        <input type="date" name="end_date" class="w-full text-sm border border-gray-200 rounded-xl p-2" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2">
                <button type="button" @click="showModalMp = false" class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-700">Cancelar</button>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-xl text-sm font-black hover:bg-indigo-700 shadow-md">Sincronizar Agora</button>
            </div>
        </form>
    </div>

    <!-- Modal Banco Inter -->
    <div x-show="showModalInter" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50" x-cloak>
        <form action="{{ route('financeiro.conciliacao.sincronizar-inter') }}" method="POST" class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl">
            @csrf
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-black text-gray-800">Sincronizar Banco Inter</h3>
                <button type="button" @click="showModalInter = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Início</label>
                        <input type="date" name="start_date" class="w-full text-sm border border-gray-200 rounded-xl p-2" value="{{ date('Y-m-d', strtotime('-7 days')) }}" required>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Fim</label>
                        <input type="date" name="end_date" class="w-full text-sm border border-gray-200 rounded-xl p-2" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2">
                <button type="button" @click="showModalInter = false" class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-700">Cancelar</button>
                <button type="submit" class="text-white px-6 py-2 rounded-xl text-sm font-black shadow-md transition hover:opacity-90" style="background-color: #FF6200;">Sincronizar Agora</button>
            </div>
        </form>
    </div>

    <!-- Modal Quick Create -->
    <div x-show="showModalQuick" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50" x-cloak>
        <form action="{{ route('financeiro.conciliacao.criar-rapido') }}" method="POST" class="bg-white rounded-3xl w-full max-w-lg overflow-visible shadow-2xl">
            @csrf
            <input type="hidden" name="transacao_id" x-model="quickData.id">
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-black text-gray-800">Lançamento Rápido</h3>
                <button type="button" @click="showModalQuick = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Descrição</label>
                    <input type="text" class="w-full text-sm border border-gray-100 bg-gray-50 rounded-xl p-2 font-bold" x-model="quickData.descricao" readonly>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Valor</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-sm font-black text-gray-400">R$</span>
                            <input type="text" class="w-full text-sm border border-gray-100 bg-gray-50 rounded-xl p-2 pl-9 font-black" x-model="quickData.valor" readonly>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Tipo</label>
                        <input type="text" class="w-full text-sm border border-gray-100 bg-gray-50 rounded-xl p-2 font-black uppercase tracking-wider" x-model="quickData.tipo" readonly>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Conta Bancária <span class="text-red-500">*</span></label>
                    <select name="conta_bancaria_id" x-model="quickData.conta_id" class="w-full text-sm border border-gray-200 rounded-xl p-2 bg-white font-bold" required>
                        <option value="">Selecione a conta de destino...</option>
                        @foreach($contas as $conta)
                            <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-data="classificacaoSearch()">
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Classificação Financeira <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" x-model="search" @input.debounce.300ms="buscar()" @focus="buscar()"
                               placeholder="Buscar categoria (Ex: Venda, Aluguel...)"
                               class="w-full text-sm border border-gray-200 rounded-xl p-2 bg-white font-bold transition-all"
                               :class="selected ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : ''" 
                               :readonly="selected"
                               required>
                        <input type="hidden" name="classificacao_financeira_id" :value="selectedId" required>
                        
                        <div x-show="loading" class="absolute right-9 top-3"><i class="fas fa-spinner fa-spin text-gray-400"></i></div>
                        
                        <div x-show="results.length > 0" 
                             class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-auto">
                            <template x-for="item in results" :key="item.id">
                                <div @click="selecionar(item)" class="p-3 hover:bg-indigo-50 cursor-pointer border-b border-gray-100 last:border-0">
                                    <div class="font-bold text-sm text-gray-800" x-text="item.text"></div>
                                </div>
                            </template>
                        </div>
                        <div x-show="selected" @click="limpar()" class="absolute right-3 top-3 cursor-pointer text-gray-400 hover:text-red-500">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>
                <div x-data="pessoaSearch()">
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Pessoa (Opcional)</label>
                    <div class="relative">
                        <input type="text" 
                               x-model="search" 
                               @input.debounce.300ms="buscar()"
                               @focus="buscar()"
                               placeholder="Buscar por nome, CPF ou email..."
                               class="w-full text-sm border border-gray-200 rounded-xl p-2 bg-white font-bold transition-all"
                               :class="selected ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : ''"
                               :readonly="selected">
                        <input type="hidden" name="pessoa_id" :value="selectedId">
                        <div x-show="loading" class="absolute right-9 top-3">
                            <i class="fas fa-spinner fa-spin text-gray-400"></i>
                        </div>
                        <div x-show="results.length > 0" 
                             class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-auto">
                            <template x-for="pessoa in results" :key="pessoa.id">
                                <div @click="selecionar(pessoa)" 
                                     class="p-3 hover:bg-indigo-50 cursor-pointer border-b border-gray-100 last:border-0">
                                    <div class="font-bold text-sm text-gray-800" x-text="pessoa.nome"></div>
                                    <div class="text-xs text-gray-500" x-text="pessoa.info || ''"></div>
                                </div>
                            </template>
                        </div>
                        <div x-show="selected" @click="limpar()" class="absolute right-3 top-3 cursor-pointer text-gray-400 hover:text-red-500">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2">
                <button type="button" @click="showModalQuick = false" class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-700">Cancelar</button>
                <button type="submit" class="bg-green-600 text-white px-8 py-2 rounded-xl text-sm font-black hover:bg-green-700 shadow-md">Salvar e Conciliar</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function conciliacaoApp() {
        return {
            quickData: { id: '', descricao: '', valor: '', tipo: '', conta_id: 1 },
            showModalOfx: false,
            showModalMp: false,
            showModalInter: false,
            showModalQuick: false,

            openQuickCreate(id, desc, valor, tipo, origem) {
                let defaultConta = 1;
                if (origem === 'mercadopago') {
                    defaultConta = 2;
                } else if (origem === 'bancointer') {
                    @php
                        $contaInterId = \App\Models\ContaBancaria::where('nome', 'like', '%Inter%')->first()?->id ?? 1;
                    @endphp
                    defaultConta = {{ $contaInterId }};
                }

                this.quickData = { 
                    id, 
                    descricao: desc, 
                    valor: this.formatMoney(valor), 
                    tipo: tipo === 'entrada' ? 'RECEITA' : 'DESPESA',
                    conta_id: defaultConta
                };
                this.suggestion = null;
                this.showModalQuick = true;

                // Buscar sugestão de pessoa (se existir pedido vinculado)
                fetch(`{{ url('admin/financeiro/conciliacao/get-sugestao-pessoa') }}/${id}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.suggestion = data.pessoa;
                            // Disparar evento para o componente de busca de pessoa
                            window.dispatchEvent(new CustomEvent('pessoa-sugestionada', { detail: data.pessoa }));
                        }
                    });
            },

            formatMoney(v) {
                if (!v) return '0,00';
                return v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }
    }

    function pessoaSearch() {
        return {
            search: '',
            results: [],
            selected: null,
            selectedId: '',
            selectedNome: '',
            loading: false,
            timeout: null,
            init() {
                window.addEventListener('pessoa-sugestionada', (e) => {
                    this.selecionar(e.detail);
                });
            },
            buscar() {
                // Se o que está no campo é exatamente o que foi selecionado, não busca
                if (this.selected && this.search === this.selectedNome) {
                    this.results = [];
                    return;
                }

                if (this.search.length < 2) {
                    this.results = [];
                    return;
                }
                
                this.loading = true;
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => {
                    fetch('{{ route("financeiro.conciliacao.buscar-pessoas") }}?q=' + encodeURIComponent(this.search))
                        .then(r => r.json())
                        .then(data => {
                            this.results = data.map(p => ({
                                id: p.id,
                                nome: p.nome,
                                info: p.documento || p.cpf || ''
                            }));
                            this.loading = false;
                        });
                }, 300);
            },
            selecionar(pessoa) {
                this.selected = pessoa;
                this.selectedId = pessoa.id;
                this.selectedNome = pessoa.nome || pessoa.text;
                this.search = this.selectedNome; // Define o texto no input
                this.results = [];
            },
            limpar() {
                this.selected = null;
                this.selectedId = '';
                this.selectedNome = '';
                this.search = '';
                this.results = [];
            }
        }
    }

    function classificacaoSearch() {
        return {
            search: '',
            results: [],
            selected: null,
            selectedId: '',
            selectedNome: '',
            loading: false,
            buscar() {
                // Se o que está no campo é exatamente o que foi selecionado, não busca
                if (this.selected && this.search === this.selectedNome) {
                    this.results = [];
                    return;
                }

                if (this.search.length < 2) {
                    this.results = [];
                    return;
                }
                
                this.loading = true;
                fetch('{{ route("financeiro.search.classificacoes") }}?q=' + encodeURIComponent(this.search))
                    .then(r => r.json())
                    .then(data => {
                        this.results = data;
                        this.loading = false;
                    });
            },
            selecionar(item) {
                this.selected = item;
                this.selectedId = item.id;
                this.selectedNome = item.text;
                this.search = this.selectedNome; // Define o texto no input
                this.results = [];
            },
            limpar() {
                this.selected = null;
                this.selectedId = '';
                this.selectedNome = '';
                this.search = '';
                this.results = [];
            }
        }
    }

    function manualLancamentoSearch(tipo) {
        return {
            search: '',
            results: [],
            selected: null,
            selectedId: '',
            selectedText: '',
            loading: false,
            timeout: null,
            buscar() {
                if (this.selected && this.search === this.selectedText) {
                    this.results = [];
                    return;
                }
                
                this.loading = true;
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => {
                    fetch('{{ route("financeiro.conciliacao.buscar-lancamentos") }}?tipo=' + tipo + '&q=' + encodeURIComponent(this.search))
                        .then(r => r.json())
                        .then(data => {
                            this.results = data;
                            this.loading = false;
                        })
                        .catch(() => {
                            this.loading = false;
                        });
                }, 300);
            },
            selecionar(item) {
                this.selected = item;
                this.selectedId = item.id;
                this.selectedText = item.text;
                this.search = item.text;
                this.results = [];
            },
            limpar() {
                this.selected = null;
                this.selectedId = '';
                this.selectedText = '';
                this.search = '';
                this.results = [];
            }
        }
    }
</script>
@endpush

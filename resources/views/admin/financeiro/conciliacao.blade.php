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
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r shadow-sm" role="alert">
            <p class="font-bold">Sucesso!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r shadow-sm" role="alert">
            <p class="font-bold">Erro!</p>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-24">
        <!-- Coluna Esquerda: Extrato -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-[600px]">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                <h5 class="font-bold text-gray-700">Extrato (Banco/MP)</h5>
                <span class="bg-gray-200 text-gray-700 text-xs font-bold px-2 py-1 rounded-full">{{ count($extrato) }} pendentes</span>
            </div>
            <div class="overflow-y-auto flex-grow">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-white shadow-sm">
                        <tr>
                            <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Data</th>
                            <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Descrição</th>
                            <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Valor</th>
                            <th class="px-5 py-3 w-20"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($extrato as $t)
                            <tr class="cursor-pointer transition hover:bg-indigo-50 group" 
                                :class="selectedExtrato === {{ $t->id }} ? 'bg-indigo-50 border-l-4 border-indigo-600' : 'border-l-4 border-transparent'"
                                @click='selectExtrato({{ $t->id }}, {{ $t->valor }}, "{{ $t->tipo }}", "{{ $t->getPedidoId() }}")'>
                                <td class="px-5 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $t->data->format('d/m/Y') }}</td>
                                <td class="px-5 py-4">
                                    <div class="text-sm font-bold text-gray-800">{{ $t->descricao }}</div>
                                    <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ $t->origem }}</div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if($t->valor_taxa > 0)
                                        <div class="flex flex-col items-end">
                                            <span class="text-xs text-gray-500 line-through" title="Valor Bruto">
                                                R$ {{ number_format($t->valor_bruto ?? $t->valor, 2, ',', '.') }}
                                            </span>
                                            <span class="text-[10px] text-red-500 font-bold" title="Taxa Mercado Pago">
                                                - R$ {{ number_format($t->valor_taxa, 2, ',', '.') }}
                                            </span>
                                            <span class="text-sm font-black {{ $t->tipo === 'entrada' ? 'text-green-600' : 'text-red-600' }}" title="Valor Líquido">
                                                {{ $t->tipo === 'entrada' ? '+' : '-' }} R$ {{ number_format($t->valor_liquido ?? $t->valor, 2, ',', '.') }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-sm font-black {{ $t->tipo === 'entrada' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $t->tipo === 'entrada' ? '+' : '-' }} R$ {{ number_format($t->valor, 2, ',', '.') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition">
                                        <button @click.stop='openQuickCreate({{ $t->id }}, {{ json_encode($t->descricao) }}, {{ $t->valor }}, "{{ $t->tipo }}", "{{ $t->origem }}")' 
                                                class="p-1.5 text-indigo-600 hover:bg-indigo-100 rounded-lg transition" title="Lançamento Rápido">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <form action="{{ route('financeiro.conciliacao.ignorar', $t->id) }}" method="POST" onsubmit="return confirm('Ignorar esta transação?')">
                                            @csrf
                                            <button type="submit" class="p-1.5 text-red-500 hover:bg-red-100 rounded-lg transition" title="Ignorar">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Coluna Direita: Sistema -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-[600px]">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                <div class="flex items-center justify-between mb-2">
                    <h5 class="font-bold text-gray-700">Lançamentos no Sistema</h5>
                    <span class="bg-gray-200 text-gray-700 text-xs font-bold px-2 py-1 rounded-full">{{ count($lancamentos) }} registros</span>
                </div>
                <div class="relative">
                    <input type="text" x-model="searchSistema" placeholder="Filtrar por pedido ou descrição..." 
                           class="w-full text-xs border border-gray-200 rounded-lg pl-8 pr-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-[10px]"></i>
                    <button x-show="searchSistema" @click="searchSistema = ''" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
            </div>
            <div class="overflow-y-auto flex-grow">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-white shadow-sm">
                        <tr>
                            <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Vencimento</th>
                            <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Descrição / Pessoa</th>
                            <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($lancamentos as $l)
                            <tr x-show='shouldShowSistema({{ json_encode($l->descricao) }}, "{{ $l->referencia_id }}")'
                                class="cursor-pointer transition hover:bg-indigo-50" 
                                :class="selectedSistema === {{ $l->id }} ? 'bg-indigo-50 border-l-4 border-indigo-600' : 'border-l-4 border-transparent'"
                                @click='selectSistema({{ $l->id }}, {{ $l->valor_total }}, "{{ $l->tipo === 'receita' ? 'entrada' : 'saida' }}")'>
                                <td class="px-5 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $l->data_vencimento->format('d/m/Y') }}</td>
                                <td class="px-5 py-4">
                                    <div class="text-sm font-bold text-gray-800">{{ $l->descricao }}</div>
                                    <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ $l->pessoa->nome ?? '---' }}</div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <span class="text-sm font-black text-gray-700">
                                        R$ {{ number_format($l->valor_total, 2, ',', '.') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Barra de Ações Fixa -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 shadow-2xl z-50">
        <div class="container mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-2">
                <template x-if="selectedExtrato">
                    <span class="bg-indigo-100 text-indigo-700 text-xs font-black px-3 py-1.5 rounded-full border border-indigo-200 flex items-center gap-2">
                        <i class="fas fa-file-invoice"></i> EXTRATO SELECIONADO
                    </span>
                </template>
                <template x-if="selectedSistema">
                    <span class="bg-purple-100 text-purple-700 text-xs font-black px-3 py-1.5 rounded-full border border-purple-200 flex items-center gap-2">
                        <i class="fas fa-desktop"></i> SISTEMA SELECIONADO
                    </span>
                </template>
                <template x-if="!selectedExtrato && !selectedSistema">
                    <span class="text-sm font-medium text-gray-400">Selecione um item de cada lado para conciliar</span>
                </template>
            </div>

            <div class="flex items-center gap-6">
                <template x-if="selectedExtrato && selectedSistema">
                    <div class="text-right">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Diferença</p>
                        <p class="text-lg font-black" :class="valorExtrato === valorSistema ? 'text-green-600' : 'text-red-600'">
                            R$ <span x-text="formatMoney(Math.abs(valorExtrato - valorSistema))"></span>
                        </p>
                    </div>
                </template>

                <form action="{{ route('financeiro.conciliacao.vincular') }}" method="POST">
                    @csrf
                    <input type="hidden" name="transacao_id" :value="selectedExtrato">
                    <input type="hidden" name="lancamento_id" :value="selectedSistema">
                    <button type="submit" 
                            class="bg-green-600 text-white px-8 py-3 rounded-2xl font-black text-sm uppercase tracking-wider hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-lg flex items-center gap-2" 
                            :disabled="!canConciliar()">
                        <i class="fas fa-link"></i> Vincular (Match)
                    </button>
                </form>
            </div>
        </div>
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
                <div class="bg-blue-50 border border-blue-100 p-3 rounded-xl flex gap-3">
                    <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                    <p class="text-[11px] text-blue-700 font-medium leading-relaxed">
                        Isso buscará todas as transações aprovadas na conta do Mercado Pago para o período selecionado.
                    </p>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2">
                <button type="button" @click="showModalMp = false" class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-700">Cancelar</button>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-xl text-sm font-black hover:bg-indigo-700 shadow-md">Sincronizar Agora</button>
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
            selectedExtrato: null,
            selectedSistema: null,
            valorExtrato: 0,
            valorSistema: 0,
            tipoExtrato: '',
            tipoSistema: '',
            quickData: { id: '', descricao: '', valor: '', tipo: '', conta_id: 1 },
            showModalOfx: false,
            showModalMp: false,
            showModalQuick: false,
            searchSistema: '',

            selectExtrato(id, valor, tipo, suggestion) {
                if (this.selectedExtrato === id) {
                    this.selectedExtrato = null;
                    this.valorExtrato = 0;
                    this.tipoExtrato = '';
                } else {
                    this.selectedExtrato = id;
                    this.valorExtrato = parseFloat(valor);
                    this.tipoExtrato = tipo;
                    // Se houver uma sugestão (ID do pedido), aplica o filtro automaticamente
                    if (suggestion) {
                        this.searchSistema = suggestion;
                    }
                }
            },

            selectSistema(id, valor, tipo) {
                if (this.selectedSistema === id) {
                    this.selectedSistema = null;
                    this.valorSistema = 0;
                    this.tipoSistema = '';
                } else {
                    this.selectedSistema = id;
                    this.valorSistema = parseFloat(valor);
                    this.tipoSistema = tipo;
                }
            },

            canConciliar() {
                return this.selectedExtrato && this.selectedSistema && (this.tipoExtrato === this.tipoSistema);
            },

            formatMoney(v) {
                return v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            openQuickCreate(id, desc, valor, tipo, origem) {
                this.quickData = { 
                    id, 
                    descricao: desc, 
                    valor: this.formatMoney(valor), 
                    tipo: tipo === 'entrada' ? 'RECEITA' : 'DESPESA',
                    conta_id: origem === 'mercadopago' ? 2 : 1
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

            shouldShowSistema(descricao, refId) {
                if (!this.searchSistema) return true;
                const search = this.searchSistema.toLowerCase();
                return descricao.toLowerCase().includes(search) || 
                       (refId && refId.toString().includes(search));
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
</script>
@endpush

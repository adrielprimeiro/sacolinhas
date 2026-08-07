@extends('layouts.app')

@section('title', 'Painel de Controle do Clube')

@section('content')

{{-- ===== CABEÇALHO ===== --}}
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Painel do Clube 🏰</h1>
        <p class="text-gray-500 text-sm mt-0.5">Gerencie participantes, pontuações e mensalidades.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.clube.desafios.index') }}"
           class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-indigo-200 rounded-xl text-xs font-black text-indigo-600 hover:bg-indigo-50 transition shadow-sm">
            <i class="fas fa-trophy"></i> <span class="hidden sm:inline">Gerenciar </span>Desafios
        </a>
        <a href="{{ route('admin.grupos.index') }}"
           class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-indigo-200 rounded-xl text-xs font-black text-indigo-600 hover:bg-indigo-50 transition shadow-sm">
            <i class="fas fa-users"></i> <span class="hidden sm:inline">Gerenciar </span>Grupos
        </a>
        <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl shadow-sm border border-gray-100">
            <label for="mes_filtro" class="text-xs font-bold text-gray-400 uppercase tracking-wider hidden sm:block">Mês:</label>
            <form action="{{ route('admin.clube.dashboard') }}" method="GET" id="formFiltroMes">
                <input type="month" name="mes" id="mes_filtro"
                       value="{{ $mesAtual }}"
                       onchange="this.form.submit()"
                       class="border-none focus:ring-0 text-gray-700 font-semibold cursor-pointer text-sm">
            </form>
        </div>
    </div>
</div>

{{-- ===== CARDS DE ESTATÍSTICAS ===== --}}
<div class="grid grid-cols-3 gap-3 mb-6">
    <div class="bg-gradient-to-br from-purple-100 to-fuchsia-200 rounded-2xl p-4 border border-purple-200 shadow-sm">
        <div class="flex items-center gap-2 mb-2">
            <div class="p-2 bg-purple-600 rounded-xl text-white shadow-sm text-xs">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="text-2xl sm:text-3xl font-black text-gray-900">{{ number_format($stats['total_membros'], 0, ',', '.') }}</div>
        <p class="text-[10px] sm:text-xs text-purple-700 font-bold mt-0.5 uppercase tracking-wider">Membros</p>
    </div>

    <div class="bg-gradient-to-br from-pink-100 to-rose-200 rounded-2xl p-4 border border-pink-200 shadow-sm">
        <div class="flex items-center gap-2 mb-2">
            <div class="p-2 bg-pink-600 rounded-xl text-white shadow-sm text-xs">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
        </div>
        <div class="text-2xl sm:text-3xl font-black text-gray-900">{{ number_format($stats['pagos_mes'], 0, ',', '.') }}</div>
        <p class="text-[10px] sm:text-xs text-pink-700 font-bold mt-0.5 uppercase tracking-wider">Pagas</p>
    </div>

    <div class="bg-gradient-to-br from-indigo-100 to-blue-200 rounded-2xl p-4 border border-indigo-200 shadow-sm">
        <div class="flex items-center gap-2 mb-2">
            <div class="p-2 bg-blue-600 rounded-xl text-white shadow-sm text-xs">
                <i class="fas fa-star"></i>
            </div>
        </div>
        <div class="text-2xl sm:text-3xl font-black text-gray-900">{{ number_format($stats['total_pontos'], 0, ',', '.') }}</div>
        <p class="text-[10px] sm:text-xs text-blue-700 font-bold mt-0.5 uppercase tracking-wider">Pontos</p>
    </div>
</div>

{{-- ===== BARRA DE BUSCA E FILTROS ===== --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-4 p-4 flex flex-col gap-3">
    {{-- Busca --}}
    <form action="{{ route('admin.clube.dashboard') }}" method="GET" id="formSearch" class="relative">
        <input type="hidden" name="mes" value="{{ $mesAtual }}">
        <input type="hidden" name="sort" value="{{ request('sort', 'nome') }}">
        <input type="hidden" name="order" value="{{ request('order', 'asc') }}">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Buscar participante..."
               class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-400 text-sm font-medium">
        <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
    </form>

    {{-- Filtros de Ordenação - scrollável no mobile --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-1 -mb-1 scrollbar-hide">
        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest flex-shrink-0">Ordenar:</span>
        @php
            $currentSort  = request('sort', 'nome');
            $currentOrder = request('order', 'asc');
            $sorts = [
                'nome'      => ['label' => 'Nome', 'icon' => 'fa-user'],
                'pagamento' => ['label' => 'Pgto', 'icon' => 'fa-money-bill-wave'],
                'grupo'     => ['label' => 'Grupo', 'icon' => 'fa-users'],
                'pontos'    => ['label' => 'Pontos', 'icon' => 'fa-star'],
            ];
        @endphp
        @foreach($sorts as $key => $info)
            @php
                $isActive  = $currentSort === $key;
                $nextOrder = ($isActive && $currentOrder === 'asc') ? 'desc' : 'asc';
                $url = route('admin.clube.dashboard', array_merge(request()->except(['_token']), ['sort' => $key, 'order' => $nextOrder]));
            @endphp
            <a href="{{ $url }}" class="flex-shrink-0 inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-black transition
                   {{ $isActive ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-500' }}">
                <i class="fas {{ $info['icon'] }} text-[10px]"></i>
                {{ $info['label'] }}
                @if($isActive)
                    <i class="fas fa-arrow-{{ $currentOrder === 'asc' ? 'up' : 'down' }} text-[9px]"></i>
                @endif
            </a>
        @endforeach
    </div>
</div>

{{-- ===== LISTA DE PARTICIPANTES (Cards no mobile, tabela no desktop) ===== --}}

{{-- MOBILE: Cards --}}
<div class="flex flex-col gap-3 sm:hidden">
    @forelse($participantes as $p)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        {{-- Linha 1: avatar + nome + pontos --}}
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-black text-lg border-2 border-white shadow-sm flex-shrink-0">
                    {{ substr($p->name, 0, 1) }}
                </div>
                <div>
                    <div class="font-black text-gray-900 text-sm leading-tight">{{ $p->name }}</div>
                    <div class="text-xs text-gray-400 font-medium">{{ $p->apelido ?: ($p->nome_cliente ?: 'Sem apelido') }}</div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-xl font-black text-gray-900">{{ number_format($p->pontos_total ?: 0, 0, ',', '.') }}</div>
                <div class="text-[9px] text-gray-400 font-bold uppercase">pts</div>
            </div>
        </div>

        {{-- Linha 2: status pagamento + grupo --}}
        <div class="flex items-center justify-between mb-3">
            <div>
                        @if($p->status_pagamento === 'pago')
                            <form action="{{ route('admin.clube.pagamento.desfazer') }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja desfazer o pagamento deste cliente? Os pontos desta mensalidade serão retirados.')">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $p->id }}">
                                <input type="hidden" name="mes_ano" value="{{ $mesAtual }}">
                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 hover:bg-red-100 hover:text-red-700 transition" title="Clique para desfazer o pagamento">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Pago
                                </button>
                            </form>
                        @else
                    <button onclick="openModalPagamento({{ $p->id }}, '{{ $p->name }}')"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 active:bg-red-200">
                        <i class="fas fa-exclamation-circle text-[10px]"></i> Pendente
                    </button>
                @endif
            </div>
            <div>
                @if($p->grupo_nome)
                    <button onclick="openModalGrupo({{ $p->id }}, '{{ $p->name }}', {{ $grupos->where('nome', $p->grupo_nome)->first()->id ?? 'null' }})"
                            class="px-2.5 py-1 rounded-lg border border-indigo-100 text-xs font-bold text-indigo-600 bg-indigo-50">
                        <i class="fas fa-users text-[9px] mr-1"></i>{{ $p->grupo_nome }}
                    </button>
                @else
                    <button onclick="openModalGrupo({{ $p->id }}, '{{ $p->name }}', null)"
                            class="text-xs text-gray-400 font-bold border border-dashed border-gray-200 px-2.5 py-1 rounded-lg">
                        <i class="fas fa-plus text-[9px] mr-1"></i> Grupo
                    </button>
                @endif
            </div>
        </div>

        {{-- Linha 3: breakdown pontos + ações --}}
        <div class="flex items-center justify-between">
            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-tight space-x-2">
                <span>Itens: {{ $p->pontos_itens ?: 0 }}</span>
                <span>Retir: {{ $p->pontos_retirados ?: 0 }}</span>
                <span>Mês: {{ $p->pontos_mensalidade ?: 0 }}</span>
                <span>Desaf: {{ $p->pontos_desafios ?: 0 }}</span>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="openModalDesafio({{ $p->id }}, '{{ $p->name }}')"
                        class="w-9 h-9 rounded-xl bg-orange-50 text-orange-500 active:bg-orange-500 active:text-white flex items-center justify-center shadow-sm text-sm">
                    <i class="fas fa-trophy"></i>
                </button>
                <a href="{{ route('admin.clientes.edit', $p->id) }}"
                   class="w-9 h-9 rounded-xl bg-gray-50 text-gray-400 active:bg-indigo-500 active:text-white flex items-center justify-center shadow-sm text-sm">
                    <i class="fas fa-user-edit"></i>
                </a>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400 font-medium">
        Nenhum participante encontrado.
    </div>
    @endforelse
</div>

{{-- DESKTOP: Tabela --}}
<div class="hidden sm:block bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Participante</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-center">Status 💰</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Grupo 👥</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-right">Pontos ⭐</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-center">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($participantes as $p)
                <tr class="hover:bg-gray-50/50 transition duration-150">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold uppercase border-2 border-white shadow-sm">
                                {{ substr($p->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="font-bold text-gray-900 leading-tight">{{ $p->name }}</div>
                                <div class="text-xs text-gray-500 font-medium lowercase">{{ $p->apelido ?: ($p->nome_cliente ?: 'Sem apelido') }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($p->status_pagamento === 'pago')
                            <form action="{{ route('admin.clube.pagamento.desfazer') }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja desfazer o pagamento deste cliente? Os pontos desta mensalidade serão retirados.')">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $p->id }}">
                                <input type="hidden" name="mes_ano" value="{{ $mesAtual }}">
                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 hover:bg-red-100 hover:text-red-700 transition" title="Clique para desfazer o pagamento">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Pago
                                </button>
                            </form>
                        @else
                            <button onclick="openModalPagamento({{ $p->id }}, '{{ $p->name }}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 hover:bg-red-200 transition">
                                <i class="fas fa-exclamation-circle text-[10px]"></i> Pendente
                            </button>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($p->grupo_nome)
                            <button onclick="openModalGrupo({{ $p->id }}, '{{ $p->name }}', {{ $grupos->where('nome', $p->grupo_nome)->first()->id ?? 'null' }})"
                                    class="px-3 py-1 rounded-lg border border-indigo-100 text-xs font-bold text-indigo-600 hover:bg-indigo-50 transition">
                                {{ $p->grupo_nome }}
                            </button>
                        @else
                            <button onclick="openModalGrupo({{ $p->id }}, '{{ $p->name }}', null)"
                                    class="text-xs text-gray-400 font-bold hover:text-indigo-500 transition border-b border-dashed border-gray-200">
                                <i class="fas fa-plus mr-1 text-[10px]"></i> Sem Grupo
                            </button>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="font-black text-gray-900 text-lg">{{ number_format($p->pontos_total ?: 0, 0, ',', '.') }}</div>
                        <div class="text-[10px] text-gray-400 font-bold space-x-2 uppercase tracking-tighter">
                            <span>Itens: {{ number_format($p->pontos_itens ?: 0, 0) }}</span>
                            <span>Retir: {{ number_format($p->pontos_retirados ?: 0, 0) }}</span>
                            <span>Mês: {{ number_format($p->pontos_mensalidade ?: 0, 0) }}</span>
                            <span>Desaf: {{ number_format($p->pontos_desafios ?: 0, 0) }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button onclick="openModalDesafio({{ $p->id }}, '{{ $p->name }}')"
                                    title="Lançar Desafio"
                                    class="w-10 h-10 rounded-xl bg-orange-50 text-orange-500 hover:bg-orange-500 hover:text-white transition flex items-center justify-center shadow-sm">
                                <i class="fas fa-trophy"></i>
                            </button>
                            <a href="{{ route('admin.clientes.edit', $p->id) }}"
                               title="Editar Perfil"
                               class="w-10 h-10 rounded-xl bg-gray-50 text-gray-400 hover:bg-indigo-500 hover:text-white transition flex items-center justify-center shadow-sm">
                                <i class="fas fa-user-edit"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-12 text-center text-gray-400 font-medium">Nenhum cliente encontrado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($participantes->hasPages())
    <div class="p-6 bg-gray-50/50 border-t border-gray-50">
        {{ $participantes->appends(request()->all())->links() }}
    </div>
    @endif
</div>

{{-- Paginação mobile --}}
@if($participantes->hasPages())
<div class="sm:hidden mt-4">
    {{ $participantes->appends(request()->all())->links() }}
</div>
@endif

{{-- ================= MODAIS ================= --}}
{{-- (modais ocupam tela inteira no mobile com rounded-t-3xl na parte de baixo) --}}

<!-- Modal Desafio -->
<div id="modalDesafio" class="fixed inset-0 z-[9999] hidden flex items-end sm:items-center justify-center">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeModals()"></div>
    <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl relative w-full sm:max-w-md animate-bounce-in overflow-hidden">
        <div class="p-5 bg-gradient-to-r from-indigo-50 to-blue-100 text-indigo-900 border-b border-indigo-200">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-trophy text-indigo-500"></i> Lançar Desafio
                </h3>
                <button onclick="closeModals()" class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-400 flex items-center justify-center text-xs hover:bg-indigo-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p id="desafio_user_name" class="text-sm text-indigo-700 mt-0.5 font-bold italic"></p>
        </div>
        <form action="{{ route('admin.clube.desafio.lancar') }}" method="POST" class="p-5 space-y-5">
            @csrf
            <input type="hidden" name="user_id" id="desafio_user_id">
            <input type="hidden" name="mes_ano" value="{{ $mesAtual }}">

            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Desafio</label>
                <div class="relative" id="combobox-wrapper">
                    <div class="relative">
                        <input type="text" id="desafio-search" autocomplete="off"
                               placeholder="Digite para buscar..."
                               class="w-full rounded-2xl border-2 border-gray-200 bg-white focus:border-indigo-400 focus:ring-0 outline-none text-gray-900 font-bold px-4 py-3 pr-10 transition text-base"
                               oninput="filtrarDesafios(this.value)"
                               onfocus="abrirDropdown()">
                        <i class="fas fa-search absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"></i>
                    </div>
                    <div id="desafio-dropdown"
                         class="hidden absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-2xl shadow-xl z-50 overflow-hidden max-h-48 overflow-y-auto">
                        @if($desafiosAtivos->isEmpty())
                            <div class="px-4 py-4 text-sm text-gray-400 text-center font-bold">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                Nenhum desafio ativo.
                                <a href="{{ route('admin.clube.desafios.create') }}" class="text-indigo-500 underline">Criar</a>
                            </div>
                        @else
                            @foreach($desafiosAtivos as $d)
                            <div class="desafio-option px-4 py-3.5 flex items-center justify-between cursor-pointer hover:bg-indigo-50 active:bg-indigo-100 transition border-b border-gray-50 last:border-0"
                                 data-nome="{{ $d->nome }}"
                                 data-pontos="{{ $d->pontos }}"
                                 onclick="escolherDesafio('{{ addslashes($d->nome) }}', {{ $d->pontos }})">
                                <div>
                                    <div class="font-black text-gray-900 text-sm">{{ $d->nome }}</div>
                                    @if($d->descricao)
                                        <div class="text-[11px] text-gray-400 line-clamp-1">{{ $d->descricao }}</div>
                                    @endif
                                </div>
                                <span class="ml-3 flex-shrink-0 inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-indigo-100 text-indigo-700 font-black text-xs">
                                    <i class="fas fa-star text-[9px]"></i> {{ $d->pontos }}
                                </span>
                            </div>
                            @endforeach
                        @endif
                    </div>
                </div>
                <input type="hidden" name="desafio_nome" id="desafio_nome_input">
            </div>

            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">
                    Pontos <span class="text-gray-300 font-normal normal-case text-[10px]">(edite se necessário)</span>
                </label>
                <div class="relative">
                    <input type="number" name="pontos" id="desafio_pontos_input" step="1" min="0" required
                           placeholder="Selecione um desafio acima"
                           class="w-full rounded-2xl border-2 border-gray-200 bg-white focus:border-indigo-400 focus:ring-0 outline-none pl-12 pr-4 py-3 text-gray-900 font-black text-lg transition">
                    <i class="fas fa-star absolute left-4 top-1/2 -translate-y-1/2 text-indigo-400 text-base"></i>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeModals()"
                        class="flex-1 px-4 py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-200 font-black rounded-2xl uppercase text-xs tracking-widest transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-3.5 bg-indigo-600 text-white font-black rounded-2xl uppercase text-xs tracking-widest shadow-lg shadow-indigo-200">
                    <i class="fas fa-trophy mr-1"></i> Lançar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Pagamento -->
<div id="modalPagamento" class="fixed inset-0 z-[9999] hidden flex items-end sm:items-center justify-center">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeModals()"></div>
    <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl relative w-full sm:max-w-md overflow-hidden">
        <div class="p-5 bg-gradient-to-r from-indigo-50 to-blue-100 text-indigo-900 border-b border-indigo-200">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-money-bill-wave text-indigo-500"></i> Registrar Pagamento
                </h3>
                <button onclick="closeModals()" class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-400 flex items-center justify-center text-xs">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p id="pagamento_user_name" class="text-sm text-indigo-700 mt-0.5 font-bold italic"></p>
        </div>
        <form action="{{ route('admin.clube.pagamento.registrar') }}" method="POST" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="user_id" id="pagamento_user_id">
            <div class="grid grid-cols-1 mb-4">
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Competência (Mês de Referência)</label>
                <input type="month" name="mes_ano" value="{{ $mesAtual }}" required
                       class="w-full rounded-2xl border-2 border-gray-200 bg-white focus:border-indigo-400 focus:ring-0 outline-none text-gray-900 font-bold px-4 py-3 text-base">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Valor (R$)</label>
                    <input type="number" name="valor" value="50" step="0.01" required
                           class="w-full rounded-2xl border-2 border-gray-200 bg-white focus:border-indigo-400 focus:ring-0 outline-none text-gray-900 font-bold px-4 py-3 text-base">
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Data do Pagamento</label>
                    <input type="date" name="data_pagamento" value="{{ date('Y-m-d') }}" required
                           class="w-full rounded-2xl border-2 border-gray-200 bg-white focus:border-indigo-400 focus:ring-0 outline-none text-gray-900 font-bold px-4 py-3 text-base">
                </div>
            </div>
            <p class="text-[10px] text-gray-500 font-bold bg-gray-50 p-3 rounded-xl border border-gray-100 italic">
                * Registrar o pagamento dará automaticamente os 100 pontos da mensalidade e bônus para o grupo.
            </p>
            <div class="flex gap-3">
                <button type="button" onclick="closeModals()"
                        class="flex-1 px-4 py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-200 font-black rounded-2xl uppercase text-xs tracking-widest transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-3.5 bg-indigo-600 text-white font-black rounded-2xl uppercase text-xs tracking-widest shadow-lg shadow-indigo-200">
                    <i class="fas fa-check mr-1"></i> Confirmar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Grupo -->
<div id="modalGrupo" class="fixed inset-0 z-[9999] hidden flex items-end sm:items-center justify-center">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeModals()"></div>
    <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl relative w-full sm:max-w-md overflow-hidden">
        <div class="p-5 bg-gradient-to-r from-indigo-50 to-blue-100 text-indigo-900 border-b border-indigo-200">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-users text-indigo-500"></i> Ajustar Grupo
                </h3>
                <button onclick="closeModals()" class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-400 flex items-center justify-center text-xs">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p id="grupo_user_name" class="text-sm text-indigo-700 mt-0.5 font-bold italic"></p>
        </div>
        <form action="{{ route('admin.clube.mudar-grupo') }}" method="POST" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="user_id" id="grupo_user_id">
            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Selecione o Grupo</label>
                <select name="grupo_id"
                        class="w-full rounded-2xl border-2 border-gray-200 bg-white focus:border-indigo-400 focus:ring-0 outline-none text-gray-900 font-bold px-4 py-3 text-base">
                    <option value="">Sem Grupo / Sair do Grupo</option>
                    @foreach($grupos as $g)
                        <option value="{{ $g->id }}">{{ $g->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeModals()"
                        class="flex-1 px-4 py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-200 font-black rounded-2xl uppercase text-xs tracking-widest transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-3.5 bg-indigo-600 text-white font-black rounded-2xl uppercase text-xs tracking-widest shadow-lg shadow-indigo-200">
                    <i class="fas fa-check mr-1"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // ==== COMBOBOX ====
    function abrirDropdown() {
        document.getElementById('desafio-dropdown').classList.remove('hidden');
    }

    function filtrarDesafios(query) {
        abrirDropdown();
        const q = query.toLowerCase().trim();
        document.querySelectorAll('.desafio-option').forEach(opt => {
            opt.style.display = opt.dataset.nome.toLowerCase().includes(q) ? '' : 'none';
        });
    }

    function escolherDesafio(nome, pontos) {
        document.getElementById('desafio-search').value = nome;
        document.getElementById('desafio_nome_input').value = nome;
        document.getElementById('desafio_pontos_input').value = pontos;
        document.getElementById('desafio-dropdown').classList.add('hidden');
    }

    document.addEventListener('click', function(e) {
        const wrapper = document.getElementById('combobox-wrapper');
        if (wrapper && !wrapper.contains(e.target)) {
            const dd = document.getElementById('desafio-dropdown');
            if (dd) dd.classList.add('hidden');
        }
    });

    // ==== MODAIS ====
    function openModalDesafio(id, name) {
        document.getElementById('desafio_user_id').value = id;
        document.getElementById('desafio_user_name').innerText = name;
        const s = document.getElementById('desafio-search');
        const n = document.getElementById('desafio_nome_input');
        const p = document.getElementById('desafio_pontos_input');
        if (s) { s.value = ''; filtrarDesafios(''); }
        if (n) n.value = '';
        if (p) p.value = '';
        document.getElementById('desafio-dropdown').classList.add('hidden');
        document.getElementById('modalDesafio').classList.remove('hidden');
    }

    function openModalPagamento(id, name) {
        document.getElementById('pagamento_user_id').value = id;
        document.getElementById('pagamento_user_name').innerText = name;
        document.getElementById('modalPagamento').classList.remove('hidden');
    }

    function openModalGrupo(id, name, grupoId) {
        document.getElementById('grupo_user_id').value = id;
        document.getElementById('grupo_user_name').innerText = name;
        const select = document.querySelector('#modalGrupo select');
        select.value = grupoId || '';
        document.getElementById('modalGrupo').classList.remove('hidden');
    }

    function closeModals() {
        document.querySelectorAll('#modalDesafio, #modalPagamento, #modalGrupo').forEach(m => m.classList.add('hidden'));
    }

    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModals(); });
</script>

<style>
    @keyframes bounce-in {
        0%   { transform: translateY(20px); opacity: 0; }
        70%  { transform: translateY(-4px); }
        100% { transform: translateY(0);    opacity: 1; }
    }
    .animate-bounce-in { animation: bounce-in 0.25s ease-out; }
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endpush

@endsection

@extends('layouts.app')

@section('title', 'Local Físico ' . $localizacao . ' - Inventário')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header Principal com Botão de Voltar -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('inventario') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition duration-150">
                    <i class="fas fa-arrow-left"></i>
                    <span>Voltar ao Inventário</span>
                </a>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <span class="px-3.5 py-1 rounded-xl bg-blue-600 text-white font-extrabold text-2xl shadow-sm">
                    {{ $localizacao }}
                </span>
                <span>Itens do Local Físico {{ $localizacao }}</span>
            </h1>
            <p class="text-gray-500 mt-1">Listagem detalhada de todos os produtos armazenados na prateleira/local <strong>{{ $localizacao }}</strong>.</p>
        </div>
        
        <!-- Botão Destaque para abrir o Scanner -->
        <div class="flex items-center gap-3">
            <a href="{{ route('inventario.scanner') }}" class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-2.5 px-5 rounded-xl shadow-md hover:shadow-lg transition duration-200 text-sm">
                <i class="fas fa-qrcode text-lg"></i>
                <span>Inventário Scanner</span>
            </a>
        </div>
    </div>

    <!-- Cards de Resumo do Local Físico -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Card 1: Total de Peças -->
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-blue-500 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Peças Armazenadas</p>
                <p class="text-2xl font-extrabold text-blue-600 mt-1">
                    {{ number_format($statsLocal['total_itens'], 0, ',', '.') }}
                </p>
            </div>
            <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                <i class="fas fa-tshirt text-xl"></i>
            </div>
        </div>

        <!-- Card 2: Valor Total de Venda -->
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-green-500 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Valor Total de Venda</p>
                <p class="text-2xl font-extrabold text-green-600 mt-1">
                    R$ {{ number_format($statsLocal['valor_total'], 2, ',', '.') }}
                </p>
            </div>
            <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                <i class="fas fa-tag text-xl"></i>
            </div>
        </div>

        <!-- Card 3: Valor Médio por Peça -->
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-yellow-500 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Valor Médio / Peça</p>
                <p class="text-2xl font-extrabold text-yellow-600 mt-1">
                    R$ {{ number_format($statsLocal['valor_medio'], 2, ',', '.') }}
                </p>
            </div>
            <div class="p-3 bg-yellow-50 text-yellow-600 rounded-lg">
                <i class="fas fa-calculator text-xl"></i>
            </div>
        </div>

        <!-- Card 4: Custo Total -->
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-purple-500 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Custo Total Apropriado</p>
                <p class="text-2xl font-extrabold text-purple-600 mt-1">
                    R$ {{ number_format($statsLocal['valor_custo'], 2, ',', '.') }}
                </p>
            </div>
            <div class="p-3 bg-purple-50 text-purple-600 rounded-lg">
                <i class="fas fa-coins text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros e Busca de Produtos -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form method="GET" action="{{ route('inventario.local', urlencode($localizacao)) }}" class="flex flex-col md:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" name="q" value="{{ $search }}" placeholder="Buscar por código (#), nome, marca, tamanho..." class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition duration-150">
            </div>

            <div class="w-full md:w-48">
                <select name="status" class="w-full py-2 px-3 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white">
                    <option value="">— Todos os Status —</option>
                    <option value="estoque" {{ $statusFiltro == 'estoque' ? 'selected' : '' }}>Em Estoque</option>
                    <option value="disponivel" {{ $statusFiltro == 'disponivel' ? 'selected' : '' }}>Disponível</option>
                    <option value="reservado" {{ $statusFiltro == 'reservado' ? 'selected' : '' }}>Reservado</option>
                    <option value="vendido" {{ $statusFiltro == 'vendido' ? 'selected' : '' }}>Vendido</option>
                </select>
            </div>

            <button type="submit" class="w-full md:w-auto bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium px-5 py-2 rounded-lg transition duration-150">
                Filtrar
            </button>
            @if(!empty($search) || !empty($statusFiltro))
                <a href="{{ route('inventario.local', urlencode($localizacao)) }}" class="w-full md:w-auto text-center text-sm font-medium text-gray-500 hover:text-gray-700 px-3 py-2">
                    Limpar Filtros
                </a>
            @endif
        </form>
    </div>

    <!-- Tabela de Itens do Local Físico -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-list text-indigo-500"></i>
                <span>Produtos Guardados no Local: <strong class="text-indigo-600">{{ $localizacao }}</strong></span>
            </h3>
            <span class="text-xs text-gray-400 font-medium">Exibindo {{ $itens->total() }} item(ns)</span>
        </div>

        @if($itens->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-100">
                            <th class="py-3.5 px-6 font-semibold">Código</th>
                            <th class="py-3.5 px-6 font-semibold">Produto</th>
                            <th class="py-3.5 px-6 font-semibold">Tamanho / Cor</th>
                            <th class="py-3.5 px-6 font-semibold">Marca / Estado</th>
                            <th class="py-3.5 px-6 font-semibold">Status</th>
                            <th class="py-3.5 px-6 font-semibold text-right">Preço Venda</th>
                            <th class="py-3.5 px-6 font-semibold text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        @foreach($itens as $item)
                            <tr class="hover:bg-indigo-50/40 transition duration-150">
                                <td class="py-4 px-6 font-mono font-bold text-gray-900">
                                    #{{ $item->codigo }}
                                </td>
                                <td class="py-4 px-6 font-medium text-gray-800">
                                    {{ $item->nome_do_produto ?: 'Sem Nome Cadastrado' }}
                                    @if($item->descricao)
                                        <p class="text-xs text-gray-400 font-normal truncate max-w-xs">{{ $item->descricao }}</p>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-gray-600 font-medium">
                                    {{ $item->tamanho ?: '-' }} / {{ $item->cor ?: '-' }}
                                </td>
                                <td class="py-4 px-6 text-gray-600">
                                    <span class="font-semibold text-gray-800">{{ $item->marca ?: '-' }}</span>
                                    @if($item->estado)
                                        <span class="text-xs text-gray-400 block">{{ $item->estado }}</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6">
                                    @php
                                        $badgeColor = 'bg-gray-100 text-gray-800';
                                        $label = ucfirst($item->status);
                                        if ($item->status === 'disponivel') { $badgeColor = 'bg-green-100 text-green-800'; $label = 'Disponível'; }
                                        elseif ($item->status === 'vendido') { $badgeColor = 'bg-red-100 text-red-800'; $label = 'Vendido'; }
                                        elseif ($item->status === 'reservado') { $badgeColor = 'bg-yellow-100 text-yellow-800'; $label = 'Reservado'; }
                                        elseif ($item->status === 'estoque') { $badgeColor = 'bg-blue-100 text-blue-800'; $label = 'Em Estoque'; }
                                    @endphp
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full {{ $badgeColor }}">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right font-bold text-emerald-600 text-base">
                                    R$ {{ number_format($item->preco ?? 0, 2, ',', '.') }}
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <a href="{{ route('items.edit', $item->id) }}" target="_blank" class="inline-flex items-center gap-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-3 py-1.5 rounded-lg text-xs font-semibold transition duration-150 border border-indigo-200">
                                        <i class="fas fa-edit"></i>
                                        <span>Editar</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($itens->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    {{ $itens->links() }}
                </div>
            @endif
        @else
            <div class="p-12 text-center text-gray-500">
                <i class="fas fa-folder-open text-4xl text-gray-300 mb-3"></i>
                <p class="font-medium text-base">Nenhum produto encontrado neste local com o filtro atual.</p>
            </div>
        @endif
    </div>
</div>
@endsection

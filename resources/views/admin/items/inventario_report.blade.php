@extends('layouts.app')

@section('title', 'Inventário - Locais Físicos do Estoque')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header Principal com Botão para o Scanner -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-boxes text-indigo-600"></i>
                <span>Inventário do Estoque</span>
            </h1>
            <p class="text-gray-500 mt-1">Clique em qualquer local físico para abrir a lista detalhada de todos os itens guardados nele.</p>
        </div>
        
        <!-- Botões de Ação -->
        <div class="flex items-center gap-3">
            <a href="{{ route('inventario.conferencias.index') }}" class="inline-flex items-center justify-center gap-2 bg-white hover:bg-gray-50 text-gray-700 font-semibold py-2.5 px-4 rounded-xl shadow-sm border border-gray-200 transition duration-200 text-sm">
                <i class="fas fa-history text-indigo-600"></i>
                <span>Histórico de Conferências</span>
            </a>
            <a href="{{ route('inventario.scanner') }}" class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-2.5 px-5 rounded-xl shadow-md hover:shadow-lg transition duration-200 text-sm">
                <i class="fas fa-qrcode text-lg"></i>
                <span>Inventário Scanner</span>
            </a>
        </div>
    </div>

    <!-- Cards de Resumo Geral -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Card 1: Total de Locais -->
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-indigo-500 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Locais Cadastrados</p>
                <p class="text-2xl font-extrabold text-gray-800 mt-1">{{ count($locaisEstoque) }}</p>
            </div>
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                <i class="fas fa-map-marker-alt text-xl"></i>
            </div>
        </div>

        <!-- Card 2: Peças com Local -->
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-blue-500 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Peças Endereçadas</p>
                <p class="text-2xl font-extrabold text-blue-600 mt-1">
                    {{ number_format($locaisEstoque->sum('qtd_pecas'), 0, ',', '.') }}
                </p>
            </div>
            <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                <i class="fas fa-tshirt text-xl"></i>
            </div>
        </div>

        <!-- Card 3: Valor Total de Venda nos Locais -->
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-green-500 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Valor Em Prateleiras</p>
                <p class="text-2xl font-extrabold text-green-600 mt-1">
                    R$ {{ number_format($locaisEstoque->sum('valor_total_venda'), 2, ',', '.') }}
                </p>
            </div>
            <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                <i class="fas fa-tag text-xl"></i>
            </div>
        </div>

        <!-- Card 4: Sem Localização -->
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-yellow-500 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Sem Localização</p>
                <p class="text-2xl font-extrabold text-yellow-600 mt-1">
                    {{ number_format($itensSemLocal ?? 0, 0, ',', '.') }}
                </p>
            </div>
            <div class="p-3 bg-yellow-50 text-yellow-600 rounded-lg">
                <i class="fas fa-question-circle text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Barra de Pesquisa de Locais -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form method="GET" action="{{ route('inventario') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" name="localizacao" value="{{ $buscaLocal }}" placeholder="Filtrar por código de local (Ex: A11, A12, A21...)" class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition duration-150">
            </div>
            <button type="submit" class="w-full sm:w-auto bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium px-5 py-2 rounded-lg transition duration-150">
                Filtrar
            </button>
            @if(!empty($buscaLocal))
                <a href="{{ route('inventario') }}" class="w-full sm:w-auto text-center text-sm font-medium text-gray-500 hover:text-gray-700 px-3 py-2">
                    Limpar Filtro
                </a>
            @endif
        </form>
    </div>

    <!-- Tabela de Locais Físicos do Estoque (Clique abre em Nova Página) -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 mb-8">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-warehouse text-indigo-500"></i>
                <span>Locais Físicos do Estoque</span>
            </h3>
            <span class="text-xs text-indigo-600 font-semibold bg-indigo-50 px-2.5 py-1 rounded-full">
                💡 Clique em qualquer linha para abrir os itens em uma nova página
            </span>
        </div>

        @if(count($locaisEstoque) > 0)
            <div class="divide-y divide-gray-100">
                @foreach($locaisEstoque as $loc)
                    <a href="{{ route('inventario.local', urlencode($loc->localizacao)) }}" class="flex items-center justify-between px-6 py-4 hover:bg-indigo-50/70 transition duration-150 group select-none block">
                        <div class="flex items-center gap-4">
                            <span class="px-3.5 py-1.5 font-extrabold rounded-lg bg-blue-600 text-white text-sm shadow-sm group-hover:bg-blue-700 transition duration-150">
                                {{ $loc->localizacao }}
                            </span>
                            <div class="flex flex-col">
                                <span class="text-gray-800 font-bold text-base group-hover:text-indigo-600 transition duration-150">
                                    Local Físico {{ $loc->localizacao }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ number_format($loc->qtd_pecas, 0, ',', '.') }} peças armazenadas
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-6">
                            <div class="text-right">
                                <p class="text-xs text-gray-400 uppercase font-semibold">Valor Total Venda</p>
                                <p class="font-bold text-emerald-600 text-lg">
                                    R$ {{ number_format($loc->valor_total_venda, 2, ',', '.') }}
                                </p>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs font-bold text-indigo-600 bg-indigo-50 group-hover:bg-indigo-600 group-hover:text-white px-3 py-2 rounded-lg transition duration-150 shadow-sm">
                                <span>Ver Detalhes</span>
                                <i class="fas fa-arrow-right text-xs group-hover:translate-x-0.5 transition-transform duration-150"></i>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="p-12 text-center text-gray-500">
                <i class="fas fa-folder-open text-4xl text-gray-300 mb-3"></i>
                <p class="font-medium text-base">Nenhum local físico encontrado.</p>
            </div>
        @endif
    </div>
</div>
@endsection

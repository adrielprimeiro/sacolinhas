@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header / Boas-vindas -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-home text-indigo-600"></i>
                <span>Painel de Controle</span>
            </h1>
            <p class="text-gray-500 mt-1">Bem-vindo ao dashboard do sistema. Aqui está uma visão geral das suas operações.</p>
        </div>
        <!-- Data e Hora atual -->
        <div class="flex items-center gap-2 text-sm text-gray-500 bg-white px-4 py-2 rounded-lg shadow-sm border border-gray-100 self-start md:self-auto">
            <i class="far fa-calendar-alt text-indigo-500"></i>
            <span>{{ \Carbon\Carbon::now()->translatedFormat('d \d\e F \d\e Y') }}</span>
        </div>
    </div>

    <!-- Grid de Estatísticas (8 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

        <!-- Card 1: Atenção - Vencimentos -->
        <div class="bg-white rounded-xl shadow-md border-t-4 border-red-500 hover:shadow-lg transition duration-300 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-red-500 uppercase tracking-wider">Prazos</p>
                        <h3 class="text-lg font-bold text-gray-800 mt-1">Atenção - Vencimentos</h3>
                    </div>
                    <div class="p-3 rounded-lg bg-red-50 text-red-500">
                        <i class="fas fa-triangle-exclamation text-xl"></i>
                    </div>
                </div>

                <div class="mt-4 space-y-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Sacolas vencendo hoje</span>
                        <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-red-100 text-red-800">
                            {{ $alertasVencimento['sacolas_vencem_hoje'] ?? 0 }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Itens vencendo hoje</span>
                        <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-red-100 text-red-800">
                            {{ $alertasVencimento['itens_vencem_hoje'] ?? 0 }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm border-t border-gray-100 pt-2">
                        <span class="text-gray-500">Valor total</span>
                        <span class="font-bold text-red-600">
                            R$ {{ number_format($alertasVencimento['valor_itens_vencem_hoje'] ?? 0, 2, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('admin.vencimentos') }}" class="block w-full text-center bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 text-sm shadow-sm hover:shadow">
                    Ver sacolas
                </a>
            </div>
        </div>

        <!-- Card 2: Estoque Físico & Locais -->
        <div class="bg-white rounded-xl shadow-md border-t-4 border-blue-500 hover:shadow-lg transition duration-300 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Físico</p>
                        <h3 class="text-lg font-bold text-gray-800 mt-1">Estoque</h3>
                    </div>
                    <div class="p-3 rounded-lg bg-blue-50 text-blue-600">
                        <i class="fas fa-warehouse text-xl"></i>
                    </div>
                </div>

                <div class="mt-4">
                    <!-- Tabela / Quadro de Locais Físicos do Estoque -->
                    @if(isset($locaisEstoque) && count($locaisEstoque) > 0)
                        <div>
                            <p class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 flex items-center justify-between">
                                <span><i class="fas fa-map-marker-alt text-blue-500 mr-1"></i> Locais Físicos</span>
                                <span class="text-[10px] font-normal text-gray-400">({{ count($locaisEstoque) }} cadastrados)</span>
                            </p>

                            <div class="max-h-60 overflow-y-auto pr-1 space-y-1.5 scrollbar-thin">
                                @foreach($locaisEstoque as $loc)
                                    <a href="{{ route('inventario') }}?localizacao={{ urlencode($loc->localizacao) }}" class="flex items-center justify-between bg-blue-50/60 hover:bg-blue-100/80 px-2.5 py-1.5 rounded-lg border border-blue-100 text-xs transition duration-150 group">
                                        <div class="flex items-center gap-2">
                                            <span class="px-1.5 py-0.5 font-bold rounded bg-blue-600 text-white text-[11px] group-hover:bg-blue-700">
                                                {{ $loc->localizacao }}
                                            </span>
                                            <span class="text-gray-600 font-medium">{{ number_format($loc->qtd_pecas, 0, ',', '.') }} pcs</span>
                                        </div>
                                        <span class="font-bold text-green-700">
                                            R$ {{ number_format($loc->valor_total_venda, 2, ',', '.') }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="text-xs text-gray-400 mt-2">Nenhum local físico cadastrado no estoque.</p>
                    @endif
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('inventario') }}" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 text-sm shadow-sm hover:shadow">
                    Ver Estoque
                </a>
            </div>
        </div>

        <!-- Card 3: Sacolas -->
        <div class="bg-white rounded-xl shadow-md border-t-4 border-purple-500 hover:shadow-lg transition duration-300 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-purple-600 uppercase tracking-wider">Clientes</p>
                        <h3 class="text-lg font-bold text-gray-800 mt-1">Sacolas</h3>
                    </div>
                    <div class="p-3 rounded-lg bg-purple-50 text-purple-600">
                        <i class="fas fa-shopping-bag text-xl"></i>
                    </div>
                </div>

                <div class="mt-4 space-y-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Total de Sacolas</span>
                        <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-purple-100 text-purple-800">
                            {{ $sacolasInfo['total_sacolas'] ?? 0 }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Itens em Sacolas</span>
                        <span class="font-bold text-blue-600">
                            {{ $sacolasInfo['total_itens'] ?? 0 }} itens
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm border-t border-gray-100 pt-2">
                        <span class="text-gray-500">Valor Total</span>
                        <span class="font-bold text-green-600">
                            R$ {{ number_format($sacolasInfo['valor_total'] ?? 0, 2, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('admin.sacolinhas.index') }}" class="block w-full text-center bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 text-sm shadow-sm hover:shadow">
                    Ver Todas
                </a>
            </div>
        </div>

        <!-- Card 4: Clientes -->
        <div class="bg-white rounded-xl shadow-md border-t-4 border-indigo-500 hover:shadow-lg transition duration-300 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-indigo-500 uppercase tracking-wider">Base</p>
                        <h3 class="text-lg font-bold text-gray-800 mt-1">Clientes</h3>
                    </div>
                    <div class="p-3 rounded-lg bg-indigo-50 text-indigo-600">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-3xl font-extrabold text-gray-800">{{ $estatisticas['total_clientes'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-2">Clientes cadastrados na plataforma e ativos no clube de pontos.</p>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('clientes.index') }}" class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 text-sm shadow-sm hover:shadow">
                    Ver Todos
                </a>
            </div>
        </div>

        <!-- Card 5: Disponíveis -->
        <div class="bg-white rounded-xl shadow-md border-t-4 border-green-500 hover:shadow-lg transition duration-300 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-green-600 uppercase tracking-wider">Produtos</p>
                        <h3 class="text-lg font-bold text-gray-800 mt-1">Disponíveis</h3>
                    </div>
                    <div class="p-3 rounded-lg bg-green-50 text-green-500">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-3xl font-extrabold text-gray-800">{{ $estatisticas['itens_disponiveis'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-2">Itens no estoque disponíveis para venda em transmissões ou catálogo direto.</p>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('items.index') }}?status=disponivel" class="block w-full text-center bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 text-sm shadow-sm hover:shadow">
                    Ver Itens
                </a>
            </div>
        </div>

        <!-- Card 6: Fluxo de Peças Do Mês -->
        <div class="bg-white rounded-xl shadow-md border-t-4 border-red-500 hover:shadow-lg transition duration-300 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-red-500 uppercase tracking-wider">Do Mês</p>
                        <h3 class="text-lg font-bold text-gray-800 mt-1">Fluxo de Peças</h3>
                    </div>
                    <div class="p-3 rounded-lg bg-red-50 text-red-500">
                        <i class="fas fa-exchange-alt text-xl"></i>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="flex items-baseline justify-between">
                        <p class="text-3xl font-extrabold text-gray-800">
                            {{ ($estatisticas['diferenca_mes'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($estatisticas['diferenca_mes'] ?? 0, 0, ',', '.') }}
                        </p>
                        <span class="text-xs font-semibold text-gray-500">Diferença</span>
                    </div>

                    <!-- Relatório de Movimentação do Mês Atual -->
                    <div class="mt-4 pt-3 border-t border-gray-100 space-y-2">
                        <p class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center justify-between">
                            <span><i class="fas fa-calendar-alt text-red-500 mr-1"></i> {{ ucfirst($estatisticas['nome_mes'] ?? 'Mês Atual') }}</span>
                        </p>
                        
                        <div class="flex items-center justify-between bg-emerald-50 px-3 py-2 rounded-lg border border-emerald-100">
                            <span class="text-xs font-medium text-emerald-800 flex items-center">
                                <i class="fas fa-arrow-down text-emerald-600 mr-1.5"></i> Entraram (Avaliação):
                            </span>
                            <span class="text-sm font-bold text-emerald-700">+{{ number_format($estatisticas['entradas_mes_avaliacao'] ?? 0, 0, ',', '.') }} pcs</span>
                        </div>

                        <div class="flex items-center justify-between bg-rose-50 px-3 py-2 rounded-lg border border-rose-100">
                            <span class="text-xs font-medium text-rose-800 flex items-center">
                                <i class="fas fa-arrow-up text-rose-600 mr-1.5"></i> Saíram (Pedidos):
                            </span>
                            <span class="text-sm font-bold text-rose-700">-{{ number_format($estatisticas['saidas_mes_pedidos'] ?? 0, 0, ',', '.') }} pcs</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <a href="{{ route('items.index') }}?status=vendido" class="block w-full text-center bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 text-sm shadow-sm hover:shadow">
                    Ver Itens Vendidos
                </a>
            </div>
        </div>

        <!-- Card 7: Reservados -->
        <div class="bg-white rounded-xl shadow-md border-t-4 border-yellow-500 hover:shadow-lg transition duration-300 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-yellow-600 uppercase tracking-wider">Reserva</p>
                        <h3 class="text-lg font-bold text-gray-800 mt-1">Reservados</h3>
                    </div>
                    <div class="p-3 rounded-lg bg-yellow-50 text-yellow-600">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-3xl font-extrabold text-gray-800">{{ $estatisticas['itens_reservados'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-2">Itens temporariamente reservados que aguardam encerramento ou checkout.</p>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('items.index') }}?status=reservado" class="block w-full text-center bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-lg transition duration-200 text-sm shadow-sm hover:shadow">
                    Ver Itens
                </a>
            </div>
        </div>

        <!-- Card 8: Itens Total -->
        <div class="bg-white rounded-xl shadow-md border-t-4 border-gray-500 hover:shadow-lg transition duration-300 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Inventário</p>
                        <h3 class="text-lg font-bold text-gray-800 mt-1">Itens Total</h3>
                    </div>
                    <div class="p-3 rounded-lg bg-gray-50 text-gray-600">
                        <i class="fas fa-box text-xl"></i>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-3xl font-extrabold text-gray-800">{{ $estatisticas['total_itens'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-2">Total geral de produtos catalogados na base de dados do sistema.</p>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('items.index') }}" class="block w-full text-center bg-gray-700 hover:bg-gray-800 text-white font-medium py-2 px-4 rounded-lg transition duration-200 text-sm shadow-sm hover:shadow">
                    Ver Todos
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
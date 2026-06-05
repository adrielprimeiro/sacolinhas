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
        <div class="bg-white rounded-xl shadow-md border-t-4 border-rose-500 hover:shadow-lg transition duration-300 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-rose-500 uppercase tracking-wider">Prazos</p>
                        <h3 class="text-lg font-bold text-gray-800 mt-1">Atenção - Vencimentos</h3>
                    </div>
                    <div class="p-3 rounded-lg bg-rose-50 text-rose-500">
                        <i class="fas fa-triangle-exclamation text-xl"></i>
                    </div>
                </div>

                <div class="mt-4 space-y-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Sacolas vencendo hoje</span>
                        <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-rose-100 text-rose-800">
                            {{ $alertasVencimento['sacolas_vencem_hoje'] ?? 0 }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Itens vencendo hoje</span>
                        <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-rose-100 text-rose-800">
                            {{ $alertasVencimento['itens_vencem_hoje'] ?? 0 }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm border-t border-gray-100 pt-2">
                        <span class="text-gray-500">Valor total</span>
                        <span class="font-bold text-rose-600">
                            R$ {{ number_format($alertasVencimento['valor_itens_vencem_hoje'] ?? 0, 2, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('admin.vencimentos') }}" class="block w-full text-center bg-rose-600 hover:bg-rose-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 text-sm shadow-sm hover:shadow">
                    Ver sacolas
                </a>
            </div>
        </div>

        <!-- Card 2: Estoque -->
        <div class="bg-white rounded-xl shadow-md border-t-4 border-cyan-500 hover:shadow-lg transition duration-300 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-cyan-600 uppercase tracking-wider">Físico</p>
                        <h3 class="text-lg font-bold text-gray-800 mt-1">Estoque</h3>
                    </div>
                    <div class="p-3 rounded-lg bg-cyan-50 text-cyan-600">
                        <i class="fas fa-warehouse text-xl"></i>
                    </div>
                </div>

                <div class="mt-4 space-y-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Quantidade</span>
                        <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-cyan-100 text-cyan-800">
                            {{ $estoqueInfo['quantidade'] ?? 0 }} itens
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Valor Total</span>
                        <span class="font-bold text-green-600">
                            R$ {{ number_format($estoqueInfo['valor_total'] ?? 0, 2, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm border-t border-gray-100 pt-2">
                        <span class="text-gray-500">Valor Médio</span>
                        <span class="font-bold text-yellow-600">
                            R$ {{ number_format($estoqueInfo['valor_medio'] ?? 0, 2, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('inventario') }}?status=estoque" class="block w-full text-center bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 text-sm shadow-sm hover:shadow">
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
                        <span class="font-bold text-cyan-600">
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
        <div class="bg-white rounded-xl shadow-md border-t-4 border-blue-500 hover:shadow-lg transition duration-300 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-blue-500 uppercase tracking-wider">Base</p>
                        <h3 class="text-lg font-bold text-gray-800 mt-1">Clientes</h3>
                    </div>
                    <div class="p-3 rounded-lg bg-blue-50 text-blue-500">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-3xl font-extrabold text-gray-800">{{ $estatisticas['total_clientes'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-2">Clientes cadastrados na plataforma e ativos no clube de pontos.</p>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('clientes.index') }}" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 text-sm shadow-sm hover:shadow">
                    Ver Todos
                </a>
            </div>
        </div>

        <!-- Card 5: Disponíveis -->
        <div class="bg-white rounded-xl shadow-md border-t-4 border-emerald-500 hover:shadow-lg transition duration-300 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-emerald-500 uppercase tracking-wider">Produtos</p>
                        <h3 class="text-lg font-bold text-gray-800 mt-1">Disponíveis</h3>
                    </div>
                    <div class="p-3 rounded-lg bg-emerald-50 text-emerald-500">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-3xl font-extrabold text-gray-800">{{ $estatisticas['itens_disponiveis'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-2">Itens no estoque disponíveis para venda em transmissões ou catálogo direto.</p>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('inventario') }}?status=disponivel" class="block w-full text-center bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 text-sm shadow-sm hover:shadow">
                    Ver Itens
                </a>
            </div>
        </div>

        <!-- Card 6: Vendidos -->
        <div class="bg-white rounded-xl shadow-md border-t-4 border-rose-500 hover:shadow-lg transition duration-300 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-rose-500 uppercase tracking-wider">Histórico</p>
                        <h3 class="text-lg font-bold text-gray-800 mt-1">Vendidos</h3>
                    </div>
                    <div class="p-3 rounded-lg bg-rose-50 text-rose-500">
                        <i class="fas fa-shopping-cart text-xl"></i>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-3xl font-extrabold text-gray-800">{{ $estatisticas['itens_vendidos'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-2">Volume total de produtos vendidos e processados pelo sistema.</p>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('inventario') }}?status=vendido" class="block w-full text-center bg-rose-600 hover:bg-rose-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 text-sm shadow-sm hover:shadow">
                    Ver Itens
                </a>
            </div>
        </div>

        <!-- Card 7: Reservados -->
        <div class="bg-white rounded-xl shadow-md border-t-4 border-amber-500 hover:shadow-lg transition duration-300 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Reserva</p>
                        <h3 class="text-lg font-bold text-gray-800 mt-1">Reservados</h3>
                    </div>
                    <div class="p-3 rounded-lg bg-amber-50 text-amber-500">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-3xl font-extrabold text-gray-800">{{ $estatisticas['itens_reservados'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-2">Itens temporariamente reservados que aguardam encerramento ou checkout.</p>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('inventario') }}?status=reservado" class="block w-full text-center bg-amber-600 hover:bg-amber-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 text-sm shadow-sm hover:shadow">
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
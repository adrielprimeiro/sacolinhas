@extends('layouts.app')

@section('title', 'Histórico de Conferências de Estoque')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header Principal -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('inventario') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition duration-150">
                    <i class="fas fa-arrow-left"></i>
                    <span>Voltar ao Inventário</span>
                </a>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-clipboard-check text-indigo-600"></i>
                <span>Histórico de Conferências de Estoque</span>
            </h1>
            <p class="text-gray-500 mt-1">Registro completo das sessões de bipagem, auditorias físicas e acurácia por prateleira/setor.</p>
        </div>
    </div>

    <!-- Cards de Estatísticas Gerais -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <!-- Card 1: Total de Conferências -->
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-indigo-500 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Conferências Realizadas</p>
                <p class="text-2xl font-extrabold text-gray-800 mt-1">{{ number_format($statsGerais['total_conferencias'], 0, ',', '.') }}</p>
            </div>
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                <i class="fas fa-history text-xl"></i>
            </div>
        </div>

        <!-- Card 2: Média de Acurácia -->
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-emerald-500 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Média de Acurácia</p>
                <p class="text-2xl font-extrabold text-emerald-600 mt-1">
                    {{ number_format($statsGerais['media_acuracia'], 1, ',', '.') }}%
                </p>
            </div>
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-lg">
                <i class="fas fa-chart-line text-xl"></i>
            </div>
        </div>

        <!-- Card 3: Peças Faltantes Detectadas -->
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-rose-500 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Peças Faltantes</p>
                <p class="text-2xl font-extrabold text-rose-600 mt-1">
                    {{ number_format($statsGerais['total_faltantes'], 0, ',', '.') }}
                </p>
            </div>
            <div class="p-3 bg-rose-50 text-rose-600 rounded-lg">
                <i class="fas fa-exclamation-triangle text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Filtro de Busca -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form method="GET" action="{{ route('inventario.conferencias.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" name="localizacao" value="{{ $localFiltro }}" placeholder="Filtrar por local físico (Ex: A11, A12...)" class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition duration-150">
            </div>
            <button type="submit" class="w-full sm:w-auto bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium px-5 py-2 rounded-lg transition duration-150">
                Filtrar
            </button>
            @if(!empty($localFiltro))
                <a href="{{ route('inventario.conferencias.index') }}" class="w-full sm:w-auto text-center text-sm font-medium text-gray-500 hover:text-gray-700 px-3 py-2">
                    Limpar Filtro
                </a>
            @endif
        </form>
    </div>

    <!-- Tabela de Conferências -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-list text-indigo-500"></i>
                <span>Sessões de Auditoria Registradas</span>
            </h3>
            <span class="text-xs text-gray-400 font-medium">Exibindo {{ $conferencias->total() }} conferência(s)</span>
        </div>

        @if($conferencias->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-100">
                            <th class="py-3.5 px-6 font-semibold">Data / Hora</th>
                            <th class="py-3.5 px-6 font-semibold">Local Físico</th>
                            <th class="py-3.5 px-6 font-semibold">Operador</th>
                            <th class="py-3.5 px-6 font-semibold">Esperados / Lidos</th>
                            <th class="py-3.5 px-6 font-semibold">Confirmados</th>
                            <th class="py-3.5 px-6 font-semibold">Faltantes</th>
                            <th class="py-3.5 px-6 font-semibold">Sobrando</th>
                            <th class="py-3.5 px-6 font-semibold">Acurácia</th>
                            <th class="py-3.5 px-6 font-semibold text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        @foreach($conferencias as $conf)
                            <tr class="hover:bg-indigo-50/40 transition duration-150">
                                <td class="py-4 px-6 font-medium text-gray-900">
                                    {{ $conf->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="py-4 px-6 font-bold">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-indigo-100 text-indigo-800 text-xs font-mono">
                                        {{ $conf->localizacao }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-gray-600 font-medium">
                                    {{ $conf->user->name ?? 'Sistema' }}
                                </td>
                                <td class="py-4 px-6 text-gray-800">
                                    {{ $conf->total_esperado }} exp / <strong class="text-indigo-600">{{ $conf->total_lido }} lidos</strong>
                                </td>
                                <td class="py-4 px-6 font-bold text-emerald-600">
                                    {{ $conf->total_encontrados }}
                                </td>
                                <td class="py-4 px-6 font-bold {{ $conf->total_faltantes > 0 ? 'text-rose-600' : 'text-gray-400' }}">
                                    {{ $conf->total_faltantes }}
                                </td>
                                <td class="py-4 px-6 font-bold {{ $conf->total_sobrando > 0 ? 'text-blue-600' : 'text-gray-400' }}">
                                    {{ $conf->total_sobrando }}
                                </td>
                                <td class="py-4 px-6">
                                    @php
                                        $acuColor = 'bg-emerald-100 text-emerald-800';
                                        if ($conf->acuracia_percentual < 90) $acuColor = 'bg-rose-100 text-rose-800';
                                        elseif ($conf->acuracia_percentual < 98) $acuColor = 'bg-yellow-100 text-yellow-800';
                                    @endphp
                                    <span class="px-2.5 py-1 text-xs font-extrabold rounded-full {{ $acuColor }}">
                                        {{ number_format($conf->acuracia_percentual, 1, ',', '.') }}%
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <a href="{{ route('inventario.conferencias.show', $conf->id) }}" class="inline-flex items-center gap-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-3 py-1.5 rounded-lg text-xs font-semibold transition duration-150 border border-indigo-200">
                                        <i class="fas fa-file-alt"></i>
                                        <span>Relatório</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($conferencias->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    {{ $conferencias->links() }}
                </div>
            @endif
        @else
            <div class="p-12 text-center text-gray-500">
                <i class="fas fa-folder-open text-4xl text-gray-300 mb-3"></i>
                <p class="font-medium text-base">Nenhuma conferência registrada ainda.</p>
                <p class="text-xs text-gray-400 mt-1">Utilize o Inventário Scanner selecionando um local físico para realizar conferências auditadas.</p>
            </div>
        @endif
    </div>
</div>
@endsection

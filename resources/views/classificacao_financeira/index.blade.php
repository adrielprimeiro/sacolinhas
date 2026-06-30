@extends('layouts.app')

@section('title', 'Plano de Contas')
@section('brand_route', 'classificacao_financeira.index')
@section('brand_icon', 'fas fa-sitemap')

@section('content')
    @include('admin.financeiro._subnav')
    <div class="max-w-6xl mx-auto bg-white shadow-lg rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-semibold text-gray-800">Plano de Contas</h1>

            <a href="{{ route('classificacao_financeira.create') }}"
               class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300">
                <i class="fas fa-plus mr-2"></i> Nova
            </a>
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-6">
            <input name="q" placeholder="Buscar por nome/código" value="{{ request('q') }}"
                   class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

            <input name="user_id" placeholder="User ID" value="{{ request('user_id') }}"
                   class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

            <select name="tipo_natureza"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Tipo (todos)</option>
                <option value="receita" @selected(request('tipo_natureza')==='receita')>Receita</option>
                <option value="despesa" @selected(request('tipo_natureza')==='despesa')>Despesa</option>
            </select>

            <select name="nivel"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Nível (todos)</option>
                <option value="sintetico" @selected(request('nivel')==='sintetico')>Sintético</option>
                <option value="analitico" @selected(request('nivel')==='analitico')>Analítico</option>
            </select>

            <button class="bg-gray-700 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded-md shadow transition duration-300">
                <i class="fas fa-filter mr-2"></i> Filtrar
            </button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Código</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Nome</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tipo</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Nível</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Pai</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">User</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Ações</th>
                </tr>
                </thead>

                <tbody class="bg-white divide-y divide-gray-200">
                @forelse($classificacoes as $c)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $c->codigo_contabil }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $c->nome }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            @if($c->tipo_natureza === 'receita')
                                <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs font-semibold">Receita</span>
                            @else
                                <span class="bg-red-200 text-red-800 py-1 px-3 rounded-full text-xs font-semibold">Despesa</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ ucfirst($c->nivel) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $c->pai ? ($c->pai->codigo_contabil.' - '.$c->pai->nome) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $c->user_id }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('classificacao_financeira.show', $c->id) }}"
                               class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1.5 px-3 rounded-md shadow-sm transition duration-300">
                                <i class="fas fa-eye mr-1"></i> Ver
                            </a>

                            <a href="{{ route('classificacao_financeira.edit', $c->id) }}"
                               class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-1.5 px-3 rounded-md shadow-sm transition duration-300 ml-2">
                                <i class="fas fa-edit mr-1"></i> Editar
                            </a>

                            <form method="POST" action="{{ route('classificacao_financeira.destroy', $c->id) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button onclick="return confirm('Excluir este registro?')"
                                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-1.5 px-3 rounded-md shadow-sm transition duration-300 ml-2">
                                    <i class="fas fa-trash mr-1"></i> Excluir
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                            Nenhuma classificação encontrada.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $classificacoes->links() }}
        </div>
    </div>
@endsection
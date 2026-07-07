@extends('layouts.app')

@section('title', 'Gerenciar Marcas')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Cabeçalho --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Marcas</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $totalMarcas }} marcas cadastradas · Configure o valor de mercado agregado (multiplicador) de cada marca.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('admin.marcas.create') }}"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm py-2.5 px-4 rounded-lg transition-colors shadow-sm"
            >
                <i class="fas fa-plus"></i>
                Nova Marca
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm shadow-sm">
            <i class="fas fa-check-circle text-green-500"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm shadow-sm">
            <i class="fas fa-exclamation-circle text-red-500"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Tabela de Marcas --}}
    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nome da Marca</th>
                        <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Itens Registrados</th>
                        <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acréscimo / Multiplicador</th>
                        <th class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($marcas as $marca)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                                #{{ $marca->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-semibold text-gray-900">{{ $marca->nome }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-gray-600 font-medium">
                                {{ number_format($marca->total_registros, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $marca->porcentagem_valor > 100 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $marca->formatted_porcentagem }}
                                    @if ($marca->porcentagem_valor > 100)
                                        <span class="text-[10px] font-normal block text-green-600 mt-0.5">({{ ($marca->porcentagem_valor - 100) }}% acréscimo)</span>
                                    @elseif ($marca->porcentagem_valor == 100)
                                        <span class="text-[10px] font-normal block text-gray-400 mt-0.5">(Preço base)</span>
                                    @else
                                        <span class="text-[10px] font-normal block text-red-500 mt-0.5">({{ (100 - $marca->porcentagem_valor) }}% redução)</span>
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- Editar --}}
                                    <a href="{{ route('admin.marcas.edit', $marca) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-gray-100 text-blue-600 transition-colors border border-gray-200" title="Editar Marca">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>

                                    {{-- Deletar --}}
                                    @if (!in_array(strtolower(trim($marca->nome)), ['sem marca', 'sem_marca']))
                                        <form action="{{ route('admin.marcas.destroy', $marca) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja remover esta marca? Quaisquer itens de avaliação vinculados a ela serão definidos para Sem Marca.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-red-50 text-red-600 transition-colors border border-gray-200" title="Excluir Marca">
                                                <i class="far fa-trash-alt text-xs"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" disabled class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-300 border border-gray-100 cursor-not-allowed" title="Marca padrão não pode ser excluída">
                                            <i class="far fa-trash-alt text-xs"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fas fa-copyright text-4xl mb-3 text-gray-300"></i>
                                    <p class="text-sm font-medium">Nenhuma marca cadastrada</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginação --}}
        @if ($marcas->hasPages())
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                {{ $marcas->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

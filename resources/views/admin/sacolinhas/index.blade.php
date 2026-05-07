@extends('layouts.app')

@section('title', 'Gerenciar Sacolinhas')
@section('brand_route', 'admin.sacolinha.gestao')
@section('brand_icon', 'fas fa-shopping-bag')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-semibold text-gray-800">Gerenciar Sacolinhas</h1>
    </div>

	{{-- Filtros --}}
	<div class="bg-white shadow-lg rounded-lg p-4 mb-6">
		<form method="GET" action="{{ route('admin.sacolinha.gestao') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
			<div class="md:col-span-3">
				<label class="block text-sm font-medium text-gray-700 mb-1">Buscar (nome do cliente)</label>
				<input type="text" name="cliente" value="{{ request('cliente') }}"
					   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
					   placeholder="Digite o nome do cliente...">
			</div>

			<div class="flex items-center justify-end gap-2 pt-6">
				<a href="{{ route('admin.sacolinha.gestao') }}"
				   class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
					Limpar
				</a>

				<button type="submit"
						class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300">
					<i class="fas fa-filter mr-2"></i> Filtrar
				</button>
			</div>
		</form>
	</div>

    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Cliente</th>
                        <th class="py-3 px-6 text-left">Aberto em</th>
                        <th class="py-3 px-6 text-left">Itens</th>
                        <th class="py-3 px-6 text-left">Valor Total</th>
                        <th class="py-3 px-6 text-center">Ações</th>
                    </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">
                    @forelse ($sacolinhas as $sacola)
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6 text-left font-medium">
                                {{ $sacola->name ?? 'N/A' }}
                            </td>

                            <td class="py-3 px-6 text-left whitespace-nowrap">
                                @if (!empty($sacola->aberto_em))
                                    {{ \Carbon\Carbon::parse($sacola->aberto_em)->format('d/m/Y H:i') }}
                                @else
                                    —
                                @endif
                            </td>

                            <td class="py-3 px-6 text-left">
                                <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-xs font-semibold">
                                    {{ $sacola->total_itens }} {{ $sacola->total_itens == 1 ? 'item' : 'itens' }}
                                </span>
                            </td>

                            <td class="py-3 px-6 text-left font-semibold whitespace-nowrap">
                                {{ 'R$ ' . number_format((float)$sacola->total_valor, 2, ',', '.') }}
                            </td>

                            <td class="py-3 px-6 text-center">
                                <div class="flex item-center justify-center space-x-2">
                                    <a href="{{ route('admin.sacolinha.show', $sacola->user_id) }}"
                                       class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-4 rounded-md shadow transition duration-300 flex items-center gap-2"
                                       title="Ver Detalhes">
                                        <i class="fas fa-eye text-sm"></i> Ver
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 px-6 text-center text-gray-500">
                                Nenhuma sacolinha aberta no momento.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (method_exists($sacolinhas, 'links'))
            <div class="p-4">
                {{ $sacolinhas->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
@endsection

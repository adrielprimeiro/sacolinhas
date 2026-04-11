@extends('layouts.app')

@section('title', 'Financeiro')
@section('brand_route', 'admin.financeiro.index')
@section('brand_icon', 'fas fa-wallet')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-semibold text-gray-800">Lançamentos</h1>
        <a href="{{ route('admin.financeiro.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300">
            <i class="fas fa-plus-circle mr-2"></i> Novo Lançamento
        </a>
    </div>
	
	{{-- Filtros --}}
	<div class="bg-white shadow-lg rounded-lg p-4 mb-6">
		<form method="GET" action="{{ route('admin.financeiro.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
			<div class="md:col-span-2">
				<label class="block text-sm font-medium text-gray-700 mb-1">Buscar (descrição)</label>
				<input type="text" name="q" value="{{ request('q') }}"
					   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
					   placeholder="Ex: frete, estorno, pagamento...">
			</div>

			<div>
				<label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
				<select name="tipo"
						class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
					<option value="">Todos</option>
					<option value="credito" {{ request('tipo') === 'credito' ? 'selected' : '' }}>Crédito</option>
					<option value="debito" {{ request('tipo') === 'debito' ? 'selected' : '' }}>Débito</option>
				</select>
			</div>

			<div>
				<label class="block text-sm font-medium text-gray-700 mb-1">Usuário (ID)</label>
				<input type="number" name="user_id" value="{{ request('user_id') }}"
					   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
					   placeholder="Ex: 322">
			</div>

			<div>
				<label class="block text-sm font-medium text-gray-700 mb-1">De</label>
				<input type="date" name="de" value="{{ request('de') }}"
					   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
			</div>

			<div>
				<label class="block text-sm font-medium text-gray-700 mb-1">Até</label>
				<input type="date" name="ate" value="{{ request('ate') }}"
					   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
			</div>

			<div class="md:col-span-6 flex items-center justify-end gap-2 pt-2">
				<a href="{{ route('admin.financeiro.index') }}"
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
                        <th class="py-3 px-6 text-left">ID</th>
                        <th class="py-3 px-6 text-left">Usuário</th>
                        <th class="py-3 px-6 text-left">Data</th>
                        <th class="py-3 px-6 text-left">Tipo</th>
                        <th class="py-3 px-6 text-left">Valor</th>
                        <th class="py-3 px-6 text-left">Descrição</th>
                        <th class="py-3 px-6 text-left">Classificação</th>
                        <th class="py-3 px-6 text-left">Saldo Anterior</th>
                        <th class="py-3 px-6 text-left">Saldo Atual</th>
                        <th class="py-3 px-6 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($movimentacoes as $movimentacao)
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6 text-left whitespace-nowrap">{{ $movimentacao->id }}</td>
                            <td class="py-3 px-6 text-left">{{ $movimentacao->user->name ?? 'N/A' }}</td>
                            <td class="py-3 px-6 text-left">{{ $movimentacao->data_movimentacao->format('d/m/Y H:i') }}</td>
                            <td class="py-3 px-6 text-left">
                                @if ($movimentacao->tipo_movimentacao == 'credito')
                                    <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs font-semibold">Crédito</span>
                                @else
                                    <span class="bg-red-200 text-red-800 py-1 px-3 rounded-full text-xs font-semibold">Débito</span>
                                @endif
                            </td>
                            <td class="py-3 px-6 text-left font-medium">R$ {{ number_format($movimentacao->valor, 2, ',', '.') }}</td>
                            <td class="py-3 px-6 text-left">{{ Str::limit($movimentacao->descricao, 30) }}</td>
                            <td class="py-3 px-6 text-left">{{ $movimentacao->classificacaoFinanceira->nome ?? 'N/A' }}</td>
                            <td class="py-3 px-6 text-left">R$ {{ number_format($movimentacao->saldo_anterior, 2, ',', '.') }}</td>
                            <td class="py-3 px-6 text-left">R$ {{ number_format($movimentacao->saldo_atual, 2, ',', '.') }}</td>
                            <td class="py-3 px-6 text-center">
                                <div class="flex item-center justify-center space-x-2">
                                    <a href="{{ route('admin.financeiro.show', $movimentacao->id) }}" class="w-8 h-8 rounded-full bg-blue-100 hover:bg-blue-200 text-blue-600 flex items-center justify-center transition duration-300" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.financeiro.edit', $movimentacao->id) }}" class="w-8 h-8 rounded-full bg-yellow-100 hover:bg-yellow-200 text-yellow-600 flex items-center justify-center transition duration-300" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.financeiro.destroy', $movimentacao->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este lançamento?');" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-full bg-red-100 hover:bg-red-200 text-red-600 flex items-center justify-center transition duration-300" title="Excluir">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-4 px-6 text-center text-gray-500">Nenhum lançamento encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-200">
			{{ $movimentacoes->appends(request()->query())->links() }}
        </div>
    </div>
@endsection
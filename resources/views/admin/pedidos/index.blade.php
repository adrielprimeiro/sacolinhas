@extends('layouts.app')

@section('title', 'Pedidos')
@section('brand_route', 'admin.pedido.index')
@section('brand_icon', 'fas fa-receipt')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-semibold text-gray-800">Pedidos</h1>

        @if (Route::has('admin.pedido.create'))
            <a href="{{ route('admin.pedido.create') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300">
                <i class="fas fa-plus-circle mr-2"></i> Novo Pedido
            </a>
        @endif
    </div>

	{{-- Filtros --}}
	<div class="bg-white shadow-lg rounded-lg p-4 mb-6">
		<form method="GET" action="{{ route('admin.pedido.index') }}" class="grid grid-cols-1 md:grid-cols-10 gap-4">
			<div class="md:col-span-2">
				<label class="block text-sm font-medium text-gray-700 mb-1">Nº Pedido</label>
				<input type="text" name="numero_pedido" value="{{ request('numero_pedido') }}"
					   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
					   placeholder="Ex: PED-000123">
			</div>

			<div class="md:col-span-3">
				<label class="block text-sm font-medium text-gray-700 mb-1">Buscar (nome/e-mail do cliente)</label>
				<input type="text" name="cliente" value="{{ request('cliente') }}"
					   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
					   placeholder="Digite nome ou e-mail...">
			</div>

			<div class="md:col-span-2">
				<label class="block text-sm font-medium text-gray-700 mb-1">Status do pedido</label>
				<select name="status_pedido"
						class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
					<option value="">Todos</option>
					@php $sp = request('status_pedido'); @endphp
					<option value="pendente" {{ $sp === 'pendente' ? 'selected' : '' }}>Pendente</option>
					<option value="confirmado" {{ $sp === 'confirmado' ? 'selected' : '' }}>Confirmado</option>
					<option value="processando" {{ $sp === 'processando' ? 'selected' : '' }}>Processando</option>
					<option value="pago" {{ $sp === 'pago' ? 'selected' : '' }}>Pago</option>
					<option value="enviado" {{ $sp === 'enviado' ? 'selected' : '' }}>Enviado</option>
					<option value="entregue" {{ $sp === 'entregue' ? 'selected' : '' }}>Entregue</option>
					<option value="concluido" {{ $sp === 'concluido' ? 'selected' : '' }}>Concluído</option>
					<option value="cancelado" {{ $sp === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
				</select>
			</div>

			<div class="md:col-span-1">
				<label class="block text-sm font-medium text-gray-700 mb-1">Origem</label>
				<select name="origem_pedido"
						class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
					<option value="">Todas</option>
					@php $op = request('origem_pedido'); @endphp
					<option value="live" {{ $op === 'live' ? 'selected' : '' }}>Live</option>
					<option value="site" {{ $op === 'site' ? 'selected' : '' }}>Site</option>
					<option value="whatsapp" {{ $op === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
					<option value="instagram" {{ $op === 'instagram' ? 'selected' : '' }}>Instagram</option>
					<option value="admin" {{ $op === 'admin' ? 'selected' : '' }}>Admin</option>
					<option value="portal" {{ $op === 'portal' ? 'selected' : '' }}>Portal</option>
				</select>
			</div>

			<div class="md:col-span-1">
				<label class="block text-sm font-medium text-gray-700 mb-1">Pagamento</label>
				<select name="status_pagamento"
						class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
					<option value="">Todos</option>
					@php $pg = request('status_pagamento'); @endphp
					<option value="pendente" {{ $pg === 'pendente' ? 'selected' : '' }}>Pendente</option>
					<option value="aprovado" {{ $pg === 'aprovado' ? 'selected' : '' }}>Aprovado</option>
					<option value="rejeitado" {{ $pg === 'rejeitado' ? 'selected' : '' }}>Rejeitado</option>
					<option value="estornado" {{ $pg === 'estornado' ? 'selected' : '' }}>Estornado</option>
				</select>
			</div>

			<div class="md:col-span-1">
				<label class="block text-sm font-medium text-gray-700 mb-1">De</label>
				<input type="date" name="de" value="{{ request('de') }}"
					   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
			</div>

			<div class="md:col-span-1">
				<label class="block text-sm font-medium text-gray-700 mb-1">Até</label>
				<input type="date" name="ate" value="{{ request('ate') }}"
					   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
			</div>

			<div class="md:col-span-10 flex items-center justify-end gap-2 pt-2">
				<a href="{{ route('admin.pedido.index') }}"
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
                        <th class="py-3 px-6 text-left">Nº Pedido</th>
                        <th class="py-3 px-6 text-left">Cliente</th>
                        <th class="py-3 px-6 text-left">Origem</th>
                        <th class="py-3 px-6 text-left">Status</th>
                        <th class="py-3 px-6 text-left">Pagamento</th>
                        <th class="py-3 px-6 text-left">Data</th>
                        <th class="py-3 px-6 text-left">Total</th>
                        <th class="py-3 px-6 text-center">Ações</th>
                    </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">
                    @forelse ($pedidos as $pedido)
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6 text-left whitespace-nowrap font-medium">
                                {{ $pedido->numero_pedido ?? ('#' . $pedido->id) }}
                            </td>

                            <td class="py-3 px-6 text-left">
                                {{ $pedido->user->name ?? 'N/A' }}
                            </td>

                            <td class="py-3 px-6 text-left">
                                @php
                                    $origem = $pedido->origem_pedido ?? null;
                                @endphp

                                @if ($origem === 'live')
                                    <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-xs font-semibold">Live</span>
                                @elseif ($origem === 'site')
                                    <span class="bg-gray-200 text-gray-800 py-1 px-3 rounded-full text-xs font-semibold">Site</span>
                                @elseif ($origem === 'whatsapp')
                                    <span class="bg-green-100 text-green-800 py-1 px-3 rounded-full text-xs font-semibold">WhatsApp</span>
                                @elseif ($origem === 'instagram')
                                    <span class="bg-pink-100 text-pink-800 py-1 px-3 rounded-full text-xs font-semibold">Instagram</span>
                                @elseif (!empty($origem))
                                    <span class="bg-gray-200 text-gray-700 py-1 px-3 rounded-full text-xs font-semibold">
                                        {{ ucfirst($origem) }}
                                    </span>
                                @else
                                    <span class="bg-gray-200 text-gray-700 py-1 px-3 rounded-full text-xs font-semibold">N/A</span>
                                @endif
                            </td>

                            <td class="py-3 px-6 text-left">
                                @php
                                    $status = $pedido->status_pedido ?? null;
                                @endphp

                                @if ($status === 'entregue' || $status === 'pago')
                                    <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs font-semibold">
                                        {{ $status === 'pago' ? 'Pago' : 'Entregue' }}
                                    </span>
                                @elseif ($status === 'enviado')
                                    <span class="bg-blue-200 text-blue-800 py-1 px-3 rounded-full text-xs font-semibold">Enviado</span>
                                @elseif ($status === 'processando')
                                    <span class="bg-yellow-200 text-yellow-800 py-1 px-3 rounded-full text-xs font-semibold">Processando</span>
                                @elseif ($status === 'confirmado')
                                    <span class="bg-indigo-200 text-indigo-800 py-1 px-3 rounded-full text-xs font-semibold">Confirmado</span>
                                @elseif ($status === 'pendente')
                                    <span class="bg-gray-200 text-gray-800 py-1 px-3 rounded-full text-xs font-semibold">Pendente</span>
                                @elseif ($status === 'cancelado')
                                    <span class="bg-red-200 text-red-800 py-1 px-3 rounded-full text-xs font-semibold">Cancelado</span>
                                @elseif (!empty($status))
                                    <span class="bg-gray-200 text-gray-700 py-1 px-3 rounded-full text-xs font-semibold">
                                        {{ ucfirst($status) }}
                                    </span>
                                @else
                                    <span class="bg-gray-200 text-gray-700 py-1 px-3 rounded-full text-xs font-semibold">N/A</span>
                                @endif
                            </td>

                            <td class="py-3 px-6 text-left">
                                @php
                                    $statusPg = $pedido->status_pagamento ?? null;
                                    $formaPg = $pedido->forma_pagamento ?? null;

                                    $formaLabel = match ($formaPg) {
                                        'pix' => 'Pix',
                                        'cartao_credito' => 'Cartão Crédito',
                                        'cartao_debito' => 'Cartão Débito',
                                        'boleto' => 'Boleto',
                                        'dinheiro' => 'Dinheiro',
                                        'transferencia' => 'Transferência',
                                        default => null,
                                    };

                                    $pgBadge = function ($txt, $bg, $fg) {
                                        return '<span class="bg-' . $bg . ' text-' . $fg . ' py-1 px-3 rounded-full text-xs font-semibold">' . $txt . '</span>';
                                    };
                                @endphp

                                <div class="flex flex-col gap-1">
                                    <div>
                                        @if ($statusPg === 'aprovado')
                                            <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs font-semibold">Aprovado</span>
                                        @elseif ($statusPg === 'pendente')
                                            <span class="bg-yellow-200 text-yellow-800 py-1 px-3 rounded-full text-xs font-semibold">Pendente</span>
                                        @elseif ($statusPg === 'rejeitado')
                                            <span class="bg-red-200 text-red-800 py-1 px-3 rounded-full text-xs font-semibold">Rejeitado</span>
                                        @elseif ($statusPg === 'estornado')
                                            <span class="bg-gray-300 text-gray-800 py-1 px-3 rounded-full text-xs font-semibold">Estornado</span>
                                        @elseif (!empty($statusPg))
                                            <span class="bg-gray-200 text-gray-700 py-1 px-3 rounded-full text-xs font-semibold">{{ ucfirst($statusPg) }}</span>
                                        @else
                                            <span class="bg-gray-200 text-gray-700 py-1 px-3 rounded-full text-xs font-semibold">N/A</span>
                                        @endif
                                    </div>

                                    <div class="text-xs text-gray-600">
                                        {{ $formaLabel ?? '—' }}
                                    </div>
                                </div>
                            </td>

                            <td class="py-3 px-6 text-left whitespace-nowrap">
                                @if (!empty($pedido->data_pedido))
                                    {{ \Carbon\Carbon::parse($pedido->data_pedido)->format('d/m/Y H:i') }}
                                @else
                                    —
                                @endif
                            </td>

                            <td class="py-3 px-6 text-left font-semibold whitespace-nowrap">
                                R$ {{ number_format(max(0, (float)$pedido->valor_total), 2, ',', '.') }}
                            </td>

                            <td class="py-3 px-6 text-center">
                                <div class="flex item-center justify-center space-x-2">
                                    @if (Route::has('admin.pedido.show'))
                                        <a href="{{ route('admin.pedido.show', $pedido->id) }}"
                                           class="w-8 h-8 rounded-full bg-blue-100 hover:bg-blue-200 text-blue-600 flex items-center justify-center transition duration-300"
                                           title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endif

                                    @if (Route::has('admin.pedido.edit'))
                                        <a href="{{ route('admin.pedido.edit', $pedido->id) }}"
                                           class="w-8 h-8 rounded-full bg-yellow-100 hover:bg-yellow-200 text-yellow-600 flex items-center justify-center transition duration-300"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif

                                    @if (Route::has('admin.pedido.destroy'))
                                        <form action="{{ route('admin.pedido.destroy', $pedido->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('Tem certeza que deseja excluir este pedido?');"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="w-8 h-8 rounded-full bg-red-100 hover:bg-red-200 text-red-600 flex items-center justify-center transition duration-300"
                                                    title="Excluir">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-6 px-6 text-center text-gray-500">
                                Nenhum pedido encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (method_exists($pedidos, 'links'))
            <div class="p-4">
                {{ $pedidos->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
@endsection
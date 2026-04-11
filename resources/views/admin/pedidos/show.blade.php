@extends('layouts.app')

@section('title', 'Pedido #' . ($pedido->numero_pedido ?? $pedido->id))
@section('brand_route', 'admin.pedido.index')
@section('brand_icon', 'fas fa-receipt')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-semibold text-gray-800">
                Pedido #{{ $pedido->numero_pedido ?? $pedido->id }}
            </h1>
            <p class="text-sm text-gray-600 mt-1">
                Criado em:
                {{ !empty($pedido->created_at) ? $pedido->created_at->format('d/m/Y H:i') : '—' }}
                • Atualizado em:
                {{ !empty($pedido->updated_at) ? $pedido->updated_at->format('d/m/Y H:i') : '—' }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.pedido.index') }}"
               class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>

            <a href="{{ route('admin.pedido.edit', $pedido->id) }}"
               class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>

            <form action="{{ route('admin.pedido.destroy', $pedido->id) }}" method="POST"
                  onsubmit="return confirm('Tem certeza que deseja excluir este pedido?');" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                    <i class="fas fa-trash-alt mr-2"></i> Excluir
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Coluna 1: Resumo --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Resumo</h2>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">ID</span>
                        <span class="text-gray-800 font-medium">{{ $pedido->id }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Número</span>
                        <span class="text-gray-800 font-medium">{{ $pedido->numero_pedido }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Data do pedido</span>
                        <span class="text-gray-800 font-medium">
                            {{ !empty($pedido->data_pedido) ? \Carbon\Carbon::parse($pedido->data_pedido)->format('d/m/Y H:i') : '—' }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4 items-center">
                        <span class="text-gray-500">Status do pedido</span>
                        <span>
                            @php $sp = $pedido->status_pedido; @endphp
                            @if ($sp === 'entregue')
                                <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs font-semibold">Entregue</span>
                            @elseif ($sp === 'enviado')
                                <span class="bg-blue-200 text-blue-800 py-1 px-3 rounded-full text-xs font-semibold">Enviado</span>
                            @elseif ($sp === 'processando')
                                <span class="bg-yellow-200 text-yellow-800 py-1 px-3 rounded-full text-xs font-semibold">Processando</span>
                            @elseif ($sp === 'confirmado')
                                <span class="bg-indigo-200 text-indigo-800 py-1 px-3 rounded-full text-xs font-semibold">Confirmado</span>
                            @elseif ($sp === 'pendente')
                                <span class="bg-gray-200 text-gray-800 py-1 px-3 rounded-full text-xs font-semibold">Pendente</span>
                            @elseif ($sp === 'cancelado')
                                <span class="bg-red-200 text-red-800 py-1 px-3 rounded-full text-xs font-semibold">Cancelado</span>
                            @else
                                <span class="bg-gray-200 text-gray-700 py-1 px-3 rounded-full text-xs font-semibold">{{ $sp ?? '—' }}</span>
                            @endif
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Origem</span>
                        <span class="text-gray-800 font-medium">{{ $pedido->origem_pedido ?? '—' }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Live ID</span>
                        <span class="text-gray-800 font-medium">{{ $pedido->live_id ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Valores</h2>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Subtotal</span>
                        @php
                            $subtotal = (float)$pedido->valor_total - (float)$pedido->valor_frete + (float)$pedido->valor_desconto;
                        @endphp
                        <span class="text-gray-800 font-semibold">R$ {{ number_format($subtotal, 2, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Frete</span>
                        <span class="text-gray-800 font-medium">R$ {{ number_format((float)$pedido->valor_frete, 2, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Desconto</span>
                        <span class="text-gray-800 font-medium">R$ {{ number_format((float)$pedido->valor_desconto, 2, ',', '.') }}</span>
                    </div>

                    <div class="border-t border-gray-200 pt-3 flex justify-between gap-4">
                        <span class="text-gray-800 font-semibold">Total</span>
                        <span class="text-gray-900 font-bold">R$ {{ number_format((float)$pedido->valor_total, 2, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between gap-4 pt-2">
                        <span class="text-gray-500">Cupom</span>
                        <span class="text-gray-800 font-medium">{{ $pedido->cupom_desconto ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Coluna 2-3: Detalhes --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Cliente</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-gray-500">Nome</div>
                        <div class="text-gray-900 font-medium">{{ $pedido->user->name ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">E-mail</div>
                        <div class="text-gray-900 font-medium">{{ $pedido->user->email ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">User ID</div>
                        <div class="text-gray-900 font-medium">{{ $pedido->user_id }}</div>
                    </div>
                </div>
            </div>		
		
		
            {{-- Itens do Pedido --}}
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Itens do Pedido</h2>

                @php
                    $itens = DB::table('items_pedido as ip')
                        ->join('items as i', 'i.id', '=', 'ip.item_id')
                        ->where('ip.pedido_id', $pedido->id)
                        ->select([
                            'ip.id',
                            'ip.quantidade',
                            'ip.preco_unitario',
                            'ip.valor_total',
                            'ip.status_item',
                            'i.nome_do_produto',
                            'i.codigo',
                            'i.marca',
                            'i.estado',
                            'i.cor',
                            'i.tamanho',
                            'i.image',
                        ])
                        ->get();
                @endphp

                @if($itens->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Produto</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Detalhes</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Qtde</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Preço Unit.</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($itens as $item)
                                    @php
                                        $img = $item->image ?? null;
                                        $imgUrl = $img ? asset('storage/' . ltrim($img, '/')) : null;
                                    @endphp

                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-12 h-12 bg-gray-100 rounded-md overflow-hidden flex items-center justify-center flex-shrink-0">
                                                    @if($imgUrl)
                                                        <img src="{{ $imgUrl }}" alt="{{ $item->nome_do_produto ?? 'Produto' }}" class="w-full h-full object-cover">
                                                    @else
                                                        <i class="fas fa-image text-gray-400"></i>
                                                    @endif
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-gray-800">{{ $item->nome_do_produto }}</p>
                                                    <p class="text-xs text-gray-500">Código: {{ $item->codigo }}</p>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ $item->marca }} • {{ $item->estado }} • {{ $item->cor }} • Tam: {{ $item->tamanho }}
                                        </td>

                                        <td class="px-4 py-3 text-sm font-medium text-gray-800">
                                            {{ $item->quantidade }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-800">
                                            R$ {{ number_format($item->preco_unitario ?? 0, 2, ',', '.') }}
                                        </td>

                                        <td class="px-4 py-3 text-sm font-semibold text-gray-800">
                                            R$ {{ number_format($item->valor_total ?? 0, 2, ',', '.') }}
                                        </td>

                                        <td class="px-4 py-3 text-sm">
                                            @php $si = $item->status_item; @endphp
                                            @if ($si === 'ativo')
                                                <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs font-semibold">Ativo</span>
                                            @elseif ($si === 'cancelado')
                                                <span class="bg-red-200 text-red-800 py-1 px-3 rounded-full text-xs font-semibold">Cancelado</span>
                                            @elseif ($si === 'devolvido')
                                                <span class="bg-gray-300 text-gray-800 py-1 px-3 rounded-full text-xs font-semibold">Devolvido</span>
                                            @else
                                                <span class="bg-gray-200 text-gray-700 py-1 px-3 rounded-full text-xs font-semibold">{{ $si ?? '—' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-600">Nenhum item encontrado para este pedido.</p>
                @endif
            </div>



            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Pagamento</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-gray-500">Forma</div>
                        <div class="text-gray-900 font-medium">{{ $pedido->forma_pagamento ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Status</div>
                        <div class="mt-1">
                            @php $pg = $pedido->status_pagamento; @endphp
                            @if ($pg === 'aprovado')
                                <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs font-semibold">Aprovado</span>
                            @elseif ($pg === 'pendente')
                                <span class="bg-yellow-200 text-yellow-800 py-1 px-3 rounded-full text-xs font-semibold">Pendente</span>
                            @elseif ($pg === 'rejeitado')
                                <span class="bg-red-200 text-red-800 py-1 px-3 rounded-full text-xs font-semibold">Rejeitado</span>
                            @elseif ($pg === 'estornado')
                                <span class="bg-gray-300 text-gray-800 py-1 px-3 rounded-full text-xs font-semibold">Estornado</span>
                            @else
                                <span class="bg-gray-200 text-gray-700 py-1 px-3 rounded-full text-xs font-semibold">{{ $pg ?? '—' }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Entrega</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-4">
                    <div>
                        <div class="text-gray-500">CEP</div>
                        <div class="text-gray-900 font-medium">{{ $pedido->cep_entrega ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Cidade / UF</div>
                        <div class="text-gray-900 font-medium">
                            {{ $pedido->cidade_entrega ?? '—' }}{{ !empty($pedido->estado_entrega) ? '/' . $pedido->estado_entrega : '' }}
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <div class="text-gray-500">Endereço</div>
                        <div class="text-gray-900 font-medium whitespace-pre-line">{{ $pedido->endereco_entrega ?? '—' }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <div class="text-gray-500">Código rastreio</div>
                        <div class="text-gray-900 font-medium">{{ $pedido->codigo_rastreamento ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Data envio</div>
                        <div class="text-gray-900 font-medium">
                            {{ !empty($pedido->data_envio) ? \Carbon\Carbon::parse($pedido->data_envio)->format('d/m/Y H:i') : '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-500">Entrega prevista</div>
                        <div class="text-gray-900 font-medium">
                            {{ !empty($pedido->data_entrega_prevista) ? \Carbon\Carbon::parse($pedido->data_entrega_prevista)->format('d/m/Y') : '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-500">Entrega realizada</div>
                        <div class="text-gray-900 font-medium">
                            {{ !empty($pedido->data_entrega_realizada) ? \Carbon\Carbon::parse($pedido->data_entrega_realizada)->format('d/m/Y H:i') : '—' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Observações</h2>

                <div class="text-sm text-gray-800 whitespace-pre-line">
                    {{ $pedido->observacoes ?? '—' }}
                </div>
            </div>
        </div>
    </div>
@endsection
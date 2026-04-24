@extends('layouts.portal-cliente')

@section('title', 'Meus Pedidos - Portal do Cliente')

@section('content')
<div class="space-y-6">

    <!-- Cabeçalho -->
    <div class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Meus Pedidos</h1>
            <p class="text-gray-600 text-sm">Acompanhe seus pedidos e compras</p>
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-500">Total de pedidos</p>
            <p class="text-lg font-semibold text-gray-800">{{ $pedidos->count() }}</p>
        </div>
    </div>

    <!-- Lista -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-800">Pedidos</h2>
            <a href="{{ route('portal.dashboard') }}" class="text-sm text-blue-600 hover:text-blue-700">
                Voltar ao Dashboard
            </a>
        </div>

        @if(($pedidos->count() ?? 0) === 0)
            <div class="p-6 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-gray-400"></i>
                </div>
                <p class="mt-3 text-sm font-semibold text-gray-800">Você ainda não fez nenhum pedido</p>
                <p class="text-sm text-gray-600 mt-1">Quando fizer, eles aparecerão aqui.</p>

                <a href="{{ route('portal.dashboard') }}"
                   class="inline-block mt-4 bg-blue-500 hover:bg-blue-600 text-white text-sm px-4 py-2 rounded-md transition duration-200">
                    Ir para o Dashboard
                </a>
            </div>
        @else

            <!-- Tabela (desktop) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pedido</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pagamento</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Valor Total</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Origem</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($pedidos as $pedido)
                            @php
                                // Badge para status do pedido
                                $statusPedido = strtolower($pedido->status_pedido ?? '');
                                $badgePedido = 'bg-gray-100 text-gray-700';
                                if (in_array($statusPedido, ['pago', 'enviado', 'concluido'])) $badgePedido = 'bg-green-100 text-green-700';
                                if (in_array($statusPedido, ['processando'])) $badgePedido = 'bg-blue-100 text-blue-700';
                                if (in_array($statusPedido, ['cancelado'])) $badgePedido = 'bg-red-100 text-red-700';

                                // Badge para status do pagamento
                                $statusPagamento = strtolower($pedido->status_pagamento ?? '');
                                $badgePagamento = 'bg-gray-100 text-gray-700';
                                if (in_array($statusPagamento, ['aprovado'])) $badgePagamento = 'bg-green-100 text-green-700';
                                if (in_array($statusPagamento, ['rejeitado', 'estornado'])) $badgePagamento = 'bg-red-100 text-red-700';
                            @endphp

                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <p class="text-sm font-semibold text-gray-800">{{ $pedido->numero_pedido }}</p>
                                    <p class="text-xs text-gray-500">ID: {{ $pedido->id }}</p>
                                </td>

                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ !empty($pedido->data_pedido) ? \Carbon\Carbon::parse($pedido->data_pedido)->format('d/m/Y H:i') : 'N/A' }}
                                </td>

                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $badgePedido }}">
                                        {{ $pedido->status_pedido ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $badgePagamento }}">
                                        {{ $pedido->status_pagamento ?? '-' }}
                                    </span>
                                    @if($pedido->forma_pagamento)
                                        <p class="text-xs text-gray-500 mt-1">{{ $pedido->forma_pagamento }}</p>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-sm font-semibold text-gray-800">
                                    R$ {{ number_format($pedido->valor_total ?? 0, 2, ',', '.') }}
                                </td>

                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $pedido->origem_pedido ?? '-' }}
                                </td>

                                <td class="px-4 py-3 text-center space-x-1">
                                    @if(in_array(strtolower($pedido->status_pagamento ?? ''), ['pendente', 'rejeitado', 'estornado', '']))
                                        <a href="{{ route('portal.mercadopago.checkout', $pedido->id) }}" class="inline-flex items-center justify-center bg-green-500 hover:bg-green-600 text-white text-xs px-3 py-2 rounded-md transition duration-200">
                                            <i class="fas fa-money-bill-wave mr-1"></i> Pagar
                                        </a>
                                    @endif
                                    <button onclick="toggleDetalhes({{ $pedido->id }})"
                                            class="inline-flex items-center justify-center bg-blue-500 hover:bg-blue-600 text-white text-xs px-3 py-2 rounded-md transition duration-200">
                                        Detalhes
                                    </button>
                                </td>
                            </tr>

                            <!-- Linha expansível com itens do pedido -->
                            <tr id="detalhes-{{ $pedido->id }}" class="hidden bg-gray-50">
                                <td colspan="7" class="px-4 py-4">
                                    <div class="space-y-3">
                                        <h4 class="text-sm font-semibold text-gray-800">Itens do Pedido</h4>
                                        
                                        @php
                                            $itensPedido = DB::table('items_pedido as ip')
                                                ->join('items as i', 'i.id', '=', 'ip.item_id')
                                                ->where('ip.pedido_id', $pedido->id)
                                                ->select([
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
                                                ])
                                                ->get();
                                        @endphp

                                        @if($itensPedido->count() > 0)
                                            <div class="space-y-2">
                                                @foreach($itensPedido as $item)
                                                    <div class="flex items-center justify-between bg-white p-3 rounded-md border border-gray-200">
                                                        <div>
                                                            <p class="text-sm font-semibold text-gray-800">{{ $item->nome_do_produto }}</p>
                                                            <p class="text-xs text-gray-500">
                                                                Código: {{ $item->codigo }} • 
                                                                {{ $item->marca }} • 
                                                                {{ $item->estado }} • 
                                                                {{ $item->cor }} • 
                                                                Tam: {{ $item->tamanho }}
                                                            </p>
                                                        </div>
                                                        <div class="text-right">
                                                            <p class="text-sm font-semibold text-gray-800">
                                                                R$ {{ number_format($item->valor_total ?? 0, 2, ',', '.') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-600">Nenhum item encontrado para este pedido.</p>
                                        @endif

                                        <!-- Informações adicionais do pedido -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                            @if($pedido->codigo_rastreamento)
                                                <div>
                                                    <p class="text-xs text-gray-500">Código de Rastreamento</p>
                                                    <p class="text-sm font-semibold text-gray-800">{{ $pedido->codigo_rastreamento }}</p>
                                                </div>
                                            @endif

                                            @if($pedido->data_envio)
                                                <div>
                                                    <p class="text-xs text-gray-500">Data de Envio</p>
                                                    <p class="text-sm font-semibold text-gray-800">
                                                        {{ \Carbon\Carbon::parse($pedido->data_envio)->format('d/m/Y H:i') }}
                                                    </p>
                                                </div>
                                            @endif

                                            @if($pedido->data_entrega_prevista)
                                                <div>
                                                    <p class="text-xs text-gray-500">Entrega Prevista</p>
                                                    <p class="text-sm font-semibold text-gray-800">
                                                        {{ \Carbon\Carbon::parse($pedido->data_entrega_prevista)->format('d/m/Y') }}
                                                    </p>
                                                </div>
                                            @endif

                                            @if($pedido->data_entrega_realizada)
                                                <div>
                                                    <p class="text-xs text-gray-500">Entrega Realizada</p>
                                                    <p class="text-sm font-semibold text-gray-800">
                                                        {{ \Carbon\Carbon::parse($pedido->data_entrega_realizada)->format('d/m/Y H:i') }}
                                                    </p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Cards (mobile) -->
            <div class="md:hidden divide-y divide-gray-200">
                @foreach($pedidos as $pedido)
                    @php
                        $statusPedido = strtolower($pedido->status_pedido ?? '');
                        $badgePedido = 'bg-gray-100 text-gray-700';
                        if (in_array($statusPedido, ['pago', 'enviado', 'concluido'])) $badgePedido = 'bg-green-100 text-green-700';
                        if (in_array($statusPedido, ['processando'])) $badgePedido = 'bg-blue-100 text-blue-700';
                        if (in_array($statusPedido, ['cancelado'])) $badgePedido = 'bg-red-100 text-red-700';

                        $statusPagamento = strtolower($pedido->status_pagamento ?? '');
                        $badgePagamento = 'bg-gray-100 text-gray-700';
                        if (in_array($statusPagamento, ['aprovado'])) $badgePagamento = 'bg-green-100 text-green-700';
                        if (in_array($statusPagamento, ['rejeitado', 'estornado'])) $badgePagamento = 'bg-red-100 text-red-700';
                    @endphp

                    <div class="p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ $pedido->numero_pedido }}</p>
                                <p class="text-xs text-gray-500">ID: {{ $pedido->id }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ !empty($pedido->data_pedido) ? \Carbon\Carbon::parse($pedido->data_pedido)->format('d/m/Y H:i') : 'N/A' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $badgePedido }} mb-1">
                                    {{ $pedido->status_pedido ?? '-' }}
                                </span>
                                <p class="text-lg font-bold text-gray-800">
                                    R$ {{ number_format($pedido->valor_total ?? 0, 2, ',', '.') }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-xs text-gray-500">Pagamento</p>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $badgePagamento }}">
                                    {{ $pedido->status_pagamento ?? '-' }}
                                </span>
                                @if($pedido->forma_pagamento)
                                    <p class="text-xs text-gray-500 mt-1">{{ $pedido->forma_pagamento }}</p>
                                @endif
                            </div>

                            <div>
                                <p class="text-xs text-gray-500">Origem</p>
                                <p class="text-gray-800">{{ $pedido->origem_pedido ?? '-' }}</p>
                            </div>
                        </div>

                        <div class="mt-3 space-y-2">
                            @if(in_array(strtolower($pedido->status_pagamento ?? ''), ['pendente', 'rejeitado', 'estornado', '']))
                                <a href="{{ route('portal.mercadopago.checkout', $pedido->id) }}" class="w-full bg-green-500 hover:bg-green-600 text-white text-sm py-2 rounded-md transition duration-200 flex justify-center items-center">
                                    <i class="fas fa-money-bill-wave mr-2"></i> Pagar
                                </a>
                            @endif
                            <button onclick="toggleDetalhes({{ $pedido->id }})"
                                    class="w-full bg-blue-500 hover:bg-blue-600 text-white text-sm py-2 rounded-md transition duration-200">
                                Ver Detalhes
                            </button>
                        </div>

                        <!-- Detalhes expansíveis (mobile) -->
                        <div id="detalhes-{{ $pedido->id }}" class="hidden mt-4 space-y-3">
                            <h4 class="text-sm font-semibold text-gray-800">Itens do Pedido</h4>
                            
                            @php
                                $itensPedido = DB::table('items_pedido as ip')
                                    ->join('items as i', 'i.id', '=', 'ip.item_id')
                                    ->where('ip.pedido_id', $pedido->id)
                                    ->select([
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
                                    ])
                                    ->get();
                            @endphp

                            @if($itensPedido->count() > 0)
                                <div class="space-y-2">
                                    @foreach($itensPedido as $item)
                                        <div class="bg-gray-50 p-3 rounded-md border border-gray-200">
                                            <p class="text-sm font-semibold text-gray-800">{{ $item->nome_do_produto }}</p>
                                            <p class="text-xs text-gray-500">
                                                Código: {{ $item->codigo }} • {{ $item->marca }} • {{ $item->estado }} • {{ $item->cor }} • Tam: {{ $item->tamanho }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                Quantidade: {{ $item->quantidade }} • Status: {{ $item->status_item }}
                                            </p>
                                            <p class="text-sm font-semibold text-gray-800 mt-1">
                                                R$ {{ number_format($item->valor_total ?? 0, 2, ',', '.') }}
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-600">Nenhum item encontrado para este pedido.</p>
                            @endif

                            <!-- Informações adicionais -->
                            <div class="grid grid-cols-1 gap-3">
                                @if($pedido->codigo_rastreamento)
                                    <div>
                                        <p class="text-xs text-gray-500">Código de Rastreamento</p>
                                        <p class="text-sm font-semibold text-gray-800">{{ $pedido->codigo_rastreamento }}</p>
                                    </div>
                                @endif

                                @if($pedido->data_envio)
                                    <div>
                                        <p class="text-xs text-gray-500">Data de Envio</p>
                                        <p class="text-sm font-semibold text-gray-800">
                                            {{ \Carbon\Carbon::parse($pedido->data_envio)->format('d/m/Y H:i') }}
                                        </p>
                                    </div>
                                @endif

                                @if($pedido->data_entrega_prevista)
                                    <div>
                                        <p class="text-xs text-gray-500">Entrega Prevista</p>
                                        <p class="text-sm font-semibold text-gray-800">
                                            {{ \Carbon\Carbon::parse($pedido->data_entrega_prevista)->format('d/m/Y') }}
                                        </p>
                                    </div>
                                @endif

                                @if($pedido->data_entrega_realizada)
                                    <div>
                                        <p class="text-xs text-gray-500">Entrega Realizada</p>
                                        <p class="text-sm font-semibold text-gray-800">
                                            {{ \Carbon\Carbon::parse($pedido->data_entrega_realizada)->format('d/m/Y H:i') }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="p-4 border-t border-gray-200 flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Total de pedidos: <span class="font-semibold text-gray-800">{{ $pedidos->count() }}</span>
                </p>
                <a href="{{ route('portal.dashboard') }}" class="text-sm text-blue-600 hover:text-blue-700">
                    Voltar
                </a>
            </div>

        @endif
    </div>

</div>

<script>
function toggleDetalhes(pedidoId) {
    const detalhes = document.getElementById('detalhes-' + pedidoId);
    detalhes.classList.toggle('hidden');
}
</script>
@endsection
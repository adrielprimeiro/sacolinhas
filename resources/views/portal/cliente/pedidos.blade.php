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
                                        <a href="{{ route('portal.checkout.show', $pedido->id) }}" class="inline-flex items-center justify-center bg-green-500 hover:bg-green-600 text-white text-xs px-3 py-2 rounded-md transition duration-200">
                                            <i class="fas fa-money-bill-wave mr-1"></i> Pagar
                                        </a>
                                    @endif
                                    <a href="{{ route('portal.pedidos.show', $pedido->id) }}"
                                             class="inline-flex items-center justify-center bg-blue-500 hover:bg-blue-600 text-white text-xs px-3 py-2 rounded-md transition duration-200">
                                         Detalhes
                                     </a>
                                    @if(strtolower($pedido->status_pedido ?? '') === 'pendente')
                                        <button onclick="confirmarCancelamento({{ $pedido->id }})"
                                                class="inline-flex items-center justify-center bg-red-500 hover:bg-red-600 text-white text-xs px-3 py-2 rounded-md transition duration-200">
                                            <i class="fas fa-times mr-1"></i> Cancelar
                                        </button>
                                    @endif
                                </td>
                            </tr>

                            <!-- Linha expansível com itens do pedido -->
                            <tr id="detalhes-desktop-{{ $pedido->id }}" class="hidden bg-gray-50">
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
                                            <div class="mt-4 pt-3 border-t border-gray-200 space-y-1 text-right max-w-xs ml-auto">
                                                <div class="flex justify-between text-xs text-gray-500">
                                                    <span>Subtotal Itens:</span>
                                                    <span class="font-medium text-gray-800">R$ {{ number_format($itensPedido->sum('valor_total'), 2, ',', '.') }}</span>
                                                </div>
                                                <div class="flex justify-between text-xs text-gray-500">
                                                    <span>Frete:</span>
                                                    <span class="font-medium text-gray-800">R$ {{ number_format($pedido->valor_frete ?? 0, 2, ',', '.') }}</span>
                                                </div>
                                                <div class="flex justify-between text-sm font-bold text-gray-900 pt-1 border-t border-gray-100">
                                                    <span>Total Pago:</span>
                                                    <span>R$ {{ number_format($pedido->valor_total ?? 0, 2, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-600">Nenhum item encontrado para este pedido.</p>
                                        @endif
                                    </div>
                                    
                                    <!-- Acompanhamento e Informações -->
                                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <!-- Timeline de Rastreamento -->
                                        <div>
                                            <h4 class="text-sm font-semibold text-gray-800 mb-4 flex items-center">
                                                <i class="fas fa-route text-blue-500 mr-2"></i> Acompanhamento da Entrega
                                            </h4>
                                            
                                            @php
                                                $rastreamentos = DB::table('pedido_rastreamentos')
                                                    ->where('pedido_id', $pedido->id)
                                                    ->orderBy('data_hora', 'desc')
                                                    ->get();
                                            @endphp

                                            @if($rastreamentos->count() > 0)
                                                <div class="relative pl-8 border-l-2 border-gray-200 space-y-6">
                                                    @foreach($rastreamentos as $index => $rastreio)
                                                        @php
                                                            $isUltimo = ($index === 0);
                                                            $icon = 'fas fa-check';
                                                            $color = 'text-gray-400';
                                                            $bgClass = 'bg-gray-100';
                                                            
                                                            if ($rastreio->status === 'Entregue') {
                                                                $icon = 'fas fa-box-open';
                                                                $color = 'text-green-500';
                                                                $bgClass = 'bg-green-100';
                                                            } elseif ($rastreio->status === 'Saiu para entrega') {
                                                                $icon = 'fas fa-truck-fast';
                                                                $color = 'text-blue-500';
                                                                $bgClass = 'bg-blue-100';
                                                            } elseif ($rastreio->status === 'Em trânsito') {
                                                                $icon = 'fas fa-truck';
                                                                $color = 'text-blue-500';
                                                                $bgClass = 'bg-blue-100';
                                                            } elseif ($rastreio->status === 'Postado') {
                                                                $icon = 'fas fa-box';
                                                                $color = 'text-orange-500';
                                                                $bgClass = 'bg-orange-100';
                                                            }
                                                        @endphp
                                                        <div class="relative">
                                                            <div class="absolute mt-1 h-6 w-6 rounded-full {{ $bgClass }} flex items-center justify-center border-2 border-white shadow-sm" style="left: -44px;">
                                                                <i class="{{ $icon }} text-[10px] {{ $color }}"></i>
                                                            </div>
                                                            <div>
                                                                <p class="text-sm font-semibold {{ $isUltimo ? 'text-gray-900' : 'text-gray-600' }}">{{ $rastreio->status }}</p>
                                                                @if($rastreio->descricao)
                                                                    <p class="text-xs text-gray-500 mt-0.5">{{ $rastreio->descricao }}</p>
                                                                @endif
                                                                <p class="text-[10px] text-gray-400 mt-1 font-medium">
                                                                    {{ \Carbon\Carbon::parse($rastreio->data_hora)->format('d/m/Y \à\s H:i') }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="bg-gray-50 rounded p-4 text-center border border-gray-100">
                                                    <i class="fas fa-truck-loading text-gray-400 text-lg mb-2 block"></i>
                                                    <p class="text-xs text-gray-500">Nenhuma atualização de rastreamento disponível ainda.</p>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Informações adicionais do pedido -->
                                        <div>
                                            <h4 class="text-sm font-semibold text-gray-800 mb-4 flex items-center">
                                                <i class="fas fa-info-circle text-gray-400 mr-2"></i> Detalhes do Envio
                                            </h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                                <a href="{{ route('portal.checkout.show', $pedido->id) }}" class="w-full bg-green-500 hover:bg-green-600 text-white text-sm py-2 rounded-md transition duration-200 flex justify-center items-center">
                                    <i class="fas fa-money-bill-wave mr-2"></i> Pagar
                                </a>
                            @endif
                            <a href="{{ route('portal.pedidos.show', $pedido->id) }}"
                                     class="w-full bg-blue-500 hover:bg-blue-600 text-white text-sm py-2 rounded-md transition duration-200 flex justify-center items-center">
                                Ver Detalhes
                            </a>
                            @if(strtolower($pedido->status_pedido ?? '') === 'pendente')
                                <button onclick="confirmarCancelamento({{ $pedido->id }})"
                                        class="w-full bg-red-500 hover:bg-red-600 text-white text-sm py-2 rounded-md transition duration-200 flex justify-center items-center">
                                    <i class="fas fa-times mr-2"></i> Cancelar Pedido
                                </button>
                            @endif
                        </div>

                        <!-- Detalhes expansíveis (mobile) -->
                        <div id="detalhes-mobile-{{ $pedido->id }}" class="hidden mt-4 space-y-3">
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

                                    <div class="mt-3 pt-3 border-t border-gray-200 space-y-1">
                                        <div class="flex justify-between text-xs text-gray-500">
                                            <span>Subtotal Itens:</span>
                                            <span class="font-medium text-gray-800">R$ {{ number_format($itensPedido->sum('valor_total'), 2, ',', '.') }}</span>
                                        </div>
                                        <div class="flex justify-between text-xs text-gray-500">
                                            <span>Frete:</span>
                                            <span class="font-medium text-gray-800">R$ {{ number_format($pedido->valor_frete ?? 0, 2, ',', '.') }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm font-bold text-gray-900 pt-1 border-t border-gray-100">
                                            <span>Total Pago:</span>
                                            <span>R$ {{ number_format($pedido->valor_total ?? 0, 2, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <p class="text-sm text-gray-600">Nenhum item encontrado para este pedido.</p>
                            @endif

                            <div class="border-t border-gray-200 mt-4 pt-4">
                                <h4 class="text-sm font-semibold text-gray-800 mb-4 flex items-center">
                                    <i class="fas fa-route text-blue-500 mr-2"></i> Acompanhamento da Entrega
                                </h4>
                                
                                @php
                                    $rastreamentos = DB::table('pedido_rastreamentos')
                                        ->where('pedido_id', $pedido->id)
                                        ->orderBy('data_hora', 'desc')
                                        ->get();
                                @endphp

                                @if($rastreamentos->count() > 0)
                                    <div class="relative pl-8 border-l-2 border-gray-200 space-y-6">
                                        @foreach($rastreamentos as $index => $rastreio)
                                            @php
                                                $isUltimo = ($index === 0);
                                                $icon = 'fas fa-check';
                                                $color = 'text-gray-400';
                                                $bgClass = 'bg-gray-100';
                                                
                                                if ($rastreio->status === 'Entregue') {
                                                    $icon = 'fas fa-box-open';
                                                    $color = 'text-green-500';
                                                    $bgClass = 'bg-green-100';
                                                } elseif ($rastreio->status === 'Saiu para entrega') {
                                                    $icon = 'fas fa-truck-fast';
                                                    $color = 'text-blue-500';
                                                    $bgClass = 'bg-blue-100';
                                                } elseif ($rastreio->status === 'Em trânsito') {
                                                    $icon = 'fas fa-truck';
                                                    $color = 'text-blue-500';
                                                    $bgClass = 'bg-blue-100';
                                                } elseif ($rastreio->status === 'Postado') {
                                                    $icon = 'fas fa-box';
                                                    $color = 'text-orange-500';
                                                    $bgClass = 'bg-orange-100';
                                                }
                                            @endphp
                                            <div class="relative">
                                                <div class="absolute mt-1 h-6 w-6 rounded-full {{ $bgClass }} flex items-center justify-center border-2 border-white shadow-sm" style="left: -44px;">
                                                    <i class="{{ $icon }} text-[10px] {{ $color }}"></i>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold {{ $isUltimo ? 'text-gray-900' : 'text-gray-600' }}">{{ $rastreio->status }}</p>
                                                    @if($rastreio->descricao)
                                                        <p class="text-xs text-gray-500 mt-0.5">{{ $rastreio->descricao }}</p>
                                                    @endif
                                                    <p class="text-[10px] text-gray-400 mt-1 font-medium">
                                                        {{ \Carbon\Carbon::parse($rastreio->data_hora)->format('d/m/Y \à\s H:i') }}
                                                    </p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="bg-gray-50 rounded p-4 text-center border border-gray-100">
                                        <i class="fas fa-truck-loading text-gray-400 text-lg mb-2 block"></i>
                                        <p class="text-xs text-gray-500">Nenhuma atualização de rastreamento disponível ainda.</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Informações adicionais -->
                            <div class="border-t border-gray-200 mt-4 pt-4">
                                <h4 class="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                                    <i class="fas fa-info-circle text-gray-400 mr-2"></i> Detalhes do Envio
                                </h4>
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

<!-- Modal de Confirmação de Cancelamento -->
<div id="cancelModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <!-- Backdrop com desfoque blur -->
    <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity duration-300"></div>
    
    <!-- Caixa do modal -->
    <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6 transform scale-95 transition-transform duration-300 z-10">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                <i class="fas fa-exclamation-triangle text-lg"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Confirmar Cancelamento</h3>
        </div>
        
        <p class="text-sm text-gray-600 mb-6">
            Aviso: Ao cancelar este pedido, todos os itens voltarão para sua sacolinha. Deseja realmente prosseguir?
        </p>
        
        <div class="flex justify-end gap-3">
            <button onclick="fecharModalCancelamento()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-md transition duration-200 focus:outline-none focus:ring-2 focus:ring-gray-300">
                Não
            </button>
            <button id="btnConfirmarCancelamento" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-md transition duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 shadow-sm flex items-center gap-2">
                <span>Sim</span>
            </button>
        </div>
    </div>
</div>

<script>
function toggleDetalhes(pedidoId, type) {
    const detalhes = document.getElementById('detalhes-' + type + '-' + pedidoId);
    if (detalhes) {
        detalhes.classList.toggle('hidden');
    }
}

let pedidoIdParaCancelar = null;

function confirmarCancelamento(pedidoId) {
    pedidoIdParaCancelar = pedidoId;
    const modal = document.getElementById('cancelModal');
    if (modal) {
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.querySelector('.transform').classList.remove('scale-95');
            modal.querySelector('.transform').classList.add('scale-100');
        }, 10);
    }
}

function fecharModalCancelamento() {
    const modal = document.getElementById('cancelModal');
    if (modal) {
        modal.querySelector('.transform').classList.remove('scale-100');
        modal.querySelector('.transform').classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            pedidoIdParaCancelar = null;
        }, 150);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const btnConfirmar = document.getElementById('btnConfirmarCancelamento');
    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', function() {
            if (!pedidoIdParaCancelar) return;
            
            const btn = this;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Processando...';
            
            fetch(`/checkout/${pedidoIdParaCancelar}/cancelar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                fecharModalCancelamento();
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Erro ao cancelar o pedido: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ocorreu um erro ao processar o cancelamento.');
                fecharModalCancelamento();
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }
});

// Exibe mensagem de sucesso se vier redirecionado após o pagamento Pix
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === 'pagamento_confirmado') {
        // Limpa o parâmetro da URL para evitar reexibição no recarregamento
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);

        // Cria e insere o banner de sucesso no topo
        const alertDiv = document.createElement('div');
        alertDiv.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6 flex justify-between items-center shadow-md animate-bounce';
        alertDiv.innerHTML = `
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-green-600 text-lg"></i>
                <div>
                    <strong class="font-bold">Pagamento Confirmado!</strong>
                    <span class="block sm:inline text-sm">Seu pagamento via Pix foi recebido com sucesso e o pedido já está pago.</span>
                </div>
            </div>
            <button onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900 focus:outline-none">
                <i class="fas fa-times"></i>
            </button>
        `;

        const container = document.querySelector('.space-y-6');
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);
        }

        // Remove automaticamente após 7 segundos
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.classList.add('opacity-0', 'transition-opacity', 'duration-500');
                setTimeout(() => alertDiv.remove(), 500);
            }
        }, 7000);
    }
});
</script>
@endsection
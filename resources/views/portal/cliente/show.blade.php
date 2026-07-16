@extends('layouts.portal-cliente')

@section('title', 'Pedido #' . ($pedido->numero_pedido ?? $pedido->id) . ' - Portal do Cliente')

@section('content')
<div class="space-y-6">

    <!-- Cabeçalho -->
    <div class="bg-white rounded-lg shadow-sm p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('portal.pedidos') }}" class="inline-flex items-center justify-center w-10 h-10 rounded-md bg-gray-50 hover:bg-gray-100 border border-gray-200 transition duration-200" title="Voltar para Pedidos">
                <i class="fas fa-arrow-left text-gray-700"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-800">Pedido {{ $pedido->numero_pedido }}</h1>
                <p class="text-gray-600 text-sm">Criado em {{ !empty($pedido->data_pedido) ? \Carbon\Carbon::parse($pedido->data_pedido)->format('d/m/Y \à\s H:i') : 'N/A' }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            @if(in_array(strtolower($pedido->status_pagamento ?? ''), ['pendente', 'rejeitado', 'estornado', '']))
                <a href="{{ route('portal.checkout.show', $pedido->id) }}" class="inline-flex items-center justify-center bg-green-500 hover:bg-green-600 text-white text-sm px-4 py-2 rounded-md font-semibold transition duration-200 shadow-sm w-full sm:w-auto">
                    <i class="fas fa-money-bill-wave mr-2"></i> Pagar Agora
                </a>
            @endif
            @if(strtolower($pedido->status_pedido ?? '') === 'pendente')
                <button onclick="confirmarCancelamento({{ $pedido->id }})" class="inline-flex items-center justify-center bg-red-500 hover:bg-red-600 text-white text-sm px-4 py-2 rounded-md font-semibold transition duration-200 shadow-sm w-full sm:w-auto">
                    <i class="fas fa-times mr-2"></i> Cancelar Pedido
                </button>
            @endif
        </div>
    </div>

    <!-- Grid Geral -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Coluna da Esquerda (Detalhes e Itens) -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Resumo e Itens -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-200">
                    <h2 class="text-sm font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-shopping-bag text-blue-500 mr-2"></i> Itens do Pedido
                    </h2>
                </div>
                
                <div class="divide-y divide-gray-100">
                    @foreach($itensPedido as $item)
                        <div class="p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold text-gray-800">{{ $item->nome_do_produto }}</h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    Código: <span class="font-medium text-gray-700">{{ $item->codigo }}</span> • 
                                    Marca: <span class="font-medium text-gray-700">{{ $item->marca }}</span> • 
                                    Tamanho: <span class="font-medium text-gray-700">{{ $item->tamanho }}</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Estado: <span class="font-medium text-gray-700">{{ $item->estado }}</span> • 
                                    Cor: <span class="font-medium text-gray-700">{{ $item->cor }}</span>
                                </p>
                            </div>
                            <div class="text-left sm:text-right w-full sm:w-auto">
                                <p class="text-sm font-semibold text-gray-800">R$ {{ number_format($item->valor_total, 2, ',', '.') }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $item->quantidade }}x R$ {{ number_format($item->preco_unitario, 2, ',', '.') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="bg-gray-50 p-4 border-t border-gray-100 space-y-2">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Subtotal:</span>
                        <span class="font-medium text-gray-800">R$ {{ number_format($itensPedido->sum('valor_total'), 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Frete:</span>
                        <span class="font-medium text-gray-800">R$ {{ number_format($pedido->valor_frete ?? 0, 2, ',', '.') }}</span>
                    </div>
                    @if($pedido->valor_desconto > 0)
                        <div class="flex justify-between text-sm text-green-600">
                            <span>Desconto:</span>
                            <span class="font-medium">- R$ {{ number_format($pedido->valor_desconto, 2, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-base font-bold text-gray-900 pt-2 border-t border-gray-200">
                        <span>Total:</span>
                        <span>R$ {{ number_format($pedido->valor_total ?? 0, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Detalhes da Entrega -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <h2 class="text-sm font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-map-marker-alt text-red-500 mr-2"></i> Endereço de Entrega
                </h2>
                @if($pedido->endereco_entrega)
                    <div class="space-y-1 text-sm text-gray-600">
                        <p class="font-semibold text-gray-800">{{ $pedido->endereco_entrega }}</p>
                        <p>CEP: {{ $pedido->cep_entrega }} • {{ $pedido->cidade_entrega }} - {{ $pedido->estado_entrega }}</p>
                    </div>
                @else
                    <p class="text-sm text-gray-500 italic">Endereço de entrega não disponível.</p>
                @endif
            </div>
            
        </div>
        
        <!-- Coluna da Direita (Acompanhamento e Status) -->
        <div class="space-y-6">
            
            <!-- Informações Resumidas do Pedido -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-4">
                <h2 class="text-sm font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-info-circle text-gray-400 mr-2"></i> Informações do Pedido
                </h2>
                
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-gray-500">Status do Pedido</p>
                        @php
                            $statusPedido = strtolower($pedido->status_pedido ?? '');
                            $badgePedido = 'bg-gray-100 text-gray-700';
                            if (in_array($statusPedido, ['pago', 'enviado', 'concluido'])) $badgePedido = 'bg-green-100 text-green-700';
                            if (in_array($statusPedido, ['processando'])) $badgePedido = 'bg-blue-100 text-blue-700';
                            if (in_array($statusPedido, ['cancelado'])) $badgePedido = 'bg-red-100 text-red-700';
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgePedido }} mt-1">
                            {{ $pedido->status_pedido ?? '-' }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Status do Pagamento</p>
                        @php
                            $statusPagamento = strtolower($pedido->status_pagamento ?? '');
                            $badgePagamento = 'bg-gray-100 text-gray-700';
                            if (in_array($statusPagamento, ['aprovado'])) $badgePagamento = 'bg-green-100 text-green-700';
                            if (in_array($statusPagamento, ['rejeitado', 'estornado'])) $badgePagamento = 'bg-red-100 text-red-700';
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgePagamento }} mt-1">
                            {{ $pedido->status_pagamento ?? '-' }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Forma de Pagamento</p>
                        <p class="font-semibold text-gray-800 mt-1">{{ $pedido->forma_pagamento ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Origem</p>
                        <p class="font-semibold text-gray-800 mt-1">{{ $pedido->origem_pedido ?? 'N/A' }}</p>
                    </div>
                    @if($pedido->codigo_rastreamento)
                        <div class="col-span-2">
                            <p class="text-xs text-gray-500">Código de Rastreamento</p>
                            <p class="font-bold text-blue-600 mt-1 flex items-center gap-1">
                                <span>{{ $pedido->codigo_rastreamento }}</span>
                                <button onclick="navigator.clipboard.writeText('{{ $pedido->codigo_rastreamento }}'); alert('Código copiado!');" class="text-gray-400 hover:text-gray-600 ml-1">
                                    <i class="far fa-copy text-xs"></i>
                                </button>
                            </p>
                        </div>
                    @endif
                    @if($pedido->data_envio)
                        <div>
                            <p class="text-xs text-gray-500">Data de Envio</p>
                            <p class="font-semibold text-gray-800 mt-1">{{ \Carbon\Carbon::parse($pedido->data_envio)->format('d/m/Y H:i') }}</p>
                        </div>
                    @endif
                    @if($pedido->data_entrega_prevista)
                        <div>
                            <p class="text-xs text-gray-500">Entrega Prevista</p>
                            <p class="font-semibold text-gray-800 mt-1">{{ \Carbon\Carbon::parse($pedido->data_entrega_prevista)->format('d/m/Y') }}</p>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Acompanhamento da Entrega -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <h2 class="text-sm font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-route text-blue-500 mr-2"></i> Acompanhamento
                </h2>
                
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
            
        </div>
        
    </div>

    <!-- Modal de Confirmação de Cancelamento -->
    <div id="cancelModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity duration-300"></div>
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
                <button onclick="fecharModalCancelamento()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-md transition duration-200 focus:outline-none">
                    Não
                </button>
                <button id="btnConfirmarCancelamento" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-md transition duration-200 focus:outline-none shadow-sm flex items-center gap-2">
                    <span>Sim</span>
                </button>
            </div>
        </div>
    </div>

</div>

<script>
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
                    window.location.href = "{{ route('portal.pedidos') }}";
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
</script>
@endsection

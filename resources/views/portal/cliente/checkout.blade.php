@extends('layouts.portal-cliente')

@section('title', 'Revisão do Pedido')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Cabeçalho -->
    <div class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Revisão do Pedido</h1>
            <p class="text-gray-600 text-sm">Resumo dos itens e escolha do frete</p>
        </div>
        <div class="text-right">
            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold uppercase">
                Passo 1 de 2
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Coluna Esquerda: Itens e Frete -->
        <div class="md:col-span-2 space-y-6">
            
            <!-- Lista de Itens -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-sm font-bold text-gray-800 uppercase">Itens no Pedido</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($itens as $item)
                    <div class="p-4 flex gap-4">
                        <div class="w-16 h-16 bg-gray-100 rounded-md overflow-hidden flex-shrink-0">
                            @if($item->image)
                                <img src="{{ asset('storage/' . $item->image) }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fas fa-image text-gray-300 text-2xl"></i>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-bold text-gray-800">{{ $item->nome_do_produto }}</h3>
                            <p class="text-xs text-gray-500">{{ $item->marca }} • {{ $item->tamanho }}</p>
                            <p class="text-sm font-bold text-gray-900 mt-1">R$ {{ number_format($item->preco_unitario, 2, ',', '.') }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Seleção de Frete -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <h2 class="text-sm font-bold text-gray-800 uppercase">Opções de Envio</h2>
                    <div class="text-xs text-gray-500">
                        CEP: <span class="font-bold">{{ auth()->user()->cep ?? 'Não informado' }}</span>
                    </div>
                </div>
                <div class="p-4">
                    @if(count($shippingOptions) > 0)
                        <div class="space-y-3" id="shippingOptionsContainer">
                            @foreach($shippingOptions as $option)
                            <label class="relative flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition border-gray-200">
                                <input type="radio" name="shipping_option" value="{{ $option['id'] }}" 
                                       data-price="{{ $option['price'] }}"
                                       data-name="{{ $option['name'] }} ({{ $option['company']['name'] }})"
                                       class="shipping-radio h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center gap-2">
                                        <img src="{{ $option['company']['picture'] }}" class="h-6 object-contain">
                                        <span class="text-sm font-bold text-gray-800">{{ $option['name'] }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500">Até {{ $option['delivery_time'] }} dias úteis</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-bold text-gray-900">R$ {{ number_format($option['price'], 2, ',', '.') }}</span>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6">
                            <i class="fas fa-truck-loading text-gray-300 text-4xl mb-2"></i>
                            <p class="text-sm text-gray-500">Não foi possível calcular o frete. Verifique seu CEP no perfil.</p>
                            <a href="{{ route('portal.perfil') }}?return_to={{ urlencode(url()->current()) }}" class="text-blue-600 text-xs font-bold mt-2 inline-block">ATUALIZAR MEU CEP</a>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- Coluna Direita: Resumo e Ação -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-6">
                <h2 class="text-sm font-bold text-gray-800 uppercase mb-4">Resumo do Valor</h2>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal itens</span>
                        <span>R$ {{ number_format($itens->sum('preco_unitario'), 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Frete</span>
                        <span id="displayFrete">R$ 0,00</span>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex justify-between font-bold text-lg text-gray-900">
                        <span>Total</span>
                        <span id="displayTotal">R$ {{ number_format($itens->sum('preco_unitario'), 2, ',', '.') }}</span>
                    </div>
                </div>

                <div class="mt-8 space-y-3">
                    <button id="btnConfirmarPedido" 
                            disabled
                            class="w-full bg-gray-200 text-gray-400 font-bold py-3 rounded-lg cursor-not-allowed transition duration-200 flex items-center justify-center gap-2">
                        IR PARA O PAGAMENTO <i class="fas fa-arrow-right"></i>
                    </button>
                    
                    <button id="btnCancelarCheckout" 
                            class="w-full text-gray-500 hover:text-red-600 text-xs font-bold py-2 transition">
                        CANCELAR E VOLTAR ITENS PARA SACOLA
                    </button>
                </div>

                <p class="text-[10px] text-gray-400 mt-4 text-center">
                    Ao confirmar, os itens serão removidos da sacolinha e transformados em um pedido aguardando pagamento.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('.shipping-radio');
    const displayFrete = document.getElementById('displayFrete');
    const displayTotal = document.getElementById('displayTotal');
    const btnConfirmar = document.getElementById('btnConfirmarPedido');
    const btnCancelar = document.getElementById('btnCancelarCheckout');
    
    const subtotal = parseFloat({{ $itens->sum('preco_unitario') }});
    let selectedShipping = null;

    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            const price = parseFloat(this.dataset.price);
            selectedShipping = {
                id: this.value,
                price: price,
                name: this.dataset.name
            };

            // Atualiza UI
            displayFrete.textContent = 'R$ ' + price.toLocaleString('pt-BR', {minimumFractionDigits: 2});
            const total = subtotal + price;
            displayTotal.textContent = 'R$ ' + total.toLocaleString('pt-BR', {minimumFractionDigits: 2});

            // Habilita botão
            btnConfirmar.disabled = false;
            btnConfirmar.classList.remove('bg-gray-200', 'text-gray-400', 'cursor-not-allowed');
            btnConfirmar.classList.add('bg-green-600', 'hover:bg-green-700', 'text-white', 'cursor-pointer');
        });
    });

    // Ação Confirmar
    btnConfirmar.addEventListener('click', async function() {
        if (!selectedShipping) return;
        
        btnConfirmar.disabled = true;
        btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PROCESSANDO...';

        try {
            const response = await fetch("{{ route('portal.checkout.confirmar', $pedido->id) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    shipping_id: selectedShipping.id,
                    shipping_price: selectedShipping.price,
                    shipping_name: selectedShipping.name
                })
            });

            const data = await response.json();

            if (data.success && data.redirect) {
                window.location.href = data.redirect;
            } else {
                alert('Erro ao confirmar pedido: ' + (data.message || 'Erro desconhecido'));
                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = 'IR PARA O PAGAMENTO <i class="fas fa-arrow-right"></i>';
            }
        } catch (error) {
            console.error(error);
            alert('Erro de comunicação com o servidor.');
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = 'IR PARA O PAGAMENTO <i class="fas fa-arrow-right"></i>';
        }
    });

    // Ação Cancelar
    btnCancelar.addEventListener('click', async function() {
        if (!confirm('Deseja realmente cancelar? Os itens voltarão para sua sacolinha.')) return;

        btnCancelar.disabled = true;
        try {
            const response = await fetch("{{ route('portal.checkout.cancelar', $pedido->id) }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            if (data.success) {
                window.location.href = "{{ route('portal.sacolinha') }}";
            } else {
                alert('Erro: ' + data.message);
                btnCancelar.disabled = false;
            }
        } catch (error) {
            console.error(error);
            alert('Erro ao cancelar checkout.');
            btnCancelar.disabled = false;
        }
    });
});
</script>
@endsection

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
            
            @if(!empty($valorCobrar))
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                            <i class="fas fa-hand-holding-usd text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-blue-800">Pagamento Parcial Autorizado</p>
                            <p class="text-xs text-blue-600">Você está efetuando o pagamento de uma parcela acordada deste pedido.</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-lg font-black text-blue-800">R$ {{ number_format($valorCobrar, 2, ',', '.') }}</span>
                    </div>
                </div>
            @endif

            <!-- Seleção de Forma de Pagamento -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-sm font-bold text-gray-800 uppercase">Meio de Pagamento</h2>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        
                        <!-- Opção Pix -->
                        <label class="relative flex flex-col p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition border-blue-500 bg-blue-50/30" id="label-pay-pix">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-teal-100 rounded-full flex items-center justify-center text-teal-600">
                                        <i class="fas fa-qrcode text-lg"></i>
                                    </div>
                                    <div>
                                        <span class="text-sm font-bold text-gray-800">Pix</span>
                                        <span class="block text-[10px] text-green-600 font-bold uppercase">Aprovação Imediata</span>
                                    </div>
                                </div>
                                <input type="radio" name="payment_method" value="pix" checked
                                       class="payment-radio h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Pague via Banco Inter. QR Code e Copia e Cola gerados na próxima tela.</p>
                        </label>

                        <!-- Opção Cartão de Crédito -->
                        <label class="relative flex flex-col p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition border-gray-200" id="label-pay-card">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600">
                                        <i class="far fa-credit-card text-lg"></i>
                                    </div>
                                    <div>
                                        <span class="text-sm font-bold text-gray-800">Cartão de Crédito</span>
                                        <span class="block text-[10px] text-gray-500 uppercase">Mercado Pago</span>
                                    </div>
                                </div>
                                <input type="radio" name="payment_method" value="cartao_credito"
                                       class="payment-radio h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Pague via Mercado Pago. Parcele em até 12x no cartão de crédito.</p>
                        </label>

                    </div>
                </div>
            </div>

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
                    @if($pedido->valor_frete > 0 || $pedido->origem_pedido === 'admin')
                        <div class="p-4 bg-blue-50 border border-blue-100 rounded-lg flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-blue-800">Frete Fixo / Definido</p>
                                    <p class="text-xs text-blue-600 italic">Valor definido pela administração para este pedido.</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-lg font-black text-blue-800">R$ {{ number_format($pedido->valor_frete, 2, ',', '.') }}</span>
                            </div>
                        </div>
                        <input type="hidden" id="fixedShippingValue" value="{{ $pedido->valor_frete }}">
                    @elseif(count($shippingOptions) > 0)
                        <div class="space-y-3" id="shippingOptionsContainer">
                            @foreach($shippingOptions as $option)
                            <label class="shipping-label relative flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition border-gray-200" data-id="{{ $option['id'] }}">
                                <input type="radio" name="shipping_option" value="{{ $option['id'] }}" 
                                       data-price="{{ $option['price'] }}"
                                       data-name="{{ $option['name'] }} ({{ $option['company']['name'] ?? 'Retirada' }})"
                                       class="shipping-radio h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center gap-2">
                                        @if(!empty($option['company']['picture']))
                                            <img src="{{ $option['company']['picture'] }}" class="h-6 object-contain">
                                        @endif
                                        <span class="text-sm font-bold text-gray-800">{{ $option['name'] }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        @if($option['id'] === 'retirada')
                                            Retirar diretamente na loja física
                                        @else
                                            Até {{ $option['delivery_time'] }} dias úteis
                                        @endif
                                    </p>
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

                    @php
                        $saldoUsado = (float)($pedido->valor_saldo_utilizado ?? 0);
                    @endphp

                    @if($saldoUsado != 0)
                    <div class="flex justify-between items-center py-2 border-t border-b border-gray-50">
                        <span class="text-gray-600">Ajuste de Carteira</span>
                        @if($saldoUsado > 0)
                            <span class="text-green-600 font-bold">- R$ {{ number_format($saldoUsado, 2, ',', '.') }}</span>
                        @else
                            <span class="text-red-600 font-bold">+ R$ {{ number_format(abs($saldoUsado), 2, ',', '.') }}</span>
                        @endif
                    </div>
                    @endif

                    <div class="border-t border-gray-100 pt-3 flex justify-between font-bold text-lg text-gray-900">
                        <span>Total do Pedido</span>
                        <span id="displayTotal">R$ {{ number_format(max(0, $itens->sum('preco_unitario') - $saldoUsado), 2, ',', '.') }}</span>
                    </div>

                    @if(!empty($valorCobrar))
                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 mt-2 flex justify-between items-center text-blue-800 font-bold text-sm">
                        <span>A Pagar Neste Link</span>
                        <span>R$ {{ number_format($valorCobrar, 2, ',', '.') }}</span>
                    </div>
                    @endif
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
    const shippingLabels = document.querySelectorAll('.shipping-label');
    const displayFrete = document.getElementById('displayFrete');
    const displayTotal = document.getElementById('displayTotal');
    const btnConfirmar = document.getElementById('btnConfirmarPedido');
    const btnCancelar = document.getElementById('btnCancelarCheckout');
    
    const paymentRadios = document.querySelectorAll('.payment-radio');
    const labelPix = document.getElementById('label-pay-pix');
    const labelCard = document.getElementById('label-pay-card');

    paymentRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'pix') {
                labelPix.classList.add('border-blue-500', 'bg-blue-50/30');
                labelPix.classList.remove('border-gray-200');
                labelCard.classList.remove('border-blue-500', 'bg-blue-50/30');
                labelCard.classList.add('border-gray-200');
            } else {
                labelCard.classList.add('border-blue-500', 'bg-blue-50/30');
                labelCard.classList.remove('border-gray-200');
                labelPix.classList.remove('border-blue-500', 'bg-blue-50/30');
                labelPix.classList.add('border-gray-200');
            }
        });
    });

    const subtotal = parseFloat({{ $itens->sum('preco_unitario') }});
    const saldoUsado = parseFloat({{ (float)($pedido->valor_saldo_utilizado ?? 0) }});
    let selectedShipping = null;

    function atualizarTotal(frete) {
        displayFrete.textContent = 'R$ ' + frete.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        const total = Math.max(0, subtotal + frete - saldoUsado);
        displayTotal.textContent = 'R$ ' + total.toLocaleString('pt-BR', {minimumFractionDigits: 2});
    }

    // Se já houver frete fixo definido
    const fixedShippingInput = document.getElementById('fixedShippingValue');
    if (fixedShippingInput) {
        const price = parseFloat(fixedShippingInput.value);
        selectedShipping = {
            id: 'fixed',
            price: price,
            name: 'Frete Fixo'
        };

        // Atualiza UI Inicial
        atualizarTotal(price);

        // Habilita botão imediatamente
        btnConfirmar.disabled = false;
        btnConfirmar.classList.remove('bg-gray-200', 'text-gray-400', 'cursor-not-allowed');
        btnConfirmar.classList.add('bg-green-600', 'hover:bg-green-700', 'text-white', 'cursor-pointer');
    }

    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            const price = parseFloat(this.dataset.price);
            selectedShipping = {
                id: this.value,
                price: price,
                name: this.dataset.name
            };

            // Atualiza UI
            atualizarTotal(price);

            // Destaca visualmente o card selecionado e reseta os outros
            shippingLabels.forEach(label => {
                if (label.dataset.id === this.value) {
                    label.classList.add('border-blue-500', 'bg-blue-50/30');
                    label.classList.remove('border-gray-200');
                } else {
                    label.classList.remove('border-blue-500', 'bg-blue-50/30');
                    label.classList.add('border-gray-200');
                }
            });

            // Habilita botão
            btnConfirmar.disabled = false;
            btnConfirmar.classList.remove('bg-gray-200', 'text-gray-400', 'cursor-not-allowed');
            btnConfirmar.classList.add('bg-green-600', 'hover:bg-green-700', 'text-white', 'cursor-pointer');
        });
    });

    // Permite selecionar clicando em qualquer parte do card do frete
    shippingLabels.forEach(label => {
        label.addEventListener('click', function(e) {
            // Se clicou diretamente no input radio, deixa o fluxo padrão
            if (e.target.tagName === 'INPUT') return;
            
            const radio = this.querySelector('.shipping-radio');
            if (radio && !radio.checked) {
                radio.checked = true;
                // Dispara o evento change para executar a lógica de atualização
                radio.dispatchEvent(new Event('change'));
            }
        });
    });

    // Ação Confirmar
    btnConfirmar.addEventListener('click', async function() {
        if (!selectedShipping) return;
        
        btnConfirmar.disabled = true;
        btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PROCESSANDO...';

        try {
            const urlParams = new URLSearchParams(window.location.search);
            const customValue = urlParams.get('valor');
            let confirmUrl = "{{ route('portal.checkout.confirmar', $pedido->id) }}";
            if (customValue) {
                confirmUrl += "?valor=" + encodeURIComponent(customValue);
            }

            const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked').value;

            const response = await fetch(confirmUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    shipping_id: selectedShipping.id,
                    shipping_price: selectedShipping.price,
                    shipping_name: selectedShipping.name,
                    payment_method: selectedPaymentMethod
                })
            });

            let rawText = await response.text();
            const idx = rawText.lastIndexOf('{"success":');
            if (idx > 0) rawText = rawText.substring(idx);
            const data = JSON.parse(rawText);

            if (data.success && data.redirect) {
                window.location.href = data.redirect;
            } else {
                alert('Erro ao confirmar pedido: ' + (data.message || 'Erro desconhecido'));
                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = 'IR PARA O PAGAMENTO <i class="fas fa-arrow-right"></i>';
            }
        } catch (error) {
            console.error(error);
            alert('Erro de comunicação com o servidor: ' + error.message);
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
            let rawText = await response.text();
            const idx = rawText.lastIndexOf('{"success":');
            if (idx > 0) rawText = rawText.substring(idx);
            const data = JSON.parse(rawText);
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

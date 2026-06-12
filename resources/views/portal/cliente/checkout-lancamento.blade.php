@extends('layouts.portal-cliente')

@section('title', 'Revisão da Recarga')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Cabeçalho -->
    <div class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-12 w-auto object-contain">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Recarga de Carteira</h1>
                <p class="text-gray-600 text-sm">Selecione o meio de pagamento para adicionar saldo</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Coluna Esquerda: Forma de Pagamento -->
        <div class="md:col-span-2 space-y-6">
            
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

        </div>

        <!-- Coluna Direita: Resumo e Ação -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-6">
                <h2 class="text-sm font-bold text-gray-800 uppercase mb-4">Resumo do Valor</h2>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Valor da Recarga</span>
                        <span>R$ {{ number_format($lancamento->valor_total, 2, ',', '.') }}</span>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex justify-between font-bold text-base text-gray-900">
                        <span>Total a Pagar</span>
                        <span>R$ {{ number_format($lancamento->valor_total, 2, ',', '.') }}</span>
                    </div>
                </div>

                <div class="mt-8">
                    <button type="button" id="btnConfirmarRecarga"
                            class="w-full text-center bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-xl transition duration-200 flex items-center justify-center gap-2 shadow-md">
                        IR PARA O PAGAMENTO <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnConfirmar = document.getElementById('btnConfirmarRecarga');
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

    btnConfirmar.addEventListener('click', async function() {
        btnConfirmar.disabled = true;
        btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PROCESSANDO...';

        const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked').value;

        try {
            const response = await fetch("{{ route('portal.checkout_lancamento.confirmar', $lancamento->id) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    payment_method: selectedPaymentMethod
                })
            });

            const data = await response.json();

            if (data.success && data.redirect) {
                window.location.href = data.redirect;
            } else {
                alert('Erro: ' + (data.message || 'Erro desconhecido'));
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
});
</script>
@endsection

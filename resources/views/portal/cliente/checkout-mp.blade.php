@extends('layouts.portal-cliente')

@section('title', 'Checkout Mercado Pago')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    
    <div class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Finalizar Pagamento</h1>
            <p class="text-gray-600 text-sm">Pedido #{{ $pedido->numero_pedido }}</p>
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-500">Valor Total</p>
            <p class="text-lg font-semibold text-gray-800">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</p>
        </div>
    </div>

    <!-- Div onde o resultado do PIX/Boleto ou sucesso vai aparecer -->
    <div id="payment-result" class="hidden bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-center">
        <div id="result-success" class="hidden">
            <div class="mx-auto w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-4">
                <i class="fas fa-check text-2xl text-green-600"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-800 mb-2">Pagamento Aprovado!</h2>
            <p class="text-gray-600 mb-6">Seu pagamento foi processado com sucesso e o pedido já está atualizado.</p>
            <a href="{{ route('portal.pedidos') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md font-semibold">
                Voltar aos Meus Pedidos
            </a>
        </div>

        <div id="result-pix" class="hidden">
            <h2 class="text-xl font-bold text-gray-800 mb-2">Pague com PIX</h2>
            <p class="text-gray-600 mb-6">Escaneie o QR Code abaixo ou copie o código PIX para finalizar seu pagamento. O status do pedido será atualizado automaticamente assim que você pagar.</p>
            
            <div class="flex justify-center mb-4">
                <img id="pix-qrcode-img" src="" alt="QR Code PIX" class="w-48 h-48 border rounded-md p-2">
            </div>

            <div class="max-w-md mx-auto">
                <label class="block text-sm font-medium text-gray-700 text-left mb-1">Código Copia e Cola:</label>
                <div class="flex mt-1 relative rounded-md shadow-sm">
                    <input type="text" id="pix-copia-cola" readonly class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-l-md p-2 bg-gray-50">
                    <button onclick="copiarPix()" class="inline-flex items-center px-4 py-2 border border-l-0 border-gray-300 rounded-r-md bg-blue-50 text-blue-700 hover:bg-blue-100 text-sm font-medium">
                        Copiar
                    </button>
                </div>
            </div>

            <div class="mt-8">
                <a href="{{ route('portal.pedidos') }}" class="text-blue-600 hover:underline">Voltar aos Meus Pedidos</a>
            </div>
        </div>

        <div id="result-ticket" class="hidden">
            <h2 class="text-xl font-bold text-gray-800 mb-2">Boleto Gerado</h2>
            <p class="text-gray-600 mb-6">Seu boleto foi gerado com sucesso. Clique no botão abaixo para visualizar ou imprimir.</p>
            
            <a id="ticket-url" href="#" target="_blank" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md font-semibold inline-block mb-6">
                Visualizar Boleto
            </a>

            <div>
                <a href="{{ route('portal.pedidos') }}" class="text-blue-600 hover:underline">Voltar aos Meus Pedidos</a>
            </div>
        </div>
    </div>

    <!-- Container do Mercado Pago Brick -->
    <div id="payment-container" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div id="paymentBrick_container"></div>
    </div>

</div>

<!-- SDK Mercado Pago -->
<script src="https://sdk.mercadopago.com/js/v2"></script>
<script>
    const mp = new MercadoPago('{{ $publicKey }}', {
        locale: 'pt-BR'
    });
    const bricksBuilder = mp.bricks();

    const renderPaymentBrick = async (bricksBuilder) => {
        const settings = {
            initialization: {
                amount: {{ $pedido->valor_total }},
                payer: {
                    email: "{{ auth()->user()->email }}"
                }
            },
            customization: {
                visual: {
                    style: {
                        theme: 'default', // ou 'dark' ou 'bootstrap'
                    }
                },
                paymentMethods: {
                    creditCard: "all",
                    debitCard: "all",
                    ticket: "all",
                    bankTransfer: "all",
                },
            },
            callbacks: {
                onReady: () => {
                    // Brick renderizado
                },
                onSubmit: ({ selectedPaymentMethod, formData }) => {
                    // Aqui processamos o envio no nosso backend
                    return new Promise((resolve, reject) => {
                        fetch("{{ route('portal.mercadopago.process', $pedido->id) }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                "Accept": "application/json"
                            },
                            body: JSON.stringify(formData)
                        })
                        .then(async (response) => {
                            if (!response.ok) {
                                let text = await response.text();
                                console.error("Error Response:", text);
                                throw new Error("Erro HTTP " + response.status + ". Veja o console.");
                            }
                            return response.json();
                        })
                        .then((data) => {
                            if (data.error) {
                                reject();
                                alert("Erro ao processar pagamento: " + data.error);
                            } else {
                                resolve();
                                processPaymentResult(data, selectedPaymentMethod);
                            }
                        })
                        .catch((error) => {
                            reject();
                            console.error(error);
                            alert("Erro de comunicação com o servidor: " + error.message);
                        })
                    });
                },
                onError: (error) => {
                    console.error(error);
                },
            },
        };
        window.paymentBrickController = await bricksBuilder.create(
            'payment',
            'paymentBrick_container',
            settings
        );
    };

    renderPaymentBrick(bricksBuilder);

    function processPaymentResult(data, paymentMethod) {
        // Esconde o container do brick
        document.getElementById('payment-container').classList.add('hidden');
        const resultContainer = document.getElementById('payment-result');
        resultContainer.classList.remove('hidden');

        if (paymentMethod === 'bank_transfer') { // PIX
            document.getElementById('result-pix').classList.remove('hidden');
            document.getElementById('pix-qrcode-img').src = 'data:image/jpeg;base64,' + data.qr_code_base64;
            document.getElementById('pix-copia-cola').value = data.qr_code;
        } else if (paymentMethod === 'ticket') { // Boleto ou lotérica
            document.getElementById('result-ticket').classList.remove('hidden');
            document.getElementById('ticket-url').href = data.ticket_url;
        } else { // Cartões (aprovados imediatamente ou pendentes)
            if (data.status === 'approved') {
                document.getElementById('result-success').classList.remove('hidden');
            } else if (data.status === 'in_process' || data.status === 'pending') {
                document.getElementById('result-success').classList.remove('hidden');
                document.getElementById('result-success').innerHTML = `
                    <div class="mx-auto w-16 h-16 rounded-full bg-yellow-100 flex items-center justify-center mb-4">
                        <i class="fas fa-clock text-2xl text-yellow-600"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800 mb-2">Pagamento em Análise</h2>
                    <p class="text-gray-600 mb-6">Seu pagamento está sendo processado. O status será atualizado em breve.</p>
                    <a href="{{ route('portal.pedidos') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md font-semibold">
                        Voltar aos Meus Pedidos
                    </a>
                `;
            } else {
                resultContainer.innerHTML = `<h2 class="text-xl font-bold text-red-600 mb-2">Pagamento Recusado</h2><p class="mb-4">Houve um problema com seu pagamento.</p><a href="javascript:location.reload()" class="bg-blue-500 text-white px-4 py-2 rounded">Tentar Novamente</a>`;
            }
        }
    }

    function copiarPix() {
        var copyText = document.getElementById("pix-copia-cola");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value);
        alert("Código PIX copiado!");
    }
</script>
@endsection

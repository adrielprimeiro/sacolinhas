@extends('layouts.portal-cliente')

@section('title', 'Checkout Mercado Pago - Recarga')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    
    <div class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-12 w-auto object-contain">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Adicionar Saldo à Carteira</h1>
                <p class="text-gray-600 text-sm">Recarga #{{ $lancamento->id }}</p>
            </div>
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-500">Valor da Recarga</p>
            <p class="text-lg font-semibold text-gray-800">R$ {{ number_format($valorCobrar, 2, ',', '.') }}</p>
        </div>
    </div>

    <!-- Div onde o resultado do sucesso vai aparecer -->
    <div id="payment-result" class="hidden bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-center">
        <div id="result-success" class="hidden">
            <div class="mx-auto w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-4">
                <i class="fas fa-check text-2xl text-green-600"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-800 mb-2">Recarga Aprovada!</h2>
            <p class="text-gray-600 mb-6">Seu pagamento foi processado com sucesso. O saldo já está disponível na sua carteira.</p>
            <a href="{{ route('portal.dashboard') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md font-semibold">
                Visualizar Meu Painel
            </a>
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
                amount: {{ max(0.01, (float)$valorCobrar) }},
                payer: {
                    email: "{{ auth()->user()->email }}"
                }
            },
            customization: {
                visual: {
                    style: {
                        theme: 'default',
                    }
                },
                paymentMethods: {
                    creditCard: "all",
                    debitCard: "all",
                    ticket: "all",
                },
            },
            callbacks: {
                onReady: () => {
                    // Brick pronto
                },
                onSubmit: ({ selectedPaymentMethod, formData }) => {
                    return new Promise((resolve, reject) => {
                        fetch("{{ route('portal.mercadopago.process_lancamento', $lancamento->id) }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                "Accept": "application/json"
                            },
                            body: JSON.stringify(formData)
                        })
                        .then(async (response) => {
                            if (response.ok) {
                                const data = await response.json();
                                if (data.status === 'approved') {
                                    document.getElementById('payment-container').classList.add('hidden');
                                    document.getElementById('payment-result').classList.remove('hidden');
                                    document.getElementById('result-success').classList.remove('hidden');
                                    resolve();
                                } else {
                                    alert('Pagamento não aprovado. Status: ' + (data.status || 'Pendente'));
                                    reject();
                                }
                            } else {
                                const errText = await response.text();
                                alert('Erro ao processar pagamento: ' + errText);
                                reject();
                            }
                        })
                        .catch((error) => {
                            console.error(error);
                            alert('Erro de rede ao processar o pagamento.');
                            reject();
                        });
                    });
                },
                onError: (error) => {
                    console.error(error);
                    alert('Erro no Mercado Pago Brick.');
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
</script>
@endsection

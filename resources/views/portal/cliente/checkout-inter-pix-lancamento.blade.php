@extends('layouts.portal-cliente')

@section('title', 'Pagamento - Pix Banco Inter')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Card Principal com Efeito Premium de Sombra e Borda Gradiente -->
    <div class="relative bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-2xl">
        <!-- Barra de Destaque Gradiente no Topo -->
        <div class="h-2 bg-gradient-to-r from-teal-400 via-emerald-400 to-green-500"></div>

        <div class="p-6 sm:p-8 text-center">
            <!-- Logo da Empresa -->
            <div class="mb-6">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-14 mx-auto object-contain">
            </div>

            <!-- Ícone e Título Principal -->
            <div class="mx-auto w-12 h-12 bg-teal-50 rounded-full flex items-center justify-center mb-4 animate-pulse">
                <i class="fas fa-qrcode text-2xl text-teal-600"></i>
            </div>
            
            <h1 class="text-2xl font-black text-gray-800 tracking-tight">Recarga via Pix</h1>
            <p class="text-sm text-gray-500 mt-1">O saldo será adicionado à sua carteira assim que o pagamento for confirmado.</p>

            <!-- Valor Destacado -->
            <div class="my-6 p-4 bg-teal-50 border border-teal-100 rounded-xl inline-block px-8" style="background-color: rgba(240, 253, 250, 0.5); border-color: rgba(204, 251, 241, 0.5);">
                <span class="text-xs text-teal-800 font-bold uppercase tracking-wider block">Valor da Recarga</span>
                <span class="text-3xl font-black text-teal-900">R$ {{ number_format($valorCobrar, 2, ',', '.') }}</span>
            </div>

            <!-- QR Code com Moldura Elegante -->
            <div class="flex flex-col items-center justify-center my-6">
                <div class="relative p-3 bg-white border-2 rounded-2xl shadow-md group hover:border-teal-500 transition duration-300" style="border-color: rgba(20, 184, 166, 0.3);">
                    <!-- Overlay Decorativo nas Bordas -->
                    <div class="absolute -top-1 -left-1 w-4 h-4 border-t-2 border-l-2 border-teal-500"></div>
                    <div class="absolute -top-1 -right-1 w-4 h-4 border-t-2 border-r-2 border-teal-500"></div>
                    <div class="absolute -bottom-1 -left-1 w-4 h-4 border-b-2 border-l-2 border-teal-500"></div>
                    <div class="absolute -bottom-1 -right-1 w-4 h-4 border-b-2 border-r-2 border-teal-500"></div>
                    
                    <img id="inter-pix-qrcode" 
                          src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($pixCopiaECola) }}" 
                          alt="QR Code Pix Banco Inter" 
                          class="w-48 h-48 sm:w-56 sm:h-56 object-contain">
                </div>
                <p class="text-xs text-gray-400 mt-2">Abra o app do seu banco e aponte a câmera para o QR Code acima.</p>
            </div>

            <!-- Código Copia e Cola -->
            <div class="max-w-md mx-auto my-6">
                <label class="block text-xs font-bold text-gray-500 text-left mb-1 uppercase tracking-wider">Código Pix Copia e Cola</label>
                <div class="relative flex items-center bg-gray-50 border border-gray-200 rounded-xl overflow-hidden shadow-inner p-1 group hover:border-teal-500/50 transition duration-200">
                    <input type="text" 
                           id="pix-copia-cola-text" 
                           value="{{ $pixCopiaECola }}" 
                           readonly 
                           class="w-full text-xs text-gray-700 bg-transparent border-0 outline-none focus:ring-0 pl-3 pr-20 py-2 select-all font-mono truncate">
                    
                    <button onclick="copiarPixCodigo()" 
                            id="btn-copiar-pix"
                            class="absolute right-1 top-1 bottom-1 px-4 bg-orange-50 hover:bg-orange-100 active:scale-95 text-orange-600 hover:text-orange-700 text-xs font-sans font-bold rounded-lg transition duration-200 border border-orange-200 flex items-center gap-1">
                        <i class="far fa-copy"></i> <span>Copiar</span>
                    </button>
                </div>
            </div>

            <!-- Indicador Dinâmico de Aguardando Pagamento -->
            <div class="border-t border-gray-100 pt-6 mt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3 text-left">
                    <div class="relative flex h-8 w-8 items-center justify-center">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 bg-teal-500"></span>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-800">Aguardando confirmação...</p>
                        <p class="text-xs text-gray-400">Identificamos o pagamento em tempo real.</p>
                    </div>
                </div>
                <div>
                    <a href="{{ route('portal.pedidos') }}" class="text-xs font-bold text-gray-400 hover:text-gray-600 transition">
                        Ver meus pedidos <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Overlay de Sucesso com Efeito Glassmorphic (Inicialmente Oculto) -->
<div id="sucesso-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-80 backdrop-filter backdrop-blur-md flex items-center justify-center z-50 opacity-0 pointer-events-none transition-all duration-500">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full text-center mx-4 transform scale-90 transition-all duration-500" id="sucesso-card">
        <div class="mx-auto w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-6 animate-bounce">
            <i class="fas fa-check text-4xl text-green-600"></i>
        </div>
        <h2 class="text-2xl font-black text-gray-800">Recarga Confirmada!</h2>
        <p class="text-gray-600 text-sm mt-2">Obrigado! Seu pagamento Pix foi recebido e o saldo foi adicionado à sua carteira.</p>
        <div class="h-1 bg-green-100 rounded-full w-full my-6 overflow-hidden">
            <div class="h-full bg-green-600 w-0 rounded-full" id="success-redirect-bar" style="width: 0%; transition: width 3s linear;"></div>
        </div>
        <a href="{{ route('portal.dashboard') }}" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 px-6 rounded-xl transition duration-200 shadow-lg inline-block">
            Ir Para o Painel
        </a>
    </div>
</div>

<script>
    function copiarPixCodigo() {
        const copyText = document.getElementById("pix-copia-cola-text");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        
        navigator.clipboard.writeText(copyText.value)
            .then(() => {
                const btn = document.getElementById("btn-copiar-pix");
                const originalText = btn.innerHTML;
                
                btn.classList.remove("bg-orange-50", "hover:bg-orange-100", "text-orange-600", "hover:text-orange-700", "border-orange-200");
                btn.classList.add("bg-green-50", "text-green-600", "border-green-200");
                btn.innerHTML = '<i class="fas fa-check"></i> <span>Copiado!</span>';
                
                setTimeout(() => {
                    btn.classList.remove("bg-green-50", "text-green-600", "border-green-200");
                    btn.classList.add("bg-orange-50", "hover:bg-orange-100", "text-orange-600", "hover:text-orange-700", "border-orange-200");
                    btn.innerHTML = originalText;
                }, 2000);
            })
            .catch(err => {
                alert("Erro ao copiar o código. Por favor, selecione e copie manualmente.");
            });
    }

    const statusUrl = "{{ route('portal.inter.checkout_lancamento.status', $lancamento->id) }}";
    const redirectUrl = "{{ route('portal.dashboard') }}";
    
    let pollInterval = setInterval(async () => {
        try {
            const response = await fetch(statusUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (response.ok) {
                const data = await response.json();
                if (data.status === 'pago') {
                    clearInterval(pollInterval);
                    exibirSucessoEPromoveRedirect();
                }
            }
        } catch (error) {
            console.error("Erro no polling do Pix:", error);
        }
    }, 3000);

    function exibirSucessoEPromoveRedirect() {
        const overlay = document.getElementById("sucesso-overlay");
        const card = document.getElementById("sucesso-card");
        const bar = document.getElementById("success-redirect-bar");
        
        overlay.classList.remove("opacity-0", "pointer-events-none");
        card.classList.remove("scale-90");
        card.classList.add("scale-100");
        
        setTimeout(() => {
            bar.style.width = "100%";
        }, 50);

        setTimeout(() => {
            window.location.href = redirectUrl + "?success=recarga_confirmada";
        }, 3050);
    }
</script>
@endsection

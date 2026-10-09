<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos de Serviço - Minha Mania Brechó</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">
    <header class="bg-white border-b border-gray-200 py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3">
                <span class="text-xl font-black text-pink-600 tracking-tight">Minha Mania</span>
                <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">Brechó & Live Shopping</span>
            </a>
            <a href="/" class="text-xs font-bold text-gray-600 hover:text-gray-900">Voltar ao Início</a>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 py-10">
        <div class="bg-white p-8 sm:p-12 rounded-3xl border border-gray-200 shadow-sm space-y-8">
            <div>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">Termos de Serviço</h1>
                <p class="text-xs text-gray-500 mt-2">Última atualização: Outubro de 2026</p>
            </div>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-gray-900">1. Aceitação dos Termos</h2>
                <p class="text-sm leading-relaxed text-gray-600">
                    Ao acessar a plataforma da <strong>Minha Mania Brechó</strong>, participar de nossas transmissões de Live Shopping ou utilizar nosso sistema de sacolinhas e catálogo de produtos, você concorda em cumprir estes Termos de Serviço e todas as leis e regulamentos aplicáveis.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-gray-900">2. Funcionamento das Sacolinhas e Live Shopping</h2>
                <p class="text-sm leading-relaxed text-gray-600">
                    Nossa plataforma permite a reserva e compra de peças únicas de vestuário durante ou após eventos ao vivo. Os pedidos confirmados são adicionados à sacolinha do cliente para fechamento e cálculo de frete nos prazos estabelecidos.
                </p>
            </section>

            <section class="space-y-3 p-5 bg-indigo-50/60 rounded-2xl border border-indigo-100">
                <h2 class="text-lg font-bold text-indigo-950 flex items-center gap-2">
                    <i class="fab fa-youtube text-red-600"></i>
                    3. Uso dos Serviços de API do YouTube
                </h2>
                <p class="text-sm leading-relaxed text-indigo-900">
                    A plataforma integra-se com a <strong>YouTube Data API v3</strong> para disponibilizar vídeos de demonstração de peças e catálogo de produtos no formato YouTube Shorts.
                </p>
                <p class="text-sm leading-relaxed text-indigo-900">
                    Os usuários e administradores que utilizam as funcionalidades vinculadas ao YouTube concordam expressamente com os 
                    <a href="https://www.youtube.com/t/terms" target="_blank" rel="noopener noreferrer" class="font-bold underline text-indigo-700 hover:text-indigo-900">Termos de Serviço do YouTube</a> e com a 
                    <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer" class="font-bold underline text-indigo-700 hover:text-indigo-900">Política de Privacidade do Google</a>.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-gray-900">4. Pagamentos e Envios</h2>
                <p class="text-sm leading-relaxed text-gray-600">
                    As compras podem ser liquidadas através dos meios de pagamento integrados (PIX, Cartão de Crédito). O envio das mercadorias segue as opções de frete contratadas pelo cliente no momento do fechamento da sacolinha.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-gray-900">5. Contato</h2>
                <p class="text-sm leading-relaxed text-gray-600">
                    Em caso de dúvidas sobre nossos termos ou serviços, entre em contato através do e-mail: <strong class="text-gray-800">contato@minhamania.net</strong>.
                </p>
            </section>
        </div>
    </main>

    <footer class="text-center py-6 text-xs text-gray-400">
        &copy; {{ date('Y') }} Minha Mania Brechó. Todos os direitos reservados.
    </footer>
</body>
</html>

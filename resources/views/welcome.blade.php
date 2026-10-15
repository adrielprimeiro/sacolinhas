<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Minha Mania - Brechó & Live Shopping Online</title>
    <meta name="description" content="Moda consciente, peças exclusivas e compras ao vivo no TikTok e Instagram. Junte suas peças na sacolinha inteligente e economize no frete!">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#fdf2f8',
                            100: '#fce7f3',
                            500: '#ec4899',
                            600: '#db2777',
                            700: '#be185d',
                            900: '#831843',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased selection:bg-pink-500 selection:text-white flex flex-col min-h-screen">

    <!-- Top Announcement Bar -->
    <div class="bg-gradient-to-r from-pink-600 via-rose-600 to-purple-600 text-white text-xs font-bold py-2 px-4 text-center tracking-wide flex items-center justify-center gap-2">
        <span class="inline-flex items-center gap-1.5 bg-white/20 px-2 py-0.5 rounded-full text-[11px] uppercase tracking-wider font-extrabold">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
            Ao Vivo
        </span>
        <span>Acompanhe nossas transmissões de Live Shopping no <strong>TikTok</strong> e <strong>Instagram</strong>!</span>
    </div>

    <!-- Navigation Header -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-gray-200 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
            <!-- Brand Logo -->
            <a href="/" class="flex items-center gap-3 shrink-0">
                <img src="{{ asset('images/Logo_Grande_Preto.png') }}" alt="Minha Mania Brechó" class="h-10 sm:h-12 w-auto object-contain">
            </a>

            <!-- Nav Links (Desktop) -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-bold text-gray-700">
                <a href="#como-funciona" class="hover:text-pink-600 transition">Como Funciona</a>
                <a href="#destaques" class="hover:text-pink-600 transition">Destaques</a>
                <a href="{{ route('loja.index') }}" class="hover:text-pink-600 transition flex items-center gap-1.5">
                    <span>Loja Online</span>
                    <span class="text-[10px] font-black uppercase px-1.5 py-0.5 rounded-md bg-pink-100 text-pink-700">Novo</span>
                </a>
                <a href="#sobre" class="hover:text-pink-600 transition">Sobre Nós</a>
                <a href="https://minhamania.net/login" target="_blank" class="hover:text-pink-600 transition">Clube Mania</a>
            </nav>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2.5">
                <a href="{{ route('loja.index') }}" class="px-4 py-2.5 rounded-xl bg-pink-600 hover:bg-pink-700 text-white text-xs sm:text-sm font-extrabold shadow-sm hover:shadow transition active:scale-95 flex items-center gap-2 cursor-pointer" style="color: #ffffff !important;">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Ver Catálogo</span>
                </a>
                <a href="{{ route('login') }}" class="px-3.5 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs sm:text-sm font-bold transition flex items-center gap-1.5" title="Acesso Administrativo">
                    <i class="fas fa-lock text-xs text-gray-500"></i>
                    <span class="hidden sm:inline">ADM</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow">

        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-b from-pink-50/60 via-white to-gray-50 py-16 sm:py-24 border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    
                    <!-- Left: Hero Text -->
                    <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-pink-100/80 border border-pink-200 text-pink-800 text-xs font-black tracking-wide">
                            <i class="fas fa-fire text-pink-600"></i>
                            <span>A Nova Experiência de Compras ao Vivo</span>
                        </div>

                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-gray-900 tracking-tight leading-[1.15]">
                            Moda Consciente, Peças Únicas e <span class="bg-gradient-to-r from-pink-600 to-rose-600 bg-clip-text text-transparent">Live Shopping</span>
                        </h1>

                        <p class="text-base sm:text-lg text-gray-600 leading-relaxed max-w-2xl mx-auto lg:mx-0 font-medium">
                            Bem-vinda ao <strong>Minha Mania Brechó</strong>. Acompanhe nossas transmissões diárias no <strong>TikTok</strong> e <strong>Instagram</strong>, arremate achados imperdíveis diretamente pelo chat e monte sua sacolinha inteligente com até 15 dias para economizar no frete.
                        </p>

                        <!-- CTA Buttons -->
                        <div class="flex flex-wrap items-center justify-center lg:justify-start gap-3.5 pt-2">
                            <a href="{{ route('loja.index') }}" class="px-6 py-3.5 rounded-2xl bg-pink-600 hover:bg-pink-700 text-white font-extrabold text-sm shadow-lg shadow-pink-600/20 hover:shadow-xl transition active:scale-95 flex items-center gap-2.5 cursor-pointer" style="color: #ffffff !important;">
                                <i class="fas fa-shopping-bag"></i>
                                <span>Explorar Loja Online</span>
                                <i class="fas fa-arrow-right text-xs"></i>
                            </a>

                            <a href="#como-funciona" class="px-5 py-3.5 rounded-2xl bg-white hover:bg-gray-100 text-gray-800 font-extrabold text-sm border border-gray-300 shadow-xs hover:border-gray-400 transition active:scale-95 flex items-center gap-2">
                                <i class="fas fa-video text-pink-600"></i>
                                <span>Como Funcionam as Lives</span>
                            </a>
                        </div>

                        <!-- Highlights / Social Proof -->
                        <div class="pt-8 border-t border-gray-200/80 grid grid-cols-3 gap-4 text-center lg:text-left">
                            <div>
                                <p class="text-2xl sm:text-3xl font-black text-gray-900">+15.000</p>
                                <p class="text-xs text-gray-500 font-semibold mt-0.5">Peças Entregues</p>
                            </div>
                            <div>
                                <p class="text-2xl sm:text-3xl font-black text-pink-600">15 Dias</p>
                                <p class="text-xs text-gray-500 font-semibold mt-0.5">Prazo de Sacolinha</p>
                            </div>
                            <div>
                                <p class="text-2xl sm:text-3xl font-black text-gray-900">100%</p>
                                <p class="text-xs text-gray-500 font-semibold mt-0.5">Envio Seguro p/ Brasil</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Visual Card Preview -->
                    <div class="lg:col-span-5 flex justify-center">
                        <div class="relative w-full max-w-sm">
                            <!-- Background Glow -->
                            <div class="absolute -inset-4 bg-gradient-to-r from-pink-500 to-purple-600 rounded-3xl opacity-20 blur-2xl"></div>

                            <!-- Showcase Phone Card -->
                            <div class="relative bg-white rounded-3xl border border-gray-200 shadow-2xl p-5 overflow-hidden">
                                <!-- Card Header -->
                                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-pink-100 text-pink-600 flex items-center justify-center font-black text-xs">
                                            MM
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-black text-gray-900">Live Mania @tiktok</h4>
                                            <p class="text-[10px] text-gray-400">Transmissão em Tempo Real</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1 bg-red-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full uppercase" style="color: #ffffff !important;">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                        AO VIVO
                                    </span>
                                </div>

                                <!-- Card Center Preview -->
                                <div class="my-4 rounded-2xl bg-gradient-to-br from-pink-100 via-rose-50 to-indigo-50 p-6 text-center border border-pink-100">
                                    <div class="w-16 h-16 rounded-2xl bg-white shadow-md mx-auto flex items-center justify-center text-pink-600 text-2xl mb-3">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                    <span class="inline-block px-2.5 py-0.5 bg-indigo-600 text-white text-[11px] font-black rounded-lg mb-1" style="color: #ffffff !important;">
                                        Peça da Live #101
                                    </span>
                                    <h3 class="text-base font-black text-gray-900 mt-1">Vestido Midi Floral Selecionado</h3>
                                    <p class="text-xs text-gray-600 font-semibold mt-0.5">Tamanho M • Peça Única</p>
                                    <p class="text-xl font-black text-emerald-600 mt-2">R$ 29,90</p>
                                </div>

                                <!-- Card Simulation Chat -->
                                <div class="space-y-2 text-xs bg-gray-50 p-3 rounded-2xl border border-gray-100">
                                    <div class="flex items-start gap-2">
                                        <span class="font-bold text-pink-600 shrink-0">@mariana:</span>
                                        <span class="text-gray-700 bg-white px-2 py-0.5 rounded border border-gray-200 font-mono font-bold">101 MEU!</span>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <span class="font-black text-emerald-700 shrink-0"><i class="fas fa-check-circle"></i> Sistema:</span>
                                        <span class="text-emerald-800 font-bold">Peça #101 reservada para Mariana na Sacolinha!</span>
                                    </div>
                                </div>

                                <!-- Card Footer Button -->
                                <a href="{{ route('loja.index') }}" class="mt-4 w-full py-2.5 rounded-xl bg-gray-900 hover:bg-black text-white text-xs font-black text-center block transition cursor-pointer" style="color: #ffffff !important;">
                                    Acessar Catálogo Completo
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- Como Funciona Section -->
        <section id="como-funciona" class="py-16 sm:py-24 bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto space-y-3 mb-16">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-pink-600 bg-pink-50 px-3 py-1 rounded-full border border-pink-200">
                        Processo Simples & Prático
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">
                        Como Funciona o Live Shopping da Minha Mania?
                    </h2>
                    <p class="text-sm sm:text-base text-gray-600 leading-relaxed font-medium">
                        Comprar nas nossas lives é rápido, divertido e seguro. Veja os 4 passos para garantir suas peças favoritas:
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Step 1 -->
                    <div class="bg-gray-50 rounded-3xl p-6 border border-gray-200/80 shadow-xs hover:shadow-md transition">
                        <div class="w-12 h-12 rounded-2xl bg-pink-600 text-white flex items-center justify-center text-lg font-black mb-4 shadow-sm" style="color: #ffffff !important;">
                            1
                        </div>
                        <h3 class="text-base font-black text-gray-900 mb-2">Assista à Transmissão</h3>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            Acompanhe nossas lives no <strong>TikTok</strong> e <strong>Instagram</strong>. Mostramos cada peça em detalhes, caimento no corpo, tecido e medidas exatas.
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="bg-gray-50 rounded-3xl p-6 border border-gray-200/80 shadow-xs hover:shadow-md transition">
                        <div class="w-12 h-12 rounded-2xl bg-pink-600 text-white flex items-center justify-center text-lg font-black mb-4 shadow-sm" style="color: #ffffff !important;">
                            2
                        </div>
                        <h3 class="text-base font-black text-gray-900 mb-2">Comente no Chat</h3>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            Gostou da peça? Basta comentar o número sequencial de 3 dígitos exibido na tela (ex: <code>101</code>, <code>quero 101</code> ou <code>101 meu</code>) para arrematar.
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="bg-gray-50 rounded-3xl p-6 border border-gray-200/80 shadow-xs hover:shadow-md transition">
                        <div class="w-12 h-12 rounded-2xl bg-pink-600 text-white flex items-center justify-center text-lg font-black mb-4 shadow-sm" style="color: #ffffff !important;">
                            3
                        </div>
                        <h3 class="text-base font-black text-gray-900 mb-2">Sacolinha Inteligente</h3>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            Sua peça fica reservada na sua sacolinha digital por até <strong>15 dias</strong>. Você pode juntar compras de várias lives e pagar um frete só!
                        </p>
                    </div>

                    <!-- Step 4 -->
                    <div class="bg-gray-50 rounded-3xl p-6 border border-gray-200/80 shadow-xs hover:shadow-md transition">
                        <div class="w-12 h-12 rounded-2xl bg-pink-600 text-white flex items-center justify-center text-lg font-black mb-4 shadow-sm" style="color: #ffffff !important;">
                            4
                        </div>
                        <h3 class="text-base font-black text-gray-900 mb-2">Receba em Casa</h3>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            Finalize via PIX ou Cartão com segurança. Enviamos com rastreamento para qualquer lugar do Brasil com todo carinho e cuidado.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Destaques da Loja Section -->
        <section id="destaques" class="py-16 sm:py-24 bg-gray-50 border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-12">
                    <div>
                        <span class="text-xs font-extrabold uppercase tracking-wider text-pink-600 bg-pink-100/70 px-3 py-1 rounded-full border border-pink-200">
                            Vitrine Virtual
                        </span>
                        <h2 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight mt-2">
                            Peças em Destaque na Loja Online
                        </h2>
                    </div>
                    <a href="{{ route('loja.index') }}" class="px-5 py-2.5 rounded-xl bg-white hover:bg-gray-100 text-gray-900 font-extrabold text-xs sm:text-sm border border-gray-300 shadow-xs hover:border-gray-400 transition flex items-center gap-2 shrink-0">
                        <span>Ver Todos os Produtos</span>
                        <i class="fas fa-arrow-right text-xs text-pink-600"></i>
                    </a>
                </div>

                <!-- Product Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                    @forelse($featuredItems ?? [] as $prod)
                        @php
                            $imageCover = $prod->medias ? $prod->medias->first() : null;
                            $path = $imageCover?->thumbnail_url ?: $imageCover?->url ?: $prod->image;
                            $prodImg = $path ? (str_starts_with($path, 'http') ? $path : \Illuminate\Support\Facades\Storage::url($path)) : null;
                            $prodImg = $prodImg ?: 'https://placehold.co/400x500/f3f4f6/a3a3a3?text=Minha+Mania';
                        @endphp
                        <div class="bg-white rounded-2xl sm:rounded-3xl border border-gray-200 overflow-hidden shadow-xs hover:shadow-lg transition flex flex-col group">
                            <!-- Image Container -->
                            <a href="{{ route('loja.show', $prod) }}" class="block relative w-full aspect-[4/5] bg-gray-100 overflow-hidden" style="aspect-ratio: 4/5; min-height: 220px;">
                                <img src="{{ $prodImg }}" alt="{{ $prod->nome_do_produto }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                                @if(!empty($prod->tamanho))
                                    <span class="absolute top-2 left-2 bg-black/75 backdrop-blur-xs text-white text-[10px] font-black px-2 py-0.5 rounded-md uppercase" style="color: #ffffff !important;">
                                        {{ $prod->tamanho }}
                                    </span>
                                @endif
                                <span class="absolute bottom-2 right-2 bg-emerald-600 text-white text-[11px] font-black px-2 py-0.5 rounded-lg shadow-sm" style="color: #ffffff !important;">
                                    R$ {{ number_format($prod->preco ?? 0, 2, ',', '.') }}
                                </span>
                            </a>

                            <!-- Info Container -->
                            <div class="p-3.5 sm:p-4 flex-1 flex flex-col justify-between gap-2">
                                <div>
                                    <p class="text-[10px] uppercase tracking-wider font-extrabold text-pink-600 truncate">
                                        {{ $prod->marca ?: 'Brechó Selecionado' }}
                                    </p>
                                    <a href="{{ route('loja.show', $prod) }}" class="block hover:text-pink-600 transition">
                                        <h4 class="text-xs sm:text-sm font-bold text-gray-900 line-clamp-2 mt-0.5" title="{{ $prod->nome_do_produto }}">
                                            {{ $prod->nome_do_produto }}
                                        </h4>
                                    </a>
                                </div>
                                <a href="{{ route('loja.show', $prod) }}" class="w-full py-2 rounded-xl bg-pink-50 hover:bg-pink-600 text-pink-700 hover:text-white text-xs font-bold text-center transition flex items-center justify-center gap-1.5 cursor-pointer">
                                    <i class="fas fa-eye text-[10px]"></i>
                                    <span>Ver Detalhes</span>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-12 text-center bg-white rounded-3xl border border-gray-200 p-8">
                            <i class="fas fa-tags text-4xl text-gray-300 mb-3"></i>
                            <h4 class="text-base font-bold text-gray-700">Explore Nosso Catálogo Completo</h4>
                            <p class="text-xs text-gray-500 mt-1 max-w-md mx-auto">Temos centenas de peças femininas, calçados e acessórios esperando por você na nossa loja online.</p>
                            <a href="{{ route('loja.index') }}" class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-pink-600 text-white font-bold text-xs shadow-sm hover:bg-pink-700 transition" style="color: #ffffff !important;">
                                Ir para a Loja Online
                            </a>
                        </div>
                    @endforelse
                </div>

                <div class="text-center mt-12">
                    <a href="{{ route('loja.index') }}" class="inline-flex items-center gap-2 px-8 py-3.5 rounded-2xl bg-gray-900 hover:bg-black text-white font-extrabold text-sm shadow-md transition active:scale-95 cursor-pointer" style="color: #ffffff !important;">
                        <span>Ver Todo o Catálogo Disponível</span>
                        <i class="fas fa-chevron-right text-xs"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- Sobre Nós & Plataforma Integrada Section -->
        <section id="sobre" class="py-16 sm:py-24 bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    
                    <!-- Left Column -->
                    <div class="lg:col-span-6 space-y-5">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-pink-600 bg-pink-50 px-3 py-1 rounded-full border border-pink-200">
                            Quem Somos
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight leading-tight">
                            Acreditamos no Poder da Moda Circular e Conectada
                        </h2>
                        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
                            O <strong>Minha Mania Brechó</strong> nasceu da paixão por garimpar peças de qualidade, marcas consagradas e modelos atemporais, promovendo um consumo mais inteligente e sustentável.
                        </p>
                        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
                            Com tecnologia própria, conectamos o carinho das lojas físicas à interatividade do <strong>Live Commerce</strong> nas redes sociais. Nosso sistema integra transmissão ao vivo no <strong>TikTok</strong> e <strong>Instagram</strong>, inteligência de reconhecimento para cortes de vídeo e gestão completa de sacolinhas com fechamento prático.
                        </p>

                        <!-- Highlights List -->
                        <div class="space-y-3 pt-2">
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black shrink-0">
                                    <i class="fas fa-check"></i>
                                </div>
                                <span class="text-xs sm:text-sm font-bold text-gray-800">Curadoria rigorosa de estado e autenticidade em cada peça</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black shrink-0">
                                    <i class="fas fa-check"></i>
                                </div>
                                <span class="text-xs sm:text-sm font-bold text-gray-800">Sacolinha flexível para economizar no frete</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black shrink-0">
                                    <i class="fas fa-check"></i>
                                </div>
                                <span class="text-xs sm:text-sm font-bold text-gray-800">Plataforma integrada com TikTok, Instagram e YouTube Shorts</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Official integrations card -->
                    <div class="lg:col-span-6">
                        <div class="bg-gray-50 rounded-3xl p-8 border border-gray-200 shadow-sm space-y-6">
                            <h3 class="text-lg font-black text-gray-900 flex items-center gap-2">
                                <i class="fas fa-network-wired text-pink-600"></i>
                                Ecossistema Integrado de Live Shopping
                            </h3>
                            <p class="text-xs sm:text-sm text-gray-600 leading-relaxed font-medium">
                                Operamos de forma 100% profissional e em total conformidade com as diretrizes e termos oficiais das plataformas parceiras:
                            </p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- TikTok Integration Card -->
                                <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
                                    <div class="flex items-center gap-2.5 mb-2">
                                        <i class="fab fa-tiktok text-xl text-black"></i>
                                        <h4 class="text-xs font-black text-gray-900">TikTok API Services</h4>
                                    </div>
                                    <p class="text-[11px] text-gray-500 leading-relaxed">
                                        Publicação autorizada de cortes de lives e interação em transmissões oficiais.
                                    </p>
                                </div>

                                <!-- Instagram Card -->
                                <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
                                    <div class="flex items-center gap-2.5 mb-2">
                                        <i class="fab fa-instagram text-xl text-pink-600"></i>
                                        <h4 class="text-xs font-black text-gray-900">Instagram Live</h4>
                                    </div>
                                    <p class="text-[11px] text-gray-500 leading-relaxed">
                                        Transmissão simultânea e arremate de peças em tempo real pelo chat.
                                    </p>
                                </div>

                                <!-- YouTube Card -->
                                <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
                                    <div class="flex items-center gap-2.5 mb-2">
                                        <i class="fab fa-youtube text-xl text-red-600"></i>
                                        <h4 class="text-xs font-black text-gray-900">YouTube Shorts</h4>
                                    </div>
                                    <p class="text-[11px] text-gray-500 leading-relaxed">
                                        Vídeos em alta definição de cada peça com atualização de status de venda.
                                    </p>
                                </div>

                                <!-- Mercado Pago / Inter Card -->
                                <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
                                    <div class="flex items-center gap-2.5 mb-2">
                                        <i class="fas fa-shield-alt text-xl text-emerald-600"></i>
                                        <h4 class="text-xs font-black text-gray-900">Pagamento Seguro</h4>
                                    </div>
                                    <p class="text-[11px] text-gray-500 leading-relaxed">
                                        Checkouts criptografados com PIX instantâneo e parcelamento no cartão.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 pt-12 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Col 1: Brand -->
                <div class="space-y-3 md:col-span-1">
                    <img src="{{ asset('images/Logo_Grande_Preto.png') }}" alt="Minha Mania" class="h-10 w-auto object-contain">
                    <p class="text-xs text-gray-500 leading-relaxed">
                        Minha Mania Brechó &bull; Moda sustentável, curadoria com amor e a melhor experiência de Live Shopping do Brasil.
                    </p>
                </div>

                <!-- Col 2: Links Rápidos -->
                <div class="space-y-2">
                    <h5 class="text-xs font-black uppercase tracking-wider text-gray-900">Navegação</h5>
                    <ul class="text-xs text-gray-600 space-y-1.5 font-semibold">
                        <li><a href="/" class="hover:text-pink-600 transition">Página Inicial</a></li>
                        <li><a href="{{ route('loja.index') }}" class="hover:text-pink-600 transition">Loja Online</a></li>
                        <li><a href="#como-funciona" class="hover:text-pink-600 transition">Como Funciona</a></li>
                        <li><a href="#sobre" class="hover:text-pink-600 transition">Sobre Nós</a></li>
                        <li><a href="https://minhamania.net/login" target="_blank" class="hover:text-pink-600 transition">Clube Mania</a></li>
                    </ul>
                </div>

                <!-- Col 3: Jurídico & Compliance -->
                <div class="space-y-2">
                    <h5 class="text-xs font-black uppercase tracking-wider text-gray-900">Legal & Privacidade</h5>
                    <ul class="text-xs text-gray-600 space-y-1.5 font-semibold">
                        <li><a href="{{ route('legal.privacidade') }}" class="hover:text-pink-600 transition">Política de Privacidade</a></li>
                        <li><a href="{{ route('legal.termos') }}" class="hover:text-pink-600 transition">Termos de Serviço</a></li>
                        <li><span class="text-gray-400">TikTok API Services Partner</span></li>
                        <li><span class="text-gray-400">YouTube API Services Compliance</span></li>
                    </ul>
                </div>

                <!-- Col 4: Contato & Acesso -->
                <div class="space-y-2">
                    <h5 class="text-xs font-black uppercase tracking-wider text-gray-900">Atendimento & Painel</h5>
                    <p class="text-xs text-gray-500">Dúvidas sobre pedidos ou sacolinhas? Fale com a nossa equipe.</p>
                    <div class="pt-2">
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold transition">
                            <i class="fas fa-lock text-[10px]"></i>
                            <span>Acesso Administrativo</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Copyright Notice -->
            <div class="pt-8 border-t border-gray-100 text-center text-xs text-gray-400">
                <p>&copy; {{ date('Y') }} Minha Mania Brechó &bull; Todos os direitos reservados. CNPJ e operações registradas no Brasil.</p>
            </div>
        </div>
    </footer>

</body>
</html>
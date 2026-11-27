<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Minha Mania Brechó — Loja Online &amp; Live Shopping</title>
    <meta name="description" content="Garimpe peças únicas selecionadas no Minha Mania Brechó. Filtre pelo seu tamanho, acompanhe as lives e junte tudo na sua sacolinha por até 15 dias pagando 1 frete só!">

    @php
        $ogImg = asset('android-chrome-512x512.png');
        if (!empty($featuredItems) && $featuredItems->isNotEmpty()) {
            $firstFeatured = $featuredItems->first();
            $firstMedia = $firstFeatured->medias?->where('media_type', 'image')->first();
            $rawPath = $firstMedia?->thumbnail_url ?: $firstMedia?->url ?: $firstFeatured->image;
            if ($rawPath) {
                $ogImg = str_starts_with($rawPath, 'http') ? $rawPath : url(\Illuminate\Support\Facades\Storage::url($rawPath));
            }
        }
    @endphp

    <!-- Open Graph / WhatsApp / Redes Sociais -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="Minha Mania Brechó">
    <meta property="og:title" content="Minha Mania Brechó — Peças Únicas &amp; Sacolinha Inteligente">
    <meta property="og:description" content="Garimpe roupas selecionadas por tamanho na nossa Loja Online e junte na mesma sacolinha das lives para economizar no frete!">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ $ogImg }}">

    <!-- PWA & Icons -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#F5148C">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Minha Mania">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
    @include('partials.site-tracking')
</head>
<body class="bg-gray-50 text-gray-900 antialiased flex flex-col min-h-screen">

    <!-- Faixa superior -->
    <div class="bg-gradient-to-r from-pink-600 via-rose-600 to-purple-600 text-white text-xs font-bold py-2 px-4 text-center flex items-center justify-center gap-2">
        <i class="fas fa-shopping-bag"></i>
        <span>Monte sua sacolinha por até <strong>15 dias</strong> (Lives + Loja Online) e pague <strong>1 frete só</strong>!</span>
    </div>

    <!-- Cabeçalho -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between gap-4">
            <a href="/" class="flex items-center gap-2.5 shrink-0">
                <img src="{{ asset('favicon-32x32.png') }}" alt="Ícone Minha Mania" class="h-8 w-8 sm:h-9 sm:w-9 object-contain">
                <img src="{{ asset('images/Logo_Grande_Preto.png') }}" alt="Minha Mania Brechó" class="h-9 sm:h-11 w-auto object-contain">
            </a>

            <nav class="hidden md:flex items-center gap-6 text-sm font-bold text-gray-700">
                <a href="{{ route('loja.index') }}" class="text-pink-700 hover:text-pink-800 transition flex items-center gap-1.5">
                    <span>Loja Online</span>
                    @if(($totalAvailable ?? 0) > 0)
                        <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-pink-100 text-pink-800">{{ $totalAvailable }}</span>
                    @endif
                </a>
                <a href="#novidades" class="hover:text-pink-700 transition">Novidades</a>
                <a href="#como-funciona" class="hover:text-pink-700 transition">Como Funciona</a>
                <a href="#sobre" class="hover:text-pink-700 transition">Sobre Nós</a>
            </nav>

            <div class="flex items-center gap-2">
                <a href="{{ route('loja.index') }}"
                   class="px-4 py-2.5 rounded-xl bg-pink-600 hover:bg-pink-700 text-white text-xs sm:text-sm font-extrabold shadow-sm transition flex items-center gap-2">
                    <i class="fas fa-store"></i>
                    <span>Ver Loja</span>
                </a>

                @auth
                    @php
                        $isUserAdmin = in_array(auth()->user()->role, ['admin', 'admin_master', 'brecho_admin'], true) || !empty(auth()->user()->is_admin);
                    @endphp
                    @if($isUserAdmin)
                        <a href="{{ route('dashboard') }}"
                           class="px-3.5 py-2.5 rounded-xl bg-gray-900 hover:bg-black text-white text-xs sm:text-sm font-bold transition">
                            Painel
                        </a>
                    @else
                        <a href="{{ route('portal.sacolinha') }}"
                           class="px-3.5 py-2.5 rounded-xl bg-pink-50 border border-pink-200 hover:bg-pink-100 text-pink-800 text-xs sm:text-sm font-extrabold transition flex items-center gap-1.5">
                            <i class="fas fa-shopping-bag text-pink-600"></i>
                            <span class="hidden sm:inline">Sacolinha</span>
                            <span class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-pink-600 px-1.5 text-[11px] font-extrabold text-white">
                                {{ $sacolinhaCount ?? 0 }}
                            </span>
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}"
                       class="px-3.5 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-900 text-xs sm:text-sm font-bold transition flex items-center gap-1.5">
                        <i class="fab fa-whatsapp text-emerald-600"></i>
                        <span>Entrar</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main class="flex-grow">
        <!-- HERO FOCADO EM COMPRA & FILTRO POR TAMANHO -->
        <section class="bg-gradient-to-b from-pink-50/70 via-white to-gray-50 pt-10 pb-10 sm:pt-14 sm:pb-14 border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-pink-100 border border-pink-200 text-pink-800 text-xs font-extrabold">
                    <i class="fas fa-sparkles text-pink-600"></i>
                    <span>{{ number_format($totalAvailable ?? 0, 0, ',', '.') }} peças únicas garimpadas disponíveis agora</span>
                </span>

                <h1 class="mt-4 text-3xl sm:text-5xl font-black text-gray-900 tracking-tight leading-tight max-w-3xl mx-auto">
                    Garimpe peças únicas no seu tamanho e <span class="text-pink-600">pague 1 frete só</span>
                </h1>

                <p class="mt-3 text-sm sm:text-base text-gray-600 max-w-2xl mx-auto font-medium">
                    Compre direto na nossa Loja Online ou nas lives do Instagram e TikTok. Tudo entra na mesma sacolinha inteligente por até 15 dias!
                </p>

                <!-- Busca rápida + CTAs -->
                <form method="GET" action="{{ route('loja.index') }}" class="mt-6 max-w-xl mx-auto flex gap-2">
                    <input type="text" name="q" placeholder="Procure por vestido, calça, marca, cor..."
                           class="flex-1 rounded-2xl border border-gray-300 bg-white px-4 py-3.5 text-sm text-gray-900 placeholder:text-gray-500 shadow-xs focus:border-pink-500 focus:ring-2 focus:ring-pink-200 outline-none">
                    <button type="submit"
                            class="shrink-0 rounded-2xl bg-pink-600 hover:bg-pink-700 px-5 sm:px-7 py-3.5 text-sm font-extrabold text-white shadow-md shadow-pink-600/20 transition cursor-pointer">
                        <i class="fas fa-search sm:mr-1.5"></i>
                        <span class="hidden sm:inline">Buscar</span>
                    </button>
                </form>

                <!-- Chips de Tamanho de 1 clique -->
                @if(!empty($popularSizes))
                    <div class="mt-7">
                        <p class="text-xs font-extrabold uppercase tracking-wider text-gray-600 mb-3">
                            Escolha seu tamanho e vá direto às peças:
                        </p>
                        <div class="flex flex-wrap items-center justify-center gap-2 max-w-3xl mx-auto">
                            @foreach($popularSizes as $sz)
                                <a href="{{ route('loja.index', ['tamanho' => $sz['label'], 'tamanho_exato' => 1]) }}"
                                   class="px-4 py-2 rounded-full bg-white hover:bg-pink-600 text-gray-900 hover:text-white border border-gray-300 hover:border-pink-600 text-xs sm:text-sm font-extrabold shadow-xs transition">
                                    {{ $sz['label'] }}
                                    <span class="ml-1 text-[11px] opacity-70">({{ $sz['total'] }})</span>
                                </a>
                            @endforeach
                            <a href="{{ route('loja.index') }}"
                               class="px-4 py-2 rounded-full bg-gray-900 hover:bg-black text-white text-xs sm:text-sm font-extrabold transition">
                                Ver todos →
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <!-- VITRINE DE NOVIDADES LOGO NA PRIMEIRA DOBRA -->
        <section id="novidades" class="py-10 sm:py-14 bg-gray-50 border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-7">
                    <div>
                        <span class="text-xs font-extrabold uppercase tracking-wider text-pink-700">Acabou de chegar</span>
                        <h2 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight mt-0.5">
                            Peças Disponíveis Agora
                        </h2>
                    </div>
                    <a href="{{ route('loja.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white hover:bg-pink-50 text-gray-900 font-extrabold text-xs sm:text-sm border border-gray-300 hover:border-pink-400 transition self-start sm:self-auto">
                        <span>Explorar todas as {{ number_format($totalAvailable ?? 0, 0, ',', '.') }} peças</span>
                        <i class="fas fa-arrow-right text-xs text-pink-600"></i>
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3.5 sm:gap-5">
                    @forelse($featuredItems ?? [] as $prod)
                        @php
                            $hasPromotion = $prod->final_price < $prod->preco;
                            $imageCover = $prod->medias ? $prod->medias->where('media_type', 'image')->first() : null;
                            $path = $imageCover?->thumbnail_url ?: $imageCover?->url ?: $prod->image;
                            $prodImg = $path ? (str_starts_with($path, 'http') ? $path : \Illuminate\Support\Facades\Storage::url($path)) : null;
                            $hasVideo = $prod->medias && $prod->medias->contains('media_type', 'video');
                        @endphp
                        <article class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xs hover:shadow-lg transition flex flex-col group">
                            <a href="{{ route('loja.show', $prod) }}" class="block relative w-full aspect-[4/5] bg-gray-100 overflow-hidden">
                                @if($prodImg)
                                    <img src="{{ $prodImg }}" alt="{{ $prod->nome_do_produto }}" loading="lazy"
                                         class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-xs text-gray-500">Ver peça</div>
                                @endif

                                @if(!empty($prod->tamanho))
                                    <span class="absolute top-2.5 left-2.5 bg-gray-900/90 text-white text-[11px] font-extrabold px-2.5 py-1 rounded-lg uppercase">
                                        Tam {{ $prod->tamanho }}
                                    </span>
                                @endif

                                @if($hasVideo)
                                    <span class="absolute top-2.5 right-2.5 bg-black/80 text-white text-[10px] font-extrabold px-2 py-1 rounded-full inline-flex items-center gap-1">
                                        <i class="fas fa-play text-pink-400 text-[9px]"></i> Vídeo
                                    </span>
                                @endif

                                @if($hasPromotion)
                                    @php $percent = round(100 - ($prod->final_price / $prod->preco * 100)); @endphp
                                    <span class="absolute bottom-2.5 right-2.5 bg-red-600 text-white text-[10px] font-extrabold px-2.5 py-1 rounded-full shadow">
                                        -{{ $percent }}% OFF
                                    </span>
                                @endif
                            </a>

                            <div class="p-3 sm:p-4 flex-1 flex flex-col justify-between gap-2">
                                <div>
                                    <p class="text-[10px] uppercase tracking-wider font-extrabold text-pink-700 truncate">
                                        {{ $prod->marca ?: 'Curadoria Minha Mania' }}
                                    </p>
                                    <a href="{{ route('loja.show', $prod) }}" class="block mt-0.5">
                                        <h3 class="text-xs sm:text-sm font-bold text-gray-900 line-clamp-2 group-hover:text-pink-700 transition">
                                            {{ $prod->nome_do_produto }}
                                        </h3>
                                    </a>
                                </div>
                                <div class="pt-2 border-t border-gray-100 flex items-center justify-between gap-2">
                                    <div>
                                        @if($hasPromotion)
                                            <span class="block text-[10px] text-gray-500 line-through">R$ {{ number_format((float) $prod->preco, 2, ',', '.') }}</span>
                                            <span class="text-base font-extrabold text-red-600">{{ $prod->formatted_final_price }}</span>
                                        @else
                                            <span class="text-base font-extrabold text-gray-900">{{ $prod->formatted_final_price }}</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('loja.show', $prod) }}"
                                       class="rounded-xl bg-pink-600 hover:bg-pink-700 px-3 py-1.5 text-xs font-extrabold text-white transition">
                                        Ver
                                    </a>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="col-span-full py-10 text-center bg-white rounded-3xl border border-gray-200 p-8">
                            <p class="text-base font-bold text-gray-800">Explore nosso catálogo completo</p>
                            <a href="{{ route('loja.index') }}" class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-pink-600 text-white font-bold text-sm">
                                Ir para a Loja Online
                            </a>
                        </div>
                    @endforelse
                </div>

                <div class="text-center mt-10">
                    <a href="{{ route('loja.index') }}"
                       class="inline-flex items-center gap-2 px-8 py-3.5 rounded-2xl bg-pink-600 hover:bg-pink-700 text-white font-extrabold text-sm shadow-md transition">
                        <span>Ver Toda a Loja Online</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- COMO FUNCIONA (COMPACTO) -->
        <section id="como-funciona" class="py-12 sm:py-16 bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-10">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-pink-700">Simples e econômico</span>
                    <h2 class="text-2xl sm:text-3xl font-black text-gray-900 mt-1">Como funciona a Sacolinha Inteligente?</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div class="bg-gray-50 rounded-2xl p-5 border border-gray-200">
                        <div class="w-10 h-10 rounded-xl bg-pink-600 text-white flex items-center justify-center font-black mb-3">1</div>
                        <h3 class="text-sm font-extrabold text-gray-900">Garimpe na Loja ou Live</h3>
                        <p class="text-xs text-gray-600 mt-1.5 leading-relaxed">Escolha suas peças aqui filtrando pelo seu tamanho ou comentando o código nas lives do Instagram e TikTok.</p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-5 border border-gray-200">
                        <div class="w-10 h-10 rounded-xl bg-pink-600 text-white flex items-center justify-center font-black mb-3">2</div>
                        <h3 class="text-sm font-extrabold text-gray-900">Entre só com seu WhatsApp</h3>
                        <p class="text-xs text-gray-600 mt-1.5 leading-relaxed">Sem burocracia: identifique-se com um código rápido no WhatsApp e reserve sua peça única na hora.</p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-5 border border-gray-200">
                        <div class="w-10 h-10 rounded-xl bg-pink-600 text-white flex items-center justify-center font-black mb-3">3</div>
                        <h3 class="text-sm font-extrabold text-gray-900">Junte por até 15 dias</h3>
                        <p class="text-xs text-gray-600 mt-1.5 leading-relaxed">Suas compras da loja e das lives ficam juntas na mesma sacolinha para você pagar um frete só.</p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-5 border border-gray-200">
                        <div class="w-10 h-10 rounded-xl bg-pink-600 text-white flex items-center justify-center font-black mb-3">4</div>
                        <h3 class="text-sm font-extrabold text-gray-900">Feche e receba em casa</h3>
                        <p class="text-xs text-gray-600 mt-1.5 leading-relaxed">Finalize via PIX ou Cartão no Portal do Cliente e acompanhe o rastreio para todo o Brasil.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- SOBRE NÓS (COMPACTO) -->
        <section id="sobre" class="py-12 sm:py-16 bg-gray-50 border-b border-gray-200">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4">
                <span class="text-xs font-extrabold uppercase tracking-wider text-pink-700">Moda Circular com Curadoria</span>
                <h2 class="text-2xl sm:text-3xl font-black text-gray-900">Por que comprar no Minha Mania Brechó?</h2>
                <p class="text-sm sm:text-base text-gray-600 leading-relaxed max-w-3xl mx-auto">
                    Selecionamos cada peça com rigor de qualidade, higienização e transparência. Com mais de <strong>15.000 peças entregues</strong> em todo o Brasil, unimos a praticidade da loja online com a emoção do Live Shopping no Instagram, TikTok e YouTube.
                </p>
                <div class="pt-4 grid grid-cols-3 gap-4 max-w-xl mx-auto">
                    <div class="bg-white rounded-2xl p-4 border border-gray-200">
                        <p class="text-xl sm:text-2xl font-black text-gray-900">+15 mil</p>
                        <p class="text-xs text-gray-600 font-semibold">Peças entregues</p>
                    </div>
                    <div class="bg-white rounded-2xl p-4 border border-gray-200">
                        <p class="text-xl sm:text-2xl font-black text-pink-600">15 dias</p>
                        <p class="text-xs text-gray-600 font-semibold">Sacolinha aberta</p>
                    </div>
                    <div class="bg-white rounded-2xl p-4 border border-gray-200">
                        <p class="text-xl sm:text-2xl font-black text-gray-900">PIX / Cartão</p>
                        <p class="text-xs text-gray-600 font-semibold">Compra segura</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Rodapé -->
    <footer class="bg-white border-t border-gray-200 pt-10 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="space-y-3">
                    <img src="{{ asset('images/Logo_Grande_Preto.png') }}" alt="Minha Mania" class="h-9 w-auto object-contain">
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Minha Mania Brechó &bull; Moda sustentável, peças únicas selecionadas e Live Shopping para todo o Brasil.
                    </p>
                </div>

                <div class="space-y-2">
                    <h5 class="text-xs font-extrabold uppercase tracking-wider text-gray-900">Loja &amp; Conta</h5>
                    <ul class="text-xs text-gray-600 space-y-1.5 font-semibold">
                        <li><a href="{{ route('loja.index') }}" class="hover:text-pink-700 transition">Loja Online</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-pink-700 transition">Minha Sacolinha / Entrar</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-pink-700 transition">Criar Conta Grátis</a></li>
                        <li><a href="#como-funciona" class="hover:text-pink-700 transition">Como Funciona</a></li>
                    </ul>
                </div>

                <div class="space-y-2">
                    <h5 class="text-xs font-extrabold uppercase tracking-wider text-gray-900">Legal &amp; Privacidade</h5>
                    <ul class="text-xs text-gray-600 space-y-1.5 font-semibold">
                        <li><a href="{{ route('legal.privacidade') }}" class="hover:text-pink-700 transition">Política de Privacidade</a></li>
                        <li><a href="{{ route('legal.termos') }}" class="hover:text-pink-700 transition">Termos de Serviço</a></li>
                        <li><span class="text-gray-500">TikTok API Services Compliance</span></li>
                        <li><span class="text-gray-500">YouTube API Services Compliance</span></li>
                    </ul>
                </div>

                <div class="space-y-2">
                    <h5 class="text-xs font-extrabold uppercase tracking-wider text-gray-900">Equipe</h5>
                    <p class="text-xs text-gray-600">Área restrita para gestão interna.</p>
                    <a href="{{ route('login', ['tab' => 'email']) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-700 hover:text-pink-700 underline">
                        <i class="fas fa-lock text-[10px]"></i> Acesso Administrativo
                    </a>
                </div>
            </div>

            <div class="pt-6 border-t border-gray-100 text-center text-xs text-gray-500">
                <p>&copy; {{ date('Y') }} Minha Mania Brechó &bull; Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js').catch(function () {});
            });
        }
    </script>
</body>
</html>

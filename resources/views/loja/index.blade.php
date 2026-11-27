<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $pageTitle = $categoriaAtiva
            ? ($categoriaAtiva->name . ' — Loja Online | Minha Mania Brechó')
            : (request('sobras') == '1'
                ? 'Sobras da Live — Loja Online | Minha Mania Brechó'
                : 'Loja Online — Peças Únicas | Minha Mania Brechó');
        $pageDescription = 'Garimpe peças únicas selecionadas no Minha Mania Brechó. Filtre pelo seu tamanho, veja vídeos reais e junte tudo na sua sacolinha para pagar um frete só!';
        $ogImage = asset('android-chrome-512x512.png');
        if ($items->count() > 0) {
            $firstItem = $items->first();
            $firstImg = $firstItem->medias->where('media_type', 'image')->first();
            $rawOg = $firstImg?->thumbnail_url ?: $firstImg?->url ?: $firstItem->image;
            if ($rawOg) {
                $ogImage = str_starts_with($rawOg, 'http') ? $rawOg : url(\Illuminate\Support\Facades\Storage::url($rawOg));
            }
        }
    @endphp
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">

    <!-- Open Graph / WhatsApp / Redes Sociais -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="Minha Mania Brechó">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ url()->full() }}">
    <meta property="og:image" content="{{ $ogImage }}">

    <!-- PWA & Icons -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#F5148C">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    @include('partials.site-tracking')
</head>
<body class="bg-zinc-50 text-zinc-900 antialiased">
    <div class="min-h-screen flex flex-col">
        <!-- Faixa informativa de benefício -->
        <div class="bg-pink-600 text-white text-xs font-bold py-2 px-4 text-center">
            <i class="fas fa-shopping-bag mr-1.5"></i>
            Peças únicas! Reserve na sua sacolinha por até 15 dias e pague 1 frete só.
        </div>

        <!-- Cabeçalho -->
        <header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/95 backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between gap-3 py-3">
                    <a href="{{ route('loja.index') }}" class="flex items-center gap-2.5 shrink-0">
                        <img src="{{ asset('favicon-32x32.png') }}" alt="Minha Mania" class="h-8 w-8 object-contain">
                        <img src="{{ asset('images/Logo_Grande_Preto.png') }}" alt="Minha Mania Brechó" class="h-9 sm:h-10 w-auto object-contain" onerror="this.remove();">
                    </a>

                    <!-- Busca rápida desktop -->
                    <form method="GET" action="{{ route('loja.index') }}" class="hidden md:flex flex-1 max-w-md mx-4">
                        @if(request('tamanho'))<input type="hidden" name="tamanho" value="{{ request('tamanho') }}">@endif
                        @if(request('categoria'))<input type="hidden" name="categoria" value="{{ request('categoria') }}">@endif
                        @if(request('com_video'))<input type="hidden" name="com_video" value="{{ request('com_video') }}">@endif
                        @if(request('sobras'))<input type="hidden" name="sobras" value="{{ request('sobras') }}">@endif
                        <div class="relative w-full">
                            <input type="text" name="q" value="{{ request('q') }}"
                                   placeholder="Buscar vestido, jeans, marca ou código..."
                                   class="w-full rounded-full border border-zinc-300 bg-zinc-50 pl-4 pr-11 py-2 text-sm text-zinc-900 placeholder:text-zinc-500 focus:bg-white focus:border-pink-500 focus:ring-2 focus:ring-pink-200 outline-none">
                            <button type="submit" aria-label="Buscar"
                                    class="absolute right-1.5 top-1/2 -translate-y-1/2 h-8 w-8 rounded-full bg-pink-600 hover:bg-pink-700 text-white flex items-center justify-center transition">
                                <i class="fas fa-search text-xs"></i>
                            </button>
                        </div>
                    </form>

                    <div class="flex items-center gap-2.5">
                        @auth
                            @php
                                $isUserAdmin = in_array(auth()->user()->role, ['admin', 'admin_master', 'brecho_admin'], true) || !empty(auth()->user()->is_admin);
                                $firstName = explode(' ', trim(auth()->user()->apelido ?: auth()->user()->name))[0];
                            @endphp

                            @if($isUserAdmin)
                                <a href="{{ route('dashboard') }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-zinc-900 hover:bg-black text-white text-xs font-bold shadow-sm transition">
                                    <i class="fas fa-layer-group"></i>
                                    <span>Painel</span>
                                </a>
                            @else
                                <a href="{{ route('portal.dashboard') }}"
                                   class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-zinc-100 hover:bg-zinc-200 text-zinc-800 text-xs font-bold transition">
                                    <i class="fas fa-user text-pink-600"></i>
                                    <span>Olá, {{ $firstName }}</span>
                                </a>
                                <a href="{{ route('portal.sacolinha') }}"
                                   class="relative inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-pink-50 border border-pink-200 hover:bg-pink-100 text-pink-800 text-xs sm:text-sm font-extrabold transition"
                                   title="Minha Sacolinha">
                                    <i class="fas fa-shopping-bag text-pink-600"></i>
                                    <span class="hidden sm:inline">Sacolinha</span>
                                    <span class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-pink-600 px-1.5 text-[11px] font-extrabold text-white">
                                        {{ $sacolinhaCount ?? 0 }}
                                    </span>
                                </a>
                            @endif
                        @else
                            <a href="{{ route('login', ['redirect_to' => request()->getRequestUri()]) }}"
                               class="inline-flex items-center gap-1.5 rounded-xl bg-zinc-900 px-3.5 py-2 text-xs sm:text-sm font-bold text-white hover:bg-black transition">
                                <i class="fab fa-whatsapp text-emerald-400"></i>
                                <span>Entrar</span>
                            </a>
                        @endauth
                    </div>
                </div>

                <!-- Busca mobile -->
                <form method="GET" action="{{ route('loja.index') }}" class="md:hidden pb-3">
                    @if(request('tamanho'))<input type="hidden" name="tamanho" value="{{ request('tamanho') }}">@endif
                    @if(request('categoria'))<input type="hidden" name="categoria" value="{{ request('categoria') }}">@endif
                    @if(request('com_video'))<input type="hidden" name="com_video" value="{{ request('com_video') }}">@endif
                    @if(request('sobras'))<input type="hidden" name="sobras" value="{{ request('sobras') }}">@endif
                    <div class="relative w-full">
                        <input type="text" name="q" value="{{ request('q') }}"
                               placeholder="Buscar peça, marca ou código..."
                               class="w-full rounded-xl border border-zinc-300 bg-zinc-50 pl-3.5 pr-11 py-2.5 text-sm text-zinc-900 placeholder:text-zinc-500 focus:bg-white focus:border-pink-500 focus:ring-2 focus:ring-pink-200 outline-none">
                        <button type="submit" aria-label="Buscar"
                                class="absolute right-1.5 top-1/2 -translate-y-1/2 h-8 w-8 rounded-lg bg-pink-600 text-white flex items-center justify-center">
                            <i class="fas fa-search text-xs"></i>
                        </button>
                    </div>
                </form>
            </div>
        </header>

        <!-- Barra de Categorias -->
        @if($categorias->isNotEmpty())
        <nav class="border-b border-zinc-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-1.5 md:gap-6 overflow-x-auto md:overflow-visible no-scrollbar">
                    <a
                        href="{{ route('loja.index', array_filter(request()->except('categoria', 'page'))) }}"
                        class="shrink-0 whitespace-nowrap px-3.5 py-3 text-xs sm:text-sm font-bold transition border-b-2
                            {{ is_null($categoriaAtiva) ? 'text-pink-700 border-pink-600' : 'text-zinc-600 border-transparent hover:text-pink-700 hover:border-pink-400' }}"
                    >
                        Tudo
                    </a>

                    @foreach($categorias as $cat)
                        @php
                            $isActive = $categoriaAtiva?->id === $cat->id;
                            $params = array_filter(request()->except('categoria', 'page'));
                            $params['categoria'] = $cat->slug;
                        @endphp
                        <div class="shrink-0 group/cat relative">
                            <a
                                href="{{ route('loja.index', $params) }}"
                                class="flex items-center gap-1.5 whitespace-nowrap px-3.5 py-3 text-xs sm:text-sm font-bold transition border-b-2
                                    {{ $isActive ? 'text-pink-700 border-pink-600' : 'text-zinc-600 border-transparent hover:text-pink-700 hover:border-pink-400' }}"
                            >
                                {{ $cat->name }}
                                @if($cat->children->isNotEmpty())
                                    <i class="fas fa-chevron-down text-[10px] opacity-50 group-hover/cat:rotate-180 transition-transform hidden md:inline"></i>
                                @endif
                            </a>

                            @if($cat->children->isNotEmpty())
                                <div class="hidden md:group-hover/cat:block absolute left-0 top-full w-[520px] max-w-[90vw] bg-white border border-zinc-200 rounded-b-2xl shadow-2xl z-50 p-6">
                                    <div class="grid grid-cols-2 gap-6">
                                        @foreach($cat->children as $child)
                                            @php
                                                $childParams = array_filter(request()->except('categoria', 'page'));
                                                $childParams['categoria'] = $child->slug;
                                            @endphp
                                            <div class="flex flex-col gap-2">
                                                <a href="{{ route('loja.index', $childParams) }}"
                                                   class="text-xs font-extrabold text-zinc-900 hover:text-pink-700 uppercase tracking-wider">
                                                    {{ $child->name }}
                                                </a>
                                                @foreach($child->children as $subChild)
                                                    @php
                                                        $subChildParams = array_filter(request()->except('categoria', 'page'));
                                                        $subChildParams['categoria'] = $subChild->slug;
                                                    @endphp
                                                    <a href="{{ route('loja.index', $subChildParams) }}"
                                                       class="text-xs text-zinc-600 hover:text-pink-700">
                                                        {{ $subChild->name }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-zinc-100">
                                        <a href="{{ route('loja.index', $params) }}" class="text-xs font-extrabold text-pink-700 hover:underline">
                                            Ver tudo em {{ $cat->name }} →
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </nav>
        @endif

        <!-- BARRA RÁPIDA DE TAMANHOS (1 TOQUE — PRINCIPAL FILTRO DE BRECHÓ) -->
        @if(!empty($popularSizes))
        <section class="bg-white border-b border-zinc-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-3">
                <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
                    <span class="shrink-0 text-xs font-extrabold uppercase tracking-wider text-zinc-600 mr-1">
                        <i class="fas fa-ruler-horizontal text-pink-600 mr-1"></i> Tamanho:
                    </span>
                    @php
                        $activeSize = strtoupper(trim((string) request('tamanho', '')));
                    @endphp
                    <a href="{{ route('loja.index', array_filter(request()->except('tamanho', 'tamanho_exato', 'page'))) }}"
                       class="shrink-0 px-3 py-1.5 rounded-full text-xs font-bold border transition
                           {{ $activeSize === '' ? 'bg-zinc-900 text-white border-zinc-900' : 'bg-zinc-50 text-zinc-800 border-zinc-300 hover:border-pink-400 hover:bg-pink-50' }}">
                        Todos
                    </a>
                    @foreach($popularSizes as $sz)
                        @php
                            $isSzActive = $activeSize === $sz['label'];
                            $szParams = array_filter(request()->except('page'));
                            if ($isSzActive) {
                                unset($szParams['tamanho'], $szParams['tamanho_exato']);
                            } else {
                                $szParams['tamanho'] = $sz['label'];
                                $szParams['tamanho_exato'] = '1';
                            }
                        @endphp
                        <a href="{{ route('loja.index', $szParams) }}"
                           class="shrink-0 px-3 py-1.5 rounded-full text-xs font-bold border transition inline-flex items-center gap-1.5
                               {{ $isSzActive ? 'bg-pink-600 text-white border-pink-600 shadow-xs' : 'bg-white text-zinc-800 border-zinc-300 hover:border-pink-400 hover:bg-pink-50' }}">
                            <span>{{ $sz['label'] }}</span>
                            <span class="text-[10px] {{ $isSzActive ? 'text-pink-100' : 'text-zinc-500' }}">({{ $sz['total'] }})</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        <!-- Overlay do menu de filtros avançados -->
        <div id="filterOverlay" class="fixed inset-0 z-40 hidden bg-black/50" onclick="closeFilterMenu()"></div>

        <!-- Drawer de filtros avançados -->
        <aside id="filterDrawer"
               class="fixed left-0 top-0 z-50 h-full w-[88%] max-w-sm -translate-x-full overflow-y-auto border-r border-zinc-200 bg-white shadow-2xl transition-transform duration-300">
            <div class="sticky top-0 flex items-center justify-between border-b border-zinc-200 bg-white px-4 py-4">
                <div>
                    <h2 class="text-base font-extrabold text-zinc-900">Filtros da Loja</h2>
                    <p class="text-xs text-zinc-600">Encontre exatamente o que procura</p>
                </div>
                <button type="button" onclick="closeFilterMenu()" aria-label="Fechar filtros"
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-zinc-300 bg-white text-zinc-800 hover:bg-zinc-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="GET" action="{{ route('loja.index') }}" class="space-y-4 p-4">
                @if(request('categoria'))<input type="hidden" name="categoria" value="{{ request('categoria') }}">@endif
                @if(request('ordem'))<input type="hidden" name="ordem" value="{{ request('ordem') }}">@endif

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-zinc-700">Buscar</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Nome, código, marca..."
                           class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none focus:border-pink-500 focus:ring-2 focus:ring-pink-200">
                </div>

                <div class="space-y-2">
                    <label class="flex items-center gap-3 cursor-pointer rounded-xl border border-pink-200 bg-pink-50 p-3 select-none">
                        <input type="checkbox" name="com_video" value="1" {{ request('com_video') == '1' ? 'checked' : '' }}
                               class="h-4 w-4 rounded border-zinc-300 text-pink-600 focus:ring-pink-500">
                        <span class="text-sm font-bold text-pink-900 flex items-center gap-2">
                            <i class="fas fa-video text-pink-600"></i> Apenas peças com vídeo
                        </span>
                    </label>

                    @if(($quickStats['sobras_count'] ?? 0) > 0)
                    <label class="flex items-center gap-3 cursor-pointer rounded-xl border border-amber-200 bg-amber-50 p-3 select-none">
                        <input type="checkbox" name="sobras" value="1" {{ request('sobras') == '1' ? 'checked' : '' }}
                               class="h-4 w-4 rounded border-zinc-300 text-amber-600 focus:ring-amber-500">
                        <span class="text-sm font-bold text-amber-900 flex items-center gap-2">
                            <i class="fas fa-fire text-amber-600"></i> Sobras das Lives
                        </span>
                    </label>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-zinc-700">Tamanho</label>
                        <input type="text" name="tamanho" value="{{ request('tamanho') }}" placeholder="Ex: M, G, 40"
                               class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none focus:border-pink-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-zinc-700">Cor</label>
                        <input type="text" name="cor" value="{{ request('cor') }}" placeholder="Ex: Preto"
                               class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none focus:border-pink-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-zinc-700">Marca</label>
                        <input type="text" name="marca" value="{{ request('marca') }}" placeholder="Ex: Zara, Farm"
                               class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none focus:border-pink-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-zinc-700">Estado</label>
                        <select name="estado"
                                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none focus:border-pink-500">
                            <option value="">Todos</option>
                            <option value="novo" {{ request('estado') === 'novo' ? 'selected' : '' }}>Novo</option>
                            <option value="seminovo" {{ request('estado') === 'seminovo' ? 'selected' : '' }}>Seminovo</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-zinc-700">Preço mín.</label>
                        <input type="number" step="0.01" name="preco_min" value="{{ request('preco_min') }}" placeholder="0,00"
                               class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none focus:border-pink-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-zinc-700">Preço máx.</label>
                        <input type="number" step="0.01" name="preco_max" value="{{ request('preco_max') }}" placeholder="999,99"
                               class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 outline-none focus:border-pink-500">
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="flex-1 rounded-xl bg-pink-600 hover:bg-pink-700 px-4 py-3 text-sm font-extrabold text-white transition cursor-pointer">
                        Aplicar
                    </button>
                    <a href="{{ route('loja.index') }}"
                       class="flex-1 rounded-xl border border-zinc-300 bg-white px-4 py-3 text-center text-sm font-bold text-zinc-800 hover:bg-zinc-100 transition">
                        Limpar
                    </a>
                </div>
            </form>
        </aside>

        <!-- Conteúdo Principal -->
        <main class="flex-grow mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            @if(request()->filled('success') || session('success'))
                <div class="mb-5 rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3.5 text-sm font-semibold text-emerald-900 flex flex-wrap items-center justify-between gap-2">
                    <span><i class="fas fa-check-circle text-emerald-600 mr-1.5"></i> {{ request('success') ?: session('success') }}</span>
                    @auth
                        <a href="{{ route('portal.sacolinha') }}" class="text-xs font-extrabold underline text-emerald-800">
                            Ver Minha Sacolinha →
                        </a>
                    @endauth
                </div>
            @endif

            @if(request()->filled('error') || session('error'))
                <div class="mb-5 rounded-2xl border border-red-300 bg-red-50 px-4 py-3.5 text-sm font-semibold text-red-900">
                    <i class="fas fa-exclamation-circle text-red-600 mr-1.5"></i> {{ request('error') ?: session('error') }}
                </div>
            @endif

            <!-- Cabeçalho de resultados + Atalhos + Ordenação -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-zinc-900">
                        @if(request('sobras') == '1')
                            Sobras das Lives
                        @elseif($categoriaAtiva)
                            {{ $categoriaAtiva->name }}
                        @else
                            Vitrine Minha Mania
                        @endif
                    </h1>
                    <p class="mt-0.5 text-xs sm:text-sm text-zinc-600 font-medium">
                        {{ $items->total() }} {{ $items->total() === 1 ? 'peça única disponível' : 'peças únicas disponíveis' }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if(($quickStats['sobras_count'] ?? 0) > 0)
                        @php
                            $sobrasParams = request()->except('page');
                            $hasSobrasFilter = request('sobras') == '1';
                            if ($hasSobrasFilter) { unset($sobrasParams['sobras']); } else { $sobrasParams['sobras'] = '1'; }
                        @endphp
                        <a href="{{ route('loja.index', $sobrasParams) }}"
                           class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-extrabold transition
                               {{ $hasSobrasFilter ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-zinc-800 border-zinc-300 hover:bg-amber-50 hover:border-amber-400' }}">
                            <i class="fas fa-fire {{ $hasSobrasFilter ? 'text-white' : 'text-amber-600' }}"></i>
                            <span>Sobras da Live ({{ $quickStats['sobras_count'] }})</span>
                        </a>
                    @endif

                    @if(($quickStats['video_count'] ?? 0) > 0 || request('com_video') == '1')
                        @php
                            $videoParams = request()->except('page');
                            $hasVideoFilter = request('com_video') == '1';
                            if ($hasVideoFilter) { unset($videoParams['com_video']); } else { $videoParams['com_video'] = '1'; }
                        @endphp
                        <a href="{{ route('loja.index', $videoParams) }}"
                           class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-extrabold transition
                               {{ $hasVideoFilter ? 'bg-pink-600 text-white border-pink-600' : 'bg-white text-zinc-800 border-zinc-300 hover:bg-pink-50 hover:border-pink-400' }}">
                            <i class="fas fa-video {{ $hasVideoFilter ? 'text-white' : 'text-pink-600' }}"></i>
                            <span>Com Vídeo</span>
                        </a>
                    @endif

                    <!-- Ordenação -->
                    <form method="GET" action="{{ route('loja.index') }}" class="inline">
                        @foreach(request()->except('ordem', 'page') as $k => $v)
                            @if(is_scalar($v))
                                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                            @endif
                        @endforeach
                        <select name="ordem" onchange="this.form.submit()" aria-label="Ordenar por"
                                class="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-xs font-bold text-zinc-800 outline-none focus:border-pink-500">
                            <option value="recentes" {{ ($ordem ?? 'recentes') === 'recentes' ? 'selected' : '' }}>Mais recentes</option>
                            <option value="menor_preco" {{ ($ordem ?? '') === 'menor_preco' ? 'selected' : '' }}>Menor preço</option>
                            <option value="maior_preco" {{ ($ordem ?? '') === 'maior_preco' ? 'selected' : '' }}>Maior preço</option>
                        </select>
                    </form>

                    <button type="button" onclick="openFilterMenu()"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-300 bg-white px-3.5 py-2 text-xs font-extrabold text-zinc-800 hover:bg-zinc-100 transition cursor-pointer">
                        <i class="fas fa-sliders-h text-pink-600"></i>
                        <span>Filtros</span>
                    </button>
                </div>
            </div>

            <!-- Chips de filtros ativos -->
            @if(
                request()->filled('q') ||
                request()->filled('categoria') ||
                request()->filled('com_video') ||
                request()->filled('sobras') ||
                request()->filled('marca') ||
                request()->filled('cor') ||
                request()->filled('tamanho') ||
                request()->filled('estado') ||
                request()->filled('preco_min') ||
                request()->filled('preco_max')
            )
                <div class="mb-5 flex flex-wrap items-center gap-2">
                    @if(request('q'))
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-zinc-800 border border-zinc-300">Busca: "{{ request('q') }}"</span>
                    @endif
                    @if($categoriaAtiva)
                        <span class="rounded-full bg-zinc-900 px-3 py-1 text-xs font-bold text-white">{{ $categoriaAtiva->name }}</span>
                    @endif
                    @if(request('tamanho'))
                        <span class="rounded-full bg-pink-600 px-3 py-1 text-xs font-bold text-white">Tamanho: {{ strtoupper(request('tamanho')) }}</span>
                    @endif
                    @if(request('marca'))
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-zinc-800 border border-zinc-300">Marca: {{ request('marca') }}</span>
                    @endif
                    @if(request('cor'))
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-zinc-800 border border-zinc-300">Cor: {{ request('cor') }}</span>
                    @endif
                    @if(request('estado'))
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-zinc-800 border border-zinc-300">Estado: {{ ucfirst(request('estado')) }}</span>
                    @endif
                    @if(request('preco_min'))
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-zinc-800 border border-zinc-300">A partir de R$ {{ request('preco_min') }}</span>
                    @endif
                    @if(request('preco_max'))
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-zinc-800 border border-zinc-300">Até R$ {{ request('preco_max') }}</span>
                    @endif
                    <a href="{{ route('loja.index') }}"
                       class="rounded-full bg-zinc-200 hover:bg-zinc-300 px-3 py-1 text-xs font-extrabold text-zinc-900 transition">
                        ✕ Limpar filtros
                    </a>
                </div>
            @endif

            @if($items->count() === 0)
                <div class="rounded-3xl border border-zinc-200 bg-white p-10 text-center shadow-xs">
                    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-pink-50 text-pink-600 text-2xl">
                        <i class="fas fa-search"></i>
                    </div>
                    <h2 class="text-lg font-extrabold text-zinc-900">Nenhuma peça encontrada com esses filtros</h2>
                    <p class="mt-1 text-sm text-zinc-600">Tente escolher outro tamanho ou limpar os filtros para ver todo o garimpo.</p>
                    <a href="{{ route('loja.index') }}"
                       class="mt-5 inline-flex items-center gap-2 rounded-xl bg-pink-600 hover:bg-pink-700 px-5 py-2.5 text-sm font-extrabold text-white transition">
                        Ver todas as peças disponíveis
                    </a>
                </div>
            @else
                <div class="grid grid-cols-2 gap-3.5 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
                    @foreach($items as $item)
                        @php
                            $hasPromotion = $item->final_price < $item->preco;
                            $imageCover = $item->medias
                                ->where('media_type', 'image')
                                ->sortBy([['is_cover', 'desc'], ['position', 'asc'], ['id', 'asc']])
                                ->first();
                            $videoCover = $item->medias->firstWhere('media_type', 'video');
                            $hasVideo = (bool) $videoCover;

                            $path = $imageCover?->thumbnail_url ?: $imageCover?->url ?: $item->image;
                            $publicUrl = $path ? (str_starts_with($path, 'http') ? $path : \Illuminate\Support\Facades\Storage::url($path)) : null;
                            $videoUrl = $videoCover?->url ? (str_starts_with($videoCover->url, 'http') ? $videoCover->url : \Illuminate\Support\Facades\Storage::url($videoCover->url)) : null;
                            $alt = $imageCover?->alt_text ?: $item->nome_do_produto;
                            $firstCatName = $item->categorias->first()?->name;
                        @endphp

                        <article class="group overflow-hidden rounded-2xl border bg-white shadow-xs hover:shadow-lg transition flex flex-col {{ $hasPromotion ? 'border-pink-300 ring-1 ring-pink-200' : 'border-zinc-200' }}">
                            <a href="{{ route('loja.show', $item) }}" class="block relative aspect-[4/5] w-full overflow-hidden bg-zinc-100">
                                @if($publicUrl)
                                    <img src="{{ $publicUrl }}" alt="{{ $alt }}" loading="lazy"
                                         class="h-full w-full object-cover transition duration-300 group-hover:scale-105" />
                                @elseif($videoUrl)
                                    <div class="relative w-full h-full bg-zinc-900 flex items-center justify-center">
                                        <video src="{{ $videoUrl }}" class="h-full w-full object-cover" preload="metadata" muted playsinline></video>
                                        <div class="absolute inset-0 bg-black/25 flex items-center justify-center pointer-events-none">
                                            <div class="w-11 h-11 rounded-full bg-white/95 flex items-center justify-center shadow-lg">
                                                <i class="fas fa-play text-zinc-900 text-sm ml-0.5"></i>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-xs font-semibold text-zinc-500">
                                        Sem foto
                                    </div>
                                @endif

                                <!-- Selo Tamanho (topo esquerdo — leitura instantânea) -->
                                @if(!empty($item->tamanho))
                                    <span class="absolute left-2.5 top-2.5 rounded-lg bg-zinc-900/90 px-2.5 py-1 text-[11px] font-extrabold uppercase text-white shadow-sm">
                                        Tam {{ $item->tamanho }}
                                    </span>
                                @endif

                                <!-- Selo Vídeo -->
                                @if($hasVideo)
                                    <span class="absolute right-2.5 top-2.5 inline-flex items-center gap-1 rounded-full bg-black/80 px-2.5 py-1 text-[10px] font-extrabold text-white">
                                        <i class="fas fa-play text-pink-400 text-[9px]"></i> Vídeo
                                    </span>
                                @endif

                                <!-- Selo Promoção -->
                                @if($hasPromotion)
                                    @php $percent = round(100 - ($item->final_price / $item->preco * 100)); @endphp
                                    <span class="absolute right-2.5 bottom-2.5 rounded-full bg-red-600 px-2.5 py-1 text-[10px] font-extrabold text-white shadow-md">
                                        -{{ $percent }}% OFF
                                    </span>
                                @endif
                            </a>

                            <div class="p-3 sm:p-4 flex-1 flex flex-col justify-between gap-2.5">
                                <div>
                                    <div class="flex items-center justify-between gap-2 text-[10px] font-extrabold uppercase tracking-wider text-pink-700">
                                        <span class="truncate">{{ $item->marca ?: ($firstCatName ?: 'Minha Mania') }}</span>
                                        @if(!empty($item->estado))
                                            <span class="shrink-0 text-zinc-500 font-bold">{{ $item->estado }}</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('loja.show', $item) }}" class="block mt-1">
                                        <h2 class="line-clamp-2 text-xs sm:text-sm font-bold leading-snug text-zinc-900 group-hover:text-pink-700 transition">
                                            {{ $item->nome_do_produto }}
                                        </h2>
                                    </a>
                                </div>

                                <div class="pt-2 border-t border-zinc-100 flex items-end justify-between gap-2">
                                    <div class="flex flex-col">
                                        @if($hasPromotion)
                                            <span class="text-[11px] font-semibold text-zinc-500 line-through">
                                                R$ {{ number_format((float) $item->preco, 2, ',', '.') }}
                                            </span>
                                            <span class="text-base sm:text-lg font-extrabold text-red-600 tabular-nums leading-tight">
                                                {{ $item->formatted_final_price }}
                                            </span>
                                        @else
                                            <span class="text-base sm:text-lg font-extrabold text-zinc-900 tabular-nums leading-tight">
                                                {{ $item->formatted_final_price }}
                                            </span>
                                        @endif
                                    </div>
                                    <a href="{{ route('loja.show', $item) }}"
                                       class="shrink-0 rounded-xl bg-pink-600 hover:bg-pink-700 px-3 py-1.5 text-xs font-extrabold text-white transition">
                                        Ver
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $items->appends(request()->query())->links() }}
                </div>
            @endif
        </main>

        <!-- Rodapé -->
        <footer class="border-t border-zinc-200 bg-white mt-10">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-zinc-600">
                <div>&copy; {{ date('Y') }} <strong>Minha Mania Brechó</strong> — Peças únicas &amp; Sacolinha inteligente</div>
                <div class="flex items-center gap-4 font-semibold">
                    <a href="/" class="hover:text-pink-700">Início</a>
                    <a href="{{ route('legal.privacidade') }}" class="hover:text-pink-700">Privacidade</a>
                    <a href="{{ route('legal.termos') }}" class="hover:text-pink-700">Termos</a>
                </div>
            </div>
        </footer>
    </div>

    <script>
        const filterDrawer = document.getElementById('filterDrawer');
        const filterOverlay = document.getElementById('filterOverlay');

        function openFilterMenu() {
            filterDrawer.classList.remove('-translate-x-full');
            filterOverlay.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeFilterMenu() {
            filterDrawer.classList.add('-translate-x-full');
            filterOverlay.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeFilterMenu();
        });

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js').catch(function () {});
            });
        }
    </script>
</body>
</html>

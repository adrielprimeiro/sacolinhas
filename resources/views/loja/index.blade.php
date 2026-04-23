<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loja</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-zinc-50 text-zinc-900">
    <div class="min-h-screen">
        <!-- Topbar -->
        <header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/80 backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-4">
                    <a href="{{ route('loja.index') }}" class="flex items-center gap-3">
                        <img
                            src="{{ asset('images/logo.png') }}"
                            alt="Logo"
                            class="h-10 w-auto"
                            onerror="this.remove();"
                        />
                        <div>
                            <div class="text-sm font-semibold leading-5">Loja</div>
                            <div class="text-xs text-zinc-500">Vitrine de produtos</div>
                        </div>
                    </a>

                    <div class="flex items-center gap-3">
                        @auth
                            <div class="flex items-center gap-3">
                                <div class="hidden sm:block text-right">
                                    <div class="text-sm font-semibold leading-5 text-zinc-900">
                                        {{ auth()->user()->name }}
                                    </div>
                                    <div class="text-xs text-zinc-500">
                                        Logado
                                    </div>
                                </div>

                                <div class="h-10 w-10 overflow-hidden rounded-full bg-zinc-100 ring-1 ring-zinc-200">
                                    <div class="flex h-full w-full items-center justify-center text-sm font-semibold text-zinc-700">
                                        {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                                    </div>
                                </div>
                            </div>

                            <a href="{{ route('portal.sacolinha') }}"
                               class="group relative flex items-center justify-center h-11 w-11 rounded-xl bg-white border border-zinc-200 shadow-sm hover:border-purple-300 hover:bg-purple-50 transition-all duration-300 active:scale-95"
                               title="Minha Sacolinha">
                                <i class="fas fa-shopping-bag text-xl text-zinc-600 group-hover:text-purple-600 transition-colors"></i>

                                <span class="absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-purple-600 text-[10px] font-bold text-white shadow-lg ring-2 ring-white">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-purple-400 opacity-20"></span>
                                    !
                                </span>
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center rounded-xl bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800">
                                Entrar
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        <!-- Barra de Categorias -->
        @if($categorias->isNotEmpty())
        <nav class="sticky top-[69px] z-30 border-b border-zinc-200 bg-white shadow-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2 md:gap-8 overflow-x-auto md:overflow-visible py-0 scrollbar-none" style="scrollbar-width:none;-ms-overflow-style:none;">
                    <a
                        href="{{ route('loja.index', array_filter(request()->except('categoria', 'page'))) }}"
                        class="shrink-0 whitespace-nowrap px-4 py-4 text-sm font-semibold transition-all duration-200 border-b-2
                            {{ is_null($categoriaAtiva) ? 'text-purple-600 border-purple-600' : 'text-zinc-600 border-transparent hover:text-purple-600 hover:border-purple-600' }}"
                    >
                        Todos
                    </a>

                    @foreach($categorias as $cat)
                        @php
                            $isActive = $categoriaAtiva?->id === $cat->id;
                            $params = array_filter(request()->except('categoria', 'page'));
                            $params['categoria'] = $cat->slug;
                        @endphp
                        <div class="shrink-0 group/cat">
                            <a
                                href="{{ route('loja.index', $params) }}"
                                class="flex items-center gap-1.5 whitespace-nowrap px-4 py-4 text-sm font-semibold transition-all duration-200 border-b-2 border-transparent hover:text-purple-600 hover:border-purple-600
                                    {{ $isActive ? 'text-purple-600 border-purple-600' : 'text-zinc-600' }}"
                            >
                                {{ $cat->name }}
                                @if($cat->children->isNotEmpty())
                                    <i class="fas fa-chevron-down text-[10px] opacity-40 group-hover/cat:rotate-180 transition-transform"></i>
                                @endif
                            </a>

                            @if($cat->children->isNotEmpty())
                                {{-- Mega Menu Estilo Repassa --}}
                                <div class="absolute left-0 top-full hidden w-screen max-w-7xl bg-white border-t border-zinc-100 shadow-2xl z-50 group-hover/cat:block" style="left: 50%; transform: translateX(-50%);">
                                    <div class="mx-auto grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-y-8 gap-x-12 px-8 py-10">
                                        @foreach($cat->children as $child)
                                            @php
                                                $childParams = array_filter(request()->except('categoria', 'page'));
                                                $childParams['categoria'] = $child->slug;
                                            @endphp
                                            <div class="flex flex-col gap-3">
                                                <a
                                                    href="{{ route('loja.index', $childParams) }}"
                                                    class="text-sm font-bold text-zinc-900 hover:text-purple-600 transition-colors uppercase tracking-wider"
                                                >
                                                    {{ $child->name }}
                                                </a>
                                                
                                                <div class="flex flex-col gap-2">
                                                    @foreach($child->children as $subChild)
                                                        @php
                                                            $subChildParams = array_filter(request()->except('categoria', 'page'));
                                                            $subChildParams['categoria'] = $subChild->slug;
                                                        @endphp
                                                        <a
                                                            href="{{ route('loja.index', $subChildParams) }}"
                                                            class="text-sm text-zinc-500 hover:text-purple-600 transition-colors"
                                                        >
                                                            {{ $subChild->name }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="bg-zinc-50 px-8 py-4 border-t border-zinc-100">
                                        <a href="{{ route('loja.index', $params) }}" class="text-xs font-bold text-purple-600 hover:underline uppercase tracking-widest">
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

        <!-- Overlay do menu -->
        <div
            id="filterOverlay"
            class="fixed inset-0 z-40 hidden bg-black/40"
            onclick="closeFilterMenu()"
        ></div>

        <!-- Drawer de filtros -->
        <aside
            id="filterDrawer"
            class="fixed left-0 top-0 z-50 h-full w-[88%] max-w-sm -translate-x-full overflow-y-auto border-r border-zinc-200 bg-white shadow-2xl transition-transform duration-300"
        >
            <div class="sticky top-0 flex items-center justify-between border-b border-zinc-200 bg-white px-4 py-4">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900">Filtrar produtos</h2>
                    <p class="text-xs text-zinc-500">Ajuste os filtros da loja</p>
                </div>

                <button
                    type="button"
                    onclick="closeFilterMenu()"
                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50"
                    aria-label="Fechar filtros"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="GET" action="{{ route('loja.index') }}" class="space-y-5 p-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-600">
                        Buscar
                    </label>
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Nome, código, marca..."
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-600">
                        Categoria
                    </label>
                    <input
                        type="text"
                        name="codigo_da_categoria"
                        value="{{ request('codigo_da_categoria') }}"
                        placeholder="Ex: 123"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-600">
                        Marca
                    </label>
                    <input
                        type="text"
                        name="marca"
                        value="{{ request('marca') }}"
                        placeholder="Ex: Nike"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-600">
                        Cor
                    </label>
                    <input
                        type="text"
                        name="cor"
                        value="{{ request('cor') }}"
                        placeholder="Ex: Preto"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-600">
                        Tamanho
                    </label>
                    <input
                        type="text"
                        name="tamanho"
                        value="{{ request('tamanho') }}"
                        placeholder="Ex: M"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-600">
                        Estado
                    </label>
                    <select
                        name="estado"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200"
                    >
                        <option value="">Todos</option>
                        <option value="novo" {{ request('estado') === 'novo' ? 'selected' : '' }}>Novo</option>
                        <option value="seminovo" {{ request('estado') === 'seminovo' ? 'selected' : '' }}>Seminovo</option>
                        <option value="usado" {{ request('estado') === 'usado' ? 'selected' : '' }}>Usado</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-600">
                            Preço mín.
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            name="preco_min"
                            value="{{ request('preco_min') }}"
                            placeholder="0,00"
                            class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200"
                        >
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-600">
                            Preço máx.
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            name="preco_max"
                            value="{{ request('preco_max') }}"
                            placeholder="999,99"
                            class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200"
                        >
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button
                        type="submit"
                        class="flex-1 rounded-xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white hover:bg-zinc-800"
                    >
                        Aplicar filtros
                    </button>

                    <a
                        href="{{ route('loja.index') }}"
                        class="flex-1 rounded-xl border border-zinc-300 bg-white px-4 py-3 text-center text-sm font-semibold text-zinc-700 hover:bg-zinc-50"
                    >
                        Limpar
                    </a>
                </div>
            </form>
        </aside>

        <!-- Conteúdo -->
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if(request()->filled('success'))
              <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                  {{ request('success') }}
              </div>
            @endif

            @if(request()->filled('error'))
              <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                  {{ request('error') }}
              </div>
            @endif

            @if(session('success'))
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ session('error') }}
                </div>
            @endif

            @if($items->count() === 0)
                <div class="rounded-2xl border border-zinc-200 bg-white p-10 text-center">
                    <div class="text-base font-semibold">Nenhum item encontrado</div>
                    <div class="mt-1 text-sm text-zinc-500">Tente ajustar os filtros ou a busca.</div>
                </div>
            @else
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-semibold tracking-tight">Produtos</h1>
                        <p class="mt-1 text-sm text-zinc-500">
                            Mostrando {{ $items->count() }} de {{ $items->total() }} itens
                        </p>
                    </div>

                    <button
                        type="button"
                        onclick="openFilterMenu()"
                        class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-800 shadow-sm hover:bg-zinc-50"
                    >
                        <i class="fas fa-bars"></i>
                        <span>Filtros</span>
                    </button>
                </div>

                @if(
                    request()->filled('q') ||
                    request()->filled('categoria') ||
                    request()->filled('codigo_da_categoria') ||
                    request()->filled('marca') ||
                    request()->filled('cor') ||
                    request()->filled('tamanho') ||
                    request()->filled('estado') ||
                    request()->filled('preco_min') ||
                    request()->filled('preco_max')
                )
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if(request('q'))
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-zinc-700 ring-1 ring-zinc-200">
                                Busca: {{ request('q') }}
                            </span>
                        @endif

                        @if($categoriaAtiva)
                            <span class="rounded-full bg-zinc-900 px-3 py-1 text-xs font-medium text-white">
                                {{ $categoriaAtiva->name }}
                            </span>
                        @endif

                        @if(request('marca'))
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-zinc-700 ring-1 ring-zinc-200">
                                Marca: {{ request('marca') }}
                            </span>
                        @endif

                        @if(request('cor'))
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-zinc-700 ring-1 ring-zinc-200">
                                Cor: {{ request('cor') }}
                            </span>
                        @endif

                        @if(request('tamanho'))
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-zinc-700 ring-1 ring-zinc-200">
                                Tamanho: {{ request('tamanho') }}
                            </span>
                        @endif

                        @if(request('estado'))
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-zinc-700 ring-1 ring-zinc-200">
                                Estado: {{ request('estado') }}
                            </span>
                        @endif

                        @if(request('preco_min'))
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-zinc-700 ring-1 ring-zinc-200">
                                Mín: {{ request('preco_min') }}
                            </span>
                        @endif

                        @if(request('preco_max'))
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-zinc-700 ring-1 ring-zinc-200">
                                Máx: {{ request('preco_max') }}
                            </span>
                        @endif

                        <a
                            href="{{ route('loja.index') }}"
                            class="rounded-full bg-zinc-900 px-3 py-1 text-xs font-medium text-white"
                        >
                            Limpar filtros
                        </a>
                    </div>
                @endif

                <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($items as $item)
                        @php
                            $cover = $item->medias->firstWhere('is_cover', 1);

                            if (!$cover) {
                                $cover = $item->medias
                                    ->where('media_type', 'image')
                                    ->sortBy('position')
                                    ->first();
                            }

                            $imgUrl = $cover?->url;
                            $sub = trim(($item->marca ?? '') . ' ' . ($item->modelo ?? ''));
                        @endphp

                        <div class="group overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition hover:shadow-md">
                            <a href="{{ route('loja.show', $item) }}" class="block">
                                <div class="relative aspect-[4/5] w-full overflow-hidden bg-zinc-100">
                                    @php
                                        $mediasOrdenadas = $item->medias
                                            ->where('media_type', 'image')
                                            ->sortBy([
                                                ['is_cover', 'desc'],
                                                ['position', 'asc'],
                                                ['id', 'asc'],
                                            ]);

                                        $cover = $mediasOrdenadas->first();
                                        $path = $cover?->thumbnail_url ?: $cover?->url;
                                        $publicUrl = $path ? \Illuminate\Support\Facades\Storage::url($path) : null;
                                        $alt = $cover?->alt_text ?: $item->nome_do_produto;
                                    @endphp

                                    @if($publicUrl)
                                        <img
                                            src="{{ $publicUrl }}"
                                            alt="{{ $alt }}"
                                            class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                                            loading="lazy"
                                        />
                                    @else
                                        <div class="flex h-full w-full items-center justify-center text-sm text-zinc-400">
                                            Sem imagem
                                        </div>
                                    @endif

                                    <div class="absolute left-3 top-3">
                                        <span class="inline-flex items-center rounded-full bg-white/90 px-3 py-1 text-xs font-medium text-zinc-700 ring-1 ring-zinc-200">
                                            {{ $item->estado }}
                                        </span>
                                    </div>
                                </div>

                                <div class="p-4">
                                    <div class="line-clamp-2 text-sm font-semibold leading-5 text-zinc-900">
                                        {{ $item->nome_do_produto }}
                                    </div>

                                    @if($sub !== '')
                                        <div class="mt-1 line-clamp-1 text-xs text-zinc-500">
                                            {{ $sub }}
                                        </div>
                                    @endif

                                    <div class="mt-2 flex items-baseline justify-between gap-3">
                                        <div class="text-xs font-medium text-zinc-500">
                                            {{ $item->codigo }}
                                        </div>

                                        <div class="text-sm font-semibold text-zinc-900 text-right tabular-nums">
                                            {{ 'R$ ' . number_format((float)$item->preco, 2, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                            </a>

                            <div class="border-t border-zinc-100 px-4 py-2">
                                <div class="mt-2 flex flex-wrap gap-2 text-xs text-zinc-500">
                                    @if($item->cor)
                                        <span class="rounded-full bg-zinc-100 px-2 py-1">{{ $item->cor }}</span>
                                    @endif

                                    @if($item->tamanho)
                                        <span class="rounded-full bg-zinc-100 px-2 py-1">{{ $item->tamanho }}</span>
                                    @endif

                                    @if($item->codigo_da_categoria)
                                        <span class="rounded-full bg-zinc-100 px-2 py-1">Cat: {{ $item->codigo_da_categoria }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $items->appends(request()->query())->links() }}
                </div>
            @endif
        </main>

        <footer class="border-t border-zinc-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-6 text-xs text-zinc-500 sm:px-6 lg:px-8">
                {{ date('Y') }} — Minha Mania
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
            if (event.key === 'Escape') {
                closeFilterMenu();
            }
        });

        // Corrigir dropdowns no mobile
        document.querySelectorAll('.group\/cat').forEach(group => {
            const trigger = group.querySelector('.cat-trigger');
            const dropdown = group.querySelector('.cat-dropdown');
            
            if (trigger && dropdown) {
                trigger.addEventListener('click', (e) => {
                    if (window.innerWidth < 768) {
                        const isHidden = dropdown.classList.contains('hidden');
                        
                        // Fecha outros
                        document.querySelectorAll('.cat-dropdown').forEach(d => d.classList.add('hidden'));
                        
                        if (isHidden) {
                            e.preventDefault(); // Na primeira vez abre o menu
                            dropdown.classList.remove('hidden');
                        }
                        // Na segunda vez (clique no link ja aberto), ele navega normalmente
                    }
                });
            }
        });
    </script>
</body>
</html>
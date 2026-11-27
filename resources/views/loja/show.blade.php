<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $medias = $item->medias
            ->sortBy([['is_cover', 'desc'], ['position', 'asc'], ['id', 'asc']])
            ->values();

        // Prioriza imagem para Open Graph / compartilhamento no WhatsApp
        $ogImageObj = $medias->where('media_type', 'image')->first();
        $ogRawPath = $ogImageObj?->thumbnail_url ?: $ogImageObj?->url ?: $item->image;
        $ogImageUrl = $ogRawPath
            ? (str_starts_with($ogRawPath, 'http') ? $ogRawPath : url(\Illuminate\Support\Facades\Storage::url($ogRawPath)))
            : asset('android-chrome-512x512.png');

        $ogDetails = array_filter([
            $item->tamanho ? ('Tam ' . $item->tamanho) : null,
            $item->marca ?: null,
            $item->formatted_final_price,
            'Peça única no Minha Mania Brechó',
        ]);
        $ogDesc = implode(' • ', $ogDetails);
        $hasPromotion = $item->final_price < $item->preco;
    @endphp
    <title>{{ $item->nome_do_produto }} ({{ $item->formatted_final_price }}) — Minha Mania Brechó</title>
    <meta name="description" content="{{ $ogDesc }}">

    <!-- Open Graph / Preview rico no WhatsApp e Instagram -->
    <meta property="og:type" content="product">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="Minha Mania Brechó">
    <meta property="og:title" content="{{ $item->nome_do_produto }} — {{ $item->formatted_final_price }}">
    <meta property="og:description" content="{{ $ogDesc }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImageUrl }}">

    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#F5148C">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
    @include('partials.site-tracking')
</head>
<body class="bg-zinc-50 text-zinc-900 antialiased">
    <div class="min-h-screen flex flex-col">
        <!-- Cabeçalho -->
        <header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8 gap-3">
                <a href="{{ route('loja.index') }}" class="inline-flex items-center gap-2 text-sm font-extrabold text-zinc-900 hover:text-pink-700 transition">
                    <i class="fas fa-arrow-left text-xs"></i>
                    <span>Voltar para a Loja</span>
                </a>

                <div class="flex items-center gap-3">
                    @auth
                        @php
                            $isUserAdmin = in_array(auth()->user()->role, ['admin', 'admin_master', 'brecho_admin'], true) || !empty(auth()->user()->is_admin);
                        @endphp
                        @if($isUserAdmin)
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-xs font-bold text-zinc-700 hover:text-pink-700">
                                <i class="fas fa-layer-group"></i> Painel
                            </a>
                        @else
                            <a href="{{ route('portal.sacolinha') }}"
                               class="inline-flex items-center gap-2 rounded-xl bg-pink-50 border border-pink-200 px-3 py-1.5 text-xs font-extrabold text-pink-800 hover:bg-pink-100 transition">
                                <i class="fas fa-shopping-bag text-pink-600"></i>
                                <span>Sacolinha</span>
                                <span id="headerBagCount" class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-pink-600 px-1.5 text-[11px] font-extrabold text-white">
                                    {{ $sacolinhaCount ?? 0 }}
                                </span>
                            </a>
                        @endif
                    @else
                        <button type="button" id="headerLoginBtn"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-zinc-900 px-3 py-1.5 text-xs font-bold text-white hover:bg-black transition cursor-pointer">
                            <i class="fab fa-whatsapp text-emerald-400"></i>
                            <span>Entrar</span>
                        </button>
                    @endauth
                </div>
            </div>
        </header>

        <main class="flex-grow mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- Alertas visíveis (erro / sucesso) -->
            @if(request()->filled('error') || session('error'))
                <div class="mb-5 rounded-2xl border border-red-300 bg-red-50 px-4 py-3.5 text-sm font-semibold text-red-900">
                    <i class="fas fa-exclamation-circle text-red-600 mr-1.5"></i>
                    {{ request('error') ?: session('error') }}
                </div>
            @endif
            <div id="inlineErrorBox" class="hidden mb-5 rounded-2xl border border-red-300 bg-red-50 px-4 py-3.5 text-sm font-semibold text-red-900"></div>

            @if(!$isAvailable)
                <!-- Peça já vendida / reservada (em vez de tela fria 404!) -->
                <div class="mb-8 rounded-3xl border border-pink-200 bg-white p-6 sm:p-8 shadow-sm">
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        <div class="relative w-32 h-40 rounded-2xl overflow-hidden bg-zinc-100 shrink-0 border border-zinc-200">
                            <img src="{{ $ogImageUrl }}" alt="{{ $item->nome_do_produto }}" class="w-full h-full object-cover opacity-75">
                            <span class="absolute inset-x-2 bottom-2 rounded-lg bg-zinc-900/90 py-1 text-center text-[10px] font-extrabold uppercase text-white">
                                Vendida
                            </span>
                        </div>
                        <div class="text-center sm:text-left">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-pink-100 px-3 py-1 text-xs font-extrabold text-pink-800">
                                <i class="fas fa-heart"></i> Peça única já vendida!
                            </span>
                            <h1 class="mt-2 text-xl sm:text-2xl font-extrabold text-zinc-900">
                                {{ $item->nome_do_produto }} já encontrou uma dona
                            </h1>
                            <p class="mt-1.5 text-sm text-zinc-600 max-w-xl">
                                No brechó cada peça é exclusiva — mas separamos abaixo opções parecidas
                                @if($item->tamanho) no <strong>tamanho {{ $item->tamanho }}</strong> @endif
                                que ainda estão disponíveis para você!
                            </p>
                            <div class="mt-4 flex flex-wrap justify-center sm:justify-start gap-2.5">
                                @if($item->tamanho)
                                    <a href="{{ route('loja.index', ['tamanho' => strtoupper(trim($item->tamanho)), 'tamanho_exato' => 1]) }}"
                                       class="inline-flex items-center gap-2 rounded-xl bg-pink-600 hover:bg-pink-700 px-5 py-2.5 text-sm font-extrabold text-white transition">
                                        Ver todas no tamanho {{ strtoupper(trim($item->tamanho)) }} →
                                    </a>
                                @endif
                                <a href="{{ route('loja.index') }}"
                                   class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 bg-white hover:bg-zinc-100 px-5 py-2.5 text-sm font-bold text-zinc-900 transition">
                                    Explorar toda a Loja
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- Produto Disponível -->
                @php
                    $videoItem = $medias->firstWhere('media_type', 'video');
                    if ($videoItem) {
                        $medias = $medias->filter(fn($m) => $m->id !== $videoItem->id)->prepend($videoItem)->values();
                    }

                    if ($medias->isEmpty() && $item->image) {
                        $firstFull = str_starts_with($item->image, 'http') ? $item->image : \Illuminate\Support\Facades\Storage::url($item->image);
                        $firstIsVideo = false;
                        $firstAlt = $item->nome_do_produto;
                    } else {
                        $first = $medias->first();
                        $firstPath = $first?->url;
                        $firstFull = $firstPath ? (str_starts_with($firstPath, 'http') ? $firstPath : \Illuminate\Support\Facades\Storage::url($firstPath)) : null;
                        $firstIsVideo = ($first?->media_type === 'video');
                        $firstAlt = $first?->alt_text ?? $item->nome_do_produto;
                    }
                    $catNames = $item->categorias->pluck('name')->filter()->implode(' • ');
                @endphp

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                    <!-- Galeria -->
                    <section class="lg:col-span-7">
                        <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm">
                            <div id="mainImageWrap" class="relative aspect-[4/5] w-full bg-zinc-900 flex items-center justify-center overflow-hidden">
                                <img id="mainImage"
                                     src="{{ !$firstIsVideo && $firstFull ? $firstFull : '' }}"
                                     alt="{{ $firstAlt }}"
                                     class="h-full w-full object-cover cursor-pointer select-none {{ $firstIsVideo || !$firstFull ? 'hidden' : '' }}" />

                                <video id="mainVideo"
                                       src="{{ $firstIsVideo && $firstFull ? $firstFull : '' }}"
                                       controls autoplay muted loop playsinline preload="metadata"
                                       class="h-full w-full object-cover bg-zinc-900 {{ $firstIsVideo ? '' : 'hidden' }}"></video>

                                @if(!$firstFull)
                                    <div class="flex h-full w-full items-center justify-center text-sm text-zinc-500 bg-zinc-100">
                                        Sem imagem
                                    </div>
                                @endif

                                @if($hasPromotion)
                                    @php $percent = round(100 - ($item->final_price / $item->preco * 100)); @endphp
                                    <span class="absolute right-4 bottom-4 z-10 rounded-full bg-red-600 px-3.5 py-1.5 text-xs font-extrabold text-white shadow-lg">
                                        -{{ $percent }}% OFF
                                    </span>
                                @endif
                            </div>

                            @if($medias->count() > 1)
                                <div class="border-t border-zinc-100 p-3.5">
                                    <div class="grid grid-cols-5 gap-2.5 sm:grid-cols-6">
                                        @foreach($medias as $index => $m)
                                            @php
                                                $isVideo = ($m->media_type === 'video');
                                                $rawUrl = $m->url;
                                                $fullUrl = $rawUrl ? (str_starts_with($rawUrl, 'http') ? $rawUrl : \Illuminate\Support\Facades\Storage::url($rawUrl)) : null;
                                                $thumbRaw = $m->thumbnail_url ?: ($isVideo ? null : $m->url);
                                                $thumbUrl = $thumbRaw ? (str_starts_with($thumbRaw, 'http') ? $thumbRaw : \Illuminate\Support\Facades\Storage::url($thumbRaw)) : null;
                                            @endphp
                                            <button type="button"
                                                    class="thumb-btn relative overflow-hidden rounded-xl border border-zinc-200 bg-white hover:border-pink-400 transition {{ $index === 0 ? 'ring-2 ring-pink-600' : '' }}"
                                                    data-full="{{ $fullUrl }}"
                                                    data-type="{{ $isVideo ? 'video' : 'image' }}"
                                                    data-index="{{ $index }}"
                                                    aria-label="{{ $isVideo ? 'Ver vídeo' : 'Ver foto' }}">
                                                <div class="aspect-square w-full bg-zinc-100 relative flex items-center justify-center">
                                                    @if($isVideo)
                                                        <div class="w-full h-full bg-zinc-900 flex flex-col items-center justify-center text-white">
                                                            <i class="fas fa-play text-pink-400 text-sm"></i>
                                                            <span class="text-[9px] font-bold uppercase mt-1">Vídeo</span>
                                                        </div>
                                                    @elseif($thumbUrl)
                                                        <img src="{{ $thumbUrl }}" alt="{{ $m->alt_text ?? 'Miniatura' }}" class="h-full w-full object-cover" loading="lazy">
                                                    @else
                                                        <span class="text-xs text-zinc-400">—</span>
                                                    @endif
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>

                    <!-- Informações e Compra -->
                    <aside class="lg:col-span-5">
                        <div class="rounded-3xl border border-zinc-200 bg-white p-5 sm:p-6 shadow-sm sticky top-20">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-3 py-1 text-xs font-extrabold text-emerald-800">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    Peça Única Disponível
                                </span>
                                @if($item->estado)
                                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-3 py-1 text-xs font-bold text-zinc-800 capitalize">
                                        {{ $item->estado }}
                                    </span>
                                @endif
                                @if($catNames)
                                    <span class="inline-flex items-center rounded-full bg-pink-50 px-3 py-1 text-xs font-bold text-pink-800">
                                        {{ $catNames }}
                                    </span>
                                @endif
                            </div>

                            <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-zinc-900 leading-snug">
                                {{ $item->nome_do_produto }}
                            </h1>

                            @php $sub = trim(($item->marca ?? '') . ' ' . ($item->modelo ?? '')); @endphp
                            @if($sub !== '')
                                <p class="mt-1 text-sm font-semibold text-zinc-600">{{ $sub }}</p>
                            @endif

                            <!-- Preço -->
                            <div class="mt-5 flex items-baseline justify-between gap-3 rounded-2xl bg-zinc-50 border border-zinc-200 px-4 py-3.5">
                                <div class="flex items-baseline gap-2.5">
                                    @if($hasPromotion)
                                        <span class="text-sm font-semibold text-zinc-500 line-through">
                                            R$ {{ number_format((float) $item->preco, 2, ',', '.') }}
                                        </span>
                                        <span class="text-3xl font-extrabold text-red-600 tabular-nums">
                                            {{ $item->formatted_final_price }}
                                        </span>
                                    @else
                                        <span class="text-3xl font-extrabold text-zinc-900 tabular-nums">
                                            {{ $item->formatted_final_price }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-xs font-bold text-zinc-600">Cód. {{ $item->codigo }}</span>
                            </div>

                            <!-- Botão Principal -->
                            <div class="mt-5 space-y-2.5">
                                <form id="addToBagForm" class="w-full">
                                    @csrf
                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                    <input type="hidden" name="quantity" value="1">

                                    <button type="submit" id="addToBagBtn"
                                            class="w-full rounded-2xl bg-pink-600 hover:bg-pink-700 px-5 py-4 text-base font-extrabold text-white shadow-md shadow-pink-600/20 transition active:scale-[0.99] disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center gap-2 cursor-pointer">
                                        <i class="fas fa-shopping-bag"></i>
                                        <span id="addToBagLabel">Adicionar na Minha Sacolinha</span>
                                    </button>
                                </form>

                                <p class="text-center text-xs text-zinc-600 font-medium">
                                    <i class="fas fa-shield-alt text-emerald-600 mr-1"></i>
                                    Reserve agora e junte peças por até <strong>15 dias</strong> em um frete só.
                                </p>

                                <div class="grid grid-cols-2 gap-2.5 pt-1">
                                    <button type="button" id="shareProductBtn"
                                            class="rounded-xl border border-zinc-300 bg-white hover:bg-zinc-100 px-3 py-2.5 text-xs font-bold text-zinc-800 transition flex items-center justify-center gap-1.5 cursor-pointer">
                                        <i class="fas fa-share-alt text-pink-600"></i>
                                        <span id="shareLabel">Compartilhar</span>
                                    </button>

                                    @php
                                        $storeWa = preg_replace('/\D+/', '', (string) config('services.store.whatsapp_contact', ''));
                                        $waText = urlencode("Olá! Tenho uma dúvida sobre a peça {$item->nome_do_produto} (Cód. {$item->codigo}): " . url()->current());
                                    @endphp
                                    @if($storeWa)
                                        <a href="https://wa.me/{{ str_starts_with($storeWa, '55') ? $storeWa : '55' . $storeWa }}?text={{ $waText }}"
                                           target="_blank" rel="noopener"
                                           class="rounded-xl border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 px-3 py-2.5 text-xs font-bold text-emerald-900 transition flex items-center justify-center gap-1.5">
                                            <i class="fab fa-whatsapp text-emerald-600 text-sm"></i>
                                            <span>Tirar dúvida</span>
                                        </a>
                                    @else
                                        <a href="{{ route('loja.index', $item->tamanho ? ['tamanho' => strtoupper(trim($item->tamanho)), 'tamanho_exato' => 1] : []) }}"
                                           class="rounded-xl border border-zinc-300 bg-white hover:bg-zinc-100 px-3 py-2.5 text-xs font-bold text-zinc-800 transition flex items-center justify-center gap-1.5">
                                            <i class="fas fa-ruler-horizontal text-pink-600"></i>
                                            <span>Mais Tam {{ $item->tamanho ?: 'similar' }}</span>
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <!-- Ficha técnica -->
                            <div class="mt-6 border-t border-zinc-200 pt-5">
                                <dl class="grid grid-cols-2 gap-4 text-sm">
                                    <div class="rounded-xl bg-zinc-50 p-3 border border-zinc-100">
                                        <dt class="text-[11px] font-bold uppercase text-zinc-500">Tamanho</dt>
                                        <dd class="mt-0.5 text-base font-extrabold text-zinc-900">{{ $item->tamanho ?: '—' }}</dd>
                                    </div>
                                    <div class="rounded-xl bg-zinc-50 p-3 border border-zinc-100">
                                        <dt class="text-[11px] font-bold uppercase text-zinc-500">Cor</dt>
                                        <dd class="mt-0.5 text-sm font-bold text-zinc-900">{{ $item->cor ?: '—' }}</dd>
                                    </div>
                                    <div class="rounded-xl bg-zinc-50 p-3 border border-zinc-100">
                                        <dt class="text-[11px] font-bold uppercase text-zinc-500">Marca</dt>
                                        <dd class="mt-0.5 text-sm font-bold text-zinc-900">{{ $item->marca ?: '—' }}</dd>
                                    </div>
                                    <div class="rounded-xl bg-zinc-50 p-3 border border-zinc-100">
                                        <dt class="text-[11px] font-bold uppercase text-zinc-500">Estado</dt>
                                        <dd class="mt-0.5 text-sm font-bold text-zinc-900 capitalize">{{ $item->estado ?: '—' }}</dd>
                                    </div>
                                </dl>
                            </div>

                            @if($item->descricao)
                                <div class="mt-5 border-t border-zinc-200 pt-5">
                                    <h2 class="text-xs font-extrabold uppercase tracking-wider text-zinc-700">Detalhes da peça</h2>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-zinc-700">{{ $item->descricao }}</p>
                                </div>
                            @endif
                        </div>
                    </aside>
                </div>
            @endif

            <!-- Peças Relacionadas -->
            @if(isset($relatedItems) && $relatedItems->isNotEmpty())
                <section class="mt-12 pt-8 border-t border-zinc-200">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-lg sm:text-xl font-extrabold text-zinc-900">
                            @if(!empty($item->tamanho))
                                Mais achadinhos no tamanho {{ $item->tamanho }}
                            @else
                                Você também pode gostar
                            @endif
                        </h2>
                        <a href="{{ route('loja.index') }}" class="text-xs sm:text-sm font-extrabold text-pink-700 hover:underline">
                            Ver tudo →
                        </a>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5 sm:gap-5">
                        @foreach($relatedItems as $rel)
                            @php
                                $rImg = $rel->medias->where('media_type', 'image')->first();
                                $rPath = $rImg?->thumbnail_url ?: $rImg?->url ?: $rel->image;
                                $rUrl = $rPath ? (str_starts_with($rPath, 'http') ? $rPath : \Illuminate\Support\Facades\Storage::url($rPath)) : null;
                            @endphp
                            <a href="{{ route('loja.show', $rel) }}"
                               class="group overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xs hover:shadow-md transition flex flex-col">
                                <div class="relative aspect-[4/5] w-full bg-zinc-100 overflow-hidden">
                                    @if($rUrl)
                                        <img src="{{ $rUrl }}" alt="{{ $rel->nome_do_produto }}" loading="lazy"
                                             class="h-full w-full object-cover group-hover:scale-105 transition duration-300">
                                    @endif
                                    @if($rel->tamanho)
                                        <span class="absolute left-2 top-2 rounded-md bg-zinc-900/90 px-2 py-0.5 text-[10px] font-extrabold text-white uppercase">
                                            {{ $rel->tamanho }}
                                        </span>
                                    @endif
                                </div>
                                <div class="p-3">
                                    <p class="text-xs font-bold text-zinc-900 line-clamp-2">{{ $rel->nome_do_produto }}</p>
                                    <p class="mt-1.5 text-sm font-extrabold text-pink-700">{{ $rel->formatted_final_price }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </main>

        <footer class="border-t border-zinc-200 bg-white mt-10">
            <div class="mx-auto max-w-7xl px-4 py-6 text-xs text-zinc-600 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2">
                <span>&copy; {{ date('Y') }} Minha Mania Brechó</span>
                <a href="{{ route('loja.index') }}" class="font-bold text-pink-700 hover:underline">Explorar Loja Online</a>
            </div>
        </footer>
    </div>

    <!-- MODAL DE SUCESSO APÓS RESERVAR (com ação imediata — sem travar 6.8s!) -->
    <div id="successModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl border border-zinc-200 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 text-2xl">
                <i class="fas fa-check"></i>
            </div>
            <h3 class="mt-4 text-xl font-extrabold text-zinc-900">Peça reservada na sacolinha!</h3>
            <p class="mt-1.5 text-sm text-zinc-600 font-medium">{{ $item->nome_do_produto }}</p>
            <p class="mt-1 text-xs text-zinc-500">Você pode continuar garimpando e pagar tudo em um único frete.</p>

            <div class="mt-6 flex flex-col gap-2.5">
                <a href="{{ route('portal.sacolinha') }}"
                   class="w-full rounded-xl bg-pink-600 hover:bg-pink-700 py-3 px-4 text-sm font-extrabold text-white transition">
                    <i class="fas fa-shopping-bag mr-1.5"></i> Ver Minha Sacolinha
                </a>
                <a href="{{ route('loja.index') }}"
                   class="w-full rounded-xl border border-zinc-300 bg-white hover:bg-zinc-100 py-3 px-4 text-sm font-bold text-zinc-900 transition">
                    ← Continuar Garimpando na Loja
                </a>
            </div>
        </div>
    </div>

    <!-- MODAL DE IDENTIFICAÇÃO RÁPIDA (PARA VISITANTES NÃO LOGADOS) -->
    <div id="quickAuthModal" class="fixed inset-0 z-50 hidden items-end sm:items-center justify-center bg-black/60 p-0 sm:p-4 backdrop-blur-xs">
        <div class="w-full sm:max-w-md rounded-t-3xl sm:rounded-3xl bg-white shadow-2xl border border-zinc-200 overflow-hidden max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4">
                <div>
                    <h3 class="text-base font-extrabold text-zinc-900">Identifique-se para reservar</h3>
                    <p class="text-xs text-zinc-600">Leva 10 segundos e já guarda a peça na sua sacolinha</p>
                </div>
                <button type="button" id="closeQuickAuthBtn" aria-label="Fechar"
                        class="h-9 w-9 rounded-xl border border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100 flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Abas do modal -->
            <div class="grid grid-cols-2 gap-1.5 bg-zinc-100 p-1.5 border-b border-zinc-200">
                <button type="button" id="modalTabWa"
                        class="rounded-xl bg-white py-2 text-xs font-extrabold text-zinc-900 shadow-xs flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fab fa-whatsapp text-emerald-600 text-sm"></i> WhatsApp (Sem senha)
                </button>
                <button type="button" id="modalTabEmail"
                        class="rounded-xl py-2 text-xs font-bold text-zinc-600 hover:text-zinc-900 flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-envelope text-pink-600"></i> E-mail e Senha
                </button>
            </div>

            <div class="p-5">
                <div id="modalAuthAlert" class="hidden mb-4 rounded-xl px-3.5 py-2.5 text-xs font-semibold"></div>

                <!-- Painel WhatsApp OTP -->
                <div id="modalPanelWa">
                    <form id="modalWaReqForm" class="space-y-3.5">
                        <div>
                            <label for="modalWaPhone" class="block text-xs font-bold text-zinc-700 mb-1">Seu WhatsApp com DDD</label>
                            <input type="tel" id="modalWaPhone" required placeholder="(48) 99999-9999" autocomplete="tel"
                                   class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-base font-semibold text-zinc-900 placeholder:text-zinc-400 focus:border-pink-500 focus:ring-2 focus:ring-pink-200 outline-none">
                        </div>

                        <div id="modalNewClientBox" class="hidden rounded-2xl bg-pink-50 border border-pink-200 p-3.5 space-y-2">
                            <p class="text-xs font-bold text-pink-900">Primeira compra? Informe seu nome:</p>
                            <input type="text" id="modalWaName" placeholder="Seu nome completo"
                                   class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 placeholder:text-zinc-400 focus:border-pink-500 outline-none">
                        </div>

                        <button type="submit" id="modalWaReqBtn"
                                class="w-full rounded-xl bg-emerald-600 hover:bg-emerald-700 py-3.5 px-4 text-sm font-extrabold text-white transition flex items-center justify-center gap-2 cursor-pointer">
                            <i class="fab fa-whatsapp text-base"></i>
                            <span>Receber código no WhatsApp</span>
                        </button>
                    </form>

                    <form id="modalWaVerifyForm" class="hidden space-y-3.5">
                        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-3 text-xs text-emerald-900 font-medium">
                            <span id="modalWaSentMsg">Código enviado no seu WhatsApp!</span>
                            <button type="button" id="modalChangePhoneBtn" class="block mt-1 font-extrabold underline text-emerald-800 cursor-pointer">
                                Trocar número
                            </button>
                        </div>
                        <div>
                            <label for="modalWaCode" class="block text-xs font-bold text-zinc-700 mb-1">Código de 6 dígitos</label>
                            <input type="text" id="modalWaCode" inputmode="numeric" maxlength="6" required placeholder="000000"
                                   class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-center text-2xl font-extrabold tracking-[0.35em] text-zinc-900 focus:border-pink-500 outline-none">
                        </div>
                        <button type="submit" id="modalWaVerifyBtn"
                                class="w-full rounded-xl bg-pink-600 hover:bg-pink-700 py-3.5 px-4 text-sm font-extrabold text-white transition cursor-pointer">
                            Confirmar e Reservar Peça
                        </button>
                    </form>
                </div>

                <!-- Painel E-mail e Senha -->
                <div id="modalPanelEmail" class="hidden">
                    <form id="modalEmailForm" class="space-y-3.5">
                        <div>
                            <label for="modalEmail" class="block text-xs font-bold text-zinc-700 mb-1">E-mail</label>
                            <input type="email" id="modalEmail" required placeholder="seuemail@exemplo.com"
                                   class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 focus:border-pink-500 outline-none">
                        </div>
                        <div>
                            <label for="modalPassword" class="block text-xs font-bold text-zinc-700 mb-1">Senha</label>
                            <input type="password" id="modalPassword" required placeholder="Sua senha"
                                   class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 focus:border-pink-500 outline-none">
                        </div>
                        <button type="submit" id="modalEmailBtn"
                                class="w-full rounded-xl bg-zinc-900 hover:bg-black py-3 px-4 text-sm font-extrabold text-white transition cursor-pointer">
                            Entrar e Reservar Peça
                        </button>
                    </form>
                </div>

                <div class="mt-4 pt-3.5 border-t border-zinc-200 text-center">
                    <a href="{{ route('register', ['redirect_to' => request()->getRequestUri()]) }}"
                       class="text-xs font-bold text-pink-700 hover:underline">
                        Prefere criar conta com e-mail e senha? Cadastre-se grátis
                    </a>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let isLoggedIn = @json(auth()->check());
    let pendingReserveAfterAuth = false;

    const form = document.getElementById('addToBagForm');
    const addToBagBtn = document.getElementById('addToBagBtn');
    const addToBagLabel = document.getElementById('addToBagLabel');
    const inlineErrorBox = document.getElementById('inlineErrorBox');
    const successModal = document.getElementById('successModal');
    const headerBagCount = document.getElementById('headerBagCount');

    const mainImage = document.getElementById('mainImage');
    const mainVideo = document.getElementById('mainVideo');
    const mainWrap = document.getElementById('mainImageWrap');
    const thumbButtons = Array.from(document.querySelectorAll('.thumb-btn'));
    const mediaList = thumbButtons.map((btn) => ({
        url: btn.getAttribute('data-full'),
        type: btn.getAttribute('data-type') || 'image'
    })).filter(m => !!m.url);

    let currentMediaIndex = 0;

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function updateCsrfToken(newToken) {
        if (!newToken) return;
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', newToken);
        document.querySelectorAll('input[name="_token"]').forEach(inp => inp.value = newToken);
    }

    function showInlineError(msg) {
        if (!inlineErrorBox) return;
        inlineErrorBox.innerHTML = '<i class="fas fa-exclamation-circle text-red-600 mr-1.5"></i>' + msg;
        inlineErrorBox.classList.remove('hidden');
        inlineErrorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Galeria
    function setMainMediaByIndex(index) {
        if (mediaList.length === 0) return;
        currentMediaIndex = index;
        const media = mediaList[currentMediaIndex];

        if (media.type === 'video') {
            if (mainImage) mainImage.classList.add('hidden');
            if (mainVideo) {
                mainVideo.classList.remove('hidden');
                if (mainVideo.getAttribute('src') !== media.url) {
                    mainVideo.setAttribute('src', media.url);
                    mainVideo.load();
                }
                mainVideo.muted = true;
                mainVideo.play().catch(() => {});
            }
        } else {
            if (mainVideo) {
                mainVideo.pause();
                mainVideo.classList.add('hidden');
            }
            if (mainImage) {
                mainImage.classList.remove('hidden');
                mainImage.setAttribute('src', media.url);
            }
        }

        thumbButtons.forEach((btn, i) => {
            btn.classList.toggle('ring-2', i === currentMediaIndex);
            btn.classList.toggle('ring-pink-600', i === currentMediaIndex);
        });
    }

    thumbButtons.forEach((btn, idx) => btn.addEventListener('click', () => setMainMediaByIndex(idx)));
    if (mainImage) {
        mainImage.addEventListener('click', () => {
            if (mediaList.length > 1) setMainMediaByIndex((currentMediaIndex + 1) % mediaList.length);
        });
    }

    if (mainWrap && mediaList.length > 1) {
        let touchStartX = 0;
        mainWrap.addEventListener('touchstart', (e) => { touchStartX = e.changedTouches[0].clientX; }, { passive: true });
        mainWrap.addEventListener('touchend', (e) => {
            const delta = e.changedTouches[0].clientX - touchStartX;
            if (Math.abs(delta) < 40) return;
            setMainMediaByIndex(delta < 0
                ? (currentMediaIndex + 1) % mediaList.length
                : (currentMediaIndex - 1 + mediaList.length) % mediaList.length);
        }, { passive: true });
    }

    if (mainVideo) {
        mainVideo.addEventListener('loadedmetadata', () => {
            if (mainVideo.videoWidth > mainVideo.videoHeight) {
                mainVideo.classList.remove('object-cover');
                mainVideo.classList.add('object-contain');
            } else {
                mainVideo.classList.remove('object-contain');
                mainVideo.classList.add('object-cover');
            }
        });
        if (!mainVideo.classList.contains('hidden')) {
            mainVideo.muted = true;
            mainVideo.play().catch(() => {});
        }
    }

    // Compartilhar peça
    const shareBtn = document.getElementById('shareProductBtn');
    const shareLabel = document.getElementById('shareLabel');
    if (shareBtn) {
        shareBtn.addEventListener('click', async () => {
            const shareData = {
                title: @json($item->nome_do_produto . ' — ' . $item->formatted_final_price),
                text: @json('Olha esse achadinho no Minha Mania Brechó: ' . $item->nome_do_produto),
                url: window.location.href
            };
            if (navigator.share) {
                try { await navigator.share(shareData); return; } catch (e) {}
            }
            try {
                await navigator.clipboard.writeText(window.location.href);
                shareLabel.textContent = 'Link copiado!';
                setTimeout(() => { shareLabel.textContent = 'Compartilhar'; }, 2000);
            } catch (e) {}
        });
    }

    // Modal de Acesso Rápido
    const quickAuthModal = document.getElementById('quickAuthModal');
    const closeQuickAuthBtn = document.getElementById('closeQuickAuthBtn');
    const headerLoginBtn = document.getElementById('headerLoginBtn');
    const modalTabWa = document.getElementById('modalTabWa');
    const modalTabEmail = document.getElementById('modalTabEmail');
    const modalPanelWa = document.getElementById('modalPanelWa');
    const modalPanelEmail = document.getElementById('modalPanelEmail');
    const modalAuthAlert = document.getElementById('modalAuthAlert');

    const modalWaReqForm = document.getElementById('modalWaReqForm');
    const modalWaVerifyForm = document.getElementById('modalWaVerifyForm');
    const modalWaPhone = document.getElementById('modalWaPhone');
    const modalWaName = document.getElementById('modalWaName');
    const modalNewClientBox = document.getElementById('modalNewClientBox');
    const modalWaReqBtn = document.getElementById('modalWaReqBtn');
    const modalWaVerifyBtn = document.getElementById('modalWaVerifyBtn');
    const modalWaCode = document.getElementById('modalWaCode');
    const modalWaSentMsg = document.getElementById('modalWaSentMsg');
    const modalChangePhoneBtn = document.getElementById('modalChangePhoneBtn');
    const modalEmailForm = document.getElementById('modalEmailForm');
    const modalEmailBtn = document.getElementById('modalEmailBtn');

    function openQuickAuthModal(shouldReserve) {
        pendingReserveAfterAuth = !!shouldReserve;
        modalAuthAlert.classList.add('hidden');
        quickAuthModal.classList.remove('hidden');
        quickAuthModal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        setTimeout(() => modalWaPhone?.focus(), 100);
    }

    function closeQuickAuthModal() {
        quickAuthModal.classList.add('hidden');
        quickAuthModal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    function showModalAlert(msg, isError) {
        modalAuthAlert.textContent = msg;
        modalAuthAlert.className = isError
            ? 'mb-4 rounded-xl px-3.5 py-2.5 text-xs font-semibold bg-red-50 border border-red-300 text-red-900'
            : 'mb-4 rounded-xl px-3.5 py-2.5 text-xs font-semibold bg-emerald-50 border border-emerald-300 text-emerald-900';
        modalAuthAlert.classList.remove('hidden');
    }

    function switchModalTab(tab) {
        modalAuthAlert.classList.add('hidden');
        if (tab === 'email') {
            modalPanelWa.classList.add('hidden');
            modalPanelEmail.classList.remove('hidden');
            modalTabEmail.className = 'rounded-xl bg-white py-2 text-xs font-extrabold text-zinc-900 shadow-xs flex items-center justify-center gap-1.5 cursor-pointer';
            modalTabWa.className = 'rounded-xl py-2 text-xs font-bold text-zinc-600 hover:text-zinc-900 flex items-center justify-center gap-1.5 cursor-pointer';
        } else {
            modalPanelEmail.classList.add('hidden');
            modalPanelWa.classList.remove('hidden');
            modalTabWa.className = 'rounded-xl bg-white py-2 text-xs font-extrabold text-zinc-900 shadow-xs flex items-center justify-center gap-1.5 cursor-pointer';
            modalTabEmail.className = 'rounded-xl py-2 text-xs font-bold text-zinc-600 hover:text-zinc-900 flex items-center justify-center gap-1.5 cursor-pointer';
        }
    }

    if (closeQuickAuthBtn) closeQuickAuthBtn.addEventListener('click', closeQuickAuthModal);
    if (headerLoginBtn) headerLoginBtn.addEventListener('click', () => openQuickAuthModal(false));
    if (modalTabWa) modalTabWa.addEventListener('click', () => switchModalTab('wa'));
    if (modalTabEmail) modalTabEmail.addEventListener('click', () => switchModalTab('email'));

    if (modalWaPhone) {
        modalWaPhone.addEventListener('input', function () {
            let v = this.value.replace(/\D/g, '').slice(0, 11);
            if (v.length > 6) {
                this.value = `(${v.slice(0, 2)}) ${v.slice(2, v.length - 4)}-${v.slice(v.length - 4)}`;
            } else if (v.length > 2) {
                this.value = `(${v.slice(0, 2)}) ${v.slice(2)}`;
            } else {
                this.value = v;
            }
        });
    }

    // Função central: reservar peça na sacolinha
    async function reserveItemNow() {
        if (!form) return;
        inlineErrorBox?.classList.add('hidden');

        if (addToBagBtn) addToBagBtn.disabled = true;
        if (addToBagLabel) addToBagLabel.textContent = 'Reservando peça...';

        try {
            const formData = new FormData(form);
            const resp = await fetch(@json(route('loja.adicionar_item')), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await resp.json().catch(() => ({}));

            if (resp.status === 401 || data.needs_auth) {
                isLoggedIn = false;
                if (addToBagBtn) addToBagBtn.disabled = false;
                if (addToBagLabel) addToBagLabel.textContent = 'Adicionar na Minha Sacolinha';
                openQuickAuthModal(true);
                return;
            }

            if (resp.ok && data.success) {
                if (headerBagCount && typeof data.sacolinha_count !== 'undefined') {
                    headerBagCount.textContent = data.sacolinha_count;
                }
                if (addToBagLabel) addToBagLabel.textContent = 'Peça reservada na sua sacolinha ✓';
                if (addToBagBtn) {
                    addToBagBtn.classList.remove('bg-pink-600', 'hover:bg-pink-700');
                    addToBagBtn.classList.add('bg-emerald-600');
                }
                if (typeof confetti === 'function') {
                    confetti({ particleCount: 90, spread: 80, origin: { y: 0.6 } });
                }
                if (successModal) {
                    successModal.classList.remove('hidden');
                    successModal.classList.add('flex');
                }
                return;
            }

            showInlineError(data.message || 'Não foi possível reservar a peça. Tente novamente.');
            if (addToBagBtn) addToBagBtn.disabled = false;
            if (addToBagLabel) addToBagLabel.textContent = 'Adicionar na Minha Sacolinha';
        } catch (err) {
            showInlineError('Erro de conexão ao reservar a peça. Verifique sua internet e tente novamente.');
            if (addToBagBtn) addToBagBtn.disabled = false;
            if (addToBagLabel) addToBagLabel.textContent = 'Adicionar na Minha Sacolinha';
        }
    }

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!isLoggedIn) {
                openQuickAuthModal(true);
                return;
            }
            await reserveItemNow();
        });
    }

    // OTP Etapa 1 no modal
    if (modalWaReqForm) {
        modalWaReqForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            modalAuthAlert.classList.add('hidden');
            modalWaReqBtn.disabled = true;
            const prev = modalWaReqBtn.innerHTML;
            modalWaReqBtn.textContent = 'Enviando...';

            try {
                const resp = await fetch(@json(route('loja.auth.request-code')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify({
                        whatsapp: modalWaPhone.value,
                        name: modalWaName.value
                    })
                });
                const data = await resp.json().catch(() => ({}));

                if (resp.ok && data.needs_name) {
                    modalNewClientBox.classList.remove('hidden');
                    modalWaName.required = true;
                    modalWaName.focus();
                    return;
                }

                if (resp.ok && data.success) {
                    modalWaSentMsg.textContent = data.message || 'Código enviado no seu WhatsApp!';
                    modalWaReqForm.classList.add('hidden');
                    modalWaVerifyForm.classList.remove('hidden');
                    modalWaCode.value = '';
                    modalWaCode.focus();
                    return;
                }

                showModalAlert(data.message || 'Não foi possível enviar o código. Use E-mail e Senha.', true);
            } catch (err) {
                showModalAlert('Erro de conexão. Tente usar E-mail e Senha.', true);
            } finally {
                modalWaReqBtn.disabled = false;
                modalWaReqBtn.innerHTML = prev;
            }
        });
    }

    if (modalChangePhoneBtn) {
        modalChangePhoneBtn.addEventListener('click', () => {
            modalWaVerifyForm.classList.add('hidden');
            modalWaReqForm.classList.remove('hidden');
            modalAuthAlert.classList.add('hidden');
            modalWaPhone.focus();
        });
    }

    // OTP Etapa 2 no modal
    if (modalWaVerifyForm) {
        modalWaVerifyForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            modalAuthAlert.classList.add('hidden');
            modalWaVerifyBtn.disabled = true;
            modalWaVerifyBtn.textContent = 'Confirmando...';

            try {
                const resp = await fetch(@json(route('loja.auth.verify-code')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify({
                        whatsapp: modalWaPhone.value,
                        code: modalWaCode.value.replace(/\D/g, '')
                    })
                });
                const data = await resp.json().catch(() => ({}));

                if (resp.ok && data.success) {
                    isLoggedIn = true;
                    updateCsrfToken(data.csrf_token);
                    closeQuickAuthModal();
                    if (pendingReserveAfterAuth) {
                        await reserveItemNow();
                    } else {
                        window.location.reload();
                    }
                    return;
                }

                showModalAlert(data.message || 'Código incorreto. Tente novamente.', true);
            } catch (err) {
                showModalAlert('Erro ao verificar código.', true);
            } finally {
                modalWaVerifyBtn.disabled = false;
                modalWaVerifyBtn.textContent = 'Confirmar e Reservar Peça';
            }
        });
    }

    // Login por e-mail/senha no modal
    if (modalEmailForm) {
        modalEmailForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            modalAuthAlert.classList.add('hidden');
            modalEmailBtn.disabled = true;
            modalEmailBtn.textContent = 'Entrando...';

            try {
                const resp = await fetch(@json(route('loja.auth.email-login')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify({
                        email: document.getElementById('modalEmail').value,
                        password: document.getElementById('modalPassword').value
                    })
                });
                const data = await resp.json().catch(() => ({}));

                if (resp.ok && data.success) {
                    isLoggedIn = true;
                    updateCsrfToken(data.csrf_token);
                    closeQuickAuthModal();
                    if (pendingReserveAfterAuth) {
                        await reserveItemNow();
                    } else {
                        window.location.reload();
                    }
                    return;
                }

                showModalAlert(data.message || 'E-mail ou senha incorretos.', true);
            } catch (err) {
                showModalAlert('Erro de conexão ao tentar entrar.', true);
            } finally {
                modalEmailBtn.disabled = false;
                modalEmailBtn.textContent = 'Entrar e Reservar Peça';
            }
        });
    }
});
</script>
</body>
</html>

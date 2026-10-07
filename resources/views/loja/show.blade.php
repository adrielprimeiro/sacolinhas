<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $item->nome_do_produto }} — Loja</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
    <style>
        #successToast {
            transform: translateY(18px) scale(0.96);
            opacity: 0;
            pointer-events: none;
            transition: all 0.45s ease;
        }

        #successToast.show {
            transform: translateY(0) scale(1);
            opacity: 1;
            pointer-events: auto;
        }

        @media (max-width: 640px) {
            #successToastWrap {
                left: 12px;
                right: 12px;
                top: auto;
                bottom: 16px;
            }

            #successToast {
                width: 100%;
                max-width: 100%;
                border-radius: 18px;
            }
        }
    </style>
</head>
<body class="bg-zinc-50 text-zinc-900">
    <div class="min-h-screen">
        <header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/80 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('loja.index') }}" class="text-sm font-semibold text-zinc-900 hover:text-zinc-700">
                    ← Voltar para a loja
                </a>
                <div class="text-xs text-zinc-500">
                    Código: <span class="font-medium text-zinc-700">{{ $item->codigo }}</span>
                </div>
            </div>
        </header>

        @php
            $medias = $item->medias;
            $cover = $medias->firstWhere('is_cover', 1) ?: $medias->sortBy([['position','asc'],['id','asc']])->first();
            $mainPath = $cover?->url;
            $mainUrl = $mainPath ? \Illuminate\Support\Facades\Storage::url($mainPath) : null;
            $thumbPath = $cover?->thumbnail_url ?: $cover?->url;
            $mainThumbUrl = $thumbPath ? \Illuminate\Support\Facades\Storage::url($thumbPath) : null;
        @endphp

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                @php
                    $medias = $item->medias
                        ->sortBy([
                            ['is_cover', 'desc'],
                            ['position', 'asc'],
                            ['id', 'asc'],
                        ])->values();

                    // Prioriza o vídeo para abrir direto e iniciar logo que a página abre
                    $videoItem = $medias->firstWhere('media_type', 'video');
                    if ($videoItem) {
                        $medias = $medias->filter(fn($m) => $m->id !== $videoItem->id)->prepend($videoItem)->values();
                    }

                    // Se não tiver mídias na tabela item_media, mas tiver o campo image legado
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
                @endphp

                <section class="lg:col-span-7">
                    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
                        <div id="mainImageWrap" class="relative aspect-[4/5] w-full bg-zinc-900 flex items-center justify-center overflow-hidden">
                            <img
                                id="mainImage"
                                src="{{ !$firstIsVideo && $firstFull ? $firstFull : '' }}"
                                alt="{{ $firstAlt }}"
                                class="h-full w-full object-cover cursor-pointer select-none {{ $firstIsVideo || !$firstFull ? 'hidden' : '' }}"
                            />

                            <video
                                id="mainVideo"
                                src="{{ $firstIsVideo && $firstFull ? $firstFull : '' }}"
                                controls
                                autoplay
                                muted
                                loop
                                playsinline
                                preload="auto"
                                class="h-full w-full object-contain bg-black {{ $firstIsVideo ? '' : 'hidden' }}"
                            ></video>

                            @if(!$firstFull)
                                <div id="noMediaNotice" class="flex h-full w-full items-center justify-center text-sm text-zinc-400 bg-zinc-100">
                                    Sem imagem
                                </div>
                            @endif

                            @if($item->final_price < $item->preco)
                                @php
                                    $percent = round(100 - ($item->final_price / $item->preco * 100));
                                @endphp
                                <div class="absolute right-4 bottom-4 z-10 pointer-events-none">
                                    <span class="inline-flex items-center rounded-full bg-red-600 px-4 py-2 text-xs font-bold text-white shadow-xl ring-1 ring-red-700">
                                        -{{ $percent }}% OFF
                                    </span>
                                </div>
                            @endif
                        </div>

                        @if($medias->count() > 1)
                            <div class="border-t border-zinc-100 p-4">
                                <div class="grid grid-cols-5 gap-3 sm:grid-cols-6">
                                    @foreach($medias as $index => $m)
                                        @php
                                            $isVideo = ($m->media_type === 'video');
                                            $rawUrl = $m->url;
                                            $fullUrl = $rawUrl ? (str_starts_with($rawUrl, 'http') ? $rawUrl : \Illuminate\Support\Facades\Storage::url($rawUrl)) : null;
                                            $thumbRaw = $m->thumbnail_url ?: ($isVideo ? null : $m->url);
                                            $thumbUrl = $thumbRaw ? (str_starts_with($thumbRaw, 'http') ? $thumbRaw : \Illuminate\Support\Facades\Storage::url($thumbRaw)) : null;
                                        @endphp

                                        <button
                                            type="button"
                                            class="thumb-btn relative overflow-hidden rounded-xl border border-zinc-200 bg-white hover:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-400 transition {{ $index === 0 ? 'ring-2 ring-zinc-900' : '' }}"
                                            data-full="{{ $fullUrl }}"
                                            data-type="{{ $isVideo ? 'video' : 'image' }}"
                                            data-index="{{ $index }}"
                                            aria-label="{{ $isVideo ? 'Ver vídeo do produto' : 'Selecionar imagem' }}"
                                        >
                                            <div class="aspect-square w-full bg-zinc-100 relative flex items-center justify-center">
                                                @if($isVideo)
                                                    <div class="w-full h-full bg-zinc-900 flex flex-col items-center justify-center text-white relative">
                                                        <svg class="w-6 h-6 text-red-500 fill-current drop-shadow" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                                        <span class="text-[9px] font-bold tracking-wider uppercase mt-1">Vídeo</span>
                                                    </div>
                                                @elseif($thumbUrl)
                                                    <img
                                                        src="{{ $thumbUrl }}"
                                                        alt="{{ $m->alt_text ?? 'Miniatura' }}"
                                                        class="h-full w-full object-cover"
                                                        loading="lazy"
                                                    >
                                                @else
                                                    <div class="flex h-full w-full items-center justify-center text-xs text-zinc-400">—</div>
                                                @endif
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </section>

                <aside class="lg:col-span-5">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700">
                                {{ $item->estado }}
                            </span>

                            @if($item->codigo_da_categoria)
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700">
                                    Cat: {{ $item->codigo_da_categoria }}
                                </span>
                            @endif

                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                Disponível na loja
                            </span>
                        </div>

                        <h1 class="mt-4 text-2xl font-semibold tracking-tight">
                            {{ $item->nome_do_produto }}
                        </h1>

                        @php
                            $sub = trim(($item->marca ?? '') . ' ' . ($item->modelo ?? ''));
                        @endphp

                        @if($sub !== '')
                            <p class="mt-1 text-sm text-zinc-500">{{ $sub }}</p>
                        @endif

                        <div class="mt-5 flex items-baseline justify-between">
                            <div class="flex items-baseline gap-3">
                                @if($item->final_price < $item->preco)
                                    <span class="text-sm font-medium text-zinc-400 line-through">
                                        {{ 'R$ ' . number_format((float)$item->preco, 2, ',', '.') }}
                                    </span>
                                    <div class="text-3xl font-extrabold text-red-600 tabular-nums">
                                        {{ $item->formatted_final_price }}
                                    </div>
                                @else
                                    <div class="text-3xl font-bold text-zinc-900 tabular-nums">
                                        {{ $item->formatted_final_price }}
                                    </div>
                                @endif
                            </div>
                            <div class="text-xs text-zinc-500">
                                Código: <span class="font-medium text-zinc-700">{{ $item->codigo }}</span>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-3">
                            <form id="addToBagForm" class="w-full">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ auth()->id() }}">
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="price" value="{{ number_format((float)$item->final_price, 2, '.', '') }}">
                                <input type="hidden" name="live_id" value="1">

                                <button
                                    type="submit"
                                    id="addToBagBtn"
                                    class="w-full rounded-xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white hover:bg-zinc-800 disabled:opacity-70 disabled:cursor-not-allowed"
                                >
                                    Adicionar na sacolinha
                                </button>
                            </form>
                        </div>

                        <div class="mt-6 border-t border-zinc-100 pt-5">
                            <dl class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <dt class="text-xs text-zinc-500">Cor</dt>
                                    <dd class="mt-1 font-medium text-zinc-900">{{ $item->cor ?: '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-zinc-500">Tamanho</dt>
                                    <dd class="mt-1 font-medium text-zinc-900">{{ $item->tamanho ?: '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-zinc-500">Marca</dt>
                                    <dd class="mt-1 font-medium text-zinc-900">{{ $item->marca ?: '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-zinc-500">Modelo</dt>
                                    <dd class="mt-1 font-medium text-zinc-900">{{ $item->modelo ?: '—' }}</dd>
                                </div>
                            </dl>
                        </div>

                        @if($item->descricao)
                            <div class="mt-6 border-t border-zinc-100 pt-5">
                                <div class="text-xs font-semibold text-zinc-700">Descrição</div>
                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-zinc-600">
                                    {{ $item->descricao }}
                                </p>
                            </div>
                        @endif
                    </div>
                </aside>
            </div>
        </main>

        <footer class="border-t border-zinc-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-6 text-xs text-zinc-500 sm:px-6 lg:px-8">
                {{ date('Y') }} — Loja
            </div>
        </footer>
    </div>

    <div id="successToastWrap" class="fixed top-4 right-4 z-[9999]">
        <div
            id="successToast"
            class="w-[380px] max-w-[calc(100vw-24px)] overflow-hidden rounded-2xl border border-emerald-200 bg-white/95 shadow-2xl backdrop-blur"
        >
            <div class="flex items-start gap-3 p-4 sm:p-5">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="text-sm font-semibold text-zinc-900 sm:text-base">Item adicionado com sucesso</div>
                    <div id="toastTitle" class="mt-1 truncate text-sm font-medium text-emerald-700"></div>
                    <div class="mt-1 text-xs leading-5 text-zinc-500 sm:text-sm">
                        Sua compra foi atualizada. Voltando para a loja...
                    </div>
                </div>
            </div>

            <div class="h-1.5 w-full bg-zinc-100">
                <div id="toastProgress" class="h-full w-0 bg-gradient-to-r from-emerald-400 via-lime-400 to-yellow-400 transition-all"></div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('addToBagForm');
  const mainImage = document.getElementById('mainImage');
  const mainVideo = document.getElementById('mainVideo');
  const mainWrap = document.getElementById('mainImageWrap');
  const thumbButtons = Array.from(document.querySelectorAll('.thumb-btn'));
  const mediaList = thumbButtons.map((btn) => ({
    url: btn.getAttribute('data-full'),
    type: btn.getAttribute('data-type') || 'image'
  })).filter(m => !!m.url);

  const nomeProduto = @json($item->nome_do_produto);
  const successToast = document.getElementById('successToast');
  const toastTitle = document.getElementById('toastTitle');
  const toastProgress = document.getElementById('toastProgress');

  let currentMediaIndex = 0;
  let touchStartX = 0;
  let touchEndX = 0;
  const swipeMinDistance = 40;

  function mostrarToast() {
    if (toastTitle) {
      toastTitle.textContent = nomeProduto;
    }

    if (successToast) {
      successToast.classList.add('show');
    }

    requestAnimationFrame(() => {
      setTimeout(() => {
        if (toastProgress) {
          toastProgress.style.width = '100%';
        }
      }, 80);
    });
  }

  function dispararConfetesFortes() {
    const duracao = 6500;
    const fim = Date.now() + duracao;
    const cores = ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444', '#a855f7', '#14b8a6', '#eab308', '#fb7185'];

    confetti({
      particleCount: 220,
      spread: 150,
      startVelocity: 55,
      scalar: 1.15,
      gravity: 1.05,
      ticks: 220,
      origin: { x: 0.5, y: 0.45 },
      colors: cores
    });

    confetti({
      particleCount: 160,
      angle: 60,
      spread: 110,
      startVelocity: 52,
      scalar: 1.1,
      origin: { x: 0.05, y: 0.65 },
      colors: cores
    });

    confetti({
      particleCount: 160,
      angle: 120,
      spread: 110,
      startVelocity: 52,
      scalar: 1.1,
      origin: { x: 0.95, y: 0.65 },
      colors: cores
    });

    const intervalo = setInterval(() => {
      if (Date.now() > fim) {
        clearInterval(intervalo);
        return;
      }

      confetti({
        particleCount: 28,
        angle: 60,
        spread: 90,
        startVelocity: 42,
        scalar: 1.05,
        gravity: 1.08,
        origin: { x: 0, y: 0.75 },
        colors: cores
      });

      confetti({
        particleCount: 28,
        angle: 120,
        spread: 90,
        startVelocity: 42,
        scalar: 1.05,
        gravity: 1.08,
        origin: { x: 1, y: 0.75 },
        colors: cores
      });

      confetti({
        particleCount: 18,
        spread: 140,
        startVelocity: 36,
        scalar: 0.95,
        gravity: 1.12,
        origin: { x: Math.random(), y: 0.08 },
        colors: cores
      });
    }, 220);
  }

  async function celebrarESair(urlDestino) {
    mostrarToast();
    dispararConfetesFortes();

    setTimeout(() => {
      window.location.href = urlDestino;
    }, 6800);
  }

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
      if (i === currentMediaIndex) {
        btn.classList.add('ring-2', 'ring-zinc-900');
      } else {
        btn.classList.remove('ring-2', 'ring-zinc-900');
      }
    });
  }

  function goToNextMedia() {
    if (mediaList.length <= 1) return;
    const nextIndex = (currentMediaIndex + 1) % mediaList.length;
    setMainMediaByIndex(nextIndex);
  }

  function goToPrevMedia() {
    if (mediaList.length <= 1) return;
    const prevIndex = (currentMediaIndex - 1 + mediaList.length) % mediaList.length;
    setMainMediaByIndex(prevIndex);
  }

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const submitBtn = form.querySelector('button[type="submit"]');
      const originalText = submitBtn ? submitBtn.textContent : null;

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Adicionando...';
      }

      try {
        const resp = await fetch('/loja/adicionar-item', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
          },
          body: new FormData(form)
        });

        const data = await resp.json().catch(() => ({}));

        if (resp.ok && data.success) {
          const msg = encodeURIComponent(`Item adicionado à sacolinha: ${nomeProduto}.`);
          const destino = `{{ route('loja.index') }}?success=${msg}`;
          await celebrarESair(destino);
          return;
        }

        const errMsg = encodeURIComponent(data.message || 'Não foi possível adicionar o item.');
        window.location.href = `{{ route('loja.show', $item) }}?error=${errMsg}`;
      } catch (error) {
        console.error('Erro detalhado:', error);
        const errMsg = encodeURIComponent('Erro de conexão ao adicionar o item.');
        window.location.href = `{{ route('loja.show', $item) }}?error=${errMsg}`;
      } finally {
        if (submitBtn && !successToast.classList.contains('show')) {
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
        }
      }
    });
  }

  if (thumbButtons.length > 0) {
    thumbButtons.forEach((thumbBtn, index) => {
      thumbBtn.addEventListener('click', () => {
        setMainMediaByIndex(index);
      });
    });
  }

  if (mainImage) {
    mainImage.addEventListener('click', () => {
      goToNextMedia();
    });
  }

  if (mainWrap) {
    mainWrap.addEventListener('touchstart', (e) => {
      touchStartX = e.changedTouches[0].clientX;
    }, { passive: true });

    mainWrap.addEventListener('touchend', (e) => {
      touchEndX = e.changedTouches[0].clientX;
      const deltaX = touchEndX - touchStartX;

      if (Math.abs(deltaX) < swipeMinDistance) return;

      if (deltaX < 0) {
        goToNextMedia();
      } else {
        goToPrevMedia();
      }
    }, { passive: true });
  }

  // Inicia reprodução imediata do vídeo ao abrir a página
  if (mainVideo && !mainVideo.classList.contains('hidden')) {
    mainVideo.muted = true;
    mainVideo.play().catch(() => {});
  }
});
</script>

</body>
</html>
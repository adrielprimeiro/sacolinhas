@extends('layouts.app')

@section('title', 'Cortes para Redes Sociais')
@section('brand_icon', 'fas fa-film text-pink-500')

@section('content')
<div class="max-w-7xl mx-auto px-2 sm:px-4 py-2" x-data="socialCutsApp()">

    {{-- CABEÇALHO COM FILTROS E SELEÇÃO DE LIVE --}}
    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-gray-200 mb-5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-gray-100">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-pink-100 text-pink-600 text-sm">
                        <i class="fas fa-film"></i>
                    </span>
                    Cortes para Redes Sociais
                </h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                    Vídeos prontos para publicação rápida em Stories, Reels, TikTok e WhatsApp.
                </p>
            </div>

            <div class="flex items-center gap-2">
                @if($live)
                    <a href="{{ route('admin.lives.cortes', ['liveId' => $live->id]) }}" 
                       class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-3 py-2 rounded-xl transition shadow-xs">
                        <i class="fas fa-sliders-h text-indigo-500"></i>
                        <span>Fatiador Studio (IA)</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- LINHA DE CONTROLES: SELETOR DE LIVE + FILTROS --}}
        <div class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            
            {{-- Seletor da Live --}}
            <div class="md:col-span-4">
                <label for="live-selector" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Selecionar Live:
                </label>
                <div class="relative">
                    <select id="live-selector" 
                            onchange="changeLive(this.value)"
                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-xs sm:text-sm rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 block p-2.5 font-bold shadow-xs pr-8">
                        @forelse($lives as $l)
                            @php
                                $liveDate = $l->data_live ? \Carbon\Carbon::parse($l->data_live)->format('d/m/Y') : ($l->data ? \Carbon\Carbon::parse($l->data)->format('d/m/Y') : '');
                            @endphp
                            <option value="{{ $l->id }}" {{ $live && $live->id == $l->id ? 'selected' : '' }}>
                                Live #{{ $l->id }} {{ $liveDate ? '(' . $liveDate . ')' : '' }} - {{ \Illuminate\Support\Str::limit($l->titulo ?: ($l->theme ?: 'Sem título'), 30) }}
                            </option>
                        @empty
                            <option value="">Nenhuma live encontrada</option>
                        @endforelse
                    </select>
                </div>
            </div>

            {{-- Filtros de Status (Pills) --}}
            <div class="md:col-span-5 flex flex-wrap items-center gap-2 pt-1 md:pt-4">
                <button type="button" 
                        @click="setFilter('all')" 
                        :class="currentFilter === 'all' ? 'bg-gray-900 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-3 py-2 rounded-xl text-xs font-extrabold transition cursor-pointer flex items-center gap-1.5">
                    <span>Todos</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="currentFilter === 'all' ? 'bg-gray-700 text-white' : 'bg-gray-200 text-gray-700'">
                        {{ $stats['total'] }}
                    </span>
                </button>

                <button type="button" 
                        @click="setFilter('unsold')" 
                        :class="currentFilter === 'unsold' ? 'bg-amber-600 text-white shadow-sm' : 'bg-amber-50 text-amber-900 border border-amber-200 hover:bg-amber-100'"
                        class="px-3 py-2 rounded-xl text-xs font-extrabold transition cursor-pointer flex items-center gap-1.5">
                    <i class="fas fa-tag"></i>
                    <span>Apenas Não Vendidos</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="currentFilter === 'unsold' ? 'bg-amber-800 text-white' : 'bg-amber-200 text-amber-900'">
                        {{ $stats['unsold'] }}
                    </span>
                </button>

                <button type="button" 
                        @click="setFilter('ready')" 
                        :class="currentFilter === 'ready' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50 text-emerald-900 border border-emerald-200 hover:bg-emerald-100'"
                        class="px-3 py-2 rounded-xl text-xs font-extrabold transition cursor-pointer flex items-center gap-1.5">
                    <i class="fas fa-check-circle"></i>
                    <span>Com Vídeo Pronto</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="currentFilter === 'ready' ? 'bg-emerald-800 text-white' : 'bg-emerald-200 text-emerald-900'">
                        {{ $stats['ready'] }}
                    </span>
                </button>
            </div>

            {{-- Campo de Busca Rápida --}}
            <div class="md:col-span-3 pt-1 md:pt-4">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                        <i class="fas fa-search text-xs"></i>
                    </div>
                    <input type="text" 
                           x-model="searchTerm" 
                           placeholder="Buscar #cód, produto..." 
                           class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 block pl-8 p-2.5 font-medium shadow-xs">
                    <button type="button" 
                            x-show="searchTerm.length > 0" 
                            @click="searchTerm = ''" 
                            class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400 hover:text-gray-600 text-xs">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>

    {{-- LISTA DE CARDS --}}
    @if(!$live || $liveItems->isEmpty())
        <div class="bg-white rounded-2xl p-10 text-center border border-gray-200 shadow-sm my-6">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto text-gray-400 text-2xl mb-3">
                <i class="fas fa-film"></i>
            </div>
            <h3 class="text-base font-bold text-gray-800">Nenhum item encontrado nesta live</h3>
            <p class="text-xs text-gray-500 mt-1 max-w-md mx-auto">
                Selecione outra live no topo ou utilize o Fatiador Studio para processar os cortes.
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5 sm:gap-4">
            @foreach($liveItems as $item)
                <div class="cut-item-card bg-white rounded-2xl p-3.5 sm:p-4 shadow-sm border border-gray-200 hover:border-pink-300 hover:shadow-md transition-all flex flex-col justify-between"
                     x-show="matchesFilter({{ json_encode($item) }})"
                     x-transition>
                    
                    <div>
                        <div class="flex items-start gap-3">
                            {{-- Foto / Thumbnail do Produto --}}
                            <div class="flex-shrink-0 relative">
                                <img src="{{ $item['item_image'] }}" 
                                     alt="{{ $item['item_name'] }}" 
                                     loading="lazy"
                                     class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl object-cover border border-gray-100 shadow-xs bg-gray-50">
                                
                                {{-- Botão Play Flutuante na imagem se houver vídeo pronto --}}
                                @if($item['is_ready'] && !empty($item['video_cut_url']))
                                    <button type="button"
                                            onclick="openPreviewModal('{{ $item['video_cut_url'] }}', '{{ addslashes($item['item_name']) }}', '#{{ $item['codigo_live'] }}')"
                                            class="absolute inset-0 m-auto w-8 h-8 rounded-full bg-black/60 text-white flex items-center justify-center text-xs hover:bg-pink-600 hover:scale-110 transition shadow-md backdrop-blur-xs"
                                            title="Assistir prévia">
                                        <i class="fas fa-play ml-0.5"></i>
                                    </button>
                                @endif
                            </div>

                            {{-- Detalhes do Produto --}}
                            <div class="flex-1 min-w-0">
                                
                                {{-- Linha 1: Badges de Código e Compradora / Disponível --}}
                                <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                                    {{-- Badge #código --}}
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black bg-indigo-600 text-white shadow-xs">
                                        #{{ $item['codigo_live'] }}
                                    </span>

                                    {{-- Badge Comprador(a) / Não Vendido --}}
                                    @if($item['is_sold'])
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300 max-w-[170px] truncate" title="{{ $item['buyer_name'] }}">
                                            <i class="fas fa-user text-[10px] text-emerald-700"></i>
                                            <span class="truncate">{{ $item['buyer_name'] }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                            <i class="fas fa-tag text-[10px] text-amber-700"></i>
                                            <span>Disponível</span>
                                        </span>
                                    @endif
                                </div>

                                {{-- Linha 2: Badge Vídeo Pronto --}}
                                <div class="mb-1">
                                    @if($item['is_ready'])
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-black bg-emerald-600 text-white shadow-xs">
                                            <i class="fas fa-check-circle text-[11px]"></i>
                                            <span>Vídeo Pronto</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                                            <i class="fas fa-clock text-gray-400"></i>
                                            <span>Sem vídeo</span>
                                        </span>
                                    @endif
                                </div>

                                {{-- Linha 3: Nome do Produto --}}
                                <h3 class="text-sm sm:text-base font-bold text-gray-900 leading-tight line-clamp-2 mt-1" title="{{ $item['item_name'] }}">
                                    {{ $item['item_name'] }}
                                </h3>

                                {{-- Linha 4: Cód SKU + Preço --}}
                                <p class="text-xs sm:text-sm font-semibold text-gray-500 mt-0.5">
                                    Cód: <span class="text-gray-700 font-bold">{{ $item['item_sku'] }}</span> • <span class="text-indigo-600 font-black">R$ {{ $item['item_price'] }}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- LINHA DE AÇÃO: BOTÃO BAIXAR VÍDEO --}}
                    <div class="mt-3 pt-3 border-t border-gray-100 flex items-center gap-2">
                        @if($item['is_ready'])
                            {{-- Botão Principal de Download --}}
                            <a href="{{ route('admin.lives.cortes.download', $item['live_item_id']) }}" 
                               download="{{ $item['download_filename'] }}"
                               class="flex-1 inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-extrabold py-2.5 px-4 rounded-xl text-xs sm:text-sm shadow-sm transition">
                                <i class="fas fa-download"></i>
                                <span>Baixar Vídeo</span>
                            </a>

                            {{-- Botão de Prévia --}}
                            @if(!empty($item['video_cut_url']))
                                <button type="button" 
                                        onclick="openPreviewModal('{{ $item['video_cut_url'] }}', '{{ addslashes($item['item_name']) }}', '#{{ $item['codigo_live'] }}')"
                                        class="inline-flex items-center justify-center gap-1.5 bg-indigo-50 hover:bg-indigo-100 active:scale-95 text-indigo-700 font-bold py-2.5 px-3 rounded-xl text-xs border border-indigo-200 transition">
                                    <i class="fas fa-play"></i>
                                    <span class="hidden sm:inline">Assistir</span>
                                </button>
                            @endif
                        @else
                            <button type="button" 
                                    disabled 
                                    class="w-full inline-flex items-center justify-center gap-1.5 bg-gray-100 text-gray-400 font-semibold py-2 px-3 rounded-xl text-xs cursor-not-allowed">
                                <i class="fas fa-clock"></i>
                                <span>Corte ainda não renderizado</span>
                            </button>
                        @endif
                    </div>

                </div>
            @endforeach
        </div>
    @endif

    {{-- MODAL DE PRÉVIA DE VÍDEO --}}
    <div id="videoPreviewModal" 
         class="fixed inset-0 bg-black/80 z-50 hidden items-center justify-center p-3 sm:p-6 backdrop-blur-xs"
         onclick="if(event.target === this) closePreviewModal()">
        
        <div class="bg-gray-900 rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-gray-700 flex flex-col max-h-[90vh]">
            
            {{-- Top Header do Modal --}}
            <div class="p-3.5 sm:p-4 bg-gray-800 border-b border-gray-700 flex items-center justify-between text-white">
                <div class="flex items-center gap-2 min-w-0">
                    <span id="modalItemCode" class="bg-indigo-600 text-white font-black text-xs px-2.5 py-0.5 rounded-full"></span>
                    <h4 id="modalItemTitle" class="text-sm font-bold truncate text-gray-100"></h4>
                </div>
                <button type="button" 
                        onclick="closePreviewModal()" 
                        class="w-8 h-8 rounded-lg bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white flex items-center justify-center transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Player de Vídeo --}}
            <div class="relative bg-black flex items-center justify-center min-h-[300px] flex-1">
                <video id="modalVideoPlayer" 
                       controls 
                       playsinline 
                       autoplay 
                       class="max-h-[60vh] w-full rounded-b-none">
                    Seu navegador não suporta a reprodução deste vídeo.
                </video>
            </div>

            {{-- Footer do Modal com Botão de Download --}}
            <div class="p-3.5 bg-gray-800 border-t border-gray-700 flex items-center justify-between gap-3">
                <a id="modalDownloadBtn" 
                   href="#" 
                   download 
                   class="flex-1 inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold py-2.5 px-4 rounded-xl text-sm transition">
                    <i class="fas fa-download"></i>
                    <span>Baixar este Vídeo (.mp4)</span>
                </a>
                <button type="button" 
                        onclick="closePreviewModal()" 
                        class="px-4 py-2.5 rounded-xl text-sm font-bold bg-gray-700 hover:bg-gray-600 text-gray-200 transition">
                    Fechar
                </button>
            </div>

        </div>
    </div>

</div>

<script>
function socialCutsApp() {
    return {
        currentFilter: '{{ $filters['unsold_only'] ? 'unsold' : ($filters['ready_only'] ? 'ready' : 'all') }}',
        searchTerm: '{{ addslashes($filters['search']) }}',

        setFilter(filterName) {
            this.currentFilter = filterName;
        },

        matchesFilter(item) {
            // Filtro por Status
            if (this.currentFilter === 'unsold' && item.is_sold) {
                return false;
            }
            if (this.currentFilter === 'ready' && !item.is_ready) {
                return false;
            }

            // Filtro por Termo de Busca
            if (this.searchTerm.trim() !== '') {
                const term = this.searchTerm.toLowerCase().trim().replace(/^#/, '');
                const code = String(item.codigo_live || '').toLowerCase();
                const sku = String(item.item_sku || '').toLowerCase();
                const name = String(item.item_name || '').toLowerCase();
                const buyer = String(item.buyer_name || '').toLowerCase();

                const matched = code.includes(term) || 
                                sku.includes(term) || 
                                name.includes(term) || 
                                buyer.includes(term);
                if (!matched) return false;
            }

            return true;
        }
    };
}

function changeLive(liveId) {
    if (!liveId) return;
    const url = new URL(window.location.href);
    url.searchParams.set('live_id', liveId);
    window.location.href = url.toString();
}

function openPreviewModal(videoUrl, title, code) {
    const modal = document.getElementById('videoPreviewModal');
    const player = document.getElementById('modalVideoPlayer');
    const titleEl = document.getElementById('modalItemTitle');
    const codeEl = document.getElementById('modalItemCode');
    const downloadBtn = document.getElementById('modalDownloadBtn');

    if (!modal || !player) return;

    titleEl.textContent = title;
    codeEl.textContent = code;
    player.src = videoUrl;
    downloadBtn.href = videoUrl;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    player.play().catch(() => {});
}

function closePreviewModal() {
    const modal = document.getElementById('videoPreviewModal');
    const player = document.getElementById('modalVideoPlayer');
    if (!modal || !player) return;

    player.pause();
    player.src = '';
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Fechar com tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePreviewModal();
    }
});
</script>
@endsection

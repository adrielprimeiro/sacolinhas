@extends('layouts.app')

@section('title', 'Cortes para Redes Sociais')
@section('brand_icon', 'fas fa-film text-pink-500')

@section('content')
<div class="max-w-7xl mx-auto px-2 sm:px-4 py-2" x-data="socialCutsApp()">

    {{-- CABEÇALHO COMPACTO: APENAS OS CAMPOS DE FILTRO --}}
    <div class="bg-white rounded-2xl p-3 sm:p-4 shadow-sm border border-gray-200 mb-4" style="background-color: #ffffff;">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-2.5 sm:gap-3 items-center">
            
            {{-- Seletor da Live --}}
            <div class="md:col-span-4">
                <div class="relative">
                    <select id="live-selector" 
                            onchange="changeLive(this.value)"
                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-xs sm:text-sm rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 block p-2.5 font-bold shadow-xs pr-8 cursor-pointer"
                            style="color: #111827;">
                        @forelse($lives as $l)
                            @php
                                $liveDate = $l->data_live ? \Carbon\Carbon::parse($l->data_live)->format('d/m/Y') : ($l->data ? \Carbon\Carbon::parse($l->data)->format('d/m/Y') : '');
                            @endphp
                            <option value="{{ $l->id }}" {{ $live && $live->id == $l->id ? 'selected' : '' }}>
                                Live #{{ $l->id }} {{ $liveDate ? '(' . $liveDate . ')' : '' }} - {{ \Illuminate\Support\Str::limit($l->titulo ?: ($l->theme ?: 'Sem título'), 28) }}
                            </option>
                        @empty
                            <option value="">Nenhuma live encontrada</option>
                        @endforelse
                    </select>
                </div>
            </div>

            {{-- Filtros de Status (Pills) --}}
            <div class="md:col-span-4 flex flex-wrap items-center gap-1.5 sm:gap-2">
                <button type="button" 
                        @click="setFilter('all')" 
                        :class="currentFilter === 'all' ? 'bg-gray-900 text-white shadow-xs' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'"
                        class="px-3.5 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5">
                    <span>Todos</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="currentFilter === 'all' ? 'bg-gray-700 text-white' : 'bg-gray-200 text-gray-800'">
                        {{ $stats['total'] }}
                    </span>
                </button>

                <button type="button" 
                        @click="setFilter('unsold')" 
                        :class="currentFilter === 'unsold' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-900 border border-amber-200 hover:bg-amber-100'"
                        class="px-3.5 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5">
                    <i class="fas fa-tag text-[10px]"></i>
                    <span>Não Vendidos</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="currentFilter === 'unsold' ? 'bg-amber-800 text-white' : 'bg-amber-200 text-amber-900'">
                        {{ $stats['unsold'] }}
                    </span>
                </button>
            </div>

            {{-- Campo de Busca Rápida --}}
            <div class="md:col-span-4">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                        <i class="fas fa-search text-xs"></i>
                    </div>
                    <input type="text" 
                           x-model="searchTerm" 
                           placeholder="Buscar #cód, produto..." 
                           class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 block pl-8 p-2.5 font-medium shadow-xs"
                           style="color: #111827;">
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($liveItems as $item)
                <div class="cut-item-card bg-white rounded-2xl p-3 sm:p-3.5 shadow-sm border border-gray-200 hover:border-pink-300 hover:shadow-md transition-all cursor-pointer group"
                     x-show="matchesFilter({{ json_encode($item) }})"
                     x-transition
                     @if(!empty($item['video_cut_url']))
                         onclick="openPreviewModal('{{ $item['video_cut_url'] }}', '{{ addslashes($item['item_name']) }}', '#{{ $item['codigo_live'] }}')"
                     @endif>
                    
                    <div class="flex items-center gap-3">
                        {{-- Foto / Thumbnail do Produto --}}
                        <div class="flex-shrink-0 relative">
                            <img src="{{ $item['item_image'] }}" 
                                 alt="{{ $item['item_name'] }}" 
                                 loading="lazy"
                                 class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl object-cover border border-gray-100 shadow-xs bg-gray-50 group-hover:opacity-90 transition">
                            
                            {{-- Ícone Play Flutuante na imagem --}}
                            @if(!empty($item['video_cut_url']))
                                <div class="absolute inset-0 m-auto w-7 h-7 rounded-full bg-black/60 text-white flex items-center justify-center text-[10px] group-hover:bg-pink-600 group-hover:scale-110 transition shadow-md backdrop-blur-xs">
                                    <i class="fas fa-play ml-0.5"></i>
                                </div>
                            @endif
                        </div>

                        {{-- Detalhes do Produto --}}
                        <div class="flex-1 min-w-0">
                            {{-- Linha 1: Badges de Código e Comprador(a) --}}
                            <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-black bg-indigo-600 text-white shadow-xs" style="background-color: #4f46e5; color: #ffffff;">
                                    #{{ $item['codigo_live'] }}
                                </span>

                                @if($item['is_sold'])
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300 max-w-[150px] sm:max-w-[200px] truncate" style="background-color: #d1fae5; color: #065f46;" title="{{ $item['buyer_name'] }}">
                                        <i class="fas fa-user text-[10px] text-emerald-700"></i>
                                        <span class="truncate">{{ $item['buyer_name'] }}</span>
                                    </span>
                                @endif
                            </div>

                            {{-- Linha 2: Nome / Descrição do Produto --}}
                            <h3 class="text-sm sm:text-base font-bold text-gray-900 leading-snug line-clamp-2" title="{{ $item['item_name'] }}">
                                {{ $item['item_name'] }}
                            </h3>

                            {{-- Linha 3: Cód SKU + Preço --}}
                            <p class="text-xs sm:text-sm font-semibold text-gray-500 mt-1">
                                Cód: <span class="text-gray-800 font-bold">{{ $item['item_sku'] }}</span> • <span class="text-indigo-600 font-black">R$ {{ $item['item_price'] }}</span>
                            </p>
                        </div>

                        {{-- Botão de Download na Lateral Direita --}}
                        <div class="flex-shrink-0" onclick="event.stopPropagation()">
                            <a href="{{ route('admin.lives.cortes.download', $item['live_item_id']) }}" 
                               download="{{ $item['download_filename'] }}"
                               style="background-color: #16a34a; color: #ffffff;"
                               class="inline-flex items-center justify-center gap-1.5 bg-green-600 hover:bg-green-700 active:scale-95 text-white font-extrabold py-2 px-3 rounded-xl text-xs shadow-sm transition"
                               title="Baixar Vídeo">
                                <i class="fas fa-download"></i>
                                <span class="hidden sm:inline">Baixar</span>
                            </a>
                        </div>
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
        currentFilter: '{{ $filters['unsold_only'] ? 'unsold' : 'all' }}',
        searchTerm: '{{ addslashes($filters['search']) }}',

        setFilter(filterName) {
            this.currentFilter = filterName;
        },

        matchesFilter(item) {
            // Filtro por Status (Apenas Não Vendidos se selecionado)
            if (this.currentFilter === 'unsold' && item.is_sold) {
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

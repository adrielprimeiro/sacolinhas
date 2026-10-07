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
                     x-show="matchesFilter({{ $item['live_item_id'] }})"
                     x-transition
                     onclick="openPreviewModalById({{ $item['live_item_id'] }})">
                    
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

    {{-- MODAL DE PRÉVIA E EDIÇÃO RÁPIDA DE VÍDEO --}}
    <div id="videoPreviewModal" 
         class="fixed inset-0 bg-black/90 hidden items-center justify-center p-0 sm:p-3 md:p-4 overflow-hidden h-[100dvh]"
         style="z-index: 9999; display: none;"
         onclick="if(event.target === this) requestClosePreviewModal()">
        
        <div class="bg-gray-900 w-full sm:max-w-lg h-full sm:h-auto sm:max-h-[96dvh] sm:rounded-2xl overflow-hidden shadow-2xl border-0 sm:border sm:border-gray-700 flex flex-col justify-between"
             style="background-color: #111827;">
            
            {{-- Top Header do Modal (Fixo no topo) --}}
            <div class="shrink-0 px-3 py-2.5 sm:py-3 bg-gray-800 border-b border-gray-700 flex items-center justify-between text-white">
                <div class="flex items-center gap-2 min-w-0">
                    <span id="modalItemCode" class="bg-indigo-600 text-white font-black text-xs px-2.5 py-0.5 rounded-full shrink-0" style="background-color: #4f46e5; color: #ffffff;"></span>
                    <h4 id="modalItemTitle" class="text-xs sm:text-sm font-bold truncate text-gray-100"></h4>
                </div>
                <button type="button" 
                        onclick="requestClosePreviewModal()" 
                        class="w-8 h-8 rounded-lg bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white flex items-center justify-center transition cursor-pointer shrink-0">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Área do Vídeo (Ocupa o espaço livre restante e se auto-ajusta ao aspect-ratio do aparelho) --}}
            <div class="flex-1 min-h-0 relative bg-black flex items-center justify-center overflow-hidden">
                <video id="modalVideoPlayer" 
                       playsinline 
                       class="w-full h-full max-h-full max-w-full object-contain cursor-pointer"
                       onclick="toggleModalPlayPause()">
                    Seu navegador não suporta a reprodução deste vídeo.
                </video>

                {{-- Overlay de Processando / Recortando FFmpeg --}}
                <div id="modalSavingOverlay" class="absolute inset-0 bg-black/85 hidden flex-col items-center justify-center text-white z-20 p-4 text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-pink-500 mb-3"></i>
                    <h5 class="text-sm font-bold text-white">Salvando e Recortando Vídeo...</h5>
                    <p class="text-xs text-gray-300 mt-1 max-w-xs">Severino FFmpeg está gerando o novo corte com a minutagem ajustada.</p>
                </div>
            </div>

            {{-- Toolbar de Controles & Edição de Minutagem (Fixa na parte inferior) --}}
            <div class="shrink-0 p-3 sm:p-4 bg-gray-850 border-t border-gray-800 space-y-2.5" style="background-color: #1a202c;">
                
                {{-- Linha Superior: Botão [⏺ Início], Duração/Status e Botão [Fim ⏺] --}}
                <div class="flex items-center justify-between gap-2">
                    {{-- Botão Gravar Ponto Início --}}
                    <button type="button" 
                            onclick="setCutPointHere('start')"
                            class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-extrabold text-xs flex items-center gap-1.5 transition shadow-xs cursor-pointer border border-indigo-400"
                            style="background-color: #4f46e5; color: #ffffff;"
                            title="Gravar posição atual da reprodução como início do corte">
                        <i class="fas fa-dot-circle text-red-400 animate-pulse text-[10px]"></i>
                        <span>Início</span>
                    </button>

                    {{-- Duração Central e Badge Editado (Alto Contraste) --}}
                    <div class="flex items-center gap-1.5">
                        <span id="badgeDurationSec" class="text-xs font-mono font-bold text-gray-200 bg-gray-800 border border-gray-700 px-2.5 py-0.5 rounded-lg">
                            0s
                        </span>
                        <span id="badgeEditedFlag" class="hidden items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black animate-pulse" style="background-color: #fef3c7; color: #92400e !important; border: 1px solid #fde68a;">
                            <i class="fas fa-pen text-[9px] text-amber-700"></i>
                            <span style="color: #92400e !important;">Editado</span>
                        </span>
                    </div>

                    {{-- Botão Gravar Ponto Fim --}}
                    <button type="button" 
                            onclick="setCutPointHere('end')"
                            class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-extrabold text-xs flex items-center gap-1.5 transition shadow-xs cursor-pointer border border-indigo-400"
                            style="background-color: #4f46e5; color: #ffffff;"
                            title="Gravar posição atual da reprodução como fim do corte">
                        <span>Fim</span>
                        <i class="fas fa-dot-circle text-red-400 animate-pulse text-[10px]"></i>
                    </button>
                </div>

                {{-- Barra Visual de Cortes Multi-Cores com Marcador Playhead --}}
                <div class="space-y-1">
                    <div class="relative w-full h-4 bg-gray-800 rounded-lg overflow-hidden flex cursor-pointer border border-gray-700 select-none shadow-inner"
                         id="visualTimelineTrack"
                         onclick="onVisualTrackClick(event)"
                         title="Clique para navegar no corte">
                        
                        {{-- Segmento Início Adicionado (Âmbar/Amarelo) --}}
                        <div id="trackSegAddedStart" 
                             class="h-full bg-amber-500 flex items-center justify-center text-[9px] font-black text-amber-950 transition-all duration-200" 
                             style="width: 0%;" 
                             title="Trecho adicionado ao início (+10s)">
                        </div>

                        {{-- Segmento Corte Original (Índigo/Roxo) --}}
                        <div id="trackSegOriginal" 
                             class="h-full bg-indigo-600 flex items-center justify-center text-[9px] font-black text-white transition-all duration-200" 
                             style="width: 100%;" 
                             title="Trecho original do corte">
                        </div>

                        {{-- Segmento Fim Adicionado (Âmbar/Amarelo) --}}
                        <div id="trackSegAddedEnd" 
                             class="h-full bg-amber-500 flex items-center justify-center text-[9px] font-black text-amber-950 transition-all duration-200" 
                             style="width: 0%;" 
                             title="Trecho adicionado ao final (+10s)">
                        </div>

                        {{-- Agulha de Reprodução (Playhead) --}}
                        <div id="trackPlayhead" 
                             class="absolute top-0 bottom-0 w-1 bg-white shadow-lg pointer-events-none transform -translate-x-1/2 transition-none z-10" 
                             style="left: 0%;">
                            <div class="w-2.5 h-2.5 bg-pink-500 rounded-full border-2 border-white -mt-0.5 -ml-0.75 shadow"></div>
                        </div>
                    </div>

                    {{-- Minutagem Direta na Barra (Início, Tempo Atual e Fim) --}}
                    <div class="flex items-center justify-between text-[11px] font-mono text-gray-300 px-0.5 font-bold">
                        <span id="barStartMinutagem" class="text-indigo-400 font-extrabold">00:00</span>
                        <div class="flex items-center gap-1 font-semibold text-gray-400">
                            <span id="modalCurrentTimeText" class="text-white font-black">00:00</span>
                            <span>/</span>
                            <span id="modalTotalTimeText" class="text-gray-300">00:00</span>
                        </div>
                        <span id="barEndMinutagem" class="text-indigo-400 font-extrabold">00:00</span>
                    </div>
                </div>

                {{-- Botões Principais: -10s | Play/Pause | +10s --}}
                <div class="flex items-center justify-center gap-6 sm:gap-8 py-0.5">
                    {{-- Botão -10s --}}
                    <button type="button" 
                            onclick="jumpMinus10()" 
                            id="btnMinus10"
                            class="group px-3.5 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-200 border border-gray-600 font-extrabold text-xs transition active:scale-95 flex items-center gap-1.5 cursor-pointer shadow-xs"
                            title="Voltar 10s (ou expande 10s no início se estiver no começo)">
                        <i class="fas fa-backward text-pink-400 group-hover:scale-110 transition"></i>
                        <span>-10s</span>
                    </button>

                    {{-- Botão Central Play / Pause --}}
                    <button type="button" 
                            onclick="toggleModalPlayPause()" 
                            id="modalPlayPauseBtn" 
                            class="w-11 h-11 rounded-full bg-pink-600 hover:bg-pink-500 active:scale-90 text-white flex items-center justify-center text-sm shadow-lg transition cursor-pointer">
                        <i class="fas fa-play ml-0.5" id="modalPlayPauseIcon"></i>
                    </button>

                    {{-- Botão +10s --}}
                    <button type="button" 
                            onclick="jumpPlus10()" 
                            id="btnPlus10"
                            class="group px-3.5 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-200 border border-gray-600 font-extrabold text-xs transition active:scale-95 flex items-center gap-1.5 cursor-pointer shadow-xs"
                            title="Avançar 10s (ou expande 10s no fim se estiver no final)">
                        <span>+10s</span>
                        <i class="fas fa-forward text-pink-400 group-hover:scale-110 transition"></i>
                    </button>
                </div>

                {{-- Toast Informativo de Ação --}}
                <div id="modalToastBox" class="hidden text-[11px] font-bold text-center py-1 px-2.5 rounded-xl bg-indigo-900/90 text-indigo-100 border border-indigo-500 shadow-sm transition"></div>

            </div>

        </div>
    </div>

    {{-- MODAL DE CONFIRMAÇÃO PARA SALVAR AO FECHAR --}}
    <div id="saveConfirmModal" 
         class="fixed inset-0 bg-black/85 hidden items-center justify-center p-4 backdrop-blur-sm"
         style="z-index: 10001; display: none;"
         onclick="if(event.target === this) hideConfirmModal()">
        
        <div class="bg-gray-900 rounded-2xl max-w-md w-full overflow-hidden shadow-2xl border border-gray-700 p-5 text-white animate-scale-in">
            
            <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center text-xl mx-auto mb-3">
                <i class="fas fa-sliders-h"></i>
            </div>

            <h3 class="text-base font-black text-center text-gray-100">Salvar Nova Minutagem?</h3>
            <p class="text-xs text-center text-gray-300 mt-1 mb-4">
                Você fez ajustes no tempo deste corte. Deseja salvar a nova configuração e recortar o vídeo agora?
            </p>

            {{-- Resumo da Alteração --}}
            <div class="bg-gray-800 rounded-xl p-3 border border-gray-700 space-y-2 mb-5 font-mono text-xs">
                <div class="flex items-center justify-between text-gray-300">
                    <span>Início do Corte:</span>
                    <span class="font-bold text-white"><span id="confirmStartOld" class="text-gray-400 line-through mr-1">00:00</span> ➔ <span id="confirmStartNew" class="text-emerald-400 font-black">00:00</span></span>
                </div>
                <div class="flex items-center justify-between text-gray-300">
                    <span>Fim do Corte:</span>
                    <span class="font-bold text-white"><span id="confirmEndOld" class="text-gray-400 line-through mr-1">00:00</span> ➔ <span id="confirmEndNew" class="text-emerald-400 font-black">00:00</span></span>
                </div>
                <div class="flex items-center justify-between text-gray-300 pt-1.5 border-t border-gray-700">
                    <span>Duração:</span>
                    <span class="font-bold text-pink-400" id="confirmDurationNew">0s</span>
                </div>
            </div>

            {{-- Botões de Decisão --}}
            <div class="flex flex-col gap-2">
                <button type="button" 
                        onclick="confirmAndSaveCut()" 
                        class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs sm:text-sm shadow-md transition flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fas fa-check-circle"></i>
                    <span>Salvar e Recortar Vídeo</span>
                </button>

                <div class="grid grid-cols-2 gap-2 mt-1">
                    <button type="button" 
                            onclick="discardAndCloseModal()" 
                            class="py-2 px-3 rounded-xl bg-gray-800 hover:bg-red-900/60 text-gray-300 hover:text-red-200 border border-gray-700 text-xs font-bold transition cursor-pointer">
                        Descartar Alterações
                    </button>

                    <button type="button" 
                            onclick="hideConfirmModal()" 
                            class="py-2 px-3 rounded-xl bg-gray-700 hover:bg-gray-600 text-gray-200 text-xs font-bold transition cursor-pointer">
                        Continuar Editando
                    </button>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
// Mapa Global de Itens indexado por live_item_id
const liveItemsMap = {!! json_encode($liveItems->keyBy('live_item_id')) !!};
const liveGlobalRecordingUrl = '{{ $recordingUrl ?: '' }}';
const csrfToken = '{{ csrf_token() }}';

let currentItem = null;
let activeStart = 0;
let activeEnd = 0;
let originalStart = 0;
let originalEnd = 0;
let isUsingRecording = false;
let toastTimeout = null;

function socialCutsApp() {
    return {
        currentFilter: '{{ $filters['unsold_only'] ? 'unsold' : 'all' }}',
        searchTerm: '{{ addslashes($filters['search']) }}',

        setFilter(filterName) {
            this.currentFilter = filterName;
        },

        matchesFilter(itemIdOrObject) {
            const item = typeof itemIdOrObject === 'object' ? itemIdOrObject : liveItemsMap[itemIdOrObject];
            if (!item) return true;

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

function openPreviewModalById(liveItemId) {
    const item = liveItemsMap[liveItemId];
    if (!item) {
        console.warn('Item não encontrado:', liveItemId);
        return;
    }
    openPreviewModal(item);
}
window.openPreviewModalById = openPreviewModalById;

function changeLive(liveId) {
    if (!liveId) return;
    const url = new URL(window.location.href);
    url.searchParams.set('live_id', liveId);
    window.location.href = url.toString();
}

function formatTime(sec) {
    if (sec === null || isNaN(sec)) return '00:00';
    sec = Math.max(0, Math.floor(sec));
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    const h = Math.floor(m / 60);
    const remM = m % 60;
    if (h > 0) {
        return `${String(h).padStart(2, '0')}:${String(remM).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
}

function openPreviewModal(item) {
    if (!item) return;
    currentItem = item;
    const modal = document.getElementById('videoPreviewModal');
    const player = document.getElementById('modalVideoPlayer');
    const titleEl = document.getElementById('modalItemTitle');
    const codeEl = document.getElementById('modalItemCode');

    if (!modal || !player) {
        console.error('Modal ou Player de vídeo não encontrados no DOM.');
        return;
    }

    activeStart = Number(item.cut_start_sec || 0);
    activeEnd = Number(item.cut_end_sec || 0);
    originalStart = activeStart;
    originalEnd = activeEnd;

    // Se temos a gravação completa da live, usamos ela para permitir expansão contínua
    if (liveGlobalRecordingUrl && liveGlobalRecordingUrl.trim() !== '') {
        isUsingRecording = true;
        player.src = liveGlobalRecordingUrl;
    } else {
        isUsingRecording = false;
        player.src = item.video_cut_url || '';
    }

    if (titleEl) titleEl.textContent = item.item_name || 'Produto';
    if (codeEl) codeEl.textContent = '#' + (item.codigo_live || item.item_sku || '');

    renderTimeBadges();
    updatePlayIcon(false);

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.style.display = 'flex';

    player.onloadedmetadata = function() {
        if (isUsingRecording && activeStart > 0) {
            player.currentTime = activeStart;
        } else {
            player.currentTime = 0;
        }
        player.play().then(() => updatePlayIcon(true)).catch(() => updatePlayIcon(false));
    };

    player.ontimeupdate = function() {
        const currentText = document.getElementById('modalCurrentTimeText');
        const totalText = document.getElementById('modalTotalTimeText');
        const playhead = document.getElementById('trackPlayhead');

        let currentOffset = 0;
        let totalSpan = Math.max(0.1, activeEnd - activeStart);

        if (isUsingRecording) {
            currentOffset = Math.max(0, player.currentTime - activeStart);

            if (player.currentTime >= activeEnd) {
                player.pause();
                player.currentTime = activeEnd;
                updatePlayIcon(false);
            }

            if (currentText) currentText.textContent = formatTime(currentOffset);
            if (totalText) totalText.textContent = formatTime(totalSpan);
        } else {
            currentOffset = player.currentTime;
            totalSpan = player.duration || totalSpan;

            if (currentText) currentText.textContent = formatTime(player.currentTime);
            if (totalText) totalText.textContent = formatTime(totalSpan);
        }

        const progressPct = Math.min(100, Math.max(0, (currentOffset / totalSpan) * 100));
        if (playhead) {
            playhead.style.left = `${progressPct}%`;
        }
    };
}

function setCutPointHere(type) {
    const player = document.getElementById('modalVideoPlayer');
    if (!player) return;

    let currentSec = 0;
    if (isUsingRecording) {
        currentSec = player.currentTime;
    } else {
        currentSec = originalStart + player.currentTime;
    }

    if (type === 'start') {
        if (currentSec >= activeEnd) {
            showToast('⚠️ O início não pode ser maior que o fim!');
            return;
        }
        activeStart = Math.max(0, Math.round(currentSec * 10) / 10);
        showToast(`📍 Início gravado: ${formatTime(activeStart)}`);
    } else if (type === 'end') {
        if (currentSec <= activeStart) {
            showToast('⚠️ O fim não pode ser menor que o início!');
            return;
        }
        activeEnd = Math.round(currentSec * 10) / 10;
        showToast(`📍 Fim gravado: ${formatTime(activeEnd)}`);
    }

    renderTimeBadges();
}

function renderTimeBadges() {
    const barStartMinutagem = document.getElementById('barStartMinutagem');
    const barEndMinutagem = document.getElementById('barEndMinutagem');
    const durSecEl = document.getElementById('badgeDurationSec');
    const editedFlag = document.getElementById('badgeEditedFlag');

    const duration = Math.max(0, Math.round(activeEnd - activeStart));

    if (barStartMinutagem) barStartMinutagem.textContent = formatTime(activeStart);
    if (barEndMinutagem) barEndMinutagem.textContent = formatTime(activeEnd);
    if (durSecEl) durSecEl.textContent = `${duration}s`;

    const hasChanged = (activeStart !== originalStart || activeEnd !== originalEnd);

    if (hasChanged) {
        if (editedFlag) editedFlag.classList.remove('hidden');
    } else {
        if (editedFlag) editedFlag.classList.add('hidden');
    }

    updateVisualTimeline();
}

function updateVisualTimeline() {
    const segAddedStart = document.getElementById('trackSegAddedStart');
    const segOriginal = document.getElementById('trackSegOriginal');
    const segAddedEnd = document.getElementById('trackSegAddedEnd');

    const totalSpan = Math.max(0.1, activeEnd - activeStart);

    // 1. Início adicionado (se activeStart < originalStart)
    const addedStartSec = Math.max(0, originalStart - activeStart);
    const addedStartPct = (addedStartSec / totalSpan) * 100;

    // 2. Fim adicionado (se activeEnd > originalEnd)
    const addedEndSec = Math.max(0, activeEnd - originalEnd);
    const addedEndPct = (addedEndSec / totalSpan) * 100;

    // 3. Corte original dentro de [activeStart, activeEnd]
    const origLeft = Math.max(activeStart, originalStart);
    const origRight = Math.min(activeEnd, originalEnd);
    const origSec = Math.max(0, origRight - origLeft);
    const origPct = Math.max(0, (origSec / totalSpan) * 100);

    if (segAddedStart) {
        segAddedStart.style.width = `${addedStartPct}%`;
        segAddedStart.textContent = addedStartPct > 12 ? `+${Math.round(addedStartSec)}s` : '';
    }
    if (segOriginal) {
        segOriginal.style.width = `${origPct}%`;
    }
    if (segAddedEnd) {
        segAddedEnd.style.width = `${addedEndPct}%`;
        segAddedEnd.textContent = addedEndPct > 12 ? `+${Math.round(addedEndSec)}s` : '';
    }
}

function onVisualTrackClick(e) {
    const track = document.getElementById('visualTimelineTrack');
    const player = document.getElementById('modalVideoPlayer');
    if (!track || !player) return;

    const rect = track.getBoundingClientRect();
    const clickX = e.clientX - rect.left;
    const pct = Math.min(1, Math.max(0, clickX / rect.width));

    const totalSpan = Math.max(1, activeEnd - activeStart);
    if (isUsingRecording) {
        player.currentTime = activeStart + (totalSpan * pct);
    } else {
        const dur = player.duration || totalSpan;
        player.currentTime = dur * pct;
    }
}

function showToast(msg) {
    const toast = document.getElementById('modalToastBox');
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.remove('hidden');
    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
        toast.classList.add('hidden');
    }, 3500);
}

function toggleModalPlayPause() {
    const player = document.getElementById('modalVideoPlayer');
    if (!player) return;

    if (player.paused || player.ended) {
        if (isUsingRecording && player.currentTime >= activeEnd) {
            player.currentTime = activeStart;
        }
        player.play().then(() => updatePlayIcon(true)).catch(() => {});
    } else {
        player.pause();
        updatePlayIcon(false);
    }
}

function updatePlayIcon(isPlaying) {
    const icon = document.getElementById('modalPlayPauseIcon');
    if (!icon) return;
    if (isPlaying) {
        icon.className = 'fas fa-pause';
    } else {
        icon.className = 'fas fa-play ml-0.5';
    }
}

function jumpMinus10() {
    const player = document.getElementById('modalVideoPlayer');
    if (!player) return;

    const isAtStart = isUsingRecording 
        ? (player.currentTime <= activeStart + 1.2 || player.currentTime <= activeStart)
        : (player.currentTime <= 1.2 || player.currentTime === 0);

    if (isAtStart) {
        // Inserir 10s no início
        activeStart = Math.max(0, activeStart - 10);
        if (isUsingRecording) {
            player.currentTime = activeStart;
            player.play().then(() => updatePlayIcon(true)).catch(() => {});
        }
        showToast(`✨ +10s adicionados ao início do corte! (${formatTime(activeStart)})`);
        renderTimeBadges();
    } else {
        // Pular 10s para trás
        if (isUsingRecording) {
            player.currentTime = Math.max(activeStart, player.currentTime - 10);
        } else {
            player.currentTime = Math.max(0, player.currentTime - 10);
        }
    }
}

function jumpPlus10() {
    const player = document.getElementById('modalVideoPlayer');
    if (!player) return;

    const isAtEnd = isUsingRecording 
        ? (player.currentTime >= activeEnd - 1.2 || (player.paused && player.currentTime >= activeEnd - 2.0))
        : (player.currentTime >= (player.duration - 1.2) || player.ended);

    if (isAtEnd) {
        // Inserir 10s no fim
        activeEnd = activeEnd + 10;
        if (isUsingRecording) {
            player.play().then(() => updatePlayIcon(true)).catch(() => {});
        }
        showToast(`✨ +10s adicionados ao fim do corte! (${formatTime(activeEnd)})`);
        renderTimeBadges();
    } else {
        // Pular 10s para frente
        if (isUsingRecording) {
            player.currentTime = Math.min(activeEnd, player.currentTime + 10);
        } else {
            player.currentTime = Math.min(player.duration || activeEnd, player.currentTime + 10);
        }
    }
}

function onScrubberInput(percentVal) {
    const player = document.getElementById('modalVideoPlayer');
    if (!player) return;

    const pct = parseFloat(percentVal) / 100;
    if (isUsingRecording) {
        const rangeDuration = Math.max(1, activeEnd - activeStart);
        player.currentTime = activeStart + (rangeDuration * pct);
    } else {
        const dur = player.duration || (activeEnd - activeStart);
        player.currentTime = dur * pct;
    }
}

function requestClosePreviewModal() {
    const hasChanged = (activeStart !== originalStart || activeEnd !== originalEnd);
    if (hasChanged) {
        showConfirmModal();
    } else {
        forceClosePreviewModal();
    }
}

function showConfirmModal() {
    const confirmModal = document.getElementById('saveConfirmModal');
    if (!confirmModal) return;

    const startOld = document.getElementById('confirmStartOld');
    const startNew = document.getElementById('confirmStartNew');
    const endOld = document.getElementById('confirmEndOld');
    const endNew = document.getElementById('confirmEndNew');
    const durNew = document.getElementById('confirmDurationNew');

    if (startOld) startOld.textContent = formatTime(originalStart);
    if (startNew) startNew.textContent = formatTime(activeStart);
    if (endOld) endOld.textContent = formatTime(originalEnd);
    if (endNew) endNew.textContent = formatTime(activeEnd);
    if (durNew) durNew.textContent = `${Math.round(activeEnd - activeStart)} segundos`;

    confirmModal.classList.remove('hidden');
    confirmModal.classList.add('flex');
    confirmModal.style.display = 'flex';
}

function hideConfirmModal() {
    const confirmModal = document.getElementById('saveConfirmModal');
    if (!confirmModal) return;
    confirmModal.classList.add('hidden');
    confirmModal.classList.remove('flex');
    confirmModal.style.display = 'none';
}

function discardAndCloseModal() {
    hideConfirmModal();
    forceClosePreviewModal();
}

function forceClosePreviewModal() {
    const modal = document.getElementById('videoPreviewModal');
    const player = document.getElementById('modalVideoPlayer');
    if (player) {
        try {
            player.pause();
            player.removeAttribute('src');
            player.load();
        } catch(e) {}
    }
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.style.display = 'none';
    }
    currentItem = null;
}

function confirmAndSaveCut() {
    hideConfirmModal();
    saveAndReCutItem();
}

window.requestClosePreviewModal = requestClosePreviewModal;
window.showConfirmModal = showConfirmModal;
window.hideConfirmModal = hideConfirmModal;
window.discardAndCloseModal = discardAndCloseModal;
window.forceClosePreviewModal = forceClosePreviewModal;
window.confirmAndSaveCut = confirmAndSaveCut;
window.setCutPointHere = setCutPointHere;
window.toggleModalPlayPause = toggleModalPlayPause;
window.jumpMinus10 = jumpMinus10;
window.jumpPlus10 = jumpPlus10;
window.onVisualTrackClick = onVisualTrackClick;

async function saveAndReCutItem() {
    if (!currentItem) return;

    const overlay = document.getElementById('modalSavingOverlay');
    if (overlay) {
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
    }

    const liveId = currentItem.live_id || '{{ $live ? $live->id : 0 }}';
    const liveItemId = currentItem.live_item_id;

    try {
        // 1. Salvar novos Timestamps
        const saveResp = await fetch(`/admin/lives/${liveId}/cortes/save-timestamp/${liveItemId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                cut_start_sec: activeStart,
                cut_end_sec: activeEnd
            })
        });
        const saveJson = await saveResp.json();
        if (!saveJson.success) {
            throw new Error(saveJson.message || 'Erro ao salvar novo timestamp.');
        }

        // 2. Re-gerar o corte de vídeo no FFmpeg
        const cutResp = await fetch(`/admin/lives/${liveId}/cortes/generate-single/${liveItemId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });
        const cutJson = await cutResp.json();
        if (!cutJson.success) {
            throw new Error(cutJson.message || 'Erro ao gerar novo corte de vídeo.');
        }

        // 3. Atualizar dados do item local
        currentItem.video_cut_url = cutJson.video_cut_url;
        currentItem.cut_start_sec = activeStart;
        currentItem.cut_end_sec = activeEnd;
        originalStart = activeStart;
        originalEnd = activeEnd;

        renderTimeBadges();
        showToast('✅ Corte atualizado e recortado com sucesso!');

        setTimeout(() => {
            if (overlay) {
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
            }
            forceClosePreviewModal();
        }, 800);

    } catch (err) {
        alert('Erro ao salvar corte: ' + err.message);
        if (overlay) {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
        }
    }
}

// Fechar com tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const confirmModal = document.getElementById('saveConfirmModal');
        if (confirmModal && !confirmModal.classList.contains('hidden')) {
            hideConfirmModal();
        } else {
            requestClosePreviewModal();
        }
    }
});
</script>
@endsection

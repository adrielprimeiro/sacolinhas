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
                         onclick="openPreviewModal({{ json_encode($item) }})"
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

    {{-- MODAL DE PRÉVIA E EDIÇÃO RÁPIDA DE VÍDEO --}}
    <div id="videoPreviewModal" 
         class="fixed inset-0 bg-black/80 z-50 hidden items-center justify-center p-2 sm:p-4 backdrop-blur-xs"
         onclick="if(event.target === this) requestClosePreviewModal()">
        
        <div class="bg-gray-900 rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-gray-700 flex flex-col max-h-[95vh]">
            
            {{-- Top Header do Modal --}}
            <div class="p-3 sm:p-3.5 bg-gray-800 border-b border-gray-700 flex items-center justify-between text-white">
                <div class="flex items-center gap-2 min-w-0">
                    <span id="modalItemCode" class="bg-indigo-600 text-white font-black text-xs px-2.5 py-0.5 rounded-full shrink-0"></span>
                    <h4 id="modalItemTitle" class="text-xs sm:text-sm font-bold truncate text-gray-100"></h4>
                </div>
                <button type="button" 
                        onclick="requestClosePreviewModal()" 
                        class="w-8 h-8 rounded-lg bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white flex items-center justify-center transition cursor-pointer shrink-0">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Player de Vídeo com Overlay de Carregamento / Salvando --}}
            <div class="relative bg-black flex items-center justify-center min-h-[260px] sm:min-h-[320px] max-h-[50vh] flex-1 overflow-hidden">
                <video id="modalVideoPlayer" 
                       playsinline 
                       class="max-h-[50vh] w-full object-contain cursor-pointer"
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

            {{-- Toolbar de Controles & Edição de Minutagem --}}
            <div class="p-3 sm:p-4 bg-gray-850 border-t border-gray-700 space-y-3" style="background-color: #1a202c;">
                
                {{-- Badges Informativas dos Timestamps do Corte --}}
                <div class="flex flex-wrap items-center justify-between gap-1.5 text-xs">
                    <div class="flex items-center gap-1.5">
                        {{-- Badge Início --}}
                        <div id="badgeStartBox" class="px-2.5 py-1 rounded-lg bg-gray-800 border border-gray-700 text-gray-200 font-mono text-[11px] font-bold flex items-center gap-1">
                            <span class="text-gray-400">Início:</span>
                            <span id="badgeStartSec" class="text-indigo-400 font-black">00:00</span>
                        </div>

                        {{-- Badge Fim --}}
                        <div id="badgeEndBox" class="px-2.5 py-1 rounded-lg bg-gray-800 border border-gray-700 text-gray-200 font-mono text-[11px] font-bold flex items-center gap-1">
                            <span class="text-gray-400">Fim:</span>
                            <span id="badgeEndSec" class="text-indigo-400 font-black">00:00</span>
                        </div>

                        {{-- Duração --}}
                        <div class="px-2 py-1 rounded-lg bg-gray-800/60 text-gray-400 text-[11px] font-semibold hidden sm:inline-flex">
                            <span id="badgeDurationSec">0s</span>
                        </div>
                    </div>

                    {{-- Indicador de Edição Pendente --}}
                    <div id="badgeEditedFlag" class="hidden items-center gap-1 px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40 text-[10px] font-black animate-pulse">
                        <i class="fas fa-pen text-[9px]"></i>
                        <span>Editado</span>
                    </div>
                </div>

                {{-- Barra de Progresso / Scrubber --}}
                <div class="space-y-1">
                    <div class="relative flex items-center">
                        <input type="range" 
                               id="modalTimeScrubber" 
                               min="0" 
                               max="100" 
                               value="0" 
                               step="0.1" 
                               oninput="onScrubberInput(this.value)"
                               class="w-full h-1.5 bg-gray-700 rounded-lg appearance-none cursor-pointer accent-pink-500">
                    </div>
                    <div class="flex justify-between text-[10px] font-mono text-gray-400 px-0.5">
                        <span id="modalCurrentTimeText">00:00</span>
                        <span id="modalTotalTimeText">00:00</span>
                    </div>
                </div>

                {{-- Botões Principais: -10s | Play/Pause | +10s --}}
                <div class="flex items-center justify-center gap-4 pt-1">
                    {{-- Botão -10s --}}
                    <button type="button" 
                            onclick="jumpMinus10()" 
                            id="btnMinus10"
                            class="group px-3 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-200 border border-gray-600 font-extrabold text-xs transition active:scale-95 flex items-center gap-1.5 cursor-pointer shadow-xs"
                            title="Voltar 10s no vídeo (ou insere 10s no início se estiver no começo)">
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
                            class="group px-3 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-200 border border-gray-600 font-extrabold text-xs transition active:scale-95 flex items-center gap-1.5 cursor-pointer shadow-xs"
                            title="Avançar 10s no vídeo (ou insere 10s no fim se estiver no final)">
                        <span>+10s</span>
                        <i class="fas fa-forward text-pink-400 group-hover:scale-110 transition"></i>
                    </button>
                </div>

                {{-- Toast Informativo de Ação --}}
                <div id="modalToastBox" class="hidden text-[11px] font-bold text-center py-1.5 px-3 rounded-xl bg-indigo-900/90 text-indigo-100 border border-indigo-500 shadow-sm transition"></div>

            </div>

            {{-- Footer do Modal com Botões de Ação --}}
            <div class="p-3 sm:p-3.5 bg-gray-800 border-t border-gray-700 flex items-center justify-between gap-2.5">
                
                {{-- Botão Download --}}
                <a id="modalDownloadBtn" 
                   href="#" 
                   download 
                   class="flex-1 inline-flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold py-2.5 px-3 rounded-xl text-xs sm:text-sm transition shadow-sm">
                    <i class="fas fa-download"></i>
                    <span>Baixar Vídeo</span>
                </a>

                {{-- Botão Salvar Edição (visível quando modificado) --}}
                <button type="button" 
                        id="modalSaveDirectBtn"
                        onclick="saveAndReCutItem()"
                        class="hidden inline-flex items-center justify-center gap-1.5 bg-pink-600 hover:bg-pink-700 text-white font-extrabold py-2.5 px-3.5 rounded-xl text-xs sm:text-sm transition shadow-sm">
                    <i class="fas fa-save"></i>
                    <span>Salvar Corte</span>
                </button>

                {{-- Botão Fechar --}}
                <button type="button" 
                        onclick="requestClosePreviewModal()" 
                        class="px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-gray-700 hover:bg-gray-600 text-gray-200 transition cursor-pointer">
                    Fechar
                </button>
            </div>

        </div>
    </div>

    {{-- MODAL DE CONFIRMAÇÃO PARA SALVAR AO FECHAR --}}
    <div id="saveConfirmModal" 
         class="fixed inset-0 bg-black/85 z-60 hidden items-center justify-center p-4 backdrop-blur-sm"
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
// Estado Global do Editor de Cortes
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
    currentItem = item;
    const modal = document.getElementById('videoPreviewModal');
    const player = document.getElementById('modalVideoPlayer');
    const titleEl = document.getElementById('modalItemTitle');
    const codeEl = document.getElementById('modalItemCode');
    const downloadBtn = document.getElementById('modalDownloadBtn');

    if (!modal || !player) return;

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
        player.src = item.video_cut_url;
    }

    titleEl.textContent = item.item_name || 'Produto';
    codeEl.textContent = '#' + (item.codigo_live || item.item_sku || '');
    downloadBtn.href = item.video_cut_url || '#';
    downloadBtn.setAttribute('download', item.download_filename || 'video_corte.mp4');

    renderTimeBadges();
    updatePlayIcon(false);

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    player.onloadedmetadata = function() {
        if (isUsingRecording && activeStart > 0) {
            player.currentTime = activeStart;
        } else {
            player.currentTime = 0;
        }
        player.play().then(() => updatePlayIcon(true)).catch(() => updatePlayIcon(false));
    };

    player.ontimeupdate = function() {
        const scrubber = document.getElementById('modalTimeScrubber');
        const currentText = document.getElementById('modalCurrentTimeText');
        const totalText = document.getElementById('modalTotalTimeText');

        if (isUsingRecording) {
            const rangeDuration = Math.max(1, activeEnd - activeStart);
            const currentOffset = Math.max(0, player.currentTime - activeStart);

            if (player.currentTime >= activeEnd) {
                player.pause();
                player.currentTime = activeEnd;
                updatePlayIcon(false);
            }

            if (scrubber) {
                scrubber.value = Math.min(100, (currentOffset / rangeDuration) * 100);
            }
            if (currentText) currentText.textContent = formatTime(currentOffset);
            if (totalText) totalText.textContent = formatTime(rangeDuration);
        } else {
            const dur = player.duration || (activeEnd - activeStart) || 1;
            if (scrubber) {
                scrubber.value = Math.min(100, (player.currentTime / dur) * 100);
            }
            if (currentText) currentText.textContent = formatTime(player.currentTime);
            if (totalText) totalText.textContent = formatTime(dur);
        }
    };
}

function renderTimeBadges() {
    const startSecEl = document.getElementById('badgeStartSec');
    const endSecEl = document.getElementById('badgeEndSec');
    const durSecEl = document.getElementById('badgeDurationSec');
    const editedFlag = document.getElementById('badgeEditedFlag');
    const directSaveBtn = document.getElementById('modalSaveDirectBtn');
    const startBox = document.getElementById('badgeStartBox');
    const endBox = document.getElementById('badgeEndBox');

    const duration = Math.max(0, Math.round(activeEnd - activeStart));

    if (startSecEl) startSecEl.textContent = formatTime(activeStart);
    if (endSecEl) endSecEl.textContent = formatTime(activeEnd);
    if (durSecEl) durSecEl.textContent = `${duration}s`;

    const hasChanged = (activeStart !== originalStart || activeEnd !== originalEnd);

    if (hasChanged) {
        if (editedFlag) editedFlag.classList.remove('hidden');
        if (directSaveBtn) directSaveBtn.classList.remove('hidden');
        if (startBox) {
            startBox.classList.toggle('border-amber-500', activeStart !== originalStart);
            startBox.classList.toggle('bg-amber-950/40', activeStart !== originalStart);
        }
        if (endBox) {
            endBox.classList.toggle('border-amber-500', activeEnd !== originalEnd);
            endBox.classList.toggle('bg-amber-950/40', activeEnd !== originalEnd);
        }
    } else {
        if (editedFlag) editedFlag.classList.add('hidden');
        if (directSaveBtn) directSaveBtn.classList.add('hidden');
        if (startBox) {
            startBox.classList.remove('border-amber-500', 'bg-amber-950/40');
        }
        if (endBox) {
            endBox.classList.remove('border-amber-500', 'bg-amber-950/40');
        }
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

    document.getElementById('confirmStartOld').textContent = formatTime(originalStart);
    document.getElementById('confirmStartNew').textContent = formatTime(activeStart);
    document.getElementById('confirmEndOld').textContent = formatTime(originalEnd);
    document.getElementById('confirmEndNew').textContent = formatTime(activeEnd);
    document.getElementById('confirmDurationNew').textContent = `${Math.round(activeEnd - activeStart)} segundos`;

    confirmModal.classList.remove('hidden');
    confirmModal.classList.add('flex');
}

function hideConfirmModal() {
    const confirmModal = document.getElementById('saveConfirmModal');
    if (!confirmModal) return;
    confirmModal.classList.add('hidden');
    confirmModal.classList.remove('flex');
}

function discardAndCloseModal() {
    hideConfirmModal();
    forceClosePreviewModal();
}

function forceClosePreviewModal() {
    const modal = document.getElementById('videoPreviewModal');
    const player = document.getElementById('modalVideoPlayer');
    if (!modal || !player) return;

    player.pause();
    player.src = '';
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    currentItem = null;
}

function confirmAndSaveCut() {
    hideConfirmModal();
    saveAndReCutItem();
}

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

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

                        {{-- Ações na Lateral Direita (Baixar e Publicar no YouTube) --}}
                        <div class="flex-shrink-0 flex flex-col gap-1.5" onclick="event.stopPropagation()">
                            {{-- Botão de Baixar Vídeo --}}
                            <a href="{{ route('admin.lives.cortes.download', $item['live_item_id']) }}" 
                               download="{{ $item['download_filename'] }}"
                               style="background-color: #16a34a; color: #ffffff !important;"
                               class="w-9 h-9 sm:w-10 sm:h-10 inline-flex items-center justify-center bg-green-600 hover:bg-green-700 active:scale-95 text-white font-extrabold rounded-xl shadow-sm transition"
                               title="Baixar Vídeo">
                                <i class="fas fa-download text-white text-sm sm:text-base"></i>
                            </a>

                            {{-- Botão Publicar no YouTube --}}
                            @if(!empty($item['youtube_video_id']))
                                <a href="{{ $item['youtube_url'] ?: ('https://youtube.com/shorts/' . $item['youtube_video_id']) }}" 
                                   target="_blank" 
                                   style="background-color: #dc2626; color: #ffffff !important;"
                                   class="relative w-9 h-9 sm:w-10 sm:h-10 inline-flex items-center justify-center bg-red-600 hover:bg-red-700 active:scale-95 text-white font-extrabold rounded-xl shadow-sm transition ring-2 ring-emerald-400" 
                                   title="{{ $item['youtube_status'] === 'sold_updated' ? 'Short no YouTube (VENDIDO 🔴) - Clique para assistir' : 'Publicado no YouTube Shorts - Clique para assistir' }}">
                                    <i class="fab fa-youtube text-white text-sm sm:text-base"></i>
                                    <span class="absolute -top-1 -right-1 bg-emerald-500 text-white rounded-full text-[9px] w-4 h-4 flex items-center justify-center font-bold shadow ring-1 ring-white">
                                        <i class="fas fa-check"></i>
                                    </span>
                                </a>
                            @else
                                <button type="button" 
                                        onclick="uploadCutToYouTubeSocial({{ $item['live_item_id'] }})" 
                                        id="btn-yt-social-{{ $item['live_item_id'] }}" 
                                        style="background-color: #dc2626; color: #ffffff !important;"
                                        class="w-9 h-9 sm:w-10 sm:h-10 inline-flex items-center justify-center bg-red-600 hover:bg-red-700 active:scale-95 text-white font-extrabold rounded-xl shadow-sm transition cursor-pointer"
                                        title="Publicar este corte no YouTube Shorts">
                                    <i class="fab fa-youtube text-white text-sm sm:text-base"></i>
                                </button>
                            @endif
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
        
        <div class="bg-gray-900 w-full sm:max-w-3xl lg:max-w-4xl h-full sm:h-auto sm:max-h-[96dvh] sm:rounded-2xl overflow-hidden shadow-2xl border-0 sm:border sm:border-gray-700 flex flex-col justify-between"
             style="background-color: #111827;">
            
            {{-- Top Header do Modal (Fixo no topo) --}}
            <div class="shrink-0 px-3 py-2.5 sm:py-3 bg-gray-800 border-b border-gray-700 flex items-center justify-between text-white">
                <div class="flex items-center gap-2 min-w-0">
                    <span id="modalItemCode" class="bg-indigo-600 text-white font-black text-xs px-2.5 py-0.5 rounded-full shrink-0" style="background-color: #4f46e5; color: #ffffff;"></span>
                    <h4 id="modalItemTitle" class="text-xs sm:text-sm font-bold truncate text-gray-100"></h4>
                    <span id="modalSourceBadge" 
                          onclick="toggleModalVideoSource()"
                          class="hidden text-[10px] px-2.5 py-0.5 rounded-full font-extrabold border shrink-0 transition flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-sliders-h mr-1 text-indigo-300"></i>Modo Edição
                    </span>
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
                <div id="modalSavingOverlay" class="absolute inset-0 bg-black/90 hidden flex-col items-center justify-center text-white z-30 p-4 text-center backdrop-blur-sm" style="display: none;">
                    <div class="bg-gray-900 border border-gray-700 rounded-3xl p-6 sm:p-7 max-w-sm w-full shadow-2xl flex flex-col items-center animate-scale-in">
                        
                        {{-- Rodinha / Spinner com Cronômetro Central --}}
                        <div class="relative w-20 h-20 mb-4 flex items-center justify-center">
                            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                <path class="text-gray-800" stroke-width="3.5" stroke="currentColor" fill="none"
                                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path id="savingProgressCircle" class="text-pink-500 transition-all duration-300" stroke-width="3.5" stroke-dasharray="5, 100" stroke-linecap="round" stroke="currentColor" fill="none"
                                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <i class="fas fa-film text-pink-400 text-xs animate-pulse mb-0.5"></i>
                                <span id="savingTimerElapsed" class="text-xs font-mono font-black text-white">0.0s</span>
                            </div>
                        </div>

                        <h5 class="text-base font-black text-white" id="savingStatusTitle">Recortando Vídeo...</h5>
                        <p class="text-xs text-gray-300 mt-1 max-w-xs" id="savingStatusDesc">Severino FFmpeg está processando o novo corte com a minutagem ajustada.</p>

                        {{-- Barra de Progresso Linear --}}
                        <div class="w-full bg-gray-800 rounded-full h-2.5 mt-4 overflow-hidden border border-gray-700">
                            <div id="savingProgressBar" class="bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 h-full rounded-full transition-all duration-200" style="width: 5%;"></div>
                        </div>

                        <div class="flex items-center justify-between w-full mt-2 text-[11px] font-mono text-gray-400">
                            <span id="savingPercentText">5%</span>
                            <span class="flex items-center gap-1"><i class="fas fa-cog fa-spin text-pink-400"></i> Renderizando</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- PAINEL DE EDIÇÃO E TIMELINE PROFISSIONAL (ESTILO CAPCUT / CLIPCHAMP) --}}
            <div class="shrink-0 p-2.5 sm:p-3 bg-gray-900 border-t border-gray-800 space-y-2" style="background-color: #12151e;">
                
                {{-- 1. Barra de Controles Superiores: Início, Tesoura, Fim | -10s, Play, +10s, Timer | Duração --}}
                <div class="flex flex-wrap items-center justify-between gap-2 px-1">
                    
                    {{-- Grupo Esquerdo: Ferramentas de Corte (Tesoura e Lixeira) --}}
                    <div class="flex items-center gap-1.5 sm:gap-2">
                        {{-- Botão Tesoura (Dividir / Split) --}}
                        <button type="button"
                                id="btnMiddleCutScissors"
                                onclick="handleScissorsClick()"
                                class="px-2.5 sm:px-3 py-1.5 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 active:scale-95 text-amber-300 font-extrabold text-xs flex items-center gap-1.5 transition shadow-xs cursor-pointer border border-amber-500/50 shrink-0"
                                title="Dividir a trilha na posição atual da agulha (Split)">
                            <i class="fas fa-scissors text-amber-400" id="iconScissors"></i>
                            <span id="textScissors">Dividir</span>
                        </button>

                        {{-- Botão Lixeira (Excluir Trecho Selecionado) --}}
                        <button type="button"
                                id="btnTrashSegment"
                                onclick="deleteSelectedSegment()"
                                class="px-2.5 sm:px-3 py-1.5 rounded-xl bg-gray-800 text-gray-500 border border-gray-700 font-extrabold text-xs flex items-center gap-1.5 transition shadow-xs cursor-not-allowed shrink-0"
                                title="Selecione uma parte da trilha e clique para excluir">
                            <i class="fas fa-trash-alt text-gray-400" id="iconTrash"></i>
                            <span id="textTrash" class="hidden sm:inline">Excluir</span>
                        </button>

                        {{-- Botão Desfazer / Restaurar --}}
                        <button type="button" 
                                id="btnClearMiddleCut"
                                onclick="resetSplitsAndCuts()"
                                class="hidden px-2 py-1.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 border border-gray-600 text-xs font-bold cursor-pointer transition shrink-0"
                                title="Desfazer divisões e restaurar trecho original">
                            <i class="fas fa-undo text-[10px] text-gray-400"></i>
                        </button>
                    </div>

                    {{-- Grupo Central: -10s, Play/Pause, +10s e Timer Digital --}}
                    <div class="flex items-center gap-2 sm:gap-3">
                        <button type="button" 
                                onclick="jumpMinus10()" 
                                id="btnMinus10"
                                class="w-8 h-8 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-200 border border-gray-700 font-bold text-xs flex items-center justify-center transition active:scale-95 cursor-pointer"
                                title="Voltar 10s">
                            <i class="fas fa-undo text-[11px] text-pink-400"></i>
                        </button>

                        <button type="button" 
                                onclick="toggleModalPlayPause()" 
                                id="modalPlayPauseBtn" 
                                class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-white hover:bg-gray-200 active:scale-90 text-gray-900 flex items-center justify-center text-sm shadow-xl transition cursor-pointer font-black">
                            <i class="fas fa-play ml-0.5 text-xs text-gray-950" id="modalPlayPauseIcon"></i>
                        </button>

                        <button type="button" 
                                onclick="jumpPlus10()" 
                                id="btnPlus10"
                                class="w-8 h-8 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-200 border border-gray-700 font-bold text-xs flex items-center justify-center transition active:scale-95 cursor-pointer"
                                title="Avançar 10s">
                            <i class="fas fa-redo text-[11px] text-pink-400"></i>
                        </button>

                        {{-- Timer Digital --}}
                        <div class="flex items-center gap-1 font-mono text-xs sm:text-sm font-bold ml-1 text-gray-400">
                            <span id="modalCurrentTimeText" class="text-white font-black">00:00</span>
                            <span class="text-gray-600">/</span>
                            <span id="modalTotalTimeText" class="text-gray-300">00:00</span>
                        </div>
                    </div>

                    {{-- Grupo Direito: Duração Total e Badge Editado --}}
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span id="badgeDurationSec" class="text-xs font-mono font-bold text-gray-200 bg-gray-800 border border-gray-700 px-2.5 py-1 rounded-lg">
                            0s
                        </span>
                        <span id="badgeEditedFlag" class="hidden items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black animate-pulse" style="background-color: #fef3c7; color: #92400e !important; border: 1px solid #fde68a;">
                            <i class="fas fa-pen text-[9px] text-amber-700"></i>
                            <span style="color: #92400e !important;">Editado</span>
                        </span>
                    </div>

                </div>

                {{-- 2. PAINEL MULTI-TRACK ESTILO CAPCUT / CLIPCHAMP --}}
                <div class="relative w-full bg-[#0d0f17] rounded-xl border border-gray-800 p-2 sm:p-2.5 shadow-2xl select-none touch-none cursor-pointer overflow-hidden space-y-1.5"
                     id="visualTimelineTrack"
                     title="Arraste ou clique para navegar no ponto exato da reprodução">

                    {{-- A. RÉGUA DE TEMPO (TIME RULER) --}}
                    <div class="relative w-full h-5 border-b border-gray-800/80 flex items-end select-none pointer-events-none mb-1 px-1 overflow-hidden"
                         id="timeRulerContainer">
                        {{-- Marcadores numéricos proporcionais gerados dinamicamente via JS --}}
                    </div>

                    {{-- B. TRILHA DE LEGENDA / PRODUTO (COM ALÇAS DE CORTE PUXÁVEIS E SEGMENTOS DIVIDIDOS) --}}
                    <div class="relative w-full h-8 sm:h-9 bg-gray-950/70 rounded-xl overflow-visible border border-gray-800 flex items-center"
                         id="captionTrackContainer">
                        {{-- Segmentos e Alças renderizados dinamicamente via renderCaptionTrack() --}}
                    </div>

                    {{-- C. TRILHA DE VÍDEO COM FILMSTRIP (FRAMES / MINIATURAS) --}}
                    <div class="relative w-full h-14 sm:h-16 bg-gray-950 rounded-xl overflow-hidden border border-gray-700/80 shadow-inner flex items-center"
                         id="filmstripTrackWrapper">
                        
                        {{-- Película de frames em miniatura lado a lado cobrindo 100% --}}
                        <div class="absolute inset-0 flex items-center w-full h-full pointer-events-none opacity-85 overflow-hidden gap-0.5 bg-black"
                             id="filmstripFramesContainer">
                            {{-- Miniaturas de frames sequenciais inseridas via JS --}}
                        </div>

                        {{-- C.1 Faixa Escura Antes do Início (Descarte) --}}
                        <div id="trackDarkBefore" 
                             class="absolute top-0 bottom-0 left-0 bg-black/85 border-r-2 border-cyan-400 z-10 transition-none pointer-events-none"
                             style="width: 0%;">
                        </div>

                        {{-- C.2 Moldura / Realce da Seleção Ativa do Corte --}}
                        <div id="trackBlueActive" 
                             class="absolute top-0 bottom-0 border-2 border-blue-500 rounded-lg pointer-events-none z-10 shadow-[0_0_12px_rgba(59,130,246,0.35)] transition-none"
                             style="left: 0%; width: 100%;">
                        </div>

                        {{-- C.3 Faixa Escura de Corte no Meio (Tesoura) --}}
                        <div id="trackDarkMiddleCut" 
                             class="hidden absolute top-0 bottom-0 z-15 items-center justify-center text-[10px] font-black text-red-200 border-x-2 border-dashed border-red-400 select-none rounded-sm pointer-events-none"
                             style="left: 0%; width: 0%; background: repeating-linear-gradient(45deg, rgba(10,10,10,0.94), rgba(10,10,10,0.94) 6px, rgba(28,28,28,0.94) 6px, rgba(28,28,28,0.94) 12px);">
                            <span id="trackDarkMiddleCutLabel" class="truncate px-1.5 py-0.5 bg-red-950/90 rounded border border-red-500/50 text-[9px] font-mono text-red-300">
                                ✂️ Corte
                            </span>
                        </div>

                        {{-- C.4 Faixa Escura Depois do Fim (Descarte) --}}
                        <div id="trackDarkAfter" 
                             class="absolute top-0 bottom-0 right-0 bg-black/85 border-l-2 border-cyan-400 z-10 transition-none pointer-events-none"
                             style="width: 0%;">
                        </div>

                        {{-- Risco de Início com Abas --}}
                        <div id="markerStart" 
                             class="absolute pointer-events-none transition-none flex flex-col items-center justify-between"
                             style="left: 0%; top: -3px; bottom: -3px; transform: translateX(-50%); z-index: 25;">
                            <div class="w-3 h-2 bg-cyan-400 rounded-t-sm shadow"></div>
                            <div class="w-1 flex-1 bg-cyan-300 shadow-[0_0_8px_rgba(34,211,238,0.95)]"></div>
                            <div class="w-3 h-2 bg-cyan-400 rounded-b-sm shadow"></div>
                        </div>

                        {{-- Risco de Fim com Abas --}}
                        <div id="markerEnd" 
                             class="absolute pointer-events-none transition-none flex flex-col items-center justify-between"
                             style="left: 100%; top: -3px; bottom: -3px; transform: translateX(-50%); z-index: 25;">
                            <div class="w-3 h-2 bg-cyan-400 rounded-t-sm shadow"></div>
                            <div class="w-1 flex-1 bg-cyan-300 shadow-[0_0_8px_rgba(34,211,238,0.95)]"></div>
                            <div class="w-3 h-2 bg-cyan-400 rounded-b-sm shadow"></div>
                        </div>

                        {{-- Marcador Tesoura Ponto 1 --}}
                        <div id="markerMiddleCut1" 
                             class="hidden absolute pointer-events-none transition-none flex flex-col items-center justify-between"
                             style="left: 0%; top: -4px; bottom: -4px; transform: translateX(-50%); z-index: 28;">
                            <div class="w-3 h-2.5 bg-amber-400 text-amber-950 text-[8px] font-black rounded-t-sm shadow flex items-center justify-center">
                                <i class="fas fa-scissors"></i>
                            </div>
                            <div class="w-1 flex-1 bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.95)]"></div>
                            <div class="w-2.5 h-1.5 bg-amber-400 rounded-b-sm shadow"></div>
                        </div>

                        {{-- Marcador Tesoura Ponto 2 --}}
                        <div id="markerMiddleCut2" 
                             class="hidden absolute pointer-events-none transition-none flex flex-col items-center justify-between"
                             style="left: 0%; top: -4px; bottom: -4px; transform: translateX(-50%); z-index: 28;">
                            <div class="w-3 h-2.5 bg-red-400 text-red-950 text-[8px] font-black rounded-t-sm shadow flex items-center justify-center">
                                <i class="fas fa-scissors"></i>
                            </div>
                            <div class="w-1 flex-1 bg-red-400 shadow-[0_0_8px_rgba(248,113,113,0.95)]"></div>
                            <div class="w-2.5 h-1.5 bg-red-400 rounded-b-sm shadow"></div>
                        </div>

                    </div>

                    {{-- E. AGULHA MESTRA DE REPRODUÇÃO (PLAYHEAD TRANSVERSAL QUE ATRAVESSA TODAS AS FAIXAS) --}}
                    <div id="trackPlayhead" 
                         class="absolute top-1 bottom-1 pointer-events-none transition-none z-35 flex flex-col items-center -translate-x-1/2" 
                         style="left: 0%;">
                        {{-- Pino Branco Superior --}}
                        <div class="w-3.5 h-4 bg-white rounded-full shadow-2xl border border-gray-300 flex items-center justify-center -mt-0.5">
                            <div class="w-1.5 h-1.5 bg-pink-500 rounded-full"></div>
                        </div>
                        {{-- Linha Branca Contínua que atravessa toda a altura --}}
                        <div class="w-0.5 flex-1 bg-white shadow-[0_0_8px_rgba(255,255,255,0.95)]"></div>
                    </div>

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
                <div id="confirmMiddleCutRow" class="hidden items-center justify-between text-gray-300 pt-1.5 border-t border-gray-700">
                    <span>Corte no Meio:</span>
                    <span class="font-bold text-amber-400" id="confirmMiddleCutText">00:00 ➔ 00:00 (-0s)</span>
                </div>
                <div class="flex items-center justify-between text-gray-300 pt-1.5 border-t border-gray-700">
                    <span>Duração Final:</span>
                    <span class="font-bold text-pink-400" id="confirmDurationNew">0s</span>
                </div>
            </div>

            {{-- Botões de Decisão com Destaque Máximo para Salvar --}}
            <div class="flex flex-col gap-2.5">
                <button type="button" 
                        onclick="confirmAndSaveCut()" 
                        class="w-full py-3.5 px-5 rounded-2xl bg-gradient-to-r from-emerald-500 via-green-600 to-emerald-600 hover:from-emerald-600 hover:to-green-700 active:scale-[0.98] text-white font-black text-sm sm:text-base shadow-xl shadow-emerald-950/60 transition-all flex items-center justify-center gap-2.5 cursor-pointer border border-emerald-400/40 group">
                    <i class="fas fa-check-circle text-lg text-emerald-200 group-hover:scale-110 transition-transform"></i>
                    <span>Salvar e Recortar Vídeo</span>
                </button>

                <div class="grid grid-cols-2 gap-2 mt-1">
                    <button type="button" 
                            onclick="discardAndCloseModal()" 
                            class="py-2.5 px-3 rounded-xl bg-gray-800 hover:bg-red-950/70 text-gray-300 hover:text-red-200 border border-gray-700 text-xs font-bold transition cursor-pointer flex items-center justify-center gap-1.5">
                        <i class="fas fa-trash-alt text-[11px] text-gray-400"></i>
                        <span>Descartar</span>
                    </button>

                    <button type="button" 
                            onclick="hideConfirmModal()" 
                            class="py-2.5 px-3 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-200 border border-gray-700 text-xs font-bold transition cursor-pointer flex items-center justify-center gap-1.5">
                        <i class="fas fa-arrow-left text-[11px] text-gray-400"></i>
                        <span>Continuar Editando</span>
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
let middleCutStart = null;
let middleCutEnd = null;
let originalMiddleCutStart = null;
let originalMiddleCutEnd = null;
let splitPoints = [];
let selectedSegmentIndex = null;
let draggingHandle = null;
let isUsingRecording = false;
let toastTimeout = null;
let isScrubbing = false;

function getWindowBounds() {
    const baseDuration = Math.max(1, originalEnd - originalStart);
    // Margem lateral para centralizar o corte original no centro da barra (mínimo 15s ou 40% da duração)
    const baseMargin = Math.max(15, Math.round(baseDuration * 0.4));

    let wStart = Math.max(0, originalStart - baseMargin);
    let wEnd = originalEnd + baseMargin;

    // Se o corte ativo foi expandido para antes de wStart (ex: -10s), expande a régua para a esquerda
    if (activeStart < wStart) {
        wStart = Math.max(0, activeStart - 10);
    }

    // Se o corte ativo foi expandido para depois de wEnd (ex: +10s), expande a régua para a direita
    if (activeEnd > wEnd) {
        wEnd = activeEnd + 10;
    }

    // Se originalStart está próximo do início da live (ex: 5s), equilibra a margem direita para centralizar
    const leftMargin = originalStart - wStart;
    if (leftMargin < baseMargin && originalStart < baseMargin) {
        wEnd = Math.max(wEnd, originalEnd + leftMargin);
    }

    // Fallback caso esteja reproduzindo apenas arquivo de corte isolado (sem gravação bruta)
    if (!isUsingRecording) {
        const player = document.getElementById('modalVideoPlayer');
        if (player && player.duration && !isNaN(player.duration) && player.duration > 0) {
            wStart = originalStart;
            wEnd = originalStart + player.duration;
        }
    }

    if (wEnd <= wStart) wEnd = wStart + 1;
    return { wStart, wEnd, span: Math.max(0.1, wEnd - wStart) };
}

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

function renderTimeRuler() {
    const container = document.getElementById('timeRulerContainer');
    if (!container) return;
    container.innerHTML = '';
    const { wStart, span } = getWindowBounds();
    const numMarkers = 6;
    for (let i = 0; i <= numMarkers; i++) {
        const pct = (i / numMarkers) * 100;
        const timeSec = wStart + (span * (i / numMarkers));
        const tick = document.createElement('div');
        tick.className = 'absolute flex flex-col items-center transform -translate-x-1/2 bottom-0 pointer-events-none select-none';
        tick.style.left = `${pct}%`;
        tick.innerHTML = `
            <span class="text-[9px] font-mono text-gray-400 font-bold mb-0.5">${formatTime(timeSec)}</span>
            <div class="w-px h-1.5 bg-gray-600"></div>
        `;
        container.appendChild(tick);

        if (i < numMarkers) {
            for (let sub = 1; sub <= 3; sub++) {
                const subPct = pct + ((sub / 4) * (100 / numMarkers));
                const subTick = document.createElement('div');
                subTick.className = 'absolute flex flex-col items-center transform -translate-x-1/2 bottom-0 pointer-events-none select-none';
                subTick.style.left = `${subPct}%`;
                subTick.innerHTML = `<div class="w-px h-1 bg-gray-700/70"></div>`;
                container.appendChild(subTick);
            }
        }
    }
}

function renderFilmstrip(imageUrl) {
    const container = document.getElementById('filmstripFramesContainer');
    if (!container) return;
    container.innerHTML = '';
    const numFrames = 12;
    const imgSrc = imageUrl || 'https://placehold.co/120x80/1a1a24/white?text=Frame';
    for (let i = 0; i < numFrames; i++) {
        const frameDiv = document.createElement('div');
        frameDiv.className = 'flex-1 h-full bg-cover bg-center border-r border-black/40 relative overflow-hidden shrink-0 select-none';
        frameDiv.style.backgroundImage = `url('${imgSrc}')`;
        const vignette = document.createElement('div');
        vignette.className = 'absolute inset-0 bg-gradient-to-b from-black/20 via-transparent to-black/35';
        frameDiv.appendChild(vignette);
        container.appendChild(frameDiv);
    }
}

function updateSourceBadgeUI(isRecording) {
    const badgeEl = document.getElementById('modalSourceBadge');
    if (!badgeEl) return;
    badgeEl.classList.remove('hidden');

    if (isRecording) {
        badgeEl.className = 'text-[10px] px-2.5 py-0.5 rounded-full font-extrabold bg-indigo-900/80 text-indigo-200 border border-indigo-400 shrink-0 transition flex items-center gap-1 cursor-pointer hover:bg-indigo-800';
        const hasCut = (currentItem && currentItem.video_cut_url && currentItem.video_cut_url.trim() !== '');
        badgeEl.innerHTML = `<i class="fas fa-sliders-h mr-1 text-indigo-300"></i>Modo Edição${hasCut ? ' <span class="text-[9px] text-gray-300 opacity-75">(ver legenda)</span>' : ''}`;
        badgeEl.title = hasCut ? 'Clique para alternar para o vídeo gerado com legenda' : 'Edição na gravação da live';
    } else {
        badgeEl.className = 'text-[10px] px-2.5 py-0.5 rounded-full font-extrabold bg-emerald-900/80 text-emerald-200 border border-emerald-400 shrink-0 transition flex items-center gap-1 cursor-pointer hover:bg-emerald-800';
        badgeEl.innerHTML = '<i class="fas fa-closed-captioning mr-1 text-emerald-300"></i>Com Legenda <span class="text-[9px] text-emerald-200 opacity-75">(editar)</span>';
        badgeEl.title = 'Clique para alternar para o modo de edição na gravação da live';
    }
}

function toggleModalVideoSource() {
    if (!currentItem) return;
    const player = document.getElementById('modalVideoPlayer');
    if (!player) return;

    if (isUsingRecording) {
        if (currentItem.video_cut_url && currentItem.video_cut_url.trim() !== '') {
            isUsingRecording = false;
            player.src = currentItem.video_cut_url;
            updateSourceBadgeUI(false);
            player.currentTime = 0;
            player.play().then(() => updatePlayIcon(true)).catch(() => {});
            renderTimeRuler();
            renderTimeBadges();
            showToast('🎬 Exibindo corte gerado com legendas');
        } else {
            showToast('⚠️ Este corte ainda não foi renderizado com legendas.');
        }
    } else {
        if (liveGlobalRecordingUrl && liveGlobalRecordingUrl.trim() !== '') {
            isUsingRecording = true;
            player.src = liveGlobalRecordingUrl;
            updateSourceBadgeUI(true);
            player.currentTime = activeStart;
            player.play().then(() => updatePlayIcon(true)).catch(() => {});
            renderTimeRuler();
            renderTimeBadges();
            showToast('✂️ Modo Edição ativado (Gravação)');
        }
    }
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

    middleCutStart = (item.middle_cut_start_sec !== null && item.middle_cut_start_sec !== undefined && item.middle_cut_start_sec !== '') ? Number(item.middle_cut_start_sec) : null;
    middleCutEnd = (item.middle_cut_end_sec !== null && item.middle_cut_end_sec !== undefined && item.middle_cut_end_sec !== '') ? Number(item.middle_cut_end_sec) : null;
    originalMiddleCutStart = middleCutStart;
    originalMiddleCutEnd = middleCutEnd;
    splitPoints = [];
    selectedSegmentIndex = null;
    draggingHandle = null;

    const badgeEl = document.getElementById('modalSourceBadge');

    // No editor de cortes: se a gravação bruta estiver disponível, priorizamos ela
    // para permitir navegar livremente antes e depois do corte original com a barra centralizada!
    if (liveGlobalRecordingUrl && liveGlobalRecordingUrl.trim() !== '') {
        isUsingRecording = true;
        player.src = liveGlobalRecordingUrl;
        updateSourceBadgeUI(true);
    } else if (item.video_cut_url && item.video_cut_url.trim() !== '') {
        isUsingRecording = false;
        player.src = item.video_cut_url;
        updateSourceBadgeUI(false);
    } else {
        isUsingRecording = false;
        player.src = '';
        if (badgeEl) badgeEl.classList.add('hidden');
    }

    if (titleEl) titleEl.textContent = item.item_name || 'Produto';
    if (codeEl) codeEl.textContent = '#' + (item.codigo_live || item.item_sku || '');

    const timelineTitle = document.getElementById('timelineProductTitleText');
    if (timelineTitle) {
        timelineTitle.textContent = item.item_name || 'Produto';
    }

    renderFilmstrip(item.item_image);
    renderTimeRuler();
    renderTimeBadges();
    updatePlayIcon(false);

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.style.display = 'flex';
    initTimelineEvents();

    player.onloadedmetadata = function() {
        renderTimeRuler();
        renderTimeBadges();
        if (isUsingRecording && activeStart > 0) {
            player.currentTime = activeStart;
        } else {
            player.currentTime = 0;
        }
        player.play().then(() => updatePlayIcon(true)).catch(() => updatePlayIcon(false));
    };

    player.ontimeupdate = function() {
        if (isScrubbing) return;

        const currentText = document.getElementById('modalCurrentTimeText');
        const totalText = document.getElementById('modalTotalTimeText');
        const playhead = document.getElementById('trackPlayhead');

        let currentAbsTime = 0;
        if (isUsingRecording) {
            currentAbsTime = player.currentTime;
        } else {
            currentAbsTime = originalStart + player.currentTime;
        }

        // Pular trecho cortado no meio em tempo real durante a reprodução!
        if (middleCutStart !== null && middleCutEnd !== null) {
            if (currentAbsTime >= middleCutStart && currentAbsTime < middleCutEnd) {
                if (isUsingRecording) {
                    player.currentTime = middleCutEnd;
                } else {
                    player.currentTime = Math.max(0, middleCutEnd - originalStart);
                }
                return;
            }
        }

        // Pausa ao atingir o fim ativo do corte
        if (currentAbsTime >= activeEnd) {
            player.pause();
            updatePlayIcon(false);
            if (isUsingRecording) {
                player.currentTime = activeEnd;
            }
        }

        const { wStart, span } = getWindowBounds();
        const progressPct = Math.min(100, Math.max(0, ((currentAbsTime - wStart) / span) * 100));

        if (playhead) {
            playhead.style.left = `${progressPct}%`;
        }

        if (currentText) {
            const displayOffset = isUsingRecording ? Math.max(0, currentAbsTime - activeStart) : (currentAbsTime - originalStart);
            currentText.textContent = formatTime(Math.max(0, displayOffset));
        }

        let baseDuration = Math.max(0, activeEnd - activeStart);
        let cutLoss = (middleCutStart !== null && middleCutEnd !== null) ? Math.max(0, middleCutEnd - middleCutStart) : 0;
        if (totalText) {
            totalText.textContent = formatTime(Math.max(0, baseDuration - cutLoss));
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
    currentSec = Math.round(currentSec * 10) / 10;

    if (type === 'start') {
        if (currentSec >= activeEnd) {
            showToast('⚠️ O início não pode ser maior ou igual ao fim!');
            return;
        }
        activeStart = Math.max(0, currentSec);

        // Se havia corte no meio e activeStart passou dele, ajusta
        if (middleCutStart !== null && activeStart >= middleCutStart) {
            clearMiddleCut(false);
        }

        showToast(`📍 Início gravado: ${formatTime(activeStart)}`);
    } else if (type === 'end') {
        if (currentSec <= activeStart) {
            showToast('⚠️ O fim não pode ser menor ou igual ao início!');
            return;
        }
        activeEnd = currentSec;

        // Se havia corte no meio e activeEnd ficou antes dele, ajusta
        if (middleCutEnd !== null && activeEnd <= middleCutEnd) {
            clearMiddleCut(false);
        }

        showToast(`📍 Fim gravado: ${formatTime(activeEnd)}`);
    }

    renderTimeBadges();
}

function getSegmentsList() {
    if (middleCutStart !== null && middleCutEnd !== null) {
        return [
            { start: activeStart, end: middleCutStart, isDeleted: false },
            { start: middleCutStart, end: middleCutEnd, isDeleted: true },
            { start: middleCutEnd, end: activeEnd, isDeleted: false }
        ];
    }

    const validSplits = splitPoints
        .filter(p => p > activeStart + 0.3 && p < activeEnd - 0.3)
        .sort((a, b) => a - b);

    if (validSplits.length === 0) {
        return [
            { start: activeStart, end: activeEnd, isDeleted: false }
        ];
    }

    const segments = [];
    let currentStart = activeStart;
    for (let i = 0; i < validSplits.length; i++) {
        segments.push({ start: currentStart, end: validSplits[i], isDeleted: false });
        currentStart = validSplits[i];
    }
    segments.push({ start: currentStart, end: activeEnd, isDeleted: false });

    return segments;
}

function renderCaptionTrack() {
    const container = document.getElementById('captionTrackContainer');
    if (!container) return;

    if (draggingHandle !== null) {
        updateTrimHandlePositionsFast();
        return;
    }

    container.innerHTML = '';
    const { wStart, span } = getWindowBounds();
    const segments = getSegmentsList();
    const itemName = (currentItem && currentItem.item_name) ? currentItem.item_name : 'Produto';

    segments.forEach((seg, index) => {
        const segStartPct = Math.min(100, Math.max(0, ((seg.start - wStart) / span) * 100));
        const segEndPct = Math.min(100, Math.max(segStartPct, ((seg.end - wStart) / span) * 100));
        const segWidthPct = Math.max(0, segEndPct - segStartPct);

        const segDiv = document.createElement('div');
        segDiv.className = 'caption-segment absolute top-0.5 bottom-0.5 rounded-lg flex items-center transition-all select-none overflow-visible';
        segDiv.style.left = `${segStartPct}%`;
        segDiv.style.width = `${segWidthPct}%`;
        segDiv.dataset.index = index;

        const isSelected = (selectedSegmentIndex === index);

        if (seg.isDeleted) {
            // Trecho excluído (Corte no meio)
            segDiv.className += ' bg-red-950/85 border border-dashed border-red-500/80 text-red-300 cursor-pointer overflow-hidden z-10';
            segDiv.title = 'Trecho excluído (Clique para selecionar ou restaurar)';
            segDiv.innerHTML = `
                <div class="w-full h-full flex items-center justify-center px-1 text-[9px] font-mono font-bold text-red-300 pointer-events-none"
                     style="background: repeating-linear-gradient(45deg, rgba(30,10,10,0.8), rgba(30,10,10,0.8) 5px, rgba(50,15,15,0.8) 5px, rgba(50,15,15,0.8) 10px);">
                    <i class="fas fa-scissors mr-1 text-[8px] text-red-400"></i>
                    <span class="truncate">-${(seg.end - seg.start).toFixed(1)}s (Excluído)</span>
                </div>
            `;
            segDiv.onclick = (e) => selectSegment(index, e);
        } else {
            // Segmento ativo normal
            if (isSelected) {
                segDiv.className += ' bg-gradient-to-r from-amber-600 via-amber-500 to-amber-600 border-2 border-amber-300 ring-2 ring-amber-400/80 shadow-[0_0_14px_rgba(251,191,36,0.7)] text-amber-950 font-black cursor-pointer z-20';
            } else {
                segDiv.className += ' bg-gradient-to-r from-purple-800 via-fuchsia-700 to-purple-800 border border-purple-400/60 shadow-md text-white cursor-pointer hover:brightness-110 z-10';
            }

            // A. ALÇA ESQUERDA (Puxador Início) apenas no primeiro segmento ativo
            if (index === 0) {
                const handleStart = document.createElement('div');
                handleStart.id = 'handleTrimStart';
                handleStart.className = 'absolute -top-1 -bottom-1 -left-1.5 w-4 bg-cyan-400 hover:bg-cyan-300 active:bg-cyan-200 cursor-ew-resize flex items-center justify-center rounded-l-md z-35 shadow-lg touch-none select-none transition-colors border border-cyan-200';
                handleStart.title = '↔ Arraste para a esquerda ou direita para ajustar o início do corte';
                handleStart.innerHTML = '<div class="w-0.5 h-3 bg-cyan-950 rounded-full pointer-events-none"></div>';
                segDiv.appendChild(handleStart);
            }

            // B. CONTEÚDO DO SEGMENTO (Texto do Produto)
            const bodyDiv = document.createElement('div');
            bodyDiv.className = 'flex-1 h-full flex items-center overflow-hidden px-3 text-[10px] sm:text-xs font-bold';
            const partLabel = segments.length > 1 ? ` <span class="text-[9px] opacity-80 font-mono ml-1">(P${index + 1})</span>` : '';
            bodyDiv.innerHTML = `
                <span class="mr-1.5 px-1 py-0.2 bg-black/40 rounded text-[9px] font-black text-purple-200 shrink-0">T</span>
                <span class="truncate font-sans font-extrabold">${itemName}${partLabel}</span>
            `;
            bodyDiv.onclick = (e) => selectSegment(index, e);
            segDiv.appendChild(bodyDiv);

            // C. ALÇA DIREITA (Puxador Fim) apenas no último segmento ativo
            if (index === segments.length - 1) {
                const handleEnd = document.createElement('div');
                handleEnd.id = 'handleTrimEnd';
                handleEnd.className = 'absolute -top-1 -bottom-1 -right-1.5 w-4 bg-cyan-400 hover:bg-cyan-300 active:bg-cyan-200 cursor-ew-resize flex items-center justify-center rounded-r-md z-35 shadow-lg touch-none select-none transition-colors border border-cyan-200';
                handleEnd.title = '↔ Arraste para a esquerda ou direita para ajustar o fim do corte';
                handleEnd.innerHTML = '<div class="w-0.5 h-3 bg-cyan-950 rounded-full pointer-events-none"></div>';
                segDiv.appendChild(handleEnd);
            }

            // D. DIVISOR DE CORTE (se houver próximo segmento)
            if (index < segments.length - 1) {
                const cutDivider = document.createElement('div');
                cutDivider.className = 'absolute -right-2 top-0 bottom-0 w-4 flex flex-col items-center justify-center z-25 pointer-events-none';
                cutDivider.innerHTML = `
                    <div class="w-4 h-4 rounded-full bg-amber-400 text-amber-950 text-[8px] font-black flex items-center justify-center shadow-lg border border-white">
                        <i class="fas fa-scissors text-[8px]"></i>
                    </div>
                `;
                segDiv.appendChild(cutDivider);
            }
        }

        container.appendChild(segDiv);
    });

    initTrimHandleEvents();
}

function initTrimHandleEvents() {
    const handleStart = document.getElementById('handleTrimStart');
    if (handleStart && handleStart.dataset.dragInit !== 'true') {
        handleStart.dataset.dragInit = 'true';

        handleStart.addEventListener('pointerdown', function(e) {
            e.stopPropagation();
            draggingHandle = 'start';
            isScrubbing = false;
            try {
                handleStart.setPointerCapture(e.pointerId);
            } catch(err) {}
        });

        handleStart.addEventListener('pointermove', function(e) {
            if (draggingHandle !== 'start') return;
            e.stopPropagation();
            onTrimDrag(e.clientX, 'start');
        });

        const stopDragStart = function(e) {
            if (draggingHandle !== 'start') return;
            draggingHandle = null;
            try {
                if (e && e.pointerId) handleStart.releasePointerCapture(e.pointerId);
            } catch(err) {}
            renderTimeBadges();
            showToast(`📍 Início ajustado para: ${formatTime(activeStart)}`);
        };

        handleStart.addEventListener('pointerup', stopDragStart);
        handleStart.addEventListener('pointercancel', stopDragStart);
    }

    const handleEnd = document.getElementById('handleTrimEnd');
    if (handleEnd && handleEnd.dataset.dragInit !== 'true') {
        handleEnd.dataset.dragInit = 'true';

        handleEnd.addEventListener('pointerdown', function(e) {
            e.stopPropagation();
            draggingHandle = 'end';
            isScrubbing = false;
            try {
                handleEnd.setPointerCapture(e.pointerId);
            } catch(err) {}
        });

        handleEnd.addEventListener('pointermove', function(e) {
            if (draggingHandle !== 'end') return;
            e.stopPropagation();
            onTrimDrag(e.clientX, 'end');
        });

        const stopDragEnd = function(e) {
            if (draggingHandle !== 'end') return;
            draggingHandle = null;
            try {
                if (e && e.pointerId) handleEnd.releasePointerCapture(e.pointerId);
            } catch(err) {}
            renderTimeBadges();
            showToast(`📍 Fim ajustado para: ${formatTime(activeEnd)}`);
        };

        handleEnd.addEventListener('pointerup', stopDragEnd);
        handleEnd.addEventListener('pointercancel', stopDragEnd);
    }
}

function onTrimDrag(clientX, type) {
    const track = document.getElementById('visualTimelineTrack');
    const player = document.getElementById('modalVideoPlayer');
    if (!track || !player) return;

    const rect = track.getBoundingClientRect();
    if (rect.width <= 0) return;

    const clickX = clientX - rect.left;
    const pct = Math.min(1, Math.max(0, clickX / rect.width));
    const { wStart, span } = getWindowBounds();

    let targetTime = wStart + (span * pct);
    targetTime = Math.round(targetTime * 10) / 10;

    if (type === 'start') {
        const maxAllowed = (splitPoints.length > 0) ? splitPoints[0] - 0.5 : activeEnd - 0.5;
        targetTime = Math.max(0, Math.min(maxAllowed, targetTime));
        activeStart = targetTime;

        if (middleCutStart !== null && activeStart >= middleCutStart) {
            middleCutStart = null;
            middleCutEnd = null;
        }

        try {
            if (isUsingRecording) {
                player.currentTime = activeStart;
            } else {
                player.currentTime = Math.max(0, activeStart - originalStart);
            }
        } catch(e) {}

    } else if (type === 'end') {
        const minAllowed = (splitPoints.length > 0) ? splitPoints[splitPoints.length - 1] + 0.5 : activeStart + 0.5;
        targetTime = Math.max(minAllowed, targetTime);
        activeEnd = targetTime;

        if (middleCutEnd !== null && activeEnd <= middleCutEnd) {
            middleCutStart = null;
            middleCutEnd = null;
        }

        try {
            if (isUsingRecording) {
                player.currentTime = activeEnd;
            } else {
                player.currentTime = Math.max(0, activeEnd - originalStart);
            }
        } catch(e) {}
    }

    updateTrimHandlePositionsFast();
    renderTimeBadgesNumbersOnly();
}

function updateTrimHandlePositionsFast() {
    const { wStart, span } = getWindowBounds();
    const startPct = Math.min(100, Math.max(0, ((activeStart - wStart) / span) * 100));
    const endPct = Math.min(100, Math.max(startPct, ((activeEnd - wStart) / span) * 100));

    const trackDarkBefore = document.getElementById('trackDarkBefore');
    const trackBlueActive = document.getElementById('trackBlueActive');
    const trackDarkAfter = document.getElementById('trackDarkAfter');
    const markerStart = document.getElementById('markerStart');
    const markerEnd = document.getElementById('markerEnd');

    if (trackDarkBefore) trackDarkBefore.style.width = `${startPct}%`;
    if (trackBlueActive) {
        trackBlueActive.style.left = `${startPct}%`;
        trackBlueActive.style.width = `${Math.max(0, endPct - startPct)}%`;
    }
    if (trackDarkAfter) trackDarkAfter.style.width = `${Math.max(0, 100 - endPct)}%`;
    if (markerStart) markerStart.style.left = `${startPct}%`;
    if (markerEnd) markerEnd.style.left = `${endPct}%`;

    const container = document.getElementById('captionTrackContainer');
    if (!container) return;
    const segElements = container.querySelectorAll('.caption-segment');
    if (segElements.length > 0) {
        const firstSeg = segElements[0];
        const lastSeg = segElements[segElements.length - 1];
        if (segElements.length === 1) {
            firstSeg.style.left = `${startPct}%`;
            firstSeg.style.width = `${Math.max(0, endPct - startPct)}%`;
        } else {
            if (draggingHandle === 'start') {
                const sEnd = splitPoints.length > 0 ? splitPoints[0] : activeEnd;
                const sEndPct = Math.min(100, Math.max(startPct, ((sEnd - wStart) / span) * 100));
                firstSeg.style.left = `${startPct}%`;
                firstSeg.style.width = `${Math.max(0, sEndPct - startPct)}%`;
            } else if (draggingHandle === 'end') {
                const sStart = splitPoints.length > 0 ? splitPoints[splitPoints.length - 1] : activeStart;
                const sStartPct = Math.min(100, Math.max(0, ((sStart - wStart) / span) * 100));
                lastSeg.style.left = `${sStartPct}%`;
                lastSeg.style.width = `${Math.max(0, endPct - sStartPct)}%`;
            }
        }
    }
}

function renderTimeBadgesNumbersOnly() {
    const durSecEl = document.getElementById('badgeDurationSec');
    const editedFlag = document.getElementById('badgeEditedFlag');
    let baseDuration = Math.max(0, activeEnd - activeStart);
    let cutLoss = (middleCutStart !== null && middleCutEnd !== null) ? Math.max(0, middleCutEnd - middleCutStart) : 0;
    let effectiveDuration = Math.max(0, Math.round(baseDuration - cutLoss));
    if (durSecEl) durSecEl.textContent = `${effectiveDuration}s`;

    const hasChanged = (
        activeStart !== originalStart || 
        activeEnd !== originalEnd || 
        middleCutStart !== originalMiddleCutStart || 
        middleCutEnd !== originalMiddleCutEnd
    );
    if (editedFlag) {
        if (hasChanged) editedFlag.classList.remove('hidden');
        else editedFlag.classList.add('hidden');
    }
}

function handleScissorsClick() {
    const player = document.getElementById('modalVideoPlayer');
    if (!player) return;

    let currentSec = 0;
    if (isUsingRecording) {
        currentSec = player.currentTime;
    } else {
        currentSec = originalStart + player.currentTime;
    }
    currentSec = Math.round(currentSec * 10) / 10;

    // Deve estar dentro do corte ativo
    if (currentSec <= activeStart + 0.5 || currentSec >= activeEnd - 0.5) {
        showToast('⚠️ Posicione a agulha de reprodução dentro do corte para dividir.');
        return;
    }

    // Se já havia um corte no meio excluído, limpa o anterior para nova divisão
    if (middleCutStart !== null && middleCutEnd !== null) {
        middleCutStart = null;
        middleCutEnd = null;
        splitPoints = [];
        selectedSegmentIndex = null;
    }

    // Máximo de 2 divisões (3 partes)
    if (splitPoints.length >= 2) {
        showToast('⚠️ Máximo de 2 divisões atingido. Selecione um trecho e clique no lixeiro 🗑️.');
        return;
    }

    if (splitPoints.some(p => Math.abs(p - currentSec) < 0.5)) {
        showToast('⚠️ Já existe uma divisão nesta posição.');
        return;
    }

    splitPoints.push(currentSec);
    splitPoints.sort((a, b) => a - b);

    // Auto-seleciona o segmento correspondente
    const segments = getSegmentsList();
    for (let i = 0; i < segments.length; i++) {
        if (currentSec >= segments[i].start && currentSec <= segments[i].end) {
            selectedSegmentIndex = i;
            break;
        }
    }

    showToast(`✂️ Trilha dividida em ${formatTime(currentSec)}! Clique na parte que quer retirar e no lixeiro 🗑️ para excluir.`);
    renderTimeBadges();
}

function selectSegment(index, event) {
    if (event) event.stopPropagation();
    if (selectedSegmentIndex === index) {
        selectedSegmentIndex = null;
    } else {
        selectedSegmentIndex = index;
    }
    renderTimeBadges();
}

function deleteSelectedSegment() {
    if (selectedSegmentIndex === null) {
        showToast('⚠️ Clique em uma parte da trilha para selecionar antes de excluir.');
        return;
    }

    const segments = getSegmentsList();
    if (!segments || selectedSegmentIndex >= segments.length) return;

    const seg = segments[selectedSegmentIndex];

    if (seg.isDeleted) {
        resetSplitsAndCuts();
        showToast('↩️ Trecho restaurado com sucesso!');
        return;
    }

    if (segments.length === 1) {
        showToast('💡 Dica: Use a tesoura ✂️ para dividir o trecho que quer excluir, ou puxe as alças ciano nas pontas.');
        return;
    }

    if (segments.length === 2) {
        const splitPoint = splitPoints[0];
        if (selectedSegmentIndex === 0) {
            activeStart = splitPoint;
            splitPoints = [];
            selectedSegmentIndex = null;
            showToast(`🗑️ Trecho inicial excluído! Novo início: ${formatTime(activeStart)}`);
        } else {
            activeEnd = splitPoint;
            splitPoints = [];
            selectedSegmentIndex = null;
            showToast(`🗑️ Trecho final excluído! Novo fim: ${formatTime(activeEnd)}`);
        }
    } else if (segments.length === 3) {
        if (selectedSegmentIndex === 0) {
            activeStart = splitPoints[0];
            splitPoints.shift();
            selectedSegmentIndex = null;
            showToast(`🗑️ Trecho inicial excluído! Novo início: ${formatTime(activeStart)}`);
        } else if (selectedSegmentIndex === 2) {
            activeEnd = splitPoints[1];
            splitPoints.pop();
            selectedSegmentIndex = null;
            showToast(`🗑️ Trecho final excluído! Novo fim: ${formatTime(activeEnd)}`);
        } else if (selectedSegmentIndex === 1) {
            middleCutStart = splitPoints[0];
            middleCutEnd = splitPoints[1];
            splitPoints = [];
            selectedSegmentIndex = null;
            const cutSec = (middleCutEnd - middleCutStart).toFixed(1);
            showToast(`🗑️ Trecho do meio excluído (-${cutSec}s)!`);
        }
    }

    renderTimeBadges();
}

function resetSplitsAndCuts() {
    splitPoints = [];
    selectedSegmentIndex = null;
    middleCutStart = null;
    middleCutEnd = null;
    renderTimeBadges();
    showToast('✂️ Divisões e cortes desfeitos.');
}

function clearMiddleCut(notify = true) {
    resetSplitsAndCuts();
}

function renderTimeBadges() {
    const durSecEl = document.getElementById('badgeDurationSec');
    const editedFlag = document.getElementById('badgeEditedFlag');
    const btnTrash = document.getElementById('btnTrashSegment');
    const textTrash = document.getElementById('textTrash');
    const iconTrash = document.getElementById('iconTrash');
    const btnReset = document.getElementById('btnClearMiddleCut');

    let baseDuration = Math.max(0, activeEnd - activeStart);
    let cutLoss = (middleCutStart !== null && middleCutEnd !== null) ? Math.max(0, middleCutEnd - middleCutStart) : 0;
    let effectiveDuration = Math.max(0, Math.round(baseDuration - cutLoss));

    if (durSecEl) durSecEl.textContent = `${effectiveDuration}s`;

    const segments = getSegmentsList();
    const hasSplitsOrCuts = (splitPoints.length > 0 || middleCutStart !== null);

    if (btnReset) {
        if (hasSplitsOrCuts) btnReset.classList.remove('hidden');
        else btnReset.classList.add('hidden');
    }

    if (btnTrash) {
        if (selectedSegmentIndex !== null && segments[selectedSegmentIndex]) {
            const seg = segments[selectedSegmentIndex];
            if (seg.isDeleted) {
                btnTrash.className = 'px-2.5 sm:px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white border border-emerald-400 font-extrabold text-xs flex items-center gap-1.5 transition shadow-sm cursor-pointer shrink-0 animate-pulse';
                if (iconTrash) iconTrash.className = 'fas fa-undo text-emerald-100';
                if (textTrash) textTrash.textContent = 'Restaurar';
                btnTrash.title = 'Clique para restaurar este trecho excluído';
            } else {
                btnTrash.className = 'px-2.5 sm:px-3 py-1.5 rounded-xl bg-red-600 hover:bg-red-500 text-white border border-red-400 font-extrabold text-xs flex items-center gap-1.5 transition shadow-sm cursor-pointer shrink-0 animate-pulse';
                if (iconTrash) iconTrash.className = 'fas fa-trash-alt text-white';
                if (textTrash) textTrash.textContent = segments.length > 1 ? `Excluir P${selectedSegmentIndex + 1}` : 'Excluir';
                btnTrash.title = 'Clique para excluir a parte selecionada';
            }
        } else {
            btnTrash.className = 'px-2.5 sm:px-3 py-1.5 rounded-xl bg-gray-800 text-gray-500 border border-gray-700 font-extrabold text-xs flex items-center gap-1.5 transition shadow-xs cursor-not-allowed shrink-0';
            if (iconTrash) iconTrash.className = 'fas fa-trash-alt text-gray-500';
            if (textTrash) textTrash.textContent = 'Excluir';
            btnTrash.title = 'Selecione uma parte da trilha para excluir';
        }
    }

    const hasChanged = (
        activeStart !== originalStart || 
        activeEnd !== originalEnd || 
        middleCutStart !== originalMiddleCutStart || 
        middleCutEnd !== originalMiddleCutEnd
    );

    if (hasChanged) {
        if (editedFlag) editedFlag.classList.remove('hidden');
    } else {
        if (editedFlag) editedFlag.classList.add('hidden');
    }

    renderTimeRuler();
    updateVisualTimeline();
}

function updateVisualTimeline() {
    renderCaptionTrack();

    const trackDarkBefore = document.getElementById('trackDarkBefore');
    const trackBlueActive = document.getElementById('trackBlueActive');
    const trackDarkAfter = document.getElementById('trackDarkAfter');
    const markerStart = document.getElementById('markerStart');
    const markerEnd = document.getElementById('markerEnd');
    const markerCut1 = document.getElementById('markerMiddleCut1');
    const markerCut2 = document.getElementById('markerMiddleCut2');
    const trackDarkMiddleCut = document.getElementById('trackDarkMiddleCut');
    const trackDarkMiddleCutLabel = document.getElementById('trackDarkMiddleCutLabel');

    const { wStart, span } = getWindowBounds();

    const startPct = Math.min(100, Math.max(0, ((activeStart - wStart) / span) * 100));
    const endPct = Math.min(100, Math.max(startPct, ((activeEnd - wStart) / span) * 100));

    // 1. Faixa escura antes do início
    if (trackDarkBefore) {
        trackDarkBefore.style.width = `${startPct}%`;
    }

    // 2. Moldura azul ativa entre Início e Fim
    if (trackBlueActive) {
        trackBlueActive.style.left = `${startPct}%`;
        trackBlueActive.style.width = `${Math.max(0, endPct - startPct)}%`;
    }

    // 3. Faixa escura depois do fim
    if (trackDarkAfter) {
        trackDarkAfter.style.width = `${Math.max(0, 100 - endPct)}%`;
    }

    // 4. Riscos verticais estendidos
    if (markerStart) {
        markerStart.style.left = `${startPct}%`;
    }
    if (markerEnd) {
        markerEnd.style.left = `${endPct}%`;
    }

    // 5. Exibe os pontos da tesoura no filmstrip
    if (markerCut1 && markerCut2 && trackDarkMiddleCut) {
        if (middleCutStart !== null && middleCutEnd !== null) {
            const mStartPct = Math.min(100, Math.max(0, ((middleCutStart - wStart) / span) * 100));
            const mEndPct = Math.min(100, Math.max(mStartPct, ((middleCutEnd - wStart) / span) * 100));

            trackDarkMiddleCut.style.left = `${mStartPct}%`;
            trackDarkMiddleCut.style.width = `${Math.max(0, mEndPct - mStartPct)}%`;
            trackDarkMiddleCut.classList.remove('hidden');
            trackDarkMiddleCut.classList.add('flex');

            if (trackDarkMiddleCutLabel) {
                const diff = (middleCutEnd - middleCutStart).toFixed(1);
                trackDarkMiddleCutLabel.textContent = `✂️ -${diff}s`;
            }

            markerCut1.style.left = `${mStartPct}%`;
            markerCut1.classList.remove('hidden');
            markerCut2.style.left = `${mEndPct}%`;
            markerCut2.classList.remove('hidden');
        } else if (splitPoints.length > 0) {
            trackDarkMiddleCut.classList.add('hidden');
            trackDarkMiddleCut.classList.remove('flex');

            const s1Pct = Math.min(100, Math.max(0, ((splitPoints[0] - wStart) / span) * 100));
            markerCut1.style.left = `${s1Pct}%`;
            markerCut1.classList.remove('hidden');

            if (splitPoints.length > 1) {
                const s2Pct = Math.min(100, Math.max(0, ((splitPoints[1] - wStart) / span) * 100));
                markerCut2.style.left = `${s2Pct}%`;
                markerCut2.classList.remove('hidden');
            } else {
                markerCut2.classList.add('hidden');
            }
        } else {
            trackDarkMiddleCut.classList.add('hidden');
            trackDarkMiddleCut.classList.remove('flex');
            markerCut1.classList.add('hidden');
            markerCut2.classList.add('hidden');
        }
    }
}

function seekToTrackPosition(clientX) {
    const track = document.getElementById('visualTimelineTrack');
    const player = document.getElementById('modalVideoPlayer');
    if (!track || !player) return;

    const rect = track.getBoundingClientRect();
    if (rect.width <= 0) return;

    const clickX = clientX - rect.left;
    const pct = Math.min(1, Math.max(0, clickX / rect.width));
    const { wStart, span } = getWindowBounds();

    const targetAbsTime = wStart + (span * pct);

    try {
        if (isUsingRecording) {
            player.currentTime = targetAbsTime;
        } else {
            player.currentTime = Math.max(0, targetAbsTime - originalStart);
        }
    } catch(e) {}

    // Atualiza agulha e minutagem instantaneamente enquanto arrasta
    const playhead = document.getElementById('trackPlayhead');
    if (playhead) {
        playhead.style.left = `${pct * 100}%`;
    }

    const currentText = document.getElementById('modalCurrentTimeText');
    if (currentText) {
        const displayOffset = isUsingRecording ? Math.max(0, targetAbsTime - activeStart) : (targetAbsTime - originalStart);
        currentText.textContent = formatTime(Math.max(0, displayOffset));
    }
}

function initTimelineEvents() {
    const track = document.getElementById('visualTimelineTrack');
    if (!track || track.dataset.dragInit === 'true') return;
    track.dataset.dragInit = 'true';

    track.addEventListener('pointerdown', function(e) {
        isScrubbing = true;
        try {
            track.setPointerCapture(e.pointerId);
        } catch(err) {}
        seekToTrackPosition(e.clientX);
    });

    track.addEventListener('pointermove', function(e) {
        if (!isScrubbing) return;
        seekToTrackPosition(e.clientX);
    });

    const stopScrubbing = function(e) {
        if (!isScrubbing) return;
        isScrubbing = false;
        try {
            if (e && e.pointerId) track.releasePointerCapture(e.pointerId);
        } catch(err) {}
    };

    track.addEventListener('pointerup', stopScrubbing);
    track.addEventListener('pointercancel', stopScrubbing);
}

document.addEventListener('DOMContentLoaded', initTimelineEvents);

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
        activeStart = Math.max(0, activeStart - 10);
        if (!isUsingRecording && liveGlobalRecordingUrl && liveGlobalRecordingUrl.trim() !== '') {
            isUsingRecording = true;
            player.src = liveGlobalRecordingUrl;
            updateSourceBadgeUI(true);
        }
        if (isUsingRecording) {
            player.currentTime = activeStart;
            player.play().then(() => updatePlayIcon(true)).catch(() => {});
        }
        showToast(`✨ +10s adicionados ao início do corte! (${formatTime(activeStart)})`);
        renderTimeBadges();
    } else {
        // Pular 10s para trás
        player.currentTime = Math.max(0, player.currentTime - 10);
    }
}

function jumpPlus10() {
    const player = document.getElementById('modalVideoPlayer');
    if (!player) return;

    const isAtEnd = isUsingRecording 
        ? (player.currentTime >= activeEnd - 1.2 || (player.paused && player.currentTime >= activeEnd - 2.0))
        : (player.currentTime >= (player.duration - 1.2) || player.ended);

    if (isAtEnd) {
        activeEnd = activeEnd + 10;
        if (!isUsingRecording && liveGlobalRecordingUrl && liveGlobalRecordingUrl.trim() !== '') {
            isUsingRecording = true;
            player.src = liveGlobalRecordingUrl;
            updateSourceBadgeUI(true);
        }
        if (isUsingRecording) {
            player.play().then(() => updatePlayIcon(true)).catch(() => {});
        }
        showToast(`✨ +10s adicionados ao fim do corte! (${formatTime(activeEnd)})`);
        renderTimeBadges();
    } else {
        // Pular 10s para frente
        player.currentTime = Math.min(player.duration || 999999, player.currentTime + 10);
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
    const hasChanged = (
        activeStart !== originalStart || 
        activeEnd !== originalEnd || 
        middleCutStart !== originalMiddleCutStart || 
        middleCutEnd !== originalMiddleCutEnd
    );
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
    const middleRow = document.getElementById('confirmMiddleCutRow');
    const middleText = document.getElementById('confirmMiddleCutText');

    if (startOld) startOld.textContent = formatTime(originalStart);
    if (startNew) startNew.textContent = formatTime(activeStart);
    if (endOld) endOld.textContent = formatTime(originalEnd);
    if (endNew) endNew.textContent = formatTime(activeEnd);

    let baseDuration = Math.max(0, activeEnd - activeStart);
    let cutLoss = (middleCutStart !== null && middleCutEnd !== null) ? Math.max(0, middleCutEnd - middleCutStart) : 0;
    let finalDur = Math.max(0, Math.round(baseDuration - cutLoss));

    if (durNew) durNew.textContent = `${finalDur} segundos`;

    if (middleRow && middleText) {
        if (middleCutStart !== null && middleCutEnd !== null) {
            middleRow.classList.remove('hidden');
            middleRow.classList.add('flex');
            middleText.textContent = `${formatTime(middleCutStart)} ➔ ${formatTime(middleCutEnd)} (-${(middleCutEnd - middleCutStart).toFixed(1)}s)`;
        } else {
            middleRow.classList.add('hidden');
            middleRow.classList.remove('flex');
        }
    }

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
window.handleScissorsClick = handleScissorsClick;
window.selectSegment = selectSegment;
window.deleteSelectedSegment = deleteSelectedSegment;
window.resetSplitsAndCuts = resetSplitsAndCuts;
window.clearMiddleCut = clearMiddleCut;
window.toggleModalPlayPause = toggleModalPlayPause;
window.jumpMinus10 = jumpMinus10;
window.jumpPlus10 = jumpPlus10;
window.seekToTrackPosition = seekToTrackPosition;
window.initTimelineEvents = initTimelineEvents;

async function saveAndReCutItem() {
    if (!currentItem) return;

    const overlay = document.getElementById('modalSavingOverlay');
    const progressCircle = document.getElementById('savingProgressCircle');
    const progressBar = document.getElementById('savingProgressBar');
    const percentText = document.getElementById('savingPercentText');
    const timerElapsed = document.getElementById('savingTimerElapsed');
    const statusTitle = document.getElementById('savingStatusTitle');
    const statusDesc = document.getElementById('savingStatusDesc');

    if (overlay) {
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        overlay.style.display = 'flex';
    }

    // Reinicia contadores visuais
    if (progressCircle) progressCircle.setAttribute('stroke-dasharray', '5, 100');
    if (progressBar) progressBar.style.width = '5%';
    if (percentText) percentText.textContent = '5%';
    if (timerElapsed) timerElapsed.textContent = '0.0s';
    if (statusTitle) statusTitle.textContent = 'Recortando Vídeo...';
    if (statusDesc) statusDesc.textContent = 'Severino FFmpeg está processando o novo corte com a minutagem ajustada.';

    const startTime = Date.now();
    let currentPct = 5;

    const ticker = setInterval(() => {
        const elapsedSec = ((Date.now() - startTime) / 1000);
        if (timerElapsed) timerElapsed.textContent = elapsedSec.toFixed(1) + 's';

        // Incremento suave até 94% enquanto aguarda a resposta do servidor
        if (currentPct < 94) {
            currentPct = Math.min(94, Math.floor(5 + (elapsedSec * 14)));
            if (progressBar) progressBar.style.width = `${currentPct}%`;
            if (percentText) percentText.textContent = `${currentPct}%`;
            if (progressCircle) progressCircle.setAttribute('stroke-dasharray', `${currentPct}, 100`);
        }

        if (elapsedSec > 2.5 && statusDesc) {
            statusDesc.textContent = 'Otimizando frames e faixas de áudio...';
        }
        if (elapsedSec > 5.0 && statusDesc) {
            statusDesc.textContent = 'Finalizando gravação do arquivo MP4 no disco...';
        }
    }, 100);

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
                cut_end_sec: activeEnd,
                middle_cut_start_sec: middleCutStart,
                middle_cut_end_sec: middleCutEnd
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
            },
            body: JSON.stringify({
                middle_cut_start_sec: middleCutStart,
                middle_cut_end_sec: middleCutEnd
            })
        });
        const cutJson = await cutResp.json();
        if (!cutJson.success) {
            throw new Error(cutJson.message || 'Erro ao gerar novo corte de vídeo.');
        }

        // Sucesso 100%
        clearInterval(ticker);
        const totalElapsed = ((Date.now() - startTime) / 1000).toFixed(1);
        if (timerElapsed) timerElapsed.textContent = totalElapsed + 's';
        if (progressBar) progressBar.style.width = '100%';
        if (percentText) percentText.textContent = '100%';
        if (progressCircle) progressCircle.setAttribute('stroke-dasharray', '100, 100');
        if (statusTitle) statusTitle.textContent = '✅ Corte Finalizado!';
        if (statusDesc) statusDesc.textContent = `Vídeo recortado com sucesso em ${totalElapsed}s.`;

        // 3. Atualizar dados do item local
        const newUrl = cutJson.video_url || cutJson.video_cut_url || currentItem.video_cut_url;
        currentItem.video_cut_url = newUrl;
        currentItem.cut_start_sec = activeStart;
        currentItem.cut_end_sec = activeEnd;
        currentItem.middle_cut_start_sec = middleCutStart;
        currentItem.middle_cut_end_sec = middleCutEnd;
        originalStart = activeStart;
        originalEnd = activeEnd;
        originalMiddleCutStart = middleCutStart;
        originalMiddleCutEnd = middleCutEnd;

        if (typeof liveItemsMap !== 'undefined' && liveItemsMap[liveItemId]) {
            liveItemsMap[liveItemId].video_cut_url = newUrl;
            liveItemsMap[liveItemId].cut_start_sec = activeStart;
            liveItemsMap[liveItemId].cut_end_sec = activeEnd;
            liveItemsMap[liveItemId].middle_cut_start_sec = middleCutStart;
            liveItemsMap[liveItemId].middle_cut_end_sec = middleCutEnd;
            liveItemsMap[liveItemId].is_ready = true;
        }

        renderTimeBadges();
        showToast('✅ Corte atualizado e recortado com sucesso!');

        setTimeout(() => {
            if (overlay) {
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
                overlay.style.display = 'none';
            }
            forceClosePreviewModal();
        }, 700);

    } catch (err) {
        clearInterval(ticker);
        alert('Erro ao salvar corte: ' + err.message);
        if (overlay) {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
            overlay.style.display = 'none';
        }
    }
}

function showGlobalToast(msg, icon = '✅', duration = 5000) {
    let toast = document.getElementById('socialGlobalToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'socialGlobalToast';
        toast.className = 'fixed top-5 right-5 z-[9999] max-w-md bg-gray-900 text-white px-4 py-3 rounded-2xl shadow-2xl flex items-center gap-3 border border-gray-700 transition-all';
        document.body.appendChild(toast);
    }
    toast.innerHTML = `<span class="text-xl flex-shrink-0">${icon}</span><span class="text-sm font-semibold text-white leading-snug">${msg}</span>`;
    toast.classList.remove('hidden');
    toast.style.display = 'flex';
    clearTimeout(window.__socialGlobalToastTimeout);
    window.__socialGlobalToastTimeout = setTimeout(() => {
        toast.classList.add('hidden');
        toast.style.display = 'none';
    }, duration);
}
window.showGlobalToast = showGlobalToast;

async function uploadCutToYouTubeSocial(itemId) {
    const isConnected = {{ !empty($youtubeAccount) ? 'true' : 'false' }};
    if (!isConnected) {
        if (confirm("Você precisa conectar o Canal do YouTube primeiro. Deseja conectar agora?")) {
            window.location.href = "{{ route('admin.youtube.connect') }}";
        }
        return;
    }

    const btn = document.getElementById(`btn-yt-social-${itemId}`);
    const oldHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin text-white text-sm sm:text-base"></i>';
    }

    showGlobalToast("Enviando vídeo para o YouTube Shorts... Aguarde alguns segundos.", "🚀", 15000);

    try {
        const res = await fetch(`{{ url('admin/youtube/upload-cut') }}/${itemId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ privacy: 'public' })
        });
        const data = await res.json();

        if (data.success) {
            if (btn) {
                btn.outerHTML = `
                    <a href="${data.url}" target="_blank" title="Publicado no YouTube Shorts - Clique para assistir" style="background-color: #dc2626; color: #ffffff !important;" class="relative w-9 h-9 sm:w-10 sm:h-10 inline-flex items-center justify-center bg-red-600 hover:bg-red-700 active:scale-95 text-white font-extrabold rounded-xl shadow-sm transition ring-2 ring-emerald-400">
                        <i class="fab fa-youtube text-white text-sm sm:text-base"></i>
                        <span class="absolute -top-1 -right-1 bg-emerald-500 text-white rounded-full text-[9px] w-4 h-4 flex items-center justify-center font-bold shadow ring-1 ring-white">
                            <i class="fas fa-check"></i>
                        </span>
                    </a>
                `;
            }
            if (typeof liveItemsMap !== 'undefined' && liveItemsMap[itemId]) {
                liveItemsMap[itemId].youtube_video_id = data.video_id || 'published';
                liveItemsMap[itemId].youtube_url = data.url;
                liveItemsMap[itemId].youtube_status = 'published';
            }
            showGlobalToast(`Publicado com sucesso no YouTube Shorts! <a href="${data.url}" target="_blank" style="color: #60a5fa; text-decoration: underline; font-weight: bold; margin-left: 6px;">Abrir Short ↗</a>`, "🎉", 10000);
        } else {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }
            showGlobalToast(data.message || 'Falha no upload para o YouTube. Tente novamente.', "⚠️", 7000);
        }
    } catch(e) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
        }
        showGlobalToast("Instabilidade ao comunicar com o servidor: " + e.message, "❌", 7000);
    }
}
window.uploadCutToYouTubeSocial = uploadCutToYouTubeSocial;

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

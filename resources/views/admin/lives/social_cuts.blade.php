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
        
        <div class="bg-gray-900 w-full sm:max-w-lg h-full sm:h-auto sm:max-h-[96dvh] sm:rounded-2xl overflow-hidden shadow-2xl border-0 sm:border sm:border-gray-700 flex flex-col justify-between"
             style="background-color: #111827;">
            
            {{-- Top Header do Modal (Fixo no topo) --}}
            <div class="shrink-0 px-3 py-2.5 sm:py-3 bg-gray-800 border-b border-gray-700 flex items-center justify-between text-white">
                <div class="flex items-center gap-2 min-w-0">
                    <span id="modalItemCode" class="bg-indigo-600 text-white font-black text-xs px-2.5 py-0.5 rounded-full shrink-0" style="background-color: #4f46e5; color: #ffffff;"></span>
                    <h4 id="modalItemTitle" class="text-xs sm:text-sm font-bold truncate text-gray-100"></h4>
                    <span id="modalSourceBadge" class="hidden text-[10px] px-2 py-0.5 rounded-full font-extrabold bg-emerald-900/80 text-emerald-200 border border-emerald-500 shrink-0">
                        <i class="fas fa-closed-captioning mr-1 text-emerald-400"></i>Com Legenda
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

            {{-- Toolbar de Controles & Edição de Minutagem (Fixa na parte inferior) --}}
            <div class="shrink-0 p-3 sm:p-4 bg-gray-850 border-t border-gray-800 space-y-2.5" style="background-color: #1a202c;">
                
                {{-- Linha Superior: Botão [Início |▶], Tesoura ✂️, Duração/Status e Botão [Fim ▶|] --}}
                <div class="flex items-center justify-between gap-1.5 sm:gap-2">
                    {{-- Botão Gravar Ponto Início (Seta com traço |▶) --}}
                    <button type="button" 
                            onclick="setCutPointHere('start')"
                            class="px-2.5 sm:px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 active:scale-95 text-white font-black text-xs flex items-center gap-1.5 transition shadow-sm cursor-pointer border border-blue-400 shrink-0"
                            style="background-color: #2563eb; color: #ffffff;"
                            title="Gravar posição atual como Início do corte (Mark In)">
                        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4 4h3v16H4V4zm5 8l11-8v16l-11-8z" />
                        </svg>
                        <span>Início</span>
                    </button>

                    {{-- Botão da Tesoura para Corte no Meio --}}
                    <div class="flex items-center gap-1">
                        <button type="button"
                                id="btnMiddleCutScissors"
                                onclick="handleScissorsClick()"
                                class="px-2.5 sm:px-3 py-1.5 rounded-xl bg-gray-800 hover:bg-gray-700 active:scale-95 text-amber-300 font-extrabold text-xs flex items-center gap-1.5 transition shadow-xs cursor-pointer border border-amber-500/50"
                                title="Cortar no meio: 1º clique marca início, 2º clique marca fim do trecho a cortar">
                            <i class="fas fa-scissors text-amber-400" id="iconScissors"></i>
                            <span id="textScissors">Cortar Meio</span>
                        </button>
                        <button type="button" 
                                id="btnClearMiddleCut"
                                onclick="clearMiddleCut()"
                                class="hidden px-2 py-1.5 rounded-xl bg-red-950/80 hover:bg-red-800 text-red-200 border border-red-500 text-xs font-black cursor-pointer transition"
                                title="Desfazer corte no meio">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    {{-- Duração Central e Badge Editado (Alto Contraste) --}}
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span id="badgeDurationSec" class="text-xs font-mono font-bold text-gray-200 bg-gray-800 border border-gray-700 px-2 py-0.5 rounded-lg">
                            0s
                        </span>
                        <span id="badgeEditedFlag" class="hidden items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black animate-pulse" style="background-color: #fef3c7; color: #92400e !important; border: 1px solid #fde68a;">
                            <i class="fas fa-pen text-[9px] text-amber-700"></i>
                            <span style="color: #92400e !important;">Editado</span>
                        </span>
                    </div>

                    {{-- Botão Gravar Ponto Fim (Seta com traço ▶|) --}}
                    <button type="button" 
                            onclick="setCutPointHere('end')"
                            class="px-2.5 sm:px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 active:scale-95 text-white font-black text-xs flex items-center gap-1.5 transition shadow-sm cursor-pointer border border-blue-400 shrink-0"
                            style="background-color: #2563eb; color: #ffffff;"
                            title="Gravar posição atual como Fim do corte (Mark Out)">
                        <span>Fim</span>
                        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17 4h3v16h-3V4zM4 20V4l11 8-11 8z" />
                        </svg>
                    </button>
                </div>

                {{-- Barra Visual de Cortes com Marcadores Maiores e Riscos Verticais --}}
                <div class="space-y-1.5 pt-1">
                    {{-- Container Externo relativo com overflow-visible para permitir que os riscos ultrapassem a barra para cima e para baixo --}}
                    <div class="relative w-full h-8 sm:h-9 flex items-center cursor-pointer select-none touch-none"
                         id="visualTimelineTrack"
                         title="Toque ou arraste para navegar e ajustar o ponto exato">
                        
                        {{-- Trilho Fundo (Barra propriamente dita com cantos arredondados) --}}
                        <div class="relative w-full h-7 sm:h-8 bg-gray-900 rounded-xl overflow-hidden border border-gray-700 shadow-inner flex pointer-events-none">
                            
                            {{-- 1. Faixa Escura Antes do Início (0% até startPct) --}}
                            <div id="trackDarkBefore" 
                                 class="h-full bg-black/85 border-r border-gray-700/80 transition-none"
                                 style="width: 0%;" 
                                 title="Trecho descartado antes do início">
                            </div>

                            {{-- 2. Faixa Azul Ativa do Corte (startPct até endPct) --}}
                            <div id="trackBlueActive" 
                                 class="relative h-full bg-gradient-to-r from-blue-600 via-blue-500 to-blue-600 transition-none flex items-center justify-center overflow-hidden" 
                                 style="width: 100%;" 
                                 title="Trecho selecionado para o corte">
                            </div>

                            {{-- 3. Faixa Escura Depois do Fim (endPct até 100%) --}}
                            <div id="trackDarkAfter" 
                                 class="h-full bg-black/85 border-l border-gray-700/80 transition-none"
                                 style="width: 0%;" 
                                 title="Trecho descartado depois do fim">
                            </div>

                        </div>

                        {{-- 4. Faixa Escura do Corte no Meio (Tesoura) sobreposta --}}
                        <div id="trackDarkMiddleCut" 
                             class="hidden absolute top-0.5 bottom-0.5 pointer-events-none z-15 items-center justify-center text-[10px] font-black text-red-200 border-x-2 border-dashed border-red-400 select-none rounded-sm"
                             style="left: 0%; width: 0%; background: repeating-linear-gradient(45deg, rgba(10,10,10,0.92), rgba(10,10,10,0.92) 6px, rgba(28,28,28,0.92) 6px, rgba(28,28,28,0.92) 12px);"
                             title="Trecho cortado no meio (removido)">
                            <span id="trackDarkMiddleCutLabel" class="truncate px-1 bg-red-950/90 rounded border border-red-500/50 text-[9px] font-mono text-red-300">
                                ✂️ Corte
                            </span>
                        </div>

                        {{-- RISCO VERTICAL DE INÍCIO (Ultrapassa a barra para cima e para baixo) --}}
                        <div id="markerStart" 
                             class="absolute pointer-events-none transition-none flex flex-col items-center justify-between"
                             style="left: 0%; top: -5px; bottom: -5px; transform: translateX(-50%); z-index: 25;">
                            {{-- Aba Superior --}}
                            <div class="w-3.5 h-2.5 bg-cyan-400 rounded-t-sm shadow-md flex items-center justify-center">
                                <div class="w-1.5 h-1 bg-cyan-950 rounded-xs"></div>
                            </div>
                            {{-- Risco Vertical Fluorescente --}}
                            <div class="w-1 flex-1 bg-cyan-300 shadow-[0_0_8px_rgba(34,211,238,0.95)]"></div>
                            {{-- Aba Inferior --}}
                            <div class="w-3.5 h-2.5 bg-cyan-400 rounded-b-sm shadow-md flex items-center justify-center">
                                <div class="w-1.5 h-1 bg-cyan-950 rounded-xs"></div>
                            </div>
                        </div>

                        {{-- RISCO VERTICAL DE FIM (Ultrapassa a barra para cima e para baixo) --}}
                        <div id="markerEnd" 
                             class="absolute pointer-events-none transition-none flex flex-col items-center justify-between"
                             style="left: 100%; top: -5px; bottom: -5px; transform: translateX(-50%); z-index: 25;">
                            {{-- Aba Superior --}}
                            <div class="w-3.5 h-2.5 bg-cyan-400 rounded-t-sm shadow-md flex items-center justify-center">
                                <div class="w-1.5 h-1 bg-cyan-950 rounded-xs"></div>
                            </div>
                            {{-- Risco Vertical Fluorescente --}}
                            <div class="w-1 flex-1 bg-cyan-300 shadow-[0_0_8px_rgba(34,211,238,0.95)]"></div>
                            {{-- Aba Inferior --}}
                            <div class="w-3.5 h-2.5 bg-cyan-400 rounded-b-sm shadow-md flex items-center justify-center">
                                <div class="w-1.5 h-1 bg-cyan-950 rounded-xs"></div>
                            </div>
                        </div>

                        {{-- RISCO VERTICAL 1 DA TESOURA (Ponto 1) --}}
                        <div id="markerMiddleCut1" 
                             class="hidden absolute pointer-events-none transition-none flex flex-col items-center justify-between"
                             style="left: 0%; top: -6px; bottom: -6px; transform: translateX(-50%); z-index: 28;">
                            <div class="w-3.5 h-3 bg-amber-400 text-amber-950 text-[8px] font-black rounded-t-sm shadow flex items-center justify-center">
                                <i class="fas fa-scissors"></i>
                            </div>
                            <div class="w-1 flex-1 bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.95)]"></div>
                            <div class="w-2.5 h-2 bg-amber-400 rounded-b-sm shadow"></div>
                        </div>

                        {{-- RISCO VERTICAL 2 DA TESOURA (Ponto 2) --}}
                        <div id="markerMiddleCut2" 
                             class="hidden absolute pointer-events-none transition-none flex flex-col items-center justify-between"
                             style="left: 0%; top: -6px; bottom: -6px; transform: translateX(-50%); z-index: 28;">
                            <div class="w-3.5 h-3 bg-red-400 text-red-950 text-[8px] font-black rounded-t-sm shadow flex items-center justify-center">
                                <i class="fas fa-scissors"></i>
                            </div>
                            <div class="w-1 flex-1 bg-red-400 shadow-[0_0_8px_rgba(248,113,113,0.95)]"></div>
                            <div class="w-2.5 h-2 bg-red-400 rounded-b-sm shadow"></div>
                        </div>

                        {{-- Agulha de Reprodução (Playhead) --}}
                        <div id="trackPlayhead" 
                             class="absolute top-0 bottom-0 w-1 bg-white shadow-2xl pointer-events-none transform -translate-x-1/2 transition-none z-30" 
                             style="left: 0%;">
                            <div class="w-3.5 h-3.5 bg-pink-500 rounded-full border-2 border-white -mt-1 -ml-1.25 shadow-md"></div>
                        </div>
                    </div>

                    {{-- Minutagem Direta na Barra (Início, Tempo Atual e Fim) --}}
                    <div class="flex items-center justify-between text-xs font-mono text-gray-300 px-0.5 font-bold">
                        <span id="barStartMinutagem" class="text-cyan-400 font-extrabold text-xs sm:text-sm">00:00</span>
                        <div class="flex items-center gap-1 font-semibold text-gray-400 text-xs sm:text-sm">
                            <span id="modalCurrentTimeText" class="text-white font-black">00:00</span>
                            <span>/</span>
                            <span id="modalTotalTimeText" class="text-gray-300">00:00</span>
                        </div>
                        <span id="barEndMinutagem" class="text-cyan-400 font-extrabold text-xs sm:text-sm">00:00</span>
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
let middleCut1 = null;
let middleCutStart = null;
let middleCutEnd = null;
let originalMiddleCutStart = null;
let originalMiddleCutEnd = null;
let isUsingRecording = false;
let toastTimeout = null;
let isScrubbing = false;

function getWindowBounds() {
    let wStart = originalStart;
    let wEnd = originalEnd;

    if (activeStart < wStart) wStart = activeStart;
    if (activeEnd > wEnd) wEnd = activeEnd;

    const player = document.getElementById('modalVideoPlayer');
    if (!isUsingRecording && player && player.duration && !isNaN(player.duration) && player.duration > 0) {
        wStart = originalStart;
        wEnd = originalStart + player.duration;
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

    middleCut1 = null;
    middleCutStart = (item.middle_cut_start_sec !== null && item.middle_cut_start_sec !== undefined && item.middle_cut_start_sec !== '') ? Number(item.middle_cut_start_sec) : null;
    middleCutEnd = (item.middle_cut_end_sec !== null && item.middle_cut_end_sec !== undefined && item.middle_cut_end_sec !== '') ? Number(item.middle_cut_end_sec) : null;
    originalMiddleCutStart = middleCutStart;
    originalMiddleCutEnd = middleCutEnd;

    const badgeEl = document.getElementById('modalSourceBadge');

    // Prioriza o vídeo cortado com legendas dinâmicas embutidas para visualização
    if (item.video_cut_url && item.video_cut_url.trim() !== '') {
        isUsingRecording = false;
        player.src = item.video_cut_url;
        if (badgeEl) {
            badgeEl.classList.remove('hidden');
            badgeEl.innerHTML = '<i class="fas fa-closed-captioning mr-1 text-emerald-400"></i>Com Legenda';
            badgeEl.className = 'text-[10px] px-2 py-0.5 rounded-full font-extrabold bg-emerald-900/80 text-emerald-200 border border-emerald-500 shrink-0';
        }
    } else if (liveGlobalRecordingUrl && liveGlobalRecordingUrl.trim() !== '') {
        isUsingRecording = true;
        player.src = liveGlobalRecordingUrl;
        if (badgeEl) {
            badgeEl.classList.remove('hidden');
            badgeEl.innerHTML = '<i class="fas fa-film mr-1 text-amber-400"></i>Gravação Bruta';
            badgeEl.className = 'text-[10px] px-2 py-0.5 rounded-full font-extrabold bg-amber-900/80 text-amber-200 border border-amber-500 shrink-0';
        }
    } else {
        isUsingRecording = false;
        player.src = '';
        if (badgeEl) badgeEl.classList.add('hidden');
    }

    if (titleEl) titleEl.textContent = item.item_name || 'Produto';
    if (codeEl) codeEl.textContent = '#' + (item.codigo_live || item.item_sku || '');

    renderTimeBadges();
    updatePlayIcon(false);

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.style.display = 'flex';
    initTimelineEvents();

    player.onloadedmetadata = function() {
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

    // Se já tinha um corte completo no meio, permite iniciar um novo corte no ponto atual
    if (middleCutStart !== null && middleCutEnd !== null) {
        middleCutStart = null;
        middleCutEnd = null;
        middleCut1 = currentSec;
        showToast(`✂️ Novo corte: 1º ponto marcado em ${formatTime(currentSec)}. Clique novamente na tesoura para marcar o 2º ponto.`);
        renderTimeBadges();
        return;
    }

    // 1º Clique da Tesoura
    if (middleCut1 === null) {
        if (currentSec <= activeStart || currentSec >= activeEnd) {
            showToast('⚠️ O corte no meio deve estar entre o Início e o Fim!');
            return;
        }
        middleCut1 = currentSec;
        showToast(`✂️ 1º Ponto marcado em ${formatTime(middleCut1)}. Avance o vídeo e clique na tesoura para marcar o 2º ponto.`);
        renderTimeBadges();
    } else {
        // 2º Clique da Tesoura
        if (Math.abs(currentSec - middleCut1) < 0.3) {
            showToast('⚠️ Escolha um intervalo maior que 0.3s para o corte no meio.');
            return;
        }

        const pMin = Math.max(activeStart, Math.min(middleCut1, currentSec));
        const pMax = Math.min(activeEnd, Math.max(middleCut1, currentSec));

        if (pMax <= pMin) {
            showToast('⚠️ Intervalo de corte inválido.');
            middleCut1 = null;
            renderTimeBadges();
            return;
        }

        middleCutStart = pMin;
        middleCutEnd = pMax;
        middleCut1 = null;

        const cutDuration = (middleCutEnd - middleCutStart).toFixed(1);
        showToast(`✂️ Intervalo de ${formatTime(middleCutStart)} a ${formatTime(middleCutEnd)} (-${cutDuration}s) cortado!`);
        renderTimeBadges();
    }
}

function clearMiddleCut(notify = true) {
    middleCut1 = null;
    middleCutStart = null;
    middleCutEnd = null;
    if (notify) {
        showToast('✂️ Corte no meio desfeito.');
    }
    renderTimeBadges();
}

function renderTimeBadges() {
    const barStartMinutagem = document.getElementById('barStartMinutagem');
    const barEndMinutagem = document.getElementById('barEndMinutagem');
    const durSecEl = document.getElementById('badgeDurationSec');
    const editedFlag = document.getElementById('badgeEditedFlag');
    const btnScissors = document.getElementById('btnMiddleCutScissors');
    const textScissors = document.getElementById('textScissors');
    const btnClearScissors = document.getElementById('btnClearMiddleCut');

    let baseDuration = Math.max(0, activeEnd - activeStart);
    let cutLoss = (middleCutStart !== null && middleCutEnd !== null) ? Math.max(0, middleCutEnd - middleCutStart) : 0;
    let effectiveDuration = Math.max(0, Math.round(baseDuration - cutLoss));

    if (barStartMinutagem) barStartMinutagem.textContent = formatTime(activeStart);
    if (barEndMinutagem) barEndMinutagem.textContent = formatTime(activeEnd);
    if (durSecEl) durSecEl.textContent = `${effectiveDuration}s`;

    if (textScissors && btnScissors) {
        if (middleCut1 !== null) {
            textScissors.textContent = '2º Ponto ✂️';
            btnScissors.className = 'px-2.5 sm:px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 active:scale-95 text-amber-950 font-black text-xs flex items-center gap-1.5 transition shadow-md cursor-pointer border border-amber-300 animate-pulse';
            if (btnClearScissors) btnClearScissors.classList.remove('hidden');
        } else if (middleCutStart !== null && middleCutEnd !== null) {
            textScissors.textContent = `-${cutLoss.toFixed(1)}s (Meio)`;
            btnScissors.className = 'px-2.5 sm:px-3 py-1.5 rounded-xl bg-red-900/90 hover:bg-red-800 active:scale-95 text-red-100 font-black text-xs flex items-center gap-1.5 transition shadow-xs cursor-pointer border border-red-500';
            if (btnClearScissors) btnClearScissors.classList.remove('hidden');
        } else {
            textScissors.textContent = 'Cortar Meio';
            btnScissors.className = 'px-2.5 sm:px-3 py-1.5 rounded-xl bg-gray-800 hover:bg-gray-700 active:scale-95 text-amber-300 font-extrabold text-xs flex items-center gap-1.5 transition shadow-xs cursor-pointer border border-amber-500/50';
            if (btnClearScissors) btnClearScissors.classList.add('hidden');
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

    updateVisualTimeline();
}

function updateVisualTimeline() {
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

    // 2. Faixa azul ativa entre Início e Fim
    if (trackBlueActive) {
        trackBlueActive.style.width = `${Math.max(0, endPct - startPct)}%`;
    }

    // 3. Faixa escura depois do fim
    if (trackDarkAfter) {
        trackDarkAfter.style.width = `${Math.max(0, 100 - endPct)}%`;
    }

    // 4. Riscos verticais estendidos (maiores para cima e para baixo)
    if (markerStart) {
        markerStart.style.left = `${startPct}%`;
    }
    if (markerEnd) {
        markerEnd.style.left = `${endPct}%`;
    }

    // 5. Marcador 1 da Tesoura (se clicou 1 vez)
    if (markerCut1) {
        if (middleCut1 !== null) {
            const cut1Pct = Math.min(100, Math.max(0, ((middleCut1 - wStart) / span) * 100));
            markerCut1.style.left = `${cut1Pct}%`;
            markerCut1.classList.remove('hidden');
        } else {
            markerCut1.classList.add('hidden');
        }
    }

    // 6. Intervalo cortado da Tesoura (se clicou 2 vezes)
    if (trackDarkMiddleCut && markerCut2) {
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

            // Exibe os marcadores de início e fim do corte no meio
            if (markerCut1) {
                markerCut1.style.left = `${mStartPct}%`;
                markerCut1.classList.remove('hidden');
            }
            markerCut2.style.left = `${mEndPct}%`;
            markerCut2.classList.remove('hidden');
        } else {
            trackDarkMiddleCut.classList.add('hidden');
            trackDarkMiddleCut.classList.remove('flex');
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
            const badgeEl = document.getElementById('modalSourceBadge');
            if (badgeEl) {
                badgeEl.classList.remove('hidden');
                badgeEl.innerHTML = '<i class="fas fa-edit mr-1 text-amber-400"></i>Modo Ajuste';
                badgeEl.className = 'text-[10px] px-2 py-0.5 rounded-full font-extrabold bg-amber-900/80 text-amber-200 border border-amber-500 shrink-0';
            }
        }
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
        activeEnd = activeEnd + 10;
        if (!isUsingRecording && liveGlobalRecordingUrl && liveGlobalRecordingUrl.trim() !== '') {
            isUsingRecording = true;
            player.src = liveGlobalRecordingUrl;
            const badgeEl = document.getElementById('modalSourceBadge');
            if (badgeEl) {
                badgeEl.classList.remove('hidden');
                badgeEl.innerHTML = '<i class="fas fa-edit mr-1 text-amber-400"></i>Modo Ajuste';
                badgeEl.className = 'text-[10px] px-2 py-0.5 rounded-full font-extrabold bg-amber-900/80 text-amber-200 border border-amber-500 shrink-0';
            }
        }
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

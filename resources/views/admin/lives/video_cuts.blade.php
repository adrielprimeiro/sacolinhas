@extends('layouts.app')

@section('title', 'Cortes de Vídeo da Live')

@section('content')
<div class="container mx-auto p-4 sm:p-6 max-w-7xl">
    
    <!-- Top Header Bar -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <a href="{{ route('admin.live-chat.dashboard', ['live_id' => $live->id]) }}" class="w-10 h-10 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-700 transition shadow-xs shrink-0" title="Voltar ao Painel da Live">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-800 border border-indigo-200">
                        Cortes & Vídeos
                    </span>
                    <span class="text-xs text-gray-500 font-bold">
                        {{ $live->data ? $live->data->format('d/m/Y') : 'Data n/d' }} &bull; {{ $live->tipo_live_formatado }}
                    </span>
                    @if($live->ativo)
                        <span class="bg-red-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full animate-pulse flex items-center gap-1 shadow-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span> AO VIVO
                        </span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-3 mt-1.5">
                    <h1 class="text-xl sm:text-2xl font-black text-gray-900 flex items-center gap-2">
                        <i class="fas fa-film text-indigo-600"></i>
                        <span>Fatiador de Vídeos</span>
                    </h1>

                    <!-- Seletor de Live -->
                    @if(isset($lives) && count($lives) > 0)
                        <div class="flex items-center gap-2 bg-indigo-50/70 border border-indigo-200 rounded-xl px-2.5 py-1 shadow-xs">
                            <label for="live-selector-cuts" class="text-xs font-black text-indigo-900 flex items-center gap-1">
                                <i class="fas fa-video text-indigo-600"></i> Live:
                            </label>
                            <select 
                                id="live-selector-cuts" 
                                onchange="if(this.value) window.location.href = '/admin/lives/' + this.value + '/cortes'"
                                class="text-xs font-black text-gray-900 bg-white border border-indigo-200 rounded-lg px-2 py-1 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none cursor-pointer max-w-[200px] sm:max-w-[260px] truncate"
                            >
                                @foreach($lives as $l)
                                    <option value="{{ $l->id }}" {{ $l->id == $live->id ? 'selected' : '' }}>
                                        #{{ $l->id }} - {{ $l->nome ?: 'Live de ' . date('d/m', strtotime($l->created_at)) }} {{ $l->ativo ? '🔴' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Badges / Stats & Ação Principal -->
        <div class="flex flex-wrap items-center gap-2.5">
            <div class="bg-gray-50 border border-gray-200 px-3.5 py-1.5 rounded-xl flex items-center gap-3 shadow-xs">
                <div class="text-center">
                    <div class="text-[10px] uppercase font-bold text-gray-500">Itens</div>
                    <div id="stat-total-items" class="text-sm font-black text-gray-900">{{ $stats['total_items'] }}</div>
                </div>
                <div class="w-px h-5 bg-gray-200"></div>
                <div class="text-center">
                    <div class="text-[10px] uppercase font-bold text-teal-700">Minutados (IA)</div>
                    <div id="stat-items-with-cuts" class="text-sm font-black text-teal-800">{{ $stats['items_with_cuts'] }}</div>
                </div>
                <div class="w-px h-5 bg-gray-200"></div>
                <div class="text-center">
                    <div class="text-[10px] uppercase font-bold text-green-700">Vídeos Prontos</div>
                    <div id="stat-items-rendered" class="text-sm font-black text-green-800">{{ $stats['items_rendered'] }}</div>
                </div>
            </div>

            <!-- Filtro de Início por Código -->
            <div class="flex items-center gap-1 bg-white border border-gray-200 rounded-xl px-2.5 py-1.5 shadow-xs" title="Defina um código para processar apenas dele em diante (ex: 20)">
                <span class="text-[10px] uppercase font-extrabold text-gray-500 whitespace-nowrap">A partir do #:</span>
                <input type="number" id="global-start-code" placeholder="Tudo" min="1" class="w-14 px-1 py-0.5 text-xs font-black text-center bg-gray-50 border border-gray-300 rounded-lg focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>

            <button type="button" onclick="autoDetectWithAI()" id="btn-auto-detect-header" class="bg-teal-600 hover:bg-teal-700 text-white font-extrabold px-3 py-2 rounded-xl text-xs shadow-md flex items-center gap-1.5 transition active:scale-95 cursor-pointer" style="background-color: #0d9488; color: #ffffff !important;" title="Detectar minutagens inteligentes com Severino IA">
                <i class="fas fa-brain text-white"></i>
                <span style="color: #ffffff !important; font-weight: 800;">Minutar com IA</span>
            </button>

            <button type="button" onclick="generateAllClips()" id="btn-batch-clips" class="bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold px-3 py-2 rounded-xl text-xs shadow-md flex items-center gap-1.5 transition active:scale-95 cursor-pointer" style="background-color: #4f46e5; color: #ffffff !important;">
                <i class="fas fa-scissors text-white"></i>
                <span style="color: #ffffff !important; font-weight: 800;">Gerar Cortes (FFmpeg)</span>
            </button>

            <button type="button" onclick="triggerAutoProcess()" id="btn-auto-process" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-extrabold px-3 py-2 rounded-xl text-xs shadow-md flex items-center gap-1.5 transition active:scale-95 cursor-pointer">
                <i class="fab fa-instagram text-white"></i>
                <span class="text-white font-black">Auto-Processar Instagram</span>
            </button>
        </div>
    </div>

    <!-- LINHA DO TEMPO COMPACTA (TIMELINE DE PONTOS) -->
    @php
        $step1Done = !empty($recordingUrl);
        $step2Done = !empty($transcriptionData) && count($transcriptionData) > 0;
        $step3Done = ($stats['total_items'] > 0 && $stats['items_with_cuts'] >= $stats['total_items']);
        $step3Partial = ($stats['items_with_cuts'] > 0 && $stats['items_with_cuts'] < $stats['total_items']);
        $step4Done = ($stats['total_items'] > 0 && $stats['items_rendered'] >= $stats['total_items']);
        $step4Partial = ($stats['items_rendered'] > 0 && $stats['items_rendered'] < $stats['total_items']);
    @endphp

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-3 sm:p-4 mb-6" style="background-color: #ffffff;">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            
            <!-- TRACK DA LINHA DO TEMPO -->
            <div class="flex-1 flex flex-wrap sm:flex-nowrap items-center justify-between gap-2 sm:gap-3">
                
                <!-- PONTO 1: VÍDEO -->
                <div class="flex items-center gap-2 cursor-pointer group p-1.5 rounded-xl hover:bg-gray-50 transition" onclick="triggerAutoProcess()" title="Clique para Auto-Processar gravação do Instagram">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black shrink-0 shadow-xs"
                         style="{{ $step1Done ? 'background-color: #16a34a; color: #ffffff !important;' : 'background-color: #d97706; color: #ffffff !important;' }}">
                        {!! $step1Done ? '<i class="fas fa-check"></i>' : '1' !!}
                    </div>
                    <div>
                        <div class="text-xs font-bold leading-tight" style="color: #111827;">1. Vídeo da Live</div>
                        <div class="text-[11px] font-semibold" style="{{ $step1Done ? 'color: #15803d;' : 'color: #b45309;' }}">
                            {{ $step1Done ? '✓ Gravado' : 'Pendente' }}
                        </div>
                    </div>
                </div>

                <!-- LINHA CONECTORA 1-2 -->
                <div class="hidden sm:block flex-1 h-0.5 mx-1" style="{{ $step1Done ? 'background-color: #16a34a;' : 'background-color: #e5e7eb;' }}"></div>

                <!-- PONTO 2: TRANSCRIÇÃO -->
                <div class="flex items-center gap-2 cursor-pointer group p-1.5 rounded-xl hover:bg-gray-50 transition" onclick="transcribeAudioWithAI()" title="Clique para transcrever áudio com Whisper">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black shrink-0 shadow-xs"
                         style="{{ $step2Done ? 'background-color: #16a34a; color: #ffffff !important;' : ($step1Done ? 'background-color: #4f46e5; color: #ffffff !important;' : 'background-color: #9ca3af; color: #ffffff !important;') }}">
                        {!! $step2Done ? '<i class="fas fa-check"></i>' : '2' !!}
                    </div>
                    <div>
                        <div class="text-xs font-bold leading-tight" style="color: #111827;">2. Transcrição</div>
                        <div class="text-[11px] font-semibold" style="{{ $step2Done ? 'color: #15803d;' : 'color: #4338ca;' }}">
                            {{ $step2Done ? '✓ ' . count($transcriptionData) . ' falas' : 'Pendente' }}
                        </div>
                    </div>
                </div>

                <!-- LINHA CONECTORA 2-3 -->
                <div class="hidden sm:block flex-1 h-0.5 mx-1" style="{{ $step2Done ? 'background-color: #16a34a;' : 'background-color: #e5e7eb;' }}"></div>

                <!-- PONTO 3: MINUTAGEM IA -->
                <div class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-gray-50 transition">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black shrink-0 shadow-xs cursor-pointer"
                         onclick="autoDetectWithAI()"
                         title="Clique para detectar minutagens com Severino IA"
                         style="{{ $step3Done ? 'background-color: #16a34a; color: #ffffff !important;' : ($step3Partial ? 'background-color: #0d9488; color: #ffffff !important;' : 'background-color: #9ca3af; color: #ffffff !important;') }}">
                        {!! $step3Done ? '<i class="fas fa-check"></i>' : '3' !!}
                    </div>
                    <div>
                        <div class="text-xs font-bold leading-tight cursor-pointer" onclick="autoDetectWithAI()" style="color: #111827;">3. Minutagem IA</div>
                        <div class="text-[11px] font-semibold" style="{{ $step3Done ? 'color: #15803d;' : ($step3Partial ? 'color: #0f766e;' : 'color: #4b5563;') }}">
                            {{ $stats['items_with_cuts'] }}/{{ $stats['total_items'] }} minutados
                        </div>
                        @if(($stats['items_without_cuts'] ?? 0) > 0)
                            <button type="button" onclick="setItemsFilter('unminuted')" class="text-[10px] font-black px-1.5 py-0.5 rounded-md mt-0.5 inline-flex items-center gap-1 cursor-pointer transition active:scale-95 shadow-2xs" style="background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a;" title="Filtrar apenas os itens que não foram minutados">
                                <i class="fas fa-exclamation-circle text-amber-600"></i> Ver {{ $stats['items_without_cuts'] }} pendente(s)
                            </button>
                        @endif
                    </div>
                </div>

                <!-- LINHA CONECTORA 3-4 -->
                <div class="hidden sm:block flex-1 h-0.5 mx-1" style="{{ $step3Done ? 'background-color: #16a34a;' : 'background-color: #e5e7eb;' }}"></div>

                <!-- PONTO 4: FATIAMENTO FFmpeg -->
                <div class="flex items-center gap-2 cursor-pointer group p-1.5 rounded-xl hover:bg-gray-50 transition" onclick="generateAllClips()" title="Clique para gerar todos os cortes">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black shrink-0 shadow-xs"
                         style="{{ $step4Done ? 'background-color: #16a34a; color: #ffffff !important;' : ($step4Partial ? 'background-color: #4f46e5; color: #ffffff !important;' : 'background-color: #9ca3af; color: #ffffff !important;') }}">
                        {!! $step4Done ? '<i class="fas fa-check"></i>' : '4' !!}
                    </div>
                    <div>
                        <div class="text-xs font-bold leading-tight" style="color: #111827;">4. Fatiamento</div>
                        <div class="text-[11px] font-semibold" style="{{ $step4Done ? 'color: #15803d;' : ($step4Partial ? 'color: #4338ca;' : 'color: #4b5563;') }}">
                            {{ $stats['items_rendered'] }}/{{ $stats['total_items'] }} prontos
                        </div>
                    </div>
                </div>

            </div>

            <!-- AÇÕES RÁPIDAS NA PONTA DIREITA -->
            <div class="flex items-center gap-2 pt-2 lg:pt-0 lg:pl-3 lg:border-l lg:border-gray-200 shrink-0">
                <a href="{{ route('admin.lives.cortes.social.live', ['liveId' => $live->id]) }}" 
                   class="inline-flex items-center gap-1.5 font-bold px-3 py-1.5 rounded-xl text-xs transition shadow-xs"
                   style="background-color: #fdf2f8; color: #be185d !important; border: 1px solid #fbcfe8;">
                    <i class="fas fa-film text-pink-600"></i>
                    <span>Feed Redes</span>
                </a>
            </div>

        </div>
    </div>

    <!-- BANNER DE PROGRESSO IA ASSÍNCRONO -->
    <div id="ai-process-progress-box" class="hidden mb-6 bg-indigo-50 text-indigo-950 rounded-2xl p-5 shadow-xl border border-indigo-200">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-3">
                <i class="fas fa-robot text-2xl text-indigo-600 animate-pulse"></i>
                <div>
                    <h4 class="text-sm font-black text-indigo-900">Processamento Automático com IA</h4>
                    <p id="ai-process-text" class="text-xs text-indigo-700 mt-0.5">Iniciando extração e transcrição do áudio...</p>
                </div>
            </div>
            <span id="ai-process-pct" class="text-lg font-black text-indigo-600 font-mono">0%</span>
        </div>
        <div class="w-full bg-indigo-100 rounded-full h-3.5 overflow-hidden border border-indigo-200">
            <div id="ai-process-bar" class="h-full w-0 transition-all duration-300 rounded-full" style="background: linear-gradient(90deg, #4f46e5 0%, #06b6d4 100%);"></div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">


        <!-- COLUNA ESQUERDA: PLAYER DE VÍDEO & TRANSCRIÇÃO (LARGURA 5) -->
        <div class="lg:col-span-5 space-y-5">
            
            <!-- Card Player de Vídeo -->
            <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm sticky top-4 max-h-[calc(100vh-2rem)] overflow-y-auto">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-100">
                    <h3 class="font-black text-sm text-gray-800 flex items-center gap-2">
                        <i class="fas fa-play-circle text-indigo-600"></i>
                        Gravação Completa da Live
                    </h3>
                    <button type="button" onclick="toggleUploadModal()" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-upload"></i> {{ $recordingUrl ? 'Trocar Vídeo' : 'Enviar Vídeo' }}
                    </button>
                </div>

                <!-- Video Container (Adaptado para Vídeos Verticais e Horizontais) -->
                <div class="relative bg-black rounded-xl overflow-hidden flex items-center justify-center border border-gray-300" style="max-height: 42vh; height: 360px;">
                    @if($recordingUrl)
                        <video id="live-main-player" src="{{ $recordingUrl }}" controls class="w-full h-full object-contain max-h-[42vh]" preload="metadata"></video>
                    @else
                        <div id="no-video-placeholder" class="text-center p-6 text-gray-400">
                            <i class="fas fa-video-slash text-4xl mb-3 text-gray-300"></i>
                            <p class="text-xs font-bold text-gray-700">Nenhum arquivo de vídeo carregado.</p>
                            <p class="text-[11px] text-gray-500 mt-1">Faça o upload do .mp4 ou defina o caminho da gravação do OBS.</p>
                            <button type="button" onclick="toggleUploadModal()" class="mt-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2 rounded-xl transition shadow" style="background-color: #4f46e5; color: #ffffff !important;">
                                <i class="fas fa-cloud-upload-alt mr-1"></i> Carregar Gravação
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Controles Avançados do Player -->
                <div class="mt-3 bg-gray-50 p-3 rounded-xl border border-gray-200 space-y-2.5">
                    
                    <!-- Linha 1: Display de Tempo & Pulos Rápidos -->
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <!-- Play/Pause & Tempo -->
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="togglePlayPause()" id="btn-play-pause" title="Play / Pause (Espaço)" class="w-8 h-8 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white flex items-center justify-center text-xs shadow-xs transition active:scale-95 cursor-pointer">
                                <i class="fas fa-play" id="icon-play-pause"></i>
                            </button>
                            <div class="flex items-center gap-1 font-mono text-xs font-black">
                                <span class="text-indigo-700" id="player-current-time">00:00:00</span>
                                <span class="text-gray-400">/</span>
                                <span class="text-gray-500 text-[11px]" id="player-duration">00:00:00</span>
                            </div>
                        </div>

                        <!-- Pulos de Tempo -->
                        <div class="flex items-center gap-1">
                            <button type="button" onclick="jumpVideo(-10)" title="Voltar 10s" class="px-2 py-1 bg-white hover:bg-gray-200 text-gray-700 font-extrabold text-[10px] rounded-lg border border-gray-300 transition active:scale-95 cursor-pointer shadow-2xs">
                                -10s
                            </button>
                            <button type="button" onclick="jumpVideo(-5)" title="Voltar 5s (←)" class="px-2 py-1 bg-white hover:bg-gray-200 text-gray-700 font-extrabold text-[10px] rounded-lg border border-gray-300 transition active:scale-95 cursor-pointer shadow-2xs">
                                -5s
                            </button>
                            <button type="button" onclick="jumpVideo(-1)" title="Voltar 1s (Shift+←)" class="px-1.5 py-1 bg-white hover:bg-gray-200 text-gray-700 font-extrabold text-[10px] rounded-lg border border-gray-300 transition active:scale-95 cursor-pointer shadow-2xs">
                                -1s
                            </button>
                            <button type="button" onclick="jumpVideo(1)" title="Avançar 1s (Shift+→)" class="px-1.5 py-1 bg-white hover:bg-gray-200 text-gray-700 font-extrabold text-[10px] rounded-lg border border-gray-300 transition active:scale-95 cursor-pointer shadow-2xs">
                                +1s
                            </button>
                            <button type="button" onclick="jumpVideo(5)" title="Avançar 5s (→)" class="px-2 py-1 bg-white hover:bg-gray-200 text-gray-700 font-extrabold text-[10px] rounded-lg border border-gray-300 transition active:scale-95 cursor-pointer shadow-2xs">
                                +5s
                            </button>
                            <button type="button" onclick="jumpVideo(10)" title="Avançar 10s" class="px-2 py-1 bg-white hover:bg-gray-200 text-gray-700 font-extrabold text-[10px] rounded-lg border border-gray-300 transition active:scale-95 cursor-pointer shadow-2xs">
                                +10s
                            </button>
                        </div>
                    </div>

                    <!-- Linha 2: Velocidade de Reprodução & Botões de Marcação -->
                    <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-gray-200">
                        <!-- Seletor de Velocidade -->
                        <div class="flex items-center gap-1">
                            <span class="text-[10px] font-bold text-gray-500 uppercase mr-0.5">Velocidade:</span>
                            @foreach([0.5, 0.75, 1.0, 1.25, 1.5, 2.0] as $speed)
                                <button type="button" onclick="setPlaybackRate({{ $speed }})" id="btn-speed-{{ str_replace('.', '_', $speed) }}" class="speed-btn px-1.5 py-0.5 rounded text-[10px] font-mono font-bold border transition cursor-pointer {{ $speed == 1.0 ? 'bg-indigo-600 text-white border-indigo-600 shadow-2xs' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100' }}">
                                    {{ $speed }}x
                                </button>
                            @endforeach
                        </div>

                        <!-- Botões de Marcação Rápida -->
                        <div class="flex items-center gap-1.5">
                            <button type="button" onclick="markCurrentTimeToActiveItem('start')" title="Marcar tempo atual como Início (I)" class="bg-green-100 hover:bg-green-200 text-green-800 border border-green-300 text-xs font-extrabold px-2.5 py-1 rounded-lg transition flex items-center gap-1 active:scale-95 cursor-pointer shadow-2xs">
                                <i class="fas fa-step-forward text-[10px]"></i> Início
                            </button>
                            <button type="button" onclick="markCurrentTimeToActiveItem('end')" title="Marcar tempo atual como Fim (O)" class="bg-amber-100 hover:bg-amber-200 text-amber-800 border border-amber-300 text-xs font-extrabold px-2.5 py-1 rounded-lg transition flex items-center gap-1 active:scale-95 cursor-pointer shadow-2xs">
                                <i class="fas fa-stop text-[10px]"></i> Fim
                            </button>
                        </div>
                    </div>

                    <!-- Linha 3: Dica de Atalhos de Teclado -->
                    <div class="pt-1.5 border-t border-gray-200 flex items-center justify-between text-[10px] text-gray-500 font-medium">
                        <span class="flex items-center gap-1 font-semibold text-gray-600">
                            <i class="fas fa-keyboard text-indigo-500"></i> Atalhos:
                        </span>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span><kbd class="px-1 py-0.5 bg-white border border-gray-300 rounded text-[9px] font-mono shadow-2xs">Espaço</kbd> Play/Pause</span>
                            <span><kbd class="px-1 py-0.5 bg-white border border-gray-300 rounded text-[9px] font-mono shadow-2xs">← / →</kbd> ±5s</span>
                            <span><kbd class="px-1 py-0.5 bg-white border border-gray-300 rounded text-[9px] font-mono shadow-2xs">I</kbd> Início</span>
                            <span><kbd class="px-1 py-0.5 bg-white border border-gray-300 rounded text-[9px] font-mono shadow-2xs">O</kbd> Fim</span>
                            <span><kbd class="px-1 py-0.5 bg-white border border-gray-300 rounded text-[9px] font-mono shadow-2xs">P</kbd> Prévia</span>
                            <span><kbd class="px-1 py-0.5 bg-white border border-gray-300 rounded text-[9px] font-mono shadow-2xs">C</kbd> Foto Capa</span>
                        </div>
                    </div>

                </div>

                <!-- Ações IA de Transcrição -->
                <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between gap-2">
                    <button type="button" onclick="autoDetectWithAI()" id="btn-auto-detect" class="flex-1 bg-teal-600 hover:bg-teal-700 text-white font-extrabold py-2 px-3 rounded-xl text-xs transition shadow flex items-center justify-center gap-1.5 cursor-pointer active:scale-95" style="background-color: #0d9488; color: #ffffff !important;">
                        <i class="fas fa-wand-magic-sparkles"></i>
                        <span style="color: #ffffff !important; font-weight: 800;">Auto-Detectar com IA</span>
                    </button>
                    <button type="button" onclick="toggleTranscriptionModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-3 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer border border-gray-300">
                        <i class="fas fa-file-alt text-gray-600"></i>
                        <span>Transcrição</span>
                    </button>
                </div>
            </div>

            <!-- Card de Dicas de Padrão de Fala -->
            <div class="bg-indigo-50 rounded-2xl border border-indigo-200 p-4 text-xs text-indigo-900 leading-relaxed shadow-xs">
                <div class="font-black text-indigo-950 flex items-center gap-1.5 mb-1.5 text-sm">
                    <i class="fas fa-lightbulb text-amber-500"></i>
                    Como funciona o corte inteligente:
                </div>
                <p class="text-xs text-indigo-800 leading-normal">
                    O algoritmo localiza a frase em que o <b>código</b> foi falado. Ele varre para trás até o início da apresentação da peça (gatilhos como <i>"Olha essa peça..."</i>) e varre para frente até o fechamento (<i>"Passando..."</i> ou entrega aos bastidores).
                </p>
            </div>

        </div>

        <!-- COLUNA DIREITA: LISTA DE ITENS & MINUTAGEM (LARGURA 7) -->
        <div class="lg:col-span-7 space-y-4">
            
            <!-- TOOLBAR DE FILTROS E BUSCA DOS ITENS -->
            <div class="bg-white p-3.5 rounded-2xl border border-gray-200 shadow-sm space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="text-xs font-black text-gray-900 flex items-center gap-2">
                        <i class="fas fa-tshirt text-indigo-600"></i>
                        <span>Itens da Live (<span id="items-visible-counter">{{ count($liveItems) }}</span> / {{ count($liveItems) }})</span>
                    </div>
                    
                    <!-- Busca rápida -->
                    <div class="relative w-full sm:w-56">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-gray-400">
                            <i class="fas fa-search text-[11px]"></i>
                        </div>
                        <input type="text" 
                               id="items-filter-search-input"
                               oninput="handleItemSearch(this.value)"
                               placeholder="Buscar #cód, produto..." 
                               class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block pl-7 p-1.5 font-medium shadow-2xs">
                    </div>
                </div>

                <!-- Abas de Filtro de Itens -->
                <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-gray-100">
                    <button type="button" 
                            onclick="setItemsFilter('all')"
                            data-filter="all"
                            class="item-filter-tab px-2.5 py-1 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1 shadow-2xs"
                            style="background-color: #1f2937; color: #ffffff;">
                        <span>Todos</span>
                        <span class="px-1 py-0.2 rounded-full text-[10px] opacity-80">{{ count($liveItems) }}</span>
                    </button>

                    <button type="button" 
                            onclick="setItemsFilter('unminuted')"
                            data-filter="unminuted"
                            class="item-filter-tab px-2.5 py-1 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1 shadow-2xs {{ ($stats['items_without_cuts'] ?? 0) > 0 ? 'animate-pulse' : '' }}"
                            style="background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                        <i class="fas fa-exclamation-triangle text-amber-600"></i>
                        <span>Não Minutados</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black" style="background-color: #f59e0b; color: #ffffff;">{{ $stats['items_without_cuts'] ?? 0 }}</span>
                    </button>

                    <button type="button" 
                            onclick="setItemsFilter('minuted')"
                            data-filter="minuted"
                            class="item-filter-tab px-2.5 py-1 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1 shadow-2xs"
                            style="background-color: #f3f4f6; color: #374151;">
                        <i class="fas fa-check-circle text-teal-600"></i>
                        <span>Minutados</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-gray-200 text-gray-700">{{ $stats['items_with_cuts'] }}</span>
                    </button>

                    <button type="button" 
                            onclick="setItemsFilter('rendered')"
                            data-filter="rendered"
                            class="item-filter-tab px-2.5 py-1 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1 shadow-2xs"
                            style="background-color: #f3f4f6; color: #374151;">
                        <i class="fas fa-video text-green-600"></i>
                        <span>Vídeos Prontos</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-gray-200 text-gray-700">{{ $stats['items_rendered'] }}</span>
                    </button>
                </div>
            </div>

            <!-- Banner Informativo quando filtrado por Não Minutados -->
            <div id="filter-unminuted-banner" class="hidden p-3.5 bg-amber-50 border border-amber-300 rounded-2xl text-xs text-amber-950 shadow-xs">
                <div class="font-black flex items-center gap-2 text-amber-900 text-xs">
                    <i class="fas fa-info-circle text-amber-600"></i>
                    <span>Exibindo itens sem minutagem detectada pela IA</span>
                </div>
                <p class="text-[11px] text-amber-900 mt-1 leading-relaxed font-medium">
                    Estes itens não tiveram correspondência direta na fala do áudio. Clique no item para carregar o vídeo, navegue até o trecho em que a peça aparece e clique em <b>Início (I)</b> e <b>Fim (O)</b> para salvar.
                </p>
            </div>

            <!-- Notice quando a busca/filtro não encontra nenhum item -->
            <div id="items-filter-empty-notice" class="hidden bg-white p-8 rounded-2xl border border-gray-200 text-center shadow-xs">
                <i class="fas fa-search text-gray-300 text-3xl mb-2"></i>
                <h4 class="text-xs font-bold text-gray-800">Nenhum item encontrado com este filtro</h4>
                <p class="text-[11px] text-gray-500 mt-0.5">Tente selecionar outra aba ou limpar o campo de busca.</p>
                <button type="button" onclick="setItemsFilter('all'); document.getElementById('items-filter-search-input').value = ''; handleItemSearch('');" class="mt-3 px-3 py-1.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs transition border border-gray-300">
                    Restaurar Todos os Itens
                </button>
            </div>

            <!-- Lista de Cards de Itens -->
            <div class="space-y-3" id="live-items-list-container">
                @forelse($liveItems as $item)
                    @php
                        $hasCutTimes = $item['cut_start_sec'] !== null && $item['cut_end_sec'] !== null;
                        $isRendered = $item['video_cut_status'] === 'recorded' && !empty($item['video_cut_url']);
                    @endphp
                    <div id="item-card-{{ $item['live_item_id'] }}" 
                         data-has-cut="{{ $hasCutTimes ? '1' : '0' }}"
                         data-is-rendered="{{ $isRendered ? '1' : '0' }}"
                         data-code="{{ $item['codigo_live'] }}"
                         data-search="{{ strtolower($item['codigo_live'] . ' ' . $item['item_codigo'] . ' ' . $item['item_name'] . ' ' . ($item['buyer_name'] ?? '')) }}"
                         onclick="selectActiveItem({{ $item['live_item_id'] }}, '{{ $item['cut_start_formatted'] ?: ($item['cut_start_sec'] ?? 0) }}')"
                         class="item-card bg-white hover:bg-gray-50 rounded-2xl border {{ $isRendered ? 'border-green-400 bg-green-50/20' : ($hasCutTimes ? 'border-indigo-300' : 'border-amber-300 bg-amber-50/30') }} p-4 transition duration-150 shadow-sm cursor-pointer relative group">
                        
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            
                            <!-- Foto & Infos do Produto -->
                            <div class="flex items-center gap-3 min-w-0">
                                <img id="item-avatar-{{ $item['live_item_id'] }}" src="{{ $item['item_image'] }}" class="w-14 h-14 rounded-xl object-cover bg-gray-100 border border-gray-200 shrink-0" onerror="this.src='https://placehold.co/100x100?text=Sem+Foto'" />
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2 py-0.5 rounded-lg text-xs font-black bg-indigo-600 text-white shadow-xs" style="background-color: #4f46e5; color: #ffffff !important;">
                                             #{{ $item['codigo_live'] }}
                                        </span>
                                        @if(!$hasCutTimes)
                                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-1 shadow-xs">
                                                <i class="fas fa-exclamation-triangle text-amber-600"></i> Não Minutado
                                            </span>
                                        @endif
                                        @php
                                            $isGold = !empty($item['review_quality']) && $item['review_quality'] === 'gold';
                                            $isAdjusted = !empty($item['is_reviewed']) && !$isGold;
                                        @endphp
                                        <span id="badge-gold-{{ $item['live_item_id'] }}" class="{{ $isGold ? '' : 'hidden' }} px-2 py-0.5 rounded-lg text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-1 shadow-xs" title="Corte memorizado como padrão-ouro para o aprendizado do Severino">
                                            <i class="fas fa-crown text-amber-600"></i> Padrão-Ouro
                                        </span>
                                        <span id="badge-adj-{{ $item['live_item_id'] }}" class="{{ $isAdjusted ? '' : 'hidden' }} px-2 py-0.5 rounded-lg text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200 flex items-center gap-1 shadow-xs" title="Corte ajustado manualmente">
                                            <i class="fas fa-user-check text-blue-600"></i> Revisado
                                        </span>
                                        @if($isRendered)
                                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-black bg-green-600 text-white flex items-center gap-1 shadow-xs" style="background-color: #16a34a; color: #ffffff !important;">
                                                <i class="fas fa-check-circle"></i> Vídeo Pronto
                                            </span>
                                        @endif
                                    </div>
                                    <h4 class="text-sm font-extrabold text-gray-900 truncate mt-1">{{ $item['item_name'] }}</h4>
                                    <div class="text-xs text-gray-500 font-semibold">Cód: {{ $item['item_codigo'] }} &bull; R$ {{ $item['item_price'] }}</div>
                                </div>
                            </div>

                            <!-- Minutagem & Controles -->
                            <div class="flex flex-wrap items-center gap-2 shrink-0 justify-end" onclick="event.stopPropagation();">
                                
                                <div class="flex items-center gap-1.5 bg-gray-100 p-1.5 rounded-xl border border-gray-200">
                                    <!-- Início -->
                                    <div class="text-center">
                                        <label class="block text-[9px] font-bold uppercase text-gray-500">Início</label>
                                        <input type="text"
                                               id="input-start-{{ $item['live_item_id'] }}"
                                               value="{{ $item['cut_start_formatted'] ?? '' }}" 
                                               placeholder="00:00:00"
                                               class="w-20 px-1.5 py-1 text-xs font-mono font-bold text-center bg-white text-green-700 rounded-lg border border-gray-300 focus:border-green-500 focus:ring-1 focus:ring-green-500 shadow-2xs">
                                    </div>

                                    <span class="text-gray-400 font-bold text-xs mt-3">➔</span>

                                    <!-- Fim -->
                                    <div class="text-center">
                                        <label class="block text-[9px] font-bold uppercase text-gray-500">Fim</label>
                                        <input type="text"
                                               id="input-end-{{ $item['live_item_id'] }}"
                                               value="{{ $item['cut_end_formatted'] ?? '' }}" 
                                               placeholder="00:00:00"
                                               class="w-20 px-1.5 py-1 text-xs font-mono font-bold text-center bg-white text-amber-700 rounded-lg border border-gray-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                                    </div>

                                    <!-- Salvar Tempo -->
                                    <button type="button" onclick="saveItemCutTime({{ $item['live_item_id'] }})" title="Salvar Minutagem & Ensinar Severino (Enter)" class="mt-3 p-1.5 text-gray-600 hover:text-gray-900 bg-white hover:bg-gray-200 rounded-lg transition border border-gray-200 shadow-xs cursor-pointer">
                                        <i class="fas fa-save text-xs"></i>
                                    </button>
                                </div>


                                <!-- Ações de Vídeo & Severino -->
                                <div class="flex items-center gap-1.5">
                                    <!-- Botão Aprovar Padrão Ouro para Treinar o Severino -->
                                    <button type="button" 
                                            onclick="approveItemCut({{ $item['live_item_id'] }})" 
                                            id="btn-approve-{{ $item['live_item_id'] }}" 
                                            title="{{ $isGold ? 'Corte já memorizado como Padrão-Ouro pelo Severino' : 'Aprovar corte como Padrão-Ouro (Severino aprende este modelo)' }}" 
                                            class="p-2 rounded-xl text-xs transition cursor-pointer active:scale-95 shadow-xs flex items-center justify-center {{ $isGold ? 'bg-amber-500 text-white font-black' : 'bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-300' }}">
                                        <i class="fas {{ $isGold ? 'fa-crown' : 'fa-thumbs-up' }}"></i>
                                    </button>

                                    <button type="button" onclick="previewItemClip({{ $item['live_item_id'] }})" title="Reproduzir Trecho Marcado no Player" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 p-2 rounded-xl text-xs transition cursor-pointer active:scale-95 shadow-xs">
                                        <i class="fas fa-play"></i>
                                    </button>

                                    <button type="button" onclick="captureThumbnailFromPlayer({{ $item['live_item_id'] }})" id="btn-thumb-{{ $item['live_item_id'] }}" title="Capturar Frame Atual do Player como Capa/Thumbnail (Atalho C)" class="bg-amber-500 hover:bg-amber-600 text-white font-bold p-2 rounded-xl text-xs transition shadow-sm cursor-pointer active:scale-95" style="background-color: #f59e0b; color: #ffffff !important;">
                                        <i class="fas fa-camera"></i>
                                    </button>

                                    <button type="button" onclick="generateSingleClip({{ $item['live_item_id'] }})" id="btn-cut-{{ $item['live_item_id'] }}" title="Gerar Corte Individual & Thumbnail com FFmpeg" class="bg-teal-600 hover:bg-teal-700 text-white font-bold p-2 rounded-xl text-xs transition shadow-sm cursor-pointer active:scale-95" style="background-color: #0d9488; color: #ffffff !important;">
                                        <i class="fas fa-scissors"></i>
                                    </button>

                                    @if($isRendered)
                                        <a href="{{ $item['video_cut_url'] }}" target="_blank" title="Assistir / Baixar Vídeo Cortado" class="bg-green-600 hover:bg-green-700 text-white font-bold p-2 rounded-xl text-xs transition shadow-sm cursor-pointer" style="background-color: #16a34a; color: #ffffff !important;">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    @endif
                                </div>

                            </div>

                        </div>

                        <!-- Galeria de Miniaturas Inteligentes (3 Opções ou Capturas Manuais) -->
                        <div class="mt-3 pt-2.5 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2" onclick="event.stopPropagation();">
                            <div class="flex items-center gap-1.5 flex-wrap" id="candidates-container-{{ $item['live_item_id'] }}">
                                <span class="text-[10px] font-bold text-gray-400 uppercase mr-1 flex items-center gap-1">
                                    <i class="fas fa-images text-indigo-500"></i> Capa:
                                </span>
                                @if(!empty($item['thumbnail_candidates']))
                                    @foreach($item['thumbnail_candidates'] as $cIdx => $cand)
                                        @php
                                            $isCandActive = !empty($cand['path']) && ($cand['path'] === $item['raw_image_path'] || str_contains($item['item_image'], basename($cand['path'])));
                                        @endphp
                                        <button type="button" 
                                                onclick="selectItemCandidateThumbnail({{ $item['live_item_id'] }}, '{{ $cand['path'] }}', '{{ $cand['url'] }}', this)"
                                                title="Clique para definir como capa oficial ({{ $cand['label'] ?? 'Opção' }})"
                                                class="candidate-thumb-btn relative rounded-lg overflow-hidden border-2 transition active:scale-95 cursor-pointer shadow-2xs group/thumb {{ $isCandActive ? 'border-green-500 ring-2 ring-green-400/50' : 'border-gray-200 hover:border-indigo-400 opacity-75 hover:opacity-100' }}"
                                                style="width: 52px; height: 52px;">
                                            <img src="{{ $cand['url'] }}" class="w-full h-full object-cover" />
                                            <span class="absolute bottom-0 inset-x-0 bg-black/75 text-[8px] font-black text-white text-center py-0.5 truncate px-0.5">
                                                {{ $cand['label'] ?? ('Opção ' . ($cIdx + 1)) }}
                                            </span>
                                            <span class="cand-badge-active {{ $isCandActive ? '' : 'hidden' }} absolute top-0.5 right-0.5 bg-green-600 text-white rounded-full w-3.5 h-3.5 flex items-center justify-center text-[8px] font-bold shadow-xs">
                                                <i class="fas fa-check"></i>
                                            </span>
                                        </button>
                                    @endforeach
                                @else
                                    <span class="text-[11px] text-gray-400 italic">Miniaturas ainda não geradas</span>
                                @endif
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button" 
                                        onclick="generateSmartThumbnails({{ $item['live_item_id'] }})" 
                                        id="btn-gen-thumbs-{{ $item['live_item_id'] }}"
                                        title="Extrair 3 miniaturas inteligentes de alta nitidez com IA/FFmpeg" 
                                        class="text-[10px] font-extrabold text-indigo-700 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-2 py-1 rounded-lg transition active:scale-95 flex items-center gap-1 cursor-pointer">
                                    <i class="fas fa-wand-magic-sparkles text-indigo-600"></i>
                                    <span>3 Miniaturas IA</span>
                                </button>
                            </div>
                        </div>

                        <!-- Snippet de Transcrição -->
                        <div id="snippet-container-{{ $item['live_item_id'] }}" class="mt-2.5 pt-2 border-t border-gray-100 text-[11px] text-gray-600 italic bg-gray-50/70 p-2 rounded-lg {{ empty($item['transcription_snippet']) ? 'hidden' : '' }}">
                            <i class="fas fa-quote-left text-[9px] text-indigo-500 mr-1"></i>
                            <span id="snippet-text-{{ $item['live_item_id'] }}">{{ $item['transcription_snippet'] ?? '' }}</span>
                        </div>

                    </div>
                @empty
                    <div class="bg-white border border-gray-200 rounded-2xl p-8 text-center text-gray-500 shadow-sm">
                        <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i>
                        <p class="text-sm font-bold text-gray-700">Nenhum produto vinculado a esta live ainda.</p>
                        <p class="text-xs text-gray-400 mt-1">Os produtos bipados na live aparecerão automaticamente nesta lista.</p>
                    </div>
                @endforelse
            </div>

        </div>

    </div>

</div>

<!-- MODAL UPLOAD VÍDEO -->
<div id="modal-upload-video" class="fixed inset-0 bg-gray-900 bg-opacity-75 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white border border-gray-200 rounded-2xl max-w-md w-full p-6 shadow-2xl text-gray-800">
        <div class="flex items-center justify-between pb-3 border-b border-gray-200 mb-4">
            <h3 class="text-lg font-black text-gray-900 flex items-center gap-2">
                <i class="fas fa-video text-indigo-600"></i> Carregar Gravação da Live
            </h3>
            <button onclick="toggleUploadModal()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
        </div>

        <form id="form-upload-video" onsubmit="handleVideoUpload(event)" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1.5">Arquivo de Vídeo (.mp4, .mov, .mkv):</label>
                <input type="file" id="video-file-input" name="video_file" accept="video/*" class="w-full text-xs text-gray-700 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer bg-gray-50 p-2 rounded-xl border border-gray-300">
            </div>

            <div class="relative flex py-2 items-center">
                <div class="flex-grow border-t border-gray-200"></div>
                <span class="flex-shrink mx-3 text-gray-400 text-[10px] uppercase font-bold">OU LINK / CAMINHO DO VÍDEO</span>
                <div class="flex-grow border-t border-gray-200"></div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center justify-between">
                    <span>Link do Instagram / TikTok / YouTube ou Caminho:</span>
                    <span class="text-[10px] text-indigo-700 font-extrabold bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-200">✨ Download Automático</span>
                </label>
                <input type="text" id="video-path-manual-input" name="video_path_manual" placeholder="Ex: https://www.instagram.com/p/... ou /var/www/..." class="w-full px-3 py-2 text-xs bg-white text-gray-900 rounded-xl border border-gray-300 focus:border-indigo-500 font-medium">
                <p class="text-[10px] text-gray-500 mt-1">Cole a URL do post/vídeo do Instagram, TikTok ou YouTube para baixar diretamente no servidor.</p>
            </div>

            <div id="upload-progress-box" class="hidden space-y-1">
                <div class="flex justify-between text-[11px] text-gray-700 font-bold">
                    <span id="upload-progress-text">Processando vídeo...</span>
                    <span id="upload-progress-pct">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                    <div id="upload-progress-bar" class="bg-indigo-600 h-full w-0 transition-all duration-150"></div>
                </div>
            </div>

            <div class="pt-3 flex justify-end gap-2 border-t border-gray-200">
                <button type="button" onclick="toggleUploadModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition">Cancelar</button>
                <button type="submit" id="btn-submit-upload" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl text-xs font-bold transition shadow flex items-center gap-1.5" style="background-color: #4f46e5; color: #ffffff !important;">
                    <i class="fas fa-check"></i> Salvar Vídeo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL TRANSCRIÇÃO -->
<div id="modal-transcription" class="fixed inset-0 bg-gray-900 bg-opacity-75 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white border border-gray-200 rounded-2xl max-w-2xl w-full p-6 shadow-2xl flex flex-col max-h-[85vh] text-gray-800">
        <div class="flex items-center justify-between pb-3 border-b border-gray-200 mb-4 shrink-0">
            <h3 class="text-lg font-black text-gray-900 flex items-center gap-2">
                <i class="fas fa-closed-captioning text-teal-600"></i> Transcrição da Live com Timestamps
            </h3>
            <button onclick="toggleTranscriptionModal()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
        </div>

        <div class="flex-1 overflow-y-auto space-y-2 pr-1" id="transcription-sentences-list">
            @forelse($transcriptionData as $idx => $st)
                <div onclick="seekPlayer({{ $st['start'] ?? 0 }})" class="p-2.5 rounded-xl bg-gray-50 hover:bg-indigo-50 border border-gray-200 cursor-pointer transition flex items-start gap-2.5">
                    <span class="text-[10px] font-mono font-bold bg-indigo-100 text-indigo-700 border border-indigo-200 px-2 py-0.5 rounded-lg shrink-0 mt-0.5">
                        {{ gmdate(($st['start'] ?? 0) >= 3600 ? 'H:i:s' : 'i:s', (int) ($st['start'] ?? 0)) }}
                    </span>
                    <p class="text-xs text-gray-800 leading-relaxed font-medium">{{ $st['text'] ?? '' }}</p>
                </div>
            @empty
                <div class="text-center py-10 text-gray-400">
                    <i class="fas fa-comment-slash text-3xl mb-2 text-gray-300"></i>
                    <p class="text-xs font-bold text-gray-700">Nenhuma frase transcrita ainda.</p>
                    <p class="text-[11px] text-gray-500 mt-1">Cole a transcrição abaixo em formato JSON ou utilize a captura em tempo real.</p>
                </div>
            @endforelse
        </div>

        <div class="pt-4 mt-3 border-t border-gray-200 shrink-0 flex flex-wrap justify-between items-center gap-2">
            <div class="flex items-center gap-2">
                <button type="button" onclick="transcribeAudioWithAI()" id="btn-transcribe-ai" class="bg-teal-600 hover:bg-teal-700 text-white font-extrabold text-xs px-3 py-1.5 rounded-xl transition shadow-xs flex items-center gap-1.5 cursor-pointer" style="background-color: #0d9488; color: #ffffff !important;">
                    <i class="fas fa-microphone-lines text-white"></i>
                    <span style="color: #ffffff !important; font-weight: 800;">Transcrever Áudio com IA</span>
                </button>
                <button type="button" onclick="promptPasteTranscription()" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1">
                    <i class="fas fa-paste"></i> Colar JSON
                </button>
            </div>
            <button type="button" onclick="toggleTranscriptionModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold px-4 py-2 rounded-xl transition border border-gray-200">Fechar</button>
        </div>
    </div>
</div>

<!-- MODAL AUTO-PROCESSAR INSTAGRAM -->
<div id="modal-auto-process-instagram" class="fixed inset-0 bg-gray-900/75 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white border border-gray-200 rounded-2xl max-w-lg w-full p-6 shadow-2xl text-gray-800 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3 border-b border-gray-200 mb-4">
            <h3 class="text-lg font-black text-gray-900 flex items-center gap-2">
                <i class="fab fa-instagram text-pink-600 text-xl"></i>
                <span>Auto-Processar Gravação da Live</span>
            </h3>
            <button type="button" onclick="toggleAutoProcessModal(false)" class="text-gray-400 hover:text-gray-700 text-2xl font-bold leading-none cursor-pointer">&times;</button>
        </div>

        <form id="form-auto-process" onsubmit="submitAutoProcess(event)" class="space-y-4">
            <div class="bg-indigo-50/70 border border-indigo-200 rounded-xl p-3.5 text-xs text-indigo-950">
                <p class="font-bold flex items-center gap-1.5 text-indigo-900">
                    <i class="fas fa-magic text-indigo-600"></i> Fluxo 100% Automático:
                </p>
                <div class="mt-1.5 text-indigo-900 space-y-1 text-[11px] leading-relaxed">
                    <div><i class="fas fa-check-circle text-indigo-600 mr-1"></i> Baixa o vídeo do Instagram (ou usa o vídeo já carregado)</div>
                    <div><i class="fas fa-check-circle text-indigo-600 mr-1"></i> Extrai o áudio e transcreve com IA Whisper</div>
                    <div><i class="fas fa-check-circle text-indigo-600 mr-1"></i> Detecta minutagens e códigos com Severino IA</div>
                    <div><i class="fas fa-check-circle text-indigo-600 mr-1"></i> Gera os cortes (.mp4) e capas para todos os itens</div>
                </div>
            </div>

            <div>
                <label for="auto-process-url-input" class="block text-xs font-bold text-gray-900 mb-1.5 flex items-center justify-between">
                    <span>Link da Publicação / Reel no Instagram:</span>
                    <button type="button" onclick="pasteClipboardToUrlInput()" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-extrabold flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-paste"></i> Colar do Teclado
                    </button>
                </label>
                <input 
                    type="url" 
                    id="auto-process-url-input" 
                    name="url" 
                    placeholder="Ex: https://www.instagram.com/reel/C... ou https://www.instagram.com/p/..." 
                    class="w-full px-3.5 py-2.5 text-xs bg-white text-gray-900 rounded-xl border border-gray-300 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-200 font-medium placeholder-gray-400 outline-none transition"
                >
                <p class="text-[11px] text-gray-600 mt-1.5">
                    Cole o link do Reel/Live postado no Instagram. Se o vídeo já estiver carregado nesta página, pode deixar em branco.
                </p>
            </div>

            <div class="pt-3 flex justify-end gap-2.5 border-t border-gray-200">
                <button type="button" onclick="toggleAutoProcessModal(false)" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-700 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 transition cursor-pointer">
                    Cancelar
                </button>
                <button type="submit" id="btn-submit-auto-process" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-extrabold px-5 py-2 rounded-xl text-xs shadow-md flex items-center gap-1.5 transition active:scale-95 cursor-pointer">
                    <i class="fas fa-play text-white"></i>
                    <span class="text-white font-black">Iniciar Auto-Processamento</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const liveId = {{ $live->id }};
    const csrfToken = '{{ csrf_token() }}';
    let activeLiveItemId = null;
    let previewTimer = null;
    let currentItemFilter = 'all';
    let currentItemSearch = '';

    function setItemsFilter(filterType) {
        currentItemFilter = filterType;

        // Atualiza estilo das abas
        document.querySelectorAll('.item-filter-tab').forEach(btn => {
            const f = btn.getAttribute('data-filter');
            if (f === filterType) {
                btn.style.backgroundColor = '#1f2937';
                btn.style.color = '#ffffff';
            } else {
                if (f === 'unminuted') {
                    btn.style.backgroundColor = '#fef3c7';
                    btn.style.color = '#92400e';
                } else {
                    btn.style.backgroundColor = '#f3f4f6';
                    btn.style.color = '#374151';
                }
            }
        });

        // Banner informativo de Não Minutados
        const banner = document.getElementById('filter-unminuted-banner');
        if (banner) {
            if (filterType === 'unminuted') {
                banner.classList.remove('hidden');
            } else {
                banner.classList.add('hidden');
            }
        }

        applyItemCardFilters();
    }

    function handleItemSearch(term) {
        currentItemSearch = String(term || '').toLowerCase().trim().replace(/^#/, '');
        applyItemCardFilters();
    }

    function applyItemCardFilters() {
        const cards = document.querySelectorAll('.item-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const hasCut = card.getAttribute('data-has-cut') === '1';
            const isRendered = card.getAttribute('data-is-rendered') === '1';
            const searchData = (card.getAttribute('data-search') || '').toLowerCase();
            const code = (card.getAttribute('data-code') || '').toLowerCase();

            let matchesTab = true;
            if (currentItemFilter === 'unminuted') {
                matchesTab = !hasCut;
            } else if (currentItemFilter === 'minuted') {
                matchesTab = hasCut;
            } else if (currentItemFilter === 'rendered') {
                matchesTab = isRendered;
            }

            let matchesSearch = true;
            if (currentItemSearch !== '') {
                matchesSearch = searchData.includes(currentItemSearch) || code === currentItemSearch;
            }

            if (matchesTab && matchesSearch) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        const emptyNotice = document.getElementById('items-filter-empty-notice');
        if (emptyNotice) {
            if (visibleCount === 0) {
                emptyNotice.classList.remove('hidden');
            } else {
                emptyNotice.classList.add('hidden');
            }
        }

        const countEl = document.getElementById('items-visible-counter');
        if (countEl) countEl.textContent = visibleCount;
    }

    const player = document.getElementById("live-main-player");

    // Sincronizar display de tempo do player
    if (player) {
        player.addEventListener('timeupdate', () => {
            const cur = document.getElementById("player-current-time");
            if (cur) cur.textContent = formatTime(player.currentTime);
        });

        player.addEventListener('loadedmetadata', () => {
            const dur = document.getElementById("player-duration");
            if (dur) dur.textContent = formatTime(player.duration);
        });

        player.addEventListener('play', updatePlayPauseIcon);
        player.addEventListener('pause', updatePlayPauseIcon);
    }

    function formatTime(sec) {
        if (!sec || isNaN(sec)) return "00:00:00";
        const s = Math.floor(sec);
        const h = Math.floor(s / 3600);
        const m = Math.floor((s % 3600) / 60);
        const rem = s % 60;
        return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(rem).padStart(2, '0')}`;
    }

    function parseTimeToSec(val) {
        if (val === null || val === undefined || val === '') return null;
        if (typeof val === 'number') return val;
        val = String(val).trim().replace(',', '.');
        if (!val.includes(':')) {
            const num = parseFloat(val);
            return isNaN(num) ? null : num;
        }
        const parts = val.split(':').map(p => parseFloat(p.trim()) || 0);
        if (parts.length === 3) {
            return (parts[0] * 3600) + (parts[1] * 60) + parts[2];
        } else if (parts.length === 2) {
            return (parts[0] * 60) + parts[1];
        }
        return null;
    }

    function togglePlayPause() {
        if (!player) return;
        if (player.paused) {
            player.play().catch(() => {});
        } else {
            player.pause();
        }
    }

    function updatePlayPauseIcon() {
        const icon = document.getElementById("icon-play-pause");
        if (!icon || !player) return;
        if (player.paused) {
            icon.className = "fas fa-play";
        } else {
            icon.className = "fas fa-pause";
        }
    }

    function jumpVideo(deltaSec) {
        if (!player) return;
        player.currentTime = Math.max(0, Math.min(player.duration || 999999, player.currentTime + deltaSec));
    }

    function setPlaybackRate(rate) {
        if (!player) return;
        player.playbackRate = rate;
        document.querySelectorAll('.speed-btn').forEach(btn => {
            btn.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600', 'shadow-2xs');
            btn.classList.add('bg-white', 'text-gray-700', 'border-gray-300');
        });
        const activeBtn = document.getElementById(`btn-speed-${String(rate).replace('.', '_')}`);
        if (activeBtn) {
            activeBtn.classList.remove('bg-white', 'text-gray-700', 'border-gray-300');
            activeBtn.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600', 'shadow-2xs');
        }
    }

    function selectActiveItem(itemId, startTime) {
        activeLiveItemId = itemId;
        document.querySelectorAll('.item-card').forEach(el => {
            el.classList.remove('ring-2', 'ring-indigo-500', 'bg-indigo-50/40');
        });
        const activeCard = document.getElementById(`item-card-${itemId}`);
        if (activeCard) {
            activeCard.classList.add('ring-2', 'ring-indigo-500', 'bg-indigo-50/40');
        }

        if (player && startTime !== null && startTime !== undefined && startTime !== '') {
            const sec = parseTimeToSec(startTime);
            if (sec !== null) player.currentTime = sec;
        }
    }

    function markCurrentTimeToActiveItem(type) {
        if (!activeLiveItemId) {
            alert("Selecione um item na lista ao lado primeiro!");
            return;
        }
        if (!player) return;

        const formatted = formatTime(player.currentTime);
        const input = document.getElementById(`input-${type}-${activeLiveItemId}`);
        if (input) {
            input.value = formatted;
            saveItemCutTime(activeLiveItemId);
        }
    }

    function seekPlayer(sec) {
        if (player) {
            player.currentTime = parseFloat(sec);
            player.play().catch(() => {});
            toggleTranscriptionModal();
        }
    }

    function previewItemClip(itemId) {
        if (!player) {
            alert("Carregue o vídeo da live para visualizar o preview.");
            return;
        }

        const startInput = document.getElementById(`input-start-${itemId}`);
        const endInput = document.getElementById(`input-end-${itemId}`);
        const start = parseTimeToSec(startInput ? startInput.value : '');
        const end = parseTimeToSec(endInput ? endInput.value : '');

        if (start === null || end === null || end <= start) {
            alert("Defina os tempos de início e fim válidos no formato HH:MM:SS ou MM:SS (ex: 01:15:20 ou 10:30).");
            return;
        }

        if (previewTimer) clearTimeout(previewTimer);

        player.currentTime = start;
        player.play();

        const durationMs = (end - start) * 1000;
        previewTimer = setTimeout(() => {
            player.pause();
        }, durationMs);
    }

    async function saveItemCutTime(itemId) {
        const startInput = document.getElementById(`input-start-${itemId}`);
        const endInput = document.getElementById(`input-end-${itemId}`);
        const startVal = startInput ? startInput.value.trim() : '';
        const endVal = endInput ? endInput.value.trim() : '';

        const start = parseTimeToSec(startVal);
        const end = parseTimeToSec(endVal);

        if (start === null || end === null || end <= start) {
            return;
        }

        try {
            const res = await fetch(`/admin/lives/${liveId}/cortes/save-timestamp/${itemId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    cut_start_sec: startVal,
                    cut_end_sec: endVal
                })
            });
            const data = await res.json();
            if (data.success) {
                const card = document.getElementById(`item-card-${itemId}`);
                if (card) card.classList.add('border-indigo-400');
                if (startInput && data.cut_start_formatted) startInput.value = data.cut_start_formatted;
                if (endInput && data.cut_end_formatted) endInput.value = data.cut_end_formatted;

                const snipBox = document.getElementById(`snippet-container-${itemId}`);
                const snipText = document.getElementById(`snippet-text-${itemId}`);
                if (snipText && data.snippet !== undefined) snipText.textContent = data.snippet;
                if (snipBox) {
                    if (data.snippet && data.snippet.trim() !== '') {
                        snipBox.classList.remove('hidden');
                    } else {
                        snipBox.classList.add('hidden');
                    }
                }

                const badgeAdj = document.getElementById(`badge-adj-${itemId}`);
                if (badgeAdj) badgeAdj.classList.remove('hidden');
            }
        } catch (e) {
            console.error("Erro ao salvar minutagem:", e);
        }
    }

    async function approveItemCut(itemId) {
        const btn = document.getElementById(`btn-approve-${itemId}`);
        const oldHtml = btn ? btn.innerHTML : '';
        if (btn) btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        try {
            const res = await fetch(`/admin/lives/${liveId}/cortes/approve/${itemId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                if (btn) {
                    btn.className = 'p-2 rounded-xl text-xs transition cursor-pointer active:scale-95 shadow-xs flex items-center justify-center bg-amber-500 text-white font-black';
                    btn.innerHTML = '<i class="fas fa-crown"></i>';
                    btn.title = 'Corte memorizado como Padrão-Ouro pelo Severino';
                }
                const badgeGold = document.getElementById(`badge-gold-${itemId}`);
                if (badgeGold) badgeGold.classList.remove('hidden');
                const badgeAdj = document.getElementById(`badge-adj-${itemId}`);
                if (badgeAdj) badgeAdj.classList.add('hidden');
                
                alert('🌟 ' + data.message);
            } else {
                if (btn) btn.innerHTML = oldHtml;
                alert('Atenção: ' + (data.message || 'Não foi possível aprovar o corte.'));
            }
        } catch (e) {
            if (btn) btn.innerHTML = oldHtml;
            alert('Falha na conexão ao aprovar corte.');
        }
    }

    // Atalhos globais de teclado para edição rápida
    document.addEventListener('keydown', (e) => {
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
            if (e.key === 'Enter' && activeLiveItemId) {
                saveItemCutTime(activeLiveItemId);
            }
            return;
        }

        if (!player) return;

        if (e.code === 'Space' || e.key === 'k') {
            e.preventDefault();
            togglePlayPause();
        } else if (e.key === 'ArrowLeft' || e.key === 'j') {
            e.preventDefault();
            jumpVideo(e.shiftKey ? -1 : -5);
        } else if (e.key === 'ArrowRight' || e.key === 'l') {
            e.preventDefault();
            jumpVideo(e.shiftKey ? 1 : 5);
        } else if (e.key.toLowerCase() === 'i' || e.key === '[') {
            e.preventDefault();
            markCurrentTimeToActiveItem('start');
        } else if (e.key.toLowerCase() === 'o' || e.key === ']') {
            e.preventDefault();
            markCurrentTimeToActiveItem('end');
        } else if (e.key.toLowerCase() === 'p' && activeLiveItemId) {
            e.preventDefault();
            previewItemClip(activeLiveItemId);
        } else if (e.key.toLowerCase() === 'c' && activeLiveItemId) {
            e.preventDefault();
            captureThumbnailFromPlayer(activeLiveItemId);
        } else if (e.key === '1') {
            setPlaybackRate(1.0);
        } else if (e.key === '2') {
            setPlaybackRate(1.25);
        } else if (e.key === '3') {
            setPlaybackRate(1.5);
        } else if (e.key === '4') {
            setPlaybackRate(2.0);
        } else if (e.key === '5') {
            setPlaybackRate(0.75);
        }
    });

    let transcribePollTimer = null;


    function startTranscriptionPolling() {
        const progressBox = document.getElementById("ai-process-progress-box");
        if (progressBox) {
            progressBox.classList.remove("hidden");
            progressBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        const btns = [
            document.getElementById("btn-auto-detect-header"),
            document.getElementById("btn-auto-detect")
        ].filter(Boolean);

        btns.forEach(b => {
            b.disabled = true;
            b.innerHTML = `<i class="fas fa-spinner fa-spin mr-1"></i> Minutando com Severino...`;
        });

        if (transcribePollTimer) clearInterval(transcribePollTimer);

        transcribePollTimer = setInterval(async () => {
            try {
                const res = await fetch(`/admin/lives/${liveId}/cortes/transcribe-status`);
                if (!res.ok) return;
                const data = await res.json();

                if (data.status === 'processing') {
                    const textEl = document.getElementById("ai-process-text");
                    const pctEl = document.getElementById("ai-process-pct");
                    const barEl = document.getElementById("ai-process-bar");
                    if (textEl) textEl.textContent = data.message || 'Processando minutagens com Severino IA...';
                    if (pctEl) pctEl.textContent = `${data.progress || 0}%`;
                    if (barEl) barEl.style.width = `${data.progress || 0}%`;
                } else if (data.status === 'completed') {
                    clearInterval(transcribePollTimer);
                    const barEl = document.getElementById("ai-process-bar");
                    if (barEl) barEl.style.width = '100%';
                    const textEl = document.getElementById("ai-process-text");
                    if (textEl) textEl.textContent = '✅ ' + (data.message || 'Minutagem concluída com sucesso! Recarregando...');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else if (data.status === 'error') {
                    clearInterval(transcribePollTimer);
                    alert("Atenção: " + (data.message || 'Erro durante a minutagem com IA.'));
                    if (progressBox) progressBox.classList.add("hidden");
                    btns.forEach(b => {
                        b.disabled = false;
                        b.innerHTML = `<i class="fas fa-wand-magic-sparkles"></i> Auto-Detectar com IA`;
                    });
                }
            } catch (e) {
                console.error("Erro ao consultar status:", e);
            }
        }, 2000);
    }

    async function transcribeAudioWithAI() {
        if (!confirm("Deseja extrair o áudio e gerar a transcrição completa com IA em segundo plano?")) return;

        const btn = document.getElementById("btn-transcribe-ai");
        const oldHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `<i class="fas fa-spinner fa-spin mr-1"></i> Iniciando...`;
        }

        try {
            const res = await fetch(`/admin/lives/${liveId}/cortes/transcribe`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }

            if (data.is_async) {
                toggleTranscriptionModal();
                startTranscriptionPolling();
            } else if (data.success) {
                alert("✅ " + data.message);
                window.location.reload();
            } else {
                alert("Atenção: " + data.message);
            }
        } catch (e) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }
            alert("Erro ao comunicar com o servidor para transcrição de áudio.");
        }
    }

    async function autoDetectWithAI() {
        const startCodeInput = document.getElementById("global-start-code");
        const startCode = startCodeInput && startCodeInput.value.trim() ? parseInt(startCodeInput.value.trim()) : null;

        let confirmMsg = "Deseja que o Severino IA analise a transcrição e detecte automaticamente os cortes de todas as peças?";
        if (startCode) {
            confirmMsg = `Deseja que o Severino IA analise e detecte os cortes a partir da peça #${startCode} em diante? (Os cortes anteriores serão preservados)`;
        }

        if (!confirm(confirmMsg)) return;

        const btns = [
            document.getElementById("btn-auto-detect-header"),
            document.getElementById("btn-auto-detect")
        ].filter(Boolean);

        btns.forEach(b => {
            b.disabled = true;
            b.innerHTML = `<i class="fas fa-spinner fa-spin mr-1"></i> Iniciando Severino...`;
        });

        try {
            const res = await fetch(`/admin/lives/${liveId}/cortes/auto-detect`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    start_code: startCode
                })
            });

            let data = null;
            try {
                data = await res.json();
            } catch (jsonErr) {
                throw new Error(`Servidor retornou resposta inesperada (${res.status} ${res.statusText})`);
            }

            if (data.is_async) {
                startTranscriptionPolling();
            } else if (data.success) {
                btns.forEach(b => {
                    b.disabled = false;
                    b.innerHTML = `<i class="fas fa-wand-magic-sparkles"></i> Auto-Detectar com IA`;
                });
                alert("✅ " + data.message);
                window.location.reload();
            } else {
                btns.forEach(b => {
                    b.disabled = false;
                    b.innerHTML = `<i class="fas fa-wand-magic-sparkles"></i> Auto-Detectar com IA`;
                });
                alert("Atenção: " + data.message);
            }
        } catch (e) {
            btns.forEach(b => {
                b.disabled = false;
                b.innerHTML = `<i class="fas fa-wand-magic-sparkles"></i> Auto-Detectar com IA`;
            });
            alert("Erro ao disparar detecção automática: " + e.message);
        }
    }



    async function generateSingleClip(itemId) {
        const btn = document.getElementById(`btn-cut-${itemId}`);
        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`;

        try {
            const res = await fetch(`/admin/lives/${liveId}/cortes/generate-single/${itemId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            btn.disabled = false;
            btn.innerHTML = oldHtml;

            if (data.success) {
                alert("✅ Corte gerado com sucesso!");
                window.location.reload();
            } else {
                alert("Erro ao cortar: " + data.message);
            }
        } catch (e) {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
            alert("Erro ao comunicar com o servidor para fatiamento de vídeo.");
        }
    }

    // 1. Seleciona uma miniatura candidata e define como capa oficial instantaneamente
    async function selectItemCandidateThumbnail(itemId, thumbPath, thumbUrl, btnElement) {
        try {
            const resp = await fetch(`/admin/lives/${liveId}/cortes/select-thumbnail/${itemId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ thumbnail_path: thumbPath })
            });
            const data = await resp.json();
            if (data.success) {
                // Atualiza o avatar principal do produto no card
                const avatar = document.getElementById(`item-avatar-${itemId}`);
                if (avatar) {
                    avatar.src = data.thumbnail_url || thumbUrl;
                }

                // Atualiza visualmente o highlight de borda e checkmark no container
                const container = document.getElementById(`candidates-container-${itemId}`);
                if (container) {
                    container.querySelectorAll('.candidate-thumb-btn').forEach(btn => {
                        btn.classList.remove('border-green-500', 'ring-2', 'ring-green-400/50', 'opacity-100');
                        btn.classList.add('border-gray-200', 'opacity-75');
                        const badge = btn.querySelector('.cand-badge-active');
                        if (badge) badge.classList.add('hidden');
                    });
                }

                if (btnElement) {
                    btnElement.classList.remove('border-gray-200', 'opacity-75');
                    btnElement.classList.add('border-green-500', 'ring-2', 'ring-green-400/50', 'opacity-100');
                    const badge = btnElement.querySelector('.cand-badge-active');
                    if (badge) badge.classList.remove('hidden');
                }
            } else {
                alert("Erro ao selecionar miniatura: " + (data.message || 'Falha na requisição.'));
            }
        } catch (e) {
            console.error("Erro ao selecionar miniatura:", e);
            alert("Erro de conexão ao definir capa.");
        }
    }

    // 2. Extrai sob demanda 3 miniaturas inteligentes de alta nitidez com IA/FFmpeg
    async function generateSmartThumbnails(itemId) {
        const btn = document.getElementById(`btn-gen-thumbs-${itemId}`);
        const oldHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `<i class="fas fa-spinner fa-spin text-indigo-600"></i> <span class="text-[10px]">Gerando...</span>`;
        }

        try {
            const resp = await fetch(`/admin/lives/${liveId}/cortes/generate-thumbnails/${itemId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            const data = await resp.json();
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }

            if (data.success && data.candidates) {
                renderCandidateButtons(itemId, data.candidates, data.primary_url);
                const avatar = document.getElementById(`item-avatar-${itemId}`);
                if (avatar && data.primary_url) {
                    avatar.src = data.primary_url;
                }
            } else {
                alert("Atenção: " + (data.message || 'Não foi possível gerar as 3 miniaturas.'));
            }
        } catch (e) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }
            alert("Erro ao comunicar com o servidor para geração de miniaturas.");
        }
    }

    // 3. Renderiza os botões das miniaturas candidatas no DOM
    function renderCandidateButtons(itemId, candidates, activeUrlOrPath) {
        const container = document.getElementById(`candidates-container-${itemId}`);
        if (!container) return;

        let html = `
            <span class="text-[10px] font-bold text-gray-400 uppercase mr-1 flex items-center gap-1">
                <i class="fas fa-images text-indigo-500"></i> Capa:
            </span>
        `;

        candidates.forEach((cand, idx) => {
            const isActive = activeUrlOrPath && (cand.url === activeUrlOrPath || cand.path === activeUrlOrPath || (cand.path && activeUrlOrPath.includes(cand.path.split('/').pop())));
            html += `
                <button type="button" 
                        onclick="selectItemCandidateThumbnail(${itemId}, '${cand.path}', '${cand.url}', this)"
                        title="Clique para definir como capa oficial (${cand.label || 'Opção ' + (idx + 1)})"
                        class="candidate-thumb-btn relative rounded-lg overflow-hidden border-2 transition active:scale-95 cursor-pointer shadow-2xs group/thumb ${isActive ? 'border-green-500 ring-2 ring-green-400/50 opacity-100' : 'border-gray-200 hover:border-indigo-400 opacity-75 hover:opacity-100'}"
                        style="width: 52px; height: 52px;">
                    <img src="${cand.url}" class="w-full h-full object-cover" />
                    <span class="absolute bottom-0 inset-x-0 bg-black/75 text-[8px] font-black text-white text-center py-0.5 truncate px-0.5">
                        ${cand.label || ('Opção ' + (idx + 1))}
                    </span>
                    <span class="cand-badge-active ${isActive ? '' : 'hidden'} absolute top-0.5 right-0.5 bg-green-600 text-white rounded-full w-3.5 h-3.5 flex items-center justify-center text-[8px] font-bold shadow-xs">
                        <i class="fas fa-check"></i>
                    </span>
                </button>
            `;
        });

        container.innerHTML = html;
    }

    // 4. Captura manual de frame do player (com atalho C ou botão da câmera)
    async function captureThumbnailFromPlayer(itemId) {
        const player = document.getElementById('live-main-player');
        const currentTime = player ? player.currentTime : null;
        const btn = document.getElementById(`btn-thumb-${itemId}`);
        const oldHtml = btn ? btn.innerHTML : '<i class="fas fa-camera"></i>';
        
        let targetTime = currentTime;
        if (!targetTime || targetTime <= 0) {
            const startInput = document.getElementById(`input-start-${itemId}`);
            targetTime = startInput ? startInput.value : 0;
        }

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }

        try {
            const resp = await fetch(`/admin/lives/${liveId}/cortes/capture-frame/${itemId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ timestamp: targetTime, set_as_cover: true })
            });
            const data = await resp.json();
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }

            if (data.success) {
                // Atualiza avatar do item
                const avatar = document.getElementById(`item-avatar-${itemId}`);
                if (avatar && data.thumbnail_url) {
                    avatar.src = data.thumbnail_url;
                }

                // Atualiza a galeria de candidatas adicionando a nova captura
                if (data.candidates && data.candidates.length > 0) {
                    renderCandidateButtons(itemId, data.candidates, data.thumbnail_url);
                }

                // Feedback sonoro/visual discreto
                alert(`📸 Foto de capa capturada e definida com sucesso no segundo ${data.timestamp_formatted || targetTime}!`);
            } else {
                alert("Erro ao capturar frame: " + data.message);
            }
        } catch (e) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }
            alert("Falha na conexão ao capturar frame do vídeo.");
        }
    }

    async function generateAllClips() {
        const startCodeInput = document.getElementById("global-start-code");
        const startCode = startCodeInput && startCodeInput.value.trim() ? parseInt(startCodeInput.value.trim()) : null;

        const itemCards = document.querySelectorAll('.item-card');
        const itemsToProcess = [];

        itemCards.forEach(card => {
            const idMatch = card.id ? card.id.match(/^item-card-(\d+)$/) : null;
            if (!idMatch) return;
            const itemId = idMatch[1];
            const startInput = document.getElementById(`input-start-${itemId}`);
            const endInput = document.getElementById(`input-end-${itemId}`);
            const codeBadge = card.querySelector('.bg-indigo-600')?.textContent?.trim() || (`#${itemId}`);
            const rawCode = parseInt(codeBadge.replace(/[^0-9]/g, '')) || 0;

            if (startCode && rawCode > 0 && rawCode < startCode) {
                return; // Pula os anteriores ao startCode
            }

            if (startInput && endInput && startInput.value.trim() !== '' && endInput.value.trim() !== '') {
                itemsToProcess.push({
                    id: itemId,
                    code: codeBadge
                });
            }
        });

        if (itemsToProcess.length === 0) {
            alert(startCode ? `Nenhum item a partir da peça #${startCode} com minutagem definida para cortar.` : "Nenhum item com minutagem definida para cortar.");
            return;
        }

        const msgConfirm = startCode ? `Deseja gerar os cortes com FFmpeg para ${itemsToProcess.length} peças (a partir da peça #${startCode})?` : `Deseja gerar os cortes de ${itemsToProcess.length} peças com FFmpeg agora?`;
        if (!confirm(msgConfirm)) return;

        const btn = document.getElementById("btn-batch-clips");
        const oldHtml = btn.innerHTML;
        btn.disabled = true;

        let successCount = 0;
        let failCount = 0;

        for (let i = 0; i < itemsToProcess.length; i++) {
            const item = itemsToProcess[i];
            const pct = Math.round(((i + 1) / itemsToProcess.length) * 100);
            btn.innerHTML = `<i class="fas fa-spinner fa-spin mr-1"></i> Cortando ${i + 1}/${itemsToProcess.length} (${item.code} - ${pct}%)...`;

            try {
                const res = await fetch(`/admin/lives/${liveId}/cortes/generate-single/${item.id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    successCount++;
                    const card = document.getElementById(`item-card-${item.id}`);
                    if (card) {
                        card.classList.remove('border-gray-200', 'border-indigo-300');
                        card.classList.add('border-green-400', 'bg-green-50/20');
                    }
                } else {
                    failCount++;
                }
            } catch (e) {
                failCount++;
            }
        }

        btn.disabled = false;
        btn.innerHTML = oldHtml;

        alert(`✅ Processamento concluído: ${successCount} cortes gerados com sucesso!` + (failCount > 0 ? ` (${failCount} falhas)` : ''));
        window.location.reload();
    }

    function toggleUploadModal() {
        document.getElementById("modal-upload-video").classList.toggle("hidden");
    }

    function toggleTranscriptionModal() {
        document.getElementById("modal-transcription").classList.toggle("hidden");
    }

    async function handleVideoUpload(e) {
        e.preventDefault();
        const fileInput = document.getElementById("video-file-input");
        const pathInput = document.getElementById("video-path-manual-input");
        const btn = document.getElementById("btn-submit-upload");

        const formData = new FormData();
        if (fileInput.files.length > 0) {
            formData.append('video_file', fileInput.files[0]);
        }
        if (pathInput.value.trim()) {
            formData.append('video_path_manual', pathInput.value.trim());
        }

        const isLink = /^https?:\/\//i.test(pathInput.value.trim());
        btn.disabled = true;
        btn.innerHTML = isLink 
            ? `<i class="fas fa-spinner fa-spin mr-1"></i> Baixando do link...`
            : `<i class="fas fa-spinner fa-spin mr-1"></i> Salvando...`;

        const progressBox = document.getElementById("upload-progress-box");
        const progressBar = document.getElementById("upload-progress-bar");
        const progressPct = document.getElementById("upload-progress-pct");
        const progressText = document.getElementById("upload-progress-text");
        if (progressBox) progressBox.classList.remove("hidden");
        if (progressText) {
            progressText.textContent = isLink 
                ? "Baixando e processando vídeo do Instagram/Link... (aguarde alguns instantes)"
                : "Enviando arquivo de vídeo...";
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', `/admin/lives/${liveId}/cortes/upload-video`, true);
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.onprogress = function(event) {
            if (event.lengthComputable) {
                const percent = Math.round((event.loaded / event.total) * 100);
                if (progressBar) progressBar.style.width = percent + '%';
                if (progressPct) progressPct.textContent = percent + '%';
            }
        };

        xhr.onload = function() {
            try {
                const data = JSON.parse(xhr.responseText);
                if (data.is_async && data.status_url) {
                    // Download em segundo plano iniciado - monitorar progresso
                    pollVideoDownloadProgress(data.status_url);
                    return;
                }
                btn.disabled = false;
                btn.innerHTML = `<i class="fas fa-check mr-1"></i> Salvar Vídeo`;
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert("Erro: " + (data.message || 'Falha ao salvar vídeo'));
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = `<i class="fas fa-check mr-1"></i> Salvar Vídeo`;
                alert("Resposta inválida do servidor.");
            }
        };

        xhr.onerror = function() {
            btn.disabled = false;
            btn.innerHTML = `<i class="fas fa-check mr-1"></i> Salvar Vídeo`;
            alert("Erro de conexão ao enviar vídeo.");
        };

        xhr.send(formData);
    }

    function pollVideoDownloadProgress(statusUrl) {
        const progressBox = document.getElementById("upload-progress-box");
        const progressBar = document.getElementById("upload-progress-bar");
        const progressPct = document.getElementById("upload-progress-pct");
        const progressText = document.getElementById("upload-progress-text");
        const btn = document.getElementById("btn-submit-upload");

        if (progressBox) progressBox.classList.remove("hidden");

        const timer = setInterval(async () => {
            try {
                const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();

                if (data.status === 'downloading') {
                    const pct = Math.min(99, Math.round(data.progress || 0));
                    if (progressBar) progressBar.style.width = Math.max(5, pct) + '%';
                    if (progressPct) progressPct.textContent = pct + '%';
                    if (progressText) progressText.textContent = data.message || "Baixando vídeo...";
                } else if (data.status === 'completed') {
                    clearInterval(timer);
                    if (progressBar) progressBar.style.width = '100%';
                    if (progressPct) progressPct.textContent = '100%';
                    if (progressText) progressText.textContent = 'Download concluído! Atualizando player...';
                    setTimeout(() => {
                        alert(data.message || "Vídeo baixado e vinculado com sucesso!");
                        window.location.reload();
                    }, 1000);
                } else if (data.status === 'error') {
                    clearInterval(timer);
                    btn.disabled = false;
                    btn.innerHTML = `<i class="fas fa-check mr-1"></i> Salvar Vídeo`;
                    alert("Erro ao baixar vídeo: " + (data.message || 'Falha desconhecida.'));
                }
            } catch (e) {
                console.warn("Erro ao consultar progresso:", e);
            }
        }, 2500);
    }

    async function promptPasteTranscription() {
        const raw = prompt("Cole o JSON com o array de frases da transcrição:\nEx: [{\"start\": 10.5, \"end\": 15.0, \"text\": \"Olha essa peça...\"}]");
        if (!raw) return;

        try {
            const parsed = JSON.parse(raw);
            const res = await fetch(`/admin/lives/${liveId}/cortes/save-transcription`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ sentences: parsed })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert("Erro: " + data.message);
            }
        } catch(e) {
            alert("JSON inválido: " + e.message);
        }
    }

    function toggleAutoProcessModal(show = null) {
        const modal = document.getElementById("modal-auto-process-instagram");
        if (!modal) return;
        if (show === null) {
            modal.classList.toggle("hidden");
        } else if (show) {
            modal.classList.remove("hidden");
        } else {
            modal.classList.add("hidden");
        }
        if (!modal.classList.contains("hidden")) {
            setTimeout(() => {
                const input = document.getElementById("auto-process-url-input");
                if (input) input.focus();
            }, 100);
        }
    }

    async function pasteClipboardToUrlInput() {
        try {
            const text = await navigator.clipboard.readText();
            if (text) {
                const input = document.getElementById("auto-process-url-input");
                if (input) {
                    input.value = text.trim();
                    input.focus();
                }
            }
        } catch (e) {
            console.warn("Clipboard access not available or denied:", e);
        }
    }

    function triggerAutoProcess() {
        toggleAutoProcessModal(true);
    }

    async function submitAutoProcess(event) {
        if (event) event.preventDefault();

        const inputUrl = document.getElementById("auto-process-url-input");
        const videoUrl = inputUrl ? inputUrl.value.trim() : '';

        const btnSubmit = document.getElementById("btn-submit-auto-process");
        const btnTop = document.getElementById("btn-auto-process");
        const oldSubmitHtml = btnSubmit ? btnSubmit.innerHTML : '';
        const oldTopHtml = btnTop ? btnTop.innerHTML : '';

        if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = `<i class="fas fa-spinner fa-spin text-white mr-1"></i> Iniciando...`;
        }
        if (btnTop) {
            btnTop.disabled = true;
            btnTop.innerHTML = `<i class="fas fa-spinner fa-spin text-white mr-1"></i> Iniciando...`;
        }

        // Exibir banner de progresso imediatamente
        const progressBox = document.getElementById("ai-process-progress-box");
        const progressText = document.getElementById("ai-process-text");
        const progressPct = document.getElementById("ai-process-pct");
        const progressBar = document.getElementById("ai-process-bar");
        if (progressBox) {
            progressBox.classList.remove("hidden");
            if (progressText) progressText.textContent = videoUrl ? "Baixando vídeo da publicação do Instagram..." : "Verificando gravação e áudio...";
            if (progressPct) progressPct.textContent = "5%";
            if (progressBar) progressBar.style.width = "5%";
        }

        // Fecha o modal
        toggleAutoProcessModal(false);

        try {
            const res = await fetch(`/admin/lives/${liveId}/cortes/auto-process`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ 
                    username: 'de_minha_mania',
                    url: videoUrl || undefined
                })
            });
            const data = await res.json();
            
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = oldSubmitHtml;
            }
            if (btnTop) {
                btnTop.disabled = false;
                btnTop.innerHTML = oldTopHtml;
            }

            if (data.is_async) {
                startTranscriptionPolling();
            } else if (data.success) {
                if (progressText) progressText.textContent = "✅ " + data.message;
                if (progressPct) progressPct.textContent = "100%";
                if (progressBar) progressBar.style.width = "100%";
                setTimeout(() => {
                    alert("✅ " + data.message);
                    window.location.reload();
                }, 1000);
            } else {
                if (progressBox) progressBox.classList.add("hidden");
                alert("⚠️ " + (data.message || 'Falha no processamento automático.'));
            }
        } catch(e) {
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = oldSubmitHtml;
            }
            if (btnTop) {
                btnTop.disabled = false;
                btnTop.innerHTML = oldTopHtml;
            }
            if (progressBox) progressBox.classList.add("hidden");
            alert("Erro de comunicação ao disparar o processamento: " + e.message);
        }
    }
</script>
@endpush
@endsection

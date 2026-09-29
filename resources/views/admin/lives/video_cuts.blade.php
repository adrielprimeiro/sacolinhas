@extends('layouts.app')

@section('title', 'Cortes de Vídeo da Live')

@section('content')
<div class="container mx-auto p-4 sm:p-6 max-w-7xl">
    
    <!-- Top Header Bar -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.live-chat.dashboard', ['live_id' => $live->id]) }}" class="w-10 h-10 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-700 transition shadow-xs">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-800 border border-indigo-200">
                        Cortes & Vídeos
                    </span>
                    <span class="text-xs text-gray-500 font-bold">
                        {{ $live->data ? $live->data->format('d/m/Y') : 'Data n/d' }} &bull; {{ $live->tipo_live_formatado }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 mt-1 flex items-center gap-2">
                    <i class="fas fa-film text-indigo-600"></i>
                    Fatiador de Vídeos da Live #{{ $live->id }}
                </h1>
            </div>
        </div>

        <!-- Badges / Stats & Ação Principal -->
        <div class="flex flex-wrap items-center gap-3">
            <div class="bg-gray-50 border border-gray-200 px-4 py-2 rounded-xl flex items-center gap-4 shadow-xs">
                <div class="text-center">
                    <div class="text-[10px] uppercase font-bold text-gray-500">Itens</div>
                    <div id="stat-total-items" class="text-sm font-black text-gray-900">{{ $stats['total_items'] }}</div>
                </div>
                <div class="w-px h-6 bg-gray-200"></div>
                <div class="text-center">
                    <div class="text-[10px] uppercase font-bold text-amber-600">Marcados</div>
                    <div id="stat-items-with-cuts" class="text-sm font-black text-amber-700">{{ $stats['items_with_cuts'] }}</div>
                </div>
                <div class="w-px h-6 bg-gray-200"></div>
                <div class="text-center">
                    <div class="text-[10px] uppercase font-bold text-green-600">Prontos</div>
                    <div id="stat-items-rendered" class="text-sm font-black text-green-700">{{ $stats['items_rendered'] }}</div>
                </div>
            </div>

            <button type="button" onclick="generateAllClips()" id="btn-batch-clips" class="bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold px-4 py-2.5 rounded-xl text-xs sm:text-sm shadow-md flex items-center gap-2 transition active:scale-95 cursor-pointer" style="background-color: #4f46e5; color: #ffffff !important;">
                <i class="fas fa-scissors text-white"></i>
                <span style="color: #ffffff !important; font-weight: 800;">Gerar Todos os Cortes (FFmpeg)</span>
            </button>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- COLUNA ESQUERDA: PLAYER DE VÍDEO & TRANSCRIÇÃO (LARGURA 5) -->
        <div class="lg:col-span-5 space-y-5">
            
            <!-- Card Player de Vídeo -->
            <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm sticky top-6">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-100">
                    <h3 class="font-black text-sm text-gray-800 flex items-center gap-2">
                        <i class="fas fa-play-circle text-indigo-600"></i>
                        Gravação Completa da Live
                    </h3>
                    <button type="button" onclick="toggleUploadModal()" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-upload"></i> {{ $recordingUrl ? 'Trocar Vídeo' : 'Enviar Vídeo' }}
                    </button>
                </div>

                <!-- Video Container -->
                <div class="relative bg-black rounded-xl overflow-hidden aspect-video flex items-center justify-center border border-gray-300">
                    @if($recordingUrl)
                        <video id="live-main-player" src="{{ $recordingUrl }}" controls class="w-full h-full object-contain" preload="metadata"></video>
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

                <!-- Controles Rápidos de Marcação -->
                <div class="mt-3 bg-gray-50 p-3 rounded-xl border border-gray-200 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono font-bold text-indigo-700" id="player-current-time">00:00</span>
                        <span class="text-[10px] text-gray-400">/</span>
                        <span class="text-[10px] font-mono text-gray-500" id="player-duration">00:00</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="markCurrentTimeToActiveItem('start')" title="Definir tempo atual como início do item selecionado" class="bg-green-100 hover:bg-green-200 text-green-800 border border-green-300 text-xs font-extrabold px-3 py-1.5 rounded-lg transition flex items-center gap-1 active:scale-95 cursor-pointer">
                            <i class="fas fa-step-forward text-[10px]"></i> Início
                        </button>
                        <button type="button" onclick="markCurrentTimeToActiveItem('end')" title="Definir tempo atual como fim do item selecionado" class="bg-amber-100 hover:bg-amber-200 text-amber-800 border border-amber-300 text-xs font-extrabold px-3 py-1.5 rounded-lg transition flex items-center gap-1 active:scale-95 cursor-pointer">
                            <i class="fas fa-stop text-[10px]"></i> Fim
                        </button>
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
            
            <div class="flex items-center justify-between gap-4 bg-white p-3.5 rounded-xl border border-gray-200 shadow-xs">
                <div class="text-xs font-black text-gray-800 flex items-center gap-2">
                    <i class="fas fa-tshirt text-indigo-600"></i>
                    <span>Itens da Live ({{ count($liveItems) }})</span>
                </div>
                <div class="text-[11px] text-gray-500 font-semibold">
                    Clique em um item para sincronizar com o player
                </div>
            </div>

            <!-- Lista de Cards de Itens -->
            <div class="space-y-3" id="live-items-list-container">
                @forelse($liveItems as $item)
                    @php
                        $hasCutTimes = $item['cut_start_sec'] !== null && $item['cut_end_sec'] !== null;
                        $isRendered = $item['video_cut_status'] === 'recorded' && !empty($item['video_cut_url']);
                    @endphp
                    <div id="item-card-{{ $item['live_item_id'] }}" 
                         onclick="selectActiveItem({{ $item['live_item_id'] }}, {{ $item['cut_start_sec'] ?? 0 }})"
                         class="item-card bg-white hover:bg-gray-50 rounded-2xl border {{ $isRendered ? 'border-green-400 bg-green-50/20' : ($hasCutTimes ? 'border-indigo-300' : 'border-gray-200') }} p-4 transition duration-150 shadow-sm cursor-pointer relative group">
                        
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            
                            <!-- Foto & Infos do Produto -->
                            <div class="flex items-center gap-3 min-w-0">
                                <img src="{{ $item['item_image'] }}" class="w-14 h-14 rounded-xl object-cover bg-gray-100 border border-gray-200 shrink-0" onerror="this.src='https://placehold.co/100x100?text=Sem+Foto'" />
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2 py-0.5 rounded-lg text-xs font-black bg-indigo-600 text-white shadow-xs" style="background-color: #4f46e5; color: #ffffff !important;">
                                            #{{ $item['codigo_live'] }}
                                        </span>
                                        @if($item['buyer_name'])
                                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-green-100 text-green-800 border border-green-200 truncate max-w-[160px]">
                                                👤 {{ $item['buyer_name'] }}
                                            </span>
                                        @endif
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
                                        <input type="number" step="0.1" min="0" 
                                               id="input-start-{{ $item['live_item_id'] }}"
                                               value="{{ $item['cut_start_sec'] ?? '' }}" 
                                               placeholder="0.0"
                                               class="w-16 px-1.5 py-1 text-xs font-mono font-bold text-center bg-white text-green-700 rounded-lg border border-gray-300 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                                    </div>

                                    <span class="text-gray-400 font-bold text-xs mt-3">➔</span>

                                    <!-- Fim -->
                                    <div class="text-center">
                                        <label class="block text-[9px] font-bold uppercase text-gray-500">Fim</label>
                                        <input type="number" step="0.1" min="0" 
                                               id="input-end-{{ $item['live_item_id'] }}"
                                               value="{{ $item['cut_end_sec'] ?? '' }}" 
                                               placeholder="0.0"
                                               class="w-16 px-1.5 py-1 text-xs font-mono font-bold text-center bg-white text-amber-700 rounded-lg border border-gray-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                                    </div>

                                    <!-- Salvar Tempo -->
                                    <button type="button" onclick="saveItemCutTime({{ $item['live_item_id'] }})" title="Salvar Minutagem" class="mt-3 p-1.5 text-gray-600 hover:text-gray-900 bg-white hover:bg-gray-200 rounded-lg transition border border-gray-200 shadow-xs">
                                        <i class="fas fa-save text-xs"></i>
                                    </button>
                                </div>

                                <!-- Ações de Vídeo -->
                                <div class="flex items-center gap-1.5">
                                    <button type="button" onclick="previewItemClip({{ $item['live_item_id'] }})" title="Reproduzir Trecho Marcado no Player" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 p-2 rounded-xl text-xs transition cursor-pointer active:scale-95 shadow-xs">
                                        <i class="fas fa-play"></i>
                                    </button>

                                    <button type="button" onclick="generateSingleClip({{ $item['live_item_id'] }})" id="btn-cut-{{ $item['live_item_id'] }}" title="Gerar Corte Individual com FFmpeg" class="bg-teal-600 hover:bg-teal-700 text-white font-bold p-2 rounded-xl text-xs transition shadow-sm cursor-pointer active:scale-95" style="background-color: #0d9488; color: #ffffff !important;">
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

                        <!-- Snippet de Transcrição (se houver) -->
                        @if(!empty($item['transcription_snippet']))
                            <div class="mt-2.5 pt-2 border-t border-gray-100 text-[11px] text-gray-600 italic bg-gray-50/70 p-2 rounded-lg">
                                <i class="fas fa-quote-left text-[9px] text-indigo-500 mr-1"></i>
                                {{ $item['transcription_snippet'] }}
                            </div>
                        @endif

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

        <div class="pt-4 mt-3 border-t border-gray-200 shrink-0 flex justify-between items-center gap-2">
            <button type="button" onclick="promptPasteTranscription()" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1">
                <i class="fas fa-paste"></i> Colar JSON de Transcrição
            </button>
            <button type="button" onclick="toggleTranscriptionModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold px-4 py-2 rounded-xl transition border border-gray-200">Fechar</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const liveId = {{ $live->id }};
    const csrfToken = '{{ csrf_token() }}';
    let activeLiveItemId = null;
    let previewTimer = null;

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
    }

    function formatTime(sec) {
        if (!sec || isNaN(sec)) return "00:00";
        const s = Math.floor(sec);
        const m = Math.floor(s / 60);
        const rem = s % 60;
        return `${String(m).padStart(2, '0')}:${String(rem).padStart(2, '0')}`;
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

        if (player && startTime !== null && startTime !== undefined) {
            player.currentTime = parseFloat(startTime);
        }
    }

    function markCurrentTimeToActiveItem(type) {
        if (!activeLiveItemId) {
            alert("Selecione um item na lista ao lado primeiro!");
            return;
        }
        if (!player) return;

        const time = Math.round(player.currentTime * 10) / 10;
        const input = document.getElementById(`input-${type}-${activeLiveItemId}`);
        if (input) {
            input.value = time;
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
        const start = parseFloat(startInput ? startInput.value : 0);
        const end = parseFloat(endInput ? endInput.value : 0);

        if (isNaN(start) || isNaN(end) || end <= start) {
            alert("Defina os tempos de início e fim válidos para visualizar o corte.");
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
        const start = parseFloat(startInput ? startInput.value : 0);
        const end = parseFloat(endInput ? endInput.value : 0);

        if (isNaN(start) || isNaN(end) || end <= start) {
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
                    cut_start_sec: start,
                    cut_end_sec: end
                })
            });
            const data = await res.json();
            if (data.success) {
                const card = document.getElementById(`item-card-${itemId}`);
                if (card) card.classList.add('border-indigo-400');
            }
        } catch (e) {
            console.error("Erro ao salvar minutagem:", e);
        }
    }

    async function autoDetectWithAI() {
        const btn = document.getElementById("btn-auto-detect");
        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin mr-1"></i> Detectando...`;

        try {
            const res = await fetch(`/admin/lives/${liveId}/cortes/auto-detect`, {
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
                alert(data.message);
                window.location.reload();
            } else {
                alert("Atenção: " + data.message);
            }
        } catch (e) {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
            alert("Erro de comunicação ao processar detecção automática.");
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

    async function generateAllClips() {
        if (!confirm("Deseja gerar todos os cortes marcados com FFmpeg agora?")) return;

        const btn = document.getElementById("btn-batch-clips");
        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin mr-1"></i> Processando Cortes...`;

        try {
            const res = await fetch(`/admin/lives/${liveId}/cortes/generate-batch`, {
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
                alert(data.message);
                window.location.reload();
            } else {
                alert("Erro: " + data.message);
            }
        } catch (e) {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
            alert("Erro ao gerar cortes em lote.");
        }
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
</script>
@endpush
@endsection

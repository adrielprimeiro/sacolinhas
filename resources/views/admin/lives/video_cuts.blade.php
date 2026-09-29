@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-900 text-slate-100 p-4 sm:p-6 lg:p-8 font-sans">
    
    <!-- Top Header Bar -->
    <div class="max-w-7xl mx-auto mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-800/80 p-5 rounded-3xl border border-slate-700/60 shadow-xl backdrop-blur-md">
        <div class="flex items-center gap-4">
            <a href="{{ route('lives.api.index') }}" class="w-10 h-10 rounded-2xl bg-slate-700/80 hover:bg-slate-700 flex items-center justify-center text-slate-300 hover:text-white transition shadow-sm">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">
                        Cortes & Vídeos
                    </span>
                    <span class="text-xs text-slate-400 font-mono">{{ $live->data ? $live->data->format('d/m/Y') : 'Data n/d' }} &bull; {{ $live->tipo_live_formatado }}</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-white mt-1 flex items-center gap-2">
                    <i class="fas fa-film text-indigo-400"></i>
                    Fatiador de Vídeos da Live #{{ $live->id }}
                </h1>
            </div>
        </div>

        <!-- Badges / Stats & Ação Principal -->
        <div class="flex flex-wrap items-center gap-3">
            <div class="bg-slate-900/80 border border-slate-700/80 px-3 py-1.5 rounded-2xl flex items-center gap-3">
                <div class="text-center">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Itens</div>
                    <div id="stat-total-items" class="text-sm font-black text-white">{{ $stats['total_items'] }}</div>
                </div>
                <div class="w-px h-6 bg-slate-700"></div>
                <div class="text-center">
                    <div class="text-[10px] uppercase font-bold text-amber-400">Marcados</div>
                    <div id="stat-items-with-cuts" class="text-sm font-black text-amber-300">{{ $stats['items_with_cuts'] }}</div>
                </div>
                <div class="w-px h-6 bg-slate-700"></div>
                <div class="text-center">
                    <div class="text-[10px] uppercase font-bold text-emerald-400">Prontos</div>
                    <div id="stat-items-rendered" class="text-sm font-black text-emerald-300">{{ $stats['items_rendered'] }}</div>
                </div>
            </div>

            <button type="button" onclick="generateAllClips()" id="btn-batch-clips" class="bg-gradient-to-r from-indigo-600 to-teal-600 hover:from-indigo-500 hover:to-teal-500 text-white font-extrabold px-4 py-2.5 rounded-2xl text-xs sm:text-sm shadow-lg shadow-indigo-600/20 flex items-center gap-2 transition active:scale-95 cursor-pointer">
                <i class="fas fa-scissors"></i>
                <span>Gerar Todos os Cortes (FFmpeg)</span>
            </button>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- COLUNA ESQUERDA: PLAYER DE VÍDEO & TRANSCRIÇÃO (LARGURA 5) -->
        <div class="lg:col-span-5 space-y-5">
            
            <!-- Card Player de Vídeo -->
            <div class="bg-slate-800/90 rounded-3xl border border-slate-700/70 p-4 shadow-xl sticky top-6">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-700/60">
                    <h3 class="font-bold text-sm text-slate-200 flex items-center gap-2">
                        <i class="fas fa-play-circle text-indigo-400"></i>
                        Gravação Completa da Live
                    </h3>
                    <button type="button" onclick="toggleUploadModal()" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1">
                        <i class="fas fa-upload"></i> {{ $recordingUrl ? 'Trocar Vídeo' : 'Enviar Vídeo' }}
                    </button>
                </div>

                <!-- Video Container -->
                <div class="relative bg-black rounded-2xl overflow-hidden aspect-[9/16] sm:aspect-video max-h-[380px] flex items-center justify-center border border-slate-700">
                    @if($recordingUrl)
                        <video id="live-main-player" src="{{ $recordingUrl }}" controls class="w-full h-full object-contain" preload="metadata"></video>
                    @else
                        <div id="no-video-placeholder" class="text-center p-6 text-slate-500">
                            <i class="fas fa-video-slash text-4xl mb-3 text-slate-600"></i>
                            <p class="text-xs font-semibold text-slate-400">Nenhum arquivo de vídeo carregado.</p>
                            <p class="text-[11px] text-slate-500 mt-1">Faça o upload do .mp4 ou defina o caminho da gravação do OBS.</p>
                            <button type="button" onclick="toggleUploadModal()" class="mt-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2 rounded-xl transition shadow">
                                <i class="fas fa-cloud-upload-alt mr-1"></i> Carregar Gravação
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Controles Rápidos de Marcação -->
                <div class="mt-3 bg-slate-900/60 p-3 rounded-2xl border border-slate-700/50 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono font-bold text-indigo-300" id="player-current-time">00:00</span>
                        <span class="text-[10px] text-slate-500">/</span>
                        <span class="text-[10px] font-mono text-slate-400" id="player-duration">00:00</span>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="markCurrentTimeToActiveItem('start')" title="Definir tempo atual como início do item selecionado" class="bg-emerald-600/20 hover:bg-emerald-600/40 text-emerald-400 border border-emerald-500/30 text-[11px] font-bold px-2.5 py-1.5 rounded-xl transition flex items-center gap-1 active:scale-95 cursor-pointer">
                            <i class="fas fa-step-forward text-[10px]"></i> Início
                        </button>
                        <button type="button" onclick="markCurrentTimeToActiveItem('end')" title="Definir tempo atual como fim do item selecionado" class="bg-amber-600/20 hover:bg-amber-600/40 text-amber-400 border border-amber-500/30 text-[11px] font-bold px-2.5 py-1.5 rounded-xl transition flex items-center gap-1 active:scale-95 cursor-pointer">
                            <i class="fas fa-stop text-[10px]"></i> Fim
                        </button>
                    </div>
                </div>

                <!-- Ações IA de Transcrição -->
                <div class="mt-3 pt-3 border-t border-slate-700/60 flex items-center justify-between gap-2">
                    <button type="button" onclick="autoDetectWithAI()" id="btn-auto-detect" class="flex-1 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white font-extrabold py-2 px-3 rounded-xl text-xs transition shadow flex items-center justify-center gap-1.5 cursor-pointer active:scale-95">
                        <i class="fas fa-wand-magic-sparkles"></i>
                        <span>Auto-Detectar com IA</span>
                    </button>
                    <button type="button" onclick="toggleTranscriptionModal()" class="bg-slate-700 hover:bg-slate-600 text-slate-200 font-bold py-2 px-3 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-file-alt"></i>
                        <span>Transcrição</span>
                    </button>
                </div>
            </div>

            <!-- Card de Dicas de Padrão de Fala -->
            <div class="bg-indigo-950/40 rounded-3xl border border-indigo-800/40 p-4 text-xs text-indigo-200 leading-relaxed shadow-lg">
                <div class="font-extrabold text-indigo-300 flex items-center gap-1.5 mb-1.5">
                    <i class="fas fa-lightbulb text-yellow-400"></i>
                    Como funciona o corte inteligente:
                </div>
                <p class="text-[11px] text-indigo-200/90">
                    O algoritmo localiza a frase em que o <b>código</b> foi falado. Ele varre para trás até o início da apresentação da peça (gatilhos como <i>"Olha esse vestido..."</i>) e varre para frente até o fechamento (<i>"Passando..."</i> ou entrega aos bastidores).
                </p>
            </div>

        </div>

        <!-- COLUNA DIREITA: LISTA DE ITENS & MINUTAGEM (LARGURA 7) -->
        <div class="lg:col-span-7 space-y-4">
            
            <div class="flex items-center justify-between gap-4 bg-slate-800/80 p-3.5 rounded-2xl border border-slate-700/60">
                <div class="text-xs font-bold text-slate-300 flex items-center gap-2">
                    <i class="fas fa-tshirt text-indigo-400"></i>
                    <span>Itens da Live ({{ count($liveItems) }})</span>
                </div>
                <div class="text-[11px] text-slate-400">
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
                         class="item-card bg-slate-800/90 hover:bg-slate-800 rounded-2xl border {{ $isRendered ? 'border-emerald-500/50 bg-emerald-950/10' : ($hasCutTimes ? 'border-indigo-500/50' : 'border-slate-700/70') }} p-4 transition-all duration-150 shadow-md cursor-pointer relative group">
                        
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            
                            <!-- Foto & Infos do Produto -->
                            <div class="flex items-center gap-3 min-w-0">
                                <img src="{{ $item['item_image'] }}" class="w-14 h-14 rounded-xl object-cover bg-slate-900 border border-slate-700 shrink-0" onerror="this.src='https://placehold.co/100x100?text=Sem+Foto'" />
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2 py-0.5 rounded-lg text-xs font-black bg-indigo-600 text-white shadow-xs">
                                            #{{ $item['codigo_live'] }}
                                        </span>
                                        @if($item['buyer_name'])
                                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 truncate max-w-[160px]">
                                                👤 {{ $item['buyer_name'] }}
                                            </span>
                                        @endif
                                        @if($isRendered)
                                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-extrabold bg-emerald-500 text-slate-900 flex items-center gap-1">
                                                <i class="fas fa-check-circle"></i> Vídeo Pronto
                                            </span>
                                        @endif
                                    </div>
                                    <h4 class="text-sm font-extrabold text-white truncate mt-1">{{ $item['item_name'] }}</h4>
                                    <div class="text-xs text-slate-400 font-mono">SKU: {{ $item['item_sku'] }} &bull; R$ {{ $item['item_price'] }}</div>
                                </div>
                            </div>

                            <!-- Minutagem & Controles -->
                            <div class="flex flex-wrap items-center gap-2 shrink-0 justify-end" onclick="event.stopPropagation();">
                                
                                <div class="flex items-center gap-1.5 bg-slate-900/90 p-1.5 rounded-xl border border-slate-700/80">
                                    <!-- Início -->
                                    <div class="text-center">
                                        <label class="block text-[9px] font-bold uppercase text-slate-500">Início</label>
                                        <input type="number" step="0.1" min="0" 
                                               id="input-start-{{ $item['live_item_id'] }}"
                                               value="{{ $item['cut_start_sec'] ?? '' }}" 
                                               placeholder="0.0"
                                               class="w-16 px-1.5 py-1 text-xs font-mono font-bold text-center bg-slate-800 text-emerald-400 rounded-lg border border-slate-700 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                                    </div>

                                    <span class="text-slate-600 font-bold text-xs mt-3">➔</span>

                                    <!-- Fim -->
                                    <div class="text-center">
                                        <label class="block text-[9px] font-bold uppercase text-slate-500">Fim</label>
                                        <input type="number" step="0.1" min="0" 
                                               id="input-end-{{ $item['live_item_id'] }}"
                                               value="{{ $item['cut_end_sec'] ?? '' }}" 
                                               placeholder="0.0"
                                               class="w-16 px-1.5 py-1 text-xs font-mono font-bold text-center bg-slate-800 text-amber-400 rounded-lg border border-slate-700 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                                    </div>

                                    <!-- Salvar Tempo -->
                                    <button type="button" onclick="saveItemCutTime({{ $item['live_item_id'] }})" title="Salvar Minutagem" class="mt-3 p-1.5 text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-lg transition">
                                        <i class="fas fa-save text-xs"></i>
                                    </button>
                                </div>

                                <!-- Ações de Vídeo -->
                                <div class="flex items-center gap-1">
                                    <button type="button" onclick="previewItemClip({{ $item['live_item_id'] }})" title="Reproduzir Trecho Marcado no Player" class="bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 p-2 rounded-xl text-xs transition cursor-pointer active:scale-95">
                                        <i class="fas fa-play"></i>
                                    </button>

                                    <button type="button" onclick="generateSingleClip({{ $item['live_item_id'] }})" id="btn-cut-{{ $item['live_item_id'] }}" title="Gerar Corte Individual com FFmpeg" class="bg-teal-600 hover:bg-teal-500 text-white font-bold p-2 rounded-xl text-xs transition shadow-sm cursor-pointer active:scale-95">
                                        <i class="fas fa-scissors"></i>
                                    </button>

                                    @if($isRendered)
                                        <a href="{{ $item['video_cut_url'] }}" target="_blank" title="Assistir / Baixar Vídeo Cortado" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold p-2 rounded-xl text-xs transition shadow-sm cursor-pointer">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    @endif
                                </div>

                            </div>

                        </div>

                        <!-- Snippet de Transcrição (se houver) -->
                        @if(!empty($item['transcription_snippet']))
                            <div class="mt-2.5 pt-2 border-t border-slate-700/50 text-[11px] text-slate-400 italic">
                                <i class="fas fa-quote-left text-[9px] text-indigo-400 mr-1"></i>
                                {{ $item['transcription_snippet'] }}
                            </div>
                        @endif

                    </div>
                @empty
                    <div class="bg-slate-800/60 border border-slate-700 rounded-2xl p-8 text-center text-slate-400">
                        <i class="fas fa-box-open text-4xl mb-3 text-slate-600"></i>
                        <p class="text-sm font-semibold">Nenhum produto vinculado a esta live ainda.</p>
                    </div>
                @endforelse
            </div>

        </div>

    </div>

</div>

<!-- MODAL UPLOAD VÍDEO -->
<div id="modal-upload-video" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-slate-800 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-700 mb-4">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <i class="fas fa-video text-indigo-400"></i> Carregar Gravação da Live
            </h3>
            <button onclick="toggleUploadModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
        </div>

        <form id="form-upload-video" onsubmit="handleVideoUpload(event)" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5">Arquivo de Vídeo (.mp4, .mov, .mkv):</label>
                <input type="file" id="video-file-input" name="video_file" accept="video/*" class="w-full text-xs text-slate-300 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer bg-slate-900 p-2 rounded-xl border border-slate-700">
            </div>

            <div class="relative flex py-2 items-center">
                <div class="flex-grow border-t border-slate-700"></div>
                <span class="flex-shrink mx-3 text-slate-500 text-[10px] uppercase font-bold">OU Caminho Local no Servidor</span>
                <div class="flex-grow border-t border-slate-700"></div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5">Caminho ou URL do Vídeo:</label>
                <input type="text" id="video-path-manual-input" name="video_path_manual" placeholder="/var/www/sacolinhas/storage/..." class="w-full px-3 py-2 text-xs bg-slate-900 text-white rounded-xl border border-slate-700 focus:border-indigo-500">
            </div>

            <div id="upload-progress-box" class="hidden space-y-1">
                <div class="flex justify-between text-[11px] text-slate-300">
                    <span id="upload-progress-text">Enviando vídeo...</span>
                    <span id="upload-progress-pct">0%</span>
                </div>
                <div class="w-full bg-slate-900 rounded-full h-2 overflow-hidden">
                    <div id="upload-progress-bar" class="bg-indigo-500 h-full w-0 transition-all duration-150"></div>
                </div>
            </div>

            <div class="pt-3 flex justify-end gap-2 border-t border-slate-700">
                <button type="button" onclick="toggleUploadModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-700 transition">Cancelar</button>
                <button type="submit" id="btn-submit-upload" class="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2 rounded-xl text-xs font-bold transition shadow flex items-center gap-1.5">
                    <i class="fas fa-check"></i> Salvar Vídeo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL TRANSCRIÇÃO -->
<div id="modal-transcription" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-slate-800 border border-slate-700 rounded-3xl max-w-2xl w-full p-6 shadow-2xl flex flex-col max-h-[85vh]">
        <div class="flex items-center justify-between pb-3 border-b border-slate-700 mb-4 shrink-0">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <i class="fas fa-closed-captioning text-teal-400"></i> Transcrição da Live com Timestamps
            </h3>
            <button onclick="toggleTranscriptionModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
        </div>

        <div class="flex-1 overflow-y-auto space-y-2 pr-1" id="transcription-sentences-list">
            @forelse($transcriptionData as $idx => $st)
                <div onclick="seekPlayer({{ $st['start'] ?? 0 }})" class="p-2.5 rounded-xl bg-slate-900/80 hover:bg-indigo-950/60 border border-slate-700/60 cursor-pointer transition flex items-start gap-2.5">
                    <span class="text-[10px] font-mono font-bold bg-indigo-900/60 text-indigo-300 border border-indigo-700/40 px-2 py-0.5 rounded-lg shrink-0 mt-0.5">
                        {{ gmdate(($st['start'] ?? 0) >= 3600 ? 'H:i:s' : 'i:s', (int) ($st['start'] ?? 0)) }}
                    </span>
                    <p class="text-xs text-slate-200 leading-relaxed">{{ $st['text'] ?? '' }}</p>
                </div>
            @empty
                <div class="text-center py-10 text-slate-500">
                    <i class="fas fa-comment-slash text-3xl mb-2 text-slate-600"></i>
                    <p class="text-xs font-semibold">Nenhuma frase transcrita ainda.</p>
                    <p class="text-[11px] text-slate-500 mt-1">Cole a transcrição abaixo em formato JSON ou utilize a captura em tempo real.</p>
                </div>
            @endforelse
        </div>

        <div class="pt-4 mt-3 border-t border-slate-700 shrink-0 flex justify-between items-center gap-2">
            <button type="button" onclick="promptPasteTranscription()" class="text-xs text-indigo-400 hover:text-indigo-300 font-bold flex items-center gap-1">
                <i class="fas fa-paste"></i> Colar JSON de Transcrição
            </button>
            <button type="button" onclick="toggleTranscriptionModal()" class="bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold px-4 py-2 rounded-xl transition">Fechar</button>
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
            el.classList.remove('ring-2', 'ring-indigo-400', 'bg-slate-700/60');
        });
        const activeCard = document.getElementById(`item-card-${itemId}`);
        if (activeCard) {
            activeCard.classList.add('ring-2', 'ring-indigo-400', 'bg-slate-700/60');
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
                if (card) card.classList.add('border-indigo-500/50');
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

        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin mr-1"></i> Salvando...`;

        const progressBox = document.getElementById("upload-progress-box");
        const progressBar = document.getElementById("upload-progress-bar");
        const progressPct = document.getElementById("upload-progress-pct");
        if (progressBox) progressBox.classList.remove("hidden");

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
            btn.disabled = false;
            btn.innerHTML = `<i class="fas fa-check mr-1"></i> Salvar Vídeo`;
            try {
                const data = JSON.parse(xhr.responseText);
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert("Erro: " + (data.message || 'Falha ao salvar vídeo'));
                }
            } catch (err) {
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

@extends('layouts.app')

@section('title', 'Leitor de QRCode & Bipagem Contínua')

@section('content')
<div class="container mx-auto px-3 sm:px-4 py-4 max-w-[1700px]">
    <!-- Header Superior -->
    <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-white p-4 rounded-2xl shadow-sm border border-gray-150">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-xl shadow-md shrink-0">
                <i class="fas fa-qrcode"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-gray-800 flex items-center gap-2 tracking-tight">
                    <span>Leitor de QRCode & Bipagem Contínua</span>
                    @if($activeLive && $activeLive->ativo)
                        <span class="bg-green-100 text-green-700 text-xs font-extrabold px-2.5 py-0.5 rounded-full border border-green-300 animate-pulse">AO VIVO</span>
                    @endif
                </h1>
                <p class="text-xs text-gray-500 font-medium">Bipe produtos diretamente para as clientes conectadas na transmissão.</p>
            </div>
        </div>
        
        <!-- Ações do Header -->
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            <!-- Link para Feed do Chat Ao Vivo -->
            <a href="{{ route('admin.live-chat.feed', ['live_id' => $activeLive ? $activeLive->id : '']) }}" target="_blank" class="bg-purple-600 hover:bg-purple-700 text-white font-extrabold px-3.5 py-2 rounded-xl text-xs shadow transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-comment-dots text-purple-200"></i>
                <span>Chat da Transmissão</span>
            </a>

            <!-- Link para Captura do Chat -->
            <a href="{{ route('admin.live-chat.dashboard', ['live_id' => $activeLive ? $activeLive->id : '']) }}" class="bg-gray-800 hover:bg-gray-900 text-white font-bold px-3.5 py-2 rounded-xl text-xs shadow transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-sliders-h text-indigo-400"></i>
                <span>Config Captura</span>
            </a>

            @if($activeLive && $activeLive->ativo)
                <button type="button" onclick="confirmEndLive({{ $activeLive->id }})" class="bg-red-600 hover:bg-red-700 text-white font-bold px-3.5 py-2 rounded-xl text-xs shadow transition flex items-center gap-1.5 cursor-pointer active:scale-95">
                    <i class="fas fa-stop-circle text-sm"></i>
                    <span>Encerrar Live & WhatsApp</span>
                </button>
            @endif

            <div class="flex items-center gap-2 bg-gray-50 p-1.5 px-2.5 rounded-xl border border-gray-200">
                <label for="live-select" class="text-xs font-bold text-gray-600 shrink-0">Live:</label>
                <form action="{{ route('admin.live-chat.bipagem') }}" method="GET" class="flex gap-2 m-0">
                    <select name="live_id" id="live-select" onchange="this.form.submit()" class="text-xs rounded-lg border border-gray-300 bg-white p-1.5 text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 font-bold">
                        <option value="">Selecione uma Live...</option>
                        @foreach($lives as $l)
                            <option value="{{ $l->id }}" {{ ($activeLive && $activeLive->id === $l->id) ? 'selected' : '' }}>
                                #{{ $l->id }} - {{ $l->tipo_live_formatado }} ({{ $l->data->format('d/m/Y') }}) {{ $l->ativo ? '[ATIVA]' : '' }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </div>

    @if(!$activeLive)
        <div class="bg-amber-50 border-l-4 border-amber-400 p-5 rounded-2xl shadow-sm mb-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-2xl text-amber-500 mr-3"></i>
                <div>
                    <h3 class="text-base font-bold text-amber-900">Nenhuma Live Selecionada ou Ativa</h3>
                    <p class="text-xs text-amber-700 mt-0.5">Selecione uma live no topo para carregar a lista de participantes e começar a bipar.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- GRID PRINCIPAL DE OPERAÇÃO (2 COLUNAS) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
        
        <!-- COLUNA ESQUERDA: LEITOR & CLIENTE SELECIONADA (LARGURA 7 EM DESKTOP) -->
        <div class="lg:col-span-7 flex flex-col gap-3.5">
            
            <!-- CARD 1: CLIENTE SELECIONADA PARA O BIPE -->
            <div class="bg-gray-900 text-white rounded-2xl p-4 shadow-md border border-gray-800 transition-all" id="active-client-card">
                <!-- Estado Quando NÃO há cliente selecionada -->
                <div id="no-client-selected-state" class="flex items-center justify-between py-2">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gray-800 border border-gray-700 flex items-center justify-center text-indigo-400 text-xl shadow-inner">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-extrabold text-white">Nenhuma Cliente Selecionada</h3>
                            <p class="text-xs text-gray-400">Clique em uma cliente na lista <strong>"Pessoas Online"</strong> ao lado para bipar para ela.</p>
                        </div>
                    </div>
                    <span class="hidden sm:inline-flex items-center gap-1 text-[11px] font-bold bg-indigo-950 text-indigo-300 border border-indigo-800 px-3 py-1 rounded-xl">
                        <i class="fas fa-arrow-right"></i> Selecione ao lado
                    </span>
                </div>

                <!-- Estado Quando HÁ cliente selecionada -->
                <div id="client-selected-state" class="hidden flex-col gap-3">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-800 pb-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="relative shrink-0">
                                <div id="selected-client-avatar-container">
                                    <div id="selected-client-avatar-placeholder" class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center font-black text-base text-white shadow-md">
                                        CL
                                    </div>
                                    <img id="selected-client-avatar-img" src="" class="w-12 h-12 rounded-2xl object-cover border border-gray-700 hidden shadow-md" onerror="this.classList.add('hidden'); document.getElementById('selected-client-avatar-placeholder').classList.remove('hidden');" />
                                </div>
                                <span id="selected-client-platform-badge" class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-white flex items-center justify-center text-[9px] shadow">
                                    <i class="fab fa-instagram text-purple-600"></i>
                                </span>
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-1">
                                        <i class="fas fa-check-circle"></i> Bipando Para:
                                    </span>
                                    <span id="selected-client-phone-badge" class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-gray-800 text-gray-400 border border-gray-700">
                                        Carregando...
                                    </span>
                                </div>
                                <h2 class="text-base sm:text-lg font-black text-white truncate flex items-center gap-1.5" id="selected-client-title">
                                    @usuario <span class="text-xs font-normal text-gray-300">(Nome do Cliente)</span>
                                </h2>
                            </div>
                        </div>

                        <!-- Botão Limpar / Trocar Cliente -->
                        <button type="button" onclick="clearSelectedClient()" class="text-xs font-bold bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white px-3 py-1.5 rounded-xl border border-gray-700 transition flex items-center gap-1 shrink-0 cursor-pointer shadow-sm">
                            <i class="fas fa-sync-alt text-[10px]"></i> Trocar
                        </button>
                    </div>

                    <!-- Linha do WhatsApp Inline -->
                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 bg-gray-950/70 p-2 rounded-xl border border-gray-800/80">
                        <label class="text-[11px] font-bold text-gray-400 flex items-center gap-1 shrink-0">
                            <i class="fab fa-whatsapp text-emerald-400 text-xs"></i> WhatsApp:
                        </label>
                        <input type="text" id="selected-client-phone-input" placeholder="DDD + Número (ex: 11999999999)" class="flex-1 px-3 py-1.5 rounded-lg border border-gray-700 bg-gray-900 text-white placeholder-gray-500 text-xs font-bold focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                        <button type="button" onclick="saveSelectedClientPhone()" id="selected-client-phone-save-btn" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1 shrink-0 cursor-pointer active:scale-95 shadow">
                            <i class="fas fa-save"></i> Salvar
                        </button>
                    </div>

                    <!-- Mensagens Recentes desta Cliente na Live -->
                    <div class="bg-gray-950/50 p-2.5 rounded-xl border border-gray-800/60">
                        <div class="flex items-center justify-between mb-1.5 text-[11px]">
                            <span class="font-bold text-indigo-300 flex items-center gap-1">
                                <i class="fas fa-comment-dots"></i> Comentários Recentes da Cliente:
                            </span>
                            <span class="text-[10px] text-gray-500">Clique no código para bipe automático</span>
                        </div>
                        <div id="selected-client-comments-list" class="max-h-24 overflow-y-auto space-y-1 pr-1 text-xs">
                            <p class="text-[11px] text-gray-500 italic">Nenhum comentário recente desta cliente nesta live.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD 2: CÂMERA & LEITOR DE QR CODE PERMANENTE -->
            <div class="bg-black rounded-2xl overflow-hidden relative border-2 border-indigo-500 shadow-xl flex flex-col" style="min-height: 380px;">
                <!-- Toolbar da Câmera -->
                <div class="bg-gray-900/95 border-b border-gray-800 px-3 py-2 flex items-center justify-between gap-2 z-10">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                        <span class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fas fa-camera text-indigo-400"></i> Câmera Ativa
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <select id="camera-select" onchange="changeCamera(this.value)" class="text-[11px] rounded-lg border border-gray-700 bg-gray-800 text-gray-200 p-1 font-semibold focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                            <option value="">Carregando câmeras...</option>
                        </select>
                        <button type="button" onclick="restartScannerCamera()" title="Reiniciar Câmera" class="p-1 px-2 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs border border-gray-700 transition cursor-pointer">
                            <i class="fas fa-redo-alt"></i>
                        </button>
                    </div>
                </div>

                <!-- Feed da Câmera com html5-qrcode -->
                <div class="relative flex-1 bg-black flex items-center justify-center" style="min-height: 320px;">
                    <div id="continuous-qr-reader" class="w-full h-full flex-1"></div>
                    
                    <!-- Overlay de Instrução no Rodapé da Câmera -->
                    <div class="absolute bottom-3 left-0 right-0 flex justify-center pointer-events-none z-10 px-4">
                        <div class="bg-gray-900/90 backdrop-blur-md px-4 py-1.5 rounded-full border border-gray-700 text-gray-200 text-xs font-semibold flex items-center gap-2 shadow-lg">
                            <i class="fas fa-expand text-indigo-400"></i>
                            <span>Aponte a etiqueta (QR Code ou Código de Barras)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD 3: ENTRADA MANUAL / LEITOR USB -->
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200 flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <label for="manual-code-input" class="text-xs font-extrabold text-gray-700 flex items-center gap-1.5">
                        <i class="fas fa-keyboard text-indigo-600 text-sm"></i>
                        <span>Bipador USB / Digitação Manual do Código:</span>
                    </label>
                    <span class="text-[10.5px] font-semibold text-gray-400">Pressione ENTER após digitar</span>
                </div>
                <div class="flex gap-2">
                    <input type="text" id="manual-code-input" onkeydown="if(event.key==='Enter') handleManualOrUsbScan(this.value)" placeholder="Digite o código da etiqueta (ex: 0001, 73254...) e tecle Enter" class="flex-1 px-3.5 py-2.5 rounded-xl border-2 border-gray-300 bg-gray-50 text-gray-900 placeholder-gray-400 text-sm font-bold focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none shadow-inner">
                    <button type="button" onclick="handleManualOrUsbScan(document.getElementById('manual-code-input').value)" class="bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold px-5 py-2.5 rounded-xl text-xs shadow-md hover:shadow-indigo-500/25 transition flex items-center justify-center gap-1.5 shrink-0 active:scale-95 cursor-pointer">
                        <i class="fas fa-plus"></i> Bipar
                    </button>
                </div>
            </div>

            <!-- FEEDBACK VISUAL EM TEMPO REAL (BANNER) -->
            <div id="scan-feedback-banner" class="p-4 rounded-2xl text-xs font-bold hidden transition duration-200 border shadow-md leading-relaxed"></div>

            <!-- CARD 4: HISTÓRICO DE PRODUTOS BIPADOS NESTA SESSÃO -->
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200 flex flex-col gap-2.5">
                <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                    <h3 class="text-xs font-extrabold text-gray-800 flex items-center gap-1.5">
                        <i class="fas fa-history text-indigo-600"></i>
                        <span>Peças Bipadas Nesta Sessão</span>
                    </h3>
                    <span id="session-scanned-count" class="bg-indigo-100 text-indigo-800 font-extrabold text-[11px] px-2.5 py-0.5 rounded-full">
                        0 peças
                    </span>
                </div>
                <div id="session-scanned-list" class="max-h-52 overflow-y-auto space-y-2 pr-1 text-xs">
                    <p class="text-xs text-gray-400 text-center py-4">Nenhum produto bipado nesta sessão ainda.</p>
                </div>
            </div>

        </div>

        <!-- COLUNA DIREITA: LISTA DE CLIENTES / PESSOAS ONLINE (LARGURA 5 EM DESKTOP) -->
        <div class="lg:col-span-5 flex flex-col bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden" style="height: calc(100vh - 120px); min-height: 600px;">
            
            <!-- Header da Coluna com Contagem e Indicador de Polling -->
            <div class="bg-gray-900 text-white p-3.5 px-4 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-indigo-600/30 border border-indigo-500/40 flex items-center justify-center text-indigo-400 text-sm">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold text-white leading-tight">Pessoas Online na Live</h2>
                        <span class="text-[10px] text-gray-400 font-medium flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span> Atualização em tempo real
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span id="online-users-count" class="bg-indigo-600 text-white text-xs font-black px-2.5 py-1 rounded-xl shadow-sm">
                        0 online
                    </span>
                </div>
            </div>

            <!-- Busca Rápida de Clientes Cadastrados (Avulso) -->
            <div class="p-3 border-b border-gray-150 bg-gray-50/80 shrink-0 relative z-30">
                <div class="relative">
                    <input type="text" id="avulso-search-input" placeholder="🔍 Buscar cliente por nome, apelido, whatsapp..." class="w-full p-2.5 pl-9 rounded-xl border border-gray-300 bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-xs font-bold text-gray-800 placeholder-gray-400 shadow-sm transition">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-xs"></i>
                </div>

                <!-- Resultados Dropdown da Busca Avulsa -->
                <div id="avulso-search-results" class="absolute left-3 right-3 mt-1 max-h-60 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-2xl hidden flex flex-col z-50">
                </div>
            </div>

            <!-- Filtros Rápidos da Lista Online -->
            <div class="px-3 py-2 bg-gray-100 border-b border-gray-200 flex items-center justify-between text-xs shrink-0">
                <div class="flex items-center gap-1.5">
                    <button type="button" id="online-filter-all" onclick="setOnlineFilter('all')" class="px-2.5 py-1 rounded-lg font-bold text-[11px] bg-indigo-600 text-white shadow-xs cursor-pointer">
                        Todas
                    </button>
                    <button type="button" id="online-filter-registered" onclick="setOnlineFilter('registered')" class="px-2.5 py-1 rounded-lg font-bold text-[11px] bg-white text-gray-700 hover:bg-gray-200 border border-gray-300 transition cursor-pointer">
                        Cadastradas
                    </button>
                    <button type="button" id="online-filter-unregistered" onclick="setOnlineFilter('unregistered')" class="px-2.5 py-1 rounded-lg font-bold text-[11px] bg-white text-amber-700 hover:bg-amber-50 border border-amber-300 transition cursor-pointer">
                        Sem Cadastro
                    </button>
                </div>
                <span class="text-[10.5px] text-gray-500 font-medium">Clique para selecionar</span>
            </div>

            <!-- Lista Scrollável de Pessoas Online -->
            <div id="online-users-container" class="flex-1 overflow-y-auto p-3 space-y-2 bg-gray-50/50">
                <div class="flex flex-col items-center justify-center h-full py-12 text-gray-400">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2 text-indigo-500"></i>
                    <p class="text-xs">Carregando participantes da live...</p>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- MODAL VINCULAR CLIENTE (QUANDO NÃO CADASTRADO) -->
<div id="link-user-modal" class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4" style="z-index: 99999;">
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-md w-full p-5 sm:p-6 mx-auto">
        <div class="flex justify-between items-start border-b border-gray-100 pb-3 mb-3">
            <h3 class="text-base font-extrabold text-gray-800 flex items-center gap-2">
                <i class="fas fa-link text-indigo-600"></i>
                <span>Vincular Usuário da Live</span>
            </h3>
            <button onclick="closeLinkModal()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold leading-none">&times;</button>
        </div>
        
        <p class="text-xs text-gray-600 mb-3 leading-relaxed">
            Associe o usuário <strong id="modal-display-username" class="text-indigo-600 font-bold">@usuario</strong> da plataforma <strong id="modal-display-platform" class="text-gray-800">INSTAGRAM</strong> a um cadastro de cliente no sistema.
        </p>
        
        <input type="hidden" id="modal-input-username">
        <input type="hidden" id="modal-input-platform">

        <div class="mb-3">
            <label class="block text-xs font-bold text-gray-700 mb-1">Buscar Cliente por Nome, Apelido ou Celular:</label>
            <input type="text" id="modal-search-input" onkeyup="searchClientsModal(this.value)" placeholder="Digite para pesquisar..." class="w-full p-2.5 rounded-xl border border-gray-300 text-xs font-bold focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div id="modal-search-results" class="max-h-48 overflow-y-auto space-y-1.5 border border-gray-200 rounded-xl p-2 bg-gray-50">
            <p class="text-xs text-gray-400 text-center py-4">Comece a digitar para pesquisar clientes.</p>
        </div>
        
        <div class="mt-4 flex justify-end gap-2 border-t border-gray-100 pt-3">
            <button onclick="closeLinkModal()" class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100">Cancelar</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<!-- Biblioteca HTML5-QRCode -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>

<script>
    // =========================================================================
    // ESTADO GLOBAL & VARIÁVEIS DO SISTEMA DE BIPAGEM CONTÍNUA
    // =========================================================================
    const liveId = "{{ $activeLive ? $activeLive->id : '' }}";
    let activeClient = null; // { userId, username, clientName, platform, whatsapp, avatarUrl }
    let rawOnlineUsers = [];
    let onlineUsersMap = {};
    let allLiveMessages = [];
    let sessionScannedItems = [];
    let html5QrScanner = null;
    let availableCameras = [];
    let selectedCameraId = null;
    let isScanProcessing = false;
    let lastScannedCode = "";
    let lastScannedTime = 0;
    let onlineFilterMode = 'all'; // 'all' | 'registered' | 'unregistered'
    let pollingInterval = null;

    // =========================================================================
    // INICIALIZAÇÃO
    // =========================================================================
    document.addEventListener("DOMContentLoaded", function() {
        if (liveId) {
            fetchChatAndOnlineData();
            pollingInterval = setInterval(fetchChatAndOnlineData, 3000);
        }

        initPermanentCameraScanner();

        // Focar no campo manual de código
        const manualInput = document.getElementById("manual-code-input");
        if (manualInput) manualInput.focus();
    });

    // =========================================================================
    // SINTETIZADOR DE ÁUDIO (FEEDBACK SONORO INSTANTÂNEO VIA WEB AUDIO API)
    // =========================================================================
    function playSuccessBeep() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.type = "sine";
            osc.frequency.setValueAtTime(880, audioCtx.currentTime); // Tom agudo agradável (A5)
            osc.frequency.setValueAtTime(1760, audioCtx.currentTime + 0.08); // (A6)
            gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.25);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.25);
        } catch(e) {}
    }

    function playErrorBeep() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.type = "sawtooth";
            osc.frequency.setValueAtTime(320, audioCtx.currentTime);
            osc.frequency.setValueAtTime(180, audioCtx.currentTime + 0.12);
            gain.gain.setValueAtTime(0.35, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.3);
        } catch(e) {}
    }

    // =========================================================================
    // POLLING DE DADOS (PESSOAS ONLINE & COMENTÁRIOS)
    // =========================================================================
    function fetchChatAndOnlineData() {
        if (!liveId) return;

        fetch(`/admin/lives/${liveId}/chat-data`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    allLiveMessages = data.messages || [];
                    rawOnlineUsers = data.online_users || [];

                    // Mapear usuários
                    onlineUsersMap = {};
                    rawOnlineUsers.forEach(u => {
                        if (u.user_id) onlineUsersMap[u.user_id] = u;
                        if (u.username) onlineUsersMap[u.username.toLowerCase()] = u;
                    });

                    renderOnlineUsersList();

                    // Se a cliente selecionada estiver ativa, atualizar dados e comentários dela
                    if (activeClient) {
                        const updatedUser = rawOnlineUsers.find(u => (u.user_id && u.user_id == activeClient.userId) || (u.username && u.username.toLowerCase() === activeClient.username.toLowerCase()));
                        if (updatedUser) {
                            if (updatedUser.user_whatsapp && !activeClient.whatsapp) {
                                activeClient.whatsapp = updatedUser.user_whatsapp;
                                const phoneInput = document.getElementById("selected-client-phone-input");
                                if (phoneInput && !phoneInput.value) phoneInput.value = activeClient.whatsapp;
                                updateSelectedPhoneBadge(true, activeClient.whatsapp);
                            }
                            if (updatedUser.avatar_url && !activeClient.avatarUrl) {
                                activeClient.avatarUrl = updatedUser.avatar_url;
                            }
                        }
                        renderActiveClientComments();
                    }
                }
            })
            .catch(err => console.error("Erro no polling da live:", err));
    }

    // =========================================================================
    // RENDERIZAÇÃO DA LISTA DE PESSOAS ONLINE
    // =========================================================================
    function setOnlineFilter(mode) {
        onlineFilterMode = mode;
        const btnAll = document.getElementById("online-filter-all");
        const btnReg = document.getElementById("online-filter-registered");
        const btnUnreg = document.getElementById("online-filter-unregistered");

        btnAll.className = mode === 'all' ? "px-2.5 py-1 rounded-lg font-bold text-[11px] bg-indigo-600 text-white shadow-xs cursor-pointer" : "px-2.5 py-1 rounded-lg font-bold text-[11px] bg-white text-gray-700 hover:bg-gray-200 border border-gray-300 transition cursor-pointer";
        btnReg.className = mode === 'registered' ? "px-2.5 py-1 rounded-lg font-bold text-[11px] bg-emerald-600 text-white shadow-xs cursor-pointer" : "px-2.5 py-1 rounded-lg font-bold text-[11px] bg-white text-gray-700 hover:bg-gray-200 border border-gray-300 transition cursor-pointer";
        btnUnreg.className = mode === 'unregistered' ? "px-2.5 py-1 rounded-lg font-bold text-[11px] bg-amber-500 text-white shadow-xs cursor-pointer" : "px-2.5 py-1 rounded-lg font-bold text-[11px] bg-white text-amber-700 hover:bg-amber-50 border border-amber-300 transition cursor-pointer";

        renderOnlineUsersList();
    }

    function renderOnlineUsersList() {
        const container = document.getElementById("online-users-container");
        const countBadge = document.getElementById("online-users-count");
        if (!container) return;

        let list = rawOnlineUsers;
        countBadge.textContent = `${list.length} online`;

        if (onlineFilterMode === 'registered') {
            list = list.filter(u => !!u.user_id);
        } else if (onlineFilterMode === 'unregistered') {
            list = list.filter(u => !u.user_id);
        }

        if (list.length === 0) {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full py-12 text-gray-400">
                    <i class="fas fa-user-slash text-3xl mb-2 text-gray-300"></i>
                    <p class="text-xs text-center font-semibold">Nenhum participante encontrado ${onlineFilterMode !== 'all' ? 'neste filtro' : 'nesta live'}.</p>
                </div>
            `;
            return;
        }

        let html = '';
        list.forEach(u => {
            const isTikTok = u.plataforma === 'tiktok';
            const platformIcon = isTikTok ? '<i class="fab fa-tiktok text-pink-500 text-xs"></i>' : '<i class="fab fa-instagram text-purple-600 text-xs"></i>';
            const initials = u.username.slice(0, 2).toUpperCase();

            const isCurrentActive = activeClient && activeClient.userId && activeClient.userId == u.user_id;

            const avatarHtml = u.avatar_url
                ? `<img src="${escapeHtml(u.avatar_url)}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" class="w-10 h-10 rounded-xl object-cover border border-gray-200 shadow-sm" /><div class="w-10 h-10 rounded-xl bg-indigo-100 items-center justify-center font-black text-xs text-indigo-700 hidden">${initials}</div>`
                : `<div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center font-black text-xs text-indigo-700">${initials}</div>`;

            let displayName = escapeHtml(u.user_name || '');
            if (u.user_apelido) {
                displayName = displayName ? `${displayName} (${escapeHtml(u.user_apelido)})` : escapeHtml(u.user_apelido);
            }

            let badgeHtml = '';
            if (u.user_id) {
                badgeHtml = `<span class="text-[9.5px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded-md flex items-center gap-1"><i class="fas fa-check-circle text-emerald-500"></i> Cadastrada</span>`;
            } else {
                badgeHtml = `<span class="text-[9.5px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded-md flex items-center gap-1"><i class="fas fa-exclamation-triangle text-amber-500"></i> Sem Cadastro</span>`;
            }

            const activeRing = isCurrentActive ? 'ring-2 ring-emerald-500 bg-emerald-50/80 border-emerald-300' : 'bg-white border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/50';

            const userJson = JSON.stringify({
                userId: u.user_id,
                username: u.username,
                clientName: displayName || u.username,
                platform: u.plataforma,
                whatsapp: u.user_whatsapp || '',
                avatarUrl: u.avatar_url || ''
            }).replace(/"/g, '&quot;');

            html += `
                <div onclick="handleSelectClientFromList(${userJson})" class="flex items-center justify-between p-2.5 rounded-xl border ${activeRing} cursor-pointer transition duration-150 shadow-xs group">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="relative shrink-0">
                            ${avatarHtml}
                            <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-white flex items-center justify-center text-[8px] shadow border border-gray-200">${platformIcon}</span>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="font-extrabold text-xs text-gray-900 group-hover:text-indigo-600 truncate">@${escapeHtml(u.username)}</span>
                                ${badgeHtml}
                            </div>
                            <div class="text-[11px] text-gray-500 truncate font-medium">
                                ${displayName || '<span class="text-gray-400 italic">Visto às ' + u.last_seen + '</span>'}
                            </div>
                        </div>
                    </div>

                    <div class="shrink-0 flex items-center gap-1.5">
                        ${
                            u.user_id ?
                            (isCurrentActive ? 
                                `<span class="bg-emerald-600 text-white font-extrabold text-[10px] px-2.5 py-1 rounded-lg shadow-sm flex items-center gap-1"><i class="fas fa-check"></i> Ativa</span>`
                                :
                                `<button type="button" class="bg-indigo-50 group-hover:bg-indigo-600 group-hover:text-white text-indigo-700 font-extrabold text-[10.5px] px-2.5 py-1 rounded-lg transition shadow-xs flex items-center gap-1"><i class="fas fa-qrcode"></i> Bipar</button>`
                            )
                            :
                            `<button type="button" onclick="event.stopPropagation(); openLinkModal('${escapeHtml(u.username)}', '${escapeHtml(u.plataforma)}')" class="bg-amber-500 hover:bg-amber-600 text-white font-bold text-[10.5px] px-2.5 py-1 rounded-lg transition shadow-xs flex items-center gap-1"><i class="fas fa-link"></i> Vincular</button>`
                        }
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // =========================================================================
    // SELEÇÃO & GERENCIAMENTO DA CLIENTE ATIVA
    // =========================================================================
    function handleSelectClientFromList(userObj) {
        if (!userObj.userId) {
            openLinkModal(userObj.username, userObj.platform || 'instagram');
            return;
        }

        selectActiveClient(userObj);
    }

    function selectActiveClient(userObj) {
        activeClient = {
            userId: userObj.userId,
            username: userObj.username,
            clientName: userObj.clientName || userObj.username,
            platform: userObj.platform || 'instagram',
            whatsapp: userObj.whatsapp || '',
            avatarUrl: userObj.avatarUrl || ''
        };

        // Atualizar UI do Card Superior
        document.getElementById("no-client-selected-state").classList.add("hidden");
        document.getElementById("client-selected-state").classList.remove("hidden");
        document.getElementById("client-selected-state").classList.add("flex");

        // Título e dados
        const titleEl = document.getElementById("selected-client-title");
        if (titleEl) {
            titleEl.innerHTML = `@${escapeHtml(activeClient.username)} <span class="text-xs font-normal text-gray-300">(${escapeHtml(activeClient.clientName)})</span>`;
        }

        // Avatar
        const avatarImg = document.getElementById("selected-client-avatar-img");
        const avatarPlaceholder = document.getElementById("selected-client-avatar-placeholder");
        if (activeClient.avatarUrl) {
            avatarImg.src = activeClient.avatarUrl;
            avatarImg.classList.remove("hidden");
            avatarPlaceholder.classList.add("hidden");
        } else {
            avatarImg.classList.add("hidden");
            avatarPlaceholder.classList.remove("hidden");
            avatarPlaceholder.textContent = activeClient.username.slice(0, 2).toUpperCase();
        }

        // Ícone da plataforma
        const platformBadge = document.getElementById("selected-client-platform-badge");
        if (platformBadge) {
            platformBadge.innerHTML = activeClient.platform === 'tiktok' 
                ? '<i class="fab fa-tiktok text-pink-500"></i>' 
                : '<i class="fab fa-instagram text-purple-600"></i>';
        }

        // Telefone / WhatsApp
        const phoneInput = document.getElementById("selected-client-phone-input");
        if (phoneInput) phoneInput.value = activeClient.whatsapp || '';
        updateSelectedPhoneBadge(!!activeClient.whatsapp, activeClient.whatsapp);

        // Renderizar comentários da cliente
        renderActiveClientComments();

        // Renderizar novamente lista de online para marcar a borda ativa
        renderOnlineUsersList();

        // Feedback toast
        showToast(`Cliente selecionada: @${activeClient.username}`);

        // Focar no input de código manual
        const manualInput = document.getElementById("manual-code-input");
        if (manualInput) manualInput.focus();
    }

    function clearSelectedClient() {
        activeClient = null;
        document.getElementById("no-client-selected-state").classList.remove("hidden");
        document.getElementById("client-selected-state").classList.add("hidden");
        document.getElementById("client-selected-state").classList.remove("flex");
        renderOnlineUsersList();
    }

    function updateSelectedPhoneBadge(hasPhone, phoneText) {
        const badge = document.getElementById("selected-client-phone-badge");
        if (!badge) return;

        if (hasPhone && phoneText && phoneText.length >= 8) {
            badge.className = "text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-emerald-900/80 text-emerald-300 border border-emerald-600";
            badge.innerHTML = `<i class="fas fa-check-circle text-emerald-400"></i> WhatsApp OK`;
        } else {
            badge.className = "text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-amber-900/80 text-amber-300 border border-amber-600 animate-pulse";
            badge.innerHTML = `<i class="fas fa-exclamation-triangle text-amber-400"></i> Sem WhatsApp`;
        }
    }

    async function saveSelectedClientPhone() {
        if (!activeClient || !activeClient.userId) return;
        const input = document.getElementById("selected-client-phone-input");
        const phone = input ? input.value.trim() : '';

        if (!phone || phone.length < 8) {
            alert("Por favor, digite um número de WhatsApp válido com DDD.");
            return;
        }

        const btn = document.getElementById("selected-client-phone-save-btn");
        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`;

        try {
            const res = await fetch('/admin/live-chat/update-user-phone', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    user_id: activeClient.userId,
                    phone: phone
                })
            });
            const data = await res.json();
            btn.disabled = false;
            btn.innerHTML = oldHtml;

            if (data.success) {
                showToast("WhatsApp salvo com sucesso!");
                activeClient.whatsapp = data.phone;
                updateSelectedPhoneBadge(true, data.phone);
                if (onlineUsersMap[activeClient.userId]) {
                    onlineUsersMap[activeClient.userId].user_whatsapp = data.phone;
                }
            } else {
                alert("Erro ao salvar WhatsApp: " + (data.message || 'Verifique o número'));
            }
        } catch (e) {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
            console.error("Erro ao salvar telefone:", e);
            alert("Erro de comunicação ao salvar o telefone.");
        }
    }

    function renderActiveClientComments() {
        const container = document.getElementById("selected-client-comments-list");
        if (!container || !activeClient) return;

        const uLower = activeClient.username.toLowerCase().replace(/^@/, '');
        const msgs = allLiveMessages.filter(m => m.username && m.username.toLowerCase() === uLower);

        if (msgs.length === 0) {
            container.innerHTML = `<p class="text-[11px] text-gray-500 italic py-1">Nenhum comentário recente desta cliente nesta live.</p>`;
            return;
        }

        let html = '';
        msgs.slice(-8).reverse().forEach(msg => {
            const time = new Date(msg.created_at).toLocaleTimeString();
            let textWithCodes = escapeHtml(msg.message);
            // Destacar e tornar clicável qualquer código numérico de 3 a 6 dígitos
            textWithCodes = textWithCodes.replace(/\b(\d{3,6})\b/g, function(match, code) {
                return `<button type="button" onclick="event.stopPropagation(); handleManualOrUsbScan('${code}')" class="inline-flex items-center gap-1 bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold px-1.5 py-0.5 rounded text-[10.5px] transition shadow cursor-pointer mx-0.5 active:scale-95"><i class="fas fa-barcode text-[9px]"></i> ${code}</button>`;
            });

            html += `
                <div class="bg-gray-900/80 p-1.5 px-2 rounded-lg border border-gray-800 text-[11.5px] flex items-center justify-between gap-2">
                    <span class="text-gray-200 leading-snug truncate">${textWithCodes}</span>
                    <span class="text-[9.5px] font-mono text-gray-500 shrink-0">${time}</span>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // =========================================================================
    // INICIALIZAÇÃO DA CÂMERA DE BIPAGEM CONTÍNUA
    // =========================================================================
    async function initPermanentCameraScanner() {
        try {
            let formats = [0, 9, 5]; // QR_CODE, EAN_13, CODE_128
            if (typeof Html5QrcodeSupportedFormats !== 'undefined') {
                formats = [
                    Html5QrcodeSupportedFormats.QR_CODE,
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.CODE_128
                ];
            }

            html5QrScanner = new Html5Qrcode("continuous-qr-reader", { formatsToSupport: formats });

            // Obter lista de câmeras conectadas
            const devices = await Html5Qrcode.getCameras();
            const camSelect = document.getElementById("camera-select");
            camSelect.innerHTML = '';

            if (devices && devices.length > 0) {
                availableCameras = devices;
                devices.forEach((dev, idx) => {
                    const opt = document.createElement("option");
                    opt.value = dev.id;
                    opt.textContent = dev.label || `Câmera ${idx + 1}`;
                    camSelect.appendChild(opt);
                });

                // Tentar selecionar preferencialmente a câmera traseira (environment) ou a primeira disponível
                let defaultCam = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('traseira')) || devices[0];
                selectedCameraId = defaultCam.id;
                camSelect.value = selectedCameraId;

                await startContinuousCamera(selectedCameraId);
            } else {
                camSelect.innerHTML = '<option value="">Nenhuma câmera detectada</option>';
                // Tenta modo genérico de ambiente
                await startContinuousCamera({ facingMode: "environment" });
            }
        } catch (err) {
            console.warn("Falha ao inicializar câmeras:", err);
            const banner = document.getElementById("scan-feedback-banner");
            if (banner) {
                banner.className = "p-3.5 rounded-2xl text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300 block shadow-sm";
                banner.innerHTML = `<i class="fas fa-exclamation-triangle text-amber-600 mr-1.5"></i> Câmera indisponível (${err.message || 'Sem permissão'}). Utilize o leitor USB ou digite os códigos no campo manual abaixo.`;
            }
        }
    }

    async function startContinuousCamera(cameraSource) {
        if (!html5QrScanner) return;

        try {
            const config = {
                fps: 15,
                qrbox: function(width, height) {
                    const minEdge = Math.min(width, height);
                    const size = Math.min(Math.floor(minEdge * 0.8), 600);
                    return { width: size, height: size };
                },
                disableFlip: true
            };

            await html5QrScanner.start(
                cameraSource,
                config,
                async (decodedText) => {
                    console.log("[Leitor Contínuo] Código lido:", decodedText);
                    await processScannedItemCode(decodedText);
                }
            );
        } catch (e) {
            console.warn("Erro ao iniciar captura contínua da câmera:", e);
        }
    }

    async function changeCamera(cameraId) {
        if (!cameraId || cameraId === selectedCameraId) return;
        selectedCameraId = cameraId;
        if (html5QrScanner) {
            try {
                await html5QrScanner.stop();
            } catch(e) {}
            await startContinuousCamera(cameraId);
        }
    }

    async function restartScannerCamera() {
        if (html5QrScanner) {
            try {
                await html5QrScanner.stop();
            } catch(e) {}
            const camSelect = document.getElementById("camera-select");
            const camId = camSelect ? camSelect.value : selectedCameraId;
            await startContinuousCamera(camId ? camId : { facingMode: "environment" });
            showToast("Câmera reiniciada!");
        }
    }

    // =========================================================================
    // PROCESSAMENTO DO BIPE (CAMERA + USB + MANUAL)
    // =========================================================================
    function handleManualOrUsbScan(rawValue) {
        if (!rawValue || !rawValue.trim()) return;
        const input = document.getElementById("manual-code-input");
        if (input) input.value = "";
        processScannedItemCode(rawValue.trim());
    }

    async function processScannedItemCode(decodedText) {
        if (!decodedText || !decodedText.trim()) return;

        let code = decodedText.trim();

        // Se for URL completa (ex: https://minhamania.net/p/1234), extrair o código limpo
        if (code.startsWith('http://') || code.startsWith('https://')) {
            try {
                const url = new URL(code);
                const p = url.searchParams.get('codigo') || url.searchParams.get('c') || url.searchParams.get('code') || url.searchParams.get('item');
                if (p) {
                    code = p.trim();
                } else {
                    const segs = url.pathname.split('/').filter(Boolean);
                    if (segs.length > 0) {
                        code = segs[segs.length - 1].trim();
                    }
                }
            } catch(e) {}
        }

        const banner = document.getElementById("scan-feedback-banner");

        // 1. Validar se há cliente selecionada
        if (!activeClient || !activeClient.userId) {
            playErrorBeep();
            if (banner) {
                banner.className = "p-4 rounded-2xl text-xs font-bold bg-amber-500/15 text-amber-900 border-2 border-amber-500 block shadow-lg animate-bounce";
                banner.innerHTML = `
                    <div class="flex items-center gap-3">
                        <i class="fas fa-user-times text-amber-600 text-2xl shrink-0"></i>
                        <div>
                            <div class="text-sm font-black text-amber-900">Nenhuma Cliente Selecionada!</div>
                            <div class="text-xs text-amber-800 font-medium mt-0.5">Clique em uma cliente na lista <strong>"Pessoas Online"</strong> ao lado antes de bipar o produto #${escapeHtml(code)}.</div>
                        </div>
                    </div>
                `;
            }
            return;
        }

        // 2. Prevenção de duplicidade por debounce e lock de concorrência
        const now = Date.now();
        if (isScanProcessing) {
            console.warn("Scan ignorado: outro item em processamento.");
            return;
        }
        if (lastScannedCode === code && (now - lastScannedTime) < 2000) {
            console.warn("Scan ignorado: mesmo código bipado em menos de 2s.");
            return;
        }

        isScanProcessing = true;
        lastScannedCode = code;
        lastScannedTime = now;

        if (banner) {
            banner.className = "p-4 rounded-2xl text-xs font-bold bg-indigo-50 text-indigo-900 border border-indigo-300 block animate-pulse shadow-sm";
            banner.innerHTML = `
                <div class="flex items-center gap-3">
                    <i class="fas fa-spinner fa-spin text-indigo-600 text-xl shrink-0"></i>
                    <div>
                        <div class="text-xs font-black uppercase text-indigo-700">Processando Bipe...</div>
                        <div class="text-xs text-indigo-900 font-medium">Buscando produto #${escapeHtml(code)} para @${escapeHtml(activeClient.username)}</div>
                    </div>
                </div>
            `;
        }

        try {
            // 3. Buscar produto pela API
            const response = await fetch(`/api/items/search?q=${encodeURIComponent(code)}${liveId ? '&live_id=' + encodeURIComponent(liveId) : ''}`);
            const data = await response.json();

            if (!data.success || !data.data || data.data.length === 0) {
                playErrorBeep();
                if (banner) {
                    banner.className = "p-4 rounded-2xl text-xs font-bold bg-red-50 text-red-900 border-2 border-red-500 block shadow-lg";
                    banner.innerHTML = `
                        <div class="flex items-center gap-3">
                            <i class="fas fa-times-circle text-red-600 text-2xl shrink-0"></i>
                            <div>
                                <div class="text-sm font-black text-red-900">Produto Não Encontrado!</div>
                                <div class="text-xs text-red-800 font-medium mt-0.5">Nenhum produto cadastrado com a etiqueta/código <strong>"${escapeHtml(code)}"</strong>.</div>
                            </div>
                        </div>
                    `;
                }
                return;
            }

            const cleanCode = code.replace(/^[#\s]+/, '').trim();
            let matchedItem = data.data.find(item => 
                (item.sku && item.sku.toLowerCase() === cleanCode.toLowerCase()) || 
                (item.codigo && item.codigo.toLowerCase() === cleanCode.toLowerCase()) || 
                String(item.id) === cleanCode ||
                (item.sku && item.sku.toLowerCase() === code.toLowerCase()) || 
                (item.codigo && item.codigo.toLowerCase() === code.toLowerCase()) || 
                String(item.id) === code
            ) || data.data[0];

            // 4. Inserir na sacola da cliente ativa
            const addResponse = await fetch('/admin/live-chat/add-to-bag', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    code_request_id: null,
                    user_id: activeClient.userId,
                    item_id: matchedItem.id,
                    live_id: liveId
                })
            });

            const addData = await addResponse.json();

            if (addData.success) {
                playSuccessBeep();
                showToast(`🎉 ${matchedItem.name} adicionado para @${activeClient.username}!`);

                const formattedPrice = matchedItem.formatted_price || `R$ ${parseFloat(matchedItem.price || 0).toFixed(2).replace('.', ',')}`;

                if (banner) {
                    banner.className = "p-4 rounded-2xl text-xs font-bold bg-emerald-50 text-emerald-950 border-2 border-emerald-500 block shadow-xl";
                    banner.innerHTML = `
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-xl shrink-0 shadow-md">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-black uppercase text-emerald-700 tracking-wider">Item Bipado com Sucesso!</span>
                                    <span class="text-sm font-black text-emerald-800">${formattedPrice}</span>
                                </div>
                                <div class="text-sm font-black text-gray-900 mt-0.5">${escapeHtml(matchedItem.name)}</div>
                                <div class="text-xs text-gray-600 font-medium mt-0.5">Adicionado à sacola de <strong>@${escapeHtml(activeClient.username)}</strong> (${escapeHtml(activeClient.clientName)})</div>
                            </div>
                        </div>
                    `;
                }

                // Registrar no Histórico da Sessão
                sessionScannedItems.unshift({
                    id: matchedItem.id,
                    name: matchedItem.name,
                    price: formattedPrice,
                    sku: matchedItem.sku || matchedItem.codigo || code,
                    clientUsername: activeClient.username,
                    time: new Date().toLocaleTimeString()
                });
                renderSessionScannedList();
            } else {
                playErrorBeep();
                if (banner) {
                    banner.className = "p-4 rounded-2xl text-xs font-bold bg-amber-50 text-amber-950 border-2 border-amber-500 block shadow-lg";
                    banner.innerHTML = `
                        <div class="flex items-center gap-3">
                            <i class="fas fa-exclamation-triangle text-amber-600 text-2xl shrink-0"></i>
                            <div>
                                <div class="text-sm font-black text-amber-900">Atenção ao Bipar:</div>
                                <div class="text-xs text-amber-800 font-medium mt-0.5">${escapeHtml(addData.message || 'Não foi possível adicionar o produto.')}</div>
                            </div>
                        </div>
                    `;
                }
            }
        } catch (err) {
            playErrorBeep();
            console.error("Erro no processamento do bipe:", err);
            if (banner) {
                banner.className = "p-4 rounded-2xl text-xs font-bold bg-red-50 text-red-900 border-2 border-red-500 block shadow-lg";
                banner.innerHTML = `
                    <div class="flex items-center gap-3">
                        <i class="fas fa-wifi text-red-600 text-2xl shrink-0"></i>
                        <div>
                            <div class="text-sm font-black text-red-900">Erro de Comunicação</div>
                            <div class="text-xs text-red-800 font-medium mt-0.5">Falha ao se comunicar com o servidor ao processar "${escapeHtml(code)}".</div>
                        </div>
                    </div>
                `;
            }
        } finally {
            setTimeout(() => {
                isScanProcessing = false;
            }, 600);
        }
    }

    function renderSessionScannedList() {
        const container = document.getElementById("session-scanned-list");
        const countBadge = document.getElementById("session-scanned-count");
        if (!container) return;

        countBadge.textContent = `${sessionScannedItems.length} peça${sessionScannedItems.length !== 1 ? 's' : ''}`;

        if (sessionScannedItems.length === 0) {
            container.innerHTML = `<p class="text-xs text-gray-400 text-center py-4">Nenhum produto bipado nesta sessão ainda.</p>`;
            return;
        }

        let html = '';
        sessionScannedItems.forEach(item => {
            html += `
                <div class="flex items-center justify-between p-2 rounded-xl bg-gray-50 border border-gray-200">
                    <div class="min-w-0 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                        <div class="truncate">
                            <span class="font-extrabold text-gray-900 text-xs">${escapeHtml(item.name)}</span>
                            <span class="text-[10px] text-gray-500 block">@${escapeHtml(item.clientUsername)} &bull; ${item.sku} &bull; ${item.time}</span>
                        </div>
                    </div>
                    <span class="font-black text-xs text-indigo-700 shrink-0 ml-2">${item.price}</span>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // =========================================================================
    // BUSCA RÁPIDA DE CLIENTES CADASTRADOS (AVULSO)
    // =========================================================================
    let lastAvulsoQuery = "";
    document.getElementById("avulso-search-input").addEventListener("keyup", function(e) {
        if (this.value === lastAvulsoQuery) return;
        lastAvulsoQuery = this.value;
        searchAvulsoClients(this.value);
    });

    function searchAvulsoClients(query) {
        const resultsContainer = document.getElementById("avulso-search-results");
        if (query.trim().length < 2) {
            resultsContainer.classList.add("hidden");
            return;
        }

        resultsContainer.classList.remove("hidden");
        resultsContainer.innerHTML = `<p class="text-xs text-gray-400 text-center py-3"><i class="fas fa-spinner fa-spin mr-1"></i> Buscando clientes...</p>`;

        fetch(`/api/users/search?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    let html = '';
                    data.data.forEach(user => {
                        const displayName = user.name + (user.apelido ? ` (${user.apelido})` : '');
                        const usernameFallback = user.instagram || user.tiktok || user.name;
                        const userJson = JSON.stringify({
                            userId: user.id,
                            username: usernameFallback,
                            clientName: displayName,
                            platform: user.tiktok ? 'tiktok' : 'instagram',
                            whatsapp: user.whatsapp || user.phone || '',
                            avatarUrl: ''
                        }).replace(/"/g, '&quot;');

                        html += `
                            <div onclick="selectAvulsoClientDirectly(${userJson})" class="p-2.5 border-b border-gray-100 bg-white hover:bg-indigo-50 cursor-pointer transition flex justify-between items-center group">
                                <div>
                                    <h4 class="font-extrabold text-xs text-gray-800 group-hover:text-indigo-700">${escapeHtml(user.name)}</h4>
                                    <div class="text-[10.5px] text-gray-500 mt-0.5 flex flex-wrap gap-2">
                                        ${user.instagram ? `<span class="text-purple-600 font-bold"><i class="fab fa-instagram"></i> @${escapeHtml(user.instagram)}</span>` : ''}
                                        ${user.tiktok ? `<span class="text-black font-bold"><i class="fab fa-tiktok"></i> @${escapeHtml(user.tiktok)}</span>` : ''}
                                        ${user.whatsapp ? `<span class="text-emerald-600 font-bold"><i class="fab fa-whatsapp"></i> ${escapeHtml(user.whatsapp)}</span>` : ''}
                                        ${user.apelido ? `<span class="text-indigo-600 font-bold"><i class="fas fa-tag"></i> ${escapeHtml(user.apelido)}</span>` : ''}
                                    </div>
                                </div>
                                <button type="button" class="bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded-lg text-xs font-bold transition shadow-xs flex items-center gap-1 shrink-0">
                                    <i class="fas fa-check"></i> Selecionar
                                </button>
                            </div>
                        `;
                    });
                    resultsContainer.innerHTML = html;
                } else {
                    resultsContainer.innerHTML = `<p class="text-xs text-gray-400 text-center py-3">Nenhum cliente encontrado.</p>`;
                }
            })
            .catch(err => {
                resultsContainer.innerHTML = `<p class="text-xs text-red-400 text-center py-3">Erro na busca de clientes.</p>`;
            });
    }

    function selectAvulsoClientDirectly(userObj) {
        const resultsContainer = document.getElementById("avulso-search-results");
        const input = document.getElementById("avulso-search-input");
        if (resultsContainer) resultsContainer.classList.add("hidden");
        if (input) input.value = "";
        
        selectActiveClient(userObj);
    }

    document.addEventListener('click', function(e) {
        const results = document.getElementById("avulso-search-results");
        const input = document.getElementById("avulso-search-input");
        if (results && input && !results.contains(e.target) && e.target !== input) {
            results.classList.add("hidden");
        }
    });

    // =========================================================================
    // MODAL DE VINCULAR CLIENTE
    // =========================================================================
    function openLinkModal(username, platform) {
        document.getElementById("modal-display-username").textContent = `@${username}`;
        document.getElementById("modal-display-platform").textContent = (platform || 'INSTAGRAM').toUpperCase();
        document.getElementById("modal-input-username").value = username;
        document.getElementById("modal-input-platform").value = platform || 'instagram';
        document.getElementById("modal-search-input").value = "";
        document.getElementById("modal-search-results").innerHTML = `<p class="text-xs text-gray-400 text-center py-4">Comece a digitar para pesquisar clientes.</p>`;
        
        document.getElementById("link-user-modal").classList.remove("hidden");
        document.getElementById("modal-search-input").focus();
    }

    function closeLinkModal() {
        document.getElementById("link-user-modal").classList.add("hidden");
    }

    function searchClientsModal(query) {
        const resultsContainer = document.getElementById("modal-search-results");
        if (query.trim().length < 2) {
            resultsContainer.innerHTML = `<p class="text-xs text-gray-400 text-center py-4">Digite pelo menos 2 caracteres.</p>`;
            return;
        }

        fetch(`/api/users/search?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    let html = '';
                    data.data.forEach(user => {
                        html += `
                            <div onclick="linkUserToProfile('${user.id}', '${escapeHtml(user.name)}', '${escapeHtml(user.whatsapp || user.phone || '')}')" class="p-2.5 rounded-xl border border-gray-200 bg-white hover:bg-indigo-50 hover:border-indigo-300 transition duration-150 cursor-pointer flex justify-between items-center">
                                <div>
                                    <h4 class="font-bold text-xs text-gray-800">${escapeHtml(user.name)}</h4>
                                    <span class="text-[10px] text-gray-500">${user.whatsapp ? 'WhatsApp: ' + escapeHtml(user.whatsapp) : 'Sem número'}</span>
                                    ${user.apelido ? `<span class="text-[10px] text-indigo-600 block">Apelido: ${escapeHtml(user.apelido)}</span>` : ''}
                                </div>
                                <i class="fas fa-plus text-indigo-500 text-xs"></i>
                            </div>
                        `;
                    });
                    resultsContainer.innerHTML = html;
                } else {
                    resultsContainer.innerHTML = `<p class="text-xs text-gray-400 text-center py-4">Nenhum cliente cadastrado encontrado.</p>`;
                }
            })
            .catch(err => console.error("Erro na busca de usuários:", err));
    }

    function linkUserToProfile(userId, clientName, whatsapp) {
        const username = document.getElementById("modal-input-username").value;
        const platform = document.getElementById("modal-input-platform").value;

        fetch('/admin/live-chat/link-user', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                username: username,
                platform: platform,
                user_id: userId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast("Vinculação realizada com sucesso!");
                closeLinkModal();
                fetchChatAndOnlineData();
                // Seleciona a cliente imediatamente para bipar
                selectActiveClient({
                    userId: userId,
                    username: username,
                    clientName: clientName,
                    platform: platform,
                    whatsapp: whatsapp,
                    avatarUrl: ''
                });
            } else {
                alert("Erro ao vincular: " + data.message);
            }
        })
        .catch(err => console.error("Erro ao vincular usuário:", err));
    }

    // =========================================================================
    // ENCERRAMENTO DA LIVE
    // =========================================================================
    async function confirmEndLive(id) {
        if (!confirm("⚠️ ATENÇÃO: Deseja realmente ENCERRAR esta Live agora?\n\n1. O status da live será alterado para ENCERRADA.\n2. Todas as sacolinhas de participantes serão processadas.\n3. Mensagens com a sacola serão disparadas via WhatsApp para as clientes cadastradas.")) {
            return;
        }

        const csrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
        showToast("Encerrando live e preparando envios de WhatsApp...");

        try {
            const res = await fetch(`/lives/${id}?enviar_whatsapp=1`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();

            if (data.success) {
                let alertMsg = `✅ LIVE ENCERRADA COM SUCESSO!\n\n` +
                    `• PDFs Gerados: ${data.pdfs_ok ?? 0}\n` +
                    `• WhatsApps Enfileirados: ${data.jobs_enfileirados ?? 0}\n`;

                if (data.clientes_sem_telefone && data.clientes_sem_telefone.length > 0) {
                    const semTel = data.clientes_sem_telefone.map(c => `• ${c.name} (@${c.username || 'sem_user'})`).join('\n');
                    alertMsg += `\n⚠️ ATENÇÃO - Clientes sem WhatsApp cadastrado:\n${semTel}\n\n(Dica: Acesse o cadastro para preencher o telefone e reenviar o link!)`;
                }

                alert(alertMsg);
                window.location.reload();
            } else {
                alert("Erro ao encerrar live: " + (data.error || data.message || 'Erro inesperado'));
            }
        } catch (e) {
            console.error("Erro ao encerrar live:", e);
            alert("Erro de comunicação ao tentar encerrar a live.");
        }
    }

    // =========================================================================
    // UTILITÁRIOS
    // =========================================================================
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function showToast(message) {
        const toast = document.createElement("div");
        toast.className = "fixed bottom-5 right-5 bg-indigo-600 text-white px-5 py-3 rounded-2xl shadow-2xl z-50 transition-all duration-300 translate-y-5 opacity-0 text-xs sm:text-sm font-bold flex items-center gap-2 border border-indigo-400";
        toast.innerHTML = `<i class="fas fa-check-circle text-emerald-300"></i> <span>${message}</span>`;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.transform = "translateY(0)";
            toast.style.opacity = "1";
        }, 100);

        setTimeout(() => {
            toast.style.transform = "translateY(5px)";
            toast.style.opacity = "0";
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
</script>
@endpush

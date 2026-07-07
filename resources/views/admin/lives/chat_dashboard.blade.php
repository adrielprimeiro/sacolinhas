@extends('layouts.app')

@section('title', 'Painel de Captura de Live')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-comments text-indigo-600"></i>
                <span>Painel de Captura de Live</span>
            </h1>
            <p class="text-gray-500 mt-1">Gerencie a fila de pedidos, identifique códigos de produtos e integre o chat do Instagram/TikTok.</p>
        </div>
        
        <!-- Seleção de Live -->
        <div class="flex items-center gap-3 bg-white p-3 rounded-xl shadow-sm border border-gray-200">
            <label for="live-select" class="text-sm font-semibold text-gray-600">Live Ativa:</label>
            <form action="{{ route('admin.live-chat.dashboard') }}" method="GET" class="flex gap-2">
                <select name="live_id" id="live-select" onchange="this.form.submit()" class="text-sm rounded-lg border border-gray-300 bg-gray-50 p-2 text-gray-900 focus:border-indigo-500 focus:ring-indigo-500">
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

    @if(!$activeLive)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-xl shadow-sm mb-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 text-yellow-500 mr-3">
                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-yellow-800">Nenhuma Live Selecionada ou Ativa</h3>
                    <p class="text-sm text-yellow-700 mt-1">Selecione uma live existente no canto superior direito para começar a capturar e visualizar os dados do chat.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Dashboard Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- COLUNA 1: FILA DE CÓDIGOS SOLICITADOS (LARGURA 5) - OCULTO POR ENQUANTO -->
        <div class="hidden lg:col-span-5 flex flex-col bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden" style="height: 75vh;">
            <div class="bg-indigo-600 px-5 py-4 flex items-center justify-between text-white">
                <div class="flex items-center gap-2">
                    <i class="fas fa-list-ol"></i>
                    <h2 class="font-bold text-lg">Fila de Códigos (Ordem de Chegada)</h2>
                </div>
                <span id="code-queue-count" class="bg-indigo-800 text-xs px-2.5 py-1 rounded-full font-bold">0 itens</span>
            </div>
            
            <div id="code-requests-container" class="flex-1 p-4 overflow-y-auto space-y-4 bg-gray-50">
                @if(!$activeLive)
                    <div class="flex flex-col items-center justify-center h-full text-gray-400">
                        <i class="fas fa-exclamation-circle text-4xl mb-2"></i>
                        <p class="text-sm text-center">Selecione uma live no topo da página para ver a fila de códigos.</p>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-full text-gray-400">
                        <i class="fas fa-box-open text-4xl mb-2"></i>
                        <p class="text-sm">Aguardando códigos detectados no chat...</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- COLUNA 2: CHAT EM TEMPO REAL (LARGURA 9) -->
        <div class="lg:col-span-9 flex flex-col bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden" style="height: 75vh;">
            <div class="bg-gray-800 px-5 py-4 flex items-center justify-between text-white">
                <div class="flex items-center gap-2">
                    <i class="fas fa-comment-alt"></i>
                    <h2 class="font-bold text-lg">Chat da Transmissão</h2>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1.5">
                        <span class="inline-block w-2.5 h-2.5 bg-green-500 rounded-full animate-ping" id="chat-ping-dot"></span>
                        <span class="text-xs text-gray-300" id="chat-status-text">Capturando</span>
                    </div>
                </div>
            </div>
            
            <div id="chat-messages-container" class="flex-1 p-4 overflow-y-auto space-y-3 bg-gray-900 text-gray-100 font-sans">
                @if(!$activeLive)
                    <div class="flex flex-col items-center justify-center h-full text-gray-500">
                        <i class="fas fa-video-slash text-3xl mb-2"></i>
                        <p class="text-xs text-center">Selecione uma live para ativar o chat.</p>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-full text-gray-500">
                        <i class="fas fa-plug text-3xl mb-2"></i>
                        <p class="text-xs text-center">Execute o Bookmarklet na aba da live para enviar mensagens para cá.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- COLUNA 3: INTEGRAÇÃO / PARTICIPANTES (LARGURA 3) -->
        <div class="lg:col-span-3 flex flex-col bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden" style="height: 75vh;">
            <!-- Tabs -->
            <div class="flex border-b border-gray-200 bg-gray-50">
                <button id="tab-btn-bookmarklet" onclick="switchTab('bookmarklet')" class="flex-1 py-3 px-4 text-center font-semibold text-sm border-b-2 border-indigo-600 text-indigo-600">
                    Conectar Lives
                </button>
                <button id="tab-btn-online" onclick="switchTab('online')" class="flex-1 py-3 px-4 text-center font-semibold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Pessoas Online
                </button>
            </div>

            <!-- Tab content container -->
            <div class="flex-1 p-4 overflow-y-auto">
                <!-- Tab: Conectar Lives -->
                <div id="tab-content-bookmarklet" class="space-y-4">
                    <!-- 1. Captura Direta TikTok Backend -->
                    <div class="bg-pink-50 rounded-xl p-4 border border-pink-200 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-pink-900 text-sm flex items-center gap-1.5">
                                <i class="fab fa-tiktok text-pink-600 text-base"></i>
                                TikTok Backend (Sem Aba)
                            </h3>
                            <span id="tiktok-backend-badge" class="bg-gray-200 text-gray-700 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
                                <span id="tiktok-status-dot" class="w-1.5 h-1.5 rounded-full bg-gray-500"></span>
                                <span id="tiktok-status-text">Desconectado</span>
                            </span>
                        </div>
                        <p class="text-xs text-pink-800 mt-1.5 leading-relaxed">
                            Conecta direto aos servidores públicos do TikTok <strong>sem precisar de login ou aba aberta</strong> no Chrome!
                        </p>
                        
                        <div class="mt-3 flex gap-2">
                            <input type="text" id="tiktok-username-input" value="_minhamania" placeholder="@usuario_tiktok" class="flex-1 text-xs px-2.5 py-1.5 rounded-lg border border-pink-300 bg-white focus:outline-none focus:ring-1 focus:ring-pink-500 font-semibold text-gray-800">
                            <button type="button" onclick="toggleTikTokBackend()" id="tiktok-toggle-btn" class="bg-pink-600 hover:bg-pink-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition duration-150 shadow-sm flex items-center gap-1.5">
                                <i class="fas fa-play text-[10px]"></i> Conectar
                            </button>
                        </div>
                    </div>

                    <!-- 2. Captura Instagram via Extensão -->
                    <div class="bg-purple-50 rounded-xl p-4 border border-purple-200 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-purple-900 text-sm flex items-center gap-1.5">
                                <i class="fab fa-instagram text-purple-600 text-base"></i>
                                Instagram Live (Extensão Chrome)
                            </h3>
                            <span id="insta-badge" class="bg-purple-200 text-purple-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
                                <span id="insta-status-dot" class="hidden w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                <span id="insta-status-text">60 FPS Turbo</span>
                            </span>
                        </div>
                        <p class="text-xs text-purple-800 mt-1.5 leading-relaxed">
                            Seguro contra bloqueios da Meta. Com a extensão ativa no Chrome, clique abaixo para abrir a Live que a captura inicia automaticamente!
                        </p>
                        
                        <div class="mt-3 flex gap-2">
                            <input type="text" id="insta-username-input" value="de_minha_mania" placeholder="@usuario_insta" class="flex-1 text-xs px-2.5 py-1.5 rounded-lg border border-purple-300 bg-white focus:outline-none focus:ring-1 focus:ring-purple-500 font-semibold text-gray-800">
                            <button type="button" onclick="toggleInstagramCapture()" id="insta-toggle-btn" class="bg-purple-600 hover:bg-purple-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition duration-150 shadow-sm flex items-center gap-1.5">
                                <i class="fas fa-play text-[10px]"></i> Conectar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tab: Pessoas Online -->
                <div id="tab-content-online" class="hidden space-y-3">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-bold text-gray-700 text-sm">Participantes na Transmissão</h3>
                        <span id="online-users-count" class="bg-gray-200 text-gray-700 text-xs px-2 py-0.5 rounded-full font-bold">0</span>
                    </div>
                    
                    <div id="online-users-list" class="space-y-2.5">
                        <!-- Gerado dinamicamente -->
                        <p class="text-xs text-gray-400 text-center py-6">Nenhum participante detectado ainda.</p>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- MODAL VINCULAR CLIENTE -->
<div id="link-user-modal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 max-w-md w-full p-6 mx-4 transform transition-all duration-300">
        <div class="flex justify-between items-start border-b border-gray-100 pb-3 mb-4">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-link text-indigo-600"></i>
                <span>Vincular Usuário da Live</span>
            </h3>
            <button onclick="closeLinkModal()" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        </div>
        
        <p class="text-xs text-gray-500 mb-4 leading-relaxed">
            Associe o username <strong id="modal-display-username" class="text-indigo-600">@usuario</strong> da plataforma <strong id="modal-display-platform" class="text-gray-800">plataforma</strong> a um cliente cadastrado no sistema para salvar os pedidos dele na sacolinha dele.
        </p>
        
        <input type="hidden" id="modal-input-username">
        <input type="hidden" id="modal-input-platform">

        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-700 mb-1">Buscar Cliente por Nome, Apelido ou Celular:</label>
            <input type="text" id="modal-search-input" onkeyup="searchClients(this.value)" placeholder="Digite para buscar..." class="w-full p-2.5 rounded-lg border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
        </div>

        <div id="modal-search-results" class="max-h-48 overflow-y-auto space-y-2 border border-gray-100 rounded-lg p-2 bg-gray-50">
            <!-- Resultados Ajax -->
            <p class="text-xs text-gray-400 text-center py-4">Comece a digitar para pesquisar clientes.</p>
        </div>
        
        <div class="mt-5 flex justify-end gap-3 border-t border-gray-100 pt-4">
            <button onclick="closeLinkModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-50">Cancelar</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Parâmetros Globais
    const liveId = "{{ $activeLive ? $activeLive->id : '' }}";
    const serverOrigin = window.location.origin;
    let currentTab = 'bookmarklet';
    let lastMessageId = 0;
    let pollingInterval = null;

    // JavaScript do Bookmarklet compilado
    const bookmarkletJsCode = `javascript:(function(){var%20js=document.createElement('script');js.src='${serverOrigin}/js/live-chat-bookmarklet.js?v='+Math.random();document.body.appendChild(js);})();`;

    document.addEventListener("DOMContentLoaded", function() {
        // Inicializar código do bookmarklet na tela
        const linkEl = document.getElementById("bookmarklet-link");
        const codeEl = document.getElementById("bookmarklet-code");
        if (linkEl) linkEl.setAttribute("href", bookmarkletJsCode);
        if (codeEl) codeEl.value = bookmarkletJsCode;

        if (liveId) {
            // Iniciar Polling de dados (a cada 3 segundos)
            fetchChatData();
            pollingInterval = setInterval(fetchChatData, 3000);
        }

        checkTikTokBackendStatus();
        setInterval(checkTikTokBackendStatus, 2500);
    });

    let tiktokConnected = false;
    async function checkTikTokBackendStatus() {
        try {
            const host = window.location.hostname || "localhost";
            const res = await fetch(`http://${host}:3001/status`);
            const data = await res.json();
            const badge = document.getElementById("tiktok-backend-badge");
            const dot = document.getElementById("tiktok-status-dot");
            const text = document.getElementById("tiktok-status-text");
            const btn = document.getElementById("tiktok-toggle-btn");
            if (!badge || !btn) return;

            tiktokConnected = data.connected;
            if (data.connecting) {
                badge.className = "bg-yellow-100 text-yellow-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 border border-yellow-300";
                dot.className = "w-1.5 h-1.5 rounded-full bg-yellow-500 animate-ping";
                text.textContent = "Conectando...";
                btn.className = "bg-yellow-500 hover:bg-yellow-600 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition duration-150 shadow-sm flex items-center gap-1.5 opacity-75 cursor-not-allowed";
                btn.innerHTML = `<i class="fas fa-spinner fa-spin text-[10px]"></i> Conectando`;
            } else if (data.connected) {
                badge.className = "bg-green-100 text-green-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 border border-green-300";
                dot.className = "w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse";
                text.textContent = "Ao Vivo";
                btn.className = "bg-red-600 hover:bg-red-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition duration-150 shadow-sm flex items-center gap-1.5";
                btn.innerHTML = `<i class="fas fa-stop text-[10px]"></i> Parar`;
            } else {
                if (data.error) {
                    badge.className = "bg-red-100 text-red-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 border border-red-300";
                    dot.className = "w-1.5 h-1.5 rounded-full bg-red-500";
                    let errText = data.error;
                    if (errText.includes('room_id') || errText.includes('user_not_found') || errText.toLowerCase().includes('offline')) {
                        errText = 'Sem Live Ativa no momento';
                    } else {
                        errText = errText.slice(0, 25);
                    }
                    text.textContent = errText;
                } else {
                    badge.className = "bg-gray-200 text-gray-700 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1";
                    dot.className = "w-1.5 h-1.5 rounded-full bg-gray-500";
                    text.textContent = "Desconectado";
                }
                btn.className = "bg-pink-600 hover:bg-pink-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition duration-150 shadow-sm flex items-center gap-1.5";
                btn.innerHTML = `<i class="fas fa-play text-[10px]"></i> Conectar`;
            }
        } catch (e) {
            const badge = document.getElementById("tiktok-backend-badge");
            const text = document.getElementById("tiktok-status-text");
            if (badge && text) {
                badge.className = "bg-red-100 text-red-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1";
                text.textContent = "Offline (3001)";
            }
        }
    }

    async function toggleTikTokBackend() {
        const input = document.getElementById("tiktok-username-input");
        if (!input) return;
        const username = input.value.trim();

        const host = window.location.hostname || "localhost";
        if (tiktokConnected) {
            await fetch(`http://${host}:3001/disconnect`, { method: "POST" }).catch(() => {});
        } else {
            if (!username) {
                alert("Digite o usuário do TikTok!");
                return;
            }
            try {
                await fetch(`http://${host}:3001/connect`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ username: username })
                });
            } catch (err) {
                alert("O serviço de captura do TikTok (Porta 3001) está offline ou não foi iniciado.");
            }
        }
        checkTikTokBackendStatus();
    }

    function openTikTokLive() {
        const input = document.getElementById("tiktok-username-input");
        let username = input ? input.value.trim() : "_minhamania";
        username = username.replace(/^@/, '');
        if (!username) username = "_minhamania";
        window.open(`https://www.tiktok.com/@${username}/live`, "_blank");
    }

    function openInstagramLive() {
        const input = document.getElementById("insta-username-input");
        let username = input ? input.value.trim() : "de_minha_mania";
        username = username.replace(/^@/, '');
        if (!username) username = "de_minha_mania";
        window.open(`https://www.instagram.com/${username}/live/`, "_blank");
    }

    function updateInstagramState(instaActive) {
        const badge = document.getElementById("insta-badge");
        const dot = document.getElementById("insta-status-dot");
        const text = document.getElementById("insta-status-text");
        const btn = document.getElementById("insta-toggle-btn");
        if (!badge || !btn) return;

        if (instaActive) {
            badge.className = "bg-green-100 text-green-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 border border-green-300";
            if (dot) dot.className = "w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse";
            if (text) text.textContent = "Ao Vivo";
            btn.className = "bg-red-600 hover:bg-red-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition duration-150 shadow-sm flex items-center gap-1.5";
            btn.innerHTML = `<i class="fas fa-stop text-[10px]"></i> Parar`;
        } else {
            badge.className = "bg-purple-200 text-purple-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1";
            if (dot) dot.className = "hidden";
            if (text) text.textContent = "60 FPS Turbo";
            btn.className = "bg-purple-600 hover:bg-purple-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition duration-150 shadow-sm flex items-center gap-1.5";
            btn.innerHTML = `<i class="fas fa-play text-[10px]"></i> Conectar`;
        }
    }

    async function toggleInstagramCapture() {
        const btn = document.getElementById("insta-toggle-btn");
        const isCurrentlyActive = btn && btn.textContent.includes("Parar");
        const action = isCurrentlyActive ? "stop" : "start";

        try {
            const res = await fetch("/admin/live-chat/toggle-instagram", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
                },
                body: JSON.stringify({ action: action })
            });
            const data = await res.json();
            if (data.success) updateInstagramState(data.insta_active);
        } catch (e) {
            console.error("Erro ao alternar captura do Instagram:", e);
        }

        if (action === "start") {
            const input = document.getElementById("insta-username-input");
            let username = input ? input.value.trim() : "de_minha_mania";
            username = username.replace(/^@/, '');
            if (!username) username = "de_minha_mania";

            const host = window.location.hostname || "localhost";
            fetch(`http://${host}:3002/connect`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ username: username })
            }).then(res => {
                if (!res.ok) throw new Error("Offline");
                return res.json();
            }).then(data => {
                console.log("[Insta Headless] Conectado no servidor:", data.message);
            }).catch(err => {
                // Se a porta 3002 não estiver rodando, abre a aba para a extensão do Chrome
                openInstagramLive();
            });
        } else {
            const host = window.location.hostname || "localhost";
            fetch(`http://${host}:3002/disconnect`, { method: "POST" }).catch(() => {});
        }
    }

    // Alternar abas da barra lateral
    function switchTab(tab) {
        currentTab = tab;
        const btnBookmarklet = document.getElementById("tab-btn-bookmarklet");
        const btnOnline = document.getElementById("tab-btn-online");
        const divBookmarklet = document.getElementById("tab-content-bookmarklet");
        const divOnline = document.getElementById("tab-content-online");

        if (tab === 'bookmarklet') {
            btnBookmarklet.className = "flex-1 py-3 px-4 text-center font-semibold text-sm border-b-2 border-indigo-600 text-indigo-600";
            btnOnline.className = "flex-1 py-3 px-4 text-center font-semibold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300";
            divBookmarklet.classList.remove("hidden");
            divOnline.classList.add("hidden");
        } else {
            btnOnline.className = "flex-1 py-3 px-4 text-center font-semibold text-sm border-b-2 border-indigo-600 text-indigo-600";
            btnBookmarklet.className = "flex-1 py-3 px-4 text-center font-semibold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300";
            divOnline.classList.remove("hidden");
            divBookmarklet.classList.add("hidden");
        }
    }

    // Buscar dados do chat da live via AJAX
    function fetchChatData() {
        if (!liveId) return;
        
        fetch(`/admin/lives/${liveId}/chat-data`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (typeof updatePauseState === 'function') updatePauseState(data.is_paused);
                    if (typeof updateInstagramState === 'function') updateInstagramState(data.insta_active);
                    renderChatMessages(data.messages);
                    renderOnlineUsers(data.online_users);
                    renderCodeRequests(data.code_requests);
                }
            })
            .catch(err => console.error("Erro no polling da live:", err));
    }

    async function toggleMasterPause() {
        try {
            const res = await fetch("/admin/live-chat/toggle-pause", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
                }
            });
            const data = await res.json();
            if (data.success) updatePauseState(data.is_paused);
        } catch (e) {
            console.error("Erro ao alternar pausa");
        }
    }

    function updatePauseState(isPaused) {
        const btn = document.getElementById("btn-master-pause");
        const dot = document.getElementById("chat-ping-dot");
        const text = document.getElementById("chat-status-text");

        if (btn) {
            if (isPaused) {
                btn.className = "bg-green-600 hover:bg-green-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition duration-150 shadow-sm flex items-center gap-1.5";
                btn.innerHTML = `<i class="fas fa-play text-[10px]"></i> Retomar Captura`;
            } else {
                btn.className = "bg-red-600 hover:bg-red-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition duration-150 shadow-sm flex items-center gap-1.5";
                btn.innerHTML = `<i class="fas fa-pause text-[10px]"></i> Pausar Captura`;
            }
        }

        if (isPaused) {
            if (dot) dot.className = "inline-block w-2.5 h-2.5 bg-yellow-500 rounded-full";
            if (text) text.textContent = "Pausado no Sistema";
        } else {
            if (dot) dot.className = "inline-block w-2.5 h-2.5 bg-green-500 rounded-full animate-ping";
            if (text) text.textContent = "Capturando";
        }
    }

    // Renderizar mensagens de chat no terminal
    function renderChatMessages(messages) {
        const container = document.getElementById("chat-messages-container");
        if (messages.length === 0) return;

        let html = '';
        messages.forEach(msg => {
            const isTikTok = msg.plataforma === 'tiktok';
            const icon = isTikTok ? '<i class="fab fa-tiktok text-pink-500 mr-1.5"></i>' : '<i class="fab fa-instagram text-purple-500 mr-1.5"></i>';
            const time = new Date(msg.created_at).toLocaleTimeString();
            
            html += `
                <div class="hover:bg-gray-800/50 p-1.5 rounded transition duration-150">
                    <div class="flex items-baseline justify-between mb-0.5">
                        <span class="font-bold text-xs text-indigo-400 flex items-center">
                            ${icon} @${msg.username}
                        </span>
                        <span class="text-[9px] text-gray-600 font-mono">${time}</span>
                    </div>
                    <p class="text-sm text-gray-200 leading-normal pl-4">${escapeHtml(msg.message)}</p>
                </div>
            `;
        });

        const shouldScroll = container.scrollTop + container.clientHeight >= container.scrollHeight - 100;
        container.innerHTML = html;
        if (shouldScroll) {
            container.scrollTop = container.scrollHeight;
        }
    }

    // Renderizar usuários online
    function renderOnlineUsers(users) {
        const list = document.getElementById("online-users-list");
        const count = document.getElementById("online-users-count");
        count.textContent = users.length;

        if (users.length === 0) {
            list.innerHTML = `<p class="text-xs text-gray-400 text-center py-6">Nenhum participante detectado ainda.</p>`;
            return;
        }

        let html = '';
        users.forEach(u => {
            const isTikTok = u.plataforma === 'tiktok';
            const icon = isTikTok ? '<i class="fab fa-tiktok text-pink-500 text-xs"></i>' : '<i class="fab fa-instagram text-purple-500 text-xs"></i>';
            const initials = u.username.slice(0,2).toUpperCase();
            
            let subtitle = '';
            if (u.user_id) {
                const clientName = escapeHtml(u.user_name || u.user_apelido || '');
                subtitle = clientName ? `<span class="text-[8.5px] font-normal text-green-700 flex items-center gap-1 mt-0.5"><i class="fas fa-user text-[7.5px]"></i> ${clientName}</span>` : '';
            } else {
                subtitle = `<span class="text-[9.5px] text-gray-400">Visto às ${u.last_seen}</span>`;
            }

            html += `
                <div class="flex items-center justify-between p-2 rounded-xl bg-gray-50 border border-gray-150 hover:bg-gray-100 transition duration-150">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center font-bold text-xs text-indigo-700">
                            ${initials}
                        </div>
                        <div>
                            <div class="flex items-center gap-1 text-xs font-semibold text-gray-800">
                                ${icon} @${escapeHtml(u.username)}
                            </div>
                            ${subtitle}
                        </div>
                    </div>
                </div>
            `;
        });

        list.innerHTML = html;
    }

    // Renderizar fila de pedidos por códigos
    function renderCodeRequests(requests) {
        const container = document.getElementById("code-requests-container");
        const count = document.getElementById("code-queue-count");
        count.textContent = `${requests.length} códigos`;

        if (requests.length === 0) {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full text-gray-400 py-12">
                    <i class="fas fa-box-open text-4xl mb-2"></i>
                    <p class="text-sm">Aguardando códigos detectados no chat...</p>
                </div>
            `;
            return;
        }

        let html = '';
        requests.forEach(req => {
            let queueHtml = '';
            req.queue.forEach((qItem, index) => {
                const isFirst = index === 0;
                let userBlock = '';
                let actionBtn = '';

                if (qItem.user_id) {
                    userBlock = `
                        <span class="text-xs font-bold text-green-700 flex items-center gap-1.5">
                            <i class="fas fa-user-circle"></i> @${qItem.username} <span class="text-[9.5px] font-normal text-green-600">(${escapeHtml(qItem.user_name)})</span>
                        </span>
                    `;
                    actionBtn = `
                        <button onclick="addToBag(${qItem.id}, ${qItem.user_id}, ${req.item_id})" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-3 py-1 rounded-lg text-xs transition duration-150 shadow-sm">
                            <i class="fas fa-cart-plus mr-1"></i> Sacola
                        </button>
                    `;
                } else {
                    userBlock = `
                        <span class="text-xs font-bold text-amber-700 flex items-center gap-1">
                            <i class="fas fa-question-circle"></i> @${qItem.username} (Sem cadastro)
                        </span>
                    `;
                    actionBtn = `
                        <button onclick="openLinkModal('${escapeHtml(qItem.username)}', 'instagram')" class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-3 py-1 rounded-lg text-xs transition duration-150 shadow-sm">
                            <i class="fas fa-link mr-1"></i> Vincular
                        </button>
                    `;
                }

                queueHtml += `
                    <div class="flex items-center justify-between p-2.5 rounded-xl border ${isFirst ? 'bg-indigo-50 border-indigo-200' : 'bg-white border-gray-100'} shadow-sm hover:shadow transition duration-150">
                        <div class="flex items-center gap-3">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold ${isFirst ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'}">${index + 1}º</span>
                            <div>
                                ${userBlock}
                                <span class="text-[10px] text-gray-400 block">Texto: "${escapeHtml(qItem.message_text)}" às ${qItem.created_at}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            ${actionBtn}
                            <button onclick="ignoreRequest(${qItem.id})" class="text-gray-400 hover:text-red-500 px-2 py-1 rounded transition duration-150" title="Ignorar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            html += `
                <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm space-y-3">
                    <div class="flex items-start justify-between pb-3 border-b border-gray-100">
                        <div>
                            <span class="inline-block bg-indigo-100 text-indigo-800 font-extrabold text-sm px-3 py-1 rounded-full uppercase tracking-wider mb-1">
                                Cód: ${req.codigo}
                            </span>
                            <h3 class="font-bold text-gray-800 text-sm">${escapeHtml(req.item_nome)}</h3>
                        </div>
                        <div class="text-right">
                            <span class="font-extrabold text-lg text-indigo-600">R$ ${parseFloat(req.item_preco).toFixed(2).replace('.', ',')}</span>
                            <span class="block text-[10px] text-gray-400 uppercase font-semibold">Status: ${req.item_status}</span>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Fila de Espera:</h4>
                        ${queueHtml}
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // Adicionar item à sacola
    function addToBag(requestId, userId, itemId) {
        if (!confirm("Confirmar adição deste item à sacola do cliente?")) return;

        fetch('/admin/live-chat/add-to-bag', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                code_request_id: requestId,
                user_id: userId,
                item_id: itemId,
                live_id: liveId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast("Item adicionado com sucesso!");
                fetchChatData();
            } else {
                alert("Erro ao adicionar: " + data.message);
            }
        })
        .catch(err => console.error("Erro ao adicionar à sacola:", err));
    }

    // Ignorar solicitação de código
    function ignoreRequest(requestId) {
        if (!confirm("Deseja ignorar esta solicitação de código?")) return;

        fetch('/admin/live-chat/ignore', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                code_request_id: requestId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                fetchChatData();
            }
        })
        .catch(err => console.error(err));
    }

    // Controladores do Modal de Vincular Usuário
    function openLinkModal(username, platform) {
        document.getElementById("modal-display-username").textContent = `@${username}`;
        document.getElementById("modal-display-platform").textContent = platform.toUpperCase();
        document.getElementById("modal-input-username").value = username;
        document.getElementById("modal-input-platform").value = platform;
        document.getElementById("modal-search-input").value = "";
        document.getElementById("modal-search-results").innerHTML = `<p class="text-xs text-gray-400 text-center py-4">Comece a digitar para pesquisar clientes.</p>`;
        
        document.getElementById("link-user-modal").classList.remove("hidden");
        document.getElementById("modal-search-input").focus();
    }

    function closeLinkModal() {
        document.getElementById("link-user-modal").classList.add("hidden");
    }

    // Buscar clientes via AJAX para vinculação
    function searchClients(query) {
        const resultsContainer = document.getElementById("modal-search-results");
        if (query.trim().length < 2) {
            resultsContainer.innerHTML = `<p class="text-xs text-gray-400 text-center py-4">Digite pelo menos 2 caracteres.</p>`;
            return;
        }

        fetch(`/users/search?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    let html = '';
                    data.data.forEach(user => {
                        html += `
                            <div onclick="linkUserToProfile('${user.id}')" class="p-2.5 rounded-lg border border-gray-200 bg-white hover:bg-indigo-50 hover:border-indigo-300 transition duration-150 cursor-pointer flex justify-between items-center">
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

    // Executar vinculação do cliente
    function linkUserToProfile(userId) {
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
                fetchChatData();
            } else {
                alert("Erro ao vincular: " + data.message);
            }
        })
        .catch(err => console.error("Erro ao vincular usuário:", err));
    }

    // Utilitários de escape de HTML
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

    // Mostrar mensagem Toast na tela
    function showToast(message) {
        const toast = document.createElement("div");
        toast.className = "fixed bottom-5 right-5 bg-green-600 text-white px-5 py-3 rounded-xl shadow-lg z-50 transition-all duration-300 translate-y-5 opacity-0 text-sm font-semibold flex items-center gap-2";
        toast.innerHTML = `<i class="fas fa-check-circle"></i> <span>${message}</span>`;
        document.body.appendChild(toast);
        
        // Animates in
        setTimeout(() => {
            toast.style.transform = "translateY(0)";
            toast.style.opacity = "1";
        }, 100);

        // Animates out and removes
        setTimeout(() => {
            toast.style.transform = "translateY(5px)";
            toast.style.opacity = "0";
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
</script>
@endpush

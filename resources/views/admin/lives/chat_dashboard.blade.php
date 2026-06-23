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
                <div class="flex items-center gap-1.5">
                    <span class="inline-block w-2.5 h-2.5 bg-green-500 rounded-full animate-ping"></span>
                    <span class="text-xs text-gray-300">Conectado</span>
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
                    Bookmarklet
                </button>
                <button id="tab-btn-online" onclick="switchTab('online')" class="flex-1 py-3 px-4 text-center font-semibold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Pessoas Online
                </button>
            </div>

            <!-- Tab content container -->
            <div class="flex-1 p-4 overflow-y-auto">
                <!-- Tab: Bookmarklet e Extensão -->
                <div id="tab-content-bookmarklet" class="space-y-4">
                    <!-- Opção 1: Extensão Chrome -->
                    <div class="bg-indigo-50 rounded-xl p-4 border border-indigo-100">
                        <h3 class="font-bold text-indigo-800 text-sm flex items-center gap-1.5">
                            <i class="fab fa-chrome"></i>
                            Opção 1: Extensão Chrome (Recomendado)
                        </h3>
                        <p class="text-xs text-indigo-700 mt-2 leading-relaxed">
                            Evita bloqueios de segurança do Instagram e TikTok. A janelinha de captura abrirá automaticamente sempre que acessar a live!
                        </p>
                        
                        <div class="mt-3 space-y-2 text-xs text-gray-700">
                            <p><strong>Como Instalar:</strong></p>
                            <ol class="list-decimal pl-4 space-y-1">
                                <li>Acesse <code class="bg-gray-150 px-1 py-0.5 rounded text-[10px] font-mono select-all">chrome://extensions</code> no Chrome.</li>
                                <li>Ative o <strong>Modo do desenvolvedor</strong> (canto superior direito).</li>
                                <li>Clique em <strong>Carregar sem compactação</strong> (canto superior esquerdo).</li>
                                <li>Selecione a pasta do projeto: <br><code class="bg-gray-100 px-1.5 py-1 rounded text-[9px] font-mono block mt-1 select-all break-all border border-gray-250">C:\serv\sacolinhas\public\extension</code></li>
                            </ol>
                        </div>
                    </div>

                    <!-- Opção 2: Console F12 -->
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                        <h3 class="font-bold text-gray-800 text-sm flex items-center gap-1.5">
                            <i class="fas fa-terminal"></i>
                            Opção 2: Console do Navegador (Sem Instalar)
                        </h3>
                        <p class="text-xs text-gray-600 mt-2 leading-relaxed">
                            Cole o código completo abaixo diretamente no console da live.
                        </p>
                        
                        <div class="mt-3 space-y-2">
                            <ol class="list-decimal pl-4 text-xs text-gray-600 space-y-1">
                                <li>Abra a live no Chrome.</li>
                                <li>Pressione <strong>F12</strong> no teclado e vá na aba <strong>Console</strong>.</li>
                                <li>Copie o código abaixo, cole no console e dê <strong>Enter</strong>.</li>
                            </ol>
                            
                            @php
                                $consoleCode = @file_get_contents(public_path('js/live-chat-bookmarklet.js'));
                            @endphp
                            <textarea readonly onclick="this.select()" class="w-full h-24 p-2 text-[9px] font-mono bg-gray-900 text-green-400 border border-gray-300 rounded-lg mt-2 resize-none focus:outline-none" placeholder="Carregando código..."></textarea>
                            <script>
                                document.addEventListener("DOMContentLoaded", function() {
                                    // Preencher o textarea com o código completo do console
                                    const textarea = document.querySelector('textarea[placeholder="Carregando código..."]');
                                    if (textarea) {
                                        // Usando o código estático já carregado no script
                                        fetch('/js/live-chat-bookmarklet.js')
                                            .then(r => r.text())
                                            .then(t => { textarea.value = t; });
                                    }
                                });
                            </script>
                        </div>
                    </div>

                    <!-- Opção 3: Favorito / Bookmarklet -->
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                        <h3 class="font-bold text-gray-800 text-sm flex items-center gap-1.5">
                            <i class="fas fa-anchor"></i>
                            Opção 3: Bookmarklet (Favorito)
                        </h3>
                        <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                            Pode ser bloqueado pelo Instagram/TikTok devido à política de segurança (CSP).
                        </p>
                        <div class="mt-3 text-center">
                            <a id="bookmarklet-link" href="#" class="inline-flex items-center gap-2 bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-3 rounded-lg text-xs transition duration-200 cursor-move">
                                <i class="fas fa-anchor"></i>
                                Capturar Live Sacolas
                            </a>
                            <input type="hidden" id="bookmarklet-code">
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
    });

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
                    renderChatMessages(data.messages);
                    renderOnlineUsers(data.online_users);
                    renderCodeRequests(data.code_requests);
                }
            })
            .catch(err => console.error("Erro no polling da live:", err));
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
            
            let badge = '';
            if (u.user_id) {
                badge = `<span class="bg-green-100 text-green-800 text-[10px] font-bold px-1.5 py-0.5 rounded-full flex items-center gap-1">
                            <i class="fas fa-check text-[8px]"></i> ${escapeHtml(u.user_name)}
                         </span>`;
            } else {
                badge = `<button onclick="openLinkModal('${escapeHtml(u.username)}', '${u.plataforma}')" class="bg-amber-100 hover:bg-amber-200 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 border border-amber-300 transition duration-150">
                            <i class="fas fa-link text-[8px]"></i> Vincular
                         </button>`;
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
                            <span class="text-[10px] text-gray-400">Visto às ${u.last_seen}</span>
                        </div>
                    </div>
                    <div>
                        ${badge}
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
                        <span class="text-xs font-bold text-green-700 flex items-center gap-1">
                            <i class="fas fa-user-circle"></i> ${escapeHtml(qItem.user_name)} (@${qItem.username})
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
                            <i class="fas fa-question-circle"></i> @${qItem.username} (Não vinculado)
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

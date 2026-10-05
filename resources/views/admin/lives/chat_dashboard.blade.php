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
        
        <!-- Seleção de Live e Ações -->
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.live-chat.feed', ['live_id' => $activeLive ? $activeLive->id : '']) }}" target="_blank" class="font-extrabold px-4 py-2.5 rounded-xl text-xs shadow-md transition duration-150 flex items-center gap-2 cursor-pointer active:scale-95 border border-purple-500" style="background-color: #9333ea !important; color: #ffffff !important;">
                <i class="fas fa-comment-dots text-base text-purple-200"></i>
                <span style="color: #ffffff !important;">Tela do Chat (Ao Vivo)</span>
            </a>

            <a href="{{ route('admin.live-chat.bipagem', ['live_id' => $activeLive ? $activeLive->id : '']) }}" target="_blank" class="font-extrabold px-4 py-2.5 rounded-xl text-xs shadow-md transition duration-150 flex items-center gap-2 cursor-pointer active:scale-95 border border-indigo-500" style="background-color: #4f46e5 !important; color: #ffffff !important;">
                <i class="fas fa-qrcode text-base text-indigo-200"></i>
                <span style="color: #ffffff !important;">Bipagem Contínua / QR Code</span>
            </a>

            <a href="{{ route('admin.live-chat.contador', ['live_id' => $activeLive ? $activeLive->id : '']) }}" target="_blank" class="font-extrabold px-4 py-2.5 rounded-xl text-xs shadow-md transition duration-150 flex items-center gap-2 cursor-pointer active:scale-95 border border-emerald-500" style="background-color: #059669 !important; color: #ffffff !important;">
                <i class="fas fa-calculator text-base text-emerald-200"></i>
                <span style="color: #ffffff !important;">Contador (Telão)</span>
            </a>

            @if($activeLive)
                <a href="{{ route('admin.lives.cortes', ['liveId' => $activeLive->id]) }}" target="_blank" class="font-black px-4 py-2.5 rounded-xl text-xs shadow-md transition duration-150 flex items-center gap-2 cursor-pointer active:scale-95 border border-teal-600" style="background-color: #0f766e !important; color: #ffffff !important;">
                    <i class="fas fa-film text-sm text-teal-200"></i>
                    <span style="color: #ffffff !important; font-weight: 800;">Cortes & Vídeos</span>
                </a>
                <a href="{{ route('admin.lives.relatorio-pdf', ['liveId' => $activeLive->id]) }}" target="_blank" class="font-black px-4 py-2.5 rounded-xl text-xs shadow-md transition duration-150 flex items-center gap-2 cursor-pointer active:scale-95 border border-indigo-600" style="background-color: #4338ca !important; color: #ffffff !important;" title="Gerar e Baixar Relatório de Fechamento em PDF para a Loja">
                    <i class="fas fa-file-pdf text-sm text-indigo-200"></i>
                    <span style="color: #ffffff !important; font-weight: 800;">Relatório PDF</span>
                </a>
            @endif

            @if($activeLive && $activeLive->ativo)
                <button type="button" onclick="confirmEndLive({{ $activeLive->id }})" class="font-bold px-4 py-2.5 rounded-xl text-xs shadow-md transition duration-150 flex items-center gap-2 cursor-pointer active:scale-95 border border-red-600" style="background-color: #dc2626 !important; color: #ffffff !important;">
                    <i class="fas fa-stop-circle text-sm text-red-200"></i>
                    <span style="color: #ffffff !important;">Encerrar Live & WhatsApp</span>
                </button>
            @endif

            <div id="twilio-balance-badge" class="bg-blue-50 border border-blue-200 text-blue-700 px-3 py-2 rounded-xl text-xs font-bold shadow-sm flex items-center gap-2 cursor-pointer" onclick="fetchTwilioBalance()">
                <i class="fas fa-wallet"></i>
                <span id="twilio-balance-text">Saldo Twilio: Carregando...</span>
            </div>

            <div class="flex items-center gap-3 bg-white p-2.5 rounded-xl shadow-sm border border-gray-200">
                <label for="live-select" class="text-xs font-bold text-gray-600">Live Ativa:</label>
                <form action="{{ route('admin.live-chat.dashboard') }}" method="GET" class="flex gap-2">
                    <select name="live_id" id="live-select" onchange="this.form.submit()" class="text-xs rounded-lg border border-gray-300 bg-gray-50 p-2 text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 font-semibold">
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

        <!-- COLUNA 1: CHAT EM TEMPO REAL (ESQUERDA - LARGURA 6) -->
        <div class="lg:col-span-6 flex flex-col bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden" style="height: 78vh;">
            <div class="bg-gray-800 px-4 py-3.5 flex items-center justify-between text-white">
                <div class="flex items-center gap-2">
                    <i class="fas fa-comment-alt text-indigo-400"></i>
                    <h2 class="font-bold text-sm">Chat da Transmissão</h2>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5 bg-gray-900/60 px-2 py-1 rounded-lg">
                        <span class="inline-block w-2 h-2 bg-green-500 rounded-full animate-ping" id="chat-ping-dot"></span>
                        <span class="text-[11px] text-gray-300 font-medium" id="chat-status-text">Capturando</span>
                    </div>
                </div>
            </div>
            
            <!-- Barra de Filtros do Chat -->
            <div class="px-3 py-2 flex items-center justify-between gap-2 border-b border-gray-800 text-xs shrink-0" style="background-color: #111827;">
                <div class="flex items-center gap-1.5">
                    <button type="button" id="chat-filter-all-btn" onclick="setChatFilter('all')" class="px-2.5 py-1 rounded-lg font-bold text-xs bg-indigo-600 text-white transition shadow-sm cursor-pointer">
                        Todas
                    </button>
                    <button type="button" id="chat-filter-marked-btn" onclick="setChatFilter('marked')" class="px-2.5 py-1 rounded-lg font-bold text-xs bg-gray-800 text-yellow-400 hover:bg-gray-700 transition flex items-center gap-1 border border-gray-700 cursor-pointer">
                        <i class="fas fa-star text-yellow-400 text-[10px]"></i> Marcadas (<span id="chat-marked-count">0</span>)
                    </button>
                    <button type="button" id="dashboard-autoscroll-btn" onclick="toggleChatAutoScroll()" class="px-2 py-1 rounded-lg font-bold text-[11px] bg-emerald-950 text-emerald-300 hover:bg-emerald-900 transition flex items-center gap-1 border border-emerald-700 cursor-pointer shadow-sm" title="Ativar ou desativar rolagem automática do chat">
                        <i class="fas fa-arrow-down text-[10px]" id="dashboard-autoscroll-icon"></i> <span id="dashboard-autoscroll-text">Auto</span>
                    </button>
                </div>
                <div id="chat-user-filter-badge" class="hidden items-center gap-1.5 bg-indigo-950 text-indigo-200 px-2.5 py-1 rounded-lg text-xs border border-indigo-700">
                    <span class="truncate max-w-[110px] font-semibold" id="chat-filtered-username">@usuario</span>
                    <button type="button" onclick="clearUserChatFilter()" class="text-indigo-400 hover:text-white font-bold ml-1 cursor-pointer" title="Limpar filtro">&times;</button>
                </div>
            </div>
            
            <div id="chat-messages-container" class="flex-1 p-3 overflow-y-auto space-y-2.5 bg-gray-900 text-gray-100 font-sans">
                @if(!$activeLive)
                    <div class="flex flex-col items-center justify-center h-full text-gray-500">
                        <i class="fas fa-video-slash text-3xl mb-2"></i>
                        <p class="text-xs text-center">Selecione uma live para ativar o chat.</p>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-full text-gray-500">
                        <i class="fas fa-plug text-3xl mb-2"></i>
                        <p class="text-xs text-center">Aguardando mensagens do chat...</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- COLUNA 2: INTEGRAÇÃO / PARTICIPANTES (DIREITA - LARGURA 6) -->
        <div class="lg:col-span-6 flex flex-col bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden" style="height: 78vh;">
            <!-- Tabs -->
            <div class="flex border-b border-gray-200 bg-gray-50">
                <button id="tab-btn-bookmarklet" onclick="switchTab('bookmarklet')" class="flex-1 py-3 px-3 text-center font-semibold text-xs border-b-2 border-indigo-600 text-indigo-600">
                    Conectar Lives
                </button>
                <button id="tab-btn-online" onclick="switchTab('online')" class="flex-1 py-3 px-3 text-center font-semibold text-xs border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Pessoas Online
                </button>
            </div>

            <!-- Tab content container -->
            <div class="flex-1 p-4 overflow-y-auto">
                <!-- Tab: Conectar Lives (Gravação via Social Stream) -->
                <div id="tab-content-bookmarklet" class="space-y-4">
                    <!-- O bloco do Social Stream Ninja foi removido para deixar só o necessário -->

                    <!-- 1.5. Extensão Oficial (Alternativa 100% Silenciosa) -->
                    <div class="bg-blue-50 rounded-xl p-4 border border-blue-200 shadow-sm mt-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-blue-900 text-sm flex items-center gap-1.5">
                                <i class="fas fa-puzzle-piece text-blue-600 text-base"></i>
                                Extensão Oficial Minha Mania (Alternativa)
                            </h3>
                            <span class="bg-blue-200 text-blue-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
                                <i class="fas fa-ghost"></i> 100% Invisível
                            </span>
                        </div>
                        <p class="text-xs text-blue-800 mt-2 leading-relaxed">
                            Se você <strong>não tiver</strong> o Social Stream Ninja, use nossa Extensão Oficial. Ela foi atualizada para rodar de forma <strong>completamente silenciosa</strong> no fundo. Nenhuma janela vai abrir no seu Instagram! Apenas instale e use os botões de Iniciar Gravação abaixo.
                        </p>
                        
                        <div class="mt-3 flex gap-2 items-center bg-white p-2 rounded-lg border border-blue-300 shadow-inner justify-between">
                            <span class="text-[11px] font-bold text-gray-700">Capturador Silencioso v1.4:</span>
                            <a href="/extensao-capturador-minhamania.zip" download class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-[10px] font-bold transition flex items-center gap-1 shadow">
                                <i class="fas fa-download"></i> Baixar Extensão (.ZIP)
                            </a>
                        </div>
                    </div>

                    <!-- 2. Controle de Gravação Instagram -->
                    <div class="bg-purple-50 rounded-xl p-4 border border-purple-200 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-purple-900 text-sm flex items-center gap-1.5">
                                <i class="fab fa-instagram text-purple-600 text-base"></i>
                                Gravação Instagram
                            </h3>
                            <span id="insta-badge" class="bg-purple-200 text-purple-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 transition-all">
                                <span id="insta-status-dot" class="hidden w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                <span id="insta-status-text">Inativo</span>
                            </span>
                        </div>
                        <p class="text-[11px] text-purple-800 mt-1.5 leading-relaxed">
                            Clique em Iniciar para que o servidor permita salvar os comentários do Instagram enviados pelo Social Stream.
                        </p>
                        <div class="mt-3 flex gap-2">
                            <button type="button" onclick="toggleInstagramCapture()" id="insta-toggle-btn" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 rounded-lg text-xs transition duration-150 shadow-sm flex items-center justify-center gap-1.5">
                                <i class="fas fa-play"></i> Iniciar Gravação Instagram
                            </button>
                        </div>
                    </div>

                    <!-- 3. Controle de Gravação TikTok -->
                    <div class="bg-pink-50 rounded-xl p-4 border border-pink-200 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-pink-900 text-sm flex items-center gap-1.5">
                                <i class="fab fa-tiktok text-pink-600 text-base"></i>
                                Gravação TikTok
                            </h3>
                            <span id="tiktok-backend-badge" class="bg-gray-200 text-gray-700 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 transition-all">
                                <span id="tiktok-status-dot" class="w-1.5 h-1.5 rounded-full bg-gray-500"></span>
                                <span id="tiktok-status-text">Inativo</span>
                            </span>
                        </div>
                        <p class="text-[11px] text-pink-800 mt-1.5 leading-relaxed">
                            Clique em Iniciar para que o servidor permita salvar os comentários do TikTok enviados pelo Social Stream.
                        </p>
                        <div class="mt-3 flex gap-2">
                            <button type="button" onclick="toggleTikTokBackend()" id="tiktok-toggle-btn" class="w-full bg-pink-600 hover:bg-pink-700 text-white font-bold py-2 rounded-lg text-xs transition duration-150 shadow-sm flex items-center justify-center gap-1.5">
                                <i class="fas fa-play"></i> Iniciar Gravação TikTok
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tab: Pessoas Online -->
                <div id="tab-content-online" class="hidden flex flex-col h-full">
                    <!-- Busca de Cliente Avulso + Botões de Ação Rápida -->
                    <div class="relative z-20 shrink-0 mb-3 flex items-center gap-2">
                        <div class="relative flex-1">
                            <input type="text" id="avulso-search-input" placeholder="Buscar cliente por nome, apelido, cel..." class="w-full p-2.5 pl-9 rounded-xl border-2 border-indigo-100 bg-indigo-50/30 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 text-xs font-bold text-gray-800 placeholder-indigo-400 transition-all">
                            <i class="fas fa-search absolute left-3 top-3 text-indigo-400 text-xs"></i>
                            <div id="avulso-search-results" class="absolute left-0 right-0 mt-1 max-h-64 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-2xl hidden flex flex-col z-30">
                            </div>
                        </div>
                        <button type="button" onclick="openQuickClientModal()" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm shrink-0 cursor-pointer border border-emerald-700" style="background-color: #059669 !important; color: #ffffff !important;" title="Cadastrar Novo Cliente">
                            <i class="fas fa-user-plus text-xs" style="color: #ffffff !important;"></i> <span style="color: #ffffff !important;">+ Cliente</span>
                        </button>
                        <button type="button" onclick="openQuickProductModal()" class="px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm shrink-0 cursor-pointer border border-purple-700" style="background-color: #7c3aed !important; color: #ffffff !important;" title="Cadastrar Novo Produto">
                            <i class="fas fa-tag text-xs" style="color: #ffffff !important;"></i> <span style="color: #ffffff !important;">+ Produto</span>
                        </button>
                    </div>

                    <!-- Banner de Cliente Selecionada para Bipar / Cadastrar Peças -->
                    <div id="selected-participant-card" class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-2xl p-2.5 mb-3 shrink-0 transition-all">
                        <div class="flex items-center justify-between gap-1 mb-2 bg-gray-200/70 p-1 rounded-xl border border-gray-300/60">
                            <button type="button" onclick="setLiveOperationMode('external')" class="flex-1 py-1 px-2 rounded-lg text-[10px] font-black transition flex items-center justify-center gap-1.5 cursor-pointer bg-purple-700 text-white shadow-xs">
                                <i class="fas fa-tag"></i> Modo Live Externa
                            </button>
                            <button type="button" onclick="setLiveOperationMode('stock')" class="flex-1 py-1 px-2 rounded-lg text-[10px] font-black transition flex items-center justify-center gap-1.5 cursor-pointer text-indigo-950 hover:bg-indigo-200/50">
                                <i class="fas fa-barcode"></i> Modo Estoque (Leitor)
                            </button>
                        </div>
                        <div class="flex items-center justify-center gap-2 text-gray-500 text-xs py-1 font-bold">
                            <i class="fas fa-hand-pointer text-indigo-500"></i>
                            <span>Clique em uma cliente na lista abaixo para selecioná-la e registrar vendas</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mb-3 shrink-0">
                        <h3 class="font-bold text-gray-700 text-sm">Participantes na Transmissão</h3>
                        <span id="online-users-count" class="bg-gray-200 text-gray-700 text-xs px-2 py-0.5 rounded-full font-bold">0</span>
                    </div>
                    
                    <div id="online-users-list" class="space-y-2.5 flex-1 overflow-y-auto relative z-10 pb-2">
                        <!-- Gerado dinamicamente -->
                        <p class="text-xs text-gray-400 text-center py-6">Nenhum participante detectado ainda.</p>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- MODAL VINCULAR CLIENTE -->
<div id="link-user-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 backdrop-blur-sm z-50 flex items-center justify-center hidden" style="z-index: 99999;">
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

<!-- MODAL LEITOR QR CODE PARA PESSOA ONLINE (TELA INTEIRA / FULLSCREEN) -->
<div id="online-qr-modal" class="fixed inset-0 bg-gray-900 z-50 flex flex-col hidden overflow-hidden" style="z-index: 99999;">
    <!-- Cabeçalho Fullscreen Elegante -->
    <div class="bg-gray-900 border-b border-gray-800 px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 shrink-0 shadow-2xl">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-100 border border-indigo-200 flex items-center justify-center shrink-0 shadow-inner">
                <i class="fas fa-qrcode text-indigo-400 text-2xl animate-pulse"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-lg sm:text-xl font-extrabold text-white tracking-wide">
                        Leitor de Etiqueta / QRCode
                    </h3>
                    <span class="bg-indigo-100 text-indigo-600 border border-indigo-200 px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider" style="font-size: 11px;">Tela Inteira</span>
                </div>
                <p class="text-xs sm:text-sm text-gray-300 mt-0.5">Adicionando itens na sacola de: <strong id="online-qr-client-name" class="text-indigo-400 font-extrabold text-sm sm:text-base bg-gray-800 px-2.5 py-0.5 rounded-md border border-gray-700">@usuario</strong></p>
            </div>
        </div>

        <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
            <label class="flex items-center gap-2 bg-gray-800 hover:bg-gray-750 border border-gray-700 px-3 py-1.5 rounded-xl cursor-pointer select-none transition shadow-sm" title="Fechar automaticamente o leitor após bipar a peça">
                <input type="checkbox" id="online-qr-auto-close-toggle" onchange="toggleAutoClosePreference(this.checked)" class="w-4 h-4 text-indigo-600 bg-gray-700 border-gray-600 rounded focus:ring-indigo-500 focus:ring-offset-gray-800 cursor-pointer">
                <span class="text-xs font-bold text-gray-200 flex items-center gap-1.5">
                    <i class="fas fa-magic text-indigo-400 text-[11px]"></i> Fechar após bipar
                </span>
            </label>
            <button onclick="closeOnlineQrModal()" class="bg-red-600 hover:bg-red-500 text-white font-bold px-6 py-2.5 rounded-xl text-sm transition-all duration-200 flex items-center gap-2 shadow-lg hover:shadow-red-500/20 active:scale-95">
                <i class="fas fa-times text-base"></i> Concluir e Voltar
            </button>
        </div>
    </div>

    <!-- Corpo / Área da Câmera em Tela Inteira -->
    <div class="flex-1 flex flex-col lg:flex-row gap-6 p-4 sm:p-6 overflow-hidden mx-auto w-full" style="max-width: 1600px;">
        <!-- Container da Câmera (Ocupa a maior parte da tela inteira) -->
        <div class="flex-1 flex flex-col bg-black rounded-3xl overflow-hidden relative border-2 border-indigo-500 shadow-2xl lg:min-h-0" style="min-height: 45vh;">
            <div id="online-qr-reader" class="w-full h-full flex-1"></div>
            
            <div class="absolute bottom-4 left-0 right-0 flex justify-center pointer-events-none z-10">
                <div class="bg-gray-900 backdrop-blur-md px-5 py-2.5 rounded-full border border-gray-700 text-gray-200 text-xs sm:text-sm font-semibold flex items-center gap-2.5 shadow-xl">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                    <i class="fas fa-camera text-indigo-400"></i>
                    <span>Aponte a câmera para o QR Code ou Código de Barras da etiqueta</span>
                </div>
            </div>
        </div>

        <!-- Painel Lateral de Controles e Entrada Manual / Feedback (Largura fixa em telas grandes) -->
        <div class="w-full flex flex-col gap-4 shrink-0 overflow-y-auto" style="width: 100%; max-width: 420px;">
            <!-- Box de WhatsApp do Cliente -->
            <div class="bg-gray-900 rounded-3xl p-4 border border-gray-800 shadow-xl flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-extrabold text-white flex items-center gap-1.5">
                        <i class="fab fa-whatsapp text-emerald-400 text-sm"></i>
                        <span>WhatsApp da Cliente</span>
                    </h4>
                    <span id="online-qr-phone-badge" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-gray-800 text-gray-400 border border-gray-700">
                        Carregando...
                    </span>
                </div>
                <div class="flex gap-2">
                    <input type="text" id="online-qr-phone-input" placeholder="DDD + Número (ex: 11999999999)" class="flex-1 px-3 py-2 rounded-xl border border-gray-700 bg-gray-800 text-white placeholder-gray-500 text-xs font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <button type="button" onclick="saveClientPhoneFromModal()" id="online-qr-phone-save-btn" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-3 py-2 rounded-xl text-xs transition flex items-center gap-1 shrink-0 cursor-pointer shadow active:scale-95">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
                <p id="online-qr-phone-help" class="text-[10.5px] text-gray-400 leading-tight">
                    O WhatsApp é indispensável para o envio automático da sacola ao encerrar a live.
                </p>
            </div>

            <!-- Box de Entrada Manual / Leitor USB -->
            <div class="bg-gray-900 rounded-3xl p-5 border border-gray-800 shadow-xl flex flex-col gap-2.5">
                <h4 class="text-xs font-extrabold text-white flex items-center gap-2">
                    <i class="fas fa-keyboard text-indigo-400 text-sm"></i>
                    <span>Bipador USB / Entrada Manual</span>
                </h4>
                <div class="flex gap-2">
                    <input type="text" id="online-qr-manual-input" onkeydown="if(event.key==='Enter') handleOnlineQrScan(this.value)" placeholder="Ex: 0001 ou 73254..." class="flex-1 px-3.5 py-2.5 rounded-xl border border-gray-700 bg-gray-800 text-white placeholder-gray-400 text-sm font-bold focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none shadow-inner">
                    <button type="button" onclick="handleOnlineQrScan(document.getElementById('online-qr-manual-input').value)" class="bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold px-4 py-2.5 rounded-xl text-xs shadow-lg hover:shadow-indigo-500/25 transition-all duration-150 flex items-center justify-center gap-1.5 shrink-0 active:scale-95 cursor-pointer">
                        <i class="fas fa-plus"></i> Bipe
                    </button>
                </div>
            </div>

            <!-- Feedback Visual em Tempo Real -->
            <div id="online-qr-feedback" class="p-4 rounded-2xl text-xs font-bold hidden transition duration-200 border shadow-xl leading-relaxed"></div>

            <!-- Box de Mensagens / Pedidos da Cliente na Live -->
            <div class="bg-gray-900 rounded-3xl p-4 border border-gray-800 shadow-xl flex flex-col gap-2 min-h-[170px] max-h-[250px]">
                <div class="flex items-center justify-between shrink-0">
                    <h4 class="text-xs font-extrabold text-white flex items-center gap-1.5">
                        <i class="fas fa-comment-dots text-indigo-400 text-sm"></i>
                        <span>Mensagens da Cliente</span>
                    </h4>
                    <button type="button" id="modal-client-filter-star-btn" onclick="toggleModalFilterMarkedOnly()" class="text-[10px] px-2 py-0.5 rounded-lg font-bold bg-gray-800 text-yellow-400 hover:bg-gray-700 border border-gray-700 transition flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-star text-yellow-400 text-[9px]"></i> <span id="modal-client-marked-count">0</span> Marcadas
                    </button>
                </div>
                <div id="online-qr-client-messages-list" class="flex-1 overflow-y-auto space-y-1.5 pr-1 text-xs">
                    <p class="text-[11px] text-gray-500 text-center py-4">Nenhum comentário desta cliente ainda.</p>
                </div>
                <div class="text-[10px] text-indigo-300 font-medium pt-1 border-t border-gray-800 shrink-0">
                    <i class="fas fa-hand-pointer text-indigo-400"></i> Clique num código para bipe automático
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE LOGIN AUTOMÁTICO DO INSTAGRAM NO SERVIDOR (VPS) -->
<div id="insta-login-modal" class="fixed inset-0 bg-gray-900/70 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl relative border border-gray-100 flex flex-col">
        <div class="flex justify-between items-center pb-3 border-b border-gray-100 mb-4">
            <h3 class="text-base font-bold text-purple-800 flex items-center gap-2">
                <i class="fas fa-key text-purple-600 text-xl"></i>
                <span>Login Direto no Servidor (VPS)</span>
            </h3>
            <button type="button" onclick="closeInstaLoginModal()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold p-1 leading-none">&times;</button>
        </div>

        <p class="text-xs text-gray-600 mb-4 leading-relaxed">
            Faça login na sua conta (ou em um perfil secundário/anônimo, ex: <strong>@sacolinhas_captura</strong>) diretamente dentro do navegador do servidor. Isso gera uma sessão definitiva na VPS e evita que o Instagram deslogue por troca de IP!
        </p>

        <div id="insta-login-form-step">
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Usuário do Instagram (@usuario):</label>
                    <input type="text" id="vps-insta-user" placeholder="Ex: sacolinhas_captura ou de_minha_mania" class="w-full p-2.5 rounded-xl border border-gray-300 text-sm font-semibold focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Senha:</label>
                    <input type="password" id="vps-insta-pass" placeholder="Sua senha do Instagram" class="w-full p-2.5 rounded-xl border border-gray-300 text-sm font-semibold focus:ring-purple-500 focus:border-purple-500">
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-3 border-t border-gray-100 pt-4">
                <button type="button" onclick="closeInstaLoginModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl text-xs transition duration-150">Cancelar</button>
                <button type="button" id="vps-login-submit-btn" onclick="submitVpsInstaLogin()" class="bg-purple-600 hover:bg-purple-700 text-white font-bold px-5 py-2 rounded-xl text-xs shadow-sm transition duration-150 flex items-center gap-2">
                    <i class="fas fa-sign-in-alt"></i> Fazer Login e Salvar Sessão
                </button>
            </div>
        </div>

        <!-- Passo 2FA / Desafio -->
        <div id="insta-login-2fa-step" class="hidden space-y-4">
            <div class="bg-amber-50 border border-amber-300 p-3 rounded-xl text-xs text-amber-800 font-medium">
                <p class="font-bold mb-1"><i class="fas fa-shield-alt"></i> Verificação de Segurança (2FA / Desafio)</p>
                <p id="vps-2fa-msg">O Instagram enviou um código para seu e-mail, SMS ou aplicativo autenticador. Digite o código abaixo:</p>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Código de Verificação (6 dígitos):</label>
                <input type="text" id="vps-insta-code" placeholder="Ex: 123456" class="w-full p-2.5 rounded-xl border border-gray-300 text-sm font-bold text-center tracking-widest focus:ring-purple-500 focus:border-purple-500">
            </div>
            <div class="flex justify-end gap-3 border-t border-gray-100 pt-3">
                <button type="button" onclick="closeInstaLoginModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl text-xs">Cancelar</button>
                <button type="button" id="vps-2fa-submit-btn" onclick="submitVpsInstaCode()" class="bg-purple-600 hover:bg-purple-700 text-white font-bold px-5 py-2 rounded-xl text-xs shadow-sm flex items-center gap-2">
                    <i class="fas fa-check"></i> Confirmar Código
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CADASTRO RÁPIDO DE CLIENTE -->
<div id="modal-quick-client" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" style="background-color: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); z-index: 99999;">
    <div class="relative w-full max-w-md rounded-3xl overflow-hidden flex flex-col" style="background-color: #ffffff; border: 2px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #065f46 0%, #047857 100%); padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; color: #ffffff; font-size: 18px;">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div>
                    <h3 style="color: #ffffff !important; font-size: 15px; font-weight: 900; margin: 0; line-height: 1.2;">Cadastrar Novo Cliente</h3>
                    <p style="color: #a7f3d0 !important; font-size: 11px; font-weight: 600; margin: 2px 0 0 0;">Disponível imediatamente para vincular compras</p>
                </div>
            </div>
            <button type="button" onclick="closeQuickClientModal()" style="color: #ffffff; background: rgba(255,255,255,0.15); border: none; border-radius: 10px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        <!-- Form -->
        <form onsubmit="submitQuickClient(event)" style="padding: 20px; display: flex; flex-direction: column; gap: 14px; background-color: #ffffff; margin: 0;">
            <div>
                <label style="display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #1e293b !important; margin-bottom: 4px;">Nome Completo</label>
                <input type="text" id="quick-client-name" placeholder="Ex: Maria Eduarda Silva" style="width: 100%; padding: 8px 12px; font-size: 13px; font-weight: 700; color: #0f172a !important; background-color: #f8fafc !important; border: 1.5px solid #cbd5e1 !important; border-radius: 10px; outline: none; box-sizing: border-box;">
                <p style="color: #64748b !important; font-size: 10.5px; margin: 4px 0 0 0;">Se vazio, usará o @ do Instagram/TikTok.</p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #1e293b !important; margin-bottom: 4px;">
                        <i class="fab fa-instagram" style="color: #e1306c; margin-right: 2px;"></i> Instagram
                    </label>
                    <div style="display: flex;">
                        <span style="display: inline-flex; align-items: center; padding: 0 10px; background-color: #e2e8f0; border: 1.5px solid #cbd5e1; border-right: none; border-radius: 10px 0 0 10px; color: #475569; font-size: 12px; font-weight: 800;">@</span>
                        <input type="text" id="quick-client-instagram" placeholder="usuario" style="width: 100%; padding: 8px 10px; font-size: 13px; font-weight: 700; color: #0f172a !important; background-color: #f8fafc !important; border: 1.5px solid #cbd5e1 !important; border-radius: 0 10px 10px 0; outline: none; box-sizing: border-box;">
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #1e293b !important; margin-bottom: 4px;">
                        <i class="fab fa-tiktok" style="color: #000000; margin-right: 2px;"></i> TikTok
                    </label>
                    <div style="display: flex;">
                        <span style="display: inline-flex; align-items: center; padding: 0 10px; background-color: #e2e8f0; border: 1.5px solid #cbd5e1; border-right: none; border-radius: 10px 0 0 10px; color: #475569; font-size: 12px; font-weight: 800;">@</span>
                        <input type="text" id="quick-client-tiktok" placeholder="usuario" style="width: 100%; padding: 8px 10px; font-size: 13px; font-weight: 700; color: #0f172a !important; background-color: #f8fafc !important; border: 1.5px solid #cbd5e1 !important; border-radius: 0 10px 10px 0; outline: none; box-sizing: border-box;">
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #1e293b !important; margin-bottom: 4px;">
                        <i class="fab fa-whatsapp" style="color: #10b981; margin-right: 2px;"></i> Telefone / Whats
                    </label>
                    <input type="text" id="quick-client-phone" placeholder="(11) 99999-9999" style="width: 100%; padding: 8px 12px; font-size: 13px; font-weight: 700; color: #0f172a !important; background-color: #f8fafc !important; border: 1.5px solid #cbd5e1 !important; border-radius: 10px; outline: none; box-sizing: border-box;">
                </div>

                <div>
                    <label style="display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #1e293b !important; margin-bottom: 4px;">
                        <i class="fas fa-wallet" style="color: #6366f1; margin-right: 2px;"></i> Limite Crédito
                    </label>
                    <div style="display: flex;">
                        <span style="display: inline-flex; align-items: center; padding: 0 10px; background-color: #e2e8f0; border: 1.5px solid #cbd5e1; border-right: none; border-radius: 10px 0 0 10px; color: #475569; font-size: 12px; font-weight: 800;">R$</span>
                        <input type="number" step="0.01" min="0" id="quick-client-limite" value="300.00" style="width: 100%; padding: 8px 10px; font-size: 13px; font-weight: 800; color: #0f172a !important; background-color: #f8fafc !important; border: 1.5px solid #cbd5e1 !important; border-radius: 0 10px 10px 0; outline: none; box-sizing: border-box;">
                    </div>
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding-top: 12px; border-top: 1px solid #e2e8f0; margin-top: 4px;">
                <button type="button" onclick="closeQuickClientModal()" style="background-color: #e2e8f0 !important; color: #334155 !important; font-weight: 800; font-size: 12px; padding: 9px 16px; border-radius: 10px; border: 1px solid #cbd5e1; cursor: pointer;">
                    Cancelar
                </button>
                <button type="submit" id="btn-save-quick-client" style="background-color: #047857 !important; color: #ffffff !important; font-weight: 900; font-size: 12px; padding: 9px 20px; border-radius: 10px; border: 1px solid #065f46; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.15);">
                    <i class="fas fa-check" style="color: #a7f3d0 !important;"></i> Salvar Cliente
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CADASTRO RÁPIDO DE PRODUTO -->
<div id="modal-quick-product" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" style="background-color: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); z-index: 99999;">
    <div class="relative w-full max-w-md rounded-3xl overflow-hidden flex flex-col" style="background-color: #ffffff; border: 2px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #5b21b6 0%, #7c3aed 100%); padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; color: #ffffff; font-size: 18px;">
                    <i class="fas fa-tag"></i>
                </div>
                <div>
                    <h3 style="color: #ffffff !important; font-size: 15px; font-weight: 900; margin: 0; line-height: 1.2;">Cadastrar Nova Peça Vendida</h3>
                    <p style="color: #ddd6fe !important; font-size: 11px; font-weight: 600; margin: 2px 0 0 0;">Gera código sequencial, calcula comissão e vincula à sacola</p>
                </div>
            </div>
            <button type="button" onclick="closeQuickProductModal()" style="color: #ffffff; background: rgba(255,255,255,0.15); border: none; border-radius: 10px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        <!-- Form -->
        <form onsubmit="submitQuickProduct(event)" style="padding: 20px; display: flex; flex-direction: column; gap: 14px; background-color: #ffffff; margin: 0;">
            <div>
                <label style="display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #1e293b !important; margin-bottom: 4px;">
                    Descrição da Peça <span style="color: #dc2626;">*</span>
                </label>
                <input type="text" id="quick-prod-descricao" required placeholder="Ex: Vestido Estampado Farm / Blusa Seda" style="width: 100%; padding: 8px 12px; font-size: 13px; font-weight: 700; color: #0f172a !important; background-color: #f8fafc !important; border: 1.5px solid #cbd5e1 !important; border-radius: 10px; outline: none; box-sizing: border-box;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label style="display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #1e293b !important; margin-bottom: 4px;">Tamanho</label>
                    <input type="text" id="quick-prod-tamanho" placeholder="Ex: M, 38, G, Único" style="width: 100%; padding: 8px 12px; font-size: 13px; font-weight: 700; color: #0f172a !important; background-color: #f8fafc !important; border: 1.5px solid #cbd5e1 !important; border-radius: 10px; outline: none; box-sizing: border-box;">
                </div>

                <div>
                    <label style="display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #1e293b !important; margin-bottom: 4px;">Cor</label>
                    <input type="text" id="quick-prod-cor" placeholder="Ex: Azul, Preto, Estampado" style="width: 100%; padding: 8px 12px; font-size: 13px; font-weight: 700; color: #0f172a !important; background-color: #f8fafc !important; border: 1.5px solid #cbd5e1 !important; border-radius: 10px; outline: none; box-sizing: border-box;">
                </div>
            </div>

            <!-- Valores: Preço, Comissão e Custo -->
            <div style="background-color: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 12px;">
                <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 10px; margin-bottom: 8px;">
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #1e293b !important; margin-bottom: 4px;">
                            Preço de Venda (R$) <span style="color: #dc2626;">*</span>
                        </label>
                        <div style="display: flex;">
                            <span style="display: inline-flex; align-items: center; padding: 0 10px; background-color: #e2e8f0; border: 1.5px solid #cbd5e1; border-right: none; border-radius: 10px 0 0 10px; color: #475569; font-size: 12px; font-weight: 800;">R$</span>
                            <input type="text" id="quick-prod-preco" required oninput="updateQuickProductCostCalc()" placeholder="50,00" style="width: 100%; padding: 8px 10px; font-size: 14px; font-weight: 900; color: #0f172a !important; background-color: #ffffff !important; border: 1.5px solid #cbd5e1 !important; border-radius: 0 10px 10px 0; outline: none; box-sizing: border-box;">
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #1e293b !important; margin-bottom: 4px;">
                            Comissão (%)
                        </label>
                        <div style="display: flex;">
                            <input type="number" step="0.5" id="quick-prod-comissao-perc" value="10" oninput="updateQuickProductCostCalc()" placeholder="10" style="width: 100%; padding: 8px 10px; font-size: 13px; font-weight: 900; color: #0f172a !important; background-color: #ffffff !important; border: 1.5px solid #cbd5e1 !important; border-right: none; border-radius: 10px 0 0 10px; outline: none; box-sizing: border-box;">
                            <span style="display: inline-flex; align-items: center; padding: 0 10px; background-color: #e2e8f0; border: 1.5px solid #cbd5e1; border-left: none; border-radius: 0 10px 10px 0; color: #475569; font-size: 12px; font-weight: 800;">%</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #475569 !important; margin-bottom: 4px;">
                        Custo / Repasse da Loja (R$)
                    </label>
                    <div style="display: flex;">
                        <span style="display: inline-flex; align-items: center; padding: 0 10px; background-color: #e2e8f0; border: 1.5px solid #cbd5e1; border-right: none; border-radius: 10px 0 0 10px; color: #475569; font-size: 12px; font-weight: 800;">R$</span>
                        <input type="text" id="quick-prod-custo" placeholder="45,00" style="width: 100%; padding: 8px 10px; font-size: 13px; font-weight: 800; color: #b45309 !important; background-color: #ffffff !important; border: 1.5px solid #cbd5e1 !important; border-radius: 0 10px 10px 0; outline: none; box-sizing: border-box;">
                    </div>
                </div>

                <!-- Resumo Dinâmico do Repasse -->
                <div id="quick-prod-calc-summary" style="margin-top: 8px; padding: 6px 10px; background-color: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 8px; font-size: 11px; font-weight: 800; color: #5b21b6; display: flex; justify-content: space-between;">
                    <span>Repasse Loja: <strong id="quick-prod-calc-repasse" style="color: #b45309;">R$ 0,00</strong></span>
                    <span>Comissão: <strong id="quick-prod-calc-comissao" style="color: #047857;">R$ 0,00 (10%)</strong></span>
                </div>
            </div>

            @if($activeLive)
                <label style="display: flex; align-items: center; gap: 8px; padding: 10px 12px; background-color: #f5f3ff; border: 1.5px solid #ddd6fe; border-radius: 10px; cursor: pointer;">
                    <input type="checkbox" id="quick-prod-link-live" checked style="width: 16px; height: 16px; accent-color: #7c3aed; cursor: pointer;">
                    <span style="font-size: 12px; font-weight: 800; color: #4c1d95 !important;">
                        Vincular à Live #{{ $activeLive->id }} (Gera Código Sequencial da Live)
                    </span>
                </label>
            @endif

            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding-top: 12px; border-top: 1px solid #e2e8f0; margin-top: 4px;">
                <button type="button" onclick="closeQuickProductModal()" style="background-color: #e2e8f0 !important; color: #334155 !important; font-weight: 800; font-size: 12px; padding: 9px 16px; border-radius: 10px; border: 1px solid #cbd5e1; cursor: pointer;">
                    Cancelar
                </button>
                <button type="submit" id="btn-save-quick-prod" style="background-color: #6d28d9 !important; color: #ffffff !important; font-weight: 900; font-size: 12px; padding: 9px 20px; border-radius: 10px; border: 1px solid #5b21b6; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.15);">
                    <i class="fas fa-check-circle" style="color: #ddd6fe !important;"></i> Salvar e Adicionar à Sacola
                </button>
            </div>
        </form>
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

    document.addEventListener("DOMContentLoaded", function() {
        if (liveId) {
            // Iniciar Polling de dados (a cada 3 segundos)
            fetchChatData();
            pollingInterval = setInterval(fetchChatData, 3000);
        }
    });

    function getBackendUrl(port, path) {
        const isLocal = window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1";
        if (isLocal) {
            return `http://localhost:${port}${path}`;
        }
        const prefix = port === 3001 ? "/tiktok-api" : "/insta-api";
        return `${prefix}${path}`;
    }
    // Controle Simplificado de Gravação (Social Stream Webhooks)
    function copyWebhookUrl() {
        const input = document.getElementById("webhook-url-input");
        input.select();
        input.setSelectionRange(0, 99999);
        document.execCommand("copy");
        showToast("URL do Webhook copiada para a área de transferência!");
    }

    function updateInstagramState(isRecording) {
        const badge = document.getElementById("insta-badge");
        const dot = document.getElementById("insta-status-dot");
        const text = document.getElementById("insta-status-text");
        const btn = document.getElementById("insta-toggle-btn");
        if (!badge || !btn) return;

        if (isRecording) {
            badge.className = "bg-green-100 text-green-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 border border-green-300 transition-all";
            if (dot) {
                dot.className = "w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse";
                dot.classList.remove("hidden");
            }
            if (text) text.textContent = "Gravando";
            btn.className = "w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded-lg text-xs transition duration-150 shadow-sm flex items-center justify-center gap-1.5";
            btn.innerHTML = `<i class="fas fa-stop"></i> Parar Gravação Instagram`;
        } else {
            badge.className = "bg-purple-200 text-purple-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 transition-all";
            if (dot) dot.classList.add("hidden");
            if (text) text.textContent = "Inativo";
            btn.className = "w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 rounded-lg text-xs transition duration-150 shadow-sm flex items-center justify-center gap-1.5";
            btn.innerHTML = `<i class="fas fa-play"></i> Iniciar Gravação Instagram`;
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
            if (data.success) {
                updateInstagramState(data.insta_active);
                showToast(data.insta_active ? "Gravação do Instagram Iniciada!" : "Gravação do Instagram Parada!");
            }
        } catch (e) {
            console.error("Erro ao alternar captura do Instagram:", e);
        }
    }

    function updateTikTokState(isRecording) {
        const badge = document.getElementById("tiktok-backend-badge");
        const dot = document.getElementById("tiktok-status-dot");
        const text = document.getElementById("tiktok-status-text");
        const btn = document.getElementById("tiktok-toggle-btn");
        if (!badge || !btn) return;

        if (isRecording) {
            badge.className = "bg-green-100 text-green-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 border border-green-300 transition-all";
            if (dot) {
                dot.className = "w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse";
                dot.classList.remove("hidden");
            }
            if (text) text.textContent = "Gravando";
            btn.className = "w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded-lg text-xs transition duration-150 shadow-sm flex items-center justify-center gap-1.5";
            btn.innerHTML = `<i class="fas fa-stop"></i> Parar Gravação TikTok`;
        } else {
            badge.className = "bg-gray-200 text-gray-700 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 transition-all";
            if (dot) {
                dot.className = "w-1.5 h-1.5 rounded-full bg-gray-500";
                dot.classList.remove("hidden");
            }
            if (text) text.textContent = "Inativo";
            btn.className = "w-full bg-pink-600 hover:bg-pink-700 text-white font-bold py-2 rounded-lg text-xs transition duration-150 shadow-sm flex items-center justify-center gap-1.5";
            btn.innerHTML = `<i class="fas fa-play"></i> Iniciar Gravação TikTok`;
        }
    }

    async function toggleTikTokBackend() {
        const btn = document.getElementById("tiktok-toggle-btn");
        const isCurrentlyActive = btn && btn.textContent.includes("Parar");
        const action = isCurrentlyActive ? "stop" : "start";

        try {
            // Reaproveitamos o mesmo estilo de endpoint do Instagram
            const res = await fetch("/admin/live-chat/toggle-tiktok", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
                },
                body: JSON.stringify({ action: action })
            });
            const data = await res.json();
            if (data.success) {
                updateTikTokState(data.tiktok_active);
                showToast(data.tiktok_active ? "Gravação do TikTok Iniciada!" : "Gravação do TikTok Parada!");
                // Notificar listener local instantaneamente
                fetch("http://127.0.0.1:3002/check-now", { mode: "cors" }).catch(() => {});
            }
        } catch (e) {
            console.error("Erro ao alternar captura do TikTok:", e);
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

    // Estado global do chat
    let allLiveMessages = [];
    let onlineUsersMap = {};
    let onlineUsersByUsername = {};
    let chatFilterMode = 'all'; // 'all' | 'marked'
    let chatFilterUser = null;  // null | string
    let modalFilterMarkedOnly = false;
    let chatAutoScrollEnabled = localStorage.getItem('live_chat_dashboard_autoscroll') !== 'false';

    function updateChatAutoScrollUI() {
        const btn = document.getElementById("dashboard-autoscroll-btn");
        const icon = document.getElementById("dashboard-autoscroll-icon");
        const text = document.getElementById("dashboard-autoscroll-text");
        if (!btn) return;

        if (chatAutoScrollEnabled) {
            btn.className = "px-2 py-1 rounded-lg font-bold text-[11px] bg-emerald-950 text-emerald-300 hover:bg-emerald-900 transition flex items-center gap-1 border border-emerald-700 cursor-pointer shadow-sm";
            if (icon) icon.className = "fas fa-arrow-down text-[10px] text-emerald-400";
            if (text) text.textContent = "Auto";
        } else {
            btn.className = "px-2 py-1 rounded-lg font-bold text-[11px] bg-amber-950 text-amber-300 hover:bg-amber-900 transition flex items-center gap-1 border border-amber-700 cursor-pointer shadow-sm";
            if (icon) icon.className = "fas fa-pause text-[10px] text-amber-400";
            if (text) text.textContent = "Pausado";
        }
    }

    function toggleChatAutoScroll() {
        chatAutoScrollEnabled = !chatAutoScrollEnabled;
        localStorage.setItem('live_chat_dashboard_autoscroll', chatAutoScrollEnabled ? 'true' : 'false');
        updateChatAutoScrollUI();
        if (chatAutoScrollEnabled) {
            const container = document.getElementById("chat-messages-container");
            if (container) container.scrollTop = container.scrollHeight;
        }
    }

    // Inicializa estado visual da rolagem
    setTimeout(updateChatAutoScrollUI, 50);

    // Buscar dados do chat da live via AJAX
    let isFetchingChatData = false;
    function fetchChatData() {
        if (!liveId || isFetchingChatData) return;
        isFetchingChatData = true;
        
        fetch(`/admin/lives/${liveId}/chat-data`)
            .then(res => res.json())
            .then(data => {
                isFetchingChatData = false;
                if (data.success) {
                    if (typeof updatePauseState === 'function') updatePauseState(data.is_paused);
                    updateInstagramState(data.insta_active);
                    updateTikTokState(data.tiktok_active);

                    allLiveMessages = data.messages || [];

                    // Atualiza contador de mensagens marcadas
                    const markedCount = (data.stats && data.stats.total_marked !== undefined) ? data.stats.total_marked : allLiveMessages.filter(m => m.is_marked).length;
                    const markedEl = document.getElementById("chat-marked-count");
                    if (markedEl) markedEl.textContent = markedCount;

                    // Mapear usuários online
                    onlineUsersMap = {};
                    onlineUsersByUsername = {};
                    (data.online_users || []).forEach(u => {
                        if (u.user_id) onlineUsersMap[u.user_id] = u;
                        if (u.username) onlineUsersByUsername[u.username.toLowerCase()] = u;
                    });

                    renderChatMessages();
                    renderOnlineUsers(data.online_users);
                    renderCodeRequests(data.code_requests);

                    // Se o modal de bipe estiver aberto para um cliente, atualiza as mensagens dele
                    if (currentOnlineQrUser && !document.getElementById("online-qr-modal").classList.contains("hidden")) {
                        renderModalClientMessages(currentOnlineQrUser.username);
                    }
                }
            })
            .catch(err => {
                isFetchingChatData = false;
                console.error("Erro no polling da live:", err);
            });
    }

    // Filtros de Chat
    function setChatFilter(mode) {
        chatFilterMode = mode;
        const btnAll = document.getElementById("chat-filter-all-btn");
        const btnMarked = document.getElementById("chat-filter-marked-btn");

        if (mode === 'all') {
            btnAll.className = "px-2.5 py-1 rounded-lg font-bold text-xs bg-indigo-600 text-white transition shadow-sm cursor-pointer";
            btnMarked.className = "px-2.5 py-1 rounded-lg font-bold text-xs bg-gray-800 text-yellow-400 hover:bg-gray-700 transition flex items-center gap-1 border border-gray-700 cursor-pointer";
        } else {
            btnMarked.className = "px-2.5 py-1 rounded-lg font-bold text-xs bg-yellow-500 text-gray-900 transition shadow-sm flex items-center gap-1 cursor-pointer";
            btnAll.className = "px-2.5 py-1 rounded-lg font-bold text-xs bg-gray-800 text-gray-300 hover:bg-gray-700 transition border border-gray-700 cursor-pointer";
        }
        renderChatMessages();
    }

    function filterChatByUser(username) {
        chatFilterUser = username.replace(/^@/, '');
        const badge = document.getElementById("chat-user-filter-badge");
        const label = document.getElementById("chat-filtered-username");
        if (badge && label) {
            label.textContent = '@' + chatFilterUser;
            badge.classList.remove("hidden");
            badge.classList.add("flex");
        }
        renderChatMessages();
    }

    function clearUserChatFilter() {
        chatFilterUser = null;
        const badge = document.getElementById("chat-user-filter-badge");
        if (badge) {
            badge.classList.add("hidden");
            badge.classList.remove("flex");
        }
        renderChatMessages();
    }

    // Alternar estrela (marcar/desmarcar) da mensagem
    async function toggleMarkLiveMessage(messageId) {
        try {
            const res = await fetch('/admin/live-chat/toggle-mark-message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
                },
                body: JSON.stringify({ message_id: messageId })
            });
            const data = await res.json();
            if (data.success) {
                const target = allLiveMessages.find(m => m.id == messageId);
                if (target) {
                    target.is_marked = data.is_marked;
                }
                const markedCount = allLiveMessages.filter(m => m.is_marked).length;
                const markedEl = document.getElementById("chat-marked-count");
                if (markedEl) markedEl.textContent = markedCount;

                renderChatMessages();
                if (currentOnlineQrUser) {
                    renderModalClientMessages(currentOnlineQrUser.username);
                }
            }
        } catch (e) {
            console.error("Erro ao marcar mensagem:", e);
        }
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

    let lastRenderedChatHash = "";

    // Helper para exibir badge visual da rede/canal de transmissão (Minha Mania vs Loja Parceira)
    function getHostBadge(hostAccount) {
        if (!hostAccount) return '';
        const clean = hostAccount.toLowerCase().replace(/^@/, '');
        if (clean === 'minhamania' || clean === '_minhamania' || clean === 'de_minha_mania') {
            return `<span class="bg-purple-950/80 text-purple-300 border border-purple-600/70 text-[8.5px] font-black px-1.5 py-0.5 rounded shadow-xs shrink-0" title="Comentário via Rede Minha Mania">🟣 Minha Mania</span>`;
        }
        return `<span class="bg-amber-950/90 text-amber-200 border border-amber-500 text-[8.5px] font-black px-1.5 py-0.5 rounded shadow-xs shrink-0 flex items-center gap-1" title="Comentário via Rede da Loja Parceira (@${escapeHtml(clean)})"><i class="fas fa-store text-[7.5px] text-amber-400"></i> @${escapeHtml(clean)}</span>`;
    }

    // Renderizar mensagens de chat no terminal
    function renderChatMessages() {
        const container = document.getElementById("chat-messages-container");
        if (!container) return;

        let messages = allLiveMessages;

        if (chatFilterMode === 'marked') {
            messages = messages.filter(m => m.is_marked);
        }
        if (chatFilterUser) {
            const lowerUser = chatFilterUser.toLowerCase();
            messages = messages.filter(m => m.username && m.username.toLowerCase() === lowerUser);
        }

        const currentHash = `${chatFilterMode}:${chatFilterUser || ''}:${messages.length}:${messages.length > 0 ? messages[messages.length - 1].id : 0}:${messages.filter(m => m.is_marked).length}`;
        if (currentHash === lastRenderedChatHash && container.innerHTML.trim().length > 50) {
            return; // Nada mudou, não reconstrói o DOM para manter a página super rápida
        }
        lastRenderedChatHash = currentHash;

        if (messages.length === 0) {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full text-gray-500 py-10">
                    <i class="fas ${chatFilterMode === 'marked' ? 'fa-star text-yellow-500/40' : 'fa-comment-slash'} text-3xl mb-2"></i>
                    <p class="text-xs text-center">${chatFilterMode === 'marked' ? 'Nenhuma mensagem marcada encontrada.' : (chatFilterUser ? 'Nenhuma mensagem de @' + escapeHtml(chatFilterUser) : 'Aguardando mensagens do chat...')}</p>
                </div>
            `;
            return;
        }

        let html = '';
        messages.forEach(msg => {
            const isTikTok = msg.plataforma === 'tiktok';
            const icon = isTikTok ? '<i class="fab fa-tiktok text-pink-500"></i>' : '<i class="fab fa-instagram text-purple-500"></i>';
            const time = new Date(msg.created_at).toLocaleTimeString();
            const cleanUser = msg.username || 'usuario';
            const initials = cleanUser.slice(0, 2).toUpperCase();
            const illustratedAvatar = `https://api.dicebear.com/7.x/lorelei/svg?seed=${encodeURIComponent(cleanUser)}&backgroundColor=b6e3f4,c0aede,d1d4f9,ffd5dc,ffdfbf`;
            const avatarHtml = msg.avatar_url
                ? `<img src="${safeAttr(msg.avatar_url)}" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src='${illustratedAvatar}';" class="w-6 h-6 rounded-full object-cover shrink-0" />`
                : `<img src="${illustratedAvatar}" referrerpolicy="no-referrer" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" class="w-6 h-6 rounded-full object-cover shrink-0" /><div class="w-6 h-6 rounded-full bg-indigo-900 items-center justify-center font-bold text-[9px] text-indigo-300 shrink-0 hidden">${initials}</div>`;
            
            const isMarked = !!msg.is_marked;
            const starClass = isMarked ? 'fas fa-star text-yellow-400' : 'far fa-star text-gray-600 hover:text-yellow-400';
            const bgClass = isMarked ? 'bg-yellow-950/30 border-l-2 border-yellow-400' : 'hover:bg-gray-800/50';

            const userReg = onlineUsersByUsername[msg.username.toLowerCase()];
            const qrBtn = (userReg && userReg.user_id) ? `
                <button type="button" onclick="selectOnlineParticipant('${userReg.user_id}', '${escapeHtml(msg.username)}', '${escapeHtml(userReg.user_name || '')}', '${escapeHtml(msg.plataforma)}', '${safeAttr(msg.avatar_url || '')}')" title="Selecionar para bipar com leitor" class="text-gray-400 hover:text-emerald-400 p-1 transition cursor-pointer text-xs">
                    <i class="fas fa-barcode"></i>
                </button>
            ` : '';

            const hostBadgeHtml = getHostBadge(msg.host_account);

            html += `
                <div class="${bgClass} p-1.5 rounded transition duration-150 relative group">
                    <div class="flex items-center justify-between mb-0.5 gap-1.5">
                        <div class="flex items-center gap-1.5 min-w-0 flex-wrap">
                            ${avatarHtml}
                            ${hostBadgeHtml}
                            <button type="button" onclick="filterChatByUser('${escapeHtml(msg.username)}')" title="Filtrar chat por @${escapeHtml(msg.username)}" class="font-bold text-xs text-indigo-400 hover:text-indigo-300 flex items-center gap-1 truncate text-left cursor-pointer">
                                ${icon} @${escapeHtml(msg.username)}
                            </button>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            ${qrBtn}
                            <button type="button" onclick="toggleMarkLiveMessage(${msg.id})" title="${isMarked ? 'Desmarcar' : 'Marcar'}" class="p-1 transition cursor-pointer">
                                <i class="${starClass}"></i>
                            </button>
                            <span class="text-[9px] text-gray-600 font-mono">${time}</span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-200 leading-normal pl-7">${escapeHtml(msg.message)}</p>
                </div>
            `;
        });

        const shouldScroll = chatAutoScrollEnabled && (container.scrollTop + container.clientHeight >= container.scrollHeight - 120);
        container.innerHTML = html;
        if (shouldScroll) {
            container.scrollTop = container.scrollHeight;
        }
    }

    let lastFetchedOnlineUsers = [];

    // Renderizar usuários online
    function renderOnlineUsers(users) {
        lastFetchedOnlineUsers = users || [];
        const list = document.getElementById("online-users-list");
        const count = document.getElementById("online-users-count");
        if (count) count.textContent = (users || []).length;

        if (!list) return;

        if (!users || users.length === 0) {
            list.innerHTML = `<p class="text-xs text-gray-400 text-center py-6">Nenhum participante detectado ainda.</p>`;
            return;
        }

        let html = '';
        users.forEach(u => {
            const isTikTok = u.plataforma === 'tiktok';
            const icon = isTikTok ? '<i class="fab fa-tiktok text-pink-500 text-xs"></i>' : '<i class="fab fa-instagram text-purple-500 text-xs"></i>';
            const initials = u.username.slice(0,2).toUpperCase();
            const cleanOnlineUser = u.username || 'usuario';
            const illustratedOnlineAvatar = `https://api.dicebear.com/7.x/lorelei/svg?seed=${encodeURIComponent(cleanOnlineUser)}&backgroundColor=b6e3f4,c0aede,d1d4f9,ffd5dc,ffdfbf`;

            // Avatar: foto de perfil se disponível, fallback para persona ilustrada e iniciais
            const avatarHtml = u.avatar_url
                ? `<img src="${safeAttr(u.avatar_url)}" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src='${illustratedOnlineAvatar}';" class="w-9 h-9 rounded-full object-cover border-2 border-white shadow-sm shrink-0" />`
                : `<img src="${illustratedOnlineAvatar}" referrerpolicy="no-referrer" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" class="w-9 h-9 rounded-full object-cover border-2 border-white shadow-sm shrink-0" /><div class="w-9 h-9 rounded-full bg-indigo-100 items-center justify-center font-bold text-xs text-indigo-700 hidden shrink-0">${initials}</div>`;
            
            let displayName = escapeHtml(u.user_name || '');
            if (u.user_apelido) {
                displayName = displayName ? `${displayName} (${escapeHtml(u.user_apelido)})` : escapeHtml(u.user_apelido);
            }

            let subtitle = '';
            if (u.user_id) {
                subtitle = displayName ? `<span class="text-[8.5px] font-normal text-emerald-700 flex items-center gap-1 mt-0.5 truncate"><i class="fas fa-user text-[7.5px]"></i> ${displayName}</span>` : '';
            } else {
                subtitle = `<span class="text-[9.5px] text-gray-400">Visto às ${u.last_seen}</span>`;
            }

            const clientName = escapeHtml(u.user_name || u.user_apelido || '');
            const isSelected = selectedParticipant && String(selectedParticipant.userId) === String(u.user_id);
            const cardClass = isSelected
                ? 'bg-emerald-50/90 border-2 border-emerald-500 ring-2 ring-emerald-300/60 shadow-md'
                : 'bg-gray-50 border border-gray-150 hover:bg-indigo-50/70 hover:border-indigo-300 shadow-xs';

            const isPartnerAccount = u.host_account && !['minhamania', '_minhamania', 'de_minha_mania'].includes(u.host_account.toLowerCase().replace(/^@/, ''));
            const partnerBadge = isPartnerAccount
                ? `<span class="bg-amber-100 text-amber-900 border border-amber-300 text-[8px] font-black px-1.5 py-0.5 rounded-md shrink-0 flex items-center gap-0.5" title="Audiência da Loja Parceira (@${escapeHtml(u.host_account)})"><i class="fas fa-store text-[7px] text-amber-700"></i> @${escapeHtml(u.host_account)}</span>`
                : (u.host_account ? `<span class="bg-purple-100 text-purple-900 border border-purple-200 text-[8px] font-black px-1.5 py-0.5 rounded-md shrink-0" title="Audiência Minha Mania">Minha Mania</span>` : '');

            html += `
                <div ${u.user_id ? `onclick="selectOnlineParticipant('${u.user_id}', '${escapeHtml(u.username)}', '${clientName}', '${escapeHtml(u.plataforma)}', '${safeAttr(u.avatar_url || '')}')"` : `onclick="openLinkModal('${escapeHtml(u.username)}', '${escapeHtml(u.plataforma)}')"`} 
                     class="flex items-center justify-between p-2.5 rounded-xl ${cardClass} cursor-pointer transition duration-150">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="shrink-0 relative">
                            ${avatarHtml}
                            <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full bg-white flex items-center justify-center text-[7px] shadow">${icon}</span>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-gray-900 truncate">
                                <span>@${escapeHtml(u.username)}</span>
                                ${partnerBadge}
                            </div>
                            ${subtitle}
                        </div>
                    </div>
                    <div class="shrink-0">
                        ${
                            u.user_id ? 
                            (isSelected ? 
                                `<button type="button" onclick="event.stopPropagation(); clearSelectedParticipant()" class="font-bold px-2.5 py-1.5 rounded-xl text-xs transition duration-150 shadow-sm flex items-center gap-1.5 border border-emerald-700" style="background-color: #059669 !important; color: #ffffff !important;"><i class="fas fa-check" style="color: #ffffff !important;"></i> <span style="color: #ffffff !important;">Ativa</span></button>`
                                :
                                `<button type="button" onclick="event.stopPropagation(); selectOnlineParticipant('${u.user_id}', '${escapeHtml(u.username)}', '${clientName}', '${escapeHtml(u.plataforma)}', '${safeAttr(u.avatar_url || '')}')" class="font-bold px-2.5 py-1.5 rounded-xl text-xs transition duration-150 shadow-sm flex items-center gap-1 border border-indigo-700" style="background-color: #4f46e5 !important; color: #ffffff !important;"><i class="fas fa-hand-pointer" style="color: #ffffff !important;"></i> <span style="color: #ffffff !important;">Selecionar</span></button>`
                            )
                            :
                            `<button type="button" onclick="event.stopPropagation(); openLinkModal('${escapeHtml(u.username)}', '${escapeHtml(u.plataforma)}')" class="font-bold px-2.5 py-1 rounded-xl text-[11px] transition duration-150 shadow-sm flex items-center gap-1 border border-amber-600" style="background-color: #d97706 !important; color: #ffffff !important;"><i class="fas fa-link" style="color: #ffffff !important;"></i> <span style="color: #ffffff !important;">Vincular</span></button>`
                        }
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

        fetch(`/api/users/search?q=${encodeURIComponent(query)}`)
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

    let currentAvulsoFocus = -1;

    // Busca de Clientes Avulso (na aba Pessoas Online)
    function searchAvulsoClients(query) {
        const resultsContainer = document.getElementById("avulso-search-results");
        if (query.trim().length < 2) {
            resultsContainer.classList.add("hidden");
            return;
        }

        resultsContainer.classList.remove("hidden");
        resultsContainer.innerHTML = `<p class="text-xs text-gray-400 text-center py-4"><i class="fas fa-spinner fa-spin mr-1"></i> Buscando...</p>`;

        fetch(`/api/users/search?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    currentAvulsoFocus = -1; // Reset focus na nova busca
                    let html = '';
                    data.data.forEach(user => {
                        const identifier = user.instagram || user.tiktok || user.whatsapp || user.id;
                        html += `
                            <div onclick="selectAvulsoClient('${user.id}', '${escapeHtml(user.name)}', '${escapeHtml(user.instagram || user.tiktok || '')}')" class="avulso-search-item p-3 border-b border-gray-100 bg-white hover:bg-indigo-50 cursor-pointer transition flex justify-between items-center group">
                                <div>
                                    <h4 class="font-bold text-sm text-gray-800 group-hover:text-indigo-700">${escapeHtml(user.name)}</h4>
                                    <div class="text-[11px] text-gray-500 mt-1 flex flex-wrap gap-2">
                                        ${user.instagram ? `<span class="text-pink-600"><i class="fab fa-instagram"></i> @${escapeHtml(user.instagram)}</span>` : ''}
                                        ${user.tiktok ? `<span class="text-black"><i class="fab fa-tiktok"></i> @${escapeHtml(user.tiktok)}</span>` : ''}
                                        ${user.whatsapp ? `<span class="text-green-600"><i class="fab fa-whatsapp"></i> ${escapeHtml(user.whatsapp)}</span>` : ''}
                                        ${user.apelido ? `<span class="text-indigo-600"><i class="fas fa-tag"></i> ${escapeHtml(user.apelido)}</span>` : ''}
                                    </div>
                                </div>
                                <button type="button" class="bg-indigo-100 text-indigo-700 w-8 h-8 rounded-lg opacity-0 group-hover:opacity-100 transition shadow-sm flex items-center justify-center shrink-0">
                                    <i class="fas fa-camera text-sm"></i>
                                </button>
                            </div>
                        `;
                    });
                    resultsContainer.innerHTML = html;
                } else {
                    resultsContainer.innerHTML = `<p class="text-xs text-gray-400 text-center py-4">Nenhum cliente cadastrado encontrado.</p>`;
                }
            })
            .catch(err => {
                resultsContainer.innerHTML = `<p class="text-xs text-red-400 text-center py-4">Erro na busca de clientes.</p>`;
                console.error("Erro na busca avulsa:", err);
            });
    }

    function selectAvulsoClient(id, name, usernameFallback) {
        const resultsContainer = document.getElementById("avulso-search-results");
        const input = document.getElementById("avulso-search-input");
        if (resultsContainer) resultsContainer.classList.add("hidden");
        if (input) input.value = "";
        
        // Seleciona o cliente para bipagem direta com o leitor
        selectOnlineParticipant(id, usernameFallback || name, name, 'instagram', '');
    }
    
    // Esconder resultados ao clicar fora
    document.addEventListener('click', function(e) {
        const results = document.getElementById("avulso-search-results");
        const input = document.getElementById("avulso-search-input");
        if (results && input && !results.contains(e.target) && e.target !== input) {
            results.classList.add("hidden");
        }
    });

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
                // Seleciona automaticamente para bipagem com leitor
                selectOnlineParticipant(userId, username, username, platform, '');
            } else {
                alert("Erro ao vincular: " + data.message);
            }
        })
        .catch(err => console.error("Erro ao vincular usuário:", err));
    }

    // ==========================================
    // GERENCIAMENTO DE CLIENTE SELECIONADA & LEITOR / LIVE EXTERNA
    // ==========================================
    let selectedParticipant = null; // { userId, username, clientName, platform, avatarUrl }
    let liveOperationMode = localStorage.getItem('live_operation_mode') || 'external'; // 'external' (padrão) ou 'stock'
    let barcodeDebounceTimer = null;
    let isDashboardBarcodeProcessing = false;
    let lastDashboardBarcode = "";
    let lastDashboardBarcodeTime = 0;
    let globalBarcodeBuffer = "";
    let globalBarcodeLastKeyTime = 0;

    function setLiveOperationMode(mode) {
        liveOperationMode = mode;
        localStorage.setItem('live_operation_mode', mode);
        updateSelectedParticipantUI();
        setTimeout(() => {
            const input = document.getElementById("dashboard-barcode-input");
            if (input) {
                input.focus();
                input.select();
            }
        }, 80);
    }

    function selectOnlineParticipant(userId, username, clientName, platform, avatarUrl) {
        if (!userId || userId === 'null' || userId === 'undefined') {
            openLinkModal(username, platform || 'instagram');
            return;
        }

        selectedParticipant = {
            userId: userId,
            username: username || '',
            clientName: clientName || username || '',
            platform: platform || 'instagram',
            avatarUrl: avatarUrl || ''
        };

        updateSelectedParticipantUI();
        renderOnlineUsers(lastFetchedOnlineUsers);
        
        // Garante que a aba de pessoas online esteja visível
        switchTab('online');

        // Foca automaticamente no campo de bipe / cadastro
        setTimeout(() => {
            const input = document.getElementById("dashboard-barcode-input");
            if (input) {
                input.focus();
                input.select();
            }
        }, 100);
    }

    function clearSelectedParticipant() {
        selectedParticipant = null;
        updateSelectedParticipantUI();
        renderOnlineUsers(lastFetchedOnlineUsers);
    }

    function updateSelectedParticipantUI() {
        const card = document.getElementById("selected-participant-card");
        if (!card) return;

        if (!selectedParticipant) {
            const isExternal = liveOperationMode === 'external';
            card.className = "bg-gray-50 border-2 border-dashed border-gray-200 rounded-2xl p-2.5 mb-3 shrink-0 transition-all";
            card.innerHTML = `
                <div class="flex items-center justify-between gap-1 mb-2 bg-gray-200/70 p-1 rounded-xl border border-gray-300/60">
                    <button type="button" onclick="setLiveOperationMode('external')" class="flex-1 py-1 px-2 rounded-lg text-[10px] font-black transition flex items-center justify-center gap-1.5 cursor-pointer ${isExternal ? 'bg-purple-700 text-white shadow-xs' : 'text-purple-950 hover:bg-purple-200/50'}">
                        <i class="fas fa-tag"></i> Modo Live Externa
                    </button>
                    <button type="button" onclick="setLiveOperationMode('stock')" class="flex-1 py-1 px-2 rounded-lg text-[10px] font-black transition flex items-center justify-center gap-1.5 cursor-pointer ${!isExternal ? 'bg-indigo-700 text-white shadow-xs' : 'text-indigo-950 hover:bg-indigo-200/50'}">
                        <i class="fas fa-barcode"></i> Modo Estoque (Leitor)
                    </button>
                </div>
                <div class="flex items-center justify-center gap-2 text-gray-500 text-xs py-1 font-bold">
                    <i class="fas fa-hand-pointer text-indigo-500"></i>
                    <span>Clique em uma cliente na lista abaixo para selecioná-la e registrar vendas</span>
                </div>
            `;
            return;
        }

        const isTikTok = selectedParticipant.platform === 'tiktok';
        const icon = isTikTok ? '<i class="fab fa-tiktok text-pink-500 text-xs"></i>' : '<i class="fab fa-instagram text-purple-500 text-xs"></i>';
        const cleanOnlineUser = selectedParticipant.username || 'usuario';
        const initials = cleanOnlineUser.slice(0, 2).toUpperCase();
        const illustratedAvatar = `https://api.dicebear.com/7.x/lorelei/svg?seed=${encodeURIComponent(cleanOnlineUser)}&backgroundColor=b6e3f4,c0aede,d1d4f9,ffd5dc,ffdfbf`;
        const avatarHtml = selectedParticipant.avatarUrl
            ? `<img src="${safeAttr(selectedParticipant.avatarUrl)}" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src='${illustratedAvatar}';" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm shrink-0" />`
            : `<img src="${illustratedAvatar}" referrerpolicy="no-referrer" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm shrink-0" /><div class="w-10 h-10 rounded-full bg-indigo-100 items-center justify-center font-bold text-xs text-indigo-700 hidden shrink-0">${initials}</div>`;

        const isExternal = liveOperationMode === 'external';

        card.className = "bg-gradient-to-br from-emerald-50 via-teal-50 to-emerald-50/70 border-2 border-emerald-500 ring-2 ring-emerald-300/30 rounded-2xl p-3.5 mb-3 shrink-0 shadow-md transition-all";
        card.innerHTML = `
            <div class="flex items-center justify-between gap-2 mb-2 pb-2 border-b border-emerald-200/70">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="shrink-0 relative">
                        ${avatarHtml}
                        <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-white flex items-center justify-center text-[8px] shadow">${icon}</span>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5">
                            <span class="bg-emerald-700 text-white font-extrabold text-[9px] px-1.5 py-0.5 rounded uppercase tracking-wider shadow-xs">
                                <i class="fas fa-check-circle mr-0.5"></i> Cliente Ativa
                            </span>
                        </div>
                        <div class="text-xs font-black text-gray-900 truncate mt-0.5">
                            @${escapeHtml(selectedParticipant.username)}
                        </div>
                        ${selectedParticipant.clientName && selectedParticipant.clientName !== selectedParticipant.username ? `<div class="text-[11px] text-emerald-900 font-bold truncate">${escapeHtml(selectedParticipant.clientName)}</div>` : ''}
                    </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="button" onclick="openQuickProductModal()" title="Cadastrar Peça Rápida [Enter]" class="bg-purple-700 hover:bg-purple-800 text-white px-2.5 py-1.5 rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-plus-circle"></i> <span class="text-[11px] font-extrabold">+ Cadastrar Peça</span>
                    </button>
                    <button type="button" onclick="openOnlineQrModal('${selectedParticipant.userId}', '${escapeHtml(selectedParticipant.username)}', '${escapeHtml(selectedParticipant.clientName)}')" title="Abrir Câmera / QRCode" class="bg-white hover:bg-gray-100 text-emerald-700 border border-emerald-300 p-2 rounded-xl text-xs transition shadow-xs cursor-pointer">
                        <i class="fas fa-camera"></i>
                    </button>
                    <button type="button" onclick="clearSelectedParticipant()" title="Desmarcar cliente" class="bg-white hover:bg-red-50 text-gray-600 hover:text-red-700 border border-gray-300 hover:border-red-300 p-2 rounded-xl text-xs transition shadow-xs cursor-pointer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Seletor de Modo de Operação (Live Externa vs Estoque com Leitor) -->
            <div class="flex items-center justify-between gap-1 mb-2 bg-emerald-100/80 p-1 rounded-xl border border-emerald-200">
                <button type="button" onclick="setLiveOperationMode('external')" class="flex-1 py-1 px-2 rounded-lg text-[10px] font-black transition flex items-center justify-center gap-1.5 cursor-pointer ${isExternal ? 'bg-purple-700 text-white shadow-xs' : 'text-purple-950 hover:bg-purple-200/50'}">
                    <i class="fas fa-tag"></i> Modo Live Externa
                </button>
                <button type="button" onclick="setLiveOperationMode('stock')" class="flex-1 py-1 px-2 rounded-lg text-[10px] font-black transition flex items-center justify-center gap-1.5 cursor-pointer ${!isExternal ? 'bg-indigo-700 text-white shadow-xs' : 'text-indigo-950 hover:bg-indigo-200/50'}">
                    <i class="fas fa-barcode"></i> Modo Estoque (Leitor)
                </button>
            </div>

            <div class="space-y-2">
                <div class="relative">
                    <input type="text" id="dashboard-barcode-input" 
                           placeholder="${isExternal ? 'Tecle [Enter] p/ abrir cadastro ou digite o valor/descrição...' : 'Bipe com o leitor ou digite o código de barras + [Enter]...'}" 
                           oninput="handleDashboardBarcodeInput(event)"
                           onkeydown="handleDashboardBarcodeKeyDown(event)"
                           class="w-full pl-9 pr-10 py-2.5 bg-white rounded-xl border-2 border-emerald-500 focus:border-purple-600 focus:ring-4 focus:ring-purple-500/20 text-xs font-black text-gray-900 placeholder-gray-500 shadow-sm transition" 
                           autocomplete="off" />
                    <i class="fas ${isExternal ? 'fa-tag text-purple-600' : 'fa-barcode text-emerald-600'} absolute left-3 top-3 text-sm"></i>
                    <button type="button" onclick="${isExternal ? 'openQuickProductModal(document.getElementById(\'dashboard-barcode-input\').value)' : 'processDashboardBarcodeScan(document.getElementById(\'dashboard-barcode-input\').value)'}" title="${isExternal ? 'Cadastrar Peça [Enter]' : 'Processar Código'}" class="absolute right-1.5 top-1.5 ${isExternal ? 'bg-purple-700 hover:bg-purple-800' : 'bg-emerald-600 hover:bg-emerald-700'} text-white rounded-lg px-2.5 py-1.5 text-[10px] font-extrabold shadow-xs cursor-pointer flex items-center gap-1">
                        <span>[Enter]</span> <i class="fas fa-arrow-right text-[9px]"></i>
                    </button>
                </div>
                <div id="dashboard-barcode-status" class="text-[11px] text-emerald-900 font-bold flex items-center justify-between min-h-[18px]">
                    ${isExternal ? '<span class="flex items-center gap-1.5 text-purple-900 font-extrabold"><span class="w-2 h-2 rounded-full bg-purple-600 animate-pulse"></span> 💡 Tecle [Enter] para cadastrar nova peça vendida para a cliente</span>' : '<span class="flex items-center gap-1.5 text-emerald-900 font-extrabold"><span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Aguardando bipe do leitor de código de barras ou [Enter]...</span>'}
                </div>
            </div>
        `;
    }

    function handleDashboardBarcodeInput(e) {
        if (barcodeDebounceTimer) clearTimeout(barcodeDebounceTimer);
        
        // No modo Live Externa, NÃO fazemos busca automática no estoque enquanto o usuário digita
        if (liveOperationMode === 'external') {
            return;
        }

        // No modo Estoque, leitores enviam string rápida. Mas deixamos o Enter tratar com 100% de segurança
    }

    function handleDashboardBarcodeKeyDown(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (barcodeDebounceTimer) clearTimeout(barcodeDebounceTimer);
            const val = (e.target.value || '').trim();
            
            if (liveOperationMode === 'external') {
                openQuickProductModal(val);
                e.target.value = '';
                return;
            }

            // Modo Estoque
            if (!val) {
                openQuickProductModal();
                return;
            }
            processDashboardBarcodeScan(val);
        }
    }

    async function processDashboardBarcodeScan(rawCode) {
        if (!rawCode || !rawCode.trim()) return;
        let code = rawCode.trim();

        if (!selectedParticipant) {
            playErrorBeep();
            showToast("⚠️ Selecione uma cliente na lista antes de bipar o produto!");
            const statusEl = document.getElementById("dashboard-barcode-status");
            if (statusEl) {
                statusEl.innerHTML = `<span class="text-amber-700 font-bold"><i class="fas fa-exclamation-triangle"></i> Selecione uma cliente para adicionar à sacola!</span>`;
            }
            return;
        }

        // Extrair código limpo se for URL
        if (code.startsWith('http://') || code.startsWith('https://')) {
            try {
                const url = new URL(code);
                const p = url.searchParams.get('codigo') || url.searchParams.get('c') || url.searchParams.get('code') || url.searchParams.get('item');
                if (p) {
                    code = p.trim();
                } else {
                    const segs = url.pathname.split('/').filter(Boolean);
                    if (segs.length > 0) code = segs[segs.length - 1].trim();
                }
            } catch(e) {}
        }

        const now = Date.now();
        if (isDashboardBarcodeProcessing) return;
        if (lastDashboardBarcode === code && (now - lastDashboardBarcodeTime) < 1800) {
            console.warn("Scan bloqueado: mesmo código bipado em intervalo muito curto.");
            return;
        }

        isDashboardBarcodeProcessing = true;
        lastDashboardBarcode = code;
        lastDashboardBarcodeTime = now;

        const input = document.getElementById("dashboard-barcode-input");
        if (input) input.value = "";

        const statusEl = document.getElementById("dashboard-barcode-status");
        if (statusEl) {
            statusEl.innerHTML = `<span class="text-blue-700 font-bold flex items-center gap-1.5"><i class="fas fa-spinner fa-spin"></i> Buscando item "${escapeHtml(code)}"...</span>`;
        }

        try {
            const response = await fetch(`/api/items/search?q=${encodeURIComponent(code)}${liveId ? '&live_id=' + encodeURIComponent(liveId) : ''}`);
            const data = await response.json();

            if (!data.success || !data.data || data.data.length === 0) {
                playErrorBeep();
                showToast(`❌ Nenhum produto encontrado com o código "${code}"`);
                if (statusEl) {
                    statusEl.innerHTML = `<span class="text-red-600 font-bold flex items-center gap-1.5"><i class="fas fa-times-circle"></i> Produto "${escapeHtml(code)}" não encontrado!</span>`;
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

            const addResponse = await fetch('/admin/live-chat/add-to-bag', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    code_request_id: null,
                    user_id: selectedParticipant.userId,
                    item_id: matchedItem.id,
                    live_id: liveId
                })
            });

            const addData = await addResponse.json();

            if (addData.success) {
                playSuccessBeep();
                showToast(`🎉 ${matchedItem.name} adicionado à sacola de @${selectedParticipant.username}!`);
                if (statusEl) {
                    statusEl.innerHTML = `<span class="text-emerald-700 font-extrabold flex items-center gap-1.5 truncate"><i class="fas fa-check-circle text-emerald-600"></i> ${escapeHtml(matchedItem.name)} (${matchedItem.formatted_price || 'R$ ' + matchedItem.price}) adicionado!</span>`;
                }
                fetchChatData();
            } else {
                playErrorBeep();
                showToast(`⚠️ ${addData.message || 'Erro ao adicionar item à sacola'}`);
                if (statusEl) {
                    statusEl.innerHTML = `<span class="text-amber-700 font-bold flex items-center gap-1.5"><i class="fas fa-exclamation-triangle"></i> ${escapeHtml(addData.message || 'Falha ao adicionar.')}</span>`;
                }
            }
        } catch (err) {
            playErrorBeep();
            console.error("Erro ao processar código de barras:", err);
            showToast("Erro de comunicação com o servidor ao buscar produto.");
            if (statusEl) {
                statusEl.innerHTML = `<span class="text-red-600 font-bold flex items-center gap-1.5"><i class="fas fa-exclamation-circle"></i> Erro de comunicação com o servidor.</span>`;
            }
        } finally {
            setTimeout(() => {
                isDashboardBarcodeProcessing = false;
                const anyModalOpen = document.querySelector('#modal-quick-product:not(.hidden), #modal-quick-client:not(.hidden), #link-user-modal:not(.hidden), #online-qr-modal:not(.hidden), #insta-login-modal:not(.hidden)');
                const input = document.getElementById("dashboard-barcode-input");
                if (input && !anyModalOpen && (!document.activeElement || (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA'))) {
                    input.focus();
                }
            }, 350);
        }
    }

    // Listener global para capturar leitor de código de barras físico USB / Sem fio
    window.addEventListener('keydown', function(e) {
        // Se qualquer modal estiver aberto, ignora completamente o leitor global
        const anyModalOpen = document.querySelector('#modal-quick-product:not(.hidden), #modal-quick-client:not(.hidden), #link-user-modal:not(.hidden), #online-qr-modal:not(.hidden), #insta-login-modal:not(.hidden)');
        if (anyModalOpen) {
            globalBarcodeBuffer = "";
            return;
        }

        const activeEl = document.activeElement;
        // Se o operador está com foco em qualquer campo de entrada, formulário ou textarea
        if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA' || activeEl.tagName === 'SELECT' || activeEl.isContentEditable)) {
            globalBarcodeBuffer = "";
            return; // Operador está digitando em um campo de texto explicitamente
        }

        const now = Date.now();

        if (e.key === 'Enter') {
            if (globalBarcodeBuffer.trim().length >= 2) {
                e.preventDefault();
                const codeToProcess = globalBarcodeBuffer.trim();
                globalBarcodeBuffer = "";
                processDashboardBarcodeScan(codeToProcess);
            }
            return;
        }

        if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
            if (now - globalBarcodeLastKeyTime > 300) {
                globalBarcodeBuffer = "";
            }
            globalBarcodeLastKeyTime = now;
            globalBarcodeBuffer += e.key;

            if (barcodeDebounceTimer) clearTimeout(barcodeDebounceTimer);
            barcodeDebounceTimer = setTimeout(() => {
                if (globalBarcodeBuffer.trim().length >= 2) {
                    const codeToProcess = globalBarcodeBuffer.trim();
                    globalBarcodeBuffer = "";
                    processDashboardBarcodeScan(codeToProcess);
                }
            }, 140);
        }
    });

    // ==========================================
    // SINTETIZADOR DE ÁUDIO (FEEDBACK SONORO INSTANTÂNEO VIA WEB AUDIO API)
    // ==========================================
    function playSuccessBeep() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.type = "sine";
            osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5
            osc.frequency.setValueAtTime(1760, audioCtx.currentTime + 0.08); // A6
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

    // ==========================================
    // LEITOR DE QRCODE / ETIQUETAS PARA PESSOAS ONLINE
    // ==========================================
    let onlineQrScannerInstance = null;
    let currentOnlineQrUser = null;
    let autoCloseAfterScan = localStorage.getItem('live_chat_auto_close_scan') !== 'false'; // Padrão: true (fechar após bipar ativado)

    function toggleAutoClosePreference(checked) {
        autoCloseAfterScan = checked;
        localStorage.setItem('live_chat_auto_close_scan', checked ? 'true' : 'false');
    }

    function openOnlineQrModal(userId, username, clientName) {
        if (!userId || userId === 'null' || userId === 'undefined') {
            openLinkModal(username, 'instagram');
            return;
        }

        currentOnlineQrUser = {
            userId: userId,
            username: username,
            clientName: clientName || username
        };

        const clientNameEl = document.getElementById("online-qr-client-name");
        if (clientNameEl) {
            clientNameEl.textContent = `@${username} (${clientName || 'Cliente'})`;
        }

        const manualInput = document.getElementById("online-qr-manual-input");
        if (manualInput) manualInput.value = "";

        const feedback = document.getElementById("online-qr-feedback");
        if (feedback) {
            feedback.className = "p-4 rounded-2xl text-xs font-bold hidden transition duration-200 border shadow-xl leading-relaxed";
            feedback.textContent = "";
        }

        // Configura campo e badge de WhatsApp
        const userObj = onlineUsersMap[userId] || {};
        const clientPhone = userObj.user_whatsapp || '';
        const phoneInput = document.getElementById("online-qr-phone-input");
        if (phoneInput) {
            phoneInput.value = clientPhone;
        }
        updatePhoneBadge(!!clientPhone, clientPhone);

        // Renderiza comentários da cliente nesta live
        modalFilterMarkedOnly = false;
        const starBtn = document.getElementById("modal-client-filter-star-btn");
        if (starBtn) {
            starBtn.className = "text-[10px] px-2 py-0.5 rounded-lg font-bold bg-gray-800 text-yellow-400 hover:bg-gray-700 border border-gray-700 transition flex items-center gap-1 cursor-pointer";
        }
        renderModalClientMessages(username);

        const autoCloseToggle = document.getElementById("online-qr-auto-close-toggle");
        if (autoCloseToggle) {
            autoCloseToggle.checked = autoCloseAfterScan;
        }

        document.getElementById("online-qr-modal").classList.remove("hidden");
        if (manualInput) manualInput.focus();

        // Inicializar câmera HTML5-QRCode
        if (typeof Html5Qrcode === 'undefined') {
            const script = document.createElement('script');
            script.src = "https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js";
            script.onload = () => startOnlineQrCamera();
            document.head.appendChild(script);
        } else {
            startOnlineQrCamera();
        }
    }

    function updatePhoneBadge(hasPhone, phoneText) {
        const badge = document.getElementById("online-qr-phone-badge");
        const help = document.getElementById("online-qr-phone-help");
        if (!badge) return;

        if (hasPhone && phoneText && phoneText.length >= 8) {
            badge.className = "text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-900/60 text-emerald-300 border border-emerald-700";
            badge.innerHTML = `<i class="fas fa-check-circle"></i> Cadastrado`;
            if (help) {
                help.className = "text-[10.5px] text-emerald-400/90 leading-tight";
                help.textContent = "✅ WhatsApp verificado para envio automático ao encerrar a live.";
            }
        } else {
            badge.className = "text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-900/60 text-amber-300 border border-amber-700 animate-pulse";
            badge.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Sem Telefone`;
            if (help) {
                help.className = "text-[10.5px] text-amber-300 font-bold leading-tight";
                help.textContent = "⚠️ Digite o WhatsApp acima e clique em 'Salvar' para garantir o envio da sacola!";
            }
        }
    }

    async function saveClientPhoneFromModal() {
        if (!currentOnlineQrUser || !currentOnlineQrUser.userId) return;
        const input = document.getElementById("online-qr-phone-input");
        const phone = input ? input.value.trim() : '';

        if (!phone || phone.length < 8) {
            alert("Por favor, digite um número de WhatsApp válido com DDD.");
            return;
        }

        const btn = document.getElementById("online-qr-phone-save-btn");
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
                    user_id: currentOnlineQrUser.userId,
                    phone: phone
                })
            });
            const data = await res.json();
            btn.disabled = false;
            btn.innerHTML = oldHtml;

            if (data.success) {
                showToast("WhatsApp salvo com sucesso!");
                updatePhoneBadge(true, data.phone);
                if (onlineUsersMap[currentOnlineQrUser.userId]) {
                    onlineUsersMap[currentOnlineQrUser.userId].user_whatsapp = data.phone;
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

    function toggleModalFilterMarkedOnly() {
        modalFilterMarkedOnly = !modalFilterMarkedOnly;
        const btn = document.getElementById("modal-client-filter-star-btn");
        if (btn) {
            if (modalFilterMarkedOnly) {
                btn.className = "text-[10px] px-2 py-0.5 rounded-lg font-bold bg-yellow-500 text-gray-900 border border-yellow-400 transition flex items-center gap-1 cursor-pointer shadow-sm";
            } else {
                btn.className = "text-[10px] px-2 py-0.5 rounded-lg font-bold bg-gray-800 text-yellow-400 hover:bg-gray-700 border border-gray-700 transition flex items-center gap-1 cursor-pointer";
            }
        }
        if (currentOnlineQrUser) {
            renderModalClientMessages(currentOnlineQrUser.username);
        }
    }

    function renderModalClientMessages(username) {
        const container = document.getElementById("online-qr-client-messages-list");
        if (!container) return;

        const uLower = username.toLowerCase().replace(/^@/, '');
        let msgs = allLiveMessages.filter(m => m.username && m.username.toLowerCase() === uLower);

        const markedCount = msgs.filter(m => m.is_marked).length;
        const countEl = document.getElementById("modal-client-marked-count");
        if (countEl) countEl.textContent = markedCount;

        if (modalFilterMarkedOnly) {
            msgs = msgs.filter(m => m.is_marked);
        }

        if (msgs.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5 text-gray-500">
                    <i class="fas ${modalFilterMarkedOnly ? 'fa-star text-yellow-500/40' : 'fa-comment-slash'} text-xl mb-1"></i>
                    <p class="text-[11px]">${modalFilterMarkedOnly ? 'Nenhuma mensagem marcada desta cliente.' : 'Nenhum comentário enviado por @' + escapeHtml(username) + ' nesta live.'}</p>
                </div>
            `;
            return;
        }

        let html = '';
        msgs.forEach(msg => {
            const time = new Date(msg.created_at).toLocaleTimeString();
            const isMarked = !!msg.is_marked;
            const starClass = isMarked ? 'fas fa-star text-yellow-400' : 'far fa-star text-gray-600 hover:text-yellow-400';
            const bgCard = isMarked ? 'bg-yellow-950/30 border border-yellow-500/40' : 'bg-gray-800/70 border border-gray-700/60';

            // Detectar sequências numéricas (3 a 6 dígitos) como códigos de produtos clicáveis
            let textWithCodes = escapeHtml(msg.message);
            textWithCodes = textWithCodes.replace(/\b(\d{3,6})\b/g, function(match, code) {
                return `<button type="button" onclick="event.stopPropagation(); clickCodeFromComment('${code}')" class="inline-flex items-center gap-1 bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold px-1.5 py-0.5 rounded text-[11px] transition shadow cursor-pointer mx-0.5 active:scale-95"><i class="fas fa-barcode text-[9px]"></i> ${code}</button>`;
            });

            html += `
                <div class="${bgCard} p-2 rounded-xl text-xs transition flex flex-col gap-1">
                    <div class="flex items-center justify-between text-[10px] text-gray-400">
                        <span class="font-mono text-gray-400">${time}</span>
                        <button type="button" onclick="toggleMarkLiveMessage(${msg.id})" title="${isMarked ? 'Desmarcar' : 'Marcar'}" class="p-0.5 cursor-pointer">
                            <i class="${starClass}"></i>
                        </button>
                    </div>
                    <div class="text-gray-100 font-medium leading-relaxed">${textWithCodes}</div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    function clickCodeFromComment(code) {
        const input = document.getElementById("online-qr-manual-input");
        if (input) {
            input.value = code;
            handleOnlineQrScan(code);
        }
    }

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

    async function startOnlineQrCamera() {
        if (!onlineQrScannerInstance) {
            let formats = [0, 9, 5]; // QR_CODE, EAN_13, CODE_128
            if (typeof Html5QrcodeSupportedFormats !== 'undefined') {
                formats = [
                    Html5QrcodeSupportedFormats.QR_CODE,
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.CODE_128
                ];
            }
            onlineQrScannerInstance = new Html5Qrcode("online-qr-reader", { formatsToSupport: formats });
        }

        try {
            const config = {
                fps: 15,
                qrbox: function(width, height) {
                    const minEdge = Math.min(width, height);
                    const size = Math.min(Math.floor(minEdge * 0.8), 650);
                    return { width: size, height: size };
                },
                disableFlip: true
            };

            await onlineQrScannerInstance.start(
                { facingMode: "environment" },
                config,
                async (decodedText) => {
                    console.log("QRCode da etiqueta lido para usuário online:", decodedText);
                    await handleOnlineQrScan(decodedText);
                }
            );
        } catch (err) {
            console.warn("Não foi possível iniciar a câmera ou permissão negada:", err);
            const feedback = document.getElementById("online-qr-feedback");
            if (feedback) {
                feedback.className = "p-4 sm:p-5 rounded-3xl text-xs sm:text-sm font-bold bg-amber-500/20 text-amber-200 border border-amber-500/40 block shadow-lg";
                feedback.innerHTML = `<i class="fas fa-exclamation-triangle text-amber-400 mr-1.5"></i> Câmera não acessível (${err.message || 'Sem permissão'}). Digite ou bipe o código com leitor USB no campo acima!`;
            }
        }
    }

    async function closeOnlineQrModal() {
        if (onlineQrScannerInstance) {
            try {
                await onlineQrScannerInstance.stop();
            } catch (e) {}
        }
        document.getElementById("online-qr-modal").classList.add("hidden");
        fetchChatData();
    }

    let isScanProcessing = false;
    let lastScannedCode = "";
    let lastScannedTime = 0;

    async function handleOnlineQrScan(decodedText) {
        if (!decodedText || !decodedText.trim() || !currentOnlineQrUser) return;
        
        let code = decodedText.trim();

        // Extrair código limpo se for URL
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

        const now = Date.now();
        if (isScanProcessing) {
            console.warn("Scan bloqueado: outro bipe em processamento.");
            return;
        }
        if (lastScannedCode === code && (now - lastScannedTime) < 2000) {
            console.warn("Scan bloqueado: mesmo código bipado em menos de 2 segundos.");
            return;
        }

        isScanProcessing = true;
        lastScannedCode = code;
        lastScannedTime = now;

        const manualInput = document.getElementById("online-qr-manual-input");
        if (manualInput) manualInput.value = "";

        const feedback = document.getElementById("online-qr-feedback");
        if (feedback) {
            feedback.className = "p-4 sm:p-5 rounded-3xl text-sm font-bold bg-blue-500/20 text-blue-200 border border-blue-500/40 block animate-pulse shadow-lg";
            feedback.innerHTML = `<div class="flex items-center gap-3"><i class="fas fa-spinner fa-spin text-blue-400 text-xl"></i><div><b>Buscando produto...</b><br><span class="text-xs text-blue-300 font-normal">Etiqueta/SKU: ${escapeHtml(code)}</span></div></div>`;
        }

        try {
            const response = await fetch(`/api/items/search?q=${encodeURIComponent(code)}${liveId ? '&live_id=' + encodeURIComponent(liveId) : ''}`);
            const data = await response.json();

            if (!data.success || !data.data || data.data.length === 0) {
                playErrorBeep();
                if (feedback) {
                    feedback.className = "p-4 sm:p-5 rounded-3xl text-sm font-bold bg-red-500/20 text-red-200 border border-red-500/40 block shadow-lg animate-shake";
                    feedback.innerHTML = `<div class="flex items-start gap-3"><i class="fas fa-times-circle text-red-400 text-xl mt-0.5"></i><div><b>Produto não encontrado!</b><br><span class="text-xs text-red-300 font-normal">Nenhum produto cadastrado com o SKU ou código de barras <b>"${escapeHtml(code)}"</b>. Verifique o código e tente novamente.</span></div></div>`;
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

            const addResponse = await fetch('/admin/live-chat/add-to-bag', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    code_request_id: null,
                    user_id: currentOnlineQrUser.userId,
                    item_id: matchedItem.id,
                    live_id: liveId
                })
            });

            const addData = await addResponse.json();

            if (addData.success) {
                playSuccessBeep();
                showToast(`🎉 ${matchedItem.name} adicionado à sacola de @${currentOnlineQrUser.username}!`);
                if (feedback) {
                    feedback.className = "p-4 sm:p-5 rounded-3xl text-sm font-bold bg-emerald-500/20 text-emerald-200 border border-emerald-500/40 block shadow-xl";
                    feedback.innerHTML = `<div class="flex items-start gap-3"><div class="w-10 h-10 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center shrink-0"><i class="fas fa-check text-emerald-400 text-xl"></i></div><div><div class="text-emerald-300 text-xs uppercase tracking-wider font-extrabold">Adicionado à sacola!</div><div class="text-white text-base font-extrabold mt-0.5">${escapeHtml(matchedItem.name)}</div><div class="text-emerald-400 font-bold text-sm mt-0.5">${matchedItem.formatted_price || 'R$ ' + matchedItem.price} &bull; <span class="text-gray-300 font-normal">Para @${escapeHtml(currentOnlineQrUser.username)}</span></div><div class="text-xs text-emerald-300/80 mt-2 font-normal">${autoCloseAfterScan ? '<i class="fas fa-check-double text-emerald-400 mr-1"></i> Fechando leitor em instantes...' : '<i class="fas fa-camera text-emerald-400 mr-1"></i> Pronto para o próximo item! Aponte a câmera ou bipe agora.'}</div></div></div>`;
                }

                if (autoCloseAfterScan) {
                    setTimeout(() => {
                        closeOnlineQrModal();
                    }, 750);
                }
            } else {
                playErrorBeep();
                if (feedback) {
                    feedback.className = "p-4 sm:p-5 rounded-3xl text-sm font-bold bg-amber-500/20 text-amber-200 border border-amber-500/40 block shadow-lg";
                    feedback.innerHTML = `<div class="flex items-start gap-3"><i class="fas fa-exclamation-triangle text-amber-400 text-xl mt-0.5"></i><div><b>Atenção:</b><br><span class="text-xs text-amber-300 font-normal">${escapeHtml(addData.message || 'Falha ao incluir na sacola.')}</span></div></div>`;
                }
            }
        } catch (err) {
            playErrorBeep();
            console.error("Erro na leitura/adição por QR Code:", err);
            if (feedback) {
                feedback.className = "p-4 sm:p-5 rounded-3xl text-sm font-bold bg-red-500/20 text-red-200 border border-red-500/40 block shadow-lg";
                feedback.innerHTML = `<div class="flex items-start gap-3"><i class="fas fa-exclamation-circle text-red-400 text-xl mt-0.5"></i><div><b>Erro de comunicação:</b><br><span class="text-xs text-red-300 font-normal">Falha ao se comunicar com o servidor ao processar "${escapeHtml(code)}".</span></div></div>`;
            }
        } finally {
            setTimeout(() => {
                isScanProcessing = false;
            }, 600);
        }
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
    // Controle do Modal de Login Direto na VPS do Instagram
    function openInstaLoginModal() {
        document.getElementById("insta-login-modal").classList.remove("hidden");
        document.getElementById("insta-login-form-step").classList.remove("hidden");
        document.getElementById("insta-login-2fa-step").classList.add("hidden");
    }

    function closeInstaLoginModal() {
        document.getElementById("insta-login-modal").classList.add("hidden");
    }

    async function submitVpsInstaLogin() {
        const usr = document.getElementById("vps-insta-user").value.trim();
        const pwd = document.getElementById("vps-insta-pass").value.trim();
        if (!usr || !pwd) {
            alert("Preencha o usuário e senha do Instagram.");
            return;
        }

        const btn = document.getElementById("vps-login-submit-btn");
        const oldText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Autenticando na VPS...`;

        try {
            const res = await fetch(getBackendUrl(3002, "/login"), {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ username: usr, password: pwd })
            });
            const data = await res.json();
            btn.disabled = false;
            btn.innerHTML = oldText;

            if (data.success) {
                showToast(data.message);
                closeInstaLoginModal();
                const input = document.getElementById("insta-username-input");
                if (input) input.value = usr.replace(/^@/, '');
                checkInstagramBackendStatus();
            } else if (data.status === 'challenge') {
                document.getElementById("insta-login-form-step").classList.add("hidden");
                document.getElementById("insta-login-2fa-step").classList.remove("hidden");
                if (data.message) document.getElementById("vps-2fa-msg").textContent = data.message;
            } else {
                alert("Atenção: " + (data.message || "Verifique suas credenciais."));
            }
        } catch(e) {
            btn.disabled = false;
            btn.innerHTML = oldText;
            alert("Erro ao comunicar com a VPS: " + e.message);
        }
    }

    async function submitVpsInstaCode() {
        const code = document.getElementById("vps-insta-code").value.trim();
        if (!code) {
            alert("Digite o código de 6 dígitos recebido.");
            return;
        }

        const btn = document.getElementById("vps-2fa-submit-btn");
        const oldText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Confirmando...`;

        try {
            const res = await fetch(getBackendUrl(3002, "/login-code"), {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ code: code })
            });
            const data = await res.json();
            btn.disabled = false;
            btn.innerHTML = oldText;

            if (data.success) {
                showToast(data.message);
                closeInstaLoginModal();
                checkInstagramBackendStatus();
            } else {
                alert("Erro: " + (data.message || "Código inválido ou expirado."));
            }
        } catch(e) {
            btn.disabled = false;
            btn.innerHTML = oldText;
            alert("Erro na requisição: " + e.message);
        }
    }

    // Busca de clientes (ignora setas e enter para não sobrepor a navegação)
    let lastAvulsoQuery = "";
    document.getElementById("avulso-search-input").addEventListener("keyup", function(e) {
        if ([38, 40, 13].includes(e.keyCode)) return;
        if (this.value === lastAvulsoQuery) return;
        lastAvulsoQuery = this.value;
        searchAvulsoClients(this.value);
    });

    // Navegação por teclado no input Avulso
    document.getElementById("avulso-search-input").addEventListener("keydown", function(e) {
        let x = document.getElementById("avulso-search-results");
        if (!x || x.classList.contains('hidden')) return;
        let items = x.querySelectorAll('.avulso-search-item');
        if (!items || items.length === 0) return;

        if (e.keyCode == 40) { // Seta para baixo
            currentAvulsoFocus++;
            addActiveAvulso(items);
            e.preventDefault();
        } else if (e.keyCode == 38) { // Seta para cima
            currentAvulsoFocus--;
            addActiveAvulso(items);
            e.preventDefault();
        } else if (e.keyCode == 13) { // Enter
            e.preventDefault();
            if (currentAvulsoFocus > -1) {
                if (items[currentAvulsoFocus]) {
                    items[currentAvulsoFocus].click();
                }
            } else if (items.length > 0) {
                items[0].click();
            }
        }
    });

    function addActiveAvulso(x) {
        if (!x) return false;
        removeActiveAvulso(x);
        if (currentAvulsoFocus >= x.length) currentAvulsoFocus = 0;
        if (currentAvulsoFocus < 0) currentAvulsoFocus = (x.length - 1);
        x[currentAvulsoFocus].classList.add("bg-indigo-100", "border-l-4", "border-indigo-500");
        x[currentAvulsoFocus].classList.remove("bg-white");
        x[currentAvulsoFocus].scrollIntoView({ block: "nearest", behavior: "smooth" });
    }

    function removeActiveAvulso(x) {
        for (var i = 0; i < x.length; i++) {
            x[i].classList.remove("bg-indigo-100", "border-l-4", "border-indigo-500");
            x[i].classList.add("bg-white");
        }
    }
    // =========================================================================
    // MODAIS DE CADASTRO RÁPIDO (CLIENTE & PRODUTO)
    // =========================================================================
    function openQuickClientModal() {
        globalBarcodeBuffer = "";
        if (barcodeDebounceTimer) clearTimeout(barcodeDebounceTimer);

        const barcodeInput = document.getElementById('dashboard-barcode-input');
        if (barcodeInput) barcodeInput.blur();

        const nameEl = document.getElementById('quick-client-name');
        const instaEl = document.getElementById('quick-client-instagram');
        const tiktokEl = document.getElementById('quick-client-tiktok');
        const phoneEl = document.getElementById('quick-client-phone');
        const limiteEl = document.getElementById('quick-client-limite');

        if (nameEl) nameEl.value = '';
        if (instaEl) instaEl.value = '';
        if (tiktokEl) tiktokEl.value = '';
        if (phoneEl) phoneEl.value = '';
        if (limiteEl) limiteEl.value = '300.00';

        const modal = document.getElementById('modal-quick-client');
        if (modal) modal.classList.remove('hidden');

        setTimeout(() => {
            if (nameEl) {
                nameEl.focus();
                nameEl.select();
            }
        }, 120);
    }

    function closeQuickClientModal() {
        const modal = document.getElementById('modal-quick-client');
        if (modal) modal.classList.add('hidden');
    }

    async function submitQuickClient(event) {
        event.preventDefault();
        const btn = document.getElementById('btn-save-quick-client') || (event.target ? event.target.querySelector('button[type="submit"]') : null);
        const originalBtnHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Salvando...';
        }

        const name = (document.getElementById('quick-client-name')?.value || '').trim();
        const instagram = (document.getElementById('quick-client-instagram')?.value || '').trim().replace(/^@/, '');
        const tiktok = (document.getElementById('quick-client-tiktok')?.value || '').trim().replace(/^@/, '');
        const phone = (document.getElementById('quick-client-phone')?.value || '').trim();
        const limite = (document.getElementById('quick-client-limite')?.value || '').trim();

        try {
            const resp = await fetch('{{ route('admin.live-chat.quick-store-client') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name: name,
                    instagram: instagram,
                    tiktok: tiktok,
                    phone: phone,
                    limite_credito: limite
                })
            });

            const data = await resp.json();
            if (data.success && data.client) {
                playSuccessBeep();
                showToast(data.message || 'Cliente cadastrado com sucesso!');
                closeQuickClientModal();

                const newClient = data.client;
                const primaryUsername = newClient.instagram || newClient.tiktok || newClient.name;
                const platform = newClient.instagram ? 'instagram' : (newClient.tiktok ? 'tiktok' : 'instagram');
                selectOnlineParticipant(newClient.id, primaryUsername, newClient.name, platform, '');
            } else {
                playErrorBeep();
                showToast(data.message || 'Erro ao cadastrar cliente.');
            }
        } catch (err) {
            playErrorBeep();
            console.error('Erro ao cadastrar cliente:', err);
            showToast('Erro de conexão ao cadastrar cliente.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml || '<i class="fas fa-check"></i> Salvar Cliente';
            }
        }
    }

    function updateQuickProductCostCalc() {
        const precoInput = document.getElementById('quick-prod-preco');
        const comissaoInput = document.getElementById('quick-prod-comissao-perc');
        const custoInput = document.getElementById('quick-prod-custo');
        const repasseBadge = document.getElementById('quick-prod-calc-repasse');
        const comissaoBadge = document.getElementById('quick-prod-calc-comissao');

        if (!precoInput) return;

        const precoRaw = precoInput.value || '';
        const precoClean = precoRaw.replace(/[^0-9,\.]/g, '').replace(',', '.');
        const precoVal = parseFloat(precoClean) || 0;

        const comissaoPerc = parseFloat(comissaoInput?.value || 10) || 0;

        const repasseVal = Math.round(precoVal * (1 - (comissaoPerc / 100)) * 100) / 100;
        const comissaoVal = Math.round((precoVal - repasseVal) * 100) / 100;

        if (custoInput && document.activeElement !== custoInput) {
            custoInput.value = repasseVal > 0 ? repasseVal.toFixed(2).replace('.', ',') : '';
        }

        if (repasseBadge) {
            repasseBadge.textContent = 'R$ ' + (repasseVal > 0 ? repasseVal.toFixed(2).replace('.', ',') : '0,00');
        }
        if (comissaoBadge) {
            comissaoBadge.textContent = 'R$ ' + (comissaoVal > 0 ? comissaoVal.toFixed(2).replace('.', ',') : '0,00') + ` (${comissaoPerc}%)`;
        }
    }

    function openQuickProductModal(initialVal = '') {
        globalBarcodeBuffer = "";
        if (barcodeDebounceTimer) clearTimeout(barcodeDebounceTimer);

        const barcodeInput = document.getElementById('dashboard-barcode-input');
        if (barcodeInput) {
            barcodeInput.value = '';
            barcodeInput.blur();
        }

        const descEl = document.getElementById('quick-prod-descricao');
        const tamEl = document.getElementById('quick-prod-tamanho');
        const corEl = document.getElementById('quick-prod-cor');
        const precoEl = document.getElementById('quick-prod-preco');
        const custoEl = document.getElementById('quick-prod-custo');
        const comissaoEl = document.getElementById('quick-prod-comissao-perc');
        const linkCheck = document.getElementById('quick-prod-link-live');

        if (tamEl) tamEl.value = '';
        if (corEl) corEl.value = '';
        if (linkCheck) linkCheck.checked = true;
        if (comissaoEl && !comissaoEl.value) comissaoEl.value = '10';

        let focusTarget = descEl;

        const trimmed = (typeof initialVal === 'string' ? initialVal : '').trim();
        if (trimmed) {
            // Verifica se é valor numérico (ex: 50, 50.00, 50,00, R$ 50)
            const numericMatch = trimmed.replace(/^R\$\s*/i, '').replace(',', '.');
            if (!isNaN(numericMatch) && Number(numericMatch) > 0) {
                if (precoEl) precoEl.value = Number(numericMatch).toFixed(2).replace('.', ',');
                if (descEl) descEl.value = '';
                focusTarget = descEl;
            } else {
                if (descEl) descEl.value = trimmed;
                if (precoEl) precoEl.value = '';
                focusTarget = precoEl;
            }
        } else {
            if (descEl) descEl.value = '';
            if (precoEl) precoEl.value = '';
        }

        updateQuickProductCostCalc();

        const modal = document.getElementById('modal-quick-product');
        if (modal) modal.classList.remove('hidden');

        setTimeout(() => {
            if (focusTarget) {
                focusTarget.focus();
                focusTarget.select();
            }
        }, 120);
    }

    function closeQuickProductModal() {
        const modal = document.getElementById('modal-quick-product');
        if (modal) modal.classList.add('hidden');
    }

    async function submitQuickProduct(event) {
        event.preventDefault();
        const btn = document.getElementById('btn-save-quick-prod') || (event.target ? event.target.querySelector('button[type="submit"]') : null);
        const originalBtnHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Salvando e Adicionando...';
        }

        const descricao = (document.getElementById('quick-prod-descricao')?.value || '').trim();
        const tamanho = (document.getElementById('quick-prod-tamanho')?.value || '').trim();
        const cor = (document.getElementById('quick-prod-cor')?.value || '').trim();
        const preco = (document.getElementById('quick-prod-preco')?.value || '').trim();
        const custo = (document.getElementById('quick-prod-custo')?.value || '').trim();
        const comissaoPerc = (document.getElementById('quick-prod-comissao-perc')?.value || '').trim();
        const linkLive = document.getElementById('quick-prod-link-live') ? document.getElementById('quick-prod-link-live').checked : false;

        try {
            const resp = await fetch('{{ route('admin.live-chat.quick-store-product') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    descricao: descricao,
                    tamanho: tamanho,
                    cor: cor,
                    preco: preco,
                    custo: custo,
                    comissao_percent: comissaoPerc,
                    live_id: {{ $activeLive ? $activeLive->id : 'null' }},
                    link_to_live: linkLive
                })
            });

            const data = await resp.json();
            if (data.success) {
                const item = data.item;
                const code = item ? item.codigo : '';
                showToast(`Peça #${code} cadastrada com sucesso!`);
                closeQuickProductModal();

                if (selectedParticipant && item) {
                    const statusEl = document.getElementById("dashboard-barcode-status");
                    if (statusEl) {
                        statusEl.innerHTML = `<span class="text-blue-700 font-bold flex items-center gap-1.5"><i class="fas fa-spinner fa-spin"></i> Adicionando à sacola de @${escapeHtml(selectedParticipant.username)}...</span>`;
                    }

                    try {
                        const addResponse = await fetch('/admin/live-chat/add-to-bag', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                code_request_id: null,
                                user_id: selectedParticipant.userId,
                                item_id: item.id,
                                live_id: liveId
                            })
                        });

                        const addData = await addResponse.json();
                        if (addData.success) {
                            playSuccessBeep();
                            showToast(`🎉 ${item.nome} adicionado à sacola de @${selectedParticipant.username}!`);
                            if (statusEl) {
                                statusEl.innerHTML = `<span class="text-emerald-700 font-extrabold flex items-center gap-1.5 truncate"><i class="fas fa-check-circle text-emerald-600"></i> ${escapeHtml(item.nome)} (${item.preco_formatado || 'R$ ' + item.preco}) adicionado à sacola!</span>`;
                            }
                            fetchChatData();
                        } else {
                            playErrorBeep();
                            showToast(`⚠️ ${addData.message || 'Erro ao adicionar item à sacola'}`);
                            if (statusEl) {
                                statusEl.innerHTML = `<span class="text-amber-700 font-bold flex items-center gap-1.5"><i class="fas fa-exclamation-triangle"></i> ${escapeHtml(addData.message || 'Falha ao adicionar.')}</span>`;
                            }
                        }
                    } catch(addErr) {
                        playErrorBeep();
                        console.error("Erro ao adicionar produto rápido à sacola:", addErr);
                    }
                } else {
                    playSuccessBeep();
                }

                setTimeout(() => {
                    const bInput = document.getElementById('dashboard-barcode-input');
                    if (bInput) {
                        bInput.value = '';
                        bInput.focus();
                    }
                }, 150);
            } else {
                playErrorBeep();
                showToast(data.message || 'Erro ao cadastrar produto.');
            }
        } catch (err) {
            playErrorBeep();
            console.error('Erro ao cadastrar produto:', err);
            showToast('Erro de conexão ao cadastrar produto.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml || '<i class="fas fa-check-circle"></i> Salvar e Adicionar à Sacola';
            }
        }
    }

    function safeAttr(str) {
        if (!str) return '';
        return String(str).replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    async function fetchTwilioBalance() {
        const badge = document.getElementById('twilio-balance-text');
        if (!badge) return;
        badge.innerText = "Calculando...";
        try {
            const res = await fetch('{{ route("admin.chat.api.twilio-balance") }}', {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                badge.innerText = `Saldo Twilio: ${data.currency} ${data.balance}`;
            } else {
                badge.innerText = "Erro ao carregar saldo";
            }
        } catch (e) {
            badge.innerText = "Erro de rede";
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        fetchTwilioBalance();
    });
</script>
@endpush

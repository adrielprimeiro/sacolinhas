@extends('layouts.app')

@section('title', 'Chat da Transmissão - Ao Vivo')

@section('content')
<style>
    /* ==========================================================================
       ESTILOS EXCLUSIVOS - CHAT DA TRANSMISSÃO (ALTA LEGIBILIDADE & CONTRASTE)
       ========================================================================== */
    .theme-dark {
        --feed-bg: #0b0f19;
        --card-bg: #161f30;
        --card-bg-hover: #1e293b;
        --card-border: #2c3b52;
        --card-marked-bg: #2d1e08;
        --card-marked-border: #f59e0b;
        --text-msg: #ffffff;
        --text-user-insta: #c084fc;
        --text-user-tiktok: #f472b6;
        --text-time: #94a3b8;
        --badge-client-bg: #064e3b;
        --badge-client-text: #a7f3d0;
        --badge-client-border: #059669;
        --topbar-bg: #111827;
        --topbar-border: #1f2937;
    }

    .theme-light {
        --feed-bg: #e2e8f0;
        --card-bg: #ffffff;
        --card-bg-hover: #f8fafc;
        --card-border: #cbd5e1;
        --card-marked-bg: #fef3c7;
        --card-marked-border: #d97706;
        --text-msg: #0f172a;
        --text-user-insta: #7e22ce;
        --text-user-tiktok: #db2777;
        --text-time: #64748b;
        --badge-client-bg: #d1fae5;
        --badge-client-text: #065f46;
        --badge-client-border: #34d399;
        --topbar-bg: #ffffff;
        --topbar-border: #e2e8f0;
    }

    .live-feed-page-wrapper {
        width: 100%;
        max-width: 100% !important;
        margin: 0;
        padding: 0 8px;
        height: calc(100vh - 78px);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    #feed-messages-container {
        background-color: var(--feed-bg);
        transition: background-color 0.2s ease;
    }

    #scan-camera-reader {
        width: 100% !important;
        height: 100% !important;
        position: relative !important;
        overflow: hidden !important;
        border-radius: 0.75rem !important;
        background: #000 !important;
    }
    #scan-camera-reader video {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        border-radius: 0.75rem !important;
    }
    #scan-camera-reader__scan_region {
        width: 100% !important;
        height: 100% !important;
    }
    #scan-camera-reader__scan_region svg,
    #scan-camera-reader__dashboard {
        display: none !important;
    }

    .chat-card {
        background-color: var(--card-bg) !important;
        border: 1.5px solid var(--card-border) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
        transition: transform 0.1s ease, background-color 0.15s ease, border-color 0.15s ease;
        cursor: pointer;
    }

    .chat-card:hover {
        background-color: var(--card-bg-hover) !important;
        border-color: #6366f1 !important;
    }

    /* Mensagem com peça vinculada: linha em azul em destaque na lateral */
    .chat-card.is-linked-msg {
        border-left: 6px solid #2563eb !important;
        border-color: #3b82f6 !important;
        background-color: rgba(37, 99, 235, 0.08) !important;
        box-shadow: 0 4px 15px rgba(37, 99, 235, 0.15) !important;
    }


    .chat-card.marked {
        background-color: var(--card-marked-bg) !important;
        border: 2px solid var(--card-marked-border) !important;
        box-shadow: 0 0 15px rgba(245, 158, 11, 0.25) !important;
    }

    .chat-card.marked.is-linked-msg {
        border-left: 6px solid #2563eb !important;
    }

    .chat-message-text {
        color: var(--text-msg) !important;
        font-weight: 700 !important;
        word-break: break-word;
        line-height: 1.45;
        letter-spacing: -0.01em;
    }

    .chat-user-tiktok {
        color: var(--text-user-tiktok) !important;
        font-weight: 800 !important;
    }

    .chat-user-insta {
        color: var(--text-user-insta) !important;
        font-weight: 800 !important;
    }

    .chat-time-label {
        color: var(--text-time) !important;
        font-family: monospace;
        font-weight: 600;
    }

    .chat-client-badge {
        background-color: var(--badge-client-bg) !important;
        color: var(--badge-client-text) !important;
        border: 1px solid var(--badge-client-border) !important;
        font-weight: 800 !important;
        padding: 2px 8px !important;
        border-radius: 8px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
    }

    .chat-product-code {
        background-color: #4f46e5 !important;
        color: #ffffff !important;
        font-weight: 900 !important;
        padding: 3px 9px !important;
        border-radius: 8px !important;
        border: 1.5px solid #818cf8 !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.25) !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
        margin: 0 2px !important;
    }

    /* Custom Scrollbar */
    #feed-messages-container::-webkit-scrollbar {
        width: 10px;
    }
    #feed-messages-container::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.15);
    }
    #feed-messages-container::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 6px;
    }
    #feed-messages-container::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.35);
    }

    #scan-panel::-webkit-scrollbar,
    #scan-items-list::-webkit-scrollbar {
        width: 6px;
    }
    #scan-panel::-webkit-scrollbar-track,
    #scan-items-list::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.04);
        border-radius: 4px;
    }
    #scan-panel::-webkit-scrollbar-thumb,
    #scan-items-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    #scan-panel::-webkit-scrollbar-thumb:hover,
    #scan-items-list::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>

<div class="w-full max-w-full px-1 sm:px-2 py-1 live-feed-page-wrapper">
    <!-- Top Bar com Controles de Leitura e Filtros (Card 1) -->
    <div id="feed-top-header" class="mb-2 bg-gray-900 text-white p-2.5 sm:p-3 rounded-2xl shadow-lg border border-gray-800 flex flex-col md:flex-row md:items-center md:justify-between gap-2.5 shrink-0">
        <!-- Título & Live Ativa -->
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-lg shadow-md shrink-0">
                <i class="fas fa-comments"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-sm sm:text-base font-black text-white tracking-tight">Chat da Transmissão</h1>
                    @if($activeLive && $activeLive->ativo)
                        <span class="bg-red-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full flex items-center gap-1 shadow animate-pulse">
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span> AO VIVO
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-400 mt-0.5">
                    <span id="chat-total-msgs-badge" class="font-semibold text-gray-300">0 mensagens capturadas</span>
                    <span>&bull;</span>
                    <span id="chat-stream-status" class="text-emerald-400 font-bold flex items-center gap-1">
                        <i class="fas fa-check-circle text-xs"></i> Sincronizando
                    </span>
                </div>
            </div>
        </div>

        <!-- Ferramentas: Zoom de Fonte, Tema e Atalhos -->
        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
            <!-- Controle de Tamanho da Fonte (A- / Normal / A+ / A++) -->
            <div class="flex items-center bg-gray-800 p-1 rounded-xl border border-gray-700 shadow-inner">
                <span class="text-xs text-gray-400 font-bold px-2 hidden sm:inline"><i class="fas fa-font"></i></span>
                <button type="button" onclick="setFontSize('sm')" id="btn-font-sm" class="px-2 py-1 text-xs font-bold rounded-lg text-gray-300 hover:text-white transition cursor-pointer">A-</button>
                <button type="button" onclick="setFontSize('md')" id="btn-font-md" class="px-2 py-1 text-xs font-bold rounded-lg bg-indigo-600 text-white transition shadow cursor-pointer">Normal</button>
                <button type="button" onclick="setFontSize('lg')" id="btn-font-lg" class="px-2 py-1 text-xs font-bold rounded-lg text-gray-300 hover:text-white transition cursor-pointer">A+</button>
                <button type="button" onclick="setFontSize('xl')" id="btn-font-xl" class="px-2 py-1 text-xs font-bold rounded-lg text-gray-300 hover:text-white transition cursor-pointer">A++</button>
            </div>

            <!-- Alternador de Tema Escuro / Claro -->
            <button type="button" onclick="toggleTheme()" id="btn-theme-toggle" class="bg-gray-800 hover:bg-gray-700 text-gray-200 p-2 px-2.5 rounded-xl border border-gray-700 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm">
                <i class="fas fa-moon text-yellow-400" id="theme-icon"></i>
                <span id="theme-text" class="hidden sm:inline">Modo Escuro</span>
            </button>

            <!-- Alternador de Rolagem Automática (Auto-Scroll) -->
            <button type="button" onclick="toggleAutoScroll()" id="btn-autoscroll-toggle" class="bg-emerald-600 hover:bg-emerald-500 text-white p-2 px-3 rounded-xl text-xs font-black transition flex items-center gap-1.5 cursor-pointer shadow-md active:scale-95" title="Ativar ou desativar a rolagem automática para as últimas mensagens">
                <i class="fas fa-arrow-down text-xs" id="autoscroll-icon"></i>
                <span id="autoscroll-text">Rolagem: Ativa</span>
            </button>

            <!-- Alternador de Histórico: 200 Recentes / Todas as Mensagens -->
            <button type="button" onclick="toggleChatHistoryMode()" id="btn-history-mode-toggle" class="bg-gray-800 hover:bg-gray-700 text-gray-200 p-2 px-2.5 rounded-xl border border-gray-700 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm" title="Alternar entre ver 200 mensagens recentes (mais leve e rápido) ou todas as mensagens da live">
                <i class="fas fa-history text-indigo-400" id="history-mode-icon"></i>
                <span id="history-mode-text" class="hidden sm:inline">200 Recentes</span>
            </button>

            <!-- Botão Alternador Painel de Bipagem (Expansão do Chat) -->
            <button type="button" onclick="toggleScanPanel()" id="btn-toggle-scan-panel" class="bg-gray-800 hover:bg-gray-700 text-gray-200 hover:text-white p-2 px-3 rounded-xl border border-gray-700 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm" title="Ocultar ou mostrar o painel lateral de bipagem para expandir a área do chat">
                <i class="fas fa-barcode text-emerald-400"></i>
                <span class="hidden sm:inline" id="scan-panel-btn-text">Ocultar Bipagem</span>
            </button>

            <!-- Botão Ocultar 2 Primeiros Cards -->
            <button type="button" onclick="toggleTopCards()" id="btn-toggle-top-cards" class="bg-gray-800 hover:bg-gray-700 text-gray-200 hover:text-white p-2 px-2.5 rounded-xl border border-gray-700 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm" title="Ocultar os 2 primeiros cards para expandir o chat">
                <i class="fas fa-chevron-up text-indigo-400"></i>
                <span class="hidden sm:inline">Ocultar Topo</span>
            </button>

            <!-- Botão Tela Cheia -->
            <button type="button" onclick="toggleFullScreen()" title="Alternar Tela Cheia" class="bg-gray-800 hover:bg-gray-700 text-gray-200 p-2 px-2.5 rounded-xl border border-gray-700 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm">
                <i class="fas fa-expand"></i>
            </button>

            <!-- Botão Contador (Telão) -->
            <a href="{{ route('admin.live-chat.contador', ['live_id' => $activeLive ? $activeLive->id : '']) }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-500 text-white p-2 px-3 rounded-xl text-xs font-black transition flex items-center gap-1.5 cursor-pointer shadow-md active:scale-95" title="Abrir Contador de Itens Bipados (Telão)">
                <i class="fas fa-calculator text-xs text-emerald-200"></i>
                <span>Contador</span>
            </a>

            @if($activeLive)
                <!-- Central de Cortes da Live -->
                <a href="{{ route('admin.lives.cortes', ['liveId' => $activeLive->id]) }}" target="_blank" class="bg-indigo-600 hover:bg-indigo-500 text-white p-2 px-3 rounded-xl text-xs font-black transition flex items-center gap-1.5 cursor-pointer shadow-md active:scale-95" title="Abrir Fatiador de Vídeos e Cortes da Live">
                    <i class="fas fa-film text-xs text-indigo-200"></i>
                    <span>Cortes & Vídeos</span>
                </a>
            @endif

            <!-- Seletor de Live -->
            <div class="flex items-center gap-2 bg-gray-800 p-1 px-2 rounded-xl border border-gray-700">
                <form action="{{ route('admin.live-chat.feed') }}" method="GET" class="flex gap-2 m-0">
                    <select name="live_id" id="live-select" onchange="this.form.submit()" class="text-xs rounded-lg border-0 bg-transparent text-gray-200 font-bold focus:ring-0 p-1 cursor-pointer">
                        <option value="" class="bg-gray-900 text-white">Selecione uma Live...</option>
                        @foreach($lives as $l)
                            <option value="{{ $l->id }}" {{ ($activeLive && $activeLive->id === $l->id) ? 'selected' : '' }} class="bg-gray-900 text-white">
                                #{{ $l->id }} - {{ $l->tipo_live_formatado }} ({{ $l->data->format('d/m/Y') }}) {{ $l->ativo ? '[ATIVA]' : '' }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros e Busca Rápida (Card 2) -->
    <div id="feed-filter-bar" class="mb-2.5 bg-white p-2.5 px-3 rounded-2xl shadow-sm border border-gray-200 flex flex-wrap items-center justify-between gap-2 shrink-0">
        <!-- Filtros Rápidos -->
        <div class="flex flex-wrap items-center gap-1.5">
            <button type="button" onclick="setFeedFilter('all')" id="filter-btn-all" class="px-3 py-1.5 rounded-xl font-bold text-xs bg-indigo-600 text-white shadow transition cursor-pointer">
                Todas (<span id="count-filter-all">0</span>)
            </button>
            <button type="button" onclick="setFeedFilter('instagram')" id="filter-btn-instagram" class="px-3 py-1.5 rounded-xl font-bold text-xs bg-gray-100 text-purple-700 hover:bg-purple-50 transition border border-gray-200 flex items-center gap-1 cursor-pointer">
                <i class="fab fa-instagram"></i> Instagram (<span id="count-filter-instagram">0</span>)
            </button>
            <button type="button" onclick="setFeedFilter('tiktok')" id="filter-btn-tiktok" class="px-3 py-1.5 rounded-xl font-bold text-xs bg-gray-100 text-pink-600 hover:bg-pink-50 transition border border-gray-200 flex items-center gap-1 cursor-pointer">
                <i class="fab fa-tiktok"></i> TikTok (<span id="count-filter-tiktok">0</span>)
            </button>
            <button type="button" onclick="setFeedFilter('marked')" id="filter-btn-marked" class="px-3 py-1.5 rounded-xl font-bold text-xs bg-gray-100 text-amber-700 hover:bg-amber-50 transition border border-amber-300 flex items-center gap-1 cursor-pointer">
                <i class="fas fa-star text-amber-500"></i> Marcadas (<span id="count-filter-marked">0</span>)
            </button>
            <button type="button" onclick="setFeedFilter('registered')" id="filter-btn-registered" class="px-3 py-1.5 rounded-xl font-bold text-xs bg-gray-100 text-emerald-700 hover:bg-emerald-50 transition border border-emerald-300 flex items-center gap-1 cursor-pointer">
                <i class="fas fa-user-check text-emerald-500"></i> Cadastradas (<span id="count-filter-registered">0</span>)
            </button>

            <!-- Totais das Sacolinhas da Live -->
            <div class="flex items-center gap-1.5 ml-1 px-3 py-1.5 rounded-xl bg-indigo-50 border border-indigo-200 text-xs font-extrabold text-indigo-800 shadow-sm select-none">
                <i class="fas fa-shopping-bag text-indigo-500"></i>
                <span id="sacolinhas-itens-badge">0 itens</span>
                <span class="text-indigo-300 font-light mx-0.5">|</span>
                <i class="fas fa-money-bill-wave text-emerald-500"></i>
                <span id="sacolinhas-valor-badge" class="text-emerald-700">R$ 0,00</span>
            </div>
        </div>

        <!-- Busca / Filtro em Tempo Real -->
        <div class="relative w-full sm:w-72">
            <input type="text" id="feed-search-input" name="live_filter_comments_field" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore="true" oninput="handleSearchChat(this.value)" placeholder="🔍 Filtrar mensagem ou @usuario..." class="w-full p-2 pl-8 pr-7 rounded-xl border border-gray-300 text-xs font-semibold text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50">
            <i class="fas fa-search absolute left-2.5 top-2.5 text-gray-400 text-xs"></i>
            <button type="button" id="feed-search-clear-btn" onclick="clearSearchFilter()" class="hidden absolute right-2.5 top-2 text-gray-400 hover:text-gray-700 p-0.5 cursor-pointer transition" title="Limpar busca">
                <i class="fas fa-times-circle text-xs"></i>
            </button>
        </div>
    </div>


    <!-- Área principal: Feed (1/3) + Painel de Bipagem e Itens (2/3) -->
    <div class="flex flex-col lg:flex-row gap-2.5 flex-1" style="min-height:0;">

        <!-- FEED PRINCIPAL DO CHAT (1/3 DO ESPAÇO DA TELA) -->
        <div id="feed-outer-wrapper" class="theme-dark relative w-full lg:w-1/3 xl:w-1/3 shrink-0 rounded-2xl shadow-2xl overflow-hidden flex flex-col border border-gray-700 min-w-0" style="min-height: 0;">
            <!-- Botão Flutuante para Reexibir os 2 Primeiros Cards (Aparece quando ocultados) -->
            <div id="floating-show-top-cards-btn" class="absolute top-3 right-4 z-40 hidden transition-all">
                <button type="button" onclick="toggleTopCards()" class="bg-gray-900/95 hover:bg-gray-900 text-white font-extrabold px-3.5 py-1.5 rounded-full text-xs shadow-2xl backdrop-blur-md flex items-center gap-1.5 border border-indigo-500/80 cursor-pointer active:scale-95 transition-all hover:scale-105">
                    <i class="fas fa-chevron-down text-indigo-400 animate-bounce"></i>
                    <span>Mostrar Topo / Filtros</span>
                </button>
            </div>

            <!-- Botão Flutuante para Reabrir Painel de Bipagem (quando oculto) -->
            <div id="floating-show-scan-panel-btn" class="absolute top-3 right-52 z-40 hidden transition-all">
                <button type="button" onclick="toggleScanPanel()" class="bg-emerald-600/95 hover:bg-emerald-500 text-white font-extrabold px-3.5 py-1.5 rounded-full text-xs shadow-2xl backdrop-blur-md flex items-center gap-1.5 border border-emerald-300 cursor-pointer active:scale-95 transition-all hover:scale-105">
                    <i class="fas fa-barcode"></i>
                    <span>Abrir Painel de Itens</span>
                </button>
            </div>

            <!-- Container com Scroll -->
            <div id="feed-messages-container" class="flex-1 p-3 sm:p-4 overflow-y-auto space-y-2.5 font-sans" onscroll="handleContainerScroll()">
                @if(!$activeLive)
                    <div class="flex flex-col items-center justify-center h-full text-gray-400 py-16">
                        <i class="fas fa-video-slash text-4xl mb-3 text-gray-500"></i>
                        <h3 class="text-base font-bold text-gray-300">Nenhuma Live Selecionada</h3>
                        <p class="text-xs text-gray-400 mt-1">Selecione uma live no topo para carregar o chat da transmissão.</p>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-full text-gray-400 py-16">
                        <i class="fas fa-spinner fa-spin text-3xl mb-3 text-indigo-500"></i>
                        <p class="text-xs font-semibold text-gray-300">Aguardando comentários da transmissão...</p>
                    </div>
                @endif
            </div>

            <!-- Botão Flutuante de Novas Mensagens / Ir para o Final -->
            <div id="new-messages-floating-badge" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 hidden z-30 transition-all">
                <button type="button" onclick="scrollToBottom(true)" class="bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold px-4 py-2 rounded-full text-xs shadow-2xl flex items-center gap-2 border border-indigo-400 active:scale-95 cursor-pointer animate-bounce">
                    <i class="fas fa-arrow-down"></i>
                    <span>Novas mensagens abaixo</span>
                </button>
            </div>
        </div>

        <!-- ============================================================
             PAINEL PRINCIPAL DE ITENS & BIPAGEM (2/3 DO ESPAÇO DA TELA)
             ============================================================ -->
        <div id="scan-panel" class="flex-1 min-w-0 flex flex-col gap-2 h-full pb-0 overflow-hidden transition-all duration-300" style="min-height:0;">

            <!-- BARRA SUPERIOR: LEITOR DE CÓDIGO DE BARRAS DIRETO COM SEQUÊNCIA AUTOMÁTICA -->
            <div class="bg-white rounded-2xl shadow-sm border border-emerald-200 p-2.5 shrink-0">
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-6 h-6 rounded-lg bg-emerald-600 text-white flex items-center justify-center shadow-sm text-xs shrink-0">
                            <i class="fas fa-barcode"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-black text-gray-900 leading-tight">Leitor de Código de Barras (USB / Físico)</h3>
                            <p class="text-[10px] text-gray-500 font-medium">Bipe as peças em sequência. O código da live é gerado automaticamente.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <div class="flex items-center gap-1.5 px-3 py-1 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-800 text-xs font-black">
                            <i class="fas fa-hashtag text-[10px] text-indigo-500"></i>
                            <span>Próxima Peça: <strong id="next-seq-badge" class="text-indigo-600 text-sm">1</strong></span>
                        </div>
                        <div id="scanner-focus-badge" onclick="ensureScannerFocus(true)" title="Clique para focar no leitor" class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-emerald-100 border border-emerald-300 text-emerald-800 text-[10px] font-black shadow-xs select-none cursor-pointer transition active:scale-95">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                            <span id="scanner-focus-text">LEITOR PRONTO (FOCO ATIVO)</span>
                        </div>
                        <button type="button" onclick="toggleScanPanel()" title="Recolher painel de itens para expandir o chat" class="w-7 h-7 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-400 hover:text-gray-700 transition cursor-pointer text-xs">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Campo de Entrada com Destaque Máximo para o Leitor -->
                <div class="relative">
                    <input type="text" id="scan-manual-input" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true"
                        placeholder="Aguardando bip do leitor... (Código da live automático: #1)"
                        class="w-full pl-9 pr-3 py-2 rounded-xl border-2 border-emerald-400 bg-emerald-50/50 text-sm font-black text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-4 focus:ring-emerald-200 focus:border-emerald-600 uppercase tracking-wider transition-all shadow-inner"
                        oninput="handleManualScanInput(event)"
                        onkeydown="handleManualScan(event)">
                    <i class="fas fa-barcode absolute left-3 top-3 text-emerald-600 text-sm"></i>
                </div>
            </div>

            <!-- Card Principal: Lista de Itens Bipados e Fila de Pedidos do Chat -->
            <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col flex-1 overflow-hidden" style="min-height: 0;">

                <!-- BANNER DE CONFIRMAÇÃO DO ÚLTIMO ITEM BIPADO (FLASH VERDE) -->
                <div id="scan-last-item-banner" class="hidden mx-2 my-1 px-2.5 py-1.5 bg-emerald-600 text-white rounded-xl flex items-center justify-between text-xs font-black shadow-md shrink-0">
                    <div class="flex items-center gap-1.5 truncate">
                        <i class="fas fa-check-circle text-xs text-emerald-200 shrink-0"></i>
                        <span id="scan-last-item-text" class="truncate">Item Bipado!</span>
                    </div>
                    <span id="scan-last-item-time" class="text-[10px] font-semibold text-emerald-100 shrink-0 ml-1"></span>
                </div>

                <!-- BANNER DE ALERTA DE CÓDIGO DA LIVE DUPLICADO (FLASH ÂMBAR/VERMELHO) -->
                <div id="scan-duplicate-warning-banner" class="hidden mx-2 my-1 px-3 py-2 bg-gradient-to-r from-red-600 via-amber-600 to-red-600 text-white rounded-xl flex items-center justify-between text-xs font-black shadow-lg shrink-0 border border-amber-300">
                    <div class="flex items-center gap-2 truncate">
                        <i class="fas fa-exclamation-triangle text-amber-200 text-sm shrink-0 animate-pulse"></i>
                        <div class="truncate">
                            <span class="text-amber-200 uppercase tracking-wider text-[9px] block font-bold leading-none">Código Duplicado na Live!</span>
                            <span id="scan-duplicate-warning-text" class="text-white text-xs font-black truncate block mt-0.5">Código já foi utilizado</span>
                        </div>
                    </div>
                    <button type="button" onclick="dismissDuplicateLiveCodeAlert()" title="Fechar alerta" class="w-6 h-6 flex items-center justify-center rounded-lg bg-black/20 hover:bg-black/40 text-white ml-2 shrink-0 cursor-pointer transition">
                        <i class="fas fa-times text-[10px]"></i>
                    </button>
                </div>

                <!-- Barra de Contador de Itens Bipados -->
                <div class="px-3 py-2 bg-gray-50 border-b border-gray-100 shrink-0 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-700 font-extrabold flex items-center gap-1.5">
                            <i class="fas fa-tags text-indigo-500 text-[11px]"></i>
                            <span>Itens Bipados na Live: <strong id="scan-count" class="text-emerald-700 font-black">0</strong></span>
                        </span>
                    </div>
                </div>

                <!-- Lista de itens bipados com Fila de Pedidos -->
                <div id="scan-items-list" class="flex-1 overflow-y-auto p-2.5 space-y-2" style="min-height: 0;">
                    <div id="scan-empty-state" class="flex flex-col items-center justify-center h-full py-12 text-gray-300">
                        <i class="fas fa-barcode text-4xl mb-2 text-gray-300"></i>
                        <p class="text-xs font-bold text-gray-400">Nenhum item bipado ainda nesta live</p>
                        <p class="text-[10.5px] text-gray-400 mt-0.5 text-center">Bipe as peças com o leitor físico ou digite o código.<br>Os clientes que pediram cada peça aparecerão na fila automaticamente por ordem de chegada!</p>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- fim flex gap row -->

</div>


<!-- MODAL VINCULAR CLIENTE -->
<div id="link-user-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4" style="z-index: 99999;">
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-md w-full p-6 mx-auto transform transition-all duration-300">
        <div class="flex justify-between items-start border-b border-gray-100 pb-3 mb-4">
            <h3 class="text-base font-extrabold text-gray-900 flex items-center gap-2">
                <i class="fas fa-link text-indigo-600"></i>
                <span>Vincular Usuário da Live</span>
            </h3>
            <button onclick="closeLinkModal()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold p-1 leading-none">&times;</button>
        </div>
        
        <p class="text-xs text-gray-500 mb-4 leading-relaxed">
            Associe o usuário <strong id="modal-display-username" class="text-indigo-600 font-extrabold">@usuario</strong> (<span id="modal-display-platform" class="text-gray-800 font-bold uppercase"></span>) a um cliente cadastrado no sistema para salvar as peças na sacola dele.
        </p>
        
        <input type="hidden" id="modal-input-username">
        <input type="hidden" id="modal-input-platform">

        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-700 mb-1">Buscar Cliente por Nome, Apelido ou Celular:</label>
            <input type="text" id="modal-search-input" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" oninput="searchClients(this.value)" onkeydown="handleLinkUserSearchKeydown(event)" placeholder="Digite o nome da cliente..." class="w-full p-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-xs font-semibold bg-gray-50">
        </div>

        <div id="modal-search-results" class="max-h-48 overflow-y-auto space-y-2 border border-gray-100 rounded-xl p-2 bg-gray-50">
            <!-- Resultados Ajax -->
            <p class="text-xs text-gray-400 text-center py-4">Comece a digitar para pesquisar clientes.</p>
        </div>
        
        <div class="mt-5 flex items-center justify-between border-t border-gray-100 pt-4">
            <span class="text-[11px] text-gray-400">Dica: <kbd class="px-1.5 py-0.5 bg-gray-200 rounded text-[10px] font-mono text-gray-700">↑</kbd> <kbd class="px-1.5 py-0.5 bg-gray-200 rounded text-[10px] font-mono text-gray-700">↓</kbd> navega, <kbd class="px-1.5 py-0.5 bg-gray-200 rounded text-[10px] font-mono text-gray-700">Enter</kbd> vincula</span>
            <button onclick="closeLinkModal()" class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition cursor-pointer">Cancelar</button>
        </div>
    </div>
</div>

<!-- MODAL DE BUSCA DE CLIENTE AVULSO PARA ANEXAR À PEÇA -->
<div id="manual-buyer-search-modal" class="fixed inset-0 bg-gray-950/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4" style="z-index: 99999;">
    <div class="relative w-full max-w-lg bg-white dark:bg-gray-900 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-800 overflow-hidden flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="px-5 py-4 bg-gradient-to-r from-indigo-700 via-indigo-800 to-purple-800 text-white flex items-center justify-between shadow-md">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center text-white text-lg shadow-inner">
                    <i class="fas fa-user-tag"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold tracking-tight text-white">Vincular Cliente à Peça</h3>
                    <p id="manual-buyer-modal-item-info" class="text-xs text-indigo-200 font-semibold truncate max-w-xs">Peça selecionada</p>
                </div>
            </div>
            <button type="button" onclick="closeManualBuyerSearchModal()" class="text-indigo-200 hover:text-white p-2 rounded-xl hover:bg-white/10 transition cursor-pointer">
                <i class="fas fa-times text-base"></i>
            </button>
        </div>

        <!-- Campo de Busca -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
            <div class="relative">
                <i class="fas fa-search absolute left-3.5 top-3.5 text-gray-400 text-sm"></i>
                <input type="text" id="manual-buyer-search-input" placeholder="Buscar por Nome, @Instagram, WhatsApp ou CPF..." class="w-full pl-10 pr-10 py-2.5 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-2xl text-xs sm:text-sm font-semibold text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition shadow-xs" autocomplete="off" oninput="handleManualBuyerSearchInput(this.value)" onkeydown="handleManualBuyerSearchKeydown(event)">
                <button type="button" id="manual-buyer-search-clear" onclick="clearManualBuyerSearch()" class="hidden absolute right-3.5 top-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-0.5 cursor-pointer">
                    <i class="fas fa-times-circle text-sm"></i>
                </button>
            </div>
        </div>

        <!-- Lista de Resultados -->
        <div id="manual-buyer-results-container" class="flex-1 overflow-y-auto p-4 space-y-2 min-h-[220px] max-h-[380px]">
            <div class="flex flex-col items-center justify-center py-10 text-gray-400 text-center">
                <i class="fas fa-search text-3xl text-gray-300 dark:text-gray-600 mb-2"></i>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">Digite para buscar uma cliente cadastrada</p>
                <p class="text-[11px] text-gray-400">ou digite o @arroba para vincular diretamente.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-3.5 bg-gray-50 dark:bg-gray-800/80 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between text-xs text-gray-500">
            <span class="text-[11px]">Dica: Use <kbd class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-[10px] font-mono text-gray-700 dark:text-gray-300">↑</kbd> <kbd class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-[10px] font-mono text-gray-700 dark:text-gray-300">↓</kbd> para navegar, <kbd class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-[10px] font-mono text-gray-700 dark:text-gray-300">Enter</kbd> para vincular e <kbd class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-[10px] font-mono text-gray-700 dark:text-gray-300">ESC</kbd> para fechar</span>
            <button type="button" onclick="closeManualBuyerSearchModal()" class="px-4 py-1.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-bold rounded-xl transition cursor-pointer">
                Fechar
            </button>
        </div>
    </div>
</div>

<!-- MODAL LEITOR QR CODE PARA PESSOA ONLINE (TELA INTEIRA / FULLSCREEN) -->
<div id="online-qr-modal" class="fixed inset-0 bg-gray-900 z-50 flex flex-col hidden overflow-hidden" style="z-index: 99999;">
    <!-- Cabeçalho Fullscreen Elegante -->
    <div class="bg-gray-900 border-b border-gray-800 px-4 sm:px-6 py-3.5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 shrink-0 shadow-2xl">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center shrink-0 shadow-inner">
                <i class="fas fa-qrcode text-indigo-400 text-xl animate-pulse"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base sm:text-lg font-black text-white tracking-tight">
                        Bipagem de Produtos / QR Code
                    </h3>
                    <span class="bg-emerald-950 text-emerald-300 border border-emerald-700 px-2 py-0.5 rounded-full font-extrabold uppercase tracking-wider text-[10px]">Ao Vivo</span>
                </div>
                <p class="text-xs text-gray-300 mt-0.5 flex items-center gap-1.5">
                    <span>Adicionando na sacola de:</span>
                    <strong id="online-qr-client-name" class="text-indigo-300 font-black bg-gray-800 px-2 py-0.5 rounded-md border border-gray-700">@usuario</strong>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
            <label class="flex items-center gap-2 bg-gray-800 hover:bg-gray-750 border border-gray-700 px-3 py-1.5 rounded-xl cursor-pointer select-none transition shadow-sm" title="Fechar automaticamente o leitor após bipar a peça">
                <input type="checkbox" id="online-qr-auto-close-toggle" onchange="toggleAutoClosePreference(this.checked)" class="w-4 h-4 text-indigo-600 bg-gray-700 border-gray-600 rounded focus:ring-indigo-500 focus:ring-offset-gray-800 cursor-pointer">
                <span class="text-xs font-bold text-gray-200 flex items-center gap-1.5">
                    <i class="fas fa-magic text-indigo-400 text-[11px]"></i> Fechar após bipar
                </span>
            </label>
            <button type="button" onclick="closeOnlineQrModal()" class="bg-red-600 hover:bg-red-500 text-white font-black px-5 py-2 rounded-xl text-xs transition-all duration-200 flex items-center gap-2 shadow-lg hover:shadow-red-500/20 active:scale-95 cursor-pointer">
                <i class="fas fa-times text-sm"></i> Concluir e Voltar
            </button>
        </div>
    </div>

    <!-- Corpo / Área da Câmera em Tela Inteira -->
    <div class="flex-1 flex flex-col lg:flex-row gap-4 sm:gap-6 p-3 sm:p-6 overflow-hidden mx-auto w-full" style="max-width: 1600px;">
        <!-- Container da Câmera -->
        <div class="flex-1 flex flex-col bg-black rounded-3xl overflow-hidden relative border-2 border-indigo-500/60 shadow-2xl lg:min-h-0" style="min-height: 40vh;">
            <div id="online-qr-reader" class="w-full h-full flex-1"></div>
            
            <div class="absolute bottom-4 left-0 right-0 flex justify-center pointer-events-none z-10 px-4">
                <div class="bg-gray-900/90 backdrop-blur-md px-4 py-2 rounded-full border border-gray-700 text-gray-200 text-xs font-bold flex items-center gap-2 shadow-xl text-center">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                    <i class="fas fa-camera text-indigo-400"></i>
                    <span>Aponte a câmera para a etiqueta ou bipe com o leitor USB</span>
                </div>
            </div>
        </div>

        <!-- Painel Lateral de Controles e Entrada Manual / Feedback -->
        <div class="w-full flex flex-col gap-3.5 shrink-0 overflow-y-auto" style="width: 100%; max-width: 420px;">
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
                    <input type="text" id="online-qr-phone-input" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" placeholder="DDD + Número (ex: 11999999999)" class="flex-1 px-3 py-2 rounded-xl border border-gray-700 bg-gray-800 text-white placeholder-gray-500 text-xs font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <button type="button" onclick="saveClientPhoneFromModal()" id="online-qr-phone-save-btn" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-3 py-2 rounded-xl text-xs transition flex items-center gap-1 shrink-0 cursor-pointer shadow active:scale-95">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
                <p id="online-qr-phone-help" class="text-[10.5px] text-gray-400 leading-tight">
                    O WhatsApp é indispensável para o envio automático da sacola ao encerrar a live.
                </p>
            </div>

            <!-- Box de Entrada Manual / Leitor USB -->
            <div class="bg-gray-900 rounded-3xl p-4 border border-gray-800 shadow-xl flex flex-col gap-2.5">
                <h4 class="text-xs font-extrabold text-white flex items-center gap-2">
                    <i class="fas fa-barcode text-indigo-400 text-sm"></i>
                    <span>Leitor de Código de Barras / Digitação (Enter Automático)</span>
                </h4>
                <div class="relative">
                    <input type="text" id="online-qr-manual-input" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" oninput="handleOnlineQrManualInput(event)" onkeydown="handleOnlineQrKeyDown(event)" placeholder="Aguardando bip ou digite o código..." class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-gray-700 bg-gray-800 text-white placeholder-gray-400 text-sm font-bold focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none shadow-inner">
                    <i class="fas fa-barcode absolute left-3 top-3.5 text-indigo-400 text-sm"></i>
                </div>
            </div>

            <!-- Feedback Visual em Tempo Real -->
            <div id="online-qr-feedback" class="p-4 rounded-2xl text-xs font-bold hidden transition duration-200 border shadow-xl leading-relaxed"></div>

            <!-- Box de Mensagens / Pedidos da Cliente na Live -->
            <div class="bg-gray-900 rounded-3xl p-4 border border-gray-800 shadow-xl flex flex-col gap-2 min-h-[160px] max-h-[240px]">
                <div class="flex items-center justify-between shrink-0">
                    <h4 class="text-xs font-extrabold text-white flex items-center gap-1.5">
                        <i class="fas fa-comment-dots text-indigo-400 text-sm"></i>
                        <span>Comentários da Cliente</span>
                    </h4>
                    <button type="button" id="modal-client-filter-star-btn" onclick="toggleModalFilterMarkedOnly()" class="text-[10px] px-2 py-0.5 rounded-lg font-bold bg-gray-800 text-yellow-400 hover:bg-gray-700 border border-gray-700 transition flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-star text-yellow-400 text-[9px]"></i> <span id="modal-client-marked-count">0</span> Marcadas
                    </button>
                </div>
                <div id="online-qr-client-messages-list" class="flex-1 overflow-y-auto space-y-1.5 pr-1 text-xs">
                    <p class="text-[11px] text-gray-500 text-center py-4">Nenhum comentário desta cliente ainda.</p>
                </div>
                <div class="text-[10px] text-indigo-300 font-medium pt-1 border-t border-gray-800 shrink-0">
                    <i class="fas fa-hand-pointer text-indigo-400"></i> Clique num código de peça para bipar instantaneamente
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<!-- Biblioteca HTML5-QRCode -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>

<script>
    // =========================================================================
    // PARÂMETROS GLOBAIS
    // =========================================================================
    const liveId = "{{ $activeLive ? $activeLive->id : '' }}";
    let allLiveMessages = [];
    let rawOnlineUsers = [];
    let onlineUsersMap = {};
    let currentFilter = 'all'; // 'all' | 'instagram' | 'tiktok' | 'marked' | 'registered'
    let currentSearchTerm = '';
    let currentFontSize = localStorage.getItem('live_chat_font_size') || 'md';
    let currentTheme = localStorage.getItem('live_chat_theme') || 'dark';
    let autoCloseAfterScan = localStorage.getItem('live_chat_auto_close_scan') !== 'false'; // Padrão: true (fechar após bipar ativado)
    let autoScrollEnabled = localStorage.getItem('live_chat_autoscroll_enabled') !== 'false'; // Padrão: true (rolagem automática ativada)
    let lastRenderedHash = "";
    let pollingInterval = null;

    function toggleAutoClosePreference(checked) {
        autoCloseAfterScan = checked;
        localStorage.setItem('live_chat_auto_close_scan', checked ? 'true' : 'false');
    }

    function updateAutoScrollUI() {
        // Botão do Cabeçalho Superior
        const btnTop = document.getElementById("btn-autoscroll-toggle");
        const iconTop = document.getElementById("autoscroll-icon");
        const textTop = document.getElementById("autoscroll-text");

        if (autoScrollEnabled) {
            if (btnTop) {
                btnTop.className = "bg-emerald-600 hover:bg-emerald-500 text-white p-2 px-3.5 rounded-xl text-xs font-black transition flex items-center gap-1.5 cursor-pointer shadow-md active:scale-95 border border-emerald-400";
                if (iconTop) iconTop.className = "fas fa-arrow-down text-xs";
                if (textTop) textTop.textContent = "Rolagem: Ativa";
            }
        } else {
            if (btnTop) {
                btnTop.className = "bg-amber-500 hover:bg-amber-600 text-gray-950 p-2 px-3.5 rounded-xl text-xs font-black transition flex items-center gap-1.5 cursor-pointer shadow-md active:scale-95 border border-amber-300 animate-pulse";
                if (iconTop) iconTop.className = "fas fa-pause text-xs";
                if (textTop) textTop.textContent = "Rolagem: Pausada";
            }
        }
    }

    function toggleAutoScroll() {
        autoScrollEnabled = !autoScrollEnabled;
        localStorage.setItem('live_chat_autoscroll_enabled', autoScrollEnabled ? 'true' : 'false');
        updateAutoScrollUI();
        if (autoScrollEnabled) {
            scrollToBottom(true);
        }
    }

    // =========================================================================
    // OCULTAR / EXIBIR CARDS DO TOPO
    // =========================================================================
    let topCardsHidden = localStorage.getItem('live_chat_top_cards_hidden') === 'true';

    function updateTopCardsUI() {
        const headerEl  = document.getElementById('feed-top-header');
        const filterEl  = document.getElementById('feed-filter-bar');
        const floatBtn  = document.getElementById('floating-show-top-cards-btn');
        const toggleBtn = document.getElementById('btn-toggle-top-cards');

        if (topCardsHidden) {
            if (headerEl)  headerEl.classList.add('hidden');
            if (filterEl)  filterEl.classList.add('hidden');
            if (floatBtn)  floatBtn.classList.remove('hidden');
            if (toggleBtn) {
                toggleBtn.querySelector('i').className = 'fas fa-chevron-down text-indigo-400';
                const span = toggleBtn.querySelector('span');
                if (span) span.textContent = 'Mostrar Cards';
            }
        } else {
            if (headerEl)  headerEl.classList.remove('hidden');
            if (filterEl)  filterEl.classList.remove('hidden');
            if (floatBtn)  floatBtn.classList.add('hidden');
            if (toggleBtn) {
                toggleBtn.querySelector('i').className = 'fas fa-chevron-up text-indigo-400';
                const span = toggleBtn.querySelector('span');
                if (span) span.textContent = 'Ocultar Cards';
            }
        }
    }

    function toggleTopCards() {
        topCardsHidden = !topCardsHidden;
        localStorage.setItem('live_chat_top_cards_hidden', topCardsHidden ? 'true' : 'false');
        updateTopCardsUI();
    }

    let scanPanelHidden = localStorage.getItem('live_chat_scan_panel_hidden') === 'true';

    function updateScanPanelUI() {
        const scanPanel = document.getElementById('scan-panel');
        const floatBtn  = document.getElementById('floating-show-scan-panel-btn');
        const toggleBtn = document.getElementById('btn-toggle-scan-panel');
        const btnText   = document.getElementById('scan-panel-btn-text');
        const feedOuter = document.getElementById('feed-outer-wrapper');

        if (scanPanelHidden) {
            if (scanPanel) scanPanel.classList.add('hidden');
            if (floatBtn)  floatBtn.classList.remove('hidden');
            if (btnText)   btnText.textContent = 'Mostrar Painel de Itens';
            if (toggleBtn) {
                toggleBtn.classList.add('bg-emerald-900/70', 'border-emerald-500/60', 'text-emerald-300');
                toggleBtn.classList.remove('bg-gray-800', 'text-gray-200');
            }
            if (feedOuter) {
                feedOuter.classList.remove('lg:w-1/3', 'xl:w-1/3', 'shrink-0');
                feedOuter.classList.add('w-full', 'flex-1');
            }
        } else {
            if (scanPanel) scanPanel.classList.remove('hidden');
            if (floatBtn)  floatBtn.classList.add('hidden');
            if (btnText)   btnText.textContent = 'Ocultar Painel de Itens';
            if (toggleBtn) {
                toggleBtn.classList.remove('bg-emerald-900/70', 'border-emerald-500/60', 'text-emerald-300');
                toggleBtn.classList.add('bg-gray-800', 'text-gray-200');
            }
            if (feedOuter) {
                feedOuter.classList.remove('flex-1');
                feedOuter.classList.add('w-full', 'lg:w-1/3', 'xl:w-1/3', 'shrink-0');
            }
        }
    }

    function toggleScanPanel() {
        scanPanelHidden = !scanPanelHidden;
        localStorage.setItem('live_chat_scan_panel_hidden', scanPanelHidden ? 'true' : 'false');
        updateScanPanelUI();
    }

    // =========================================================================
    // SISTEMA DE BIPAGEM — CÂMERA (Html5Qrcode) + RECONHECIMENTO DE VOZ
    // =========================================================================
    const initialLinkedLiveItems = @json($linkedLiveItems ?? []);
    const bgScanItems = [];           // [{id, itemId, code, liveCode, buyerUsername, buyerName, buyerUserId, liveMessageId, time, source, productName, productDetails, ...}]
    let bgSpeechRecog = null;
    let bgSpeechActive = false;


    function extractCleanCode(raw) {
        if (!raw) return '';
        let code = String(raw).trim();
        if (code.startsWith('http://') || code.startsWith('https://')) {
            try {
                const url = new URL(code);
                const p = url.searchParams.get('codigo') || url.searchParams.get('c') || url.searchParams.get('code') || url.searchParams.get('item') || url.searchParams.get('id');
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
        return code;
    }

    // =========================================================================
    // =========================================================================
    // GERENCIAMENTO INTELIGENTE DE FOCO DO LEITOR DE CÓDIGO DE BARRAS
    // =========================================================================
    function isUserTypingElsewhere() {
        const el = document.activeElement;
        if (!el) return false;
        if (isModalOpen()) return true;
        const tag = (el.tagName || '').toLowerCase();
        if (tag === 'textarea' || tag === 'select') return true;
        if (tag === 'input') {
            const id = el.id || '';
            if (id === 'scan-manual-input') return false;
            return true;
        }
        return false;
    }

    function isModalOpen() {
        const manualBuyerModal = document.getElementById('manual-buyer-search-modal');
        if (manualBuyerModal && !manualBuyerModal.classList.contains('hidden')) return true;

        const linkModal = document.getElementById('link-user-modal');
        if (linkModal && !linkModal.classList.contains('hidden')) return true;

        const qrModal = document.getElementById('online-qr-modal');
        if (qrModal && !qrModal.classList.contains('hidden')) return true;

        return false;
    }

    function applyLiveCodeAndFocusScanner(code) {
        if (code && String(code).trim()) {
            applySpokenLiveCode(code);
        }
        setTimeout(() => {
            focusScannerInput(true);
        }, 50);
    }

    function focusLiveCodeInput(select = true) {
        // Agora o foco principal é sempre no leitor de código de barras
        focusScannerInput(select);
    }

    function focusScannerInput(select = true) {
        if (isModalOpen()) return;
        const scanInput = document.getElementById('scan-manual-input');
        if (scanInput) {
            if (document.activeElement !== scanInput) {
                scanInput.focus();
            }
            if (select) {
                scanInput.select();
            }
            updateScannerFocusBadge(true);
        }
    }

    function ensureScannerFocus(force = false) {
        if (isModalOpen()) return;
        if (!force && isUserTypingElsewhere()) return;

        const scanInput = document.getElementById('scan-manual-input');
        if (scanInput) {
            if (document.activeElement !== scanInput) {
                scanInput.focus();
            }
            updateScannerFocusBadge(true);
        }
    }

    function updateScannerFocusBadge(isScannerFocused) {
        const badge = document.getElementById('scanner-focus-badge');
        const text = document.getElementById('scanner-focus-text');
        if (!badge || !text) return;

        if (isScannerFocused) {
            badge.className = "flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-emerald-100 border border-emerald-300 text-emerald-800 text-[10px] font-black shadow-xs select-none cursor-pointer transition active:scale-95";
            text.innerHTML = `<span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping mr-1 inline-block"></span>LEITOR PRONTO (FOCO ATIVO)`;
        } else {
            badge.className = "flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-amber-100 border border-amber-300 text-amber-800 text-[10px] font-black shadow-xs select-none cursor-pointer transition active:scale-95";
            text.innerHTML = `<i class="fas fa-mouse-pointer text-[9px] mr-1"></i>CLIQUE PARA FOCAR NO LEITOR`;
        }
    }

    function initScannerFocusEvents() {
        const scanInput = document.getElementById('scan-manual-input');

        if (scanInput) {
            scanInput.addEventListener('focus', () => updateScannerFocusBadge(true));
            scanInput.addEventListener('blur', () => {
                setTimeout(() => {
                    if (!isUserTypingElsewhere()) {
                        updateScannerFocusBadge(false);
                    }
                }, 100);
            });
        }

        // Clicar em áreas neutras (cards, fundo, mensagens) traz o foco de volta para o leitor
        document.addEventListener('click', function(e) {
            const target = e.target;
            if (!target) return;
            const tag = (target.tagName || '').toLowerCase();
            if (tag === 'input' || tag === 'textarea' || tag === 'select' || tag === 'button' || tag === 'a' || target.closest('button') || target.closest('a') || target.closest('input')) {
                return;
            }
            ensureScannerFocus(false);
        });

        // Quando a janela volta a ter foco (ex: trocou de aba ou programa)
        window.addEventListener('focus', function() {
            ensureScannerFocus(false);
        });

        // Foco inicial imediato no leitor de código de barras
        setTimeout(() => {
            ensureScannerFocus(true);
        }, 150);
    }

    /* ---- Reconhecimento de Voz Inteligente (SpeechRecognition) ------------ */
    const ptSpeechUnits = {
        zero: 0, um: 1, uma: 1, dois: 2, duas: 2, tres: 3, quatro: 4, cinco: 5,
        seis: 6, meia: 6, sete: 7, oito: 8, nove: 9, dez: 10, onze: 11, doze: 12,
        treze: 13, quatorze: 14, catorze: 14, quinze: 15, dezesseis: 16, dezasseis: 16,
        dezessete: 17, dezassete: 17, dezoito: 18, dezenove: 19, dezanove: 19
    };
    const ptSpeechTens = {
        vinte: 20, trinta: 30, quarenta: 40, cinquenta: 50, sessenta: 60,
        setenta: 70, oitenta: 80, noventa: 90
    };
    const ptSpeechHundreds = {
        cem: 100, cento: 100, duzentos: 200, duzentas: 200, trezentos: 300, trezentas: 300,
        quatrocentos: 400, quatrocentas: 400, quinhentos: 500, quinhentas: 500,
        seiscentos: 600, seiscentas: 600, setecentos: 700, setecentas: 700,
        oitocentos: 800, oitocentas: 800, novecentos: 900, novecentas: 900
    };

    function parsePortugueseWordsFromTokens(tokens) {
        let startIndex = -1;
        for (let i = 0; i < tokens.length; i++) {
            const w = tokens[i];
            if (ptSpeechUnits[w] !== undefined || ptSpeechTens[w] !== undefined || ptSpeechHundreds[w] !== undefined) {
                startIndex = i;
                break;
            }
        }
        if (startIndex === -1) return null;

        let total = 0;
        let current = 0;
        let matchedAny = false;
        let prefixLetter = "";

        // Se antes do número havia uma letra isolada que NÃO seja verbo ou artigo (ex: 'peca B doze' => B12)
        if (startIndex > 0) {
            const prevTok = tokens[startIndex - 1];
            if (prevTok.length === 1 && /^[a-z]$/i.test(prevTok) && !['e', 'o', 'a'].includes(prevTok.toLowerCase())) {
                prefixLetter = prevTok.toUpperCase();
            }
        }

        for (let i = startIndex; i < tokens.length; i++) {
            const w = tokens[i];
            if (w === 'e') continue;

            if (ptSpeechUnits[w] !== undefined) {
                current += ptSpeechUnits[w];
                matchedAny = true;
            } else if (ptSpeechTens[w] !== undefined) {
                current += ptSpeechTens[w];
                matchedAny = true;
            } else if (ptSpeechHundreds[w] !== undefined) {
                current += ptSpeechHundreds[w];
                matchedAny = true;
            } else if (w === 'mil') {
                if (current === 0) current = 1;
                total += current * 1000;
                current = 0;
                matchedAny = true;
            } else {
                break;
            }
        }

        if (!matchedAny) return null;
        total += current;
        return prefixLetter + total;
    }

    function extractCodeFromSpokenText(transcript) {
        if (!transcript) return null;

        // 1. Remove acentos e qualquer pontuação
        let clean = transcript
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .toLowerCase()
            .replace(/[,.:;!?"'()\[\]{}—–_\-\/]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        if (!clean) return null;

        // OBRIGATÓRIO: Códigos são APENAS o que vem após a palavra "código" ou "cod"
        const triggerMatch = clean.match(/\b(?:codigo|cod)\b/i);
        if (!triggerMatch) return null;

        const triggerIndex = clean.indexOf(triggerMatch[0]);
        let after = clean.slice(triggerIndex + triggerMatch[0].length).trim();
        if (!after) return null;

        const rawTokens = after.split(' ').filter(Boolean);
        if (rawTokens.length === 0) return null;

        // Remove palavras conectoras iniciais (ex: "é", "eh", "o", "a", "de", "da", "do", etc.)
        const leadStopWords = ['e', 'eh', 'o', 'a', 'os', 'as', 'de', 'do', 'da', 'dos', 'das', 'esse', 'essa', 'este', 'esta', 'deste', 'desta', 'desse', 'dessa', 'aqui', 'vai', 'ser', 'sera', 'fica', 'ficou', 'numero', 'num', 'ta', 'foi', 'para', 'pra'];
        let startIndex = 0;
        while (startIndex < rawTokens.length && leadStopWords.includes(rawTokens[startIndex])) {
            startIndex++;
        }
        const tokens = rawTokens.slice(startIndex);
        if (tokens.length === 0) return null;

        // Se após "código" houver um número em dígitos direto (ex: "código 15", "código 203", "código P12")
        const firstToken = tokens[0];
        if (/^[a-z]?\d{1,6}$/i.test(firstToken)) {
            return firstToken.toUpperCase();
        }

        // Se após o gatilho "código" for um número por extenso (ex: "código vinte e cinco", "código dez")
        const isPureNumPhrase = tokens.every(t => t === 'e' || ptSpeechUnits[t] !== undefined || ptSpeechTens[t] !== undefined || ptSpeechHundreds[t] !== undefined || t === 'mil' || /^\d+$/.test(t));
        if (isPureNumPhrase) {
            const wordsNum = parsePortugueseWordsFromTokens(tokens);
            if (wordsNum !== null) {
                return String(wordsNum);
            }
        }

        // Caso contenha número por extenso ou alfanumérico após "código" (ex: "código B doze" => B12)
        const wordsNum = parsePortugueseWordsFromTokens(tokens);
        if (wordsNum !== null) {
            return String(wordsNum);
        }

        // Pega até 3 palavras significativas após a palavra "código"
        const trailingStopWords = ['viu', 'gente', 'meninas', 'meninos', 'pessoal', 'por', 'favor', 'ta', 'ok', 'agora', 'vamos', 'para', 'proximo', 'proxima'];
        const codeWords = [];
        for (let i = 0; i < tokens.length && codeWords.length < 3; i++) {
            const tok = tokens[i];
            if (trailingStopWords.includes(tok)) break;
            codeWords.push(tok.toUpperCase());
        }

        if (codeWords.length > 0) {
            return codeWords.join(' ');
        }

        return null;
    }

    let lastSpokenCode = null;
    let lastSpokenTime = 0;
    let micTranscriptTimer = null;

    function initScanSpeech() {
        const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SR) {
            console.warn('[Scan] SpeechRecognition não suportado neste navegador.');
            updateMicDot(false, 'Voz não suportada neste navegador');
            return;
        }

        if (bgSpeechRecog && bgSpeechActive) return;

        try {
            bgSpeechRecog = new SR();
            bgSpeechRecog.lang = 'pt-BR';
            bgSpeechRecog.continuous = true;
            bgSpeechRecog.interimResults = true;
            bgSpeechRecog.maxAlternatives = 5;

            bgSpeechRecog.onresult = function(event) {
                let currentPreview = '';
                let foundCodeInTurn = null;

                for (let i = event.resultIndex; i < event.results.length; ++i) {
                    const res = event.results[i];
                    if (res[0]) {
                        currentPreview = res[0].transcript;
                    }

                    // Percorre todas as alternativas oferecidas pelo reconhecimento
                    for (let a = 0; a < res.length; a++) {
                        const transcript = res[a].transcript;


                        const detectedCode = extractCodeFromSpokenText(transcript);
                        if (detectedCode) {
                            foundCodeInTurn = detectedCode;
                            break;
                        }
                    }
                    if (foundCodeInTurn) break;
                }

                // Se identificou um código válido
                if (foundCodeInTurn) {
                    const now = Date.now();
                    const liveInput = document.getElementById('scan-live-code');
                    const isDifferentOrEmpty = !liveInput || !liveInput.value || liveInput.value.trim().toUpperCase() !== foundCodeInTurn;

                    if (isDifferentOrEmpty || (now - lastSpokenTime) > 1500) {
                        lastSpokenCode = foundCodeInTurn;
                        lastSpokenTime = now;
                        applySpokenLiveCode(foundCodeInTurn);
                    }
                }

                // Atualiza o feedback visual do que o microfone está ouvindo em tempo real
                if (currentPreview) {
                    showMicTranscript(currentPreview, foundCodeInTurn);
                }
            };

            bgSpeechRecog.onerror = function(e) {
                if (e.error !== 'no-speech') {
                    console.warn('[Scan] Speech error:', e.error);
                    if (e.error === 'not-allowed') {
                        updateMicDot(false, 'Microfone bloqueado: autorize no navegador');
                    }
                }
            };

            bgSpeechRecog.onend = function() {
                // Reinicia continuamente se ativo para manter a escuta sem interrupções
                if (bgSpeechActive) {
                    setTimeout(function() {
                        try {
                            if (bgSpeechActive) bgSpeechRecog.start();
                        } catch(e) {}
                    }, 200);
                }
            };

            bgSpeechRecog.start();
            bgSpeechActive = true;
            updateMicDot(true, 'Microfone super sensível ouvindo...');
            updateScanDevicesBtn();
        } catch(e) {
            console.warn('[Scan] Erro ao iniciar microfone:', e.message);
            updateMicDot(false, 'Microfone: ' + e.message);
            updateScanDevicesBtn();
        }
    }

    function showMicTranscript(text, detectedCode) {
        const preview = document.getElementById('mic-transcript-preview');
        const textEl = document.getElementById('mic-transcript-text');
        if (preview && textEl) {
            if (detectedCode) {
                textEl.innerHTML = `"${escapeHtml(text.trim())}" &rarr; <span class="text-emerald-700 font-extrabold bg-emerald-100 px-1.5 py-0.5 rounded shadow-sm">Código: ${escapeHtml(detectedCode)}</span>`;
            } else {
                textEl.textContent = `"${text.trim()}"`;
            }
            preview.classList.remove('hidden');
            clearTimeout(micTranscriptTimer);
            micTranscriptTimer = setTimeout(() => {
                preview.classList.add('hidden');
            }, 3500);
        }
    }

    function stopScanSpeech() {
        if (bgSpeechRecog) {
            bgSpeechActive = false;
            try { bgSpeechRecog.stop(); } catch(e) {}
            updateMicDot(false, 'Microfone parado');
            updateScanDevicesBtn();
        }
    }

    function updateMicDot(active, label) {
        const dot = document.getElementById('mic-status-dot');
        const lbl = document.getElementById('mic-status-label');
        const badge = document.getElementById('scan-mic-active-badge');
        if (dot) {
            dot.className = `w-2.5 h-2.5 rounded-full ${active ? 'bg-emerald-500 animate-pulse shadow-sm' : 'bg-gray-300'}`;
            dot.title = label || (active ? 'Microfone ativo' : 'Microfone inativo');
        }
        if (lbl) {
            lbl.textContent = active ? 'Ouvindo' : 'Inativo';
            lbl.className = `text-[10px] font-bold ${active ? 'text-emerald-600' : 'text-gray-400'}`;
        }
        if (badge) {
            badge.className = `w-2 h-2 rounded-full ${active ? 'bg-emerald-500' : 'bg-gray-300'}`;
        }
    }

    function flashMicDot() {
        const dot = document.getElementById('mic-status-dot');
        if (!dot) return;
        dot.className = 'w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping';
        setTimeout(() => updateMicDot(bgSpeechActive), 1000);
    }

    const scanProductCache = {};

    function buildProductDetailsHtml(prod) {
        if (!prod) return '';
        const detailParts = [];
        const safePush = (val) => {
            if (val !== null && val !== undefined) {
                const s = String(val).trim();
                if (s !== '' && s.toLowerCase() !== 'null' && s.toLowerCase() !== '&null' && s.toLowerCase() !== '&bnull' && s.toLowerCase() !== '&bull;') {
                    detailParts.push(escapeHtml(s));
                }
            }
        };

        if (prod.description && String(prod.description).trim().toLowerCase() !== 'null' && String(prod.description).trim() !== String(prod.name || '').trim()) {
            safePush(prod.description);
        }
        if (prod.tamanho && String(prod.tamanho).toLowerCase() !== 'null') safePush('Tam: ' + prod.tamanho);
        if (prod.marca && String(prod.marca).toLowerCase() !== 'null') safePush(prod.marca);
        if (prod.cor && String(prod.cor).toLowerCase() !== 'null') safePush(prod.cor);
        if (prod.formatted_price && String(prod.formatted_price).toLowerCase() !== 'null') safePush(prod.formatted_price);

        const detailsText = detailParts.join(' • ');
        const nameHtml = escapeHtml(prod.name || 'Produto');

        return `
            <div class="font-bold text-gray-900 leading-tight text-[11px]">${nameHtml}</div>
            ${detailsText ? `<div class="text-[9.5px] text-gray-500 font-medium leading-tight mt-0.5">${detailsText}</div>` : ''}
        `;
    }

    function checkLiveCodeDuplicate(code, excludeScanId = null, excludeCode = null, excludeItemId = null) {
        if (!code) return null;
        const norm = String(code).trim().toUpperCase();
        if (!norm) return null;
        return bgScanItems.find(i => {
            if (excludeScanId && i.id === excludeScanId) return false;
            if (excludeCode && i.code === excludeCode) return false;
            if (excludeItemId && i.itemId && i.itemId === excludeItemId) return false;
            if (!i.liveCode) return false;
            return String(i.liveCode).trim().toUpperCase() === norm;
        }) || null;
    }

    function triggerDuplicateLiveCodeAlert(code, dupItem) {
        const dupCode = dupItem.code || dupItem.codigo || 'peça';
        playErrorBeep();

        const banner = document.getElementById('scan-duplicate-warning-banner');
        const text = document.getElementById('scan-duplicate-warning-text');
        if (banner && text) {
            text.textContent = `Código "${code}" já foi usado na peça #${dupCode}!`;
            banner.classList.remove('hidden');
            clearTimeout(window._scanDupBannerTimeout);
            window._scanDupBannerTimeout = setTimeout(() => {
                banner.classList.add('hidden');
            }, 8000);
        }

        showToast(`⚠️ Atenção: Código de live "${code}" já foi cadastrado na peça #${dupCode}!`, 'warning');
    }

    function dismissDuplicateLiveCodeAlert() {
        const banner = document.getElementById('scan-duplicate-warning-banner');
        if (banner) banner.classList.add('hidden');
        clearTimeout(window._scanDupBannerTimeout);
    }

    function renderLiveCodeHtml(item) {
        if (item && item.liveCode) {
            const dup = checkLiveCodeDuplicate(item.liveCode, item.id, item.code, item.itemId);
            const dupBadge = dup
                ? `<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 text-[9px] bg-red-100 text-red-700 font-black rounded border border-red-300 ml-1 animate-pulse" title="Código repetido na live! Já usado no item #${escapeHtml(dup.code)}">
                    <i class="fas fa-exclamation-triangle text-[8px] text-red-600"></i> Repetido (Já em #${escapeHtml(dup.code)})
                   </span>`
                : '';

            return `<p class="text-[11px] text-indigo-600 font-extrabold scan-item-live-code flex items-center flex-wrap gap-1">
                <i class="fas fa-tag text-[9px]"></i> <span>${escapeHtml(item.liveCode)}</span>
                <button type="button" onclick="editItemLiveCode('${item.id}')" title="Alterar código da live" class="text-gray-400 hover:text-indigo-600 ml-0.5 p-0.5 cursor-pointer"><i class="fas fa-pen text-[8px]"></i></button>
                ${dupBadge}
            </p>`;
        } else {
            const scanId = item ? item.id : '';
            return `<p class="text-[10px] text-amber-600 font-bold italic scan-item-live-code flex items-center gap-1">
                <i class="fas fa-clock text-[9px]"></i> Aguardando código da live
                <button type="button" onclick="editItemLiveCode('${scanId}')" title="Inserir código da live" class="text-amber-500 hover:text-amber-700 ml-1 p-0.5 cursor-pointer"><i class="fas fa-plus-circle text-[9px]"></i></button>
            </p>`;
        }
    }

    function editItemLiveCode(scanId) {
        const item = bgScanItems.find(x => x.id === scanId);
        if (!item) return;
        const currentCode = item.liveCode || '';
        const novoCod = prompt('Código específico da live para o item ' + item.code + ':', currentCode);
        if (novoCod === null) return;
        const cleanCod = novoCod.trim().toUpperCase();

        if (cleanCod !== '') {
            const dup = checkLiveCodeDuplicate(cleanCod, scanId, item.code, item.itemId);
            if (dup) {
                triggerDuplicateLiveCodeAlert(cleanCod, dup);
                const proceed = confirm(`⚠️ ATENÇÃO: O código da live "${cleanCod}" já está em uso na peça #${dup.code}!\n\nDeseja utilizar este código mesmo assim?`);
                if (!proceed) {
                    return;
                }
            }
        }

        item.liveCode = cleanCod;
        updateItemLiveCodeUI(scanId, cleanCod);
        refreshAllScanItemsLiveCodeUI();
        syncLinkItemToLive(item.itemId || null, item.code, cleanCod);
    }

    function updateItemLiveCodeUI(scanId, liveCode) {
        let itemEl = document.querySelector(`[data-scan-id="${scanId}"]`);
        if (!itemEl) {
            const it = bgScanItems.find(x => x.id === scanId);
            if (it && it.code) itemEl = document.querySelector(`[data-code="${it.code}"]`);
        }
        if (!itemEl) return;
        const it = bgScanItems.find(x => x.id === scanId);
        const wrapper = itemEl.querySelector('.scan-item-live-wrapper');
        if (wrapper) {
            wrapper.innerHTML = renderLiveCodeHtml(it || { id: scanId, liveCode: liveCode });
        }
        updateScanItemBuyerUI(scanId);
    }

    function refreshAllScanItemsLiveCodeUI() {
        bgScanItems.forEach(item => {
            updateItemLiveCodeUI(item.id, item.liveCode);
        });
    }

    function syncLinkItemToLive(itemId, code, liveCode) {
        if (!liveId) {
            console.warn('[Scan] Não há live ativa/selecionada para vincular o item.');
            showToast('⚠️ Selecione uma live no topo para vincular itens!', 'warning');
            return;
        }
        fetch('/admin/live-chat/link-item-live', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                live_id: liveId,
                item_id: itemId || null,
                code: code || null,
                codigo_live: liveCode || ''
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.duplicate_warning) {
                triggerDuplicateLiveCodeAlert(liveCode, data.duplicate_warning);
            }
            if (data.success && data.data && data.data.item_id) {
                const found = bgScanItems.find(x => (code && x.code === code) || (itemId && x.itemId === itemId));
                if (found) {
                    found.itemId = data.data.item_id;
                }
            }
        })
        .catch(err => console.warn('[Scan] Erro ao sincronizar item à live:', err));
    }

    function unlinkItemFromLive(itemId, code) {
        if (!liveId) return;
        fetch('/admin/live-chat/unlink-item-live', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                live_id: liveId,
                item_id: itemId || null,
                code: code || null
            })
        }).catch(err => console.warn('[Scan] Erro ao desvincular item:', err));
    }

    async function fetchAndRenderItemDetails(code, scanId) {
        let prod = scanProductCache[code];
        if (prod === undefined) {
            try {
                const res = await fetch(`/api/items/search?q=${encodeURIComponent(code)}${liveId ? '&live_id=' + encodeURIComponent(liveId) : ''}`);
                const data = await res.json();
                if (data.success && data.data && data.data.length > 0) {
                    const clean = code.replace(/^[#\s]+/, '').trim().toLowerCase();
                    prod = data.data.find(item => 
                        (item.sku && item.sku.toLowerCase() === clean) ||
                        (item.codigo && item.codigo.toLowerCase() === clean) ||
                        String(item.id) === clean
                    ) || (data.data.length === 1 ? data.data[0] : null);
                    scanProductCache[code] = prod;
                } else {
                    scanProductCache[code] = null;
                    prod = null;
                }
            } catch(e) {
                console.warn('[Scan] Erro ao buscar produto:', e);
                prod = null;
            }
        }

        const itemObj = bgScanItems.find(x => x.id === scanId);
        const itemEl = document.querySelector(`[data-scan-id="${scanId}"]`);
        if (!itemEl) return;

        const detailsEl = itemEl.querySelector('.scan-item-details');

        if (prod && prod.id) {
            // Verificar se outro item diferente nesta live já possui este mesmo produto
            const existingInList = bgScanItems.find(x => x.id !== scanId && x.itemId && x.itemId === prod.id);
            if (existingInList) {
                // Remove o item duplicado recém-criado
                const dupIdx = bgScanItems.findIndex(x => x.id === scanId);
                if (dupIdx !== -1) bgScanItems.splice(dupIdx, 1);
                if (itemEl) itemEl.remove();
                updateScanCount();
                if (bgScanItems.length === 0) showScanEmptyState();

                playErrorBeep();
                showToast(`⚠️ A peça #${existingInList.code} já está adicionada nesta live! Não foi duplicada.`, 'warning');

                const banner = document.getElementById('scan-duplicate-warning-banner');
                const bannerText = document.getElementById('scan-duplicate-warning-text');
                if (banner && bannerText) {
                    bannerText.textContent = `A peça #${existingInList.code} já foi bipada nesta live!`;
                    banner.classList.remove('hidden');
                    clearTimeout(window._scanDupBannerTimeout);
                    window._scanDupBannerTimeout = setTimeout(() => {
                        banner.classList.add('hidden');
                    }, 8000);
                }

                // Destaca o item original na lista
                let origEl = document.querySelector(`[data-scan-id="${existingInList.id}"]`);
                if (!origEl && existingInList.code) {
                    origEl = document.querySelector(`[data-code="${existingInList.code}"]`);
                }
                if (origEl) {
                    origEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    origEl.classList.remove('bg-gray-50', 'bg-emerald-50');
                    origEl.classList.add('bg-amber-100', 'border-amber-500', 'ring-4', 'ring-amber-400');
                    setTimeout(() => {
                        origEl.classList.remove('bg-amber-100', 'border-amber-500', 'ring-4', 'ring-amber-400');
                        origEl.classList.add('bg-gray-50');
                    }, 3000);
                }
                return;
            }

            if (itemObj) {
                itemObj.itemId = prod.id;
                itemObj.productName = prod.name;
                itemObj.productDetails = prod.description;
                itemObj.productPrice = prod.formatted_price;
                itemObj.tamanho = prod.tamanho;
                itemObj.marca = prod.marca;
                itemObj.cor = prod.cor;
            }

            if (detailsEl) {
                detailsEl.innerHTML = buildProductDetailsHtml(prod);
            }

            const banner = document.getElementById('scan-last-item-banner');
            const bannerText = document.getElementById('scan-last-item-text');
            if (banner && bannerText && bgScanItems[0] && bgScanItems[0].id === scanId) {
                const liveCode = itemObj ? itemObj.liveCode : '';
                bannerText.textContent = code + (liveCode ? ' • Live: ' + liveCode : '') + ' • ' + prod.name;
            }

            // Sincroniza vinculação do item à live com o banco de dados
            syncLinkItemToLive(prod.id, code, itemObj ? itemObj.liveCode : '');
        } else {
            if (detailsEl) {
                detailsEl.innerHTML = `<span class="text-[10px] text-gray-400 font-medium">Item não cadastrado</span>`;
            }
        }
    }

    function applySpokenLiveCode(code) {
        if (!code) return;
        code = String(code).trim().toUpperCase();

        flashMicDot();

        // 1. Procura na lista de itens bipados o item mais recente sem código de live
        const waitingItem = bgScanItems.find(i => !i.liveCode);

        // Checar duplicidade na live atual
        const dupItem = checkLiveCodeDuplicate(code, waitingItem ? waitingItem.id : null);
        if (dupItem) {
            triggerDuplicateLiveCodeAlert(code, dupItem);
        }

        if (waitingItem) {
            // Associa o código a esse item pendente
            waitingItem.liveCode = code;

            // Atualiza o elemento no DOM com layout limpo e menor
            updateItemLiveCodeUI(waitingItem.id, code);
            refreshAllScanItemsLiveCodeUI();

            let itemEl = document.querySelector('[data-scan-id="' + waitingItem.id + '"]');
            if (!itemEl && waitingItem.code) {
                itemEl = document.querySelector('[data-code="' + waitingItem.code + '"]');
            }
            if (itemEl) {
                if (dupItem) {
                    itemEl.classList.add('bg-amber-100', 'border-amber-500', 'ring-2', 'ring-amber-400');
                    setTimeout(() => {
                        itemEl.classList.remove('bg-amber-100', 'border-amber-500', 'ring-2', 'ring-amber-400');
                    }, 2500);
                } else {
                    // Destaque visual piscando verde para confirmar vinculação normal
                    itemEl.classList.add('bg-emerald-100', 'border-emerald-500', 'ring-2', 'ring-emerald-400');
                    setTimeout(() => {
                        itemEl.classList.remove('bg-emerald-100', 'border-emerald-500', 'ring-2', 'ring-emerald-400');
                    }, 1500);
                }
            }

            // Atualiza banner de confirmação do item se não for duplicado
            const banner = document.getElementById('scan-last-item-banner');
            const bannerText = document.getElementById('scan-last-item-text');
            const bannerTime = document.getElementById('scan-last-item-time');
            if (banner && bannerText && bgScanItems[0] === waitingItem && !dupItem) {
                const prodName = waitingItem.productName ? ' • ' + waitingItem.productName : '';
                bannerText.textContent = waitingItem.code + ' • Live: ' + code + prodName;
                if (bannerTime) bannerTime.textContent = waitingItem.time || '';
                banner.classList.remove('hidden');
                clearTimeout(window._scanBannerTimeout);
                window._scanBannerTimeout = setTimeout(function() {
                    banner.classList.add('hidden');
                }, 3000);
            }

            // Sincroniza com a tabela live_items
            syncLinkItemToLive(waitingItem.itemId, waitingItem.code, code);

            if (!dupItem) {
                playSuccessBeep();
            }

            // Limpa o campo do áudio imediatamente conforme solicitado!
            clearLiveCode();

        } else {
            // Se NÃO houver item sem código, mantém no campo do áudio aguardando a próxima bipagem
            const liveInput = document.getElementById('scan-live-code');
            if (liveInput) {
                liveInput.value = code;

                if (dupItem) {
                    liveInput.classList.add('ring-2', 'ring-amber-500', 'bg-amber-50', 'text-amber-900');
                    setTimeout(() => {
                        liveInput.classList.remove('ring-2', 'ring-amber-500', 'bg-amber-50', 'text-amber-900');
                    }, 2000);
                } else {
                    playSuccessBeep();
                    // Destaque visual pulsante no input (verde esmeralda)
                    liveInput.classList.add('ring-2', 'ring-emerald-500', 'bg-emerald-50', 'text-emerald-900');
                    setTimeout(() => {
                        liveInput.classList.remove('ring-2', 'ring-emerald-500', 'bg-emerald-50', 'text-emerald-900');
                    }, 1200);
                }

                try {
                    liveInput.dispatchEvent(new Event('input', { bubbles: true }));
                    liveInput.dispatchEvent(new Event('change', { bubbles: true }));
                } catch(e) {}
            }
        }
    }

    /* ---- Controle Unificado (Ativar / Desativar Câmera e Voz) ------------- */
    function toggleScanDevices() {
        if (!bgCameraActive || !bgSpeechActive) {
            initScanCamera();
            initScanSpeech();
        } else {
            stopScanCamera();
            stopScanSpeech();
        }
    }

    function updateScanDevicesBtn() {
        const btn = document.getElementById('btn-toggle-scan-devices');
        const text = document.getElementById('scan-devices-btn-text');
        if (!btn) return;

        if (bgCameraActive && bgSpeechActive) {
            btn.className = "text-[10px] font-bold px-2 py-0.5 rounded-lg text-red-600 hover:text-red-700 hover:bg-red-50 transition cursor-pointer";
            if (text) text.textContent = "Desativar Dispositivos";
        } else if (bgCameraActive || bgSpeechActive) {
            btn.className = "text-[10px] font-bold px-2 py-0.5 rounded-lg text-indigo-700 hover:bg-indigo-50 transition cursor-pointer";
            if (text) text.textContent = bgCameraActive ? "+ Ativar Microfone" : "+ Ativar Câmera";
        } else {
            btn.className = "text-[10px] font-bold px-2 py-0.5 rounded-lg text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 transition cursor-pointer font-extrabold";
            if (text) text.textContent = "Ativar Câmera e Voz";
        }
    }

    function escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /**
     * Procura no chat todos os clientes que comentaram o código da live ou código do item.
     * Retorna a fila em ordem cronológica exata de quem pediu primeiro para o último.
     */
    function findAllSpeakersForCode(liveCode, code) {
        if (!allLiveMessages || allLiveMessages.length === 0) return [];

        const targets = [];
        if (liveCode && String(liveCode).trim()) {
            const lc = String(liveCode).trim().toLowerCase();
            if (lc) targets.push(lc);
            if (/^\d+$/.test(lc)) {
                const num = parseInt(lc, 10);
                const rawNum = String(num);
                if (!targets.includes(rawNum)) targets.push(rawNum);
                const paddedNum = rawNum.length === 1 ? '0' + rawNum : rawNum;
                if (!targets.includes(paddedNum)) targets.push(paddedNum);
            }
        }
        if (code && String(code).trim()) {
            const c = String(code).replace(/^[#\s]+/, '').trim().toLowerCase();
            if (c && !targets.includes(c)) targets.push(c);
        }
        if (targets.length === 0) return [];

        const queue = [];
        const seenUsers = new Set();

        for (let i = 0; i < allLiveMessages.length; i++) {
            const msg = allLiveMessages[i];
            if (!msg || !msg.message) continue;
            const cleanUser = (msg.username || 'usuario').trim().toLowerCase();
            if (seenUsers.has(cleanUser)) continue;

            const msgText = msg.message.toLowerCase();
            let matched = false;

            for (const target of targets) {
                const regex = new RegExp('(?:^|[^a-z0-9])' + escapeRegex(target) + '(?:$|[^a-z0-9])', 'i');
                if (regex.test(msgText) || msgText.trim() === target) {
                    matched = true;
                    break;
                }
            }

            if (matched) {
                seenUsers.add(cleanUser);
                const displayName = msg.user_name || msg.user_apelido || msg.username || 'usuario';
                let formattedTime = '';
                try {
                    if (msg.created_at) {
                        formattedTime = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    }
                } catch(e) {}

                queue.push({
                    position: queue.length + 1,
                    id: msg.id,
                    username: msg.username || 'usuario',
                    displayName: displayName,
                    userId: msg.user_id || null,
                    whatsapp: msg.user_whatsapp || '',
                    plataforma: msg.plataforma || 'instagram',
                    text: msg.message,
                    time: formattedTime
                });
            }
        }

        return queue;
    }

    function renderScanItemBuyerHtml(item) {
        const queue = findAllSpeakersForCode(item.liveCode, item.code);

        // CASO 1: A peça JÁ FOI VINCULADA a uma cliente (ou @username da transmissão)
        if (item.buyerUsername) {
            const cleanUser = String(item.buyerUsername).replace(/^@/, '');
            const otherSpeakers = queue.filter(q => q.username.toLowerCase() !== item.buyerUsername.toLowerCase() && q.username.toLowerCase() !== cleanUser.toLowerCase());
            const isRegistered = !!item.buyerUserId;

            return `
                <div class="flex flex-col gap-1 items-start">
                    ${isRegistered ? `
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 border border-emerald-300 rounded-lg shadow-xs text-xs justify-start text-left" title="Cliente cadastrada com sacolinha aberta">
                            <span class="w-4 h-4 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-[9px] shrink-0">
                                <i class="fas fa-check"></i>
                            </span>
                            <span class="font-black text-emerald-950 truncate max-w-[120px]">@${escapeHtml(cleanUser)}</span>
                            ${item.buyerName ? `<span class="text-[10.5px] font-semibold text-emerald-800 truncate max-w-[80px]">(${escapeHtml(item.buyerName)})</span>` : ''}
                            <button type="button" onclick="event.stopPropagation(); unlinkItemBuyerAction('${item.id}')" title="Desvincular cliente" class="text-gray-400 hover:text-red-600 ml-1 text-[10px] cursor-pointer">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    ` : `
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-red-50 border border-red-300 rounded-lg shadow-xs text-xs justify-start text-left" title="Usuário não cadastrado. Aguardando WhatsApp no chat para cadastrar cliente e abrir sacolinha">
                            <span class="w-4 h-4 rounded-full bg-red-600 text-white flex items-center justify-center font-bold text-[9px] shrink-0 animate-pulse">
                                <i class="fas fa-clock"></i>
                            </span>
                            <span class="font-black text-red-950 truncate max-w-[110px]">@${escapeHtml(cleanUser)}</span>
                            <span class="text-[9.5px] font-extrabold bg-red-600 text-white px-1.5 py-0.5 rounded shadow-2xs whitespace-nowrap">Esperando telefone</span>
                            <button type="button" onclick="event.stopPropagation(); unlinkItemBuyerAction('${item.id}')" title="Desvincular" class="text-gray-400 hover:text-red-700 ml-1 text-[10px] cursor-pointer">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `}
                    ${otherSpeakers.length > 0 ? `
                        <div class="flex flex-col gap-1 items-start w-full">
                            ${otherSpeakers.map(q => `
                                <button type="button" onclick="event.stopPropagation(); linkItemToBuyer('${item.id}', '${escapeHtml(q.username)}', '${escapeHtml(q.displayName)}', '${q.userId || ''}', ${q.id})" title="Transferir para ${q.position}º @${escapeHtml(q.username)}: &quot;${escapeHtml(q.text)}&quot; (${q.time})" class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-white hover:bg-amber-50 text-gray-700 hover:text-amber-900 border border-gray-200 hover:border-amber-300 rounded-md text-xs font-semibold transition cursor-pointer shadow-2xs w-full justify-start text-left">
                                    <span class="text-indigo-600 font-bold shrink-0">${q.position}º</span>
                                    <span class="truncate max-w-[120px]">@${escapeHtml(q.username)}</span>
                                </button>
                            `).join('')}
                        </div>
                    ` : ''}
                    <button type="button" onclick="event.stopPropagation(); openManualBuyerSearchModal('${item.id}')" title="Buscar outro cliente para anexar" class="text-[10px] text-gray-400 hover:text-indigo-600 font-bold self-start mt-0.5 px-1.5 py-0.5 hover:bg-gray-100 rounded transition cursor-pointer flex items-center gap-1">
                        <i class="fas fa-search text-[9px]"></i> <span>Avulso</span>
                    </button>
                </div>
            `;
        }

        // CASO 2: A peça AINDA NÃO FOI VINCULADA e existem pedidos detectados no chat
        if (queue.length > 0) {
            return `
                <div class="flex flex-col gap-1 items-start w-full">
                    ${queue.map(q => `
                        <button type="button" onclick="event.stopPropagation(); linkItemToBuyer('${item.id}', '${escapeHtml(q.username)}', '${escapeHtml(q.displayName)}', '${q.userId || ''}', ${q.id})" title="Vincular a ${q.position}º @${escapeHtml(q.username)}: &quot;${escapeHtml(q.text)}&quot; (${q.time})" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white hover:bg-indigo-50 text-gray-800 hover:text-indigo-900 border border-gray-200 hover:border-indigo-400 rounded-lg text-xs font-semibold transition cursor-pointer shadow-xs active:scale-95 w-full justify-start text-left">
                            <span class="text-indigo-600 font-extrabold shrink-0">${q.position}º</span>
                            <span class="font-bold truncate max-w-[130px]">@${escapeHtml(q.username)}</span>
                        </button>
                    `).join('')}
                    <button type="button" onclick="event.stopPropagation(); openManualBuyerSearchModal('${item.id}')" title="Buscar cliente para anexar" class="inline-flex items-center gap-1 px-2.5 py-1 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50/80 border border-dashed border-gray-300 hover:border-indigo-400 rounded-lg text-[11px] font-bold transition cursor-pointer shadow-2xs w-full justify-center">
                        <i class="fas fa-user-plus text-[10px]"></i> <span>Avulso</span>
                    </button>
                </div>
            `;
        }

        // CASO 3: A peça NÃO tem pedidos no chat ainda
        if (item.liveCode) {
            return `
                <div class="flex flex-col gap-1 items-start">
                    <div class="flex items-center gap-1.5 text-gray-400 text-xs justify-start">
                        <i class="fas fa-spinner fa-spin text-indigo-400 text-[10px] shrink-0"></i>
                        <span class="truncate text-[11px]">Aguardando <strong class="text-indigo-600 font-mono">"${escapeHtml(item.liveCode)}"</strong> no chat...</span>
                    </div>
                    <button type="button" onclick="event.stopPropagation(); openManualBuyerSearchModal('${item.id}')" title="Buscar cliente para anexar" class="inline-flex items-center gap-1 px-2 py-0.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50/80 border border-dashed border-gray-300 hover:border-indigo-400 rounded-md text-[11px] font-bold transition cursor-pointer shadow-2xs">
                        <i class="fas fa-user-plus text-[10px]"></i> <span>Avulso</span>
                    </button>
                </div>
            `;
        }

        return `
            <div class="flex flex-col gap-1 items-start">
                <div class="flex items-center gap-1.5 text-amber-700 text-xs justify-start">
                    <i class="fas fa-microphone text-amber-500 text-[10px] shrink-0"></i>
                    <span class="truncate text-[11px]">Defina o código</span>
                </div>
                <button type="button" onclick="event.stopPropagation(); openManualBuyerSearchModal('${item.id}')" title="Buscar cliente para anexar" class="inline-flex items-center gap-1 px-2 py-0.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50/80 border border-dashed border-gray-300 hover:border-indigo-400 rounded-md text-[11px] font-bold transition cursor-pointer shadow-2xs">
                    <i class="fas fa-user-plus text-[10px]"></i> <span>Avulso</span>
                </button>
            </div>
        `;
    }

    /* ---- Modal de Busca de Cliente Avulso ------------------------------- */
    let currentManualBuyerScanId = null;
    let manualBuyerSearchTimeout = null;

    function openManualBuyerSearchModal(scanId) {
        const item = bgScanItems.find(x => x.id === scanId);
        if (!item) return;

        currentManualBuyerScanId = scanId;
        const modal = document.getElementById('manual-buyer-search-modal');
        const input = document.getElementById('manual-buyer-search-input');
        const itemInfo = document.getElementById('manual-buyer-modal-item-info');

        let label = '#' + item.code;
        if (item.liveCode) label += ' • ' + item.liveCode;
        if (item.productName) label += ' (' + item.productName + ')';
        if (itemInfo) itemInfo.textContent = label;

        if (input) {
            input.value = '';
            setTimeout(() => input.focus(), 150);
        }

        renderManualBuyerEmptyState();
        if (modal) modal.classList.remove('hidden');
    }

    function closeManualBuyerSearchModal() {
        const modal = document.getElementById('manual-buyer-search-modal');
        if (modal) modal.classList.add('hidden');
        currentManualBuyerScanId = null;
    }

    function clearManualBuyerSearch() {
        const input = document.getElementById('manual-buyer-search-input');
        const clearBtn = document.getElementById('manual-buyer-search-clear');
        if (input) {
            input.value = '';
            input.focus();
        }
        if (clearBtn) clearBtn.classList.add('hidden');
        renderManualBuyerEmptyState();
    }

    function renderManualBuyerEmptyState() {
        const container = document.getElementById('manual-buyer-results-container');
        if (!container) return;
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center py-10 text-gray-400 text-center">
                <i class="fas fa-search text-3xl text-gray-300 dark:text-gray-600 mb-2"></i>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">Digite para buscar uma cliente cadastrada</p>
                <p class="text-[11px] text-gray-400">ou digite o @arroba para vincular diretamente.</p>
            </div>
        `;
    }

    let manualBuyerSelectedIndex = 0;

    function handleManualBuyerSearchKeydown(e) {
        e.stopPropagation();
        const container = document.getElementById('manual-buyer-results-container');
        if (!container) return;

        const items = container.querySelectorAll('.manual-buyer-result-item');

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (items.length === 0) return;
            manualBuyerSelectedIndex = (manualBuyerSelectedIndex + 1) % items.length;
            updateManualBuyerSelectedHighlight();
            return;
        }

        if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (items.length === 0) return;
            manualBuyerSelectedIndex = (manualBuyerSelectedIndex - 1 + items.length) % items.length;
            updateManualBuyerSelectedHighlight();
            return;
        }

        if (e.key === 'Enter') {
            e.preventDefault();
            if (items.length > 0) {
                const targetIdx = (manualBuyerSelectedIndex >= 0 && manualBuyerSelectedIndex < items.length) ? manualBuyerSelectedIndex : 0;
                items[targetIdx].click();
                return;
            }
            const directBtn = container.querySelector('.manual-buyer-fallback-btn');
            if (directBtn) {
                directBtn.click();
                return;
            }
        }

        if (e.key === 'Escape' || e.key === 'Esc') {
            e.preventDefault();
            closeManualBuyerSearchModal();
        }
    }

    function updateManualBuyerSelectedHighlight() {
        const container = document.getElementById('manual-buyer-results-container');
        if (!container) return;
        const items = container.querySelectorAll('.manual-buyer-result-item');
        items.forEach((item, idx) => {
            if (idx === manualBuyerSelectedIndex) {
                item.classList.add('ring-2', 'ring-indigo-500', 'bg-indigo-50', 'dark:bg-indigo-950/60', 'border-indigo-400');
                item.classList.remove('bg-white', 'dark:bg-gray-800', 'border-gray-200', 'dark:border-gray-700');
                item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                item.classList.remove('ring-2', 'ring-indigo-500', 'bg-indigo-50', 'dark:bg-indigo-950/60', 'border-indigo-400');
                item.classList.add('bg-white', 'dark:bg-gray-800', 'border-gray-200', 'dark:border-gray-700');
            }
        });
    }

    function handleManualBuyerSearchInput(val) {
        const clearBtn = document.getElementById('manual-buyer-search-clear');
        if (clearBtn) {
            if (val.trim()) clearBtn.classList.remove('hidden');
            else clearBtn.classList.add('hidden');
        }

        clearTimeout(manualBuyerSearchTimeout);
        const query = val.trim();
        if (!query) {
            renderManualBuyerEmptyState();
            return;
        }

        manualBuyerSearchTimeout = setTimeout(() => {
            executeManualBuyerSearch(query);
        }, 200);
    }

    async function executeManualBuyerSearch(query) {
        const container = document.getElementById('manual-buyer-results-container');
        if (!container) return;

        container.innerHTML = `
            <div class="flex items-center justify-center py-10 text-indigo-500">
                <i class="fas fa-spinner fa-spin text-2xl mr-2"></i>
                <span class="text-xs font-bold text-gray-500">Buscando clientes...</span>
            </div>
        `;

        try {
            const response = await fetch(`/api/users/search?q=${encodeURIComponent(query)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const json = await response.json();
            const users = (json && json.success && Array.isArray(json.data)) ? json.data : [];

            let html = '';
            if (users.length > 0) {
                users.forEach(u => {
                    const username = u.instagram || u.tiktok || u.name || ('cliente_' + u.id);
                    const displayName = u.name || u.apelido || username;
                    const initials = (u.name || username).slice(0, 2).toUpperCase();
                    const cleanPhone = u.whatsapp || u.phone || '';
                    const userPayload = encodeURIComponent(JSON.stringify({
                        id: u.id,
                        username: username,
                        displayName: displayName
                    }));

                    html += `
                        <div onclick="handleSelectManualBuyerClick(this)" data-user="${userPayload}" class="manual-buyer-result-item p-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/60 border border-gray-200 dark:border-gray-700 rounded-2xl flex items-center justify-between cursor-pointer transition shadow-xs group">
                            <div class="flex items-center gap-2.5 min-w-0 pointer-events-none">
                                <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-xs font-black shrink-0 shadow-sm">
                                    ${escapeHtml(initials)}
                                </div>
                                <div class="truncate">
                                    <div class="text-xs font-bold text-gray-900 dark:text-gray-100 flex items-center gap-1.5">
                                        <span class="truncate">${escapeHtml(displayName)}</span>
                                        ${u.instagram ? `<span class="text-indigo-600 dark:text-indigo-400 text-[11px] font-semibold">@${escapeHtml(u.instagram)}</span>` : ''}
                                    </div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 flex items-center gap-2 mt-0.5">
                                        ${cleanPhone ? `<span><i class="fab fa-whatsapp text-emerald-500"></i> ${escapeHtml(cleanPhone)}</span>` : ''}
                                        ${u.cpf ? `<span>CPF: ${escapeHtml(u.cpf)}</span>` : ''}
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="px-3 py-1 bg-gray-100 dark:bg-gray-700 group-hover:bg-indigo-600 group-hover:text-white text-gray-700 dark:text-gray-200 text-xs font-bold rounded-xl shrink-0 transition pointer-events-none">
                                Vincular
                            </button>
                        </div>
                    `;
                });
            } else {
                const cleanUserTerm = query.trim().replace(/^@/, '');
                html = `
                    <div class="flex flex-col items-center justify-center py-8 text-gray-400 text-center">
                        <i class="fas fa-user-slash text-2xl text-gray-300 dark:text-gray-600 mb-2"></i>
                        <p class="text-xs font-bold text-gray-600 dark:text-gray-300">Nenhum cliente cadastrado encontrado com "${escapeHtml(query)}"</p>
                        <p class="text-[11px] text-gray-400 mt-0.5 mb-3">Deseja vincular diretamente como novo comprador?</p>
                        <button type="button" onclick="selectManualBuyerUser(null, '${escapeHtml(cleanUserTerm)}', '${escapeHtml(query)}')" class="manual-buyer-fallback-btn px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-user-plus"></i> Vincular @${escapeHtml(cleanUserTerm)} à Peça
                        </button>
                    </div>
                `;
            }

            container.innerHTML = html;
            manualBuyerSelectedIndex = 0;
            updateManualBuyerSelectedHighlight();
        } catch(e) {
            console.error('[ManualBuyer] Erro na busca:', e);
            const cleanUserTerm = query.trim().replace(/^@/, '');
            container.innerHTML = `
                <div class="text-center py-6 text-red-500 text-xs font-bold">
                    <p class="mb-2">Erro ao conectar com a busca de clientes.</p>
                    <button type="button" onclick="selectManualBuyerUser(null, '${escapeHtml(cleanUserTerm)}', '${escapeHtml(query)}')" class="manual-buyer-fallback-btn px-3 py-1.5 bg-indigo-600 text-white font-bold rounded-xl text-xs shadow transition">
                        Vincular @${escapeHtml(cleanUserTerm)} diretamente
                    </button>
                </div>
            `;
            manualBuyerSelectedIndex = 0;
        }
    }

    function handleSelectManualBuyerClick(el) {
        try {
            const raw = el.getAttribute('data-user');
            if (!raw) return;
            const data = JSON.parse(decodeURIComponent(raw));
            selectManualBuyerUser(data.id, data.username, data.displayName);
        } catch(err) {
            console.error('[ManualBuyer] Erro ao selecionar usuário:', err);
        }
    }

    function selectManualBuyerUser(userId, username, displayName) {
        if (!currentManualBuyerScanId) return;
        const scanId = currentManualBuyerScanId;
        closeManualBuyerSearchModal();
        linkItemToBuyer(scanId, username, displayName, userId, null);
    }

    // Fechar modal ao pressionar tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' || e.key === 'Esc') {
            closeManualBuyerSearchModal();
        }
    });

    function updateScanItemBuyerUI(scanId) {
        const itemEl = document.querySelector(`[data-scan-id="${scanId}"]`);
        if (!itemEl) return;
        const wrapper = itemEl.querySelector('.scan-item-buyer-wrapper');
        if (!wrapper) return;
        const item = bgScanItems.find(x => x.id === scanId);
        if (!item) return;
        wrapper.innerHTML = renderScanItemBuyerHtml(item);
    }

    function refreshAllScanItemsBuyerUI() {
        bgScanItems.forEach(item => {
            updateScanItemBuyerUI(item.id);
        });
    }

    async function linkItemToBuyer(scanId, username, displayName, userId, msgId) {
        const item = bgScanItems.find(x => x.id === scanId);
        if (!item) return;

        item.buyerUsername = username;
        item.buyerName = displayName || '';
        item.buyerUserId = userId || null;
        item.liveMessageId = msgId || null;

        updateScanItemBuyerUI(scanId);

        if (msgId) {
            const msg = allLiveMessages.find(m => m.id === msgId);
            if (msg) {
                msg.linked_item_id = item.itemId;
                msg.linked_code = item.code;
                msg.linked_live_code = item.liveCode || null;
                msg.linked_product_name = item.productName || '';
                msg.linked_product_details = item.productDetails || '';
                msg.linked_product_tamanho = item.tamanho || '';
                msg.linked_product_cor = item.cor || '';
                msg.linked_product_preco = item.productPrice || '';
                renderChatFeed();
            }
        }

        playSuccessBeep();

        const banner = document.getElementById('scan-last-item-banner');
        const bannerText = document.getElementById('scan-last-item-text');
        if (banner && bannerText) {
            bannerText.textContent = `Peça #${item.code} vinculada a @${username}!`;
            banner.classList.remove('hidden');
            clearTimeout(window._scanBannerTimeout);
            window._scanBannerTimeout = setTimeout(() => banner.classList.add('hidden'), 3500);
        }

        if (liveId && (item.itemId || item.code)) {
            try {
                const resp = await fetch('/admin/live-chat/link-item-buyer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        live_id: liveId,
                        item_id: item.itemId || null,
                        code: item.code || null,
                        username: username,
                        buyer_name: displayName || '',
                        user_id: userId || null,
                        message_id: msgId || null
                    })
                });
                const resData = await resp.json();
                if (resData && resData.data) {
                    if (resData.data.item_id) {
                        item.itemId = resData.data.item_id;
                        const el = document.querySelector(`[data-scan-id="${scanId}"]`);
                        if (el) el.dataset.itemId = resData.data.item_id;
                    }
                    if (resData.data.user_id !== undefined) {
                        item.buyerUserId = resData.data.user_id;
                    }
                    if (resData.data.buyer_name) {
                        item.buyerName = resData.data.buyer_name;
                    }
                    updateScanItemBuyerUI(scanId);
                }
            } catch(e) {
                console.warn('[Scan] Erro ao vincular comprador no servidor:', e);
            }
        }
    }

    async function unlinkItemBuyerAction(scanId) {
        const item = bgScanItems.find(x => x.id === scanId);
        if (!item) return;

        const oldMsgId = item.liveMessageId;
        const itemId = item.itemId;
        const code = item.code;

        item.buyerUsername = null;
        item.buyerName = null;
        item.buyerUserId = null;
        item.liveMessageId = null;

        updateScanItemBuyerUI(scanId);

        if (oldMsgId) {
            const msg = allLiveMessages.find(m => m.id === oldMsgId);
            if (msg) {
                msg.linked_item_id = null;
                msg.linked_code = null;
                msg.linked_live_code = null;
                msg.linked_product_name = null;
                msg.linked_product_details = null;
                msg.linked_product_tamanho = null;
                msg.linked_product_cor = null;
                msg.linked_product_preco = null;
                renderChatFeed();
            }
        }

        if (liveId && (itemId || code || oldMsgId)) {
            try {
                await fetch('/admin/live-chat/unlink-item-buyer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        live_id: liveId,
                        item_id: itemId || null,
                        code: code || null,
                        message_id: oldMsgId || null
                    })
                });
            } catch(e) {
                console.warn('[Scan] Erro ao desvincular comprador no servidor:', e);
            }
        }
    }

    async function unlinkItemBuyerByMsgId(msgId) {
        const item = bgScanItems.find(x => x.liveMessageId === msgId);
        if (item) {
            unlinkItemBuyerAction(item.id);
            return;
        }
        const msg = allLiveMessages.find(m => m.id === msgId);
        if (msg) {
            msg.linked_item_id = null;
            msg.linked_code = null;
            msg.linked_live_code = null;
            msg.linked_product_name = null;
            msg.linked_product_details = null;
            msg.linked_product_tamanho = null;
            msg.linked_product_cor = null;
            msg.linked_product_preco = null;
            renderChatFeed();
        }
        if (liveId) {
            try {
                await fetch('/admin/live-chat/unlink-item-buyer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        live_id: liveId,
                        message_id: msgId
                    })
                });
            } catch(e) {}
        }
    }

    /* ---- Lista de Itens Bipados ------------------------------------------ */
    function createScanItemElement(item, isNew) {
        const el = document.createElement('div');
        const isDuplicate = item.liveCode && checkLiveCodeDuplicate(item.liveCode, item.id, item.code, item.itemId);
        el.className = isNew
            ? (isDuplicate
                ? 'p-3 bg-amber-50 border-2 border-amber-400 ring-2 ring-amber-300 rounded-2xl group transition-all duration-500 scan-item shadow-sm'
                : 'p-3 bg-emerald-50 border-2 border-emerald-400 ring-2 ring-emerald-300 rounded-2xl group transition-all duration-500 scan-item shadow-sm')
            : (isDuplicate
                ? 'p-3 bg-red-50/40 border border-red-200 rounded-2xl group hover:border-red-400 hover:bg-red-50/60 transition scan-item shadow-xs'
                : 'p-3 bg-white border border-gray-200 rounded-2xl group hover:border-indigo-300 hover:shadow-md transition scan-item shadow-xs');
        el.dataset.code = item.code;
        el.dataset.scanId = item.id;
        if (item.itemId) el.dataset.itemId = item.itemId;

        let detailsInitial = '<span class="text-gray-400 italic text-[10px]"><i class="fas fa-spinner fa-spin text-[9px] mr-1"></i> Carregando produto...</span>';
        if (item.productName) {
            const fakeProd = {
                name: item.productName,
                description: item.productDetails,
                tamanho: item.tamanho,
                marca: item.marca,
                cor: item.cor,
                formatted_price: item.productPrice
            };
            detailsInitial = buildProductDetailsHtml(fakeProd);
        }

        el.innerHTML =
            '<div class="flex items-center justify-between gap-3">' +
                '<div class="flex-1 min-w-0 scan-item-info cursor-pointer">' +
                    '<div class="flex items-center gap-2 flex-wrap">' +
                        '<span class="text-xs font-black text-gray-900 font-mono tracking-wider bg-gray-100 px-2.5 py-0.5 rounded-lg border border-gray-200 shadow-2xs">#' + escapeHtml(item.code) + '</span>' +
                        '<div class="scan-item-live-wrapper inline-block">' +
                            renderLiveCodeHtml(item) +
                        '</div>' +
                    '</div>' +
                    '<div class="scan-item-details text-[11px] text-gray-600 leading-snug mt-1">' +
                        detailsInitial +
                    '</div>' +
                '</div>' +
                '<div class="scan-item-buyer-wrapper flex items-center justify-end gap-1.5 shrink-0">' +
                    renderScanItemBuyerHtml(item) +
                '</div>' +
                '<button type="button" onclick="event.stopPropagation(); removeScanItem(this)" title="Remover item da live" ' +
                    'class="w-6 h-6 flex items-center justify-center rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition cursor-pointer shrink-0">' +
                    '<i class="fas fa-trash-alt text-[10px]"></i>' +
                '</button>' +
            '</div>';

        if (isNew) {
            setTimeout(function() {
                const stillDup = item.liveCode && checkLiveCodeDuplicate(item.liveCode, item.id, item.code, item.itemId);
                el.className = stillDup
                    ? 'p-3 bg-red-50/40 border border-red-200 rounded-2xl group hover:border-red-400 hover:bg-red-50/60 transition scan-item shadow-xs'
                    : 'p-3 bg-white border border-gray-200 rounded-2xl group hover:border-indigo-300 hover:shadow-md transition scan-item shadow-xs';
            }, 2500);
        }

        return el;
    }

    function findExistingScanItem(code) {
        if (!code) return null;
        const clean = String(code).replace(/^[#\s]+/, '').trim().toUpperCase();
        if (!clean) return null;

        // 1. Procura diretamente na lista por código exato
        let found = bgScanItems.find(i => 
            (i.code && String(i.code).trim().toUpperCase() === clean)
        );
        if (found) return found;

        // 2. Se temos cache desse produto com id válido, checa se esse itemId já existe na lista
        const cached = scanProductCache[clean] || scanProductCache[code];
        if (cached && cached.id) {
            found = bgScanItems.find(i => 
                (i.itemId && i.itemId === cached.id)
            );
            if (found) return found;
        }

        return null;
    }

    // Retorna o próximo número sequencial da live (1, 2, 3...)
    function getNextSequentialLiveCode() {
        let maxNum = 0;
        for (const item of bgScanItems) {
            if (item && item.liveCode) {
                const parsed = parseInt(String(item.liveCode).trim(), 10);
                if (!isNaN(parsed) && parsed > maxNum) {
                    maxNum = parsed;
                }
            }
        }
        return maxNum > 0 ? String(maxNum + 1) : String(bgScanItems.length + 1);
    }

    function updateNextSeqBadge() {
        const badge = document.getElementById('next-seq-badge');
        const input = document.getElementById('scan-manual-input');
        const nextSeq = getNextSequentialLiveCode();
        if (badge) badge.textContent = nextSeq;
        if (input) {
            input.placeholder = `Aguardando bip do leitor... (Código da live automático: #${nextSeq})`;
        }
    }

    let lastProcessedScanCode = null;
    let lastProcessedScanTime = 0;

    function addScanItem(code, source) {
        source = source || 'manual';
        const cleanCode = extractCleanCode(code).toUpperCase();
        if (!cleanCode) return;

        const nowMs = Date.now();
        // Previne disparo duplo do mesmo código pelo leitor físico USB em rajada rápida
        if (cleanCode === lastProcessedScanCode && (nowMs - lastProcessedScanTime < 1000)) {
            return;
        }
        lastProcessedScanCode = cleanCode;
        lastProcessedScanTime = nowMs;

        // Se o item já existe na lista desta live:
        const existingItem = findExistingScanItem(cleanCode);
        if (existingItem) {
            // Destaca visualmente a peça na lista
            let existingEl = document.querySelector(`[data-scan-id="${existingItem.id}"]`);
            if (!existingEl && existingItem.code) {
                existingEl = document.querySelector(`[data-code="${existingItem.code}"]`);
            }
            if (existingEl) {
                existingEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                existingEl.classList.add('ring-4', 'ring-indigo-400');
                setTimeout(() => {
                    existingEl.classList.remove('ring-4', 'ring-indigo-400');
                }, 2000);
            }

            const manualInput = document.getElementById('scan-manual-input');
            if (manualInput) manualInput.value = '';

            playSuccessBeep();
            setTimeout(() => ensureScannerFocus(true), 50);
            return;
        }

        const emptyState = document.getElementById('scan-empty-state');
        if (emptyState) emptyState.remove();

        const now = new Date();
        const timeStr = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const autoLiveCode = getNextSequentialLiveCode();
        const scanUniqueId = 'scan_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);

        const item = {
            id: scanUniqueId,
            code: cleanCode,
            liveCode: autoLiveCode,
            buyerUserId: null,
            buyerUsername: null,
            buyerName: null,
            liveMessageId: null,
            videoCutPath: null,
            videoCutFilename: null,
            videoCutDuration: null,
            videoCutStatus: null,
            videoCutStartedAt: null,
            videoCutFinishedAt: null,
            videoCutTrigger: null,
            time: timeStr,
            timestamp: Date.now(),
            source: source,
            productName: '',
            productDetails: '',
            productPrice: ''
        };
        bgScanItems.unshift(item);

        const list = document.getElementById('scan-items-list');
        const el = createScanItemElement(item, true);

        if (list) {
            list.prepend(el);
            list.scrollTop = 0; // Rola a lista automaticamente para o topo
        }

        refreshAllScanItemsLiveCodeUI();

        // Busca assíncrona dos dados do produto para exibir Descrição e Detalhes
        fetchAndRenderItemDetails(cleanCode, scanUniqueId);

        // Já sincroniza com a tabela live_items
        syncLinkItemToLive(null, cleanCode, item.liveCode);

        // Exibe o banner de confirmação com destaque no topo
        const banner = document.getElementById('scan-last-item-banner');
        const bannerText = document.getElementById('scan-last-item-text');
        const bannerTime = document.getElementById('scan-last-item-time');
        if (banner && bannerText) {
            bannerText.textContent = `${cleanCode} • Código da Live: #${item.liveCode}`;
            if (bannerTime) bannerTime.textContent = timeStr;
            banner.classList.remove('hidden');
            clearTimeout(window._scanBannerTimeout);
            window._scanBannerTimeout = setTimeout(function() {
                banner.classList.add('hidden');
            }, 3000);
        }
        playSuccessBeep();

        updateScanCount();

        // Foco garantido e contínuo no leitor de código de barras
        setTimeout(() => {
            ensureScannerFocus(true);
        }, 50);
    }

    function removeScanItem(btn) {
        const el = btn.closest('.scan-item');
        const scanId = el ? el.dataset.scanId : null;
        const code = el ? el.dataset.code : null;
        let itemObj = null;

        if (scanId) {
            const idx = bgScanItems.findIndex(x => x.id === scanId);
            if (idx !== -1) {
                itemObj = bgScanItems[idx];
                bgScanItems.splice(idx, 1);
            }
        } else if (code) {
            const idx = bgScanItems.findIndex(x => x.code === code);
            if (idx !== -1) {
                itemObj = bgScanItems[idx];
                bgScanItems.splice(idx, 1);
            }
        }
        if (el) el.remove();
        updateScanCount();
        if (bgScanItems.length === 0) showScanEmptyState();

        if (itemObj) {
            refreshAllScanItemsLiveCodeUI();
            unlinkItemFromLive(itemObj.itemId || null, itemObj.code);
        }
    }

    function clearScanList() {
        bgScanItems.length = 0;
        const list = document.getElementById('scan-items-list');
        if (list) list.innerHTML = '';
        showScanEmptyState();
        updateScanCount();
    }

    function loadInitialLinkedLiveItems() {
        if (window._initialLiveItemsLoaded) return;
        if (!Array.isArray(initialLinkedLiveItems) || initialLinkedLiveItems.length === 0) return;
        window._initialLiveItemsLoaded = true;
        const emptyState = document.getElementById('scan-empty-state');
        if (emptyState) emptyState.remove();
        const list = document.getElementById('scan-items-list');

        const seenKeys = new Set();
        initialLinkedLiveItems.forEach(item => {
            const key = (item.itemId ? 'id_' + item.itemId : '') || (item.code ? 'code_' + String(item.code).trim().toUpperCase() : '');
            if (key && seenKeys.has(key)) return;
            if (key) seenKeys.add(key);

            // Popula cache de produto com os itens já existentes
            if (item.code && item.itemId) {
                scanProductCache[String(item.code).trim().toUpperCase()] = {
                    id: item.itemId,
                    codigo: item.code,
                    name: item.productName || '',
                    description: item.productDetails || '',
                    formatted_price: item.productPrice || ''
                };
            }

            bgScanItems.push(item);
            if (list) {
                list.appendChild(createScanItemElement(item, false));
            }
        });
        refreshAllScanItemsLiveCodeUI();
        updateScanCount();
    }

    function showScanEmptyState() {
        const list = document.getElementById('scan-items-list');
        if (list && list.querySelector('.scan-item')) return;
        if (list) {
            list.innerHTML =
                '<div id="scan-empty-state" class="flex flex-col items-center justify-center h-full py-8 text-gray-300">' +
                    '<i class="fas fa-barcode text-4xl mb-2 text-gray-300"></i>' +
                    '<p class="text-xs font-semibold text-gray-400">Nenhum item bipado ainda</p>' +
                    '<p class="text-[10px] text-gray-400 mt-1 text-center">Aponte a câmera para o código<br>ou use leitor USB / teclado</p>' +
                '</div>';
        }
    }

    function updateScanCount() {
        const el = document.getElementById('scan-count');
        if (el) el.textContent = bgScanItems.length;
        if (typeof updateNextSeqBadge === 'function') {
            updateNextSeqBadge();
        }
    }

    function clearLiveCode() {
        const el = document.getElementById('scan-live-code');
        if (el) el.value = '';
        lastSpokenCode = null;
        lastSpokenTime = 0;
    }

    let manualScanDebounceTimer = null;

    function handleManualScanInput(event) {
        // Digitação manual aguarda a tecla Enter no handleManualScan
        if (manualScanDebounceTimer) clearTimeout(manualScanDebounceTimer);
    }

    function handleManualScan(event) {
        if (event.key === 'Enter' || event.key === 'Tab') {
            event.preventDefault();
            if (manualScanDebounceTimer) clearTimeout(manualScanDebounceTimer);
            addManualCode();
        }
    }

    function addManualCode() {
        if (manualScanDebounceTimer) {
            clearTimeout(manualScanDebounceTimer);
            manualScanDebounceTimer = null;
        }
        if (usbScanTimer) {
            clearTimeout(usbScanTimer);
            usbScanTimer = null;
        }
        usbScanBuffer = "";
        const input = document.getElementById('scan-manual-input');
        if (!input) return;
        const rawVal = input.value;
        input.value = '';
        const code = extractCleanCode(rawVal).toUpperCase();
        if (!code) {
            focusLiveCodeInput(true);
            return;
        }
        addScanItem(code, 'manual');
    }

    // =========================================================================
    // LISTENER GLOBAL PARA LEITORES DE CÓDIGO DE BARRAS USB (HARDWARE SCANNER)
    // =========================================================================
    let usbScanBuffer = "";
    let usbLastKeyTime = 0;
    let usbScanTimer = null;
    const USB_MAX_INTERVAL_MS = 75; // Leitores físicos USB disparam teclas em < 50ms

    window.addEventListener('keydown', function(event) {
        const activeEl = document.activeElement;
        const activeTag = activeEl ? (activeEl.tagName || '').toLowerCase() : '';
        const isEditingOtherText = activeEl && (
            activeEl.id !== 'scan-manual-input' &&
            activeEl.id !== 'online-qr-manual-input' &&
            (
                activeTag === 'input' || 
                activeTag === 'textarea' || 
                activeTag === 'select' || 
                activeEl.isContentEditable ||
                Boolean(activeEl.closest && (activeEl.closest('#manual-buyer-search-modal') || activeEl.closest('#link-user-modal')))
            )
        );

        if (isEditingOtherText) {
            usbScanBuffer = "";
            return;
        }

        const isOurManualInput = document.activeElement && (
            document.activeElement.id === 'scan-manual-input' ||
            document.activeElement.id === 'online-qr-manual-input'
        );

        const now = Date.now();
        const diff = now - usbLastKeyTime;

        if (event.key === 'Enter' || event.key === 'Tab') {
            if (usbScanTimer) clearTimeout(usbScanTimer);
            if (usbScanBuffer.length >= 1) {
                event.preventDefault();
                event.stopPropagation();
                const cleanCode = extractCleanCode(usbScanBuffer);
                if (cleanCode) {
                    const onlineModal = document.getElementById('online-qr-modal');
                    if (onlineModal && !onlineModal.classList.contains('hidden')) {
                        handleOnlineQrScan(cleanCode);
                    } else {
                        addScanItem(cleanCode, 'usb');
                    }
                    const manualInput = document.getElementById('scan-manual-input');
                    if (manualInput) manualInput.value = '';
                }
                usbScanBuffer = "";
                return;
            }
            if (isOurManualInput) {
                event.preventDefault();
                if (document.activeElement.id === 'online-qr-manual-input') {
                    const onlineInput = document.getElementById('online-qr-manual-input');
                    if (onlineInput && onlineInput.value.trim()) {
                        const code = onlineInput.value.trim();
                        onlineInput.value = '';
                        handleOnlineQrScan(code);
                    }
                } else {
                    addManualCode();
                }
                return;
            }
            usbScanBuffer = "";
            return;
        }

        if (diff > 120 && !isOurManualInput) {
            usbScanBuffer = "";
        }

        if (event.key.length === 1 && !event.ctrlKey && !event.altKey && !event.metaKey) {
            usbScanBuffer += event.key;
            usbLastKeyTime = now;

            // Debounce automático caso o leitor físico USB não envie a tecla Enter ao final
            if (usbScanTimer) clearTimeout(usbScanTimer);
            usbScanTimer = setTimeout(() => {
                if (usbScanBuffer.length >= 1 && (Date.now() - usbLastKeyTime >= 120)) {
                    const cleanCode = extractCleanCode(usbScanBuffer);
                    if (cleanCode) {
                        const onlineModal = document.getElementById('online-qr-modal');
                        if (onlineModal && !onlineModal.classList.contains('hidden')) {
                            handleOnlineQrScan(cleanCode);
                        } else {
                            addScanItem(cleanCode, 'usb');
                        }
                        const manualInput = document.getElementById('scan-manual-input');
                        if (manualInput) manualInput.value = '';
                    }
                    usbScanBuffer = "";
                }
            }, 130);
        }
    }, true);




    function initScanSystem() {
        loadInitialLinkedLiveItems();
        initScanSpeech();
        initScannerFocusEvents();
    }

    // Paleta de cores vibrantes para avatares sem foto
    const avatarGradientPalette = [
        'linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)', // Indigo / Purple
        'linear-gradient(135deg, #059669 0%, #0d9488 100%)', // Emerald / Teal
        'linear-gradient(135deg, #e11d48 0%, #db2777 100%)', // Rose / Pink
        'linear-gradient(135deg, #d97706 0%, #ea580c 100%)', // Amber / Orange
        'linear-gradient(135deg, #0284c7 0%, #2563eb 100%)', // Sky / Blue
        'linear-gradient(135deg, #9333ea 0%, #c026d3 100%)', // Purple / Fuchsia
        'linear-gradient(135deg, #0891b2 0%, #059669 100%)'  // Cyan / Emerald
    ];

    function getGradientForUser(username) {
        if (!username) return avatarGradientPalette[0];
        let hash = 0;
        for (let i = 0; i < username.length; i++) {
            hash = username.charCodeAt(i) + ((hash << 5) - hash);
        }
        const index = Math.abs(hash) % avatarGradientPalette.length;
        return avatarGradientPalette[index];
    }

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


    document.addEventListener("DOMContentLoaded", function() {
        // Limpar qualquer preenchimento automático indevido do navegador no campo de busca do chat
        const searchInput = document.getElementById("feed-search-input");
        if (searchInput && (searchInput.value.includes('@') || searchInput.value.includes('.com'))) {
            searchInput.value = '';
            currentSearchTerm = '';
        }

        applyFontSize(currentFontSize);
        applyTheme(currentTheme);
        updateAutoScrollUI();
        updateTopCardsUI();
        updateScanPanelUI();
        loadInitialLinkedLiveItems();

        if (liveId) {
            fetchChatFeed();
            pollingInterval = setInterval(fetchChatFeed, 2000);
        }

        // Sistema de bipagem inicia após 1s (isolado para não travar a página)
        setTimeout(function() {
            try { initScanSystem(); } catch(e) { console.warn('[Scan] Erro ao iniciar:', e); }
        }, 1000);
    });

    // =========================================================================
    // POLLING EM TEMPO REAL & CONTROLE DE HISTÓRICO (200 vs TODAS)
    // =========================================================================
    let chatHistoryMode = 'recent'; // 'recent' (200) ou 'all' (todas até 5000)
    let isFetchingChatFeed = false;

    function toggleChatHistoryMode() {
        chatHistoryMode = (chatHistoryMode === 'recent') ? 'all' : 'recent';
        const btn = document.getElementById('btn-history-mode-toggle');
        const text = document.getElementById('history-mode-text');
        const icon = document.getElementById('history-mode-icon');
        if (chatHistoryMode === 'all') {
            if (btn) btn.className = "bg-indigo-600 hover:bg-indigo-500 text-white p-2 px-2.5 rounded-xl text-xs font-black transition flex items-center gap-1.5 cursor-pointer shadow-md";
            if (text) text.textContent = "Todas as Mensagens";
            if (icon) icon.className = "fas fa-infinity text-yellow-300";
            showToast("Carregando todas as mensagens da live...", "info");
        } else {
            if (btn) btn.className = "bg-gray-800 hover:bg-gray-700 text-gray-200 p-2 px-2.5 rounded-xl border border-gray-700 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm";
            if (text) text.textContent = "200 Recentes";
            if (icon) icon.className = "fas fa-history text-indigo-400";
            showToast("Modo 200 mensagens recentes ativado (mais rápido)", "info");
        }
        fetchChatFeed(true);
    }

    function fetchChatFeed(force) {
        if (!liveId || (isFetchingChatFeed && !force)) return;
        isFetchingChatFeed = true;

        const limitQuery = chatHistoryMode === 'all' ? 'all' : '200';
        fetch(`/admin/lives/${liveId}/chat-data?limit=${limitQuery}`)
            .then(res => res.json())
            .then(data => {
                isFetchingChatFeed = false;
                if (data.success) {
                    allLiveMessages = data.messages || [];
                    rawOnlineUsers = data.online_users || [];
                    
                    onlineUsersMap = {};
                    rawOnlineUsers.forEach(u => {
                        if (u.user_id) onlineUsersMap[u.user_id] = u;
                        if (u.username) onlineUsersMap[u.username.toLowerCase()] = u;
                    });

                    // Sincronizar itens da live em tempo real (ex: quando auto-cadastrar por telefone detectado)
                    if (data.live_items && Array.isArray(data.live_items)) {
                        data.live_items.forEach(serverItem => {
                            const localItem = bgScanItems.find(i => 
                                (i.itemId && serverItem.item_id && i.itemId === serverItem.item_id) ||
                                (i.code && serverItem.code && i.code.trim().toUpperCase() === serverItem.code.trim().toUpperCase())
                            );
                            if (localItem) {
                                if (serverItem.item_id && !localItem.itemId) localItem.itemId = serverItem.item_id;
                                localItem.buyerUserId = serverItem.buyer_user_id || null;
                                if (serverItem.buyer_username) localItem.buyerUsername = serverItem.buyer_username;
                                if (serverItem.buyer_name) localItem.buyerName = serverItem.buyer_name;
                            }
                        });
                    }

                    updateFilterCounts(data.stats);
                    renderChatFeed();
                    refreshAllScanItemsBuyerUI();

                    // Se o modal de bipe estiver aberto, atualizar mensagens da cliente
                    if (currentOnlineQrUser && !document.getElementById("online-qr-modal").classList.contains("hidden")) {
                        renderModalClientMessages(currentOnlineQrUser.username);
                    }
                }
            })
            .catch(err => {
                isFetchingChatFeed = false;
                console.error("Erro no polling do chat:", err);
                const statusEl = document.getElementById("chat-stream-status");
                if (statusEl) {
                    statusEl.className = "text-amber-400 font-bold flex items-center gap-1";
                    statusEl.innerHTML = `<i class="fas fa-exclamation-circle text-xs"></i> Reconectando...`;
                }
            });
    }

    function updateFilterCounts(stats) {
        const total = stats ? stats.total_messages : allLiveMessages.length;
        const insta = stats ? stats.total_instagram : allLiveMessages.filter(m => m.plataforma === 'instagram').length;
        const tiktok = stats ? stats.total_tiktok : allLiveMessages.filter(m => m.plataforma === 'tiktok').length;
        const marked = stats ? stats.total_marked : allLiveMessages.filter(m => !!m.is_marked).length;
        const reg = stats ? stats.total_registered : allLiveMessages.filter(m => !!m.user_id).length;

        const totalBadge = document.getElementById("chat-total-msgs-badge");
        if (totalBadge) totalBadge.textContent = `${total} mensagens capturadas`;
        
        const countAll = document.getElementById("count-filter-all");
        if (countAll) countAll.textContent = total;
        
        const countInsta = document.getElementById("count-filter-instagram");
        if (countInsta) countInsta.textContent = insta;
        
        const countTiktok = document.getElementById("count-filter-tiktok");
        if (countTiktok) countTiktok.textContent = tiktok;
        
        const countMarked = document.getElementById("count-filter-marked");
        if (countMarked) countMarked.textContent = marked;
        
        const countReg = document.getElementById("count-filter-registered");
        if (countReg) countReg.textContent = reg;

        const statusEl = document.getElementById("chat-stream-status");
        if (statusEl) {
            statusEl.className = "text-emerald-400 font-bold flex items-center gap-1";
            statusEl.innerHTML = `<i class="fas fa-check-circle text-xs"></i> Ao Vivo (${total})`;
        }

        // Atualizar totais das sacolinhas
        if (stats) {
            const itensEl = document.getElementById("sacolinhas-itens-badge");
            const valorEl = document.getElementById("sacolinhas-valor-badge");
            if (itensEl) {
                const qtd = stats.sacolinhas_itens ?? 0;
                itensEl.textContent = `${qtd} ${qtd === 1 ? 'item' : 'itens'}`;
            }
            if (valorEl) {
                const valor = parseFloat(stats.sacolinhas_valor ?? 0);
                valorEl.textContent = valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            }
        }
    }

    // =========================================================================
    // RENDERIZAÇÃO DO FEED COM DESIGN ULTRA LEGÍVEL & FOTOS DE AVATAR
    // =========================================================================
    function setFeedFilter(filter) {
        currentFilter = filter;
        const buttons = {
            all: document.getElementById("filter-btn-all"),
            instagram: document.getElementById("filter-btn-instagram"),
            tiktok: document.getElementById("filter-btn-tiktok"),
            marked: document.getElementById("filter-btn-marked"),
            registered: document.getElementById("filter-btn-registered")
        };

        Object.keys(buttons).forEach(key => {
            const btn = buttons[key];
            if (!btn) return;
            if (key === filter) {
                btn.className = "px-3 py-1.5 rounded-xl font-bold text-xs bg-indigo-600 text-white shadow transition cursor-pointer";
            } else {
                btn.className = "px-3 py-1.5 rounded-xl font-bold text-xs bg-gray-100 text-gray-700 hover:bg-gray-200 transition border border-gray-200 flex items-center gap-1 cursor-pointer";
            }
        });

        renderChatFeed();
    }

    function handleSearchChat(val) {
        currentSearchTerm = (val || '').trim().toLowerCase();
        const clearBtn = document.getElementById("feed-search-clear-btn");
        if (clearBtn) {
            if (currentSearchTerm) {
                clearBtn.classList.remove("hidden");
            } else {
                clearBtn.classList.add("hidden");
            }
        }
        renderChatFeed();
    }

    function clearSearchFilter() {
        const input = document.getElementById("feed-search-input");
        if (input) input.value = '';
        currentSearchTerm = '';
        const clearBtn = document.getElementById("feed-search-clear-btn");
        if (clearBtn) clearBtn.classList.add("hidden");
        renderChatFeed();
    }

    function renderChatFeed() {
        const container = document.getElementById("feed-messages-container");
        if (!container) return;

        // Prevenção contra Autofill indevido do navegador com e-mail do admin (ex: adrielprimeiro@gmail.com)
        if (currentSearchTerm && currentSearchTerm.includes('@') && (currentSearchTerm.includes('.com') || currentSearchTerm.includes('.br'))) {
            const searchInput = document.getElementById("feed-search-input");
            if (searchInput) searchInput.value = '';
            currentSearchTerm = '';
            const clearBtn = document.getElementById("feed-search-clear-btn");
            if (clearBtn) clearBtn.classList.add("hidden");
        }

        let list = allLiveMessages;

        // 1. Filtragem por Aba
        if (currentFilter === 'instagram') {
            list = list.filter(m => m.plataforma === 'instagram');
        } else if (currentFilter === 'tiktok') {
            list = list.filter(m => m.plataforma === 'tiktok');
        } else if (currentFilter === 'marked') {
            list = list.filter(m => !!m.is_marked);
        } else if (currentFilter === 'registered') {
            list = list.filter(m => !!m.user_id);
        }

        // 2. Filtragem por Busca de Texto
        if (currentSearchTerm) {
            list = list.filter(m => 
                (m.username && m.username.toLowerCase().includes(currentSearchTerm)) ||
                (m.message && m.message.toLowerCase().includes(currentSearchTerm)) ||
                (m.user_name && m.user_name.toLowerCase().includes(currentSearchTerm)) ||
                (m.user_apelido && m.user_apelido.toLowerCase().includes(currentSearchTerm))
            );
        }

        const avatarHash = list.map(m => (m.avatar_url ? m.avatar_url.length : 0)).join(',');
        const linkedHash = list.map(m => m.linked_code || '').join(',');
        const currentHash = `${currentFilter}:${currentSearchTerm}:${list.length}:${list.length > 0 ? list[list.length - 1].id : 0}:${list.filter(m => m.is_marked).length}:${avatarHash}:${currentFontSize}:${currentTheme}:${linkedHash}`;
        if (currentHash === lastRenderedHash && container.innerHTML.trim().length > 50) {
            return; // Sem alterações
        }
        lastRenderedHash = currentHash;

        if (list.length === 0) {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full text-gray-400 py-16">
                    <i class="fas ${currentFilter === 'marked' ? 'fa-star text-amber-500' : 'fa-comment-slash'} text-4xl mb-3"></i>
                    <h3 class="text-sm font-bold text-gray-300">Nenhum comentário encontrado</h3>
                    <p class="text-xs text-gray-400 mt-1">${currentSearchTerm ? 'Nenhum resultado para "' + escapeHtml(currentSearchTerm) + '"' : 'Aguardando novas mensagens...'}</p>
                    ${currentSearchTerm ? `
                        <button type="button" onclick="clearSearchFilter()" class="mt-4 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-md cursor-pointer active:scale-95">
                            <i class="fas fa-times-circle"></i> Limpar filtro de pesquisa
                        </button>
                    ` : ''}
                </div>
            `;
            return;
        }

        // Tamanhos de fonte e espaçamento dinâmicos
        let fontSizes = {
            avatar: "52px",
            initials: "17px",
            username: "15px",
            message: "17px",
            badge: "11px",
            time: "12px",
            padding: "14px 16px"
        };

        if (currentFontSize === 'sm') {
            fontSizes = {
                avatar: "42px",
                initials: "14px",
                username: "13px",
                message: "14px",
                badge: "10px",
                time: "11px",
                padding: "10px 12px"
            };
        } else if (currentFontSize === 'lg') {
            fontSizes = {
                avatar: "62px",
                initials: "20px",
                username: "18px",
                message: "21px",
                badge: "12px",
                time: "13px",
                padding: "16px 20px"
            };
        } else if (currentFontSize === 'xl') {
            fontSizes = {
                avatar: "74px",
                initials: "25px",
                username: "21px",
                message: "26px",
                badge: "14px",
                time: "14px",
                padding: "20px 24px"
            };
        }

        let html = '';
        list.forEach(msg => {
            const isTikTok = msg.plataforma === 'tiktok';
            const platformIcon = isTikTok 
                ? '<i class="fab fa-tiktok" style="color: #ff0050;"></i>' 
                : '<i class="fab fa-instagram" style="color: #ffffff;"></i>';
            const platformBadgeBg = isTikTok ? '#000000' : 'linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%)';

            const cleanUser = msg.username || 'usuario';
            const displayName = msg.user_name || msg.user_apelido || cleanUser;
            const initials = cleanUser.slice(0, 2).toUpperCase();
            const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const isMarked = !!msg.is_marked;
            const isLinkedMsg = !!(msg.linked_code || msg.linked_item_id);
            const cardSelectionClass = isLinkedMsg ? 'is-linked-msg' : '';

            // Foto de perfil com avatar grande e nítido (Prioridade: Foto Real -> Persona Ilustrada -> Iniciais em Gradiente)
            const gradientBg = getGradientForUser(cleanUser);
            const illustratedAvatar = `https://api.dicebear.com/7.x/lorelei/svg?seed=${encodeURIComponent(cleanUser)}&backgroundColor=b6e3f4,c0aede,d1d4f9,ffd5dc,ffdfbf`;

            const avatarHtml = msg.avatar_url
                ? `<img src="${safeAttr(msg.avatar_url)}" referrerpolicy="no-referrer" loading="lazy" onerror="this.onerror=null;this.src='${illustratedAvatar}';" style="width: ${fontSizes.avatar}; height: ${fontSizes.avatar}; border-radius: 16px; object-fit: cover; border: 2px solid rgba(255,255,255,0.25); box-shadow: 0 4px 8px rgba(0,0,0,0.25); flex-shrink: 0;" /><div style="display: none; width: ${fontSizes.avatar}; height: ${fontSizes.avatar}; border-radius: 16px; background: ${gradientBg}; align-items: center; justify-content: center; font-weight: 900; font-size: ${fontSizes.initials}; color: #ffffff; box-shadow: 0 4px 8px rgba(0,0,0,0.25); flex-shrink: 0;">${initials}</div>`
                : `<img src="${illustratedAvatar}" referrerpolicy="no-referrer" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" style="width: ${fontSizes.avatar}; height: ${fontSizes.avatar}; border-radius: 16px; object-fit: cover; border: 2px solid rgba(255,255,255,0.25); box-shadow: 0 4px 8px rgba(0,0,0,0.25); flex-shrink: 0;" /><div style="display: none; width: ${fontSizes.avatar}; height: ${fontSizes.avatar}; border-radius: 16px; background: ${gradientBg}; align-items: center; justify-content: center; font-weight: 900; font-size: ${fontSizes.initials}; color: #ffffff; box-shadow: 0 4px 8px rgba(0,0,0,0.25); flex-shrink: 0;">${initials}</div>`;

            // Badge de Cliente Cadastrada
            let clientBadge = '';
            if (msg.user_id) {
                let nameText = escapeHtml(msg.user_name || '');
                if (msg.user_apelido) nameText = `${nameText} (${escapeHtml(msg.user_apelido)})`;
                clientBadge = `
                    <span class="chat-client-badge" style="font-size: ${fontSizes.badge};">
                        <i class="fas fa-check-circle"></i> ${nameText || 'Cliente Cadastrada'}
                    </span>
                `;
            }

            // WhatsApp Badge se disponível
            let whatsappBadge = '';
            if (msg.user_whatsapp) {
                whatsappBadge = `
                    <span class="chat-client-badge" style="background-color: #064e3b; color: #34d399; font-size: ${fontSizes.badge};" title="WhatsApp: ${escapeHtml(msg.user_whatsapp)}">
                        <i class="fab fa-whatsapp"></i> ${escapeHtml(msg.user_whatsapp)}
                    </span>
                `;
            }

            // Destacar códigos de produtos na mensagem
            let formattedMessage = escapeHtml(msg.message || '');
            formattedMessage = formattedMessage.replace(/\b([a-zA-Z0-9]{3,6})\b/g, function(match, code) {
                if (/\d/.test(code)) {
                    return `<span class="chat-product-code"><i class="fas fa-tag" style="font-size: 0.85em;"></i> ${code}</span>`;
                }
                return code;
            });

            // Códigos limpos da peça vinculada (sem a palavra 'Live:')
            let cleanItemCode = '';
            let cleanLiveCode = '';
            if (msg.linked_code) {
                const liveMatch = String(msg.linked_code).match(/\(Live:\s*([^)]+)\)/i);
                if (liveMatch) {
                    cleanLiveCode = liveMatch[1].trim();
                } else if (msg.linked_live_code) {
                    cleanLiveCode = String(msg.linked_live_code).trim();
                }
                cleanItemCode = String(msg.linked_code).replace(/\s*\(Live:.*?\)/gi, '').replace(/^#/, '').trim();
            } else if (msg.linked_live_code) {
                cleanLiveCode = String(msg.linked_live_code).trim();
            }

            // Tag de Peça Vinculada (se houver)
            let vincularBtn = '';
            if (isLinkedMsg) {
                let codeDisplay = cleanItemCode ? ('#' + escapeHtml(cleanItemCode)) : '';
                if (cleanLiveCode) {
                    codeDisplay = codeDisplay ? (codeDisplay + ' &bull; ' + escapeHtml(cleanLiveCode)) : escapeHtml(cleanLiveCode);
                }
                vincularBtn = `
                    <span class="inline-flex items-center gap-1.5 text-xs font-black text-blue-200 bg-blue-900/80 border border-blue-500/60 px-2.5 py-1 rounded-xl shadow-sm tracking-wide" title="Peça vinculada a este comentário">
                        <i class="fas fa-tag text-[10px] text-blue-400"></i>
                        <span>${codeDisplay || 'Vinculado'}</span>
                    </span>
                `;
            }

            // Em baixo em azul: Descrição e Detalhes do Produto Vinculado
            let linkedItemBadge = '';
            if (isLinkedMsg) {
                let prodName = msg.linked_product_name || '';
                let prodDetails = msg.linked_product_details || '';
                let prodTam = msg.linked_product_tamanho || '';
                let prodCor = msg.linked_product_cor || '';
                let prodPrice = msg.linked_product_preco || '';

                // Fallback para buscar de bgScanItems se os dados ainda não vieram do polling
                if (!prodName) {
                    const scanObj = bgScanItems.find(i => 
                        (i.itemId && msg.linked_item_id && i.itemId === msg.linked_item_id) ||
                        (i.code && cleanItemCode && i.code.trim().toUpperCase() === cleanItemCode.toUpperCase())
                    );
                    if (scanObj) {
                        prodName = scanObj.productName || '';
                        prodDetails = scanObj.productDetails || '';
                        prodTam = scanObj.tamanho || '';
                        prodCor = scanObj.cor || '';
                        prodPrice = scanObj.productPrice || '';
                    }
                }

                let detailsText = escapeHtml(prodName || 'Produto vinculado');
                if (prodDetails) detailsText += ' &bull; ' + escapeHtml(prodDetails);
                if (prodTam) detailsText += ` (Tam: ${escapeHtml(prodTam)})`;
                if (prodCor) detailsText += ` (${escapeHtml(prodCor)})`;
                if (prodPrice) detailsText += ` &bull; <strong>${escapeHtml(prodPrice)}</strong>`;

                linkedItemBadge = `
                    <div class="mt-2 flex items-center justify-between gap-2 bg-blue-600/90 text-white px-3 py-1.5 rounded-xl text-xs shadow-md border border-blue-400/80">
                        <div class="flex items-center gap-2 min-w-0">
                            <i class="fas fa-shopping-bag text-blue-200 text-xs shrink-0"></i>
                            <div class="truncate text-blue-50 font-medium">
                                ${detailsText}
                            </div>
                        </div>
                        <button type="button" onclick="event.stopPropagation(); unlinkItemBuyerByMsgId(${msg.id})" title="Desvincular produto deste comentário" class="text-blue-200 hover:text-white hover:bg-blue-700 rounded-lg p-1 transition cursor-pointer shrink-0">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                `;
            }

            const userClass = isTikTok ? 'chat-user-tiktok' : 'chat-user-insta';
            const starClass = isMarked ? 'fas fa-star text-amber-400 text-lg' : 'far fa-star text-gray-500 hover:text-amber-400 text-lg';

            html += `
                <div class="chat-card ${isMarked ? 'marked' : ''} ${cardSelectionClass} rounded-2xl flex items-start gap-3 sm:gap-4 relative group transition-all duration-100 hover:shadow-lg" style="padding: ${fontSizes.padding};" title="${isLinkedMsg ? 'Peça #' + escapeHtml(msg.linked_code) + ' vinculada a este comentário' : ''}">
                    <!-- Avatar com Badge de Plataforma -->
                    <div class="relative flex-shrink-0">
                        ${avatarHtml}
                        <span style="position: absolute; bottom: -4px; right: -4px; width: 22px; height: 22px; border-radius: 50%; background: ${platformBadgeBg}; display: flex; align-items: center; justify-content: center; font-size: 11px; box-shadow: 0 2px 5px rgba(0,0,0,0.3); border: 1.5px solid #ffffff;">
                            ${platformIcon}
                        </span>
                    </div>

                    <!-- Conteúdo da Mensagem -->
                    <div class="flex-1" style="min-width: 0;">
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <div class="flex flex-wrap items-center gap-2" style="min-width: 0;">
                                <span class="${userClass}" style="font-size: ${fontSizes.username};">
                                    @${escapeHtml(cleanUser)}
                                </span>
                                ${clientBadge}
                                ${whatsappBadge}
                            </div>

                            <div class="flex items-center gap-2 flex-shrink-0">
                                ${vincularBtn}
                                <span class="chat-time-label" style="font-size: ${fontSizes.time};">${time}</span>
                                <button type="button" onclick="event.stopPropagation(); toggleMarkLiveMessageFeed(${msg.id})" title="${isMarked ? 'Desmarcar' : 'Marcar'}" class="p-1 transition cursor-pointer" style="background: none; border: none;">
                                    <i class="${starClass}"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Texto do Comentário Ultra Legível -->
                        <div class="chat-message-text" style="font-size: ${fontSizes.message};">
                            ${formattedMessage}
                        </div>
                        ${linkedItemBadge}
                    </div>
                </div>
            `;
        });

        const isNearBottom = (container.scrollTop + container.clientHeight >= container.scrollHeight - 180);
        const shouldScroll = autoScrollEnabled && isNearBottom;
        container.innerHTML = html;

        if (shouldScroll) {
            scrollToBottom(false);
        } else if (!isNearBottom) {
            const badge = document.getElementById("new-messages-floating-badge");
            if (badge) badge.classList.remove("hidden");
        }
    }

    // =========================================================================
    // AUTO-SCROLL & CONTROLES DE NAVEGAÇÃO
    // =========================================================================
    function handleContainerScroll() {
        const container = document.getElementById("feed-messages-container");
        const badge = document.getElementById("new-messages-floating-badge");
        if (!container || !badge) return;

        const isNearBottom = container.scrollTop + container.clientHeight >= container.scrollHeight - 80;
        if (isNearBottom) {
            badge.classList.add("hidden");
        }
    }

    function scrollToBottom(smooth) {
        const container = document.getElementById("feed-messages-container");
        if (container) {
            container.scrollTo({
                top: container.scrollHeight,
                behavior: smooth ? 'smooth' : 'auto'
            });
            const badge = document.getElementById("new-messages-floating-badge");
            if (badge) badge.classList.add("hidden");
        }
    }

    // =========================================================================
    // MARCAR MENSAGEM
    // =========================================================================
    async function toggleMarkLiveMessageFeed(messageId) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch('/admin/live-chat/toggle-mark-message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ message_id: messageId })
            });
            const data = await res.json();
            if (data.success) {
                const target = allLiveMessages.find(m => m.id == messageId);
                if (target) {
                    target.is_marked = data.is_marked;
                }
                updateFilterCounts();
                renderChatFeed();
                if (currentOnlineQrUser) {
                    renderModalClientMessages(currentOnlineQrUser.username);
                }
            }
        } catch (e) {
            console.error("Erro ao marcar mensagem:", e);
        }
    }

    // =========================================================================
    // MODAL DE BIPAGEM / QR CODE PARA CLIENTE
    // =========================================================================
    let onlineQrScannerInstance = null;
    let currentOnlineQrUser = null;
    let modalFilterMarkedOnly = false;
    let isScanProcessing = false;
    let lastScannedCode = "";
    let lastScannedTime = 0;

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

    function quickBeepForUser(userId, username, clientName, code) {
        if (!userId || userId === 'null' || userId === 'undefined') {
            openLinkModal(username, 'instagram');
            return;
        }
        openOnlineQrModal(userId, username, clientName);
        if (code) {
            const input = document.getElementById("online-qr-manual-input");
            if (input) input.value = code;
            handleOnlineQrScan(code);
        }
    }

    let onlineQrManualDebounceTimer = null;

    function handleOnlineQrManualInput(event) {
        if (onlineQrManualDebounceTimer) clearTimeout(onlineQrManualDebounceTimer);
        const val = event.target ? event.target.value.trim() : '';
        if (!val) return;
        onlineQrManualDebounceTimer = setTimeout(() => {
            const input = document.getElementById("online-qr-manual-input");
            if (input && input.value.trim()) {
                const code = input.value.trim();
                input.value = '';
                handleOnlineQrScan(code);
            }
        }, 120);
    }

    function handleOnlineQrKeyDown(event) {
        if (event.key === 'Enter' || event.key === 'Tab') {
            event.preventDefault();
            if (onlineQrManualDebounceTimer) {
                clearTimeout(onlineQrManualDebounceTimer);
                onlineQrManualDebounceTimer = null;
            }
            const input = document.getElementById("online-qr-manual-input");
            if (input && input.value.trim()) {
                const code = input.value.trim();
                input.value = '';
                handleOnlineQrScan(code);
            }
        }
    }

    function clickCodeFromComment(code) {
        const input = document.getElementById("online-qr-manual-input");
        if (input) {
            input.value = code;
            handleOnlineQrScan(code);
        }
    }

    async function closeOnlineQrModal() {
        if (onlineQrScannerInstance) {
            try {
                await onlineQrScannerInstance.stop();
            } catch (e) {}
        }
        document.getElementById("online-qr-modal").classList.add("hidden");
        
        // Devolve o foco imediatamente para o campo do leitor de código de barras
        setTimeout(() => ensureScannerFocus(true), 100);

        fetchChatFeed();
    }

    async function startOnlineQrCamera() {
        if (!onlineQrScannerInstance) {
            let formats = [0, 9, 5, 3]; // QR_CODE, EAN_13, CODE_128, CODE_39
            if (typeof Html5QrcodeSupportedFormats !== 'undefined') {
                formats = [
                    Html5QrcodeSupportedFormats.QR_CODE,
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39
                ];
            }
            onlineQrScannerInstance = new Html5Qrcode("online-qr-reader", {
                formatsToSupport: formats,
                verbose: false,
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                }
            });
        }

        try {
            const config = {
                fps: 25,
                disableFlip: true,
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                },
                videoConstraints: {
                    facingMode: "environment",
                    focusMode: { ideal: "continuous" },
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            };

            await onlineQrScannerInstance.start(
                { facingMode: "environment" },
                config,
                async (decodedText) => {
                    console.log("QRCode lido:", decodedText);
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

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const addResponse = await fetch('/admin/live-chat/add-to-bag', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
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
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch('/admin/live-chat/update-user-phone', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
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

        const uLower = (username || '').toLowerCase().replace(/^@/, '');
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
                        <button type="button" onclick="toggleMarkLiveMessageFeed(${msg.id})" title="${isMarked ? 'Desmarcar' : 'Marcar'}" class="p-0.5 cursor-pointer">
                            <i class="${starClass}"></i>
                        </button>
                    </div>
                    <div class="text-gray-100 font-medium leading-relaxed">${textWithCodes}</div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // =========================================================================
    // MODAL DE VINCULAR CLIENTE
    // =========================================================================
    function openLinkModal(username, platform) {
        document.getElementById("modal-display-username").textContent = `@${username}`;
        document.getElementById("modal-display-platform").textContent = (platform || 'instagram').toUpperCase();
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

    let linkUserSelectedIndex = 0;

    function handleLinkUserSearchKeydown(e) {
        e.stopPropagation();
        const container = document.getElementById("modal-search-results");
        if (!container) return;

        const items = container.querySelectorAll('.link-user-result-item');

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (items.length === 0) return;
            linkUserSelectedIndex = (linkUserSelectedIndex + 1) % items.length;
            updateLinkUserSelectedHighlight();
            return;
        }

        if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (items.length === 0) return;
            linkUserSelectedIndex = (linkUserSelectedIndex - 1 + items.length) % items.length;
            updateLinkUserSelectedHighlight();
            return;
        }

        if (e.key === 'Enter') {
            e.preventDefault();
            if (items.length > 0) {
                const targetIdx = (linkUserSelectedIndex >= 0 && linkUserSelectedIndex < items.length) ? linkUserSelectedIndex : 0;
                items[targetIdx].click();
            }
            return;
        }

        if (e.key === 'Escape' || e.key === 'Esc') {
            e.preventDefault();
            closeLinkModal();
        }
    }

    function updateLinkUserSelectedHighlight() {
        const container = document.getElementById("modal-search-results");
        if (!container) return;
        const items = container.querySelectorAll('.link-user-result-item');
        items.forEach((item, idx) => {
            if (idx === linkUserSelectedIndex) {
                item.classList.add('ring-2', 'ring-indigo-500', 'bg-indigo-50', 'border-indigo-400');
                item.classList.remove('bg-white', 'border-gray-200');
                item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                item.classList.remove('ring-2', 'ring-indigo-500', 'bg-indigo-50', 'border-indigo-400');
                item.classList.add('bg-white', 'border-gray-200');
            }
        });
    }

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
                            <div onclick="linkUserToProfile('${user.id}')" class="link-user-result-item p-2.5 rounded-xl border border-gray-200 bg-white hover:bg-indigo-50 hover:border-indigo-300 transition duration-150 cursor-pointer flex justify-between items-center">
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
                    linkUserSelectedIndex = 0;
                    updateLinkUserSelectedHighlight();
                } else {
                    resultsContainer.innerHTML = `<p class="text-xs text-gray-400 text-center py-4">Nenhum cliente cadastrado encontrado.</p>`;
                    linkUserSelectedIndex = 0;
                }
            })
            .catch(err => console.error("Erro na busca de usuários:", err));
    }

    function linkUserToProfile(userId) {
        const username = document.getElementById("modal-input-username").value;
        const platform = document.getElementById("modal-input-platform").value;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        fetch('/admin/live-chat/link-user', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
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
                fetchChatFeed();
                // Abre o leitor de QR Code para esse usuário que acabou de ser vinculado
                openOnlineQrModal(userId, username, '');
            } else {
                alert("Erro ao vincular: " + data.message);
            }
        })
        .catch(err => console.error("Erro ao vincular usuário:", err));
    }

    // =========================================================================
    // TAMANHO DA FONTE & TEMA
    // =========================================================================
    function setFontSize(size) {
        currentFontSize = size;
        localStorage.setItem('live_chat_font_size', size);
        applyFontSize(size);
        lastRenderedHash = ""; // Força re-renderização
        renderChatFeed();
    }

    function applyFontSize(size) {
        const buttons = {
            sm: document.getElementById("btn-font-sm"),
            md: document.getElementById("btn-font-md"),
            lg: document.getElementById("btn-font-lg"),
            xl: document.getElementById("btn-font-xl")
        };

        Object.keys(buttons).forEach(key => {
            const btn = buttons[key];
            if (!btn) return;
            if (key === size) {
                btn.className = "px-2.5 py-1 text-xs font-bold rounded-lg bg-indigo-600 text-white transition shadow";
            } else {
                btn.className = "px-2.5 py-1 text-xs font-bold rounded-lg text-gray-300 hover:text-white transition";
            }
        });
    }

    function toggleTheme() {
        currentTheme = currentTheme === 'dark' ? 'light' : 'dark';
        localStorage.setItem('live_chat_theme', currentTheme);
        applyTheme(currentTheme);
        lastRenderedHash = "";
        renderChatFeed();
    }

    function applyTheme(theme) {
        const wrapper = document.getElementById("feed-outer-wrapper");
        const icon = document.getElementById("theme-icon");
        const text = document.getElementById("theme-text");

        if (theme === 'dark') {
            if (wrapper) wrapper.className = "theme-dark relative flex-1 rounded-2xl shadow-2xl overflow-hidden flex flex-col border border-gray-700";
            if (icon) icon.className = "fas fa-moon text-yellow-400";
            if (text) text.textContent = "Modo Escuro";
        } else {
            if (wrapper) wrapper.className = "theme-light relative flex-1 rounded-2xl shadow-2xl overflow-hidden flex flex-col border border-gray-300";
            if (icon) icon.className = "fas fa-sun text-amber-500";
            if (text) text.textContent = "Modo Claro";
        }
    }

    function toggleFullScreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(err => console.log(err));
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    }

    // =========================================================================
    // NOTIFICAÇÃO TOAST (ALTO CONTRASTE E LEGIBILIDADE MÁXIMA)
    // =========================================================================
    function showToast(message, type = 'success') {
        const toast = document.createElement("div");
        
        let bgColor = '#065f46'; // emerald 800
        let borderColor = '#34d399'; // emerald 400
        let textColor = '#ffffff';
        let iconHtml = '<i class="fas fa-check-circle" style="color:#6ee7b7; font-size:16px; flex-shrink:0;"></i>';

        if (type === 'warning') {
            bgColor = '#78350f'; // amber 900
            borderColor = '#fbbf24'; // amber 400
            textColor = '#ffffff';
            iconHtml = '<i class="fas fa-exclamation-triangle" style="color:#fde047; font-size:16px; flex-shrink:0;"></i>';
        } else if (type === 'error') {
            bgColor = '#7f1d1d'; // red 900
            borderColor = '#f87171'; // red 400
            textColor = '#ffffff';
            iconHtml = '<i class="fas fa-times-circle" style="color:#fca5a5; font-size:16px; flex-shrink:0;"></i>';
        } else if (type === 'info') {
            bgColor = '#1e1b4b'; // indigo 950
            borderColor = '#818cf8'; // indigo 400
            textColor = '#ffffff';
            iconHtml = '<i class="fas fa-info-circle" style="color:#a5b4fc; font-size:16px; flex-shrink:0;"></i>';
        }

        toast.style.position = 'fixed';
        toast.style.bottom = '20px';
        toast.style.right = '20px';
        toast.style.backgroundColor = bgColor;
        toast.style.color = textColor;
        toast.style.border = `2px solid ${borderColor}`;
        toast.style.borderRadius = '16px';
        toast.style.padding = '12px 20px';
        toast.style.fontSize = '13px';
        toast.style.fontWeight = '800';
        toast.style.boxShadow = '0 10px 25px -5px rgba(0, 0, 0, 0.6), 0 8px 10px -6px rgba(0, 0, 0, 0.6)';
        toast.style.display = 'flex';
        toast.style.alignItems = 'center';
        toast.style.gap = '10px';
        toast.style.zIndex = '9999999';
        toast.style.maxWidth = '460px';
        toast.style.lineHeight = '1.4';
        toast.style.transition = 'all 0.3s cubic-bezier(0.16, 1, 0.3, 1)';
        toast.style.transform = 'translateY(20px)';
        toast.style.opacity = '0';

        toast.innerHTML = `${iconHtml} <span style="color:#ffffff !important; font-weight:800 !important; text-shadow:0 1px 2px rgba(0,0,0,0.4);">${escapeHtml(message)}</span>`;
        document.body.appendChild(toast);
        
        requestAnimationFrame(() => {
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';
        });

        const duration = (type === 'warning' || type === 'error') ? 6000 : 3500;
        setTimeout(() => {
            toast.style.transform = 'translateY(10px)';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, duration);
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
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function safeAttr(str) {
        if (!str) return '';
        return String(str).replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
</script>
@endpush

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
        max-width: 1750px;
        margin: 0 auto;
        height: calc(100vh - 85px);
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

    .chat-card.marked {
        background-color: var(--card-marked-bg) !important;
        border: 2px solid var(--card-marked-border) !important;
        box-shadow: 0 0 15px rgba(245, 158, 11, 0.25) !important;
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

<div class="container mx-auto px-2 sm:px-4 py-2 live-feed-page-wrapper">
    <!-- Top Bar com Controles de Leitura e Filtros (Card 1) -->
    <div id="feed-top-header" class="mb-2.5 bg-gray-900 text-white p-3 sm:p-3.5 rounded-2xl shadow-lg border border-gray-800 flex flex-col md:flex-row md:items-center md:justify-between gap-3 shrink-0">
        <!-- Título & Live Ativa -->
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-xl shadow-md shrink-0">
                <i class="fas fa-comments"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-base sm:text-lg font-black text-white tracking-tight">Chat da Transmissão</h1>
                    @if($activeLive && $activeLive->ativo)
                        <span class="bg-red-600 text-white text-xs font-black px-2 py-0.5 rounded-full flex items-center gap-1 shadow animate-pulse">
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
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            <!-- Controle de Tamanho da Fonte (A- / Normal / A+ / A++) -->
            <div class="flex items-center bg-gray-800 p-1 rounded-xl border border-gray-700 shadow-inner">
                <span class="text-xs text-gray-400 font-bold px-2 hidden sm:inline"><i class="fas fa-font"></i> Tamanho:</span>
                <button type="button" onclick="setFontSize('sm')" id="btn-font-sm" class="px-2.5 py-1 text-xs font-bold rounded-lg text-gray-300 hover:text-white transition cursor-pointer">A-</button>
                <button type="button" onclick="setFontSize('md')" id="btn-font-md" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-indigo-600 text-white transition shadow cursor-pointer">Normal</button>
                <button type="button" onclick="setFontSize('lg')" id="btn-font-lg" class="px-2.5 py-1 text-xs font-bold rounded-lg text-gray-300 hover:text-white transition cursor-pointer">A+</button>
                <button type="button" onclick="setFontSize('xl')" id="btn-font-xl" class="px-2.5 py-1 text-xs font-bold rounded-lg text-gray-300 hover:text-white transition cursor-pointer">A++</button>
            </div>

            <!-- Alternador de Tema Escuro / Claro -->
            <button type="button" onclick="toggleTheme()" id="btn-theme-toggle" class="bg-gray-800 hover:bg-gray-700 text-gray-200 p-2 px-3 rounded-xl border border-gray-700 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm">
                <i class="fas fa-moon text-yellow-400" id="theme-icon"></i>
                <span id="theme-text" class="hidden sm:inline">Modo Escuro</span>
            </button>

            <!-- Alternador de Rolagem Automática (Auto-Scroll) -->
            <button type="button" onclick="toggleAutoScroll()" id="btn-autoscroll-toggle" class="bg-emerald-600 hover:bg-emerald-500 text-white p-2 px-3.5 rounded-xl text-xs font-black transition flex items-center gap-1.5 cursor-pointer shadow-md active:scale-95" title="Ativar ou desativar a rolagem automática para as últimas mensagens">
                <i class="fas fa-arrow-down text-xs" id="autoscroll-icon"></i>
                <span id="autoscroll-text">Rolagem: Ativa</span>
            </button>

            <!-- Botão Tela Cheia -->
            <button type="button" onclick="toggleFullScreen()" title="Alternar Tela Cheia" class="bg-gray-800 hover:bg-gray-700 text-gray-200 p-2 px-3 rounded-xl border border-gray-700 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm">
                <i class="fas fa-expand"></i>
                <span class="hidden sm:inline">Tela Cheia</span>
            </button>

            <!-- Botão Ocultar 2 Primeiros Cards -->
            <button type="button" onclick="toggleTopCards()" id="btn-toggle-top-cards" class="bg-gray-800 hover:bg-gray-700 text-gray-200 hover:text-white p-2 px-3 rounded-xl border border-gray-700 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm" title="Ocultar os 2 primeiros cards para expandir o chat">
                <i class="fas fa-chevron-up text-indigo-400"></i>
                <span class="hidden sm:inline">Ocultar Cards</span>
            </button>

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
            <input type="text" id="feed-search-input" onkeyup="handleSearchChat(this.value)" placeholder="🔍 Filtrar mensagem ou @usuario..." class="w-full p-2 pl-8 rounded-xl border border-gray-300 text-xs font-semibold text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50">
            <i class="fas fa-search absolute left-2.5 top-2.5 text-gray-400 text-xs"></i>
        </div>
    </div>

    <!-- Área principal: Feed + Painel de Bipagem (lado a lado) -->
    <div class="flex gap-2.5 flex-1" style="min-height:0;">

        <!-- FEED PRINCIPAL DO CHAT (LEITURA ULTRA FÁCIL & AVATAR EM DESTAQUE) -->
        <div id="feed-outer-wrapper" class="theme-dark relative flex-1 rounded-2xl shadow-2xl overflow-hidden flex flex-col border border-gray-700" style="min-height: 0;">
            <!-- Botão Flutuante para Reexibir os 2 Primeiros Cards (Aparece quando ocultados) -->
            <div id="floating-show-top-cards-btn" class="absolute top-3 right-4 z-40 hidden transition-all">
                <button type="button" onclick="toggleTopCards()" class="bg-gray-900/95 hover:bg-gray-900 text-white font-extrabold px-3.5 py-1.5 rounded-full text-xs shadow-2xl backdrop-blur-md flex items-center gap-1.5 border border-indigo-500/80 cursor-pointer active:scale-95 transition-all hover:scale-105">
                    <i class="fas fa-chevron-down text-indigo-400 animate-bounce"></i>
                    <span>Mostrar Cards / Filtros</span>
                </button>
            </div>

            <!-- Container com Scroll -->
            <div id="feed-messages-container" class="flex-1 p-3 sm:p-5 overflow-y-auto space-y-3 font-sans" onscroll="handleContainerScroll()">
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
             PAINEL DIREITO — BIPAGEM DE ITENS (CÂMERA + VOZ)
             ============================================================ -->
        <div id="scan-panel" class="w-80 sm:w-96 shrink-0 flex flex-col gap-2.5 overflow-y-auto h-full pr-1 pb-8" style="min-height:0;">

            <!-- Card 1: Código da Live (capturado por voz) -->
            <div class="bg-white rounded-2xl shadow border border-gray-200 p-2.5 shrink-0">
                <div class="flex items-center justify-between gap-1.5 mb-1.5">
                    <div class="flex items-center gap-1.5">
                        <div class="w-6 h-6 rounded-lg bg-indigo-600 flex items-center justify-center shrink-0 shadow-sm text-white text-[11px]">
                            <i class="fas fa-microphone"></i>
                        </div>
                        <span class="text-xs font-black text-gray-800">Código da Live (Voz)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span id="mic-status-label" class="text-[10px] font-bold text-gray-400">Inativo</span>
                        <div id="mic-status-dot" class="w-2 h-2 rounded-full bg-gray-300 shrink-0" title="Microfone inativo"></div>
                    </div>
                </div>
                <div class="flex gap-1.5">
                    <input type="text" id="scan-live-code" placeholder="Diga: 'O código é ...' ou digite"
                        onkeydown="if(event.key==='Enter'){event.preventDefault(); applySpokenLiveCode(this.value);}"
                        class="flex-1 px-2.5 py-1.5 rounded-xl border border-gray-200 bg-gray-50 text-xs font-black text-indigo-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-400 tracking-wider uppercase transition-all duration-300">
                    <button type="button" onclick="clearLiveCode()" title="Limpar código"
                        class="w-8 h-8 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-red-50 border border-gray-200 hover:border-red-300 text-gray-400 hover:text-red-500 transition cursor-pointer text-xs shrink-0">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <!-- Feedback ao vivo do que o microfone está ouvindo -->
                <div id="mic-transcript-preview" class="mt-1.5 px-2 py-1 rounded-lg bg-indigo-50 border border-indigo-100 text-[10px] text-gray-500 font-medium truncate flex items-center gap-1.5 hidden transition-all">
                    <i class="fas fa-wave-square text-[9px] text-indigo-500 animate-pulse shrink-0"></i>
                    <span class="truncate">Ouvindo: <span id="mic-transcript-text" class="text-indigo-900 italic font-bold"></span></span>
                </div>
            </div>

            <!-- Card 2: Câmera com Imagem + Lista de Itens Bipados (Layout Limpo) -->
            <div class="bg-white rounded-2xl shadow border border-gray-200 flex flex-col shrink-0 sm:shrink-0 overflow-hidden" style="min-height: 520px;">

                <!-- IMAGEM DA CÂMERA AO VIVO DENTRO DO CARD (FLUSH NO TOPO) -->
                <div id="scan-camera-wrapper" class="p-2 pb-1 shrink-0 transition-all duration-200">
                    <div class="relative w-full bg-gray-950 rounded-xl overflow-hidden shadow-inner border border-gray-200 flex items-center justify-center" style="height: 180px;">
                        <!-- Container da Câmera (Html5Qrcode injeta o vídeo aqui) -->
                        <div id="scan-camera-reader" class="w-full h-full"></div>

                        <!-- Placeholder quando desligada (Sem textos extras poluindo) -->
                        <div id="scan-camera-placeholder" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-900 text-gray-400 p-2 text-center z-10 transition-all">
                            <button type="button" onclick="startScanDevices()" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold text-xs shadow-lg transition cursor-pointer flex items-center gap-2 active:scale-95">
                                <i class="fas fa-camera text-sm"></i> Ativar Câmera e Voz
                            </button>
                        </div>

                        <!-- Mira de Leitura visual limpa (sem texto sobre a imagem) -->
                        <div id="scan-camera-overlay" class="absolute inset-0 pointer-events-none hidden z-10 flex flex-col items-center justify-center p-2">
                            <div class="w-56 h-28 border border-emerald-400/60 rounded-2xl shadow-[0_0_15px_rgba(52,211,153,0.25)] relative flex items-center justify-center">
                                <div class="absolute inset-x-3 top-1/2 -translate-y-1/2 h-0.5 bg-emerald-400/70 animate-pulse"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BANNER DE CONFIRMAÇÃO DO ÚLTIMO ITEM BIPADO (FLASH VERDE) -->
                <div id="scan-last-item-banner" class="hidden mx-2 my-1 px-2.5 py-1.5 bg-emerald-600 text-white rounded-xl flex items-center justify-between text-xs font-black shadow-md shrink-0">
                    <div class="flex items-center gap-1.5 truncate">
                        <i class="fas fa-check-circle text-xs text-emerald-200 shrink-0"></i>
                        <span id="scan-last-item-text" class="truncate">Item Bipado!</span>
                    </div>
                    <span id="scan-last-item-time" class="text-[10px] font-semibold text-emerald-100 shrink-0 ml-1"></span>
                </div>

                <!-- Entrada manual de código de barras / leitor USB -->
                <div class="px-2.5 py-1.5 border-b border-gray-100 shrink-0">
                    <div class="flex gap-1.5">
                        <input type="text" id="scan-manual-input" placeholder="Bipador USB ou digitar código..."
                            class="flex-1 px-2.5 py-1.5 rounded-xl border border-gray-200 bg-gray-50 text-xs font-bold text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-400 uppercase"
                            onkeydown="handleManualScan(event)">
                        <button type="button" onclick="addManualCode()" title="Adicionar"
                            class="w-7 h-7 flex items-center justify-center rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white transition cursor-pointer text-xs shadow-sm shrink-0">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>

                <!-- Barra de Contador e Ação Copiar -->
                <div class="px-3 py-1.5 bg-gray-50 border-b border-gray-100 shrink-0 flex items-center justify-between">
                    <span class="text-xs text-gray-700 font-extrabold">
                        Bipados: <span id="scan-count" class="text-emerald-700 font-black">0</span>
                    </span>
                    <button type="button" onclick="copyScanList()" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold cursor-pointer flex items-center gap-1 transition">
                        <i class="fas fa-copy text-xs"></i> Copiar lista
                    </button>
                </div>

                <!-- Lista de itens bipados (COM SCROLL GARANTIDO E ALTURA AMPLIADA) -->
                <div id="scan-items-list" class="flex-1 overflow-y-auto p-2 space-y-1.5" style="min-height: 280px; max-height: 480px;">
                    <div id="scan-empty-state" class="flex flex-col items-center justify-center h-full py-8 text-gray-300">
                        <i class="fas fa-barcode text-3xl mb-1 text-gray-300"></i>
                        <p class="text-xs font-semibold text-gray-400">Nenhum item bipado ainda</p>
                        <p class="text-[10px] text-gray-400 mt-0.5 text-center">Aponte a câmera para o código<br>ou use leitor USB / teclado</p>
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
            <input type="text" id="modal-search-input" onkeyup="searchClients(this.value)" placeholder="Digite o nome da cliente..." class="w-full p-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-xs font-semibold bg-gray-50">
        </div>

        <div id="modal-search-results" class="max-h-48 overflow-y-auto space-y-2 border border-gray-100 rounded-xl p-2 bg-gray-50">
            <!-- Resultados Ajax -->
            <p class="text-xs text-gray-400 text-center py-4">Comece a digitar para pesquisar clientes.</p>
        </div>
        
        <div class="mt-5 flex justify-end gap-3 border-t border-gray-100 pt-4">
            <button onclick="closeLinkModal()" class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition cursor-pointer">Cancelar</button>
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
            <div class="bg-gray-900 rounded-3xl p-4 border border-gray-800 shadow-xl flex flex-col gap-2.5">
                <h4 class="text-xs font-extrabold text-white flex items-center gap-2">
                    <i class="fas fa-keyboard text-indigo-400 text-sm"></i>
                    <span>Bipador USB / Entrada Manual de Código</span>
                </h4>
                <div class="flex gap-2">
                    <input type="text" id="online-qr-manual-input" onkeydown="if(event.key==='Enter') handleOnlineQrScan(this.value)" placeholder="Ex: 0001, 73254 ou SKU..." class="flex-1 px-3.5 py-2.5 rounded-xl border border-gray-700 bg-gray-800 text-white placeholder-gray-400 text-sm font-bold focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none shadow-inner">
                    <button type="button" onclick="handleOnlineQrScan(document.getElementById('online-qr-manual-input').value)" class="bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold px-4 py-2.5 rounded-xl text-xs shadow-lg hover:shadow-indigo-500/25 transition-all duration-150 flex items-center justify-center gap-1.5 shrink-0 active:scale-95 cursor-pointer">
                        <i class="fas fa-plus"></i> Bipar
                    </button>
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

    // =========================================================================
    // SISTEMA DE BIPAGEM — CÂMERA (Html5Qrcode) + RECONHECIMENTO DE VOZ
    // =========================================================================
    const initialLinkedLiveItems = @json($linkedLiveItems ?? []);
    const bgScanItems = [];           // [{id, itemId, code, liveCode, time, source, productName, productDetails, ...}]
    let bgHtml5QrCode = null;
    let bgCameraActive = false;
    let bgSpeechRecog = null;
    let bgSpeechActive = false;
    let bgLastScannedCode = null;
    let bgLastScannedTime = 0;
    const BG_SCAN_DEBOUNCE_MS = 1500;

    /* ---- Câmera Visível no Card (Html5Qrcode) ----------------------------- */
    let availableCameras = [];
    let selectedCameraId = null;

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

    async function initScanCamera(preferredCameraId) {
        if (bgCameraActive && !preferredCameraId) return;

        // Se a lib Html5Qrcode ainda não carregou, aguarda brevemente
        if (typeof Html5Qrcode === 'undefined') {
            console.log('[Scan] Aguardando lib Html5Qrcode...');
            setTimeout(() => initScanCamera(preferredCameraId), 400);
            return;
        }

        try {
            const readerEl = document.getElementById('scan-camera-reader');
            if (!readerEl) return;

            // Se já estava escaneando e desejamos mudar de câmera, encerra a anterior
            if (bgHtml5QrCode) {
                try {
                    if (bgHtml5QrCode.isScanning) {
                        await bgHtml5QrCode.stop();
                    }
                } catch(e) {}
                bgCameraActive = false;
            }

            // Formatos essenciais focados em máxima velocidade (QR Code e Códigos de Barras 1D padrão)
            let formats = [0, 9, 5, 3]; // QR_CODE, EAN_13, CODE_128, CODE_39
            if (typeof Html5QrcodeSupportedFormats !== 'undefined') {
                formats = [
                    Html5QrcodeSupportedFormats.QR_CODE,
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39
                ];
            }

            if (!bgHtml5QrCode) {
                bgHtml5QrCode = new Html5Qrcode("scan-camera-reader", {
                    formatsToSupport: formats,
                    verbose: false,
                    experimentalFeatures: {
                        useBarCodeDetectorIfSupported: true // BarcodeDetector nativo via hardware GPU
                    }
                });
            }

            // Descobre câmeras disponíveis para popular o seletor
            try {
                const devices = await Html5Qrcode.getCameras();
                if (devices && devices.length > 0) {
                    availableCameras = devices;
                    populateCameraSelect(devices);
                }
            } catch(e) {
                console.warn('[Scan] Não foi possível enumerar câmeras:', e);
            }

            // Escolhe a câmera correta
            let cameraConfig = { facingMode: "environment" };
            if (preferredCameraId) {
                cameraConfig = preferredCameraId;
                selectedCameraId = preferredCameraId;
            } else if (availableCameras && availableCameras.length > 0) {
                const saved = localStorage.getItem('scan_preferred_camera');
                const foundSaved = availableCameras.find(c => c.id === saved);
                if (foundSaved) {
                    cameraConfig = foundSaved.id;
                    selectedCameraId = foundSaved.id;
                } else {
                    const backCam = availableCameras.find(c =>
                        (c.label || '').toLowerCase().includes('back') ||
                        (c.label || '').toLowerCase().includes('traseira') ||
                        (c.label || '').toLowerCase().includes('environment')
                    );
                    const chosen = backCam || availableCameras[0];
                    cameraConfig = chosen.id;
                    selectedCameraId = chosen.id;
                }
            }

            // Sincroniza o select visual
            const camSelect = document.getElementById('scan-camera-select');
            if (camSelect && selectedCameraId && typeof cameraConfig === 'string') {
                camSelect.value = selectedCameraId;
            }

            // Configuração para máxima velocidade e leitura no sensor inteiro
            const config = {
                fps: 25, // Taxa de quadros alta para reconhecimento instantâneo
                disableFlip: false,
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                },
                videoConstraints: {
                    facingMode: { ideal: "environment" },
                    focusMode: { ideal: "continuous" },
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            };

            await bgHtml5QrCode.start(
                cameraConfig,
                config,
                function(decodedText) {
                    const cleanCode = extractCleanCode(decodedText);
                    if (!cleanCode) return;
                    const now = Date.now();
                    if (cleanCode !== bgLastScannedCode || (now - bgLastScannedTime) > BG_SCAN_DEBOUNCE_MS) {
                        bgLastScannedCode = cleanCode;
                        bgLastScannedTime = now;
                        addScanItem(cleanCode, 'camera');
                    }
                },
                function() {} // ignora frames sem detecção
            );

            bgCameraActive = true;
            // Oculta placeholder e exibe overlay
            const placeholder = document.getElementById('scan-camera-placeholder');
            if (placeholder) placeholder.classList.add('hidden');
            const overlay = document.getElementById('scan-camera-overlay');
            if (overlay) overlay.classList.remove('hidden');

            updateCamDot(true, 'Câmera ativa lendo códigos');
            updateScanDevicesBtn();
        } catch (err) {
            console.warn('[Scan] Câmera não pôde iniciar automaticamente:', err.message || err);
            bgCameraActive = false;
            updateCamDot(false, 'Câmera: ' + (err.message || 'Permissão necessária'));
            updateScanDevicesBtn();
        }
    }

    function populateCameraSelect(devices) {
        const camSelect = document.getElementById('scan-camera-select');
        if (!camSelect) return;
        camSelect.innerHTML = '';
        devices.forEach((dev, idx) => {
            const opt = document.createElement('option');
            opt.value = dev.id;
            let label = dev.label || ('Câmera ' + (idx + 1));
            if (label.length > 22) label = label.substring(0, 20) + '...';
            opt.textContent = label;
            camSelect.appendChild(opt);
        });
        if (devices.length > 1) {
            camSelect.classList.remove('hidden');
        } else {
            camSelect.classList.add('hidden');
        }
    }

    async function changeScanCamera(cameraId) {
        if (!cameraId) return;
        selectedCameraId = cameraId;
        localStorage.setItem('scan_preferred_camera', cameraId);
        await stopScanCamera();
        await initScanCamera(cameraId);
    }

    async function stopScanCamera() {
        if (bgHtml5QrCode) {
            try {
                if (bgHtml5QrCode.isScanning) {
                    await bgHtml5QrCode.stop();
                }
            } catch(e) {}
            bgCameraActive = false;

            const placeholder = document.getElementById('scan-camera-placeholder');
            if (placeholder) placeholder.classList.remove('hidden');
            const overlay = document.getElementById('scan-camera-overlay');
            if (overlay) overlay.classList.add('hidden');

            updateCamDot(false, 'Câmera parada');
            updateScanDevicesBtn();
        }
    }

    async function switchScanCamera() {
        if (!availableCameras || availableCameras.length <= 1) return;
        const currentIdx = availableCameras.findIndex(c => c.id === selectedCameraId);
        const nextIdx = (currentIdx + 1) % availableCameras.length;
        const nextCam = availableCameras[nextIdx];
        await changeScanCamera(nextCam.id);
    }

    let cameraViewCollapsed = false;
    function toggleCameraViewSize() {
        cameraViewCollapsed = !cameraViewCollapsed;
        const wrapper = document.getElementById('scan-camera-wrapper');
        const icon = document.getElementById('cam-size-icon');
        if (wrapper) {
            if (cameraViewCollapsed) {
                wrapper.classList.add('hidden');
                if (icon) icon.className = "fas fa-chevron-down text-[10px]";
            } else {
                wrapper.classList.remove('hidden');
                if (icon) icon.className = "fas fa-chevron-up text-[10px]";
            }
        }
    }

    function startScanDevices() {
        initScanCamera();
        initScanSpeech();
    }

    function updateCamDot(active, title) {
        const dot = document.getElementById('cam-status-dot');
        const badge = document.getElementById('scan-cam-active-badge');
        if (dot) {
            dot.className = `w-2.5 h-2.5 rounded-full ${active ? 'bg-emerald-500 animate-pulse shadow-sm' : 'bg-gray-300'}`;
            dot.title = title || (active ? 'Câmera ativa' : 'Câmera inativa');
        }
        if (badge) {
            badge.className = `w-2 h-2 rounded-full ${active ? 'bg-emerald-500' : 'bg-gray-300'}`;
        }
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

        // Se a fala inteira for puramente um número em dígitos (ex: "15", "120", "P15", "A1")
        const directNumMatch = clean.match(/^[a-z]?\d{1,5}$/i);
        if (directNumMatch) {
            return directNumMatch[0].toUpperCase();
        }

        // Se a fala inteira for um número por extenso (ex: "quinze", "vinte e cinco")
        const directTokens = clean.split(' ').filter(Boolean);
        const directWordNum = parsePortugueseWordsFromTokens(directTokens);
        if (directWordNum !== null && directTokens.length <= 4) {
            const isPureNum = directTokens.every(t => t === 'e' || ptSpeechUnits[t] !== undefined || ptSpeechTens[t] !== undefined || ptSpeechHundreds[t] !== undefined);
            if (isPureNum) return String(directWordNum);
        }

        // Procura pelo gatilho: "codigo", "cod", "peca", "item", "numero", "num"
        const triggerMatch = clean.match(/\b(?:codigo|cod|peca|item|numero|num)\b/i);
        if (!triggerMatch) return null;

        const triggerIndex = clean.indexOf(triggerMatch[0]);
        let after = clean.slice(triggerIndex + triggerMatch[0].length).trim();
        if (!after) return null;

        const rawTokens = after.split(' ').filter(Boolean);
        if (rawTokens.length === 0) return null;

        // Remove palavras conectoras iniciais (ex: "é", "eh", "o", "a", "de", "da", "do", etc.)
        const leadStopWords = ['e', 'eh', 'o', 'a', 'os', 'as', 'de', 'do', 'da', 'dos', 'das', 'esse', 'essa', 'este', 'esta', 'deste', 'desta', 'desse', 'dessa', 'aqui', 'vai', 'ser', 'sera', 'fica', 'ficou', 'numero', 'num', 'ta', 'foi'];
        let startIndex = 0;
        while (startIndex < rawTokens.length && leadStopWords.includes(rawTokens[startIndex])) {
            startIndex++;
        }
        const tokens = rawTokens.slice(startIndex);
        if (tokens.length === 0) return null;

        // Se após o gatilho for um número por extenso (ex: "código vinte e cinco")
        const isPureNumPhrase = tokens.every(t => t === 'e' || ptSpeechUnits[t] !== undefined || ptSpeechTens[t] !== undefined || ptSpeechHundreds[t] !== undefined || t === 'mil' || /^\d+$/.test(t));
        if (isPureNumPhrase) {
            const wordsNum = parsePortugueseWordsFromTokens(tokens);
            if (wordsNum !== null) {
                return String(wordsNum);
            }
        }

        // Pega até 3 palavras significativas para o código (ex: "VESTIDO AZUL CURTO", "CALCA JEANS 38")
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
            <div class="font-extrabold text-gray-900 leading-tight">${nameHtml}</div>
            ${detailsText ? `<div class="text-[10px] text-gray-500 font-medium leading-tight mt-0.5">${detailsText}</div>` : ''}
        `;
    }

    function renderLiveCodeHtml(item) {
        if (item && item.liveCode) {
            return `<p class="text-[11px] text-indigo-600 font-extrabold scan-item-live-code flex items-center gap-1">
                <i class="fas fa-tag text-[9px]"></i> Live: <span>${escapeHtml(item.liveCode)}</span>
                <button type="button" onclick="editItemLiveCode('${item.id}')" title="Alterar código da live" class="text-gray-400 hover:text-indigo-600 ml-1 p-0.5 cursor-pointer"><i class="fas fa-pen text-[8px]"></i></button>
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
        item.liveCode = cleanCod;
        updateItemLiveCodeUI(scanId, cleanCod);
        syncLinkItemToLive(item.itemId || null, item.code, cleanCod);
    }

    function updateItemLiveCodeUI(scanId, liveCode) {
        let itemEl = document.querySelector(`[data-scan-id="${scanId}"]`);
        if (!itemEl) {
            const it = bgScanItems.find(x => x.id === scanId);
            if (it && it.code) itemEl = document.querySelector(`[data-code="${it.code}"]`);
        }
        if (!itemEl) return;
        const wrapper = itemEl.querySelector('.scan-item-live-wrapper');
        if (wrapper) {
            wrapper.innerHTML = renderLiveCodeHtml({ id: scanId, liveCode: liveCode });
        }
    }

    function syncLinkItemToLive(itemId, code, liveCode) {
        if (!liveId) return;
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
                    ) || data.data[0];
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

        if (prod) {
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

        if (waitingItem) {
            // Associa o código a esse item pendente
            waitingItem.liveCode = code;

            // Atualiza o elemento no DOM com layout limpo e menor
            updateItemLiveCodeUI(waitingItem.id, code);

            let itemEl = document.querySelector('[data-scan-id="' + waitingItem.id + '"]');
            if (!itemEl && waitingItem.code) {
                itemEl = document.querySelector('[data-code="' + waitingItem.code + '"]');
            }
            if (itemEl) {
                // Destaque visual piscando verde para confirmar vinculação
                itemEl.classList.add('bg-emerald-100', 'border-emerald-500', 'ring-2', 'ring-emerald-400');
                setTimeout(() => {
                    itemEl.classList.remove('bg-emerald-100', 'border-emerald-500', 'ring-2', 'ring-emerald-400');
                }, 1500);
            }

            // Atualiza banner de confirmação do item
            const banner = document.getElementById('scan-last-item-banner');
            const bannerText = document.getElementById('scan-last-item-text');
            const bannerTime = document.getElementById('scan-last-item-time');
            if (banner && bannerText && bgScanItems[0] === waitingItem) {
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

            playSuccessBeep();

            // Limpa o campo do áudio imediatamente conforme solicitado!
            clearLiveCode();

        } else {
            // Se NÃO houver item sem código, mantém no campo do áudio aguardando a próxima bipagem
            const liveInput = document.getElementById('scan-live-code');
            if (liveInput) {
                liveInput.value = code;
                playSuccessBeep();

                // Destaque visual pulsante no input (verde esmeralda)
                liveInput.classList.add('ring-2', 'ring-emerald-500', 'bg-emerald-50', 'text-emerald-900');
                setTimeout(() => {
                    liveInput.classList.remove('ring-2', 'ring-emerald-500', 'bg-emerald-50', 'text-emerald-900');
                }, 1200);

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

    /* ---- Lista de Itens Bipados ------------------------------------------ */
    function createScanItemElement(item, isNew) {
        const el = document.createElement('div');
        el.className = isNew
            ? 'flex items-start gap-2 bg-emerald-50 border border-emerald-400 ring-2 ring-emerald-300 rounded-xl p-2.5 group transition-all duration-500 scan-item'
            : 'flex items-start gap-2 bg-gray-50 border border-gray-200 rounded-xl p-2.5 group hover:border-emerald-400 hover:bg-emerald-50/20 transition scan-item';
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
            '<div class="flex-1 min-w-0 scan-item-info">' +
                '<div class="flex items-center justify-between gap-1">' +
                    '<span class="text-xs font-black text-gray-900 font-mono tracking-wider truncate">' + escapeHtml(item.code) + '</span>' +
                '</div>' +
                '<div class="scan-item-live-wrapper mt-0.5">' +
                    renderLiveCodeHtml(item) +
                '</div>' +
                '<div class="scan-item-details text-[11px] text-gray-600 leading-snug mt-1">' +
                    detailsInitial +
                '</div>' +
            '</div>' +
            '<button type="button" onclick="removeScanItem(this)" title="Remover item da live" ' +
                'class="w-6 h-6 flex items-center justify-center rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition cursor-pointer shrink-0">' +
                '<i class="fas fa-trash-alt text-[10px]"></i>' +
            '</button>';

        if (isNew) {
            setTimeout(function() {
                el.className = 'flex items-start gap-2 bg-gray-50 border border-gray-200 rounded-xl p-2.5 group hover:border-emerald-400 hover:bg-emerald-50/20 transition scan-item';
            }, 2500);
        }

        return el;
    }

    function addScanItem(code, source) {
        source = source || 'manual';
        const cleanCode = extractCleanCode(code).toUpperCase();
        if (!cleanCode) return;

        const emptyState = document.getElementById('scan-empty-state');
        if (emptyState) emptyState.remove();

        const now = new Date();
        const timeStr = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const liveInput = document.getElementById('scan-live-code');
        const liveCodeInField = liveInput ? liveInput.value.trim().toUpperCase() : '';
        const scanUniqueId = 'scan_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);

        const item = {
            id: scanUniqueId,
            code: cleanCode,
            liveCode: liveCodeInField,
            time: timeStr,
            timestamp: Date.now(),
            source: source,
            productName: '',
            productDetails: '',
            productPrice: ''
        };
        bgScanItems.unshift(item);

        // Se havia código no campo do áudio, já inseriu no item e agora limpa o campo!
        if (liveCodeInField) {
            clearLiveCode();
        }

        const list = document.getElementById('scan-items-list');
        const el = createScanItemElement(item, true);

        if (list) {
            list.prepend(el);
            list.scrollTop = 0; // Rola a lista automaticamente para o topo
        }

        // Busca assíncrona dos dados do produto para exibir Descrição e Detalhes
        fetchAndRenderItemDetails(cleanCode, scanUniqueId);

        // Já sincroniza com a tabela live_items
        syncLinkItemToLive(null, cleanCode, item.liveCode);

        // Exibe o banner de confirmação com destaque no topo
        const banner = document.getElementById('scan-last-item-banner');
        const bannerText = document.getElementById('scan-last-item-text');
        const bannerTime = document.getElementById('scan-last-item-time');
        if (banner && bannerText) {
            bannerText.textContent = cleanCode + (item.liveCode ? ' • Live: ' + item.liveCode : '');
            if (bannerTime) bannerTime.textContent = timeStr;
            banner.classList.remove('hidden');
            clearTimeout(window._scanBannerTimeout);
            window._scanBannerTimeout = setTimeout(function() {
                banner.classList.add('hidden');
            }, 3000);
        }

        updateScanCount();
        playSuccessBeep();
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

        initialLinkedLiveItems.forEach(item => {
            bgScanItems.push(item);
            if (list) {
                list.appendChild(createScanItemElement(item, false));
            }
        });
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
    }

    function clearLiveCode() {
        const el = document.getElementById('scan-live-code');
        if (el) el.value = '';
        lastSpokenCode = null;
        lastSpokenTime = 0;
    }

    function handleManualScan(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            addManualCode();
        }
    }

    function addManualCode() {
        const input = document.getElementById('scan-manual-input');
        if (!input) return;
        const code = extractCleanCode(input.value).toUpperCase();
        if (!code) return;
        addScanItem(code, 'manual');
        input.value = '';
        input.focus();
    }

    // =========================================================================
    // LISTENER GLOBAL PARA LEITORES DE CÓDIGO DE BARRAS USB (HARDWARE SCANNER)
    // =========================================================================
    let usbScanBuffer = "";
    let usbLastKeyTime = 0;
    const USB_MAX_INTERVAL_MS = 65; // Leitores físicos USB disparam teclas em < 40ms

    window.addEventListener('keydown', function(event) {
        const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
        const isEditingText = (activeTag === 'input' || activeTag === 'textarea' || activeTag === 'select');
        const isOurManualInput = document.activeElement && document.activeElement.id === 'scan-manual-input';

        const now = Date.now();
        const diff = now - usbLastKeyTime;

        if (event.key === 'Enter') {
            if (usbScanBuffer.length >= 2 && (diff < 120 || isOurManualInput)) {
                event.preventDefault();
                event.stopPropagation();
                const cleanCode = extractCleanCode(usbScanBuffer);
                if (cleanCode) {
                    addScanItem(cleanCode, 'usb');
                    const manualInput = document.getElementById('scan-manual-input');
                    if (manualInput) manualInput.value = '';
                }
                usbScanBuffer = "";
                return;
            }
            usbScanBuffer = "";
            return;
        }

        if (diff > 90) {
            usbScanBuffer = "";
        }

        if (event.key.length === 1 && !event.ctrlKey && !event.altKey && !event.metaKey) {
            if (isEditingText && !isOurManualInput && diff > USB_MAX_INTERVAL_MS) {
                usbScanBuffer = "";
                return;
            }
            usbScanBuffer += event.key;
            usbLastKeyTime = now;
        }
    }, true);

    function copyScanList() {
        if (bgScanItems.length === 0) return;
        const lines = bgScanItems.map(function(i) {
            const prodInfo = (i.productName ? i.productName + (i.productDetails ? ' (' + i.productDetails + ')' : '') : '-');
            return (i.code + '\t' + (i.liveCode || '-') + '\t' + prodInfo + '\t' + (i.productPrice || '-'));
        });
        if (navigator.clipboard) {
            navigator.clipboard.writeText('CÓDIGO\tCÓDIGO_LIVE\tPRODUTO_DETALHES\tVALOR\n' + lines.join('\n')).then(function() {
                const btn = document.querySelector('[onclick="copyScanList()"]');
                if (btn) {
                    const orig = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check text-emerald-500"></i> Copiado!';
                    setTimeout(function() { btn.innerHTML = orig; }, 1500);
                }
            }).catch(function() {});
        }
    }

    /* ---- Inicialização --------------------------------------------------- */
    function initScanSystem() {
        loadInitialLinkedLiveItems();
        initScanCamera();
        initScanSpeech();
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
        applyFontSize(currentFontSize);
        applyTheme(currentTheme);
        updateAutoScrollUI();
        updateTopCardsUI();
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
    // POLLING EM TEMPO REAL
    // =========================================================================
    function fetchChatFeed() {
        if (!liveId) return;

        fetch(`/admin/lives/${liveId}/chat-data`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    allLiveMessages = data.messages || [];
                    rawOnlineUsers = data.online_users || [];
                    
                    onlineUsersMap = {};
                    rawOnlineUsers.forEach(u => {
                        if (u.user_id) onlineUsersMap[u.user_id] = u;
                        if (u.username) onlineUsersMap[u.username.toLowerCase()] = u;
                    });

                    updateFilterCounts(data.stats);
                    renderChatFeed();

                    // Se o modal de bipe estiver aberto, atualizar mensagens da cliente
                    if (currentOnlineQrUser && !document.getElementById("online-qr-modal").classList.contains("hidden")) {
                        renderModalClientMessages(currentOnlineQrUser.username);
                    }
                }
            })
            .catch(err => {
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
        renderChatFeed();
    }

    function renderChatFeed() {
        const container = document.getElementById("feed-messages-container");
        if (!container) return;

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
        const currentHash = `${currentFilter}:${currentSearchTerm}:${list.length}:${list.length > 0 ? list[list.length - 1].id : 0}:${list.filter(m => m.is_marked).length}:${avatarHash}:${currentFontSize}:${currentTheme}`;
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

            // Destacar códigos de produtos na mensagem e torná-los clicáveis para bipe rápido
            let formattedMessage = escapeHtml(msg.message);
            formattedMessage = formattedMessage.replace(/\b([a-zA-Z0-9]{3,6})\b/g, function(match, code) {
                if (/\d/.test(code)) { // Se contém números (código de peça)
                    const cleanCode = code.replace(/^#/, '');
                    if (msg.user_id) {
                        return `<button type="button" onclick="event.stopPropagation(); quickBeepForUser('${msg.user_id}', '${escapeHtml(cleanUser)}', '${escapeHtml(displayName)}', '${cleanCode}')" title="Bipar código ${cleanCode} para @${escapeHtml(cleanUser)}" class="chat-product-code hover:opacity-90 active:scale-95 transition cursor-pointer"><i class="fas fa-tag" style="font-size: 0.85em;"></i> ${code}</button>`;
                    } else {
                        return `<button type="button" onclick="event.stopPropagation(); openLinkModal('${escapeHtml(cleanUser)}', '${msg.plataforma || 'instagram'}')" title="Vincular @${escapeHtml(cleanUser)} para bipar ${cleanCode}" class="chat-product-code hover:opacity-90 active:scale-95 transition cursor-pointer"><i class="fas fa-tag" style="font-size: 0.85em;"></i> ${code}</button>`;
                    }
                }
                return code;
            });

            // Botão Bipar para este cliente / Vincular
            const biparBtn = msg.user_id 
                ? `<button type="button" onclick="event.stopPropagation(); openOnlineQrModal('${msg.user_id}', '${escapeHtml(cleanUser)}', '${escapeHtml(displayName)}')" title="Bipar produtos para @${escapeHtml(cleanUser)}" class="bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold px-3 py-1 rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer active:scale-95 text-xs"><i class="fas fa-qrcode text-xs"></i> <span>Bipar</span></button>`
                : `<button type="button" onclick="event.stopPropagation(); openLinkModal('${escapeHtml(cleanUser)}', '${msg.plataforma || 'instagram'}')" title="Vincular @${escapeHtml(cleanUser)} a um cliente" class="bg-indigo-600/90 hover:bg-indigo-500 text-white font-bold px-2.5 py-1 rounded-xl shadow transition flex items-center gap-1 cursor-pointer active:scale-95 text-xs"><i class="fas fa-user-plus text-[11px]"></i> <span>Vincular</span></button>`;

            const userClass = isTikTok ? 'chat-user-tiktok' : 'chat-user-insta';
            const starClass = isMarked ? 'fas fa-star text-amber-400 text-lg' : 'far fa-star text-gray-500 hover:text-amber-400 text-lg';

            const cardClickAction = msg.user_id
                ? `onclick="openOnlineQrModal('${msg.user_id}', '${escapeHtml(cleanUser)}', '${escapeHtml(displayName)}')"`
                : `onclick="openLinkModal('${escapeHtml(cleanUser)}', '${msg.plataforma || 'instagram'}')"`;

            html += `
                <div ${cardClickAction} class="chat-card ${isMarked ? 'marked' : ''} rounded-2xl flex items-start gap-3 sm:gap-4 relative group cursor-pointer transition-all duration-100 hover:shadow-lg active:scale-[0.995]" style="padding: ${fontSizes.padding};" title="Clique para bipar para @${escapeHtml(cleanUser)}">
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
                                ${biparBtn}
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

        // Se a câmera do card lateral estiver ativa, pausa temporariamente para evitar conflito de hardware
        if (bgCameraActive) {
            sidebarCamPausedForModal = true;
            stopScanCamera();
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

    let sidebarCamPausedForModal = false;

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
        
        // Se a câmera do card lateral estava ligada antes de abrir o modal, reativa-a suavemente
        if (sidebarCamPausedForModal) {
            sidebarCamPausedForModal = false;
            setTimeout(() => initScanCamera(), 300);
        }

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
                            <div onclick="linkUserToProfile('${user.id}')" class="p-2.5 rounded-xl border border-gray-200 bg-white hover:bg-indigo-50 hover:border-indigo-300 transition duration-150 cursor-pointer flex justify-between items-center">
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
    // NOTIFICAÇÃO TOAST
    // =========================================================================
    function showToast(message) {
        const toast = document.createElement("div");
        toast.className = "fixed bottom-5 right-5 bg-emerald-600 text-white px-5 py-3 rounded-2xl shadow-2xl z-50 transition-all duration-300 translate-y-5 opacity-0 text-sm font-extrabold flex items-center gap-2 border border-emerald-400";
        toast.style.zIndex = "999999";
        toast.innerHTML = `<i class="fas fa-check-circle"></i> <span>${escapeHtml(message)}</span>`;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.transform = "translateY(0)";
            toast.style.opacity = "1";
        }, 100);

        setTimeout(() => {
            toast.style.transform = "translateY(5px)";
            toast.style.opacity = "0";
            setTimeout(() => toast.remove(), 300);
        }, 3500);
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

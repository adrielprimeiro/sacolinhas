<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Telão & Chat da Apresentadora • Minha Mania</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&family=JetBrains+Mono:wght@700;900&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            user-select: none;
            overflow: hidden;
        }

        .font-mono-numbers {
            font-family: 'JetBrains Mono', monospace;
            font-variant-numeric: tabular-nums;
        }

        /* Themes */
        .theme-dark {
            --bg-main: #06080f;
            --panel-bg: rgba(15, 23, 42, 0.92);
            --card-bg: #111827;
            --card-border: #2c3b52;
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --accent: #a855f7;
            background-color: #06080f;
            color: #ffffff;
        }
        .theme-dark .counter-num {
            color: #ffffff;
            text-shadow: 0 0 50px rgba(168, 85, 247, 0.5), 0 0 100px rgba(168, 85, 247, 0.25);
        }

        .theme-light {
            --bg-main: #f1f5f9;
            --panel-bg: rgba(255, 255, 255, 0.96);
            --card-bg: #ffffff;
            --card-border: #cbd5e1;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --accent: #6366f1;
            background-color: #f1f5f9;
            color: #0f172a;
        }
        .theme-light .counter-num {
            color: #0f172a;
            text-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .theme-neon {
            --bg-main: #020617;
            --panel-bg: rgba(11, 19, 43, 0.94);
            --card-bg: #091329;
            --card-border: #1e3a5f;
            --text-primary: #22d3ee;
            --text-secondary: #94a3b8;
            --accent: #06b6d4;
            background-color: #020617;
            color: #22d3ee;
        }
        .theme-neon .counter-num {
            color: #22d3ee;
            text-shadow: 0 0 40px rgba(34, 211, 238, 0.8), 0 0 90px rgba(34, 211, 238, 0.4);
        }

        .theme-chroma {
            --bg-main: #00ff00;
            --panel-bg: rgba(0, 0, 0, 0.88);
            --card-bg: #111827;
            --card-border: #374151;
            --text-primary: #ffffff;
            --text-secondary: #9ca3af;
            --accent: #10b981;
            background-color: #00ff00 !important;
            color: #ffffff;
        }
        .theme-chroma .counter-num {
            color: #ffffff;
            text-shadow: 0 0 20px rgba(0, 0, 0, 0.9), 0 0 40px rgba(0, 0, 0, 0.8);
        }

        .theme-transparent {
            --bg-main: transparent;
            --panel-bg: rgba(15, 23, 42, 0.85);
            --card-bg: rgba(17, 24, 39, 0.85);
            --card-border: rgba(255, 255, 255, 0.2);
            --text-primary: #ffffff;
            --text-secondary: #cbd5e1;
            --accent: #818cf8;
            background-color: transparent !important;
            color: #ffffff;
        }
        .theme-transparent .counter-num {
            color: #ffffff;
            text-shadow: 0 0 30px rgba(0, 0, 0, 0.9), 0 0 60px rgba(0, 0, 0, 0.8);
        }

        /* Number bounce / pulse animation */
        @keyframes counterPop {
            0% { transform: scale(1); }
            35% { transform: scale(1.12); }
            65% { transform: scale(0.97); }
            100% { transform: scale(1); }
        }

        .animate-pop {
            animation: counterPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        /* Auto-hide controls */
        .controls-layer {
            transition: opacity 0.4s ease, transform 0.4s ease;
        }
        .controls-hidden {
            opacity: 0;
            pointer-events: none;
            transform: translateY(-10px);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.15);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.25);
            border-radius: 6px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.45);
        }

        /* Chat Cards High Contrast Studio Quality */
        .telao-chat-card {
            background-color: var(--card-bg) !important;
            border: 2px solid var(--card-border) !important;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25) !important;
            transition: all 0.2s ease;
        }

        .telao-chat-card.platform-instagram {
            border-left: 6px solid #ec4899 !important;
        }

        .telao-chat-card.platform-tiktok {
            border-left: 6px solid #06b6d4 !important;
        }

        .telao-chat-card.is-linked-msg {
            border: 2.5px solid #3b82f6 !important;
            border-left: 8px solid #2563eb !important;
            background-color: rgba(37, 99, 235, 0.15) !important;
            box-shadow: 0 0 25px rgba(37, 99, 235, 0.3) !important;
        }

        .telao-chat-card.is-marked {
            border: 2.5px solid #f59e0b !important;
            border-left: 8px solid #f59e0b !important;
            background-color: rgba(245, 158, 11, 0.14) !important;
            box-shadow: 0 0 20px rgba(245, 158, 11, 0.35) !important;
        }

        .telao-code-badge {
            background-color: #4f46e5 !important;
            color: #ffffff !important;
            font-weight: 900 !important;
            padding: 3px 9px !important;
            border-radius: 8px !important;
            display: inline-flex !important;
            align-items: center !important;
            font-family: 'JetBrains Mono', monospace !important;
            font-size: 0.95em !important;
            margin: 0 3px !important;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.4) !important;
            border: 1.5px solid #818cf8 !important;
        }

        @keyframes msgSlideIn {
            from {
                opacity: 0;
                transform: translateY(14px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .msg-entry-animate {
            animation: msgSlideIn 0.22s ease-out forwards;
        }
    </style>
</head>
<body id="bodyEl" class="theme-dark h-screen w-screen flex flex-col justify-between items-center relative select-none">

    <!-- Audio Beep (Web Audio API fallback) -->
    <audio id="beepSound" preload="auto">
        <source src="data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU"+("A".repeat(100)) type="audio/wav">
    </audio>

    <!-- Floating Toast Notification on Scan -->
    <div id="scanFeedbackToast" class="fixed top-16 inset-x-0 mx-auto max-w-md w-full px-4 z-50 transition-all duration-300 pointer-events-none opacity-0 -translate-y-6">
        <div id="toastCard" class="px-5 py-3.5 rounded-2xl shadow-2xl backdrop-blur-xl border border-white/20 flex items-center gap-3.5 text-white bg-zinc-900/90">
            <div id="toastIconBox" class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 flex items-center justify-center text-lg shrink-0">
                <i id="toastIcon" class="fas fa-check"></i>
            </div>
            <div class="min-w-0 flex-1">
                <h4 id="toastTitle" class="text-sm font-black truncate text-white">Item Anexado à Live!</h4>
                <p id="toastSubtitle" class="text-xs text-white/80 mt-0.5 truncate">#Código • Nome da Peça</p>
            </div>
        </div>
    </div>

    <!-- Top Floating Toolbar (Auto-hides on idle) -->
    <header id="controlsBar" class="controls-layer w-full max-w-7xl mx-auto pt-3 px-4 z-50 flex flex-wrap items-center justify-between gap-2.5">
        <!-- Live info & Selector -->
        <div class="flex items-center gap-2.5 bg-black/60 backdrop-blur-md px-3.5 py-1.5 rounded-2xl border border-white/10 shadow-lg">
            <div class="flex items-center gap-2">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
                <span id="liveStatusText" class="text-xs font-bold uppercase tracking-wider text-emerald-400">Ao Vivo</span>
            </div>

            <div class="h-4 w-px bg-white/20"></div>

            <form method="GET" action="{{ route('admin.live-chat.contador') }}" class="m-0 flex items-center gap-2">
                <select 
                    id="liveSelect"
                    name="live_id" 
                    onchange="this.form.submit()"
                    class="bg-transparent text-xs font-semibold text-white/90 border-0 focus:ring-0 cursor-pointer outline-none max-w-[180px] sm:max-w-[260px] truncate"
                >
                    @foreach($lives as $live)
                        <option value="{{ $live->id }}" {{ ($activeLive && $activeLive->id == $live->id) ? 'selected' : '' }} class="bg-zinc-900 text-white">
                            #{{ $live->id }} - {{ $live->nome ?: 'Live de ' . date('d/m', strtotime($live->created_at)) }} {{ $live->ativo ? '🔴' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>

            <div class="hidden lg:flex items-center gap-1.5 bg-purple-500/20 text-purple-300 border border-purple-500/30 px-2 py-0.5 rounded-lg text-[10px] font-bold">
                <i class="fas fa-barcode text-purple-400"></i>
                <span>Leitor Pronto</span>
            </div>
        </div>

        <!-- Action Controls -->
        <div class="flex items-center gap-1.5 bg-black/60 backdrop-blur-md px-2 py-1 rounded-2xl border border-white/10 shadow-lg">
            <!-- Layout Selector (Split / Contador / Chat) -->
            <div class="flex items-center bg-white/10 p-0.5 rounded-xl text-xs">
                <button type="button" onclick="setLayoutMode('split')" id="btn-layout-split" class="px-2.5 py-1 rounded-lg font-bold text-white bg-indigo-600 shadow-sm transition flex items-center gap-1" title="Divisão 50/50: Contador + Chat">
                    <i class="fas fa-columns text-[10px]"></i> <span class="hidden sm:inline">Telão + Chat</span>
                </button>
                <button type="button" onclick="setLayoutMode('counter')" id="btn-layout-counter" class="px-2.5 py-1 rounded-lg font-bold text-white/70 hover:text-white hover:bg-white/10 transition flex items-center gap-1" title="Apenas Contador Gigante (100%)">
                    <i class="fas fa-hashtag text-[10px]"></i> <span class="hidden sm:inline">Só Contador</span>
                </button>
                <button type="button" onclick="setLayoutMode('chat')" id="btn-layout-chat" class="px-2.5 py-1 rounded-lg font-bold text-white/70 hover:text-white hover:bg-white/10 transition flex items-center gap-1" title="Apenas Chat da Transmissão (100%)">
                    <i class="fas fa-comments text-[10px]"></i> <span class="hidden sm:inline">Só Chat</span>
                </button>
            </div>

            <div class="h-4 w-px bg-white/20"></div>

            <!-- Theme dropdown -->
            <select 
                id="themeSelect" 
                onchange="changeTheme(this.value)"
                class="bg-transparent text-xs font-semibold text-white/90 border-0 focus:ring-0 cursor-pointer outline-none px-1.5 py-1"
                title="Estilo visual"
            >
                <option value="theme-dark" class="bg-zinc-900 text-white">🌙 Dark Studio</option>
                <option value="theme-neon" class="bg-zinc-900 text-white">⚡ Neon Cyber</option>
                <option value="theme-light" class="bg-zinc-900 text-white">☀️ Light Clean</option>
                <option value="theme-chroma" class="bg-zinc-900 text-white">🟢 Chroma Key</option>
                <option value="theme-transparent" class="bg-zinc-900 text-white">🔲 Transparente (OBS)</option>
            </select>

            <div class="h-4 w-px bg-white/20"></div>

            <!-- Toggle Details button -->
            <button 
                type="button" 
                id="toggleDetailsBtn"
                onclick="toggleDetails()"
                class="px-2 py-1 text-xs font-semibold text-white/80 hover:text-white rounded-lg hover:bg-white/10 transition flex items-center gap-1"
                title="Mostrar/Ocultar último item"
            >
                <i class="fas fa-tag text-[10px]"></i>
                <span class="hidden md:inline">Item</span>
            </button>

            <!-- Toggle Sound button -->
            <button 
                type="button" 
                id="toggleSoundBtn"
                onclick="toggleSound()"
                class="px-2 py-1 text-xs font-semibold text-white/80 hover:text-white rounded-lg hover:bg-white/10 transition flex items-center gap-1"
                title="Ativar/Desativar som ao bipar"
            >
                <i id="soundIcon" class="fas fa-volume-up text-[10px] text-emerald-400"></i>
            </button>

            <!-- Fullscreen button -->
            <button 
                type="button" 
                onclick="toggleFullScreen()"
                class="px-2 py-1 text-xs font-semibold text-white/80 hover:text-white rounded-lg hover:bg-white/10 transition flex items-center gap-1"
                title="Tela Cheia (F11 ou tecla F)"
            >
                <i id="fsIcon" class="fas fa-expand text-[10px]"></i>
            </button>
        </div>
    </header>

    <!-- MAIN STAGE: 2-COLUMN SPLIT (CONTADOR + CHAT DA APRESENTADORA) -->
    <main id="mainStageContainer" class="flex-1 w-full max-w-[1780px] mx-auto px-3 sm:px-5 py-2 grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 min-h-0 overflow-hidden items-stretch">
        
        <!-- ========================================== -->
        <!-- LADO ESQUERDO: CONTADOR GIGANTE & STATS   -->
        <!-- ========================================== -->
        <section id="col-counter-panel" class="flex flex-col justify-between items-center h-full min-h-0 relative p-4 sm:p-6 rounded-3xl backdrop-blur-xl border border-white/10 shadow-2xl transition-all duration-300" style="background-color: var(--panel-bg);">
            
            <!-- Live Stats Badges (Itens, Total R$, Bipados) -->
            <div class="w-full flex flex-wrap items-center justify-center gap-2.5 shrink-0 pt-1">
                <div id="liveBadge" class="inline-flex items-center gap-2 px-4 py-1.5 rounded-2xl bg-purple-500/20 border border-purple-500/40 backdrop-blur text-xs sm:text-sm font-black tracking-wider text-purple-200 shadow-md">
                    <i class="fas fa-shopping-bag text-purple-400 text-sm"></i>
                    <span><strong id="totalItemsCount" class="text-white font-mono text-base sm:text-lg">{{ $sacolinhasItensCount }}</strong> <span class="text-white/80">itens</span></span>
                </div>

                <div id="liveTotalValueBadge" class="inline-flex items-center gap-2 px-4.5 py-1.5 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 backdrop-blur text-xs sm:text-sm font-black tracking-wide text-emerald-200 shadow-md transition-all duration-300">
                    <i class="fas fa-coins text-emerald-400 text-sm"></i>
                    <span class="text-white/80">Total:</span>
                    <strong id="totalLiveValueText" class="text-emerald-300 font-mono font-black text-base sm:text-lg">{{ $initialTotalValueFormatted }}</strong>
                </div>

                <div id="liveBipadosBadge" class="hidden sm:inline-flex items-center gap-2 px-3.5 py-1.5 rounded-2xl bg-white/10 border border-white/20 backdrop-blur text-xs font-bold text-white/80 shadow-xs">
                    <i class="fas fa-barcode text-purple-300 text-xs"></i>
                    <span><strong id="totalBipadosCount" class="text-white font-mono text-sm">{{ $initialBipadosCount }}</strong> bipados</span>
                </div>
            </div>

            <!-- The GIANT Number (Código da Peça / Sequência da Live) -->
            <div class="my-auto flex flex-col items-center justify-center text-center cursor-pointer select-none py-2" onclick="triggerEasterEgg()" title="Clique duplo para Tela Cheia">
                <span class="text-xs sm:text-sm font-black uppercase tracking-widest text-purple-400/90 mb-1 flex items-center gap-1.5">
                    <i class="fas fa-tag text-xs"></i> Peça Atual da Live
                </span>
                <div 
                    id="counterNumber" 
                    class="counter-num font-mono-numbers font-black tracking-tighter leading-none select-none"
                    style="font-size: clamp(8rem, 18vw, 24rem);"
                >
                    {{ $initialPieceCode }}
                </div>
            </div>

            <!-- Subtitle / Last Biped Item Info (Collapsible) -->
            <div id="lastItemContainer" class="w-full shrink-0 transition-all duration-300 {{ $lastItem ? '' : 'hidden' }}">
                <div class="w-full flex items-center justify-between gap-3 px-4 sm:px-5 py-3 rounded-2xl bg-black/50 border border-white/15 backdrop-blur-md shadow-xl text-left">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="h-11 w-11 rounded-2xl bg-purple-500/30 border border-purple-500/50 flex items-center justify-center text-purple-300 font-black text-lg shrink-0 shadow-inner">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="font-black text-white flex items-center gap-2 text-sm sm:text-base">
                                <span id="lastItemCode" class="text-purple-300 font-mono font-black text-base sm:text-lg">#{{ $lastItem['codigo'] ?? '---' }}</span>
                                <span class="text-white/40">•</span>
                                <span id="lastItemName" class="truncate font-black text-white">{{ $lastItem['nome'] ?? '---' }}</span>
                            </div>
                            <div class="text-white/80 text-xs sm:text-sm flex items-center gap-2.5 mt-0.5 font-bold">
                                <span id="lastItemPrice" class="font-black text-emerald-400 text-sm sm:text-base">{{ $lastItem['preco'] ?? '' }}</span>
                                <span class="text-white/40">•</span>
                                <span id="lastItemTime" class="text-white/60 font-mono">{{ $lastItem['hora'] ?? '' }}</span>
                            </div>
                        </div>
                    </div>
                    <span class="text-xs font-black bg-purple-600/30 text-purple-200 px-3 py-1 rounded-xl shrink-0 border border-purple-500/40">Último Bipado</span>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- LADO DIREITO: CHAT DA APRESENTADORA        -->
        <!-- ========================================== -->
        <section id="col-chat-panel" class="flex flex-col h-full min-h-0 rounded-3xl backdrop-blur-xl border border-white/10 shadow-2xl overflow-hidden transition-all duration-300" style="background-color: var(--panel-bg);">
            
            <!-- Header do Chat -->
            <div class="p-3 sm:p-4 border-b border-white/10 flex flex-wrap items-center justify-between gap-2.5 shrink-0 bg-black/30">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center text-base shadow-md shrink-0">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm sm:text-base font-black text-white tracking-tight">Chat da Apresentadora</h2>
                            <span id="chat-live-pulse-badge" class="bg-red-600 text-white text-[9px] font-black px-2 py-0.5 rounded-full flex items-center gap-1 shadow animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span> AO VIVO
                            </span>
                        </div>
                        <p class="text-xs text-white/70 font-bold truncate">
                            <span id="chat-msgs-counter" class="text-purple-300 font-mono font-black">0</span> comentários capturados
                        </p>
                    </div>
                </div>

                <!-- Controles de Legibilidade & Filtros -->
                <div class="flex items-center gap-2">
                    <!-- Zoom de Fonte (Tamanho de Leitura da Apresentadora) -->
                    <div class="flex bg-white/10 p-0.5 rounded-xl text-xs font-black" title="Tamanho do Texto no Telão">
                        <button type="button" onclick="setChatFontSize('normal')" id="btn-font-normal" class="px-2.5 py-1 rounded-lg text-white/70 hover:text-white hover:bg-white/10 transition">
                            1x
                        </button>
                        <button type="button" onclick="setChatFontSize('large')" id="btn-font-large" class="px-2.5 py-1 rounded-lg text-white bg-indigo-600 shadow-sm transition">
                            1.5x
                        </button>
                        <button type="button" onclick="setChatFontSize('huge')" id="btn-font-huge" class="px-2.5 py-1 rounded-lg text-white/70 hover:text-white hover:bg-white/10 transition">
                            2x
                        </button>
                    </div>

                    <!-- Filtro Tabs -->
                    <div class="flex bg-white/10 p-0.5 rounded-xl text-[11px] font-extrabold">
                        <button type="button" onclick="setChatFilter('all')" id="tab-chat-all" class="px-2.5 py-1 rounded-lg text-white bg-purple-600 shadow-xs transition">
                            Todas
                        </button>
                        <button type="button" onclick="setChatFilter('marked')" id="tab-chat-marked" class="px-2 py-1 rounded-lg text-white/70 hover:text-white hover:bg-white/10 transition flex items-center gap-1" title="Mensagens Marcadas">
                            <i class="fas fa-star text-amber-400 text-xs"></i>
                        </button>
                        <button type="button" onclick="setChatFilter('instagram')" id="tab-chat-instagram" class="px-2 py-1 rounded-lg text-white/70 hover:text-white hover:bg-white/10 transition" title="Apenas Instagram">
                            <i class="fab fa-instagram text-pink-400 text-xs"></i>
                        </button>
                        <button type="button" onclick="setChatFilter('tiktok')" id="tab-chat-tiktok" class="px-2 py-1 rounded-lg text-white/70 hover:text-white hover:bg-white/10 transition" title="Apenas TikTok">
                            <i class="fab fa-tiktok text-cyan-400 text-xs"></i>
                        </button>
                    </div>

                    <!-- Auto-scroll lock toggle -->
                    <button type="button" onclick="toggleAutoScroll()" id="btn-autoscroll" class="px-2.5 py-1 text-xs font-bold text-emerald-400 bg-white/10 hover:bg-white/20 rounded-xl transition flex items-center gap-1 border border-white/10" title="Travar/Destravar Rolagem Automática">
                        <i id="autoscroll-icon" class="fas fa-arrow-down text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Feed de Mensagens Rolável -->
            <div id="telao-chat-messages-container" class="flex-1 min-h-0 overflow-y-auto p-3 sm:p-5 space-y-3 sm:space-y-4 relative">
                <!-- Preenchido dinamicamente via JS -->
                <div class="flex flex-col items-center justify-center h-full text-white/40 py-16 text-center">
                    <i class="fas fa-comments text-5xl mb-3 text-white/20 animate-pulse"></i>
                    <p class="text-sm font-extrabold text-white/80">Aguardando comentários da live...</p>
                    <p class="text-xs text-white/50 mt-1">As mensagens de Instagram e TikTok aparecerão aqui em tempo real com destaque para a apresentadora.</p>
                </div>
            </div>

            <!-- Floating Jump-to-Bottom Pill (Quando usuário rolou para cima) -->
            <div id="chat-jump-bottom-btn" onclick="scrollToChatBottom(true)" class="hidden absolute bottom-5 right-6 z-20 bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold text-xs px-4 py-2 rounded-full shadow-2xl border-2 border-indigo-400 cursor-pointer flex items-center gap-2 animate-bounce">
                <i class="fas fa-arrow-down text-xs"></i> <span>Novas mensagens abaixo</span>
            </div>

            <!-- Footer do Chat (Status de Conexão) -->
            <div class="px-4 py-2 border-t border-white/10 bg-black/40 flex items-center justify-between text-xs text-white/50 font-semibold shrink-0">
                <span class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span id="chat-status-text" class="text-emerald-300 font-bold">Captura Ativa</span>
                </span>
                <span id="chat-active-filter-label" class="font-bold text-white/70">Todas as redes • Destaque de compras ativo</span>
            </div>
        </section>
    </main>

    <!-- Bottom subtle bar -->
    <footer class="w-full text-center pb-2.5 pt-1 text-xs text-white/40 font-semibold z-10 flex items-center justify-center gap-4">
        <span>Minha Mania Live Studio</span>
        <span>•</span>
        <span id="syncIndicator" class="flex items-center gap-1.5 text-emerald-400/90 font-black">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            Sincronizado
        </span>
        <span class="hidden sm:inline">•</span>
        <span class="hidden sm:inline text-white/30">Pressione 'F' para Tela Cheia • '1' Só Telão • '2' Dividido • '3' Só Chat</span>
    </footer>

    <!-- Invisible Auto-Focus Input for USB / Bluetooth Barcode Scanners -->
    <input 
        type="text" 
        id="barcodeWedgeInput" 
        autocomplete="off" 
        autocorrect="off" 
        autocapitalize="off" 
        spellcheck="false" 
        style="position: fixed; top: -1000px; left: -1000px; opacity: 0; pointer-events: none; width: 1px; height: 1px;"
    />

    <script>
        // =========================================================================
        // ESTADO GLOBAL & CONFIGURAÇÕES
        // =========================================================================
        let activeLiveId = "{{ $activeLive ? $activeLive->id : '' }}";
        const csrfToken = "{{ csrf_token() }}";
        const linkItemUrl = "{{ route('admin.live-chat.link-item-live') }}";
        let currentPieceCode = "{{ $initialPieceCode }}";
        let soundEnabled = true;
        let showDetails = true;
        let isPolling = false;
        let isProcessingScan = false;
        let idleTimer = null;
        let toastTimer = null;

        // Chat Feed State
        let allLiveMessages = [];
        let currentChatFilter = 'all'; // 'all', 'marked', 'instagram', 'tiktok'
        let autoScrollEnabled = true;
        let isUserScrollingChat = false;
        let chatFontSizeMode = 'large'; // Default: 'large' para leitura de estúdio à distância!
        let layoutMode = 'split'; // 'split', 'counter', 'chat'

        // DOM Elements
        const counterEl = document.getElementById('counterNumber');
        const totalItemsCountEl = document.getElementById('totalItemsCount');
        const totalBipadosCountEl = document.getElementById('totalBipadosCount');
        const totalLiveValueTextEl = document.getElementById('totalLiveValueText');
        const liveTotalValueBadge = document.getElementById('liveTotalValueBadge');
        const lastItemContainer = document.getElementById('lastItemContainer');
        const lastItemCode = document.getElementById('lastItemCode');
        const lastItemName = document.getElementById('lastItemName');
        const lastItemPrice = document.getElementById('lastItemPrice');
        const lastItemTime = document.getElementById('lastItemTime');
        const controlsBar = document.getElementById('controlsBar');
        const syncIndicator = document.getElementById('syncIndicator');
        const bodyEl = document.getElementById('bodyEl');
        const barcodeInput = document.getElementById('barcodeWedgeInput');
        const chatContainer = document.getElementById('telao-chat-messages-container');
        const chatMsgsCounter = document.getElementById('chat-msgs-counter');
        const jumpBottomBtn = document.getElementById('chat-jump-bottom-btn');
        const mainStageContainer = document.getElementById('mainStageContainer');
        const colCounterPanel = document.getElementById('col-counter-panel');
        const colChatPanel = document.getElementById('col-chat-panel');

        // =========================================================================
        // FOCO PERMANENTE NO LEITOR DE CÓDIGOS DE BARRA
        // =========================================================================
        function ensureScannerFocus() {
            if (!barcodeInput) return;
            const activeTag = document.activeElement ? document.activeElement.tagName : '';
            if (activeTag !== 'SELECT' && activeTag !== 'TEXTAREA' && activeTag !== 'INPUT') {
                barcodeInput.focus();
            }
        }
        setInterval(ensureScannerFocus, 1000);
        document.addEventListener('click', () => setTimeout(ensureScannerFocus, 50));

        // =========================================================================
        // SINTETIZADORES DE ÁUDIO WEB AUDIO API
        // =========================================================================
        function playChime() {
            if (!soundEnabled) return;
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const now = ctx.currentTime;
                
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(587.33, now);
                gain1.gain.setValueAtTime(0.25, now);
                gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.18);
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.start(now);
                osc1.stop(now + 0.18);

                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(880, now + 0.08);
                gain2.gain.setValueAtTime(0.35, now + 0.08);
                gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.start(now + 0.08);
                osc2.stop(now + 0.35);
            } catch (e) {
                console.warn('Audio Context error:', e);
            }
        }

        function playErrorTone() {
            if (!soundEnabled) return;
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const now = ctx.currentTime;
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();

                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(180, now);
                osc.frequency.setValueAtTime(140, now + 0.12);

                gain.gain.setValueAtTime(0.3, now);
                gain.gain.exponentialRampToValueAtTime(0.01, now + 0.3);

                osc.connect(gain);
                gain.connect(ctx.destination);

                osc.start(now);
                osc.stop(now + 0.3);
            } catch (e) {
                console.warn('Audio Context error:', e);
            }
        }

        // =========================================================================
        // ANIMAÇÃO DE CONTADOR & VALORES
        // =========================================================================
        function animateCounterChange(newVal) {
            counterEl.textContent = newVal;
            counterEl.classList.remove('animate-pop');
            void counterEl.offsetWidth;
            counterEl.classList.add('animate-pop');
        }

        function animateTotalValueChange(formattedVal) {
            if (!totalLiveValueTextEl || !formattedVal) return;
            totalLiveValueTextEl.textContent = formattedVal;
            if (liveTotalValueBadge) {
                liveTotalValueBadge.classList.remove('scale-105', 'bg-emerald-500/25');
                void liveTotalValueBadge.offsetWidth;
                liveTotalValueBadge.classList.add('scale-105', 'bg-emerald-500/25');
                setTimeout(() => {
                    liveTotalValueBadge.classList.remove('scale-105', 'bg-emerald-500/25');
                }, 350);
            }
        }

        // =========================================================================
        // TOAST FEEDBACK
        // =========================================================================
        function showScanToast(type, title, subtitle) {
            const toast = document.getElementById('scanFeedbackToast');
            const toastCard = document.getElementById('toastCard');
            const iconBox = document.getElementById('toastIconBox');
            const icon = document.getElementById('toastIcon');
            const titleEl = document.getElementById('toastTitle');
            const subEl = document.getElementById('toastSubtitle');

            if (!toast) return;

            if (type === 'success') {
                toastCard.className = 'px-5 py-3.5 rounded-2xl shadow-2xl backdrop-blur-xl border border-emerald-500/40 flex items-center gap-3.5 text-white bg-emerald-950/90';
                iconBox.className = 'w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center text-lg shrink-0';
                icon.className = 'fas fa-check';
            } else if (type === 'warning' || type === 'already_biped') {
                toastCard.className = 'px-5 py-3.5 rounded-2xl shadow-2xl backdrop-blur-xl border border-amber-500/50 flex items-center gap-3.5 text-white bg-amber-950/95 ring-2 ring-amber-400/30';
                iconBox.className = 'w-10 h-10 rounded-xl bg-amber-500/25 border border-amber-500/50 text-amber-300 flex items-center justify-center text-lg shrink-0';
                icon.className = 'fas fa-history';
            } else {
                toastCard.className = 'px-5 py-3.5 rounded-2xl shadow-2xl backdrop-blur-xl border border-rose-500/40 flex items-center gap-3.5 text-white bg-rose-950/90';
                iconBox.className = 'w-10 h-10 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-400 flex items-center justify-center text-lg shrink-0';
                icon.className = 'fas fa-exclamation-triangle';
            }

            titleEl.textContent = title;
            subEl.textContent = subtitle;

            toast.classList.remove('opacity-0', '-translate-y-6');
            toast.classList.add('opacity-100', 'translate-y-0');

            if (toastTimer) clearTimeout(toastTimer);
            toastTimer = setTimeout(() => {
                toast.classList.remove('opacity-100', 'translate-y-0');
                toast.classList.add('opacity-0', '-translate-y-6');
            }, 4000);
        }

        // =========================================================================
        // PROCESSAMENTO DE BIPAGEM VIA LEITOR
        // =========================================================================
        async function processBipagem(barcode) {
            if (!barcode) return;
            let cleanBar = String(barcode).trim().replace(/^[\r\n\s]+|[\r\n\s]+$/g, '');
            if (!cleanBar) return;

            if (isProcessingScan) return;
            isProcessingScan = true;

            try {
                const res = await fetch(linkItemUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        live_id: activeLiveId || null,
                        code: cleanBar
                    })
                });

                const data = await res.json();

                if (data.success && data.data) {
                    const item = data.data;

                    if (item.live_id && !activeLiveId) {
                        activeLiveId = item.live_id;
                    }

                    const pieceNum = item.piece_code || item.codigo_live || item.code || cleanBar;
                    if (pieceNum !== undefined) {
                        currentPieceCode = pieceNum;
                        animateCounterChange(currentPieceCode);
                    }

                    if (item.total_valor_formatado) {
                        animateTotalValueChange(item.total_valor_formatado);
                    }

                    if (item.total_sacolinhas_itens !== undefined && totalItemsCountEl) {
                        totalItemsCountEl.textContent = item.total_sacolinhas_itens;
                    }

                    if (item.total_bipados !== undefined && totalBipadosCountEl) {
                        totalBipadosCountEl.textContent = item.total_bipados;
                    }

                    lastItemCode.textContent = '#' + (item.codigo_live || item.code || cleanBar);
                    lastItemName.textContent = item.name || 'Produto';
                    lastItemPrice.textContent = item.price || '';
                    lastItemTime.textContent = item.hora || new Date().toLocaleTimeString('pt-BR');
                    if (showDetails) {
                        lastItemContainer.classList.remove('hidden');
                    }

                    if (item.is_already_in_live) {
                        const buyerText = item.buyer_username ? ` • Sacola de @${item.buyer_username}` : '';
                        showScanToast('warning', `⚠️ Item #${item.code || cleanBar} Já Cadastrado!`, `Código da Live: #${item.codigo_live} • ${item.name || 'Produto'}${item.price ? ' (' + item.price + ')' : ''}${buyerText}`);
                    } else {
                        const seqInfo = item.codigo_live ? `Sequência #${item.codigo_live}` : '';
                        const priceInfo = item.price ? ` • ${item.price}` : '';
                        showScanToast('success', `Item #${item.code || cleanBar} Anexado à Live!`, `${item.name || 'Produto'}${priceInfo} ${seqInfo ? '(' + seqInfo + ')' : ''}`);
                    }

                    playChime();
                    localStorage.setItem('last_live_item_biped', Date.now());
                } else {
                    showScanToast('error', 'Item Não Vinculado', data.message || `Código #${cleanBar} não encontrado.`);
                    playErrorTone();
                }
            } catch (err) {
                console.error("Erro na bipagem:", err);
                showScanToast('error', 'Falha na Conexão', `Não foi possível registrar o código #${cleanBar}.`);
                playErrorTone();
            } finally {
                isProcessingScan = false;
                if (barcodeInput) barcodeInput.value = '';
                ensureScannerFocus();
            }
        }

        if (barcodeInput) {
            barcodeInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const code = barcodeInput.value.trim();
                    barcodeInput.value = '';
                    if (code.length > 0) {
                        processBipagem(code);
                    }
                }
            });
        }

        // ZERO-FOCUS BARCODE SCANNER LISTENER GLOBAL
        let scanBuffer = '';
        let lastKeyTime = 0;

        document.addEventListener('keydown', (e) => {
            if (e.target && (e.target.tagName === 'SELECT' || e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA')) {
                return;
            }

            const now = Date.now();
            const timeDiff = now - lastKeyTime;

            if (e.key === 'Enter') {
                const code = (scanBuffer || (barcodeInput ? barcodeInput.value : '')).trim();
                scanBuffer = '';
                if (barcodeInput) barcodeInput.value = '';
                if (code.length >= 1) {
                    e.preventDefault();
                    processBipagem(code);
                    return;
                }
            }

            if (timeDiff > 600) {
                scanBuffer = '';
            }

            // Atalhos numéricos rápidos
            if (scanBuffer.length === 0 && !/\d/.test(e.key)) {
                if (e.key === 'f' || e.key === 'F') {
                    toggleFullScreen();
                    return;
                } else if (e.key === '1') {
                    setLayoutMode('counter');
                    return;
                } else if (e.key === '2') {
                    setLayoutMode('split');
                    return;
                } else if (e.key === '3') {
                    setLayoutMode('chat');
                    return;
                } else if (e.key === 'd' || e.key === 'D') {
                    toggleDetails();
                    return;
                } else if (e.key === 's' || e.key === 'S') {
                    toggleSound();
                    return;
                }
            }

            if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
                scanBuffer += e.key;
                lastKeyTime = now;
            }
        });

        // =========================================================================
        // DESTAQUES DE LEITURA & INTENÇÃO DE COMPRA (QUERO / CÓDIGOS)
        // =========================================================================
        let lastRenderedChatHash = '';

        function escapeHtml(text) {
            if (!text) return '';
            return String(text)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function safeAttr(text) {
            if (!text) return '';
            return String(text).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function highlightPresenterKeywords(text) {
            if (!text) return '';
            let safe = escapeHtml(text);

            // 1. Destaca palavras de intenção de compra da cliente (QUERO, MEU, FICO, etc.)
            const intentRegex = /\b(QUERO|EU QUERO|MEU|MINHA|FICO|FICO COM|RESERVA|RESERVO|PEGA|PEGO|COMPRO|PASSA|ME LEVA|MINHA PECA|MINHA PEÇA)\b/gi;
            safe = safe.replace(intentRegex, '<span class="font-black text-emerald-300 bg-emerald-500/25 border border-emerald-400/50 px-2 py-0.5 rounded-lg shadow-sm tracking-wide uppercase">$1</span>');

            // 2. Destaca códigos (#123)
            safe = safe.replace(/(#\d{1,6})/gi, '<span class="telao-code-badge">$1</span>');

            // 3. Destaca números isolados (ex: "quero 25", "meu 40")
            safe = safe.replace(/(^|\s)(\d{1,5})($|\s|[.,!?])/g, '$1<span class="telao-code-badge">#$2</span>$3');

            return safe;
        }

        function getGradientForUser(username) {
            const gradients = [
                'linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)',
                'linear-gradient(135deg, #059669 0%, #0d9488 100%)',
                'linear-gradient(135deg, #d97706 0%, #ea580c 100%)',
                'linear-gradient(135deg, #db2777 0%, #9333ea 100%)',
                'linear-gradient(135deg, #2563eb 0%, #06b6d4 100%)',
                'linear-gradient(135deg, #e11d48 0%, #c026d3 100%)'
            ];
            let hash = 0;
            for (let i = 0; i < (username || '').length; i++) {
                hash = username.charCodeAt(i) + ((hash << 5) - hash);
            }
            return gradients[Math.abs(hash) % gradients.length];
        }

        // =========================================================================
        // RENDERIZAÇÃO DO CHAT COM MÁXIMA LEGIBILIDADE PARA APRESENTADORA
        // =========================================================================
        function renderTelaoChatFeed() {
            if (!chatContainer) return;

            let list = allLiveMessages;

            // Filtros
            if (currentChatFilter === 'instagram') {
                list = list.filter(m => m.plataforma === 'instagram');
            } else if (currentChatFilter === 'tiktok') {
                list = list.filter(m => m.plataforma === 'tiktok');
            } else if (currentChatFilter === 'marked') {
                list = list.filter(m => !!m.is_marked);
            }

            const linkedHash = list.map(m => m.linked_code || m.linked_item_id || '').join(',');
            const currentHash = `${currentChatFilter}:${list.length}:${list.length > 0 ? list[0].id : 0}:${list.filter(m => m.is_marked).length}:${chatFontSizeMode}:${linkedHash}`;
            if (currentHash === lastRenderedChatHash && chatContainer.innerHTML.trim().length > 50) {
                return;
            }
            lastRenderedChatHash = currentHash;

            if (chatMsgsCounter) {
                chatMsgsCounter.textContent = allLiveMessages.length;
            }

            if (list.length === 0) {
                chatContainer.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full text-white/40 py-16 text-center">
                        <i class="fas ${currentChatFilter === 'marked' ? 'fa-star text-amber-400' : 'fa-comment-slash'} text-5xl mb-3 text-white/20"></i>
                        <p class="text-sm font-black text-white/80">${currentChatFilter === 'marked' ? 'Nenhum comentário marcado' : 'Aguardando comentários...'}</p>
                        <p class="text-xs text-white/50 mt-1">${currentChatFilter === 'marked' ? 'Comentários favoritados aparecerão aqui.' : 'Nenhuma mensagem recebida para este filtro.'}</p>
                    </div>
                `;
                return;
            }

            // Escala de tamanhos de fonte otimizada para o Telão
            let fontStyles = {
                username: 'text-sm sm:text-base font-black',
                clientName: 'text-xs sm:text-sm font-extrabold',
                message: 'text-base sm:text-lg font-bold leading-relaxed',
                time: 'text-xs font-mono',
                avatarBox: 'w-12 h-12 sm:w-14 sm:h-14',
                avatarText: 'text-sm sm:text-base font-black',
                padding: 'p-3.5 sm:p-4',
                linkedBanner: 'text-xs sm:text-sm'
            };

            if (chatFontSizeMode === 'large') {
                // 1.5x (PADRÃO RECOMENDADO PARA ESTÚDIO)
                fontStyles = {
                    username: 'text-base sm:text-lg font-black',
                    clientName: 'text-sm sm:text-base font-black',
                    message: 'text-xl sm:text-2xl font-extrabold leading-snug tracking-tight',
                    time: 'text-xs sm:text-sm font-mono',
                    avatarBox: 'w-14 h-14 sm:w-16 sm:h-16',
                    avatarText: 'text-base sm:text-lg font-black',
                    padding: 'p-4 sm:p-5',
                    linkedBanner: 'text-sm sm:text-base'
                };
            } else if (chatFontSizeMode === 'huge') {
                // 2x (TELÃO GIGANTE À LONGA DISTÂNCIA)
                fontStyles = {
                    username: 'text-lg sm:text-xl font-black',
                    clientName: 'text-base sm:text-lg font-black',
                    message: 'text-2xl sm:text-3xl font-black leading-snug tracking-tight',
                    time: 'text-sm sm:text-base font-mono',
                    avatarBox: 'w-16 h-16 sm:w-20 sm:h-20',
                    avatarText: 'text-lg sm:text-xl font-black',
                    padding: 'p-5 sm:p-6',
                    linkedBanner: 'text-base sm:text-lg'
                };
            }

            // Ordem cronológica: mais recentes no final para leitura natural de chat ao vivo
            const chronologicalList = [...list].reverse();

            let html = '';
            chronologicalList.forEach(msg => {
                const isTikTok = msg.plataforma === 'tiktok';
                const isLinkedMsg = !!(msg.linked_code || msg.linked_item_id || msg.linked_live_code);
                const platformClass = isLinkedMsg ? 'is-linked-msg' : (isTikTok ? 'platform-tiktok' : 'platform-instagram');
                
                const platformIcon = isTikTok 
                    ? '<i class="fab fa-tiktok" style="color: #22d3ee;"></i>' 
                    : '<i class="fab fa-instagram" style="color: #ffffff;"></i>';

                const platformBadgeStyle = isTikTok 
                    ? 'background-color: #000000; border: 1.5px solid #22d3ee;' 
                    : 'background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); border: 1.5px solid #ffffff;';

                const cleanUser = msg.username || 'usuario';
                const realClientName = msg.user_name || msg.user_apelido || '';
                const hasRegisteredClient = !!(msg.user_id && realClientName);
                const initials = cleanUser.slice(0, 2).toUpperCase();
                const time = msg.created_at ? new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }) : '';
                const isMarked = !!msg.is_marked;

                // Extração dos dados da peça vinculada (se houver)
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

                // Tag de Item no Header
                let linkedHeaderBadge = '';
                let linkedItemBanner = '';
                if (isLinkedMsg) {
                    let codeBadgeText = cleanLiveCode ? (`Seq #${cleanLiveCode}`) : (cleanItemCode ? `#${cleanItemCode}` : 'Vendido');
                    if (cleanLiveCode && cleanItemCode && cleanLiveCode !== cleanItemCode) {
                        codeBadgeText = `Seq #${cleanLiveCode} • #${cleanItemCode}`;
                    }
                    linkedHeaderBadge = `
                        <span class="bg-blue-600 text-white border-2 border-blue-400 text-xs sm:text-sm font-black px-2.5 py-0.5 rounded-xl shrink-0 flex items-center gap-1.5 shadow-md animate-pulse">
                            <i class="fas fa-shopping-bag text-blue-200"></i> ${escapeHtml(codeBadgeText)}
                        </span>
                    `;

                    let prodName = msg.linked_product_name || 'Peça Vinculada à Sacolinha';
                    let prodDetails = msg.linked_product_details || '';
                    let prodTam = msg.linked_product_tamanho || '';
                    let prodCor = msg.linked_product_cor || '';
                    let prodPrice = msg.linked_product_preco || '';

                    linkedItemBanner = `
                        <div class="mt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-800 text-white p-3 sm:p-3.5 rounded-2xl shadow-xl border-2 border-blue-400/90 ${fontStyles.linkedBanner}">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-white/20 flex items-center justify-center text-white shrink-0 text-base sm:text-xl font-black shadow-inner border border-white/30">
                                    <i class="fas fa-tag"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-black text-white truncate flex items-center gap-2">
                                        <span class="text-white drop-shadow">${escapeHtml(prodName)}</span>
                                        ${cleanLiveCode ? `<span class="bg-white/25 text-blue-100 text-xs px-2 py-0.5 rounded-lg font-mono font-black border border-white/30">Live #${escapeHtml(cleanLiveCode)}</span>` : ''}
                                    </div>
                                    <div class="text-blue-100 font-bold flex flex-wrap items-center gap-2 mt-0.5 text-xs sm:text-sm">
                                        ${cleanItemCode ? `<span class="font-mono bg-black/20 px-1.5 py-0.2 rounded">Cód: #${escapeHtml(cleanItemCode)}</span>` : ''}
                                        ${prodTam ? `<span>• Tam: <strong>${escapeHtml(prodTam)}</strong></span>` : ''}
                                        ${prodCor ? `<span>• Cor: <strong>${escapeHtml(prodCor)}</strong></span>` : ''}
                                        ${prodPrice ? `<span class="text-emerald-300 font-black text-sm sm:text-base drop-shadow">• ${escapeHtml(prodPrice)}</span>` : ''}
                                    </div>
                                </div>
                            </div>
                            <div class="shrink-0 flex items-center gap-2">
                                <span class="bg-emerald-500 text-white font-black text-xs sm:text-sm px-3 py-1.5 rounded-xl border-2 border-emerald-300 shadow-md flex items-center gap-1.5">
                                    <i class="fas fa-check-circle"></i> VENDIDO / NA SACOLA
                                </span>
                            </div>
                        </div>
                    `;
                }

                // Identificação de Origem do Canal (Minha Mania vs Loja Parceira)
                const isPartnerAccount = msg.host_account && !['minhamania', '_minhamania', 'de_minha_mania'].includes(msg.host_account.toLowerCase().replace(/^@/, ''));
                const hostBadge = isPartnerAccount
                    ? `<span class="bg-amber-500/25 text-amber-300 border-2 border-amber-400 text-xs sm:text-sm font-black px-2.5 py-0.5 rounded-xl shrink-0 flex items-center gap-1.5 shadow-sm" title="Audiência da Loja Parceira (@${escapeHtml(msg.host_account)})"><i class="fas fa-store text-amber-400"></i> Loja @${escapeHtml(msg.host_account)}</span>`
                    : (msg.host_account ? `<span class="bg-purple-500/25 text-purple-200 border-2 border-purple-400 text-xs sm:text-sm font-black px-2.5 py-0.5 rounded-xl shrink-0 flex items-center gap-1.5 shadow-sm"><i class="fas fa-crown text-purple-300"></i> Minha Mania</span>` : '');

                // Badge de Cliente Cadastrada com Nome Real em Destaque
                const clientBadge = hasRegisteredClient
                    ? `<span class="bg-emerald-500/25 text-emerald-300 border-2 border-emerald-400 ${fontStyles.clientName} px-2.5 py-0.5 rounded-xl shrink-0 flex items-center gap-1.5 shadow-sm"><i class="fas fa-star text-emerald-400"></i> ${escapeHtml(realClientName)}</span>`
                    : (msg.user_id ? `<span class="bg-emerald-500/25 text-emerald-300 border-2 border-emerald-400 text-xs sm:text-sm font-black px-2 py-0.5 rounded-xl shrink-0 flex items-center gap-1"><i class="fas fa-star text-emerald-400"></i> Cliente</span>` : '');

                const handleColorClass = isTikTok ? 'text-cyan-300' : 'text-pink-300';

                // Avatar com alta resolução e contraste
                const gradientBg = getGradientForUser(cleanUser);
                const avatarHtml = msg.avatar_url
                    ? `<img src="${safeAttr(msg.avatar_url)}" alt="@${safeAttr(cleanUser)}" class="w-full h-full object-cover rounded-2xl border-2 border-white/20" onerror="this.outerHTML='<div class=\\'w-full h-full rounded-2xl flex items-center justify-center ${fontStyles.avatarText} text-white border-2 border-white/20\\' style=\\'background: ${gradientBg}\\'>${initials}</div>'">`
                    : `<div class="w-full h-full rounded-2xl flex items-center justify-center ${fontStyles.avatarText} text-white shadow-inner border-2 border-white/20" style="background: ${gradientBg};">${initials}</div>`;

                const formattedMsg = highlightPresenterKeywords(msg.message);

                html += `
                    <div class="telao-chat-card ${platformClass} ${isMarked ? 'is-marked' : ''} rounded-3xl ${fontStyles.padding} flex items-start gap-3.5 sm:gap-4.5 msg-entry-animate" title="${isLinkedMsg ? 'Peça vinculada a este comentário' : ''}">
                        <!-- Avatar & Platform Badge -->
                        <div class="shrink-0 relative">
                            <div class="${fontStyles.avatarBox} rounded-2xl overflow-hidden shadow-lg">
                                ${avatarHtml}
                            </div>
                            <span class="absolute -bottom-1.5 -right-1.5 w-6 h-6 rounded-full flex items-center justify-center text-xs shadow-md" style="${platformBadgeStyle}">
                                ${platformIcon}
                            </span>
                        </div>

                        <!-- Conteúdo da Mensagem -->
                        <div class="min-w-0 flex-1">
                            <!-- Linha 1: Identificação de Quem Fala -->
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                <div class="flex flex-wrap items-center gap-2 min-w-0">
                                    <span class="${fontStyles.username} ${handleColorClass} tracking-tight truncate max-w-[220px] sm:max-w-[320px]">
                                        @${escapeHtml(cleanUser)}
                                    </span>
                                    ${clientBadge}
                                    ${hostBadge}
                                    ${linkedHeaderBadge}
                                </div>
                                <span class="${fontStyles.time} text-white/50 shrink-0 bg-black/30 px-2 py-0.5 rounded-lg border border-white/10 font-bold">${time}</span>
                            </div>

                            <!-- Linha 2: Texto da Mensagem (Destaque para Apresentadora) -->
                            <p class="${fontStyles.message} text-white break-words">
                                ${formattedMsg}
                            </p>

                            <!-- Linha 3: Banner do Item Vinculado (se houver) -->
                            ${linkedItemBanner}
                        </div>

                        <!-- Estrela se Marcada -->
                        ${isMarked ? `
                            <div class="shrink-0 text-amber-400 text-lg sm:text-2xl pt-1">
                                <i class="fas fa-star drop-shadow-md"></i>
                            </div>
                        ` : ''}
                    </div>
                `;
            });

            chatContainer.innerHTML = html;

            if (autoScrollEnabled && !isUserScrollingChat) {
                scrollToChatBottom(false);
            }
        }

        // =========================================================================
        // CONTROLE DE ROLAGEM & ZOOM DE FONTE
        // =========================================================================
        function scrollToChatBottom(smooth = false) {
            if (!chatContainer) return;
            chatContainer.scrollTo({
                top: chatContainer.scrollHeight,
                behavior: smooth ? 'smooth' : 'auto'
            });
            if (jumpBottomBtn) jumpBottomBtn.classList.add('hidden');
        }

        function toggleAutoScroll() {
            autoScrollEnabled = !autoScrollEnabled;
            const btn = document.getElementById('btn-autoscroll');
            const icon = document.getElementById('autoscroll-icon');
            if (autoScrollEnabled) {
                btn.className = 'px-2.5 py-1 text-xs font-bold text-emerald-400 bg-white/10 hover:bg-white/20 rounded-xl transition flex items-center gap-1 border border-white/10';
                icon.className = 'fas fa-arrow-down text-xs';
                scrollToChatBottom(true);
            } else {
                btn.className = 'px-2.5 py-1 text-xs font-bold text-amber-400 bg-white/10 hover:bg-white/20 rounded-xl transition flex items-center gap-1 border border-amber-500/30';
                icon.className = 'fas fa-pause text-xs';
            }
        }

        if (chatContainer) {
            chatContainer.addEventListener('scroll', () => {
                const scrollPos = chatContainer.scrollTop + chatContainer.clientHeight;
                const distanceToBottom = chatContainer.scrollHeight - scrollPos;
                if (distanceToBottom > 80) {
                    isUserScrollingChat = true;
                    if (jumpBottomBtn) jumpBottomBtn.classList.remove('hidden');
                } else {
                    isUserScrollingChat = false;
                    if (jumpBottomBtn) jumpBottomBtn.classList.add('hidden');
                }
            });
        }

        function setChatFilter(filter) {
            currentChatFilter = filter;
            ['all', 'marked', 'instagram', 'tiktok'].forEach(tab => {
                const el = document.getElementById(`tab-chat-${tab}`);
                if (!el) return;
                if (tab === filter) {
                    el.className = 'px-2.5 py-1 rounded-lg text-white bg-purple-600 shadow-xs transition';
                } else {
                    el.className = 'px-2.5 py-1 rounded-lg text-white/70 hover:text-white hover:bg-white/10 transition';
                }
            });

            const labelEl = document.getElementById('chat-active-filter-label');
            if (labelEl) {
                const labels = {
                    'all': 'Todas as redes • Destaque de compras ativo',
                    'marked': 'Apenas marcadas • Destaque ativo',
                    'instagram': 'Instagram • Destaque ativo',
                    'tiktok': 'TikTok • Destaque ativo'
                };
                labelEl.textContent = labels[filter] || filter;
            }

            renderTelaoChatFeed();
        }

        function setChatFontSize(mode) {
            chatFontSizeMode = mode;
            ['normal', 'large', 'huge'].forEach(m => {
                const b = document.getElementById(`btn-font-${m}`);
                if (!b) return;
                if (m === mode) {
                    b.className = 'px-2.5 py-1 rounded-lg text-white bg-indigo-600 shadow-sm transition';
                } else {
                    b.className = 'px-2.5 py-1 rounded-lg text-white/70 hover:text-white hover:bg-white/10 transition';
                }
            });

            localStorage.setItem('live_telao_font_size', mode);
            lastRenderedChatHash = '';
            renderTelaoChatFeed();
        }

        const savedFontSize = localStorage.getItem('live_telao_font_size');
        if (savedFontSize && ['normal', 'large', 'huge'].includes(savedFontSize)) {
            setChatFontSize(savedFontSize);
        } else {
            setChatFontSize('large'); // 1.5x por padrão para apresentadora
        }

        function setLayoutMode(mode) {
            layoutMode = mode;
            
            ['split', 'counter', 'chat'].forEach(m => {
                const b = document.getElementById(`btn-layout-${m}`);
                if (!b) return;
                if (m === mode) {
                    b.className = 'px-2.5 py-1 rounded-lg font-bold text-white bg-indigo-600 shadow-sm transition flex items-center gap-1';
                } else {
                    b.className = 'px-2.5 py-1 rounded-lg font-bold text-white/70 hover:text-white hover:bg-white/10 transition flex items-center gap-1';
                }
            });

            if (mode === 'split') {
                mainStageContainer.className = 'flex-1 w-full max-w-[1780px] mx-auto px-3 sm:px-5 py-2 grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 min-h-0 overflow-hidden items-stretch';
                colCounterPanel.classList.remove('hidden');
                colChatPanel.classList.remove('hidden');
                counterEl.style.fontSize = 'clamp(8rem, 18vw, 24rem)';
            } else if (mode === 'counter') {
                mainStageContainer.className = 'flex-1 w-full max-w-6xl mx-auto px-4 py-2 flex flex-col justify-center items-center min-h-0 overflow-hidden';
                colCounterPanel.classList.remove('hidden');
                colChatPanel.classList.add('hidden');
                counterEl.style.fontSize = 'clamp(10rem, 28vw, 36rem)';
            } else if (mode === 'chat') {
                mainStageContainer.className = 'flex-1 w-full max-w-5xl mx-auto px-4 py-2 flex flex-col justify-center items-stretch min-h-0 overflow-hidden';
                colCounterPanel.classList.add('hidden');
                colChatPanel.classList.remove('hidden');
            }

            localStorage.setItem('live_telao_layout_mode', mode);
            setTimeout(() => {
                if (mode !== 'counter') scrollToChatBottom(false);
            }, 100);
        }

        const savedLayout = localStorage.getItem('live_telao_layout_mode');
        if (savedLayout && ['split', 'counter', 'chat'].includes(savedLayout)) {
            setLayoutMode(savedLayout);
        }

        // =========================================================================
        // POLLING PRINCIPAL DE DADOS (CONTADOR + CHAT DATA)
        // =========================================================================
        async function fetchLiveStudioData() {
            if (isPolling) return;
            isPolling = true;

            try {
                if (activeLiveId) {
                    const chatUrl = `/admin/lives/${encodeURIComponent(activeLiveId)}/chat-data?limit=250&_t=${Date.now()}`;
                    const res = await fetch(chatUrl);
                    if (res.ok) {
                        const chatData = await res.json();
                        if (chatData && chatData.messages) {
                            allLiveMessages = chatData.messages;
                            renderTelaoChatFeed();
                        }
                    }
                }

                const liveParam = activeLiveId ? `live_id=${encodeURIComponent(activeLiveId)}&` : '';
                const counterUrl = `{{ route('api.live-contador.data') }}?${liveParam}_t=${Date.now()}`;
                const resCounter = await fetch(counterUrl);
                
                if (resCounter.ok) {
                    const data = await resCounter.json();
                    if (data && data.success) {
                        if (data.live_id && !activeLiveId) {
                            activeLiveId = data.live_id;
                        }

                        const pieceNum = data.piece_code !== undefined ? data.piece_code : data.count;
                        if (pieceNum !== undefined && String(pieceNum) !== String(currentPieceCode)) {
                            currentPieceCode = pieceNum;
                            animateCounterChange(currentPieceCode);
                            playChime();
                        }

                        if (data.total_sacolinhas_itens !== undefined && totalItemsCountEl) {
                            totalItemsCountEl.textContent = data.total_sacolinhas_itens;
                        }

                        if (data.total_valor_formatado) {
                            animateTotalValueChange(data.total_valor_formatado);
                        }

                        if (data.total_bipados !== undefined && totalBipadosCountEl) {
                            totalBipadosCountEl.textContent = data.total_bipados;
                        }

                        if (data.last_item) {
                            lastItemCode.textContent = '#' + (data.last_item.codigo || '---');
                            lastItemName.textContent = data.last_item.nome || 'Produto';
                            lastItemPrice.textContent = data.last_item.preco || '';
                            lastItemTime.textContent = data.last_item.hora || '';
                            if (showDetails) {
                                lastItemContainer.classList.remove('hidden');
                            }
                        }

                        syncIndicator.innerHTML = `<span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Sincronizado`;
                    }
                }
            } catch (err) {
                syncIndicator.innerHTML = `<span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span> Reconectando...`;
            } finally {
                isPolling = false;
            }
        }

        setInterval(fetchLiveStudioData, 1800);
        fetchLiveStudioData();

        window.addEventListener('storage', (e) => {
            if (e.key === 'last_live_item_biped' || e.key === 'last_live_chat_message') {
                fetchLiveStudioData();
            }
        });

        // =========================================================================
        // CONTROLES DE TEMA & INTERFACE
        // =========================================================================
        function changeTheme(themeClass) {
            bodyEl.className = `${themeClass} h-screen w-screen flex flex-col justify-between items-center relative select-none`;
            localStorage.setItem('live_contador_theme', themeClass);
        }

        const savedTheme = localStorage.getItem('live_contador_theme');
        if (savedTheme) {
            const themeSelect = document.getElementById('themeSelect');
            if (themeSelect) themeSelect.value = savedTheme;
            changeTheme(savedTheme);
        }

        function toggleDetails() {
            showDetails = !showDetails;
            if (showDetails) {
                lastItemContainer.classList.remove('hidden');
                document.getElementById('toggleDetailsBtn').classList.add('text-purple-400');
            } else {
                lastItemContainer.classList.add('hidden');
                document.getElementById('toggleDetailsBtn').classList.remove('text-purple-400');
            }
            localStorage.setItem('live_contador_show_details', showDetails ? '1' : '0');
        }

        function toggleSound() {
            soundEnabled = !soundEnabled;
            const icon = document.getElementById('soundIcon');
            const btn = document.getElementById('toggleSoundBtn');
            if (soundEnabled) {
                icon.className = 'fas fa-volume-up text-[10px] text-emerald-400';
                btn.classList.add('bg-white/10');
                playChime();
            } else {
                icon.className = 'fas fa-volume-mute text-[10px] text-white/50';
                btn.classList.remove('bg-white/10');
            }
            localStorage.setItem('live_contador_sound', soundEnabled ? '1' : '0');
        }

        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
                document.getElementById('fsIcon').className = 'fas fa-compress text-[10px]';
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                    document.getElementById('fsIcon').className = 'fas fa-expand text-[10px]';
                }
            }
        }

        function resetIdleTimer() {
            controlsBar.classList.remove('controls-hidden');
            clearTimeout(idleTimer);
            idleTimer = setTimeout(() => {
                controlsBar.classList.add('controls-hidden');
            }, 4500);
        }

        window.addEventListener('mousemove', resetIdleTimer);
        window.addEventListener('touchstart', resetIdleTimer);
        resetIdleTimer();

        function triggerEasterEgg() {
            counterEl.classList.remove('animate-pop');
            void counterEl.offsetWidth;
            counterEl.classList.add('animate-pop');
        }
    </script>
</body>
</html>

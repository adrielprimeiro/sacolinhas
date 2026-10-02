<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contador da Live • Minha Mania</title>
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
            background-color: #09090b;
            color: #ffffff;
        }
        .theme-dark .counter-num {
            color: #ffffff;
            text-shadow: 0 0 60px rgba(168, 85, 247, 0.45), 0 0 120px rgba(168, 85, 247, 0.2);
        }

        .theme-light {
            background-color: #f8fafc;
            color: #0f172a;
        }
        .theme-light .counter-num {
            color: #0f172a;
            text-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        }

        .theme-neon {
            background-color: #030712;
            color: #22d3ee;
        }
        .theme-neon .counter-num {
            color: #22d3ee;
            text-shadow: 0 0 40px rgba(34, 211, 238, 0.8), 0 0 100px rgba(34, 211, 238, 0.4);
        }

        .theme-chroma {
            background-color: #00ff00 !important;
            color: #ffffff;
        }
        .theme-chroma .counter-num {
            color: #ffffff;
            text-shadow: 0 0 20px rgba(0, 0, 0, 0.9), 0 0 40px rgba(0, 0, 0, 0.8);
        }

        .theme-transparent {
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
            35% { transform: scale(1.16); }
            65% { transform: scale(0.96); }
            100% { transform: scale(1); }
        }

        .animate-pop {
            animation: counterPop 0.45s cubic-bezier(0.175, 0.885, 0.32, 1.275);
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
            width: 6px;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }
    </style>
</head>
<body id="bodyEl" class="theme-dark h-screen w-screen flex flex-col justify-between items-center relative select-none">

    <!-- Audio Beep (Web Audio API fallback) -->
    <audio id="beepSound" preload="auto">
        <source src="data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU"+("A".repeat(100)) type="audio/wav">
    </audio>

    <!-- Floating Toast Notification on Scan -->
    <div id="scanFeedbackToast" class="fixed top-20 inset-x-0 mx-auto max-w-md w-full px-4 z-50 transition-all duration-300 pointer-events-none opacity-0 -translate-y-6">
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
    <header id="controlsBar" class="controls-layer w-full max-w-6xl mx-auto pt-4 px-4 z-50 flex items-center justify-between gap-3">
        <!-- Live info & Selector -->
        <div class="flex items-center gap-3 bg-black/40 backdrop-blur-md px-3.5 py-2 rounded-2xl border border-white/10 shadow-lg">
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
                    class="bg-transparent text-xs font-semibold text-white/90 border-0 focus:ring-0 cursor-pointer outline-none max-w-[200px] sm:max-w-[280px] truncate"
                >
                    @foreach($lives as $live)
                        <option value="{{ $live->id }}" {{ ($activeLive && $activeLive->id == $live->id) ? 'selected' : '' }} class="bg-zinc-900 text-white">
                            #{{ $live->id }} - {{ $live->nome ?: 'Live de ' . date('d/m', strtotime($live->created_at)) }} {{ $live->ativo ? '🔴' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>

            <div class="hidden lg:flex items-center gap-1.5 bg-purple-500/20 text-purple-300 border border-purple-500/30 px-2.5 py-0.5 rounded-lg text-[10px] font-bold">
                <i class="fas fa-barcode text-purple-400"></i>
                <span>Leitor Ativo</span>
            </div>
        </div>

        <!-- Action Controls -->
        <div class="flex items-center gap-2 bg-black/40 backdrop-blur-md px-2 py-1.5 rounded-2xl border border-white/10 shadow-lg">
            <!-- Theme dropdown -->
            <select 
                id="themeSelect" 
                onchange="changeTheme(this.value)"
                class="bg-transparent text-xs font-semibold text-white/90 border-0 focus:ring-0 cursor-pointer outline-none px-2 py-1"
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
                class="px-2.5 py-1 text-xs font-semibold text-white/80 hover:text-white rounded-lg hover:bg-white/10 transition flex items-center gap-1.5"
                title="Mostrar/Ocultar último item"
            >
                <i class="fas fa-tag text-[11px]"></i>
                <span class="hidden sm:inline">Último Item</span>
            </button>

            <!-- Toggle Sound button -->
            <button 
                type="button" 
                id="toggleSoundBtn"
                onclick="toggleSound()"
                class="px-2.5 py-1 text-xs font-semibold text-white/80 hover:text-white rounded-lg hover:bg-white/10 transition flex items-center gap-1.5"
                title="Ativar/Desativar som ao bipar"
            >
                <i id="soundIcon" class="fas fa-volume-mute text-[11px]"></i>
            </button>

            <!-- Fullscreen button -->
            <button 
                type="button" 
                onclick="toggleFullScreen()"
                class="px-2.5 py-1 text-xs font-semibold text-white/80 hover:text-white rounded-lg hover:bg-white/10 transition flex items-center gap-1.5"
                title="Tela Cheia (F11 ou F)"
            >
                <i id="fsIcon" class="fas fa-expand text-[11px]"></i>
            </button>

            <!-- Link to Bipagem / Feed -->
            <a 
                href="{{ route('admin.live-chat.bipagem', ['live_id' => $activeLive ? $activeLive->id : '']) }}"
                target="_blank"
                class="px-2.5 py-1 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow transition flex items-center gap-1"
                title="Abrir tela de Bipagem"
            >
                <i class="fas fa-qrcode text-[10px]"></i>
                <span class="hidden md:inline">Bipagem</span>
            </a>
        </div>
    </header>

    <!-- Center Stage: GIANT COUNTER NUMBER -->
    <main class="flex-1 flex flex-col justify-center items-center w-full px-4 text-center cursor-pointer" onclick="triggerEasterEgg()" title="Clique duplo para Tela Cheia">
        
        <!-- Live Stats Badges (Quantidade de Itens em Sacolinhas & Valor Total) -->
        <div class="mb-2 sm:mb-4 flex flex-wrap items-center justify-center gap-3">
            <div id="liveBadge" class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-purple-500/10 border border-purple-500/30 backdrop-blur text-xs sm:text-sm font-bold tracking-wider text-purple-300 shadow-md">
                <i class="fas fa-shopping-bag text-purple-400"></i>
                <span><strong id="totalItemsCount" class="text-white font-mono text-sm sm:text-base">{{ $sacolinhasItensCount }}</strong> <span class="text-white/70">itens</span></span>
            </div>

            <div id="liveTotalValueBadge" class="inline-flex items-center gap-2 px-4.5 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 backdrop-blur text-xs sm:text-sm font-bold tracking-wide text-emerald-300 shadow-md transition-all duration-300">
                <i class="fas fa-coins text-emerald-400"></i>
                <span class="text-white/70">Total:</span>
                <strong id="totalLiveValueText" class="text-emerald-300 font-mono font-black text-sm sm:text-base">{{ $initialTotalValueFormatted }}</strong>
            </div>

            <div id="liveBipadosBadge" class="hidden sm:inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/5 border border-white/10 backdrop-blur text-xs font-semibold text-white/50 shadow-xs">
                <i class="fas fa-barcode text-purple-400 text-xs"></i>
                <span><strong id="totalBipadosCount" class="text-white/80 font-mono">{{ $initialBipadosCount }}</strong> bipados</span>
            </div>
        </div>

        <!-- The GIANT Number (Código da Peça / Sequência da Live) -->
        <div 
            id="counterNumber" 
            class="counter-num font-mono-numbers font-black tracking-tighter leading-none select-none my-auto"
            style="font-size: clamp(10rem, 28vw, 36rem);"
        >
            {{ $initialPieceCode }}
        </div>

        <!-- Subtitle / Last Biped Item Info (Collapsible) -->
        <div id="lastItemContainer" class="mt-2 sm:mt-6 transition-all duration-300 {{ $lastItem ? '' : 'hidden' }}">
            <div class="inline-flex items-center gap-3 px-5 py-2.5 rounded-2xl bg-black/40 border border-white/10 backdrop-blur-md shadow-2xl text-left">
                <div class="h-9 w-9 rounded-xl bg-purple-500/20 border border-purple-500/30 flex items-center justify-center text-purple-400 font-bold text-sm shrink-0">
                    <i class="fas fa-check"></i>
                </div>
                <div class="text-xs sm:text-sm">
                    <div class="font-bold text-white flex items-center gap-2">
                        <span id="lastItemCode" class="text-purple-300 font-mono">#{{ $lastItem['codigo'] ?? '---' }}</span>
                        <span class="text-white/40">•</span>
                        <span id="lastItemName" class="truncate max-w-[220px] sm:max-w-[350px]">{{ $lastItem['nome'] ?? '---' }}</span>
                    </div>
                    <div class="text-white/60 text-xs flex items-center gap-2 mt-0.5">
                        <span id="lastItemPrice" class="font-semibold text-emerald-400">{{ $lastItem['preco'] ?? '' }}</span>
                        <span class="text-white/30">•</span>
                        <span id="lastItemTime" class="text-white/50">{{ $lastItem['hora'] ?? '' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bottom subtle bar -->
    <footer class="w-full text-center pb-3 text-[11px] text-white/30 font-medium z-10 flex items-center justify-center gap-4">
        <span>Minha Mania Live Studio</span>
        <span>•</span>
        <span id="syncIndicator" class="flex items-center gap-1.5 text-emerald-400/80">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            Sincronizado
        </span>
        <span class="hidden sm:inline">•</span>
        <span class="hidden sm:inline text-white/20">Pressione 'F' para Tela Cheia</span>
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
        // State & Configuration
        let activeLiveId = "{{ $activeLive ? $activeLive->id : '' }}";
        const csrfToken = "{{ csrf_token() }}";
        const linkItemUrl = "{{ route('admin.live-chat.link-item-live') }}";
        let currentPieceCode = "{{ $initialPieceCode }}";
        let soundEnabled = true; // Habilitado por padrão para feedback ao bipar
        let showDetails = true;
        let isPolling = false;
        let isProcessingScan = false;
        let idleTimer = null;
        let toastTimer = null;

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

        // Garante foco permanente no leitor de código de barras
        function ensureScannerFocus() {
            if (!barcodeInput) return;
            const activeTag = document.activeElement ? document.activeElement.tagName : '';
            if (activeTag !== 'SELECT' && activeTag !== 'TEXTAREA') {
                barcodeInput.focus();
            }
        }
        setInterval(ensureScannerFocus, 1000);
        document.addEventListener('click', () => setTimeout(ensureScannerFocus, 50));

        // Web Audio API Synthesizer (Som de Sucesso ao Bipar)
        function playChime() {
            if (!soundEnabled) return;
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const now = ctx.currentTime;
                
                // Nota 1 (D5 - 587Hz)
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

                // Nota 2 (A5 - 880Hz)
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

        // Web Audio API Synthesizer (Som de Erro / Alerta)
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

        // Trigger Pop Animation on Counter & Count Badge
        function animateCounterChange(newVal) {
            counterEl.textContent = newVal;
            counterEl.classList.remove('animate-pop');
            void counterEl.offsetWidth; // force reflow
            counterEl.classList.add('animate-pop');
        }

        // Trigger Pop Animation on Total Value Badge
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

        // Floating Toast Notification
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

        // Processar Bipagem de Código de Barras
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

                    // 1. Atualiza o contador gigante (código da peça)
                    const pieceNum = item.piece_code || item.codigo_live || item.code || cleanBar;
                    if (pieceNum !== undefined) {
                        currentPieceCode = pieceNum;
                        animateCounterChange(currentPieceCode);
                    }

                    // Atualiza o valor total acumulado e itens na sacola
                    if (item.total_valor_formatado) {
                        animateTotalValueChange(item.total_valor_formatado);
                    }

                    if (item.total_sacolinhas_itens !== undefined && totalItemsCountEl) {
                        totalItemsCountEl.textContent = item.total_sacolinhas_itens;
                    }

                    if (item.total_bipados !== undefined && totalBipadosCountEl) {
                        totalBipadosCountEl.textContent = item.total_bipados;
                    }

                    // 2. Atualiza os dados do Último Item Bipado
                    lastItemCode.textContent = '#' + (item.codigo_live || item.code || cleanBar);
                    lastItemName.textContent = item.name || 'Produto';
                    lastItemPrice.textContent = item.price || '';
                    lastItemTime.textContent = item.hora || new Date().toLocaleTimeString('pt-BR');
                    if (showDetails) {
                        lastItemContainer.classList.remove('hidden');
                    }

                    // 3. Exibe o Toast adequado (Novo vs Já Cadastrado)
                    if (item.is_already_in_live) {
                        const buyerText = item.buyer_username ? ` • Sacola de @${item.buyer_username}` : '';
                        showScanToast('warning', `⚠️ Item #${item.code || cleanBar} Já Cadastrado!`, `Código da Live: #${item.codigo_live} • ${item.name || 'Produto'}${item.price ? ' (' + item.price + ')' : ''}${buyerText}`);
                    } else {
                        const seqInfo = item.codigo_live ? `Sequência #${item.codigo_live}` : '';
                        const priceInfo = item.price ? ` • ${item.price}` : '';
                        showScanToast('success', `Item #${item.code || cleanBar} Anexado à Live!`, `${item.name || 'Produto'}${priceInfo} ${seqInfo ? '(' + seqInfo + ')' : ''}`);
                    }

                    // 4. Toca som de confirmação
                    playChime();

                    // 5. Notifica outras abas (Chat, OBS, etc)
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

        // Listener no input invisível (para leitores que agem como teclado)
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
            // Ignora se o usuário estiver digitando no select de temas ou lives
            if (e.target && e.target.tagName === 'SELECT') {
                return;
            }

            const now = Date.now();
            const timeDiff = now - lastKeyTime;

            // Ao pressionar Enter, executa a bipagem se houver código no buffer
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

            // Se o intervalo entre teclas for muito longo (> 600ms), limpa o buffer
            if (timeDiff > 600) {
                scanBuffer = '';
            }

            // Atalhos rápidos somente se não houver números sendo digitados
            if (scanBuffer.length === 0 && !/\d/.test(e.key)) {
                if (e.key === 'f' || e.key === 'F') {
                    toggleFullScreen();
                    return;
                } else if (e.key === 'd' || e.key === 'D') {
                    toggleDetails();
                    return;
                } else if (e.key === 's' || e.key === 'S') {
                    toggleSound();
                    return;
                }
            }

            // Acumula caracteres imprimíveis
            if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
                scanBuffer += e.key;
                lastKeyTime = now;
            }
        });

        // Polling de Dados em Tempo Real
        async function fetchCounterData() {
            if (isPolling || isProcessingScan) return;
            isPolling = true;

            try {
                const liveParam = activeLiveId ? `live_id=${encodeURIComponent(activeLiveId)}&` : '';
                const url = `{{ route('api.live-contador.data') }}?${liveParam}_t=${Date.now()}`;
                const res = await fetch(url);
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                
                const data = await res.json();
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
            } catch (err) {
                syncIndicator.innerHTML = `<span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span> Reconectando...`;
            } finally {
                isPolling = false;
            }
        }

        // Sincronização periódica a cada 2 segundos
        setInterval(fetchCounterData, 2000);

        // Sincronização instantânea via storage local
        window.addEventListener('storage', (e) => {
            if (e.key === 'last_live_item_biped') {
                fetchCounterData();
            }
        });

        // Theme management
        function changeTheme(themeClass) {
            bodyEl.className = `${themeClass} h-screen w-screen flex flex-col justify-between items-center relative select-none`;
            localStorage.setItem('live_contador_theme', themeClass);
        }

        const savedTheme = localStorage.getItem('live_contador_theme');
        if (savedTheme) {
            document.getElementById('themeSelect').value = savedTheme;
            changeTheme(savedTheme);
        }

        // Toggle Details
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

        // Toggle Sound
        function toggleSound() {
            soundEnabled = !soundEnabled;
            const icon = document.getElementById('soundIcon');
            const btn = document.getElementById('toggleSoundBtn');
            if (soundEnabled) {
                icon.className = 'fas fa-volume-up text-[11px] text-emerald-400';
                btn.classList.add('bg-white/10');
                playChime();
            } else {
                icon.className = 'fas fa-volume-mute text-[11px]';
                btn.classList.remove('bg-white/10');
            }
            localStorage.setItem('live_contador_sound', soundEnabled ? '1' : '0');
        }

        if (localStorage.getItem('live_contador_sound') === '0') {
            toggleSound();
        } else {
            // Ativa som por padrão
            const icon = document.getElementById('soundIcon');
            const btn = document.getElementById('toggleSoundBtn');
            if (icon && btn) {
                icon.className = 'fas fa-volume-up text-[11px] text-emerald-400';
                btn.classList.add('bg-white/10');
            }
        }

        // Fullscreen Toggle
        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
                document.getElementById('fsIcon').className = 'fas fa-compress text-[11px]';
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                    document.getElementById('fsIcon').className = 'fas fa-expand text-[11px]';
                }
            }
        }

        // Double click fullscreen
        document.body.addEventListener('dblclick', (e) => {
            if (!e.target.closest('#controlsBar') && !e.target.closest('select') && !e.target.closest('button')) {
                toggleFullScreen();
            }
        });

        // Auto-hide toolbar on inactivity
        function resetIdleTimer() {
            controlsBar.classList.remove('controls-hidden');
            clearTimeout(idleTimer);
            idleTimer = setTimeout(() => {
                controlsBar.classList.add('controls-hidden');
            }, 3500);
        }

        window.addEventListener('mousemove', resetIdleTimer);
        window.addEventListener('touchstart', resetIdleTimer);
        resetIdleTimer();

        // Visual click effect
        function triggerEasterEgg() {
            counterEl.classList.remove('animate-pop');
            void counterEl.offsetWidth;
            counterEl.classList.add('animate-pop');
        }
    </script>
</body>
</html>

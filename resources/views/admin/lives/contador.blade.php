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
        
        <!-- Live Title Badge -->
        <div id="liveBadge" class="mb-2 sm:mb-4 inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/5 border border-white/10 backdrop-blur text-xs sm:text-sm font-bold uppercase tracking-widest text-white/70">
            <i class="fas fa-barcode text-purple-400"></i>
            <span>Itens Bipados na Live</span>
        </div>

        <!-- The GIANT Number -->
        <div 
            id="counterNumber" 
            class="counter-num font-mono-numbers font-black tracking-tighter leading-none select-none my-auto"
            style="font-size: clamp(10rem, 28vw, 36rem);"
        >
            {{ $initialCount }}
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

    <script>
        // State
        const activeLiveId = "{{ $activeLive ? $activeLive->id : '' }}";
        let currentCount = {{ $initialCount }};
        let soundEnabled = false;
        let showDetails = true;
        let isPolling = false;
        let idleTimer = null;

        const counterEl = document.getElementById('counterNumber');
        const lastItemContainer = document.getElementById('lastItemContainer');
        const lastItemCode = document.getElementById('lastItemCode');
        const lastItemName = document.getElementById('lastItemName');
        const lastItemPrice = document.getElementById('lastItemPrice');
        const lastItemTime = document.getElementById('lastItemTime');
        const controlsBar = document.getElementById('controlsBar');
        const syncIndicator = document.getElementById('syncIndicator');
        const bodyEl = document.getElementById('bodyEl');

        // Web Audio API Beep Synthesizer
        function playChime() {
            if (!soundEnabled) return;
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();

                osc.type = 'sine';
                osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
                osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.15); // A5

                gain.gain.setValueAtTime(0.3, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);

                osc.connect(gain);
                gain.connect(ctx.destination);

                osc.start();
                osc.stop(ctx.currentTime + 0.35);
            } catch (e) {
                console.warn('Audio Context error:', e);
            }
        }

        // Trigger Pop Animation on Counter
        function animateCounterChange(newVal) {
            counterEl.textContent = newVal;
            counterEl.classList.remove('animate-pop');
            // force reflow
            void counterEl.offsetWidth;
            counterEl.classList.add('animate-pop');
            playChime();
        }

        // Fetch Live Counter Data
        async function fetchCounterData() {
            if (!activeLiveId || isPolling) return;
            isPolling = true;

            try {
                const url = `{{ route('admin.live-chat.contador-data') }}?live_id=${encodeURIComponent(activeLiveId)}&_t=${Date.now()}`;
                const res = await fetch(url);
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                
                const data = await res.json();
                if (data && data.success) {
                    // Check if count changed
                    if (data.count !== currentCount) {
                        currentCount = data.count;
                        animateCounterChange(currentCount);
                    }

                    // Update last item
                    if (data.last_item) {
                        lastItemCode.textContent = '#' + (data.last_item.codigo || '---');
                        lastItemName.textContent = data.last_item.nome || 'Produto';
                        lastItemPrice.textContent = data.last_item.preco || '';
                        lastItemTime.textContent = data.last_item.hora || '';
                        if (showDetails) {
                            lastItemContainer.classList.remove('hidden');
                        }
                    }

                    // Sync pulse
                    syncIndicator.innerHTML = `<span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Sincronizado`;
                }
            } catch (err) {
                syncIndicator.innerHTML = `<span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span> Reconectando...`;
            } finally {
                isPolling = false;
            }
        }

        // Poll every 1.5s
        setInterval(fetchCounterData, 1500);

        // Instant local sync from operator bipagem in the same browser
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

        // Load saved theme
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

        if (localStorage.getItem('live_contador_sound') === '1') {
            toggleSound();
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

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'f' || e.key === 'F') {
                toggleFullScreen();
            } else if (e.key === 'd' || e.key === 'D') {
                toggleDetails();
            } else if (e.key === 's' || e.key === 'S') {
                toggleSound();
            }
        });

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

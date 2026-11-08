<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal do Cliente')</title>

    <!-- PWA Settings & Web App Manifest -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#F5148C">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="Minha Mania">

    <!-- iOS / Apple PWA Support -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Minha Mania">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

	<link rel="icon" href="{{ asset('favicon.ico') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
	<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
	
    <!-- Tailwind CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Outfit', 'Inter', sans-serif !important;
            -webkit-tap-highlight-color: transparent;
        }
        .fade-out {
            opacity: 0;
            transition: opacity 0.5s ease-out;
        }
        /* PWA Native Feel Utilities */
        .pwa-bottom-bar {
            padding-bottom: max(env(safe-area-inset-bottom), 8px);
        }
        .active-tap:active {
            transform: scale(0.95);
            transition: transform 0.08s ease-in-out;
        }
        /* Standalone PWA adjustments */
        @media all and (display-mode: standalone) {
            body {
                user-select: none;
            }
            input, textarea, select {
                user-select: auto;
            }
        }
    </style>
</head>
<body class="bg-gray-100 antialiased">
    <nav class="bg-white shadow-md">
        <div class="container mx-auto flex justify-between items-center p-4">
            <div class="flex items-center gap-3">
                {{-- Hamburger --}}
                <button id="portalMenuBtn"
                        type="button"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500"
                        aria-label="Abrir menu">
                    <i class="fas fa-bars text-gray-700"></i>
                </button>

                {{-- Brand --}}
                @php
                    $brandName = 'Portal do Cliente';
                    if (auth()->check()) {
                        $nameToUse = auth()->user()->apelido ?: explode(' ', auth()->user()->name)[0];
                        $brandName = 'Mania de ' . $nameToUse;
                    }
                @endphp
                <a href="{{ route('portal.dashboard') }}"
                   class="text-2xl font-bold text-gray-800 hover:text-green-600 transition duration-300 flex items-center gap-2">
                    @auth
                        @if(auth()->user()->photo)
                            <img src="{{ asset('storage/' . auth()->user()->photo) }}" alt="Foto" class="w-8 h-8 rounded-full object-cover">
                        @else
                            <i class="fas fa-user-circle text-gray-600"></i>
                        @endif
                    @else
                        <i class="fas fa-user-circle"></i>
                    @endauth
                    <span class="brand-text-span">{{ $brandName }}</span>
                </a>
            </div>

            <div class="flex items-center space-x-4">
                @auth
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-600 hover:text-green-600 transition duration-300 focus:outline-none flex items-center gap-1">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </button>
                    </form>
                @endauth
            </div>
        </div>

        {{-- Overlay --}}
        <div id="portalOverlay" class="fixed inset-0 bg-black/40 hidden" style="z-index: 9998;"></div>

        {{-- Drawer --}}
        <aside id="portalDrawer"
               class="fixed top-0 left-0 h-full w-72 bg-white shadow-xl hidden"
               style="z-index: 9999;">
            <div class="h-14 px-4 border-b border-gray-200 flex items-center justify-between">
                <div class="font-semibold text-gray-800">
                    Menu do Cliente
                </div>
                <button id="portalMenuCloseBtn"
                        type="button"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500"
                        aria-label="Fechar menu">
                    <i class="fas fa-times text-gray-700"></i>
                </button>
            </div>

            <nav class="p-4">
                <a href="{{ route('portal.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('portal.dashboard') ? 'bg-gray-100 font-semibold' : '' }}">
                    <i class="fas fa-home text-gray-500 w-5"></i>
                    <span>Painel</span>
                </a>

                <a href="{{ route('portal.perfil') }}"
                   class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('portal.perfil') ? 'bg-gray-100 font-semibold' : '' }}">
                    <i class="fas fa-user text-gray-500 w-5"></i>
                    <span>Meu Perfil</span>
                </a>

                <a href="{{ route('portal.pedidos') }}"
                   class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('portal.pedidos') ? 'bg-gray-100 font-semibold' : '' }}">
                    <i class="fas fa-receipt text-gray-500 w-5"></i>
                    <span>Meus Pedidos</span>
                </a>
						
                <a href="{{ route('portal.sacolinha') }}"
                   class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('portal.sacolinha') ? 'bg-gray-100 font-semibold' : '' }}">
                    <i class="fas fa-shopping-bag text-gray-500 w-5"></i>
                    <span>Minha Sacolinha</span>
                </a>

                <a href="{{ route('portal.movimentacao') }}"
                   class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('portal.movimentacao') ? 'bg-gray-100 font-semibold' : '' }}">
                    <i class="fas fa-wallet text-gray-500 w-5"></i>
                    <span>Minha Carteira</span>
                </a>

                <a href="{{ route('portal.avaliacoes') }}"
                   class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('portal.avaliacoes') ? 'bg-gray-100 font-semibold' : '' }}">
                    <i class="fas fa-star text-gray-500 w-5"></i>
                    <span>Minhas Avaliações</span>
                </a>

                <a href="{{ route('loja.index') }}"
                   class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('loja.index') ? 'bg-gray-100 font-semibold' : '' }}">
                    <i class="fas fa-store text-gray-500 w-5"></i>
                    <span>Loja</span>
                </a>
				
				<a href="{{ url('/dashboard-pontuacoes') }}"
				   class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('dashboard.pontuacoes') ? 'bg-gray-100 font-semibold' : '' }}">
				   
					<img src="{{ asset('icons8-trophy-48.png') }}" alt="Jogar" class="w-5 h-5 object-contain flex-shrink-0">
				   
				   <span>Jogar</span>
				</a>

                <a href="{{ route('portal.mani') }}"
                   class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-pink-50 text-pink-700 font-medium {{ request()->routeIs('portal.mani') ? 'bg-pink-100 font-semibold' : '' }}">
                    <i class="fas fa-star text-pink-500 w-5 text-center"></i>
                    <span>Mani <span class="text-[10px] bg-pink-500 text-white px-1.5 py-0.5 rounded-full ml-1 font-bold">IA</span></span>
                </a>

                {{-- Botão de Instalar PWA no Drawer --}}
                <button type="button"
                        id="pwaDrawerInstallBtn"
                        class="mt-2 w-full flex items-center gap-3 px-3 py-2 rounded-md bg-pink-50 hover:bg-pink-100 text-pink-700 font-semibold transition text-left hidden">
                    <i class="fas fa-download text-pink-600 w-5"></i>
                    <span>Instalar Aplicativo</span>
                </button>

                <div class="my-4 border-t border-gray-200"></div>

                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 text-left">
                            <i class="fas fa-sign-out-alt text-gray-500 w-5"></i>
                            <span>Sair</span>
                        </button>
                    </form>
                @endauth
            </nav>
        </aside>
    </nav>

    <main class="container mx-auto mt-8 p-4 pb-28 md:pb-8">
        <!-- Flash Messages -->
        @if (session('success'))
            <div id="success-alert" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Sucesso!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                </span>
            </div>
        @endif

        @if (session('error'))
            <div id="error-alert" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Erro!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                </span>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Native PWA Mobile Bottom Navigation Bar -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 shadow-[0_-4px_16px_rgba(0,0,0,0.06)] pwa-bottom-bar" aria-label="Navegação inferior">
        <div class="grid grid-cols-5 h-14 items-center">
            <!-- Início -->
            <a href="{{ route('portal.dashboard') }}"
               class="flex flex-col items-center justify-center py-1 active-tap {{ request()->routeIs('portal.dashboard') ? 'text-pink-600 font-bold' : 'text-gray-500 hover:text-gray-900' }}">
                <i class="fas fa-home text-lg"></i>
                <span class="text-[11px] mt-0.5 tracking-tight">Início</span>
            </a>

            <!-- Sacolinha -->
            <a href="{{ route('portal.sacolinha') }}"
               class="flex flex-col items-center justify-center py-1 active-tap {{ request()->routeIs('portal.sacolinha') ? 'text-pink-600 font-bold' : 'text-gray-500 hover:text-gray-900' }}">
                <i class="fas fa-shopping-bag text-lg"></i>
                <span class="text-[11px] mt-0.5 tracking-tight">Sacolinha</span>
            </a>

            <!-- Pedidos -->
            <a href="{{ route('portal.pedidos') }}"
               class="flex flex-col items-center justify-center py-1 active-tap {{ request()->routeIs('portal.pedidos') ? 'text-pink-600 font-bold' : 'text-gray-500 hover:text-gray-900' }}">
                <i class="fas fa-receipt text-lg"></i>
                <span class="text-[11px] mt-0.5 tracking-tight">Pedidos</span>
            </a>

            <!-- Carteira -->
            <a href="{{ route('portal.movimentacao') }}"
               class="flex flex-col items-center justify-center py-1 active-tap {{ request()->routeIs('portal.movimentacao') ? 'text-pink-600 font-bold' : 'text-gray-500 hover:text-gray-900' }}">
                <i class="fas fa-wallet text-lg"></i>
                <span class="text-[11px] mt-0.5 tracking-tight">Carteira</span>
            </a>

            <!-- Mani IA -->
            <a href="{{ route('portal.mani') }}"
               class="flex flex-col items-center justify-center py-1 active-tap {{ request()->routeIs('portal.mani') ? 'text-pink-600 font-bold' : 'text-gray-500 hover:text-gray-900' }}">
                <div class="relative">
                    <i class="fas fa-magic text-lg"></i>
                    <span class="absolute -top-1 -right-2 text-[9px] bg-pink-600 text-white font-black px-1 py-0.2 rounded-full leading-none">IA</span>
                </div>
                <span class="text-[11px] mt-0.5 tracking-tight">Mani</span>
            </a>
        </div>
    </nav>

    <!-- PWA Install Banner -->
    <div id="pwaInstallBanner"
         class="fixed bottom-20 md:bottom-6 left-4 right-4 md:left-auto md:right-6 md:max-w-md z-50 bg-white border border-pink-200 rounded-2xl p-4 shadow-2xl transition-all duration-300 transform translate-y-32 opacity-0 pointer-events-none">
        <div class="flex items-start gap-3">
            <img src="{{ asset('android-chrome-192x192.png') }}" alt="Ícone App Minha Mania" class="w-12 h-12 rounded-xl shadow-sm object-cover flex-shrink-0">
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-bold text-gray-900 leading-tight">Instalar App Minha Mania</h4>
                    <button type="button" id="pwaCloseBannerBtn" class="text-gray-400 hover:text-gray-700 p-1 -mr-1" aria-label="Fechar">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
                <p class="text-xs text-gray-600 mt-1 leading-snug">
                    Instale em 1 clique para acessar suas sacolinhas e novidades direto da sua tela inicial!
                </p>
                <div class="mt-3 flex items-center gap-2">
                    <button type="button" id="pwaInstallBtn" class="flex-1 bg-pink-600 hover:bg-pink-700 text-white text-xs font-bold py-2 px-3 rounded-lg shadow-sm transition active-tap">
                        <i class="fas fa-download mr-1"></i> Instalar Agora
                    </button>
                    <button type="button" id="pwaLaterBtn" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold py-2 px-3 rounded-lg transition">
                        Agora não
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- iOS Instructions Modal -->
    <div id="pwaIosModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/60 hidden">
        <div class="bg-white rounded-2xl max-w-sm w-full p-5 shadow-2xl border border-gray-100 transform transition-all">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('android-chrome-192x192.png') }}" alt="App Minha Mania" class="w-8 h-8 rounded-lg object-cover">
                    <h3 class="text-base font-bold text-gray-900">Como Instalar no iPhone</h3>
                </div>
                <button type="button" id="pwaIosCloseBtn" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Fechar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="py-4 space-y-3 text-sm text-gray-700">
                <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-pink-100 text-pink-700 font-bold flex items-center justify-center flex-shrink-0 text-xs">1</div>
                    <p class="leading-relaxed">Toque no ícone de <strong>Compartilhar</strong> <i class="fas fa-share-square text-blue-500 mx-1"></i> na barra inferior do Safari.</p>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-pink-100 text-pink-700 font-bold flex items-center justify-center flex-shrink-0 text-xs">2</div>
                    <p class="leading-relaxed">Role para baixo e selecione <strong>"Adicionar à Tela de Início"</strong> <i class="fas fa-plus-square text-gray-700 mx-1"></i>.</p>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-pink-100 text-pink-700 font-bold flex items-center justify-center flex-shrink-0 text-xs">3</div>
                    <p class="leading-relaxed">Toque em <strong>Adicionar</strong> no canto superior direito para finalizar!</p>
                </div>
            </div>
            <button type="button" id="pwaIosUnderstoodBtn" class="w-full bg-pink-600 hover:bg-pink-700 text-white font-bold py-2.5 rounded-xl text-sm transition">
                Entendi
            </button>
        </div>
    </div>

        @yield('content')
    </main>

    <script>
        // Script para fechar as mensagens de alerta
        document.addEventListener('DOMContentLoaded', function() {
            const successAlert = document.getElementById('success-alert');
            const errorAlert = document.getElementById('error-alert');

            if (successAlert) {
                setTimeout(() => {
                    successAlert.classList.add('fade-out');
                    successAlert.addEventListener('transitionend', () => successAlert.remove());
                }, 5000);
                successAlert.querySelector('svg').addEventListener('click', () => successAlert.remove());
            }

            if (errorAlert) {
                setTimeout(() => {
                    errorAlert.classList.add('fade-out');
                    errorAlert.addEventListener('transitionend', () => errorAlert.remove());
                }, 5000);
                errorAlert.querySelector('svg').addEventListener('click', () => errorAlert.remove());
            }
        });

        // Script para menu do portal
        document.addEventListener('DOMContentLoaded', function () {
            var btn = document.getElementById('portalMenuBtn');
            var closeBtn = document.getElementById('portalMenuCloseBtn');
            var drawer = document.getElementById('portalDrawer');
            var overlay = document.getElementById('portalOverlay');

            function openDrawer() {
                if (!drawer || !overlay) return;
                drawer.classList.remove('hidden');
                overlay.classList.remove('hidden');
            }

            function closeDrawer() {
                if (!drawer || !overlay) return;
                drawer.classList.add('hidden');
                overlay.classList.add('hidden');
            }

            if (btn) btn.addEventListener('click', openDrawer);
            if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
            if (overlay) overlay.addEventListener('click', closeDrawer);

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeDrawer();
            });

            @auth
            try {
                localStorage.setItem('sacolinhas_last_url', window.location.pathname + window.location.search);
                sessionStorage.removeItem('sacolinhas_redirect_to');
            } catch (e) {}
            @endauth
        });

        // ============================================
        // PWA & Service Worker Registration & Prompts
        // ============================================
        (function() {
            // 1. Registro do Service Worker
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/sw.js')
                        .then(function(reg) {
                            console.log('[PWA Minha Mania] Service Worker ativo:', reg.scope);
                        })
                        .catch(function(err) {
                            console.warn('[PWA Minha Mania] Erro ao registrar SW:', err);
                        });
                });
            }

            // 2. Verificar se já está rodando em modo standalone / app instalado
            var isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                               window.navigator.standalone === true;

            if (isStandalone) {
                return; // Já está no app instalado, não precisa exibir banners de instalação
            }

            var deferredPrompt = null;
            var banner = document.getElementById('pwaInstallBanner');
            var installBtn = document.getElementById('pwaInstallBtn');
            var closeBannerBtn = document.getElementById('pwaCloseBannerBtn');
            var laterBtn = document.getElementById('pwaLaterBtn');
            var drawerInstallBtn = document.getElementById('pwaDrawerInstallBtn');
            var iosModal = document.getElementById('pwaIosModal');
            var iosCloseBtn = document.getElementById('pwaIosCloseBtn');
            var iosUnderstoodBtn = document.getElementById('pwaIosUnderstoodBtn');

            // Detecção de iOS Safari
            var isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            var isSafari = /Safari/.test(navigator.userAgent) && !/Chrome|CriOS|FxiOS|EdgiOS/.test(navigator.userAgent);

            function showBanner() {
                if (!banner) return;
                var dismissedAt = localStorage.getItem('minhamania_pwa_dismissed');
                if (dismissedAt) {
                    var daysPassed = (Date.now() - parseInt(dismissedAt, 10)) / (1000 * 60 * 60 * 24);
                    if (daysPassed < 5) return; // Dispensado recentemente (aguarda 5 dias)
                }
                banner.classList.remove('translate-y-32', 'opacity-0', 'pointer-events-none');
            }

            function hideBanner(dismissPermanently) {
                if (!banner) return;
                banner.classList.add('translate-y-32', 'opacity-0', 'pointer-events-none');
                if (dismissPermanently) {
                    localStorage.setItem('minhamania_pwa_dismissed', Date.now().toString());
                }
            }

            function handleInstallFlow() {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then(function(choiceResult) {
                        if (choiceResult.outcome === 'accepted') {
                            hideBanner(true);
                            if (drawerInstallBtn) drawerInstallBtn.classList.add('hidden');
                        }
                        deferredPrompt = null;
                    });
                } else if (isIos) {
                    hideBanner();
                    if (iosModal) iosModal.classList.remove('hidden');
                } else {
                    alert('Para instalar o app, clique no menu do navegador (três pontinhos no canto superior) e selecione "Instalar aplicativo" ou "Adicionar à tela inicial".');
                }
            }

            // Escuta o evento beforeinstallprompt (Android / Chrome / Edge)
            window.addEventListener('beforeinstallprompt', function(e) {
                e.preventDefault();
                deferredPrompt = e;
                if (drawerInstallBtn) drawerInstallBtn.classList.remove('hidden');
                setTimeout(showBanner, 2500);
            });

            // No iOS Safari, disponibiliza o botão no Drawer e exibe o banner convidativo
            if (isIos && isSafari) {
                if (drawerInstallBtn) drawerInstallBtn.classList.remove('hidden');
                setTimeout(showBanner, 3000);
            }

            if (installBtn) installBtn.addEventListener('click', handleInstallFlow);
            if (drawerInstallBtn) {
                drawerInstallBtn.addEventListener('click', function() {
                    handleInstallFlow();
                    var closeDrawer = document.getElementById('portalMenuCloseBtn');
                    if (closeDrawer) closeDrawer.click();
                });
            }

            if (closeBannerBtn) closeBannerBtn.addEventListener('click', function() { hideBanner(true); });
            if (laterBtn) laterBtn.addEventListener('click', function() { hideBanner(true); });

            if (iosCloseBtn) iosCloseBtn.addEventListener('click', function() { if (iosModal) iosModal.classList.add('hidden'); });
            if (iosUnderstoodBtn) iosUnderstoodBtn.addEventListener('click', function() { if (iosModal) iosModal.classList.add('hidden'); });

            window.addEventListener('appinstalled', function() {
                hideBanner(true);
                if (drawerInstallBtn) drawerInstallBtn.classList.add('hidden');
                deferredPrompt = null;
            });
        })();
    </script>

    <!-- Scripts and Alpine -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.x/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal do Cliente')</title>
	<link rel="icon" href="{{ asset('favicon.ico') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
	<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
	
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
        }
        .fade-out {
            opacity: 0;
            transition: opacity 0.5s ease-out;
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
                    <img src="{{ asset('favicon.ico') }}" alt="Loja" class="w-5 h-5 object-contain">
                    <span>Loja</span>
                </a>
				
				<a href="{{ url('/dashboard-pontuacoes') }}"
				   class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('dashboard.pontuacoes') ? 'bg-gray-100 font-semibold' : '' }}">
				   
					<img src="{{ asset('icons8-trophy-48.png') }}" alt="Jogar" class="w-5 h-5 object-contain flex-shrink-0">
				   
				   <span>Jogar</span>
				</a>
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

    <main class="container mx-auto mt-8 p-4">
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
        });
    </script>
</body>
</html>
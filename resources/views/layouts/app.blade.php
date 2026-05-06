<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Financeiro')</title>
	<link rel="icon" href="{{ asset('favicon.ico') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
	<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
	
    <!-- Tailwind CSS CDN (para demonstração, em produção compile via npm) -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* Estilos personalizados ou overrides do Tailwind podem vir aqui */
        .fade-out {
            opacity: 0;
            transition: opacity 0.5s ease-out;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased">
	<nav class="bg-white shadow-md">
		<div class="container mx-auto flex justify-between items-center p-4">
			<div class="flex items-center gap-3">
				{{-- Hamburger --}}
				<button id="adminMenuBtn"
						type="button"
						class="inline-flex items-center justify-center w-10 h-10 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
						aria-label="Abrir menu">
					<i class="fas fa-bars text-gray-700"></i>
				</button>

				@php
					$brandRoute = trim($__env->yieldContent('brand_route', 'dashboard'));
					$brandIcon  = trim($__env->yieldContent('brand_icon', 'fas fa-layer-group'));
				@endphp

				<a href="{{ route($brandRoute) }}"
				   class="text-2xl font-bold text-gray-800 hover:text-blue-600 transition duration-300">
					<i class="{{ $brandIcon }} mr-1"></i>@yield('title', 'Admin')
				</a>
			</div>

			<div class="flex items-center space-x-4">
				@auth
					<a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-blue-600 transition duration-300">
						<i class="fas fa-home"></i> Dashboard
					</a>
					<form action="{{ route('logout') }}" method="POST" class="inline">
						@csrf
						<button type="submit" class="text-gray-600 hover:text-blue-600 transition duration-300 focus:outline-none">
							<i class="fas fa-sign-out-alt mr-1"></i> Sair
						</button>
					</form>
				@else
					<a href="{{ route('login') }}" class="text-gray-600 hover:text-blue-600 transition duration-300">
						<i class="fas fa-sign-in-alt mr-1"></i> Login
					</a>
					<a href="{{ route('register') }}" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition duration-300">
						<i class="fas fa-user-plus mr-1"></i> Registrar
					</a>
				@endauth
			</div>
		</div>

		{{-- Overlay --}}
		<div id="adminOverlay" class="fixed inset-0 bg-black/40 hidden" style="z-index: 9998;"></div>

		{{-- Drawer --}}
		<aside id="adminDrawer"
			   class="fixed top-0 left-0 h-full w-72 bg-white shadow-xl hidden"
			   style="z-index: 9999;">
			<div class="h-14 px-4 border-b border-gray-200 flex items-center justify-between">
				<div class="font-semibold text-gray-800">
					Menu Admin
				</div>
				<button id="adminMenuCloseBtn"
						type="button"
						class="inline-flex items-center justify-center w-10 h-10 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
						aria-label="Fechar menu">
					<i class="fas fa-times text-gray-700"></i>
				</button>
			</div>

			<nav class="p-4">
				<a href="{{ route('dashboard') }}"
				   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('dashboard') ? 'bg-gray-100 font-semibold' : '' }}">
					<i class="fas fa-home text-gray-500 w-5"></i>
					<span>Dashboard</span>
				</a>

				<a href="{{ route('clientes.index') }}"
				   class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('clientes.*') ? 'bg-gray-100 font-semibold' : '' }}">
					<i class="fas fa-users text-gray-500 w-5"></i>
					<span>Clientes</span>
				</a>

				<a href="{{ route('items.index') }}"
				   class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('items.*') ? 'bg-gray-100 font-semibold' : '' }}">
					<i class="fas fa-box text-gray-500 w-5"></i>
					<span>Itens</span>
				</a>

                <a href="{{ route('admin.categorias.index') }}"
				   class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('admin.categorias.*') ? 'bg-gray-100 font-semibold' : '' }}">
					<i class="fas fa-tags text-gray-500 w-5"></i>
					<span>Categorias</span>
				</a>

				<a href="{{ route('bags.index') }}"
				   class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('bags.*') ? 'bg-gray-100 font-semibold' : '' }}">
					<i class="fas fa-broadcast-tower text-gray-500 w-5"></i>
					<span>Live</span>
				</a>

				<a href="{{ route('classificacao_financeira.index') }}"
				   class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('admin.sacolinhas.*') ? 'bg-gray-100 font-semibold' : '' }}">
					<i class="fas fa-shopping-bag text-gray-500 w-5"></i>
					<span>Plano de Contas</span>
				</a>

				<a href="{{ route('financeiro.dashboard') }}"
				   class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('financeiro.*') ? 'bg-gray-100 font-semibold' : '' }}">
					<i class="fas fa-chart-line text-gray-500 w-5"></i>
					<span>Financeiro</span>
				</a>

				<a href="{{ route('admin.conta_corrente.index') }}"
				   class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('admin.conta_corrente.*') ? 'bg-gray-100 font-semibold' : '' }}">
					<i class="fas fa-history text-gray-500 w-5"></i>
					<span>Extrato (Antigo)</span>
				</a>

				<a href="{{ route('admin.pedido.index') }}"
				   class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('admin.pedido.*') ? 'bg-gray-100 font-semibold' : '' }}">
					<i class="fas fa-receipt text-gray-500 w-5"></i>
					<span>Pedidos</span>
				</a>

                <a href="{{ route('admin.clube.dashboard') }}"
                    class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('admin.clube.dashboard') ? 'bg-gray-100 font-semibold' : '' }}">
                    <i class="fas fa-crown text-gray-500 w-5"></i>
                    <span>Painel do Clube</span>
                </a>

                <a href="{{ route('admin.clube.desafios.index') }}"
                    class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('admin.clube.desafios.*') ? 'bg-gray-100 font-semibold' : '' }}">
                    <i class="fas fa-trophy text-gray-500 w-5"></i>
                    <span>Desafios</span>
                </a>

                <a href="{{ route('admin.grupos.index') }}"
                    class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('admin.grupos.*') ? 'bg-gray-100 font-semibold' : '' }}">
                    <i class="fas fa-users text-gray-500 w-5"></i>
                    <span>Grupos</span>
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
        // Script para fechar as mensagens de alerta e fazê-las desaparecer
        document.addEventListener('DOMContentLoaded', function() {
            const successAlert = document.getElementById('success-alert');
            const errorAlert = document.getElementById('error-alert');

            if (successAlert) {
                setTimeout(() => {
                    successAlert.classList.add('fade-out');
                    successAlert.addEventListener('transitionend', () => successAlert.remove());
                }, 5000); // Remove após 5 segundos
                successAlert.querySelector('svg').addEventListener('click', () => successAlert.remove());
            }

            if (errorAlert) {
                setTimeout(() => {
                    errorAlert.classList.add('fade-out');
                    errorAlert.addEventListener('transitionend', () => errorAlert.remove());
                }, 5000); // Remove após 5 segundos
                errorAlert.querySelector('svg').addEventListener('click', () => errorAlert.remove());
            }
        });
		// Script para menu
		document.addEventListener('DOMContentLoaded', function () {
			var btn = document.getElementById('adminMenuBtn');
			var closeBtn = document.getElementById('adminMenuCloseBtn');
			var drawer = document.getElementById('adminDrawer');
			var overlay = document.getElementById('adminOverlay');

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
	
	@stack('scripts')	
</body>
</html>
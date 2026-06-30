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
    
    <!-- Alpine.js v3.14.8 (local) -->
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>

    <style>
        /* Estilos personalizados ou overrides do Tailwind podem vir aqui */
        .fade-out {
            opacity: 0;
            transition: opacity 0.5s ease-out;
        }
        /* Oculta elementos Alpine.js antes da inicialização (evita flash de modais) */
        [x-cloak] { display: none !important; }
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
					<a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-blue-600 transition duration-300" title="Dashboard">
						<i class="fas fa-home text-lg"></i>
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

			<nav class="p-4 space-y-4">
				<a href="{{ route('dashboard') }}"
				   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('dashboard') ? 'bg-gray-100 font-semibold' : '' }}">
					<i class="fas fa-home text-gray-500 w-5"></i>
					<span>Dashboard</span>
				</a>

                {{-- Grupo: Cadastro --}}
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-3 mb-1">Cadastro</p>
                    
                    {{-- Clientes Collapsible Submenu --}}
                    <div x-data="{ open: {{ request()->routeIs('clientes.*', 'admin.clientes.*', 'admin.chat.*', 'admin.whatsapp.dashboard') ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open"
                                class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('clientes.*', 'admin.clientes.*', 'admin.chat.*', 'admin.whatsapp.dashboard') ? 'bg-gray-100 font-semibold' : '' }}">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-users text-gray-500 w-5"></i>
                                <span>Clientes</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 text-xs transition duration-200" :class="open ? 'transform rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" x-cloak class="pl-8 pr-3 py-1.5 space-y-1 bg-gray-50/50 rounded-lg mt-0.5 border border-gray-100/50">
                            <a href="{{ route('clientes.index') }}" class="block py-1 text-sm text-gray-600 hover:text-indigo-600 {{ request()->routeIs('clientes.index') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-list mr-1"></i> Lista de Clientes
                            </a>
                            <a href="{{ route('admin.chat.index') }}" class="block py-1 text-sm text-gray-600 hover:text-indigo-600 {{ request()->routeIs('admin.chat.index') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-comments mr-1"></i> Chat
                            </a>
                            <a href="{{ route('admin.whatsapp.dashboard') }}" class="block py-1 text-sm text-gray-600 hover:text-indigo-600 {{ request()->routeIs('admin.whatsapp.dashboard') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-chart-pie mr-1"></i> Chat Dashboard
                            </a>
                        </div>
                    </div>
                    
                    {{-- Itens Collapsible Submenu --}}
                    <div x-data="{ open: {{ request()->routeIs('items.*', 'inventario', 'upload.batch.form', 'image-groups.*', 'admin.categorias.*') ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open"
                                class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('items.*', 'inventario', 'upload.batch.form', 'image-groups.*', 'admin.categorias.*') ? 'bg-gray-100 font-semibold' : '' }}">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-box text-gray-500 w-5"></i>
                                <span>Itens</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 text-xs transition duration-200" :class="open ? 'transform rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" x-cloak class="pl-8 pr-3 py-1.5 space-y-1 bg-gray-50/50 rounded-lg mt-0.5 border border-gray-100/50">
                            <a href="{{ route('items.index') }}" class="block py-1 text-sm text-gray-600 hover:text-indigo-600 {{ request()->routeIs('items.index') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-list mr-1"></i> Lista de Itens
                            </a>
                            <a href="{{ route('admin.categorias.index') }}" class="block py-1 text-sm text-gray-600 hover:text-indigo-600 {{ request()->routeIs('admin.categorias.*') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-tags mr-1"></i> Categorias
                            </a>
                            <a href="{{ route('inventario') }}" class="block py-1 text-sm text-gray-600 hover:text-indigo-600 {{ request()->routeIs('inventario') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-clipboard-list mr-1"></i> Inventário
                            </a>
                            <a href="{{ route('upload.batch.form') }}" class="block py-1 text-sm text-gray-600 hover:text-indigo-600 {{ request()->routeIs('upload.batch.form') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-download mr-1"></i> Download Imagens
                            </a>
                            <a href="{{ route('image-groups.index') }}" class="block py-1 text-sm text-gray-600 hover:text-indigo-600 {{ request()->routeIs('image-groups.index') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-image mr-1"></i> Imagens->Item
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Grupo: Financeiro --}}
                <div x-data="{ 
                    openAnalises: {{ (request()->routeIs('financeiro.dashboard') || request()->routeIs('financeiro.fluxodecaixa') || request()->routeIs('financeiro.relatoriogerencial') || request()->routeIs('financeiro.dre') || request()->routeIs('financeiro.orcamento.*')) ? 'true' : 'false' }},
                    openOperacoes: {{ (request()->routeIs('financeiro.lancamentos.*') || request()->routeIs('financeiro.conciliacao.*') || request()->routeIs('financeiro.movimentacoes.*')) ? 'true' : 'false' }},
                    openCadastros: {{ (request()->routeIs('classificacao_financeira.*') || request()->routeIs('admin.conta_corrente.*') || request()->routeIs('financeiro.contas.*') || request()->routeIs('financeiro.pessoas.*')) ? 'true' : 'false' }}
                }">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-3 mb-2">Financeiro</p>
                    
                    {{-- Subgrupo: Análises --}}
                    <div class="mb-1">
                        <button @click="openAnalises = !openAnalises" class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-50 text-gray-700">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-chart-bar text-gray-500 w-5 text-center"></i>
                                <span class="text-sm font-medium">Análises & Relatórios</span>
                            </div>
                            <i class="fas fa-chevron-down text-[10px] transition-transform duration-200" :class="openAnalises ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="openAnalises" x-cloak class="pl-11 pr-3 py-1 space-y-1">
                            <a href="{{ route('financeiro.dashboard') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.dashboard') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-home mr-1"></i> Dashboard
                            </a>
                            <a href="{{ route('financeiro.fluxodecaixa') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.fluxodecaixa') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-funnel-dollar mr-1"></i> Fluxo de Caixa
                            </a>
                            <a href="{{ route('financeiro.dre') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.dre') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-file-invoice mr-1"></i> DRE Contábil
                            </a>
                            <a href="{{ route('financeiro.relatoriogerencial') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.relatoriogerencial') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-chart-pie mr-1"></i> Relatório Gerencial
                            </a>
                            <a href="{{ route('financeiro.orcamento.index') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.orcamento.*') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-chart-line mr-1"></i> Orçamento
                            </a>
                        </div>
                    </div>

                    {{-- Subgrupo: Operações --}}
                    <div class="mb-1">
                        <button @click="openOperacoes = !openOperacoes" class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-50 text-gray-700">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-exchange-alt text-gray-500 w-5 text-center"></i>
                                <span class="text-sm font-medium">Dia a Dia / Operações</span>
                            </div>
                            <i class="fas fa-chevron-down text-[10px] transition-transform duration-200" :class="openOperacoes ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="openOperacoes" x-cloak class="pl-11 pr-3 py-1 space-y-1">
                            <a href="{{ route('financeiro.lancamentos.index') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.lancamentos.*') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-file-invoice-dollar mr-1"></i> Lançamentos
                            </a>
                            <a href="{{ route('financeiro.conciliacao.index') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.conciliacao.*') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-balance-scale mr-1"></i> Conciliação
                            </a>
                            <a href="{{ route('financeiro.movimentacoes.index') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.movimentacoes.*') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-history mr-1"></i> Movimentações
                            </a>
                        </div>
                    </div>

                    {{-- Subgrupo: Cadastros --}}
                    <div class="mb-1">
                        <button @click="openCadastros = !openCadastros" class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-50 text-gray-700">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-cogs text-gray-500 w-5 text-center"></i>
                                <span class="text-sm font-medium">Cadastros & Estrutura</span>
                            </div>
                            <i class="fas fa-chevron-down text-[10px] transition-transform duration-200" :class="openCadastros ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="openCadastros" x-cloak class="pl-11 pr-3 py-1 space-y-1">
                            <a href="{{ route('financeiro.contas.index') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.contas.*') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-university mr-1"></i> Contas Bancárias
                            </a>
                            <a href="{{ route('classificacao_financeira.index') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('classificacao_financeira.*') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-list-ul mr-1"></i> Plano de Contas
                            </a>
                            <a href="{{ route('financeiro.pessoas.index') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.pessoas.*') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-users mr-1"></i> Contatos
                            </a>
                        </div>
                    </div>

                    {{-- Item Separado: Carteira Cliente --}}
                    <div class="mt-2 pt-2 border-t border-gray-100/50">
                        <a href="{{ route('admin.conta_corrente.index') }}"
                           class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 text-gray-700 {{ request()->routeIs('admin.conta_corrente.*') ? 'bg-gray-100 font-bold text-indigo-600' : '' }}">
                            <i class="fas fa-wallet text-gray-500 w-5 text-center"></i>
                            <span class="text-sm font-medium">Carteira Cliente</span>
                        </a>
                    </div>
                </div>

                {{-- Grupo: Comercial --}}
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-3 mb-1">Comercial</p>
                    <a href="{{ route('bags.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('bags.*') ? 'bg-gray-100 font-semibold' : '' }}">
                        <i class="fas fa-broadcast-tower text-gray-500 w-5"></i>
                        <span>Live</span>
                    </a>
                    
                    {{-- Sacolinhas Collapsible Submenu --}}
                    <div x-data="{ open: {{ request()->routeIs('admin.sacolinhas.*', 'sacolinhas.*', 'admin.sacolinha.*') ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open"
                                class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('admin.sacolinhas.*', 'sacolinhas.*', 'admin.sacolinha.*') ? 'bg-gray-100 font-semibold' : '' }}">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-shopping-bag text-gray-500 w-5"></i>
                                <span>Sacolas</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 text-xs transition duration-200" :class="open ? 'transform rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" x-cloak class="pl-8 pr-3 py-1.5 space-y-1 bg-gray-50/50 rounded-lg mt-0.5 border border-gray-100/50">
                            <a href="{{ route('admin.sacolinhas.index') }}" class="block py-1 text-sm text-gray-600 hover:text-indigo-600 {{ request()->routeIs('admin.sacolinhas.index') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-broadcast-tower mr-1"></i> Da Live
                            </a>
                            <a href="{{ route('admin.sacolinha.gestao') }}" class="block py-1 text-sm text-gray-600 hover:text-indigo-600 {{ request()->routeIs('admin.sacolinha.gestao', 'admin.sacolinha.show') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-user mr-1"></i> Por Cliente
                            </a>
                            <a href="{{ route('admin.sacolinhas.qrcode.scanner') }}" class="block py-1 text-sm text-gray-600 hover:text-indigo-600 {{ request()->routeIs('admin.sacolinhas.qrcode.scanner') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-qrcode mr-1"></i> Item->Sacolinha
                            </a>
                        </div>
                    </div>

                    <a href="{{ route('admin.pedido.index') }}"
                       class="mt-1 flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('admin.pedido.*') ? 'bg-gray-100 font-semibold' : '' }}">
                        <i class="fas fa-receipt text-gray-500 w-5"></i>
                        <span>Pedidos</span>
                    </a>
                </div>

                {{-- Grupo: Clube --}}
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-3 mb-1">Clube</p>
                    <a href="{{ route('admin.clube.dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('admin.clube.dashboard') ? 'bg-gray-100 font-semibold' : '' }}">
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
                </div>

                {{-- Grupo: Relatórios --}}
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-3 mb-1">Relatórios</p>
                    <div x-data="{ open: {{ request()->routeIs('admin.clientes.relatorios') ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open"
                                class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-50 {{ request()->routeIs('admin.clientes.relatorios') ? 'bg-gray-100 font-semibold' : '' }}">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-chart-bar text-gray-500 w-5"></i>
                                <span>Relatórios</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 text-xs transition duration-200" :class="open ? 'transform rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" x-cloak class="pl-8 pr-3 py-1.5 space-y-1 bg-gray-50/50 rounded-lg mt-0.5 border border-gray-100/50">
                            <a href="{{ route('admin.clientes.relatorios') }}" class="block py-1 text-sm text-gray-600 hover:text-indigo-600 {{ request()->routeIs('admin.clientes.relatorios') ? 'font-semibold text-indigo-600' : '' }}">
                                <i class="fas fa-chart-line mr-1"></i> Clientes
                            </a>
                            <a href="#" class="block py-1 text-sm text-gray-600 hover:text-indigo-600">
                                <i class="fas fa-shopping-cart mr-1"></i> Vendas
                            </a>
                        </div>
                    </div>
                </div>

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
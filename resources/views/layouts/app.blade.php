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
			   class="fixed top-0 left-0 h-full w-72 bg-white shadow-xl hidden overflow-y-auto"
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

			<nav class="p-3 space-y-1 text-sm">
				{{-- 1. Dashboard --}}
				<a href="{{ route('dashboard') }}"
				   class="flex items-center gap-3 px-3 py-2 rounded-lg transition duration-150 hover:bg-indigo-50 hover:text-indigo-600 {{ request()->routeIs('dashboard') ? 'bg-indigo-50 font-bold text-indigo-600' : 'text-gray-700' }}">
					<i class="fas fa-home w-5 text-center text-indigo-500"></i>
					<span>Dashboard</span>
				</a>

                {{-- 2. Comercial & Captação --}}
                <div x-data="{ open: {{ request()->routeIs('bags.*', 'admin.sacolinhas.*', 'sacolinhas.*', 'admin.sacolinha.*', 'admin.pedido.*', 'admin.avaliacoes.*') ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg transition duration-150 hover:bg-gray-100 {{ request()->routeIs('bags.*', 'admin.sacolinhas.*', 'sacolinhas.*', 'admin.sacolinha.*', 'admin.pedido.*', 'admin.avaliacoes.*') ? 'bg-gray-100 font-bold text-gray-900' : 'text-gray-700' }}">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-shopping-cart w-5 text-center text-indigo-500"></i>
                            <span>Comercial & Captação</span>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs transition duration-200" :class="open ? 'transform rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak class="pl-9 pr-2 py-1 space-y-1 bg-gray-50/80 rounded-lg mt-0.5 border border-gray-100">
                        <a href="{{ route('bags.index') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('bags.*') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-broadcast-tower mr-1.5 text-indigo-400"></i> Live
                        </a>
                        <a href="{{ route('admin.sacolinhas.index') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('admin.sacolinhas.index') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-shopping-bag mr-1.5 text-indigo-400"></i> Sacolas da Live
                        </a>
                        <a href="{{ route('admin.sacolinha.gestao') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('admin.sacolinha.gestao', 'admin.sacolinha.show') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-user-tag mr-1.5 text-indigo-400"></i> Sacolas por Cliente
                        </a>
                        <a href="{{ route('admin.sacolinhas.qrcode.scanner') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('admin.sacolinhas.qrcode.scanner') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-qrcode mr-1.5 text-indigo-400"></i> Bipar Sacolinha
                        </a>
                        <a href="{{ route('admin.pedido.index') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('admin.pedido.*') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-receipt mr-1.5 text-indigo-400"></i> Pedidos
                        </a>
                        <a href="{{ route('admin.avaliacoes.index') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('admin.avaliacoes.*') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-hand-holding-usd mr-1.5 text-emerald-500"></i> Avaliação Desapegos
                        </a>
                    </div>
                </div>

                {{-- 3. Produtos & Estoque --}}
                <div x-data="{ open: {{ request()->routeIs('items.*', 'admin.categorias.*', 'admin.marcas.*', 'inventario*', 'upload.batch.form', 'image-groups.*') ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg transition duration-150 hover:bg-gray-100 {{ request()->routeIs('items.*', 'admin.categorias.*', 'admin.marcas.*', 'inventario*', 'upload.batch.form', 'image-groups.*') ? 'bg-gray-100 font-bold text-gray-900' : 'text-gray-700' }}">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-box w-5 text-center text-indigo-500"></i>
                            <span>Produtos & Estoque</span>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs transition duration-200" :class="open ? 'transform rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak class="pl-9 pr-2 py-1 space-y-1 bg-gray-50/80 rounded-lg mt-0.5 border border-gray-100">
                        <a href="{{ route('items.index') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('items.index') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-tshirt mr-1.5 text-indigo-400"></i> Lista de Produtos
                        </a>
                        <a href="{{ route('admin.categorias.index') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('admin.categorias.*') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-tags mr-1.5 text-indigo-400"></i> Categorias
                        </a>
                        <a href="{{ route('admin.marcas.index') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('admin.marcas.*') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-copyright mr-1.5 text-indigo-400"></i> Marcas
                        </a>
                        <a href="{{ route('inventario') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('inventario') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-warehouse mr-1.5 text-indigo-400"></i> Inventário Físico
                        </a>
                        <a href="{{ route('inventario.conferencias.index') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('inventario.conferencias.*') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-check-double mr-1.5 text-indigo-400"></i> Conferências de Estoque
                        </a>
                        <a href="{{ route('upload.batch.form') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('upload.batch.form') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-download mr-1.5 text-indigo-400"></i> Download de Imagens
                        </a>
                        <a href="{{ route('image-groups.index') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('image-groups.index') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-image mr-1.5 text-indigo-400"></i> Vincular Fotos a Itens
                        </a>
                    </div>
                </div>

                {{-- 4. Clientes & Atendimento --}}
                <div x-data="{ open: {{ request()->routeIs('clientes.*', 'admin.clientes.*', 'admin.chat.*', 'admin.whatsapp.dashboard', 'admin.conta_corrente.*') ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg transition duration-150 hover:bg-gray-100 {{ request()->routeIs('clientes.*', 'admin.clientes.*', 'admin.chat.*', 'admin.whatsapp.dashboard', 'admin.conta_corrente.*') ? 'bg-gray-100 font-bold text-gray-900' : 'text-gray-700' }}">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-users w-5 text-center text-indigo-500"></i>
                            <span>Clientes & Atendimento</span>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs transition duration-200" :class="open ? 'transform rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak class="pl-9 pr-2 py-1 space-y-1 bg-gray-50/80 rounded-lg mt-0.5 border border-gray-100">
                        <a href="{{ route('clientes.index') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('clientes.index') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-address-book mr-1.5 text-indigo-400"></i> Lista de Clientes
                        </a>
                        <a href="{{ route('admin.chat.index') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('admin.chat.index') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-comments mr-1.5 text-indigo-400"></i> Chat ao Vivo
                        </a>
                        <a href="{{ route('admin.whatsapp.dashboard') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('admin.whatsapp.dashboard') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fab fa-whatsapp mr-1.5 text-emerald-500"></i> WhatsApp Dashboard
                        </a>
                        <a href="{{ route('admin.conta_corrente.index') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('admin.conta_corrente.*') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-wallet mr-1.5 text-indigo-400"></i> Carteira do Cliente
                        </a>
                    </div>
                </div>

                {{-- 5. Financeiro --}}
                <div x-data="{ 
                    open: {{ (request()->routeIs('financeiro.*') || request()->routeIs('classificacao_financeira.*')) ? 'true' : 'false' }},
                    openAnalises: {{ (request()->routeIs('financeiro.dashboard') || request()->routeIs('financeiro.fluxodecaixa') || request()->routeIs('financeiro.relatoriogerencial') || request()->routeIs('financeiro.dre') || request()->routeIs('financeiro.orcamento.*')) ? 'true' : 'false' }},
                    openOperacoes: {{ (request()->routeIs('financeiro.lancamentos.*') || request()->routeIs('financeiro.conciliacao.*') || request()->routeIs('financeiro.movimentacoes.*')) ? 'true' : 'false' }},
                    openCadastros: {{ (request()->routeIs('classificacao_financeira.*') || request()->routeIs('financeiro.contas.*') || request()->routeIs('financeiro.pessoas.*')) ? 'true' : 'false' }}
                }">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg transition duration-150 hover:bg-gray-100 {{ (request()->routeIs('financeiro.*') || request()->routeIs('classificacao_financeira.*')) ? 'bg-gray-100 font-bold text-gray-900' : 'text-gray-700' }}">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-dollar-sign w-5 text-center text-indigo-500"></i>
                            <span>Financeiro</span>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs transition duration-200" :class="open ? 'transform rotate-180' : ''"></i>
                    </button>
                    
                    <div x-show="open" x-cloak class="pl-6 pr-2 py-1 space-y-1.5 bg-gray-50/80 rounded-lg mt-0.5 border border-gray-100">
                        {{-- Subgrupo: Análises --}}
                        <div>
                            <button @click="openAnalises = !openAnalises" class="w-full flex items-center justify-between py-1 px-2 rounded text-xs font-semibold text-gray-700 hover:text-indigo-600">
                                <span><i class="fas fa-chart-line mr-1.5 text-indigo-400"></i> Análises</span>
                                <i class="fas fa-chevron-down text-[10px] transition-transform duration-200" :class="openAnalises ? 'rotate-180' : ''"></i>
                            </button>
                            <div x-show="openAnalises" x-cloak class="pl-5 space-y-0.5 mt-0.5">
                                <a href="{{ route('financeiro.dashboard') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.dashboard') ? 'font-bold text-indigo-600' : '' }}">Dashboard</a>
                                <a href="{{ route('financeiro.fluxodecaixa') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.fluxodecaixa') ? 'font-bold text-indigo-600' : '' }}">Fluxo de Caixa</a>
                                <a href="{{ route('financeiro.dre') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.dre') ? 'font-bold text-indigo-600' : '' }}">DRE Contábil</a>
                                <a href="{{ route('financeiro.relatoriogerencial') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.relatoriogerencial') ? 'font-bold text-indigo-600' : '' }}">Relatório Gerencial</a>
                                <a href="{{ route('financeiro.orcamento.index') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.orcamento.*') ? 'font-bold text-indigo-600' : '' }}">Orçamento</a>
                            </div>
                        </div>

                        {{-- Subgrupo: Operações --}}
                        <div>
                            <button @click="openOperacoes = !openOperacoes" class="w-full flex items-center justify-between py-1 px-2 rounded text-xs font-semibold text-gray-700 hover:text-indigo-600">
                                <span><i class="fas fa-exchange-alt mr-1.5 text-indigo-400"></i> Operações</span>
                                <i class="fas fa-chevron-down text-[10px] transition-transform duration-200" :class="openOperacoes ? 'rotate-180' : ''"></i>
                            </button>
                            <div x-show="openOperacoes" x-cloak class="pl-5 space-y-0.5 mt-0.5">
                                <a href="{{ route('financeiro.lancamentos.index') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.lancamentos.*') ? 'font-bold text-indigo-600' : '' }}">Lançamentos</a>
                                <a href="{{ route('financeiro.conciliacao.index') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.conciliacao.*') ? 'font-bold text-indigo-600' : '' }}">Conciliação</a>
                                <a href="{{ route('financeiro.movimentacoes.index') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.movimentacoes.*') ? 'font-bold text-indigo-600' : '' }}">Movimentações</a>
                            </div>
                        </div>

                        {{-- Subgrupo: Cadastros --}}
                        <div>
                            <button @click="openCadastros = !openCadastros" class="w-full flex items-center justify-between py-1 px-2 rounded text-xs font-semibold text-gray-700 hover:text-indigo-600">
                                <span><i class="fas fa-cogs mr-1.5 text-indigo-400"></i> Cadastros</span>
                                <i class="fas fa-chevron-down text-[10px] transition-transform duration-200" :class="openCadastros ? 'rotate-180' : ''"></i>
                            </button>
                            <div x-show="openCadastros" x-cloak class="pl-5 space-y-0.5 mt-0.5">
                                <a href="{{ route('financeiro.contas.index') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.contas.*') ? 'font-bold text-indigo-600' : '' }}">Contas Bancárias</a>
                                <a href="{{ route('classificacao_financeira.index') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('classificacao_financeira.*') ? 'font-bold text-indigo-600' : '' }}">Plano de Contas</a>
                                <a href="{{ route('financeiro.pessoas.index') }}" class="block py-1 text-xs text-gray-600 hover:text-indigo-600 {{ request()->routeIs('financeiro.pessoas.*') ? 'font-bold text-indigo-600' : '' }}">Contatos</a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 6. Clube & Engajamento --}}
                <div x-data="{ open: {{ request()->routeIs('admin.clube.*', 'admin.grupos.*') ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg transition duration-150 hover:bg-gray-100 {{ request()->routeIs('admin.clube.*', 'admin.grupos.*') ? 'bg-gray-100 font-bold text-gray-900' : 'text-gray-700' }}">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-crown w-5 text-center text-indigo-500"></i>
                            <span>Clube & Engajamento</span>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs transition duration-200" :class="open ? 'transform rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak class="pl-9 pr-2 py-1 space-y-1 bg-gray-50/80 rounded-lg mt-0.5 border border-gray-100">
                        <a href="{{ route('admin.clube.dashboard') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('admin.clube.dashboard') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-tachometer-alt mr-1.5 text-indigo-400"></i> Painel do Clube
                        </a>
                        <a href="{{ route('admin.clube.desafios.index') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('admin.clube.desafios.*') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-trophy mr-1.5 text-indigo-400"></i> Desafios
                        </a>
                        <a href="{{ route('admin.grupos.index') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('admin.grupos.*') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-users mr-1.5 text-indigo-400"></i> Grupos
                        </a>
                    </div>
                </div>

                {{-- 7. Relatórios --}}
                <div x-data="{ open: {{ request()->routeIs('admin.clientes.relatorios', 'admin.portal-acessos.*') ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg transition duration-150 hover:bg-gray-100 {{ request()->routeIs('admin.clientes.relatorios', 'admin.portal-acessos.*') ? 'bg-gray-100 font-bold text-gray-900' : 'text-gray-700' }}">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-chart-pie w-5 text-center text-indigo-500"></i>
                            <span>Relatórios</span>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs transition duration-200" :class="open ? 'transform rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak class="pl-9 pr-2 py-1 space-y-1 bg-gray-50/80 rounded-lg mt-0.5 border border-gray-100">
                        <a href="{{ route('admin.clientes.relatorios') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('admin.clientes.relatorios') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-chart-line mr-1.5 text-indigo-400"></i> Relatório de Clientes
                        </a>
                        <a href="{{ route('admin.portal-acessos.index') }}" class="block py-1 px-2 rounded text-xs text-gray-600 hover:text-indigo-600 hover:bg-white {{ request()->routeIs('admin.portal-acessos.*') ? 'font-bold text-indigo-600 bg-white shadow-xs' : '' }}">
                            <i class="fas fa-history mr-1.5 text-indigo-400"></i> Acessos ao Portal
                        </a>
                    </div>
                </div>

				<div class="my-2 border-t border-gray-200"></div>

				@if(auth()->check() && auth()->user()->role === 'admin_master')
					<div>
						<a href="{{ route('admin.equipe.index') }}"
							class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.equipe.*') ? 'bg-gray-100 font-bold text-indigo-600' : '' }}">
							<i class="fas fa-user-shield w-5 text-center text-indigo-500"></i>
							<span>Gestão de Equipe</span>
						</a>
						<a href="{{ route('admin.knowledge-base.index') ?? '#' }}"
							class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.knowledge-base.*') ? 'bg-gray-100 font-bold text-indigo-600' : '' }}">
							<i class="fas fa-brain w-5 text-center text-indigo-500"></i>
							<span>Memória da IA (RAG)</span>
						</a>
					</div>
					<div class="my-2 border-t border-gray-200"></div>
				@endif

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
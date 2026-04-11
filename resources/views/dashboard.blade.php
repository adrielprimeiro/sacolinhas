<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard</title>
	<link rel="icon" href="{{ asset('favicon.ico') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
	<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .bag-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .card-disabled {
            pointer-events: none;
            opacity: 0.6;
            background-color: #f7f7f7;
        }
        .live-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .live-ativa {
            background-color: #d4edda;
            color: #155724;
        }
        .live-encerrada {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* ===== MELHORIAS NO MENU ===== */
        .nav-link {
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        .nav-link.active {
            background-color: rgba(255,255,255,0.2);
            font-weight: bold;
        }
        .sidebar-brand {
            font-weight: bold;
            font-size: 1.4rem;
            margin-bottom: 1rem;
        }
        .nav-item {
            margin-bottom: 4px;
        }
        .nav-link i {
            width: 20px;
            margin-right: 8px;
        }
		
	  
		/* NOVOS ESTILOS PARA CARDS DO ESTOQUE */
		.card.h-100 {
			transition: transform 0.3s ease, box-shadow 0.3s ease;
		}
		
		.card.h-100:hover {
			transform: translateY(-5px);
			box-shadow: 0 8px 25px rgba(0,0,0,0.15);
		}
		
		.card-title {
			font-weight: 600;
		}
		
		.badge {
			font-size: 0.9em;
			padding: 8px 12px;
		}
		
		.border-info {
			border-color: #0dcaf0 !important;
			border-width: 2px !important;
		}
		
		.border-success {
			border-color: #198754 !important;
			border-width: 2px !important;
		}
		
		.border-danger {
			border-color: #dc3545 !important;
			border-width: 2px !important;
		}
		
		.border-warning {
			border-color: #ffc107 !important;
			border-width: 2px !important;
		}
		
		/* Cor roxa para sacolas */
		.text-purple {
			color: #6f42c1 !important;
		}
		
		/* Responsividade */
		@media (max-width: 768px) {
			.card .display-6 {
				font-size: 2rem;
			}
			
			.card h6 {
				font-size: 0.9rem;
			}
		}
			
		/* Estilo para a cor de fundo roxa */
		.bg-purple {
			background-color: #6f42c1 !important; /* Usando o mesmo tom de roxo do text-purple */
			color: #ffffff !important; /* Cor do texto branca para contrastar com o fundo roxo */
		}

		/* Estilo para a borda roxa (usado no card) */
		.border-purple {
			border-color: #6f42c1 !important;
			border-width: 2px !important;
		}	
		
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
			<div class="col-md-2 sidebar text-white p-0">
				<div class="p-3">
					<div class="sidebar-brand">
						<i class="fas fa-store"></i> Admin
					</div>
					<hr class="text-white-50">
					<ul class="nav flex-column">
						<li class="nav-item">
							<a class="nav-link text-white {{ request()->routeIs('dashboard') ? 'active' : '' }}" 
							   href="{{ route('dashboard') }}">
								<i class="fas fa-home"></i> Dashboard
							</a>
						</li>
						<li class="nav-item">
							<a class="nav-link text-white {{ request()->routeIs('financeiro.*') ? 'active' : '' }}" 
							   href="{{ route('admin.financeiro.index') }}">
								<i class="fas fa-wallet mr-1"></i>Financeiro
							</a>
						</li>						
						<!-- SEÇÃO DE CLIENTES -->
						<li class="nav-item">
							<a class="nav-link text-white {{ request()->routeIs('clientes.*') ? 'active' : '' }}" 
							   href="{{ route('clientes.index') }}">
								<i class="fas fa-users"></i> Clientes
							</a>
						</li>
						<!-- SEÇÃO DE PEDIDOS -->
						<li class="nav-item">
							<a class="nav-link text-white {{ request()->routeIs('pedidos.*') ? 'active' : '' }}" 
							   href="{{ route('pedidos.index') }}">
								<i class="fas fa-file-invoice"></i> Pedidos
							</a>
						</li>						
						<!-- ✅ ITENS COM SUBMENU (Inventário) -->
						<li class="nav-item">
							<a class="nav-link text-white {{ request()->routeIs('items.*', 'inventario') ? 'active' : '' }}" 
							   href="#" data-bs-toggle="collapse" data-bs-target="#itensMenu">
								<i class="fas fa-box"></i> Itens
								<i class="fas fa-chevron-down float-end mt-1"></i>
							</a>
							<div class="collapse {{ request()->routeIs('items.*', 'inventario') ? 'show' : '' }}" id="itensMenu">
								<ul class="nav flex-column ms-3">
									<li class="nav-item">
										<a class="nav-link text-white {{ request()->routeIs('items.*') ? 'active' : '' }}" 
										   href="{{ route('items.index') }}">
											<i class="fas fa-list"></i> Lista de Itens
										</a>
									</li>
									<li class="nav-item">
										<a class="nav-link text-white {{ request()->routeIs('inventario') ? 'active' : '' }}" 
										   href="{{ route('inventario') }}">
											<i class="fas fa-clipboard-list"></i> Inventário
										</a>
									</li>

									<!-- Download Imagens -->
									<li class="nav-item">
									  <a class="nav-link text-white {{ request()->routeIs('inventario') ? 'active' : '' }}"
										 href="{{ route('upload.batch.form') }}">
										<i class="fas fa-download"></i> Download Imagens
									  </a>
									</li>

									<!-- Imagens->Item -->
									<li class="nav-item">
									  <a class="nav-link text-white {{ request()->routeIs('inventario') ? 'active' : '' }}"
										 href="{{ route('image-groups.index') }}">
										<i class="fas fa-image"></i> Imagens->Item
									  </a>
									</li>									
									
								</ul>
							</div>
						</li>
						
						<!-- LIVE -->
						<li class="nav-item">
							<a class="nav-link text-white {{ request()->routeIs('bags.*') ? 'active' : '' }}" 
							   href="{{ route('bags.index') }}">
								<i class="fas fa-broadcast-tower"></i> Live
							</a>
						</li>
						
						<!-- ✅ SACOLAS COM SUBMENU (Da Live / Por Cliente) -->
						<li class="nav-item">
							<a class="nav-link text-white {{ request()->routeIs('admin.sacolinhas.*', 'sacolinhas.*') ? 'active' : '' }}" 
							   href="#" data-bs-toggle="collapse" data-bs-target="#sacolinhasMenu">
								<i class="fas fa-shopping-bag"></i> Sacolas
								<i class="fas fa-chevron-down float-end mt-1"></i>
							</a>
							<div class="collapse {{ request()->routeIs('admin.sacolinhas.*', 'sacolinhas.*') ? 'show' : '' }}" id="sacolinhasMenu">
								<ul class="nav flex-column ms-3">
									<li class="nav-item">
										<a class="nav-link text-white {{ request()->routeIs('admin.sacolinhas.*') ? 'active' : '' }}" 
										   href="{{ route('admin.sacolinhas.index') }}">
											<i class="fas fa-broadcast-tower"></i> Da Live
										</a>
									</li>
									<li class="nav-item">
										<a class="nav-link text-white {{ request()->routeIs('sacolinhas.cliente') ? 'active' : '' }}" 
										   href="{{ route('sacolinhas.consultar') }}">
											<i class="fas fa-user"></i> Por Cliente
										</a>
									</li>
									<li class="nav-item">
										<a class="nav-link text-white {{ request()->routeIs('admin.sacolinhas.qrcode.scanner') ? 'active' : '' }}" 
										   href="{{ route('admin.sacolinhas.qrcode.scanner') }}">
											<i class="fas fa-qrcode me-2"></i> Item->Sacolinha
										</a>
									</li>									
								</ul>
							</div>
						</li>
						
						<!-- SEPARADOR -->
						<hr class="text-white-50 my-3">
						
						<!-- SEÇÃO DE RELATÓRIOS -->
						<li class="nav-item">
							<a class="nav-link text-white-50 small" href="#" data-bs-toggle="collapse" data-bs-target="#relatoriosMenu">
								<i class="fas fa-chart-bar"></i> Relatórios
								<i class="fas fa-chevron-down float-end mt-1"></i>
							</a>
							<div class="collapse" id="relatoriosMenu">
								<ul class="nav flex-column ms-3">
									<li class="nav-item">
										<a class="nav-link text-white-75 small" 
										   href="{{ route('admin.clientes.relatorios') ?? '#' }}">
											<i class="fas fa-user-chart"></i> Clientes
										</a>
									</li>
									<li class="nav-item">
										<a class="nav-link text-white-75 small" href="#">
											<i class="fas fa-shopping-chart"></i> Vendas
										</a>
									</li>
								</ul>
							</div>
						</li>
						
						<!-- CONFIGURAÇÕES -->
						<li class="nav-item mt-3">
							<a class="nav-link text-white-50 small" href="#">
								<i class="fas fa-cog"></i> Configurações
							</a>
						</li>
						
						<!-- LOGOUT -->
						<li class="nav-item">
							<form method="POST" action="{{ route('logout') }}" class="d-inline">
								@csrf
								<button type="submit" class="nav-link text-white-50 small border-0 bg-transparent w-100 text-start">
									<i class="fas fa-sign-out-alt"></i> Sair
								</button>
							</form>
						</li>
					</ul>
				</div>
			</div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <!-- Header com Breadcrumbs -->
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                    <!-- Título com Breadcrumbs -->
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-1">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('dashboard') }}" class="text-decoration-none">
                                        <i class="fas fa-home"></i> Home
                                    </a>
                                </li>
                                @if(request()->routeIs('clientes.*'))
                                    <li class="breadcrumb-item active">Clientes</li>
                                @elseif(request()->routeIs('items.*'))
                                    <li class="breadcrumb-item active">Itens</li>
                                @elseif(request()->routeIs('bags.*'))
                                    <li class="breadcrumb-item active">Live</li>
                                @elseif(request()->routeIs('admin.sacolinhas.*'))
                                    <li class="breadcrumb-item active">Sacolas</li>
                                @else
                                    <li class="breadcrumb-item active">Dashboard</li>
                                @endif
                            </ol>
                        </nav>
                        <h2 class="mb-0">
                            @if(request()->routeIs('clientes.*'))
                                <i class="fas fa-users text-primary"></i> Gestão de Clientes
                            @elseif(request()->routeIs('items.*'))
                                <i class="fas fa-box text-primary"></i> Gestão de Itens
                            @elseif(request()->routeIs('bags.*'))
                                <i class="fas fa-broadcast-tower text-primary"></i> Gestão de Lives
                            @elseif(request()->routeIs('admin.sacolinhas.*'))
                                <i class="fas fa-shopping-bag text-primary"></i> Gestão de Sacolas
                            @else
                                <i class="fas fa-home text-primary"></i> Dashboard
                            @endif
                        </h2>
                    </div>
                    
                    <!-- Ações Rápidas -->
                    <div class="d-flex gap-2">
                        @if(request()->routeIs('clientes.*'))
                            <a href="{{ route('clientes.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Novo Cliente
                            </a>
                        @elseif(request()->routeIs('items.*'))
                            <a href="{{ route('items.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Novo Item
                            </a>
                        @endif
                        
                        <!-- Notificações -->
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                <span class="badge bg-danger">3</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#">Novo cliente cadastrado</a></li>
                                <li><a class="dropdown-item" href="#">Live iniciada</a></li>
                                <li><a class="dropdown-item" href="#">Estoque baixo</a></li>
                            </ul>
                        </div>
                        
                        <!-- Profile -->
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> {{ Auth::user()->name }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user-edit"></i> Perfil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-sign-out-alt"></i> Sair
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

				<!-- Cards de Estatísticas Rápidas (apenas no dashboard) -->
				@if(request()->routeIs('dashboard'))

					<!-- Linha 1 -->
					<div class="row g-3 mb-4">

						<!-- Card: Atenção - Vencimentos -->
						<div class="col-12 col-sm-6 col-lg-3">
							<div class="card text-center h-100 border-danger">
								<div class="card-body">
									<i class="fas fa-triangle-exclamation fa-2x text-danger mb-2"></i>
									<h5 class="card-title text-danger">Atenção - Vencimentos</h5>

									<div class="mb-2">
										<div class="small text-muted">Sacolinhas com vencimento hoje</div>
										<span class="badge bg-danger fs-6">
											{{ $alertasVencimento['sacolas_vencem_hoje'] ?? 0 }}
										</span>
									</div>


									<div class="mb-2">
										<div class="small text-muted">Itens vencendo hoje</div>
										<span class="badge bg-danger fs-6">
											{{ $alertasVencimento['itens_vencem_hoje'] ?? 0 }}
										</span>
									</div>

									<div class="mb-3">
										<div class="small text-muted">Valor dos itens vencendo hoje</div>
										<div class="fw-bold text-danger">
											R$ {{ number_format($alertasVencimento['valor_itens_vencem_hoje'] ?? 0, 2, ',', '.') }}
										</div>
									</div>

									<a href="{{ route('admin.vencimentos') }}" class="btn btn-danger btn-sm">
										Ver sacolas
									</a>
								</div>
							</div>
						</div>

						<!-- Card: Estoque -->
						<div class="col-12 col-sm-6 col-lg-3">
							<div class="card text-center h-100 border-info">
								<div class="card-body">
									<i class="fas fa-warehouse fa-2x text-info mb-2"></i>
									<h5 class="card-title text-info">Estoque</h5>

									<div class="mb-2">
										<div class="small text-muted">Quantidade</div>
										<span class="badge bg-info fs-6">
											{{ $estoqueInfo['quantidade'] ?? 0 }} itens
										</span>
									</div>

									<div class="mb-2">
										<div class="small text-muted">Valor Total</div>
										<div class="text-success fw-bold">
											R$ {{ number_format($estoqueInfo['valor_total'] ?? 0, 2, ',', '.') }}
										</div>
									</div>

									<div class="mb-3">
										<div class="small text-muted">Valor Médio</div>
										<div class="text-warning fw-bold">
											R$ {{ number_format($estoqueInfo['valor_medio'] ?? 0, 2, ',', '.') }}
										</div>
									</div>

									<a href="{{ route('inventario') }}?status=estoque" class="btn btn-sm btn-info">
										<i class="fas fa-eye me-1"></i>Ver Estoque
									</a>
								</div>
							</div>
						</div>

						<!-- Card: Sacolas -->
						<div class="col-12 col-sm-6 col-lg-3">
							<div class="card text-center h-100 border-purple">
								<div class="card-body">
									<i class="fas fa-shopping-bag fa-2x text-purple mb-2"></i>
									<h5 class="card-title text-purple">Sacolas</h5>

									<div class="mb-2">
										<div class="small text-muted">Total de Sacolas</div>
										<span class="badge bg-purple fs-6">
											{{ $sacolasInfo['total_sacolas'] ?? 0 }}
										</span>
									</div>

									<div class="mb-2">
										<div class="small text-muted">Itens em Sacolas</div>
										<div class="text-info fw-bold">
											{{ $sacolasInfo['total_itens'] ?? 0 }} itens
										</div>
									</div>

									<div class="mb-3">
										<div class="small text-muted">Valor Total</div>
										<div class="text-success fw-bold">
											R$ {{ number_format($sacolasInfo['valor_total'] ?? 0, 2, ',', '.') }}
										</div>
									</div>

									<a href="{{ route('admin.sacolinhas.index') }}" class="btn btn-sm" style="background: #6f42c1; color: white;">
										Ver Todas
									</a>
								</div>
							</div>
						</div>

						<!-- Card: Clientes -->
						<div class="col-12 col-sm-6 col-lg-3">
							<div class="card text-center h-100">
								<div class="card-body">
									<i class="fas fa-users fa-2x text-primary mb-2"></i>
									<h5 class="card-title">Clientes</h5>
									<p class="card-text display-6">{{ $estatisticas['total_clientes'] ?? 0 }}</p>
									<a href="{{ route('clientes.index') }}" class="btn btn-sm btn-primary">Ver Todos</a>
								</div>
							</div>
						</div>

					</div>

					<!-- Linha 2 -->
					<div class="row g-3 mb-4">

						<!-- Card: Disponíveis -->
						<div class="col-12 col-sm-6 col-lg-3">
							<div class="card text-center h-100 border-success">
								<div class="card-body">
									<i class="fas fa-check-circle fa-2x text-success mb-2"></i>
									<h5 class="card-title text-success">Disponíveis</h5>
									<p class="card-text display-6">{{ $estatisticas['itens_disponiveis'] ?? 0 }}</p>
									<a href="{{ route('inventario') }}?status=disponivel" class="btn btn-sm btn-success">Ver</a>
								</div>
							</div>
						</div>

						<!-- Card: Vendidos -->
						<div class="col-12 col-sm-6 col-lg-3">
							<div class="card text-center h-100 border-danger">
								<div class="card-body">
									<i class="fas fa-shopping-cart fa-2x text-danger mb-2"></i>
									<h5 class="card-title text-danger">Vendidos</h5>
									<p class="card-text display-6">{{ $estatisticas['itens_vendidos'] ?? 0 }}</p>
									<a href="{{ route('inventario') }}?status=vendido" class="btn btn-sm btn-danger">Ver</a>
								</div>
							</div>
						</div>

						<!-- Card: Reservados -->
						<div class="col-12 col-sm-6 col-lg-3">
							<div class="card text-center h-100 border-warning">
								<div class="card-body">
									<i class="fas fa-clock fa-2x text-warning mb-2"></i>
									<h5 class="card-title text-warning">Reservados</h5>
									<p class="card-text display-6">{{ $estatisticas['itens_reservados'] ?? 0 }}</p>
									<a href="{{ route('inventario') }}?status=reservado" class="btn btn-sm btn-warning">Ver</a>
								</div>
							</div>
						</div>

						<!-- Card: Itens Total -->
						<div class="col-12 col-sm-6 col-lg-3">
							<div class="card text-center h-100">
								<div class="card-body">
									<i class="fas fa-box fa-2x text-success mb-2"></i>
									<h5 class="card-title">Itens Total</h5>
									<p class="card-text display-6">{{ $estatisticas['total_itens'] ?? 0 }}</p>
									<a href="{{ route('items.index') }}" class="btn btn-sm btn-success">Ver Todos</a>
								</div>
							</div>
						</div>

					</div>

				@endif

                <!-- Conteúdo da Página -->
                <div class="row">
                    <div class="col-12">
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
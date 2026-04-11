<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Controle de Pedidos</title>
	<link rel="icon" href="{{ asset('favicon.ico') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
	<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
	

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reset básico e box-sizing */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Layout principal com Flexbox */
        .main-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Sidebar */
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex-shrink: 0; /* Não permite que o sidebar encolha */
            width: 200px; /* Largura fixa do sidebar */
            overflow-y: auto; /* Scroll se o conteúdo for maior que a altura */
            padding: 1rem 0;
        }

        .sidebar-brand {
            font-weight: bold;
            font-size: 1.4rem;
            margin-bottom: 1rem;
            padding: 0 1rem;
            text-align: center;
        }

        .sidebar .nav-link {
            border-radius: 8px;
            margin: 2px 1rem;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            transform: translateX(5px);
            color: white;
        }

        .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.2);
            font-weight: bold;
            color: white;
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 8px;
        }

        .sidebar .nav-item {
            margin-bottom: 4px;
        }

        .sidebar .collapse .nav-link {
            padding-left: 2.5rem; /* Indentação para submenus */
            font-size: 0.9rem;
        }

        .sidebar hr {
            border-color: rgba(255, 255, 255, 0.2);
            margin: 1rem 0;
        }

        /* Main Content */
        .main-content {
            flex: 1; /* Ocupa o restante do espaço */
            padding: 1.5rem; /* Espaçamento interno */
            overflow-y: auto; /* Scroll se o conteúdo for maior que a altura */
        }

        /* Cards e Elementos Gerais */
        .card { 
            margin-bottom: 1.5rem; 
            border: none; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            border-radius: 0.75rem;
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.5rem;
            font-weight: 600;
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
        }

        .card-header h5 {
            margin-bottom: 0;
            color: #343a40;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Cliente Dropdown */
        .cliente-dropdown { 
            position: fixed; /* Posicionamento fixo para evitar problemas de scroll */
            z-index: 2000; /* Acima de outros elementos */
            max-height: 300px; 
            overflow-y: auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .cliente-item { 
            cursor: pointer; 
            padding: 10px 15px; 
            border-bottom: 1px solid #eee; 
        }

        .cliente-item:last-child {
            border-bottom: none;
        }

        .cliente-item:hover { 
            background: #f8f9fa; 
        }

        /* Tabelas */
        .table-sm th { 
            background: #4169e1; 
            color: white; 
            font-weight: 600;
            padding: 0.75rem 1rem;
        }

        .table-sm td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
        }

        .item-row, .item-pedido-row { 
            cursor: pointer; 
            transition: background 0.2s; 
        }

        .item-row:hover { 
            background: #f0f0f0; 
        }

        .item-pedido-row:hover { 
            background: #fff3cd; 
        }

        /* Container das Tabelas (Grid Responsivo) */
        .tabela-container { 
            display: grid; 
            grid-template-columns: 1fr 1fr; /* Duas colunas por padrão */
            gap: 1.5rem; /* Espaçamento entre as colunas */
            margin-top: 1.5rem;
        }

        @media (max-width: 1200px) {
            .tabela-container { 
                grid-template-columns: 1fr; /* Uma coluna em telas menores */
            }
        }

        /* Informações de Resumo no Header do Card */
        .card-header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; /* Permite quebrar linha em telas pequenas */
        }

        .info-resumo {
            font-size: 0.9rem;
            text-align: right;
            margin-top: 0.5rem;
        }

        .info-resumo strong {
            display: block;
            font-size: 1.2rem;
            color: #666666;
        }

        .info-resumo small {
            /*color: rgba(255, 255, 255, 0.8);*/
			color: #666666;
        }

        /* Loading Spinner */
        #loading {
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-store"></i> Admin
            </div>
            <hr>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" 
                       href="{{ route('dashboard') }}">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                
                <!-- SEÇÃO DE CLIENTES -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}" 
                       href="{{ route('clientes.index') }}">
                        <i class="fas fa-users"></i> Clientes
                    </a>
                </li>
                
                <!-- SEÇÃO DE PEDIDOS -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('pedidos.*') ? 'active' : '' }}" 
                       href="{{ route('pedidos.index') }}">
                        <i class="fas fa-file-invoice"></i> Pedidos
                    </a>
                </li>
                
                <!-- ITENS COM SUBMENU -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('items.*', 'inventario') ? 'active' : '' }}" 
                       href="#" data-bs-toggle="collapse" data-bs-target="#itensMenu">
                        <i class="fas fa-box"></i> Itens
                        <i class="fas fa-chevron-down float-end mt-1"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('items.*', 'inventario') ? 'show' : '' }}" id="itensMenu">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('items.*') ? 'active' : '' }}" 
                                   href="{{ route('items.index') }}">
                                    <i class="fas fa-list"></i> Lista de Itens
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('inventario') ? 'active' : '' }}" 
                                   href="{{ route('inventario') }}">
                                    <i class="fas fa-clipboard-list"></i> Inventário
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                
                <!-- LIVE -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('bags.*') ? 'active' : '' }}" 
                       href="{{ route('bags.index') }}">
                        <i class="fas fa-broadcast-tower"></i> Live
                    </a>
                </li>
                
                <!-- SACOLAS COM SUBMENU -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.sacolinhas.*', 'sacolinhas.*') ? 'active' : '' }}" 
                       href="#" data-bs-toggle="collapse" data-bs-target="#sacolinhasMenu">
                        <i class="fas fa-shopping-bag"></i> Sacolas
                        <i class="fas fa-chevron-down float-end mt-1"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('admin.sacolinhas.*', 'sacolinhas.*') ? 'show' : '' }}" id="sacolinhasMenu">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.sacolinhas.*') ? 'active' : '' }}" 
                                   href="{{ route('admin.sacolinhas.index') }}">
                                    <i class="fas fa-broadcast-tower"></i> Da Live
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('sacolinhas.cliente') ? 'active' : '' }}" 
                                   href="{{ route('sacolinhas.consultar') }}">
                                    <i class="fas fa-user"></i> Por Cliente
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                
                <hr>
                
                <!-- LOGOUT -->
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}" class="d-inline w-100">
                        @csrf
                        <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </button>
                    </form>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header com Breadcrumbs e Profile -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1">
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboard') }}" class="text-decoration-none">
                                    <i class="fas fa-home"></i> Home
                                </a>
                            </li>
                            <li class="breadcrumb-item active">Pedidos</li>
                        </ol>
                    </nav>
                    <h2 class="mb-0">
                        <i class="fas fa-file-invoice text-primary"></i> Controle de Pedidos
                    </h2>
                </div>
                
                <!-- Profile Dropdown -->
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

            <!-- Card Busca Cliente -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-search"></i> Selecionar Cliente
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <label class="form-label">Buscar Cliente</label>
                            <div class="position-relative">
                                <input type="text" id="cliente-search" class="form-control" 
                                       placeholder="Digite nome ou email..." autocomplete="off">
                                <div id="cliente-dropdown" class="list-group cliente-dropdown" style="display: none;"></div>

								<button type="button" class="btn btn-outline-secondary" id="btn-limpar-selecao">
									🗑️ Limpar Seleção
								</button>	
								<button type="button" id="btn-criar-pedido" class="btn btn-success" disabled>
									<i class="fas fa-check me-2"></i>Criar Pedido
								</button>								
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div id="cliente-info" class="alert alert-success mt-3" style="display: none;">
                                <strong>✓ Cliente:</strong> <span id="cliente-nome"></span><br>
								<i class="fas fa-tasks"></i><strong> Saldo:</strong> <span id="cliente-saldo" class="text-primary fw-bold">R$ 0,00</span><br>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Tabelas: Sacolinha e Pedido -->
            <div id="tabelas-container" class="tabela-container" style="display: none;">
                <!-- Tabela Sacolinha -->
                <div id="tabela-sacolinha-card" class="card">
                    <div class="card-header bg-light text-blue">
                        <div class="card-header-info">
                            <div>
                                <h5 class="mb-0"><i class="fas fa-shopping-bag"></i> Itens na Sacolinha</h5>
                                <small>Clique no item para mover para o pedido</small>
                            </div>
                            <div class="info-resumo">
								<button type="button" id="btn-imprimir-sacolinha" class="btn btn-info text-white w-100 btn-lg" disabled>
									<i class="fas fa-print me-2"></i> Imprimir
								</button>
								<div style="display:flex; align-items:center; gap:6px; white-space:nowrap;">
									<small>Itens:</small>
									<strong id="sacolinha-total-itens">0</strong>

									<small>Valor Total:</small>
									<strong>R$ <span id="sacolinha-valor-total">0,00</span></strong>
								</div>
                            </div>
							
							

                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Produto</th>
                                        <th>Preço</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody id="itens-sacolinha-tbody">
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            Selecione um cliente para visualizar a sacolinha
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tabela Pedido -->
				<div class="card-header bg-light text-blue">
				    <div class="card-header-info d-flex justify-content-between align-items-start w-100">
					
						<div>
						  <h5 class="mb-0"><i class="fas fa-file-invoice"></i> Itens no Pedido</h5>
						  <small id="pedido-numero-info">Pedido Pendente</small>
						  <small style="display: block; margin-top: 5px;">Clique no item para devolver para sacolinha</small>
						  <br>
						  <div style="display:flex; align-items:center; gap:6px; white-space:nowrap;">
							<small>Itens:</small>
							<strong id="pedido-total-itens">0</strong>
							<small>Valor:</small>
							<strong>R$ <span id="pedido-valor">0,00</span></strong>
						  </div>
						</div>

						<div class="info-resumo d-flex flex-column align-items-end gap-2 text-end">
						  <button type="button" id="btn-imprimir-pedido" class="btn btn-secondary" disabled>
							<i class="fas fa-print me-2"></i>Imprimir Pedidos
						  </button>

						  <div class="input-group" style="width: 250px;">
							<select class="form-select" id="status-pedido">
							  <option value="" selected disabled>Alterar Status...</option>
							  <option value="pendente">Pendente</option>
							  <option value="processando">Processando</option>
							  <option value="pago">Pago</option>
							  <option value="enviado">Enviado</option>
							  <option value="concluido">Concluído</option>
							  <option value="cancelado">Cancelado</option>
							</select>
							<button class="btn btn-primary" type="button" id="btn-atualizar-status">
							  <i class="fas fa-sync-alt me-1"></i>Atualizar
							</button>
						  </div>

						  <div style="display:flex; align-items:center; gap:6px; white-space:nowrap; justify-content:flex-end;">
							<small>Frete:</small>
							<strong>R$ <span id="frete">0,00</span></strong>
							<small>Valor Total:</small>
							<strong>R$ <span id="pedido-valor-total">0,00</span></strong>
						  </div>
						</div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Produto</th>
                                        <th>Preço</th>
                                    </tr>
                                </thead>
                                <tbody id="itens-pedido-tbody">
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            Nenhum item no pedido ainda
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading -->
            <div id="loading" class="text-center py-4" style="display: none;">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Carregando...</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        let clienteAtual = null;
        let pedidoAtual = null;
        let debounceTimer = null;

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // ✅ Busca cliente ao digitar
        $('#cliente-search').on('input', function() {
            const termo = $(this).val().trim();
            clearTimeout(debounceTimer);
            
            if (termo.length < 2) {
                $('#cliente-dropdown').hide();
                return;
            }

            debounceTimer = setTimeout(() => {
                buscarClientes(termo);
            }, 300);
        });

        function buscarClientes(termo) {
            $.ajax({
                url: '/pedidos/buscar-clientes',
                data: { termo: termo },
                success: function(clientes) {
                    mostrarDropdown(clientes);
                },
                error: function() {
                    $('#cliente-dropdown').html('<div class="list-group-item text-danger">Erro na busca</div>').show();
                }
            });
        }

        function mostrarDropdown(clientes) {
            const dropdown = $('#cliente-dropdown');
            
            dropdown.empty();

            if (!clientes.length) {
                dropdown.html('<div class="list-group-item">Nenhum cliente encontrado</div>');
                dropdown.show();
                posicionarDropdown();
                return;
            }

            clientes.forEach(cliente => {
                dropdown.append(`
                    <div class="list-group-item cliente-item" 
                         data-id="${cliente.id}" 
                         data-name="${cliente.name}" 
                         data-email="${cliente.email}"
                         data-saldo="${cliente.saldo_bruto}"             {{-- NOVO: Saldo bruto --}}
                         data-saldo-formatado="${cliente.saldo_formatado}"> {{-- NOVO: Saldo formatado --}}
                        <strong>${cliente.name}</strong><br>
                        <small>${cliente.email}</small><br>
                        <small>Saldo: ${cliente.saldo_formatado}</small> {{-- NOVO: Exibe o saldo no dropdown --}}
                    </div>
                `);
            });
            
            dropdown.show();
            posicionarDropdown();
        }


        function posicionarDropdown() {
            const input = $('#cliente-search');
            const dropdown = $('#cliente-dropdown');
            const offset = input.offset();
            
            dropdown.css({
                'position': 'fixed',
                'top': (offset.top + input.outerHeight() + 5) + 'px',
                'left': offset.left + 'px',
                'width': input.outerWidth() + 'px',
                'z-index': '2000'
            });
        }

        // ✅ Reposicionar dropdown ao scroll ou redimensionar
        $(window).on('scroll resize', function() {
            if ($('#cliente-dropdown').is(':visible')) {
                posicionarDropdown();
            }
        });

        // ✅ Selecionar cliente
		$(document).on('click', '.cliente-item', function() {
			const clickedItem = $(this); 
			clienteAtual = {
				id: clickedItem.data('id'),
				name: clickedItem.data('name'),
				email: clickedItem.data('email'),
				saldo_formatado: clickedItem.data('saldo-formatado'),
				saldo_bruto: clickedItem.data('saldo')    
			};

			// 2. Logar o objeto clienteAtual completo
			console.log('Objeto clienteAtual após preenchimento:', clienteAtual);
			// 3. Logar o valor que será inserido no #cliente-saldo
			console.log('Valor que será inserido no #cliente-saldo:', clienteAtual.saldo_formatado);


			$('#cliente-search').val(clienteAtual.name);
			$('#cliente-dropdown').hide();
			$('#cliente-nome').text(clienteAtual.name);
			$('#cliente-saldo').text(clienteAtual.saldo_formatado); 
			$('#cliente-info').show();
			$('#card-acoes').show();

			carregarDados(clienteAtual.id);
			togglePrintButtons();
		});
		

		// ✅ CRIAR PEDIDO - Evento de clique
		$('#btn-criar-pedido').on('click', function() {
			if (!clienteAtual || !clienteAtual.id) {
				alert('⚠️ Por favor, selecione um cliente primeiro');
				return;
			}

			// Desabilitar botão
			const $btn = $(this);
			const originalText = $btn.html();
			$btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Criando...');

			$.ajax({
				url: '/pedidos/criar-pedido',
				type: 'POST',
				data: {
					_token: $('meta[name="csrf-token"]').attr('content'),
					user_id: clienteAtual.id
				},
				success: function(response) {
					if (response.success) {
						showNotification('✅ Pedido criado: ' + response.pedido_numero, 'success');
						console.log('✅ Novo pedido:', response);
						
						// Recarregar dados
						carregarDados(clienteAtual.id);
					} else {
						showNotification('❌ ' + response.message, 'error');
					}
				},
				error: function(xhr) {
					const errorMsg = xhr.responseJSON?.message || 'Erro ao criar pedido';
					showNotification('❌ ' + errorMsg, 'error');
					console.error('❌ Erro:', xhr);
				},
				complete: function() {
					// Restaurar botão
					$btn.html(originalText).prop('disabled', false);
				}
			});
		});


		function carregarDados(userId) {
			if (!userId) return;

			$('#loading').show();
			$('#card-acoes, #tabelas-container').hide();

			// ✅ CHAMADA 1: Carregar SACOLINHA
			$.ajax({
				url: '/pedidos/itens-sacolinha',
				type: 'POST',
				data: { 
					_token: $('meta[name="csrf-token"]').attr('content'),
					user_id: userId 
				},
				success: function(response) {
					console.log('✅ Sacolinha carregada:', response);  
					if (response.success) {
						preencherSacolinha(response.itens_sacolinha);
						atualizarResumoSacolinha(response.resumo);
					} else {
						alert('Erro: ' + response.message);
					}
				},
				error: function() {
					alert('Erro ao carregar sacolinha');
				}
			});

			// ✅ CHAMADA 2: Carregar PEDIDO (separado)
			$.ajax({
				url: '/pedidos/itens-pedido',
				type: 'POST',
				data: { 
					_token: $('meta[name="csrf-token"]').attr('content'),
					user_id: userId 
				},
				success: function(response) {
					console.log('✅ Pedido carregado:', response);  
					console.log('pedido_status:', response.pedido_status);
					console.log('pedido_valor_frete:', response.pedido_valor_frete);
					const frete = parseFloat(response.pedido_valor_frete) || 0;
					if (response.success) {
						if (response.tem_pedido) {
							pedidoAtual = {
							  id: response.pedido_id,
							  numero: response.pedido_numero,
							  status: response.pedido_status
							};
							const status = normalizarStatus(response.pedido_status);
							$('#status-pedido').val(status);

							const frete = response.pedido_valor_frete ?? 0;
							$('#frete').text(formatMoneyBR(frete));							

					
							preencherPedido(response.itens_pedido, response.pedido_numero, frete);
							
							// ✅ Verificar status
							if (response.pedido_status === 'concluido') {
								// Pedido concluído: desabilitar card
								$('#tabela-pedido-card').show();
								$('#tabela-pedido-card').css({
									'opacity': '0.5',
									'pointer-events': 'none',
									'background-color': '#f0f0f0'
								});
								$('#tabela-pedido-card').find('button, select, input').prop('disabled', true);
								$('#pedido-numero-info').html(`
									Pedido: ${response.pedido_numero}
									<span class="badge bg-success ms-2">Concluído</span>
								`);
								$('#btn-criar-pedido').show().prop('disabled', false);
							} else {
								// Pedido ativo: habilitar card
								$('#tabela-pedido-card').show();
								$('#tabela-pedido-card').css({
									'opacity': '1',
									'pointer-events': 'auto',
									'background-color': 'white'
								});
								$('#status-pedido').val('');
								$('#pedido-valor-frete').val('R$ 0,00');
								$('#tabela-pedido-card').find('button, select, input').prop('disabled', false);
								const statusLabel = response.pedido_status.charAt(0).toUpperCase() + response.pedido_status.slice(1);
								$('#pedido-numero-info').html(`
									Pedido: ${response.pedido_numero}
									<span class="badge bg-warning ms-2">${statusLabel}</span>
								`);
								$('#btn-criar-pedido').hide();
							}
						} else {
							// ✅ Sem pedido: ocultar card e mostrar botão "Criar Pedido"
							$('#status-pedido').val('');
							$('#frete').text('0,00');  
							$('#pedido-valor-total').text('0,00');  
							$('#tabela-pedido-card').hide();
							$('#btn-criar-pedido').show().prop('disabled', false);
						}
					}
				},
				error: function(xhr) {
					console.error('❌ Erro ao carregar pedido:', xhr);
					alert('Erro ao carregar pedido');
				},
				complete: function() {
					$('#loading').hide();
					$('#tabelas-container').show();
				}
			});
		}		
		

		function preencherSacolinha(itens) {
			const tbody = $('#itens-sacolinha-tbody');
			tbody.empty();

			if (!itens || itens.length === 0) {
				tbody.html('<tr><td colspan="4" class="text-center">Nenhum item na sacolinha</td></tr>');
				return;
			}

			itens.forEach(item => {
				const codigo = item.codigo || 'N/A';
				const nome = item.nome_do_produto || 'N/A';
				const preco = parseFloat(item.price).toFixed(2);
				const data = new Date(item.add_at).toLocaleDateString('pt-BR');

				// ✅ Detalhes em cinza
				let detalhes = [];
				if (item.marca) detalhes.push(`${item.marca}`);
				if (item.estado) detalhes.push(`${item.estado}`);
				if (item.cor) detalhes.push(`${item.cor}`);
				if (item.tamanho) detalhes.push(`Tam: ${item.tamanho}`);

				const detalhesHtml = detalhes.length > 0 
					? `<small style="color: #999; display: block; margin-top: 4px;">${detalhes.join(' • ')}</small>`
					: '';

				tbody.append(`
					<tr class="item-row" 
						data-sacola-id="${item.sacola_id}"
						data-item-id="${item.item_id}">
						style="cursor: pointer;">
						<td>${codigo}</td>
						<td>
							<strong>${nome}</strong>
							${detalhesHtml}
						</td>
						<td>R$ ${preco}</td>
						<td>${data}</td>
					</tr>
				`);
			});
		}

		function preencherPedido(itens, numeroPedido, valorFrete) {
		  const tbody = $('#itens-pedido-tbody');
		  tbody.empty();

		  $('#pedido-numero-info').text(`Pedido: ${numeroPedido}`);

		  if (!itens || itens.length === 0) {
			tbody.html('<tr><td colspan="4" class="text-center py-4">Nenhum item no pedido ainda</td></tr>');
			$('#pedido-total-itens').text('0');
			$('#pedido-valor').text('0,00');
			$('#frete').text(formatMoneyBR(valorFrete || 0));
			$('#pedido-valor-total').text(formatMoneyBR(valorFrete || 0));
			return;
		  }

		  let totalItens = itens.length;
		  let valorItens = 0;

		  itens.forEach(item => {
			const valorItem = parseFloat(item.valor_total) || parseFloat(item.preco) || 0;
			valorItens += valorItem;

			let detalhes = [];
			if (item.marca) detalhes.push(`${item.marca}`);
			if (item.estado) detalhes.push(`${item.estado}`);
			if (item.cor) detalhes.push(`${item.cor}`);
			if (item.tamanho) detalhes.push(`Tam: ${item.tamanho}`);

			const detalhesHtml = detalhes.length > 0
			  ? `<small style="color: #999; display: block; margin-top: 4px;">${detalhes.join(' • ')}</small>`
			  : '';

			const valorFormatado = formatMoneyBR(valorItem);

			tbody.append(`
			  <tr class="item-pedido-row"
				data-item-pedido-id="${item.item_pedido_id}"
				data-item-id="${item.item_id}"
				style="cursor: pointer;">
				<td><strong>${item.codigo}</strong></td>
				<td>
				  <strong>${item.nome_do_produto}</strong>
				  ${detalhesHtml}
				</td>
				<td class="text-end"><strong>R$ ${valorFormatado}</strong></td>
			  </tr>
			`);
		  });

		  const freteNum = parseFloat(valorFrete) || 0;
		  const valorTotal = valorItens + freteNum;

		  $('#pedido-total-itens').text(totalItens);

		  // ✅ nos spans, só número (sem "R$")
		  $('#pedido-valor').text(formatMoneyBR(valorItens));
		  $('#frete').text(formatMoneyBR(freteNum));
		  $('#pedido-valor-total').text(formatMoneyBR(valorTotal));
		}

		function atualizarResumoSacolinha(resumo) {
			console.log('📊 Resumo:', resumo);
			$('#sacolinha-total-itens').text(resumo.total_itens || '0');
			$('#sacolinha-valor-total').text(resumo.valor_total || '0,00');
		}

        // ✅ Clicar em item da sacolinha para mover
		$(document).on('click', '.item-row', function() {
			console.log('🛒 Clicou em item da SACOLINHA');
			console.log('Dados disponíveis:', $(this).data());
			
			const sacolaId = $(this).data('sacola-id');
			
			console.log('🔍 sacolaId:', sacolaId);
			
			if (!sacolaId) {
				console.error('❌ ERRO: sacolaId é null/undefined!');
				alert('Erro: ID da sacolinha inválido');
				return;
			}
			
			if (confirm('Mover este item para o pedido?')) {
				moverParaPedido(sacolaId);
			}
		});

		function moverParaPedido(sacolaId) {
			if (!pedidoAtual || !pedidoAtual.id) {
				alert('❌ Nenhum pedido disponível. Crie um pedido primeiro!');
				return;
			}

			$.ajax({
				url: '/pedidos/mover-para-pedido',
				type: 'POST',
				data: { 
					_token: $('meta[name="csrf-token"]').attr('content'),
					sacola_id: sacolaId,
					pedido_id: pedidoAtual.id,
					user_id: clienteAtual.id
				},
				success: function(response) {
					console.log('✅ Resposta:', response);
					if (response.success) {
						showNotification('✅ Item movido para o pedido!', 'success');
						carregarDados(clienteAtual.id);  // Recarregar dados
					} else {
						showNotification('❌ ' + response.message, 'error');
					}
				},
				error: function(xhr) {
					console.error('❌ Erro:', xhr);
					showNotification('❌ Erro ao mover item', 'error');
				}
			});
		}

		// ✅ Clicar em item do pedido para devolver
		$(document).on('click', '.item-pedido-row', function() {
			console.log('📦 Clicou em item do PEDIDO');
			console.log('Dados disponíveis:', $(this).data());
			
			const itemPedidoId = $(this).data('item-pedido-id');
			
			console.log('🔍 itemPedidoId:', itemPedidoId);
			
			if (!itemPedidoId) {
				console.error('❌ ERRO: itemPedidoId é null/undefined!');
				alert('Erro: ID do item inválido');
				return;
			}
			
			if (confirm('Devolver este item para a sacolinha?')) {
				devolverParaSacolinha(itemPedidoId);
			}
		});


		function devolverParaSacolinha(itemPedidoId) {
			console.log('⬅️ Devolvendo item pedido:', itemPedidoId);
			
			$.post('/pedidos/devolver-para-sacolinha', {
				item_pedido_id: itemPedidoId,
				user_id:        clienteAtual.id
			})
			.done(function(response) {
				console.log('✅ Resposta:', response);
				if (response.success) {
					carregarDados(clienteAtual.id);
				} else {
					alert('Erro: ' + response.message);
				}
			})
			.fail(function(error) {
				console.error('❌ Erro:', error);
				alert('Erro ao devolver item');
			});
		}


		// ✅ Atualizar Status do Pedido
		$('#btn-atualizar-status').on('click', function() {
			const statusSelecionado = $('#status-pedido').val();
			
			if (!statusSelecionado) {
				alert('Por favor, selecione um status');
				return;
			}
			
			if (!pedidoAtual || !pedidoAtual.id) {
				alert('Por favor, selecione um pedido primeiro');
				return;
			}

			// --- VERIFICAÇÃO DE SALDO PARA STATUS 'CONCLUIDO' ---
			let valorTotalPedido = 0; // Inicializa com 0
			if (statusSelecionado === 'concluido') {
				if (!clienteAtual || clienteAtual.saldo_bruto === undefined || clienteAtual.saldo_bruto === null) {
					alert('Não foi possível verificar o saldo do cliente. Por favor, recarregue a página ou selecione o cliente novamente.');
					return;
				}

				const saldoCliente = parseFloat(clienteAtual.saldo_bruto);
				
				const pedidoValorTotalText = $('#pedido-valor-total').text().replace('R$ ', '').replace(',', '.');
				valorTotalPedido = parseFloat(pedidoValorTotalText); // Atribui o valor aqui

				if (isNaN(valorTotalPedido)) {
					alert('Não foi possível obter o valor total do pedido para verificação de saldo.');
					return;
				}

				if (saldoCliente < valorTotalPedido) {
					alert(`❌ Saldo insuficiente! O saldo atual do cliente (${clienteAtual.saldo_formatado}) é menor que o valor total do pedido (R$ ${valorTotalPedido.toFixed(2).replace('.', ',')}).`);
					return; // Impede a atualização do status
				}
			}
			// --- FIM DA VERIFICAÇÃO DE SALDO ---
			
			// Desabilitar botão
			$(this).prop('disabled', true);
			const originalText = $(this).html();
			$(this).html('<i class="fas fa-spinner fa-spin me-1"></i>Atualizando...');
			
			$.ajax({
				url: '/pedidos/atualizar-status',
				type: 'POST',
				data: {
					_token: $('meta[name="csrf-token"]').attr('content'),
					pedido_id: pedidoAtual.id,
					status: statusSelecionado
				},
				success: function(response) {
					if (response.success) {
						showNotification('✅ Status atualizado para: ' + statusSelecionado, 'success');
						$('#status-pedido').val('').prop('disabled', false);
						
						if (statusSelecionado === 'concluido') {
							$.ajax({
								url: '/pedidos/registrar-debito-conclusao',
								type: 'POST',
								data: {
									_token: $('meta[name="csrf-token"]').attr('content'),
									user_id: clienteAtual.id,
									valor: valorTotalPedido,
									pedido_id: pedidoAtual.id,
									pedido_numero: pedidoAtual.numero
								},
								success: function(debitoResponse) {
									if (debitoResponse.success) {
										showNotification('✅ Lançamento de débito do pedido registrado!', 'success');
										
										// --- NOVO: ATUALIZAÇÃO OTIMISTA DO SALDO NO FRONTEND ---
										// Subtrai o valor do pedido do saldo bruto atual
										clienteAtual.saldo_bruto -= valorTotalPedido;
										// Reformatar o saldo para exibição
										clienteAtual.saldo_formatado = 'R$ ' + clienteAtual.saldo_bruto.toFixed(2).replace('.', ',');
										// Atualiza o elemento HTML com o novo saldo
										$('#cliente-saldo').text(clienteAtual.saldo_formatado);
										showNotification('✅ Saldo do cliente atualizado na tela!', 'info');
										// --- FIM NOVO ---

									} else {
										showNotification('❌ Erro ao registrar débito do pedido: ' + debitoResponse.message, 'error');
									}
								},
								error: function(debitoXhr) {
									const debitoErrorMsg = debitoXhr.responseJSON?.message || 'Erro ao registrar débito do pedido';
									showNotification('❌ ' + debitoErrorMsg, 'error');
								},
								complete: function() {
									// Recarrega os dados da sacolinha/pedido.
									// Isso também fará uma busca pelo saldo mais recente do backend,
									// garantindo que o frontend esteja sempre sincronizado.
									carregarDados(clienteAtual.id); 
								}
							});
						} else {
							// Se não for 'concluido', apenas recarrega os dados
							carregarDados(clienteAtual.id); 
						}

					} else {
						showNotification('❌ ' + response.message, 'error');
					}
				},
				error: function(xhr) {
					const errorMsg = xhr.responseJSON?.message || 'Erro ao atualizar status';
					showNotification('❌ ' + errorMsg, 'error');
				},
				complete: function() {
					$('#btn-atualizar-status').html(originalText).prop('disabled', false);
				}
			});

		});

		
		function limparTabelaPedido() {
			$('#itens-pedido-tbody').html(
				'<tr><td colspan="4" class="text-center py-4">Nenhum item no pedido ainda</td></tr>'
			);
			$('#pedido-total-itens').text('0');
			$('#pedido-valor-total').text('0,00');
		}

        // ✅ Limpar seleção
        $('#btn-limpar').click(function() {
            clienteAtual = null;
            pedidoAtual = null;
            $('#cliente-search').val('');
            $('#cliente-info, #card-acoes, #tabelas-container').hide();
            $('#cliente-dropdown').hide();
        });

        // ✅ Fechar dropdown ao clicar fora
        $(document).click(function(e) {
            if (!$(e.target).closest('#cliente-search, #cliente-dropdown').length) {
                $('#cliente-dropdown').hide();
            }
        });
		
        // ==========================================
        // FUNÇÕES DE IMPRESSÃO PDF
        // ==========================================
        
        // Função para exibir notificações
        function showNotification(message, type = 'info') {
            const alertClass = type === 'success' ? 'alert-success' : 
                              type === 'error' ? 'alert-danger' : 'alert-info';
            
            const notification = $(`
                <div class="alert ${alertClass} alert-dismissible fade show notification-toast" role="alert" style="
                    position: fixed; 
                    top: 20px; 
                    right: 20px; 
                    z-index: 1050; 
                    min-width: 300px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                ">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert">&times;</button>
                </div>
            `);
            
            $('body').append(notification);
            
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 5000);
        }

        // Função genérica para impressão de relatório
		function imprimirRelatorio(url, buttonSelector, reportName, extraData = {}) {
			if (!clienteAtual || !clienteAtual.id) {
				showNotification('⚠️ Por favor, selecione um cliente antes de imprimir a ' + reportName + '.', 'error');
				return;
			}

			const $button = $(buttonSelector);
			const originalHtml = $button.html();

			$button.prop('disabled', true).html('⏳ Gerando PDF...');
			showNotification('🔄 Gerando PDF da ' + reportName + '...', 'info');

			const formData = new FormData();
			formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
			formData.append('cliente_id', clienteAtual.id);

			// ✅ adiciona campos extras (ex.: pedido_id)
			Object.keys(extraData).forEach((key) => {
				if (extraData[key] !== undefined && extraData[key] !== null && extraData[key] !== '') {
					formData.append(key, extraData[key]);
				}
			});

			console.log('📤 Enviando requisição PDF:', {
				url: url,
				clienteId: clienteAtual.id,
				extraData: extraData
			});

			$.ajax({
				url: url,
				method: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				xhrFields: { responseType: 'blob' },
				success: function(response, status, xhr) {
					let filename = reportName.toLowerCase() + '_' + clienteAtual.name.replace(/\s+/g, '_') + '_' + new Date().toISOString().slice(0,10) + '.pdf';

					const contentDisposition = xhr.getResponseHeader('Content-Disposition');
					if (contentDisposition) {
						const match = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
						if (match && match[1]) filename = match[1].replace(/['"]/g, '');
					}

					const blob = new Blob([response], { type: 'application/pdf' });
					const link = document.createElement('a');
					link.href = window.URL.createObjectURL(blob);
					link.download = filename;
					document.body.appendChild(link);
					link.click();
					document.body.removeChild(link);
					window.URL.revokeObjectURL(link.href);

					showNotification('✅ PDF da ' + reportName + ' baixado com sucesso!', 'success');
				},
				error: function(xhr, status, error) {
					console.error('❌ Erro ao gerar relatório:', { status: xhr.status, error: error, response: xhr.responseText });

					let errorMessage = 'Erro desconhecido ao gerar o relatório.';
					if (xhr.status === 404) errorMessage = 'Rota de PDF não encontrada. Verifique se as rotas estão configuradas.';
					else if (xhr.status === 500) errorMessage = 'Erro interno do servidor.';
					else if (xhr.responseJSON) errorMessage = xhr.responseJSON.error || xhr.responseJSON.message || errorMessage;

					showNotification('❌ Erro ao gerar ' + reportName + ': ' + errorMessage, 'error');
				},
				complete: function() {
					$button.html(originalHtml);
					$button.prop('disabled', !clienteAtual);
				}
			});
		}


        // Eventos de clique para os botões de impressão
        $('#btn-imprimir-sacolinha').on('click', function() {
            imprimirRelatorio('/pedidos/imprimir-sacolinha', this, 'Sacolinha');
        });

		$('#btn-imprimir-pedido').on('click', function() {
			if (!pedidoAtual || !pedidoAtual.id) {
				showNotification('⚠️ Nenhum pedido selecionado/carregado para imprimir.', 'error');
				return;
			}

			imprimirRelatorio('/pedidos/imprimir-pedido', this, 'Pedido', {
				pedido_id: pedidoAtual.id
			});
		});

        // Evento para limpar seleção (atualizado)
        $('#btn-limpar-selecao').on('click', function() {
            clienteAtual = null;
            pedidoAtual = null;
            $('#cliente-search').val('');
            $('#cliente-info, #tabelas-container').hide();
            $('#cliente-dropdown').hide();
            showNotification('✅ Seleção limpa!', 'success');
        });

        // Habilitar/desabilitar botões baseado na seleção
		function togglePrintButtons() {
			const ativo = Boolean(clienteAtual && clienteAtual.id);
			$('#btn-imprimir-sacolinha, #btn-imprimir-pedido, #btn-criar-pedido')
				.prop('disabled', !ativo);
		}


        // ========================================
        // FUNÇÕES DE DEBUG GLOBAIS
        // ========================================
        
        window.debugCliente = function() {
            console.log('=== DEBUG CLIENTE ===');
            console.log('Cliente atual:', clienteAtual);
            console.log('Pedido atual:', pedidoAtual);
            console.log('Botões sacolinha:', $('#btn-imprimir-sacolinha').length);
            console.log('Botões pedido:', $('#btn-imprimir-pedido').length);
            console.log('CSRF Token:', $('meta[name="csrf-token"]').attr('content') ? 'presente' : 'ausente');
            console.log('====================');
        };

        window.habilitarBotoes = function() {
            $('#btn-imprimir-sacolinha, #btn-imprimir-pedido, #btn-criar-pedido').prop('disabled', false);
            console.log('🔧 Botões habilitados manualmente');
        };

        window.testarPDF = function(tipo) {
            if (!clienteAtual) {
                console.log('⚠️ Definindo cliente teste...');
                clienteAtual = { id: 2, name: 'Cliente Teste', email: 'teste@teste.com' };
            }
            
            if (tipo === 'sacolinha') {
                $('#btn-imprimir-sacolinha').click();
            } else {
                $('#btn-imprimir-pedido').click();
            }
        };

        // Inicialização dos botões (desabilitados inicialmente)
        togglePrintButtons();
        
        console.log('🚀 Sistema de PDF carregado e integrado');		
		
    });
	
	function formatMoneyBR(value) {
	  const n = Number(value) || 0;
	  return n.toFixed(2).replace('.', ',');
	}

	function normalizarStatus(status) {
	  return (status || '').toString().trim().toLowerCase()
		.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
	}
	
	
    </script>
</body>
</html>
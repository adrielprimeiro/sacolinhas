<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Controle de Pedidos</title>

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
                    <h5 class="mb-0"><i class="fas fa-search"></i> Selecionar Cliente</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <label class="form-label">Buscar Cliente</label>
                            <div class="position-relative">
                                <input type="text" id="cliente-search" class="form-control" 
                                       placeholder="Digite nome ou email..." autocomplete="off">
                                <div id="cliente-dropdown" class="list-group cliente-dropdown" style="display: none;"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div id="cliente-info" class="alert alert-success mt-3" style="display: none;">
                                <strong>✓ Cliente:</strong> <span id="cliente-nome"></span><br>
                                <small>ID: <span id="cliente-id"></span></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Botões -->
            <div id="card-acoes" class="card" style="display: none;">
                <div class="card-body">
                    <button class="btn btn-primary me-2" id="btn-criar-pedido" style="display: none;">
                        <i class="fas fa-plus"></i> Criar Pedido
                    </button>
                    <button class="btn btn-secondary" id="btn-limpar">
                        <i class="fas fa-times"></i> Limpar Seleção
                    </button>
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
                                <small>Total Itens:</small>
                                <strong id="sacolinha-total-itens">0</strong>
                                <small>Valor Total:</small>
                                <strong>R$ <span id="sacolinha-valor-total">0,00</span></strong>
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
                                        <th>Qtd</th>
                                        <th>Subtotal</th>
                                        <th>Status</th>
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
                <div id="tabela-pedido-card" class="card" style="display: none;">
                    <div class="card-header bg-light text-blue">
                        <div class="card-header-info">
                            <div>
                                <h5 class="mb-0"><i class="fas fa-file-invoice"></i> Itens no Pedido</h5>
                                <small id="pedido-numero-info">Pedido Pendente</small>
                                <small style="display: block; margin-top: 5px;">Clique no item para devolver para sacolinha</small>
                            </div>
                            <div class="info-resumo">
                                <small>Total Itens:</small>
                                <strong id="pedido-total-itens">0</strong>
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
                                        <th>Status</th>
                                        <th>Data</th>
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
                         data-id="${cliente.id}" data-name="${cliente.name}" data-email="${cliente.email}">
                        <strong>${cliente.name}</strong><br>
                        <small>${cliente.email} - ID: ${cliente.id}</small>
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
            clienteAtual = {
                id: $(this).data('id'),
                name: $(this).data('name'),
                email: $(this).data('email')
            };

            $('#cliente-search').val(clienteAtual.name);
            $('#cliente-dropdown').hide();
            $('#cliente-nome').text(clienteAtual.name);
            $('#cliente-id').text(clienteAtual.id);
            $('#cliente-info').show();
            $('#card-acoes').show();

            carregarDados(clienteAtual.id);
        });

        function carregarDados(userId) {
            $('#loading').show();
            $('#tabelas-container').hide();

            $.ajax({
                url: '/pedidos/itens-sacolinha',
                data: { user_id: userId },
                success: function(response) {
                    if (response.success) {
                        pedidoAtual = {
                            id: response.pedido_id,
                            numero: response.pedido_numero
                        };

                        preencherSacolinha(response.itens_sacolinha);
                        atualizarResumoSacolinha(response.resumo);
                        
                        if (response.resumo.tem_pedido_pendente) {
                            preencherPedido(response.itens_pedido, response.pedido_numero);
                            $('#tabela-pedido-card').show();
                            $('#btn-criar-pedido').hide();
                        } else {
                            $('#tabela-pedido-card').hide();
                            $('#btn-criar-pedido').show();
                        }
                        
                        $('#tabelas-container').show();
                    } else {
                        alert('Erro: ' + response.message);
                    }
                },
                error: function() {
                    alert('Erro ao carregar dados');
                },
                complete: function() {
                    $('#loading').hide();
                }
            });
        }

        function preencherSacolinha(itens) {
            const tbody = $('#itens-sacolinha-tbody');
            tbody.empty();

            if (!itens.length) {
                tbody.html('<tr><td colspan="7" class="text-center py-4">Nenhum item na sacolinha</td></tr>');
                return;
            }

            itens.forEach(item => {
                const data = new Date(item.add_at).toLocaleDateString('pt-BR');
                const subtotal = (item.price * item.quantity).toFixed(2);
                
                tbody.append(`
                    <tr class="item-row" data-sacola-id="${item.sacola_id}" data-item-id="${item.item_id}">
                        <td><strong>${item.codigo}</strong></td>
                        <td>${item.nome_do_produto}</td>
                        <td>R$ ${parseFloat(item.price).toFixed(2).replace('.', ',')}</td>
                        <td class="text-center">${item.quantity}</td>
                        <td><strong>R$ ${subtotal.replace('.', ',')}</strong></td>
                        <td><span class="badge bg-secondary">${item.status}</span></td>
                        <td>${data}</td>
                    </tr>
                `);
            });
        }

        function preencherPedido(itens, numeroPedido) {
            const tbody = $('#itens-pedido-tbody');
            tbody.empty();

            $('#pedido-numero-info').text(`Pedido: ${numeroPedido}`);

            if (!itens.length) {
                tbody.html('<tr><td colspan="5" class="text-center py-4">Nenhum item no pedido ainda</td></tr>');
                $('#pedido-total-itens').text('0');
                $('#pedido-valor-total').text('0,00');
                return;
            }

            let totalItens = 0;
            let valorTotal = 0;
            itens.forEach(item => {
                const data = new Date(item.created_at).toLocaleDateString('pt-BR');
                totalItens++;
                const preco = parseFloat(item.preco);
                valorTotal += preco;
                
                tbody.append(`
                    <tr class="item-pedido-row" data-item-id="${item.item_id}">
                        <td><strong>${item.codigo}</strong></td>
                        <td>${item.nome_do_produto}</td>
                        <td>R$ ${preco.toFixed(2).replace('.', ',')}</td>
                        <td><span class="badge bg-info">${item.status}</span></td>
                        <td>${data}</td>
                    </tr>
                `);
            });

            $('#pedido-total-itens').text(totalItens);
            $('#pedido-valor-total').text(valorTotal.toFixed(2).replace('.', ','));
        }

        function atualizarResumoSacolinha(resumo) {
            $('#sacolinha-total-itens').text(resumo.total_itens);
            $('#sacolinha-valor-total').text(resumo.valor_total);
        }

        // ✅ Clicar em item da sacolinha para mover
        $(document).on('click', '.item-row', function() {
            const sacolaId = $(this).data('sacola-id');
            
            if (!pedidoAtual || !pedidoAtual.numero) {
                alert('Crie um pedido primeiro!');
                return;
            }

            moverParaPedido(sacolaId);
        });

        function moverParaPedido(sacolaId) {
            $.ajax({
                url: '/pedidos/mover-para-pedido',
                type: 'POST',
                data: { 
                    sacola_id: sacolaId,
                    pedido_numero: pedidoAtual.numero,
                    user_id: clienteAtual.id
                },
                success: function(response) {
                    if (response.success) {
                        carregarDados(clienteAtual.id);
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('Erro ao mover item');
                }
            });
        }

        // ✅ Clicar em item do pedido para devolver
        $(document).on('click', '.item-pedido-row', function() {
            const itemId = $(this).data('item-id');
            
            if (confirm('Devolver este item para a sacolinha?')) {
                devolverParaSacolinha(itemId);
            }
        });

        function devolverParaSacolinha(itemId) {
            $.ajax({
                url: '/pedidos/devolver-para-sacolinha',
                type: 'POST',
                data: { 
                    item_id: itemId,
                    user_id: clienteAtual.id
                },
                success: function(response) {
                    if (response.success) {
                        carregarDados(clienteAtual.id);
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('Erro ao devolver item');
                }
            });
        }

        // ✅ Criar novo pedido
        $('#btn-criar-pedido').click(function() {
            $.ajax({
                url: '/pedidos/criar-pedido',
                type: 'POST',
                data: { user_id: clienteAtual.id },
                success: function(response) {
                    if (response.success) {
                        pedidoAtual = {
                            id: response.pedido_id,
                            numero: response.pedido_numero
                        };
                        carregarDados(clienteAtual.id);
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('Erro ao criar pedido');
                }
            });
        });

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
    });
    </script>
</body>
</html>
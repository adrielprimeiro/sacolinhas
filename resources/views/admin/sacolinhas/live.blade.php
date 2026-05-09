<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gerenciar Sacolas - Da Live</title>
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
        /* Estilo para linha de live selecionada */
        .live-row.table-primary {
            background-color: #cfe2ff !important;
            border-left: 5px solid #0d6efd;
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
        /* Estilos para status dos itens */
        .item-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pendente {
            background-color: #e9ecef;
            color: #495057;
        }
        .status-reservado {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-sacolinha {
            background-color: #d4edda;
            color: #155724;
        }
        .status-vendido {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .btn-status-reservado {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
        }
        .btn-status-sacolinha {
            background-color: #28a745;
            border-color: #28a745;
            color: #fff;
        }
        .btn-status-active {
            opacity: 0.7;
            pointer-events: none;
        }
        /* Limite de altura para tabela de lives */
        .lives-table-container {
            max-height: 300px;
            overflow-y: auto;
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
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (Atualizada do Dashboard) -->
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
							   href="{{ route('financeiro.dashboard') }}">
								<i class="fas fa-wallet mr-1"></i>Financeiro
							</a>
						</li>						
						<li class="nav-item">
							<a class="nav-link text-white {{ request()->routeIs('clientes.*') ? 'active' : '' }}" 
							   href="{{ route('clientes.index') }}">
								<i class="fas fa-users"></i> Clientes
							</a>
						</li>
						<li class="nav-item">
							<a class="nav-link text-white {{ request()->routeIs('pedidos.*') ? 'active' : '' }}" 
							   href="{{ route('pedidos.index') }}">
								<i class="fas fa-file-invoice"></i> Pedidos
							</a>
						</li>						
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
								</ul>
							</div>
						</li>
						
						<li class="nav-item">
							<a class="nav-link text-white {{ request()->routeIs('bags.*') ? 'active' : '' }}" 
							   href="{{ route('bags.index') }}">
								<i class="fas fa-broadcast-tower"></i> Live
							</a>
						</li>
						
						<li class="nav-item">
							<a class="nav-link text-white active" 
							   href="#" data-bs-toggle="collapse" data-bs-target="#sacolinhasMenu">
								<i class="fas fa-shopping-bag"></i> Sacolas
								<i class="fas fa-chevron-down float-end mt-1"></i>
							</a>
							<div class="collapse show" id="sacolinhasMenu">
								<ul class="nav flex-column ms-3">
									<li class="nav-item">
										<a class="nav-link text-white active" 
										   href="{{ route('admin.sacolinhas.index') }}">
											<i class="fas fa-broadcast-tower"></i> Da Live
										</a>
									</li>
									<li class="nav-item">
										<a class="nav-link text-white" 
										   href="{{ route('sacolinhas.consultar') }}">
											<i class="fas fa-user"></i> Por Cliente
										</a>
									</li>
								</ul>
							</div>
						</li>
						
						<hr class="text-white-50 my-3">
						
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

            <!-- Main Content (Restaurado do Backup) -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                    <h2>Gerenciar Sacolinhas - Da Live</h2>
                </div>

                <div id="alert-container">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                </div>

                <!-- Card de Busca e Lista de Lives -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-search"></i> Buscar e Filtrar Lives</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="search-client" class="form-label">Filtrar por Cliente (nas sacolas)</label>
                                <input type="text" class="form-control" id="search-client" placeholder="Nome ou email do cliente...">
                            </div>
                            <div class="col-md-6">
                                <label for="search-live" class="form-label">Filtrar por Live (tipo, plataforma, ID)</label>
                                <input type="text" class="form-control" id="search-live" placeholder="Tipo de live, plataforma, ID...">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0"><i class="fas fa-broadcast-tower"></i> Últimas 5 Lives</h6>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary me-2" onclick="carregarTodasAsLives()"><i class="fas fa-sync-alt"></i> Recarregar</button>
                                        <button class="btn btn-sm btn-outline-info" onclick="mostrarTodasAsLives()"><i class="fas fa-list"></i> Ver Todas (<span id="total-lives-count">0</span>)</button>
                                    </div>
                                </div>
                                <div class="lives-table-container">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Tipo</th>
                                                    <th>Plataformas</th>
                                                    <th>Status</th>
                                                    <th>Criada em</th>
                                                </tr>
                                            </thead>
                                            <tbody id="lives-table-body">
                                                <tr><td colspan="5" class="text-center py-3">Carregando lives...</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sacolas da Live Selecionada -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-shopping-bag"></i> Sacolas da Live Selecionada <span id="selected-live-info" class="text-muted ms-2"></span></h6>
                    </div>
                    <div class="card-body">
                        <div id="selected-live-bags-display">
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-hand-pointer fa-3x mb-3 opacity-50"></i>
                                <h5>Selecione uma live para ver suas sacolas</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Remoção (Restaurado) -->
    <div class="modal fade" id="modalConfirmarRemocao" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Remover Item</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <p class="mb-1 text-muted text-uppercase fw-bold small">Você está removendo:</p>
                    <h4 id="remove_item_name" class="fw-bold text-dark mb-3"></h4>
                    <div class="bg-light p-3 rounded-3 mb-4">
                        <p class="mb-0 text-muted">Valor do item: <strong id="remove_item_price" class="text-danger"></strong></p>
                    </div>
                    <p class="text-secondary mb-0">Como deseja prosseguir com a pontuação?</p>
                </div>
                <div class="modal-footer border-0 p-3 bg-light d-flex flex-column gap-2">
                    <button type="button" class="btn btn-danger w-100 py-2 fw-bold" id="btnConfirmarComDesconto">Retirar descontando pontos</button>
                    <button type="button" class="btn btn-outline-secondary w-100 py-2 fw-bold" id="btnConfirmarSemDesconto">Retirar SEM desconto</button>
                    <button type="button" class="btn btn-link btn-sm text-muted text-decoration-none" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let allLives = [];
        let selectedLiveId = null;
        let showingAllLives = false;
        let itemParaRemover = null;

        document.addEventListener('DOMContentLoaded', carregarTodasAsLives);

        async function carregarTodasAsLives() {
            const body = document.getElementById('lives-table-body');
            body.innerHTML = '<tr><td colspan="5" class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>';
            
            try {
                const res = await fetch('/api/lives/all');
                const data = await res.json();
                if (data.success) {
                    allLives = data.data || data.lives || [];
                    document.getElementById('total-lives-count').textContent = allLives.length;
                    renderLivesTable(allLives);
                }
            } catch (e) {
                body.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Erro ao carregar lives</td></tr>';
            }
        }

        function renderLivesTable(lives) {
            const body = document.getElementById('lives-table-body');
            const display = showingAllLives ? lives : lives.slice(0, 5);
            
            body.innerHTML = display.map(live => {
                const status = live.ativo ? 'ativa' : 'encerrada';
                const statusClass = status === 'ativa' ? 'live-ativa' : 'live-encerrada';
                const date = new Date(live.created_at).toLocaleString('pt-BR');
                const isSelected = selectedLiveId == live.id ? 'table-primary' : '';
                
                return `
                    <tr class="live-row ${isSelected}" data-live-id="${live.id}" onclick="selectLive(${live.id})" style="cursor:pointer">
                        <td>${live.id}</td>
                        <td>${(live.tipo_live || '').toUpperCase()}</td>
                        <td>${live.plataformas || 'N/A'}</td>
                        <td><span class="live-status ${statusClass}">${status.toUpperCase()}</span></td>
                        <td>${date}</td>
                    </tr>
                `;
            }).join('');
        }

        function mostrarTodasAsLives() {
            showingAllLives = !showingAllLives;
            renderLivesTable(allLives);
        }

        function selectLive(id) {
            selectedLiveId = id;
            document.querySelectorAll('.live-row').forEach(r => r.classList.remove('table-primary'));
            const row = document.querySelector(`.live-row[data-live-id="${id}"]`);
            if (row) row.classList.add('table-primary');
            document.getElementById('selected-live-info').textContent = `(ID: ${id})`;
            carregarSacolas(id);
        }

        async function carregarSacolas(liveId) {
            const container = document.getElementById('selected-live-bags-display');
            container.innerHTML = '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

            try {
                const res = await fetch(`/api/sacolinhas/live/${liveId}`);
                const data = await res.json();
                if (data.success) {
                    exibirSacolas(data.data, container, liveId);
                } else {
                    container.innerHTML = `<div class="text-center py-5 text-danger">${data.message}</div>`;
                }
            } catch (e) {
                container.innerHTML = '<div class="text-center py-5 text-danger">Erro ao carregar sacolas</div>';
            }
        }

        function exibirSacolas(bags, container, liveId) {
            if (!bags || bags.length === 0) {
                container.innerHTML = '<div class="text-center py-3 text-muted">Nenhuma sacola nesta live.</div>';
                return;
            }

            container.innerHTML = bags.map(bag => `
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <img src="${bag.client.avatar_url}" class="rounded-circle me-2" width="32">
                            <div><strong>${bag.client.name}</strong> <small class="text-muted">(ID: ${bag.client.id})</small></div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary">Itens: ${bag.total_items}</span>
                            <div class="fw-bold text-success">${bag.formatted_total}</div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr><th>Item</th><th>Status</th><th>Preço</th><th width="150">Ações</th></tr>
                            </thead>
                            <tbody>
                                ${bag.items.map(item => `
                                    <tr>
                                        <td>${item.item_name}</td>
                                        <td><span class="item-status status-${(item.status || 'pendente').toLowerCase()}">${(item.status || 'pendente').toUpperCase()}</span></td>
                                        <td class="fw-bold text-success">${item.formatted_total_price}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-status-reservado ${item.status === 'reservado' ? 'btn-status-active' : ''}" onclick="alterarStatus(${item.item_id}, 'reservado', this)" ${item.status === 'reservado' ? 'disabled' : ''}>R</button>
                                                <button class="btn btn-status-sacolinha ${item.status === 'sacolinha' ? 'btn-status-active' : ''}" onclick="alterarStatus(${item.item_id}, 'sacolinha', this)" ${item.status === 'sacolinha' ? 'disabled' : ''}>S</button>
                                                <button class="btn btn-outline-danger" onclick="abrirModalRemocao(${item.item_id}, ${bag.client.id}, '${item.item_name}', '${item.formatted_total_price}')"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `).join('');
        }

        async function alterarStatus(itemId, status, btn) {
            btn.disabled = true;
            try {
                const res = await fetch(`/api/items/${itemId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({status})
                });
                const data = await res.json();
                if (data.success) carregarSacolas(selectedLiveId);
            } catch (e) { alert('Erro ao alterar status'); }
            btn.disabled = false;
        }

        function abrirModalRemocao(itemId, userId, name, price) {
            itemParaRemover = { itemId, userId };
            document.getElementById('remove_item_name').textContent = name;
            document.getElementById('remove_item_price').textContent = price;
            new bootstrap.Modal(document.getElementById('modalConfirmarRemocao')).show();
        }

        document.getElementById('btnConfirmarComDesconto').onclick = () => executarRemocao(true);
        document.getElementById('btnConfirmarSemDesconto').onclick = () => executarRemocao(false);

        async function executarRemocao(descontar) {
            const { itemId, userId } = itemParaRemover;
            try {
                const res = await fetch('/api/sacolinhas/remove', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ item_id: itemId, user_id: userId, live_id: selectedLiveId, descontar_pontos: descontar })
                });
                const data = await res.json();
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalConfirmarRemocao')).hide();
                    carregarSacolas(selectedLiveId);
                }
            } catch (e) { alert('Erro ao remover item'); }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gerenciar Sacolas</title>
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
            background-color: #cfe2ff !important; /* Cor de destaque do Bootstrap */
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
        /* Estilos para os botões de status */
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
        .btn-status-reservado:hover {
            background-color: #e0a800;
            border-color: #d39e00;
            color: #000;
        }
        .btn-status-sacolinha:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: #fff;
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
        /* Estilo para botão ativo */
        .btn-status-active {
            opacity: 0.7;
            pointer-events: none;
        }
        /* Limite de altura para tabela de lives */
        .lives-table-container {
            max-height: 300px;
            overflow-y: auto;
        }
		
		.status-solicitado-na-live {
			background-color: #cfe2ff;
			color: #084298;
		}		
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar text-white p-0">
                <div class="p-3">
                    <h4>Admin</h4>
                    <hr>
                      <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('items.index') }}">
                                <i class="fas fa-box"></i> Itens
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('bags.index') }}"> <!-- Novo item no menu -->
                                <i class="fas fa-broadcast-tower"></i> Live
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('admin.sacolinhas.index') }}"> <!-- Atualizado para a nova rota -->
                                <i class="fas fa-shopping-bag"></i> Sacolas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('dashboard') }}">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                    <!-- Título -->
                    <h2>Gerenciar Sacolinhas</h2>
                </div>

                <!-- Alerts (mantido para mensagens genéricas) -->
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
                        <h6 class="mb-0">
                            <i class="fas fa-search"></i>
                            Buscar e Filtrar Lives
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Filtros de Busca -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="search-client" class="form-label">Filtrar por Cliente (nas sacolas da live selecionada)</label>
                                <input type="text" class="form-control" id="search-client" placeholder="Nome ou email do cliente...">
                            </div>
                            <div class="col-md-6">
                                <label for="search-live" class="form-label">Filtrar por Live (tipo, plataforma, ID)</label>
                                <input type="text" class="form-control" id="search-live" placeholder="Tipo de live, plataforma, ID...">
                            </div>
                        </div>

                        <!-- Tabela de Lives (LIMITADA A 5) -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">
                                        <i class="fas fa-broadcast-tower"></i>
                                        Últimas 5 Lives
                                    </h6>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary me-2" onclick="carregarTodasAsLives()" title="Recarregar Lives">
                                            <i class="fas fa-sync-alt"></i> Recarregar
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" onclick="mostrarTodasAsLives()" title="Ver Todas as Lives">
                                            <i class="fas fa-list"></i> Ver Todas (<span id="total-lives-count">0</span>)
                                        </button>
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
                                                <!-- Lives serão carregadas aqui via JavaScript -->
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-3">
                                                        <i class="fas fa-spinner fa-spin me-2"></i> Carregando lives...
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card das Sacolas da Live Selecionada -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-shopping-bag"></i>
                            Sacolas da Live Selecionada
                            <span id="selected-live-info" class="text-muted ms-2"></span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="selected-live-bags-display">
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-hand-pointer fa-3x mb-3 opacity-50"></i>
                                <h5>Selecione uma live para ver suas sacolas</h5>
                                <p>Clique em uma live na tabela acima para exibir os detalhes das sacolas.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Remoção com Pontuação -->
    <div class="modal fade" id="modalConfirmarRemocao" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header bg-danger text-white border-0" style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-exclamation-triangle me-2"></i> Remover Item
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <p class="mb-1 text-muted text-uppercase fw-bold small">Você está removendo:</p>
                    <h4 id="remove_item_name" class="fw-bold text-dark mb-3"></h4>
                    <div class="bg-light p-3 rounded-3 mb-4">
                        <p class="mb-0 text-muted">Valor do item: <strong id="remove_item_price" class="text-danger"></strong></p>
                    </div>
                    <p class="text-secondary mb-0">Como deseja prosseguir com a pontuação do cliente?</p>
                </div>
                <div class="modal-footer border-0 p-3 bg-light d-flex flex-column gap-2" style="border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                    <button type="button" class="btn btn-danger w-100 py-2 fw-bold" id="btnConfirmarComDesconto">
                        <i class="fas fa-minus-circle me-1"></i> Retirar descontando pontos
                    </button>
                    <button type="button" class="btn btn-outline-secondary w-100 py-2 fw-bold" id="btnConfirmarSemDesconto">
                        <i class="fas fa-check me-1"></i> Retirar SEM desconto nos pontos
                    </button>
                    <button type="button" class="btn btn-link btn-sm text-muted text-decoration-none mt-1" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript das Lives e Sacolinhas -->
    <script>
        let allLives = [];    // Todas as lives carregadas para filtragem
        let selectedLiveId = null; // ID da live selecionada na tabela para visualização de sacolas
        let showingAllLives = false; // Controle para mostrar todas ou apenas 5 lives
        let itemStatusCache = {}; // Cache para armazenar status dos itens localmente

        document.addEventListener('DOMContentLoaded', function() {
            carregarTodasAsLives(); // Carrega todas as lives na tabela ao iniciar

            // Event listeners para os campos de busca de lives
            document.getElementById('search-client').addEventListener('input', filterLivesTable);
            document.getElementById('search-live').addEventListener('input', filterLivesTable);
        });

        // Função para carregar todas as lives e popular a tabela (MODIFICADA)
        async function carregarTodasAsLives() {
            const livesTableBody = document.getElementById('lives-table-body');
            livesTableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                        <i class="fas fa-spinner fa-spin me-2"></i> Carregando lives...
                    </td>
                </tr>
            `;
            
            try {
                console.log('Iniciando carregamento das lives...');
                
                // Vamos tentar diferentes endpoints possíveis
                const possibleEndpoints = [
                    '/api/lives/all',
                    '/lives',
                    '/admin/api/lives'
                ];
                
                let response = null;
                let usedEndpoint = null;
                
                // Tenta cada endpoint até encontrar um que funcione
                for (const endpoint of possibleEndpoints) {
                    try {
                        console.log(`Tentando endpoint: ${endpoint}`);
                        const tempRes = await fetch(endpoint, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        
                        if (tempRes.ok && tempRes.headers.get('content-type')?.includes('application/json')) {
                            response = tempRes;
                            usedEndpoint = endpoint;
                            console.log(`Sucesso com endpoint: ${endpoint}`);
                            break;
                        }
                    } catch (e) { continue; }
                }
                
                if (!response || !response.ok) {
                    throw new Error(`Nenhum endpoint funcionou. Status: ${response ? response.status : 'N/A'}`);
                }
                
                const data = await response.json();
                console.log('Dados recebidos:', data);
                
                // Tenta diferentes estruturas de resposta
                let livesData = [];
                
                if (data.success && data.data) {
                    livesData = data.data;
                } else if (data.data) {
                    livesData = data.data;
                } else if (Array.isArray(data)) {
                    livesData = data;
                } else if (data.lives) {
                    livesData = data.lives;
                } else {
                    console.error('Estrutura de resposta não reconhecida:', data);
                    throw new Error('Estrutura de dados não reconhecida');
                }
                
                console.log('Lives processadas:', livesData);
                
                if (Array.isArray(livesData) && livesData.length > 0) {
                    allLives = livesData; // Armazena todas as lives para filtragem
                    
                    // Atualiza o contador total
                    document.getElementById('total-lives-count').textContent = livesData.length;
                    
                    // Renderiza apenas as primeiras 5 por padrão
                    showingAllLives = false;
                    renderLivesTable(livesData);
                    mostrarAlert(`${livesData.length} lives carregadas com sucesso (endpoint: ${usedEndpoint})`, 'success');
                } else {
                    console.log('Nenhuma live encontrada');
                    document.getElementById('total-lives-count').textContent = '0';
                    livesTableBody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">
                                <i class="fas fa-info-circle me-2"></i> Nenhuma live encontrada
                            </td>
                        </tr>
                    `;
                }
                
            } catch (error) {
                console.error('Erro completo ao carregar lives:', error);
                livesTableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-danger py-3">
                            <i class="fas fa-exclamation-triangle me-2"></i> 
                            Erro ao carregar lives: ${error.message}
                            <br><small class="mt-2">Verifique o console do navegador para mais detalhes.</small>
                        </td>
                    </tr>
                `;
                mostrarAlert(`Erro ao carregar lives: ${error.message}`, 'danger');
            }
        }

        // Nova função para alternar entre mostrar 5 ou todas as lives
        function mostrarTodasAsLives() {
            showingAllLives = !showingAllLives;
            const button = document.querySelector('button[onclick="mostrarTodasAsLives()"]');
            
            if (showingAllLives) {
                button.innerHTML = '<i class="fas fa-compress"></i> Mostrar Menos';
                button.title = 'Mostrar apenas 5 lives';
            } else {
                button.innerHTML = `<i class="fas fa-list"></i> Ver Todas (<span id="total-lives-count">${allLives.length}</span>)`;
                button.title = 'Ver todas as lives';
            }
            
            renderLivesTable(allLives);
        }

        // Função para renderizar a tabela de lives (MODIFICADA para limitar a 5)
        function renderLivesTable(livesToRender) {
            const livesTableBody = document.getElementById('lives-table-body');
            let html = '';
            
            console.log('Renderizando lives:', livesToRender);
            
            if (!livesToRender || livesToRender.length === 0) {
                html = `
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">
                            <i class="fas fa-search me-2"></i> Nenhuma live encontrada com os critérios de busca.
                        </td>
                    </tr>
                `;
            } else {
                // LIMITAÇÃO: Mostra apenas 5 lives por padrão, ou todas se showingAllLives for true
                const livesDisplay = showingAllLives ? livesToRender : livesToRender.slice(0, 5);
                
                livesDisplay.forEach(live => {
                    console.log('Processando live:', live);
                    
                    // Mapeamento flexível dos campos
                    const liveId = live.id || live.live_id || 'N/A';
                    const liveType = live.tipo_live || live.type || live.tipo || 'N/A';
                    const liveStatus = live.status || live.ativo === 1 ? 'ativa' : 'encerrada'; // CORRIGIDO: usa campo 'ativo'
                    const livePlatforms = live.plataformas || live.platforms || live.platform || 'N/A';
                    const liveCreatedAt = live.created_at || live.data || new Date().toISOString(); // CORRIGIDO: usa campo 'data'
                    
                    const statusClass = liveStatus === 'ativa' ? 'live-ativa' : 'live-encerrada';
                    const formattedPlatforms = livePlatforms !== 'N/A' && livePlatforms 
                        ? livePlatforms.split(',').map(p => p.charAt(0).toUpperCase() + p.slice(1)).join(', ') 
                        : 'N/A';
                    
                    let formattedDate = 'N/A';
                    try {
                        formattedDate = new Date(liveCreatedAt).toLocaleDateString('pt-BR', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    } catch (dateError) {
                        console.warn('Erro ao formatar data:', dateError);
                    }
                    
                    const isSelected = selectedLiveId == liveId ? 'table-primary' : '';
                    
                    html += `
                        <tr class="live-row ${isSelected}" data-live-id="${liveId}" style="cursor: pointer;">
                            <td>${liveId}</td>
                            <td>${liveType.toString().replace('-', ' ').toUpperCase()}</td>
                            <td>${formattedPlatforms}</td>
                            <td><span class="live-status ${statusClass}">${liveStatus.toString().toUpperCase()}</span></td>
                            <td>${formattedDate}</td>
                        </tr>
                    `;
                });
            }
            
            livesTableBody.innerHTML = html;

            // Adiciona event listeners às novas linhas
            livesTableBody.querySelectorAll('.live-row').forEach(row => {
                row.addEventListener('click', function() {
                    const liveId = this.dataset.liveId;
                    selectLive(liveId);
                });
            });
        }

        // Função para filtrar a tabela de lives
        function filterLivesTable() {
            const searchClient = document.getElementById('search-client').value.toLowerCase();
            const searchLive = document.getElementById('search-live').value.toLowerCase();

            const filteredLives = allLives.filter(live => {
                const liveType = (live.tipo_live || live.type || '').toLowerCase();
                const livePlatforms = (live.plataformas || live.platforms || '').toLowerCase();
                const liveId = String(live.id || live.live_id || '');
                
                const liveTypeMatch = liveType.includes(searchLive);
                const platformsMatch = livePlatforms.includes(searchLive);
                const liveIdMatch = liveId.includes(searchLive);

                return (liveTypeMatch || platformsMatch || liveIdMatch);
            });
            
            renderLivesTable(filteredLives);

            // Se houver uma live selecionada e o filtro de cliente for alterado, recarrega as sacolas
            if (selectedLiveId && searchClient) {
                carregarSacolas(selectedLiveId);
            }
        }

        // Função para lidar com a seleção de uma live na tabela (MODIFICADA)
        function selectLive(liveId) {
            selectedLiveId = liveId;
            console.log('Live selecionada:', liveId);

            // Remove destaque da linha previamente selecionada
            document.querySelectorAll('.live-row.table-primary').forEach(row => {
                row.classList.remove('table-primary');
            });

            // Adiciona destaque à linha recém-selecionada
            const selectedRow = document.querySelector(`.live-row[data-live-id="${liveId}"]`);
            if (selectedRow) {
                selectedRow.classList.add('table-primary');
                
                // Atualiza informações da live selecionada
                const liveInfo = document.getElementById('selected-live-info');
                liveInfo.textContent = `(Live ID: ${liveId})`;
            }

            // Carrega as sacolas para a live selecionada no painel de visualização
            carregarSacolas(liveId);
        }

        // Função para carregar sacolas
        function carregarSacolas(liveId) {
            const container = document.getElementById('selected-live-bags-display');
            container.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="fas fa-spinner fa-spin fa-3x mb-3 opacity-50"></i>
                    <h5>Carregando sacolas para a Live ID: ${liveId}...</h5>
                </div>
            `;

            fetch(`/api/sacolinhas/live/${liveId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Dados das sacolas recebidos:', data); // DEBUG: mostra estrutura completa
                    
                    if (data.success) {
                        // Filtra as sacolas por cliente se o campo de busca de cliente estiver preenchido
                        let bagsToDisplay = data.data;
                        const searchClient = document.getElementById('search-client').value.toLowerCase();
                        if (searchClient) {
                            bagsToDisplay = bagsToDisplay.filter(bag =>
                                bag.client.name.toLowerCase().includes(searchClient) ||
                                bag.client.email.toLowerCase().includes(searchClient) ||
                                (bag.client.phone && bag.client.phone.includes(searchClient))
                            );
                        }
                        exibirSacolas(bagsToDisplay, container, liveId);
                    } else {
                        console.error('Erro ao carregar sacolas:', data.message);
                        container.innerHTML = `
                            <div class="text-center text-danger py-5">
                                <i class="fas fa-exclamation-triangle fa-3x mb-3 opacity-50"></i>
                                <h5>Erro ao carregar sacolas</h5>
                                <p>${data.message}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    container.innerHTML = `
                        <div class="text-center text-danger py-5">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3 opacity-50"></i>
                            <h5>Erro de rede ao carregar sacolas</h5>
                            <p>Verifique sua conexão ou tente novamente.</p>
                        </div>
                    `;
                });
        }

		
	// Função para exibir sacolas (CORRIGIDA - sem requisições extras)
	function exibirSacolas(bags, targetContainer, currentLiveId) {
		if (bags.length === 0) {
			targetContainer.innerHTML = `
				<div class="text-center text-muted py-3">
					<i class="fas fa-shopping-bag fa-2x mb-2 opacity-50"></i>
					<h6>Nenhuma sacola encontrada para esta live ou com o filtro de cliente.</h6>
					<p class="mb-0">Adicione itens ou ajuste o filtro de busca.</p>
				</div>
			`;
			return;
		}

		let html = '';
		bags.forEach(bag => {
			html += `
				<div class="card mb-3">
					<div class="card-header">
						<div class="d-flex align-items-center">
							<img src="${bag.client.avatar_url}" class="rounded-circle me-2" width="32" height="32">
							<div class="flex-grow-1">
								<h6 class="mb-0">${bag.client.name}</h6>
								<small class="text-muted">${bag.client.email} (ID: ${bag.client.id})</small>
							</div>
							
							<div class="text-end d-flex align-items-center gap-2">
							  <button class="btn btn-success btn-sm"
									  onclick="enviarMsg1Cliente(${currentLiveId}, ${bag.client.id}, this)"
									  title="Enviar mensagem no WhatsApp para este cliente">
								  <i class="fab fa-whatsapp"></i>
							  </button>

							  <div class="text-end">
								  <span class="badge bg-primary me-2">Total de Itens: ${bag.total_items}</span>
								  <div class="fw-bold text-success">${bag.formatted_total}</div>
							  </div>
							</div>
						</div>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-sm mb-0">
								<thead class="table-light">
									<tr>
										<th>Item</th>
										<th>Detalhes</th>
										<th>Status Real</th>
										<th>Preço</th>
										<th width="200">Ações</th>
									</tr>
								</thead>
								<tbody>
			`;

			bag.items.forEach(item => {
				console.log('Item completo:', item);
				
				const details = [];
				if (item.item_sku) details.push(`SKU: ${item.item_sku}`);
				if (item.item_brand) details.push(`Marca: ${item.item_brand}`);
				if (item.item_color) details.push(`Cor: ${item.item_color}`);
				if (item.item_size) details.push(`Tam: ${item.item_size}`);

				// ✅ USAR STATUS QUE JÁ VEM DA API - SEM REQUISIÇÕES EXTRAS!
				const currentStatus = item.status || 'pendente';
				const normalizedStatus = currentStatus.toLowerCase().replace(/\s+/g, '-');
				const statusClass = `status-${normalizedStatus}`;
				const statusText = currentStatus.toUpperCase();

				// Verifica se os botões devem estar ativos ou inativos
				const reservadoActive = currentStatus === 'reservado' ? 'btn-status-active' : '';
				const sacolinhaActive = currentStatus === 'sacolinha' ? 'btn-status-active' : '';

				html += `
					<tr>
						<td>
							<strong>${item.item_name}</strong>
						</td>
						<td>
							<small class="text-muted">${details.join(' | ')}</small>
						</td>
						<td>
							<span class="item-status ${statusClass}">${statusText}</span>
						</td>
						<td class="fw-bold text-success">${item.formatted_total_price}</td>
						<td>
							<div class="btn-group-vertical btn-group-sm d-flex gap-1">
								<div class="btn-group btn-group-sm">
									<button class="btn btn-status-reservado btn-sm ${reservadoActive}" 
											onclick="alterarStatusItem(${item.item_id}, ${bag.client.id}, ${currentLiveId}, 'reservado', this)" 
											title="Marcar como Reservado" 
											${currentStatus === 'reservado' ? 'disabled' : ''}>
										<i class="fas fa-clock"></i> Reservado
									</button>
									<button class="btn btn-status-sacolinha btn-sm ${sacolinhaActive}" 
											onclick="alterarStatusItem(${item.item_id}, ${bag.client.id}, ${currentLiveId}, 'sacolinha', this)" 
											title="Marcar como Sacolinha"
											${currentStatus === 'sacolinhas' ? 'disabled' : ''}>
										<i class="fas fa-shopping-bag"></i> Sacolinha
									</button>
								</div>
								<button class="btn btn-outline-danger btn-sm" onclick="removerTodosItens(${item.item_id}, ${bag.client.id}, 1, ${currentLiveId}, '${item.item_name.replace(/'/g, "\\'")}', '${item.formatted_total_price}')" title="Remover item">
									<i class="fas fa-trash"></i> Remover
								</button>
							</div>
						</td>
					</tr>
				`;
			});

			html += `
							</tbody>
						</table>
					</div>
				</div>
			</div>
			`;
		});
		
		targetContainer.innerHTML = html;
	}
		
		
		// Função para alterar status do item (VERSÃO CORRIGIDA - USA ENDPOINT QUE FUNCIONA)
		function alterarStatusItem(itemId, userId, liveId, novoStatus, buttonElement) {
			// Desabilita o botão temporariamente para evitar cliques múltiplos
			const originalButtonContent = buttonElement.innerHTML;
			buttonElement.disabled = true;
			buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Alterando...';

			console.log(`🔄 Alterando status do item ${itemId} para: ${novoStatus}`);

			// USAR NOSSO ENDPOINT QUE FUNCIONA
			fetch(`/api/items/${itemId}/status`, {
				method: 'PATCH',
				headers: {
					'Content-Type': 'application/json',
					'Accept': 'application/json',
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
				},
				body: JSON.stringify({status: novoStatus})
			})
			.then(response => response.json())
			.then(data => {
				// Restaura o botão
				buttonElement.disabled = false;
				buttonElement.innerHTML = originalButtonContent;

				if (data.success) {
					console.log('✅ Status realmente alterado no banco:', data);
					
					// Armazena no cache local
					const itemKey = `${itemId}-${userId}-${liveId}`;
					itemStatusCache[itemKey] = novoStatus;
					
					// Mostra alerta de sucesso
					mostrarAlert(`✅ Status alterado para "${novoStatus.toUpperCase()}" com sucesso!`, 'success');
					
					// Recarrega sacolas para mostrar mudança real
					carregarSacolas(liveId);
					
				} else {
					console.error('❌ Erro na resposta:', data);
					mostrarAlert(`❌ Erro: ${data.message || 'Erro desconhecido'}`, 'danger');
				}
			})
			.catch(error => {
				console.error('❌ Erro na requisição:', error);
				
				// Restaura o botão
				buttonElement.disabled = false;
				buttonElement.innerHTML = originalButtonContent;
				
				mostrarAlert(`❌ Erro de conexão: ${error.message}`, 'danger');
			});
		}



        // Variáveis globais para controle da remoção
        let itemParaRemover = null;

        function removerUmItem(itemId, userId, liveIdToRefresh) {
            console.warn("removerUmItem foi chamado, mas o botão foi removido. Usando removerItens.");
            // Esta função parece não ser mais usada diretamente pelos botões
        }

        function removerTodosItens(itemId, userId, quantity, liveIdToRefresh, itemName, itemPrice) {
            itemParaRemover = { itemId, userId, liveIdToRefresh };
            
            document.getElementById('remove_item_name').innerText = itemName || 'Item';
            document.getElementById('remove_item_price').innerText = itemPrice || 'R$ 0,00';
            
            const modal = new bootstrap.Modal(document.getElementById('modalConfirmarRemocao'));
            modal.show();
        }

        // Event listeners para os botões do modal de remoção
        document.getElementById('btnConfirmarComDesconto').addEventListener('click', function() {
            executarRemocao(true);
        });

        document.getElementById('btnConfirmarSemDesconto').addEventListener('click', function() {
            executarRemocao(false);
        });

        function executarRemocao(descontarPontos) {
            if (!itemParaRemover) return;
            
            const { itemId, userId, liveIdToRefresh } = itemParaRemover;
            
            const data = {
                item_id: itemId,
                user_id: userId,
                live_id: liveIdToRefresh,
                descontar_pontos: descontarPontos
            };
            
            const modalElement = document.getElementById('modalConfirmarRemocao');
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) modal.hide();
            
            fetch('/api/sacolinhas/remove', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarAlert(data.message, 'success');
                    carregarSacolas(liveIdToRefresh);
                } else {
                    mostrarAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarAlert('Erro ao remover item', 'danger');
            })
            .finally(() => {
                itemParaRemover = null;
            });
        }

        // Função para mostrar alertas
        function mostrarAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'exclamation-triangle'}"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            alertContainer.appendChild(alert);

            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }
		
		// FUNÇÃO PARA BUSCAR STATUS REAIS DOS ITENS
	async function buscarStatusReais(items) {
		console.log('🔍 Buscando status reais de', items.length, 'itens...');
		
		// Extrair apenas os IDs únicos dos itens
		const itemIds = [...new Set(items.map(item => item.item_id))];
		
		try {
			// Buscar status de todos os itens de uma vez
			const promises = itemIds.map(itemId => 
				fetch(`/api/items/${itemId}/status`, {
					method: 'GET', // GET para consultar
					headers: {
						'Accept': 'application/json',
						'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
					}
				})
				.then(response => response.json())
				.then(data => ({itemId, status: data.status || 'pendente'}))
				.catch(error => {
					console.warn(`Erro ao buscar status do item ${itemId}:`, error);
					return {itemId, status: 'pendente'};
				})
			);
			
			const statusResults = await Promise.all(promises);
			
			// Criar um mapa de ID → Status
			const statusMap = {};
			statusResults.forEach(result => {
				statusMap[result.itemId] = result.status;
			});
			
			console.log('✅ Status reais obtidos:', statusMap);
			return statusMap;
			
		} catch (error) {
			console.error('❌ Erro ao buscar status reais:', error);
			return {};
		}
	}


	async function enviarMsg1Cliente(liveId, userId, btn) {
	  if (!confirm('Enviar msg1 (template) para este cliente?')) return;

	  const original = btn.innerHTML;
	  btn.disabled = true;
	  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

	  try {
		const res = await fetch(`/lives/${liveId}/sacolas/${userId}/whatsapp/first`, {
		  method: 'POST',
		  headers: {
			'Accept': 'application/json',
			'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
			'X-Requested-With': 'XMLHttpRequest'
		  }
		});

		const data = await res.json();

		if (data.success) {
			  mostrarAlert(data.message || 'Enfileirado com sucesso!', 'success');
			} else if (data.already_sent) {
			  mostrarAlert(data.message || 'Já enviado anteriormente.', 'warning');
			} else {
			  mostrarAlert(data.message || 'Erro ao enviar msg1.', 'danger');
			}
	  } catch (e) {
		mostrarAlert(`Erro: ${e.message}`, 'danger');
	  } finally {
		btn.disabled = false;
		btn.innerHTML = original;
	  }
	}
    </script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
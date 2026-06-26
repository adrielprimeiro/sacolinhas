<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gerenciar Sacolinhas</title>

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
		#total-sacolas {
			font-family: 'Courier New', monospace;
			text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
		}

		#contador-sacolas {
			opacity: 0.8;
		}

		.card-header .text-end {
			min-width: 150px;
		}

		/* Animação suave quando o total aparece/desaparece */
		#total-sacolas, #contador-sacolas {
			transition: opacity 0.3s ease;
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
                            <a class="nav-link text-white {{ request()->routeIs('clientes.*') ? 'active' : '' }}" 
                               href="{{ route('clientes.index') }}">
                                <i class="fas fa-users"></i> Clientes
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link text-white {{ request()->routeIs('items.*') ? 'active' : '' }}" 
                               href="{{ route('items.index') }}">
                                <i class="fas fa-box"></i> Itens
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link text-white {{ request()->routeIs('bags.*') ? 'active' : '' }}" 
                               href="{{ route('bags.index') }}">
                                <i class="fas fa-broadcast-tower"></i> Live
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link text-white {{ request()->routeIs('admin.sacolinhas.*') ? 'active' : '' }}" 
                               href="{{ route('admin.sacolinhas.index') }}">
                                <i class="fas fa-shopping-bag"></i> Sacolas
                            </a>
                        </li>
                        
                        <!-- SEPARADOR -->
                        <hr class="text-white-50 my-3">
                        
                        <!-- SEÇÃO DE RELATÓRIOS (OPCIONAL) -->
                        <li class="nav-item">
                            <a class="nav-link text-white-50 small" href="#" data-bs-toggle="collapse" data-bs-target="#relatoriosMenu">
                                <i class="fas fa-chart-bar"></i> Relatórios
                                <i class="fas fa-chevron-down float-end mt-1"></i>
                            </a>
                            <div class="collapse" id="relatoriosMenu">
                                <ul class="nav flex-column ms-3">
                                    <li class="nav-item">
                                        <a class="nav-link text-white-75 small" 
                                           href="">
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
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                    <!-- Título -->
                    <h2>Gerenciar Sacolinhas</h2>
                </div>

                <!-- Alerts -->
                <div id="alert-container">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                </div>

                <!-- NOVO CARD: Buscar Sacolinha por Cliente -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-search"></i> Buscar Sacolinha por Cliente
                        </h6>
                    </div>
                    <div class="card-body">
                        @include('components.user-search', [
                            'name' => 'filter_client_id',
                            'placeholder' => 'Buscar cliente por nome, email ou telefone para filtrar sacolinhas...',
                            'value' => null, // Começa sem cliente selecionado
                            'id_prefix' => 'filter-' // Prefixo para IDs internos do componente
                        ])
                    </div>
                </div>

                <!-- Adicionar Item à Sacola -->
                <div class="card mb-4 card-disabled" id="add-item-to-bag-card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-shopping-bag"></i>
                            Adicionar Item à Sacola do Cliente
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('sacolinhas.store') }}" id="add-item-form">
                            @csrf
                            <div class="row">
								<!-- Campo Item - NOVO COMPONENTE -->
								<div class="col-md-6 mb-3">
									<div class="d-flex justify-content-between align-items-center mb-2">
										<label class="form-label mb-0">
											<i class="fas fa-box"></i>
											Selecionar Item
										</label>
										
										<!-- 🎯 BOTÃO NOVO ITEM -->
										<a href="{{ route('items.create') }}" 
										   class="btn btn-sm btn-outline-primary" 
										   target="_blank"
										   title="Cadastrar novo item">
											<i class="fas fa-plus"></i> Novo Item
										</a>
									</div>
									
									@include('components.item-search', [
										'name' => 'item_id',
										'priceField' => 'item_price',
										'placeholder' => 'Buscar item por nome, SKU ou descrição...',
										'value' => old('item_id'),
										'priceValue' => old('item_price')
									])
								</div>
								<!-- Busca de Cliente -->
								<div class="col-md-6 mb-3">
									<div class="d-flex justify-content-between align-items-center mb-2">
										<label class="form-label mb-0">
											<i class="fas fa-user"></i>
											Selecionar Cliente
										</label>
										
										<!-- BOTÃO NOVO CLIENTE (se tiver rota) -->
										<a href="{{ route('clientes.create') }}" 
										   class="btn btn-sm btn-outline-primary" 
										   target="_blank"
										   title="Cadastrar novo cliente">
											<i class="fas fa-user-plus"></i> Novo Cliente
										</a>
									</div>
									
									@include('components.user-search', [
										'name' => 'client_id',
										'placeholder' => 'Buscar cliente por nome, email ou telefone...',
										'value' => old('client_id')
									])
								</div>
								
                            </div>
                            <div class="row">
                                <!-- Preço -->
                                <div class="col-md-6 mb-3"> <!-- Era col-md-4 -->
                                    <label for="item-price" class="form-label">
                                        <i class="fas fa-dollar-sign"></i>
                                        Preço
                                        <span id="original-price-display" class="text-muted ms-2" style="text-decoration: line-through; display: none;"></span>
                                    </label>
									<input type="text" 
										   class="form-control" 
										   name="item_price" 
										   id="item-price" 
										   placeholder="0,00" 
										   pattern="[0-9]+([,\.][0-9]{1,2})?" 
										   title="Use formato: 25,50 ou 25.50"
										   value="{{ old('item_price') }}" 
										   required>
                                </div>
                                <!-- Botão -->
                                <div class="col-md-6 mb-3 d-flex align-items-end"> <!-- Era col-md-4 -->
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-plus"></i> Adicionar à Sacola
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Lista de Sacolinhas -->
				<div class="card">
					<div class="card-header">
						<div class="d-flex justify-content-between align-items-center">
							<h6 class="mb-0">
								<i class="fas fa-shopping-bag"></i>
								Sacolinhas da Live Atual
							</h6>
							<!-- NOVO: Total das sacolas -->
							<div class="text-end">
								<div id="total-sacolas" class="fw-bold text-success fs-5" style="display: none;">
									Total: R$ 0,00
								</div>
								<small class="text-muted" id="contador-sacolas" style="display: none;">
									0 sacola(s)
								</small>
							</div>
						</div>
					</div>
                    <div class="card-body">
                        <div id="bags-list">
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-shopping-bag fa-3x mb-3 opacity-50"></i>
                                <h5>Nenhuma sacola criada ainda</h5>
                                <p>Selecione um cliente para ver suas sacolas ou adicione um item.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JavaScript das Lives e Sacolinhas -->
    <script>
        let liveAtiva = @json($liveAtiva ?? null); // Recebe a live ativa do controller
        const DISCOUNT_PERCENTAGE = 0.5; // 50% de desconto para live do 'precinho'

        let itemSearchWrapper = null;
        let selectedItem = null;
        let itemHighlightedIndex = -1;

        // NOVO: Variáveis para o filtro de cliente e todas as sacolas
        let selectedClientForFilter = null; // Cliente selecionado no card de busca de sacolinhas
        let allBagsForLive = []; // Todas as sacolas da live ativa, para filtragem em JS

        document.addEventListener('DOMContentLoaded', function() {
            itemSearchWrapper = document.querySelector('[data-item-search="true"]');
            
            // Inicializa o estado do card "Adicionar Item à Sacola"
            updateAddItemCardState();

            // Event listener para seleção de cliente no card de ADICIONAR ITEM
            const addItemClientSearchComponent = document.querySelector('[data-user-search="true"]:not(#filter-client-search)');
            if (addItemClientSearchComponent) {
                addItemClientSearchComponent.addEventListener('userSelected', function(e) {
                    const user = e.detail.user;
                    console.log('Cliente selecionado (Adicionar Item):', user);
                    mostrarAlert(`Cliente selecionado para adicionar item: ${user.name}`, 'info');
                    
                    const itemInput = document.querySelector('[data-item-search="true"] [data-search-input="true"]');
                    if (itemInput) {
                        itemInput.focus();
                    }
                });
                addItemClientSearchComponent.addEventListener('userCleared', function(e) {
                    console.log('Seleção de cliente limpa (Adicionar Item)');
                });
            }

            // NOVO: Event listener para o card "Buscar Sacolinha por Cliente"
            const filterClientSearchComponent = document.getElementById('filter-client-search');
            if (filterClientSearchComponent) {
                filterClientSearchComponent.addEventListener('userSelected', function(e) {
                    selectedClientForFilter = e.detail.user;
                    console.log('Cliente selecionado (Filtrar Sacolinhas):', selectedClientForFilter);
                    mostrarAlert(`Filtrando sacolinhas para: ${selectedClientForFilter.name}`, 'info');
                    updateAddItemCardState(); // Ativa/desativa o card de adicionar
                    displayFilteredSacolas(selectedClientForFilter.id); // Filtra e exibe
                });
                filterClientSearchComponent.addEventListener('userCleared', function(e) {
                    selectedClientForFilter = null;
                    console.log('Seleção de cliente limpa (Filtrar Sacolinhas)');
                    mostrarAlert('Exibindo todas as sacolinhas da live.', 'info');
                    updateAddItemCardState(); // Ativa/desativa o card de adicionar
                    displayFilteredSacolas(null); // Exibe todas as sacolas
                });
            }

            // Event listener para seleção de item
            const itemSearchComponent = document.querySelector('[data-item-search="true"]');
            if (itemSearchComponent) {
                itemSearchComponent.addEventListener('itemSelected', function(e) {
                    const item = e.detail.item;
                    console.log('📦 Item selecionado (via event listener):', item);
                    console.log('DEBUG: liveAtiva no momento da seleção do item:', liveAtiva);
                    
                    mostrarAlert(`Item selecionado: ${item.name} - ${item.formatted_price}`, 'info');
                    
                    const itemPriceInput = document.getElementById('item-price');
                    const originalPriceDisplay = document.getElementById('original-price-display');

                    if (itemPriceInput) {
                        const isPrecinhoLive = liveAtiva && liveAtiva.tipo_live === 'precinho';
                        console.log('DEBUG: isPrecinhoLive (true/false):', isPrecinhoLive);
                        
                        if (isPrecinhoLive) {
                            const originalPrice = parseFloat(item.price);
                            console.log('DEBUG: Preço Original (item.price):', originalPrice);
                            const discountedPrice = originalPrice * DISCOUNT_PERCENTAGE;
                            console.log('DEBUG: Preço com Desconto:', discountedPrice);
                            
                            itemPriceInput.value = discountedPrice.toFixed(2);
                            originalPriceDisplay.textContent = `R$ ${originalPrice.toFixed(2).replace('.', ',')}`;
                            originalPriceDisplay.style.display = 'inline';
                        } else {
                            console.log('DEBUG: Não é live "precinho" ou liveAtiva não está definida. Usando preço original.');
                            itemPriceInput.value = parseFloat(item.price).toFixed(2);
                            originalPriceDisplay.style.display = 'none';
                        }
                    }
					setTimeout(() => {
						const clientInput = document.querySelector('[data-user-search="true"]:not(#filter-client-search) [data-search-input="true"]');
						if (clientInput) {
							clientInput.focus();
							console.log('🎯 Foco movido para o campo de cliente');
						}
					}, 100);
				});
                itemSearchComponent.addEventListener('itemCleared', function(e) {
                    console.log('Seleção de item limpa');
                    document.getElementById('item-price').value = '';
                    document.getElementById('original-price-display').style.display = 'none';
                });
            }
			
			// Validação em tempo real do campo de preço
			const itemPriceInput = document.getElementById('item-price');
			if (itemPriceInput) {
				itemPriceInput.addEventListener('keypress', function(e) {
					const char = String.fromCharCode(e.which);
					if (!/[0-9,.]/.test(char)) {
						e.preventDefault();
					}
				});
				
				itemPriceInput.addEventListener('input', function(e) {
					const value = e.target.value.replace(',', '.');
					const numValue = parseFloat(value);
					if (isNaN(numValue) || numValue<= 0){
						e.target.setCustomValidity('Informe um preço válido (ex: 25,50)');
					} else {
						e.target.setCustomValidity('');
					}
				});
			}

            // Event listener para o formulário de adicionar item
            document.getElementById('add-item-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const clientId = document.querySelector('input[name="client_id"]').value;
                const itemId = document.querySelector('input[name="item_id"]').value;
				const itemPrice = document.getElementById('item-price').value;
                const itemPriceConverted = itemPrice.replace(',', '.');

                if (!clientId) {
                    mostrarAlert('Por favor, selecione um cliente primeiro!', 'warning');
                    return false;
                }

                if (!itemId) {
                    mostrarAlert('Por favor, selecione um item primeiro!', 'warning');
                    return false;
                }
				
                if (!itemPriceConverted || parseFloat(itemPriceConverted) <= 0) {
					mostrarAlert('Por favor, informe um preço válido!', 'warning');
					return false;
				}		
				
                if (!liveAtiva) {
                    mostrarAlert('Nenhuma live ativa. Não é possível adicionar itens.', 'warning');
                    return false;
                }
                const formData = new FormData(this);
                formData.append('item_quantity', 1); 
				formData.set('item_price', itemPriceConverted);
                formData.append('live_id', liveAtiva.id); // Garante que a live_id seja enviada
                
                const button = this.querySelector('button[type="submit"]');
                const originalText = button.innerHTML;
                
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adicionando...';
                try {
                    const response = await fetch('/sacolinhas', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: formData
                    });
                    const data = await response.json();
						if (data.success) {
							mostrarAlert(data.message, 'success');
							
							console.log('🧹 === LIMPEZA PADRONIZADA (DOM DIRETO) ===');
							
							// Limpar campo de preço
							const itemPriceInput = document.getElementById('item-price');
							if (itemPriceInput) {
								itemPriceInput.value = '';
							}
							document.getElementById('original-price-display').style.display = 'none';
							
							// LIMPAR CLIENTE - DOM DIRETO (do formulário de adicionar)
							console.log('🧹 Limpando cliente via DOM direto (sincrono)...');
							const clientSearchInput = document.querySelector('[data-user-search="true"]:not(#filter-client-search) [data-search-input="true"]');
							const clientHiddenInput = document.querySelector('[data-user-search="true"]:not(#filter-client-search) [data-hidden-input="true"]');
							const clientDisplayCard = document.querySelector('[data-user-search="true"]:not(#filter-client-search) [data-selected-display="true"]');
							const clientDropdown = document.querySelector('[data-user-search="true"]:not(#filter-client-search) [data-suggestions="true"]');
							const clientClearBtn = document.querySelector('[data-user-search="true"]:not(#filter-client-search) [data-clear-btn="true"]');
							
							if (clientSearchInput) {
								clientSearchInput.value = '';
							}
							if (clientHiddenInput) {
								clientHiddenInput.value = '';
							}
							if (clientDisplayCard) {
								clientDisplayCard.classList.add('d-none');
							}
							if (clientDropdown) {
								clientDropdown.style.display = 'none';
							}
							if (clientClearBtn) {
								clientClearBtn.classList.add('d-none');
							}
							
							// LIMPAR ITEM - DOM DIRETO
							console.log('🧹 Limpando item via DOM direto...');
							const itemSearchInput = document.querySelector('[data-item-search="true"] [data-search-input="true"]');
							const itemHiddenInput = document.querySelector('[data-item-search="true"] [data-selected-id="true"]');
							const itemDisplayCard = document.querySelector('[data-item-search="true"] [data-selected-display="true"]');
							const itemResultsContainer = document.querySelector('[data-item-search="true"] [data-results-container="true"]');
							
							if (itemSearchInput) {
								itemSearchInput.value = '';
							}
							if (itemHiddenInput) {
								itemHiddenInput.value = '';
							}
							if (itemDisplayCard) {
								itemDisplayCard.classList.add('d-none');
							}
							if (itemResultsContainer) {
								itemResultsContainer.style.display = 'none';
							}
							
							if (typeof selectedItem !== 'undefined') {
								selectedItem = null;
							}
							
							// FOCAR NO ITEM PARA PRÓXIMA ADIÇÃO
							setTimeout(function() {
								const itemSearchInput = document.querySelector('[data-item-search="true"] [data-search-input="true"]');
								if (itemSearchInput) {
									console.log('🎯 Focando no ITEM para próxima adição...');
									itemSearchInput.focus();
								}
							}, 200);
							
							carregarSacolas(); // Recarrega todas as sacolas e as filtra
							console.log('🧹 === LIMPEZA FINALIZADA ===');
						}             
					} catch (error) {
                    console.error('Erro:', error);
                    mostrarAlert('Erro ao adicionar item à sacola', 'danger');
                } finally {
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            });

            // Carrega as sacolas inicialmente
            carregarSacolas();
        });

        // NOVO: Função para atualizar o estado do card "Adicionar Item à Sacola"
        function updateAddItemCardState() {
            const addItemCard = document.getElementById('add-item-to-bag-card');
            if (selectedClientForFilter) {
                addItemCard.classList.remove('card-disabled');
            } else {
                addItemCard.classList.add('card-disabled');
            }
        }

        // Função para carregar sacolas (agora carrega todas e armazena)
        async function carregarSacolas() {
            if (!liveAtiva) {
                allBagsForLive = [];
                displayFilteredSacolas(null); // Exibe estado vazio
                return;
            }
            try {
                const response = await fetch(`/api/sacolinhas/live/${liveAtiva.id}`);
                const data = await response.json();
                if (data.success) {
                    allBagsForLive = data.data; // Armazena todas as sacolas
                    displayFilteredSacolas(selectedClientForFilter ? selectedClientForFilter.id : null);
                } else {
                    console.error('Erro ao carregar sacolas:', data.message);
                    allBagsForLive = [];
                    displayFilteredSacolas(null);
                }
            } catch (error) {
                console.error('Erro:', error);
                allBagsForLive = [];
                displayFilteredSacolas(null);
            }
        }
		
        // NOVO: Função para exibir sacolas filtradas
        function displayFilteredSacolas(clientId) {
			const container = document.getElementById('bags-list');
			const totalSacolas = document.getElementById('total-sacolas');
			const contadorSacolas = document.getElementById('contador-sacolas');
            
            let bagsToDisplay = allBagsForLive;

            if (clientId) {
                bagsToDisplay = allBagsForLive.filter(bag => bag.client.id === clientId);
            }

			if (bagsToDisplay.length === 0) {
				container.innerHTML = `
					<div class="text-center text-muted py-3">
						<i class="fas fa-shopping-bag fa-2x mb-2 opacity-50"></i>
						<h6>Nenhuma sacola ainda</h6>
						<p class="mb-0">${clientId ? 'Este cliente não possui sacolas.' : 'Adicione itens às sacolas dos clientes.'}</p>
					</div>
				`;
				
				totalSacolas.style.display = 'none';
				contadorSacolas.style.display = 'none';
				return;
			}
			
			let totalGeral = 0;
			let totalItens = 0;
			
			bagsToDisplay.forEach(bag => {
				const valorNumerico = parseFloat(
					bag.formatted_total
						.replace('R$', '')
						.replace(/\s/g, '')
						.replace(',', '.')
				);
				
				if (!isNaN(valorNumerico)) {
					totalGeral += valorNumerico;
				}
				
				totalItens += bag.items.length;
			});
			
			totalSacolas.textContent = `Total: R$ ${totalGeral.toFixed(2).replace('.', ',')}`;
			totalSacolas.style.display = 'block';
			
			contadorSacolas.textContent = `${bagsToDisplay.length} sacola(s) • ${totalItens} item(s)`;
			contadorSacolas.style.display = 'block';
			
			let html = '';
			bagsToDisplay.forEach(bag => {
				html += `
					<div class="card mb-3">
						<div class="card-header">
							<div class="d-flex align-items-center">
								<img src="${bag.client.avatar_url}" class="rounded-circle me-2" width="32" height="32">
								<div class="flex-grow-1">
									<h6 class="mb-0">${bag.client.name}</h6>
									<small class="text-muted">${bag.client.email} (ID: ${bag.client.id})</small>
								</div>
								<div class="text-end">
									<span class="badge bg-primary">${bag.items.length} item(s)</span>
									<div class="fw-bold text-success">${bag.formatted_total}</div>
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
											<th>Preço</th>
											<th width="80">Ações</th>
										</tr>
									</thead>
									<tbody>
				`;
				
				bag.items.forEach(item => {
					const details = [];
					if (item.item_sku) details.push(`Código: ${item.item_sku}`);
					if (item.item_brand) details.push(`Marca: ${item.item_brand}`);
					if (item.item_color) details.push(`Cor: ${item.item_color}`);
					if (item.item_size) details.push(`Tam: ${item.item_size}`);
					
					html += `
						<tr>
							<td>
								<strong>${item.item_name}</strong>
							</td>
							<td>
								<small class="text-muted">${details.length > 0 ? details.join(' | ') : 'Sem detalhes adicionais'}</small>
							</td>
							<td class="fw-bold text-success">${item.formatted_total_price}</td>
							<td>
								<button class="btn btn-sm btn-outline-danger" onclick="removerItem(${item.item_id}, ${bag.client.id}, '${item.item_name.replace(/'/g, "\\'").replace(/"/g, '&quot;')}', '${item.formatted_total_price}')" title="Remover item">
									<i class="fas fa-trash"></i>
								</button>
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
			container.innerHTML = html;
		}

        // Variáveis globais para controle da remoção
        let itemParaRemover = null;

        function removerItem(itemId, userId, itemName, itemPrice) {
            itemParaRemover = { itemId, userId, liveId: liveAtiva.id };
            
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
            
            const { itemId, userId, liveId } = itemParaRemover;
            
            const data = {
                item_id: itemId,
                user_id: userId,
                live_id: liveId,
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
                    carregarSacolas(); // Recarregar lista
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

        // Função para mostrar alertas (sem alterações)
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

    function selectItem(item) {
        console.log('📦 Item selecionado(selectItem):', item);
		console.log('DEBUG: liveAtiva no momento da seleção do item:', liveAtiva); 
        
        // Atualizar campos hidden
        const itemIdInput = itemSearchWrapper.querySelector('[data-selected-id]');
        const itemDisplayCard = itemSearchWrapper.querySelector('[data-selected-display]');
        
        itemIdInput.value = item.id;
        
        // Mostrar card de item selecionado
        itemDisplayCard.innerHTML = `
            <div class="card border-success">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${item.name}</h6>
                            <small class="text-muted">
                                SKU: ${item.sku || 'N/A'} | 
                                Preço: ${item.formatted_price}
                            </small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-clear-btn="true">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        itemDisplayCard.classList.remove('d-none');
        console.log('DEBUG: Card de exibição do item (data-selected-display) agora tem classes:', itemDisplayCard.classList);
        
        // Limpar campo de busca
        const searchInput = itemSearchWrapper.querySelector('[data-search-input]');
        searchInput.value = '';
        
        // Esconder dropdown
        const resultsContainer = itemSearchWrapper.querySelector('[data-results-container]');
        resultsContainer.style.display = 'none';
        
        // ===== CORRIGIR AQUI: Preencher o campo de preço =====
        const priceInput = document.getElementById('item-price');
        const originalPriceDisplay = document.getElementById('original-price-display');
        
        if (priceInput) {
            // Verificar se é live do tipo "precinho" para aplicar desconto
            const isPrecinhoLive = liveAtiva && liveAtiva.tipo_live === 'precinho';
			console.log('DEBUG: isPrecinhoLive (true/false):', isPrecinhoLive); 
            
            if (isPrecinhoLive) {
                // Live precinho: mostrar preço com desconto e preço original riscado
                const originalPrice = parseFloat(item.price);
				console.log('DEBUG: Preço Original (item.price):', originalPrice);
                const discountedPrice = originalPrice * DISCOUNT_PERCENTAGE; // 50% de desconto
				console.log('DEBUG: Preço com Desconto:', discountedPrice);
                
                priceInput.value = discountedPrice.toFixed(2);
                originalPriceDisplay.textContent = item.formatted_price;
                originalPriceDisplay.style.display = 'inline';
            } else {
                // Outras lives: preço normal, sem valor riscado
				console.log('DEBUG: Não é live "precinho" ou liveAtiva não está definida. Usando preço original.'); 
                priceInput.value = parseFloat(item.price).toFixed(2);
                originalPriceDisplay.style.display = 'none';
            }
        }
        
        // Configurar botão de limpar
        const clearBtn = itemDisplayCard.querySelector('[data-clear-btn]');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => clearSelection('item'));
        }
        
        // Salvar item selecionado globalmente
        selectedItem = item;
        console.log('Item selecionado:', selectedItem);
        
        // Disparar evento personalizado
        document.dispatchEvent(new CustomEvent('itemSelected', { detail: item }));
    }
function clearSelection(type) {
        console.log(`DEBUG: clearSelection (${type.charAt(0).toUpperCase() + type.slice(1)}) chamada.`);
        
        if (type === 'user') {
            // Limpar seleção de usuário (do formulário de adicionar)
            const userSearchWrapper = document.querySelector('[data-user-search="true"]:not(#filter-client-search)');
            const userDisplayCard = userSearchWrapper.querySelector('[data-selected-display="true"]');
            const userSearchInput = userSearchWrapper.querySelector('[data-search-input="true"]');
            const userSelectedId = userSearchWrapper.querySelector('[data-hidden-input="true"]');
            const userClearBtn = userSearchWrapper.querySelector('[data-clear-btn="true"]');
            const userResultsContainer = userSearchWrapper.querySelector('[data-suggestions="true"]');
            
            userDisplayCard.classList.add('d-none');
            userSearchInput.value = '';
            userSelectedId.value = '';
            if (userClearBtn) {
                userClearBtn.style.display = 'none';
            }
            userResultsContainer.style.display = 'none';
            
            // selectedUser = null; // Não temos selectedUser global aqui
            document.dispatchEvent(new CustomEvent('userCleared'));
            
        } else if (type === 'filter-user') { // NOVO: Limpar seleção do filtro
            const filterUserSearchWrapper = document.getElementById('filter-client-search');
            const filterUserDisplayCard = filterUserSearchWrapper.querySelector('[data-selected-display="true"]');
            const filterUserSearchInput = filterUserSearchWrapper.querySelector('[data-search-input="true"]');
            const filterUserSelectedId = filterUserSearchWrapper.querySelector('[data-hidden-input="true"]');
            const filterUserClearBtn = filterUserSearchWrapper.querySelector('[data-clear-btn="true"]');
            const filterUserResultsContainer = filterUserSearchWrapper.querySelector('[data-suggestions="true"]');

            filterUserDisplayCard.classList.add('d-none');
            filterUserSearchInput.value = '';
            filterUserSelectedId.value = '';
            if (filterUserClearBtn) {
                filterUserClearBtn.style.display = 'none';
            }
            filterUserResultsContainer.style.display = 'none';

            selectedClientForFilter = null; // Limpa o cliente global do filtro
            updateAddItemCardState(); // Atualiza o estado do card de adicionar
            displayFilteredSacolas(null); // Exibe todas as sacolas
            document.dispatchEvent(new CustomEvent('userCleared')); // Dispara evento para o componente
            
        } else if (type === 'item') {
            // Limpar seleção de item
            const itemDisplayCard = itemSearchWrapper.querySelector('[data-selected-display="true"]');
            const itemSearchInput = itemSearchWrapper.querySelector('[data-search-input="true"]');
            const itemSelectedId = itemSearchWrapper.querySelector('[data-selected-id="true"]');
            const itemClearBtn = itemSearchWrapper.querySelector('[data-clear-btn="true"]');
            const itemResultsContainer = itemSearchWrapper.querySelector('[data-results-container="true"]');
            
            itemDisplayCard.classList.add('d-none');
            itemSearchInput.value = '';
            itemSelectedId.value = '';
            if (itemClearBtn) {
                itemClearBtn.style.display = 'none';
            }
            itemResultsContainer.style.display = 'none';
            
            const priceInput = document.getElementById('item-price');
            const originalPriceDisplay = document.getElementById('original-price-display');
            
            if (priceInput) {
                priceInput.value = '';
            }
            
            if (originalPriceDisplay) {
                originalPriceDisplay.style.display = 'none';
                originalPriceDisplay.textContent = '';
            }
            
            selectedItem = null;
            document.dispatchEvent(new CustomEvent('itemCleared'));
        }
    }
    </script>
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
</body>
</html>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Live</title>
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
			
		/* Destaque visual quando o botão tem foco */

		#add-to-bag-btn:focus {
			outline: 3px solid #0d6efd !important;
			outline-offset: 2px;
			box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
		}

		/* Opcional: Adicione animação para ficar mais evidente */
		#add-to-bag-btn:focus {
			animation: pulse-focus 0.5s ease-in-out;
		}

		@keyframes pulse-focus {
			0% {
				box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.7);
			}
			70% {
				box-shadow: 0 0 0 10px rgba(13, 110, 253, 0);
			}
			100% {
				box-shadow: 0 0 0 0 rgba(13, 110, 253, 0);
			}
		}		
		
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
                    <h2>Gerenciar Live</h2>

                    <!-- Campos de Seleção -->
                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <!-- Combo Box -->
                        <div>
                            <label for="live-type" class="form-label">Tipo de Live</label>
                            <select id="live-type" name="live_type" class="form-select">
                                <option value="loja-aberta">Live Loja Aberta</option>
                                <option value="leilao">Live Leilão</option>
                                <option value="precinho">Live do Precinho</option>
                            </select>
                        </div>
                        <!-- Checkboxes -->
                        <div>
                            <label class="form-label">Plataformas</label>
                            <div class="form-check">
                                <input class="form-check-input platform-checkbox" type="checkbox" id="instagram" name="platforms[]" value="instagram">
                                <label class="form-check-label" for="instagram">Instagram</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input platform-checkbox" type="checkbox" id="tiktok" name="platforms[]" value="tiktok">
                                <label class="form-check-label" for="tiktok">Tiktok</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input platform-checkbox" type="checkbox" id="youtube" name="platforms[]" value="youtube">
                                <label class="form-check-label" for="youtube">YouTube</label>
                            </div>
                        </div>
                    </div>
                    <!-- Botão -->
                    <button type="button" id="toggle-live" class="btn btn-primary" onclick="handleToggleLiveClick()">
                        <i class="fas fa-plus"></i> Nova Live
                    </button>
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

                <!-- Lives Ativas -->
                <div id="live-status-display" class="mb-4">
                    <!-- O conteúdo será carregado dinamicamente pelo JavaScript -->
                </div>
                <!-- SEÇÃO COM BUSCA DE USUÁRIO -->
                <!-- Adicionar Item à Sacola -->
                <div class="card mb-4 card-disabled" id="filter-card">
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
										<a href="{{ route('admin.items.create') }}" 
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
										<a href="{{ route('admin.clientes.create') }}" 
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
									<div class="col-md-3 mb-3">
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
									
									<!-- ✨ NOVO: Observações na mesma linha -->
									<div class="col-md-5 mb-3">
										<label for="obs" class="form-label">
											<i class="fas fa-sticky-note"></i>
											Observação
										</label>
										<textarea 
											class="form-control" 
											name="obs" 
											id="obs" 
											rows="1"
											placeholder="Obs (opcional)"
											maxlength="200"
											style="resize: none; overflow: hidden;"></textarea>
									</div>
									
									<!-- Botão -->
									<div class="col-md-4 mb-3 d-flex align-items-end">
										<button type="submit" class="btn btn-primary w-100" id="add-to-bag-btn">
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
                                <p>Inicie uma live e adicione itens às sacolas dos clientes.</p>
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
        // Vacina global anti-lixo para limpar JSONs corrompidos pelo servidor
        function parseJsonSafely(response) {
            return response.text().then(text => {
                let idxSuccess = text.lastIndexOf('{"success":');
                let idxError = text.lastIndexOf('{"error":');
                let startIdx = Math.max(idxSuccess, idxError);
                
                if (startIdx === -1) {
                    // Se não tem success nem error, tenta o primeiro { ou [
                    let idxObj = text.lastIndexOf('{');
                    let idxArr = text.lastIndexOf('[');
                    startIdx = Math.max(idxObj, idxArr);
                }
                
                if (startIdx > 0) text = text.substring(startIdx);
                try {
                    return JSON.parse(text);
                } catch(e) {
                    console.error("Falha no parse do JSON:", text);
                    throw e;
                }
            });
        }

        let liveAtiva = null;
        const DISCOUNT_PERCENTAGE = 0.5; // 50% de desconto para live do 'precinho'


		let itemSearchWrapper = null;
		let selectedItem = null;
		let itemHighlightedIndex = -1;

		// 🔧 INICIALIZAR WRAPPER:
		itemSearchWrapper = document.querySelector('[data-item-search="true"]');
        carregarLiveStatus(); // Renomeado para refletir o novo propósito
        
        // Event listener para seleção de usuário
			const userSearchComponent = document.querySelector('[data-user-search="true"]');
			if (userSearchComponent) {
				userSearchComponent.addEventListener('userSelected', function(e) {
					const user = e.detail.user;
					console.log('Cliente selecionado:', user);
					mostrarAlert(`Cliente selecionado: ${user.name}`, 'info');
					
					// 🎯 Foco automático para botão Adicionar à Sacola
					setTimeout(() => {
						const addButton = document.getElementById('add-to-bag-btn');
						if (addButton) {
							addButton.focus();
							console.log('✅ Foco movido para botão Adicionar à Sacola');
						}
					}, 300);
				})
			}
            // Event listener para seleção de item
            const itemSearchComponent = document.querySelector('[data-item-search="true"]');
            if (itemSearchComponent) {
                itemSearchComponent.addEventListener('itemSelected', function(e) {
                    const item = e.detail.item;
                    console.log('📦 Item selecionado (via event listener):', item); // Mantendo seu log
                    console.log('DEBUG: liveAtiva no momento da seleção do item:', liveAtiva); // ADICIONADO
                    
                    mostrarAlert(`Item selecionado: ${item.name} - ${item.formatted_price}`, 'info');
                    
                    const itemPriceInput = document.getElementById('item-price');
                    const originalPriceDisplay = document.getElementById('original-price-display');

                    if (itemPriceInput) {
                        const isPrecinhoLive = liveAtiva && liveAtiva.tipo_live === 'precinho';
                        console.log('DEBUG: isPrecinhoLive (true/false):', isPrecinhoLive); // ADICIONADO
                        
                        if (isPrecinhoLive) {
                            const originalPrice = parseFloat(item.price); // Usar item.price conforme seus logs
                            console.log('DEBUG: Preço Original (item.price):', originalPrice); // ADICIONADO
                            const discountedPrice = originalPrice * DISCOUNT_PERCENTAGE;
                            console.log('DEBUG: Preço com Desconto:', discountedPrice); // ADICIONADO
                            
                            itemPriceInput.value = discountedPrice.toFixed(2);
                            originalPriceDisplay.textContent = `R$ ${originalPrice.toFixed(2).replace('.', ',')}`; // Formato com vírgula
                            originalPriceDisplay.style.display = 'inline';
                        } else {
                            console.log('DEBUG: Não é live "precinho" ou liveAtiva não está definida. Usando preço original.'); // ADICIONADO
                            itemPriceInput.value = parseFloat(item.price).toFixed(2); // Usar item.price
                            originalPriceDisplay.style.display = 'none';
                        }
                    }
					setTimeout(() => {
						console.log('⏳ Tentando mover foco para cliente...');
						
						// ✅ SELETOR CORRETO
						const clientInput = document.querySelector('[data-user-search="true"] .user-search-input');
						console.log('🔍 Element encontrado:', clientInput);
						
						if (clientInput) {
							clientInput.focus();
							console.log('✅ Foco movido para o campo de cliente');
						} else {
							console.log('❌ Campo de cliente NÃO encontrado!');
						}
					}, 100);
				});
                itemSearchComponent.addEventListener('itemCleared', function(e) {
                    console.log('Seleção de item limpa');
                    document.getElementById('item-price').value = '';
                    document.getElementById('original-price-display').style.display = 'none';
                });
			
				// Validação em tempo real do campo de preço
				const itemPriceInput = document.getElementById('item-price');
				if (itemPriceInput) {
					// Permitir apenas números, vírgula e ponto
					itemPriceInput.addEventListener('keypress', function(e) {
						const char = String.fromCharCode(e.which);
						if (!/[0-9,.]/.test(char)) {
							e.preventDefault();
						}
					});
					
					// Converter vírgula para ponto na validação
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
            }
			
			
            // Event listener para o formulário
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
				
                if (!itemPriceConverted || parseFloat(itemPriceConverted) <= 0) { // ADICIONADO: Validar preço
					mostrarAlert('Por favor, informe um preço válido!', 'warning');
					return false;
				}		
				
                if (!liveAtiva) {
                    mostrarAlert('Inicie uma live antes de adicionar itens!', 'warning');
                    return false;
                }
                const formData = new FormData(this);
                // Como a quantidade foi removida do frontend, definimos explicitamente como 1
                formData.append('item_quantity', 1); 
				// ADICIONADO: Garantir que o preço atual seja enviado
                formData.set('item_price', itemPriceConverted);
                
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
							
							// 🎯 LIMPAR CLIENTE - SELETORES CORRETOS
							console.log('🧹 Limpando cliente via DOM direto (sincrono)...');
							const clientSearchInput = document.querySelector('[data-user-search="true"] .user-search-input');
							const clientHiddenInput = document.querySelector('[data-user-search="true"] .user-selected-id');
							const clientDisplayCard = document.querySelector('[data-user-search="true"] .user-selected-display');
							const clientDropdown = document.querySelector('[data-user-search="true"] .user-suggestions-dropdown');
							const clientClearBtn = document.querySelector('[data-user-search="true"] .user-clear-btn');
							
							if (clientSearchInput) {
								clientSearchInput.value = '';
								console.log('✅ Campo de busca de cliente limpo');
							}
							if (clientHiddenInput) {
								clientHiddenInput.value = '';
								console.log('✅ Campo hidden de cliente limpo');
							}
							if (clientDisplayCard) {
								clientDisplayCard.style.display = 'none';
								console.log('✅ Card de cliente escondido');
							}
							if (clientDropdown) {
								clientDropdown.style.display = 'none';
								console.log('✅ Dropdown de cliente escondido');
							}
							if (clientClearBtn) {
								clientClearBtn.style.display = 'none';
								console.log('✅ Botão clear de cliente escondido');
							}
							
							// 🎯 LIMPAR ITEM - DOM DIRETO (MANTÉM IGUAL)
							console.log('🧹 Limpando item via DOM direto...');
							const itemSearchInput = document.querySelector('[data-item-search="true"] [data-search-input="true"]');
							const itemHiddenInput = document.querySelector('[data-item-search="true"] [data-selected-id="true"]');
							const itemDisplayCard = document.querySelector('[data-item-search="true"] [data-selected-display="true"]');
							const itemResultsContainer = document.querySelector('[data-item-search="true"] [data-results-container="true"]');
							
							if (itemSearchInput) {
								itemSearchInput.value = '';
								console.log('✅ Campo de busca de item limpo');
							}
							if (itemHiddenInput) {
								itemHiddenInput.value = '';
								console.log('✅ Campo hidden de item limpo');
							}
							if (itemDisplayCard) {
								itemDisplayCard.classList.add('d-none');
								console.log('✅ Card de item escondido');
							}
							if (itemResultsContainer) {
								itemResultsContainer.style.display = 'none';
								console.log('✅ Dropdown de item escondido');
							}
							
							if (typeof selectedItem !== 'undefined') {
								selectedItem = null;
								console.log('✅ selectedItem resetado');
							}
							console.log('🔍 APÓS LIMPEZA - Valores dos campos:');
							console.log('- client_id:', document.querySelector('input[name="client_id"]')?.value);
							console.log('- item_id:', document.querySelector('input[name="item_id"]')?.value);
							console.log('- item_price:', document.getElementById('item-price')?.value);
							
							// 🎯 FOCAR NO ITEM PARA PRÓXIMA ADIÇÃO
							setTimeout(function() {
								const itemSearchInput = document.querySelector('[data-item-search="true"] [data-search-input="true"]');
								if (itemSearchInput) {
									console.log('🎯 Focando no ITEM para próxima adição...');
									itemSearchInput.focus();
								}
							}, 200);
							
							carregarSacolas();
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
        // Função para carregar sacolas (sem alterações significativas aqui)
        function carregarSacolas() {
            if (!liveAtiva) {
                document.getElementById('bags-list').innerHTML = `
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-shopping-bag fa-3x mb-3 opacity-50"></i>
                        <h5>Nenhuma sacola criada ainda</h5>
                        <p>Inicie uma live e adicione itens às sacolas dos clientes.</p>
                    </div>
                `;
                return;
            }
            fetch(`/api/sacolinhas/live/${liveAtiva.id}`)
                .then(parseJsonSafely)
                .then(data => {
                    if (data.success) {
                        exibirSacolas(data.data);						
                    } else {
                        console.error('Erro ao carregar sacolas:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                });
        }


		// Função para exibir sacolas
		function exibirSacolas(bags) {
			const container = document.getElementById('bags-list');
			const totalSacolas = document.getElementById('total-sacolas');
			const contadorSacolas = document.getElementById('contador-sacolas');
			
			if (bags.length === 0) {
				container.innerHTML = `
					<div class="text-center text-muted py-3">
						<i class="fas fa-shopping-bag fa-2x mb-2 opacity-50"></i>
						<h6>Nenhuma sacola ainda</h6>
						<p class="mb-0">Adicione itens às sacolas dos clientes.</p>
					</div>
				`;
				
				// Esconder total quando não há sacolas
				totalSacolas.style.display = 'none';
				contadorSacolas.style.display = 'none';
				return;
			}
			
			// NOVO: Calcular total de todas as sacolas
			let totalGeral = 0;
			let totalItens = 0;
			
			bags.forEach(bag => {
				// Extrair valor numérico do formatted_total
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
			
			// Exibir total formatado
			totalSacolas.textContent = `Total: R$ ${totalGeral.toFixed(2).replace('.', ',')}`;
			totalSacolas.style.display = 'block';
			
			// Exibir contador
			contadorSacolas.textContent = `${bags.length} sacola(s) • ${totalItens} item(s)`;
			contadorSacolas.style.display = 'block';
			
			// Resto da função
			let html = '';
			bags.forEach(bag => {
				// ✨ NOVO: Obter Instagram e TikTok do cliente
				//const instagram = bag.client.instagram || bag.client.remember_token || '';
				//const tiktok = bag.client.tiktok || bag.client.nome_cliente || '';
				const whatsapp = bag.client.whatsapp || 'N/A';
			
				// ✨ NOVO: Criar string com redes sociais em cinza
				let socialInfo = `<span class="text-muted ms-2">${whatsapp}</span>`; 
				/*if (instagram) {
					socialInfo += `<span class="text-muted ms-2">@${instagram}</span>`;
				}
				if (tiktok) {
					socialInfo += `<span class="text-muted ms-2">@${tiktok}</span>`;
				*/
				
				html += `
					<div class="card mb-3">
						<div class="card-header">
							<div class="d-flex align-items-center">
								<img src="${bag.client.avatar_url}" class="rounded-circle me-2" width="32" height="32">
								<div class="flex-grow-1">
									<h6 class="mb-0">${bag.client.name}</h6>
									<small class="text-muted">
										ID: ${bag.client.id} | ${socialInfo} 
									</small>
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
					// ✨ NOVO: Adicionar observações se existirem
					let obsDisplay = '';
					if (item.obs) {
						obsDisplay = `
							<small class="text-muted d-block mb-1">
								<i class="fas fa-sticky-note"></i> <strong>Obs:</strong>
								${item.obs}
							</small>

						`;
					}					
					
					html += `
						<tr>
							<td>
								<strong>${item.item_name}</strong>
							</td>
							<td>
								<small class="text-muted">${details.length > 0 ? details.join(' | ') : 'Sem detalhes adicionais'}</small>
								${obsDisplay}
							</td>
							<td class="fw-bold text-success">${item.formatted_total_price}</td>
							<td>
								<button class="btn btn-sm btn-outline-danger" onclick="removerItem(${item.item_id}, ${bag.client.id}, '${item.item_name.replace(/'/g, "\\'")}', '${item.formatted_total_price}')" title="Remover item">
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

        // Função simplificada para remover item único
        function removerItem(itemId, userId, itemName, itemPrice) {
            if (!liveAtiva) {
                mostrarAlert('Nenhuma live ativa identificada para remover itens.', 'warning');
                return;
            }
            if (confirm(`Deseja realmente remover o item "${itemName}" da sacolinha?`)) {
                itemParaRemover = { itemId, userId, liveId: liveAtiva.id };
                executarRemocao(false); // Sempre sem desconto de pontos
            }
        }

        function closeModals() {
            document.querySelectorAll('#modalDesafio, #modalPagamento, #modalGrupo').forEach(m => m.classList.add('hidden'));
        }

        function executarRemocao(descontarPontos) {
            if (!itemParaRemover) return;
            
            const data = {
                ...itemParaRemover,
                descontar_pontos: descontarPontos
            };
            
            fetch('/api/sacolinhas/remove', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(parseJsonSafely)
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


        // Função para carregar status da live (renomeada e modificada)
        function carregarLiveStatus() {
            console.log('🔄 Carregando status da live...');
            
            fetch('/lives', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(parseJsonSafely)
            .then(data => {
                console.log('📡 Resposta da API de live(carregarLiveStatus):', data);
                const liveStatusDisplay = document.getElementById('live-status-display');
                // const toggleLiveBtn = document.getElementById('toggle-live'); // REMOVIDO: Não precisamos mais manipular o onclick aqui
                
                if (data.success && data.live) {
                    // Live ativa encontrada
                    liveAtiva = data.live;
					console.log('DEBUG: Live ativa definida:', liveAtiva); 
                    
                    // Determinar cor do badge baseado no tipo
                    let badgeClass = 'bg-primary';
                    let liveTypeText = data.live.tipo_live;
                    
                    if (data.live.tipo_live === 'precinho') {
                        badgeClass = 'bg-warning text-dark';
                        liveTypeText = 'Precinho (50% OFF)';
                    } else if (data.live.tipo_live === 'outlet') {
                        badgeClass = 'bg-danger';
                        liveTypeText = 'Outlet';
                    }
                    
                    liveStatusDisplay.innerHTML = `
                        <div class="alert alert-success border-success">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="fas fa-broadcast-tower text-success"></i>
                                    <strong>Live Ativa:</strong>
                                    <span class="badge ${badgeClass} ms-2">${liveTypeText}</span>
                                    <small class="text-muted ms-2">Plataformas: ${data.live.plataformas}</small>
                                </div>
                                <small class="text-muted">ID: ${data.live.id}</small>
                            </div>
                        </div>
                    `;
                    
                    // REMOVIDO: Atualizar botão (será feito por atualizarEstadoControlesLive no finally)
                    // toggleLiveBtn.innerHTML = '<i class="fas fa-stop"></i> Encerrar Live';
                    // toggleLiveBtn.className = 'btn btn-danger w-100';
                    // toggleLiveBtn.onclick = () => encerrarLive(data.live.id); // REMOVIDO: Evita chamadas duplicadas
                    
                    // Carregar sacolas após um pequeno delay
                    setTimeout(() => {
                        carregarSacolas(data.live.id);
                    }, 500);
                    
                } else {
                    // Nenhuma live ativa
                    liveAtiva = null;
					console.log('DEBUG: Nenhuma live ativa. liveAtiva = null');
                    liveStatusDisplay.innerHTML = `
                        <div class="alert alert-info border-info">
                            <i class="fas fa-info-circle text-info"></i>
                            Nenhuma live ativa no momento. Crie uma nova live para começar.
                        </div>
                    `;
                    
                    // REMOVIDO: Atualizar botão (será feito por atualizarEstadoControlesLive no finally)
                    // toggleLiveBtn.innerHTML = '<i class="fas fa-plus"></i> Nova Live';
                    // toggleLiveBtn.className = 'btn btn-primary w-100';
                    // toggleLiveBtn.onclick = criarNovaLive; // REMOVIDO: Evita chamadas duplicadas
                    
                    // Limpar lista de sacolas
                    document.getElementById('bags-list').innerHTML = `
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-shopping-bag fa-3x mb-3 opacity-50"></i>
                            <h5>Nenhuma sacola criada ainda</h5>
                            <p>Inicie uma live e adicione itens às sacolas dos clientes.</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('❌ Erro ao carregar status da live:', error);
                document.getElementById('live-status-display').innerHTML = `
                    <div class="alert alert-danger border-danger">
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                        Erro ao carregar status da live.
                    </div>
                `;
            })
            .finally(() => { // ADICIONADO: Garante que os controles da UI sejam atualizados após a requisição
                atualizarEstadoControlesLive();
            });
        }
        // Função para criar elemento de status da live (nova função)
        function criarElementoLiveStatus(live) {
            // Assumindo que o objeto live tem propriedades como tipo_live, data, created_at, plataformas
            const formattedPlatforms = live.plataformas ? live.plataformas.split(',').map(p => p.charAt(0).toUpperCase() + p.slice(1)).join(', ') : '';
            const formattedDate = new Date(live.data).toLocaleDateString('pt-BR');
            const formattedTime = new Date(live.created_at).toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
            return `
                <div class="card border-success mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">
                                    <i class="fas fa-broadcast-tower text-danger"></i>
                                    <strong>Live ${live.tipo_live.replace('-', ' ').toUpperCase()}</strong>
                                </h6>
                                <small class="text-muted">
                                    <i class="fas fa-calendar"></i> ${formattedDate} às ${formattedTime}
                                </small>
                                <br>
                                <small class="text-info">
                                    <i class="fas fa-share-alt"></i> 
                                    Plataformas: ${formattedPlatforms}
                                </small>
                            </div>
                            <div>
                                <span class="badge bg-success fs-6">
                                    <i class="fas fa-circle"></i> ATIVA
                                </span>
                                <button class="btn btn-sm btn-danger ms-2" onclick="encerrarLive(${live.id})" title="Encerrar Live">
                                    <i class="fas fa-times"></i> Encerrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
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
        // Função para atualizar estado dos controles de live (renomeada e modificada)
        function atualizarEstadoControlesLive() {
            const toggleButton = document.getElementById('toggle-live');
            const filterCard = document.getElementById('filter-card');
            const liveTypeSelect = document.getElementById('live-type');
            const platformCheckboxes = document.querySelectorAll('.platform-checkbox');
            if (liveAtiva) {
                toggleButton.classList.remove('btn-primary');
                toggleButton.classList.add('btn-danger');
                toggleButton.innerHTML = '<i class="fas fa-times"></i> Encerrar Live';
                filterCard.classList.remove('card-disabled');
                
                // Desabilita tipo de live e plataformas
                liveTypeSelect.disabled = true;
                platformCheckboxes.forEach(checkbox => checkbox.disabled = true);
            } else {
                toggleButton.classList.remove('btn-danger');
                toggleButton.classList.add('btn-primary');
                toggleButton.innerHTML = '<i class="fas fa-plus"></i> Nova Live';
                filterCard.classList.add('card-disabled');

                // Habilita tipo de live e plataformas
                liveTypeSelect.disabled = false;
                platformCheckboxes.forEach(checkbox => checkbox.disabled = false);
            }
        }
        // Event listener para o botão toggle
        function handleToggleLiveClick() {
            console.log("🔥 Botão toggle-live clicado!");
            if (liveAtiva) {
                console.log("Encerrando live ativa:", liveAtiva.id);
                encerrarLive(liveAtiva.id);
            } else {
                console.log("Criando nova live...");
                criarNovaLive();
            }
        }
        // Expor para o escopo global para o HTML onclick funcionar
        window.handleToggleLiveClick = handleToggleLiveClick;

        // Função para criar nova live
        function criarNovaLive() {
            console.log("🛠️ Iniciando criarNovaLive...");
            const tipoLive = document.getElementById('live-type').value;
            const plataformas = Array.from(document.querySelectorAll('.platform-checkbox:checked'))
                                   .map(checkbox => checkbox.value);

            if (plataformas.length === 0) {
                console.log("⚠️ Nenhuma plataforma selecionada!");
                alert('Selecione pelo menos uma plataforma antes de criar a live!'); // Alert nativo
                mostrarAlert('Selecione pelo menos uma plataforma!', 'warning');
                return;
            }
            console.log("Plataformas selecionadas:", plataformas, "Tipo:", tipoLive);
            const dados = {
                tipo_live: tipoLive,
                plataformas: plataformas
            };
            const button = document.getElementById('toggle-live');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';
            console.log("📡 Enviando POST para /lives com payload:", dados);
            fetch('/lives', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(dados)
            })
            .then(parseJsonSafely)
            .then(data => {
                if (data.success) {
                    mostrarAlert(data.message, 'success');
                    liveAtiva = data.live; // Atualiza liveAtiva com a live recém-criada
                    carregarLiveStatus(); // Atualiza o status e os controles
                } else {
                    mostrarAlert(data.message || 'Erro ao criar live', 'danger');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarAlert('Erro ao criar live', 'danger');
            })
            .finally(() => {
                button.disabled = false;
                // REMOVIDO: atualizarEstadoControlesLive(); // Agora é chamado por carregarLiveStatus().finally
            });
        }

        // Função para encerrar live (sem alterações significativas, apenas a chamada para carregarLiveStatus)
		function encerrarLive(liveId) {
			if (!confirm('Tem certeza que deseja encerrar esta live?')) return;

			const enviar = confirm(
				'Deseja encerrar a live E enviar as mensagens do WhatsApp?\n\n' +
				'OK = Encerrar com envio\n' +
				'Cancelar = Encerrar sem enviar'
			);

			fetch(`/lives/${liveId}?enviar_whatsapp=${enviar ? 1 : 0}`, {
				method: 'DELETE',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
					'X-Requested-With': 'XMLHttpRequest'
				}
			})
			.then(parseJsonSafely)
			.then(data => {
				if (data.success) {
					mostrarAlert(data.message, 'success');
					liveAtiva = null;
					carregarLiveStatus();
				} else {
					mostrarAlert(data.error || data.message || 'Erro ao encerrar live', 'danger');
				}
			})
			.catch(err => {
				console.error(err);
				mostrarAlert('Erro ao encerrar live', 'danger');
			});
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
            // Limpar seleção de usuário
            const userDisplayCard = userSearchWrapper.querySelector('[data-selected-display]');
            const userSearchInput = userSearchWrapper.querySelector('[data-search-input]');
            const userSelectedId = userSearchWrapper.querySelector('[data-selected-id]');
            const userClearBtn = userSearchWrapper.querySelector('[data-clear-btn]');
            const userResultsContainer = userSearchWrapper.querySelector('[data-results-container]');
            
            userDisplayCard.classList.add('d-none');
            console.log('DEBUG: Card de exibição do usuário (data-selected-display) agora tem classes:', userDisplayCard.classList);
            
            userSearchInput.value = '';
            console.log('DEBUG: Campo de busca de usuário (data-search-input) limpo.');
            
            userSelectedId.value = '';
            
            if (userClearBtn) {
                userClearBtn.style.display = 'none';
                console.log('DEBUG: Botão de limpar (data-clear-btn) escondido.');
            }
            
            userResultsContainer.style.display = 'none';
            console.log('DEBUG: Dropdown de sugestões de usuário escondido.');
            
            userHighlightedIndex = -1;
            console.log('DEBUG: highlightedIndex resetado.');
            
            selectedUser = null;
            console.log('Seleção de cliente limpa');
            
            document.dispatchEvent(new CustomEvent('userCleared'));
            console.log('DEBUG: Evento userCleared disparado.');
            
        } else if (type === 'item') {
            // Limpar seleção de item
            const itemDisplayCard = itemSearchWrapper.querySelector('[data-selected-display]');
            const itemSearchInput = itemSearchWrapper.querySelector('[data-search-input]');
            const itemSelectedId = itemSearchWrapper.querySelector('[data-selected-id]');
            const itemClearBtn = itemSearchWrapper.querySelector('[data-clear-btn]');
            const itemResultsContainer = itemSearchWrapper.querySelector('[data-results-container]');
            
            itemDisplayCard.classList.add('d-none');
            console.log('DEBUG: Card de exibição do item (data-selected-display) agora tem classes:', itemDisplayCard.classList);
            
            itemSearchInput.value = '';
            console.log('DEBUG: Campo de busca de item (data-search-input) limpo.');
            
            itemSelectedId.value = '';
            
            if (itemClearBtn) {
                itemClearBtn.style.display = 'none';
                console.log('DEBUG: Botão de limpar (data-clear-btn) escondido.');
            }
            
            itemResultsContainer.style.display = 'none';
            console.log('DEBUG: Dropdown de sugestões de item escondido.');
            
            itemHighlightedIndex = -1;
            console.log('DEBUG: highlightedIndex resetado.');
            
            // ===== CORRIGIR AQUI: Limpar campo de preço e preço original =====
            const priceInput = document.getElementById('item-price');
            const originalPriceDisplay = document.getElementById('original-price-display');
            
            if (priceInput) {
                priceInput.value = '';
                console.log('DEBUG: Campo de preço externo (item-price) limpo.');
            }
            
            if (originalPriceDisplay) {
                originalPriceDisplay.style.display = 'none';
                originalPriceDisplay.textContent = '';
                console.log('DEBUG: Preço original riscado escondido.');
            }
            
            selectedItem = null;
            console.log('Seleção de item limpa');
            
            document.dispatchEvent(new CustomEvent('itemCleared'));
            console.log('DEBUG: Evento itemCleared disparado.');
        }
    }
    </script>
</body>
</html>
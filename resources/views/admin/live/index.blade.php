@extends('layouts.app')

@section('title', 'Gerenciar Live')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Gerenciar Live</h1>
            <p class="text-sm text-gray-500 mt-1">Crie transmissões, acompanhe vendas em tempo real e gerencie sacolas de clientes.</p>
        </div>

        <!-- Controls -->
        <div class="flex flex-wrap items-end gap-4 bg-white p-4 rounded-xl border border-gray-200 shadow-sm" id="live-creation-card">
            <div class="flex-grow min-w-[200px]">
                <label for="live-type" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Tipo de Live</label>
                <select id="live-type" name="live_type" class="w-full text-sm border border-gray-300 rounded-lg p-2 bg-white focus:border-blue-500 focus:ring focus:ring-blue-200">
                    <option value="loja-aberta">Live Loja Aberta</option>
                    <option value="leilao">Live Leilão</option>
                    <option value="precinho">Live do Precinho</option>
                </select>
            </div>

            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Plataformas</span>
                <div class="flex items-center gap-3 py-2">
                    <label class="inline-flex items-center cursor-pointer text-sm text-gray-700">
                        <input class="form-checkbox rounded text-blue-600 platform-checkbox focus:ring-blue-500" type="checkbox" id="instagram" name="platforms[]" value="instagram">
                        <span class="ml-1.5">Instagram</span>
                    </label>
                    <label class="inline-flex items-center cursor-pointer text-sm text-gray-700">
                        <input class="form-checkbox rounded text-blue-600 platform-checkbox focus:ring-blue-500" type="checkbox" id="tiktok" name="platforms[]" value="tiktok">
                        <span class="ml-1.5">Tiktok</span>
                    </label>
                    <label class="inline-flex items-center cursor-pointer text-sm text-gray-700">
                        <input class="form-checkbox rounded text-blue-600 platform-checkbox focus:ring-blue-500" type="checkbox" id="youtube" name="platforms[]" value="youtube">
                        <span class="ml-1.5">YouTube</span>
                    </label>
                </div>
            </div>

            <button type="button" id="toggle-live" class="w-full lg:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg shadow transition duration-200 flex items-center justify-center gap-2" onclick="handleToggleLiveClick()">
                <i class="fas fa-plus"></i> Nova Live
            </button>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alert-container" class="space-y-2 mb-4">
        @if(session('success'))
            <div class="flex items-center justify-between p-4 text-sm bg-green-50 border border-green-300 text-green-800 rounded-xl shadow-sm">
                <div class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-green-500 text-lg"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" class="text-gray-400 hover:text-gray-600" onclick="this.parentNode.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif
    </div>

    <!-- Live Status Display -->
    <div id="live-status-display" class="mb-6">
        <!-- Dynamic loading -->
    </div>

    <!-- Form Section: Add Item -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 mb-6 opacity-60 pointer-events-none" id="filter-card">
        <div class="border-b border-gray-100 pb-4 mb-4 flex items-center gap-2">
            <i class="fas fa-shopping-bag text-blue-500 text-lg"></i>
            <h2 class="font-bold text-gray-800 text-lg">Adicionar Item à Sacola do Cliente</h2>
        </div>

        <form method="POST" action="{{ route('sacolinhas.store') }}" id="add-item-form">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                
                <!-- Selecionar Cliente -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-semibold text-gray-700">
                            <i class="fas fa-user text-gray-400 mr-1"></i> Selecionar Cliente
                        </label>
                        <a href="{{ route('admin.clientes.create') }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1" target="_blank" title="Cadastrar novo cliente">
                            <i class="fas fa-user-plus"></i> Novo Cliente
                        </a>
                    </div>
                    @include('components.user-search', [
                        'name' => 'client_id',
                        'placeholder' => 'Digite nome ou e-mail...',
                        'value' => old('client_id')
                    ])
                </div>

                <!-- Selecionar Item -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-semibold text-gray-700">
                            <i class="fas fa-box text-gray-400 mr-1"></i> Selecionar Item
                        </label>
                        <a href="{{ route('admin.items.create') }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1" target="_blank" title="Cadastrar novo item">
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

            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                <!-- Preço -->
                <div class="md:col-span-3">
                    <label for="item-price" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-dollar-sign text-gray-400 mr-1"></i> Preço
                        <span id="original-price-display" class="text-gray-400 text-xs ml-2 line-through hidden" style="text-decoration: line-through;"></span>
                    </label>
                    <input type="text" 
                           class="w-full text-sm border border-gray-300 rounded-lg p-2.5 bg-white focus:border-blue-500 focus:ring focus:ring-blue-200" 
                           name="item_price" 
                           id="item-price" 
                           placeholder="0,00" 
                           pattern="[0-9]+([,\.][0-9]{1,2})?" 
                           title="Use formato: 25,50 ou 25.50"
                           value="{{ old('item_price') }}" 
                           required>
                </div>

                <!-- Observação -->
                <div class="md:col-span-5">
                    <label for="obs" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-sticky-note text-gray-400 mr-1"></i> Observação
                    </label>
                    <textarea 
                        class="w-full text-sm border border-gray-300 rounded-lg p-2.5 bg-white focus:border-blue-500 focus:ring focus:ring-blue-200" 
                        name="obs" 
                        id="obs" 
                        rows="1"
                        placeholder="Obs (opcional)"
                        maxlength="200"
                        style="resize: none;"></textarea>
                </div>

                <!-- Botão de submissão -->
                <div class="md:col-span-4">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow transition duration-200 flex items-center justify-center gap-2" id="add-to-bag-btn">
                        <i class="fas fa-plus"></i> Adicionar à Sacola
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Sacolas Section -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="bg-gray-50 border-b border-gray-100 p-4 flex justify-between items-center flex-wrap gap-4">
            <div class="flex items-center gap-2">
                <i class="fas fa-shopping-bag text-indigo-500 text-lg"></i>
                <h2 class="font-bold text-gray-800 text-lg">Sacolinhas da Live Atual</h2>
            </div>
            <div class="text-right flex items-center gap-4">
                <div id="total-sacolas" class="font-bold text-green-600 text-lg hidden">
                    Total: R$ 0,00
                </div>
                <small class="text-xs font-semibold text-gray-405 uppercase bg-gray-100 px-2.5 py-1 rounded-full hidden animate-fade-in" id="contador-sacolas">
                    0 sacola(s)
                </small>
            </div>
        </div>
        
        <div class="p-6">
            <div id="bags-list">
                <div class="flex flex-col items-center justify-center text-center text-gray-400 py-12">
                    <i class="fas fa-shopping-bag text-5xl mb-4 opacity-50"></i>
                    <h3 class="font-semibold text-gray-700 text-base">Nenhuma sacola criada ainda</h3>
                    <p class="text-sm mt-1 text-gray-400">Inicie uma live e adicione itens às sacolas dos clientes.</p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Vacina global anti-lixo para limpar JSONs corrompidos pelo servidor
    function parseJsonSafely(response) {
        return response.text().then(text => {
            let idxSuccess = text.lastIndexOf('{"success":');
            let idxError = text.lastIndexOf('{"error":');
            let startIdx = Math.max(idxSuccess, idxError);
            
            if (startIdx === -1) {
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
    const DISCOUNT_PERCENTAGE = 0.5;

    let itemSearchWrapper = null;
    let selectedItem = null;
    let selectedUser = null;
    let itemHighlightedIndex = -1;

    document.addEventListener('DOMContentLoaded', () => {
        itemSearchWrapper = document.querySelector('[data-item-search="true"]');
        carregarLiveStatus();
        
        // Event listener para seleção de usuário
        const userSearchComponent = document.querySelector('[data-user-search="true"]');
        if (userSearchComponent) {
            userSearchComponent.addEventListener('userSelected', function(e) {
                const user = e.detail.user;
                selectedUser = user;
                console.log('Cliente selecionado:', user);
                mostrarAlert(`Cliente selecionado: ${user.name}`, 'info');
                
                setTimeout(() => {
                    const itemInput = document.querySelector('[data-item-search="true"] .item-search-input');
                    if (itemInput) {
                        itemInput.focus();
                        console.log('✅ Foco movido para Selecionar Item');
                    }
                }, 300);
            });
        }
        
        // Garante foco em Selecionar Cliente ao carregar a página
        setTimeout(() => {
            const clientInput = document.querySelector('[data-user-search="true"] .user-search-input');
            if (clientInput) {
                clientInput.focus();
            }
        }, 500);
    });

    // Event listener para seleção de item
    document.addEventListener('itemSelected', function(e) {
        const item = e.detail.item;
        selectedItem = item;
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
                originalPriceDisplay.classList.remove('hidden');
            } else {
                console.log('DEBUG: Não é live "precinho" ou liveAtiva não está definida. Usando preço original.');
                itemPriceInput.value = parseFloat(item.price).toFixed(2);
                originalPriceDisplay.classList.add('hidden');
            }
        }
        
        setTimeout(() => {
            console.log('⏳ Tentando mover foco para o preço...');
            const priceInput = document.getElementById('item-price');
            if (priceInput) {
                priceInput.focus();
                priceInput.select();
                console.log('✅ Foco movido para o preço');
            }
        }, 100);
    });

    document.addEventListener('itemCleared', function(e) {
        console.log('Seleção de item limpa');
        selectedItem = null;
        const itemPriceInput = document.getElementById('item-price');
        if (itemPriceInput) itemPriceInput.value = '';
        const originalPriceDisplay = document.getElementById('original-price-display');
        if (originalPriceDisplay) originalPriceDisplay.classList.add('hidden');
    });

    // Validação em tempo real do campo de preço
    document.addEventListener('DOMContentLoaded', () => {
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
                if (isNaN(numValue) || numValue <= 0){
                    e.target.setCustomValidity('Informe um preço válido (ex: 25,50)');
                } else {
                    e.target.setCustomValidity('');
                }
            });
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
            
            if (!itemPriceConverted || parseFloat(itemPriceConverted) <= 0) {
                mostrarAlert('Por favor, informe um preço válido!', 'warning');
                return false;
            }		
            
            if (!liveAtiva) {
                mostrarAlert('Inicie uma live antes de adicionar itens!', 'warning');
                return false;
            }

            const formData = new FormData(this);
            formData.append('item_quantity', 1); 
            formData.set('item_price', itemPriceConverted);
            
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Adicionando...';
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
                    
                    const itemPriceInput = document.getElementById('item-price');
                    if (itemPriceInput) {
                        itemPriceInput.value = '';
                    }
                    document.getElementById('original-price-display').classList.add('hidden');
                    
                    const clientSearchInput = document.querySelector('[data-user-search="true"] .user-search-input');
                    const clientHiddenInput = document.querySelector('[data-user-search="true"] .user-selected-id');
                    const clientDisplayCard = document.querySelector('[data-user-search="true"] .user-selected-display');
                    const clientDropdown = document.querySelector('[data-user-search="true"] .user-suggestions-dropdown');
                    const clientClearBtn = document.querySelector('[data-user-search="true"] .user-clear-btn');
                    
                    if (clientSearchInput) clientSearchInput.value = '';
                    if (clientHiddenInput) clientHiddenInput.value = '';
                    if (clientDisplayCard) clientDisplayCard.style.display = 'none';
                    if (clientDropdown) clientDropdown.style.display = 'none';
                    if (clientClearBtn) clientClearBtn.style.display = 'none';
                    
                    const itemSearchInput = document.querySelector('[data-item-search="true"] [data-search-input="true"]');
                    const itemHiddenInput = document.querySelector('[data-item-search="true"] [data-selected-id="true"]');
                    const itemDisplayCard = document.querySelector('[data-item-search="true"] [data-selected-display="true"]');
                    const itemResultsContainer = document.querySelector('[data-item-search="true"] [data-results-container="true"]');
                    
                    if (itemSearchInput) itemSearchInput.value = '';
                    if (itemHiddenInput) itemHiddenInput.value = '';
                    if (itemDisplayCard) itemDisplayCard.classList.add('d-none');
                    if (itemResultsContainer) itemResultsContainer.style.display = 'none';
                    
                    selectedItem = null;
                    selectedUser = null;
                    
                    setTimeout(function() {
                        const clientSearchInput = document.querySelector('[data-user-search="true"] .user-search-input');
                        if (clientSearchInput) {
                            console.log('🎯 Focando no CLIENTE para próxima adição...');
                            clientSearchInput.focus();
                        }
                    }, 200);
                    
                    carregarSacolas();
                }
            } catch (error) {
                console.error('Erro:', error);
                mostrarAlert('Erro ao adicionar item à sacola', 'danger');
            } finally {
                button.disabled = false;
                button.innerHTML = originalText;
            }
        });
    });

    function carregarSacolas() {
        if (!liveAtiva) {
            document.getElementById('bags-list').innerHTML = `
                <div class="flex flex-col items-center justify-center text-center text-gray-400 py-12">
                    <i class="fas fa-shopping-bag text-5xl mb-4 opacity-50"></i>
                    <h3 class="font-semibold text-gray-700 text-base">Nenhuma sacola criada ainda</h3>
                    <p class="text-sm mt-1 text-gray-400">Inicie uma live e adicione itens às sacolas dos clientes.</p>
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

    function exibirSacolas(bags) {
        const container = document.getElementById('bags-list');
        const totalSacolas = document.getElementById('total-sacolas');
        const contadorSacolas = document.getElementById('contador-sacolas');
        
        if (bags.length === 0) {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center text-center text-gray-400 py-12">
                    <i class="fas fa-shopping-bag text-5xl mb-4 opacity-50"></i>
                    <h3 class="font-semibold text-gray-700 text-base">Nenhuma sacola criada ainda</h3>
                    <p class="text-sm mt-1 text-gray-400">Adicione itens às sacolas dos clientes.</p>
                </div>
            `;
            totalSacolas.classList.add('hidden');
            contadorSacolas.classList.add('hidden');
            return;
        }
        
        let totalGeral = 0;
        let totalItens = 0;
        
        bags.forEach(bag => {
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
        totalSacolas.classList.remove('hidden');
        
        contadorSacolas.textContent = `${bags.length} sacola(s) • ${totalItens} item(s)`;
        contadorSacolas.classList.remove('hidden');
        
        let html = '';
        bags.forEach(bag => {
            const whatsapp = bag.client.whatsapp || 'N/A';
            
            html += `
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mb-6 transition hover:shadow-md">
                    <div class="bg-gray-50 border-b border-gray-100 p-4">
                        <div class="flex items-center justify-between flex-wrap gap-3">
                            <div class="flex items-center gap-3">
                                <img src="${bag.client.avatar_url}" class="rounded-full border border-gray-200" width="40" height="40">
                                <div>
                                    <h3 class="font-semibold text-gray-800 text-base">${bag.client.name}</h3>
                                    <div class="flex flex-wrap items-center gap-2 mt-0.5 text-xs text-gray-500">
                                        <span>ID: ${bag.client.id}</span>
                                        <span>•</span>
                                        <span>WhatsApp: ${whatsapp}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right flex items-center gap-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800" style="background-color: #e0e7ff; color: #4338ca;">
                                    ${bag.items.length} item(s)
                                </span>
                                <div class="text-lg font-bold text-green-600">${bag.formatted_total}</div>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm text-left">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Item</th>
                                    <th class="px-6 py-3 font-medium">Detalhes</th>
                                    <th class="px-6 py-3 font-medium">Preço</th>
                                    <th class="px-6 py-3 text-center" width="80">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white text-gray-700">
            `;
            
            bag.items.forEach(item => {
                const details = [];
                if (item.item_sku) details.push(`Código: ${item.item_sku}`);
                if (item.item_brand) details.push(`Marca: ${item.item_brand}`);
                if (item.item_color) details.push(`Cor: ${item.item_color}`);
                if (item.item_size) details.push(`Tam: ${item.item_size}`);
                
                let obsDisplay = '';
                if (item.obs) {
                    obsDisplay = `
                        <div class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                            <i class="fas fa-sticky-note text-gray-400"></i>
                            <span>Obs: ${item.obs}</span>
                        </div>
                    `;
                }					
                
                html += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-900">${item.item_name}</td>
                        <td class="px-6 py-4">
                            <div class="text-xs text-gray-500">${details.length > 0 ? details.join(' | ') : 'Sem detalhes adicionais'}</div>
                            ${obsDisplay}
                        </td>
                        <td class="px-6 py-4 font-semibold text-green-600">${item.formatted_total_price}</td>
                        <td class="px-6 py-4 text-center">
                            <button class="text-red-500 hover:text-red-700 transition" onclick="removerItem(${item.item_id}, ${bag.client.id}, '${item.item_name.replace(/'/g, "\\'").replace(/"/g, '&quot;')}', '${item.formatted_total_price}')" title="Remover item">
                                <i class="fas fa-trash-alt"></i>
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
            `;
        });
        container.innerHTML = html;
    }

    let itemParaRemover = null;

    function removerItem(itemId, userId, itemName, itemPrice) {
        if (!liveAtiva) {
            mostrarAlert('Nenhuma live ativa identificada para remover itens.', 'warning');
            return;
        }
        if (confirm(`Deseja realmente remover o item "${itemName}" da sacolinha?`)) {
            itemParaRemover = { 
                item_id: itemId, 
                user_id: userId, 
                live_id: liveAtiva.id 
            };
            executarRemocao(false);
        }
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
                carregarSacolas();
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

    function carregarLiveStatus() {
        console.log('🔄 Carregando status da live...');
        
        fetch('/lives', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(parseJsonSafely)
        .then(data => {
            console.log('📡 Resposta da API de live (carregarLiveStatus):', data);
            const liveStatusDisplay = document.getElementById('live-status-display');
            
            if (data.success && data.live) {
                liveAtiva = data.live;
                console.log('DEBUG: Live ativa definida:', liveAtiva); 
                
                let badgeClass = 'bg-blue-100 text-blue-800';
                let liveTypeText = data.live.tipo_live;
                
                if (data.live.tipo_live === 'precinho') {
                    badgeClass = 'bg-yellow-100 text-yellow-800';
                    liveTypeText = 'Precinho (50% OFF)';
                } else if (data.live.tipo_live === 'outlet') {
                    badgeClass = 'bg-red-100 text-red-800';
                    liveTypeText = 'Outlet';
                }
                
                liveStatusDisplay.innerHTML = `
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4 shadow-sm">
                        <div class="flex items-center justify-between flex-wrap gap-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-3 w-3 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                                </span>
                                <div class="text-sm text-green-800">
                                    <strong class="font-bold text-green-900">Live Ativa:</strong>
                                    <span class="ml-1.5 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider ${badgeClass}">
                                        ${liveTypeText}
                                    </span>
                                    <span class="ml-3 text-gray-500 font-medium">Plataformas: ${data.live.plataformas}</span>
                                </div>
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-gray-400 mr-3">ID: ${data.live.id}</span>
                                <button class="bg-red-500 hover:bg-red-600 text-white font-semibold py-1 px-3 rounded-lg text-xs transition duration-200 inline-flex items-center gap-1 shadow-sm" onclick="encerrarLive(${data.live.id})" title="Encerrar Live">
                                    <i class="fas fa-times"></i> Encerrar Live
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                setTimeout(() => {
                    carregarSacolas(data.live.id);
                }, 500);
                
            } else {
                liveAtiva = null;
                console.log('DEBUG: Nenhuma live ativa. liveAtiva = null');
                liveStatusDisplay.innerHTML = `
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 shadow-sm text-sm text-blue-800 flex items-center gap-2">
                        <i class="fas fa-info-circle text-blue-500 text-lg"></i>
                        <span>Nenhuma live ativa no momento. Crie uma nova live para começar.</span>
                    </div>
                `;
                
                document.getElementById('bags-list').innerHTML = `
                    <div class="flex flex-col items-center justify-center text-center text-gray-400 py-12">
                        <i class="fas fa-shopping-bag text-5xl mb-4 opacity-50"></i>
                        <h3 class="font-semibold text-gray-700 text-base">Nenhuma sacola criada ainda</h3>
                        <p class="text-sm mt-1 text-gray-400">Inicie uma live e adicione itens às sacolas dos clientes.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('❌ Erro ao carregar status da live:', error);
            document.getElementById('live-status-display').innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 shadow-sm text-sm text-red-800 flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle text-red-500 text-lg"></i>
                    <span>Erro ao carregar status da live.</span>
                </div>
            `;
        })
        .finally(() => {
            atualizarEstadoControlesLive();
        });
    }

    function mostrarAlert(message, type = 'success') {
        const alertContainer = document.getElementById('alert-container');
        const alert = document.createElement('div');
        let colorClasses = 'bg-green-50 text-green-800 border-green-300';
        let iconClass = 'fa-check-circle text-green-500';
        
        if (type === 'danger' || type === 'error') {
            colorClasses = 'bg-red-50 text-red-800 border-red-300';
            iconClass = 'fa-exclamation-circle text-red-500';
        } else if (type === 'warning') {
            colorClasses = 'bg-yellow-50 text-yellow-800 border-yellow-300';
            iconClass = 'fa-exclamation-triangle text-yellow-500';
        } else if (type === 'info') {
            colorClasses = 'bg-blue-50 text-blue-800 border-blue-300';
            iconClass = 'fa-info-circle text-blue-500';
        }
        
        alert.className = `flex items-center justify-between p-4 border rounded-xl shadow-sm ${colorClasses}`;
        alert.innerHTML = `
            <div class="flex items-center gap-2">
                <i class="fas ${iconClass} text-lg"></i>
                <span>${message}</span>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="this.parentNode.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        alertContainer.appendChild(alert);

        setTimeout(() => {
            if (alert.parentNode) {
                alert.classList.add('opacity-0', 'transition-opacity', 'duration-500');
                setTimeout(() => alert.remove(), 500);
            }
        }, 5500);
    }

    function atualizarEstadoControlesLive() {
        const toggleButton = document.getElementById('toggle-live');
        const filterCard = document.getElementById('filter-card');
        const liveTypeSelect = document.getElementById('live-type');
        const platformCheckboxes = document.querySelectorAll('.platform-checkbox');
        const creationCard = document.getElementById('live-creation-card');
        
        if (liveAtiva) {
            toggleButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            toggleButton.classList.add('bg-red-600', 'hover:bg-red-700');
            toggleButton.innerHTML = '<i class="fas fa-times"></i> Encerrar Live';
            filterCard.classList.remove('opacity-60', 'pointer-events-none');
            
            liveTypeSelect.disabled = true;
            platformCheckboxes.forEach(checkbox => checkbox.disabled = true);

            if (creationCard) {
                creationCard.classList.add('hidden');
            }

            // Ao iniciar live, dar foco em Selecionar Cliente
            setTimeout(() => {
                const clientInput = document.querySelector('[data-user-search="true"] .user-search-input');
                if (clientInput && document.activeElement !== clientInput) {
                    clientInput.focus();
                }
            }, 300);
        } else {
            toggleButton.classList.remove('bg-red-600', 'hover:bg-red-700');
            toggleButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
            toggleButton.innerHTML = '<i class="fas fa-plus"></i> Nova Live';
            filterCard.classList.add('opacity-60', 'pointer-events-none');

            liveTypeSelect.disabled = false;
            platformCheckboxes.forEach(checkbox => checkbox.disabled = false);

            if (creationCard) {
                creationCard.classList.remove('hidden');
            }
        }
    }

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

    window.handleToggleLiveClick = handleToggleLiveClick;
    window.removerItem = removerItem;
    window.encerrarLive = encerrarLive;

    function criarNovaLive() {
        console.log("🛠️ Iniciando criarNovaLive...");
        const tipoLive = document.getElementById('live-type').value;
        const plataformas = Array.from(document.querySelectorAll('.platform-checkbox:checked'))
                               .map(checkbox => checkbox.value);

        if (plataformas.length === 0) {
            console.log("⚠️ Nenhuma plataforma selecionada!");
            alert('Selecione pelo menos uma plataforma antes de criar a live!');
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
                liveAtiva = data.live;
                carregarLiveStatus();
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
        });
    }

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
        
        const itemIdInput = itemSearchWrapper.querySelector('[data-selected-id]');
        const itemDisplayCard = itemSearchWrapper.querySelector('[data-selected-display]');
        
        itemIdInput.value = item.id;
        
        itemDisplayCard.innerHTML = `
            <div class="bg-green-50 border border-green-200 rounded-xl p-3 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <img class="rounded border border-gray-200" src="${item.image_url}" width="36" height="36">
                    <div>
                        <h4 class="font-bold text-gray-800 text-sm">${item.name}</h4>
                        <small class="text-xs text-gray-500">
                            SKU: ${item.sku || 'N/A'} | 
                            Preço Original: ${item.formatted_price}
                        </small>
                    </div>
                </div>
                <button type="button" class="text-red-500 hover:text-red-700" data-clear-btn="true" title="Remover item">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        itemDisplayCard.classList.remove('d-none');
        
        const searchInput = itemSearchWrapper.querySelector('[data-search-input]');
        searchInput.value = '';
        
        const resultsContainer = itemSearchWrapper.querySelector('[data-results-container]');
        resultsContainer.style.display = 'none';
        
        const priceInput = document.getElementById('item-price');
        const originalPriceDisplay = document.getElementById('original-price-display');
        
        if (priceInput) {
            const isPrecinhoLive = liveAtiva && liveAtiva.tipo_live === 'precinho';
            console.log('DEBUG: isPrecinhoLive (true/false):', isPrecinhoLive); 
            
            if (isPrecinhoLive) {
                const originalPrice = parseFloat(item.price);
                console.log('DEBUG: Preço Original (item.price):', originalPrice);
                const discountedPrice = originalPrice * DISCOUNT_PERCENTAGE;
                console.log('DEBUG: Preço com Desconto:', discountedPrice);
                
                priceInput.value = discountedPrice.toFixed(2);
                originalPriceDisplay.textContent = item.formatted_price;
                originalPriceDisplay.classList.remove('hidden');
            } else {
                console.log('DEBUG: Não é live "precinho" ou liveAtiva não está definida. Usando preço original.'); 
                priceInput.value = parseFloat(item.price).toFixed(2);
                originalPriceDisplay.classList.add('hidden');
            }
        }
        
        const clearBtn = itemDisplayCard.querySelector('[data-clear-btn]');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => clearSelection('item'));
        }
        
        selectedItem = item;
        document.dispatchEvent(new CustomEvent('itemSelected', { detail: item }));
    }

    function clearSelection(type) {
        console.log(`DEBUG: clearSelection (${type.charAt(0).toUpperCase() + type.slice(1)}) chamada.`);
        
        if (type === 'user') {
            const userDisplayCard = userSearchWrapper.querySelector('[data-selected-display]');
            const userSearchInput = userSearchWrapper.querySelector('[data-search-input]');
            const userSelectedId = userSearchWrapper.querySelector('[data-selected-id]');
            const userClearBtn = userSearchWrapper.querySelector('[data-clear-btn]');
            const userResultsContainer = userSearchWrapper.querySelector('[data-results-container]');
            
            userDisplayCard.classList.add('d-none');
            userSearchInput.value = '';
            userSelectedId.value = '';
            
            if (userClearBtn) userClearBtn.style.display = 'none';
            userResultsContainer.style.display = 'none';
            
            selectedUser = null;
            document.dispatchEvent(new CustomEvent('userCleared'));
            
        } else if (type === 'item') {
            const itemDisplayCard = itemSearchWrapper.querySelector('[data-selected-display]');
            const itemSearchInput = itemSearchWrapper.querySelector('[data-search-input]');
            const itemSelectedId = itemSearchWrapper.querySelector('[data-selected-id]');
            const itemClearBtn = itemSearchWrapper.querySelector('[data-clear-btn]');
            const itemResultsContainer = itemSearchWrapper.querySelector('[data-results-container]');
            
            itemDisplayCard.classList.add('d-none');
            itemSearchInput.value = '';
            itemSelectedId.value = '';
            
            if (itemClearBtn) itemClearBtn.style.display = 'none';
            itemResultsContainer.style.display = 'none';
            
            const priceInput = document.getElementById('item-price');
            const originalPriceDisplay = document.getElementById('original-price-display');
            
            if (priceInput) priceInput.value = '';
            if (originalPriceDisplay) {
                originalPriceDisplay.classList.add('hidden');
                originalPriceDisplay.textContent = '';
            }
            
            selectedItem = null;
            document.dispatchEvent(new CustomEvent('itemCleared'));
        }
    }
</script>
@endpush
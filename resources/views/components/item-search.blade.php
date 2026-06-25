<div class="item-search-wrapper" data-item-search="true">
    <div class="position-relative">
        <!-- Input de Busca -->
        <div class="input-group">
            <span class="input-group-text">
                <i class="fas fa-box text-muted"></i>
            </span>
			<input 
				type="text" 
				class="form-control item-search-input" 
				placeholder="{{ $placeholder ?? 'Buscar por nome, SKU ou descrição...' }}"
				autocomplete="off"
				autocapitalize="none"
				autocorrect="off"
				spellcheck="false"
				data-form-type="other"
				data-search-input="true"
			>
            <button class="btn btn-outline-secondary item-qr-btn" type="button" data-qr-btn="true" title="Ler QRCode">
                <i class="fas fa-qrcode"></i>
            </button>
            <button class="btn btn-outline-secondary item-clear-btn d-none" type="button" data-clear-btn="true">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Scanner de QRCode -->
        <div class="item-qr-reader-wrap d-none mt-2 border border-secondary rounded p-2 bg-white" data-qr-reader-wrap="true">
            <div class="text-sm text-muted mb-2 d-flex justify-content-between align-items-center" style="font-size: 0.85rem;">
                <span><i class="fas fa-camera me-1"></i> Aponte a câmera para o QR Code</span>
                <button type="button" class="btn-close border-0 bg-transparent text-secondary fw-bold" data-qr-close-btn="true" aria-label="Close" style="cursor: pointer;">✕</button>
            </div>
            <div class="qr-reader-area" style="width: 100%; max-width: 350px; margin: 0 auto; overflow: hidden; border-radius: 4px;"></div>
        </div>

        <!-- Dropdown de Sugestões -->
        <div class="item-suggestions-dropdown" data-suggestions="true" style="display: none;">
            <!-- Resultados aparecerão aqui -->
        </div>

        <!-- Campos Hidden -->
        <input type="hidden" class="item-selected-id" name="{{ $name ?? 'item_id' }}" value="{{ $value ?? '' }}" data-hidden-input="true">
        <input type="hidden" class="item-selected-price" name="{{ $priceField ?? 'item_price' }}" value="{{ $priceValue ?? '' }}" data-price-input="true">
    </div>

    <!-- Item Selecionado -->
    <div class="item-selected-display mt-2 d-none" data-selected-display="true">
        <div class="card border-success">
            <div class="card-body p-2">
                <div class="d-flex align-items-center">
                    <img class="item-selected-image rounded me-2" src="" alt="" width="32" height="32">
                    <div class="flex-grow-1">
                        <div class="item-selected-name fw-bold"></div>
                        <small class="item-selected-price text-success fw-bold"></small>
                        <br><small class="item-selected-sku text-muted"></small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger item-remove-btn" data-remove-btn="true">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.item-search-wrapper {
    position: relative;
}
/* Fallback CSS for non-Bootstrap contexts */
.item-search-wrapper .input-group {
    display: flex;
    width: 100%;
}
.item-search-wrapper .input-group .item-search-input {
    flex-grow: 1;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem 0 0 0.375rem;
    padding: 0.5rem 0.75rem;
    outline: none;
    font-size: 0.875rem;
    background-color: #fff;
}
.item-search-wrapper .input-group .input-group-text {
    display: flex;
    align-items: center;
    padding: 0.5rem 0.75rem;
    background-color: #f3f4f6;
    border: 1px solid #d1d5db;
    border-right: none;
    border-radius: 0.375rem 0 0 0.375rem;
}
.item-search-wrapper .input-group .input-group-text + .item-search-input {
    border-radius: 0;
}
.item-search-wrapper .input-group .item-qr-btn {
    border: 1px solid #d1d5db;
    border-left: none;
    background: #f9fafb;
    padding: 0.5rem 0.75rem;
    cursor: pointer;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.item-search-wrapper .input-group .item-qr-btn:hover {
    background-color: #f3f4f6;
}
.item-search-wrapper .input-group .item-clear-btn {
    border: 1px solid #d1d5db;
    border-left: none;
    border-radius: 0 0.375rem 0.375rem 0;
    background: #f9fafb;
    padding: 0.5rem 0.75rem;
    cursor: pointer;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.item-search-wrapper .input-group .item-clear-btn:hover {
    background-color: #f3f4f6;
}

/* Card & Utility Fallbacks for Tailwind context */
.item-search-wrapper .card {
    background-color: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}
.item-search-wrapper .card.border-success {
    border-color: #10b981;
    background-color: #ecfdf5;
}
.item-search-wrapper .card-body {
    padding: 0.5rem 0.75rem;
}
.item-search-wrapper .d-flex {
    display: flex;
}
.item-search-wrapper .align-items-center {
    align-items: center;
}
.item-search-wrapper .flex-grow-1 {
    flex-grow: 1;
}
.item-search-wrapper .fw-bold {
    font-weight: 700;
}
.item-search-wrapper .text-success {
    color: #10b981;
}
.item-search-wrapper .text-muted {
    color: #6b7280;
}
.item-search-wrapper .me-2 {
    margin-right: 0.5rem;
}
.item-search-wrapper .mt-2 {
    margin-top: 0.5rem;
}
.item-search-wrapper .rounded {
    border-radius: 0.25rem;
}
.item-search-wrapper .btn-outline-danger {
    color: #ef4444;
    background: transparent;
    border: 1px solid #ef4444;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: background-color 0.2s, color 0.2s;
}
.item-search-wrapper .btn-outline-danger:hover {
    background-color: #ef4444;
    color: #fff;
}
.item-search-wrapper .d-none {
    display: none !important;
}

.item-suggestions-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 9999;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    max-height: 400px;
    overflow-y: auto;
    margin-top: 2px;
}

.item-suggestion-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.15s ease;
}

.item-suggestion-item:hover {
    background-color: #f8f9fa;
}

.item-suggestion-item:last-child {
    border-bottom: none;
}

.item-search-loading,
.item-search-error,
.item-search-no-results {
    padding: 1rem;
    text-align: center;
    color: #6c757d;
}

.item-search-error {
    color: #dc3545;
}

.item-suggestion-item.highlighted {
    background-color: #e9ecef; /* Cor de destaque */
    border-left: 3px solid #007bff; /* Borda para indicar destaque */
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('�� Inicializando busca de itens...');

    const wrapper = document.querySelector('[data-item-search="true"]');
    if (!wrapper) {
        console.error('❌ Wrapper de busca de item não encontrado');
        return;
    }

    console.log('✅ Wrapper de busca de item encontrado');

    const elements = {
        input: wrapper.querySelector('[data-search-input="true"]'),
        dropdown: wrapper.querySelector('[data-suggestions="true"]'),
        hiddenInput: wrapper.querySelector('[data-hidden-input="true"]'),
        priceInput: wrapper.querySelector('[data-price-input="true"]'),
        clearBtn: wrapper.querySelector('[data-clear-btn="true"]'),
        selectedDisplay: wrapper.querySelector('[data-selected-display="true"]'),
        removeBtn: wrapper.querySelector('[data-remove-btn="true"]')
    };

    // Verificar elementos
    const missing = Object.keys(elements).filter(key => !elements[key]);
    if (missing.length > 0) {
        console.error('❌ Elementos de busca de item não encontrados:', missing);
        return;
    }

    console.log('✅ Todos os elementos de busca de item encontrados');

    let debounceTimer;
    let highlightedIndex = -1; // Índice do item destacado para navegação por teclado

    // Função para destacar um item na lista
    function highlightItem(index) {
        const items = elements.dropdown.querySelectorAll('.item-suggestion-item');
        items.forEach((item, i) => {
            if (i === index) {
                item.classList.add('highlighted');
                item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else {
                item.classList.remove('highlighted');
            }
        });
        highlightedIndex = index;
    }

    // Função para limpar completamente a seleção e o estado da UI
    function clearSelection() {
        console.log('DEBUG: clearSelection (Item) chamada.');

        // Limpa os inputs ocultos (ID e preço do item)
        elements.hiddenInput.value = '';
        elements.priceInput.value = '';

        // Esconde o card de exibição do item selecionado
        elements.selectedDisplay.classList.add('d-none');
        console.log('DEBUG: Card de exibição do item (data-selected-display) agora tem classes:', elements.selectedDisplay.classList);

        // Limpa o campo de texto visível
        elements.input.value = '';
        console.log('DEBUG: Campo de busca de item (data-search-input) limpo.');

        // Esconde o botão de limpar (o "X" ao lado do campo de busca)
        elements.clearBtn.classList.add('d-none');
        console.log('DEBUG: Botão de limpar (data-clear-btn) escondido.');

        // Esconde o dropdown de sugestões
        hideDropdown();
        console.log('DEBUG: Dropdown de sugestões de item escondido.');

        // Reseta o índice de destaque para navegação por teclado
        highlightedIndex = -1;
        console.log('DEBUG: highlightedIndex resetado.');

        // Limpar campo de preço externo, se existir
        const priceField = document.getElementById('item-price');
        if (priceField) {
            priceField.value = '';
            console.log('DEBUG: Campo de preço externo (item-price) limpo.');
        }

        // Dispara o evento customizado
        wrapper.dispatchEvent(new CustomEvent('itemCleared'));
        console.log('DEBUG: Evento itemCleared disparado.');
    }

    // EXPOR A FUNÇÃO clearSelection através do wrapper
    wrapper.clear = clearSelection; 
    // Event listener para input
    elements.input.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        console.log('📝 Digitando (Item):', query);

        if (query.length > 0) {
            elements.clearBtn.classList.remove('d-none');
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => searchItems(query), 300);
        } else {
            // Se o campo de busca estiver vazio, limpa tudo
            clearSelection();
        }
    });

    // Event listener para keydown (setas e enter)
    elements.input.addEventListener('keydown', function(e) {
        const items = elements.dropdown.querySelectorAll('.item-suggestion-item');
        if (items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            highlightedIndex = (highlightedIndex + 1) % items.length;
            highlightItem(highlightedIndex);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            highlightedIndex = (highlightedIndex - 1 + items.length) % items.length;
            highlightItem(highlightedIndex);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (highlightedIndex !== -1 && items[highlightedIndex]) {
                items[highlightedIndex].click();
            } else if (items.length > 0) {
                items[0].click();
            }
        }
    });

    // Event listener para o botão de limpar (ao lado do input)
    elements.clearBtn.addEventListener('click', function() {
        console.log('DEBUG: Botão de limpar (Item) clicado.');
        clearSelection(); // Chama a função que limpa tudo
    });

    // Event listener para o botão de remover (no card de seleção)
    elements.removeBtn.addEventListener('click', function() {
        console.log('DEBUG: Botão de remover (Item) do card clicado.');
        clearSelection(); // Chama a função que limpa tudo
    });

    // Buscar itens
    async function searchItems(query) {
        console.log('🔎 Buscando itens:', query);
        showLoading();

        try {
            const response = await fetch(`/api/items/search?q=${encodeURIComponent(query)}`);
            const data = await response.json();

            console.log('📦 Dados de itens recebidos:', data);
            if (data.success && data.data) {
                displaySuggestions(data.data);
            } else {
                showNoResults();
            }
        } catch (error) {
            console.error('💥 Erro na busca de itens:', error);
            showError();
        }
    }

    // Mostrar loading
    function showLoading() {
        elements.dropdown.innerHTML = `
            <div class="item-search-loading">
                <div class="spinner-border spinner-border-sm text-success"></div>
                <div class="mt-2">Buscando itens...</div>
            </div>
        `;
        showDropdown();
        highlightedIndex = -1;
    }

    // Mostrar sugestões
    function displaySuggestions(items) {
        console.log(`📋 ${items.length} itens encontrados`);

        if (items.length === 0) {
            showNoResults();
            return;
        }

        elements.dropdown.innerHTML = '';

        items.forEach(item => {
            const itemElement = document.createElement('div');
            itemElement.className = 'item-suggestion-item';
            itemElement.innerHTML = `
                <div class="d-flex align-items-center">
                    <img src="${item.image_url}" class="rounded me-2" width="32" height="32">
                    <div class="flex-grow-1">
                        <div class="fw-bold">${item.name}</div>
                        <small class="text-success fw-bold">${item.formatted_price}</small>
                        ${item.sku ? `<br><small class="text-muted">SKU: ${item.sku}</small>` : ''}
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Estoque: ${item.stock}</small>
                    </div>
                </div>
            `;
            itemElement.addEventListener('click', () => selectItem(item));
            elements.dropdown.appendChild(itemElement);
        });

        showDropdown();
        highlightedIndex = -1;
    }

    // Sem resultados
    function showNoResults() {
        elements.dropdown.innerHTML = `
            <div class="item-search-no-results">
                <i class="fas fa-box-open fa-2x mb-2"></i>
                <div>Nenhum item encontrado</div>
            </div>
        `;
        showDropdown();
        highlightedIndex = -1;
    }

    // Erro
    function showError() {
        elements.dropdown.innerHTML = `
            <div class="item-search-error">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                <div>Erro ao buscar itens</div>
            </div>
        `;
        showDropdown();
        highlightedIndex = -1;
    }

    // Selecionar item
    function selectItem(item) {
        console.log('📦 Item selecionado:', item.name);

        elements.hiddenInput.value = item.id;
        elements.priceInput.value = item.price;
        elements.input.value = item.name;

        elements.selectedDisplay.querySelector('.item-selected-image').src = item.image_url;
        elements.selectedDisplay.querySelector('.item-selected-name').textContent = item.name;
        elements.selectedDisplay.querySelector('.item-selected-price').textContent = item.formatted_price;
        elements.selectedDisplay.querySelector('.item-selected-sku').textContent = item.sku ? `SKU: ${item.sku}` : 'Sem SKU';

        elements.selectedDisplay.classList.remove('d-none'); // Mostra o card
        console.log('DEBUG: Card de exibição do item (data-selected-display) agora tem classes:', elements.selectedDisplay.classList);

        elements.clearBtn.classList.add('d-none'); // Esconde o botão de limpar do input (o "X" do input)
        hideDropdown();
        highlightedIndex = -1;

        // Preencher campo de preço automaticamente
        const priceField = document.getElementById('item-price');
        if (priceField) {
            priceField.value = item.price;
        }

        // Evento
        wrapper.dispatchEvent(new CustomEvent('itemSelected', {
            detail: { item: item }
        }));
    }

    // Mostrar/esconder dropdown
    function showDropdown() {
        elements.dropdown.style.display = 'block';
    }

    function hideDropdown() {
        elements.dropdown.style.display = 'none';
    }

    // Clique fora
    document.addEventListener('click', function(e) {
        if (!wrapper.contains(e.target)) {
            hideDropdown();
        }
    });

    // Lógica para pré-preencher se houver um valor inicial
    if (elements.hiddenInput.value) {
        elements.input.value = `ID: ${elements.hiddenInput.value}`; // Placeholder
        elements.selectedDisplay.classList.remove('d-none');
        elements.clearBtn.classList.remove('d-none'); // Mostra o botão de limpar
        console.log('DEBUG: Item pré-selecionado (placeholder).');
    }

    // Lógica do QR Code Scanner
    const qrBtn = wrapper.querySelector('[data-qr-btn="true"]');
    const qrWrap = wrapper.querySelector('[data-qr-reader-wrap="true"]');
    const qrCloseBtn = wrapper.querySelector('[data-qr-close-btn="true"]');
    const qrReaderArea = wrapper.querySelector('.qr-reader-area');
    let html5QrScanner = null;
    let qrReaderId = 'qr-reader-' + Math.random().toString(36).substr(2, 9);
    
    if (qrReaderArea) {
        qrReaderArea.id = qrReaderId;
    }

    if (qrBtn && qrWrap && qrCloseBtn) {
        async function stopQrScanner() {
            if (html5QrScanner && html5QrScanner.isScanning) {
                try {
                    await html5QrScanner.stop();
                } catch (e) {
                    console.error("Erro ao parar scanner:", e);
                }
            }
            qrWrap.classList.add('d-none');
        }

        qrCloseBtn.addEventListener('click', async () => {
            await stopQrScanner();
        });

        qrBtn.addEventListener('click', async () => {
            const isHidden = qrWrap.classList.contains('d-none');
            if (!isHidden) {
                await stopQrScanner();
                return;
            }

            qrWrap.classList.remove('d-none');

            // Carrega a biblioteca se não estiver carregada
            if (typeof Html5Qrcode === 'undefined') {
                const script = document.createElement('script');
                script.src = "https://unpkg.com/html5-qrcode";
                script.onload = () => {
                    startScanning();
                };
                document.head.appendChild(script);
            } else {
                startScanning();
            }
        });

        async function startScanning() {
            if (!html5QrScanner) {
                html5QrScanner = new Html5Qrcode(qrReaderId);
            }

            try {
                await html5QrScanner.start(
                    { facingMode: "environment" },
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 }
                    },
                    async (decodedText) => {
                        console.log("QR Code detectado:", decodedText);
                        await stopQrScanner();
                        
                        // Buscar item
                        try {
                            const response = await fetch(`/api/items/search?q=${encodeURIComponent(decodedText)}`);
                            const data = await response.json();
                            
                            if (data.success && data.data && data.data.length > 0) {
                                // Tentar achar correspondência exata de SKU/codigo ou ID
                                let matchedItem = data.data.find(item => item.sku === decodedText || String(item.id) === decodedText);
                                if (!matchedItem) {
                                    matchedItem = data.data[0];
                                }
                                selectItem(matchedItem);
                                
                                // Focar no campo do cliente
                                setTimeout(() => {
                                    const clientInput = document.querySelector('[data-user-search="true"] .user-search-input');
                                    if (clientInput) {
                                        clientInput.focus();
                                    }
                                }, 200);
                            } else {
                                alert(`Item com código "${decodedText}" não foi encontrado.`);
                            }
                        } catch (err) {
                            console.error("Erro ao buscar item escaneado:", err);
                        }
                    },
                    (errorMessage) => {
                        // Silencioso
                    }
                );
            } catch (err) {
                console.error("Erro ao inicializar câmera:", err);
                alert("Não foi possível acessar a câmera. Verifique as permissões.");
                qrWrap.classList.add('d-none');
            }
        }
    }

    console.log('🎉 Componente de busca de itens pronto!');
});
</script>
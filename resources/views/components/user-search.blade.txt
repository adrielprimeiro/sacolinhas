<div class="user-search-wrapper" data-user-search="true">
    <div class="position-relative">
        <!-- Input de Busca -->
        <div class="input-group">
            <span class="input-group-text">
                <i class="fas fa-search text-muted"></i>
            </span>
            <input 
				type="text" 
				class="form-control user-search-input" 
				placeholder="{{ $placeholder ?? 'Buscar por nome, email ou ID...' }}"
				autocomplete="off"
				autocapitalize="none"
				autocorrect="off"
				spellcheck="false"
				data-form-type="other"
				data-search-input="true"
			>
            <button class="btn btn-outline-secondary user-clear-btn d-none" type="button" data-clear-btn="true">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Dropdown de Sugestões -->
        <div class="user-suggestions-dropdown" data-suggestions="true" style="display: none;">
            <!-- Resultados aparecerão aqui -->
        </div>

        <!-- Campo Hidden -->
        <input type="hidden" class="user-selected-id" name="{{ $name ?? 'user_id' }}" value="{{ $value ?? '' }}" data-hidden-input="true">
    </div>

    <!-- Usuário Selecionado -->
    <div class="user-selected-display mt-2 d-none" data-selected-display="true">
        <div class="card border-success">
            <div class="card-body p-2">
                <div class="d-flex align-items-center">
                    <img class="user-selected-avatar rounded-circle me-2" src="" alt="" width="32" height="32">
                    <div class="flex-grow-1">
                        <div class="user-selected-name fw-bold"></div>
                        <small class="user-selected-email text-muted"></small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger user-remove-btn" data-remove-btn="true">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.user-search-wrapper {
    position: relative;
}

.user-suggestions-dropdown {
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

.user-suggestion-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.15s ease;
}

.user-suggestion-item:hover {
    background-color: #f8f9fa;
}

.user-suggestion-item:last-child {
    border-bottom: none;
}

.user-search-loading,
.user-search-error,
.user-search-no-results {
    padding: 1rem;
    text-align: center;
    color: #6c757d;
}

.user-search-error {
    color: #dc3545;
}
.user-suggestion-item.highlighted,
.item-suggestion-item.highlighted {
    background-color: #e9ecef; /* Cor de destaque */
    border-left: 3px solid #007bff; /* Borda para indicar destaque */
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Inicializando busca de usuários...');

    const wrapper = document.querySelector('[data-user-search="true"]');
    if (!wrapper) {
        console.error('❌ Wrapper de busca de usuário não encontrado');
        return;
    }

    console.log('✅ Wrapper de busca de usuário encontrado');

    const elements = {
        input: wrapper.querySelector('[data-search-input="true"]'),
        dropdown: wrapper.querySelector('[data-suggestions="true"]'),
        hiddenInput: wrapper.querySelector('[data-hidden-input="true"]'),
        clearBtn: wrapper.querySelector('[data-clear-btn="true"]'),
        selectedDisplay: wrapper.querySelector('[data-selected-display="true"]'),
        removeBtn: wrapper.querySelector('[data-remove-btn="true"]')
    };

    // Verificar elementos
    const missing = Object.keys(elements).filter(key => !elements[key]);
    if (missing.length > 0) {
        console.error('❌ Elementos de busca de usuário não encontrados:', missing);
        return;
    }

    console.log('✅ Todos os elementos de busca de usuário encontrados');

    let debounceTimer;
    let highlightedIndex = -1; // Índice do item destacado para navegação por teclado

    // Função para destacar um item na lista
    function highlightItem(index) {
        const items = elements.dropdown.querySelectorAll('.user-suggestion-item');
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
        console.log('DEBUG: clearSelection (User) chamada.');

        // Limpa o input oculto (ID do usuário)
        elements.hiddenInput.value = '';

        // Esconde o card de exibição do usuário selecionado
        elements.selectedDisplay.classList.add('d-none');
        console.log('DEBUG: Card de exibição do usuário (data-selected-display) agora tem classes:', elements.selectedDisplay.classList);

        // Limpa o campo de texto visível
        elements.input.value = '';
        console.log('DEBUG: Campo de busca de usuário (data-search-input) limpo.');

        // Esconde o botão de limpar (o "X" ao lado do campo de busca)
        elements.clearBtn.classList.add('d-none');
        console.log('DEBUG: Botão de limpar (data-clear-btn) escondido.');

        // Esconde o dropdown de sugestões
        hideDropdown();
        console.log('DEBUG: Dropdown de sugestões de usuário escondido.');

        // Reseta o índice de destaque para navegação por teclado
        highlightedIndex = -1;
        console.log('DEBUG: highlightedIndex resetado.');

        // Dispara o evento customizado
        wrapper.dispatchEvent(new CustomEvent('userCleared'));
        console.log('DEBUG: Evento userCleared disparado.');
    }

    // EXPOR A FUNÇÃO clearSelection através do wrapper
    wrapper.clear = clearSelection; // <--- ADICIONE ESTA LINHA

    // Event listener para input
    elements.input.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        console.log('📝 Digitando (User):', query);

        if (query.length > 0) {
            elements.clearBtn.classList.remove('d-none');
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => searchUsers(query), 300);
        } else {
            // Se o campo de busca estiver vazio, limpa tudo
            clearSelection();
        }
    });

    // Event listener para keydown (setas e enter)
    elements.input.addEventListener('keydown', function(e) {
        const items = elements.dropdown.querySelectorAll('.user-suggestion-item');
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
        console.log('DEBUG: Botão de limpar (User) clicado.');
        clearSelection(); // Chama a função que limpa tudo
    });

    // Event listener para o botão de remover (no card de seleção)
    elements.removeBtn.addEventListener('click', function() {
        console.log('DEBUG: Botão de remover (User) do card clicado.');
        clearSelection(); // Chama a função que limpa tudo
    });

    // Buscar usuários
    async function searchUsers(query) {
        console.log('🔎 Buscando usuários:', query);
        showLoading();

        try {
            const response = await fetch(`/api/users/search?q=${encodeURIComponent(query)}&role=client`);
            const data = await response.json();

            console.log('📦 Dados de usuários recebidos:', data);
            if (data.success && data.data) {
                displaySuggestions(data.data);
            } else {
                showNoResults();
            }
        } catch (error) {
            console.error('💥 Erro na busca de usuários:', error);
            showError();
        }
    }

    // Mostrar loading
    function showLoading() {
        elements.dropdown.innerHTML = `
            <div class="user-search-loading">
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <div class="mt-2">Buscando...</div>
            </div>
        `;
        showDropdown();
        highlightedIndex = -1;
    }

    // Mostrar sugestões
    function displaySuggestions(users) {
        console.log(`📋 ${users.length} usuários encontrados`);

        if (users.length === 0) {
            showNoResults();
            return;
        }

        elements.dropdown.innerHTML = '';

        users.forEach(user => {
            const item = document.createElement('div');
            item.className = 'user-suggestion-item';
            item.innerHTML = `
                <div class="d-flex align-items-center">
                    <img src="${user.avatar_url}" class="rounded-circle me-2" width="32" height="32">
                    <div class="flex-grow-1">
                        <div class="fw-bold">${user.name}</div>
                        <small class="text-muted">${user.email}</small>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">ID: ${user.id}</small>
                    </div>
                </div>
            `;

            item.addEventListener('click', () => selectUser(user));
            elements.dropdown.appendChild(item);
        });

        showDropdown();
        highlightedIndex = -1;
    }

    // Sem resultados
    function showNoResults() {
        elements.dropdown.innerHTML = `
            <div class="user-search-no-results">
                <i class="fas fa-user-slash fa-2x mb-2"></i>
                <div>Nenhum usuário encontrado</div>
            </div>
        `;
        showDropdown();
        highlightedIndex = -1;
    }

    // Erro
    function showError() {
        elements.dropdown.innerHTML = `
            <div class="user-search-error">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                <div>Erro ao buscar</div>
            </div>
        `;
        showDropdown();
        highlightedIndex = -1;
    }

    // Selecionar usuário
    function selectUser(user) {
        console.log('�� Selecionado (User):', user.name);

        elements.hiddenInput.value = user.id;
        elements.input.value = user.name;

        elements.selectedDisplay.querySelector('.user-selected-avatar').src = user.avatar_url;
        elements.selectedDisplay.querySelector('.user-selected-name').textContent = user.name;
        elements.selectedDisplay.querySelector('.user-selected-email').textContent = user.email;

        elements.selectedDisplay.classList.remove('d-none'); // Mostra o card
        console.log('DEBUG: Card de exibição do usuário (data-selected-display) agora tem classes:', elements.selectedDisplay.classList);

        elements.clearBtn.classList.add('d-none'); // Esconde o botão de limpar do input (o "X" do input)
        hideDropdown();
        highlightedIndex = -1;

        // Evento
        wrapper.dispatchEvent(new CustomEvent('userSelected', {
            detail: { user: user }
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
        console.log('DEBUG: Usuário pré-selecionado (placeholder).');
    }

    console.log('🎉 Componente de busca de usuário pronto!');
});
</script>


<div class="user-search-wrapper" data-user-search="true">
    <div class="position-relative">
        <input type="text" 
               class="form-control user-search-input" 
               placeholder="Buscar por nome, apelido, Instagram (@), TikTok, email ou ID..."
               autocomplete="off"
               data-search-input="true">
        
        <div class="search-icon">
            <i class="fas fa-search text-muted"></i>
        </div>
        
        <button class="btn btn-outline-secondary user-clear-btn d-none" type="button" data-clear-btn="true">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <input type="hidden" 
           class="user-selected-id" 
           name="{{ $name ?? 'client_id' }}"    
           value="{{ $value ?? '' }}" 
           data-hidden-input="true">

    <div class="user-suggestions-dropdown" data-suggestions="true" style="display: none;">
    </div>

    <div class="user-selected-display mt-2 d-none" data-selected-display="true">
        <div class="card">
            <div class="card-body p-2">
                <div class="d-flex align-items-center">
                    <img class="user-avatar me-2" src="" alt="Avatar" width="32" height="32" style="border-radius: 50%;">
                    <div class="flex-grow-1">
                        <div class="user-name fw-semibold"></div>
                        <!-- MODIFICADO: Mostrar Instagram em vez de email -->
                        <div class="user-instagram text-muted small"></div>
                        <!-- Área para mostrar plataformas -->
                        <div class="user-platforms mt-1"></div>
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

.user-search-wrapper .search-icon { 
    position: absolute; right: 10px; top: 50%; 
    transform: translateY(-50%); pointer-events: none; z-index: 2; 
}
.user-search-wrapper .user-clear-btn { 
    position: absolute; right: 8px; top: 50%; 
    transform: translateY(-50%); border: none; background: transparent; 
    padding: 2px 6px; font-size: 12px; z-index: 3; 
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

.usr-search-error {
    color: #dc3545;
}

.user-suggestion-item.highlighted {
    background-color: #e9ecef; /* Cor de destaque */
    border-left: 3px solid #007bff; /* Borda para indicar destaque */
}

/* Estilos para badges das plataformas */
.bg-instagram {
    background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%) !important;
    color: white !important;
}
.bg-tiktok {
    background-color: #000000 !important;
    color: white !important;
}
.platform-badge {
    font-size: 0.7em;
    padding: 2px 6px;
    margin-right: 4px;
}

/* NOVO: Estilo para destacar Instagram na lista */
.instagram-handle {
    color: #e1306c;
    font-weight: 500;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando busca de usuários...');
    
    var wrapper = document.querySelector('[data-user-search="true"]');
    var searchInput = wrapper.querySelector('[data-search-input="true"]');
    var hiddenInput = wrapper.querySelector('[data-hidden-input="true"]');
    var suggestionsDropdown = wrapper.querySelector('[data-suggestions="true"]');
    var selectedDisplay = wrapper.querySelector('[data-selected-display="true"]');
    var clearBtn = wrapper.querySelector('[data-clear-btn="true"]');
    var removeBtn = wrapper.querySelector('[data-remove-btn="true"]');

    var debounceTimer;
    var currentSearch = '';
    var selectedIndex = -1;
    var currentUsers = [];

    function searchUsers() {
        var query = searchInput.value.trim();
        currentSearch = query;
        
        if (!query) {
            suggestionsDropdown.style.display = 'none';
            return;
        }

        console.log('Buscando por: ' + query);
        
        fetch('/users/search?q=' + encodeURIComponent(query) + '&role=client')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            console.log('Resultado da busca:', data);
            
            if (data.success) {
                if (data.data && data.data.length > 0) {
                    showUsers(data.data);
                } else {
                    showNoResults();
                }
            } else {
                showError();
            }
        })
        .catch(function(error) {
            console.error('Erro na busca:', error);
            showError();
        });
    }

    function showUsers(users) {
        console.log('Mostrando usuários:', users);
        currentUsers = users;
        suggestionsDropdown.innerHTML = '';
        resetSelection();
        
        for (var i = 0; i < users.length; i++) {
            var user = users[i];
            var item = document.createElement('div');
            item.className = 'user-suggestion-item';
            item.dataset.index = i;
            
            var avatarSrc = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.name) + '&size=32';
            
            // MODIFICADO: Mostrar Instagram em vez de email na segunda linha
            var instagramDisplay = '';
            if (user.remember_token) {
                instagramDisplay = '<span class="instagram-handle"><i class="fab fa-instagram"></i> @' + user.remember_token + '</span>';
            } else {
                instagramDisplay = '<span class="text-muted">Sem Instagram</span>';
            }
            
            // Incluir outras plataformas na terceira linha (mais compacto)
            var otherPlatformsHtml = '';
            if (user.nome_cliente) {
                otherPlatformsHtml += '<span class="badge bg-tiktok platform-badge"><i class="fab fa-tiktok"></i> @' + user.nome_cliente + '</span>';
            }
            if (user.apelido) {
                otherPlatformsHtml += '<span class="badge bg-secondary platform-badge">' + user.apelido + '</span>';
            }
            
            item.innerHTML = '<div class="d-flex align-items-center">' +
                '<img src="' + avatarSrc + '" alt="Avatar" width="32" height="32" style="border-radius: 50%; margin-right: 10px;">' +
                '<div class="flex-grow-1">' +
                    '<div class="fw-semibold">' + user.name + '</div>' +
                    '<div class="small">' + instagramDisplay + '</div>' +
                    (otherPlatformsHtml ? '<div class="mt-1">' + otherPlatformsHtml + '</div>' : '') +
                '</div>' +
                '<div class="text-muted small">' + user.id + '</div>' +
            '</div>';
            
            item.onclick = function(userData) {
                return function() { selectUser(userData); };
            }(user);
            
            suggestionsDropdown.appendChild(item);
        }
        
        suggestionsDropdown.style.display = 'block';
    }

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

    function showError() {
        suggestionsDropdown.innerHTML = '<div class="text-center py-3 text-danger">' +
            'Erro ao buscar usuários</div>';
        suggestionsDropdown.style.display = 'block';
    }

    function selectUser(user) {
        console.log('=== SELECIONANDO USUÁRIO ===');
        console.log('User data:', user);
        
        hiddenInput.value = user.id;
        searchInput.value = user.name;
        
        // Atualizar display
        const avatar = selectedDisplay.querySelector('.user-avatar');
        const name = selectedDisplay.querySelector('.user-name');
        const instagram = selectedDisplay.querySelector('.user-instagram'); // MODIFICADO
        const platforms = selectedDisplay.querySelector('.user-platforms');
        
        if (avatar && name && instagram && platforms) {
            var avatarSrc = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.name) + '&size=32';
            if (user.avatar_url && user.avatar_url !== null && user.avatar_url !== '') {
                avatarSrc = user.avatar_url;
            }
            
            avatar.src = avatarSrc;
            name.textContent = user.name;
            
            // MODIFICADO: Mostrar Instagram em vez de email
            if (user.remember_token) {
                instagram.innerHTML = '<i class="fab fa-instagram"></i> @' + user.remember_token;
                instagram.className = 'instagram-handle small';
            } else {
                instagram.textContent = 'Sem Instagram';
                instagram.className = 'text-muted small';
            }
            
            // Mostrar outras plataformas
            var platformsHtml = '';
            if (user.nome_cliente) {
                platformsHtml += '<span class="badge bg-tiktok platform-badge"><i class="fab fa-tiktok"></i> @' + user.nome_cliente + '</span>';
            }
            if (user.apelido) {
                platformsHtml += '<span class="badge bg-secondary platform-badge">' + user.apelido + '</span>';
            }
            platforms.innerHTML = platformsHtml;
            
            selectedDisplay.classList.remove('d-none');
            suggestionsDropdown.style.display = 'none';
            clearBtn.classList.remove('d-none');
            
            console.log('Usuário selecionado com sucesso!');
            
            // MODIFICADO: Focar no botão de adicionar em vez de mover para próximo campo
            setTimeout(function() {
                const addButton = document.querySelector('#add-item-form button[type="submit"]');
                if (addButton) {
                    console.log('🎯 Focando no botão adicionar...');
                    addButton.focus();
                }
            }, 200);
        }
        
        console.log('=== FIM SELEÇÃO ===');
    }

    function clearSelection() {
        hiddenInput.value = '';
        searchInput.value = '';
        selectedDisplay.classList.add('d-none');
        clearBtn.classList.add('d-none');
        suggestionsDropdown.style.display = 'none';
        resetSelection();
    }

    function createNewClient() {
        console.log('Criando novo cliente:', currentSearch);
        
        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch('/users/quick-create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ name: currentSearch })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                selectUser(data.user);
            } else {
                alert('Erro ao criar cliente');
            }
        })
        .catch(function(error) {
            console.error('Erro:', error);
            alert('Erro de comunicação');
        });
    }

    // Event listeners
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(searchUsers, 300);
        
        if (this.value.trim()) {
            clearBtn.classList.remove('d-none');
        } else {
            clearBtn.classList.add('d-none');
        }
    });

    clearBtn.addEventListener('click', clearSelection);
    removeBtn.addEventListener('click', clearSelection);

    document.addEventListener('click', function(e) {
        if (!wrapper.contains(e.target)) {
            suggestionsDropdown.style.display = 'none';
        }
    });

    // Navegação por teclado com scroll automático
    searchInput.addEventListener('keydown', function(e) {
        var items = suggestionsDropdown.querySelectorAll('.user-suggestion-item, .create-client-item');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
            updateHighlight(items);
            scrollToHighlighted(items[selectedIndex]);
        } 
        else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, -1);
            updateHighlight(items);
            if (selectedIndex >= 0) {
                scrollToHighlighted(items[selectedIndex]);
            }
        } 
        else if (e.key === 'Enter' && selectedIndex >= 0) {
            e.preventDefault();
            if (items[selectedIndex].classList.contains('create-client-item')) {
                createNewClient();
            } else {
                var userIndex = parseInt(items[selectedIndex].dataset.index);
                if (currentUsers[userIndex]) {
                    selectUser(currentUsers[userIndex]);
                }
            }
        }
        else if (e.key === 'Escape') {
            suggestionsDropdown.style.display = 'none';
            selectedIndex = -1;
        }
    });

    function updateHighlight(items) {
        for (var i = 0; i < items.length; i++) {
            items[i].classList.remove('highlighted');
        }
        
        if (selectedIndex >= 0 && selectedIndex < items.length) {
            items[selectedIndex].classList.add('highlighted');
        }
    }

    function scrollToHighlighted(item) {
        if (!item) return;
        
        var container = suggestionsDropdown;
        var containerTop = container.scrollTop;
        var containerBottom = containerTop + container.clientHeight;
        var itemTop = item.offsetTop;
        var itemBottom = itemTop + item.offsetHeight;
        
        if (itemTop < containerTop) {
            container.scrollTop = itemTop;
        } else if (itemBottom > containerBottom) {
            container.scrollTop = itemBottom - container.clientHeight;
        }
    }

    function resetSelection() {
        selectedIndex = -1;
    }

    // MODIFICADO: Focar no campo de item após adicionar à sacola
    document.addEventListener('itemAddedToCart', function() {
        console.log('🛍️ Item adicionado à sacola - limpando seleção e focando no campo item');
        clearSelection();
        
        // Focar no campo de busca de item
        setTimeout(function() {
            const itemSearchInput = document.querySelector('[data-search-input="true"]:not(.user-search-input)');
            if (itemSearchInput) {
                console.log('🎯 Focando no campo de item...');
                itemSearchInput.focus();
                itemSearchInput.select(); // Selecionar todo o texto se houver
            }
        }, 300);
    });

    // Observer para detectar quando item é adicionado
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                var bagsContainer = document.getElementById('bags-list');
                if (bagsContainer && mutation.target === bagsContainer) {
                    // Sacola foi atualizada - limpar seleção e focar no campo item
                    setTimeout(function() {
                        if (hiddenInput.value) {
                            console.log('🛍️ Sacola atualizada - limpando e focando no item');
                            clearSelection();
                            
                            // MODIFICADO: Focar no campo de item em vez de cliente
                            const itemSearchInput = document.querySelector('[data-search-input="true"]:not(.user-search-input)');
                            if (itemSearchInput) {
                                console.log('🎯 Focando no campo de item...');
                                itemSearchInput.focus();
                                itemSearchInput.select();
                            }
                        }
                    }, 500);
                }
            }
        });
    });
    
    // Observar mudanças no container de sacolas
    var bagsContainer = document.getElementById('bags-list');
    if (bagsContainer) {
        observer.observe(bagsContainer, { childList: true, subtree: true });
    }

    console.log('Componente carregado!');
});
</script>
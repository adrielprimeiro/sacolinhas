<div class="user-search-wrapper" data-user-search="true">
    <div class="position-relative">
        <input type="text" 
               class="form-control user-search-input" 
               placeholder="Buscar por nome, email ou ID..." 
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
                        <div class="user-email text-muted small"></div>
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
.user-search-wrapper { position: relative; }
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
    position: absolute; top: 100%; left: 0; right: 0; 
    background: white; border: 1px solid #ddd; border-radius: 0.375rem; 
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); z-index: 1000; 
    max-height: 300px; overflow-y: auto; 
}
.user-suggestion-item { 
    padding: 10px 15px; cursor: pointer; 
    border-bottom: 1px solid #f0f0f0; transition: background-color 0.2s; 
}
.user-suggestion-item:hover { background-color: #f8f9fa; }
.create-client-item { 
    padding: 10px 15px; cursor: pointer; 
    background-color: #e3f2fd; border-left: 4px solid #2196f3; 
}
.create-client-item:hover { background-color: #bbdefb; 
}
.user-suggestion-item.highlighted,
.create-client-item.highlighted {
    background-color: #007bff !important;
    color: white !important;
}

.user-suggestion-item.highlighted .text-muted,
.create-client-item.highlighted .text-muted {
    color: rgba(255, 255, 255, 0.8) !important;
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
		suggestionsDropdown.innerHTML = '';
		resetSelection(); // ADICIONADO
		
		for (var i = 0; i < users.length; i++) {
			var user = users[i];
			var item = document.createElement('div');
			item.className = 'user-suggestion-item';
			
			var avatarSrc = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.name) + '&size=32';
			
			item.innerHTML = '<div class="d-flex align-items-center">' +
				'<img src="' + avatarSrc + '" alt="Avatar" width="32" height="32" style="border-radius: 50%; margin-right: 10px;">' +
				'<div class="flex-grow-1">' +
					'<div class="fw-semibold">' + user.name + '</div>' +
					'<div class="text-muted small">' + user.email + '</div>' +
				'</div>' +
				'<div class="text-muted small">#' + user.id + '</div>' +
			'</div>';
			
			item.onclick = function(userData) {
				return function() { selectUser(userData); };
			}(user);
			
			suggestionsDropdown.appendChild(item);
		}
		
		suggestionsDropdown.style.display = 'block';
	}
    

	function showNoResults() {
		resetSelection(); // ADICIONADO
		
		suggestionsDropdown.innerHTML = '<div class="text-center py-3">' +
			'<span>Nenhum usuário encontrado</span></div>' +
			'<div class="create-client-item" onclick="createNewClient()">' +
				'<div class="d-flex align-items-center">' +
					'<i class="fas fa-plus-circle" style="color: #2196f3; margin-right: 8px;"></i>' +
					'<div><div class="fw-semibold">Cadastrar Novo Cliente</div></div>' +
				'</div>' +
			'</div>';
		
		suggestionsDropdown.style.display = 'block';
	}

    function showError() {
        suggestionsDropdown.innerHTML = '<div class="text-center py-3 text-danger">' +
            'Erro ao buscar usuários</div>';
        suggestionsDropdown.style.display = 'block';
    }

	// SUBSTITUIR função selectUser completa:
	function selectUser(user) {
		console.log('=== SELECIONANDO USUÁRIO ===');
		console.log('User data:', user);
		
		hiddenInput.value = user.id;
		searchInput.value = user.name;
		
		// Atualizar display
		const avatar = selectedDisplay.querySelector('.user-avatar');
		const name = selectedDisplay.querySelector('.user-name');
		const email = selectedDisplay.querySelector('.user-email');
		
		if (avatar && name && email) {
			var avatarSrc = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.name) + '&size=32';
			if (user.avatar_url && user.avatar_url !== null && user.avatar_url !== '') {
				avatarSrc = user.avatar_url;
			}
			
			avatar.src = avatarSrc;
			name.textContent = user.name;
			email.textContent = user.email;
			
			selectedDisplay.classList.remove('d-none');
			suggestionsDropdown.style.display = 'none';
			clearBtn.classList.remove('d-none');
			
			console.log('Usuário selecionado com sucesso!');
			
			// 🎯 ADICIONAR ESTA LINHA NO FINAL:
			setTimeout(function() {
				const itemInput = document.querySelector('[data-item-search="true"] [data-search-input="true"]');
				if (itemInput) {
					console.log('🎯 Focando no campo de item...');
					itemInput.focus();
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
    }

    window.createNewClient = function() {
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
    };

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
	var selectedIndex = -1; // Controlar qual item está selecionado

	// Navegação por teclado
	searchInput.addEventListener('keydown', function(e) {
		var items = suggestionsDropdown.querySelectorAll('.user-suggestion-item, .create-client-item');
		
		if (e.key === 'ArrowDown') {
			e.preventDefault();
			selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
			updateHighlight(items);
		} 
		else if (e.key === 'ArrowUp') {
			e.preventDefault();
			selectedIndex = Math.max(selectedIndex - 1, -1);
			updateHighlight(items);
		} 
		else if (e.key === 'Enter' && selectedIndex >= 0) {
			e.preventDefault();
			items[selectedIndex].click();
		}
		else if (e.key === 'Escape') {
			suggestionsDropdown.style.display = 'none';
			selectedIndex = -1;
		}
	});

	// Função para destacar item selecionado
	function updateHighlight(items) {
		for (var i = 0; i < items.length; i++) {
			if (i === selectedIndex) {
				items[i].classList.add('highlighted');
			} else {
				items[i].classList.remove('highlighted');
			}
		}
	}

	// Resetar seleção quando mostrar novos resultados
	function resetSelection() {
		selectedIndex = -1;
	}
	
	// Escutar evento personalizado de limpeza
	wrapper.addEventListener('userCleared', function(e) {
		console.log('🧹 Evento userCleared recebido - limpando seleção');
		clearSelection();
	});

	// Também escutar evento global de item adicionado à sacola
	document.addEventListener('bagItemAdded', function(e) {
		console.log('🎯 Item adicionado à sacola - limpando cliente automaticamente');
		clearSelection();
	});	

	// Função global para limpar de fora
	window.clearUserSelectionManual = function() {
		console.log('🧹 clearUserSelectionManual chamada');
		
		// Limpar campos
		searchInput.value = '';
		hiddenInput.value = '';
		
		// Esconder elementos
		selectedDisplay.classList.add('d-none');
		clearBtn.classList.add('d-none');
		suggestionsDropdown.style.display = 'none';
		
		console.log('✅ Cliente limpo manualmente');
	};

	// Função para focar item
	window.focusItemField = function() {
		setTimeout(function() {
			const itemInput = document.querySelector('[data-item-search="true"] [data-search-input="true"]');
			if (itemInput) {
				console.log('🎯 Focando campo item...');
				itemInput.focus();
				itemInput.click();
			}
		}, 300);
	};
	
    console.log('Componente carregado!');
});
</script>
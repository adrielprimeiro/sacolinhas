<div class="user-search-wrapper" data-user-search="true">
    <div class="input-group">
        <span class="input-group-text search-icon">
            <i class="fas fa-search"></i>
        </span>
        <input type="text" 
               class="form-control user-search-input" 
               placeholder="Buscar cliente por nome, @instagram, @tiktok, email ou ID"
               autocomplete="off"
               spellcheck="false">
        <button class="btn btn-outline-secondary user-clear-btn" type="button" style="display: none;">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <input type="hidden" name="{{ $name ?? 'client_id' }}" class="user-selected-id">
    <div class="user-suggestions-dropdown" style="display: none;"></div>
    <div class="user-selected-display" style="display: none;"></div>
</div>

<style>
    .user-search-wrapper {
        position: relative;
        margin-bottom: 1rem;
    }
    /* Fallback CSS for non-Bootstrap contexts */
    .user-search-wrapper .input-group {
        display: flex;
        width: 100%;
    }
    .user-search-wrapper .input-group .user-search-input {
        flex-grow: 1;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        outline: none;
        font-size: 0.875rem;
        background-color: #fff;
    }
    .user-search-wrapper .input-group .input-group-text {
        display: flex;
        align-items: center;
        padding: 0.5rem 0.75rem;
        background-color: #f3f4f6;
        border: 1px solid #d1d5db;
        border-right: none;
        border-radius: 0.375rem 0 0 0.375rem;
    }
    .user-search-wrapper .input-group .input-group-text + .user-search-input {
        border-radius: 0 0.375rem 0.375rem 0;
    }
    .user-search-wrapper .input-group .user-search-input:not(:last-child) {
        border-radius: 0;
    }
    .user-search-wrapper .input-group .user-clear-btn {
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
    .user-search-wrapper .input-group .user-clear-btn:hover {
        background-color: #f3f4f6;
    }

    .user-suggestions-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-top: none;
        border-radius: 0 0 8px 8px;
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .suggestion-item {
        padding: 12px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .suggestion-item:hover {
        background-color: #f8f9fa;
    }

    .badge-instagram {
        background: linear-gradient(45deg, #f58529, #dd2a7b, #8134af, #515bd4) !important;
        color: white;
    }

    .badge-tiktok {
        background: #000 !important;
        color: white;
    }
</style>

<script>
if (typeof window.initUserSearch === 'undefined') {
    window.initUserSearch = function() {
        const wrapper = document.querySelector('[data-user-search="true"]');
        if (!wrapper) return;

        const searchInput = wrapper.querySelector('.user-search-input');
        const clearBtn = wrapper.querySelector('.user-clear-btn');
        const hiddenInput = wrapper.querySelector('.user-selected-id');
        const dropdown = wrapper.querySelector('.user-suggestions-dropdown');
        const display = wrapper.querySelector('.user-selected-display');

        let debounceTimer = null;
        let selectedIndex = -1;

        // ✅ FUNÇÃO: Obter Instagram
        function getInstagram(user) {
            return user.instagram || user.remember_token || '';
        }

        // ✅ FUNÇÃO: Obter TikTok
        function getTikTok(user) {
            return user.tiktok || user.nome_cliente || '';
        }

        // ✅ FUNÇÃO: Obter WhatsApp
        function getWhatsApp(user) {
            return user.whatsapp || user.phone || '';
        }

        // ✨ FUNÇÃO: Destacar item na navegação
        function highlightItem(items, index) {
            items.forEach((item, i) => {
                if (i === index) {
                    item.style.backgroundColor = '#e9ecef';
                    item.style.fontWeight = 'bold';
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.style.backgroundColor = '';
                    item.style.fontWeight = 'normal';
                }
            });
        }

        // Buscar clientes
        function searchUsers() {
            const q = searchInput.value.trim();
            if (q.length < 2) {
                dropdown.style.display = 'none';
                return;
            }

            dropdown.innerHTML = '<div class="p-3 text-center"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';
            dropdown.style.display = 'block';

            fetch(`/users/search?q=${encodeURIComponent(q)}&role=client`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        dropdown.innerHTML = '';
                        data.data.forEach(user => {
                            const div = document.createElement('div');
                            div.className = 'suggestion-item';
                            
                            const instagram = getInstagram(user);
                            const tiktok = getTikTok(user);
                            
                            let badges = '';
                            if (instagram) {
                                badges += `<span class="badge badge-instagram ms-1">@${instagram}</span>`;
                            }
                            if (tiktok) {
                                badges += `<span class="badge badge-tiktok ms-1">@${tiktok}</span>`;
                            }
                            if (user.apelido) {
                                badges += `<span class="badge bg-secondary ms-1">${user.apelido}</span>`;
                            }
                            
                           
                            div.innerHTML = `
                                <div class="fw-bold">${user.name}</div>
                                <div>${badges}</div>
                            `;
                            
                            div.onclick = () => selectUser(user);
                            dropdown.appendChild(div);
                        });
                    } else {
                        dropdown.innerHTML = '<div class="p-3 text-center text-muted">Nenhum cliente encontrado</div>';
                    }
                })
                .catch(e => {
                    console.error('Erro:', e);
                    dropdown.innerHTML = '<div class="p-3 text-center text-danger">Erro na busca</div>';
                });
        }

        // Selecionar cliente
        function selectUser(user) {
            hiddenInput.value = user.id;
            searchInput.value = user.name;
            
            const instagram = getInstagram(user);
            const tiktok = getTikTok(user);
            
            let badges = '';
            if (instagram) {
                badges += `<span class="badge badge-instagram ms-1">@${instagram}</span>`;
            }
            if (tiktok) {
                badges += `<span class="badge badge-tiktok ms-1">@${tiktok}</span>`;
            }
            if (user.apelido) {
                badges += `<span class="badge bg-secondary ms-1">${user.apelido}</span>`;
            }
            
            display.innerHTML = `
                <div class="card border-success mt-2">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold">${user.name}</div>
                                <div>${badges}</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            display.querySelector('button').onclick = clearSelection;
            display.style.display = 'block';
            clearBtn.style.display = 'inline-block';
            dropdown.style.display = 'none';
            
            const event = new CustomEvent('userSelected', {
                detail: { user: user }
            });
            wrapper.dispatchEvent(event);
            console.log('📢 Evento userSelected disparado!', user);
        }

        // Limpar seleção
        function clearSelection() {
            hiddenInput.value = '';
            searchInput.value = '';
            display.style.display = 'none';
            clearBtn.style.display = 'none';
        }

        // ========== EVENT LISTENERS ==========
        
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(searchUsers, 300);
            clearBtn.style.display = this.value ? 'inline-block' : 'none';
            selectedIndex = -1;
        });

        clearBtn.addEventListener('click', clearSelection);

        // ✨ Navegação com setas do teclado
        searchInput.addEventListener('keydown', function(e) {
            console.log('🔑 Tecla pressionada:', e.key);
            
            const items = dropdown.querySelectorAll('.suggestion-item');
            console.log('📋 Total de itens:', items.length);
            
            if (items.length === 0) return;
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                highlightItem(items, selectedIndex);
                console.log('⬇️ Índice:', selectedIndex);
            } 
            else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                highlightItem(items, selectedIndex);
                console.log('⬆️ Índice:', selectedIndex);
            } 
            else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedIndex >= 0 && items[selectedIndex]) {
                    items[selectedIndex].click();
                    console.log('✅ Clicado item:', selectedIndex);
                }
            } 
            else if (e.key === 'Escape') {
                dropdown.style.display = 'none';
                selectedIndex = -1;
            }
        });

        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                dropdown.style.display = 'none';
                selectedIndex = -1;
            }
        });
    };

    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.initUserSearch);
    } else {
        window.initUserSearch();
    }
}
</script>
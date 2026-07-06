<div class="user-search-wrapper" data-user-search="true">
    <div class="input-group">
        <span class="input-group-text search-icon">
            <i class="fas fa-search"></i>
        </span>
        <input type="text" 
               class="form-control user-search-input" 
               placeholder="{{ $placeholder ?? 'Digite nome ou e-mail...' }}"
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
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        max-height: 350px;
        overflow-y: auto;
        z-index: 9999;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        margin-top: 4px;
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

    /* Custom selected card layout (standardized) */
    .search-selected-card {
        background-color: #f0fdf4 !important; /* bg-green-50 */
        border: 1px solid #bbf7d0 !important; /* border-green-200 */
        border-radius: 0.75rem !important; /* rounded-xl */
        padding: 0.75rem !important; /* p-3 */
        display: flex !important; /* flex */
        align-items: center !important; /* items-center */
        justify-content: space-between; /* justify-between */
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important; /* shadow-sm */
        margin-top: 0.5rem !important; /* mt-2 */
    }
    
    .search-selected-card-info {
        display: flex !important;
        align-items: center !important;
        gap: 0.75rem !important;
    }
    
    .search-selected-card-title {
        font-weight: 700 !important;
        color: #1f2937 !important; /* text-gray-800 */
        font-size: 0.875rem !important; /* text-sm */
        margin: 0 !important;
    }
    
    .search-selected-card-subtitle {
        font-size: 0.75rem !important; /* text-xs */
        color: #6b7280 !important; /* text-gray-500 */
        margin-top: 0.25rem !important;
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 0.375rem !important;
        align-items: center !important;
    }
    
    .search-selected-card-remove {
        color: #ef4444 !important; /* text-red-500 */
        background: transparent !important;
        border: none !important;
        cursor: pointer !important;
        padding: 0.25rem !important;
        font-size: 1rem !important;
        line-height: 1 !important;
        transition: color 0.2s !important;
    }
    
    .search-selected-card-remove:hover {
        color: #b91c1c !important; /* text-red-700 */
    }

    /* Custom badge styles */
    .custom-badge {
        display: inline-flex !important;
        align-items: center !important;
        padding: 0.125rem 0.375rem !important;
        font-size: 0.6875rem !important;
        font-weight: 700 !important;
        border-radius: 0.25rem !important;
        line-height: 1 !important;
        color: #ffffff !important;
        margin-left: 0.25rem !important;
    }
    
    .custom-badge-instagram {
        background: linear-gradient(45deg, #f58529, #dd2a7b, #8134af, #515bd4) !important;
    }
    
    .custom-badge-tiktok {
        background: #000000 !important;
    }
    
    .custom-badge-whatsapp {
        background: #25d366 !important;
    }
    
    .custom-badge-secondary {
        background: #6b7280 !important;
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

            fetch(`/api/users/search?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        dropdown.innerHTML = '';
                        data.data.forEach(user => {
                            const div = document.createElement('div');
                            div.className = 'suggestion-item';
                            
                            const instagram = getInstagram(user);
                            const tiktok = getTikTok(user);
                            const whatsapp = getWhatsApp(user);
                            
                            let badges = '';
                            if (instagram) {
                                badges += `<span class="custom-badge custom-badge-instagram">@${instagram}</span>`;
                            }
                            if (tiktok) {
                                badges += `<span class="custom-badge custom-badge-tiktok">@${tiktok}</span>`;
                            }
                            if (whatsapp) {
                                badges += `<span class="custom-badge custom-badge-whatsapp"><i class="fab fa-whatsapp me-1"></i>${whatsapp}</span>`;
                            }
                            if (user.apelido) {
                                badges += `<span class="custom-badge custom-badge-secondary">${user.apelido}</span>`;
                            }
                            
                            div.innerHTML = `
                                <div class="fw-bold">${user.name}</div>
                                <div style="margin-top: 4px; display: flex; flex-wrap: wrap; gap: 4px;">${badges}</div>
                            `;
                            
                            div.onclick = () => selectUser(user);
                            dropdown.appendChild(div);
                        });

                        // Selecionar o primeiro cliente como padrão
                        selectedIndex = 0;
                        const items = dropdown.querySelectorAll('.suggestion-item');
                        if (items.length > 0) {
                            highlightItem(items, 0);
                        }
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
            const whatsapp = getWhatsApp(user);
            
            let badges = '';
            if (instagram) {
                badges += `<span class="custom-badge custom-badge-instagram">@${instagram}</span>`;
            }
            if (tiktok) {
                badges += `<span class="custom-badge custom-badge-tiktok">@${tiktok}</span>`;
            }
            if (whatsapp) {
                badges += `<span class="custom-badge custom-badge-whatsapp"><i class="fab fa-whatsapp me-1"></i>${whatsapp}</span>`;
            }
            if (user.apelido) {
                badges += `<span class="custom-badge custom-badge-secondary">${user.apelido}</span>`;
            }
            
            display.innerHTML = `
                <div class="search-selected-card">
                    <div class="search-selected-card-info">
                        <div class="rounded-circle text-white flex items-center justify-center font-bold" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; background-color: #10b981; border-radius: 50%;">
                            ${user.name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h4 class="search-selected-card-title">${user.name}</h4>
                            <div class="search-selected-card-subtitle">
                                ${badges || '<span class="text-muted" style="font-size:0.75rem;">Sem redes sociais</span>'}
                            </div>
                        </div>
                    </div>
                    <button type="button" class="search-selected-card-remove" title="Remover cliente">
                        <i class="fas fa-times"></i>
                    </button>
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
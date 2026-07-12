@extends('layouts.app')

@section('title', 'Gerenciar Sacolinhas - Da Live')
@section('brand_route', 'admin.sacolinhas.index')
@section('brand_icon', 'fas fa-broadcast-tower')

@section('content')
<div class="space-y-6">
    {{-- Cabeçalho da Página --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex justify-between items-center flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-black text-gray-800 flex items-center gap-2">
                <i class="fas fa-shopping-bag text-blue-600"></i> Gerenciar Sacolinhas - Da Live
            </h1>
            <p class="text-sm text-gray-500 mt-1">Visualize e altere o status de itens comprados por live e envie notificações para o Portal do Cliente.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="carregarTodasAsLives()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold hover:bg-gray-50 transition flex items-center gap-1">
                <i class="fas fa-sync-alt"></i> Recarregar
            </button>
            <button onclick="mostrarTodasAsLives()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition flex items-center gap-1">
                <i class="fas fa-list"></i> Ver Todas (<span id="total-lives-count">0</span>)
            </button>
        </div>
    </div>

    {{-- Layout em Duas Colunas --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- Coluna Esquerda: Listagem de Lives --}}
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fas fa-broadcast-tower text-gray-400"></i> Últimas Lives
                </h3>

                {{-- Filtro de Lives --}}
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Filtrar Lives</label>
                    <input type="text" id="search-live" oninput="filtrarLives()" placeholder="Tipo de live, plataforma, ID..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                {{-- Tabela de Lives --}}
                <div class="overflow-x-auto border border-gray-200 rounded-lg max-h-[400px] overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-250">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tipo</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Plataformas</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="lives-table-body" class="bg-white divide-y divide-gray-150">
                            <tr>
                                <td colspan="5" class="text-center py-6 text-gray-500 text-sm">
                                    <i class="fas fa-spinner fa-spin mr-1"></i> Carregando lives...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Coluna Direita: Sacolas da Live Selecionada --}}
        <div class="lg:col-span-7 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                <div class="flex justify-between items-center border-b border-gray-100 pb-3 flex-wrap gap-2">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                        <i class="fas fa-shopping-bag text-gray-400"></i> Sacolas da Live Selecionada <span id="selected-live-info" class="text-blue-600 font-extrabold ml-1"></span>
                    </h3>
                    {{-- Filtro de Clientes --}}
                    <div class="w-full sm:w-64">
                        <input type="text" id="search-client" oninput="filtrarSacolas()" placeholder="Filtrar por nome do cliente..." 
                               class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                {{-- Display de Sacolas --}}
                <div id="selected-live-bags-display" class="space-y-4 min-h-[300px]">
                    <div class="text-center text-gray-400 py-16">
                        <i class="fas fa-hand-pointer text-4xl mb-3 opacity-40"></i>
                        <p class="text-sm font-bold">Selecione uma live ao lado para ver suas sacolas.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Modal Confirmar Remoção (Tailwind pura) --}}
<div id="modalConfirmarRemocao" class="fixed inset-0 z-50 items-center justify-center bg-black/40 hidden">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4 mx-4">
        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
            <i class="fas fa-exclamation-triangle text-red-500"></i> Confirmar Remoção
        </h3>
        <p class="text-sm text-gray-600">
            Deseja realmente remover o item <span id="remove_item_name" class="font-extrabold text-gray-800"></span> no valor de <span id="remove_item_price" class="font-extrabold text-green-600"></span>?
        </p>
        <p class="text-xs text-gray-500 bg-gray-50 p-2.5 rounded border border-gray-150">
            Escolha se deseja descontar os pontos de jogo que o cliente ganhou por comprar este item.
        </p>
        <div class="flex justify-end gap-2 pt-2 text-xs">
            <button onclick="fecharModalRemocao()" class="px-4 py-2 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition">
                Cancelar
            </button>
            <button id="btnConfirmarSemDesconto" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold rounded-lg transition">
                Manter Pontos
            </button>
            <button id="btnConfirmarComDesconto" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                Descontar Pontos
            </button>
        </div>
    </div>
</div>

{{-- Scripts da Página --}}
<script>
    let allLives = [];
    let selectedLiveId = null;
    let showingAllLives = false;
    let itemParaRemover = null;

    document.addEventListener('DOMContentLoaded', carregarTodasAsLives);

    async function carregarTodasAsLives() {
        const body = document.getElementById('lives-table-body');
        body.innerHTML = '<tr><td colspan="5" class="text-center py-6 text-gray-500 text-sm"><i class="fas fa-spinner fa-spin mr-1"></i> Carregando...</td></tr>';
        
        try {
            const res = await fetch('/api/lives/all');
            const data = await res.json();
            if (data.success) {
                allLives = data.data || data.lives || [];
                document.getElementById('total-lives-count').textContent = allLives.length;
                renderLivesTable(allLives);
            }
        } catch (e) {
            body.innerHTML = '<tr><td colspan="5" class="text-center py-6 text-red-500 text-sm"><i class="fas fa-exclamation-circle mr-1"></i> Erro ao carregar lives</td></tr>';
        }
    }

    function renderLivesTable(lives) {
        const body = document.getElementById('lives-table-body');
        const filterVal = document.getElementById('search-live').value.toLowerCase().trim();
        
        // Filtra se houver valor
        let filtered = lives;
        if (filterVal) {
            filtered = lives.filter(l => 
                String(l.id).includes(filterVal) || 
                (l.tipo_live || '').toLowerCase().includes(filterVal) || 
                (l.plataformas || '').toLowerCase().includes(filterVal)
            );
        }

        const display = showingAllLives ? filtered : filtered.slice(0, 5);
        
        if (display.length === 0) {
            body.innerHTML = '<tr><td colspan="5" class="text-center py-6 text-gray-400 text-xs">Nenhuma live encontrada</td></tr>';
            return;
        }

        body.innerHTML = display.map(live => {
            const status = live.ativo ? 'ativa' : 'encerrada';
            const statusClass = status === 'ativa' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            const date = new Date(live.created_at).toLocaleDateString('pt-BR');
            const isSelected = selectedLiveId == live.id ? 'bg-blue-50 border-l-4 border-blue-500 font-bold' : 'hover:bg-gray-50';
            
            return `
                <tr class="live-row cursor-pointer transition ${isSelected}" data-live-id="${live.id}" onclick="selectLive(${live.id})">
                    <td class="px-4 py-3 text-sm text-gray-900 font-semibold">${live.id}</td>
                    <td class="px-4 py-3 text-sm text-gray-700 font-medium">${(live.tipo_live || '').toUpperCase()}</td>
                    <td class="px-4 py-3 text-sm text-gray-500">${live.plataformas || 'N/A'}</td>
                    <td class="px-4 py-3 text-sm">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide ${statusClass}">${status}</span>
                    </td>
                    <td class="px-4 py-3 text-center text-sm">
                        <button onclick="enviarPortalNotification(${live.id}, event)" 
                                class="bg-green-600 hover:bg-green-700 text-white font-extrabold px-2 py-1 rounded-lg text-[10px] uppercase tracking-wide flex items-center gap-1 mx-auto transition shadow-sm"
                                title="Notificar link do Portal via WhatsApp">
                            <i class="fas fa-paper-plane text-[9px]"></i> Portal
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function filtrarLives() {
        renderLivesTable(allLives);
    }

    function mostrarTodasAsLives() {
        showingAllLives = !showingAllLives;
        renderLivesTable(allLives);
    }

    function selectLive(id) {
        selectedLiveId = id;
        document.querySelectorAll('.live-row').forEach(r => {
            r.classList.remove('bg-blue-50', 'border-l-4', 'border-blue-500', 'font-bold');
        });
        const row = document.querySelector(`.live-row[data-live-id="${id}"]`);
        if (row) row.classList.add('bg-blue-50', 'border-l-4', 'border-blue-500', 'font-bold');
        document.getElementById('selected-live-info').textContent = `(ID: ${id})`;
        carregarSacolas(id);
    }

    async function carregarSacolas(liveId) {
        const container = document.getElementById('selected-live-bags-display');
        container.innerHTML = '<div class="text-center py-16 text-gray-500"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

        try {
            const res = await fetch(`/api/sacolinhas/live/${liveId}`);
            const data = await res.json();
            if (data.success) {
                exibirSacolas(data.data, container, liveId);
            } else {
                container.innerHTML = `<div class="text-center py-16 text-red-500 font-semibold">${data.message}</div>`;
            }
        } catch (e) {
            container.innerHTML = '<div class="text-center py-16 text-red-500 font-semibold">Erro ao carregar sacolas.</div>';
        }
    }

    // Cache local de sacolas para busca em tempo real
    let currentLiveBags = [];
    let currentLiveId = null;
    function exibirSacolas(bags, container, liveId) {
        currentLiveBags = bags || [];
        currentLiveId = liveId;
        filtrarSacolas();
    }

    function filtrarSacolas() {
        const container = document.getElementById('selected-live-bags-display');
        const filterVal = document.getElementById('search-client').value.toLowerCase().trim();

        let filtered = currentLiveBags;
        if (filterVal) {
            filtered = currentLiveBags.filter(b => 
                (b.client.name || '').toLowerCase().includes(filterVal) ||
                String(b.client.id).includes(filterVal)
            );
        }

        if (!filtered || filtered.length === 0) {
            container.innerHTML = '<div class="text-center py-16 text-gray-400 text-sm">Nenhuma sacola encontrada.</div>';
            return;
        }

        container.innerHTML = filtered.map(bag => `
            <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm bg-white card-bag">
                <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center flex-wrap gap-2">
                    <div class="flex items-center gap-3">
                        <img src="${bag.client.avatar_url}" class="rounded-full w-8 h-8 object-cover border border-gray-300">
                        <div>
                            <strong class="text-gray-800 text-sm">${bag.client.name}</strong> 
                            <span class="text-xs text-gray-450 block">ID: ${bag.client.id}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button onclick="enviarMensagemInicial(${currentLiveId}, ${bag.client.id}, this)" 
                                class="bg-green-600 hover:bg-green-700 text-white font-extrabold px-3 py-1.5 rounded-lg text-[9px] uppercase tracking-wide flex items-center gap-1 transition shadow-sm"
                                title="Enviar mensagem inicial da live">
                            <i class="fab fa-whatsapp text-sm"></i> Msg Inicial
                        </button>
                        <div class="text-right">
                            <span class="px-2.5 py-0.5 bg-blue-100 text-blue-800 rounded-full text-[10px] font-bold uppercase tracking-wide">Itens: ${bag.total_items}</span>
                            <div class="font-black text-green-600 text-sm mt-0.5">${bag.formatted_total}</div>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-150 text-xs">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-500 uppercase">Cód</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-500 uppercase">Item</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-500 uppercase">Detalhes</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-500 uppercase">Preço</th>
                                <th class="px-4 py-2 text-center font-semibold text-gray-500 uppercase" width="130">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            ${bag.items.map(item => {
                                const itemStatus = (item.item_status || 'pendente').toLowerCase();
                                let statusBadgeClass = 'bg-gray-100 text-gray-700';
                                if (itemStatus === 'reservado') statusBadgeClass = 'bg-yellow-100 text-yellow-800 border border-yellow-250';
                                if (itemStatus === 'sacolinha') statusBadgeClass = 'bg-green-100 text-green-800 border border-green-250';
                                if (itemStatus === 'vendido') statusBadgeClass = 'bg-blue-100 text-blue-850';
                                
                                return `
                                    <tr class="hover:bg-gray-50/30">
                                        <td class="px-4 py-2 text-gray-400 font-mono">${item.item_sku || '---'}</td>
                                        <td class="px-4 py-2 font-bold text-gray-800">${item.item_name}</td>
                                        <td class="px-4 py-2 text-gray-500">${item.item_brand || ''} • ${item.item_color || ''} • ${item.item_size || ''}</td>
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase ${statusBadgeClass}">${itemStatus}</span>
                                        </td>
                                        <td class="px-4 py-2 text-right font-bold text-gray-900">${item.formatted_total_price}</td>
                                        <td class="px-4 py-2 text-center">
                                            <div class="inline-flex rounded-md shadow-sm text-[10px]" role="group">
                                                <button class="px-2.5 py-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded-l-md font-bold transition disabled:opacity-50 disabled:pointer-events-none" 
                                                        onclick="alterarStatus(${item.item_id}, 'reservado', this)" 
                                                        ${item.item_status === 'reservado' ? 'disabled' : ''}>R</button>
                                                <button class="px-2.5 py-1 bg-green-600 hover:bg-green-700 text-white font-bold transition disabled:opacity-50 disabled:pointer-events-none" 
                                                        onclick="alterarStatus(${item.item_id}, 'sacolinha', this)" 
                                                        ${item.item_status === 'sacolinha' ? 'disabled' : ''}>S</button>
                                                <button class="px-2.5 py-1 border border-red-200 text-red-600 hover:bg-red-50 rounded-r-md font-bold transition" 
                                                        onclick="abrirModalRemocao(${item.item_id}, ${bag.client.id}, '${item.item_name.replace(/'/g, "\\'")}', '${item.formatted_total_price}')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `).join('');
    }

    async function alterarStatus(itemId, status, btn) {
        btn.disabled = true;
        try {
            const res = await fetch(`/api/items/${itemId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({status})
            });
            const data = await res.json();
            if (data.success) carregarSacolas(selectedLiveId);
        } catch (e) { alert('Erro ao alterar status'); }
        btn.disabled = false;
    }

    function abrirModalRemocao(itemId, userId, name, price) {
        itemParaRemover = { itemId, userId };
        document.getElementById('remove_item_name').textContent = name;
        document.getElementById('remove_item_price').textContent = price;
        const modal = document.getElementById('modalConfirmarRemocao');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function fecharModalRemocao() {
        const modal = document.getElementById('modalConfirmarRemocao');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.getElementById('btnConfirmarComDesconto').onclick = () => executarRemocao(true);
    document.getElementById('btnConfirmarSemDesconto').onclick = () => executarRemocao(false);

    async function executarRemocao(descontar) {
        const { itemId, userId } = itemParaRemover;
        try {
            const res = await fetch('/api/sacolinhas/remove', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ item_id: itemId, user_id: userId, live_id: selectedLiveId, descontar_pontos: descontar })
            });
            const data = await res.json();
            if (data.success) {
                fecharModalRemocao();
                carregarSacolas(selectedLiveId);
            }
        } catch (e) { alert('Erro ao remover item'); }
    }

    async function enviarPortalNotification(liveId, event) {
        event.stopPropagation(); // Impede seleção da linha
        
        if (!confirm('Deseja realmente enviar a notificação com link seguro do Portal para todos os clientes desta live?')) {
            return;
        }

        try {
            const res = await fetch(`/admin/live/${liveId}/send-portal-notifications`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
            } else {
                alert(data.message || 'Erro ao enviar notificações.');
            }
        } catch (e) {
            alert('Erro ao enviar notificações. Verifique a conexão.');
        }
    }

    async function enviarMensagemInicial(liveId, userId, btn) {
        if (!confirm('Deseja realmente enviar a mensagem inicial da live (Msg1) para este cliente?')) {
            return;
        }

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Enviando...';

        try {
            const res = await fetch(`/lives/${liveId}/sacolas/${userId}/whatsapp/first`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message || 'Mensagem enfileirada com sucesso!');
            } else {
                alert(data.message || 'Erro ao enviar a mensagem.');
            }
        } catch (e) {
            alert('Erro de conexão ao enviar a mensagem.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }
</script>
@endsection

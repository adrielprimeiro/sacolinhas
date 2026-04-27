@extends('layouts.app')

@section('title', 'Fotos Órfãs')

@section('content')
<style>
    /* GARANTIA DE VISIBILIDADE DO SELETOR */
    .orphan-checkbox:checked + .custom-checkbox-div {
        background-color: #4f46e5 !important; /* Indigo 600 */
        border-color: #4f46e5 !important;
    }
    .orphan-checkbox:checked + .custom-checkbox-div svg {
        color: white !important;
        opacity: 1 !important;
    }
    
    /* GARANTIA DE Z-INDEX PARA MODAIS */
    #modal-zoom { z-index: 9999 !important; }
    #modal-transferencia { z-index: 9998 !important; }

    /* Efeito de seleção no card */
    .orphan-card.is-selected {
        border-color: #4f46e5 !important;
        background-color: rgba(79, 70, 229, 0.05) !important;
    }

    /* VISUAL DEBUGGER OVERLAY */
    #visual-debug-log {
        position: fixed;
        bottom: 20px;
        left: 20px;
        background: #1e293b;
        color: #38bdf8;
        padding: 10px;
        border-radius: 8px;
        font-family: monospace;
        font-size: 11px;
        max-width: 300px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 99999;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        pointer-events: auto; /* Permite cliques agora */
        cursor: pointer;
        display: block;
    }
</style>

<div id="visual-debug-log">Log Iniciado...</div>

<div class="max-w-7xl mx-auto py-8 px-4" id="orphan-app">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4 sticky top-0 bg-gray-50/95 py-4 z-30">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Fotos Órfãs</h1>
            <p class="text-gray-500 mt-1">Selecione as fotos e atribua a um item pelo código.</p>
        </div>

        <div class="flex items-center gap-3">
            <span id="selection-count" class="hidden bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-sm font-bold animate-pulse">
                0 selecionadas
            </span>
            
            <button
                type="button"
                id="btn-abrir-transferencia"
                onclick="abrirModalTransferencia()"
                class="hidden bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg text-sm font-bold shadow-lg transition-all active:scale-95 flex items-center gap-2"
            >
                <span>Transferir Selecionadas</span>
                <span class="bg-indigo-500 px-1.5 py-0.5 rounded text-[10px] hidden md:block">ENTER</span>
            </button>

            <button
                type="button"
                id="btn-deletar-selecionadas"
                onclick="deletarSelecionadas()"
                class="hidden bg-red-50 text-red-600 hover:bg-red-600 hover:text-white px-6 py-2.5 rounded-lg text-sm font-bold border-2 border-red-100 hover:border-red-600 transition-all active:scale-95 flex items-center gap-2"
            >
                <span>Deletar Selecionadas</span>
            </button>

            <form
                method="POST"
                action="{{ route('image-groups.orphans.delete') }}"
                onsubmit="return confirm('Isso vai excluir PERMANENTEMENTE as imagens órfãs exibidas. Deseja continuar?')"
            >
                @csrf
                <button
                    type="submit"
                    class="text-red-600 hover:text-red-700 hover:bg-red-50 px-3 py-2 rounded-lg text-sm font-medium transition"
                >
                    Excluir Todas
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 p-4 mb-6 rounded shadow-sm flex justify-between items-center">
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="text-emerald-900/50 hover:text-emerald-900">&times;</button>
        </div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
        @forelse($orphans as $media)
            @php $thumbUrl = "/storage/" . ($media->thumbnail_url ?: $media->url); @endphp
            <div 
                class="relative group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 border-2 border-transparent orphan-card"
                data-id="{{ $media->id }}"
                data-thumb="{{ $thumbUrl }}"
            >
                <!-- Área de Clique para Zoom -->
                <div class="aspect-square overflow-hidden bg-gray-100 cursor-zoom-in" onclick="abrirZoom('{{ $thumbUrl }}')">
                    <img
                        src="{{ $thumbUrl }}"
                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                        loading="lazy"
                    >
                </div>

                <!-- Checkbox de Seleção -->
                <div class="absolute top-3 right-3 z-20">
                    <label class="relative flex items-center justify-center cursor-pointer">
                        <input
                            type="checkbox"
                            value="{{ $media->id }}"
                            class="orphan-checkbox sr-only"
                            onchange="updateUI()"
                        >
                        <!-- Estilo customizado (Controlado via CSS lá em cima) -->
                        <div class="custom-checkbox-div w-10 h-10 bg-white/90 backdrop-blur-md border-2 border-gray-300 rounded-full shadow-xl flex items-center justify-center transition-all hover:scale-110">
                            <svg class="w-6 h-6 opacity-0 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </label>
                </div>
                
                <div class="absolute inset-0 bg-indigo-900/5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
            </div>
        @empty
            <div class="col-span-full py-20 text-center bg-white rounded-3xl border-2 border-dashed border-gray-200">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-50 rounded-full mb-4">
                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.587-1.587a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900">Nenhuma foto encontrada</h3>
                <p class="text-gray-500">Tudo limpo por aqui! Nenhuma imagem sem item no momento.</p>
            </div>
        @endforelse
    </div>
</div>

<!-- MODAIS NO FINAL DO BODY PARA EVITAR PROBLEMAS DE STACKING CONTEXT -->

<!-- Modal de Zoom -->
<div
    id="modal-zoom"
    class="fixed inset-0 hidden bg-black/95 items-center justify-center p-4 cursor-zoom-out"
    onclick="fecharZoom()"
>
    <img id="img-zoom" class="max-w-full max-h-full rounded-lg shadow-2xl animate-in zoom-in duration-200">
    <button class="absolute top-6 right-6 text-white text-4xl hover:scale-110 transition">&times;</button>
</div>

<!-- Modal de Transferência -->
<div
    id="modal-transferencia"
    class="fixed inset-0 hidden bg-gray-900/80 backdrop-blur-sm items-center justify-center p-4 z-[100]"
    onkeydown="handleModalKeys(event)"
>
    <!-- Card Principal -->
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-300 flex flex-col max-h-[90vh]" onclick="event.stopPropagation()">
        
        <!-- Header -->
        <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-xl font-extrabold text-gray-900">Vincular fotos a Item</h3>
            <button onclick="fecharModalTransferencia()" class="bg-white border rounded-full w-10 h-10 flex items-center justify-center text-gray-400 hover:text-red-500 hover:border-red-100 transition shadow-sm">&times;</button>
        </div>

        <!-- Corpo (Scrollable) -->
        <div class="p-6 overflow-y-auto custom-scrollbar flex-1">
            <!-- Grid de Fotos (Ordenáveis) -->
            <div class="mb-8">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4">Ordem das Imagens (Arraste para organizar)</label>
                <div id="modal-thumbs" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 p-1">
                    <!-- Preenchido via JS com miniaturas de 112px -->
                </div>
            </div>

            <!-- Dados do Item -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start border-t pt-8">
                <div class="space-y-4">
                    <div>
                        <label for="input-codigo" class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2">Código do Item</label>
                        <input
                            type="text"
                            id="input-codigo"
                            class="w-full border-2 border-gray-100 rounded-2xl px-5 py-3 text-2xl font-black focus:border-indigo-500 focus:ring-0 outline-none transition-all placeholder:text-gray-200 uppercase"
                            placeholder="Ex: 04Y5"
                            autocomplete="off"
                        >
                        <div id="codigo-feedback" class="mt-2 text-xs font-bold hidden"></div>
                    </div>

                    <button
                        type="button"
                        id="btn-confirmar"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 rounded-2xl shadow-xl shadow-indigo-200 transition-all active:scale-[0.98] flex items-center justify-center gap-3 text-lg"
                    >
                        <span>CONFIRMAR (ENTER)</span>
                    </button>
                    <p class="text-[9px] text-center text-gray-400 uppercase font-bold tracking-widest">As fotos serão movidas para a galeria do item</p>
                </div>

                <!-- Preview Inteligente -->
                <div id="item-preview" class="hidden p-5 rounded-2xl border-2 border-indigo-50 bg-indigo-50/30 min-h-[160px] animate-in slide-in-from-top-2">
                    <!-- Preenchido via JS após encontrar código -->
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>
    let selectedIds = [];

    // Carregar histórico do log caso existam erros graves (opcional, mantendo apenas console)
    window.addEventListener('load', () => {
        console.log("[OrphanTransfer] Interface Iniciada");
    });

    function updateUI() {
        const checkboxes = document.querySelectorAll('.orphan-checkbox:checked');
        selectedIds = Array.from(checkboxes).map(cb => cb.value);
        
        const btnTransfer = document.getElementById('btn-abrir-transferencia');
        const btnDelete = document.getElementById('btn-deletar-selecionadas');
        const countSpan = document.getElementById('selection-count');
        
        if (selectedIds.length > 0) {
            btnTransfer.classList.remove('hidden');
            btnTransfer.classList.add('flex');
            
            btnDelete.classList.remove('hidden');
            btnDelete.classList.add('flex');
            
            countSpan.classList.remove('hidden');
            countSpan.textContent = `${selectedIds.length} selecionada${selectedIds.length > 1 ? 's' : ''}`;
        } else {
            btnTransfer.classList.add('hidden');
            btnTransfer.classList.remove('flex');
            
            btnDelete.classList.add('hidden');
            btnDelete.classList.remove('flex');
            
            countSpan.classList.add('hidden');
        }

        // Estilizar cards selecionados
        document.querySelectorAll('.orphan-card').forEach(card => {
            const cb = card.querySelector('.orphan-checkbox');
            if (cb.checked) {
                card.classList.add('is-selected');
            } else {
                card.classList.remove('is-selected');
            }
        });
    }

    function abrirZoom(url) {
        const modal = document.getElementById('modal-zoom');
        document.getElementById('img-zoom').src = url;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function fecharZoom() {
        const modal = document.getElementById('modal-zoom');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (document.getElementById('modal-transferencia').classList.contains('hidden')) {
            document.body.style.overflow = 'auto';
        }
    }

    function abrirModalTransferencia() {
        if (selectedIds.length === 0) return;

        const modal = document.getElementById('modal-transferencia');
        const thumbsContainer = document.getElementById('modal-thumbs');
        const input = document.getElementById('input-codigo');

        // Limpar e preencher thumbs
        thumbsContainer.innerHTML = '';
        selectedIds.forEach(id => {
            const card = document.querySelector(`.orphan-card[data-id="${id}"]`);
            const thumbUrl = card.dataset.thumb;
            
            const div = document.createElement('div');
            div.className = "relative group cursor-move aspect-square rounded-xl overflow-hidden border-2 border-transparent hover:border-indigo-500 transition shadow-sm";
            div.setAttribute('data-id', id);
            div.innerHTML = `
                <img src="${thumbUrl}" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/20 group-hover:bg-transparent transition"></div>
                <div class="absolute top-1 right-1 bg-white/90 rounded p-1 text-[10px] font-bold text-gray-500 border shadow-sm opacity-50">
                    <i class="fas fa-grip-vertical"></i>
                </div>
            `;
            thumbsContainer.appendChild(div);
        });

        // Inicializar Sortable
        if (typeof Sortable !== 'undefined') {
            new Sortable(thumbsContainer, {
                animation: 150,
                ghostClass: 'opacity-20'
            });
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        
        setTimeout(() => input.focus(), 100);
        
        document.getElementById('item-preview').classList.add('hidden');
        document.getElementById('codigo-feedback').classList.add('hidden');
    }

    function fecharModalTransferencia() {
        const modal = document.getElementById('modal-transferencia');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }

    function handleModalKeys(event) {
        if (event.key === 'Escape') fecharModalTransferencia();
        if (event.key === 'Enter') {
            const input = document.getElementById('input-codigo');
            if (document.activeElement === input) {
                event.preventDefault();
                confirmarTransferencia();
            }
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && document.getElementById('modal-transferencia').classList.contains('hidden') && document.getElementById('modal-zoom').classList.contains('hidden')) {
            if (selectedIds.length > 0) {
                abrirModalTransferencia();
                e.preventDefault();
            }
        }
    });

    function confirmarTransferencia() {
        try {
            const inputCodigo = document.getElementById('input-codigo');
            const codigo = inputCodigo ? inputCodigo.value.trim() : null;
            const btn = document.getElementById('btn-confirmar');
            const feedback = document.getElementById('codigo-feedback');

            const statusSelect = document.getElementById('edit-status');
            const estadoSelect = document.getElementById('edit-estado');
            const status = statusSelect ? statusSelect.value : null;
            const estado = estadoSelect ? estadoSelect.value : null;

            const thumbsContainer = document.getElementById('modal-thumbs');
            const orderedIds = Array.from(thumbsContainer.querySelectorAll('[data-id]'))
                                    .map(el => el.getAttribute('data-id'));
            
            if (!codigo) {
                alert('Por favor, digite o código do item.');
                if (inputCodigo) inputCodigo.focus();
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span>AGUARDE...</span>';

                    const categorias = Array.from(document.querySelectorAll('input[name="modal_categorias[]"]')).map(el => el.value);

                    fetch("{{ route('image-groups.transfer-orphans') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            codigo: codigo,
                            media_ids: orderedIds,
                            status: status,
                            estado: estado,
                            categorias: categorias
                        })
                    })
            .then(async (response) => {
                const data = await response.json();
                if (!response.ok) throw data;
                return data;
            })
            .then(data => {
                if (data.success) {
                    btn.innerHTML = '<span>✓ SUCESSO!</span>';
                    btn.style.backgroundColor = '#10b981';
                    setTimeout(() => location.reload(), 2000);
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = '<span>CONFIRMAR (ENTER)</span>';
                let msg = error.message || 'Erro de segurança ou limite de tempo.';
                alert("FALHA: " + msg);
                if (feedback) feedback.textContent = msg;
            });

        } catch (fatalError) {
            alert("ERRO CRÍTICO: " + fatalError.message);
        }
    }

    function deletarSelecionadas() {
        if (selectedIds.length === 0) return;

        if (!confirm(`Deseja realmente deletar as ${selectedIds.length} imagens selecionadas?`)) {
            return;
        }

        const btn = document.getElementById('btn-deletar-selecionadas');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<span>DELETANDO...</span>';

        fetch("{{ route('image-groups.orphans.delete-selected') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                media_ids: selectedIds
            })
        })
        .then(async (response) => {
            if (!response.ok) {
                const data = await response.json();
                throw data;
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            alert(error.message || 'Erro ao deletar imagens.');
        });
    }

    let searchTimeout;
    document.getElementById('input-codigo').addEventListener('input', function() {
        const codigo = this.value.trim();
        const feedback = document.getElementById('codigo-feedback');
        const preview = document.getElementById('item-preview');
        
        clearTimeout(searchTimeout);
        if (codigo.length < 2) {
            preview.classList.add('hidden');
            feedback.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch("{{ route('image-groups.buscar-codigo') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ codigo: codigo })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const suggestedId = data.suggested_category_id;
                    const allCats = data.all_categories;
                    const itemCats = data.item.categorias || [];

                    preview.innerHTML = `
                        <div class="flex flex-col space-y-4">
                            <h1 class="text-xl font-black text-indigo-900 leading-tight">${data.item.nome_do_produto}</h1>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1">Status</label>
                                    <select id="edit-status" class="w-full bg-white border border-indigo-100 rounded-lg text-[11px] font-bold p-2 outline-none focus:border-indigo-400">
                                        <option value="disponivel">Disponível</option>
                                        <option value="loja">Loja</option>
                                        <option value="estoque">Estoque</option>
                                        <option value="reservado">Reservado</option>
                                        <option value="vendido">Vendido</option>
                                        <option value="live">Live</option>
                                        <option value="em_sacolinha">Em Sacolinha</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1">Estado</label>
                                    <select id="edit-estado" class="w-full bg-white border border-indigo-100 rounded-lg text-[11px] font-bold p-2 outline-none focus:border-indigo-400">
                                        <option value="novo">Novo</option>
                                        <option value="semi-novo">Semi-novo</option>
                                        <option value="usado">Usado</option>
                                        <option value="recondicionado">Recondicionado</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Seletor de Categorias --}}
                            <div class="space-y-1.5" x-data="{ 
                                search: '', 
                                open: false,
                                selected: ${JSON.stringify(itemCats.length > 0 ? itemCats : (suggestedId ? [suggestedId] : []))},
                                all: ${JSON.stringify(allCats)},
                                suggested: ${suggestedId || 'null'},
                                get filtered() {
                                    if (!this.search) return this.all;
                                    return this.all.filter(c => c.path.toLowerCase().includes(this.search.toLowerCase()));
                                },
                                toggle(id) {
                                    const idx = this.selected.indexOf(id);
                                    if (idx > -1) this.selected.splice(idx, 1);
                                    else this.selected.push(id);
                                }
                            }">
                                <label class="block text-[8px] font-black text-gray-400 uppercase tracking-widest">Categorias</label>
                                
                                <div class="relative">
                                    <div @click="open = !open" class="w-full bg-white border border-indigo-100 rounded-xl p-2.5 cursor-pointer min-h-[40px] flex flex-wrap gap-1">
                                        <template x-if="selected.length === 0">
                                            <span class="text-[10px] text-gray-400 font-bold italic">Nenhuma selecionada</span>
                                        </template>
                                        <template x-for="id in selected" :key="id">
                                            <div class="bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-0.5 rounded-md flex items-center gap-1">
                                                <span x-text="all.find(c => c.id == id)?.name"></span>
                                                <input type="hidden" name="modal_categorias[]" :value="id">
                                                <span @click.stop="toggle(id)" class="hover:text-red-500">×</span>
                                            </div>
                                        </template>
                                    </div>

                                    <div x-show="open" @click.away="open = false" class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-2xl z-50 p-2 max-h-60 overflow-y-auto custom-scrollbar">
                                        <input type="text" x-model="search" placeholder="Filtrar categorias..." class="w-full border border-gray-100 rounded-lg px-3 py-2 text-xs mb-2 outline-none focus:border-indigo-300">
                                        
                                        <div class="space-y-1">
                                            <template x-for="cat in filtered" :key="cat.id">
                                                <div 
                                                    @click="toggle(cat.id)"
                                                    class="p-2 rounded-lg cursor-pointer hover:bg-gray-50 flex items-center justify-between group"
                                                    :class="selected.includes(cat.id) ? 'bg-indigo-50' : ''"
                                                >
                                                    <div class="flex flex-col">
                                                        <span class="text-[10px] font-bold" :class="selected.includes(cat.id) ? 'text-indigo-700' : 'text-gray-700'" x-text="cat.name"></span>
                                                        <span class="text-[8px] text-gray-400" x-text="cat.path"></span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <template x-if="cat.id == suggested">
                                                            <span class="bg-amber-100 text-amber-700 text-[8px] font-black px-1.5 py-0.5 rounded uppercase">Sugestão</span>
                                                        </template>
                                                        <div class="w-4 h-4 border-2 rounded flex items-center justify-center transition-colors" :class="selected.includes(cat.id) ? 'bg-indigo-600 border-indigo-600' : 'border-gray-200'">
                                                            <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"></path></svg>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-[10px] bg-white/50 p-3 rounded-xl border border-white/50">
                                <div class="flex flex-col"><span class="text-gray-400 font-bold uppercase text-[8px]">Marca:</span> <span class="text-gray-700 font-bold">${data.item.marca || '-'}</span></div>
                                <div class="flex flex-col"><span class="text-gray-400 font-bold uppercase text-[8px]">Tamanho:</span> <span class="text-gray-700 font-bold">${data.item.tamanho || '-'}</span></div>
                                <div class="flex flex-col"><span class="text-gray-400 font-bold uppercase text-[8px]">Cor:</span> <span class="text-gray-700 font-bold">${data.item.cor || '-'}</span></div>
                            </div>
                        </div>
                    `;
                    
                    // Sincroniza valores atuais
                    document.getElementById('edit-status').value = data.item.status;
                    document.getElementById('edit-estado').value = data.item.estado;

                    preview.classList.remove('hidden');
                } else {
                    feedback.textContent = '× Código não encontrado';
                    feedback.classList.remove('hidden');
                    feedback.className = 'mt-2 text-xs font-bold text-red-500';
                    preview.classList.add('hidden');
                }
            })
            .catch(() => {
                feedback.classList.add('hidden');
                preview.classList.add('hidden');
            });
        }, 300);
    });

    // Registrar Evento do Botão de Confirmar via JS para maior robustez
    document.addEventListener('DOMContentLoaded', () => {
        const btnConfirmar = document.getElementById('btn-confirmar');
        if (btnConfirmar) {
            btnConfirmar.addEventListener('click', (e) => {
                e.preventDefault();
                confirmarTransferencia();
            });
        }
    });

    // Botão de Limpar Log (Removido)
</script>

<style>
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
</style>
@endsection
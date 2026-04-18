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
    class="fixed inset-0 hidden bg-gray-900/80 backdrop-blur-sm items-center justify-center p-4"
    onkeydown="handleModalKeys(event)"
>
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden animate-in fade-in zoom-in duration-300" onclick="event.stopPropagation()">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-xl font-extrabold text-gray-900">Associar a Item</h3>
            <button onclick="fecharModalTransferencia()" class="text-gray-400 hover:text-gray-600 text-2xl transition">&times;</button>
        </div>

        <div class="p-8">
            <!-- Miniaturas Selecionadas -->
            <div id="modal-thumbs" class="flex flex-wrap gap-2 mb-8 max-h-32 overflow-y-auto p-2 bg-gray-50 rounded-xl"></div>

            <div class="space-y-6">
                <div>
                    <label for="input-codigo" class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Código do Item</label>
                    <input
                        type="text"
                        id="input-codigo"
                        class="w-full border-2 border-gray-200 rounded-2xl px-5 py-4 text-2xl font-bold focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all placeholder:text-gray-300"
                        placeholder="Ex: 1234"
                        autocomplete="off"
                    >
                    <div id="codigo-feedback" class="mt-2 text-sm hidden"></div>
                </div>

                <div id="item-preview" class="hidden p-4 rounded-2xl bg-indigo-50 border border-indigo-100 animate-in slide-in-from-top-2">
                    <!-- Preenchido via JS -->
                </div>

                <button
                    type="button"
                    id="btn-confirmar"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-5 rounded-2xl shadow-xl shadow-indigo-200 transition-all active:scale-[0.98] flex items-center justify-center gap-3 text-lg"
                >
                    <span>CONFIRMAR (ENTER)</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let selectedIds = [];

    // DEBUGGER PERSISTENTE (Hardenizado contra erros de memória)
    function vLog(msg, color = '#38bdf8') {
        try {
            const log = document.getElementById('visual-debug-log');
            if (!log) return;

            const entry = document.createElement('div');
            entry.style.color = color;
            const time = new Date().toLocaleTimeString();
            const text = `> ${time}: ${msg}`;
            entry.textContent = text;
            log.prepend(entry);
            console.log(`[OrphanTransfer] ${msg}`);
            
            // Salva no localStorage com segurança
            try {
                let history = JSON.parse(localStorage.getItem('orphan_debug_history') || '[]');
                history.unshift(text);
                localStorage.setItem('orphan_debug_history', JSON.stringify(history.slice(0, 30)));
            } catch (e) {
                // Se o localStorage estiver cheio ou corrompido, limpa e continua
                localStorage.removeItem('orphan_debug_history');
            }
        } catch (err) {
            console.error("vLog failed", err);
        }
    }

    // Carregar histórico ao iniciar
    window.addEventListener('load', () => {
        const history = JSON.parse(localStorage.getItem('orphan_debug_history') || '[]');
        history.forEach(msg => {
            const log = document.getElementById('visual-debug-log');
            const entry = document.createElement('div');
            entry.textContent = msg;
            entry.style.opacity = '0.6';
            log.appendChild(entry);
        });
        vLog("Página Carregada/Recarregada");
    });

    function limparLog() {
        localStorage.removeItem('orphan_debug_history');
        location.reload();
    }

    window.onerror = function(message, source, lineno, colno, error) {
        vLog(`CRITICAL JS ERROR: ${message} (L:${lineno})`);
    };

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
        vLog(`Abrindo modal para ${selectedIds.length} fotos`);
        if (selectedIds.length === 0) return;

        const modal = document.getElementById('modal-transferencia');
        const thumbsContainer = document.getElementById('modal-thumbs');
        const input = document.getElementById('input-codigo');

        // Limpar e preencher thumbs
        thumbsContainer.innerHTML = '';
        selectedIds.forEach(id => {
            const card = document.querySelector(`.orphan-card[data-id="${id}"]`);
            const thumbUrl = card.dataset.thumb;
            const img = document.createElement('img');
            img.src = thumbUrl;
            img.className = "w-12 h-12 object-cover rounded-lg shadow-sm border border-white";
            thumbsContainer.appendChild(img);
        });

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        
        // Pequeno atraso para garantir o foco após a animação do modal
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
                vLog("Enter pressionado no input");
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
            vLog("Iniciando confirmarTransferencia...");
            const inputCodigo = document.getElementById('input-codigo');
            const codigo = inputCodigo ? inputCodigo.value.trim() : null;
            const btn = document.getElementById('btn-confirmar');
            const feedback = document.getElementById('codigo-feedback');

            if (!selectedIds || selectedIds.length === 0) {
                vLog("FALHA: Nenhuma foto selecionada.", "red");
                return;
            }

            vLog(`Ação: Vincular ${selectedIds.length} fotos ao código ${codigo}`);

            if (!codigo) {
                vLog("FALHA: Código vazio.", "red");
                alert('Por favor, digite o código do item.');
                if (inputCodigo) inputCodigo.focus();
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span>AGUARDE...</span>';

            vLog("Disparando Fetch...");
            fetch("{{ route('image-groups.transfer-orphans') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    codigo: codigo,
                    media_ids: selectedIds
                })
            })
            .then(async (response) => {
                vLog(`Status HTTP: ${response.status}`);
                const data = await response.json();
                if (!response.ok) throw data;
                return data;
            })
            .then(data => {
                if (data.success) {
                    vLog("✓ SUCESSO NO BANCO!", "#10b981");
                    
                    if (data.edit_url) {
                        vLog(`CLIQUE AQUI: <a href="${data.edit_url}" target="_blank" style="color: white; font-weight: bold; text-decoration: underline;">[ABRIR ITEM]</a>`, "#10b981");
                    }

                    vLog("Aguardando 3s para atualizar...", "#10b981");
                    btn.innerHTML = '<span>✓ SUCESSO!</span>';
                    btn.style.backgroundColor = '#10b981';
                    setTimeout(() => location.reload(), 3000);
                }
            })
            .catch(error => {
                vLog(`ERRO SERVIDOR: ${JSON.stringify(error)}`, "red");
                btn.disabled = false;
                btn.innerHTML = '<span>CONFIRMAR (ENTER)</span>';
                let msg = error.message || 'Erro de segurança ou limite de tempo.';
                alert("FALHA: " + msg);
                if (feedback) feedback.textContent = msg;
            });

        } catch (fatalError) {
            vLog(`CRASH JS: ${fatalError.message}`, "red");
            alert("ERRO CRÍTICO NO NAVEGADOR: " + fatalError.message);
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
                vLog("Imagens deletadas.");
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
            vLog(`Buscando código: ${codigo}`);
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
                    vLog("Item encontrado via AJAX");
                    feedback.textContent = '✓ Item encontrado';
                    feedback.classList.remove('hidden', 'text-red-600');
                    feedback.classList.add('text-indigo-600');
                    
                    preview.innerHTML = `
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <h4 class="font-bold text-indigo-900">${data.item.nome_do_produto}</h4>
                            </div>
                        </div>
                    `;
                    preview.classList.remove('hidden');
                } else {
                    vLog("Item NÃO encontrado");
                    feedback.textContent = '× Código não encontrado';
                    feedback.classList.remove('hidden', 'text-indigo-600');
                    feedback.classList.add('text-red-600');
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
                vLog("Botão Confirmar clicado!");
                confirmarTransferencia();
            });
        }
    });

    // Botão de Limpar Log
    document.getElementById('visual-debug-log').onclick = limparLog;
    document.getElementById('visual-debug-log').title = "Clique para limpar histórico e recarregar";
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
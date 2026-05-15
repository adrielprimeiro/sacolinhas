{{-- MODAL: Adicionar Item ao Pedido --}}
<div id="modalAdicionarItem"
     class="fixed inset-0 z-50 flex items-center justify-center"
     style="display:none !important;"
     role="dialog" aria-modal="true">

    {{-- Overlay --}}
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="fecharModalAdicionarItem()"></div>

    {{-- Panel --}}
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden z-10">

        {{-- Header --}}
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-box-open text-blue-600"></i> Adicionar Item ao Pedido
            </h3>
            <button type="button" onclick="fecharModalAdicionarItem()" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        {{-- Body --}}
        <div class="p-6 space-y-4">

            {{-- Campo de busca --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Buscar por código ou nome</label>
                <div class="flex gap-2">
                    <input type="text" id="inputBuscarItem"
                           placeholder="Ex: 12345 ou Camiseta..."
                           class="flex-1 border border-gray-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                           onkeydown="if(event.key==='Enter'){ buscarItem(); }">
                    <button type="button" onclick="buscarItem()"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-xl transition flex items-center gap-2 text-sm">
                        <i id="iconBuscar" class="fas fa-search"></i> Buscar
                    </button>
                </div>
                <p id="erroBusca" class="text-red-500 text-xs mt-1 hidden"></p>
            </div>

            {{-- Resultado da busca --}}
            <div id="resultadoBusca" class="hidden">

                {{-- Preview do item encontrado --}}
                <div id="previewItem" class="bg-gray-50 rounded-2xl p-4 border border-gray-100 flex gap-4 items-start">
                    <img id="itemImagem" src="" alt="" class="w-16 h-16 rounded-xl object-cover border border-gray-200 bg-white flex-shrink-0">
                    <div class="flex-1">
                        <p id="itemNome" class="font-bold text-gray-800 text-sm"></p>
                        <p id="itemDetalhes" class="text-xs text-gray-500 mt-0.5"></p>
                        <span id="itemStatus" class="inline-block mt-1 text-xs font-semibold px-2 py-0.5 rounded-full"></span>
                    </div>
                </div>

                {{-- Preço e quantidade --}}
                <div class="grid grid-cols-2 gap-3 mt-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Preço Unit. (R$)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-bold">R$</span>
                            <input type="number" step="0.01" id="itemPreco"
                                   class="w-full pl-9 border border-gray-300 rounded-xl py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Quantidade</label>
                        <input type="number" id="itemQtde" value="1" min="1"
                               class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    </div>
                </div>

                {{-- Botão confirmar --}}
                <button type="button" id="btnConfirmarAdicionar" onclick="confirmarAdicionarItem()"
                        class="w-full mt-4 bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl shadow transition flex items-center justify-center gap-2 text-sm">
                    <i class="fas fa-plus-circle"></i> Adicionar ao Pedido
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: Confirmar Remoção de Item --}}
<div id="modalRemoverItem"
     class="fixed inset-0 z-50 flex items-center justify-center"
     style="display:none !important;"
     role="dialog" aria-modal="true">

    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="fecharModalRemover()"></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden z-10">
        <div class="bg-red-500 px-6 py-4 flex justify-between items-center">
            <h3 class="text-white font-bold flex items-center gap-2">
                <i class="fas fa-exclamation-triangle"></i> Remover Item
            </h3>
            <button type="button" onclick="fecharModalRemover()" class="text-white opacity-70 hover:opacity-100">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 text-center">
            <p class="text-gray-500 text-xs uppercase font-bold mb-1">Você está removendo:</p>
            <h4 id="nomeItemRemover" class="font-bold text-gray-800 text-lg mb-4"></h4>
            <p class="text-gray-500 text-sm">Esta ação irá excluir o item do pedido. Deseja continuar?</p>
        </div>
        <div class="px-6 pb-6 flex flex-col gap-2">
            <button type="button" id="btnConfirmarRemover" onclick="executarRemocao()"
                    class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-2 rounded-xl transition">
                Sim, remover
            </button>
            <button type="button" onclick="fecharModalRemover()"
                    class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 rounded-xl transition text-sm">
                Cancelar
            </button>
        </div>
    </div>
</div>

<script>
    const PEDIDO_ID    = {{ $pedido->id }};
    const CSRF_TOKEN   = document.querySelector('meta[name="csrf-token"]').content;
    const BUSCA_URL    = '{{ route("admin.pedido.buscarItem") }}';
    const ADD_URL      = `/admin/pedido/${PEDIDO_ID}/adicionar-item`;
    const REMOVE_BASE  = `/admin/pedido/${PEDIDO_ID}/remover-item`;

    let itemSelecionado = null;
    let itemParaRemover = null;

    function mostrar(id) {
        const el = document.getElementById(id);
        if (el) el.setAttribute('style', 'display: flex !important;');
    }
    function esconder(id) {
        const el = document.getElementById(id);
        if (el) el.setAttribute('style', 'display: none !important;');
    }

    function abrirModalAdicionarItem() {
        itemSelecionado = null;
        document.getElementById('inputBuscarItem').value = '';
        document.getElementById('erroBusca').classList.add('hidden');
        document.getElementById('resultadoBusca').classList.add('hidden');
        mostrar('modalAdicionarItem');
        setTimeout(() => document.getElementById('inputBuscarItem').focus(), 100);
    }

    function fecharModalAdicionarItem() {
        esconder('modalAdicionarItem');
    }

    async function buscarItem() {
        const q = document.getElementById('inputBuscarItem').value.trim();
        const erro = document.getElementById('erroBusca');
        erro.classList.add('hidden');

        if (q.length < 2) {
            erro.textContent = 'Digite pelo menos 2 caracteres.';
            erro.classList.remove('hidden');
            return;
        }

        const icon = document.getElementById('iconBuscar');
        icon.className = 'fas fa-spinner fa-spin';

        try {
            const res  = await fetch(`${BUSCA_URL}?q=${encodeURIComponent(q)}`);
            const data = await res.json();

            if (!data.success || !data.data || data.data.length === 0) {
                erro.textContent = 'Nenhum item encontrado.';
                erro.classList.remove('hidden');
                document.getElementById('resultadoBusca').classList.add('hidden');
                return;
            }

            itemSelecionado = data.data[0];
            document.getElementById('itemNome').textContent = itemSelecionado.nome_do_produto;
            document.getElementById('itemDetalhes').textContent = `${itemSelecionado.marca} • Tam: ${itemSelecionado.tamanho}`;
            document.getElementById('itemPreco').value = itemSelecionado.preco_venda;
            
            const img = document.getElementById('itemImagem');
            if (itemSelecionado.image) {
                img.src = '/storage/' + itemSelecionado.image.replace(/^\//, '');
                img.classList.remove('hidden');
            } else {
                img.classList.add('hidden');
            }

            document.getElementById('resultadoBusca').classList.remove('hidden');

        } catch (e) {
            erro.textContent = 'Erro ao buscar item.';
            erro.classList.remove('hidden');
        } finally {
            icon.className = 'fas fa-search';
        }
    }

    async function confirmarAdicionarItem() {
        if (!itemSelecionado) return;

        const preco = document.getElementById('itemPreco').value;
        const qtde  = document.getElementById('itemQtde').value;

        const btn = document.getElementById('btnConfirmarAdicionar');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adicionando...';

        try {
            const res = await fetch(ADD_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({
                    item_id: itemSelecionado.id,
                    preco_unitario: preco,
                    quantidade: qtde
                })
            });

            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Erro ao adicionar item.');
            }
        } catch (e) {
            alert('Erro na requisição.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus-circle"></i> Adicionar ao Pedido';
        }
    }

    function confirmarRemocao(id, nome) {
        itemParaRemover = id;
        document.getElementById('nomeItemRemover').textContent = nome;
        mostrar('modalRemoverItem');
    }

    function fecharModalRemover() {
        esconder('modalRemoverItem');
        itemParaRemover = null;
    }

    async function executarRemocao() {
        if (!itemParaRemover) return;

        const btn = document.getElementById('btnConfirmarRemover');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removendo...';

        try {
            const res = await fetch(`${REMOVE_BASE}/${itemParaRemover}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
            });

            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Erro ao remover item.');
            }
        } catch (e) {
            alert('Erro na requisição.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Sim, remover';
        }
    }
</script>

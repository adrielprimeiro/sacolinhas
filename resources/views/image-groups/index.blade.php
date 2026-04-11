@extends('layouts.app')

@section('title', 'Agrupar Imagens')

@section('content')
<div class="max-w-6xl mx-auto py-8 px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Grupos de Imagens</h1>

        <form method="POST" action="{{ route('image-groups.group-orphans') }}" class="flex items-center gap-2">
            @csrf
            <input
                type="number"
                name="limit"
                value="30"
                min="1"
                max="100"
                class="w-20 border rounded px-2 py-1 text-sm"
                title="Imagens por lote"
            >
            <button
                type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm font-medium transition"
            >
                Agrupar Órfãs
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
            <ul class="list-disc ml-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="space-y-8">
        @forelse($groups as $group)
            @php
                $payload = data_get($group->metadata, 'ai_catalog.payload', []);
                $mediaRef = data_get($group->metadata, 'ai_catalog.media_id_referencia');
                $mediaTag = data_get($group->metadata, 'ai_catalog.media_id_tag');
                $codigoTag = data_get($payload, 'codigo_produto_tag');
            @endphp

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <span class="text-sm font-bold text-indigo-600 uppercase tracking-wider">
                            Grupo #{{ $group->id }}
                        </span>
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ data_get($payload, 'nome_do_produto', 'Produto sem nome') }}
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <form method="POST" action="{{ route('image-groups.edit', $group->id) }}">
                            @csrf
                            <button
                                type="submit"
                                class="text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1"
                            >
                                ✨ IA: Editar Fotos
                            </button>
                        </form>
                    </div>
                </div>

                <form
                    method="POST"
                    action="{{ route('image-groups.transfer', $group->id) }}"
                    class="p-6"
                    onsubmit="event.preventDefault(); confirmarTransferencia(this);"
                >
                    @csrf

                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6 bg-emerald-50 p-4 rounded-lg border border-emerald-100">

						<div class="flex-1">
							<label class="text-sm font-bold text-emerald-800 whitespace-nowrap block mb-2">
								Código na Tag 
							</label>

							<div class="flex flex-col md:flex-row gap-2">
								<input
									type="text"
									name="codigo"
									value="{{ old('codigo', $codigoTag) }}"
									class="codigo-input flex-1 w-full border-emerald-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none shadow-sm"
									placeholder="Ex: 0328"
								>

								<button
									type="button"
									onclick="buscarCodigo(this)"
									class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow transition"
								>
									Conferir Código
								</button>
							</div>

							<div class="codigo-preview mt-3 hidden rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-gray-700"></div>
						</div>


                        <div class="flex-none">
                            <button
                                type="submit"
                                class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white px-8 py-2 rounded-lg font-bold shadow-lg border-b-4 border-blue-800 transition-all active:border-b-0 active:mt-1"
                            >
                                Transferir Selecionadas
                            </button>
                        </div>
                    </div>

                    <div class="text-xs text-gray-500 mb-3 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                        <span>
                            Arraste para ordenar. Selecione as melhores fotos para o anúncio.
                            As não marcadas serão <strong>excluídas permanentemente</strong>.
                        </span>

                        <button
                            type="button"
                            onclick="toggleAll({{ $group->id }})"
                            class="text-indigo-600 hover:underline font-medium text-left md:text-right"
                        >
                            Selecionar Todas
                        </button>
                    </div>

                    <div
                        id="sortable-grid-{{ $group->id }}"
                        class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 sortable-grid"
                    >
                        @foreach($group->medias as $index => $media)
                            @php
                                $isRef = (int) $media->id === (int) $mediaRef;
                                $isTag = (int) $media->id === (int) $mediaTag;
                                $isEdited = data_get($media->metadata, 'edited') === true;
                            @endphp

                            <div
                                class="relative bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm group cursor-move sortable-card"
                                data-id="{{ $media->id }}"
                            >
                                <div
                                    class="absolute top-1 left-1 z-20 bg-black/70 text-white text-[10px] font-bold px-1.5 py-0.5 rounded"
                                    data-order-badge
                                >
                                    {{ $index + 1 }}
                                </div>

                                <div
                                    class="aspect-square overflow-hidden cursor-zoom-in relative"
                                    onclick="openModal(event, '/storage/{{ $media->url }}')"
                                >
                                    <img
                                        src="/storage/{{ $media->thumbnail_url ?: $media->url }}"
                                        class="w-full h-full object-cover transition transform group-hover:scale-105 select-none"
                                        draggable="false"
                                    >

                                    <div class="absolute bottom-1 right-1 flex flex-col gap-1 items-end pointer-events-none">
                                        @if($isRef)
                                            <span class="bg-blue-600 text-white text-[9px] font-bold px-1 py-0.5 rounded shadow-sm">REF</span>
                                        @endif

                                        @if($isTag)
                                            <span class="bg-amber-600 text-white text-[9px] font-bold px-1 py-0.5 rounded shadow-sm">TAG</span>
                                        @endif

                                        @if($isEdited)
                                            <span class="bg-purple-500 text-white text-[9px] font-bold px-1 py-0.5 rounded shadow-sm">EDITADA</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="p-2 bg-gray-50 border-t border-gray-100 flex items-center justify-center">
                                    <label class="flex items-center gap-2 cursor-pointer w-full justify-center">
                                        <input
                                            type="checkbox"
                                            name="media_ids[]"
                                            value="{{ $media->id }}"
                                            class="group-checkbox-{{ $group->id }} w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition-all"
                                            {{ $isEdited ? 'checked' : '' }}
                                        >
                                        <span class="text-[10px] font-bold text-gray-500 uppercase">Selecionar</span>
                                    </label>
                                </div>

                                <button
                                    type="button"
                                    onclick="removeMedia({{ $group->id }}, {{ $media->id }})"
                                    class="absolute top-1 right-1 bg-red-500/90 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs shadow-lg opacity-0 group-hover:opacity-100 transition-opacity z-10"
                                >
                                    ×
                                </button>
                            </div>
                        @endforeach
                    </div>
                </form>
            </div>
        @empty
            <div class="text-center py-12 bg-white rounded-xl border-2 border-dashed border-gray-300">
                <p class="text-gray-500">Nenhum grupo de imagens encontrado.</p>
            </div>
        @endforelse
    </div>

    @if($orphans->count() > 0)
        <div class="mt-12 bg-white border border-yellow-200 rounded-xl p-6 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                <h2 class="text-lg font-semibold text-yellow-800">
                    Imagens sem Grupo ({{ $orphans->count() }})
                </h2>

                <form
                    method="POST"
                    action="{{ route('image-groups.orphans.delete') }}"
                    onsubmit="return confirm('Isso vai excluir PERMANENTEMENTE as imagens órfãs (arquivos e registros). Deseja continuar?')"
                    class="flex items-center gap-2"
                >
                    @csrf
                    <input type="hidden" name="limit" value="200">

                    <button
                        type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm font-semibold border border-red-700"
                    >
                        Excluir órfãs
                    </button>
                </form>
            </div>

            <div class="grid grid-cols-4 md:grid-cols-8 gap-3">
                @foreach($orphans as $media)
                    <div class="relative group aspect-square">
                        <img
                            src="/storage/{{ $media->thumbnail_url ?: $media->url }}"
                            class="w-full h-full object-cover rounded-lg border border-gray-200"
                        >

                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center p-2">
                            <select
                                onchange="addToGroup({{ $media->id }}, this.value)"
                                class="text-[10px] w-full rounded border-none py-1"
                            >
                                <option value="">Mover para...</option>
                                @foreach($groups as $g)
                                    <option value="{{ $g->id }}">Grupo #{{ $g->id }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<div
    id="imageModal"
    class="fixed inset-0 z-[100] hidden bg-black/90 flex items-center justify-center p-4 cursor-pointer"
    onclick="closeModal()"
>
    <button
        type="button"
        class="absolute top-5 right-5 text-white text-4xl hover:text-gray-300 transition"
    >
        &times;
    </button>

    <img
        id="modalImage"
        src=""
        class="max-w-full max-h-full rounded-lg shadow-2xl object-contain"
    >
</div>

<div
    id="editItemModal"
    class="fixed inset-0 z-[110] hidden bg-black/90 items-center justify-center p-4 overflow-y-auto"
>
    <div class="bg-white rounded-lg shadow-2xl max-w-5xl w-full my-8">
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">Editar Item</h2>
            <button
                type="button"
                onclick="closeEditModal()"
                class="text-gray-500 hover:text-gray-700 text-2xl"
            >
                &times;
            </button>
        </div>

        <div id="editItemModalContent" class="p-6 overflow-y-auto max-h-[calc(100vh-160px)]"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    initSortableGrids();

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
            closeEditModal();
        }
    });

    const editModal = document.getElementById('editItemModal');
    editModal.addEventListener('click', function (event) {
        if (event.target === editModal) {
            closeEditModal();
        }
    });
});

function initSortableGrids() {
    document.querySelectorAll('.sortable-grid').forEach(function (grid) {
        new Sortable(grid, {
            animation: 150,
            ghostClass: 'opacity-50',
            dragClass: 'rotate-1',
            onEnd: function () {
                updateOrderBadges(grid);
            }
        });

        updateOrderBadges(grid);
    });
}

function updateOrderBadges(grid) {
    const cards = grid.querySelectorAll('[data-id]');

    cards.forEach(function (card, index) {
        const badge = card.querySelector('[data-order-badge]');
        if (badge) {
            badge.textContent = index + 1;
        }
    });
}

function toggleAll(groupId) {
    const checkboxes = document.querySelectorAll(`.group-checkbox-${groupId}`);
    const allChecked = Array.from(checkboxes).every(function (checkbox) {
        return checkbox.checked;
    });

    checkboxes.forEach(function (checkbox) {
        checkbox.checked = !allChecked;
    });
}

function removeMedia(groupId, mediaId) {
    if (!confirm('Remover esta imagem do grupo?')) {
        return;
    }

    fetch(`/image-groups/${groupId}/remove-media`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ media_id: mediaId })
    }).then(function () {
        location.reload();
    });
}

function addToGroup(mediaId, groupId) {
    if (!groupId) {
        return;
    }

    fetch(`/image-groups/${groupId}/add-media`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ media_id: mediaId })
    }).then(function () {
        location.reload();
    });
}

function confirmarTransferencia(form) {
    const selecionadas = form.querySelectorAll('input[name="media_ids[]"]:checked').length;
    const total = form.querySelectorAll('input[name="media_ids[]"]').length;

    if (selecionadas === 0) {
        const ok = confirm(
            `Nenhuma imagem selecionada.\n\n` +
            `Isso vai EXCLUIR permanentemente as ${total} imagens do grupo e remover o grupo.\n\n` +
            `Deseja continuar?`
        );

        if (ok) {
            transferirEAbrir(form);
        }

        return false;
    }

    const codigo = (form.querySelector('input[name="codigo"]')?.value || '').trim();

    if (!codigo) {
        alert('Informe o Código na tag para transferir as imagens selecionadas.');
        return false;
    }

    const excluidas = total - selecionadas;

    const ok = confirm(
        `Confirma transferir ${selecionadas} imagens para o item ${codigo}?\n\n` +
        `ATENÇÃO: As outras ${excluidas} serão EXCLUÍDAS permanentemente e o grupo será removido.`
    );

    if (ok) {
        transferirEAbrir(form);
    }

    return false;
}

function transferirEAbrir(form) {
    const formData = new FormData(form);
    const sortableGrid = form.querySelector('.sortable-grid');

    if (sortableGrid) {
        const orderedIds = Array.from(sortableGrid.querySelectorAll('[data-id]')).map(function (card) {
            return card.dataset.id;
        });

        const selectedIds = Array.from(
            form.querySelectorAll('input[name="media_ids[]"]:checked')
        ).map(function (input) {
            return input.value;
        });

        const orderedSelectedIds = orderedIds.filter(function (id) {
            return selectedIds.includes(id);
        });

        formData.delete('media_ids[]');
        orderedSelectedIds.forEach(function (id) {
            formData.append('media_ids[]', id);
        });
    }

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(async function (response) {
        const data = await response.json();

        if (!response.ok) {
            throw data;
        }

        return data;
    })
    .then(function (data) {
        if (data.success && data.edit_url) {
            fetch(data.edit_url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            })
            .then(function (response) {
                return response.text();
            })
            .then(function (html) {
                openEditModal(html);
            });
        } else {
            location.reload();
        }
    })
    .catch(function (error) {
        if (error?.errors?.codigo?.length) {
            alert(error.errors.codigo[0]);
            return;
        }

        alert(error.message || 'Erro ao transferir imagens.');
    });
}

function openEditModal(formHtml) {
    const modal = document.getElementById('editItemModal');
    const content = document.getElementById('editItemModalContent');

    content.innerHTML = formHtml;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';

    // ATENÇÃO: Chama a configuração do formulário após injetar o HTML
    setupEditForm();
}

function setupEditForm() {
    const form = document.querySelector('#editItemModalContent form[action*="/items/"]');

    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        e.stopPropagation();

        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok) {
                if (data?.errors) {
                    const firstError = Object.values(data.errors)[0];
                    if (Array.isArray(firstError) && firstError.length) {
                        alert(firstError[0]);
                        return;
                    }
                }

                alert(data.message || 'Erro ao salvar.');
                return;
            }

            closeEditModal();
            location.reload();
        } catch (error) {
            console.error('Erro ao salvar no modal:', error);
            alert('Erro ao salvar o item.');
        }
    }, { once: true });
}


function closeEditModal() {
    const modal = document.getElementById('editItemModal');
    const content = document.getElementById('editItemModalContent');

    content.innerHTML = '';
    modal.classList.add('hidden');
    modal.classList.remove('flex');

    if (document.getElementById('imageModal').classList.contains('hidden')) {
        document.body.style.overflow = 'auto';
    }
}

function openModal(event, src) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');

    modalImg.src = src;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');

    modal.classList.add('hidden');
    modalImg.src = '';

    if (document.getElementById('editItemModal').classList.contains('hidden')) {
        document.body.style.overflow = 'auto';
    }
}

function buscarCodigo(button) {
    const container = button.closest('.flex-1');
    const input = container.querySelector('.codigo-input');
    const preview = container.querySelector('.codigo-preview');
    const codigo = (input.value || '').trim();

    if (!codigo) {
        alert('Digite um código para conferir.');
        return;
    }

    preview.classList.remove('hidden');
    preview.innerHTML = 'Buscando informações do código...';

    fetch("{{ route('image-groups.buscar-codigo') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ codigo: codigo })
    })
    .then(async function(response) {
        const data = await response.json();
        if (!response.ok) {
            throw data;
        }
        return data;
    })
    .then(function(data) {
        const item = data.item;

        preview.innerHTML = `
            <div class="space-y-1">
                <div><strong>ID:</strong> ${item.id}</div>
                <div><strong>Código:</strong> ${item.codigo ?? '-'}</div>
                <div><strong>Nome:</strong> ${item.nome_do_produto ?? '-'}</div>
                <div><strong>Marca:</strong> ${item.marca ?? '-'}</div>
                <div><strong>Cor:</strong> ${item.cor ?? '-'}</div>
                <div><strong>Tam:</strong> ${item.tamanho ?? '-'}</div>
				<div><strong>Estado:</strong> ${item.estado ?? '-'}</div>
				<div><strong>Status:</strong> ${item.status ?? '-'}</div>
            </div>
        `;
    })
    .catch(function(error) {
        preview.innerHTML = `
            <div class="text-red-600 font-medium">
                ${error.message || 'Item não encontrado para este código.'}
            </div>
        `;
    });
}


</script>
@endsection
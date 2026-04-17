@extends('layouts.app')

@section('title', 'Editar: ' . $item->nome_do_produto)

@section('content')
<div class="flex justify-between items-start mb-6">
  <div>
    <h1 class="text-3xl font-semibold text-gray-800">Editar Item</h1>
    <nav class="mt-2 text-sm text-gray-500">
      <a href="{{ route('items.index') }}" class="hover:text-blue-600">Itens</a>
      <span class="mx-2">/</span>
      <span class="text-gray-700">Editar: {{ $item->nome_do_produto }}</span>
    </nav>
  </div>
  <a href="{{ route('items.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md shadow-md transition">
    <i class="fas fa-arrow-left mr-2"></i> Voltar para a Lista
  </a>
</div>

@if ($errors->any())
  <div class="mb-4 bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded">
    <div class="font-semibold mb-2"><i class="fas fa-exclamation-triangle mr-2"></i> Corrija os erros abaixo:</div>
    <ul class="list-disc pl-5">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

{{-- Overlay de carregamento (oculto) --}}
<div id="uploadOverlay" class="hidden fixed inset-0 bg-white/70 z-50 items-center justify-center">
  <div class="bg-white border border-gray-200 shadow-lg rounded-lg px-4 py-3 text-sm text-gray-700">
    <span class="font-semibold">Enviando mídias…</span>
    <span class="ml-2 text-gray-500">aguarde</span>
  </div>
</div>

<form method="POST" action="{{ route('items.update', $item) }}" enctype="multipart/form-data">
  @csrf
  @method('PUT')

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Coluna 1: Galeria de Imagens e Mídias --}}
    <div class="lg:col-span-1">
      <div class="bg-white shadow-lg rounded-lg p-6 sticky top-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Imagens e Mídias</h3>
		<div id="mediaActions" class="hidden mb-3 p-2 rounded-md border border-gray-200 bg-gray-50 flex items-center justify-between">
		  <div class="text-sm text-gray-700">
			Selecionadas: <span id="selectedCount" class="font-semibold">0</span>
		  </div>

		  <button type="button"
				  id="btnEditSelected"
				  class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 px-3 rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
				  disabled>
			Editar
		  </button>
		</div>		

        {{-- ✅ id="mediaGrid" fica AQUI (grid da galeria) --}}
		<div id="mediaGrid" class="grid grid-cols-3 gap-3 mb-4">
			@forelse ($item->medias->sortBy('position') as $media)
				<div class="relative group" data-media-card="{{ $media->id }}">
					
					<!-- Checkbox (Canto Superior Esquerdo) -->
					<label class="absolute top-1 left-1 z-10 bg-white/90 rounded p-1 border border-gray-200 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
						<input type="checkbox" class="media-select w-4 h-4" data-media-id="{{ $media->id }}">
					</label>

					<!-- Alça de Arraste (Canto Superior Direito) -->
					<div class="absolute top-1 right-1 z-10 bg-white/90 rounded p-1 border border-gray-200 cursor-move media-drag-handle opacity-0 group-hover:opacity-100 transition-opacity" title="Arraste para ordenar">
						<i class="fas fa-grip-vertical text-gray-600"></i>
					</div>

					@php
						$rawUrl = $media->thumbnail_url ?: $media->url;
						$finalUrl = str_starts_with($rawUrl, 'http') ? $rawUrl : asset('storage/' . ltrim($rawUrl, '/'));
					@endphp

					<img src="{{ $finalUrl }}" class="w-full h-24 object-cover rounded-md border border-gray-200">

					<!-- Estrela de Capa (Canto Inferior Direito para não sobrepor a alça) -->
					@if ($media->is_cover)
						<div class="absolute bottom-1 right-1 bg-yellow-400 text-white w-5 h-5 flex items-center justify-center rounded-full text-xs shadow-sm">
							<i class="fas fa-star"></i>
						</div>
					@endif

					<!-- Botão Remover -->
					<div class="absolute top-0 right-0 opacity-0 group-hover:opacity-100 transition-opacity">
						<button type="button"
								onclick="if(confirm('Remover mídia?')) document.getElementById('delete-medias-{{ $media->id }}').submit();"
								class="bg-red-600 text-white w-6 h-6 flex items-center justify-center rounded-full -mt-2 -mr-2 shadow-lg z-20">
							<i class="fas fa-times text-sm"></i>
						</button>
					</div>
				</div>
			@empty
				<div class="col-span-3 text-center text-sm text-gray-500 py-4">Nenhuma mídia cadastrada.</div>
			@endforelse
		</div>
 

        {{-- Quadrinho "+" --}}
        <button type="button"
                id="addMediaPlaceholder"
                class="w-full h-24 rounded-md border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center cursor-pointer hover:bg-gray-100 transition"
                aria-label="Adicionar mídias">
          <span class="text-gray-400 text-3xl font-semibold leading-none">+</span>
        </button>

        <input type="file"
               id="new_media"
               name="new_media[]"
               multiple
               accept="image/*,video/*"
               class="hidden">
      </div>
    </div>

    {{-- Coluna 2: Formulário de Detalhes do Item (PRESERVADO) --}}
    <div class="lg:col-span-2">
      <div class="bg-white shadow-lg rounded-lg p-6">

        {{-- Linha 1: Código + Nome --}}
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
          <div class="md:col-span-6">
            <label for="codigo" class="block text-sm font-medium text-gray-700 mb-1">Código *</label>
            <div class="relative">
              <input type="text" id="codigo" name="codigo"
                     value="{{ old('codigo', $item->codigo) }}"
                     required
                     class="w-full border border-gray-300 rounded-md pl-3 pr-12 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('codigo') border-red-400 @enderror"
                     placeholder="Ex: SKU12345" autocomplete="off" />

              <button type="button" id="btnToggleQr"
                      class="absolute inset-y-0 right-0 flex items-center justify-center w-11 text-gray-500 hover:text-indigo-700"
                      title="Ler QRCode e preencher o código">
                <i class="fas fa-qrcode text-lg"></i>
              </button>
            </div>
            @error('codigo')
              <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror

            <div id="qrReaderWrap" class="hidden mt-3 border border-gray-200 rounded-md p-3 bg-gray-50">
              <div class="text-sm text-gray-600 mb-2">Permita o acesso à câmera e aponte para o QRCode.</div>
              <div id="qrReader" class="w-full"></div>
              <div class="mt-2">
                <button type="button" id="btnCloseQr" class="text-sm text-gray-600 hover:text-gray-900">Fechar leitor</button>
              </div>
            </div>
          </div>

          <div class="md:col-span-6">
            <label for="nome_do_produto" class="block text-sm font-medium text-gray-700 mb-1">Nome do Produto *</label>
            <input type="text" id="nome_do_produto" name="nome_do_produto"
                   value="{{ old('nome_do_produto', $item->nome_do_produto) }}"
                   required
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nome_do_produto') border-red-400 @enderror"
                   placeholder="Ex: Hambúrguer Artesanal" />
            @error('nome_do_produto')
              <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
          </div>
        </div>

        {{-- Descrição --}}
        <div class="mt-4">
          <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
          <textarea id="descricao" name="descricao" rows="4"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('descricao') border-red-400 @enderror"
                    placeholder="Descreva o item, ingredientes, características especiais...">{{ old('descricao', $item->descricao) }}</textarea>
          @error('descricao')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
          @enderror
        </div>

        {{-- Linha 2: Custo + Preço + Categoria --}}
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mt-4">
          <div class="md:col-span-4">
            <label for="custo" class="block text-sm font-medium text-gray-700 mb-1">Custo (R$)</label>
            <div class="flex">
              <span class="inline-flex items-center px-3 rounded-l-md border border-gray-300 bg-gray-50 text-gray-600">R$</span>
              <input type="number" id="custo" name="custo"
                     value="{{ old('custo', $item->custo) }}"
                     step="0.01" min="0"
                     class="w-full border border-gray-300 rounded-r-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('custo') border-red-400 @enderror" />
            </div>
            @error('custo')
              <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
          </div>

          <div class="md:col-span-4">
            <label for="preco" class="block text-sm font-medium text-gray-700 mb-1">Preço (R$) *</label>
            <div class="flex">
              <span class="inline-flex items-center px-3 rounded-l-md border border-gray-300 bg-gray-50 text-gray-600">R$</span>
              <input type="number" id="preco" name="preco"
                     value="{{ old('preco', $item->preco) }}"
                     step="0.01" min="0.01" required
                     class="w-full border border-gray-300 rounded-r-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('preco') border-red-400 @enderror"
                     placeholder="0,00" />
            </div>
            @error('preco')
              <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
          </div>

          <div class="md:col-span-4">
            <label for="codigo_da_categoria" class="block text-sm font-medium text-gray-700 mb-1">Código da Categoria</label>
            <input type="text" id="codigo_da_categoria" name="codigo_da_categoria"
                   value="{{ old('codigo_da_categoria', $item->codigo_da_categoria) }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('codigo_da_categoria') border-red-400 @enderror"
                   placeholder="Ex: LANCHES, BEBIDAS" />
            @error('codigo_da_categoria')
              <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
          </div>
        </div>

        {{-- Categorias Novo Sistema --}}
        @include('admin.items.partials.categories', ['item' => $item])

        {{-- Linha 3: Marca + Modelo + Estado --}}
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mt-4">
          <div class="md:col-span-4">
            <label for="marca" class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
            <input type="text" id="marca" name="marca"
                   value="{{ old('marca', $item->marca) }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('marca') border-red-400 @enderror"
                   placeholder="Ex: Nike, Apple" />
            @error('marca')
              <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
          </div>

          <div class="md:col-span-4">
            <label for="modelo" class="block text-sm font-medium text-gray-700 mb-1">Modelo</label>
            <input type="text" id="modelo" name="modelo"
                   value="{{ old('modelo', $item->modelo) }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('modelo') border-red-400 @enderror"
                   placeholder="Ex: Air Max, iPhone 15" />
            @error('modelo')
              <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
          </div>

          <div class="md:col-span-4">
            <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
            <select id="estado" name="estado" required
                    class="w-full border border-gray-300 rounded-md px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 @error('estado') border-red-400 @enderror">
              <option value="novo" {{ old('estado', $item->estado) == 'novo' ? 'selected' : '' }}>Novo</option>
              <option value="usado" {{ old('estado', $item->estado) == 'usado' ? 'selected' : '' }}>Usado</option>
              <option value="semi-novo" {{ old('estado', $item->estado) == 'semi-novo' ? 'selected' : '' }}>Semi-novo</option>
              <option value="recondicionado" {{ old('estado', $item->estado) == 'recondicionado' ? 'selected' : '' }}>Recondicionado</option>
            </select>
            @error('estado')
              <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
          </div>
        </div>

        {{-- Linha 4: Cor + Tamanho + Pedido --}}
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mt-4">
          <div class="md:col-span-4">
            <label for="cor" class="block text-sm font-medium text-gray-700 mb-1">Cor</label>
            <input type="text" id="cor" name="cor"
                   value="{{ old('cor', $item->cor) }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('cor') border-red-400 @enderror"
                   placeholder="Ex: Azul, Vermelho" />
            @error('cor')
              <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
          </div>

          <div class="md:col-span-4">
            <label for="tamanho" class="block text-sm font-medium text-gray-700 mb-1">Tamanho</label>
            <input type="text" id="tamanho" name="tamanho"
                   value="{{ old('tamanho', $item->tamanho) }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tamanho') border-red-400 @enderror"
                   placeholder="Ex: P, M, G, 38, 40" />
            @error('tamanho')
              <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
          </div>

          <div class="md:col-span-4">
            <label for="pedido" class="block text-sm font-medium text-gray-700 mb-1">Pedido (Opcional)</label>
            <input type="text" id="pedido" name="pedido"
                   value="{{ old('pedido', $item->pedido) }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('pedido') border-red-400 @enderror"
                   placeholder="Ex: Pedido#123" />
            @error('pedido')
              <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
            @enderror
          </div>
        </div>

        {{-- Status --}}
        <div class="mt-4">
          <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
          <select id="status" name="status" required
                  class="w-full border border-gray-300 rounded-md px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 @error('status') border-red-400 @enderror">
            <option value="indisponivel" {{ old('status', $item->status) == 'indisponivel' ? 'selected' : '' }}>Indisponível</option>
            <option value="disponivel" {{ old('status', $item->status) == 'disponivel' ? 'selected' : '' }}>Disponível</option>
            <option value="reservado" {{ old('status', $item->status) == 'reservado' ? 'selected' : '' }}>Reservado</option>
            <option value="vendido" {{ old('status', $item->status) == 'vendido' ? 'selected' : '' }}>Vendido</option>
            <option value="em_sacolinha" {{ old('status', $item->status) == 'em_sacolinha' ? 'selected' : '' }}>Em Sacolinha</option>
            <option value="loja" {{ old('status', $item->status) == 'loja' ? 'selected' : '' }}>Loja</option>
            <option value="estoque" {{ old('status', $item->status) == 'estoque' ? 'selected' : '' }}>Estoque</option>
            <option value="live" {{ old('status', $item->status) == 'live' ? 'selected' : '' }}>Live</option>
			<option value="solicitado na loja" {{ old('status', $item->status) == 'solicitado na loja' ? 'selected' : '' }}>Solicitado na Loja</option>
			<option value="solicitado na live" {{ old('status', $item->status) == 'solicitado na live' ? 'selected' : '' }}>Solicitado na Live</option>
          </select>
		  
          @error('status')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
          @enderror
        </div>
		
        {{-- Botões --}}
        <div class="mt-8 flex justify-end border-t border-gray-200 pt-6">
          <a href="{{ route('items.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md shadow-md transition mr-3">
            Cancelar
          </a>
          <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition">
            <i class="fas fa-save mr-2"></i> Salvar Alterações
          </button>
        </div>

      </div>
    </div>

  </div>
</form>

{{-- deletes --}}
@foreach($item->medias as $media)
  <form id="delete-medias-{{ $media->id }}"
        action="{{ route('items.medias.destroy', ['item' => $item->id, 'medias' => $media->id]) }}"
        method="POST" style="display:none;">
    @csrf
    @method('DELETE')
  </form>
@endforeach

@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
(function () {
  // 1. Definição de Elementos e URLs
  const grid = document.getElementById('mediaGrid');
  const addBtn = document.getElementById('addMediaPlaceholder');
  const input = document.getElementById('new_media');
  const overlay = document.getElementById('uploadOverlay');
  const actions = document.getElementById('mediaActions');
  const selectedCount = document.getElementById('selectedCount');
  const btnEdit = document.getElementById('btnEditSelected');

  const uploadUrl = "{{ route('items.media.upload', $item) }}";
  const aiEditUrl = "{{ route('items.media.aiEdit', $item) }}";
  const reorderUrl = "{{ route('items.media.reorder', $item) }}";

  // 2. Funções Auxiliares
  function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  function showOverlay() { overlay?.classList.replace('hidden', 'flex'); }
  function hideOverlay() { overlay?.classList.replace('flex', 'hidden'); }

  function buildSelectCheckbox(mediaId) {
    return `<label class="absolute top-1 left-1 z-10 bg-white/90 rounded p-1 border border-gray-200 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
      <input type="checkbox" class="media-select w-4 h-4" data-media-id="${mediaId}">
    </label>`;
  }

  function buildDragHandle() {
    return `<div class="absolute top-1 right-1 z-10 bg-white/90 rounded p-1 border border-gray-200 cursor-move media-drag-handle opacity-0 group-hover:opacity-100 transition-opacity" title="Arraste para ordenar">
      <i class="fas fa-grip-vertical text-gray-600"></i>
    </div>`;
  }

  // 3. Lógica de Ordenação (Sortable)
  async function saveOrder() {
    if (!grid) return;
    const orderedIds = Array.from(grid.querySelectorAll('[data-media-card]'))
      .map(el => el.getAttribute('data-media-card'))
      .filter(Boolean);

    if (orderedIds.length === 0) return;

    const resp = await fetch(reorderUrl, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ ordered_ids: orderedIds }),
    });

    if (!resp.ok) throw new Error('Erro ao salvar no servidor');
    console.log('Ordem salva com sucesso!');
  }

  if (grid && typeof Sortable !== 'undefined') {
    new Sortable(grid, {
      animation: 150,
      handle: '.media-drag-handle',
      draggable: '[data-media-card]',
      ghostClass: 'opacity-50',
      onEnd: async () => {
        try {
          await saveOrder();
        } catch (e) {
          console.error(e);
          alert('Não consegui salvar a ordem das mídias.');
        }
      },
    });
  }

  // 4. Upload de Arquivos
  if (addBtn && input) {
    addBtn.addEventListener('click', () => input.click());
    input.addEventListener('change', async () => {
      if (!input.files?.length) return;
      showOverlay();
      
      const fd = new FormData();
      for (const f of input.files) fd.append('new_media[]', f);

      try {
        const resp = await fetch(uploadUrl, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
          body: fd,
        });
        const data = await resp.json();
        
        if (data.ok && Array.isArray(data.media)) {
          data.media.forEach(m => {
            const imgSrc = m.final_url || m.thumb || m.url;
            const wrap = document.createElement('div');
            wrap.className = 'relative group';
            wrap.setAttribute('data-media-card', m.id);
            wrap.innerHTML = `${buildSelectCheckbox(m.id)}${buildDragHandle()}
              <img src="${imgSrc}" class="w-full h-24 object-cover rounded-md border border-gray-200">`;
            grid.prepend(wrap);
          });
          await saveOrder();
        }
      } catch (e) {
        alert('Falha no upload.');
      } finally {
        hideOverlay();
        input.value = '';
      }
    });
  }

  // 5. Seleção e Edição IA
  function refreshSelectionUI() {
    const selected = document.querySelectorAll('.media-select:checked');
    const count = selected.length;
    if (actions) actions.classList.toggle('hidden', count === 0);
    if (selectedCount) selectedCount.textContent = count;
    if (btnEdit) btnEdit.disabled = count === 0;
  }

  document.addEventListener('change', (e) => {
    if (e.target.classList.contains('media-select')) refreshSelectionUI();
  });

  if (btnEdit) {
    btnEdit.addEventListener('click', async () => {
      const ids = Array.from(document.querySelectorAll('.media-select:checked')).map(cb => cb.dataset.mediaId);
      showOverlay();
      try {
        const resp = await fetch(aiEditUrl, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
          body: JSON.stringify({ media_ids: ids }),
        });
        const data = await resp.json();
        if (data.ok) {
          data.updated.forEach(u => {
            const img = document.querySelector(`[data-media-card="${u.id}"] img`);
            if (img) img.src = u.final_url + '?t=' + Date.now();
          });
          alert('Edição concluída.');
        }
      } catch (e) {
        alert('Erro na edição IA.');
      } finally { hideOverlay(); }
    });
  }
})();
</script>
@endpush
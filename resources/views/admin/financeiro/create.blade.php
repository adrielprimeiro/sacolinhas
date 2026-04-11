@extends('layouts.app')

@section('title', 'Novo Lançamento')
@section('brand_route', 'admin.financeiro.index')
@section('brand_icon', 'fas fa-wallet')

@section('content')
	<style>
	  #user_list .user-item.active { background: #eff6ff; }
	</style>

    <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-lg p-6">
        <h1 class="text-3xl font-semibold text-gray-800 mb-6">Novo Lançamento</h1>

        <form action="{{ route('admin.financeiro.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Usuário -->
				<!-- Usuário (Autocomplete AJAX) -->
				@php
					$oldUserId = old('user_id');

					// Usa a lista $users já disponível na view para exibir o "texto" ao voltar com erro
					$oldUser = null;
					if (!empty($oldUserId) && isset($users)) {
						$oldUser = $users->firstWhere('id', (int) $oldUserId);
					}

					$oldUserText = $oldUser
						? ($oldUser->name . (!empty($oldUser->email) ? ' — ' . $oldUser->email : ''))
						: '';
				@endphp

				<div class="relative">
				  <label for="user_search" class="block text-sm font-medium text-gray-700 mb-1">Usuário</label>

					<input id="user_search" type="text"
					  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3
							 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('user_id') border-red-500 @enderror"
					  placeholder="Digite nome ou e-mail..."
					  autocomplete="off"
					  value="{{ $oldUserText }}">

					<input type="hidden" name="user_id" id="user_id_hidden" value="{{ $oldUserId }}">

				  <div id="user_list"
					   class="absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-md shadow-lg hidden overflow-auto"
					   style="max-height: 260px; z-index: 9999;">
				  </div>
				  @error('user_id')
					  <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    			  @enderror
				</div>

                <!-- Data e Hora da Movimentação -->
                <div>
                    <label for="data_movimentacao" class="block text-sm font-medium text-gray-700 mb-1">Data e Hora da Movimentação</label>
                    <input type="datetime-local" name="data_movimentacao" id="data_movimentacao" value="{{ old('data_movimentacao', now()->format('Y-m-d\TH:i')) }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('data_movimentacao') border-red-500 @enderror">
                    @error('data_movimentacao')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Tipo de Movimentação -->
                <div>
                    <label for="tipo_movimentacao" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Movimentação</label>
                    <select name="tipo_movimentacao" id="tipo_movimentacao" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('tipo_movimentacao') border-red-500 @enderror">
                        <option value="">Selecione o tipo...</option>
                        <option value="credito" {{ old('tipo_movimentacao') == 'credito' ? 'selected' : '' }}>Crédito</option>
                        <option value="debito" {{ old('tipo_movimentacao') == 'debito' ? 'selected' : '' }}>Débito</option>
                    </select>
                    @error('tipo_movimentacao')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Valor -->
                <div>
                    <label for="valor" class="block text-sm font-medium text-gray-700 mb-1">Valor</label>
                    <input type="number" step="0.01" name="valor" id="valor" value="{{ old('valor') }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('valor') border-red-500 @enderror" placeholder="Ex: 150.75">
                    @error('valor')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Descrição -->
            <div class="mb-6">
                <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                <textarea name="descricao" id="descricao" rows="3"
                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('descricao') border-red-500 @enderror" placeholder="Descreva o lançamento financeiro...">{{ old('descricao') }}</textarea>
                @error('descricao')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Classificação Financeira -->
            <div class="mb-6">
                <label for="classificacao_id" class="block text-sm font-medium text-gray-700 mb-1">Classificação Financeira</label>
                <select name="classificacao_id" id="classificacao_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('classificacao_id') border-red-500 @enderror">
                    <option value="">Selecione a classificação...</option>
                    @foreach ($classificacoes as $classificacao)
                        <option value="{{ $classificacao->id }}" {{ old('classificacao_id') == $classificacao->id ? 'selected' : '' }}>
                            {{ $classificacao->nome }} ({{ $classificacao->codigo_contabil }})
                        </option>
                    @endforeach
                </select>
                @error('classificacao_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Tipo de Referência (Opcional) -->
                <div>
                    <label for="referencia_tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Referência (Opcional)</label>
                    <select name="referencia_tipo" id="referencia_tipo" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('referencia_tipo') border-red-500 @enderror">
                        <option value="">Nenhum</option>
                        <option value="sacolinha" {{ old('referencia_tipo') == 'sacolinha' ? 'selected' : '' }}>Sacolinha</option>
                        <option value="pagamento" {{ old('referencia_tipo') == 'pagamento' ? 'selected' : '' }}>Pagamento</option>
                        <option value="pedido" {{ old('referencia_tipo') == 'pedido' ? 'selected' : '' }}>Pedido</option>
                        <option value="ajuste" {{ old('referencia_tipo') == 'ajuste' ? 'selected' : '' }}>Ajuste</option>
                        <option value="desconto" {{ old('referencia_tipo') == 'desconto' ? 'selected' : '' }}>Desconto</option>
                        <!-- Adicione mais tipos conforme necessário -->
                    </select>
                    @error('referencia_tipo')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- ID da Referência (Opcional, visibilidade controlada por JS) -->
                <div id="referencia_id_field" class="{{ old('referencia_tipo') ? '' : 'hidden' }}">
                    <label for="referencia_id" class="block text-sm font-medium text-gray-700 mb-1">ID da Referência</label>
                    <input type="text" name="referencia_id" id="referencia_id" value="{{ old('referencia_id') }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('referencia_id') border-red-500 @enderror" placeholder="Ex: 12345">
                    @error('referencia_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- ID da Live (Opcional) -->
            <div class="mb-6">
                <label for="live_id" class="block text-sm font-medium text-gray-700 mb-1">ID da Live (Opcional)</label>
                <input type="text" name="live_id" id="live_id" value="{{ old('live_id') }}"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('live_id') border-red-500 @enderror" placeholder="Ex: 98765">
                @error('live_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Observações (Opcional) -->
            <div class="mb-6">
                <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações (Opcional)</label>
                <textarea name="observacoes" id="observacoes" rows="3"
                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('observacoes') border-red-500 @enderror" placeholder="Adicione quaisquer observações relevantes...">{{ old('observacoes') }}</textarea>
                @error('observacoes')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end space-x-4">
                <a href="{{ route('admin.financeiro.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                    <i class="fas fa-times-circle mr-2"></i> Cancelar
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300">
                    <i class="fas fa-save mr-2"></i> Salvar Lançamento
                </button>
            </div>
        </form>
    </div>

	<script>
	document.addEventListener('DOMContentLoaded', function () {
	  // =========================
	  // 1) Toggle Referência ID
	  // =========================
	  var referenciaTipoSelect = document.getElementById('referencia_tipo');
	  var referenciaIdField = document.getElementById('referencia_id_field');

	  function toggleReferenciaIdField() {
		if (!referenciaTipoSelect || !referenciaIdField) return;
		if (referenciaTipoSelect.value) referenciaIdField.classList.remove('hidden');
		else referenciaIdField.classList.add('hidden');
	  }

	  if (referenciaTipoSelect) {
		referenciaTipoSelect.addEventListener('change', toggleReferenciaIdField);
		toggleReferenciaIdField();
	  }

	  // =========================
	  // 2) Autocomplete Usuário (Vanilla)
	  // =========================
	  var input = document.getElementById('user_search');
	  var hidden = document.getElementById('user_id_hidden'); // <- ID novo
	  var list = document.getElementById('user_list');

	  // Se não existir nessa página, não faz nada
	  if (!input || !hidden || !list) return;

	  var debounceTimer = null;
	  var abortCtrl = null;

	  var activeIndex = -1;
	  var currentItems = [];

	  function escapeHtml(s) {
		s = (s === null || s === undefined) ? '' : String(s);
		return s.replace(/&/g,'&amp;')
				.replace(/</g,'&lt;')
				.replace(/>/g,'&gt;')
				.replace(/"/g,'&quot;')
				.replace(/'/g,'&#039;');
	  }


	  function showList() {
		list.classList.remove('hidden');
		list.style.display = 'block';
	  }

	  function hideList() {
		list.classList.add('hidden');
		list.style.display = 'none';
		activeIndex = -1;
		currentItems = [];
	  }

	  function setListHtml(html) {
		list.innerHTML = html;
		showList();
	  }

	  function showLoading() {
		setListHtml('<div class="px-3 py-2 text-sm text-gray-500">Buscando...</div>');
	  }

	  function showEmpty(q) {
		setListHtml('<div class="px-3 py-2 text-sm text-gray-500">Nenhum resultado para "' + escapeHtml(q) + '"</div>');
	  }

	  function showError() {
		setListHtml('<div class="px-3 py-2 text-sm text-red-600">Erro ao buscar usuários</div>');
	  }

	  function setActive(index) {
		var items = list.querySelectorAll('.user-item');
		if (!items.length) return;

		if (index < 0) index = items.length - 1;
		if (index >= items.length) index = 0;

		activeIndex = index;

		for (var i = 0; i < items.length; i++) items[i].classList.remove('active');
		items[activeIndex].classList.add('active');

		if (items[activeIndex].scrollIntoView) {
		  items[activeIndex].scrollIntoView({ block: 'nearest' });
		}
	  }

		function selectUser(user) {
		  var name = user && user.name ? String(user.name) : '';
		  input.value = name;
		  hidden.value = user && user.id ? user.id : '';
		  hideList();
		}

	  function normalizeResponse(data) {
		if (Array.isArray(data)) return data;
		if (data && Array.isArray(data.data)) return data.data;
		return [];
	  }


		function renderResults(q, data) {
		  var users = normalizeResponse(data);

		  currentItems = users;
		  activeIndex = -1;

		  if (!users.length) {
			showEmpty(q);
			return;
		  }

		  var qLower = String(q || '').toLowerCase();
		  var html = '';

		  for (var i = 0; i < users.length; i++) {
			var u = users[i] || {};
			var rawName = (u.name === null || u.name === undefined) ? '' : String(u.name);

			var safeId = escapeHtml(u.id);
			var safeName = escapeHtml(rawName);

			// highlight sem regex (estável)
			var displayName = safeName;
			var pos = qLower ? rawName.toLowerCase().indexOf(qLower) : -1;
			if (pos >= 0 && qLower.length > 0) {
			  displayName =
				escapeHtml(rawName.substring(0, pos)) +
				'<strong>' + escapeHtml(rawName.substring(pos, pos + qLower.length)) + '</strong>' +
				escapeHtml(rawName.substring(pos + qLower.length));
			}

			html += ''
			  + '<button type="button" '
			  + 'class="user-item w-full text-left px-3 py-2 text-sm hover:bg-gray-50 border-b border-gray-100" '
			  + 'data-idx="' + i + '">'
			  +   '<div class="flex items-center justify-between gap-3">'
			  +     '<div class="font-medium text-gray-900">' + displayName + '</div>'
			  +     '<div class="text-xs text-gray-500 whitespace-nowrap">#' + safeId + '</div>'
			  +   '</div>'
			  + '</button>';
		  }

		  setListHtml(html);

		  var last = list.querySelector('.user-item:last-child');
		  if (last) last.classList.remove('border-b');
		}
	  
	  
	  
	  function fetchUsers(q) {
		var query = (q === null || q === undefined) ? '' : String(q);
		query = query.trim();

		// Importantíssimo: se editar o texto, zera o ID pra não enviar ID antigo por engano
		if (query.length === 0) {
		  hidden.value = '';
		  hideList();
		  return;
		}

		if (query.length < 2) {
		  hideList();
		  return;
		}

		if (abortCtrl) abortCtrl.abort();
		abortCtrl = new AbortController();

		showLoading();

		fetch('/api/users/search?q=' + encodeURIComponent(query), {
		  method: 'GET',
		  headers: { 'Accept': 'application/json' },
		  signal: abortCtrl.signal
		})
		.then(function (res) {
		  if (!res.ok) throw new Error('HTTP ' + res.status);
		  return res.json();
		})
		.then(function (data) {
		  renderResults(query, data);
		})
		.catch(function (e) {
		  if (e && e.name === 'AbortError') return;
		  showError();
		});
	  }

	  // Debounce
	  input.addEventListener('input', function () {
		clearTimeout(debounceTimer);
		var q = input.value;
		debounceTimer = setTimeout(function () { fetchUsers(q); }, 250);
	  });

	  // Teclado
	  input.addEventListener('keydown', function (e) {
		if (list.classList.contains('hidden')) return;

		if (e.key === 'ArrowDown') {
		  e.preventDefault();
		  setActive(activeIndex + 1);
		} else if (e.key === 'ArrowUp') {
		  e.preventDefault();
		  setActive(activeIndex - 1);
		} else if (e.key === 'Enter') {
		  if (activeIndex >= 0 && currentItems[activeIndex]) {
			e.preventDefault();
			selectUser(currentItems[activeIndex]);
		  }
		} else if (e.key === 'Escape') {
		  e.preventDefault();
		  hideList();
		}
	  });

	  // Clique em item
	  list.addEventListener('click', function (e) {
		var btn = e.target.closest('.user-item');
		if (!btn) return;

		var idx = parseInt(btn.getAttribute('data-idx'), 10);
		if (currentItems[idx]) selectUser(currentItems[idx]);
	  });

	  // Clique fora fecha
	  document.addEventListener('click', function (e) {
		if (e.target.closest('#user_search') || e.target.closest('#user_list')) return;
		hideList();
	  });

	  // Estado inicial
	  hideList();
	});
	</script>

@endsection
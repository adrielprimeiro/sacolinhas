@extends('layouts.app')

@section('title', 'Registrar Mensalidade')
@section('brand_route', 'dashboard') {{-- ajuste se você tiver uma rota melhor --}}
@section('brand_icon', 'fas fa-id-card')

@section('content')
    <style>
      #user_list .user-item.active { background: #eff6ff; }
    </style>

    <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-lg p-6">
        <h1 class="text-3xl font-semibold text-gray-800 mb-6">Registrar Mensalidade</h1>

        @if (session('status'))
            <div class="mb-4 p-3 rounded bg-green-50 text-green-700 border border-green-200">
                {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('admin.clube.mensalidades.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- Cliente (Autocomplete AJAX) --}}
                @php
                    $oldUserId = old('user_id');

                    $oldUser = null;
                    if (!empty($oldUserId) && isset($users)) {
                        $oldUser = $users->firstWhere('id', (int) $oldUserId);
                    }

                    $oldUserText = $oldUser
                        ? ($oldUser->name . (!empty($oldUser->email) ? ' — ' . $oldUser->email : ''))
                        : '';
                @endphp

                <div class="relative">
                    <label for="user_search" class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>

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

                {{-- Competência --}}
                <div>
                    <label for="competencia" class="block text-sm font-medium text-gray-700 mb-1">Competência (Ano/Mês)</label>
                    <input type="month" name="competencia" id="competencia" value="{{ old('competencia') }}"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3
                               focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('competencia') border-red-500 @enderror"
                        required>
                    @error('competencia')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- Valor --}}
                <div>
                    <label for="valor" class="block text-sm font-medium text-gray-700 mb-1">Valor</label>
                    <input type="number" step="0.01" min="0" name="valor" id="valor" value="{{ old('valor') }}"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3
                               focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('valor') border-red-500 @enderror"
                        placeholder="Ex: 49.90"
                        required>
                    @error('valor')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Data pagamento --}}
                <div>
                    <label for="data_pagamento" class="block text-sm font-medium text-gray-700 mb-1">Data do pagamento</label>
                    <input type="date" name="data_pagamento" id="data_pagamento"
                        value="{{ old('data_pagamento', now()->toDateString()) }}"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3
                               focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('data_pagamento') border-red-500 @enderror"
                        required>
                    @error('data_pagamento')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end space-x-4">
                <a href="{{ url('/admin') }}"
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                    <i class="fas fa-times-circle mr-2"></i> Cancelar
                </a>

                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300">
                    <i class="fas fa-save mr-2"></i> Salvar Mensalidade
                </button>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
      var input = document.getElementById('user_search');
      var hidden = document.getElementById('user_id_hidden');
      var list = document.getElementById('user_list');

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
        setListHtml('<div class="px-3 py-2 text-sm text-red-600">Erro ao buscar clientes</div>');
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

      function normalizeResponse(data) {
        if (Array.isArray(data)) return data;
        if (data && Array.isArray(data.data)) return data.data;
        return [];
      }

      function selectUser(user) {
        var name = user && user.name ? String(user.name) : '';
        input.value = name;
        hidden.value = user && user.id ? user.id : '';
        hideList();
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

      input.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        var q = input.value;
        debounceTimer = setTimeout(function () { fetchUsers(q); }, 250);
      });

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

      list.addEventListener('click', function (e) {
        var btn = e.target.closest('.user-item');
        if (!btn) return;

        var idx = parseInt(btn.getAttribute('data-idx'), 10);
        if (currentItems[idx]) selectUser(currentItems[idx]);
      });

      document.addEventListener('click', function (e) {
        if (e.target.closest('#user_search') || e.target.closest('#user_list')) return;
        hideList();
      });

      hideList();
    });
    </script>
@endsection
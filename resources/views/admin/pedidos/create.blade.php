@extends('layouts.app')

@section('title', 'Novo Pedido')
@section('brand_route', 'admin.pedido.index')
@section('brand_icon', 'fas fa-receipt')

@section('content')
    <style>
      #user_list .user-item.active { background: #eff6ff; }
    </style>

    <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg p-6">
        <h1 class="text-3xl font-semibold text-gray-800 mb-6">Novo Pedido</h1>

        <form action="{{ route('admin.pedido.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- Usuário (Autocomplete AJAX) - idêntico ao Financeiro --}}
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
                    <label for="user_search" class="block text-sm font-medium text-gray-700 mb-1">Usuário</label>

                    <input id="user_search" type="text"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('user_id') border-red-500 @enderror"
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

                {{-- Número do Pedido --}}
                <div>
                    <label for="numero_pedido" class="block text-sm font-medium text-gray-700 mb-1">Número do Pedido</label>
                    <input type="text" name="numero_pedido" id="numero_pedido" value="{{ old('numero_pedido') }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('numero_pedido') border-red-500 @enderror"
                           placeholder="Ex: PED-000123">
                    @error('numero_pedido')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Data do Pedido --}}
                <div>
                    <label for="data_pedido" class="block text-sm font-medium text-gray-700 mb-1">Data do Pedido</label>
                    <input type="datetime-local" name="data_pedido" id="data_pedido"
                           value="{{ old('data_pedido', now()->format('Y-m-d\TH:i')) }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('data_pedido') border-red-500 @enderror">
                    @error('data_pedido')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Live ID (Opcional) --}}
                <div>
                    <label for="live_id" class="block text-sm font-medium text-gray-700 mb-1">ID da Live (Opcional)</label>
                    <input type="number" name="live_id" id="live_id" value="{{ old('live_id') }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('live_id') border-red-500 @enderror"
                           placeholder="Ex: 98765">
                    @error('live_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- Status do Pedido --}}
                <div>
                    <label for="status_pedido" class="block text-sm font-medium text-gray-700 mb-1">Status do Pedido</label>
                    <select name="status_pedido" id="status_pedido"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('status_pedido') border-red-500 @enderror">
                        @php $sp = old('status_pedido', 'pendente'); @endphp
                        <option value="pendente" {{ $sp === 'pendente' ? 'selected' : '' }}>Pendente</option>
                        <option value="confirmado" {{ $sp === 'confirmado' ? 'selected' : '' }}>Confirmado</option>
                        <option value="processando" {{ $sp === 'processando' ? 'selected' : '' }}>Processando</option>
                        <option value="enviado" {{ $sp === 'enviado' ? 'selected' : '' }}>Enviado</option>
                        <option value="entregue" {{ $sp === 'entregue' ? 'selected' : '' }}>Entregue</option>
                        <option value="cancelado" {{ $sp === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                    </select>
                    @error('status_pedido')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Origem do Pedido --}}
                <div>
                    <label for="origem_pedido" class="block text-sm font-medium text-gray-700 mb-1">Origem do Pedido</label>
                    <select name="origem_pedido" id="origem_pedido"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('origem_pedido') border-red-500 @enderror">
                        @php $op = old('origem_pedido', 'live'); @endphp
                        <option value="live" {{ $op === 'live' ? 'selected' : '' }}>Live</option>
                        <option value="site" {{ $op === 'site' ? 'selected' : '' }}>Site</option>
                        <option value="whatsapp" {{ $op === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                        <option value="instagram" {{ $op === 'instagram' ? 'selected' : '' }}>Instagram</option>
                    </select>
                    @error('origem_pedido')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Forma de Pagamento (Opcional) --}}
                <div>
                    <label for="forma_pagamento" class="block text-sm font-medium text-gray-700 mb-1">Forma de Pagamento (Opcional)</label>
                    <select name="forma_pagamento" id="forma_pagamento"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('forma_pagamento') border-red-500 @enderror">
                        @php $fp = old('forma_pagamento'); @endphp
                        <option value="" {{ empty($fp) ? 'selected' : '' }}>—</option>
                        <option value="pix" {{ $fp === 'pix' ? 'selected' : '' }}>Pix</option>
                        <option value="cartao_credito" {{ $fp === 'cartao_credito' ? 'selected' : '' }}>Cartão Crédito</option>
                        <option value="cartao_debito" {{ $fp === 'cartao_debito' ? 'selected' : '' }}>Cartão Débito</option>
                        <option value="boleto" {{ $fp === 'boleto' ? 'selected' : '' }}>Boleto</option>
                        <option value="dinheiro" {{ $fp === 'dinheiro' ? 'selected' : '' }}>Dinheiro</option>
                        <option value="transferencia" {{ $fp === 'transferencia' ? 'selected' : '' }}>Transferência</option>
                    </select>
                    @error('forma_pagamento')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Status do Pagamento --}}
                <div>
                    <label for="status_pagamento" class="block text-sm font-medium text-gray-700 mb-1">Status do Pagamento</label>
                    <select name="status_pagamento" id="status_pagamento"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('status_pagamento') border-red-500 @enderror">
                        @php $stp = old('status_pagamento', 'pendente'); @endphp
                        <option value="pendente" {{ $stp === 'pendente' ? 'selected' : '' }}>Pendente</option>
                        <option value="aprovado" {{ $stp === 'aprovado' ? 'selected' : '' }}>Aprovado</option>
                        <option value="rejeitado" {{ $stp === 'rejeitado' ? 'selected' : '' }}>Rejeitado</option>
                        <option value="estornado" {{ $stp === 'estornado' ? 'selected' : '' }}>Estornado</option>
                    </select>
                    @error('status_pagamento')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                {{-- Valores --}}
                <div>
                    <label for="valor_total" class="block text-sm font-medium text-gray-700 mb-1">Valor Total</label>
                    <input type="number" step="0.01" name="valor_total" id="valor_total" value="{{ old('valor_total', 0) }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('valor_total') border-red-500 @enderror">
                    @error('valor_total')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="valor_frete" class="block text-sm font-medium text-gray-700 mb-1">Valor Frete</label>
                    <input type="number" step="0.01" name="valor_frete" id="valor_frete" value="{{ old('valor_frete', 0) }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('valor_frete') border-red-500 @enderror">
                    @error('valor_frete')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="valor_desconto" class="block text-sm font-medium text-gray-700 mb-1">Valor Desconto</label>
                    <input type="number" step="0.01" name="valor_desconto" id="valor_desconto" value="{{ old('valor_desconto', 0) }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('valor_desconto') border-red-500 @enderror">
                    @error('valor_desconto')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- Endereço --}}
                <div>
                    <label for="endereco_entrega" class="block text-sm font-medium text-gray-700 mb-1">Endereço de Entrega (Opcional)</label>
                    <textarea name="endereco_entrega" id="endereco_entrega" rows="3"
                              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('endereco_entrega') border-red-500 @enderror"
                              placeholder="Rua, número, bairro, complemento...">{{ old('endereco_entrega') }}</textarea>
                    @error('endereco_entrega')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Observações --}}
                <div>
                    <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações (Opcional)</label>
                    <textarea name="observacoes" id="observacoes" rows="3"
                              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('observacoes') border-red-500 @enderror"
                              placeholder="Observações do pedido...">{{ old('observacoes') }}</textarea>
                    @error('observacoes')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div>
                    <label for="cep_entrega" class="block text-sm font-medium text-gray-700 mb-1">CEP (Opcional)</label>
                    <input type="text" name="cep_entrega" id="cep_entrega" value="{{ old('cep_entrega') }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('cep_entrega') border-red-500 @enderror"
                           placeholder="00000-000">
                    @error('cep_entrega')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="cidade_entrega" class="block text-sm font-medium text-gray-700 mb-1">Cidade (Opcional)</label>
                    <input type="text" name="cidade_entrega" id="cidade_entrega" value="{{ old('cidade_entrega') }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('cidade_entrega') border-red-500 @enderror">
                    @error('cidade_entrega')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="estado_entrega" class="block text-sm font-medium text-gray-700 mb-1">UF (Opcional)</label>
                    <input type="text" name="estado_entrega" id="estado_entrega" value="{{ old('estado_entrega') }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('estado_entrega') border-red-500 @enderror"
                           placeholder="SP">
                    @error('estado_entrega')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="cupom_desconto" class="block text-sm font-medium text-gray-700 mb-1">Cupom (Opcional)</label>
                    <input type="text" name="cupom_desconto" id="cupom_desconto" value="{{ old('cupom_desconto') }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('cupom_desconto') border-red-500 @enderror">
                    @error('cupom_desconto')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label for="codigo_rastreamento" class="block text-sm font-medium text-gray-700 mb-1">Código Rastreamento (Opcional)</label>
                    <input type="text" name="codigo_rastreamento" id="codigo_rastreamento" value="{{ old('codigo_rastreamento') }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('codigo_rastreamento') border-red-500 @enderror">
                    @error('codigo_rastreamento')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="data_envio" class="block text-sm font-medium text-gray-700 mb-1">Data Envio (Opcional)</label>
                    <input type="datetime-local" name="data_envio" id="data_envio"
                           value="{{ old('data_envio') }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('data_envio') border-red-500 @enderror">
                    @error('data_envio')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="data_entrega_prevista" class="block text-sm font-medium text-gray-700 mb-1">Entrega Prevista (Opcional)</label>
                    <input type="date" name="data_entrega_prevista" id="data_entrega_prevista"
                           value="{{ old('data_entrega_prevista') }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('data_entrega_prevista') border-red-500 @enderror">
                    @error('data_entrega_prevista')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <label for="data_entrega_realizada" class="block text-sm font-medium text-gray-700 mb-1">Entrega Realizada (Opcional)</label>
                    <input type="datetime-local" name="data_entrega_realizada" id="data_entrega_realizada"
                           value="{{ old('data_entrega_realizada') }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('data_entrega_realizada') border-red-500 @enderror">
                    @error('data_entrega_realizada')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="endereco_entrega" class="block text-sm font-medium text-gray-700 mb-1">Endereço (Resumo)</label>
                    <input type="text" value="{{ old('cidade_entrega') }} {{ old('estado_entrega') }}"
                           class="mt-1 block w-full border border-gray-200 bg-gray-50 rounded-md shadow-sm py-2 px-3 sm:text-sm"
                           readonly>
                    <p class="text-xs text-gray-500 mt-1">Somente visual (montado a partir de cidade/UF).</p>
                </div>
            </div>

            <div class="flex justify-end space-x-4">
                <a href="{{ route('admin.pedido.index') }}"
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                    <i class="fas fa-times-circle mr-2"></i> Cancelar
                </a>
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300">
                    <i class="fas fa-save mr-2"></i> Salvar Pedido
                </button>
            </div>
        </form>
    </div>

    {{-- Autocomplete Usuário (Vanilla) - idêntico ao Financeiro/create --}}
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
        s = (s === null || s === undefined) ?'' : String(s);
        return s.replace(/&/g,'&amp;')
                .replace(/</g,'&lt;')
                .replace(/>/g,'&gt;')
                .replace(/\"/g,'&quot;')
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
        var name = user && user.name ?String(user.name) : '';
        input.value = name;
        hidden.value = user && user.id ?user.id : '';
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
          var rawName = (u.name === null || u.name === undefined) ?'' : String(u.name);

          var safeId = escapeHtml(u.id);
          var safeName = escapeHtml(rawName);

          var displayName = safeName;
          var pos = qLower ?rawName.toLowerCase().indexOf(qLower) : -1;
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
        var query = (q === null || q === undefined) ?'' : String(q);
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
@extends('layouts.app')

@section('title', 'Gerenciar Sacolinhas')
@section('brand_route', 'admin.sacolinha.gestao')
@section('brand_icon', 'fas fa-shopping-bag')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-semibold text-gray-800">Gerenciar Sacolinhas</h1>
    </div>

	{{-- Filtros --}}
	<div class="bg-white shadow-lg rounded-lg p-4 mb-6">
		<form method="GET" action="{{ route('admin.sacolinha.gestao') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
			<div class="md:col-span-3">
				@php $selectedUser = request('user_id') ? \App\Models\User::find(request('user_id')) : null; @endphp
				<div x-data="{ 
					open: false, 
					search: '{{ addslashes($selectedUser->name ?? request('cliente') ?? '') }}',
					selectedId: '{{ request('user_id') }}',
					users: [],
					loading: false,
					focusedIndex: -1,
					timeout: null,
					handleInput() {
						this.selectedId = '';
						this.focusedIndex = -1;
						this.open = true;
						if (this.search.length < 2) {
							this.users = [];
							return;
						}
						this.loading = true;
						clearTimeout(this.timeout);
						this.timeout = setTimeout(() => {
							this.fetchUsers();
						}, 300);
					},
					fetchUsers() {
						fetch('{{ route('api.users.search') }}?q=' + encodeURIComponent(this.search))
							.then(res => res.json())
							.then(res => {
								if (res.success) {
									this.users = res.data.map(u => {
										let extraInfo = [u.email, u.instagram, u.tiktok, u.apelido].filter(Boolean).join(' • ');
										return { id: String(u.id), name: u.name, info: extraInfo };
									});
									this.focusedIndex = this.users.length > 0 ? 0 : -1;
								} else {
									this.users = [];
									this.focusedIndex = -1;
								}
								this.loading = false;
							})
							.catch(() => {
								this.users = [];
								this.focusedIndex = -1;
								this.loading = false;
							});
					},
					selectUser(user) {
						this.selectedId = user.id; 
						this.search = user.name; 
						this.open = false;
						document.getElementById('btn-search').focus();
					},
					onKeyDown(e) {
						if (e.key === 'Enter') {
							if (this.open && this.focusedIndex >= 0 && this.focusedIndex < this.users.length) {
								e.preventDefault();
								this.selectUser(this.users[this.focusedIndex]);
							}
							return;
						}
						
						if (!this.open || this.users.length === 0) return;
						
						if (e.key === 'ArrowDown') {
							e.preventDefault();
							this.focusedIndex = this.focusedIndex < this.users.length - 1 ? this.focusedIndex + 1 : 0;
						} else if (e.key === 'ArrowUp') {
							e.preventDefault();
							this.focusedIndex = this.focusedIndex > 0 ? this.focusedIndex - 1 : this.users.length - 1;
						}
					}
				}" class="relative">
					<label class="block text-sm font-medium text-gray-700 mb-1">Buscar (nome do cliente)</label>
					<input type="text" 
						   name="cliente"
						   x-model="search"
						   @input="handleInput()"
						   @click="open = true"
						   @click.away="open = false"
						   @keydown.escape="open = false"
						   @keydown="onKeyDown($event)"
						   class="w-full text-sm border border-gray-300 rounded-lg p-2 bg-white transition-all focus:border-blue-500 focus:ring focus:ring-blue-200"
						   placeholder="Digite o nome do cliente...">
					
					<input type="hidden" name="user_id" :value="selectedId">

					<!-- Dropdown de Sugestões -->
					<div x-show="open" 
						 x-transition
						 class="absolute z-50 mt-1 w-full bg-white border border-gray-100 shadow-xl rounded-xl max-h-60 overflow-y-auto"
						 style="display: none;">
						<template x-for="(user, index) in users" :key="user.id">
							<div @click="selectUser(user)"
								 class="p-3 cursor-pointer border-b border-gray-100 last:border-0 transition"
								 :class="[
									selectedId == user.id ? 'border-l-4 border-indigo-600' : 'border-l-4 border-transparent',
									focusedIndex === index ? 'bg-indigo-50' : 'hover:bg-indigo-50'
								 ]">
								<div class="font-bold text-sm text-gray-800" x-text="user.name"></div>
								<div class="text-[10px] text-gray-500" x-show="user.info" x-text="user.info"></div>
							</div>
						</template>
						<div x-show="loading" class="px-4 py-2 text-sm text-gray-400 italic">
							Carregando...
						</div>
						<div x-show="!loading && users.length === 0 && search.length >= 2" class="px-4 py-2 text-sm text-gray-400 italic">
							Nenhum usuário encontrado
						</div>
						<div x-show="search.length < 2" class="px-4 py-2 text-xs text-gray-400 italic">
							Digite pelo menos 2 caracteres...
						</div>
						<!-- Opção para limpar -->
						<div @click="selectedId = ''; search = ''; open = false; users = [];"
							 class="px-4 py-2 text-xs text-red-500 hover:bg-red-50 cursor-pointer border-t border-gray-50 font-bold uppercase">
							Limpar Seleção
						</div>
					</div>
				</div>
			</div>

			<div class="flex items-center justify-end gap-2 pt-6">
				<a href="{{ route('admin.sacolinha.gestao') }}"
				   class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
					Limpar
				</a>

				<button type="submit" id="btn-search"
						class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300">
					<i class="fas fa-filter mr-2"></i> Filtrar
				</button>
			</div>
		</form>
	</div>

    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Cliente</th>
                        <th class="py-3 px-6 text-left">Aberto em</th>
                        <th class="py-3 px-6 text-left">Itens</th>
                        <th class="py-3 px-6 text-left">Valor Total</th>
                        <th class="py-3 px-6 text-center">Ações</th>
                    </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">
                    @forelse ($sacolinhas as $sacola)
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6 text-left font-medium">
                                {{ $sacola->name ?? 'N/A' }}
                            </td>

                            <td class="py-3 px-6 text-left whitespace-nowrap">
                                @if (!empty($sacola->aberto_em))
                                    {{ \Carbon\Carbon::parse($sacola->aberto_em)->format('d/m/Y H:i') }}
                                @else
                                    —
                                @endif
                            </td>

                            <td class="py-3 px-6 text-left">
                                <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-xs font-semibold">
                                    {{ $sacola->total_itens }} {{ $sacola->total_itens == 1 ? 'item' : 'itens' }}
                                </span>
                            </td>

                            <td class="py-3 px-6 text-left font-semibold whitespace-nowrap">
                                {{ 'R$ ' . number_format((float)$sacola->total_valor, 2, ',', '.') }}
                            </td>

                            <td class="py-3 px-6 text-center">
                                <div class="flex item-center justify-center space-x-2">
                                    <a href="{{ route('admin.sacolinha.show', $sacola->user_id) }}"
                                       class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-4 rounded-md shadow transition duration-300 flex items-center gap-2"
                                       title="Ver Detalhes">
                                        <i class="fas fa-eye text-sm"></i> Ver
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 px-6 text-center text-gray-500">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-shopping-bag text-gray-400 text-2xl"></i>
                                </div>
                                <p class="text-sm font-semibold text-gray-800">Nenhuma sacolinha aberta no momento.</p>
                                
                                <div class="mt-4">
                                    @if(request('user_id') && isset($selectedUser))
                                        <a href="{{ route('admin.sacolinha.show', request('user_id')) }}" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition duration-200 uppercase tracking-wider inline-flex items-center justify-center gap-2 shadow-sm">
                                            <i class="fas fa-plus"></i> Abrir Sacolinha para {{ $selectedUser->name }}
                                        </a>
                                    @else
                                        <button type="button" onclick="document.querySelector('input[name=cliente]').focus()" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition duration-200 uppercase tracking-wider inline-flex items-center justify-center gap-2 shadow-sm">
                                            <i class="fas fa-search"></i> Buscar cliente para abrir sacolinha
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (method_exists($sacolinhas, 'links'))
            <div class="p-4">
                {{ $sacolinhas->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Movimentações de Conta Corrente')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Card de Filtros -->
    <div class="bg-white shadow-xl rounded-2xl border border-gray-100 mb-6">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 rounded-t-2xl">
            <h3 class="text-sm font-bold text-gray-700 flex items-center gap-2">
                <i class="fas fa-filter text-blue-500"></i> Filtros de Busca
            </h3>
        </div>
        <div class="p-6">
            <form action="{{ route('admin.conta_corrente.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <!-- Busca por Descrição -->
                <div class="md:col-span-1">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Descrição</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar descrição..." 
                           class="w-full text-sm border border-gray-300 rounded-lg p-2 bg-white transition-all focus:border-blue-500 focus:ring focus:ring-blue-200">
                </div>

                <!-- Filtro por Tipo -->
                <div class="md:col-span-1">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tipo</label>
                    <select name="tipo" class="w-full text-sm border border-gray-300 rounded-lg p-2 bg-white transition-all focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <option value="">Todos</option>
                        <option value="credito" {{ request('tipo') == 'credito' ? 'selected' : '' }}>Crédito</option>
                        <option value="debito" {{ request('tipo') == 'debito' ? 'selected' : '' }}>Débito</option>
                    </select>
                </div>

                <!-- Filtro por Usuário (Searchable) -->
                <div x-data="{ 
                    open: false, 
                    search: '{{ addslashes($selectedUser->name ?? '') }}',
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
                }" class="relative md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Usuário</label>
                    <input type="text" 
                           x-model="search"
                           @input="handleInput()"
                           @click="open = true"
                           @click.away="open = false"
                           @keydown.escape="open = false"
                           @keydown="onKeyDown($event)"
                           placeholder="Digite para buscar..."
                           class="w-full text-sm border border-gray-300 rounded-lg p-2 bg-white transition-all focus:border-blue-500 focus:ring focus:ring-blue-200">
                    
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

                <!-- Período: De -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">De</label>
                    <input type="date" name="de" value="{{ request('de') }}" 
                           class="w-full text-sm border border-gray-300 rounded-lg p-2 bg-white transition-all focus:border-blue-500 focus:ring focus:ring-blue-200">
                </div>

                <!-- Período: Até -->
                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Até</label>
                        <input type="date" name="ate" value="{{ request('ate') }}" class="w-full text-sm border border-gray-300 rounded-lg p-2 bg-white transition-all focus:border-blue-500 focus:ring focus:ring-blue-200">
                    </div>
                    <button type="submit" id="btn-search" class="bg-blue-100 hover:bg-blue-200 text-blue-800 p-2.5 rounded-lg transition border border-blue-200 font-bold" title="Pesquisar">
                        <i class="fas fa-search"></i>
                    </button>
                    @if(request()->anyFilled(['q', 'tipo', 'user_id', 'de', 'ate']))
                        <a href="{{ route('admin.conta_corrente.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 p-2.5 rounded-lg transition border border-gray-200" title="Limpar">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row justify-between items-center gap-4">
            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-history text-blue-600"></i>
                Movimentações de Conta Corrente
            </h2>
            <a href="{{ route('admin.conta_corrente.create') }}" 
               class="inline-flex items-center gap-2 bg-blue-100 hover:bg-blue-200 text-blue-800 px-4 py-2 rounded-xl text-sm font-bold transition shadow-sm border border-blue-200">
                <i class="fas fa-plus"></i> Novo Lançamento
            </a>
        </div>

        <div class="p-6">
            @if (session('success'))
                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r-xl flex items-center gap-3">
                    <i class="fas fa-check-circle"></i>
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Descrição</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Valor</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider hidden md:table-cell">Saldo Ant.</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider hidden md:table-cell">Saldo Atual</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Usuário/Cliente</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($movimentacoes as $movimentacao)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">#{{ $movimentacao->id }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ $movimentacao->data_movimentacao->format('d/m/Y') }}
                                    <span class="text-xs text-gray-400 block">{{ $movimentacao->data_movimentacao->format('H:i') }}</span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-800 font-medium">{{ $movimentacao->descricao }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $movimentacao->tipo_movimentacao === 'credito' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ ucfirst($movimentacao->tipo_movimentacao) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-bold {{ $movimentacao->tipo_movimentacao === 'credito' ? 'text-green-600' : 'text-red-600' }}">
                                    R$ {{ number_format($movimentacao->valor, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">
                                    R$ {{ number_format($movimentacao->saldo_anterior, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 hidden md:table-cell">
                                    R$ {{ number_format($movimentacao->saldo_atual, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600">
                                    @if ($movimentacao->user)
                                        <div class="font-bold text-gray-800">{{ $movimentacao->user->name }}</div>
                                        <div class="text-[10px] text-gray-500">{{ $movimentacao->user->telefone ?? '' }}</div>
                                    @else
                                        <span class="text-gray-400">Sistema</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('admin.conta_corrente.show', $movimentacao->id) }}" 
                                           class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.conta_corrente.edit', $movimentacao->id) }}" 
                                           class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.conta_corrente.destroy', $movimentacao->id) }}" 
                                              method="POST" 
                                              onsubmit="return confirm('Tem certeza que deseja excluir este lançamento?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-12 text-center text-gray-400 italic">
                                    <i class="fas fa-folder-open text-4xl mb-3 block"></i>
                                    Nenhum lançamento encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $movimentacoes->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
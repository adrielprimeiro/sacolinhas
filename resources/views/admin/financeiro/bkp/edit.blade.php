@extends('layouts.app')

@section('title', 'Editar Lançamento')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-edit text-amber-600"></i>
                Editar Lançamento de Conta Corrente #{{ $financeiro->id }}
            </h2>
            <a href="{{ route('admin.conta_corrente.index', request()->query()) }}" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left mr-1"></i> Voltar
            </a>
        </div>

        <div class="p-8">
            <form action="{{ route('admin.conta_corrente.update', array_merge([$financeiro->id], request()->query())) }}" method="POST" class="space-y-6" x-data="{ isSubmitting: false }" @submit="if (isSubmitting) { $event.preventDefault(); } else { isSubmitting = true; }">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-12 gap-6">
                    <!-- Usuário (Obrigatório) -->
                    <div x-data="{ 
                        open: false, 
                        search: '{{ addslashes($financeiro->user->name ?? '') }}',
                        selectedId: '{{ $financeiro->user_id }}',
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
                    }" class="relative col-span-12">
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Usuário / Cliente <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" 
                                   x-model="search"
                                   @input="handleInput()"
                                   @click="open = true"
                                   @click.away="open = false"
                                   @keydown.escape="open = false"
                                   @keydown="onKeyDown($event)"
                                   placeholder="Busque o nome do usuário..."
                                   class="w-full pr-10 text-sm border border-gray-200 rounded-xl p-2.5 font-bold focus:ring-2 focus:ring-indigo-300 focus:outline-none bg-white transition-all shadow-sm @error('user_id') border-red-500 @enderror">
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="fas fa-search text-sm"></i>
                            </div>
                        </div>
                        
                        <input type="hidden" name="user_id" :value="selectedId" required>

                        <!-- Dropdown de Sugestões -->
                        <div x-show="open" 
                             x-transition
                             class="absolute z-50 mt-1 w-full bg-white border border-gray-100 shadow-2xl rounded-xl max-h-60 overflow-y-auto"
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
                            <div x-show="loading" class="px-4 py-3 text-center text-sm text-gray-400 italic">
                                Carregando...
                            </div>
                            <div x-show="!loading && users.length === 0 && search.length >= 2" class="px-4 py-3 text-center text-sm text-gray-400 italic">
                                Nenhum usuário encontrado
                            </div>
                            <div x-show="search.length < 2" class="px-4 py-3 text-center text-xs text-gray-400 italic">
                                Digite pelo menos 2 caracteres...
                            </div>
                        </div>
                        @error('user_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Descrição -->
                    <div class="col-span-12">
                        <label for="descricao" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Descrição</label>
                        <input type="text" 
                               class="w-full text-sm border border-gray-200 rounded-xl p-2.5 font-bold focus:ring-2 focus:ring-indigo-300 focus:outline-none bg-white transition-all shadow-sm @error('descricao') border-red-500 @enderror" 
                               id="descricao" name="descricao" value="{{ old('descricao', $financeiro->descricao) }}" placeholder="Ex: Ajuste de saldo, Crédito promocional..." required>
                        @error('descricao')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Data e Hora -->
                    <div class="col-span-12 md:col-span-6">
                        <label for="data_movimentacao" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Data e Hora</label>
                        <input type="datetime-local" 
                               class="w-full text-sm border border-gray-200 rounded-xl p-2.5 font-bold focus:ring-2 focus:ring-indigo-300 focus:outline-none bg-white transition-all shadow-sm @error('data_movimentacao') border-red-500 @enderror" 
                               id="data_movimentacao" name="data_movimentacao" 
                               value="{{ old('data_movimentacao', $financeiro->data_movimentacao->format('Y-m-d\TH:i')) }}" required>
                        @error('data_movimentacao')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tipo de Movimentação -->
                    <div class="col-span-12 md:col-span-6">
                        <label for="tipo_movimentacao" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Tipo de Movimentação</label>
                        <select class="w-full text-sm border border-gray-200 rounded-xl p-2.5 bg-white font-bold focus:ring-2 focus:ring-indigo-300 focus:outline-none transition shadow-sm @error('tipo_movimentacao') border-red-500 @enderror" 
                                id="tipo_movimentacao" name="tipo_movimentacao" required>
                            <option value="">Selecione...</option>
                            @foreach ($tiposMovimentacao as $tipo)
                                <option value="{{ $tipo }}" {{ old('tipo_movimentacao', $financeiro->tipo_movimentacao) == $tipo ? 'selected' : '' }}>
                                    {{ $tipo === 'credito' ? 'Crédito (Adicionar Saldo)' : 'Débito (Retirar Saldo)' }}
                                </option>
                            @endforeach
                        </select>
                        @error('tipo_movimentacao')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Valor (R$) -->
                    <div class="col-span-12 md:col-span-6">
                        <label for="valor" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Valor (R$)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-black text-gray-400">R$</span>
                            <input type="number" step="0.01" 
                                   class="w-full pl-10 text-sm border border-gray-200 rounded-xl p-2.5 font-black focus:ring-2 focus:ring-indigo-300 focus:outline-none bg-white transition-all shadow-sm @error('valor') border-red-500 @enderror" 
                                   id="valor" name="valor" value="{{ old('valor', $financeiro->valor) }}" placeholder="0,00" required>
                        </div>
                        @error('valor')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Classificação Financeira (Searchable) -->
                    <div x-data="{ 
                        open: false, 
                        search: '{{ $financeiro->classificacaoFinanceira->nome ?? '' }} ({{ $financeiro->classificacaoFinanceira->codigo_contabil ?? '' }})',
                        selectedId: '{{ $financeiro->classificacao_id }}',
                        items: [
                            @foreach($classificacoes as $class)
                                { id: '{{ $class->id }}', name: '{{ addslashes($class->nome) }} ({{ $class->codigo_contabil }})' },
                            @endforeach
                        ],
                        get filteredItems() {
                            if (this.search === '') return this.items;
                            return this.items.filter(i => i.name.toLowerCase().includes(this.search.toLowerCase()));
                        }
                    }" class="relative col-span-12 md:col-span-6">
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Classificação Financeira <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" 
                                   x-model="search"
                                   @click="open = true"
                                   @click.away="open = false"
                                   @keydown.escape="open = false"
                                   placeholder="Busque a classificação..."
                                   class="w-full pr-10 text-sm border border-gray-200 rounded-xl p-2.5 font-bold focus:ring-2 focus:ring-indigo-300 focus:outline-none bg-white transition-all shadow-sm @error('classificacao_id') border-red-500 @enderror">
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="fas fa-list-ul text-sm"></i>
                            </div>
                        </div>
                        
                        <input type="hidden" name="classificacao_id" :value="selectedId" required>

                        <!-- Dropdown de Sugestões -->
                        <div x-show="open" 
                             x-transition
                             class="absolute z-50 mt-1 w-full bg-white border border-gray-100 shadow-2xl rounded-xl max-h-60 overflow-y-auto"
                             style="display: none;">
                            <template x-for="item in filteredItems" :key="item.id">
                                <div @click="selectedId = item.id; search = item.name; open = false;"
                                     class="px-4 py-3 text-sm hover:bg-blue-50 cursor-pointer transition border-b border-gray-50 flex items-center justify-between"
                                     :class="selectedId == item.id ? 'bg-blue-50 font-bold text-blue-700' : 'text-gray-700'">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-tag text-gray-300"></i>
                                        <span x-text="item.name"></span>
                                    </div>
                                    <span x-show="selectedId == item.id" class="text-blue-600"><i class="fas fa-check"></i></span>
                                </div>
                            </template>
                            <div x-show="filteredItems.length === 0" class="px-4 py-4 text-center text-sm text-gray-400 italic">
                                Nenhuma classificação encontrada
                            </div>
                        </div>
                        @error('classificacao_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tipo de Referência (Opcional) -->
                    <div class="col-span-12 md:col-span-6">
                        <label for="referencia_tipo" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Tipo de Referência (Opcional)</label>
                        <select class="w-full text-sm border border-gray-200 rounded-xl p-2.5 bg-white font-bold focus:ring-2 focus:ring-indigo-300 focus:outline-none transition shadow-sm" 
                                id="referencia_tipo" name="referencia_tipo">
                            <option value="">Nenhum</option>
                            @foreach (['sacolinha', 'pagamento', 'pedido', 'ajuste', 'desconto'] as $refTipo)
                                <option value="{{ $refTipo }}" {{ old('referencia_tipo', $financeiro->referencia_tipo) == $refTipo ? 'selected' : '' }}>
                                    {{ ucfirst($refTipo) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- ID da Referência -->
                    <div id="referencia_id_group" class="col-span-12 md:col-span-6" style="{{ old('referencia_tipo', $financeiro->referencia_tipo) ? '' : 'display:none;' }}">
                        <label for="referencia_id" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">ID da Referência</label>
                        <input type="number" 
                               class="w-full text-sm border border-gray-200 rounded-xl p-2.5 font-bold focus:ring-2 focus:ring-indigo-300 focus:outline-none bg-white transition-all shadow-sm @error('referencia_id') border-red-500 @enderror" 
                               id="referencia_id" name="referencia_id" value="{{ old('referencia_id', $financeiro->referencia_id) }}" placeholder="Ex: 1234">
                        @error('referencia_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Observações (Opcional) -->
                    <div class="col-span-12">
                        <label for="observacoes" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Observações (Opcional)</label>
                        <textarea class="w-full text-sm border border-gray-200 rounded-xl p-2.5 font-bold focus:ring-2 focus:ring-indigo-300 focus:outline-none bg-white transition-all shadow-sm @error('observacoes') border-red-500 @enderror" 
                                  id="observacoes" name="observacoes" rows="3" placeholder="Informações adicionais sobre este lançamento...">{{ old('observacoes', $financeiro->observacoes) }}</textarea>
                        @error('observacoes')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-100">
                    <a href="{{ route('admin.conta_corrente.index', request()->query()) }}" 
                       class="px-6 py-3 rounded-xl text-sm font-semibold text-gray-500 hover:bg-gray-100 transition">
                        Cancelar
                    </a>
                    <button type="submit" 
                            :disabled="isSubmitting"
                            class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-black transition shadow-md shadow-indigo-100 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isSubmitting">Atualizar Lançamento</span>
                        <span x-show="isSubmitting" x-cloak><i class="fas fa-spinner fa-spin mr-2"></i>Salvando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const referenciaTipoSelect = document.getElementById('referencia_tipo');
        const referenciaIdGroup = document.getElementById('referencia_id_group');

        function toggleReferenciaIdField() {
            if (referenciaTipoSelect.value && referenciaTipoSelect.value !== '') {
                referenciaIdGroup.style.display = 'block';
            } else {
                referenciaIdGroup.style.display = 'none';
            }
        }

        referenciaTipoSelect.addEventListener('change', toggleReferenciaIdField);
        toggleReferenciaIdField();
    });
</script>
@endsection
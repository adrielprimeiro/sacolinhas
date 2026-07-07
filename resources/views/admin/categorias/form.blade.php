@extends('layouts.app')

@section('title', isset($categoria) ? 'Editar Categoria' : 'Nova Categoria')

@section('content')
<script>
function categorySearch(config) {
    return {
        categorias: config.categorias,
        selectedId: config.selectedId || '',
        search: '',
        showDropdown: false,
        activeIndex: 0,

        init() {
            if (this.selectedId) {
                const matched = this.categorias.find(c => c.id == this.selectedId);
                if (matched) {
                    this.search = matched.name;
                }
            }
        },

        filteredCategorias() {
            const query = (this.search || '').toLowerCase().trim();
            if (!query) {
                return this.categorias;
            }
            return this.categorias.filter(c => c.name.toLowerCase().includes(query));
        },

        selectCategory(cat) {
            this.selectedId = cat.id;
            this.search = cat.name;
            this.showDropdown = false;
        },

        selectRoot() {
            this.selectedId = '';
            this.search = '';
            this.showDropdown = false;
        },

        clearSelection() {
            this.selectedId = '';
            this.search = '';
        }
    };
}
</script>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    {{-- Breadcrumbs --}}
    <div class="mb-6">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('admin.categorias.index') }}" class="text-gray-700 hover:text-blue-600 inline-flex items-center text-sm font-medium">
                        Categorias
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 text-xs mx-2"></i>
                        <span class="text-gray-500 text-sm font-medium">{{ isset($categoria) ? 'Editar' : 'Nova' }}</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    {{-- Card Principal --}}
    <div class="bg-white shadow-lg rounded-lg p-6">
        <h1 class="text-3xl font-semibold text-gray-800 mb-6">
            {{ isset($categoria) ? 'Editar Categoria: ' . $categoria->name : 'Criar Nova Categoria' }}
        </h1>

        <form action="{{ isset($categoria) ? route('admin.categorias.update', $categoria) : route('admin.categorias.store') }}" method="POST">
            @csrf
            @if (isset($categoria))
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- Nome --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nome da Categoria <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $categoria->name ?? '') }}" 
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Categoria Pai (Busca Autocompletável) --}}
                <div x-data="categorySearch({
                    categorias: {{ json_encode($parentCategorias) }},
                    selectedId: '{{ old('parent_id', $categoria->parent_id ?? '') }}'
                })" class="relative" @click.away="showDropdown = false">
                    <label for="parent_search" class="block text-sm font-medium text-gray-700 mb-1">Categoria Hierárquica (Pai)</label>
                    
                    <div class="relative mt-1">
                        <input 
                            id="parent_search"
                            type="text" 
                            placeholder="Pesquisar categoria pai..." 
                            x-model="search"
                            @focus="showDropdown = true; activeIndex = 0;"
                            @input="showDropdown = true; selectedId = ''; activeIndex = 0;"
                            @keydown.arrow-down.prevent="activeIndex = Math.min((filteredCategorias().length || 1) - 1, activeIndex + 1)"
                            @keydown.arrow-up.prevent="activeIndex = Math.max(0, activeIndex - 1)"
                            @keydown.enter.prevent="if (showDropdown && filteredCategorias().length > 0) { selectCategory(filteredCategorias()[activeIndex]); }"
                            class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 pr-10 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            autocomplete="off"
                        >
                        <!-- Indicador/Botão para limpar -->
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-gray-400 hover:text-gray-600"
                             x-show="search || selectedId"
                             @click="clearSelection()">
                            <i class="fas fa-times-circle text-xs"></i>
                        </div>
                    </div>

                    <!-- Input oculto para o formulário POST -->
                    <input type="hidden" name="parent_id" :value="selectedId">

                    <!-- Dropdown flutuante de categorias -->
                    <div 
                        x-show="showDropdown" 
                        x-cloak
                        class="absolute left-0 right-0 z-30 mt-1 bg-white shadow-xl max-h-60 rounded-md py-1 text-xs ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none border border-gray-200"
                    >
                        <button 
                            type="button"
                            @click="selectRoot()"
                            class="w-full text-left px-4 py-2 hover:bg-indigo-50 text-gray-900 focus:bg-indigo-50 transition-colors border-b border-gray-100 font-semibold"
                        >
                            -- Categoria Raiz --
                        </button>

                        <template x-for="(cat, catIdx) in filteredCategorias()" :key="cat.id">
                            <button 
                                type="button"
                                @click="selectCategory(cat)"
                                :class="activeIndex === catIdx ? 'bg-blue-50 text-blue-900' : 'text-gray-700'"
                                class="w-full text-left px-4 py-2 hover:bg-indigo-50 focus:bg-indigo-50 transition-colors border-b border-gray-100 flex justify-between items-center gap-2"
                            >
                                <span class="font-semibold text-gray-700" x-html="search ? cat.path : cat.formatted_name"></span>
                            </button>
                        </template>

                        <div x-show="filteredCategorias().length === 0" class="px-4 py-2 text-gray-400 text-center font-medium">
                            Nenhuma categoria encontrada
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 my-6 pt-6">
                <h3 class="text-lg font-medium text-gray-800 mb-4"><i class="fas fa-tag mr-2 text-blue-500"></i> Regras de Desconto</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- Valor do Desconto --}}
                <div>
                    <label for="valor_desconto" class="block text-sm font-medium text-gray-700 mb-1">Valor do Desconto</label>
                    <input type="number" step="0.01" name="valor_desconto" id="valor_desconto" value="{{ old('valor_desconto', $categoria->valor_desconto ?? 0) }}" 
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Deixe 0 se não houver desconto específico para esta categoria.</p>
                </div>

                {{-- Tipo de Desconto --}}
                <div>
                    <label for="tipo_desconto" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Desconto</label>
                    <select name="tipo_desconto" id="tipo_desconto" 
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="porcentagem" {{ old('tipo_desconto', $categoria->tipo_desconto ?? '') == 'porcentagem' ? 'selected' : '' }}>Porcentagem (%)</option>
                        <option value="fixo" {{ old('tipo_desconto', $categoria->tipo_desconto ?? '') == 'fixo' ? 'selected' : '' }}>Valor Fixo (R$)</option>
                    </select>
                </div>
            </div>

            <div class="border-t border-gray-200 my-6 pt-6">
                <h3 class="text-lg font-medium text-gray-800 mb-4"><i class="fas fa-hand-holding-usd mr-2 text-blue-500"></i> Avaliação de Desapego</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- Preço Base --}}
                <div>
                    <label for="preco_base" class="block text-sm font-medium text-gray-700 mb-1">Preço Base de Venda (R$)</label>
                    <input type="number" step="0.01" name="preco_base" id="preco_base" value="{{ old('preco_base', $categoria->preco_base ?? 0) }}" 
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Preço de venda padrão na loja para itens desta categoria.</p>
                </div>
            </div>

            <div class="border-t border-gray-200 my-6 pt-6">
                <h3 class="text-lg font-medium text-gray-800 mb-4"><i class="fas fa-box mr-2 text-blue-500"></i> Dimensões Médias (para frete)</h3>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                {{-- Altura --}}
                <div>
                    <label for="altura" class="block text-sm font-medium text-gray-700 mb-1">Altura (cm)</label>
                    <input type="number" step="0.01" name="altura" id="altura" value="{{ old('altura', $categoria->altura ?? '') }}" 
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                {{-- Largura --}}
                <div>
                    <label for="largura" class="block text-sm font-medium text-gray-700 mb-1">Largura (cm)</label>
                    <input type="number" step="0.01" name="largura" id="largura" value="{{ old('largura', $categoria->largura ?? '') }}" 
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                {{-- Comprimento --}}
                <div>
                    <label for="comprimento" class="block text-sm font-medium text-gray-700 mb-1">Comprimento (cm)</label>
                    <input type="number" step="0.01" name="comprimento" id="comprimento" value="{{ old('comprimento', $categoria->comprimento ?? '') }}" 
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                {{-- Peso --}}
                <div>
                    <label for="peso" class="block text-sm font-medium text-gray-700 mb-1">Peso (kg)</label>
                    <input type="number" step="0.001" name="peso" id="peso" value="{{ old('peso', $categoria->peso ?? '') }}" 
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
            </div>

            {{-- Botões de Ação --}}
            <div class="flex justify-end gap-3 border-t border-gray-100 pt-6">
                <a href="{{ route('admin.categorias.index') }}" 
                   class="bg-white py-2.5 px-5 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none transition">
                    Cancelar
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm py-2.5 px-5 rounded-lg shadow-sm transition">
                    <i class="far fa-save mr-1.5"></i> {{ isset($categoria) ? 'Atualizar Categoria' : 'Criar Categoria' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

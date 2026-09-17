@extends('layouts.app')

@section('title', 'Cadastrar Itens no Estoque')
@section('brand_route', 'items.index')
@section('brand_icon', 'fas fa-box')

@section('content')
<div class="space-y-6" x-data="itemBatchForm()">

    {{-- Top Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-5 rounded-xl shadow-sm border border-gray-200">
        <div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-100 text-indigo-600 shadow-xs">
                    <i class="fas fa-plus-circle text-lg"></i>
                </span>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 leading-tight">Cadastrar Itens no Estoque</h1>
                    <p class="text-sm text-gray-500">
                        Inserção rápida e prática de peças no estoque com busca automática de classificação
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('items.index') }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 text-sm font-medium transition duration-150">
                <i class="fas fa-arrow-left text-gray-500"></i>
                <span>Voltar para Itens</span>
            </a>
        </div>
    </div>

    @if (isset($errors) && $errors->any())
        <div class="bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded-xl shadow-xs">
            <div class="font-semibold flex items-center gap-2 mb-1">
                <i class="fas fa-exclamation-triangle"></i> Atenção:
            </div>
            <ul class="list-disc pl-5 text-sm space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Datalist de Marcas existentes --}}
    <datalist id="marcas-list">
        @foreach($marcas as $marcaNome)
            <option value="{{ $marcaNome }}">
        @endforeach
    </datalist>

    <form method="POST" action="{{ route('items.store') }}" @submit="onSubmit($event)" id="items-form">
        @csrf

        {{-- Container da Tabela de Inserção Rápida --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 bg-gray-50/80 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-2">
                    <i class="fas fa-tshirt text-indigo-600 text-base"></i>
                    <h2 class="text-base font-bold text-gray-900">Peças para Cadastro</h2>
                    <span class="text-xs bg-indigo-50 text-indigo-700 font-semibold px-2 py-0.5 rounded-full border border-indigo-200"
                          x-text="items.length + (items.length === 1 ? ' peça' : ' peças')"></span>
                </div>

                <div class="flex items-center gap-3 text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1">
                        <i class="fas fa-keyboard text-gray-400"></i> Dica: Pressione <strong>Enter</strong> no preço para abrir nova linha
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto pb-44">
                <table class="min-w-full table-fixed divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-[11px] uppercase font-bold tracking-wider">
                        <tr>
                            <th class="px-3 py-3 text-left" style="width: 32%;">Categoria / Nome</th>
                            <th class="px-2 py-3 text-left" style="width: 20%;">Marca</th>
                            <th class="px-2 py-3 text-center" style="width: 14%;">Conserv.</th>
                            <th class="px-2 py-3 text-center" style="width: 16%;">Cor / Tam</th>
                            <th class="px-2 py-3 text-right pr-3" style="width: 13%;">Preço Venda</th>
                            <th class="px-2 py-3 text-center" style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="hover:bg-indigo-50/30 transition-colors">
                                {{-- 1. Categoria / Nome / Código --}}
                                <td class="px-3 py-2.5 align-top">
                                    <div class="relative mb-1.5" @click.away="item.showCatDropdown = false">
                                        <!-- Busca de Categoria -->
                                        <div class="relative">
                                            <input
                                                type="text"
                                                placeholder="Pesquisar categoria..."
                                                x-model="item.catSearch"
                                                @focus="item.showCatDropdown = true; item.activeCatIndex = 0; $el.select();"
                                                @input="item.showCatDropdown = true; item.categoria_id = ''; item.activeCatIndex = 0;"
                                                @keydown.arrow-down.prevent="item.activeCatIndex = Math.min((getFilteredCategorias(item).length || 1) - 1, (item.activeCatIndex ?? 0) + 1)"
                                                @keydown.arrow-up.prevent="item.activeCatIndex = Math.max(0, (item.activeCatIndex ?? 0) - 1)"
                                                @keydown.enter.prevent="if (item.showCatDropdown && getFilteredCategorias(item).length > 0) { selectCategory(item, getFilteredCategorias(item)[item.activeCatIndex ?? 0], index); }"
                                                class="block w-full border border-gray-300 rounded-lg shadow-xs py-1.5 pl-2.5 pr-7 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-xs"
                                                autocomplete="off"
                                            >
                                            <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none text-gray-400">
                                                <i class="fas fa-search text-[10px]"></i>
                                            </div>
                                        </div>

                                        <!-- ID oculto -->
                                        <input type="hidden" :value="item.categoria_id">

                                        <!-- Dropdown de Categorias -->
                                        <div
                                            x-show="item.showCatDropdown"
                                            x-cloak
                                            class="absolute z-40 mt-1 w-full bg-white shadow-xl max-h-56 rounded-lg py-1 text-xs ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none border border-gray-200"
                                        >
                                            <template x-for="(cat, catIdx) in getFilteredCategorias(item)" :key="cat.id">
                                                <button
                                                    type="button"
                                                    @click="selectCategory(item, cat, index)"
                                                    :class="item.activeCatIndex === catIdx ? 'bg-indigo-50 text-indigo-900 font-semibold' : 'text-gray-900'"
                                                    class="w-full text-left px-3 py-2 hover:bg-indigo-50 focus:bg-indigo-50 transition-colors border-b border-gray-100 flex justify-between items-center gap-2"
                                                >
                                                    <span class="text-gray-700" x-html="item.catSearch ? cat.path : cat.formatted_name"></span>
                                                    <span class="text-[10px] text-gray-400 font-medium whitespace-nowrap"
                                                          x-show="cat.preco_base > 0"
                                                          x-text="`R$ ${parseFloat(cat.preco_base).toFixed(2)}`"></span>
                                                </button>
                                            </template>
                                            <div x-show="getFilteredCategorias(item).length === 0" class="px-3 py-2.5 text-gray-400 text-center font-medium">
                                                Nenhuma categoria encontrada
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Nome customizado do Item -->
                                    <div class="flex items-center gap-1.5">
                                        <input
                                            type="text"
                                            :id="`item-nome-${index}`"
                                            x-model="item.nome"
                                            required
                                            placeholder="Nome / Descrição da peça..."
                                            @keydown.enter.prevent="document.getElementById(`item-marca-${index}`)?.focus()"
                                            @blur="item.nome = capitalizeWords(item.nome)"
                                            class="block w-full border border-gray-300 rounded-lg shadow-xs py-1.5 px-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-xs font-medium"
                                        >

                                        <!-- Código gerado automaticamente (inativo) -->
                                        <div class="w-20 flex-shrink-0 flex items-center justify-center bg-gray-100 border border-gray-200 rounded-lg py-1.5 px-2 text-[11px] font-mono text-gray-500 select-none cursor-not-allowed shadow-xs"
                                             title="Código gerado automaticamente">
                                            <span x-text="item.codigoAuto || 'Auto'"></span>
                                        </div>
                                    </div>
                                </td>

                                {{-- 2. Marca (Campo 100% Editável) --}}
                                <td class="px-2 py-2.5 align-top">
                                    <input
                                        type="text"
                                        :id="`item-marca-${index}`"
                                        x-model="item.marca"
                                        list="marcas-list"
                                        placeholder="Digite ou selecione a marca..."
                                        @keydown.enter.prevent="document.getElementById(`item-estado-${index}`)?.focus()"
                                        @blur="item.marca = capitalizeWords(item.marca)"
                                        class="block w-full border border-gray-300 rounded-lg shadow-xs py-1.5 px-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-xs"
                                        autocomplete="off"
                                    >
                                </td>

                                {{-- 3. Conservação --}}
                                <td class="px-2 py-2.5 align-top text-center">
                                    <select
                                        :id="`item-estado-${index}`"
                                        x-model="item.estado"
                                        class="block w-full border border-gray-300 rounded-lg shadow-xs py-1.5 px-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-xs text-center bg-white"
                                    >
                                        <option value="Seminovo">Seminovo</option>
                                        <option value="Usado">Usado</option>
                                        <option value="Novo">Novo</option>
                                    </select>
                                </td>

                                {{-- 4. Cor / Tam --}}
                                <td class="px-2 py-2.5 align-top">
                                    <div class="space-y-1.5">
                                        <input
                                            type="text"
                                            x-model="item.cor"
                                            placeholder="Cor (ex: Azul)"
                                            @blur="item.cor = capitalizeWords(item.cor)"
                                            class="block w-full border border-gray-300 rounded-lg shadow-xs py-1.5 px-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-xs"
                                        >
                                        <input
                                            type="text"
                                            x-model="item.tamanho"
                                            placeholder="Tam (ex: M, 38)"
                                            @blur="item.tamanho = (item.tamanho || '').toUpperCase().trim()"
                                            class="block w-full border border-gray-300 rounded-lg shadow-xs py-1.5 px-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-xs"
                                        >
                                    </div>
                                </td>

                                {{-- 5. Preço de Venda (Editável) --}}
                                <td class="px-2 py-2.5 align-top text-right pr-3">
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-xs font-bold text-gray-500 pointer-events-none">
                                            R$
                                        </span>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            :id="`item-preco-${index}`"
                                            x-model="item.preco"
                                            required
                                            placeholder="0,00"
                                            @keydown.enter.prevent="onPrecoEnter(index)"
                                            class="block w-full pl-8 pr-2.5 py-1.5 text-right font-bold text-gray-900 border border-gray-300 rounded-lg shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-xs"
                                        >
                                    </div>
                                </td>

                                {{-- 6. Ações --}}
                                <td class="px-2 py-2.5 text-center align-top whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1">
                                        <button
                                            type="button"
                                            @click="duplicateItem(index)"
                                            title="Duplicar peça"
                                            class="w-7 h-7 rounded-lg text-indigo-600 hover:bg-indigo-50 hover:text-indigo-800 transition inline-flex items-center justify-center"
                                        >
                                            <i class="far fa-copy text-xs"></i>
                                        </button>
                                        <button
                                            type="button"
                                            @click="removeItem(index)"
                                            title="Remover peça"
                                            class="w-7 h-7 rounded-lg text-red-500 hover:bg-red-50 hover:text-red-700 transition inline-flex items-center justify-center"
                                            x-show="items.length > 1"
                                        >
                                            <i class="far fa-trash-alt text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50/75 border-t border-gray-200">
                            <td colspan="3" class="px-4 py-3">
                                <button
                                    type="button"
                                    @click="addItem(true)"
                                    id="btn-add-item"
                                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 text-xs font-bold transition duration-150 shadow-xs"
                                >
                                    <i class="fas fa-plus"></i>
                                    <span>Adicionar Outra Peça</span>
                                </button>
                            </td>
                            <td colspan="3" class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-4 text-xs">
                                    <span class="text-gray-600">
                                        Total de Peças: <strong class="text-gray-900 text-sm font-bold" x-text="items.length"></strong>
                                    </span>
                                    <span class="text-gray-600">
                                        Valor Total: <strong class="text-emerald-700 text-sm font-black" x-text="formatCurrency(totalVenda)"></strong>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Bottom Actions --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('items.index') }}"
               class="px-5 py-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium transition duration-150">
                Cancelar
            </a>
            <button
                type="submit"
                :disabled="isSaving"
                class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-sm transition duration-150 disabled:opacity-50"
            >
                <i class="fas fa-save" x-show="!isSaving"></i>
                <i class="fas fa-spinner fa-spin" x-show="isSaving"></i>
                <span x-text="isSaving ? 'Salvando...' : 'Salvar Itens no Estoque'"></span>
            </button>
        </div>
    </form>

</div>

<script>
function itemBatchForm() {
    return {
        categorias: @json($categorias ?? []),
        proximoCodigoBase: '{{ $proximoCodigo ?? "0001" }}',
        items: [],
        isSaving: false,

        init() {
            this.addItem(false);
        },

        get totalVenda() {
            return this.items.reduce((sum, item) => sum + (parseFloat(item.preco) || 0), 0);
        },

        addItem(focusNew = false) {
            const nextIdx = this.items.length;
            this.items.push({
                categoria_id: '',
                catSearch: '',
                showCatDropdown: false,
                activeCatIndex: 0,
                nome: '',
                codigo: '',
                codigoAuto: this.calculateNextCode(nextIdx),
                marca: '',
                estado: 'Seminovo',
                cor: '',
                tamanho: '',
                preco: ''
            });

            if (focusNew) {
                this.$nextTick(() => {
                    const inputs = document.querySelectorAll('input[placeholder="Pesquisar categoria..."]');
                    if (inputs && inputs.length > 0) {
                        inputs[inputs.length - 1].focus();
                    }
                });
            }
        },

        calculateNextCode(offset) {
            try {
                const baseDec = parseInt(this.proximoCodigoBase, 36);
                if (isNaN(baseDec)) return '';
                const nextDec = baseDec + offset;
                return nextDec.toString(36).toUpperCase().padStart(4, '0');
            } catch (e) {
                return '';
            }
        },

        duplicateItem(index) {
            const original = this.items[index];
            const duplicate = JSON.parse(JSON.stringify(original));
            duplicate.codigo = '';
            duplicate.codigoAuto = this.calculateNextCode(this.items.length);
            this.items.splice(index + 1, 0, duplicate);
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },

        getFilteredCategorias(item) {
            const query = (item.catSearch || '').toLowerCase().trim();
            if (!query) {
                return this.categorias;
            }
            return this.categorias.filter(c => 
                (c.name && c.name.toLowerCase().includes(query)) ||
                (c.path && c.path.toLowerCase().includes(query))
            );
        },

        selectCategory(item, cat, index) {
            item.categoria_id = cat.id;
            item.catSearch = cat.name;
            if (!item.nome || item.nome.trim() === '') {
                item.nome = this.capitalizeWords(this.singularizePortuguese(cat.name));
            }
            if (!item.preco || parseFloat(item.preco) === 0) {
                if (cat.preco_base && parseFloat(cat.preco_base) > 0) {
                    item.preco = parseFloat(cat.preco_base).toFixed(2);
                }
            }
            item.showCatDropdown = false;

            this.$nextTick(() => {
                const nomeInput = document.getElementById(`item-nome-${index}`);
                if (nomeInput) {
                    nomeInput.focus();
                }
            });
        },

        onPrecoEnter(index) {
            if (index === this.items.length - 1) {
                this.addItem(true);
            } else {
                const nextCatInput = document.querySelectorAll('input[placeholder="Pesquisar categoria..."]')[index + 1];
                if (nextCatInput) {
                    nextCatInput.focus();
                }
            }
        },

        capitalizeWords(str) {
            if (!str) return '';
            const prepositions = ['de', 'da', 'do', 'dos', 'das', 'com', 'em', 'para', 'e'];
            return str.toString().trim().split(/\s+/).map(w => {
                let lower = w.toLowerCase();
                if (prepositions.includes(lower)) return lower;
                return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase();
            }).join(' ');
        },

        singularizePortuguese(phrase) {
            if (!phrase) return '';
            const words = phrase.trim().split(/\s+/);
            const singularizedWords = words.map(w => {
                const lower = w.toLowerCase();
                const exceptions = ['tênis', 'óculos', 'lápis', 'pires', 'vírus', 'clube', 'status', 'jeans', 'grátis'];
                if (exceptions.includes(lower)) return w;
                if (lower.endsWith('ões')) return w.slice(0, -3) + 'ão';
                if (lower.endsWith('éis')) return w.slice(0, -3) + 'el';
                if (lower.endsWith('ais')) return w.slice(0, -3) + 'al';
                if (lower.endsWith('eis')) return w.slice(0, -3) + 'el';
                if (lower.endsWith('is')) {
                    if (lower.endsWith('ntis')) return w.slice(0, -3) + 'il';
                    if (lower.endsWith('uis')) return w.slice(0, -3) + 'ul';
                    return w.slice(0, -1);
                }
                if (lower.endsWith('res')) return w.slice(0, -2);
                if (lower.endsWith('s')) {
                    if (lower.endsWith('ts') || lower.endsWith('ys')) return w.slice(0, -1);
                    return w.slice(0, -1);
                }
                return w;
            });
            return singularizedWords.join(' ');
        },

        formatCurrency(value) {
            return 'R$ ' + (parseFloat(value) || 0).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        onSubmit(event) {
            const validItems = this.items.filter(item => item.nome && item.nome.trim() !== '');

            if (validItems.length === 0) {
                event.preventDefault();
                alert('Por favor, informe pelo menos uma peça com nome preenchido.');
                return;
            }

            for (let i = 0; i < validItems.length; i++) {
                if (!validItems[i].preco || parseFloat(validItems[i].preco) < 0) {
                    event.preventDefault();
                    alert(`Por favor, preencha o Preço de Venda do item #${i + 1}.`);
                    return;
                }
            }

            this.isSaving = true;

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'items_json';
            hidden.value = JSON.stringify(validItems);
            event.target.appendChild(hidden);
        }
    };
}
</script>
@endsection

@extends('layouts.app')

@section('title', isset($avaliacao) ? 'Editar Avaliação' : 'Nova Avaliação')

@section('content')
<style>
  #user_list .user-item.active { background: #eff6ff; }
</style>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    {{-- Breadcrumbs --}}
    <div class="mb-6">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('admin.avaliacoes.index') }}" class="text-gray-700 hover:text-blue-600 inline-flex items-center text-sm font-medium">
                        Avaliações de Desapegos
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 text-xs mx-2"></i>
                        <span class="text-gray-500 text-sm font-medium">{{ isset($avaliacao) ? 'Editar' : 'Nova' }}</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    {{-- Card Principal (Padrão de Layout de Pedidos) --}}
    <div class="bg-white shadow-lg rounded-lg p-4"
         id="evaluation-container"
         x-data="evaluationForm({
            categorias: {{ json_encode($categorias) }},
            marcas: {{ $marcas->toJson() }},
            editingAvaliacao: {{ isset($avaliacao) ? $avaliacao->toJson() : 'null' }},
            editingItems: {{ isset($avaliacao) ? $avaliacao->items->toJson() : '[]' }}
         })"
         @user-selected.window="onUserSelected($event.detail)">
        
        <h1 class="text-3xl font-semibold text-gray-800 mb-6">
            {{ isset($avaliacao) ? 'Editar Lote de Avaliação #' . str_pad($avaliacao->id, 5, '0', STR_PAD_LEFT) : 'Nova Avaliação (Entrada)' }}
        </h1>

        <form action="{{ isset($avaliacao) ? route('admin.avaliacoes.update', $avaliacao) : route('admin.avaliacoes.store') }}" method="POST" id="evaluation-form" @submit="onSubmit($event)">
            @csrf
            @if (isset($avaliacao))
                @method('PUT')
            @endif

            {{-- Grid de Informações Principais --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- Fornecedor / Cliente (Busca Autocomplete idêntica a Pedidos) --}}
                @php
                    $oldUserText = isset($avaliacao) && $avaliacao->user
                        ? ($avaliacao->user->name . (!empty($avaliacao->user->email) ? ' — ' . $avaliacao->user->email : ''))
                        : '';
                @endphp
                <div class="relative">
                    <label for="user_search" class="block text-sm font-medium text-gray-700 mb-1">Fornecedor / Cliente <span class="text-red-500">*</span></label>
                    <input id="user_search" type="text" autofocus
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="Digite nome ou e-mail..."
                           autocomplete="off"
                           value="{{ $oldUserText }}">
                    <input type="hidden" name="user_id" id="user_id_hidden" :value="userId">
                    <div id="user_list" class="absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-md shadow-lg hidden overflow-auto" style="max-height: 260px; z-index: 9999;"></div>
                </div>

                {{-- Status de Adesão --}}
                <div>
                    <label for="tipo_cliente" class="block text-sm font-medium text-gray-700 mb-1">Status de Adesão <span class="text-red-500">*</span></label>
                    <select id="tipo_cliente" x-model="tipoCliente" disabled
                            class="mt-1 block w-full border border-gray-300 bg-gray-50 text-gray-500 cursor-not-allowed rounded-md shadow-sm py-2 px-3 sm:text-sm">
                        <option value="fora_clube">Fora do Clube (Regra Geral)</option>
                        <option value="clube">Do Clube (Clube de Assinatura)</option>
                    </select>
                    <input type="hidden" name="tipo_cliente" :value="tipoCliente">
                </div>

                {{-- Tipo de Entrada (Regime) --}}
                <div>
                    <label for="tipo_compra" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Entrada (Regime) <span class="text-red-500">*</span></label>
                    <select name="tipo_compra" id="tipo_compra" x-model="tipoCompra" @change="recalculateAll()"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="avaliados">Avaliados (Avaliação e Precificação de Desapegos)</option>
                        <option value="direta">Compra Direta (Multiplica pelo Markup Contábil)</option>
                    </select>
                </div>

                {{-- Custo de Frete --}}
                <div>
                    <label for="frete_display" class="block text-sm font-medium text-gray-700 mb-1">Custo de Frete Total (R$)</label>
                    <input type="text" id="frete_display" x-model="freteRaw" 
                           @input="updateFrete()"
                           @blur="formatFrete()"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="0,00">
                    <input type="hidden" name="frete" :value="frete">
                </div>
            </div>

            {{-- Observações --}}
            <div class="mb-6">
                <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações do Lote</label>
                <textarea name="observacoes" id="observacoes" x-model="observacoes" rows="2"
                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                          placeholder="Detalhes adicionais (marcas, defeitos, observações gerais)..."></textarea>
            </div>

            {{-- Grid de Itens --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4 shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-tshirt text-blue-500"></i> Peças para Avaliação
                        </h2>
                    </div>
                    <button type="button" id="btn-add-item" @click="addItem(true)"
                            :disabled="!canAddItem()"
                            :class="!canAddItem() ? 'bg-gray-300 text-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700 text-white'"
                            class="text-sm font-bold py-2 px-4 rounded-lg shadow transition duration-200">
                        <i class="fas fa-plus mr-1"></i> Adicionar Peça
                    </button>
                </div>

                <div class="overflow-x-auto pb-36">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-[10px] uppercase font-bold tracking-wider">
                            <tr>
                                <th class="px-1 py-3 text-left w-[35%]">Categoria / Nome</th>
                                <th class="px-1 py-3 text-center w-[20%]">Marca</th>
                                <th class="px-1 py-3 text-center w-[2.5%]" x-show="tipoCompra === 'avaliados'">Conserv.</th>
                                <th class="px-1 py-3 text-center w-[2.5%]" x-show="tipoCompra === 'avaliados'">Curadoria</th>
                                <th class="px-1 py-3 text-center w-[10%]">Cor / Tam</th>
                                <th class="px-1 py-3 text-center w-[10%]" x-show="tipoCompra === 'avaliados'">Preço Base</th>
                                <th class="px-1 py-3 text-center w-[10%]" x-show="tipoCompra === 'direta'">Custo</th>
                                <th class="px-1 py-3 text-right w-[20%]">Preço Venda / Repasse</th>
                                <th class="px-1 py-3 text-center w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="hover:bg-gray-50">
                                     {{-- Categoria e Nome --}}
                                     <td class="px-1 py-2 align-top w-[35%]">
                                         <div class="relative mb-2" @click.away="item.showCatDropdown = false">
                                             <!-- Campo de busca -->
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
                                                     class="block w-full border border-gray-300 rounded-md shadow-sm py-1.5 pl-2.5 pr-8 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs"
                                                 >
                                                 <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-gray-400">
                                                     <i class="fas fa-search text-[10px]"></i>
                                                 </div>
                                             </div>
                                             
                                             <!-- Campo ID oculto -->
                                             <input type="hidden" :name="`items[${index}][categoria_id]`" :value="item.categoria_id">

                                             <!-- Dropdown flutuante de categorias -->
                                             <div 
                                                 x-show="item.showCatDropdown" 
                                                 x-cloak
                                                 class="absolute z-30 mt-1 w-full bg-white shadow-xl max-h-48 rounded-md py-1 text-xs ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none border border-gray-200"
                                             >
                                                 <template x-for="(cat, catIdx) in getFilteredCategorias(item)" :key="cat.id">
                                                     <button 
                                                         type="button"
                                                         @click="selectCategory(item, cat, index)"
                                                         :class="item.activeCatIndex === catIdx ? 'bg-blue-50 text-blue-900' : 'text-gray-900'"
                                                         class="w-full text-left px-3 py-1.5 hover:bg-indigo-50 focus:bg-indigo-50 transition-colors border-b border-gray-100 flex justify-between items-center gap-2"
                                                     >
                                                         <span class="font-semibold text-gray-700" x-html="item.catSearch ? cat.path : cat.formatted_name"></span>
                                                         <span class="text-[10px] text-gray-400 font-medium" x-text="`R$ ${parseFloat(cat.preco_base).toFixed(2)}`"></span>
                                                     </button>
                                                 </template>
                                                 <div x-show="getFilteredCategorias(item).length === 0" class="px-3 py-2 text-gray-400 text-center font-medium">
                                                     Nenhuma categoria encontrada
                                                 </div>
                                             </div>
                                         </div>
                                         
                                         <!-- Nome customizado do Item -->
                                         <input type="text" :id="`item-nome-${index}`" :name="`items[${index}][nome]`" x-model="item.nome" required placeholder="Nome/Descrição do item..."
                                                @keydown.enter.prevent="document.getElementById('item-brand-search-' + index)?.focus()"
                                                @blur="item.nome = capitalizeWords(item.nome)"
                                                class="block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-2.5 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs">
                                     </td>

                                     {{-- Marca --}}
                                     <td class="px-1 py-2 align-top w-[20%]">
                                         <div class="relative mb-2" @click.away="item.showBrandDropdown = false">
                                             <!-- Campo de busca -->
                                             <div class="relative">
                                                 <input 
                                                     :id="`item-brand-search-${index}`"
                                                     type="text" 
                                                     placeholder="Pesquisar marca..." 
                                                     x-model="item.brandSearch"
                                                     @focus="item.showBrandDropdown = true; item.activeBrandIndex = 0; $el.select();"
                                                     @input="item.showBrandDropdown = true; item.marca_id = ''; item.activeBrandIndex = 0;"
                                                     @keydown.arrow-down.prevent="item.activeBrandIndex = Math.min((getFilteredMarcas(item).length || 1) - 1, (item.activeBrandIndex ?? 0) + 1)"
                                                     @keydown.arrow-up.prevent="item.activeBrandIndex = Math.max(0, (item.activeBrandIndex ?? 0) - 1)"
                                                     @keydown.enter.prevent="if (item.showBrandDropdown && getFilteredMarcas(item).length > 0) { selectBrand(item, getFilteredMarcas(item)[item.activeBrandIndex ?? 0], index); }"
                                                     class="block w-full border border-gray-300 rounded-md shadow-sm py-1.5 pl-2.5 pr-8 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs"
                                                     autocomplete="off"
                                                 >
                                                 <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-gray-400">
                                                     <i class="fas fa-search text-[10px]"></i>
                                                 </div>
                                             </div>
                                             
                                             <!-- Campo ID oculto -->
                                             <input type="hidden" :name="`items[${index}][marca_id]`" :value="item.marca_id">

                                             <!-- Dropdown flutuante de marcas -->
                                             <div 
                                                 x-show="item.showBrandDropdown" 
                                                 x-cloak
                                                 class="absolute left-0 right-0 z-30 mt-1 bg-white shadow-xl max-h-48 rounded-md py-1 text-xs ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none border border-gray-200"
                                             >
                                                 <template x-for="(brand, bIdx) in getFilteredMarcas(item)" :key="brand.id">
                                                     <button 
                                                         type="button"
                                                         @click="selectBrand(item, brand, index)"
                                                         :class="item.activeBrandIndex === bIdx ? 'bg-blue-50 text-blue-900' : 'text-gray-900'"
                                                         class="w-full text-left px-3 py-1.5 hover:bg-indigo-50 focus:bg-indigo-50 transition-colors border-b border-gray-100 flex justify-between items-center gap-2"
                                                     >
                                                         <span class="font-semibold text-gray-700" x-text="brand.nome"></span>
                                                         <span class="text-[10px] text-gray-400 font-medium" x-text="`(${parseInt(brand.porcentagem_valor)}%)`"></span>
                                                     </button>
                                                 </template>
                                                 <div x-show="getFilteredMarcas(item).length === 0" class="px-3 py-2 text-center font-medium">
                                                     <span class="text-gray-400 block mb-2">Nenhuma marca encontrada</span>
                                                     <button type="button" @click="openNovaMarcaModal(item, index)" class="text-blue-600 text-xs font-bold hover:underline">
                                                         + Adicionar Nova Marca
                                                     </button>
                                                 </div>
                                             </div>
                                         </div>
                                         
                                         <!-- Nome textual da Marca selecionada/customizada -->
                                         <input type="text" :id="`item-marca-text-${index}`" :name="`items[${index}][marca]`" x-model="item.marca" required placeholder="Marca..."
                                                @blur="item.marca = capitalizeWords(item.marca)"
                                                class="block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-2.5 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs">
                                     </td>

                                    {{-- Conservação --}}
                                    <td class="px-1 py-2 text-center align-top w-[2.5%]" x-show="tipoCompra === 'avaliados'">
                                        <select :name="`items[${index}][estado]`" x-model="item.estado" @change="recalculateItem(item)"
                                                class="block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-1 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs text-center">
                                            <option value="Novo">Novo</option>
                                            <option value="Seminovo">Seminovo</option>
                                            <option value="Usado">Usado</option>
                                        </select>
                                    </td>

                                    {{-- Curadoria --}}
                                    <td class="px-1 py-2 text-center align-top w-[2.5%]" x-show="tipoCompra === 'avaliados'">
                                        <select :name="`items[${index}][nota_curadoria]`" x-model="item.nota_curadoria" @change="recalculateItem(item)"
                                                class="block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-1 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs text-center">
                                            <template x-for="n in 10">
                                                <option :value="n" x-text="n" :selected="item.nota_curadoria == n"></option>
                                            </template>
                                        </select>
                                    </td>

                                    {{-- Cor / Tam --}}
                                    <td class="px-1 py-2 align-top w-[10%]">
                                        <input type="text" :name="`items[${index}][cor]`" x-model="item.cor" placeholder="Cor"
                                               @blur="item.cor = capitalizeWords(item.cor)"
                                               class="block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs mb-2">
                                        <input type="text" :name="`items[${index}][tamanho]`" x-model="item.tamanho" placeholder="Tam"
                                               @blur="item.tamanho = (item.tamanho || '').toUpperCase().trim()"
                                               class="block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs">
                                    </td>

                                    {{-- Preço Base --}}
                                    <td class="px-1 py-2 text-center align-top w-[10%]">
                                        <input type="text" x-model="item.preco_base_raw" 
                                               @input="normalizePrecoBase(item); recalculateItem(item);"
                                               @blur="formatPrecoBaseRaw(item);"
                                               @keydown="onPrecoBaseKeyDown($event)"
                                               required
                                               class="block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-2.5 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs text-center">
                                        <input type="hidden" :name="`items[${index}][preco_base]`" :value="item.preco_base">
                                    </td>

                                    {{-- Resultados --}}
                                    <td class="px-1 py-2 text-right text-xs whitespace-nowrap align-top w-[20%]">
                                        <div class="font-bold text-gray-900">
                                            Venda: <span x-text="formatCurrency(item.preco_venda)"></span>
                                        </div>
                                        <div class="text-[10px] text-gray-500 mt-0.5" x-show="tipoCompra === 'avaliados'">
                                            Taxa Curad: <span x-text="formatCurrency(item.taxa_curadoria)"></span>
                                        </div>
                                        <div class="text-[10px] text-blue-600 font-semibold mt-0.5">
                                            Crédito: <span x-text="formatCurrency(item.payout_credito)"></span>
                                        </div>
                                        <div class="text-[10px] text-green-600 font-semibold">
                                            Dinheiro: <span x-text="formatCurrency(item.payout_dinheiro)"></span>
                                        </div>
                                    </td>

                                    {{-- Ações --}}
                                    <td class="px-1 py-2 text-center align-top whitespace-nowrap">
                                        <button type="button" @click="duplicateItem(index)" title="Duplicar Peça"
                                                class="text-blue-500 hover:text-blue-700 transition-colors w-8 h-8 rounded-lg hover:bg-blue-50 inline-flex items-center justify-center border border-transparent hover:border-blue-100">
                                            <i class="far fa-copy text-xs"></i>
                                        </button>
                                        <button type="button" @click="removeItem(index)" title="Remover Peça"
                                                class="text-red-500 hover:text-red-700 transition-colors w-8 h-8 rounded-lg hover:bg-red-50 inline-flex items-center justify-center border border-transparent hover:border-red-100">
                                            <i class="far fa-trash-alt text-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                              </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Resumo e Totais --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end bg-gray-50 rounded-lg p-6 border border-gray-100 mb-6">
                <div>
                    <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-2">Totais Estimados do Lote</h3>
                    <div class="space-y-1.5 text-xs text-gray-600">
                        <div class="flex justify-between">
                            <span>Quantidade de Peças:</span>
                            <span class="font-semibold text-gray-900" x-text="items.length"></span>
                        </div>
                        <div class="flex justify-between" x-show="parseFloat(frete) > 0">
                            <span>Frete por Item:</span>
                            <span class="font-semibold text-gray-900" x-text="formatCurrency(frete / (items.length || 1))"></span>
                        </div>
                        <div class="flex justify-between border-t border-gray-200 pt-2 text-sm font-bold text-gray-900">
                            <span>Total Venda Estimado:</span>
                            <span class="text-green-600" x-text="formatCurrency(totalVenda)"></span>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-4 space-y-2">
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Repasse Fornecedor</span>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Repasse Crédito:</span>
                        <span class="font-black text-lg text-blue-600" x-text="formatCurrency(totalPayoutCredito)"></span>
                    </div>
                    <div class="flex justify-between items-center border-t border-gray-100 pt-1">
                        <span class="text-xs text-gray-500">Repasse Dinheiro:</span>
                        <span class="font-black text-lg text-green-600" x-text="formatCurrency(totalPayoutDinheiro)"></span>
                    </div>
                </div>
            </div>

            {{-- Botões de Submissão --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.avaliacoes.index') }}"
                   class="bg-white py-2.5 px-5 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none transition">
                    Cancelar
                </a>
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm py-2.5 px-5 rounded-lg shadow-sm transition">
                    <i class="far fa-save mr-1.5"></i> Salvar Lote em Rascunho
                </button>
            </div>

        </form>
    </div>
</div>

<script>
function evaluationForm(config) {
    return {
        categorias: config.categorias,
        marcas: config.marcas,
        frete: 0.00,
        freteRaw: '0,00',
        observacoes: '',
        userId: '',
        tipoCliente: 'fora_clube',
        tipoCompra: 'avaliados',
        items: [],
        totalVenda: 0,
        totalPayoutCredito: 0,
        totalPayoutDinheiro: 0,
        showMarcaModal: false,
        salvandoMarca: false,
        novaMarcaForm: { nome: '', porcentagem_valor: 100 },
        marcaTargetItem: null,
        marcaTargetIndex: null,
        isSavingSilently: false,

        init() {
            if (config.editingAvaliacao) {
                const av = config.editingAvaliacao;
                this.frete = parseFloat(av.frete || 0);
                this.freteRaw = this.frete.toFixed(2).replace('.', ',');
                this.observacoes = av.observacoes || '';
                this.userId = av.user_id;
                this.tipoCliente = av.tipo_cliente;
                this.tipoCompra = av.tipo_compra;
                
                this.items = config.editingItems.map(item => {
                    let matchedMarcaId = item.marca_id;
                    if (!matchedMarcaId && item.marca) {
                        let lowerName = item.marca.toLowerCase().replace('_', ' ');
                        const matchedBrand = this.marcas.find(m => m.nome.toLowerCase().replace('_', ' ') === lowerName);
                        if (matchedBrand) {
                            matchedMarcaId = matchedBrand.id;
                        }
                    }
                    if (!matchedMarcaId) {
                        const semMarca = this.marcas.find(m => m.nome.toLowerCase() === 'sem marca' || m.nome.toLowerCase() === 'sem_marca');
                        matchedMarcaId = semMarca ? semMarca.id : '';
                    }

                    const catObj = this.categorias.find(c => c.id == item.categoria_id);
                    const brandObj = this.marcas.find(b => b.id == matchedMarcaId);

                    const isSemMarca = brandObj && (brandObj.nome.toLowerCase() === 'sem marca' || brandObj.nome.toLowerCase() === 'sem_marca');
                    return {
                        id: item.id,
                        categoria_id: item.categoria_id || '',
                        catSearch: catObj ? catObj.name : '',
                        showCatDropdown: false,
                        activeCatIndex: 0,
                        marca_id: matchedMarcaId,
                        brandSearch: (brandObj && !isSemMarca) ? (brandObj.nome + ' (' + parseInt(brandObj.porcentagem_valor) + '%)') : '',
                        showBrandDropdown: false,
                        activeBrandIndex: 0,
                        nome: item.nome,
                        marca: isSemMarca ? '' : (item.marca || (brandObj ? brandObj.nome : '')),
                        estado: item.estado,
                        nota_curadoria: item.nota_curadoria,
                        cor: item.cor || '',
                        tamanho: item.tamanho || '',
                        preco_base: parseFloat(item.preco_base),
                        preco_base_raw: parseFloat(item.preco_base).toFixed(2).replace('.', ','),
                        preco_venda: parseFloat(item.preco_venda),
                        taxa_curadoria: parseFloat(item.taxa_curadoria),
                        payout_credito: parseFloat(item.payout_credito),
                        payout_dinheiro: parseFloat(item.payout_dinheiro)
                    };
                });
            } else {
                this.addItem(false);
            }

            this.recalculateAll();
        },

        onUserSelected(detail) {
            this.userId = detail.id;
            this.tipoCliente = detail.tipo_cliente || 'fora_clube';
            this.recalculateAll();
        },

        canAddItem() {
            if (this.items.length === 0) return true;
            const lastItem = this.items[this.items.length - 1];
            return !!(lastItem.categoria_id && lastItem.nome && lastItem.nome.trim());
        },

        addItem(focusNew = false) {
            if (this.canAddItem() && this.items.length > 0) {
                this.saveSilently();
            }

            const semMarca = this.marcas.find(m => m.nome.toLowerCase() === 'sem marca' || m.nome.toLowerCase() === 'sem_marca');
            const defaultMarcaId = semMarca ? semMarca.id : '';

            this.items.push({
                categoria_id: '',
                catSearch: '',
                showCatDropdown: false,
                activeCatIndex: 0,
                nome: '',
                marca_id: defaultMarcaId,
                brandSearch: '',
                showBrandDropdown: false,
                activeBrandIndex: 0,
                marca: '',
                estado: 'Seminovo',
                nota_curadoria: 10,
                cor: '',
                tamanho: '',
                preco_base: 0.00,
                preco_base_raw: '0,00',
                preco_venda: 0.00,
                taxa_curadoria: 0.00,
                payout_credito: 0.00,
                payout_dinheiro: 0.00
            });
            this.recalculateAll();

            if (focusNew) {
                this.$nextTick(() => {
                    const inputs = document.querySelectorAll('input[placeholder="Pesquisar categoria..."]');
                    if (inputs && inputs.length > 0) {
                        inputs[inputs.length - 1].focus();
                    }
                });
            }
        },

        duplicateItem(index) {
            const original = this.items[index];
            const duplicate = JSON.parse(JSON.stringify(original));
            if (duplicate.id) {
                delete duplicate.id;
            }
            this.items.splice(index + 1, 0, duplicate);
            this.recalculateAll();
            this.saveSilently();
        },

        openNovaMarcaModal(item, index) {
            this.marcaTargetItem = item;
            this.marcaTargetIndex = index;
            this.novaMarcaForm.nome = item.brandSearch || '';
            this.novaMarcaForm.porcentagem_valor = 100;
            this.showMarcaModal = true;
        },

        async salvarNovaMarca() {
            if (!this.novaMarcaForm.nome) return;
            this.salvandoMarca = true;
            try {
                const formData = new FormData();
                formData.append('nome', this.novaMarcaForm.nome);
                formData.append('porcentagem_valor', this.novaMarcaForm.porcentagem_valor);
                formData.append('ajax', '1');
                formData.append('_token', document.querySelector('input[name="_token"]').value);

                const res = await fetch('{{ route("admin.marcas.store") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });
                
                if (res.ok) {
                    const data = await res.json();
                    if (data.success && data.marca) {
                        this.marcas.push(data.marca);
                        if (this.marcaTargetItem !== null) {
                            this.selectBrand(this.marcaTargetItem, data.marca, this.marcaTargetIndex);
                        }
                        this.showMarcaModal = false;
                    }
                } else {
                    const err = await res.json();
                    alert(err.message || 'Erro ao salvar marca. Pode já existir.');
                }
            } catch (e) {
                alert('Erro de conexão ao tentar salvar a marca.');
            } finally {
                this.salvandoMarca = false;
            }
        },

        async saveSilently() {
            if (!this.userId) return;
            const validItems = this.items.filter(i => i.categoria_id && i.nome && i.marca_id);
            if (validItems.length === 0) return;
            
            const form = document.getElementById('evaluation-form');
            if (!form.checkValidity()) return;
            
            this.isSavingSilently = true;
            try {
                const formData = new FormData(form);
                formData.append('ajax', '1');
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.success && data.id && !form.action.endsWith('/' + data.id)) {
                        form.action = '{{ url("admin/avaliacoes") }}/' + data.id;
                        if (!form.querySelector('input[name="_method"]')) {
                            const methodInput = document.createElement('input');
                            methodInput.type = 'hidden';
                            methodInput.name = '_method';
                            methodInput.value = 'PUT';
                            form.appendChild(methodInput);
                        }
                        const title = document.querySelector('h1');
                        if (title && title.innerText.includes('Nova')) {
                            title.innerText = 'Editar Lote de Avaliação #' + String(data.id).padStart(5, '0');
                        }
                    }
                }
            } catch (e) {
                console.error('Auto-save falhou', e);
            } finally {
                this.isSavingSilently = false;
            }
        },

        removeItem(index) {
            this.items.splice(index, 1);
            if (this.items.length === 0) {
                this.addItem(false);
            } else {
                this.recalculateAll();
            }
        },

        getFilteredCategorias(item) {
            const query = (item.catSearch || '').toLowerCase().trim();
            if (!query) {
                return this.categorias;
            }
            return this.categorias.filter(c => c.name.toLowerCase().includes(query));
        },

        selectCategory(item, cat, index) {
            item.categoria_id = cat.id;
            item.catSearch = cat.name;
            item.nome = this.capitalizeWords(this.singularizePortuguese(cat.name));
            item.preco_base = parseFloat(cat.preco_base);
            item.preco_base_raw = parseFloat(cat.preco_base || 0).toFixed(2).replace('.', ',');
            item.showCatDropdown = false;
            this.recalculateItem(item);

            this.$nextTick(() => {
                const inputEl = document.getElementById(`item-nome-${index}`);
                if (inputEl) {
                    inputEl.focus();
                }
            });
        },

        getFilteredMarcas(item) {
            let query = (item.brandSearch || '').trim();
            const idx = query.indexOf('(');
            if (idx >= 0) {
                query = query.substring(0, idx).trim();
            }
            query = query.toLowerCase();

            if (!query) {
                return this.marcas;
            }
            return this.marcas.filter(m => m.nome.toLowerCase().includes(query));
        },

        selectBrand(item, brand, index) {
            item.marca_id = brand.id;
            const isSemMarca = brand && (brand.nome.toLowerCase() === 'sem marca' || brand.nome.toLowerCase() === 'sem_marca');
            item.brandSearch = isSemMarca ? '' : (brand.nome + ' (' + parseInt(brand.porcentagem_valor) + '%)');
            item.marca = isSemMarca ? '' : brand.nome;
            item.showBrandDropdown = false;
            this.recalculateItem(item);

            this.$nextTick(() => {
                const inputEl = document.getElementById(`item-marca-text-${index}`);
                if (inputEl) {
                    inputEl.focus();
                }
            });
        },

        normalizePrecoBase(item) {
            let cleaned = (item.preco_base_raw || '').toString().replace(',', '.');
            let val = parseFloat(cleaned);
            item.preco_base = isNaN(val) ? 0 : val;
        },

        formatPrecoBaseRaw(item) {
            let val = parseFloat(item.preco_base || 0);
            item.preco_base_raw = val.toFixed(2).replace('.', ',');
        },

        updateFrete() {
            let cleaned = (this.freteRaw || '').toString().replace(',', '.');
            let val = parseFloat(cleaned);
            this.frete = isNaN(val) ? 0 : val;
            this.recalculateAll();
        },

        formatFrete() {
            let val = parseFloat(this.frete || 0);
            this.freteRaw = val.toFixed(2).replace('.', ',');
        },

        onPrecoBaseKeyDown(e) {
            if (e.key === 'Enter' || e.key === 'Tab') {
                e.preventDefault();
                const btn = document.getElementById('btn-add-item');
                if (btn) {
                    btn.focus();
                }
            }
        },

        capitalizeWords(str) {
            if (!str) return '';
            const prepositions = ['de', 'da', 'do', 'dos', 'das', 'com', 'em', 'para', 'e'];
            return str.toString().trim().split(/\s+/).map(w => {
                let lower = w.toLowerCase();
                if (prepositions.includes(lower)) {
                    return lower;
                }
                return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase();
            }).join(' ');
        },

        singularizePortuguese(phrase) {
            if (!phrase) return '';
            
            const words = phrase.trim().split(/\s+/);
            const singularizedWords = words.map(w => {
                const lower = w.toLowerCase();
                const exceptions = ['tênis', 'óculos', 'lápis', 'pires', 'vírus', 'clube', 'status', 'jeans', 'grátis'];
                if (exceptions.includes(lower)) {
                    return w;
                }
                
                if (lower.endsWith('ões')) {
                    return w.slice(0, -3) + 'ão';
                }
                if (lower.endsWith('éis')) {
                    return w.slice(0, -3) + 'el';
                }
                if (lower.endsWith('ais')) {
                    return w.slice(0, -3) + 'al';
                }
                if (lower.endsWith('eis')) {
                    return w.slice(0, -3) + 'el';
                }
                if (lower.endsWith('is')) {
                    if (lower.endsWith('ntis')) {
                        return w.slice(0, -3) + 'il';
                    }
                    if (lower.endsWith('uis')) {
                        return w.slice(0, -3) + 'ul';
                    }
                    if (lower.endsWith('nis')) {
                        return w.slice(0, -1);
                    }
                    return w.slice(0, -1);
                }
                if (lower.endsWith('res')) {
                    return w.slice(0, -2);
                }
                if (lower.endsWith('s')) {
                    if (lower.endsWith('ts')) {
                        return w.slice(0, -1);
                    }
                    if (lower.endsWith('ys')) {
                        return w.slice(0, -1);
                    }
                    return w.slice(0, -1);
                }
                return w;
            });
            
            return singularizedWords.join(' ');
        },

        recalculateItem(item) {
            const numItems = this.items.length || 1;
            const freteUnitario = parseFloat(this.frete || 0) / numItems;

            if (this.tipoCompra === 'direta') {
                item.preco_venda = parseFloat(item.preco_base || 0) * 2.023121387;
                item.taxa_curadoria = 0.00;
                item.payout_credito = parseFloat(item.preco_base || 0);
                item.payout_dinheiro = parseFloat(item.preco_base || 0);
            } else {
                const brand = this.marcas.find(m => m.id == item.marca_id);
                const brandPct = brand ? parseFloat(brand.porcentagem_valor) : 100.00;

                item.preco_venda = (parseFloat(item.preco_base || 0) * (brandPct / 100.00)) * (parseInt(item.estado) / 10.0);

                const nota = parseInt(item.nota_curadoria) || 10;
                if (nota === 10) {
                    item.taxa_curadoria = 0.00;
                } else if (nota === 1) {
                    item.taxa_curadoria = 10.00;
                } else {
                    item.taxa_curadoria = parseFloat(10 - nota);
                }

                if (this.tipoCliente === 'clube') {
                    const payCredit = (item.preco_venda * 0.60) - freteUnitario - item.taxa_curadoria;
                    const payCash = (item.preco_venda * 0.40) - freteUnitario - item.taxa_curadoria;
                    item.payout_credito = Math.max(0.00, payCredit);
                    item.payout_dinheiro = Math.max(0.00, payCash);
                } else {
                    const payCredit = (item.preco_venda * 0.50) - freteUnitario - item.taxa_curadoria;
                    const payCash = (item.preco_venda * 0.30) - freteUnitario - item.taxa_curadoria;
                    item.payout_credito = Math.max(0.00, payCredit);
                    item.payout_dinheiro = Math.max(0.00, payCash);
                }
            }
            
            this.sumTotals();
        },

        recalculateAll() {
            this.items.forEach(item => this.recalculateItem(item));
        },

        sumTotals() {
            this.totalVenda = this.items.reduce((sum, item) => sum + parseFloat(item.preco_venda || 0), 0);
            this.totalPayoutCredito = this.items.reduce((sum, item) => sum + parseFloat(item.payout_credito || 0), 0);
            this.totalPayoutDinheiro = this.items.reduce((sum, item) => sum + parseFloat(item.payout_dinheiro || 0), 0);
        },

        formatCurrency(value) {
            return 'R$ ' + parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        onSubmit(e) {
            if (!this.userId) {
                e.preventDefault();
                alert('Por favor, selecione um Fornecedor / Cliente válido pesquisando pelo nome.');
                return;
            }

            for (let i = 0; i < this.items.length; i++) {
                if (!this.items[i].categoria_id) {
                    e.preventDefault();
                    alert(`Por favor, selecione uma categoria válida para o item #${i + 1} pesquisando e clicando na opção.`);
                    return;
                }
            }
        }
    };
}

// Autocomplete Usuário (Vanilla) - Idêntico ao de Pedidos
document.addEventListener('DOMContentLoaded', function () {
  var input = document.getElementById('user_search');
  var hidden = document.getElementById('user_id_hidden');
  var list = document.getElementById('user_list');

  if (!input || !hidden || !list) return;

  // Evitar submissão do formulário ao pressionar Enter nos campos de input
  var form = document.getElementById('evaluation-form');
  if (form) {
    form.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
        e.preventDefault();
        return false;
      }
    });
  }

  var debounceTimer = null;
  var abortCtrl = null;

  var activeIndex = -1;
  var currentItems = [];

  function escapeHtml(s) {
    s = (s === null || s === undefined) ? '' : String(s);
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

    for (var i = 0; i < items.length; i++) {
      items[i].classList.remove('bg-blue-50', 'text-blue-900');
    }
    items[activeIndex].classList.add('bg-blue-50', 'text-blue-900');

    if (items[activeIndex].scrollIntoView) {
      items[activeIndex].scrollIntoView({ block: 'nearest' });
    }
  }

  function selectUser(user) {
    var name = user && user.name ? String(user.name) : '';
    input.value = name + (user.email ? ' — ' + user.email : '');
    hidden.value = user && user.id ? user.id : '';
    
    // Dispara evento para o AlpineJS sincronizar
    window.dispatchEvent(new CustomEvent('user-selected', {
      detail: {
        id: user.id,
        tipo_cliente: user.tipo_cliente || 'fora_clube'
      }
    }));
    
    hideList();
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

  function normalizeResponse(data) {
    if (Array.isArray(data)) return data;
    if (data && Array.isArray(data.data)) return data.data;
    return [];
  }

  function renderResults(q, data) {
    var users = normalizeResponse(data);

    currentItems = users;
    activeIndex = users.length > 0 ? 0 : -1;

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

    if (activeIndex >= 0) {
      setActive(activeIndex);
    }
  }

  input.addEventListener('input', function () {
    clearTimeout(debounceTimer);
    var q = input.value;
    debounceTimer = setTimeout(function () { fetchUsers(q); }, 250);
  });

  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      if (activeIndex >= 0 && currentItems[activeIndex]) {
        selectUser(currentItems[activeIndex]);
      }
      return;
    }

    if (list.classList.contains('hidden')) return;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setActive(activeIndex + 1);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setActive(activeIndex - 1);
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
  setTimeout(function () {
    input.focus();
  }, 50);
});
</script>
@endsection

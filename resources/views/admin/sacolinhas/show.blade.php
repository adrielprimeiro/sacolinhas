@extends('layouts.app')

@section('title', 'Sacolinha - ' . ($user->name ?? 'Cliente'))
@section('brand_route', 'admin.sacolinha.gestao')
@section('brand_icon', 'fas fa-shopping-bag')

@section('content')
<div class="space-y-6" x-data="sacolinhaAdmin()">

    <!-- Cabeçalho -->
    <div class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-between border border-gray-200">
        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ $user->name }}</h1>
            <p class="text-gray-600 text-sm">
                <span class="font-bold">{{ $itens->count() }}</span> {{ $itens->count() == 1 ? 'Item' : 'Itens' }}
            </p>
            <div class="mt-2 space-y-1">
                <div class="flex items-baseline gap-2">
                    <p class="text-xs text-gray-500 uppercase font-semibold">Total Itens:</p>
                    <p class="text-xl font-bold text-gray-900">R$ {{ number_format($total ?? 0, 2, ',', '.') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-2 bg-green-50 px-3 py-1 rounded-full border border-green-100">
                        <i class="fas fa-wallet text-green-600 text-[10px]"></i>
                        <span class="text-[10px] text-green-600 font-bold uppercase">Saldo Carteira:</span>
                        <span class="text-sm font-bold text-green-700">R$ {{ number_format($valorPago ?? 0, 2, ',', '.') }}</span>
                    </div>
                    @if($valorPago != 0)
                        <span class="text-[10px] text-gray-400 italic">
                            ({{ $valorPago > 0 ? 'Será descontado' : 'Será somado' }} no fechamento)
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-2 mt-2" x-show="selectedIds.length > 0" style="display: none;">
                    <div class="flex items-center gap-2 bg-blue-50 px-3 py-1.5 rounded-full border border-blue-100">
                        <i class="fas fa-check-double text-blue-600 text-[10px]"></i>
                        <span class="text-[10px] text-blue-600 font-bold uppercase">Selecionados (<span x-text="selectedIds.length"></span>):</span>
                        <span class="text-sm font-bold text-blue-700">R$ <span x-text="parseFloat(selectedTotal).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-col gap-2 min-w-[180px]">
            <div class="flex items-center bg-gray-100 rounded-lg px-3 py-1.5 border border-gray-200">
                <span class="text-[10px] font-bold text-gray-400 uppercase mr-2">Frete R$</span>
                <input type="number" step="0.01" x-model="freteValor" 
                       class="bg-transparent border-none focus:ring-0 w-20 text-right font-bold text-blue-600 p-0"
                       placeholder="0,00">
            </div>
            <button id="btnFecharSacolinha" 
                    :disabled="selectedIds.length === 0"
                    @click="fecharSacolinha()"
                    :class="selectedIds.length === 0 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700 text-white cursor-pointer'"
                    class="w-full text-xs font-bold py-2 px-4 rounded-lg transition duration-200 uppercase tracking-wider flex items-center justify-center gap-2">
                <i class="fas fa-check-circle"></i> Fechar Sacolinha
            </button>
            <button id="btnSimularFrete" 
                    :disabled="selectedIds.length === 0"
                    @click="openModalFrete()"
                    :class="selectedIds.length === 0 ? 'border-gray-200 text-gray-400 cursor-not-allowed' : 'border-blue-600 text-blue-600 hover:bg-blue-50 cursor-pointer'"
                    class="w-full border-2 text-xs font-bold py-1.5 px-4 rounded-lg transition duration-200 uppercase tracking-wider flex items-center justify-center gap-2">
                <i class="fas fa-truck"></i> Simular Frete
            </button>
            <a href="{{ route('admin.sacolinha.pdf', $user->id) }}" target="_blank"
               class="w-full bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition duration-200 uppercase tracking-wider flex items-center justify-center gap-2 text-center">
                <i class="fas fa-file-pdf"></i> Imprimir Sacolinha
            </a>
        </div>
    </div>

    <!-- Lista -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center justify-between flex-1">
                <h2 class="text-sm font-semibold text-gray-800 uppercase tracking-wider">Itens na Sacola</h2>
            </div>
            <div class="flex items-center gap-4">
                <button @click="openModalAddItem()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition duration-200 uppercase tracking-wider flex items-center justify-center gap-2 shadow-sm">
                    <i class="fas fa-plus"></i> Adicionar Item
                </button>
            </div>
        </div>

        @if(($itens->count() ?? 0) === 0)
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shopping-basket text-gray-400 text-2xl"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Esta sacolinha está vazia</p>
                <p class="text-sm text-gray-500 mt-1">Clique em "Adicionar Item" para começar.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">Produto</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">Detalhes</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">Adicionado em</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">Valor</th>
                            <th class="px-4 py-3 text-center text-[10px] font-bold text-gray-500 uppercase tracking-widest">Ação</th>
                            <th class="px-4 py-3 text-center text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                                <div class="flex items-center justify-center gap-1.5 cursor-pointer">
                                    <span>Sel.</span>
                                    <input type="checkbox" 
                                           @change="toggleSelectAll($event)"
                                           :checked="isAllSelected()"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4 cursor-pointer"
                                           title="Selecionar Todos">
                                </div>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($itens as $item)
                            @php
                                $img = $item->image ?? null;
                                $imgUrl = $img ? asset('storage/' . ltrim($img, '/')) : asset('images/no-image.png');
                                
                                $detalhes = [];
                                if (!empty($item->marca)) $detalhes[] = $item->marca;
                                if (!empty($item->estado)) $detalhes[] = $item->estado;
                                if (!empty($item->cor)) $detalhes[] = $item->cor;
                                if (!empty($item->tamanho)) $detalhes[] = 'Tam: ' . $item->tamanho;
                                
                                $detalhesFormatados = implode(' • ', $detalhes);
                                
                                $emAnalise = strtolower($item->sacolinha_status ?? '') === 'em analise';
                            @endphp

                            <tr class="{{ $emAnalise ? 'bg-yellow-50 hover:bg-yellow-100' : 'hover:bg-gray-50' }}">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 bg-gray-100 rounded-md overflow-hidden flex items-center justify-center flex-shrink-0 border border-gray-100">
                                            <img src="{{ $imgUrl }}" alt="{{ $item->nome_do_produto }}" class="w-full h-full object-cover">
                                        </div>

                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 leading-tight">
                                                {{ $item->nome_do_produto }}
                                            </p>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                Cod: <span class="font-bold">{{ $item->codigo }}</span>
                                            </p>
                                            @if($emAnalise)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-yellow-100 text-yellow-800 mt-1 uppercase tracking-wider">
                                                    Em Análise
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-xs text-gray-600">
                                    {{ $detalhesFormatados }}
                                </td>

                                <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                                    {{ !empty($item->add_at) ? \Carbon\Carbon::parse($item->add_at)->format('d/m/Y H:i') : '—' }}
                                </td>

                                <td class="px-4 py-3 text-sm font-bold text-gray-800">
                                    R$ {{ number_format($item->price ?? 0, 2, ',', '.') }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" 
                                                @click="openModalEditPrice({{ $item->sacolinha_id }}, '{{ addslashes($item->nome_do_produto) }}', '{{ $item->codigo }}', {{ $item->price ?? 0 }}, '{{ $imgUrl }}')"
                                                class="text-blue-500 hover:text-blue-700 transition"
                                                title="Editar Preço">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" 
                                                @click="confirmarRemocao({{ $item->item_id }}, {{ $user->id }}, {{ $item->live_id }}, '{{ addslashes($item->nome_do_produto) }}', 'R$ {{ number_format($item->price ?? 0, 2, ',', '.') }}')"
                                                class="text-red-500 hover:text-red-700 transition"
                                                title="Remover Item">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <input type="checkbox" 
                                           value="{{ $item->sacolinha_id }}" 
                                           data-item-id="{{ $item->item_id }}"
                                           data-price="{{ $item->price }}"
                                           @change="updateSelection($event)"
                                           :checked="selectedIds.includes({{ $item->sacolinha_id }})"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-5 w-5 cursor-pointer">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-gray-200 flex items-center justify-between bg-gray-50 flex-wrap gap-4">
                <div class="flex items-center gap-6 flex-wrap">
                    <p class="text-sm text-gray-600">
                        Valor Total: <span class="font-bold text-gray-900 text-lg">R$ {{ number_format($total ?? 0, 2, ',', '.') }}</span>
                    </p>
                    <p class="text-sm text-blue-600 font-semibold" x-show="selectedIds.length > 0">
                        Selecionados (<span x-text="selectedIds.length"></span>): 
                        <span class="font-bold text-blue-800 text-lg">R$ <span x-text="parseFloat(selectedTotal).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span></span>
                    </p>
                </div>
                <a href="{{ route('admin.sacolinha.gestao') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800 flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i> Voltar para Lista
                </a>
            </div>
        @endif
    </div>

    <!-- Modal Adicionar Item -->
    <div x-show="modalAddItem" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="modalAddItem = false"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 overflow-hidden">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Adicionar Item à Sacola</h3>
                    <button @click="modalAddItem = false" class="text-gray-400 hover:text-gray-600 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Código do Item</label>
                        <div class="flex gap-2">
                            <input type="text" x-model="searchCode" @keydown.enter="searchItem()"
                                   placeholder="Digite o código (ex: 00123)"
                                   class="flex-1 rounded-xl border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-200 transition">
                            <button @click="searchItem()" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 rounded-xl transition duration-200 flex items-center gap-2"
                                    :disabled="searching">
                                <template x-if="searching">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </template>
                                <template x-if="!searching">
                                    <i class="fas fa-search"></i>
                                </template>
                                Buscar
                            </button>
                        </div>
                    </div>

                    <!-- Resultado da Busca -->
                    <div x-show="foundItem" class="bg-gray-50 rounded-2xl p-4 border border-gray-100" x-transition>
                        <div class="flex gap-4">
                            <div class="w-20 h-20 bg-white rounded-xl overflow-hidden shadow-sm flex-shrink-0 border border-gray-100">
                                <img :src="foundItem.image_url" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-800" x-text="foundItem.nome_do_produto"></p>
                                <p class="text-xs text-gray-500" x-text="foundItem.marca + ' • ' + foundItem.tamanho"></p>
                                
                                <div class="mt-3">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Preço do Lançamento</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">R$</span>
                                        <input type="number" step="0.01" x-model="editPrice"
                                               class="w-full pl-10 rounded-xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition font-bold text-lg text-gray-800">
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-1">Preço original: R$ <span x-text="foundItem.preco"></span></p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Observação (opcional)</label>
                            <textarea x-model="addObs" rows="2" class="w-full rounded-xl border-gray-200 text-sm" placeholder="Ex: Ajuste de preço negociado..."></textarea>
                        </div>

                        <button @click="confirmAddItem()" 
                                class="w-full mt-4 bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl shadow-lg transition duration-200 flex items-center justify-center gap-2"
                                :disabled="adding">
                            <template x-if="adding">
                                <i class="fas fa-spinner fa-spin"></i>
                            </template>
                            <template x-if="!adding">
                                <i class="fas fa-plus-circle"></i>
                            </template>
                            Adicionar na Sacolinha
                        </button>
                    </div>

                    <div x-show="errorMessage" class="text-red-500 text-sm text-center p-4 bg-red-50 rounded-xl border border-red-100" x-text="errorMessage"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Simular Frete -->
    <div x-show="modalFrete" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;"
         x-transition>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="modalFrete = false"></div>

            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Simular Frete</h3>
                    <button @click="modalFrete = false" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Informe o CEP do Cliente</label>
                    <div class="flex gap-2">
                        <input type="text" x-model="cepInput" x-mask="99999-999" placeholder="00000-000"
                               class="flex-1 border-gray-300 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <button @click="calcularFrete()" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-xl transition duration-200">
                            Calcular
                        </button>
                    </div>
                    <p x-show="cepError" class="text-red-500 text-xs mt-1" x-text="cepError"></p>
                </div>
                
                <div x-show="freteResults" class="space-y-3 mt-4 max-h-60 overflow-y-auto">
                    <template x-for="opt in freteResults" :key="opt.name">
                        <div class="border border-gray-100 rounded-xl p-3 flex justify-between items-center bg-gray-50">
                            <div class="flex items-center gap-3">
                                <img :src="opt.company.picture" class="h-8 w-8 object-contain">
                                <div>
                                    <p class="text-sm font-bold text-gray-800" x-text="opt.name"></p>
                                    <p class="text-[10px] text-gray-500 uppercase tracking-wider" x-text="'Prazo: ' + opt.delivery_time + ' dias'"></p>
                                </div>
                            </div>
                            <div class="text-right flex items-center gap-3">
                                <p class="text-sm font-bold text-blue-700" x-text="'R$ ' + parseFloat(opt.price).toLocaleString('pt-BR', {minimumFractionDigits: 2})"></p>
                                <button @click="freteValor = opt.price; modalFrete = false" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold py-1 px-2 rounded-md transition duration-200 uppercase">
                                    Aplicar
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
                
                <div x-show="loadingFrete" class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-blue-600 text-3xl"></i>
                    <p class="text-sm text-gray-500 mt-2 font-medium">Calculando rotas...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Remoção com Pontuação -->
    <div x-show="modalRemocao" 
         class="fixed inset-0 z-[60] overflow-y-auto" 
         style="display: none;"
         x-transition>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="modalRemocao = false"></div>

            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="bg-red-600 p-4 text-white flex justify-between items-center">
                    <h5 class="font-bold flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle"></i> Remover Item
                    </h5>
                    <button @click="modalRemocao = false" class="text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6 text-center">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Você está removendo:</p>
                    <h4 class="text-lg font-bold text-gray-800 mb-2" x-text="itemRemocao.nome"></h4>
                    <div class="bg-gray-50 p-3 rounded-xl mb-4 border border-gray-100">
                        <p class="text-sm text-gray-600">Valor do item: <strong class="text-red-600" x-text="itemRemocao.preco"></strong></p>
                    </div>
                    <p class="text-sm text-gray-600">Como deseja prosseguir com a pontuação do cliente?</p>
                </div>
                <div class="p-4 bg-gray-50 flex flex-col gap-2">
                    <button type="button" @click="executarRemocao(true)" 
                            class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 rounded-xl transition duration-200 flex items-center justify-center gap-2">
                        <i class="fas fa-minus-circle"></i> Retirar descontando pontos
                    </button>
                    <button type="button" @click="executarRemocao(false)" 
                            class="w-full border-2 border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-2 rounded-xl transition duration-200 flex items-center justify-center gap-2">
                        <i class="fas fa-check"></i> Retirar SEM desconto nos pontos
                    </button>
                    <button type="button" @click="modalRemocao = false" class="text-xs text-gray-400 font-bold mt-2 hover:text-gray-600">
                        CANCELAR
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Preço do Item -->
    <div x-show="modalEditPrice" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="modalEditPrice = false"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 overflow-hidden">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Editar Preço do Item</h3>
                    <button @click="modalEditPrice = false" class="text-gray-400 hover:text-gray-600 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 flex gap-3">
                        <div class="w-12 h-12 bg-white rounded-lg overflow-hidden border border-gray-100 flex-shrink-0">
                            <img :src="editItemData.imageUrl" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-800" x-text="editItemData.nome"></p>
                            <p class="text-xs text-gray-500" x-text="'Código: ' + editItemData.codigo"></p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Novo Preço</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">R$</span>
                            <input type="number" step="0.01" x-model="editItemData.price"
                                   class="w-full pl-10 rounded-xl border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-200 transition font-bold text-lg text-gray-800">
                        </div>
                    </div>

                    <button @click="confirmEditPrice()" 
                            class="w-full mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-lg transition duration-200 flex items-center justify-center gap-2"
                            :disabled="savingPrice">
                        <template x-if="savingPrice">
                            <i class="fas fa-spinner fa-spin"></i>
                        </template>
                        <template x-if="!savingPrice">
                            <i class="fas fa-save"></i>
                        </template>
                        Salvar Preço
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function sacolinhaAdmin() {
    return {
        modalAddItem: false,
        modalFrete: false,
        freteValor: 0,
        searchCode: '',
        foundItem: null,
        searching: false,
        adding: false,
        errorMessage: '',
        editPrice: 0,
        addObs: '',
        selectedIds: [],
        selectedItemIds: [],
        selectedTotal: 0,
        allItems: [
            @foreach($itens as $item)
                { id: {{ $item->sacolinha_id }}, itemId: {{ $item->item_id }}, price: {{ $item->price ?? 0 }} },
            @endforeach
        ],
        cepInput: '{{ $user->cep ?? '' }}',
        freteResults: null,
        loadingFrete: false,
        cepError: '',
        modalRemocao: false,
        modalEditPrice: false,
        savingPrice: false,
        editItemData: {
            sacolinhaId: null,
            nome: '',
            codigo: '',
            price: 0,
            imageUrl: ''
        },
        itemRemocao: {
            id: null,
            user_id: null,
            live_id: null,
            nome: '',
            preco: ''
        },

        confirmarRemocao(itemId, userId, liveId, nome, preco) {
            this.itemRemocao = { id: itemId, user_id: userId, live_id: liveId, nome, preco };
            this.modalRemocao = true;
        },

        async executarRemocao(descontar) {
            try {
                const response = await fetch('/api/sacolinhas/remove', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        item_id: this.itemRemocao.id,
                        user_id: this.itemRemocao.user_id,
                        live_id: this.itemRemocao.live_id,
                        descontar_pontos: descontar
                    })
                });

                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Erro ao remover item');
                }
            } catch (e) {
                alert('Erro na comunicação com o servidor');
            } finally {
                this.modalRemocao = false;
            }
        },

        openModalAddItem() {
            this.modalAddItem = true;
            this.searchCode = '';
            this.foundItem = null;
            this.errorMessage = '';
        },

        async searchItem() {
            if (!this.searchCode) return;
            this.searching = true;
            this.errorMessage = '';
            this.foundItem = null;

            try {
                const res = await fetch(`{{ route('admin.sacolinha.searchItem') }}?codigo=${this.searchCode}`);
                const data = await res.json();
                if (data.success) {
                    this.foundItem = data.item;
                    this.editPrice = data.item.preco;
                } else {
                    this.errorMessage = data.message;
                }
            } catch (e) {
                this.errorMessage = 'Erro ao buscar item.';
            } finally {
                this.searching = false;
            }
        },

        async confirmAddItem() {
            this.adding = true;
            try {
                const res = await fetch(`{{ route('admin.sacolinha.addItem') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        user_id: {{ $user->id }},
                        item_id: this.foundItem.id,
                        price: this.editPrice,
                        obs: this.addObs
                    })
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            } catch (e) {
                alert('Erro ao adicionar item.');
            } finally {
                this.adding = false;
            }
        },

        openModalEditPrice(sacolinhaId, nome, codigo, price, imageUrl) {
            this.editItemData = { sacolinhaId, nome, codigo, price, imageUrl };
            this.modalEditPrice = true;
        },

        async confirmEditPrice() {
            this.savingPrice = true;
            try {
                const res = await fetch(`{{ route('admin.sacolinha.updatePrice') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        sacolinha_id: this.editItemData.sacolinhaId,
                        price: this.editItemData.price
                    })
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            } catch (e) {
                alert('Erro ao atualizar preço.');
            } finally {
                this.savingPrice = false;
            }
        },

        isAllSelected() {
            return this.allItems.length > 0 && this.selectedIds.length === this.allItems.length;
        },

        toggleSelectAll(e) {
            const checked = e.target.checked;
            if (checked) {
                this.selectedIds = this.allItems.map(item => parseInt(item.id));
                this.selectedItemIds = this.allItems.map(item => parseInt(item.itemId));
                this.selectedTotal = this.allItems.reduce((sum, item) => sum + parseFloat(item.price), 0);
            } else {
                this.selectedIds = [];
                this.selectedItemIds = [];
                this.selectedTotal = 0;
            }
            this.selectedTotal = Math.max(0, parseFloat(this.selectedTotal.toFixed(2)));
        },

        updateSelection(e) {
            const cb = e.target;
            const price = parseFloat(cb.dataset.price) || 0;
            const val = parseInt(cb.value);
            const itemId = parseInt(cb.dataset.itemId);
            if (cb.checked) {
                if (!this.selectedIds.includes(val)) {
                    this.selectedIds.push(val);
                    this.selectedItemIds.push(itemId);
                    this.selectedTotal += price;
                }
            } else {
                this.selectedIds = this.selectedIds.filter(id => id != val);
                this.selectedItemIds = this.selectedItemIds.filter(id => id != itemId);
                this.selectedTotal -= price;
            }
            this.selectedTotal = Math.max(0, parseFloat(this.selectedTotal.toFixed(2)));
        },

        async fecharSacolinha() {
            if (!confirm('Deseja gerar o pedido para os itens selecionados?')) return;
            
            try {
                const response = await fetch("{{ route('admin.sacolinha.fechar') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ 
                        user_id: {{ $user->id }},
                        valor_frete: this.freteValor,
                        itens: this.selectedIds 
                    })
                });
                const data = await response.json();
                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || 'Erro ao iniciar checkout');
                }
            } catch (e) {
                alert('Erro de comunicação');
            }
        },

        openModalFrete() {
            this.modalFrete = true;
            this.freteResults = null;
            this.cepError = '';
        },

        async calcularFrete() {
            const cep = this.cepInput.replace(/\D/g, '');
            if (cep.length !== 8) {
                this.cepError = 'CEP inválido';
                return;
            }
            this.loadingFrete = true;
            this.freteResults = null;
            this.cepError = '';

            try {
                const response = await fetch('{{ route("api.frete.simular") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ cep: cep, itens: this.selectedItemIds })
                });
                const data = await response.json();
                if (data.success) {
                    this.freteResults = data.options;
                } else {
                    this.cepError = data.message || 'Nenhuma opção encontrada';
                }
            } catch (e) {
                this.cepError = 'Erro ao calcular';
            } finally {
                this.loadingFrete = false;
            }
        }
    }
}
</script>
@endsection

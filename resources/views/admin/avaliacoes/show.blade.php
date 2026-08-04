@extends('layouts.app')

@section('title', 'Recibo de Avaliação #' . str_pad($avaliacao->id, 5, '0', STR_PAD_LEFT))

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Breadcrumbs & Ações --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6 print:hidden">
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
                        <span class="text-gray-500 text-sm font-medium">Recibo Lote #{{ str_pad($avaliacao->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </div>
                </li>
            </ol>
        </nav>
        
        <div class="flex items-center gap-2">
            <button 
                type="button"
                onclick="window.print()"
                class="inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-900 text-white font-semibold text-sm py-2 px-4 rounded-lg transition-colors shadow-sm print:hidden"
            >
                <i class="fas fa-print"></i> Imprimir Recibo
            </button>

            <a 
                href="{{ route('admin.avaliacoes.pdf', $avaliacao) }}"
                target="_blank"
                class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 font-semibold text-sm py-2 px-4 rounded-lg border border-gray-300 transition-colors shadow-sm"
            >
                <i class="fas fa-file-pdf text-red-500"></i> Gerar PDF
            </a>
            
            @if ($avaliacao->status === 'rascunho')
                <a 
                    href="{{ route('admin.avaliacoes.edit', $avaliacao) }}"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm py-2 px-4 rounded-lg transition-colors shadow-sm"
                >
                    <i class="fas fa-edit"></i> Editar Rascunho
                </a>
            @endif

            <button 
                type="button"
                id="btn-print-labels"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm py-2 px-4 rounded-lg transition-colors shadow-sm print:hidden"
            >
                <i class="fas fa-print"></i> Imprimir Etiquetas
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm shadow-sm print:hidden">
            <i class="fas fa-check-circle text-green-500"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Recibo Principal (Estilo Fatura/A4) --}}
    <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-8 print:border-0 print:shadow-none print:p-0">
        
        {{-- Logotipo & Lote # --}}
        <div class="flex justify-between items-start border-b border-gray-200 pb-6 mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-layer-group text-blue-600"></i> Sacolinhas Mania
                </h2>
                <p class="text-xs text-gray-400 mt-1">Brechó & Curadoria de Moda Circular</p>
            </div>
            <div class="text-right">
                <h3 class="text-lg font-black text-gray-900 uppercase">Recibo de Entrada</h3>
                <span class="text-sm font-semibold text-indigo-600">Lote #{{ str_pad($avaliacao->id, 5, '0', STR_PAD_LEFT) }}</span>
                <span class="block text-[10px] text-gray-400 mt-0.5"><i class="far fa-calendar-alt mr-1"></i>{{ $avaliacao->formatted_data_avaliacao }}</span>
            </div>
        </div>

        {{-- Meta do Lote: Fornecedor, Custos, etc --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 rounded-xl p-5 border border-gray-100 mb-6 print:grid-cols-2">
            <div class="space-y-2">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Fornecedor / Fornecedora</h4>
                <div class="text-sm font-bold text-gray-900">{{ $avaliacao->user->name ?? 'Fornecedor Desconhecido' }}</div>
                <div class="text-xs text-gray-500 space-y-0.5">
                    @if ($avaliacao->user && $avaliacao->user->apelido)
                        <div><strong>Apelido:</strong> {{ $avaliacao->user->apelido }}</div>
                    @endif
                    <div><strong>Adesão:</strong> <span class="capitalize font-medium">{{ $avaliacao->tipo_cliente === 'clube' ? 'Clube' : 'Fora do Clube' }}</span></div>
                    @if ($avaliacao->user && $avaliacao->user->email)
                        <div><strong>E-mail:</strong> {{ $avaliacao->user->email }}</div>
                    @endif
                    @if ($avaliacao->user && $avaliacao->user->whatsapp)
                        <div><strong>WhatsApp:</strong> {{ $avaliacao->user->whatsapp }}</div>
                    @endif
                </div>
            </div>

            <div class="space-y-2">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Informações da Operação</h4>
                <div class="text-sm font-bold text-gray-900">
                    {{ $avaliacao->tipo_compra === 'avaliados' ? 'Regime de Avaliação e Desapego' : 'Compra Direta' }}
                </div>
                <div class="text-xs text-gray-500 space-y-0.5">
                    <div><strong>Frete da Remessa:</strong> {{ $avaliacao->formatted_frete }}</div>
                    <div><strong>Quantidade de Peças:</strong> {{ $avaliacao->items->count() }}</div>
                    <div>
                        <strong>Forma de Repasse:</strong> 
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $avaliacao->status === 'finalizada' ? ($avaliacao->pagamento_escolhido === 'credito' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700') : 'bg-yellow-100 text-yellow-700' }}">
                            @if ($avaliacao->status === 'finalizada')
                                {{ $avaliacao->pagamento_escolhido === 'credito' ? 'Créditos na Loja' : 'Dinheiro/PIX' }}
                            @else
                                Pendente (Lote em Rascunho)
                            @endif
                        </span>
                    </div>
                    <div>
                        <strong>Status Lote:</strong>
                        <span class="capitalize font-semibold {{ $avaliacao->status === 'finalizada' ? 'text-green-600' : ($avaliacao->status === 'cancelada' ? 'text-red-600' : 'text-yellow-600') }}">
                            {{ $avaliacao->status_label }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Itens List --}}
        <div class="border border-gray-200 rounded-xl overflow-x-auto mb-6" x-data="quickEditManager()">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-center w-10 print:hidden">
                            <input type="checkbox" id="selectAllEtiquetas" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                        </th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Peça</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Detalhes</th>
                        <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-400 uppercase tracking-wider">Preço Venda</th>
                        <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-400 uppercase tracking-wider">Taxa Curad.</th>
                        <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-400 uppercase tracking-wider">Repasse</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider print:hidden">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-150">
                    @foreach ($avaliacao->items as $item)
                        @php
                            $marcaLabel = $item->marca;
                            if ($marcaLabel === 'sem_marca' || empty($marcaLabel)) $marcaLabel = 'Sem Marca';
                            elseif ($marcaLabel === 'de_marca') $marcaLabel = 'De Marca';
                            elseif (strtolower($marcaLabel) === 'farm') $marcaLabel = 'Farm';
                            
                            $itemData = [
                                'codigo' => $item->item ? $item->item->sku : 'AV'.$item->id,
                                'produto' => strtoupper($marcaLabel) . '   ' . titleCase($item->nome) . ' ' . titleCase($item->cor) . ' [' . strtolower($item->estado) . ']',
                                'tamanho' => trim($item->tamanho),
                                'preco' => number_format($item->preco_venda, 2, ',', '')
                            ];
                            
                            $detalhes = [];
                            if (!empty($marcaLabel)) $detalhes[] = $marcaLabel;
                            if (!empty($item->estado)) $detalhes[] = $item->estado;
                            if (!empty($item->nota_curadoria)) $detalhes[] = 'Cur: ' . $item->nota_curadoria . '/10';
                            if (!empty($item->motivo_curadoria)) $detalhes[] = 'Local: ' . $item->motivo_curadoria;
                            if (!empty($item->cor)) $detalhes[] = $item->cor;
                            if (!empty($item->tamanho)) $detalhes[] = 'Tam: ' . $item->tamanho;
                            $detalhesFormatados = implode(' • ', $detalhes);
                        @endphp
                        <tr class="hover:bg-gray-50 cursor-pointer transition-colors" @click.self="openModal({{ $item->id }}, '{{ addslashes($item->nome) }}', '{{ addslashes($item->cor) }}', '{{ addslashes($item->tamanho) }}', '{{ $itemData['codigo'] }}')">
                            <td class="px-4 py-3 text-center print:hidden" @click.stop>
                                <input type="checkbox" class="item-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" data-item="{{ json_encode($itemData) }}">
                            </td>
                            <td class="px-4 py-3" @click="openModal({{ $item->id }}, '{{ addslashes($item->nome) }}', '{{ addslashes($item->cor) }}', '{{ addslashes($item->tamanho) }}', '{{ $itemData['codigo'] }}')">
                                <p class="text-sm font-semibold text-gray-900 leading-tight">
                                    {{ $item->nome }}
                                </p>
                                <p class="text-[10px] text-gray-400 font-mono mt-0.5">
                                    Cod: <span class="font-bold">{{ $itemData['codigo'] }}</span>
                                </p>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 capitalize" @click="openModal({{ $item->id }}, '{{ addslashes($item->nome) }}', '{{ addslashes($item->cor) }}', '{{ addslashes($item->tamanho) }}', '{{ $itemData['codigo'] }}')">
                                {{ $detalhesFormatados }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-xs font-semibold text-gray-900">
                                R$ {{ number_format($item->preco_venda, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-xs text-red-500 font-medium">
                                {{ $item->taxa_curadoria > 0 ? '- R$ ' . number_format($item->taxa_curadoria, 2, ',', '.') : '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-xs font-black text-indigo-600">
                                @php
                                    $payoutVal = 0.00;
                                    if ($avaliacao->status === 'finalizada') {
                                        $payoutVal = $avaliacao->pagamento_escolhido === 'credito' ? $item->payout_credito : $item->payout_dinheiro;
                                    } else {
                                        $payoutVal = $avaliacao->tipo_cliente === 'clube' ? $item->payout_credito : $item->payout_credito;
                                    }
                                @endphp
                                R$ {{ number_format($payoutVal, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center print:hidden">
                                @if ($item->item_id)
                                    <a href="{{ route('admin.items.edit', $item->item_id) }}" class="text-blue-600 hover:text-blue-800 transition-colors" title="Editar no Estoque">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @else
                                    <span class="text-gray-300" title="Item ainda em rascunho"><i class="fas fa-edit"></i></span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            <!-- Modal Template (Alpine) - Inside the same scope -->
            <template x-teleport="body">
                <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 print:hidden" style="display: none;">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm transition-opacity" @click="closeModal"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"></div>
                         
                    <!-- Modal Panel -->
                    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all relative z-10 overflow-hidden"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                         
                        <div class="bg-blue-100 px-6 py-5 border-b border-blue-200 flex justify-between items-start">
                            <div>
                                <h3 class="text-xl font-bold text-indigo-900 flex items-center gap-2">
                                    <i class="fas fa-edit text-indigo-700"></i> Editar Detalhes
                                </h3>
                                <p class="text-sm text-indigo-800 italic mt-1 font-semibold" x-text="itemForm.sku || 'Carregando...'"></p>
                            </div>
                            <button @click="closeModal" type="button" class="text-indigo-400 hover:text-indigo-600 focus:outline-none transition bg-blue-50 hover:bg-white rounded-full w-8 h-8 flex items-center justify-center">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="space-y-5">
                            <div>
                                <label class="block text-[11px] font-extrabold text-gray-500 uppercase tracking-wider mb-2">Descrição (Nome)</label>
                                <input type="text" x-ref="nomeInput" x-model="itemForm.nome" class="w-full rounded-2xl border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition sm:text-sm py-3 px-4 font-semibold text-gray-800 capitalize">
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[11px] font-extrabold text-gray-500 uppercase tracking-wider mb-2">Cor</label>
                                    <input type="text" x-model="itemForm.cor" class="w-full rounded-2xl border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition sm:text-sm py-3 px-4 font-semibold text-gray-800 capitalize">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-extrabold text-gray-500 uppercase tracking-wider mb-2">Tamanho</label>
                                    <input type="text" x-model="itemForm.tamanho" class="w-full rounded-2xl border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition sm:text-sm uppercase py-3 px-4 font-semibold text-gray-800">
                                </div>
                            </div>
                        </div>
                        
                        <div class="px-6 pb-6 pt-2 flex flex-col gap-3">
                            <div class="flex gap-3">
                                <button @click="closeModal" type="button" class="flex-1 py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-bold uppercase tracking-wide rounded-2xl transition duration-200 focus:outline-none focus:ring-2 focus:ring-gray-300">
                                    Cancelar
                                </button>
                                <button @click="submitEdit(false)" type="button" :disabled="saving" class="flex-1 inline-flex items-center justify-center py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold uppercase tracking-wide rounded-2xl transition duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm disabled:opacity-50">
                                    <i class="fas fa-spinner fa-spin mr-2" x-show="saving"></i>
                                    <i class="fas fa-check mr-2" x-show="!saving"></i>
                                    Confirmar
                                </button>
                            </div>
                            <button @click="submitEdit(true)" type="button" :disabled="saving" class="w-full inline-flex items-center justify-center py-3.5 bg-gray-800 hover:bg-gray-900 text-white text-sm font-bold uppercase tracking-wide rounded-2xl transition duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 shadow-sm disabled:opacity-50 mt-1">
                                <i class="fas fa-spinner fa-spin mr-2" x-show="saving"></i>
                                <i class="fas fa-print mr-2" x-show="!saving"></i>
                                Confirmar e Imprimir
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Seção de Totais e Assinatura --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-end print:grid-cols-2">
            {{-- Observações / Assinatura --}}
            <div class="space-y-4">
                @if ($avaliacao->observacoes)
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-xs text-gray-500">
                        <strong class="text-gray-700 block mb-1">Observações Lote:</strong>
                        {{ $avaliacao->observacoes }}
                    </div>
                @endif

                <div class="hidden print:block border-t border-gray-300 pt-8 mt-12 text-center text-xs text-gray-400">
                    <div class="w-64 border-b border-gray-300 mx-auto mb-2"></div>
                    Assinatura do Fornecedor / Responsável
                </div>
            </div>

            {{-- Totais e Repasses --}}
            @php
                $sumPayoutCredito = $avaliacao->items->sum('payout_credito');
                $sumPayoutDinheiro = $avaliacao->items->sum('payout_dinheiro');
            @endphp
            <div class="space-y-4">
                {{-- Total Repasse Fornecedor (Padrão de Criação/Edição) --}}
                <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm space-y-3">
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Repasse Fornecedor</span>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Repasse Crédito:</span>
                        <span class="font-black text-lg text-blue-600">R$ {{ number_format($sumPayoutCredito, 2, ',', '.') }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center border-t border-gray-150 pt-2">
                        <span class="text-xs text-gray-500">Repasse Dinheiro:</span>
                        <span class="font-black text-lg text-green-600">R$ {{ number_format($sumPayoutDinheiro, 2, ',', '.') }}</span>
                    </div>
                    
                    <div class="text-[10px] text-gray-400 text-right pt-2 border-t border-gray-100">
                        @if ($avaliacao->status === 'finalizada')
                            Pago via <strong class="capitalize">{{ $avaliacao->pagamento_escolhido === 'credito' ? 'Créditos em Carteira' : 'Dinheiro/PIX' }}</strong>
                        @else
                            Aguardando finalização do lote (Rascunho)
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
@media print {
    body {
        background-color: white !important;
    }
    .print\:hidden {
        display: none !important;
    }
    .print\:border-0 {
        border: 0 !important;
    }
    .print\:shadow-none {
        box-shadow: none !important;
    }
    .print\:p-0 {
        padding: 0 !important;
    }
    .print\:grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
    .print\:block {
        display: block !important;
    }
}
</style>

@php
    function titleCase($str) {
        if (!$str) return '';
        $words = explode(' ', $str);
        foreach ($words as &$word) {
            $word = ucfirst(strtolower($word));
        }
        return implode(' ', $words);
    }
@endphp

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllBtn = document.getElementById('selectAllEtiquetas');
        const itemCheckboxes = document.querySelectorAll('.item-checkbox');
        const printBtn = document.getElementById('btn-print-labels');

        if (selectAllBtn) {
            selectAllBtn.addEventListener('change', function() {
                itemCheckboxes.forEach(cb => cb.checked = this.checked);
            });
        }

        if (printBtn) {
            printBtn.addEventListener('click', function() {
                const selectedItems = Array.from(itemCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => JSON.parse(cb.dataset.item));

                if (selectedItems.length === 0) {
                    alert('Selecione pelo menos um item para imprimir a etiqueta.');
                    return;
                }

                printLabelsMultiplas(selectedItems);
            });
        }

        window.imprimirEtiquetas = function(etiquetas) {
            const htmlContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                    @page {
                        size: 60mm 30mm;
                        margin: 0;
                    }
                    body {
                        margin: 0.5mm;
                        padding: 0;
                        display: flex;
                        flex-wrap: wrap;
                        flex-direction: row;
                        font-family: Arial, sans-serif;
                        line-height: 1.05;
                    }
                    .label {
                        width: 60mm;
                        height: 29mm;
                        display: flex;
                        flex-direction: row;
                        justify-content: space-between;
                        align-items: flex-start;
                        font-size: 12px;
                        padding: 1mm;
                        box-sizing: border-box;
                        overflow: hidden;
                    }
                    .left {
                        flex: 1.3;
                        display: flex;
                        flex-direction: column;
                        justify-content: flex-start;
                        align-items: flex-start;
                        height: 100%;
                        gap: 0.1mm;
                        overflow: hidden;
                    }
                    .produto {
                        font-weight: normal;
                        font-size: 9px;
                        text-align: left;
                        word-wrap: break-word;
                        white-space: normal;
                        margin: 0;
                        line-height: 1.1;
                    }
                    .tamanho {
                        font-weight: bold;
                        font-size: 22px;
                        text-align: center;
                        margin-top: 0 !important;
                        margin-bottom: 0;
                        line-height: 1;
                    }
                    .preco {
                        font-weight: bold;
                        font-size: 11px;
                        margin-top: 0 !important;
                        white-space: nowrap;
                    }
                    .valor {
                        font-weight: normal;
                        font-size: 11px;
                        margin-top: 0 !important;
                        margin-left: 0.5mm;
                    }
                    .right {
                        flex: 0 0 22.5mm;
                        display: flex;
                        flex-direction: column;
                        align-items: flex-end;
                        height: 100%;
                        gap: 0.1mm;
                    }
                    .barcode {
                        width: 22.5mm;
                        height: 22.5mm;
                    }
                    .codigoBarra {
                        font-weight: normal;
                        font-size: 10px;
                        text-align: center;
                        margin-top: 0 !important;
                    }
                    @media print {
                        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    }
                    </style>
                </head>
                <body>
                    ${etiquetas.map(etiqueta => `
                        <div class="label">
                            <div class="left">
                                <div>
                                    <span class="produto">- ${etiqueta.produto.replace(/'/g, "\\'")}</span>
                                </div>
                                <div class="tamanho">${etiqueta.tamanho}</div>
                                <div>
                                    <span class="preco">Preço:  </span>
                                    <span class="valor">${etiqueta.preco}</span>
                                </div>
                            </div>
                            <div class="right">
                            <img class="barcode" 
                                 src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(etiqueta.codigo)}"
                                 alt="QR Code ${etiqueta.codigo}" 
                                 onload="this.style.opacity=1;" 
                                 style="opacity:0.5;" />
                                <div class="codigoBarra">${etiqueta.codigo}</div>
                            </div>
                        </div>
                    `).join('')}
                    <script>
                        window.onload = function() {
                            setTimeout(function() {
                                window.print();
                                window.close();
                            }, 500);
                        };
                    <\/script>
                </body>
                </html>
            `;

            const printWindow = window.open('', 'EtiquetasSacolinha', 'width=1000,height=700,scrollbars=yes,resizable=yes');
            printWindow.document.write(htmlContent);
            printWindow.document.close();
        }

        // Keep local reference for buttons
        window.printLabelsMultiplas = window.imprimirEtiquetas;
    });
</script>

<!-- AlpineJS Quick Edit Manager -->
<script>
    function quickEditManager() {
        return {
            showModal: false,
            saving: false,
            itemForm: {
                id: null,
                sku: '',
                nome: '',
                cor: '',
                tamanho: ''
            },
            
            openModal(id, nome, cor, tamanho, sku) {
                this.itemForm.id = id;
                this.itemForm.sku = sku;
                this.itemForm.nome = nome;
                this.itemForm.cor = cor;
                this.itemForm.tamanho = tamanho;
                this.showModal = true;
                setTimeout(() => {
                    this.$refs.nomeInput.focus();
                }, 100);
            },
            
            closeModal() {
                this.showModal = false;
            },
            
            async submitEdit(imprimir = false) {
                this.saving = true;
                try {
                    const response = await fetch(`/admin/avaliacoes/item/${this.itemForm.id}/quick-update`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.itemForm)
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        if (imprimir && data.etiqueta) {
                            if (typeof window.imprimirEtiquetas === 'function') {
                                window.imprimirEtiquetas([data.etiqueta]);
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                window.location.reload();
                            }
                        } else {
                            window.location.reload();
                        }
                    } else {
                        alert(data.message || 'Erro ao atualizar o item');
                        this.saving = false;
                    }
                } catch (e) {
                    alert('Erro de conexão ao salvar.');
                    this.saving = false;
                }
            }
        }
    }
</script>

@endsection

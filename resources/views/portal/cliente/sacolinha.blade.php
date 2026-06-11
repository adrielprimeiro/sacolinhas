@extends('layouts.portal-cliente')

@section('title', 'Sacolinha - Portal do Cliente')

@section('content')
<div class="space-y-6">

    <!-- Cabeçalho -->
    <div class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Minha Sacolinha</h1>
            <p class="text-gray-600 text-sm">
                <span class="font-bold">{{ $itens->count() }}</span> {{ $itens->count() == 1 ? 'Item reservado' : 'Itens reservados' }} pra você
            </p>
            <div class="mt-2 flex items-baseline gap-2">
                <p class="text-xs text-gray-500 uppercase font-semibold">Total:</p>
                <p class="text-xl font-bold text-gray-900">R$ {{ number_format($total ?? 0, 2, ',', '.') }}</p>
                
                <!-- Badge Dinâmico de Selecionados -->
                <div id="totalSelecionado" class="hidden flex items-center gap-2 bg-blue-50 px-3 py-1 rounded-full border border-blue-100 ml-2">
                    <span class="text-[10px] text-blue-600 font-bold uppercase">Selecionado:</span>
                    <span class="text-sm font-bold text-blue-700">R$ 0,00</span>
                </div>
            </div>
        </div>
        <div class="flex flex-col gap-2 min-w-[180px]">
            <button id="btnFecharSacolinha" 
                    disabled
                    class="w-full bg-gray-200 text-gray-400 text-xs font-bold py-2 px-4 rounded-lg cursor-not-allowed transition duration-200 uppercase tracking-wider flex items-center justify-center gap-2">
                <i class="fas fa-check-circle"></i> Fechar Sacolinha
            </button>
            <button id="btnSimularFrete" 
                    disabled
                    class="w-full border-2 border-gray-200 text-gray-400 text-xs font-bold py-1.5 px-4 rounded-lg cursor-not-allowed transition duration-200 uppercase tracking-wider flex items-center justify-center gap-2">
                <i class="fas fa-truck"></i> Simular Frete
            </button>
        </div>
    </div>

    <!-- Card de Limite (apenas se tiver itens em análise) -->
    @if($temEmAnalise)
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Lado esquerdo: Mensagem e opções -->
            <div class="flex-1">
                <div class="flex items-start gap-3 mb-4">
                    <div class="text-yellow-600">
                        <i class="fas fa-exclamation-triangle text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-yellow-800">
                            👉 Os itens em amarelo foram escolhidos na Live Mania, mas ultrapassaram o seu limite.
                        </h3>
                        <p class="text-sm text-yellow-700 mt-2">
                            O valor dos itens é de: <span class="font-bold">R$ {{ number_format($totalItensEmAnalise ?? 0, 2, ',', '.') }}</span>
                        </p>
                    </div>
                </div>

                <!-- Opções -->
                <div class="space-y-3">
                    <p class="text-sm font-medium text-yellow-800">Opções:</p>
                    
                    <div class="space-y-2">
                        <div class="flex items-start gap-2">
                            <span class="text-yellow-700">•</span>
                            <div>
                                <p class="text-sm text-yellow-700 font-medium">Pagar à vista:</p>
                                <p class="text-sm text-yellow-600">com Pix: <span class="font-mono font-bold">mania@maniademelissa.com</span></p>
                                <p class="text-xs text-yellow-600">ou solicite um link para pagamento no cartão.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-2">
                            <span class="text-yellow-700">•</span>
                            <p class="text-sm text-yellow-700">Retirar algum item da sacolinha</p>
                        </div>
                    </div>
                    
                    <p class="text-xs text-yellow-600 mt-3">
                        ⏰ Sem resposta em 24h os itens serão cancelados
                    </p>
                </div>
            </div>

            <!-- Lado direito: Excedente, Selecionados e Botão -->
            <div class="md:w-1/3 border-l border-yellow-200 md:border-l md:pl-6">
                <div class="space-y-4">
                    <!-- Excedente -->
                    <div>
                        <p class="text-xs text-yellow-700 font-medium">Excedente:</p>
                        <p id="excedenteValor" class="text-lg font-bold text-red-600">
                            R$ {{ number_format($excedente ?? 0, 2, ',', '.') }}
                        </p>
                        <p class="text-xs text-yellow-600">
                            Valor total dos itens em análise
                        </p>
                    </div>

                    <!-- Selecionados -->
                    <div>
                        <p class="text-xs text-yellow-700 font-medium">Selecionados:</p>
                        <p id="selecionadosValor" class="text-lg font-bold text-blue-600">
                            R$ 0,00
                        </p>
                        <p class="text-xs text-yellow-600">
                            Valor dos itens selecionados
                        </p>
                    </div>

                    <!-- Botão Excluir Selecionados -->
                    <div>
                        <button id="btnExcluirSelecionados" 
                                disabled
                                class="w-full bg-gray-300 text-gray-500 text-sm font-medium py-2 px-4 rounded-md cursor-not-allowed transition duration-200">
                            Excluir Selecionados
                        </button>
                        <p id="mensagemBotao" class="text-xs text-yellow-600 mt-1">
                            Selecione itens no valor de R$ {{ number_format($excedente ?? 0, 2, ',', '.') }} para habilitar
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Feedback -->
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Lista -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between flex-1 gap-4">
                <div>
                    <h2 class="text-sm font-semibold text-gray-800">Itens</h2>
                    <p class="text-[11px] md:text-xs text-gray-500 font-medium italic">
                        Selecione os Itens que você quer incluir no seu PEDIDO ou simular FRETE
                    </p>
                </div>
                <!-- Checkbox Selecionar Todos -->
                <label class="flex items-center gap-2 cursor-pointer bg-gray-50 hover:bg-gray-100 transition px-3 py-1.5 rounded-md border border-gray-200 select-none self-start sm:self-auto">
                    <input type="checkbox" id="selectAllGlobal" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                    <span class="text-xs font-semibold text-gray-700">Selecionar Todos</span>
                </label>
            </div>
            <div class="flex items-center gap-4">
                <span id="totalSelecionado" class="text-sm bg-blue-50 text-blue-700 px-3 py-1 rounded-full hidden">
                    <span class="font-semibold">Selecionados:</span> 
                    <span class="font-bold">R$ 0,00</span>
                </span>
            </div>
        </div>

        @if(($itens->count() ?? 0) === 0)
            <div class="p-6 text-center">
                <p class="text-sm font-semibold text-gray-800">Sua sacolinha está vazia</p>
                <p class="text-sm text-gray-600 mt-1">Quando você adicionar itens, eles aparecerão aqui.</p>
            </div>
        @else

            <!-- Tabela (desktop) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Produto</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Detalhes</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Adicionado em</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Valor</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider w-12">
                                <input type="checkbox" id="selectAllDesktop" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4" title="Selecionar Todos">
                            </th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($itens as $item)
                            @php
                                $img = $item->image ?? null;
                                $imgUrl = $img ? asset('storage/' . ltrim($img, '/')) : null;
                                
                                // Formatar detalhes: marca • estado • cor • Tam: tamanho
                                $detalhes = [];
                                if (!empty($item->marca)) $detalhes[] = $item->marca;
                                if (!empty($item->estado)) $detalhes[] = $item->estado;
                                if (!empty($item->cor)) $detalhes[] = $item->cor;
                                if (!empty($item->tamanho)) $detalhes[] = 'Tam: ' . $item->tamanho;
                                
                                $detalhesFormatados = implode(' • ', $detalhes);
                                if (empty($detalhesFormatados)) $detalhesFormatados = '-';
                                
                                // Verificar se está em análise
                                $emAnalise = strtolower($item->sacolinha_status ?? '') === 'em analise';
                                $rowClass = $emAnalise ? 'bg-yellow-100 hover:bg-yellow-200' : 'hover:bg-gray-50';
                            @endphp

                            <tr class="{{ $rowClass }}">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 bg-gray-100 rounded-md overflow-hidden flex items-center justify-center flex-shrink-0">
                                            @if($imgUrl)
                                                <img src="{{ $imgUrl }}" alt="{{ $item->nome_do_produto ?? 'Produto' }}" class="w-full h-full object-cover">
                                            @else
                                                <i class="fas fa-image text-gray-400"></i>
                                            @endif
                                        </div>

                                        <div>
                                            <p class="text-sm font-semibold text-gray-800">
                                                {{ $item->nome_do_produto ?? 'Produto sem nome' }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                Código: <span class="font-medium text-gray-700">{{ $item->codigo ?? '-' }}</span>
                                            </p>
                                            @if($emAnalise)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mt-1">
                                                    Em Análise
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $detalhesFormatados }}
                                </td>

                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ !empty($item->add_at) ? \Carbon\Carbon::parse($item->add_at)->format('d/m/Y H:i') : 'N/A' }}
                                </td>

                                <td class="px-4 py-3 text-sm font-semibold text-gray-800">
                                    R$ {{ number_format($item->price ?? 0, 2, ',', '.') }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <input type="checkbox" 
                                           name="selecionar[]" 
                                           value="{{ $item->sacolinha_id }}" 
                                           data-item-id="{{ $item->item_id }}"
                                           data-price="{{ $item->price ?? 0 }}"
                                           data-em-analise="{{ $emAnalise ? 'true' : 'false' }}"
                                           class="item-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-5 w-5">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Cards (mobile) -->
            <div class="md:hidden divide-y divide-gray-200">
                @foreach($itens as $item)
                    @php
                        $img = $item->image ?? null;
                        $imgUrl = $img ? asset('storage/' . ltrim($img, '/')) : null;
                        
                        // Formatar detalhes: marca • estado • cor • Tam: tamanho
                        $detalhes = [];
                        if (!empty($item->marca)) $detalhes[] = $item->marca;
                        if (!empty($item->estado)) $detalhes[] = $item->estado;
                        if (!empty($item->cor)) $detalhes[] = $item->cor;
                        if (!empty($item->tamanho)) $detalhes[] = 'Tam: ' . $item->tamanho;
                        
                        $detalhesFormatados = implode(' • ', $detalhes);
                        if (empty($detalhesFormatados)) $detalhesFormatados = '-';
                        
                        // Verificar se está em análise
                        $emAnalise = strtolower($item->sacolinha_status ?? '') === 'em analize';
                        $rowClass = $emAnalise ? 'bg-yellow-100' : '';
                    @endphp

                    <div class="p-4 {{ $rowClass }}">
                        <div class="flex gap-3">
                            <div class="w-16 h-16 bg-gray-100 rounded-md overflow-hidden flex items-center justify-center flex-shrink-0">
                                @if($imgUrl)
                                    <img src="{{ $imgUrl }}" alt="{{ $item->nome_do_produto ?? 'Produto' }}" class="w-full h-full object-cover">
                                @else
                                    <i class="fas fa-image text-gray-400"></i>
                                @endif
                            </div>

                            <div class="flex-1">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">
                                            {{ $item->nome_do_produto ?? 'Produto sem nome' }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            Código: <span class="font-medium text-gray-700">{{ $item->codigo ?? '-' }}</span>
                                        </p>
                                        @if($emAnalise)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mt-1">
                                                Em Análise
                                            </span>
                                        @endif
                                    </div>
                                    <input type="checkbox" 
                                           name="selecionar[]" 
                                           value="{{ $item->sacolinha_id }}" 
                                           data-item-id="{{ $item->item_id }}"
                                           data-price="{{ $item->price ?? 0 }}"
                                           data-em-analise="{{ $emAnalise ? 'true' : 'false' }}"
                                           class="item-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-5 w-5">
                                </div>

                                <p class="text-sm text-gray-700 mt-2">{{ $detalhesFormatados }}</p>

                                <div class="mt-2 flex items-center justify-between">
                                    <p class="text-sm font-bold text-gray-800">
                                        R$ {{ number_format($item->price ?? 0, 2, ',', '.') }}
                                    </p>
                                </div>

                                <p class="text-xs text-gray-500 mt-2">
                                    Adicionado em: {{ !empty($item->add_at) ? \Carbon\Carbon::parse($item->add_at)->format('d/m/Y H:i') : 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="p-4 border-t border-gray-200 flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Total: <span class="font-semibold text-gray-800">R$ {{ number_format($total ?? 0, 2, ',', '.') }}</span>
                </p>
                <a href="{{ route('portal.dashboard') }}" class="text-sm text-blue-600 hover:text-blue-700">
                    Voltar
                </a>
            </div>

        @endif
    </div>

    @php
        $userCep = auth()->user()->cep ?? '';
        if (strlen($userCep) === 8) {
            $userCep = substr($userCep, 0, 5) . '-' . substr($userCep, 5);
        }
    @endphp

    <!-- Modal Simular Frete -->
    <div id="modalFrete" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Simular Frete</h3>
                <button id="btnCloseModalFrete" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mb-4">
                <label for="cepInput" class="block text-sm font-medium text-gray-700 mb-1">Informe seu CEP</label>
                <div class="flex gap-2">
                    <input type="text" id="cepInput" class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="00000-000" maxlength="9" value="{{ $userCep }}">
                    <button id="btnCalcularFreteAPI" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-200">
                        Calcular
                    </button>
                </div>
                <p id="cepError" class="text-red-500 text-xs mt-1 hidden"></p>
            </div>
            
            <!-- Resultados do Frete -->
            <div id="freteResults" class="space-y-3 mt-4 hidden max-h-60 overflow-y-auto">
                <!-- Opções de frete serão injetadas aqui -->
            </div>
            
            <!-- Loading -->
            <div id="freteLoading" class="hidden text-center py-4">
                <i class="fas fa-spinner fa-spin text-blue-600 text-2xl"></i>
                <p class="text-sm text-gray-500 mt-2">Calculando as melhores opções...</p>
            </div>
        </div>
    </div>

</div>

<!-- JavaScript para calcular total dos selecionados e controlar botão -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const totalSpan = document.querySelector('#totalSelecionado span:last-child');
    const totalContainer = document.getElementById('totalSelecionado');
    const selecionadosValor = document.getElementById('selecionadosValor');
    const btnExcluirSelecionados = document.getElementById('btnExcluirSelecionados');
    const mensagemBotao = document.getElementById('mensagemBotao');
    const btnFecharSacolinha = document.getElementById('btnFecharSacolinha');
    const btnSimularFrete = document.getElementById('btnSimularFrete');
    
    const selectAllGlobal = document.getElementById('selectAllGlobal');
    const selectAllDesktop = document.getElementById('selectAllDesktop');
    
    // Valor do excedente (vem do controller)
    const excedente = parseFloat({{ $excedente ?? 0 }});
    
    function updateTotais() {
        let totalSelecionados = 0;
        let selectedCount = 0;
        
        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                totalSelecionados += parseFloat(checkbox.dataset.price) || 0;
                selectedCount++;
            }
        });
        
        // Atualizar badge no cabeçalho
        if (selectedCount > 0) {
            if (totalContainer) totalContainer.classList.remove('hidden');
            if (totalSpan) {
                totalSpan.textContent = 'R$ ' + totalSelecionados.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            // Habilitar botões de ação
            if (btnFecharSacolinha) {
                btnFecharSacolinha.disabled = false;
                btnFecharSacolinha.classList.remove('bg-gray-200', 'text-gray-400', 'cursor-not-allowed');
                btnFecharSacolinha.classList.add('bg-green-600', 'hover:bg-green-700', 'text-white', 'cursor-pointer');
            }
            if (btnSimularFrete) {
                btnSimularFrete.disabled = false;
                btnSimularFrete.classList.remove('border-gray-200', 'text-gray-400', 'cursor-not-allowed');
                btnSimularFrete.classList.add('border-blue-600', 'text-blue-600', 'hover:bg-blue-50', 'cursor-pointer');
            }
        } else {
            if (totalContainer) totalContainer.classList.add('hidden');
            
            // Desabilitar botões de ação
            if (btnFecharSacolinha) {
                btnFecharSacolinha.disabled = true;
                btnFecharSacolinha.classList.add('bg-gray-200', 'text-gray-400', 'cursor-not-allowed');
                btnFecharSacolinha.classList.remove('bg-green-600', 'hover:bg-green-700', 'text-white', 'cursor-pointer');
            }
            if (btnSimularFrete) {
                btnSimularFrete.disabled = true;
                btnSimularFrete.classList.add('border-gray-200', 'text-gray-400', 'cursor-not-allowed');
                btnSimularFrete.classList.remove('border-blue-600', 'text-blue-600', 'hover:bg-blue-50', 'cursor-pointer');
            }
        }
        
        // Atualizar valor dos selecionados no card
        if (selecionadosValor) {
            selecionadosValor.textContent = 'R$ ' + totalSelecionados.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
        // Controlar botão Excluir Selecionados
        if (btnExcluirSelecionados) {
            if (totalSelecionados >= excedente) {
                btnExcluirSelecionados.disabled = false;
                btnExcluirSelecionados.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
                btnExcluirSelecionados.classList.add('bg-red-500', 'hover:bg-red-600', 'text-white', 'cursor-pointer');
                if (mensagemBotao) mensagemBotao.textContent = 'Clique para excluir os itens selecionados';
            } else {
                btnExcluirSelecionados.disabled = true;
                btnExcluirSelecionados.classList.remove('bg-red-500', 'hover:bg-red-600', 'text-white', 'cursor-pointer');
                btnExcluirSelecionados.classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
                if (mensagemBotao) {
                    mensagemBotao.textContent = 'Selecione itens no valor de R$ ' + excedente.toLocaleString('pt-BR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }) + ' para habilitar';
                }
            }
        }
    }
    
    function toggleAll(checked) {
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
        
        if (selectAllGlobal) selectAllGlobal.checked = checked;
        if (selectAllDesktop) selectAllDesktop.checked = checked;
        
        updateTotais();
    }

    if (selectAllGlobal) {
        selectAllGlobal.addEventListener('change', function() {
            toggleAll(this.checked);
        });
    }

    if (selectAllDesktop) {
        selectAllDesktop.addEventListener('change', function() {
            toggleAll(this.checked);
        });
    }

    function updateSelectAllState() {
        if (checkboxes.length === 0) return;
        const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
        const noneChecked = Array.from(checkboxes).every(checkbox => !checkbox.checked);
        
        if (selectAllGlobal) {
            selectAllGlobal.checked = allChecked;
            selectAllGlobal.indeterminate = !allChecked && !noneChecked;
        }
        if (selectAllDesktop) {
            selectAllDesktop.checked = allChecked;
            selectAllDesktop.indeterminate = !allChecked && !noneChecked;
        }
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateTotais();
            updateSelectAllState();
        });
    });
    
    // Inicializar
    updateTotais();
    updateSelectAllState();

    // Lógica Fechar Sacolinha
    if (btnFecharSacolinha) {
        btnFecharSacolinha.addEventListener('click', async function() {
            // Pegar IDs selecionados (sacolinha_id)
            const selectedIds = [];
            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    selectedIds.push(checkbox.value);
                }
            });

            if (selectedIds.length === 0) return;

            btnFecharSacolinha.disabled = true;
            btnFecharSacolinha.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PROCESSANDO...';

            try {
                const response = await fetch("{{ route('portal.checkout.iniciar') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        itens: selectedIds
                    })
                });

                let data;
                let textResponse = await response.text();
                try {
                    // Vacina: Se o servidor mandou múltiplos JSONs grudados ou lixo antes,
                    // procuramos onde começa a resposta verdadeira de sucesso.
                    const successIndex = textResponse.lastIndexOf('{"success":');
                    if (successIndex > 0) {
                        console.warn("Lixo detectado antes do JSON real. Limpando...", textResponse.substring(0, successIndex));
                        textResponse = textResponse.substring(successIndex);
                    }
                    data = JSON.parse(textResponse);
                } catch (e) {
                    console.error("Servidor não retornou JSON válido. Retornou:", textResponse);
                    alert("O servidor respondeu com um erro fatal ou página HTML. Verifique o console! Resposta: " + textResponse.substring(0, 150));
                    btnFecharSacolinha.disabled = false;
                    btnFecharSacolinha.innerHTML = '<i class="fas fa-check-circle"></i> Fechar Sacolinha';
                    return;
                }

                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    alert('Erro ao iniciar checkout: ' + (data.message || 'Erro desconhecido'));
                    btnFecharSacolinha.disabled = false;
                    btnFecharSacolinha.innerHTML = '<i class="fas fa-check-circle"></i> Fechar Sacolinha';
                }
            } catch (error) {
                console.error(error);
                alert('Erro de comunicação com o servidor.');
                btnFecharSacolinha.disabled = false;
                btnFecharSacolinha.innerHTML = '<i class="fas fa-check-circle"></i> Fechar Sacolinha';
            }
        });
    }

    // Lógica do Modal de Frete
    const modalFrete = document.getElementById('modalFrete');
    const btnCloseModalFrete = document.getElementById('btnCloseModalFrete');
    const btnCalcularFreteAPI = document.getElementById('btnCalcularFreteAPI');
    const cepInput = document.getElementById('cepInput');
    const freteResults = document.getElementById('freteResults');
    const freteLoading = document.getElementById('freteLoading');
    const cepError = document.getElementById('cepError');

    // Máscara CEP
    cepInput.addEventListener('input', function(e) {
        let x = e.target.value.replace(/\D/g, '').match(/(\d{0,5})(\d{0,3})/);
        e.target.value = !x[2] ? x[1] : x[1] + '-' + x[2];
    });

    if (btnSimularFrete) {
        btnSimularFrete.addEventListener('click', function() {
            modalFrete.classList.remove('hidden');
            freteResults.classList.add('hidden');
            freteResults.innerHTML = '';
            cepInput.value = "{{ $userCep }}";
            cepError.classList.add('hidden');
        });
    }

    btnCloseModalFrete.addEventListener('click', function() {
        modalFrete.classList.add('hidden');
    });

    btnCalcularFreteAPI.addEventListener('click', async function() {
        const cep = cepInput.value.replace(/\D/g, '');
        if (cep.length !== 8) {
            cepError.textContent = 'Digite um CEP válido com 8 dígitos.';
            cepError.classList.remove('hidden');
            return;
        }
        cepError.classList.add('hidden');

        // Pegar IDs dos itens selecionados
        const selectedItems = [];
        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                selectedItems.push(checkbox.dataset.itemId);
            }
        });

        if (selectedItems.length === 0) {
            cepError.textContent = 'Nenhum item selecionado.';
            cepError.classList.remove('hidden');
            return;
        }

        // Mostrar loading
        freteLoading.classList.remove('hidden');
        freteResults.classList.add('hidden');
        freteResults.innerHTML = '';

        try {
            const response = await fetch('{{ route("api.frete.simular") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    cep: cep,
                    itens: selectedItems
                })
            });

            const data = await response.json();

            freteLoading.classList.add('hidden');
            freteResults.classList.remove('hidden');

            if (data.success && data.options && data.options.length > 0) {
                let html = '<p class="text-xs text-gray-500 font-semibold uppercase mb-2">Opções de Envio</p>';
                
                data.options.forEach(opt => {
                    html += `
                    <div class="border border-gray-200 rounded-md p-3 flex justify-between items-center hover:bg-gray-50 transition">
                        <div class="flex items-center gap-3">
                            <img src="${opt.company.picture}" alt="${opt.company.name}" class="h-8 w-8 object-contain">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">${opt.name}</p>
                                <p class="text-xs text-gray-500">Entrega em até ${opt.delivery_time} dias úteis</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-gray-900">R$ ${parseFloat(opt.price).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                        </div>
                    </div>`;
                });
                
                // Mostrar dimensões calculadas (debug/transparência)
                html += `
                <div class="mt-4 p-2 bg-gray-50 rounded text-xs text-gray-500 text-center">
                    Pacote calculado: ${data.package.weight}kg | ${data.package.length}x${data.package.width}x${data.package.height}cm
                </div>`;
                
                freteResults.innerHTML = html;
            } else {
                freteResults.innerHTML = `<div class="text-center p-4 text-red-500 text-sm">
                    ${data.message || 'Nenhuma opção de frete disponível para este CEP.'}
                </div>`;
            }

        } catch (error) {
            console.error('Erro ao calcular frete:', error);
            freteLoading.classList.add('hidden');
            freteResults.classList.remove('hidden');
            freteResults.innerHTML = `<div class="text-center p-4 text-red-500 text-sm">Erro ao conectar com o servidor. Tente novamente.</div>`;
        }
    });

});
</script>
@endsection
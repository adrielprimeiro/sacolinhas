@extends('layouts.portal-cliente')

@section('title', 'Sacolinha - Portal do Cliente')

@section('content')
<div class="space-y-6">

    <!-- Cabeçalho -->
    <div class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Minha Sacolinha</h1>
            <p class="text-gray-600 text-sm">Itens reservados para você</p>
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-500">Total</p>
            <p class="text-lg font-semibold text-gray-800">R$ {{ number_format($total ?? 0, 2, ',', '.') }}</p>
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
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-800">Itens</h2>
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
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider"></th>
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
            totalContainer.classList.remove('hidden');
            totalSpan.textContent = 'R$ ' + totalSelecionados.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        } else {
            totalContainer.classList.add('hidden');
        }
        
        // Atualizar valor dos selecionados no card
        selecionadosValor.textContent = 'R$ ' + totalSelecionados.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        
        // Controlar botão Excluir Selecionados
        if (totalSelecionados >= excedente) {
            btnExcluirSelecionados.disabled = false;
            btnExcluirSelecionados.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            btnExcluirSelecionados.classList.add('bg-red-500', 'hover:bg-red-600', 'text-white', 'cursor-pointer');
            mensagemBotao.textContent = 'Clique para excluir os itens selecionados';
        } else {
            btnExcluirSelecionados.disabled = true;
            btnExcluirSelecionados.classList.remove('bg-red-500', 'hover:bg-red-600', 'text-white', 'cursor-pointer');
            btnExcluirSelecionados.classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            mensagemBotao.textContent = 'Selecione itens no valor de R$ ' + excedente.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' para habilitar';
        }
    }
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateTotais);
    });
    
    // Inicializar
    updateTotais();
});
</script>
@endsection
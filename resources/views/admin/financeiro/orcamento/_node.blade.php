{{-- resources/views/admin/financeiro/orcamento/_node.blade.php --}}
@php 
    $temFilhos = !empty($node['children']) && $node['children']->isNotEmpty(); 
@endphp

<div 
    x-data="{ 
        open: true,
        saving: false,
        saved: false,
        valor: '{{ $node['previsto'] }}',
        valorOriginal: '{{ $node['previsto'] }}',
        async salvar() {
            if (this.valor === this.valorOriginal) return;
            this.saving = true;
            try {
                const res = await fetch('{{ route('financeiro.orcamento.upsert') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        classificacao_financeira_id: {{ $node['id'] }},
                        periodo: '{{ $periodo->format('Y-m-d') }}',
                        valor_previsto: parseFloat(this.valor) || 0
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.valorOriginal = this.valor;
                    this.saved = true;
                    setTimeout(() => {
                        this.saved = false;
                        window.location.reload();
                    }, 500);
                }
            } catch(e) { 
                alert('Erro ao salvar orçamento.'); 
            }
            this.saving = false;
        },
        cancelar() {
            this.valor = this.valorOriginal;
        }
    }"
    class="categoria-node border-b border-gray-50 last:border-0"
>
    {{-- Linha principal --}}
    <div
        class="grid grid-cols-12 gap-4 items-center pl-1 pr-3 py-3 hover:bg-gray-50/60 transition-all group"
    >
        {{-- Coluna 1: Categoria --}}
        <div 
            :class="isEditing ? 'col-span-8' : 'col-span-6'"
            class="flex items-center gap-2"
            style="padding-left: {{ ($nivel * 0.75) }}rem"
        >
            {{-- Botão expand/collapse se tiver filhos --}}
            <div class="w-5 h-5 flex-shrink-0 flex items-center justify-center">
                @if ($temFilhos)
                    <button
                        type="button"
                        @click="open = !open"
                        class="p-1 rounded hover:bg-gray-200 text-gray-500 transition-colors"
                    >
                        <i class="fas text-[10px]" :class="open ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                    </button>
                @else
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                @endif
            </div>

            {{-- Nome e Código Contábil --}}
            <div class="truncate">
                <span class="text-sm font-bold text-gray-800 {{ $nivel === 0 ? 'text-gray-900' : 'text-gray-700' }}">
                    {{ $node['nome'] }}
                </span>
                @if ($node['codigo_contabil'])
                    <span class="text-[10px] text-gray-400 font-mono font-bold block">{{ $node['codigo_contabil'] }}</span>
                @endif
            </div>
        </div>

        {{-- Coluna 2: Progresso ou Input de Edição --}}
        <div 
            :class="isEditing ? 'col-span-4' : 'col-span-6'"
            class="flex items-center justify-end"
        >
            {{-- Modo Visualização (Diferença e Progresso) --}}
            <div x-show="!isEditing" class="w-full" x-transition:enter>
                <div class="flex flex-col w-full">
                    {{-- Realizado e Previsto --}}
                    <div class="flex justify-between items-center text-xs font-bold text-gray-600 mb-1">
                        <span>Real: R$ {{ number_format($node['realizado'], 2, ',', '.') }}</span>
                        <span>Prev: R$ {{ number_format($node['previsto'], 2, ',', '.') }}</span>
                    </div>
                    
                    {{-- Barra de Progresso --}}
                    @php
                        $pct = min($node['percentual'], 100);
                        $pctDisplay = $node['percentual'];
                        $status = $node['status_barra']; // success, danger, warning, info
                        $barColors = [
                            'success' => 'bg-green-500',
                            'danger'  => 'bg-red-500',
                            'warning' => 'bg-yellow-500',
                            'info'    => 'bg-blue-500',
                        ];
                        $barBg = $barColors[$status] ?? 'bg-gray-300';
                        $overflow = $pctDisplay > 100;
                        
                        $dif = $node['diferenca'];
                        $tipo = $node['tipo_natureza'];
                        $difClass = $tipo === 'despesa'
                            ? ($dif >= 0 ? 'text-green-600' : 'text-red-600')
                            : ($dif <= 0 ? 'text-green-600' : 'text-orange-600');
                    @endphp
                    <!-- Barra de Valor Alcançado -->
                    <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden mb-0.5">
                        <div class="h-1.5 rounded-full transition-all duration-500 {{ $barBg }} {{ $overflow ? 'animate-pulse' : '' }}"
                             style="width: {{ $pct }}%">
                        </div>
                    </div>
                    <!-- Barra de Dias Decorridos (Paralela) -->
                    <div class="w-full bg-gray-100 rounded-full overflow-hidden mb-1" style="height: 4px;">
                        <div class="h-full transition-all duration-500"
                             style="width: {{ $pctMesPassou }}%; background-color: #818cf8;">
                        </div>
                    </div>
                    
                    {{-- Porcentagem discreta centralizada --}}
                    <div class="text-center text-[10px] text-gray-400 font-bold mt-0.5">
                        {{ $pctDisplay }}%
                    </div>
                </div>
            </div>

            {{-- Modo Edição (Input Previsto) --}}
            <div x-show="isEditing" class="w-full flex items-center gap-1.5" x-cloak x-transition:enter>
                <div class="w-4 h-4 flex-shrink-0 flex items-center justify-center">
                    <span x-show="saving" x-cloak><i class="fas fa-spinner fa-spin text-indigo-500 text-[10px]"></i></span>
                    <span x-show="saved" x-cloak x-transition class="text-green-500"><i class="fas fa-check text-[10px]"></i></span>
                </div>
                <input type="number"
                       x-model="valor"
                       step="0.01" min="0"
                       @change="salvar()"
                       @keydown.enter="salvar(); $event.target.blur()"
                       @keydown.escape="cancelar(); $event.target.blur()"
                       class="w-full text-right text-sm border border-indigo-200 rounded-xl py-1 px-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white font-bold text-gray-800 transition-all shadow-sm">
            </div>
        </div>
    </div>

    {{-- Filhos recursivos --}}
    @if ($temFilhos)
        <div x-show="open" x-cloak>
            <div class="relative border-l border-gray-100 ml-5">
                @foreach ($node['children'] as $filho)
                    @include('admin.financeiro.orcamento._node', ['node' => $filho, 'nivel' => $nivel + 1, 'periodo' => $periodo, 'pctMesPassou' => $pctMesPassou])
                @endforeach
            </div>
        </div>
    @endif
</div>

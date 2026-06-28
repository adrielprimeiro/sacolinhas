{{-- resources/views/admin/financeiro/fluxo_caixa/_node.blade.php --}}
@php 
    $temFilhos = !empty($node['children']) && $node['children']->isNotEmpty(); 
@endphp

<div 
    x-data="{ open: true }"
    class="categoria-node border-b border-gray-50 last:border-0"
>
    {{-- Linha principal --}}
    <div class="grid grid-cols-12 gap-4 items-center pl-1 pr-3 py-3 hover:bg-gray-50/60 transition-all group">
        {{-- Coluna 1: Categoria --}}
        <div class="col-span-8 flex items-center gap-2" style="padding-left: {{ ($nivel * 0.75) }}rem">
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

        {{-- Coluna 2: Realizado --}}
        <div class="col-span-4 flex items-center justify-end font-bold text-sm text-gray-800">
            R$ {{ number_format($node['realizado'], 2, ',', '.') }}
        </div>
    </div>

    {{-- Filhos recursivos --}}
    @if ($temFilhos)
        <div x-show="open" x-cloak>
            <div class="relative border-l border-gray-100 ml-5">
                @foreach ($node['children'] as $filho)
                    @include('admin.financeiro.fluxo_caixa._node', ['node' => $filho, 'nivel' => $nivel + 1])
                @endforeach
            </div>
        </div>
    @endif
</div>

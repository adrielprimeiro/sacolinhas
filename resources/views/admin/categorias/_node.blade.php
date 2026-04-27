{{-- resources/views/admin/categorias/_node.blade.php --}}
@php $temFilhos = $categoria->children->isNotEmpty(); @endphp

<div 
    x-data="{ open: false }" 
    class="categoria-node"
    @expand-all.window="open = true"
    @collapse-all.window="open = false"
>
    {{-- Linha principal --}}
    <div
        class="flex items-center gap-2 group rounded-lg px-2 py-1.5 hover:bg-gray-50 transition-colors"
        style="padding-left: {{ 0.5 + ($nivel * 1.5) }}rem"
    >
        {{-- Botão expand/collapse --}}
        <div class="w-6 h-6 flex-shrink-0 flex items-center justify-center">
            @if ($temFilhos)
                <button
                    type="button"
                    @click="open = !open"
                    class="p-1 rounded hover:bg-gray-200 text-gray-500 transition-colors"
                >
                    <svg x-show="!open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <svg x-show="open" class="w-4 h-4" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                    </svg>
                </button>
            @else
                <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
            @endif
        </div>

        {{-- Nome --}}
        <div 
            class="flex-1 flex items-center gap-2 cursor-pointer select-none"
            @click="if($temFilhos) open = !open"
        >
            <span class="text-sm {{ $nivel === 0 ? 'font-bold text-gray-900' : 'font-medium text-gray-700' }}">
                {{ $categoria->name }}
            </span>
            <span class="text-xs text-gray-400 font-mono">/{{ $categoria->slug }}</span>
        </div>

        {{-- Badge Desconto --}}
        @if ($categoria->valor_desconto > 0)
            <span class="flex-shrink-0 px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700">
                {{ $categoria->tipo_desconto === 'porcentagem' ? $categoria->valor_desconto . '%' : 'R$ ' . number_format($categoria->valor_desconto, 2, ',', '.') }}
            </span>
        @endif

        {{-- Ações --}}
        <div class="flex-shrink-0 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <a
                href="{{ route('admin.categorias.edit', $categoria) }}"
                class="p-1.5 rounded-md text-blue-600 hover:bg-blue-50 transition-colors"
                title="Editar"
            >
                <i class="fas fa-edit text-xs"></i>
            </a>

            <form
                action="{{ route('admin.categorias.destroy', $categoria) }}"
                method="POST"
                class="inline"
                onsubmit="return confirm('Excluir \'{{ addslashes($categoria->name) }}\'?')"
            >
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="p-1.5 rounded-md text-red-500 hover:bg-red-50 transition-colors"
                    title="Excluir"
                >
                    <i class="fas fa-trash-alt text-xs"></i>
                </button>
            </form>
        </div>
    </div>

    {{-- Filhos --}}
    @if ($temFilhos)
        <div x-show="open" x-cloak style="display: none;">
            <div class="relative ml-3 border-l border-gray-100">
                @foreach ($categoria->children->sortBy('name') as $filho)
                    @include('admin.categorias._node', ['categoria' => $filho, 'nivel' => $nivel + 1])
                @endforeach
            </div>
        </div>
    @endif
</div>

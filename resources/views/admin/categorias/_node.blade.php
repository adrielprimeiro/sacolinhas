{{-- resources/views/admin/categorias/_node.blade.php --}}
{{-- Componente recursivo: renderiza um nó da árvore e seus filhos --}}

<div
    x-data="{ open: false }"
    class="select-none"
>
    {{-- Linha do nó --}}
    <div
        class="flex items-center gap-2 group rounded-lg px-2 py-1.5 hover:bg-gray-50 transition-colors"
        style="padding-left: {{ 0.5 + ($nivel * 1.25) }}rem"
    >
        {{-- Ícone expand/collapse (só aparece se tiver filhos) --}}
        @if ($categoria->children->isNotEmpty())
            <button
                @click="open = !open"
                class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-gray-700 transition-colors flex-shrink-0"
            >
                <i class="fas text-xs" :class="open ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
            </button>
        @else
            <span class="w-5 h-5 flex-shrink-0 flex items-center justify-center">
                <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
            </span>
        @endif

        {{-- Ícone da categoria --}}
        <span class="flex-shrink-0 text-{{ $nivel === 0 ? 'blue' : ($nivel === 1 ? 'indigo' : 'violet') }}-500 text-xs">
            <i class="fas fa-{{ $nivel === 0 ? 'folder' : ($nivel === 1 ? 'folder-open' : 'tag') }}"></i>
        </span>

        {{-- Nome e slug --}}
        <div class="flex-1 min-w-0">
            <span class="text-sm font-{{ $nivel === 0 ? 'semibold' : 'medium' }} text-gray-{{ $nivel === 0 ? '900' : '700' }} truncate">
                {{ $categoria->name }}
            </span>
            <span class="ml-2 text-xs text-gray-400 font-mono">/{{ $categoria->slug }}</span>
        </div>

        {{-- Badge desconto --}}
        @if ($categoria->valor_desconto > 0)
            <span class="flex-shrink-0 px-1.5 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-700">
                {{ $categoria->tipo_desconto === 'porcentagem' ? $categoria->valor_desconto . '%' : 'R$ ' . number_format($categoria->valor_desconto, 2, ',', '.') }}
            </span>
        @endif

        {{-- Badge nº de itens diretos --}}
        @if (isset($categoria->items_count) && $categoria->items_count > 0)
            <span class="flex-shrink-0 px-1.5 py-0.5 rounded text-xs bg-blue-50 text-blue-600">
                {{ $categoria->items_count }} itens
            </span>
        @endif

        {{-- Ações (aparecem no hover) --}}
        <div class="flex-shrink-0 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <a
                href="{{ route('admin.categorias.edit', $categoria) }}"
                class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium text-blue-600 hover:bg-blue-50 transition-colors"
                title="Editar"
            >
                <i class="fas fa-edit"></i>
                <span class="hidden sm:inline">Editar</span>
            </a>

            <form
                action="{{ route('admin.categorias.destroy', $categoria) }}"
                method="POST"
                class="inline"
                onsubmit="return confirm('Excluir a categoria \'{{ addslashes($categoria->name) }}\' e todas as suas subcategorias?')"
            >
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium text-red-500 hover:bg-red-50 transition-colors"
                    title="Excluir"
                >
                    <i class="fas fa-trash-alt"></i>
                    <span class="hidden sm:inline">Excluir</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Filhos (mostrados/ocultados via Alpine) --}}
    @if ($categoria->children->isNotEmpty())
        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
            {{-- Linha vertical de indentação --}}
            <div class="relative" style="padding-left: {{ 0.5 + ($nivel * 1.25) + 0.625 }}rem">
                <div class="absolute left-0 top-0 bottom-0 border-l-2 border-gray-100" style="left: {{ 0.5 + ($nivel * 1.25) + 0.625 }}rem"></div>
            </div>

            @foreach ($categoria->children->sortBy('name') as $filho)
                @include('admin.categorias._node', ['categoria' => $filho, 'nivel' => $nivel + 1])
            @endforeach
        </div>
    @endif
</div>

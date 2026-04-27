{{-- resources/views/admin/items/partials/_category_node.blade.php --}}
@php 
    $temFilhos = $categoria->children->isNotEmpty(); 
    $isChecked = in_array($categoria->id, $selectedCategories);
@endphp

<div 
    x-data="{ open: {{ $isChecked ? 'true' : 'false' }} }" 
    class="categoria-selection-node"
    @expand-all-cats.window="open = true"
    @collapse-all-cats.window="open = false"
>
    {{-- Linha da categoria --}}
    <div
        class="flex items-center gap-2 group py-1 hover:bg-gray-50 transition-colors rounded px-1"
        style="padding-left: {{ $nivel * 1.25 }}rem"
    >
        {{-- Botão expand/collapse --}}
        <div class="w-5 h-5 flex-shrink-0 flex items-center justify-center">
            @if ($temFilhos)
                <button
                    type="button"
                    @click="open = !open"
                    class="p-0.5 rounded hover:bg-gray-200 text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <svg x-show="!open" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    <svg x-show="open" class="w-3 h-3" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/>
                    </svg>
                </button>
            @else
                <span class="w-1 h-1 rounded-full bg-gray-300"></span>
            @endif
        </div>

        {{-- Checkbox + Nome --}}
        <label class="flex-1 flex items-center gap-2 cursor-pointer select-none py-0.5">
            <input 
                type="checkbox" 
                name="categorias[]" 
                value="{{ $categoria->id }}"
                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 w-4 h-4"
                {{ $isChecked ? 'checked' : '' }}
            >
            <span class="text-sm {{ $nivel === 0 ? 'font-bold text-gray-800' : 'text-gray-700' }}">
                {{ $categoria->name }}
            </span>
            @if($nivel > 0)
                <span class="text-[10px] text-gray-400 font-mono">/{{ $categoria->slug }}</span>
            @endif
        </label>
    </div>

    {{-- Filhos --}}
    @if ($temFilhos)
        <div x-show="open" x-cloak style="display: none;" class="ml-2.5 border-l border-gray-100">
            @foreach ($categoria->children->sortBy('name') as $filho)
                @include('admin.items.partials._category_node', [
                    'categoria' => $filho, 
                    'nivel' => $nivel + 1,
                    'selectedCategories' => $selectedCategories
                ])
            @endforeach
        </div>
    @endif
</div>

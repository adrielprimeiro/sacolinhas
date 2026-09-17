{{-- resources/views/admin/items/partials/categories.blade.php --}}
@php
    $selectedCategories = isset($item) ? $item->categorias->pluck('id')->toArray() : [];
    $selectedCategoriesData = isset($item)
        ? $item->categorias->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values()->all()
        : [];
@endphp

<div 
    x-data="{
        showTree: false,
        selected: {{ \Illuminate\Support\Js::from($selectedCategoriesData) }},
        onCategoryToggle(e) {
            if (!e.target.matches('input[name=\'categorias[]\']')) return;
            const cb = e.target;
            const id = parseInt(cb.value);
            const labelEl = cb.closest('label');
            const name = labelEl ? (labelEl.querySelector('span')?.textContent?.trim() || '') : '';
            if (cb.checked) {
                if (!this.selected.some(s => s.id === id)) {
                    this.selected.push({ id, name });
                }
            } else {
                this.selected = this.selected.filter(s => s.id !== id);
            }
        },
        unselect(id) {
            this.selected = this.selected.filter(s => s.id !== id);
            const cb = this.$el.querySelector(`input[name='categorias[]'][value='${id}']`);
            if (cb) {
                cb.checked = false;
            }
        }
    }"
    @change="onCategoryToggle($event)"
    class="mt-6 border-t border-gray-100 pt-4"
>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
        <div>
            <label class="block text-sm font-bold text-gray-700">Categorias do Produto</label>
            <p class="text-xs text-gray-500">Categoria(s) selecionada(s) para este item</p>
        </div>

        <button type="button" 
                @click="showTree = !showTree" 
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold transition shadow-xs"
                :class="showTree ? 'bg-gray-100 border-gray-300 text-gray-700 hover:bg-gray-200' : 'bg-indigo-50 border-indigo-200 text-indigo-700 hover:bg-indigo-100'">
            <i class="fas" :class="showTree ? 'fa-chevron-up' : 'fa-sitemap'"></i>
            <span x-text="showTree ? 'Ocultar árvore de opções' : 'Alterar / Abrir árvore de opções'"></span>
        </button>
    </div>

    {{-- Exibição apenas da(s) categoria(s) selecionada(s) --}}
    <div class="bg-gray-50/80 border border-gray-200 rounded-lg p-3 min-h-[46px] flex flex-wrap items-center gap-2">
        <template x-for="cat in selected" :key="cat.id">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md bg-white border border-gray-300 shadow-xs text-xs font-medium text-gray-800">
                <i class="fas fa-tag text-indigo-500"></i>
                <span x-text="cat.name"></span>
                <button type="button" 
                        @click="unselect(cat.id)" 
                        class="text-gray-400 hover:text-red-600 ml-1 transition-colors" 
                        title="Remover categoria">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
        </template>
        
        <div x-show="selected.length === 0" class="text-xs text-gray-400 italic">
            Nenhuma categoria selecionada. Clique em "Alterar / Abrir árvore de opções" para selecionar.
        </div>
    </div>

    {{-- Árvore colapsável de categorias --}}
    <div 
        x-show="showTree" 
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="mt-3 bg-white rounded-xl border border-gray-200 p-3 shadow-xs"
    >
        <div class="flex items-center justify-between pb-2 mb-2 border-b border-gray-100 text-xs text-gray-500">
            <span class="font-medium">Selecione na árvore abaixo:</span>
            <div class="flex items-center gap-2">
                <button type="button" @click="$dispatch('expand-all-cats')" class="text-[10px] uppercase font-bold text-gray-400 hover:text-indigo-600 transition-colors">
                    Expandir tudo
                </button>
                <span class="text-gray-300 text-[10px]">|</span>
                <button type="button" @click="$dispatch('collapse-all-cats')" class="text-[10px] uppercase font-bold text-gray-400 hover:text-indigo-600 transition-colors">
                    Recolher
                </button>
            </div>
        </div>

        <div class="max-h-[350px] overflow-y-auto custom-scrollbar">
            @forelse ($treeCategories ?? [] as $cat)
                @include('admin.items.partials._category_node', [
                    'categoria' => $cat, 
                    'nivel' => 0,
                    'selectedCategories' => $selectedCategories
                ])
            @empty
                <div class="text-center py-4">
                    <p class="text-sm text-gray-500 italic">Nenhuma categoria cadastrada.</p>
                    <a href="{{ route('admin.categorias.create') }}" class="text-xs text-blue-600 font-bold hover:underline">
                        Criar Categorias
                    </a>
                </div>
            @endforelse
        </div>
    </div>
    
    @error('categorias')
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>

@if(isset($item) && (float)$item->final_price < (float)$item->preco)
    <div class="mt-4 p-3 bg-green-50 rounded-lg border border-green-100">
        <p class="text-xs text-green-800">
            <i class="fas fa-magic mr-1.5"></i> <strong>Melhor Preço Aplicado:</strong> 
            De <span class="line-through">{{ $item->formatted_price }}</span> por <span class="font-bold text-sm">{{ $item->formatted_final_price }}</span>
        </p>
    </div>
@endif

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
</style>

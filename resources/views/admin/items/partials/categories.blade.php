{{-- resources/views/admin/items/partials/categories.blade.php --}}
@php
    $selectedCategories = isset($item) ? $item->categorias->pluck('id')->toArray() : [];
@endphp

<div class="mt-6 border-t border-gray-100 pt-4">
    <div class="flex items-center justify-between mb-3">
        <label class="block text-sm font-bold text-gray-700">Categorias do Produto</label>
        
        <div class="flex items-center gap-2" x-data>
            <button type="button" @click="$dispatch('expand-all-cats')" class="text-[10px] uppercase font-bold text-gray-400 hover:text-blue-600 transition-colors">
                Expandir tudo
            </button>
            <span class="text-gray-300 text-[10px]">|</span>
            <button type="button" @click="$dispatch('collapse-all-cats')" class="text-[10px] uppercase font-bold text-gray-400 hover:text-blue-600 transition-colors">
                Recolher
            </button>
        </div>
    </div>

    <div 
        class="bg-gray-50/50 rounded-xl border border-gray-100 p-3 max-h-[400px] overflow-y-auto custom-scrollbar"
    >
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

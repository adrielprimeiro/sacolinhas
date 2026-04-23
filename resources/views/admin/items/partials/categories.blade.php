{{-- resources/views/admin/items/partials/categories.blade.php --}}
@php
    $allCategories = \App\Models\Categoria::all();
    $selectedCategories = isset($item) ? $item->categorias->pluck('id')->toArray() : [];
@endphp

<div class="mt-4 border-t border-gray-100 pt-4">
    <label class="block text-sm font-medium text-gray-700 mb-2">Categorias do Produto</label>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        @foreach ($allCategories as $cat)
            <label class="inline-flex items-center p-2 rounded hover:bg-gray-50 border border-gray-100 cursor-pointer transition duration-150">
                <input type="checkbox" name="categorias[]" value="{{ $cat->id }}" 
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                    {{ in_array($cat->id, $selectedCategories) ? 'checked' : '' }}>
                <span class="ml-2 text-sm text-gray-700">{{ $cat->name }}</span>
            </label>
        @endforeach
    </div>
    @if($allCategories->isEmpty())
        <p class="text-sm text-gray-500 italic">Nenhuma categoria cadastrada. <a href="{{ route('admin.categorias.create') }}" class="text-blue-600 hover:underline">Criar uma agora</a>.</p>
    @endif
</div>

@if(isset($item) && (float)$item->final_price < (float)$item->preco)
    <div class="mt-4 p-3 bg-green-50 rounded-md border border-green-200">
        <p class="text-sm text-green-800">
            <i class="fas fa-tag mr-2"></i> <strong>Melhor Preço Aplicado:</strong> 
            De <span class="line-through">{{ $item->formatted_price }}</span> por <strong>{{ $item->formatted_final_price }}</strong>
        </p>
    </div>
@endif

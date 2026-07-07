@extends('layouts.app')

@section('title', isset($marca) ? 'Editar Marca' : 'Nova Marca')

@section('content')
<div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
    {{-- Breadcrumbs --}}
    <div class="mb-6">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('admin.marcas.index') }}" class="text-gray-700 hover:text-blue-600 inline-flex items-center text-sm font-medium">
                        Marcas
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 text-xs mx-2"></i>
                        <span class="text-gray-500 text-sm font-medium">{{ isset($marca) ? 'Editar' : 'Nova' }}</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    {{-- Card Principal --}}
    <div class="bg-white shadow-lg rounded-lg p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">
            {{ isset($marca) ? 'Editar Marca: ' . $marca->nome : 'Cadastrar Nova Marca' }}
        </h1>

        <form action="{{ isset($marca) ? route('admin.marcas.update', $marca) : route('admin.marcas.store') }}" method="POST">
            @csrf
            @if (isset($marca))
                @method('PUT')
            @endif

            <div class="space-y-5 mb-6">
                {{-- Nome da Marca --}}
                <div>
                    <label for="nome" class="block text-sm font-medium text-gray-700">Nome da Marca <span class="text-red-500">*</span></label>
                    <input 
                        type="text" 
                        name="nome" 
                        id="nome" 
                        value="{{ old('nome', $marca->nome ?? '') }}" 
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('nome') border-red-500 @enderror"
                        placeholder="Ex: Farm, Melissa, Zara..."
                        required
                        @if(isset($marca) && in_array(strtolower(trim($marca->nome)), ['sem marca', 'sem_marca'])) readonly @endif
                    >
                    @error('nome')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Porcentagem de Valor Agregado --}}
                <div>
                    <label for="porcentagem_valor" class="block text-sm font-medium text-gray-700">Acréscimo de Valor (%) <span class="text-red-500">*</span></label>
                    <div class="relative rounded-md shadow-sm mt-1">
                        <input 
                            type="number" 
                            step="0.01" 
                            name="porcentagem_valor" 
                            id="porcentagem_valor" 
                            value="{{ old('porcentagem_valor', $marca->porcentagem_valor ?? '100.00') }}" 
                            class="block w-full border border-gray-300 rounded-md py-2 px-3 pr-12 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('porcentagem_valor') border-red-500 @enderror"
                            placeholder="100.00"
                            required
                        >
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">%</span>
                        </div>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-400">
                        100% mantém o preço base da categoria. <br>
                        350% vende por 3,5 vezes o preço base. <br>
                        400% vende por 4 vezes o preço base.
                    </p>
                    @error('porcentagem_valor')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Botões de Ação --}}
            <div class="flex justify-end gap-3 border-t border-gray-150 pt-5">
                <a 
                    href="{{ route('admin.marcas.index') }}" 
                    class="bg-white py-2 px-4 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition"
                >
                    Cancelar
                </a>
                <button 
                    type="submit" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm py-2 px-4 rounded-md shadow-sm transition"
                >
                    <i class="far fa-save mr-1.5"></i> Salvar Marca
                </button>
            </div>

        </form>
    </div>
</div>
@endsection

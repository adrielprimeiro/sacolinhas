@extends('layouts.app')

@section('title', isset($categoria) ? 'Editar Categoria' : 'Nova Categoria')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('admin.categorias.index') }}" class="text-gray-700 hover:text-blue-600 inline-flex items-center text-sm font-medium">
                        Categorias
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 text-xs mx-2"></i>
                        <span class="text-gray-500 text-sm font-medium">{{ isset($categoria) ? 'Editar' : 'Nova' }}</span>
                    </div>
                </li>
            </ol>
        </nav>
        <h1 class="text-2xl font-semibold text-gray-900 mt-2">{{ isset($categoria) ? 'Editar Categoria: ' . $categoria->name : 'Criar Nova Categoria' }}</h1>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <form action="{{ isset($categoria) ? route('admin.categorias.update', $categoria) : route('admin.categorias.store') }}" method="POST">
            @csrf
            @if (isset($categoria))
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Nome --}}
                <div class="col-span-1">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nome da Categoria</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $categoria->name ?? '') }}" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Categoria Pai --}}
                <div class="col-span-1">
                    <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-1">Categoria Hierárquica (Pai)</label>
                    <select name="parent_id" id="parent_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">-- Categoria Raiz --</option>
                        @foreach ($parentCategorias as $p)
                            <option value="{{ $p->id }}" {{ old('parent_id', $categoria->parent_id ?? '') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-2 border-t border-gray-100 my-2 pt-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4"><i class="fas fa-tag mr-2"></i> Regras de Desconto</h3>
                </div>

                {{-- Valor do Desconto --}}
                <div class="col-span-1">
                    <label for="valor_desconto" class="block text-sm font-medium text-gray-700 mb-1">Valor do Desconto</label>
                    <div class="relative rounded-md shadow-sm">
                        <input type="number" step="0.01" name="valor_desconto" id="valor_desconto" value="{{ old('valor_desconto', $categoria->valor_desconto ?? 0) }}" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Deixe 0 se não houver desconto específico para esta categoria.</p>
                </div>

                {{-- Tipo de Desconto --}}
                <div class="col-span-1">
                    <label for="tipo_desconto" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Desconto</label>
                    <select name="tipo_desconto" id="tipo_desconto" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="porcentagem" {{ old('tipo_desconto', $categoria->tipo_desconto ?? '') == 'porcentagem' ? 'selected' : '' }}>Porcentagem (%)</option>
                        <option value="fixo" {{ old('tipo_desconto', $categoria->tipo_desconto ?? '') == 'fixo' ? 'selected' : '' }}>Valor Fixo (R$)</option>
                    </select>
                </div>

                <div class="col-span-2 border-t border-gray-100 my-2 pt-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4"><i class="fas fa-box mr-2"></i> Dimensões Médias (para frete)</h3>
                </div>

                {{-- Altura --}}
                <div class="col-span-1 sm:col-span-1 md:col-span-1 lg:col-span-1">
                    <label for="altura" class="block text-sm font-medium text-gray-700 mb-1">Altura (cm)</label>
                    <input type="number" step="0.01" name="altura" id="altura" value="{{ old('altura', $categoria->altura ?? '') }}" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>

                {{-- Largura --}}
                <div class="col-span-1 sm:col-span-1 md:col-span-1 lg:col-span-1">
                    <label for="largura" class="block text-sm font-medium text-gray-700 mb-1">Largura (cm)</label>
                    <input type="number" step="0.01" name="largura" id="largura" value="{{ old('largura', $categoria->largura ?? '') }}" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>

                {{-- Comprimento --}}
                <div class="col-span-1 sm:col-span-1 md:col-span-1 lg:col-span-1">
                    <label for="comprimento" class="block text-sm font-medium text-gray-700 mb-1">Comprimento (cm)</label>
                    <input type="number" step="0.01" name="comprimento" id="comprimento" value="{{ old('comprimento', $categoria->comprimento ?? '') }}" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>

                {{-- Peso --}}
                <div class="col-span-1 sm:col-span-1 md:col-span-1 lg:col-span-1">
                    <label for="peso" class="block text-sm font-medium text-gray-700 mb-1">Peso (kg)</label>
                    <input type="number" step="0.001" name="peso" id="peso" value="{{ old('peso', $categoria->peso ?? '') }}" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
            </div>

            <div class="mt-8 flex justify-end space-x-3">
                <a href="{{ route('admin.categorias.index') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    {{ isset($categoria) ? 'Atualizar Categoria' : 'Criar Categoria' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

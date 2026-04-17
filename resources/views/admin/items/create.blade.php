@extends('layouts.app')

@section('title', 'Criar Novo Item')

@section('content')
<div class="flex justify-between items-start mb-6">
  <div>
    <h1 class="text-3xl font-semibold text-gray-800">Criar Novo Item</h1>
    <nav class="mt-2 text-sm text-gray-500">
      <a href="{{ route('items.index') }}" class="hover:text-blue-600">Itens</a>
      <span class="mx-2">/</span>
      <span class="text-gray-700">Novo</span>
    </nav>
  </div>
  <a href="{{ route('items.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md shadow-md transition">
    <i class="fas fa-arrow-left mr-2"></i> Voltar para a Lista
  </a>
</div>

@if ($errors->any())
  <div class="mb-4 bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded">
    <div class="font-semibold mb-2"><i class="fas fa-exclamation-triangle mr-2"></i> Corrija os erros abaixo:</div>
    <ul class="list-disc pl-5">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form method="POST" action="{{ route('items.store') }}" enctype="multipart/form-data">
  @csrf

  <div class="bg-white shadow-lg rounded-lg p-6 max-w-5xl">
    {{-- Linha 1: Código + Nome --}}
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
      <div class="md:col-span-4">
        <label for="codigo" class="block text-sm font-medium text-gray-700 mb-1">Código *</label>
        <input type="text" id="codigo" name="codigo" value="{{ old('codigo') }}" required
               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('codigo') border-red-400 @enderror"
               placeholder="Ex: SKU12345" />
        @error('codigo')
          <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
      </div>

      <div class="md:col-span-8">
        <label for="nome_do_produto" class="block text-sm font-medium text-gray-700 mb-1">Nome do Produto *</label>
        <input type="text" id="nome_do_produto" name="nome_do_produto" value="{{ old('nome_do_produto') }}" required
               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nome_do_produto') border-red-400 @enderror"
               placeholder="Ex: Nome do Produto" />
        @error('nome_do_produto')
          <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
      </div>
    </div>

    {{-- Descrição --}}
    <div class="mt-4">
      <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
      <textarea id="descricao" name="descricao" rows="3"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('descricao') border-red-400 @enderror"
                placeholder="Descreva o item...">{{ old('descricao') }}</textarea>
      @error('descricao')
        <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
      @enderror
    </div>

    {{-- Linha 2: Custo + Preço + Categoria --}}
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mt-4">
      <div class="md:col-span-4">
        <label for="custo" class="block text-sm font-medium text-gray-700 mb-1">Custo (R$)</label>
        <input type="number" id="custo" name="custo" value="{{ old('custo') }}" step="0.01" min="0"
               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('custo') border-red-400 @enderror" />
        @error('custo')
          <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
      </div>

      <div class="md:col-span-4">
        <label for="preco" class="block text-sm font-medium text-gray-700 mb-1">Preço (R$) *</label>
        <input type="number" id="preco" name="preco" value="{{ old('preco') }}" step="0.01" min="0.01" required
               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('preco') border-red-400 @enderror"
               placeholder="0,00" />
        @error('preco')
          <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
      </div>

      <div class="md:col-span-4">
        <label for="codigo_da_categoria" class="block text-sm font-medium text-gray-700 mb-1">Código da Categoria (Legacy)</label>
        <input type="text" id="codigo_da_categoria" name="codigo_da_categoria" value="{{ old('codigo_da_categoria') }}"
               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
               placeholder="Ex: CATEGORIA123" />
      </div>
    </div>

    {{-- Categorias Novo Sistema --}}
    @include('admin.items.partials.categories')

    {{-- Linha 3: Marca + Modelo + Estado --}}
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mt-4">
      <div class="md:col-span-4">
        <label for="marca" class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
        <input type="text" id="marca" name="marca" value="{{ old('marca') }}"
               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>

      <div class="md:col-span-4">
        <label for="modelo" class="block text-sm font-medium text-gray-700 mb-1">Modelo</label>
        <input type="text" id="modelo" name="modelo" value="{{ old('modelo') }}"
               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>

      <div class="md:col-span-4">
        <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
        <select id="estado" name="estado" required
                class="w-full border border-gray-300 rounded-md px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="novo" {{ old('estado') == 'novo' ? 'selected' : '' }}>Novo</option>
          <option value="usado" {{ old('estado') == 'usado' ? 'selected' : '' }}>Usado</option>
          <option value="semi-novo" {{ old('estado') == 'semi-novo' ? 'selected' : '' }}>Semi-novo</option>
          <option value="recondicionado" {{ old('estado') == 'recondicionado' ? 'selected' : '' }}>Recondicionado</option>
        </select>
      </div>
    </div>

    {{-- Linha 4: Status + Imagem --}}
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mt-4">
      <div class="md:col-span-6">
        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
        <select id="status" name="status" required
                class="w-full border border-gray-300 rounded-md px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="disponivel" {{ old('status') == 'disponivel' ? 'selected' : '' }}>Disponível</option>
          <option value="reservado" {{ old('status') == 'reservado' ? 'selected' : '' }}>Reservado</option>
          <option value="vendido" {{ old('status') == 'vendido' ? 'selected' : '' }}>Vendido</option>
          <option value="indisponivel" {{ old('status') == 'indisponivel' ? 'selected' : '' }}>Indisponível</option>
        </select>
      </div>
      <div class="md:col-span-6">
        <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Imagem de Capa</label>
        <input type="file" id="image" name="image" accept="image/*"
               class="w-full border border-gray-300 rounded-md px-3 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>
    </div>

    <div class="mt-8 flex justify-end border-t border-gray-200 pt-6">
      <a href="{{ route('items.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md shadow-md transition mr-3">
        Cancelar
      </a>
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition">
        <i class="fas fa-save mr-2"></i> Criar Item
      </button>
    </div>
  </div>
</form>
@endsection
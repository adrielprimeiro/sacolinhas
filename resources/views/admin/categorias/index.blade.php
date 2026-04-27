@extends('layouts.app')

@section('title', 'Gerenciar Categorias')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Cabeçalho --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Categorias</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $totalCategorias }} categorias · {{ $totalRaiz }} grupos raiz
            </p>
        </div>

        <div class="flex items-center gap-2" x-data>
            <button
                type="button"
                @click="$dispatch('expand-all')"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 border border-gray-200 transition-colors"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                </svg>
                Expandir tudo
            </button>
            <button
                type="button"
                @click="$dispatch('collapse-all')"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 border border-gray-200 transition-colors"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                </svg>
                Recolher tudo
            </button>

            <a
                href="{{ route('admin.categorias.create') }}"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm py-2 px-4 rounded-lg transition-colors shadow-sm"
            >
                <i class="fas fa-plus"></i>
                Nova Categoria
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Árvore de categorias --}}
    <div
        class="bg-white shadow-sm rounded-xl border border-gray-200 divide-y divide-gray-100 overflow-hidden"
        x-data="{}"
        @expand-all.window="$el.querySelectorAll('[x-data]').forEach(el => { if(el._x_dataStack) { el._x_dataStack[0].open = true } })"
        @collapse-all.window="$el.querySelectorAll('[x-data]').forEach(el => { if(el._x_dataStack) { el._x_dataStack[0].open = false } })"
    >
        @forelse ($categorias as $categoria)
            <div class="py-0.5">
                @include('admin.categorias._node', ['categoria' => $categoria, 'nivel' => 0])
            </div>
        @empty
            <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                <i class="fas fa-folder-open text-4xl mb-3"></i>
                <p class="text-sm font-medium">Nenhuma categoria cadastrada</p>
                <a href="{{ route('admin.categorias.create') }}" class="mt-3 text-sm text-blue-600 hover:underline">
                    Criar a primeira categoria
                </a>
            </div>
        @endforelse
    </div>

    {{-- Legenda --}}
    <div class="mt-4 flex flex-wrap items-center gap-4 text-xs text-gray-400">
        <span class="flex items-center gap-1.5">
            <i class="fas fa-folder text-blue-400"></i> Categoria raiz
        </span>
        <span class="flex items-center gap-1.5">
            <i class="fas fa-folder-open text-indigo-400"></i> Subcategoria
        </span>
        <span class="flex items-center gap-1.5">
            <i class="fas fa-tag text-violet-400"></i> Categoria folha
        </span>
        <span class="flex items-center gap-1.5">
            <span class="px-1.5 py-0.5 rounded bg-green-100 text-green-700 font-semibold">%</span>
            Desconto ativo
        </span>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection

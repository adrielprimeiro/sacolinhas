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
                href="{{ route('admin.categorias.create', ['brecho_id' => $brechoId !== 'all' ? $brechoId : 1]) }}"
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

    {{-- Filtro por Brechó (Apenas Matriz / Minha Mania) --}}
    @if(empty($isParceiro))
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider mr-1">Visualizar:</span>
                <a href="{{ route('admin.categorias.index', ['brecho_id' => 1]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ ($brechoId == 1 || $brechoId === null) ? 'bg-purple-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    <i class="fas fa-store text-[10px]"></i> Minha Mania (Padrão + Mania)
                </a>
                <a href="{{ route('admin.categorias.index', ['brecho_id' => 'padrao']) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ ($brechoId === 'padrao') ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    <i class="fas fa-globe text-[10px]"></i> Apenas Padrão Global
                </a>
                @if(isset($brechos))
                    @foreach($brechos as $b)
                        @if($b->id != 1)
                            <a href="{{ route('admin.categorias.index', ['brecho_id' => $b->id]) }}"
                               class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ ($brechoId == $b->id) ? 'bg-amber-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                <i class="fas fa-store-alt text-[10px]"></i> {{ $b->nome }}
                            </a>
                        @endif
                    @endforeach
                @endif
                <a href="{{ route('admin.categorias.index', ['brecho_id' => 'all']) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ ($brechoId === 'all') ? 'bg-gray-800 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    <i class="fas fa-layer-group text-[10px]"></i> Todas
                </a>
            </div>
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
                @include('admin.categorias._node', ['categoria' => $categoria, 'nivel' => 0, 'isParceiro' => $isParceiro ?? false])
            </div>
        @empty
            <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                <i class="fas fa-folder-open text-4xl mb-3"></i>
                <p class="text-sm font-medium">Nenhuma categoria cadastrada</p>
                <a href="{{ route('admin.categorias.create', ['brecho_id' => $brechoId !== 'all' ? $brechoId : 1]) }}" class="mt-3 text-sm text-blue-600 hover:underline">
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
            <span class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 font-semibold border border-gray-200">Padrão</span>
            Base do Sistema
        </span>
        <span class="flex items-center gap-1.5">
            <span class="px-1.5 py-0.5 rounded bg-purple-100 text-purple-800 font-semibold border border-purple-200">Mania</span>
            Exclusivo Minha Mania
        </span>
        <span class="flex items-center gap-1.5">
            <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold border border-amber-200">Taco Balaio</span>
            Exclusivo Taco Balaio
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

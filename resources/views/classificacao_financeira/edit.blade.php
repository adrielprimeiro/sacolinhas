@extends('layouts.app')

@section('title', 'Editar Classificação #' . $classificacao_financeira->id)
@section('brand_route', 'classificacao_financeira.index')
@section('brand_icon', 'fas fa-sitemap')

@section('content')
    <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-semibold text-gray-800">Editar Classificação #{{ $classificacao_financeira->id }}</h1>

            <a href="{{ route('classificacao_financeira.show', $classificacao_financeira->id) }}"
               class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300">
                <i class="fas fa-eye mr-2"></i> Ver
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-4 bg-red-100 text-red-800 px-4 py-3 rounded-md">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('classificacao_financeira.update', $classificacao_financeira->id) }}">
            @csrf
            @method('PUT')

            @include('classificacao_financeira._form', [
                'classificacao_financeira' => $classificacao_financeira,
                'pais' => $pais
            ])

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('classificacao_financeira.index') }}"
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                    <i class="fas fa-arrow-alt-circle-left mr-2"></i> Cancelar
                </a>

                <button type="submit"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300">
                    <i class="fas fa-save mr-2"></i> Salvar
                </button>
            </div>
        </form>
    </div>
@endsection
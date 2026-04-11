@extends('layouts.app')

@section('title', 'Nova Classificação')
@section('brand_route', 'classificacao_financeira.index')
@section('brand_icon', 'fas fa-sitemap')

@section('content')
    <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-semibold text-gray-800">Nova Classificação</h1>

            <a href="{{ route('classificacao_financeira.index') }}"
               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                <i class="fas fa-arrow-alt-circle-left mr-2"></i> Voltar para a Lista
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

        <form method="POST" action="{{ route('classificacao_financeira.store') }}">
            @include('classificacao_financeira._form', [
                'classificacao_financeira' => null,
                'pais' => $pais
            ])

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('classificacao_financeira.index') }}"
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                    <i class="fas fa-times mr-2"></i> Cancelar
                </a>

                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300">
                    <i class="fas fa-save mr-2"></i> Criar
                </button>
            </div>
        </form>
    </div>
@endsection
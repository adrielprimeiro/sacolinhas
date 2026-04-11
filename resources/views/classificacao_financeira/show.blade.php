@extends('layouts.app')

@section('title', 'Classificação #' . $classificacao_financeira->id)
@section('brand_route', 'classificacao_financeira.index')
@section('brand_icon', 'fas fa-sitemap')

@section('content')
    <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-semibold text-gray-800">Detalhes da Classificação #{{ $classificacao_financeira->id }}</h1>

            <a href="{{ route('classificacao_financeira.edit', $classificacao_financeira->id) }}"
               class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
        </div>

        <div class="border-t border-gray-200 pt-4">
            <dl class="divide-y divide-gray-200">
                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">ID</dt>
                    <dd class="text-gray-900">{{ $classificacao_financeira->id }}</dd>
                </div>

                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">User ID</dt>
                    <dd class="text-gray-900">{{ $classificacao_financeira->user_id }}</dd>
                </div>

                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Código Contábil</dt>
                    <dd class="text-gray-900">{{ $classificacao_financeira->codigo_contabil }}</dd>
                </div>

                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Nome</dt>
                    <dd class="text-gray-900 text-right max-w-md">{{ $classificacao_financeira->nome }}</dd>
                </div>

                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Tipo Natureza</dt>
                    <dd class="text-gray-900">
                        @if ($classificacao_financeira->tipo_natureza === 'receita')
                            <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs font-semibold">Receita</span>
                        @else
                            <span class="bg-red-200 text-red-800 py-1 px-3 rounded-full text-xs font-semibold">Despesa</span>
                        @endif
                    </dd>
                </div>

                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Nível</dt>
                    <dd class="text-gray-900">{{ ucfirst($classificacao_financeira->nivel) }}</dd>
                </div>

                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Pai</dt>
                    <dd class="text-gray-900 text-right max-w-md">
                        {{ $classificacao_financeira->pai ? ($classificacao_financeira->pai->nome.' ('.$classificacao_financeira->pai->codigo_contabil.')') : 'N/A' }}
                    </dd>
                </div>

                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Área Finalidade</dt>
                    <dd class="text-gray-900">{{ $classificacao_financeira->area_finalidade ?? 'N/A' }}</dd>
                </div>

                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Frequência</dt>
                    <dd class="text-gray-900">{{ $classificacao_financeira->frequencia ?? 'N/A' }}</dd>
                </div>

                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Descrição</dt>
                    <dd class="text-gray-900 text-right max-w-md">{{ $classificacao_financeira->descricao ?? 'N/A' }}</dd>
                </div>

                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Criado em</dt>
                    <dd class="text-gray-900">{{ optional($classificacao_financeira->created_at)->format('d/m/Y H:i') ?? 'N/A' }}</dd>
                </div>

                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Última Atualização</dt>
                    <dd class="text-gray-900">{{ optional($classificacao_financeira->updated_at)->format('d/m/Y H:i') ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <div class="mt-6 text-right">
            <a href="{{ route('classificacao_financeira.index') }}"
               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                <i class="fas fa-arrow-alt-circle-left mr-2"></i> Voltar para a Lista
            </a>
        </div>
    </div>
@endsection
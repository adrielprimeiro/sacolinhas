@extends('layouts.app')

@section('title', 'Lançamento #' . $financeiro->id)
@section('brand_route', 'admin.financeiro.index')
@section('brand_icon', 'fas fa-wallet')

@section('content')
    <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-semibold text-gray-800">Detalhes do Lançamento #{{ $financeiro->id }}</h1>
            <a href="{{ route('admin.financeiro.edit', $financeiro->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
        </div>

        <div class="border-t border-gray-200 pt-4">
            <dl class="divide-y divide-gray-200">
                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">ID</dt>
                    <dd class="text-gray-900">{{ $financeiro->id }}</dd>
                </div>
                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Usuário</dt>
                    <dd class="text-gray-900">{{ $financeiro->user->name ?? 'N/A' }}</dd>
                </div>
                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Data e Hora</dt>
                    <dd class="text-gray-900">{{ $financeiro->data_movimentacao->format('d/m/Y H:i') }}</dd>
                </div>
                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Tipo de Movimentação</dt>
                    <dd class="text-gray-900">
                        @if ($financeiro->tipo_movimentacao == 'credito')
                            <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs font-semibold">Crédito</span>
                        @else
                            <span class="bg-red-200 text-red-800 py-1 px-3 rounded-full text-xs font-semibold">Débito</span>
                        @endif
                    </dd>
                </div>
                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Valor</dt>
                    <dd class="text-gray-900 font-bold">R$ {{ number_format($financeiro->valor, 2, ',', '.') }}</dd>
                </div>
                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Descrição</dt>
                    <dd class="text-gray-900 text-right max-w-md">{{ $financeiro->descricao }}</dd>
                </div>
                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Saldo Anterior</dt>
                    <dd class="text-gray-900">R$ {{ number_format($financeiro->saldo_anterior, 2, ',', '.') }}</dd>
                </div>
                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Saldo Atual</dt>
                    <dd class="text-gray-900 font-bold">R$ {{ number_format($financeiro->saldo_atual, 2, ',', '.') }}</dd>
                </div>
                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Classificação Financeira</dt>
                    <dd class="text-gray-900">{{ $financeiro->classificacaoFinanceira->nome ?? 'N/A' }} ({{ $financeiro->classificacaoFinanceira->codigo_contabil ?? 'N/A' }})</dd>
                </div>
                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Tipo de Referência</dt>
                    <dd class="text-gray-900">{{ $financeiro->referencia_tipo ? ucfirst($financeiro->referencia_tipo) : 'N/A' }}</dd>
                </div>
                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">ID da Referência</dt>
                    <dd class="text-gray-900">{{ $financeiro->referencia_id ?? 'N/A' }}</dd>
                </div>
                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">ID da Live</dt>
                    <dd class="text-gray-900">{{ $financeiro->live_id ?? 'N/A' }}</dd>
                </div>
                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Observações</dt>
                    <dd class="text-gray-900 text-right max-w-md">{{ $financeiro->observacoes ?? 'N/A' }}</dd>
                </div>
                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Criado em</dt>
                    <dd class="text-gray-900">{{ $financeiro->created_at->format('d/m/Y H:i') }}</dd>
                </div>
                <div class="py-3 flex justify-between text-sm font-medium">
                    <dt class="text-gray-500">Última Atualização</dt>
                    <dd class="text-gray-900">{{ $financeiro->updated_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        <div class="mt-6 text-right">
            <a href="{{ route('admin.financeiro.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                <i class="fas fa-arrow-alt-circle-left mr-2"></i> Voltar para a Lista
            </a>
        </div>
    </div>
@endsection
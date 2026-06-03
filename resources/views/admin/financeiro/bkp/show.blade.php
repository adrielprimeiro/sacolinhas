@extends('layouts.app')

@section('title', 'Detalhes do Lançamento')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-info-circle text-blue-600"></i>
                Detalhes do Lançamento #{{ $financeiro->id }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.conta_corrente.edit', array_merge([$financeiro->id], request()->query())) }}" 
                   class="inline-flex items-center gap-2 bg-amber-100 hover:bg-amber-200 text-amber-900 px-4 py-2 rounded-xl text-sm font-bold border border-amber-200 transition">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="{{ route('admin.conta_corrente.index', request()->query()) }}" 
                   class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-xl text-sm font-semibold transition">
                    <i class="fas fa-list"></i> Lista
                </a>
            </div>
        </div>

        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-6">
                    <div>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Informações Básicas</h4>
                        <div class="bg-gray-50 p-4 rounded-2xl space-y-4">
                            <div>
                                <p class="text-sm text-gray-500">ID do Lançamento</p>
                                <p class="font-mono font-bold text-gray-800">#{{ $financeiro->id }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Usuário Responsável</p>
                                <p class="font-bold text-gray-800">{{ $financeiro->user->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Data e Hora</p>
                                <p class="font-bold text-gray-800">{{ $financeiro->data_movimentacao->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Valores e Status</h4>
                        <div class="bg-gray-50 p-4 rounded-2xl space-y-4">
                            <div>
                                <p class="text-sm text-gray-500">Tipo de Movimentação</p>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $financeiro->tipo_movimentacao === 'credito' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ ucfirst($financeiro->tipo_movimentacao) }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Valor Total</p>
                                <p class="text-2xl font-black {{ $financeiro->tipo_movimentacao === 'credito' ? 'text-green-600' : 'text-red-600' }}">
                                    R$ {{ number_format($financeiro->valor, 2, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Classificação e Referência</h4>
                        <div class="bg-gray-50 p-4 rounded-2xl space-y-4">
                            <div>
                                <p class="text-sm text-gray-500">Classificação Financeira</p>
                                <p class="font-bold text-blue-700">
                                    {{ $financeiro->classificacaoFinanceira->nome ?? 'N/A' }} 
                                    <span class="text-xs text-gray-400 font-normal">({{ $financeiro->classificacaoFinanceira->codigo_contabil ?? '' }})</span>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Referência</p>
                                <p class="font-bold text-gray-800">
                                    {{ $financeiro->referencia_tipo ? ucfirst($financeiro->referencia_tipo) : 'N/A' }}
                                    @if($financeiro->referencia_id)
                                        <span class="text-blue-600">#{{ $financeiro->referencia_id }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Descrição e Notas</h4>
                        <div class="bg-gray-50 p-4 rounded-2xl space-y-4">
                            <div>
                                <p class="text-sm text-gray-500">Descrição do Lançamento</p>
                                <p class="font-medium text-gray-800">{{ $financeiro->descricao }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Observações Internas</p>
                                <p class="text-sm text-gray-600 italic">{{ $financeiro->observacoes ?? 'Nenhuma observação registrada.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-100 flex justify-between text-xs text-gray-400 italic">
                <span>Criado em: {{ $financeiro->created_at->format('d/m/Y H:i:s') }}</span>
                <span>Última atualização: {{ $financeiro->updated_at->format('d/m/Y H:i:s') }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
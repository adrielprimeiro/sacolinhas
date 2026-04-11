@extends('layouts.app')

@section('title', 'Editar Lançamento #' . $financeiro->id)
@section('brand_route', 'admin.financeiro.index')
@section('brand_icon', 'fas fa-wallet')

@section('content')
    <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-lg p-6">
        <h1 class="text-3xl font-semibold text-gray-800 mb-6">Editar Lançamento #{{ $financeiro->id }}</h1>

        <form action="{{ route('admin.financeiro.update', $financeiro->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Usuário -->
                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">Usuário</label>
                    <select name="user_id" id="user_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('user_id') border-red-500 @enderror">
                        <option value="">Selecione um usuário...</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id', $financeiro->user_id) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Data e Hora da Movimentação -->
                <div>
                    <label for="data_movimentacao" class="block text-sm font-medium text-gray-700 mb-1">Data e Hora da Movimentação</label>
                    <input type="datetime-local" name="data_movimentacao" id="data_movimentacao" value="{{ old('data_movimentacao', $financeiro->data_movimentacao->format('Y-m-d\TH:i')) }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('data_movimentacao') border-red-500 @enderror">
                    @error('data_movimentacao')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Tipo de Movimentação -->
                <div>
                    <label for="tipo_movimentacao" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Movimentação</label>
                    <select name="tipo_movimentacao" id="tipo_movimentacao" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('tipo_movimentacao') border-red-500 @enderror">
                        <option value="">Selecione o tipo...</option>
                        <option value="credito" {{ old('tipo_movimentacao', $financeiro->tipo_movimentacao) == 'credito' ? 'selected' : '' }}>Crédito</option>
                        <option value="debito" {{ old('tipo_movimentacao', $financeiro->tipo_movimentacao) == 'debito' ? 'selected' : '' }}>Débito</option>
                    </select>
                    @error('tipo_movimentacao')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Valor -->
                <div>
                    <label for="valor" class="block text-sm font-medium text-gray-700 mb-1">Valor</label>
                    <input type="number" step="0.01" name="valor" id="valor" value="{{ old('valor', $financeiro->valor) }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('valor') border-red-500 @enderror" placeholder="Ex: 150.75">
                    @error('valor')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Descrição -->
            <div class="mb-6">
                <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                <textarea name="descricao" id="descricao" rows="3"
                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('descricao') border-red-500 @enderror" placeholder="Descreva o lançamento financeiro...">{{ old('descricao', $financeiro->descricao) }}</textarea>
                @error('descricao')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Classificação Financeira -->
            <div class="mb-6">
                <label for="classificacao_financeira_id" class="block text-sm font-medium text-gray-700 mb-1">Classificação Financeira</label>
                <select name="classificacao_financeira_id" id="classificacao_financeira_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('classificacao_financeira_id') border-red-500 @enderror">
                    <option value="">Selecione a classificação...</option>
                    @foreach ($classificacoes as $classificacao)
                        <option value="{{ $classificacao->id }}" {{ old('classificacao_financeira_id', $financeiro->classificacao_financeira_id) == $classificacao->id ? 'selected' : '' }}>
                            {{ $classificacao->nome }} ({{ $classificacao->codigo_contabil }})
                        </option>
                    @endforeach
                </select>
                @error('classificacao_financeira_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Tipo de Referência (Opcional) -->
                <div>
                    <label for="referencia_tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Referência (Opcional)</label>
                    <select name="referencia_tipo" id="referencia_tipo" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('referencia_tipo') border-red-500 @enderror">
                        <option value="">Nenhum</option>
                        <option value="sacolinha" {{ old('referencia_tipo', $financeiro->referencia_tipo) == 'sacolinha' ? 'selected' : '' }}>Sacolinha</option>
                        <option value="pagamento" {{ old('referencia_tipo', $financeiro->referencia_tipo) == 'pagamento' ? 'selected' : '' }}>Pagamento</option>
                        <option value="pedido" {{ old('referencia_tipo', $financeiro->referencia_tipo) == 'pedido' ? 'selected' : '' }}>Pedido</option>
                        <option value="ajuste" {{ old('referencia_tipo', $financeiro->referencia_tipo) == 'ajuste' ? 'selected' : '' }}>Ajuste</option>
                        <option value="desconto" {{ old('referencia_tipo', $financeiro->referencia_tipo) == 'desconto' ? 'selected' : '' }}>Desconto</option>
                        <!-- Adicione mais tipos conforme necessário -->
                    </select>
                    @error('referencia_tipo')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- ID da Referência (Opcional, visibilidade controlada por JS) -->
                <div id="referencia_id_field" class="{{ old('referencia_tipo', $financeiro->referencia_tipo) ? '' : 'hidden' }}">
                    <label for="referencia_id" class="block text-sm font-medium text-gray-700 mb-1">ID da Referência</label>
                    <input type="text" name="referencia_id" id="referencia_id" value="{{ old('referencia_id', $financeiro->referencia_id) }}"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('referencia_id') border-red-500 @enderror" placeholder="Ex: 12345">
                    @error('referencia_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- ID da Live (Opcional) -->
            <div class="mb-6">
                <label for="live_id" class="block text-sm font-medium text-gray-700 mb-1">ID da Live (Opcional)</label>
                <input type="text" name="live_id" id="live_id" value="{{ old('live_id', $financeiro->live_id) }}"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('live_id') border-red-500 @enderror" placeholder="Ex: 98765">
                @error('live_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Observações (Opcional) -->
            <div class="mb-6">
                <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações (Opcional)</label>
                <textarea name="observacoes" id="observacoes" rows="3"
                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('observacoes') border-red-500 @enderror" placeholder="Adicione quaisquer observações relevantes...">{{ old('observacoes', $financeiro->observacoes) }}</textarea>
                @error('observacoes')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end space-x-4">
                <a href="{{ route('admin.financeiro.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                    <i class="fas fa-times-circle mr-2"></i> Cancelar
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300">
                    <i class="fas fa-sync-alt mr-2"></i> Atualizar Lançamento
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const referenciaTipoSelect = document.getElementById('referencia_tipo');
            const referenciaIdField = document.getElementById('referencia_id_field');

            function toggleReferenciaIdField() {
                if (referenciaTipoSelect.value) {
                    referenciaIdField.classList.remove('hidden');
                } else {
                    referenciaIdField.classList.add('hidden');
                }
            }

            referenciaTipoSelect.addEventListener('change', toggleReferenciaIdField);

            // Garante que o estado inicial está correto ao carregar a página
            toggleReferenciaIdField();
        });
    </script>
@endsection
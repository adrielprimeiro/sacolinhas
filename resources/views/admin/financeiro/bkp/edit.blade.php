@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">Editar Lançamento de Conta Corrente #{{ $financeiro->id }}</div>
                <div class="card-body">
                    <form action="{{ route('admin.financeiro.update', $financeiro->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="data_movimentacao" class="form-label">Data e Hora da Movimentação</label>
                            <input type="datetime-local" class="form-control @error('data_movimentacao') is-invalid @enderror" id="data_movimentacao" name="data_movimentacao" value="{{ old('data_movimentacao', $financeiro->data_movimentacao->format('Y-m-d\TH:i')) }}" required>
                            @error('data_movimentacao')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="tipo_movimentacao" class="form-label">Tipo de Movimentação</label>
                            <select class="form-control @error('tipo_movimentacao') is-invalid @enderror" id="tipo_movimentacao" name="tipo_movimentacao" required>
                                <option value="">Selecione...</option>
                                @foreach ($tiposMovimentacao as $tipo)
                                    <option value="{{ $tipo }}" {{ old('tipo_movimentacao', $financeiro->tipo_movimentacao) == $tipo ? 'selected' : '' }}>
                                        {{ ucfirst($tipo) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tipo_movimentacao')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="valor" class="form-label">Valor</label>
                            <input type="number" step="0.01" class="form-control @error('valor') is-invalid @enderror" id="valor" name="valor" value="{{ old('valor', $financeiro->valor) }}" required>
                            @error('valor')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <input type="text" class="form-control @error('descricao') is-invalid @enderror" id="descricao" name="descricao" value="{{ old('descricao', $financeiro->descricao) }}" required>
                            @error('descricao')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- <--- AJUSTES AQUI --- --}}
                        <div class="mb-3">
                            <label for="classificacao_id" class="form-label">Classificação Financeira</label>
                            <select class="form-control @error('classificacao_id') is-invalid @enderror" id="classificacao_id" name="classificacao_id" required>
                                <option value="">Selecione...</option>
                                @foreach ($classificacoes as $classificacao)
                                    <option value="{{ $classificacao->id }}" {{ old('classificacao_id', $financeiro->classificacao_id) == $classificacao->id ? 'selected' : '' }}>
                                        {{ $classificacao->nome }} ({{ $classificacao->codigo_contabil }})
                                    </option>
                                @endforeach
                            </select>
                            @error('classificacao_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="referencia_tipo" class="form-label">Tipo de Referência (Opcional)</label>
                            <select class="form-control @error('referencia_tipo') is-invalid @enderror" id="referencia_tipo" name="referencia_tipo">
                                <option value="">Nenhum</option>
                                @foreach (['sacolinha', 'pagamento', 'pedido', 'ajuste', 'desconto'] as $refTipo)
                                    <option value="{{ $refTipo }}" {{ old('referencia_tipo', $financeiro->referencia_tipo) == $refTipo ? 'selected' : '' }}>
                                        {{ ucfirst($refTipo) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('referencia_tipo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" id="referencia_id_group" style="{{ old('referencia_tipo', $financeiro->referencia_tipo) ? '' : 'display:none;' }}">
                            <label for="referencia_id" class="form-label">ID da Referência</label>
                            <input type="number" class="form-control @error('referencia_id') is-invalid @enderror" id="referencia_id" name="referencia_id" value="{{ old('referencia_id', $financeiro->referencia_id) }}">
                            @error('referencia_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- --- FIM DOS AJUSTES --- --}}


                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações (Opcional)</label>
                            <textarea class="form-control @error('observacoes') is-invalid @enderror" id="observacoes" name="observacoes" rows="3">{{ old('observacoes', $financeiro->observacoes) }}</textarea>
                            @error('observacoes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-success">Atualizar Lançamento</button>
                        <a href="{{ route('admin.financeiro.index') }}" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const referenciaTipoSelect = document.getElementById('referencia_tipo');
        const referenciaIdGroup = document.getElementById('referencia_id_group');

        function toggleReferenciaIdField() {
            if (referenciaTipoSelect.value && referenciaTipoSelect.value !== '') {
                referenciaIdGroup.style.display = 'block';
            } else {
                referenciaIdGroup.style.display = 'none';
            }
        }

        referenciaTipoSelect.addEventListener('change', toggleReferenciaIdField);

        // Chamar na carga da página para definir o estado inicial
        toggleReferenciaIdField();
    });
</script>
@endsection
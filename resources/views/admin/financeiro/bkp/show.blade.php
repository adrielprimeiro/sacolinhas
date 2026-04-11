@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">Detalhes do Lançamento de Conta Corrente #{{ $financeiro->id }}</div>
                <div class="card-body">
                    <p><strong>ID:</strong> {{ $financeiro->id }}</p>
                    <p><strong>Usuário:</strong> {{ $financeiro->user->name ?? 'N/A' }}</p>
                    <p><strong>Data e Hora:</strong> {{ $financeiro->data_movimentacao->format('d/m/Y H:i') }}</p>
                    <p><strong>Tipo de Movimentação:</strong>
                        <span class="badge bg-{{ $financeiro->tipo_movimentacao === 'credito' ? 'success' : 'danger' }}">
                            {{ ucfirst($financeiro->tipo_movimentacao) }}
                        </span>
                    </p>
                    <p><strong>Valor:</strong> R$ {{ number_format($financeiro->valor, 2, ',', '.') }}</p>
                    <p><strong>Descrição:</strong> {{ $financeiro->descricao }}</p>
                    <p><strong>Saldo Anterior:</strong> R$ {{ number_format($financeiro->saldo_anterior, 2, ',', '.') }}</p>
                    <p><strong>Saldo Atual:</strong> R$ {{ number_format($financeiro->saldo_atual, 2, ',', '.') }}</p>

                    {{-- <--- AJUSTES AQUI --- --}}
                    <p><strong>Classificação Financeira:</strong>
                        @if ($financeiro->classificacaoFinanceira)
                            {{ $financeiro->classificacaoFinanceira->nome }} ({{ $financeiro->classificacaoFinanceira->codigo_contabil }})
                        @else
                            N/A
                        @endif
                    </p>
                    <p><strong>Tipo de Referência:</strong> {{ $financeiro->referencia_tipo ? ucfirst($financeiro->referencia_tipo) : 'N/A' }}</p>
                    <p><strong>ID da Referência:</strong> {{ $financeiro->referencia_id ?? 'N/A' }}</p>
                    {{-- --- FIM DOS AJUSTES --- --}}


                    <p><strong>Observações:</strong> {{ $financeiro->observacoes ?? 'N/A' }}</p>
                    <p><strong>Criado em:</strong> {{ $financeiro->created_at->format('d/m/Y H:i') }}</p>
                    <p><strong>Última Atualização:</strong> {{ $financeiro->updated_at->format('d/m/Y H:i') }}</p>

                    <a href="{{ route('admin.financeiro.edit', $financeiro->id) }}" class="btn btn-warning">Editar</a>
                    <a href="{{ route('admin.financeiro.index') }}" class="btn btn-secondary">Voltar para a Lista</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
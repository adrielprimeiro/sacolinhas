@extends('layouts.app') {{-- Assumindo um layout base --}}

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    Movimentações de Conta Corrente
                    <a href="{{ route('admin.financeiro.create') }}" class="btn btn-primary btn-sm float-end">Novo Lançamento</a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data</th>
                                <th>Descrição</th>
                                <th>Tipo</th>
                                <th>Valor</th>
                                <th>Saldo Anterior</th>
                                <th>Saldo Atual</th>
                                <th>Classificação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($movimentacoes as $movimentacao)
                                <tr>
                                    <td>{{ $movimentacao->id }}</td>
                                    <td>{{ $movimentacao->data_movimentacao->format('d/m/Y H:i') }}</td>
                                    <td>{{ $movimentacao->descricao }}</td>
                                    <td>
                                        <span class="badge bg-{{ $movimentacao->tipo_movimentacao === 'credito' ? 'success' : 'danger' }}">
                                            {{ ucfirst($movimentacao->tipo_movimentacao) }}
                                        </span>
                                    </td>
                                    <td>R$ {{ number_format($movimentacao->valor, 2, ',', '.') }}</td>
                                    <td>R$ {{ number_format($movimentacao->saldo_anterior, 2, ',', '.') }}</td>
                                    <td>R$ {{ number_format($movimentacao->saldo_atual, 2, ',', '.') }}</td>
                                    <td>
                                        @if ($movimentacao->classificacaoFinanceira)
                                            {{ $movimentacao->classificacaoFinanceira->nome }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.financeiro.show', $movimentacao->id) }}" class="btn btn-info btn-sm">Ver</a>
                                        <a href="{{ route('admin.financeiro.edit', $movimentacao->id) }}" class="btn btn-warning btn-sm">Editar</a>
                                        <form action="{{ route('admin.financeiro.destroy', $movimentacao->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este lançamento?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">Nenhum lançamento encontrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-center">
                        {{ $movimentacoes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
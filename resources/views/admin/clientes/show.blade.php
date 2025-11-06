<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Cliente: {{ $cliente->nome_cliente ?? $cliente->name }} - Sacolinhas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>👤 Detalhes do Cliente</h1>
            <div>
                <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary">← Voltar</a>
                <a href="{{ route('admin.clientes.edit', $cliente) }}" class="btn btn-warning">✏️ Editar</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr><td><strong>ID:</strong></td><td>{{ $cliente->id }}</td></tr>
                            <tr><td><strong>Nome:</strong></td><td>{{ $cliente->nome_cliente ?? $cliente->name }}</td></tr>
                            <tr><td><strong>Email:</strong></td><td>{{ $cliente->email }}</td></tr>
                            <tr><td><strong>CPF:</strong></td><td>{{ $cliente->cpf ?? 'Não informado' }}</td></tr>
                            <tr><td><strong>Telefone:</strong></td><td>{{ $cliente->telefone_principal ?? 'Não informado' }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr><td><strong>Data Nascimento:</strong></td><td>{{ $cliente->data_nascimento ? date('d/m/Y', strtotime($cliente->data_nascimento)) : 'Não informado' }}</td></tr>
                            <tr><td><strong>Sexo:</strong></td><td>{{ ucfirst($cliente->sexo) ?? 'Não informado' }}</td></tr>
                            <tr><td><strong>Cadastrado em:</strong></td><td>{{ $cliente->created_at->format('d/m/Y H:i') }}</td></tr>
                            <tr><td><strong>Atualizado em:</strong></td><td>{{ $cliente->updated_at->format('d/m/Y H:i') }}</td></tr>
                            <tr><td><strong>Status:</strong></td><td>
                                @if($cliente->bloqueado)
                                    <span class="badge bg-danger">Bloqueado</span>
                                @else
                                    <span class="badge bg-success">Ativo</span>
                                @endif
                            </td></tr>
                        </table>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <a href="{{ route('admin.clientes.edit', $cliente) }}" class="btn btn-warning">✏️ Editar</a>
                    
                    <form action="{{ route('admin.clientes.toggle_block', $cliente) }}" method="POST" class="d-inline">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-{{ $cliente->bloqueado ? 'success' : 'danger' }}">
                            {{ $cliente->bloqueado ? '🔓 Desbloquear' : '🔒 Bloquear' }}
                        </button>
                    </form>

                    <form action="{{ route('admin.clientes.destroy', $cliente) }}" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Confirmar exclusão?')">
                            🗑️ Excluir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

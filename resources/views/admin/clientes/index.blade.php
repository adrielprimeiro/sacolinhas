<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Clientes - Sacolinhas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>🛒 Admin Clientes</h1>
            <a href="{{ route('admin.clientes.create') }}" class="btn btn-primary">+ Novo Cliente</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        
        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr><th>ID</th><th>Nome</th><th>Email</th><th>Status</th><th>Ações</th></tr>
                    </thead>
                    <tbody>
                        @foreach($clientes as $cliente)
                        <tr>
                            <td>{{ $cliente->id }}</td>
                            <td>{{ $cliente->nome_cliente ?? $cliente->name }}</td>
                            <td>{{ $cliente->email }}</td>
                            <td>
                                @if($cliente->bloqueado)
                                    <span class="badge bg-danger">Bloqueado</span>
                                @else
                                    <span class="badge bg-success">Ativo</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.clientes.show', $cliente) }}" class="btn btn-sm btn-info">Ver</a>
                                <a href="{{ route('admin.clientes.edit', $cliente) }}" class="btn btn-sm btn-warning">Editar</a>
                                <form action="{{ route('admin.clientes.destroy', $cliente) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Confirmar?')">Excluir</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $clientes->links() }}
            </div>
        </div>
    </div>
</body>
</html>

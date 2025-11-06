<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Novo Cliente - Sacolinhas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>➕ Novo Cliente</h1>
            <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary">← Voltar</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.clientes.store') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nome_cliente" class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" name="nome_cliente" 
                                   value="{{ old('nome_cliente') }}" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" 
                                   value="{{ old('email') }}" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Senha *</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">Confirmar Senha *</label>
                            <input type="password" class="form-control" name="password_confirmation" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cpf" class="form-label">CPF</label>
                            <input type="text" class="form-control" name="cpf" 
                                   value="{{ old('cpf') }}">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="telefone_principal" class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="telefone_principal" 
                                   value="{{ old('telefone_principal') }}">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                            <input type="date" class="form-control" name="data_nascimento" 
                                   value="{{ old('data_nascimento') }}">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="sexo" class="form-label">Sexo</label>
                            <select class="form-control" name="sexo">
                                <option value="">Selecione...</option>
                                <option value="masculino" {{ old('sexo') == 'masculino' ? 'selected' : '' }}>Masculino</option>
                                <option value="feminino" {{ old('sexo') == 'feminino' ? 'selected' : '' }}>Feminino</option>
                                <option value="outros" {{ old('sexo') == 'outros' ? 'selected' : '' }}>Outros</option>
                                <option value="prefiro_nao_informar" {{ old('sexo') == 'prefiro_nao_informar' ? 'selected' : '' }}>Prefiro não informar</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="bloqueado" id="bloqueado" 
                                   {{ old('bloqueado') ? 'checked' : '' }}>
                            <label class="form-check-label" for="bloqueado">Cliente Bloqueado</label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">💾 Salvar Cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

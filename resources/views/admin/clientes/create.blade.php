<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Novo Cliente - Sacolinhas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .preview-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar text-white p-0">
                <div class="p-3">
                    <h4><i class="fas fa-shopping-cart me-2"></i>Admin</h4>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('items.index') }}">
                                <i class="fas fa-box"></i> Itens
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('bags.index') }}">
                                <i class="fas fa-broadcast-tower"></i> Live
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white active" href="{{ route('admin.clientes.index') }}">
                                <i class="fas fa-users"></i> Clientes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('admin.sacolinhas.index') }}">
                                <i class="fas fa-shopping-bag"></i> Sacolas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('dashboard') }}">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2><i class="fas fa-user-plus me-2 text-primary"></i>Novo Cliente</h2>
                        <p class="text-muted mb-0">Cadastro rápido de cliente</p>
                    </div>
                    <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </a>
                </div>

                <!-- Alerts -->
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i><strong>Erros encontrados:</strong></h6>
                        <hr class="my-2">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li><strong>{{ $error }}</strong></li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><strong>{{ session('success') }}</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Form Card -->
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('admin.clientes.store') }}" method="POST">
                            @csrf
                            
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="name" class="form-label">Nome</label>
                                    <!-- ✅ MUDANÇA: nome_cliente → name -->
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" 
                                           placeholder="Nome do cliente"
                                           value="{{ old('name') }}">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        Se vazio, usaremos seu Instagram ou TikTok como nome
                                    </small>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Instagram -->
                                <div class="col-md-6 mb-3">
                                    <label for="instagram" class="form-label">
                                        <i class="fab fa-instagram" style="color: #E4405F;"></i> Instagram
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">@</span>
                                        <!-- ✅ MUDANÇA: ig_instagram → instagram -->
                                        <input type="text" class="form-control @error('instagram') is-invalid @enderror" 
                                               id="instagram" name="instagram" 
                                               placeholder="seu_usuario"
                                               value="{{ old('instagram') }}">
                                    </div>
                                    @error('instagram')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Apenas o nome do usuário, sem @</small>
                                </div>

                                <!-- TikTok -->
                                <div class="col-md-6 mb-3">
                                    <label for="tiktok" class="form-label">
                                        <i class="fab fa-tiktok" style="color: #000000;"></i> TikTok
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">@</span>
                                        <!-- ✅ MUDANÇA: ig_tiktok → tiktok -->
                                        <input type="text" class="form-control @error('tiktok') is-invalid @enderror" 
                                               id="tiktok" name="tiktok" 
                                               placeholder="seu_usuario"
                                               value="{{ old('tiktok') }}">
                                    </div>
                                    @error('tiktok')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Apenas o nome do usuário, sem @</small>
                                </div>                        
                            </div>

                            <!-- Preview Box -->
                            <div class="preview-box" id="preview-container" style="display: none;">
                                <h6><i class="fas fa-eye me-2"></i>Preview do Cliente:</h6>
                                <div id="nome-preview"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i>Salvar Cliente
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Preview do nome que será usado
            function atualizarPreview() {
                // ✅ MUDANÇA: nome_cliente → name, ig_instagram → instagram, ig_tiktok → tiktok
                const nome = $('#name').val().trim();
                const instagram = $('#instagram').val().trim();
                const tiktok = $('#tiktok').val().trim();
                
                let nomeUsado = '';
                let emailUsado = '';
                
                // Mostrar preview apenas se algum campo foi preenchido
                if (nome || instagram || tiktok) {
                    $('#preview-container').show();
                    
                    if (nome) {
                        nomeUsado = nome;
                    } else if (instagram) {
                        nomeUsado = instagram;
                    } else if (tiktok) {
                        nomeUsado = tiktok;
                    }
                    
                    emailUsado = nomeUsado.toLowerCase().replace(/[^a-z0-9]/g, '') + '@mania.com';
                    
                    $('#nome-preview').html(
                        '<i class="fas fa-user text-primary"></i> <strong>Nome:</strong> ' + nomeUsado + '<br>' +
                        '<i class="fas fa-envelope text-secondary"></i> <strong>Email:</strong> ' + emailUsado
                    );
                } else {
                    $('#preview-container').hide();
                }
            }
            
            // ✅ MUDANÇA: Atualizar seletores para novos IDs
            $('#name, #instagram, #tiktok').on('input', atualizarPreview);
            
            // Preview inicial
            atualizarPreview();
            
            console.log('✅ Sistema de nome automático ativo!');
        });
    </script>
</body>
</html>

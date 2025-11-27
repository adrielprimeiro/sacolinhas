<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar: {{ $cliente->nome_completo }} - Sacolinhas</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

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

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 0.75rem;
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .avatar-section {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 1rem;
        }

        .form-error {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .text-muted-small {
            font-size: 0.85rem;
            color: #6c757d;
        }

        /* TABS CUSTOMIZAÇÃO */
        .nav-tabs {
            border-bottom: 2px solid #667eea;
            gap: 0.5rem;
        }

        .nav-tabs .nav-link {
            color: #667eea;
            border: none;
            border-bottom: 3px solid transparent;
            border-radius: 8px 8px 0 0;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            color: #764ba2;
            background: rgba(102, 126, 234, 0.1);
        }

        .nav-tabs .nav-link.active {
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom-color: transparent;
        }

        .tab-content {
            padding: 2rem 0;
        }

        .tab-pane {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .social-input {
            position: relative;
        }

        .social-input .form-control {
            padding-left: 2.5rem;
        }

        .social-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            z-index: 5;
        }

        .readonly-field {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }

        .badge-status {
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>
                            <i class="fas fa-edit me-2 text-primary"></i>Editar Cliente
                        </h2>
                        <p class="text-muted mb-0">
                            {{ $cliente->nome_completo }}
                            @if($cliente->codigo_cliente)
                                <span class="badge bg-secondary ms-2">#{{ $cliente->codigo_cliente }}</span>
                            @endif
                            <span class="badge badge-status bg-{{ $cliente->status_class }} ms-1">
                                {{ $cliente->status }}
                            </span>
                        </p>
                    </div>
                    <a href="{{ route('admin.clientes.show', $cliente) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </a>
                </div>

                <!-- Alerts -->
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Erros encontrados:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Form Card -->
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('admin.clientes.update', $cliente) }}" method="POST" id="clientForm">
                            @csrf @method('PUT')
                            
                            <!-- TABS NAVIGATION -->
                            <ul class="nav nav-tabs" id="clientTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="pessoal-tab" data-bs-toggle="tab" data-bs-target="#pessoal" type="button" role="tab">
                                        <i class="fas fa-user me-2"></i>Dados Pessoais
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="contato-tab" data-bs-toggle="tab" data-bs-target="#contato" type="button" role="tab">
                                        <i class="fas fa-phone me-2"></i>Contatos
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="redes-tab" data-bs-toggle="tab" data-bs-target="#redes" type="button" role="tab">
                                        <i class="fas fa-share-alt me-2"></i>Redes Sociais
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="endereco-tab" data-bs-toggle="tab" data-bs-target="#endereco" type="button" role="tab">
                                        <i class="fas fa-map-marker-alt me-2"></i>Endereço
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="documentos-tab" data-bs-toggle="tab" data-bs-target="#documentos" type="button" role="tab">
                                        <i class="fas fa-id-card me-2"></i>Documentos
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="comercial-tab" data-bs-toggle="tab" data-bs-target="#comercial" type="button" role="tab">
                                        <i class="fas fa-shopping-cart me-2"></i>Comercial
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="seguranca-tab" data-bs-toggle="tab" data-bs-target="#seguranca" type="button" role="tab">
                                        <i class="fas fa-lock me-2"></i>Segurança
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="status-tab" data-bs-toggle="tab" data-bs-target="#status" type="button" role="tab">
                                        <i class="fas fa-toggle-on me-2"></i>Status
                                    </button>
                                </li>
                            </ul>

                            <!-- TAB CONTENT -->
                            <div class="tab-content" id="clientTabsContent">

                                <!-- ABA 1: DADOS PESSOAIS -->
                                <div class="tab-pane fade show active" id="pessoal" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">Nome Completo *</label>
                                            <input type="text" 
                                                   class="form-control @error('name') is-invalid @enderror" 
                                                   name="name" 
                                                   id="name"
                                                   value="{{ old('name', $cliente->name) }}" 
                                                   required>
                                            @error('name')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="apelido" class="form-label">Apelido</label>
                                            <input type="text" 
                                                   class="form-control @error('apelido') is-invalid @enderror" 
                                                   name="apelido" 
                                                   id="apelido"
                                                   placeholder="Como prefere ser chamado"
                                                   value="{{ old('apelido', $cliente->apelido) }}">
                                            @error('apelido')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                                            <input type="date" 
                                                   class="form-control @error('data_nascimento') is-invalid @enderror" 
                                                   name="data_nascimento" 
                                                   id="data_nascimento"
                                                   value="{{ old('data_nascimento', $cliente->data_nascimento ? $cliente->data_nascimento->format('Y-m-d') : '') }}">
                                            @error('data_nascimento')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                            @if($cliente->idade)
                                                <small class="text-muted-small">{{ $cliente->idade }} anos</small>
                                            @endif
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="sexo" class="form-label">Sexo</label>
                                            <select class="form-select @error('sexo') is-invalid @enderror" name="sexo" id="sexo">
                                                <option value="">Selecione...</option>
                                                <option value="M" {{ old('sexo', $cliente->sexo) == 'M' ? 'selected' : '' }}>Masculino</option>
                                                <option value="F" {{ old('sexo', $cliente->sexo) == 'F' ? 'selected' : '' }}>Feminino</option>
                                                <option value="Outro" {{ old('sexo', $cliente->sexo) == 'Outro' ? 'selected' : '' }}>Outro</option>
                                            </select>
                                            @error('sexo')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" 
                                                   class="form-control @error('email') is-invalid @enderror" 
                                                   name="email" 
                                                   id="email"
                                                   value="{{ old('email', $cliente->email) }}" 
                                                   required>
                                            @error('email')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- ABA 2: CONTATOS -->
                                <div class="tab-pane fade" id="contato" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="telefone_principal" class="form-label">
                                                <i class="fas fa-phone me-2 text-primary"></i>Telefone Principal
                                            </label>
                                            <input type="text" 
                                                   class="form-control mask-phone @error('telefone_principal') is-invalid @enderror" 
                                                   name="telefone_principal" 
                                                   id="telefone_principal"
                                                   placeholder="(00) 00000-0000"
                                                   value="{{ old('telefone_principal', $cliente->telefone_principal_formatado) }}">
                                            @error('telefone_principal')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="telefone_2" class="form-label">
                                                <i class="fas fa-phone-alt me-2 text-secondary"></i>Telefone Secundário
                                            </label>
                                            <input type="text" 
                                                   class="form-control mask-phone @error('telefone_2') is-invalid @enderror" 
                                                   name="telefone_2" 
                                                   id="telefone_2"
                                                   placeholder="(00) 00000-0000"
                                                   value="{{ old('telefone_2', $cliente->telefone_2_formatado) }}">
                                            @error('telefone_2')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="whatsapp" class="form-label">
                                                <i class="fab fa-whatsapp me-2" style="color: #25D366;"></i>WhatsApp
                                            </label>
                                            <div class="social-input">
                                                <input type="text" 
                                                       class="form-control mask-phone @error('whatsapp') is-invalid @enderror" 
                                                       name="whatsapp" 
                                                       id="whatsapp"
                                                       placeholder="(00) 00000-0000"
                                                       value="{{ old('whatsapp', $cliente->whatsapp_formatado) }}">
                                                @error('whatsapp')
                                                    <div class="form-error">{{ $message }}</div>
                                                @enderror
                                                @if($cliente->whatsapp_url)
                                                    <small class="text-muted-small">
                                                        <a href="{{ $cliente->whatsapp_url }}" target="_blank" class="text-success">
                                                            <i class="fas fa-external-link-alt me-1"></i>Abrir no WhatsApp
                                                        </a>
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ABA 3: REDES SOCIAIS -->
                                <div class="tab-pane fade" id="redes" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="instagram" class="form-label">
                                                <i class="fab fa-instagram me-2" style="color: #E4405F;"></i>Instagram
                                            </label>
                                            <div class="social-input">
                                                <i class="fab fa-instagram social-icon" style="color: #E4405F;"></i>
                                                <input type="text" 
                                                       class="form-control @error('instagram') is-invalid @enderror" 
                                                       name="instagram" 
                                                       id="instagram"
                                                       placeholder="seu_usuario"
                                                       value="{{ old('instagram', $cliente->instagram) }}">
                                                @error('instagram')
                                                    <div class="form-error">{{ $message }}</div>
                                                @enderror
                                                @if($cliente->instagram_url)
                                                    <small class="text-muted-small">
                                                        <a href="{{ $cliente->instagram_url }}" target="_blank" class="text-dark">
                                                            <i class="fas fa-external-link-alt me-1"></i>Ver perfil
                                                        </a>
                                                    </small>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="tiktok" class="form-label">
                                                <i class="fab fa-tiktok me-2" style="color: #000;"></i>TikTok
                                            </label>
                                            <div class="social-input">
                                                <i class="fab fa-tiktok social-icon" style="color: #000;"></i>
                                                <input type="text" 
                                                       class="form-control @error('tiktok') is-invalid @enderror" 
                                                       name="tiktok" 
                                                       id="tiktok"
                                                       placeholder="seu_usuario"
                                                       value="{{ old('tiktok', $cliente->tiktok) }}">
                                                @error('tiktok')
                                                    <div class="form-error">{{ $message }}</div>
                                                @enderror
                                                @if($cliente->tiktok_url)
                                                    <small class="text-muted-small">
                                                        <a href="{{ $cliente->tiktok_url }}" target="_blank" class="text-dark">
                                                            <i class="fas fa-external-link-alt me-1"></i>Ver perfil
                                                        </a>
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="info-box">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Dica:</strong> Digite apenas o nome de usuário, sem @ ou URLs completas.
                                    </div>
                                </div>

                                <!-- ABA 4: ENDEREÇO -->
                                <div class="tab-pane fade" id="endereco" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label for="cep" class="form-label">CEP</label>
                                            <input type="text" 
                                                   class="form-control mask-cep @error('cep') is-invalid @enderror" 
                                                   name="cep" 
                                                   id="cep"
                                                   placeholder="00000-000"
                                                   value="{{ old('cep', $cliente->cep_formatado) }}">
                                            @error('cep')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted-small">Busca automática do endereço</small>
                                        </div>

                                        <div class="col-md-7 mb-3">
                                            <label for="endereco" class="form-label">Endereço</label>
                                            <input type="text" 
                                                   class="form-control @error('endereco') is-invalid @enderror" 
                                                   name="endereco" 
                                                   id="endereco"
                                                   placeholder="Rua, Avenida, etc."
                                                   value="{{ old('endereco', $cliente->endereco) }}">
                                            @error('endereco')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-2 mb-3">
                                            <label for="numero_endereco" class="form-label">Número</label>
                                            <input type="text" 
                                                   class="form-control @error('numero_endereco') is-invalid @enderror" 
                                                   name="numero_endereco" 
                                                   id="numero_endereco"
                                                   placeholder="000"
                                                   value="{{ old('numero_endereco', $cliente->numero_endereco) }}">
                                            @error('numero_endereco')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="complemento" class="form-label">Complemento</label>
                                            <input type="text" 
                                                   class="form-control @error('complemento') is-invalid @enderror" 
                                                   name="complemento" 
                                                   id="complemento"
                                                   placeholder="Apto, Bloco, Casa, etc."
                                                   value="{{ old('complemento', $cliente->complemento) }}">
                                            @error('complemento')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="bairro" class="form-label">Bairro</label>
                                            <input type="text" 
                                                   class="form-control @error('bairro') is-invalid @enderror" 
                                                   name="bairro" 
                                                   id="bairro"
                                                   value="{{ old('bairro', $cliente->bairro) }}">
                                            @error('bairro')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="cidade" class="form-label">Cidade</label>
                                            <input type="text" 
                                                   class="form-control @error('cidade') is-invalid @enderror" 
                                                   name="cidade" 
                                                   id="cidade"
                                                   value="{{ old('cidade', $cliente->cidade) }}">
                                            @error('cidade')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label for="estado" class="form-label">Estado</label>
                                            <select class="form-select @error('estado') is-invalid @enderror" name="estado" id="estado">
                                                <option value="">Selecione...</option>
                                                <option value="AC" {{ old('estado', $cliente->estado) == 'AC' ? 'selected' : '' }}>AC</option>
                                                <option value="AL" {{ old('estado', $cliente->estado) == 'AL' ? 'selected' : '' }}>AL</option>
                                                <option value="AP" {{ old('estado', $cliente->estado) == 'AP' ? 'selected' : '' }}>AP</option>
                                                <option value="AM" {{ old('estado', $cliente->estado) == 'AM' ? 'selected' : '' }}>AM</option>
                                                <option value="BA" {{ old('estado', $cliente->estado) == 'BA' ? 'selected' : '' }}>BA</option>
                                                <option value="CE" {{ old('estado', $cliente->estado) == 'CE' ? 'selected' : '' }}>CE</option>
                                                <option value="DF" {{ old('estado', $cliente->estado) == 'DF' ? 'selected' : '' }}>DF</option>
                                                <option value="ES" {{ old('estado', $cliente->estado) == 'ES' ? 'selected' : '' }}>ES</option>
                                                <option value="GO" {{ old('estado', $cliente->estado) == 'GO' ? 'selected' : '' }}>GO</option>
                                                <option value="MA" {{ old('estado', $cliente->estado) == 'MA' ? 'selected' : '' }}>MA</option>
                                                <option value="MT" {{ old('estado', $cliente->estado) == 'MT' ? 'selected' : '' }}>MT</option>
                                                <option value="MS" {{ old('estado', $cliente->estado) == 'MS' ? 'selected' : '' }}>MS</option>
                                                <option value="MG" {{ old('estado', $cliente->estado) == 'MG' ? 'selected' : '' }}>MG</option>
                                                <option value="PA" {{ old('estado', $cliente->estado) == 'PA' ? 'selected' : '' }}>PA</option>
                                                <option value="PB" {{ old('estado', $cliente->estado) == 'PB' ? 'selected' : '' }}>PB</option>
                                                <option value="PR" {{ old('estado', $cliente->estado) == 'PR' ? 'selected' : '' }}>PR</option>
                                                <option value="PE" {{ old('estado', $cliente->estado) == 'PE' ? 'selected' : '' }}>PE</option>
                                                <option value="PI" {{ old('estado', $cliente->estado) == 'PI' ? 'selected' : '' }}>PI</option>
                                                <option value="RJ" {{ old('estado', $cliente->estado) == 'RJ' ? 'selected' : '' }}>RJ</option>
                                                <option value="RN" {{ old('estado', $cliente->estado) == 'RN' ? 'selected' : '' }}>RN</option>
                                                <option value="RS" {{ old('estado', $cliente->estado) == 'RS' ? 'selected' : '' }}>RS</option>
                                                <option value="RO" {{ old('estado', $cliente->estado) == 'RO' ? 'selected' : '' }}>RO</option>
                                                <option value="RR" {{ old('estado', $cliente->estado) == 'RR' ? 'selected' : '' }}>RR</option>
                                                <option value="SC" {{ old('estado', $cliente->estado) == 'SC' ? 'selected' : '' }}>SC</option>
                                                <option value="SP" {{ old('estado', $cliente->estado) == 'SP' ? 'selected' : '' }}>SP</option>
                                                <option value="SE" {{ old('estado', $cliente->estado) == 'SE' ? 'selected' : '' }}>SE</option>
                                                <option value="TO" {{ old('estado', $cliente->estado) == 'TO' ? 'selected' : '' }}>TO</option>
                                            </select>
                                            @error('estado')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label for="pais" class="form-label">País</label>
                                            <input type="text" 
                                                   class="form-control @error('pais') is-invalid @enderror" 
                                                   name="pais" 
                                                   id="pais"
                                                   value="{{ old('pais', $cliente->pais ?? 'Brasil') }}">
                                            @error('pais')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- ABA 5: DOCUMENTOS -->
                                <div class="tab-pane fade" id="documentos" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="cpf" class="form-label">CPF</label>
                                            <input type="text" 
                                                   class="form-control mask-cpf @error('cpf') is-invalid @enderror" 
                                                   name="cpf" 
                                                   id="cpf"
                                                   placeholder="000.000.000-00"
                                                   value="{{ old('cpf', $cliente->cpf_formatado) }}">
                                            @error('cpf')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="rg" class="form-label">RG</label>
                                            <input type="text" 
                                                   class="form-control @error('rg') is-invalid @enderror" 
                                                   name="rg" 
                                                   id="rg"
                                                   placeholder="00.000.000-0"
                                                   value="{{ old('rg', $cliente->rg) }}">
                                            @error('rg')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- ABA 6: COMERCIAL -->
                                <div class="tab-pane fade" id="comercial" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="codigo_cliente" class="form-label">Código do Cliente</label>
                                            <input type="number" 
                                                   class="form-control readonly-field @error('codigo_cliente') is-invalid @enderror" 
                                                   name="codigo_cliente" 
                                                   id="codigo_cliente"
                                                   value="{{ old('codigo_cliente', $cliente->codigo_cliente) }}"
                                                   readonly>
                                            <small class="text-muted-small">Auto-gerado pelo sistema</small>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="tipo_cliente" class="form-label">Tipo de Cliente</label>
                                            <select class="form-select @error('tipo_cliente') is-invalid @enderror" name="tipo_cliente" id="tipo_cliente">
                                                <option value="">Selecione...</option>
                                                <option value="Premium" {{ old('tipo_cliente', $cliente->tipo_cliente) == 'Premium' ? 'selected' : '' }}>Premium</option>
                                                <option value="Standard" {{ old('tipo_cliente', $cliente->tipo_cliente) == 'Standard' ? 'selected' : '' }}>Standard</option>
                                                <option value="VIP" {{ old('tipo_cliente', $cliente->tipo_cliente) == 'VIP' ? 'selected' : '' }}>VIP</option>
                                                <option value="Bronze" {{ old('tipo_cliente', $cliente->tipo_cliente) == 'Bronze' ? 'selected' : '' }}>Bronze</option>
                                                <option value="Prata" {{ old('tipo_cliente', $cliente->tipo_cliente) == 'Prata' ? 'selected' : '' }}>Prata</option>
                                                <option value="Ouro" {{ old('tipo_cliente', $cliente->tipo_cliente) == 'Ouro' ? 'selected' : '' }}>Ouro</option>
                                            </select>
                                            @error('tipo_cliente')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="total_pedidos" class="form-label">Total de Pedidos</label>
                                            <input type="number" 
                                                   class="form-control readonly-field @error('total_pedidos') is-invalid @enderror" 
                                                   name="total_pedidos" 
                                                   id="total_pedidos"
                                                   value="{{ old('total_pedidos', $cliente->total_pedidos) }}"
                                                   readonly>
                                            <small class="text-muted-small">Atualizado automaticamente</small>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="data_cadastro" class="form-label">Data de Cadastro</label>
                                            <input type="text" 
                                                   class="form-control readonly-field" 
                                                   value="{{ $cliente->data_cadastro ? $cliente->data_cadastro->format('d/m/Y H:i') : '-' }}"
                                                   readonly>
                                            <small class="text-muted-small">{{ $cliente->tempo_com_cliente }}</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="ultima_compra" class="form-label">Última Compra</label>
                                            <input type="text" 
                                                   class="form-control readonly-field" 
                                                   value="{{ $cliente->ultima_compra ? $cliente->ultima_compra->format('d/m/Y H:i') : 'Nunca' }}"
                                                   readonly>
                                            @if($cliente->ultima_compra)
                                                <small class="text-muted-small">{{ $cliente->ultima_compra->diffForHumans() }}</small>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="observacao_cliente" class="form-label">Observações</label>
                                            <textarea class="form-control @error('observacao_cliente') is-invalid @enderror" 
                                                      name="observacao_cliente" 
                                                      id="observacao_cliente"
                                                      rows="4"
                                                      placeholder="Informações adicionais sobre o cliente...">{{ old('observacao_cliente', $cliente->observacao_cliente) }}</textarea>
                                            @error('observacao_cliente')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- ABA 7: SEGURANÇA -->
                                <div class="tab-pane fade" id="seguranca" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="password" class="form-label">Nova Senha</label>
                                            <small class="text-muted-small d-block mb-2">(deixe vazio para manter atual)</small>
                                            <input type="password" 
                                                   class="form-control @error('password') is-invalid @enderror" 
                                                   name="password"
                                                   id="password"
                                                   placeholder="Mínimo 8 caracteres">
                                            @error('password')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="password_confirmation" class="form-label">Confirmar Nova Senha</label>
                                            <small class="text-muted-small d-block mb-2">&nbsp;</small>
                                            <input type="password" 
                                                   class="form-control @error('password_confirmation') is-invalid @enderror" 
                                                   name="password_confirmation"
                                                   id="password_confirmation"
                                                   placeholder="Confirmação da senha">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="role" class="form-label">Função/Papel</label>
                                            <select class="form-select @error('role') is-invalid @enderror" name="role" id="role">
                                                <option value="client" {{ old('role', $cliente->role) == 'client' ? 'selected' : '' }}>Cliente</option>
                                                <option value="admin" {{ old('role', $cliente->role) == 'admin' ? 'selected' : '' }}>Administrador</option>
                                            </select>
                                            @error('role')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="is_admin" class="form-label">Status Admin</label>
                                            <select class="form-select @error('is_admin') is-invalid @enderror" name="is_admin" id="is_admin">
                                                <option value="0" {{ old('is_admin', $cliente->is_admin) == 0 ? 'selected' : '' }}>Não</option>
                                                <option value="1" {{ old('is_admin', $cliente->is_admin) == 1 ? 'selected' : '' }}>Sim</option>
                                            </select>
                                            @error('is_admin')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- ABA 8: STATUS -->
                                <div class="tab-pane fade" id="status" role="tabpanel">
                                    <div class="mb-4">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" 
                                                   class="form-check-input" 
                                                   name="bloqueado" 
                                                   id="bloqueado" 
                                                   value="1"
                                                   {{ old('bloqueado', $cliente->bloqueado) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="bloqueado">
                                                <strong>Cliente Bloqueado</strong>
                                                <small class="text-muted d-block">Desabilita o acesso do cliente à plataforma</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="info-box">
                                        <strong>Status Atual:</strong>
                                        <span class="badge bg-{{ $cliente->status_class }} ms-2">
                                            <i class="fas fa-{{ $cliente->bloqueado ? 'lock' : 'check-circle' }} me-1"></i>
                                            {{ $cliente->status }}
                                        </span>
                                        
                                        @if($cliente->data_cadastro)
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    Cliente desde: {{ $cliente->data_cadastro->format('d/m/Y') }}
                                                    ({{ $cliente->tempo_com_cliente }})
                                                </small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- BOTÕES -->
                            <div class="d-flex justify-content-between gap-2 pt-3 border-top mt-4">
                                <a href="{{ route('admin.clientes.show', $cliente) }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Atualizar Cliente
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
    
    <!-- Máscaras e Funcionalidades -->
    <script>
        // Máscaras para campos
        document.addEventListener('DOMContentLoaded', function() {
            // Máscara para CPF
            const maskCpf = (value) => {
                return value
                    .replace(/\D/g, '')
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d{1,2})/, '$1-$2')
                    .replace(/(-\d{2})\d+?$/, '$1');
            };

            // Máscara para telefone
            const maskPhone = (value) => {
                return value
                    .replace(/\D/g, '')
                    .replace(/(\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{4})(\d)/, '$1-$2')
                    .replace(/(\d{4})-(\d)(\d{4})/, '$1$2-$3')
                    .replace(/(-\d{4})\d+?$/, '$1');
            };

            // Máscara para CEP
            const maskCep = (value) => {
                return value
                    .replace(/\D/g, '')
                    .replace(/(\d{5})(\d)/, '$1-$2')
                    .replace(/(-\d{3})\d+?$/, '$1');
            };

            // Aplicar máscaras
            document.querySelectorAll('.mask-cpf').forEach(input => {
                input.addEventListener('input', e => {
                    e.target.value = maskCpf(e.target.value);
                });
            });

            document.querySelectorAll('.mask-phone').forEach(input => {
                input.addEventListener('input', e => {
                    e.target.value = maskPhone(e.target.value);
                });
            });

            document.querySelectorAll('.mask-cep').forEach(input => {
                input.addEventListener('input', e => {
                    e.target.value = maskCep(e.target.value);
                });
            });

            // Busca CEP
            document.getElementById('cep')?.addEventListener('blur', function() {
                const cep = this.value.replace(/\D/g, '');
                
                if (cep.length === 8) {
                    fetch(`https://viacep.com.br/ws/${cep}/json/`)
                        .then(response => response.json())
                        .then(data => {
                            if (!data.erro) {
                                document.getElementById('endereco').value = data.logradouro;
                                document.getElementById('bairro').value = data.bairro;
                                document.getElementById('cidade').value = data.localidade;
                                document.getElementById('estado').value = data.uf;
                            }
                        })
                        .catch(error => console.error('Erro ao buscar CEP:', error));
                }
            });

            // Remover @ das redes sociais automaticamente
            document.getElementById('instagram')?.addEventListener('input', function() {
                this.value = this.value.replace('@', '');
            });

            document.getElementById('tiktok')?.addEventListener('input', function() {
                this.value = this.value.replace('@', '');
            });
        });
    </script>
</body>
</html>
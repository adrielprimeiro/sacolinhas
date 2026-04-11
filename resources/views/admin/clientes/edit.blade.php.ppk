<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar: {{ $cliente->name }} - Sacolinhas</title>

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
                        <p class="text-muted mb-0">{{ $cliente->name }}</p>
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
                        <form action="{{ route('admin.clientes.update', $cliente) }}" method="POST">
                            @csrf @method('PUT')
                            
                            <!-- TABS NAVIGATION -->
                            <ul class="nav nav-tabs" id="clientTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="pessoal-tab" data-bs-toggle="tab" data-bs-target="#pessoal" type="button" role="tab">
                                        <i class="fas fa-user me-2"></i>Dados Pessoais
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="redes-tab" data-bs-toggle="tab" data-bs-target="#redes" type="button" role="tab">
                                        <i class="fas fa-share-alt me-2"></i>Redes Sociais
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="contato-tab" data-bs-toggle="tab" data-bs-target="#contato" type="button" role="tab">
                                        <i class="fas fa-phone me-2"></i>Contato
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
                                            <!-- CORREÇÃO: name="name" para salvar na coluna 'name' do BD -->
                                            <input type="text" 
                                                   class="form-control @error('name') is-invalid @enderror" 
                                                   name="name" 
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
                                                   value="{{ old('apelido', $cliente->apelido) }}">
                                            @error('apelido')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!--<div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                                            <input type="date" 
                                                   class="form-control @error('data_nascimento') is-invalid @enderror" 
                                                   name="data_nascimento" 
                                                   value="{{ old('data_nascimento', $cliente->data_nascimento ? \Carbon\Carbon::parse($cliente->data_nascimento)->format('Y-m-d') : '') }}">
                                            @error('data_nascimento')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="sexo" class="form-label">Sexo</label>
                                            <select class="form-select @error('sexo') is-invalid @enderror" name="sexo">
                                                <option value="">Selecione...</option>
                                                <option value="M" {{ old('sexo', $cliente->sexo) == 'M' ? 'selected' : '' }}>Masculino</option>
                                                <option value="F" {{ old('sexo', $cliente->sexo) == 'F' ? 'selected' : '' }}>Feminino</option>
                                                <option value="Outro" {{ old('sexo', $cliente->sexo) == 'Outro' ? 'selected' : '' }}>Outro</option>
                                            </select>
                                            @error('sexo')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                    </div>-->
                                </div>
								
								<!-- ABA 2: REDES SOCIAIS -->
                                <div class="tab-pane fade" id="redes" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="remember_token" class="form-label">
                                                <i class="fab fa-instagram me-2" style="color: #E4405F;"></i>Instagram
                                            </label>
                                            <!-- CORREÇÃO: name="remember_token" para salvar na coluna 'remember_token' do BD -->
                                            <input type="text" 
                                                   class="form-control @error('remember_token') is-invalid @enderror" 
                                                   name="remember_token" 
                                                   placeholder="@seu_usuario_instagram"
                                                   value="{{ old('remember_token', $cliente->remember_token) }}">
                                            @error('remember_token')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted-small">Usuário do Instagram</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="nome_cliente" class="form-label">
                                                <i class="fab fa-tiktok me-2" style="color: #000;"></i>TikTok
                                            </label>
                                            <!-- CORREÇÃO: name="nome_cliente" para salvar na coluna 'nome_cliente' do BD -->
                                            <input type="text" 
                                                   class="form-control @error('nome_cliente') is-invalid @enderror" 
                                                   name="nome_cliente" 
                                                   id="nome_cliente"
                                                   placeholder="@seu_usuario_tiktok"
                                                   value="{{ old('nome_cliente', $cliente->nome_cliente) }}">
                                            @error('nome_cliente')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted-small">Usuário do TikTok</small>
                                        </div>
                                    </div>
								</div>	

								<!-- ABA 3: CONTATO -->
                                <div class="tab-pane fade" id="contato" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" 
                                                   class="form-control @error('email') is-invalid @enderror" 
                                                   name="email" 
                                                   value="{{ old('email', $cliente->email) }}" 
                                                   required>
                                            @error('email')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="telefone_principal" class="form-label">Telefone Principal</label>
                                            <input type="text" 
                                                   class="form-control @error('telefone_principal') is-invalid @enderror" 
                                                   name="telefone_principal" 
                                                   placeholder="(00) 00000-0000"
                                                   value="{{ old('telefone_principal', $cliente->telefone_principal) }}">
                                            @error('telefone_principal')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                   
										<div class="col-md-6 mb-3">
											<label for="telefone_2" class="form-label">Telefone Secundário</label>
											<input type="text" 
												   class="form-control @error('telefone_2') is-invalid @enderror" 
												   name="telefone_2" 
												   placeholder="(00) 00000-0000"
												   value="{{ old('telefone_2', $cliente->telefone_2) }}">
											@error('telefone_2')
												<div class="form-error">{{ $message }}</div>
											@enderror
										</div>

										<div class="col-md-6 mb-3">
											<label for="phone" class="form-label">Telefone (Adicional)</label>
											<input type="text" 
												   class="form-control @error('phone') is-invalid @enderror" 
												   name="phone" 
												   placeholder="(00) 00000-0000"
												   value="{{ old('phone', $cliente->phone) }}">
											@error('phone')
												<div class="form-error">{{ $message }}</div>
											@enderror
										</div>
                                    </div>
								</div>
								

                                <!-- ABA 3: DOCUMENTOS -->
                                <div class="tab-pane fade" id="documentos" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="cpf" class="form-label">CPF</label>
                                            <input type="text" 
                                                   class="form-control @error('cpf') is-invalid @enderror" 
                                                   name="cpf" 
                                                   placeholder="000.000.000-00"
                                                   value="{{ old('cpf', $cliente->cpf) }}">
                                            @error('cpf')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="rg" class="form-label">RG</label>
                                            <input type="text" 
                                                   class="form-control @error('rg') is-invalid @enderror" 
                                                   name="rg" 
                                                   placeholder="00.000.000-0"
                                                   value="{{ old('rg', $cliente->rg) }}">
                                            @error('rg')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
        
                          
                                <!-- ABA 4: ENDEREÇO -->
                                <div class="tab-pane fade" id="endereco" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-9 mb-3">
                                            <label for="endereco" class="form-label">Endereço</label>
                                            <input type="text" 
                                                   class="form-control @error('endereco') is-invalid @enderror" 
                                                   name="endereco" 
                                                   placeholder="Rua, Avenida, etc."
                                                   value="{{ old('endereco', $cliente->endereco) }}">
                                            @error('endereco')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label for="numero_endereco" class="form-label">Número</label>
                                            <input type="text" 
                                                   class="form-control @error('numero_endereco') is-invalid @enderror" 
                                                   name="numero_endereco" 
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
                                                   placeholder="Apto, Bloco, etc."
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
                                                   value="{{ old('bairro', $cliente->bairro) }}">
                                            @error('bairro')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-5 mb-3">
                                            <label for="cidade" class="form-label">Cidade</label>
                                            <input type="text" 
                                                   class="form-control @error('cidade') is-invalid @enderror" 
                                                   name="cidade" 
                                                   value="{{ old('cidade', $cliente->cidade) }}">
                                            @error('cidade')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-2 mb-3">
                                            <label for="estado" class="form-label">Estado</label>
                                            <input type="text" 
                                                   class="form-control @error('estado') is-invalid @enderror" 
                                                   name="estado" 
                                                   placeholder="SP"
                                                   maxlength="2"
                                                   value="{{ old('estado', $cliente->estado) }}">
                                            @error('estado')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label for="cep" class="form-label">CEP</label>
                                            <input type="text" 
                                                   class="form-control @error('cep') is-invalid @enderror" 
                                                   name="cep" 
                                                   placeholder="00000-000"
                                                   value="{{ old('cep', $cliente->cep) }}">
                                            @error('cep')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-2 mb-3">
                                            <label for="pais" class="form-label">País</label>
                                            <input type="text" 
                                                   class="form-control @error('pais') is-invalid @enderror" 
                                                   name="pais" 
                                                   value="{{ old('pais', $cliente->pais ?? 'Brasil') }}">
                                            @error('pais')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- ABA 5: COMERCIAL -->
                                <div class="tab-pane fade" id="comercial" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label for="codigo_cliente" class="form-label">Código do Cliente</label>
                                            <input type="number" 
                                                   class="form-control @error('codigo_cliente') is-invalid @enderror" 
                                                   name="codigo_cliente" 
                                                   value="{{ old('codigo_cliente', $cliente->codigo_cliente) }}">
                                            <small class="text-muted-small">Auto-gerado pelo sistema</small>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label for="tipo_cliente" class="form-label">Tipo de Cliente</label>
                                            <input type="text" 
                                                   class="form-control @error('tipo_cliente') is-invalid @enderror" 
                                                   name="tipo_cliente" 
                                                   placeholder="Ex: Premium, Standart"
                                                   value="{{ old('tipo_cliente', $cliente->tipo_cliente) }}">
                                            @error('tipo_cliente')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label for="total_pedidos" class="form-label">Total de Pedidos</label>
                                            <input type="number" 
                                                   class="form-control @error('total_pedidos') is-invalid @enderror" 
                                                   name="total_pedidos" 
                                                   value="{{ old('total_pedidos', $cliente->total_pedidos) }}"
                                                   readonly>
                                            <small class="text-muted-small">Somente leitura</small>
                                        </div>
<!--
                                        <div class="col-md-3 mb-3">
                                            <label for="ultima_compra" class="form-label">Última Compra</label>
                                            <input type="datetime-local" 
                                                   class="form-control @error('ultima_compra') is-invalid @enderror" 
                                                   name="ultima_compra" 
                                                   value="{{ old('ultima_compra', $cliente->ultima_compra ? \Carbon\Carbon::parse($cliente->ultima_compra)->format('Y-m-d\TH:i') : '') }}"
                                                   readonly>
                                            <small class="text-muted-small">Somente leitura</small>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="ultima_visita" class="form-label">Última Visita</label>
                                            <input type="datetime-local" 
                                                   class="form-control @error('ultima_visita') is-invalid @enderror" 
                                                   name="ultima_visita" 
                                                   value="{{ old('ultima_visita', $cliente->ultima_visita ? \Carbon\Carbon::parse($cliente->ultima_visita)->format('Y-m-d\TH:i') : '') }}"
                                                   readonly>
                                            <small class="text-muted-small">Somente leitura</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="data_cadastro" class="form-label">Data de Cadastro</label>
                                            <input type="datetime-local" 
                                                   class="form-control" 
                                                   value="{{ $cliente->data_cadastro ? \Carbon\Carbon::parse($cliente->data_cadastro)->format('d/m/Y') : '-' }}"
                                                   readonly>
                                            <small class="text-muted-small">Somente leitura</small>
                                        </div>
                                    </div>
-->
										<div class="row">	
											<div class="col-md-12 mb-3">
												<label for="observacao_cliente" class="form-label">Observações</label>
												<textarea class="form-control @error('observacao_cliente') is-invalid @enderror" 
														  name="observacao_cliente" 
														  rows="4"
														  placeholder="Informações adicionais sobre o cliente...">{{ old('observacao_cliente', $cliente->observacao_cliente) }}</textarea>
												@error('observacao_cliente')
													<div class="form-error">{{ $message }}</div>
												@enderror
											</div>
										</div>
									</div>
                                </div>

                                <!-- ABA 6: SEGURANÇA -->
                                <div class="tab-pane fade" id="seguranca" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="password" class="form-label">Nova Senha</label>
                                            <small class="text-muted-small d-block mb-2">(deixe vazio para manter atual)</small>
                                            <input type="password" 
                                                   class="form-control @error('password') is-invalid @enderror" 
                                                   name="password"
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
                                                   placeholder="Confirmação da senha">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="role" class="form-label">Função/Papel</label>
                                            <select class="form-select @error('role') is-invalid @enderror" name="role">
                                                <option value="client" {{ old('role', $cliente->role) == 'client' ? 'selected' : '' }}>Cliente</option>
                                                <option value="admin" {{ old('role', $cliente->role) == 'admin' ? 'selected' : '' }}>Administrador</option>
                                            </select>
                                            @error('role')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="is_admin" class="form-label">Status Admin</label>
                                            <select class="form-select @error('is_admin') is-invalid @enderror" name="is_admin">
                                                <option value="0" {{ old('is_admin', $cliente->is_admin) == 0 ? 'selected' : '' }}>Não</option>
                                                <option value="1" {{ old('is_admin', $cliente->is_admin) == 1 ? 'selected' : '' }}>Sim</option>
                                            </select>
                                            @error('is_admin')
                                                <div class="form-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
<!--
                                    <div class="mb-3">
                                        <label for="email_verified_at" class="form-label">Email Verificado em</label>
                                        <input type="datetime-local" 
                                               class="form-control @error('email_verified_at') is-invalid @enderror" 
                                               name="email_verified_at" 
                                               value="{{ old('email_verified_at', $cliente->email_verified_at ? \Carbon\Carbon::parse($cliente->email_verified_at)->format('Y-m-d\TH:i') : '') }}">
                                        @error('email_verified_at')
                                            <div class="form-error">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
-->								

                                <!-- ABA 7: STATUS -->
                                <div class="tab-pane fade" id="status" role="tabpanel">
                                    <div class="mb-4">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" 
                                                   class="form-check-input" 
                                                   name="bloqueado" 
                                                   id="bloqueado" 
                                                   {{ old('bloqueado', $cliente->bloqueado) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="bloqueado">
                                                <strong>Cliente Bloqueado</strong>
                                                <small class="text-muted d-block">Desabilita o acesso do cliente à plataforma</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="info-box">
                                        <strong>Status Atual:</strong>
                                        @if($cliente->bloqueado)
                                            <span class="badge bg-danger">
                                                <i class="fas fa-lock me-1"></i>Bloqueado
                                            </span>
                                        @else
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>Ativo
                                            </span>
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
</body>
</html>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cliente: {{ $cliente->nome_completo }} - Sacolinhas</title>

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

        .info-table {
            margin-bottom: 0;
        }

        .info-table td {
            padding: 0.75rem;
            border-top: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .info-table td:first-child {
            font-weight: 600;
            color: #495057;
            width: 200px;
        }

        .badge-status {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }

        .social-link {
            display: inline-block;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            text-decoration: none;
            color: white;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .social-link:hover {
            text-decoration: none;
            color: white;
            transform: translateY(-1px);
        }

        .social-instagram {
            background: linear-gradient(45deg, #f58529, #dd2a7b, #8134af, #515bd4);
        }

        .social-whatsapp {
            background: #25D366;
        }

        .social-tiktok {
            background: #000;
        }

        .empty-info {
            color: #6c757d;
            font-style: italic;
        }

        .section-divider {
            border-top: 2px solid #e9ecef;
            margin: 1.5rem 0;
            padding-top: 1rem;
        }

        .avatar-simple {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            margin-right: 1rem;
            float: left;
        }

        .client-header {
            overflow: hidden;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .client-name {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
        }

        .client-subtitle {
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .action-btn {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
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
                        <h2><i class="fas fa-user-circle me-2 text-primary"></i>Detalhes do Cliente</h2>
                        <p class="text-muted mb-0">Visualização completa dos dados</p>
                    </div>
                    <div>
                        <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary action-btn">
                            <i class="fas fa-arrow-left me-2"></i>Voltar
                        </a>
                        <a href="{{ route('admin.clientes.edit', $cliente) }}" class="btn btn-warning action-btn">
                            <i class="fas fa-edit me-2"></i>Editar
                        </a>
                    </div>
                </div>

                <!-- Alerts -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- CARD PRINCIPAL -->
                <div class="card">
                    <div class="card-body">
                        <!-- Header do Cliente -->
                        <div class="client-header">
                            <div class="avatar-simple">
                                {{ strtoupper(substr($cliente->nome_completo, 0, 1)) }}
                            </div>
                            <div>
                                <h3 class="client-name">{{ $cliente->nome_completo }}</h3>
                                @if($cliente->apelido)
                                    <p class="client-subtitle">"{{ $cliente->apelido }}"</p>
                                @endif
                                <div>
                                    @if($cliente->codigo_cliente)
                                        <span class="badge bg-secondary me-2">#{{ $cliente->codigo_cliente }}</span>
                                    @endif
                                    <span class="badge badge-status bg-{{ $cliente->status_class }}">
                                        <i class="fas fa-{{ $cliente->bloqueado ? 'lock' : 'check-circle' }} me-1"></i>
                                        {{ $cliente->status }}
                                    </span>
                                    @if($cliente->tipo_cliente)
                                        <span class="badge bg-success ms-1">{{ $cliente->tipo_cliente }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Tabela de Informações -->
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table info-table">
                                    <tr>
                                        <td><i class="fas fa-id-card me-2 text-primary"></i>ID:</td>
                                        <td>{{ $cliente->id }}</td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-envelope me-2 text-primary"></i>Email:</td>
                                        <td>
                                            <a href="mailto:{{ $cliente->email }}" class="text-decoration-none">
                                                {{ $cliente->email }}
                                            </a>
                                        </td>
                                    </tr>
                                    @if($cliente->cpf)
                                        <tr>
                                            <td><i class="fas fa-id-card me-2 text-secondary"></i>CPF:</td>
                                            <td>{{ $cliente->cpf_formatado }}</td>
                                        </tr>
                                    @endif
                                    @if($cliente->rg)
                                        <tr>
                                            <td><i class="fas fa-id-badge me-2 text-secondary"></i>RG:</td>
                                            <td>{{ $cliente->rg }}</td>
                                        </tr>
                                    @endif
                                    @if($cliente->data_nascimento)
                                        <tr>
                                            <td><i class="fas fa-birthday-cake me-2 text-info"></i>Nascimento:</td>
                                            <td>
                                                {{ $cliente->data_nascimento->format('d/m/Y') }}
                                                @if($cliente->idade)
                                                    <small class="text-muted">({{ $cliente->idade }} anos)</small>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                    @if($cliente->sexo)
                                        <tr>
                                            <td><i class="fas fa-venus-mars me-2 text-warning"></i>Sexo:</td>
                                            <td>
                                                @switch($cliente->sexo)
                                                    @case('M') Masculino @break
                                                    @case('F') Feminino @break
                                                    @case('Outro') Outro @break
                                                    @default {{ $cliente->sexo }}
                                                @endswitch
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            </div>

                            <div class="col-md-6">
                                <table class="table info-table">
                                    <tr>
                                        <td><i class="fas fa-calendar-plus me-2 text-primary"></i>Cadastro:</td>
                                        <td>
                                            {{ $cliente->data_cadastro ? $cliente->data_cadastro->format('d/m/Y H:i') : $cliente->created_at->format('d/m/Y H:i') }}
                                            <small class="text-muted d-block">{{ $cliente->tempo_com_cliente }}</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-clock me-2 text-secondary"></i>Atualização:</td>
                                        <td>
                                            {{ $cliente->updated_at->format('d/m/Y H:i') }}
                                            <small class="text-muted d-block">{{ $cliente->updated_at->diffForHumans() }}</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-shopping-cart me-2 text-success"></i>Total Pedidos:</td>
                                        <td>
                                            <strong class="text-primary">{{ $cliente->total_pedidos }}</strong>
                                        </td>
                                    </tr>
                                    @if($cliente->ultima_compra)
                                        <tr>
                                            <td><i class="fas fa-shopping-bag me-2 text-success"></i>Última Compra:</td>
                                            <td>
                                                {{ $cliente->ultima_compra->format('d/m/Y') }}
                                                <small class="text-muted d-block">{{ $cliente->ultima_compra->diffForHumans() }}</small>
                                            </td>
                                        </tr>
                                    @endif
                                    @if($cliente->ultima_visita)
                                        <tr>
                                            <td><i class="fas fa-eye me-2 text-info"></i>Última Visita:</td>
                                            <td>
                                                {{ $cliente->ultima_visita->format('d/m/Y H:i') }}
                                                <small class="text-muted d-block">{{ $cliente->ultima_visita->diffForHumans() }}</small>
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                        </div>

                        <!-- SEÇÃO: CONTATOS -->
                        <div class="section-divider">
                            <h5 class="mb-3"><i class="fas fa-phone me-2 text-primary"></i>Contatos</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table info-table">
                                        @if($cliente->telefone_principal)
                                            <tr>
                                                <td><i class="fas fa-phone me-2 text-primary"></i>Principal:</td>
                                                <td>
                                                    <a href="tel:{{ $cliente->telefone_principal }}" class="text-decoration-none">
                                                        {{ $cliente->telefone_principal_formatado }}
                                                    </a>
                                                </td>
                                            </tr>
                                        @endif
                                        @if($cliente->telefone_2)
                                            <tr>
                                                <td><i class="fas fa-phone-alt me-2 text-secondary"></i>Secundário:</td>
                                                <td>
                                                    <a href="tel:{{ $cliente->telefone_2 }}" class="text-decoration-none">
                                                        {{ $cliente->telefone_2_formatado }}
                                                    </a>
                                                </td>
                                            </tr>
                                        @endif
                                        @if($cliente->whatsapp)
                                            <tr>
                                                <td><i class="fab fa-whatsapp me-2" style="color: #25D366;"></i>WhatsApp:</td>
                                                <td>
                                                    <a href="{{ $cliente->whatsapp_url }}" target="_blank" class="text-decoration-none" style="color: #25D366;">
                                                        {{ $cliente->whatsapp_formatado }}
                                                        <i class="fas fa-external-link-alt ms-1"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endif
                                        @if(!$cliente->telefone_principal && !$cliente->telefone_2 && !$cliente->whatsapp)
                                            <tr>
                                                <td colspan="2" class="empty-info">
                                                    <i class="fas fa-phone-slash me-2"></i>Nenhum telefone cadastrado
                                                </td>
                                            </tr>
                                        @endif
                                    </table>
                                </div>

                                <div class="col-md-6">
                                    <strong class="d-block mb-2"><i class="fas fa-share-alt me-2 text-primary"></i>Redes Sociais:</strong>
                                    @if($cliente->temInstagram() || $cliente->temTikTok())
                                        <div>
                                            @if($cliente->temInstagram())
                                                <a href="{{ $cliente->instagram_url }}" target="_blank" class="social-link social-instagram">
                                                    <i class="fab fa-instagram me-1"></i>{{ $cliente->instagram }}
                                                </a>
                                            @endif

                                            @if($cliente->temTikTok())
                                                <a href="{{ $cliente->tiktok_url }}" target="_blank" class="social-link social-tiktok">
                                                    <i class="fab fa-tiktok me-1"></i>{{ $cliente->tiktok }}
                                                </a>
                                            @endif
                                        </div>
                                    @else
                                        <p class="empty-info mb-0">
                                            <i class="fas fa-share-alt me-2"></i>Nenhuma rede social cadastrada
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- SEÇÃO: ENDEREÇO -->
                        @if($cliente->endereco || $cliente->cidade)
                            <div class="section-divider">
                                <h5 class="mb-3"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Endereço</h5>
                                <table class="table info-table">
                                    @if($cliente->endereco)
                                        <tr>
                                            <td><i class="fas fa-road me-2 text-primary"></i>Endereço:</td>
                                            <td>
                                                {{ $cliente->endereco }}
                                                @if($cliente->numero_endereco), {{ $cliente->numero_endereco }}@endif
                                                @if($cliente->complemento) - {{ $cliente->complemento }}@endif
                                            </td>
                                        </tr>
                                    @endif
                                    @if($cliente->bairro)
                                        <tr>
                                            <td><i class="fas fa-map me-2 text-secondary"></i>Bairro:</td>
                                            <td>{{ $cliente->bairro }}</td>
                                        </tr>
                                    @endif
                                    @if($cliente->cidade)
                                        <tr>
                                            <td><i class="fas fa-city me-2 text-info"></i>Cidade/Estado:</td>
                                            <td>
                                                {{ $cliente->cidade }}
                                                @if($cliente->estado), {{ $cliente->estado }}@endif
                                            </td>
                                        </tr>
                                    @endif
                                    @if($cliente->cep)
                                        <tr>
                                            <td><i class="fas fa-mail-bulk me-2 text-warning"></i>CEP:</td>
                                            <td>{{ $cliente->cep_formatado }}</td>
                                        </tr>
                                    @endif
                                    @if($cliente->pais && $cliente->pais !== 'Brasil')
                                        <tr>
                                            <td><i class="fas fa-globe me-2 text-success"></i>País:</td>
                                            <td>{{ $cliente->pais }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                        @endif

                        <!-- SEÇÃO: OBSERVAÇÕES -->
                        @if($cliente->observacao_cliente)
                            <div class="section-divider">
                                <h5 class="mb-3"><i class="fas fa-sticky-note me-2 text-primary"></i>Observações</h5>
                                <div class="p-3 bg-light rounded">
                                    <p class="mb-0">{{ $cliente->observacao_cliente }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- AÇÕES -->
                        <div class="section-divider">
                            <h5 class="mb-3"><i class="fas fa-cogs me-2 text-primary"></i>Ações</h5>
                            <div>
                                <a href="{{ route('admin.clientes.edit', $cliente) }}" class="btn btn-warning action-btn">
                                    <i class="fas fa-edit me-2"></i>Editar Cliente
                                </a>
                                
                                <form action="{{ route('admin.clientes.toggle_block', $cliente) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-{{ $cliente->bloqueado ? 'success' : 'danger' }} action-btn">
                                        <i class="fas fa-{{ $cliente->bloqueado ? 'unlock' : 'lock' }} me-2"></i>
                                        {{ $cliente->bloqueado ? 'Desbloquear' : 'Bloquear' }}
                                    </button>
                                </form>

                                <form action="{{ route('admin.clientes.destroy', $cliente) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger action-btn" onclick="return confirm('⚠️ Confirmar exclusão?\n\nEsta ação não pode ser desfeita.')">
                                        <i class="fas fa-trash me-2"></i>Excluir
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
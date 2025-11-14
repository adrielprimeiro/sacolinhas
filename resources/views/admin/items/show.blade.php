<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $item->nome_do_produto }} - Sacolinhas</title> {{-- Atualizado para nome_do_produto --}}

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
        .item-image {
            max-width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 15px;
        }
        .info-label {
            font-weight: 600;
            color: #6c757d;
        }
        .price-display {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar text-white p-0">
                <div class="p-3">
                    <h4>🛒 Admin</h4>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white active" href="{{ route('items.index') }}">
                                <i class="fas fa-box"></i> Itens
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/dashboard">
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
                        <h2>{{ $item->nome_do_produto }}</h2> {{-- Atualizado para nome_do_produto --}}
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('items.index') }}">Itens</a>
                                </li>
                                <li class="breadcrumb-item active">{{ $item->nome_do_produto }}</li> {{-- Atualizado para nome_do_produto --}}
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <a href="{{ route('items.edit', $item) }}" class="btn btn-warning me-2">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                    </div>
                </div>

                <div class="row">
                    <!-- Imagem -->
                    <div class="col-md-5 mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                @if($item->image)
                                    <img src="{{ asset('storage/' . $item->image) }}"
                                         alt="{{ $item->nome_do_produto }}" {{-- Atualizado para nome_do_produto --}}
                                         class="item-image">
                                @else
                                    <div class="d-flex align-items-center justify-content-center bg-light"
                                         style="height: 300px; border-radius: 15px;">
                                        <div class="text-center">
                                            <i class="fas fa-image fa-4x text-muted mb-3"></i>
                                            <p class="text-muted">Sem imagem</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Informações -->
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-body">
                                <!-- Status e Categoria -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge bg-info fs-6">{{ $item->codigo ?? 'N/A' }}</span> {{-- Atualizado para codigo_da_categoria --}}
                                    @if($item->status == 'indisponivel') {{-- Ajustado para os novos status --}}
                                        <span class="badge bg-danger fs-6">Indisponível</span>
									@elseif($item->status == 'disponivel')
                                        <span class="badge bg-success text-dark fs-6">Disponível</span>	
	                                @elseif($item->status == 'reservado')
                                        <span class="badge bg-warning text-dark fs-6">Reservado</span>
                                    @elseif($item->status == 'vendido')
                                        <span class="badge bg-warning fs-6">Vendido</span>
                                    @elseif($item->status == 'em_sacolinha')
                                        <span class="badge bg-primary fs-6">Em Sacolinha</span>
                                    @else
                                        <span class="badge bg-secondary fs-6">{{ $item->status }}</span>
                                    @endif
                                </div>

                                <!-- Preço -->
                                <div class="mb-4">
                                    <span class="info-label">Preço:</span>
                                    <div class="price-display">{{ $item->formatted_price }}</div>
                                </div>

                                <!-- Descrição -->
                                <div class="mb-4">
                                    <span class="info-label">Descrição:</span>
                                    <p class="mt-2">{{ $item->descricao }}</p> {{-- Atualizado para descricao --}}
                                </div>

                                <hr> {{-- Separador para os novos detalhes --}}

                                <!-- Novos Detalhes do Item -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <span class="info-label">Código:</span>
                                        <p>{{ $item->codigo }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="info-label">Custo:</span>
                                        <p>R\$ {{ number_format($item->custo, 2, ',', '.') }}</p>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <span class="info-label">Marca:</span>
                                        <p>{{ $item->marca ?? 'Não informado' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="info-label">Modelo:</span>
                                        <p>{{ $item->modelo ?? 'Não informado' }}</p>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <span class="info-label">Estado:</span>
                                        <p>{{ ucfirst($item->estado ?? 'Não informado') }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="info-label">Cor:</span>
                                        <p>{{ $item->cor ?? 'Não informado' }}</p>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <span class="info-label">Tamanho:</span>
                                        <p>{{ $item->tamanho ?? 'Não informado' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="info-label">Pedido:</span>
                                        <p>{{ $item->pedido ?? 'Não informado' }}</p>
                                    </div>
                                </div>

                                <hr> {{-- Separador --}}

                                <!-- Informações de Tempo -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <span class="info-label">Criado em:</span>
                                        <p>{{ $item->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="info-label">Atualizado em:</span>
                                        <p>{{ $item->updated_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>

                                <!-- Ações -->
                                <div class="d-flex gap-2 mt-4">
                                    <a href="{{ route('items.edit', $item) }}" class="btn btn-warning">
                                        <i class="fas fa-edit"></i> Editar Item
                                    </a>
                                    <form action="{{ route('items.destroy', $item) }}"
                                          method="POST"
                                          style="display: inline;"
                                          onsubmit="return confirm('Tem certeza que deseja deletar este item?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-trash"></i> Deletar Item
                                        </button>
                                    </form>
                                </div>
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
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventário</title>

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
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

		#qr-scanner-container {
			border-radius: 10px;
			overflow: hidden;
		}
		
		#qr-scanner-container video {
			border-radius: 10px;
			max-width: 100%;
			height: auto;
		}
		
		/* Customizar controles do scanner */
		#qr-scanner-container select,
		#qr-scanner-container input[type="range"] {
			margin: 10px 0;
			width: 100%;
		}
		
		/* Animação para o resultado */
		#qr-result .alert {
			animation: fadeInUp 0.5s ease-out;
		}
		
		@keyframes fadeInUp {
			from {
				opacity: 0;
				transform: translateY(20px);
			}
			to {
				opacity: 1;
				transform: translateY(0);
			}
		}
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar text-white p-0">
                <div class="p-3">
                    <h4> Admin</h4>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('items.index') }}">
                                <i class="fas fa-box"></i> Itens
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('bags.index') }}"> <!-- Novo item no menu -->
                                <i class="fas fa-broadcast-tower"></i> Live
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('admin.sacolinhas.index') }}"> <!-- Atualizado para a nova rota -->
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
                    <h2>Inventário</h2>
                    <a href="{{ route('items.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Novo Item
                    </a>
                </div>

                <!-- Alerts -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
								
				<!-- Filtros com QR Code - Sprint 1 -->
				<div class="card mb-4">
					<div class="card-body">
						<form method="GET" action="{{ route('items.index') }}">
							<div class="row">
								<div class="col-md-4">
									<div class="input-group">
										<input type="text"
											   class="form-control"
											   name="search"
											   id="searchInput"
											   placeholder="Buscar por código do produto ou escaneie o QR Code..."
											   value="{{ request('search') }}">
										<button type="button" 
												class="btn btn-outline-secondary"
												id="qrScanBtn"
												title="Escanear QR Code">
											<i class="fas fa-qrcode"></i>
										</button>
									</div>
									<small class="text-success mt-1">
										<i class="fas fa-camera"></i> 
										<strong>Scanner QR ativo!</strong> Clique no ícone para escanear códigos
									</small>
								</div>
							 
								<div class="col-md-2">
									<button type="submit" class="btn btn-outline-primary w-100">
										<i class="fas fa-search"></i> Filtrar
									</button>
								</div>
							</div>
						</form>
						
						<!-- Modal QR Scanner - Sprint 2 -->
				<div class="modal fade" id="qrModal" tabindex="-1">
					<div class="modal-dialog modal-dialog-centered modal-lg">
						<div class="modal-content">
							<div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
								<h5 class="modal-title">
									<i class="fas fa-qrcode me-2"></i>
									Scanner QR Code - Live
								</h5>
								<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
							</div>
							<div class="modal-body text-center">
								<div id="qr-reader" style="width: 100%; min-height: 300px; border: 2px dashed #dee2e6; border-radius: 15px; overflow: hidden;">
									<!-- Scanner será inicializado aqui -->
									<div class="d-flex align-items-center justify-content-center h-100 p-4">
										<div class="text-muted">
											<i class="fas fa-camera fa-3x mb-3" style="color: #667eea;"></i>
											<h6>Aguardando inicialização da câmera...</h6>
										</div>
									</div>
								</div>
								<div id="qr-result" class="mt-3">
									<!-- Resultados aparecerão aqui -->
								</div>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
									<i class="fas fa-times"></i> Fechar
								</button>
								<div class="text-muted small">
									<i class="fas fa-info-circle"></i>
									O scanner fechará automaticamente após ler um QR Code
								</div>
							</div>
						</div>
					</div>
				</div>

                <!-- Lista de Itens -->
                <div class="card">
                    <div class="card-body">
                        @if($items->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Imagem</th>
                                            <th>Nome do Produto</th> {{-- Atualizado --}}
                                            <th>Marca</th> {{-- Nova coluna --}}
                                            <th>Cor</th> {{-- Nova coluna --}}
                                            <th>Tamanho</th> {{-- Nova coluna --}}
                                            <th>Estado</th> {{-- Nova coluna --}}
                                            <th>Preço</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $item)
                                            <tr>
                                                <td>
                                                    @if($item->image)
                                                        <img src="{{ asset('storage/' . $item->image) }}"
                                                             alt="{{ $item->nome_do_produto }}" {{-- Atualizado --}}
                                                             class="item-image">
                                                    @else
                                                        <div class="item-image bg-secondary d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-image text-white"></i>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <strong>{{ $item->nome_do_produto }}</strong><br> {{-- Atualizado --}}
                                                    <small class="text-muted">{{ Str::limit($item->descricao, 50) }}</small> {{-- Atualizado --}}
                                                </td>
                                                <td>{{ $item->marca ?? 'N/A' }}</td> {{-- Nova coluna --}}
                                                <td>{{ $item->cor ?? 'N/A' }}</td> {{-- Nova coluna --}}
                                                <td>{{ $item->tamanho ?? 'N/A' }}</td> {{-- Nova coluna --}}
                                                <td>{{ ucfirst($item->estado ?? 'N/A') }}</td> {{-- Nova coluna, com ucfirst para formatar --}}
                                                <td>
                                                    <strong class="text-success">R$ {{ number_format($item->preco, 2, ',', '.') }}</strong> {{-- Atualizado para $item->preco --}}
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('items.show', $item) }}"
                                                           class="btn btn-sm btn-outline-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('items.edit', $item) }}"
                                                           class="btn btn-sm btn-outline-warning">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('items.destroy', $item) }}"
                                                              method="POST"
                                                              style="display: inline;"
                                                              onsubmit="return confirm('Tem certeza que deseja deletar este item?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginação -->
                            <div class="d-flex justify-content-center mt-4">
                                {{ $items->links() }}
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Nenhum item encontrado</h5>
                                <p class="text-muted">Comece criando seu primeiro item!</p>
                                <a href="{{ route('items.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Criar Primeiro Item
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Html5-QRCode Library - Sprint 2 -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    
    <!-- QR Scanner JS - Sprint 2 -->
    <script src="{{ asset('js/qr-scanner.js') }}"></script>
    
    <!-- QR Scanner JS - Sprint 1 -->
    <script>
        console.log('QR Scanner carregado - Sprint 1');

        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM carregado, inicializando QR Scanner...');
            
            // Verificar se botão QR existe
            const qrBtn = document.getElementById('qrScanBtn');
            const qrModal = document.getElementById('qrModal');
            
            if (qrBtn && qrModal) {
                // Bootstrap modal instance
                const modalInstance = new bootstrap.Modal(qrModal);
                
                qrBtn.addEventListener('click', function() {
                    console.log('Botão QR clicado - abrindo modal');
                    modalInstance.show();
                    
                    // Placeholder para Sprint 1
                    document.getElementById('qr-result').innerHTML = 
                        '<div class="alert alert-info">' +
                        '<i class="fas fa-info-circle me-2"></i>' +
                        '<strong>Sprint 1:</strong> Estrutura criada com sucesso!' +
                        '</div>';
                });
                
                console.log('QR Scanner inicializado com sucesso!');
            } else {
                console.warn('Elementos QR não encontrados na página');
            }
        });
    </script>
</body>
</html>
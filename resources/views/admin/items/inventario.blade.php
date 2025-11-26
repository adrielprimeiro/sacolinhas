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

        
        /* Modal responsivo */
        @media (max-width: 992px) {
            .modal-xl {
                max-width: 95%;
            }
        }
        
        /* Scanner container */
        #qr-scanner-container {
            border-radius: 15px;
            overflow: hidden;
            background: #f8f9fa;
        }
        
        #qr-scanner-container video {
            width: 100% !important;
            height: auto !important;
            border-radius: 15px;
        }
        
        /* Controles do scanner */
        #qr-scanner-container select,
        #qr-scanner-container input[type="range"] {
            margin: 8px 0;
            width: 100%;
            border-radius: 8px;
        }
        
        /* Botões do scanner */
        #qr-scanner-container button {
            margin: 5px;
            border-radius: 8px;
            font-size: 12px;
        }
        
        /* Histórico scrollable */
        #scan-history {
            font-size: 0.85em;
        }
        
        #scan-history code {
            font-size: 0.8em;
            background: #f1f3f4;
            padding: 2px 4px;
            border-radius: 3px;
        }
        
        /* Animações */
        .alert {
            animation: slideInFromTop 0.5s ease-out;
        }
        
        @keyframes slideInFromTop {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Badge personalizado */
        .badge {
            font-size: 0.7em;
        }
        
        /* Cards do painel lateral */
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: 600;
            font-size: 0.9em;
        }
        
        /* Spinner customizado */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
        
        /* Botão QR com animação */
        #qrScanBtn:hover {
            transform: scale(1.05);
            transition: transform 0.2s ease;
        }

        /* Status Badges Customizados */
        .badge-status-disponivel { background-color: #198754; }
        .badge-status-vendido { background-color: #dc3545; }
        .badge-status-reservado { background-color: #ffc107; color: #000; }
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
                            <a class="nav-link text-white" href="{{ route('bags.index') }}">
                                <i class="fas fa-broadcast-tower"></i> Live
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
                    <h2>Inventário</h2>
                </div>

                <!-- Alerts -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                                
                <!-- Filtros com QR Code -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="{{ route('inventario') }}">
                            <div class="row align-items-end">
                                <!-- CAMPO STATUS (2 colunas) -->
								<div class="col-md-2">
									<label class="form-label small">Status para Aplicar</label> <!-- Mudei o label para ficar claro -->
									<select name="status" class="form-control">
										<option value="">Apenas Buscar</option>
										
										<option value="disponivel" 
											{{ (session('last_status') ?? request('status')) == 'disponivel' ? 'selected' : '' }}>
											Disponível
										</option>
										
										<option value="vendido" 
											{{ (session('last_status') ?? request('status')) == 'vendido' ? 'selected' : '' }}>
											Vendido
										</option>
										
										<option value="reservado" 
											{{ (session('last_status') ?? request('status')) == 'reservado' ? 'selected' : '' }}>
											Reservado
										</option>
										
										<option value="estoque" 
											{{ (session('last_status') ?? request('status')) == 'estoque' ? 'selected' : '' }}>
											Estoque
										</option>
										
										<option value="sacolinha" 
											{{ (session('last_status') ?? request('status')) == 'sacolinha' ? 'selected' : '' }}>
											Sacolinha
										</option>

										<option value="indisponivel" 
											{{ (session('last_status') ?? request('status')) == 'indisponivel' ? 'selected' : '' }}>
											Indisponível
										</option>
										
									</select>
								</div>

                                <!-- CAMPO BUSCA COM QR (6 colunas) -->
                                <div class="col-md-6">
                                    <label class="form-label small">Buscar produto</label>
                                    <div class="input-group">
                                        <input type="text"
                                               class="form-control"
                                               name="search"
                                               id="searchInput"
                                               placeholder="Código do produto, QR Code, ou código de barras..."
                                               value="{{ request('search') }}">
                                        <button type="button" 
                                                class="btn btn-outline-primary position-relative"
                                                id="qrScanBtn"
                                                title="Scanner Multi-formato">
                                            <i class="fas fa-qrcode"></i>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success" style="font-size: 0.6em;">
                                                v3
                                            </span>
                                        </button>
                                    </div>
                                </div>

                                <!-- BOTÃO FILTRAR (2 colunas) -->
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                </div>

                                <!-- BOTÃO LIMPAR (2 colunas) -->
                                <div class="col-md-2">
									<a href="{{ route('inventario', ['reset' => 1]) }}" class="btn btn-outline-secondary w-100" title="Reiniciar lista a partir de agora">
										<i class="fas fa-refresh"></i> Limpar / Reiniciar
                                    </a>
                                </div>
                            </div>
                        </form>
                                        
                        <!-- Modal QR Scanner SIMPLES -->
                        <div class="modal fade" id="qrModal" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title">
                                            <i class="fas fa-qrcode me-2"></i> Scanner QR Code
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <!-- Scanner Container -->
                                        <div id="qr-reader" style="min-height: 300px; border-radius: 15px;"></div>
                                        
                                        <!-- Resultado -->
                                        <div id="qr-result" class="mt-3"></div>
                                    </div>
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
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="80">Imagem</th>
                                            <th>Nome do Produto</th>
                                            <th>Marca</th>
                                            <th>Cor</th>
                                            <th>Tamanho</th>
                                            <th>Estado</th>
                                            <th>Preço</th>
                                            <th width="150" class="text-center">Status Atual</th> <!-- COLUNA MODIFICADA -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $item)
                                            <tr>
                                                <td>
                                                    @if($item->image)
                                                        <img src="{{ asset('storage/' . $item->image) }}"
                                                             alt="{{ $item->nome_do_produto }}"
                                                             class="item-image">
                                                    @else
                                                        <div class="item-image bg-secondary d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-image text-white"></i>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <strong>{{ $item->nome_do_produto }}</strong><br>
                                                    @if($item->codigo)
                                                        <small class="text-muted"><i class="fas fa-barcode"></i> {{ $item->codigo }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ $item->marca ?? 'N/A' }}</td>
                                                <td>{{ $item->cor ?? 'N/A' }}</td>
                                                <td>{{ $item->tamanho ?? 'N/A' }}</td>
                                                <td>{{ ucfirst($item->estado ?? 'N/A') }}</td>
                                                <td>
                                                    <strong class="text-success">`R$ {{ number_format($item->preco, 2, ',', '.') }}`</strong>
                                                </td>
                                                <!-- COLUNA STATUS (SUBSTITUINDO AÇÕES) -->
                                                <td class="text-center">
                                                    @php
                                                        $statusClass = match($item->status) {
                                                            'disponivel' => 'badge-status-disponivel',
                                                            'vendido' => 'badge-status-vendido',
                                                            'reservado' => 'badge-status-reservado',
                                                            default => 'bg-secondary'
                                                        };
                                                        
                                                        $statusIcon = match($item->status) {
                                                            'disponivel' => 'fa-check-circle',
                                                            'vendido' => 'fa-shopping-bag',
                                                            'reservado' => 'fa-clock',
                                                            default => 'fa-question-circle'
                                                        };
                                                    @endphp
                                                    
                                                    <div class="d-flex flex-column align-items-center">
                                                        <span class="badge {{ $statusClass }} p-2 mb-1 w-100">
                                                            <i class="fas {{ $statusIcon }} me-1"></i>
                                                            {{ ucfirst($item->status) }}
                                                        </span>
                                                        <small class="text-muted" style="font-size: 0.75em;">
                                                            <i class="fas fa-history"></i> {{ $item->updated_at->format('d/m H:i') }}
                                                        </small>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginação -->
                            <div class="d-flex justify-content-center mt-4">
                                {{ $items->appends(request()->query())->links() }}
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
    
    <!-- Html5-QRCode Library -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    
    <!-- Scanner QR SIMPLES E COMPATÍVEL -->
    <script>
        console.log('📱 Scanner QR Inventário Iniciando...');

        // Variáveis globais
        var scanner = null;
        var isScanning = false;
        var modal = null;

        // Inicializar quando página carregar
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Inicializando scanner no inventário...');
            initScanner();
            initEnterKey(); // 🎯 NOVO: Inicializar tecla Enter
        });

        function initScanner() {
            // Elementos
            var scanBtn = document.getElementById('qrScanBtn');
            var modalElement = document.getElementById('qrModal');
            
            if (!scanBtn || !modalElement) {
                console.warn('⚠️ Elementos não encontrados');
                return;
            }

            // Modal Bootstrap
            modal = new bootstrap.Modal(modalElement);
            
            // Evento do botão principal
            scanBtn.addEventListener('click', function() {
                console.log('🎯 Abrindo scanner no inventário...');
                openScanner();
            });

            // Fechar modal
            modalElement.addEventListener('hidden.bs.modal', function() {
                stopScanner();
            });

            console.log('✅ Scanner inventário inicializado!');
        }

        // 🎯 NOVA FUNÇÃO: Detectar Enter no campo de busca
        function initEnterKey() {
            var searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' || e.keyCode === 13) {
                        e.preventDefault();
                        console.log('⌨️ Enter pressionado - acionando filtro...');
                        submitForm();
                    }
                });
                console.log('✅ Tecla Enter configurada!');
            }
        }

        function openScanner() {
            modal.show();
            
            // Interface inicial
            var qrReader = document.getElementById('qr-reader');
            if (qrReader) {
                qrReader.innerHTML = 
                    '<div class="text-center p-5">' +
                        '<div class="mb-4">' +
                            '<i class="fas fa-camera fa-4x text-primary"></i>' +
                        '</div>' +
                        '<h4 class="mb-3">Scanner Pronto</h4>' +
                        '<p class="text-muted">Vai automaticamente filtrar após capturar</p>' +
                        '<button class="btn btn-success btn-lg" onclick="startCamera()">' +
                            '<i class="fas fa-play me-2"></i>Iniciar Câmera' +
                        '</button>' +
                    '</div>';
            }

            // Esconder painel lateral se existir
            var sidebar = document.querySelector('.col-lg-4');
            var mainPanel = document.querySelector('.col-lg-8');
            
            if (sidebar) sidebar.style.display = 'none';
            if (mainPanel) mainPanel.className = 'col-12';
        }

        function startCamera() {
            console.log('📷 Iniciando câmera no inventário...');
            
            // Loading
            var qrReader = document.getElementById('qr-reader');
            if (qrReader) {
                qrReader.innerHTML = 
                    '<div class="text-center p-5">' +
                        '<div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>' +
                        '<h5>Iniciando câmera...</h5>' +
                        '<small class="text-muted">Aguarde um momento</small>' +
                    '</div>';
            }

            // Inicializar scanner
            scanner = new Html5Qrcode("qr-reader");
            
            var config = {
                fps: 10,
                qrbox: { width: 250, height: 250 }
            };

            scanner.start(
                { facingMode: "environment" },
                config,
                function(decodedText) {
                    // SUCESSO - Código encontrado
                    console.log('✅ Código encontrado no inventário:', decodedText);
                    onScanSuccess(decodedText);
                },
                function(error) {
                    // Erro silencioso (normal durante escaneamento)
                }
            ).then(function() {
                // Scanner iniciado com sucesso
                isScanning = true;
                showMessage('success', '📷 Scanner ativo! Aponte para o código...');
                
            }).catch(function(error) {
                // Erro ao iniciar
                console.error('❌ Erro ao iniciar:', error);
                var errorMsg = 'Erro ao acessar câmera';
                
                if (error.toString().indexOf('Permission') !== -1) {
                    errorMsg = 'Permissão de câmera negada. Permita o acesso à câmera.';
                } else if (error.toString().indexOf('NotFound') !== -1) {
                    errorMsg = 'Nenhuma câmera encontrada no dispositivo.';
                }
                
                showMessage('danger', '❌ ' + errorMsg);
            });
        }

        function onScanSuccess(code) {
            console.log('🎯 Código capturado no inventário:', code);
            
            // Parar scanner primeiro
            stopScanner();
            
            // Preencher campo de busca
            var searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.value = code;
                searchInput.readOnly = false;
            }

            // Mostrar sucesso
            showMessage('success', 
                '<div class="text-center">' +
                    '<i class="fas fa-check-circle fa-2x text-success mb-2"></i>' +
                    '<h5>Código Capturado!</h5>' +
                    '<code class="fs-5 text-primary">' + code + '</code>' +
                    '<p class="mt-2 mb-0"><small>Processando automaticamente...</small></p>' +
                '</div>'
            );

            // 🎯 AGUARDAR 1 SEGUNDO E SUBMETER AUTOMATICAMENTE
            setTimeout(function() {
                modal.hide();
                console.log('🚀 Auto-submetendo formulário após QR...');
                submitForm();
            }, 1000);
        }

        // 🎯 NOVA FUNÇÃO CENTRALIZADA: Submeter formulário
        function submitForm() {
            var form = document.querySelector('form[method="GET"]');
            if (form) {
                console.log('📝 Submetendo formulário automaticamente...');
                form.submit();
            } else {
                console.warn('⚠️ Formulário não encontrado');
            }
        }

        function stopScanner() {
            if (scanner && isScanning) {
                scanner.stop().then(function() {
                    scanner.clear();
                    isScanning = false;
                    console.log('⏹️ Scanner parado');
                }).catch(function(error) {
                    console.error('Erro ao parar scanner:', error);
                });
            }
        }

        function showMessage(type, message) {
            var resultDiv = document.getElementById('qr-result');
            if (resultDiv) {
                resultDiv.innerHTML = 
                    '<div class="alert alert-' + type + ' mt-3">' +
                        message +
                    '</div>';
            }
        }

        // Log final
        console.log('✅ Scanner QR Inventário com Auto-Submit carregado!');
    </script>
</body>
</html>
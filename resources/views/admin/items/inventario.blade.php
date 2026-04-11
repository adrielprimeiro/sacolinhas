<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventário</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
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
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 15px 15px;
        }

        /* ZXing Scanner Styles - OTIMIZADO */
        #video-scanner {
            width: 100% !important;
            max-width: 500px;
            height: auto !important;
            border-radius: 15px;
            transform: scaleX(-1); /* Espelhar para melhor UX */
            background: #000;
        }
        
        .scanner-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 300px;
            background: #f8f9fa;
            border-radius: 15px;
            position: relative;
        }
        
        .scanner-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10;
            background: rgba(255,255,255,0.9);
            padding: 20px;
            border-radius: 10px;
        }
        
        .scanner-overlay {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 20;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 0.9em;
        }
        
        /* Animações otimizadas */
        .alert {
            animation: slideInFromTop 0.4s ease-out;
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
        
        /* QR Button com animação - DESTAQUE */
        #qrScanBtn {
            transition: all 0.3s ease;
            font-size: 1.2em !important;
            min-height: 120px;
            padding: 2rem 4rem !important;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
		
		@media (max-width: 768px) {
			#qrScanBtn {
				min-height: 600px;  /* Altura específica para mobile */
			}
		}		
        
        #qrScanBtn:hover {
            transform: scale(1.08) translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }
        
        /* Status Badges */
        .badge-status-disponivel { background-color: #198754; }
        .badge-status-vendido { background-color: #dc3545; }
        .badge-status-reservado { background-color: #ffc107; color: #000; }
        .badge-status-estoque { background-color: #0d6efd; }
        .badge-status-sacolinha { background-color: #6f42c1; }
        .badge-status-indisponivel { background-color: #6c757d; }
        
        /* Mobile responsivo */
        @media (max-width: 768px) {
            #video-scanner {
                max-width: 100%;
            }
            
            .modal-lg {
                max-width: 95%;
            }

            #qrScanBtn {
                width: 100% !important;
                margin-bottom: 1rem;
            }
        }
        
        /* Loading spinner personalizado */
        .spinner-zxing {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Header Section -->
    <div class="header-section">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="fas fa-box me-2"></i>
                        Inventário
                    </h1>
                    <small class="badge bg-light text-dark mt-2">
                        <i class="fas fa-rocket"></i> ZXing v2.0
                    </small>
                </div>
                <div>
                    <a href="{{ route('dashboard') }}" class="btn btn-light btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>
                        Voltar para Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4">
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
                        
        <!-- Filtros com QR Code ZXing - LAYOUT MODIFICADO -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('inventario') }}" id="inventory-form">
                    <!-- 1. BOTÃO QR DESTAQUE NA PARTE SUPERIOR (MAIOR) -->
                    <div class="row mb-4">
                        <div class="col-12 text-center">
                            <button type="button" 
                                    class="btn btn-primary btn-lg w-100 w-md-auto" 
                                    id="qrScanBtn"
                                    title="Scanner ZXing - Ultra Rápido"
                                    style="max-width: 350px;">
                                <i class="fas fa-qrcode me-2 fa-lg"></i>
                                <span>ESCANEIE O CÓDIGO</span>
                                <span class="badge bg-success ms-2" style="font-size: 0.7em;">ZX</span>
                            </button>
                        </div>
                    </div>

                    <!-- 2. SELEÇÃO DE STATUS LOGO ABAIXO -->
                    <div class="row align-items-end mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Status para Aplicar</label>
                            <select name="status" class="form-control form-control-lg">
                                <option value="">Apenas Buscar</option>
							    <option value="indisponivel" {{ old('status', $item->status) == 'indisponivel' ? 'selected' : '' }}>Indisponível</option>
								<option value="disponivel" {{ old('status', $item->status) == 'disponivel' ? 'selected' : '' }}>Disponível</option>
								<option value="reservado" {{ old('status', $item->status) == 'reservado' ? 'selected' : '' }}>Reservado</option>
								<option value="vendido" {{ old('status', $item->status) == 'vendido' ? 'selected' : '' }}>Vendido</option>
								<option value="em_sacolinha" {{ old('status', $item->status) == 'em_sacolinha' ? 'selected' : '' }}>Em Sacolinha</option>
								<option value="loja" {{ old('status', $item->status) == 'loja' ? 'selected' : '' }}>Loja</option>
								<option value="estoque" {{ old('status', $item->status) == 'estoque' ? 'selected' : '' }}>Estoque</option>
								<option value="live" {{ old('status', $item->status) == 'live' ? 'selected' : '' }}>Live</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Buscar produto</label>
                            <input type="text"
                                   class="form-control form-control-lg"
                                   name="search"
                                   id="searchInput"
                                   placeholder="Código, QR Code, código de barras..."
                                   value="{{ request('search') }}"
                                   autocomplete="off">
                        </div>
                    </div>

                    <!-- 3. BOTÕES FILTRAR E LIMPAR (MENOR NO FINAL) -->
                    <div class="row">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="fas fa-search me-1"></i>
                                Filtrar
                            </button>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('inventario', ['reset' => 1]) }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-refresh me-1"></i>
                                Limpar
                            </a>
                        </div>
                    </div>
                </form>
                
                <!-- Modal ZXing Scanner - ULTRA SIMPLES (mantido igual) -->
                <div class="modal fade" id="qrModal" tabindex="-1" data-bs-backdrop="static">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-qrcode me-2"></i> Scanner ZXing
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="closeModal"></button>
                            </div>
                            <div class="modal-body p-0">
                                <!-- Scanner Container -->
                                <div class="scanner-container">
                                    <video id="video-scanner" style="display: none;"></video>
                                    
                                    <!-- Loading inicial -->
                                    <div id="scanner-loading" class="scanner-loading">
                                        <div class="text-center">
                                            <div class="spinner-zxing mb-3"></div>
                                            <h6>Iniciando Scanner ZXing...</h6>
                                            <small class="text-muted">Aguarde um momento</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Overlay de status -->
                                    <div id="scanner-overlay" class="scanner-overlay" style="display: none;">
                                        <i class="fas fa-camera me-2"></i>
                                        Aponte para o código...
                                    </div>
                                </div>
                                
                                <!-- Resultado -->
                                <div id="scanner-result" class="p-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Itens (mantida igual) -->
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
                                    <th width="150" class="text-center">Status Atual</th>
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
                                            <strong class="text-success">R$ {{ number_format($item->preco, 2, ',', '.') }}</strong>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $statusClass = match($item->status) {
                                                    'disponivel' => 'badge-status-disponivel',
                                                    'vendido' => 'badge-status-vendido',
                                                    'reservado' => 'badge-status-reservado',
                                                    'estoque' => 'badge-status-estoque',
                                                    'sacolinha' => 'badge-status-sacolinha',
                                                    'indisponivel' => 'badge-status-indisponivel',
                                                    default => 'bg-secondary'
                                                };
                                                
                                                $statusIcon = match($item->status) {
                                                    'disponivel' => 'fa-check-circle',
                                                    'vendido' => 'fa-shopping-bag',
                                                    'reservado' => 'fa-clock',
                                                    'estoque' => 'fa-boxes',
                                                    'sacolinha' => 'fa-shopping-bag',
                                                    'indisponivel' => 'fa-times-circle',
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- ZXing Library - MUITO MAIS RÁPIDO -->
    <script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.18.6/umd/index.min.js"></script>

    <!-- Scanner ZXing - IMPLEMENTAÇÃO ULTRA RÁPIDA (mantido igual) -->
    <script>
    let codeReader = null;
    let scanning = false;
    let modal = null;
    let videoElement = null;

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof ZXing !== 'undefined') {
            console.log('✅ ZXing carregado!');
            initZXingScanner();
        }
        initEnterKey();
    });

    function initZXingScanner() {
        const scanBtn = document.getElementById('qrScanBtn');
        const modalElement = document.getElementById('qrModal');
        const closeBtn = document.getElementById('closeModal');
        
        if (!scanBtn || !modalElement) return;

        try {
            codeReader = new ZXing.BrowserQRCodeReader();
            modal = new bootstrap.Modal(modalElement);
            videoElement = document.getElementById('video-scanner');
            
            // Otimização Canvas (elimina warning)
            if (videoElement) {
                videoElement.setAttribute('willReadFrequently', 'true');
            }
            
            scanBtn.addEventListener('click', requestCameraPermission);
            if (closeBtn) closeBtn.addEventListener('click', closeScanner);
            
            // OTIMIZAÇÃO: Eventos sem auto-start
            modalElement.addEventListener('shown.bs.modal', function() {
                // Delay para evitar múltiplos starts
                setTimeout(startZXingCamera, 500);
            });
            modalElement.addEventListener('hidden.bs.modal', stopZXingScanner);

            console.log('✅ ZXing otimizado inicializado!');
        } catch (error) {
            console.error('❌ Erro:', error);
        }
    }

    async function requestCameraPermission() {
        console.log('🎥 Solicitando permissão...');
        
        try {
            // Verificar se já tem permissão
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                } 
            });
            
            // Parar stream temporário
            stream.getTracks().forEach(track => track.stop());
            
            console.log('✅ Permissão OK!');
            openZXingScanner();
            
        } catch (error) {
            console.error('❌ Permissão negada:', error);
            showPermissionError();
        }
    }

    function openZXingScanner() {
        console.log('📱 Abrindo scanner otimizado...');
        
        // Reset UI
        resetScannerUI();
        
        // Abrir modal
        modal.show();
    }

    function resetScannerUI() {
        const loading = document.getElementById('scanner-loading');
        const video = document.getElementById('video-scanner');
        const overlay = document.getElementById('scanner-overlay');
        const result = document.getElementById('scanner-result');
        
        if (loading) loading.style.display = 'block';
        if (video) {
            video.style.display = 'none';
            // OTIMIZAÇÃO: Parar vídeo se já estiver rodando
            video.pause();
            video.currentTime = 0;
        }
        if (overlay) overlay.style.display = 'none';
        if (result) result.innerHTML = '';
    }

    async function startZXingCamera() {
        if (scanning || !codeReader) {
            console.log('⚠️ Scanner já ativo ou não disponível');
            return;
        }
        
        console.log('📷 Iniciando câmera traseira...');
        
        try {
            scanning = true;
            
            const videoInputDevices = await codeReader.getVideoInputDevices();
            console.log('📹 Câmeras encontradas:', videoInputDevices.length);
            
            // Listar todas as câmeras para debug
            videoInputDevices.forEach((device, index) => {
                console.log(`📷 Câmera ${index}:`, device.label, '| ID:', device.deviceId);
            });
            
            // MÚLTIPLAS ESTRATÉGIAS para encontrar câmera traseira
            let selectedDeviceId = null;
            
            // Estratégia 1: Procurar por palavras-chave (mais específicas)
            const backCamera = videoInputDevices.find(device => {
                const label = device.label.toLowerCase();
                return (
                    label.includes('back') ||
                    label.includes('rear') ||
                    label.includes('environment') ||
                    label.includes('world') ||
                    label.includes('traseira') ||
                    label.includes('principal') ||
                    // Padrões específicos de fabricantes
                    label.includes('camera2 0') ||  // Android padrão
                    label.includes('0, facing back') ||
                    label.includes('back camera') ||
                    !label.includes('front') && !label.includes('user') && !label.includes('face')
                );
            });
            
            if (backCamera) {
                selectedDeviceId = backCamera.deviceId;
                console.log('✅ Câmera traseira encontrada:', backCamera.label);
            } else {
                // Estratégia 2: Se não encontrou, usar constraints do navegador
                console.log('⚠️ Câmera traseira não encontrada por label, tentando facingMode...');
                
                try {
                    // Tentar forçar câmera traseira via constraints
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: { 
                            facingMode: { exact: 'environment' }  // Força câmera traseira
                        }
                    });
                    
                    // Pegar o device ID do stream ativo
                    const track = stream.getVideoTracks()[0];
                    const settings = track.getSettings();
                    selectedDeviceId = settings.deviceId;
                    
                    console.log('✅ Câmera traseira forçada via facingMode:', settings);
                    
                    // Parar stream temporário
                    stream.getTracks().forEach(t => t.stop());
                    
                } catch (facingError) {
                    console.warn('⚠️ Não conseguiu forçar facingMode:', facingError);
                    
                    // Estratégia 3: Usar a última câmera (geralmente é a traseira)
                    if (videoInputDevices.length > 1) {
                        selectedDeviceId = videoInputDevices[videoInputDevices.length - 1].deviceId;
                        console.log('🎯 Usando última câmera (provavelmente traseira)');
                    } else {
                        selectedDeviceId = videoInputDevices[0]?.deviceId;
                        console.log('🎯 Usando única câmera disponível');
                    }
                }
            }
            
            if (!selectedDeviceId) {
                throw new Error('Nenhuma câmera disponível');
            }
            
            console.log('🎯 ID da câmera selecionada:', selectedDeviceId);
            
            // Mostrar vídeo
            if (videoElement) {
                videoElement.style.display = 'block';
            }
            
            // Esconder loading
            const loading = document.getElementById('scanner-loading');
            if (loading) loading.style.display = 'none';
            
            // Mostrar overlay
            const overlay = document.getElementById('scanner-overlay');
            if (overlay) {
                overlay.style.display = 'block';
                overlay.innerHTML = '<i class="fas fa-camera me-2"></i>Câmera traseira ativa - Aponte para o código...';
            }
            
            // Configurações otimizadas
            const hints = new Map();
            hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
            hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [ZXing.BarcodeFormat.QR_CODE]);
            
            // Iniciar decodificação com a câmera selecionada
            const result = await codeReader.decodeOnceFromVideoDevice(selectedDeviceId, 'video-scanner', hints);
            
            console.log('✅ Código capturado com câmera traseira:', result.text);
            onZXingScanSuccess(result.text);
            
        } catch (error) {
            console.error('❌ Erro na câmera traseira:', error);
            handleZXingError(error);
        }
    }

    function onZXingScanSuccess(code) {
        console.log('🎯 Processando código:', code);
        
        // Parar scanner imediatamente
        stopZXingScanner();
        
        // UI de sucesso
        showScanResult('success', 
            `<div class="text-center py-3">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h5 class="text-success">✅ Código Capturado!</h5>
                <div class="alert alert-success mb-3">
                    <code class="fs-5 text-dark">${code}</code>
                </div>
                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                <small class="text-muted">Processando automaticamente...</small>
            </div>`
        );
        
        // Preencher campo
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.value = code;
        }
        
        // Auto-submit otimizado
        setTimeout(() => {
            modal.hide();
            
            // Delay adicional para garantir que modal fechou
            setTimeout(() => {
                const form = document.getElementById('inventory-form');
                if (form) {
                    console.log('📝 Submetendo formulário...');
                    form.submit();
                }
            }, 300);
        }, 1200);
    }

    function handleZXingError(error) {
        console.error('❌ Erro ZXing:', error.name, error.message);
        
        let errorMsg = 'Erro desconhecido';
        let instructions = '';
        
        if (error.name === 'NotAllowedError') {
            errorMsg = '🚨 Câmera bloqueada';
            instructions = `
                <div class="alert alert-warning mt-3">
                    <h6><i class="fas fa-info-circle"></i> Como resolver:</h6>
                    <ol class="text-start small mb-0">
                        <li>Clique no ícone 🔒 na barra de endereços</li>
                        <li>Altere "Câmera" para "Permitir"</li>
                        <li>Recarregue a página (F5)</li>
                    </ol>
                </div>
            `;
        } else if (error.name === 'NotFoundError') {
            errorMsg = '📷 Nenhuma câmera encontrada';
        } else if (error.name === 'NotSupportedError') {
            errorMsg = '❌ Câmera não suportada';
        }
        
        showScanResult('danger', 
            `<div class="text-center py-3">
                <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>
                <h6 class="text-danger">${errorMsg}</h6>
                ${instructions}
                <div class="mt-3">
                    <button class="btn btn-primary me-2" onclick="startZXingCamera()">
                        <i class="fas fa-redo me-1"></i> Tentar Novamente
                    </button>
                    <button class="btn btn-secondary" onclick="useManualInput()">
                        <i class="fas fa-keyboard me-1"></i> Digitar Manual
                    </button>
                </div>
            </div>`
        );
    }

    function stopZXingScanner() {
        if (codeReader && scanning) {
            try {
                codeReader.reset();
                scanning = false;
                
                // OTIMIZAÇÃO: Limpar vídeo completamente
                if (videoElement) {
                    videoElement.pause();
                    videoElement.currentTime = 0;
                    videoElement.style.display = 'none';
                }
                
                console.log('⏹️ Scanner parado e limpo');
            } catch (error) {
                console.warn('⚠️ Erro ao parar scanner:', error);
            }
        }
    }

    function closeScanner() {
        stopZXingScanner();
        modal.hide();
    }

    function useManualInput() {
        modal.hide();
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.focus();
            searchInput.placeholder = 'Digite o código aqui...';
        }
    }

    function showScanResult(type, message) {
        const loading = document.getElementById('scanner-loading');
        const video = document.getElementById('video-scanner');
        const overlay = document.getElementById('scanner-overlay');
        const result = document.getElementById('scanner-result');
        
        if (loading) loading.style.display = 'none';
        if (video) video.style.display = 'none';
        if (overlay) overlay.style.display = 'none';
        if (result) {
            result.innerHTML = `<div class="alert alert-${type} mb-0">${message}</div>`;
        }
    }

    function showPermissionError() {
        alert(`🚨 CÂMERA BLOQUEADA!

    📋 Para usar o scanner:
    1️⃣ Clique no ícone 🔒 na barra de endereços
    2️⃣ Mude "Câmera" para "Permitir"  
    3️⃣ Recarregue a página (F5)
    4️⃣ Tente novamente

    ⌨️ Ou digite o código no campo de busca.`);
        
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.focus();
            searchInput.placeholder = 'Digite o código (câmera bloqueada)';
        }
    }

    function initEnterKey() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('inventory-form').submit();
                }
            });
        }
    }

    // Limpeza ao sair
    window.addEventListener('beforeunload', function() {
        stopZXingScanner();
    });

    console.log('✅ ZXing Scanner OTIMIZADO pronto! 🚀');
    </script>

</body>
</html>
// QR Scanner - Sprint 2 - Scanner Real Implementado
console.log('QR Scanner Sprint 2 - Scanner Real Carregado');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM carregado, inicializando QR Scanner Sprint 2...');
    
    let html5QrCode = null;
    let isScanning = false;
    
    const qrBtn = document.getElementById('qrScanBtn');
    const qrModal = document.getElementById('qrModal');
    const qrReader = document.getElementById('qr-reader');
    const qrResult = document.getElementById('qr-result');
    const searchInput = document.getElementById('searchInput');
    
    if (qrBtn && qrModal) {
        const modalInstance = new bootstrap.Modal(qrModal);
        
        // Botão para abrir modal
        qrBtn.addEventListener('click', function() {
            console.log('Botão QR clicado - abrindo modal Sprint 2');
            modalInstance.show();
        });
        
        // Quando modal abrir - iniciar scanner
        qrModal.addEventListener('shown.bs.modal', function() {
            startScanner();
        });
        
        // Quando modal fechar - parar scanner
        qrModal.addEventListener('hidden.bs.modal', function() {
            stopScanner();
        });
        
        // Função para iniciar o scanner
        function startScanner() {
            if (isScanning) return;
            
            console.log('Iniciando scanner QR...');
            
            // Limpar conteúdo anterior
            qrReader.innerHTML = '<div id="qr-scanner-container"></div>';
            qrResult.innerHTML = '<div class="alert alert-info"><i class="fas fa-camera me-2"></i>Iniciando câmera...</div>';
            
            // Configurações do scanner
            const config = { 
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0,
                showTorchButtonIfSupported: true,
                showZoomSliderIfSupported: true
            };
            
            // Callback de sucesso
            const qrCodeSuccessCallback = (decodedText, decodedResult) => {
                console.log('QR Code detectado:', decodedText);
                
                // Mostrar sucesso
                qrResult.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>QR Code Lido:</strong> ${decodedText}
                    </div>
                `;
                
                // Preencher campo de busca
                if (searchInput) {
                    searchInput.value = decodedText;
                }
                
                // Parar scanner
                stopScanner();
                
                // Fechar modal após 1.5 segundos
                setTimeout(() => {
                    modalInstance.hide();
                    
                    // Submeter formulário automaticamente
                    const form = searchInput.closest('form');
                    if (form) {
                        form.submit();
                    }
                }, 1500);
            };
            
            // Callback de erro
            const qrCodeErrorCallback = (error) => {
                // Não logar erros de frame (muito verboso)
                // console.warn('QR Code error:', error);
            };
            
            // Inicializar Html5Qrcode
            html5QrCode = new Html5Qrcode("qr-scanner-container");
            
            // Obter câmeras disponíveis
            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    // Preferir câmera traseira se disponível
                    let cameraId = devices[0].id;
                    
                    // Procurar câmera traseira
                    const backCamera = devices.find(device => 
                        device.label.toLowerCase().includes('environment')
                    );
                    
                    if (backCamera) {
                        cameraId = backCamera.id;
                    }
                    
                    console.log('Usando câmera:', cameraId);
                    
                    // Iniciar scanner
                    html5QrCode.start(
                        cameraId,
                        config,
                        qrCodeSuccessCallback,
                        qrCodeErrorCallback
                    ).then(() => {
                        isScanning = true;
                        qrResult.innerHTML = `
                            <div class="alert alert-primary">
                                <i class="fas fa-qrcode me-2"></i>
                                <strong>Scanner Ativo!</strong> Aponte a câmera para o QR Code
                            </div>
                        `;
                        console.log('Scanner QR iniciado com sucesso!');
                    }).catch(err => {
                        console.error("Erro ao iniciar scanner:", err);
                        showError("Erro ao acessar a câmera. Verifique as permissões.");
                    });
                } else {
                    console.error("Nenhuma câmera encontrada");
                    showError("Nenhuma câmera encontrada no dispositivo.");
                }
            }).catch(err => {
                console.error("Erro ao obter câmeras:", err);
                showError("Erro ao acessar as câmeras do dispositivo.");
            });
        }
        
        // Função para parar o scanner
        function stopScanner() {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().then(() => {
                    console.log('Scanner QR parado');
                    html5QrCode.clear();
                    isScanning = false;
                }).catch(err => {
                    console.error("Erro ao parar scanner:", err);
                });
            }
        }
        
        // Função para mostrar erros
        function showError(message) {
            qrResult.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Erro:</strong> ${message}
                    <br><small class="mt-2">
                        • Certifique-se de que o navegador tem permissão para acessar a câmera<br>
                        • Use HTTPS em produção para melhor compatibilidade<br>
                        • Tente recarregar a página
                    </small>
                </div>
            `;
        }
        
        console.log('QR Scanner Sprint 2 inicializado com sucesso!');
    } else {
        console.warn('Elementos QR não encontrados na página');
    }
});

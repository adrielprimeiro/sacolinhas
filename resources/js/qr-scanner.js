// QR Scanner - Sprint 3 - Versão Avançada e Completa
console.log('🚀 QR Scanner Sprint 3 - Versão Avançada Carregada');

// Classe QR Scanner Avançada
class AdvancedQRScanner {
    constructor() {
        this.html5QrCode = null;
        this.isScanning = false;
        this.scanHistory = this.loadHistory();
        this.settings = this.loadSettings();
        this.stats = this.loadStats();
        
        console.log('Scanner avançado inicializado');
    }
    
    // Inicializar o scanner
    init() {
        const qrBtn = document.getElementById('qrScanBtn');
        const qrModal = document.getElementById('qrModal');
        
        
        this.modalInstance = new bootstrap.Modal(qrModal);
        this.setupEventListeners();
        this.updateHistoryDisplay();
        this.updateStatsDisplay();
        
        console.log('✅ Scanner avançado pronto para uso');
    }
    
    // Event Listeners
    setupEventListeners() {
        const qrBtn = document.getElementById('qrScanBtn');
        const qrModal = document.getElementById('qrModal');
        
        // Abrir modal
        qrBtn.addEventListener('click', () => this.openScanner());
        
        // Modal events
        qrModal.addEventListener('shown.bs.modal', () => this.startScanner());
        qrModal.addEventListener('hidden.bs.modal', () => this.stopScanner());
        
        // Botões do modal
        document.getElementById('startScanBtn')?.addEventListener('click', () => this.startScanner());
        document.getElementById('stopScanBtn')?.addEventListener('click', () => this.stopScanner());
        document.getElementById('uploadImageBtn')?.addEventListener('click', () => this.uploadImage());
        document.getElementById('continuousScanToggle')?.addEventListener('change', (e) => this.toggleContinuousMode(e.target.checked));
        document.getElementById('clearHistoryBtn')?.addEventListener('click', () => this.clearHistory());
    }
    
    // Abrir scanner
    openScanner() {
        console.log('📷 Abrindo scanner Sprint 3');
        this.modalInstance.show();
        this.updateStats('opens');
    }
    
    // Iniciar scanner
    startScanner() {
        if (this.isScanning) return;
        
        console.log('🔍 Iniciando scanner avançado...');
        
        const qrReader = document.getElementById('qr-reader');
        const qrResult = document.getElementById('qr-result');
        
        // Limpar e preparar interface
        qrReader.innerHTML = '<div id="qr-scanner-container"></div>';
        qrResult.innerHTML = this.getLoadingHTML();
        
        // Configurações avançadas
        const config = {
            qrbox: { width: 280, height: 280 },
            aspectRatio: 1.0,
            showTorchButtonIfSupported: true,
            showZoomSliderIfSupported: true,
            supportedScanTypes: [
                Html5QrcodeScanType.SCAN_TYPE_QRCODE,
                Html5QrcodeScanType.SCAN_TYPE_EAN_13,
                Html5QrcodeScanType.SCAN_TYPE_EAN_8,
                Html5QrcodeScanType.SCAN_TYPE_CODE_128,
                Html5QrcodeScanType.SCAN_TYPE_CODE_39
            ]
        };
        
        // Callbacks
        const successCallback = (decodedText, decodedResult) => this.onScanSuccess(decodedText, decodedResult);
        const errorCallback = (error) => this.onScanError(error);
        
        // Inicializar scanner
        this.html5QrCode = new Html5Qrcode("qr-scanner-container");
        
        Html5Qrcode.getCameras().then(devices => {
            if (!devices?.length) {
                this.showError("Nenhuma câmera encontrada");
                return;
            }
            
            // Selecionar melhor câmera
            const cameraId = this.selectBestCamera(devices);
            
            return this.html5QrCode.start(cameraId, config, successCallback, errorCallback);
        }).then(() => {
            this.isScanning = true;
            this.showScanningStatus();
            this.updateStats('scans_started');
        }).catch(err => {
            console.error("❌ Erro ao iniciar scanner:", err);
            this.showError("Erro ao acessar a câmera: " + err.message);
        });
    }
    
    // Parar scanner
    stopScanner() {
        
        this.html5QrCode.stop().then(() => {
            console.log('⏹️ Scanner parado');
            this.html5QrCode.clear();
            this.isScanning = false;
        }).catch(err => {
            console.error("Erro ao parar scanner:", err);
        });
    }
    
    // Sucesso no scan
    onScanSuccess(decodedText, decodedResult) {
        console.log('✅ Código detectado:', decodedText);
        
        // Adicionar ao histórico
        this.addToHistory(decodedText, decodedResult.decodedResult?.format);
        
        // Feedback sonoro e vibração
        this.playFeedback();
        
        // Mostrar resultado
        this.showScanResult(decodedText, decodedResult);
        
        // Preencher campo
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.value = decodedText;
            searchInput.focus();
        }
        
        // Atualizar estatísticas
        this.updateStats('successful_scans');
        
        // Modo contínuo ou fechar
        if (!this.settings.continuousMode) {
            setTimeout(() => {
                this.modalInstance.hide();
                // Auto-submit se habilitado
                if (this.settings.autoSubmit) {
                    const form = searchInput?.closest('form');
                    if (form) form.submit();
                }
        }
    }
    
    // Erro no scan
    onScanError(error) {
        // Silenciar erros de frame normais
        if (!error.includes("No QR code found")) {
            console.warn("Scanner error:", error);
        }
    }
    
    // Selecionar melhor câmera
    selectBestCamera(devices) {
        // Preferir câmera traseira
        const backCamera = devices.find(device => 
            device.label.toLowerCase().includes('environment')
        );
        
        return backCamera ? backCamera.id : devices[0].id;
    }
    
    // Feedback sonoro e vibração
    playFeedback() {
        // Som de beep
        if (this.settings.soundEnabled) {
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBj2Z1/LKdSEEE2e27uPotGgdBzSa1/LLfSwIJ3fJ69uWQwgWW7Xs6Z9NEg1NoPLygGMLCTGMz/HgpV0RC0Oo5O9iGCE=');
            audio.play().catch(() => {});
        }
        
        // Vibração
        if (this.settings.vibrationEnabled && 'vibrate' in navigator) {
            navigator.vibrate([100, 50, 100]);
        }
    }
    
    // Histórico de scans
    addToHistory(code, format) {
        const timestamp = new Date().toISOString();
        const entry = {
            code,
            timestamp,
            id: Date.now()
        };
        
        // Evitar duplicatas recentes (últimos 5 minutos)
        const fiveMinutesAgo = new Date(Date.now() - 5 * 60 * 1000).toISOString();
        const isDuplicate = this.scanHistory.some(item => 
            item.code === code && item.timestamp > fiveMinutesAgo
        );
        
        if (!isDuplicate) {
            this.scanHistory.unshift(entry);
            
            // Manter apenas últimos 50 itens
            if (this.scanHistory.length > 50) {
                this.scanHistory = this.scanHistory.slice(0, 50);
            }
            
            this.saveHistory();
            this.updateHistoryDisplay();
        }
    }
    
    // Upload de imagem
    uploadImage() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        
        input.onchange = (e) => {
            const file = e.target.files[0];
            if (!file) return;
            
            const html5QrCode = new Html5Qrcode("qr-scanner-container");
            
            html5QrCode.scanFile(file, true).then(decodedText => {
                console.log('✅ Código lido da imagem:', decodedText);
                this.onScanSuccess(decodedText, { decodedResult: { format: 'IMAGE_SCAN' } });
            }).catch(err => {
                console.error('❌ Erro ao ler imagem:', err);
                this.showError("Nenhum código encontrado na imagem");
            });
        };
        
        input.click();
    }
    
    // Interface HTML avançada
    getLoadingHTML() {
        return `
            <div class="alert alert-info">
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    <div>Iniciando câmera avançada...</div>
                </div>
            </div>
        `;
    }
    
    showScanningStatus() {
        document.getElementById('qr-result').innerHTML = `
            <div class="alert alert-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-qrcode me-2"></i>
                        <strong>Scanner Ativo!</strong> Detectando múltiplos formatos
                    </div>
                    <div class="badge bg-success">Sprint 3</div>
                </div>
                <small class="d-block mt-2">
                    Suporta: QR Code, EAN-13, EAN-8, Code-128, Code-39
                </small>
            </div>
        `;
    }
    
    showScanResult(code, result) {
        const timestamp = new Date().toLocaleString('pt-BR');
        
        document.getElementById('qr-result').innerHTML = `
            <div class="alert alert-success">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Código Detectado!
                    </h6>
                    <span class="badge bg-success">${format}</span>
                </div>
                <div class="bg-white p-2 rounded border">
                    <code class="text-dark">${code}</code>
                </div>
                <small class="text-muted mt-2 d-block">
                    <i class="fas fa-clock me-1"></i>
                    ${timestamp}
                </small>
                ${!this.settings.continuousMode ? '<div class="mt-2"><small class="text-muted">Fechando automaticamente...</small></div>' : ''}
            </div>
        `;
    }
    
    showError(message) {
        document.getElementById('qr-result').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Erro:</strong> ${message}
            </div>
        `;
    }
    
    // Atualizar displays
    updateHistoryDisplay() {
        const historyContainer = document.getElementById('scan-history');
        if (!historyContainer) return;
        
        if (this.scanHistory.length === 0) {
            historyContainer.innerHTML = '<small class="text-muted">Nenhum código escaneado ainda</small>';
            return;
        }
        
        const historyHTML = this.scanHistory.slice(0, 5).map(item => `
            <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                <div>
                    <small><code>${item.code}</code></small><br>
                    <small class="text-muted">${new Date(item.timestamp).toLocaleString('pt-BR')}</small>
                </div>
                <span class="badge bg-secondary">${item.format}</span>
            </div>
        `).join('');
        
        historyContainer.innerHTML = historyHTML;
    }
    
    updateStatsDisplay() {
        const statsContainer = document.getElementById('scan-stats');
        if (!statsContainer) return;
        
        statsContainer.innerHTML = `
            <div class="row text-center">
                <div class="col-4">
                    <small class="text-muted">Aberturas</small>
                </div>
                <div class="col-4">
                    <small class="text-muted">Sucessos</small>
                </div>
                <div class="col-4">
                    <div class="text-info fw-bold">${this.scanHistory.length}</div>
                    <small class="text-muted">Histórico</small>
                </div>
            </div>
        `;
    }
    
    // Storage functions
    loadHistory() {
        try {
        } catch {
            return [];
        }
    }
    
    saveHistory() {
        localStorage.setItem('qr_scan_history', JSON.stringify(this.scanHistory));
    }
    
    loadSettings() {
        try {
        } catch {
            return {};
        }
    }
    
    saveSettings() {
        localStorage.setItem('qr_scanner_settings', JSON.stringify(this.settings));
    }
    
    loadStats() {
        try {
        } catch {
            return {};
        }
    }
    
    updateStats(key) {
        localStorage.setItem('qr_scanner_stats', JSON.stringify(this.stats));
        this.updateStatsDisplay();
    }
    
    clearHistory() {
        if (confirm('Limpar todo o histórico de escaneamentos?')) {
            this.scanHistory = [];
            this.saveHistory();
            this.updateHistoryDisplay();
        }
    }
    
    toggleContinuousMode(enabled) {
        this.settings.continuousMode = enabled;
        this.saveSettings();
        console.log('Modo contínuo:', enabled ? 'Ativado' : 'Desativado');
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    window.qrScanner = new AdvancedQRScanner();
    window.qrScanner.init();
});
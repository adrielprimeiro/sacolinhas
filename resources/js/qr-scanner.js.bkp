// ===== SCANNER QR FUNCIONAL - qr-scanner.js =====

console.log('🚀 qr-scanner.js carregado');

class QRScannerManager {
    constructor() {
        this.html5QrCode = null;
        this.isScanning = false;
        this.modal = null;
        this.scanHistory = [];
        
        // Aguardar DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }
    
    init() {
        console.log('🔧 Inicializando QR Scanner Manager...');
        
        // Verificar se elementos existem
        this.elements = {
            scanBtn: document.getElementById('qrScanBtn'),
            modal: document.getElementById('qrModal'),
            searchInput: document.getElementById('searchInput'),
            qrReader: document.getElementById('qr-reader'),
            qrResult: document.getElementById('qr-result'),
            startBtn: document.getElementById('startScanBtn'),
            stopBtn: document.getElementById('stopScanBtn'),
            uploadBtn: document.getElementById('uploadImageBtn'),
            continuousToggle: document.getElementById('continuousScanToggle'),
            scanStats: document.getElementById('scan-stats'),
            scanHistory: document.getElementById('scan-history'),
            clearHistoryBtn: document.getElementById('clearHistoryBtn')
        };
 
		if (!this.elements.scanBtn || !this.elements.modal || !this.elements.qrReader){
        // Verificar se elementos principais existem
            console.warn('⚠️ Elementos do scanner não encontrados');
            return;
        }
        
        // Inicializar Bootstrap Modal
        this.modal = new bootstrap.Modal(this.elements.modal);
        
        // Setup Event Listeners
        this.setupEventListeners();
        
        // Inicializar UI
        this.updateStats();
        this.updateHistory();
        
        console.log('✅ QR Scanner Manager inicializado com sucesso!');
    }
    
    setupEventListeners() {
        // Botão principal de scanner
        this.elements.scanBtn?.addEventListener('click', () => {
            console.log('📷 Abrindo modal do scanner...');
            this.openModal();
        });
        
        // Controles do modal
        this.elements.startBtn?.addEventListener('click', () => this.startScanning());
        this.elements.stopBtn?.addEventListener('click', () => this.stopScanning());
        this.elements.uploadBtn?.addEventListener('click', () => this.uploadImage());
        this.elements.clearHistoryBtn?.addEventListener('click', () => this.clearHistory());
        
        // Fechar modal - parar scanner
        this.elements.modal?.addEventListener('hidden.bs.modal', () => {
            this.stopScanning();
        });
        
        // Enter no input de busca
        this.elements.searchInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.performSearch();
            }
        });
    }
    
    openModal() {
        this.modal.show();
        this.showPlaceholder();
        this.updateUI();
    }
    
    showPlaceholder() {
        if (this.elements.qrReader) {
            this.elements.qrReader.innerHTML = `
                <div class="d-flex align-items-center justify-content-center h-100 p-4" style="min-height: 400px;">
                    <div class="text-muted text-center">
                        <i class="fas fa-camera fa-4x mb-3" style="color: #667eea;"></i>
                        <h6>Scanner Multi-formato Pronto</h6>
                        <small>QR Code • EAN-13 • EAN-8 • Code-128 • Code-39</small>
                        <div class="mt-3">
                            <button type="button" class="btn btn-primary" onclick="qrScanner.startScanning()">
                                <i class="fas fa-play"></i> Iniciar Scanner
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
    }
    
    async startScanning() {
        try {
            console.log('▶️ Iniciando escaneamento...');
            
            this.showResult('info', '<i class="fas fa-spinner fa-spin"></i> Iniciando câmera...');
            
            // Limpar container do reader
            if (this.elements.qrReader) {
                this.elements.qrReader.innerHTML = '';
            }
            
            // Configuração do scanner
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0,
                showTorchButtonIfSupported: true,
                showZoomSliderIfSupported: true,
                defaultZoomValueIfSupported: 2
            };
            
            // Inicializar scanner
            this.html5QrCode = new Html5Qrcode("qr-reader");
            
            await this.html5QrCode.start(
                { facingMode: "environment" }, // Câmera traseira preferida
                config,
                (decodedText, decodedResult) => {
                    this.onScanSuccess(decodedText, decodedResult);
                },
                (errorMessage) => {
                    // Erro silencioso - normal durante escaneamento
                }
            );
            
            this.isScanning = true;
            this.updateUI();
            this.showResult('success', '<i class="fas fa-camera"></i> Scanner ativo! Aponte para um código...');
            
        } catch (error) {
            console.error('❌ Erro ao iniciar scanner:', error);
            
            let errorMsg = 'Erro ao acessar câmera.';
            if (error.message.includes('Permission')) {
                errorMsg = 'Permissão de câmera negada. Verifique as configurações do navegador.';
            } else if (error.message.includes('NotFoundError')) {
                errorMsg = 'Nenhuma câmera encontrada no dispositivo.';
            }
            
            this.showResult('danger', `<i class="fas fa-exclamation-triangle"></i> ${errorMsg}`);
            this.showPlaceholder();
        }
    }
    
    async stopScanning() {
        if (this.html5QrCode && this.isScanning) {
            try {
                console.log('⏹️ Parando scanner...');
                await this.html5QrCode.stop();
                this.html5QrCode.clear();
                this.isScanning = false;
                console.log('✅ Scanner parado');
                
                this.showResult('info', '<i class="fas fa-info-circle"></i> Scanner parado.');
                
            } catch (error) {
                console.error('❌ Erro ao parar scanner:', error);
            }
        }
        
        this.updateUI();
        setTimeout(() => this.showPlaceholder(), 1000);
    }
    
    onScanSuccess(decodedText, decodedResult) {
        console.log('🎯 Código detectado:', decodedText);
        
        // Adicionar ao histórico
        this.addToHistory(decodedText);
        
        // Preencher campo de busca
        if (this.elements.searchInput) {
            this.elements.searchInput.value = decodedText;
        }
        
        // Mostrar resultado
        this.showResult('success', `
            <strong><i class="fas fa-check-circle"></i> Código Escaneado!</strong><br>
            <code class="fs-6">${decodedText}</code><br>
            <small class="text-muted">Campo de busca preenchido automaticamente</small>
        `);
        
        // Parar scanner se não estiver em modo contínuo
        if (!this.elements.continuousToggle?.checked) {
            setTimeout(() => {
                this.stopScanning();
                // Fechar modal após 3 segundos
                setTimeout(() => {
                    this.modal.hide();
                    // Focar no campo de busca
                    this.elements.searchInput?.focus();
                }, 3000);
            }, 1500);
        }
        
        this.updateStats();
    }
    
    addToHistory(code) {
        const timestamp = new Date().toLocaleTimeString('pt-BR');
        this.scanHistory.unshift({
            code: code,
            timestamp: timestamp,
            type: this.detectCodeType(code)
        });
        
        // Limitar histórico a 10 itens
        if (this.scanHistory.length > 10) {
            this.scanHistory = this.scanHistory.slice(0, 10);
        }
        
        this.updateHistory();
    }
    
    detectCodeType(code) {
        if (code.length === 13 && /^\d+$/.test(code)) return 'EAN-13';
        if (code.length === 8 && /^\d+$/.test(code)) return 'EAN-8';
        if (/^[A-Z0-9\-. $\/+%]+$/i.test(code)) return 'Code-128';
        return 'QR Code';
    }
    
    updateHistory() {
        if (!this.elements.scanHistory) return;
        
        if (this.scanHistory.length === 0) {
            this.elements.scanHistory.innerHTML = `
                <div class="text-muted text-center">
                    <i class="fas fa-history"></i><br>
                    <small>Nenhum scan ainda</small>
                </div>
            `;
            return;
        }
        
        const historyHtml = this.scanHistory.map(item => `
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                <div>
                    <code class="small">${item.code.length > 15 ? item.code.substring(0, 15) + '...' : item.code}</code><br>
                    <span class="badge bg-secondary">${item.type}</span>
                </div>
                <small class="text-muted">${item.timestamp}</small>
            </div>
        `).join('');
        
        this.elements.scanHistory.innerHTML = historyHtml;
    }
    
    updateStats() {
        if (!this.elements.scanStats) return;
        
        const totalScans = this.scanHistory.length;
        const uniqueCodes = new Set(this.scanHistory.map(item => item.code)).size;
        
        this.elements.scanStats.innerHTML = `
            <div class="row text-center">
                <div class="col-6">
                    <div class="h5 mb-0 text-primary">${totalScans}</div>
                    <small class="text-muted">Total</small>
                </div>
                <div class="col-6">
                    <div class="h5 mb-0 text-success">${uniqueCodes}</div>
                    <small class="text-muted">Únicos</small>
                </div>
            </div>
        `;
    }
    
    clearHistory() {
        this.scanHistory = [];
        this.updateHistory();
        this.updateStats();
        this.showResult('info', '<i class="fas fa-trash"></i> Histórico limpo.');
    }
    
    showResult(type, message) {
        if (this.elements.qrResult) {
            this.elements.qrResult.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }
    }
    
    updateUI() {
        // Atualizar estados dos botões
        if (this.elements.startBtn) {
            this.elements.startBtn.style.display = this.isScanning ? 'none' : 'inline-block';
        }
        if (this.elements.stopBtn) {
            this.elements.stopBtn.style.display = this.isScanning ? 'inline-block' : 'none';
        }
    }
    
    uploadImage() {
        // Criar input file temporário
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        
        input.onchange = async (e) => {
            const file = e.target.files[0];
            if (file) {
                try {
                    this.showResult('info', '<i class="fas fa-spinner fa-spin"></i> Processando imagem...');
                    
                    const result = await Html5Qrcode.scanFile(file, true);
                    this.onScanSuccess(result, null);
                    
                } catch (error) {
                    console.error('Erro ao processar imagem:', error);
                    this.showResult('warning', '<i class="fas fa-exclamation-triangle"></i> Nenhum código encontrado na imagem.');
                }
            }
        };
        
        input.click();
    }
    
    performSearch() {
        const searchTerm = this.elements.searchInput?.value.trim();
        if (searchTerm) {
            console.log('🔍 Realizando busca por:', searchTerm);
            // Aqui você pode implementar a lógica de busca
            // Por exemplo, submeter o formulário ou fazer uma requisição AJAX
            
            // Se existe um formulário pai, submeter
            const form = this.elements.searchInput?.closest('form');
            if (form) {
                form.submit();
            }
        }
    }
}

// Instanciar o scanner globalmente
let qrScanner;

// Inicializar quando script carregar
console.log('📱 Preparando QR Scanner...');
qrScanner = new QRScannerManager();

// Export para uso global
window.qrScanner = qrScanner;
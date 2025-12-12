// resources/js/qr-scanner.js

import { BrowserQRCodeReader } from '@zxing/library';

class QRScanner {
    constructor() {
        this.codeReader = new BrowserQRCodeReader();
        this.scanning = false;
        this.videoElementId = 'video-scanner'; // ID do elemento <video> no HTML
        this.searchFieldId = 'search'; // ID do campo de busca
        this.searchFormId = 'search-form'; // ID do formulário de busca
        this.csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    }

    /**
     * Inicia o processo de escaneamento do QR Code.
     * Seleciona a câmera traseira automaticamente.
     */
    async startScanning() {
        if (this.scanning) {
            console.warn("Scanner já está ativo.");
            return;
        }

        this.scanning = true;
        this.clearFeedback(); // Limpa mensagens de feedback anteriores
        this.showLoading('Iniciando câmera...');

        try {
            // Tenta encontrar a câmera traseira
            const videoInputDevices = await this.codeReader.getVideoInputDevices();
            const backCamera = videoInputDevices.find(device =>
                device.label.toLowerCase().includes('back') ||
                device.label.toLowerCase().includes('rear') ||
                device.label.toLowerCase().includes('environment')
            );

            const selectedDeviceId = backCamera ? backCamera.deviceId : undefined;

            // Inicia o scanner e decodifica uma vez
            const result = await this.codeReader.decodeOnceFromVideoDevice(
                selectedDeviceId,
                this.videoElementId
            );

            // Se um resultado for obtido, processa
            if (result) {
                this.onScanSuccess(result.text);
            } else {
                this.onScanError(new Error("Nenhum QR Code detectado após iniciar."));
            }

        } catch (err) {
            console.error('Erro ao iniciar scanner ou ler QR Code:', err);
            this.onScanError(err);
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Para o scanner e libera os recursos da câmera.
     */
    stopScanning() {
        if (this.scanning) {
            this.codeReader.reset();
            this.scanning = false;
            console.log("Scanner parado.");
        }
        this.clearFeedback();
    }

    /**
     * Callback para quando um QR Code é lido com sucesso.
     * @param {string} qrCodeData - O texto decodificado do QR Code.
     */
    onScanSuccess(qrCodeData) {
        this.stopScanning(); // Para o scanner imediatamente
        $('#qrModal').modal('hide'); // Fecha o modal

        // Preenche o campo de busca
        const searchInput = document.getElementById(this.searchFieldId);
        if (searchInput) {
            searchInput.value = qrCodeData;
        }

        this.showSuccess('QR Code lido: ' + qrCodeData);

        // Dispara a busca AJAX
        this.searchItem(qrCodeData);
    }

    /**
     * Callback para quando ocorre um erro na leitura do QR Code ou na câmera.
     * @param {Error} error - O objeto de erro.
     */
    onScanError(error) {
        this.stopScanning(); // Garante que o scanner seja parado
        this.showError('Erro ao ler QR Code ou acessar câmera. Por favor, tente novamente.');
        console.error('Detalhes do erro:', error);
    }

    /**
     * Realiza a busca do item via AJAX.
     * @param {string} code - O código do QR Code a ser buscado.
     */
    async searchItem(code) {
        this.showLoading('Buscando item...');
        try {
            const response = await fetch('/inventario/search-code', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({ search: code })
            });

            const data = await response.json();
            this.hideLoading();

            if (data.success) {
                this.showSuccess('Item encontrado!');
                // Redireciona para a página do item ou atualiza a interface
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    // Se não houver redirect, você pode atualizar uma lista, por exemplo
                    console.log('Item encontrado:', data.item);
                    // Exemplo: document.getElementById('item-details').innerHTML = `<h3>${data.item.nome}</h3>`;
                }
            } else {
                this.showError(data.message || 'Item não encontrado.');
            }
        } catch (error) {
            this.hideLoading();
            this.showError('Erro na comunicação com o servidor.');
            console.error('Erro na busca AJAX:', error);
        }
    }

    /**
     * Exibe uma mensagem de sucesso.
     * @param {string} message
     */
    showSuccess(message) {
        const feedbackDiv = document.getElementById('scanner-feedback');
        if (feedbackDiv) {
            feedbackDiv.innerHTML = `<div class="alert alert-success" role="alert">${message}</div>`;
            feedbackDiv.style.display = 'block';
        }
        // Opcional: Usar uma biblioteca de notificações como Toastr
        // toastr.success(message);
    }

    /**
     * Exibe uma mensagem de erro.
     * @param {string} message
     */
    showError(message) {
        const feedbackDiv = document.getElementById('scanner-feedback');
        if (feedbackDiv) {
            feedbackDiv.innerHTML = `<div class="alert alert-danger" role="alert">${message}</div>`;
            feedbackDiv.style.display = 'block';
        }
        // Opcional: Usar uma biblioteca de notificações como Toastr
        // toastr.error(message);
    }

    /**
     * Exibe uma mensagem de carregamento.
     * @param {string} message
     */
    showLoading(message) {
        const feedbackDiv = document.getElementById('scanner-feedback');
        if (feedbackDiv) {
            feedbackDiv.innerHTML = `<div class="alert alert-info" role="alert"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${message}</div>`;
            feedbackDiv.style.display = 'block';
        }
    }

    /**
     * Limpa todas as mensagens de feedback.
     */
    clearFeedback() {
        const feedbackDiv = document.getElementById('scanner-feedback');
        if (feedbackDiv) {
            feedbackDiv.innerHTML = '';
            feedbackDiv.style.display = 'none';
        }
    }
}

// Instancia o scanner
const qrScanner = new QRScanner();

// Adiciona event listeners para o modal Bootstrap
document.addEventListener('DOMContentLoaded', () => {
    // Botão para abrir o modal do scanner
    const btnQrScanner = document.getElementById('btn-qr-scanner');
    if (btnQrScanner) {
        btnQrScanner.addEventListener('click', () => {
            $('#qrModal').modal('show');
        });
    }

    // Inicia o scanner quando o modal é exibido
    $('#qrModal').on('shown.bs.modal', () => {
        qrScanner.startScanning();
    });

    // Para o scanner quando o modal é ocultado
    $('#qrModal').on('hidden.bs.modal', () => {
        qrScanner.stopScanning();
    });
});

// Exporta para uso em outros módulos se necessário
export default qrScanner;
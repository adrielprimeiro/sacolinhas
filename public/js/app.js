// public/js/app.js

$(document).ready(function() {
    // Função para obter o token CSRF
    function getCsrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    // Função para exibir notificações
    function showNotification(message, type = 'info') {
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'error' ? 'alert-danger' : 'alert-info';
        
        const notification = $(`
            <div class="alert ${alertClass} alert-dismissible fade show notification-toast" role="alert" style="
                position: fixed; 
                top: 20px; 
                right: 20px; 
                z-index: 1050; 
                min-width: 300px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            ">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">&times;</button>
            </div>
        `);
        
        $('body').append(notification);
        
        // Auto remove após 5 segundos
        setTimeout(() => {
            notification.fadeOut(() => notification.remove());
        }, 5000);
    }

    // Variável global para armazenar o cliente selecionado
    window.clienteSelecionado = null;

    // Função para obter cliente selecionado (adaptada ao seu sistema)
    function getClienteSelecionado() {
        // Tentar diferentes formas de obter o cliente selecionado
        if (window.clienteSelecionado) {
            return window.clienteSelecionado;
        }
        
        // Tentar pelo select (se existir)
        const selectCliente = $('#cliente_id').val();
        if (selectCliente) {
            return selectCliente;
        }
        
        // Tentar por variáveis globais do seu sistema
        if (typeof userId !== 'undefined' && userId) {
            return userId;
        }
        
        return null;
    }

    // Função para habilitar/desabilitar botões de impressão
    function togglePrintButtons(enable) {
        $('#btn-imprimir-sacolinha, #btnImprimirSacolinha').prop('disabled', !enable);
        $('#btn-imprimir-pedido, #btnImprimirPedido').prop('disabled', !enable);
        
        if (enable) {
            console.log('✅ Botões de impressão habilitados');
        } else {
            console.log('❌ Botões de impressão desabilitados');
        }
    }

    // Interceptar quando um cliente for selecionado (adaptar ao seu sistema)
    $(document).on('click', '[data-cliente-id]', function() {
        const clienteId = $(this).data('cliente-id');
        window.clienteSelecionado = clienteId;
        togglePrintButtons(true);
        console.log('🎯 Cliente selecionado:', clienteId);
    });

    // Também interceptar mudanças no select (se existir)
    $('#cliente_id').on('change', function() {
        const clienteId = $(this).val();
        window.clienteSelecionado = clienteId;
        togglePrintButtons(!!clienteId);
        console.log('🎯 Cliente selecionado via select:', clienteId);
    });

    // Função genérica para impressão de relatório
    function imprimirRelatorio(url, buttonSelector, reportName) {
        const clienteId = getClienteSelecionado();
        
        console.log('🖨️ Tentando imprimir:', reportName, 'para cliente:', clienteId);
        
        if (!clienteId) {
            showNotification('⚠️ Por favor, selecione um cliente antes de imprimir a ' + reportName + '.', 'error');
            return;
        }

        const $button = $(buttonSelector);
        const originalHtml = $button.html();
        
        // Desabilitar botão e mostrar loading
        $button.prop('disabled', true).html('⏳ Gerando PDF...');
        
        showNotification('🔄 Gerando PDF da ' + reportName + '...', 'info');

        // Criar FormData para envio
        const formData = new FormData();
        formData.append('_token', getCsrfToken());
        formData.append('cliente_id', clienteId);

        console.log('📤 Enviando requisição:', {
            url: url,
            clienteId: clienteId,
            token: getCsrfToken() ? 'presente' : 'ausente'
        });

        // Fazer requisição AJAX
        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhrFields: {
                responseType: 'blob'
            },
            success: function(response, status, xhr) {
                console.log('✅ PDF gerado com sucesso');
                
                // Extrair nome do arquivo
                let filename = reportName.toLowerCase() + '_cliente_' + clienteId + '_' + new Date().toISOString().slice(0,10) + '.pdf';
                
                const contentDisposition = xhr.getResponseHeader('Content-Disposition');
                if (contentDisposition) {
                    const match = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                    if (match && match[1]) {
                        filename = match[1].replace(/['"]/g, '');
                    }
                }

                // Criar blob e download
                const blob = new Blob([response], { type: 'application/pdf' });
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(link.href);

                showNotification('✅ PDF da ' + reportName + ' baixado com sucesso!', 'success');
            },
            error: function(xhr, status, error) {
                console.error('❌ Erro ao gerar relatório:', {
                    status: xhr.status,
                    error: error,
                    response: xhr.responseText
                });
                
                let errorMessage = 'Erro desconhecido ao gerar o relatório.';
                
                if (xhr.status === 404) {
                    errorMessage = 'Rota não encontrada. Verifique se as rotas PDF estão configuradas.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Erro interno do servidor.';
                } else if (xhr.responseJSON) {
                    errorMessage = xhr.responseJSON.error || xhr.responseJSON.message || errorMessage;
                }
                
                showNotification('❌ Erro ao gerar ' + reportName + ': ' + errorMessage, 'error');
            },
            complete: function() {
                // Restaurar botão
                $button.html(originalHtml);
                const clienteAtual = getClienteSelecionado();
                $button.prop('disabled', !clienteAtual);
            }
        });
    }

    // Eventos de clique para os botões de impressão
    $(document).on('click', '#btn-imprimir-sacolinha, #btnImprimirSacolinha', function() {
        imprimirRelatorio('/pedidos/imprimir-sacolinha', this, 'Sacolinha');
    });

    $(document).on('click', '#btn-imprimir-pedido, #btnImprimirPedido', function() {
        imprimirRelatorio('/pedidos/imprimir-pedido', this, 'Pedidos');
    });

    // Eventos para limpar seleção
    $(document).on('click', '#btn-limpar-selecao, #btnLimparSelecao', function() {
        window.clienteSelecionado = null;
        $('#cliente_id').val('').trigger('change');
        togglePrintButtons(false);
        showNotification('✅ Seleção limpa!', 'success');
        console.log('🗑️ Seleção limpa');
    });

    // ========================================
    // FUNÇÕES DE DEBUG GLOBAIS (CORRIGIDAS)
    // ========================================
    
    // Definir no escopo global
    window.debugCliente = function() {
        console.log('=== DEBUG CLIENTE ===');
        console.log('Cliente via função:', getClienteSelecionado());
        console.log('Cliente na variável global:', window.clienteSelecionado);
        console.log('Cliente no select:', $('#cliente_id').val());
        console.log('UserId global:', typeof userId !== 'undefined' ? userId : 'não definido');
        console.log('Select existe:', $('#cliente_id').length > 0);
        console.log('Botões sacolinha encontrados:', $('#btn-imprimir-sacolinha, #btnImprimirSacolinha').length);
        console.log('Botões pedido encontrados:', $('#btn-imprimir-pedido, #btnImprimirPedido').length);
        console.log('CSRF Token:', getCsrfToken() ? 'presente' : 'ausente');
        console.log('====================');
    };

    window.habilitarBotoes = function() {
        togglePrintButtons(true);
        $('#btn-imprimir-sacolinha, #btnImprimirSacolinha').prop('disabled', false);
        $('#btn-imprimir-pedido, #btnImprimirPedido').prop('disabled', false);
        console.log('🔧 Botões habilitados manualmente');
    };

    window.testarPDF = function(tipo) {
        if (!window.clienteSelecionado) {
            window.clienteSelecionado = 2; // Usar o userId que vi nos logs
        }
        
        if (tipo === 'sacolinha') {
            $('#btn-imprimir-sacolinha').click();
        } else {
            $('#btn-imprimir-pedido').click();
        }
    };

    // Função para detectar quando cliente é selecionado no seu sistema
    window.setClienteSelecionado = function(clienteId) {
        window.clienteSelecionado = clienteId;
        togglePrintButtons(true);
        console.log('🎯 Cliente definido via função:', clienteId);
    };

    // ========================================
    // INICIALIZAÇÃO E MONITORAMENTO
    // ========================================

    // Monitorar mudanças na variável userId (do seu sistema)
    let lastUserId = null;
    setInterval(function() {
        if (typeof userId !== 'undefined' && userId !== lastUserId) {
            lastUserId = userId;
            window.clienteSelecionado = userId;
            togglePrintButtons(true);
            console.log('🔄 UserId atualizado automaticamente:', userId);
        }
    }, 1000);

    // Inicialização
    console.log('🚀 Sistema de PDF carregado');
    
    // Tentar detectar cliente selecionado após carregamento
    setTimeout(() => {
        if (typeof userId !== 'undefined' && userId) {
            window.clienteSelecionado = userId;
            togglePrintButtons(true);
            console.log('✅ Cliente detectado na inicialização:', userId);
        }
        
        // Log de debug inicial
        window.debugCliente();
    }, 2000);

});
const { WebcastPushConnection } = require('tiktok-live-connector');
const axios = require('axios');

// Configurações do painel
const API_BASE_URL = 'https://minhamania.net/api';
const POLL_INTERVAL_MS = 10000; // 10 segundos
let currentUsername = null;
let currentLiveId = null;
let tiktokConnection = null;
let isConnecting = false;

// Função para buscar vidas ativas no Laravel
async function checkActiveLives() {
    try {
        const response = await axios.get(`${API_BASE_URL}/active-tiktok-lives`);
        const data = response.data;
        
        if (data.success && data.active_live) {
            const { username, live_id } = data.active_live;
            
            // Se já estamos conectados a esse usuário, não fazer nada
            if (currentUsername === username && tiktokConnection) {
                return;
            }
            
            console.log(`[TikTok Listener] Nova live ativa encontrada para @${username} (Live ID: ${live_id})`);
            await connectToTikTok(username, live_id);
        } else {
            // Nenhuma live ativa. Desconectar se estiver conectado.
            if (tiktokConnection) {
                console.log(`[TikTok Listener] Live encerrada no painel. Desconectando de @${currentUsername}...`);
                tiktokConnection.disconnect();
                tiktokConnection = null;
                currentUsername = null;
                currentLiveId = null;
            }
        }
    } catch (error) {
        console.error('[TikTok Listener] Erro ao consultar a API do Laravel:', error.message);
    }
}

// Conectar ao WebSocket do TikTok
async function connectToTikTok(username, liveId) {
    if (isConnecting) return;
    isConnecting = true;
    
    // Limpar conexão antiga se existir
    if (tiktokConnection) {
        tiktokConnection.disconnect();
    }
    
    currentUsername = username;
    currentLiveId = liveId;
    
    console.log(`[TikTok Listener] Conectando ao chat de @${username}...`);
    
    tiktokConnection = new WebcastPushConnection(username, {
        processInitialData: false,
        enableExtendedGiftInfo: false,
        enableWebsocketUpgrade: true,
        requestPollingIntervalMs: 2000,
        clientParams: {
            "app_language": "pt-BR",
            "device_platform": "web"
        }
    });
    
    try {
        const state = await tiktokConnection.connect();
        console.info(`[TikTok Listener] Conectado com sucesso à sala de @${username} (Room ID: ${state.roomId})`);
        
        // Listener de mensagens de chat
        tiktokConnection.on('chat', (data) => {
            console.log(`[Chat] @${data.uniqueId}: ${data.comment}`);
            sendToLaravel(data.uniqueId, data.comment, currentLiveId);
        });
        
        // Listener de conexão perdida
        tiktokConnection.on('disconnected', () => {
            console.log(`[TikTok Listener] Desconectado de @${username}`);
            tiktokConnection = null;
            currentUsername = null;
        });
        
    } catch (err) {
        console.error(`[TikTok Listener] Falha ao conectar em @${username}:`, err.message);
        tiktokConnection = null;
        currentUsername = null;
    } finally {
        isConnecting = false;
    }
}

// Enviar a mensagem para o Laravel
async function sendToLaravel(username, message, liveId) {
    if (!liveId) return;
    
    try {
        await axios.post(`${API_BASE_URL}/live-chat/message`, {
            live_id: liveId,
            platform: 'tiktok',
            username: username,
            message: message
        }, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
    } catch (err) {
        console.error(`[Laravel API] Erro ao enviar mensagem de @${username}:`, err.message);
    }
}

// Iniciar o loop de checagem
console.log('=============================================');
console.log('🚀 TikTok Live Listener (Modo Invisível) Iniciado');
console.log(`📡 Consultando a API a cada ${POLL_INTERVAL_MS / 1000}s`);
console.log('=============================================');

checkActiveLives();
setInterval(checkActiveLives, POLL_INTERVAL_MS);

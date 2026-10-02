const express = require('express');
const { TikTokLiveConnection } = require('tiktok-live-connector');

const app = express();
app.use(express.json());

// Liberar CORS para chamadas do painel no Chrome
app.use((req, res, next) => {
    res.header("Access-Control-Allow-Origin", "*");
    res.header("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept");
    res.header("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
    if (req.method === 'OPTIONS') {
        return res.sendStatus(200);
    }
    next();
});

const PORT = 3001;
const LARAVEL_WEBHOOK_URL = process.env.LARAVEL_URL || 'https://minhamania.net/api/live-chat/message-batch';

let tiktokConnection = null;
let currentUsername = '_minhamania';
let isConnecting = false;
let lastError = null;
let shouldAutoReconnect = true;
let reconnectTimer = null;

// Fila de Lotes (Batch Buffer)
let batchQueue = [];
let flushTimer = null;

function flushBatch() {
    if (batchQueue.length === 0) return;
    const batch = batchQueue.splice(0, 50);
    console.log(`[TikTok Service] 🚀 Enviando lote de ${batch.length} mensagem(ns) para Laravel...`);
    fetch(LARAVEL_WEBHOOK_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ messages: batch })
    }).then(async res => {
        const text = await res.text();
        console.log(`[TikTok Service] ✅ Resposta Laravel (${res.status}): ${text}`);
    }).catch(err => {
        console.error('[TikTok Service] ❌ Erro ao enviar lote para Laravel:', err.message);
    });
}

function startFlushTimer() {
    if (!flushTimer) {
        flushTimer = setInterval(flushBatch, 150);
    }
}

function stopFlushTimer() {
    if (flushTimer) {
        clearInterval(flushTimer);
        flushTimer = null;
    }
}

function disconnectCurrent() {
    if (reconnectTimer) {
        clearTimeout(reconnectTimer);
        reconnectTimer = null;
    }
    if (tiktokConnection) {
        try {
            tiktokConnection.disconnect();
        } catch (e) {}
        tiktokConnection = null;
    }
    isConnecting = false;
    stopFlushTimer();
}

function scheduleAutoReconnect() {
    if (!shouldAutoReconnect || !currentUsername) return;
    if (reconnectTimer) clearTimeout(reconnectTimer);
    
    console.log(`[TikTok Service] ⏳ Agendando reconexão automática para @${currentUsername} em 10s...`);
    reconnectTimer = setTimeout(() => {
        if (shouldAutoReconnect && currentUsername) {
            console.log(`[TikTok Service] 🔄 Tentando reconectar automaticamente a @${currentUsername}...`);
            connectToUser(currentUsername);
        }
    }, 10000);
}

function connectToUser(username) {
    const cleanUser = (username || currentUsername || '_minhamania').replace(/^@/, '').trim();

    disconnectCurrent();
    isConnecting = true;
    lastError = null;
    currentUsername = cleanUser;
    shouldAutoReconnect = true;

    console.log(`[TikTok Service] 📡 Conectando à live de @${cleanUser}...`);

    try {
        tiktokConnection = new TikTokLiveConnection(cleanUser, {
            processInitialData: true,
            enableWebsocketUpgrade: true,
            clientParams: {
                app_language: 'pt-BR',
                device_platform: 'web'
            }
        });

        tiktokConnection.connect().then(state => {
            isConnecting = false;
            lastError = null;
            console.log(`[TikTok Service] 🎉 Conectado com sucesso ao @${cleanUser} (Room ID: ${state.roomId})`);
            startFlushTimer();
        }).catch(err => {
            isConnecting = false;
            lastError = err.message || 'Falha ao conectar na live';
            console.error(`[TikTok Service] ❌ Falha ao conectar em @${cleanUser}:`, err.message);
            disconnectCurrent();
            scheduleAutoReconnect();
        });

        // Ouvinte de mensagens do chat
        tiktokConnection.on('chat', data => {
            const msgText = data.content || data.comment || data.commentText || '';
            const userText = data.user?.displayId || data.uniqueId || data.user?.uniqueId || data.user?.nickname || data.nickname || 'Anônimo';
            if (!msgText) return;

            console.log(`[TikTok Service] 💬 @${userText}: "${msgText}"`);

            let avatarUrl = '';
            if (data.user?.avatarThumb?.urlList && data.user.avatarThumb.urlList.length > 0) {
                avatarUrl = data.user.avatarThumb.urlList[0];
            } else if (data.profilePictureUrl) {
                avatarUrl = data.profilePictureUrl;
            } else if (data.user?.profilePictureUrl) {
                avatarUrl = data.user.profilePictureUrl;
            } else if (data.user?.avatarMedium?.urlList && data.user.avatarMedium.urlList.length > 0) {
                avatarUrl = data.user.avatarMedium.urlList[0];
            } else if (data.user?.avatarLarge?.urlList && data.user.avatarLarge.urlList.length > 0) {
                avatarUrl = data.user.avatarLarge.urlList[0];
            }

            batchQueue.push({
                live_id: 'auto',
                username: userText,
                message: msgText,
                profile_picture: avatarUrl,
                avatar_url: avatarUrl,
                platform: 'tiktok'
            });
            startFlushTimer();
        });

        // Ouvinte de perguntas no TikTok
        tiktokConnection.on('questionNew', data => {
            const msgText = data.questionText || data.content || '';
            const userText = data.user?.displayId || data.uniqueId || data.user?.uniqueId || data.user?.nickname || data.nickname || 'Anônimo';
            if (!msgText) return;

            console.log(`[TikTok Service] ❓ Pergunta de @${userText}: "${msgText}"`);

            batchQueue.push({
                live_id: 'auto',
                username: userText,
                message: msgText,
                platform: 'tiktok'
            });
            startFlushTimer();
        });

        tiktokConnection.on('streamEnd', () => {
            console.log(`[TikTok Service] ⏹️ Live de @${cleanUser} foi encerrada.`);
            lastError = 'Live encerrada';
            disconnectCurrent();
            scheduleAutoReconnect();
        });

        tiktokConnection.on('disconnected', () => {
            console.log(`[TikTok Service] 🔌 Desconectado de @${cleanUser}.`);
            disconnectCurrent();
            scheduleAutoReconnect();
        });

        tiktokConnection.on('error', err => {
            lastError = err.message || 'Erro na conexão';
            console.error(`[TikTok Service] ⚠️ Erro na conexão:`, err.message);
        });
    } catch (err) {
        isConnecting = false;
        lastError = err.message;
        console.error(`[TikTok Service] ❌ Exceção ao iniciar conexão:`, err.message);
        scheduleAutoReconnect();
    }
}

app.post('/connect', (req, res) => {
    const { username } = req.body || {};
    const cleanUser = (username || currentUsername || '_minhamania').replace(/^@/, '').trim();

    connectToUser(cleanUser);

    return res.json({ success: true, message: `Conectando em @${cleanUser}...`, username: cleanUser });
});

app.post('/disconnect', (req, res) => {
    shouldAutoReconnect = false;
    currentUsername = null;
    disconnectCurrent();
    lastError = null;
    return res.json({ success: true, message: 'Desconectado do TikTok Live' });
});

app.get('/status', (req, res) => {
    return res.json({
        connected: !!tiktokConnection && !isConnecting && (tiktokConnection.isConnected ? tiktokConnection.isConnected() : true),
        connecting: isConnecting,
        username: currentUsername,
        error: lastError
    });
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`⚡ [TikTok Backend Service] Rodando na porta ${PORT} em 0.0.0.0`);
    
    setTimeout(() => {
        console.log('[TikTok Service] Conectando automaticamente ao @_minhamania');
        connectToUser('_minhamania');
    }, 2000);
});

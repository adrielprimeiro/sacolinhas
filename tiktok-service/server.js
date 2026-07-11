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
let currentUsername = null;
let isConnecting = false;
let lastError = null;

// Fila de Lotes (Batch Buffer)
let batchQueue = [];
let flushTimer = null;

function flushBatch() {
    if (batchQueue.length === 0) return;
    const batch = batchQueue.splice(0, 50);
    fetch(LARAVEL_WEBHOOK_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ messages: batch })
    }).catch(err => {
        console.error('[TikTok Service] Erro ao enviar lote para Laravel:', err.message);
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
    if (tiktokConnection) {
        try {
            tiktokConnection.disconnect();
        } catch (e) {}
        tiktokConnection = null;
    }
    currentUsername = null;
    isConnecting = false;
    stopFlushTimer();
}

app.post('/connect', (req, res) => {
    const { username } = req.body;
    if (!username) {
        return res.status(400).json({ success: false, message: 'Usuário do TikTok é obrigatório' });
    }

    const cleanUser = username.replace(/^@/, '').trim();

    if (currentUsername === cleanUser && tiktokConnection) {
        return res.json({ success: true, message: `Já conectado ao @${cleanUser}`, username: cleanUser });
    }

    disconnectCurrent();
    isConnecting = true;
    lastError = null;
    currentUsername = cleanUser;

    console.log(`[TikTok Service] Conectando à live de @${cleanUser}...`);

    tiktokConnection = new TikTokLiveConnection(cleanUser, {});

    tiktokConnection.connect().then(state => {
        isConnecting = false;
        lastError = null;
        console.log(`[TikTok Service] Conectado com sucesso ao @${cleanUser} (Room ID: ${state.roomId})`);
        startFlushTimer();
    }).catch(err => {
        isConnecting = false;
        lastError = err.message || 'Falha ao conectar na live';
        console.error(`[TikTok Service] Falha ao conectar em @${cleanUser}:`, err.message);
        disconnectCurrent();
    });

    // Ouvinte de mensagens do chat
    tiktokConnection.on('chat', data => {
        const msgText = data.content || data.comment;
        const userText = data.user?.displayId || data.user?.nickname || data.uniqueId || data.nickname || 'Anônimo';
        if (!msgText) return;
        batchQueue.push({
            live_id: 'auto',
            username: userText,
            message: msgText,
            profile_picture: (data.user?.avatarThumb?.urlList && data.user.avatarThumb.urlList.length > 0) ? data.user.avatarThumb.urlList[0] : '',
            platform: 'tiktok'
        });
        startFlushTimer();
    });

    tiktokConnection.on('streamEnd', () => {
        console.log(`[TikTok Service] Live de @${cleanUser} foi encerrada.`);
        lastError = 'Live encerrada';
        disconnectCurrent();
    });

    tiktokConnection.on('disconnected', () => {
        console.log(`[TikTok Service] Desconectado de @${cleanUser}.`);
        disconnectCurrent();
    });

    tiktokConnection.on('error', err => {
        lastError = err.message || 'Erro na conexão';
        console.error(`[TikTok Service] Erro na conexão:`, err.message);
    });

    return res.json({ success: true, message: `Conectando em @${cleanUser}...`, username: cleanUser });
});

app.post('/disconnect', (req, res) => {
    disconnectCurrent();
    lastError = null;
    return res.json({ success: true, message: 'Desconectado do TikTok Live' });
});

app.get('/status', (req, res) => {
    return res.json({
        connected: !!tiktokConnection && !isConnecting,
        connecting: isConnecting,
        username: currentUsername,
        error: lastError
    });
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`⚡ [TikTok Backend Service] Rodando na porta ${PORT} em 0.0.0.0`);
});

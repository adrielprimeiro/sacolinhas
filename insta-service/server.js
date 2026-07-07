const express = require('express');
const puppeteer = require('puppeteer');

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

const PORT = 3002;
const LARAVEL_WEBHOOK_URL = process.env.LARAVEL_URL || 'http://127.0.0.1:8000/api/live-chat/message-batch';

let browserInstance = null;
let pageInstance = null;
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
        console.error('[Insta Service] Erro ao enviar lote para Laravel:', err.message);
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

async function disconnectCurrent() {
    if (browserInstance) {
        try {
            await browserInstance.close();
        } catch (e) {}
        browserInstance = null;
        pageInstance = null;
    }
    currentUsername = null;
    isConnecting = false;
    stopFlushTimer();
}

app.post('/connect', async (req, res) => {
    const { username } = req.body;
    if (!username) {
        return res.status(400).json({ success: false, message: 'Usuário do Instagram é obrigatório' });
    }

    const cleanUser = username.replace(/^@/, '').trim();

    if (currentUsername === cleanUser && browserInstance) {
        return res.json({ success: true, message: `Já conectado ao @${cleanUser}`, username: cleanUser });
    }

    await disconnectCurrent();
    isConnecting = true;
    lastError = null;
    currentUsername = cleanUser;

    console.log(`[Insta Service] Conectando à live de @${cleanUser} via Puppeteer...`);

    try {
        browserInstance = await puppeteer.launch({
            headless: "new",
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--window-size=1280,800'
            ],
            userDataDir: './insta_session'
        });

        pageInstance = await browserInstance.newPage();
        await pageInstance.setUserAgent('Mozilla/50.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

        await pageInstance.exposeFunction('onInstagramComment', (data) => {
            if (!data || !data.username || !data.message) return;
            batchQueue.push({
                live_id: 'auto',
                username: data.username,
                message: data.message,
                profile_picture: data.profile_picture || '',
                platform: 'instagram'
            });
            startFlushTimer();
        });

        const liveUrl = `https://www.instagram.com/${cleanUser}/live/`;
        await pageInstance.goto(liveUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });

        // Injetar observador no DOM para extrair comentários
        await pageInstance.evaluate(() => {
            window.__seenComments = new Set();
            setInterval(() => {
                try {
                    const imgs = document.querySelectorAll("img[alt*='profile'], img[src*='s150x150'], img[src*='instagram']");
                    imgs.forEach(img => {
                        let row = img.closest("div[class], section, li") || img.parentElement;
                        for (let depth = 0; row && depth < 5; depth++) {
                            const text = (row.textContent || "").trim();
                            if (text && text.length > 2 && text.length < 300) {
                                const leaves = row.querySelectorAll("span, div, a");
                                let username = "";
                                let message = "";
                                for (let i = 0; i < leaves.length; i++) {
                                    let t = (leaves[i].textContent || "").trim();
                                    if (!t || t.includes("entrou") || t.includes("joined") || t === "...") continue;
                                    if (!username && t.length < 30 && !t.includes(" ")) {
                                        username = t;
                                    } else if (username && t !== username) {
                                        message = t;
                                        break;
                                    }
                                }
                                if (username && message && !window.__seenComments.has(username + ":" + message)) {
                                    window.__seenComments.add(username + ":" + message);
                                    if (window.__seenComments.size > 1500) window.__seenComments.clear();
                                    window.onInstagramComment({
                                        username: username.replace(/^@/, ''),
                                        message: message,
                                        profile_picture: img.src || ''
                                    });
                                }
                                break;
                            }
                            row = row.parentElement;
                        }
                    });
                } catch(e) {}
            }, 800);
        });

        isConnecting = false;
        lastError = null;
        console.log(`[Insta Service] Conectado com sucesso à live de @${cleanUser}`);
        startFlushTimer();

        return res.json({ success: true, message: `Conectado em @${cleanUser}...`, username: cleanUser });
    } catch (err) {
        isConnecting = false;
        lastError = err.message || 'Falha ao conectar no Instagram';
        console.error(`[Insta Service] Erro ao conectar em @${cleanUser}:`, err.message);
        await disconnectCurrent();
        return res.status(500).json({ success: false, message: lastError });
    }
});

app.post('/disconnect', async (req, res) => {
    await disconnectCurrent();
    lastError = null;
    return res.json({ success: true, message: 'Desconectado do Instagram Live' });
});

app.get('/status', (req, res) => {
    return res.json({
        connected: !!browserInstance && !isConnecting,
        connecting: isConnecting,
        username: currentUsername,
        error: lastError
    });
});

app.listen(PORT, () => {
    console.log(`⚡ [Instagram Headless Service] Rodando na porta ${PORT}`);
});

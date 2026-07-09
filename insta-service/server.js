const express = require('express');
const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

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

        // Carregar cookies do Instagram se existirem em insta_session/cookies.json
        const cookiesPath = path.resolve(__dirname, 'insta_session/cookies.json');
        if (fs.existsSync(cookiesPath)) {
            try {
                const cookiesContent = fs.readFileSync(cookiesPath, 'utf8');
                const cookies = JSON.parse(cookiesContent);
                if (Array.isArray(cookies) && cookies.length > 0) {
                    const formattedCookies = cookies.map(c => {
                        let domain = c.domain || '.instagram.com';
                        if (!domain.startsWith('.')) domain = '.' + domain.replace(/^www\./, '');
                        return {
                            name: c.name,
                            value: c.value,
                            domain: domain,
                            path: c.path || '/',
                            secure: c.secure !== undefined ? c.secure : true,
                            httpOnly: c.httpOnly !== undefined ? c.httpOnly : false,
                            sameSite: (c.sameSite === 'no_restriction' || c.sameSite === 'None' || c.sameSite === 'none') ? 'None' : (c.sameSite === 'Lax' || c.sameSite === 'lax' ? 'Lax' : 'Strict')
                        };
                    });
                    await pageInstance.setCookie(...formattedCookies);
                    console.log(`[Insta Service] 🍪 ${formattedCookies.length} cookies injetados com sucesso!`);
                }
            } catch (err) {
                console.error('[Insta Service] Erro ao processar insta_session/cookies.json:', err.message);
            }
        } else {
            console.log('[Insta Service] ⚠️ Arquivo insta_session/cookies.json não encontrado.');
        }

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

        let currentUrl = pageInstance.url();
        if (currentUrl.includes('login') || currentUrl.includes('challenge')) {
            console.log(`[Insta Service] ⚠️ ALERTA: O Instagram redirecionou para a tela de login (${currentUrl}). O arquivo de cookies precisa ser atualizado.`);
        } else {
            console.log(`[Insta Service] 🌟 Página carregada em: ${currentUrl}`);
            
            // Se redirecionou para o perfil (/de_minha_mania/), tentar clicar no anel/foto de perfil para abrir a live
            if (currentUrl.replace(/\/$/, '').endsWith(cleanUser.toLowerCase())) {
                console.log(`[Insta Service] Redirecionado para o perfil @${cleanUser}. Tentando clicar na foto de perfil (Live Ring)...`);
                try {
                    await pageInstance.waitForSelector("header img, header canvas, header div[role='button']", { timeout: 5000 });
                    await pageInstance.evaluate(() => {
                        const headerImg = document.querySelector("header img, header canvas, header div[role='button']");
                        if (headerImg) headerImg.click();
                    });
                    await new Promise(r => setTimeout(r, 4000));
                    currentUrl = pageInstance.url();
                    console.log(`[Insta Service] URL após clique no anel da Live: ${currentUrl}`);
                } catch (e) {
                    console.log(`[Insta Service] Aviso ao clicar no anel da Live: ${e.message}`);
                }
            }
        }

        // Tirar screenshot de diagnóstico para podermos ver exatamente a tela no navegador via https://minhamania.net/insta_debug.png
        try {
            await pageInstance.screenshot({ path: path.resolve(__dirname, '../public/insta_debug.png') });
            console.log(`[Insta Service] 📸 Screenshot salva em /public/insta_debug.png`);
        } catch (e) {}

        // Injetar observador no DOM para extrair comentários
        await pageInstance.evaluate(() => {
            window.__seenComments = new Set();
            setInterval(() => {
                try {
                    const scope = document.querySelector('[role="dialog"]') || document.querySelector("section") || document.body;
                    const imgs = scope.querySelectorAll("img[alt*='profile'], img[src*='s150x150'], img[src*='instagram'], img[src*='cdninstagram']");
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
                                    if (!t || t.toLowerCase() === 'responder' || t.toLowerCase() === 'reply' || t.includes("entrou") || t.includes("joined") || t === "...") continue;
                                    if (!username && t.length < 30 && !t.includes(" ")) {
                                        username = t;
                                    } else if (username && t !== username) {
                                        message = t;
                                        break;
                                    }
                                }
                                if (username && message && !window.__seenComments.has(username + ":" + message)) {
                                    const lowerMsg = message.toLowerCase();
                                    if (lowerMsg !== 'entrou' && lowerMsg !== 'joined' && !lowerMsg.includes('acenou') && !lowerMsg.includes('participar')) {
                                        window.__seenComments.add(username + ":" + message);
                                        if (window.__seenComments.size > 1500) window.__seenComments.clear();
                                        window.onInstagramComment({
                                            username: username.replace(/^@/, ''),
                                            message: message,
                                            profile_picture: img.src || ''
                                        });
                                        break;
                                    }
                                }
                            }
                            row = row.parentElement;
                        }
                    });
                } catch (e) {}
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

app.listen(PORT, '0.0.0.0', () => {
    console.log(`⚡ [Instagram Headless Service] Rodando na porta ${PORT} em 0.0.0.0`);
});

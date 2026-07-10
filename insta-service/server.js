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

        await pageInstance.exposeFunction('onInstagramIdleTimeout', async () => {
            console.log(`[Insta Service] Auto-desconectando robô por inatividade (economia de memória RAM)...`);
            await disconnectCurrent();
        });

        const liveUrl = `https://www.instagram.com/${cleanUser}/live/`;
        await pageInstance.goto(liveUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });

        let currentUrl = pageInstance.url();
        if (currentUrl.includes('login') || currentUrl.includes('challenge')) {
            console.log(`[Insta Service] ⚠️ ALERTA: O Instagram redirecionou para a tela de login (${currentUrl}). O arquivo de cookies precisa ser atualizado.`);
        } else {
            console.log(`[Insta Service] 🌟 Página carregada em: ${currentUrl}`);
            
            // Se redirecionou para o perfil (/de_minha_mania/), aguardar carregamento completo do DOM
            if (currentUrl.replace(/\/$/, '').endsWith(cleanUser.toLowerCase())) {
                console.log(`[Insta Service] Redirecionado para o perfil @${cleanUser}. Aguardando 4s para renderização do canvas da Live...`);
                await new Promise(r => setTimeout(r, 4000));

                // 1. Pressionar a tecla Escape 2 vezes e tentar fechar qualquer modal aberto ("Nova nota", "Notificações", etc)
                try {
                    await pageInstance.keyboard.press('Escape');
                    await new Promise(r => setTimeout(r, 800));
                    await pageInstance.keyboard.press('Escape');
                    await pageInstance.evaluate(() => {
                        const dialog = document.querySelector('[role="dialog"]');
                        if (dialog) {
                            // O primeiro botão ou SVG no topo esquerdo do dialog costuma ser o X (Fechar)
                            const firstClickable = dialog.querySelector('svg, div[role="button"], button');
                            if (firstClickable) firstClickable.closest('div[role="button"], button')?.click() || firstClickable.click();
                        }
                    });
                    await new Promise(r => setTimeout(r, 1500));
                } catch (e) {}

                // 2. Clicar estritamente no CANVAS (anel da Live/Story ao redor da foto) e no seu container pai
                try {
                    console.log(`[Insta Service] Clicando no elemento <canvas> (anel de Live/Story)...`);
                    await pageInstance.evaluate(() => {
                        const c = document.querySelector('canvas');
                        if (c) {
                            c.click();
                            if (c.parentElement) c.parentElement.click();
                        } else {
                            // Se não houver canvas (nenhum anel colorido na hora), tentar a foto do perfil sem pegar a Nota
                            const avatarSpan = document.querySelector('header img[alt*="Foto do perfil"], header img[alt*="profile picture"]')?.closest('span, div[role="button"]');
                            if (avatarSpan) avatarSpan.click();
                        }
                    });
                    await new Promise(r => setTimeout(r, 4000));
                    
                    // 3. Se abriu modal perguntando "Ver Story" vs "Assistir a vídeo ao vivo", clicar em "Assistir a vídeo ao vivo"
                    await pageInstance.evaluate(() => {
                        const allElems = document.querySelectorAll('div[role="button"], div[role="dialog"] div, span, button, a');
                        for (const el of allElems) {
                            const txt = (el.textContent || "").trim().toLowerCase();
                            if (txt.includes('assistir') && (txt.includes('ao vivo') || txt.includes('live'))) {
                                el.click();
                                break;
                            } else if (txt === 'assistir a vídeo ao vivo' || txt === 'watch live video') {
                                el.click();
                                break;
                            }
                        }
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

                    // AUTO-WATCHDOG DE MEMÓRIA E RECONEXÃO:
                    // Se passar 15 minutos sem nenhum comentário novo (live encerrada), auto-desconectar para liberar RAM (fechar Chromium)
                    if (!window.__lastActivityTime) window.__lastActivityTime = Date.now();
                    if (window.__lastSeenCount !== window.__seenComments.size) {
                        window.__lastSeenCount = window.__seenComments.size;
                        window.__lastActivityTime = Date.now();
                    }
                    if (Date.now() - window.__lastActivityTime > 15 * 60 * 1000) {
                        console.log("[Insta Service] ⏰ 15 minutos sem novos comentários. Live encerrada. Fechando Chromium para economizar RAM...");
                        window.onInstagramIdleTimeout();
                    } else if (Date.now() - window.__lastActivityTime > 3 * 60 * 1000) {
                        // Se faz 3 minutos sem comentários e estamos no perfil, tentar clicar no anel <canvas> para entrar na nova live
                        if (!window.__lastRingRetry || Date.now() - window.__lastRingRetry > 3 * 60 * 1000) {
                            window.__lastRingRetry = Date.now();
                            const c = document.querySelector('header canvas');
                            if (c) {
                                console.log("[Insta Service] 🔄 Tentando entrar na live via clique no canvas...");
                                c.click();
                                if (c.parentElement) c.parentElement.click();
                            }
                        }
                    }
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

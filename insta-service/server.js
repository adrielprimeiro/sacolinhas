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

        // Carregar cookies do Instagram de insta_session/cookies.json apenas se não houver sessão ativa
        const existingCookies = await pageInstance.cookies('https://www.instagram.com');
        const hasActiveSession = existingCookies.some(c => c.name === 'sessionid');
        const cookiesPath = path.resolve(__dirname, 'insta_session/cookies.json');
        if (!hasActiveSession && fs.existsSync(cookiesPath)) {
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
        } else if (hasActiveSession) {
            console.log(`[Insta Service] ⚡ Sessão ativa encontrada no perfil de navegador (userDataDir). Mantendo sessão!`);
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

        const profileUrl = `https://www.instagram.com/${cleanUser}/`;
        await pageInstance.goto(profileUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });

        let currentUrl = pageInstance.url();
        if (currentUrl.includes('login') || currentUrl.includes('challenge')) {
            console.log(`[Insta Service] ⚠️ ALERTA: O Instagram redirecionou para a tela de login (${currentUrl}). O arquivo de cookies precisa ser atualizado.`);
        } else {
            console.log(`[Insta Service] 🌟 Página carregada em: ${currentUrl}`);
            
            // Se redirecionou para feed ou não está no perfil, garantir que vamos para o perfil
            if (!currentUrl.toLowerCase().includes(cleanUser.toLowerCase())) {
                console.log(`[Insta Service] Redirecionado para fora do perfil (${currentUrl}). Forçando ida para @${cleanUser}...`);
                await pageInstance.goto(profileUrl, { waitUntil: 'domcontentloaded', timeout: 45000 });
                currentUrl = pageInstance.url();
            }

            // Se não estamos dentro da URL de live ainda, tentar entrar pela foto de perfil (Canvas/Ring)
            if (!currentUrl.includes('/live/')) {
                console.log(`[Insta Service] No perfil @${cleanUser}. Aguardando 4s para renderização do canvas da Live...`);
                await new Promise(r => setTimeout(r, 4000));

                // 1. Pressionar a tecla Escape 2 vezes e tentar fechar qualquer modal aberto ("Nova nota", "Notificações", etc)
                try {
                    await pageInstance.keyboard.press('Escape');
                    await new Promise(r => setTimeout(r, 800));
                    await pageInstance.keyboard.press('Escape');
                    await pageInstance.evaluate(() => {
                        const dialog = document.querySelector('[role="dialog"]');
                        if (dialog) {
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
                        const c = document.querySelector('header canvas, canvas');
                        if (c) {
                            c.click();
                            if (c.parentElement) c.parentElement.click();
                        } else {
                            const avatarSpan = document.querySelector('header img[alt*="Foto do perfil"], header img[alt*="profile picture"]')?.closest('span, div[role="button"], a');
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

                // 4. Se ainda não entrou na URL /live/, tentar ir direto para /live/
                if (!pageInstance.url().includes('/live/')) {
                    console.log(`[Insta Service] Tentando URL direta de live: https://www.instagram.com/${cleanUser}/live/`);
                    try {
                        await pageInstance.goto(`https://www.instagram.com/${cleanUser}/live/`, { waitUntil: 'domcontentloaded', timeout: 30000 });
                        await new Promise(r => setTimeout(r, 4000));
                    } catch(e) {}
                }
            }
        }

        // Tirar screenshot de diagnóstico para podermos ver exatamente a tela no navegador via https://minhamania.net/insta_debug.png
        try {
            await pageInstance.screenshot({ path: path.resolve(__dirname, '../public/insta_debug.png') });
            console.log(`[Insta Service] 📸 Screenshot salva em /public/insta_debug.png`);
        } catch (e) {}

        // Se após a navegação a sessão estiver ativa, atualizar o arquivo de cookies local
        try {
            const currentSessionCookies = await pageInstance.cookies('https://www.instagram.com');
            if (currentSessionCookies.some(c => c.name === 'sessionid')) {
                const cookiesPath = path.resolve(__dirname, 'insta_session/cookies.json');
                fs.writeFileSync(cookiesPath, JSON.stringify(currentSessionCookies, null, 4));
                console.log(`[Insta Service] 🍪 Sessão ativa atualizada e persistida no servidor em cookies.json!`);
            }
        } catch(e) {}

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

app.post('/eval', async (req, res) => {
    if (!pageInstance) return res.status(400).json({ error: 'No active browser page' });
    try {
        const codeStr = req.body.code;
        const fn = new Function(`return (${codeStr})();`);
        const result = await pageInstance.evaluate(fn);
        return res.json({ success: true, result });
    } catch(e) {
        return res.status(500).json({ success: false, error: e.message });
    }
});

app.post('/login', async (req, res) => {
    const { username, password } = req.body;
    if (!username || !password) {
        return res.status(400).json({ success: false, message: 'Usuário e senha são obrigatórios para login no servidor.' });
    }

    const cleanUser = username.replace(/^@/, '').trim();

    await disconnectCurrent();
    isConnecting = true;
    lastError = null;

    console.log(`[Insta Service] 🔑 Iniciando login para @${cleanUser} no servidor VPS...`);

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

        console.log(`[Insta Service] Navegando para tela de login do Instagram...`);
        await pageInstance.goto('https://www.instagram.com/accounts/login/', { waitUntil: 'domcontentloaded', timeout: 60000 });
        await new Promise(r => setTimeout(r, 4000));

        // Pressionar Escape para fechar modais de cookies caso apareçam (Europa/LGPD/etc)
        try { await pageInstance.keyboard.press('Escape'); } catch(e){}

        // Tentar preencher usuário e senha e submeter
        const userFilled = await pageInstance.evaluate(async (usr, pwd) => {
            const userInp = document.querySelector('input[name="username"]');
            const passInp = document.querySelector('input[name="password"]');
            if (userInp && passInp) {
                userInp.focus();
                userInp.value = usr;
                userInp.dispatchEvent(new Event('input', { bubbles: true }));
                userInp.dispatchEvent(new Event('change', { bubbles: true }));
                
                passInp.focus();
                passInp.value = pwd;
                passInp.dispatchEvent(new Event('input', { bubbles: true }));
                passInp.dispatchEvent(new Event('change', { bubbles: true }));

                const btn = document.querySelector('button[type="submit"]');
                if (btn) {
                    btn.click();
                    return true;
                }
            }
            return false;
        }, cleanUser, password);

        if (!userFilled) {
            console.log(`[Insta Service] Inputs de login não encontrados. URL atual: ${pageInstance.url()}`);
        } else {
            console.log(`[Insta Service] Submetido formulário de login. Aguardando resposta do Instagram (6s)...`);
            await new Promise(r => setTimeout(r, 6000));
        }

        let currentUrl = pageInstance.url();
        console.log(`[Insta Service] URL após tentativa de login: ${currentUrl}`);

        // Salvar screenshot do resultado do login para inspeção e visualização pelo painel
        const loginChallengePath = path.resolve(__dirname, '../public/insta_login_challenge.png');
        try { await pageInstance.screenshot({ path: loginChallengePath }); } catch(e){}

        // Verificar se pediu código 2FA / desafio de segurança (SMS/Email/App)
        if (currentUrl.includes('challenge') || currentUrl.includes('two_factor') || currentUrl.includes('login/two_factor')) {
            isConnecting = false;
            return res.json({
                success: false,
                status: 'challenge',
                message: 'O Instagram enviou um código de segurança (2FA/Challenge) para o seu e-mail, SMS ou aplicativo autenticador. Digite o código recebido no campo abaixo para aprovar o login.'
            });
        }

        // Salvar novos cookies
        const cookies = await pageInstance.cookies('https://www.instagram.com');
        const cookiesPath = path.resolve(__dirname, 'insta_session/cookies.json');
        if (cookies.length > 0) {
            fs.writeFileSync(cookiesPath, JSON.stringify(cookies, null, 4));
            console.log(`[Insta Service] 🍪 ${cookies.length} cookies salvos no servidor em insta_session/cookies.json após login!`);
        }

        isConnecting = false;
        if (cookies.some(c => c.name === 'sessionid') || !currentUrl.includes('login')) {
            currentUsername = cleanUser;
            return res.json({ success: true, message: `🎉 Login efetuado com sucesso no Servidor! Sessão e Cookies salvos na VPS. Agora clique no botão Conectar para iniciar a captura da Live.` });
        } else {
            return res.status(400).json({ success: false, message: `Não foi possível concluir o login. Verifique se o usuário e senha estão corretos. (Uma captura de tela foi salva em /insta_login_challenge.png)` });
        }
    } catch (err) {
        isConnecting = false;
        lastError = err.message || 'Erro durante o login';
        console.error(`[Insta Service] Erro no login:`, err.message);
        await disconnectCurrent();
        return res.status(500).json({ success: false, message: lastError });
    }
});

app.post('/login-code', async (req, res) => {
    const { code } = req.body;
    if (!code || !pageInstance) {
        return res.status(400).json({ success: false, message: 'Código de verificação ou sessão do navegador inválida/encerrada.' });
    }

    console.log(`[Insta Service] 🔑 Inserindo código de verificação (${code})...`);

    try {
        await pageInstance.evaluate((c) => {
            const inps = document.querySelectorAll('input[name="verificationCode"], input[name="security_code"], input[type="tel"], input[placeholder*="Código"], input');
            for (const inp of inps) {
                if (inp.type !== 'hidden' && inp.type !== 'submit') {
                    inp.focus();
                    inp.value = c;
                    inp.dispatchEvent(new Event('input', { bubbles: true }));
                    inp.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
            const btns = document.querySelectorAll('button[type="submit"], button, div[role="button"]');
            for (const b of btns) {
                const txt = (b.textContent || "").trim().toLowerCase();
                if (txt.includes('confirm') || txt.includes('enviar') || txt.includes('submit') || b.type === 'submit') {
                    b.click();
                    break;
                }
            }
        }, code);

        await new Promise(r => setTimeout(r, 6000));
        let currentUrl = pageInstance.url();
        console.log(`[Insta Service] URL após envio do código 2FA: ${currentUrl}`);

        const loginChallengePath = path.resolve(__dirname, '../public/insta_login_challenge.png');
        try { await pageInstance.screenshot({ path: loginChallengePath }); } catch(e){}

        const cookies = await pageInstance.cookies('https://www.instagram.com');
        const cookiesPath = path.resolve(__dirname, 'insta_session/cookies.json');
        if (cookies.length > 0) {
            fs.writeFileSync(cookiesPath, JSON.stringify(cookies, null, 4));
            console.log(`[Insta Service] 🍪 ${cookies.length} cookies salvos após confirmação do código!`);
        }

        if (cookies.some(c => c.name === 'sessionid') || !currentUrl.includes('challenge')) {
            return res.json({ success: true, message: `✔️ Código confirmado e sessão validada! Cookies salvos no servidor da VPS. Agora você já pode clicar em Conectar na Live.` });
        } else {
            return res.status(400).json({ success: false, message: `Código incorreto ou verificação ainda pendente no Instagram. (Verifique o print /insta_login_challenge.png)` });
        }
    } catch (err) {
        return res.status(500).json({ success: false, message: err.message });
    }
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`⚡ [Instagram Headless Service] Rodando na porta ${PORT} em 0.0.0.0`);
});

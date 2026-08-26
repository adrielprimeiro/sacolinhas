const { TikTokLiveConnection } = require("tiktok-live-connector");
const axios = require("axios");

const API_BASE_URL = "https://minhamania.net/api";
const POLL_INTERVAL_MS = 10000;
let currentUsername = null;
let currentLiveId = null;
let tiktokConnection = null;
let isConnecting = false;
let failCount = 0;
let nextRetryAfter = 0;

async function checkActiveLives() {
    try {
        const response = await axios.get(API_BASE_URL + "/active-tiktok-lives");
        const data = response.data;
        if (data.success && data.active_live) {
            const { username, live_id } = data.active_live;
            if (currentUsername === username && tiktokConnection) return;
            if (Date.now() < nextRetryAfter) {
                const waitSec = Math.ceil((nextRetryAfter - Date.now()) / 1000);
                console.log("[Backoff] Aguardando " + waitSec + "s...");
                return;
            }
            console.log("[TikTok] Nova live: @" + username + " (ID: " + live_id + ")");
            await connectToTikTok(username, live_id);
        } else {
            if (tiktokConnection) {
                console.log("[TikTok] Live encerrada. Desconectando...");
                try { tiktokConnection.disconnect(); } catch(_) {}
                tiktokConnection = null; currentUsername = null; currentLiveId = null;
                failCount = 0; nextRetryAfter = 0;
            }
        }
    } catch (e) {
        console.error("[API] Erro: " + e.message);
    }
}

async function connectToTikTok(username, liveId) {
    if (isConnecting) return;
    isConnecting = true;
    if (tiktokConnection) { try { tiktokConnection.disconnect(); } catch(_) {} }
    currentUsername = username;
    currentLiveId = liveId;
    console.log("[TikTok] Conectando a @" + username + "...");
    tiktokConnection = new TikTokLiveConnection(username, {
        processInitialData: false,
        enableExtendedGiftInfo: false,
        enableWebsocketUpgrade: true,
        requestPollingIntervalMs: 2000,
        clientParams: { app_language: "pt-BR", device_platform: "web" }
    });
    try {
        const state = await tiktokConnection.connect();
        console.log("[TikTok] Conectado! Room ID: " + state.roomId);
        failCount = 0; nextRetryAfter = 0;
        tiktokConnection.on("chat", (data) => {
            const user = (data.user && data.user.displayId) || data.uniqueId || (data.user && data.user.nickname);
            const comment = data.comment || data.content || data.text;
            const avatar = (data.user && data.user.profilePictureUrl) || data.profilePictureUrl || null;
            if (user && comment) {
                console.log("[Chat] @" + user + ": " + comment);
                sendToLaravel(user, comment, currentLiveId, avatar);
            }
        });
        tiktokConnection.on("disconnected", () => {
            console.log("[TikTok] Desconectado de @" + username);
            tiktokConnection = null; currentUsername = null;
        });
    } catch (err) {
        tiktokConnection = null; currentUsername = null;
        failCount++;
        const waitMs = Math.min(30000 * Math.pow(2, failCount - 1), 300000);
        nextRetryAfter = Date.now() + waitMs;
        console.error("[TikTok] Falha (tentativa " + failCount + "): " + err.message);
        console.log("[TikTok] Proxima tentativa em " + Math.ceil(waitMs/1000) + "s");
    } finally {
        isConnecting = false;
    }
}

async function sendToLaravel(username, message, liveId, avatarUrl) {
    if (!liveId) return;
    try {
        await axios.post(API_BASE_URL + "/live-chat/message", {
            live_id: liveId, platform: "tiktok",
            username: username, message: message, avatar_url: avatarUrl
        }, { headers: { "Content-Type": "application/json", "Accept": "application/json" } });
    } catch (e) {
        console.error("[Laravel] Erro: " + e.message);
    }
}

console.log("=== TikTok Live Listener LOCAL Iniciado ===");
console.log("Consultando API a cada " + (POLL_INTERVAL_MS/1000) + "s...");
checkActiveLives();
setInterval(checkActiveLives, POLL_INTERVAL_MS);

// Micro-servidor HTTP local para comandos instantâneos vindos do painel web
const http = require("http");
const localServer = http.createServer((req, res) => {
    res.setHeader("Access-Control-Allow-Origin", "*");
    res.setHeader("Access-Control-Allow-Methods", "GET, POST, OPTIONS");
    res.setHeader("Access-Control-Allow-Headers", "Content-Type");

    if (req.method === "OPTIONS") {
        res.writeHead(204);
        res.end();
        return;
    }

    if (req.url === "/status" || req.url === "/ping") {
        res.writeHead(200, { "Content-Type": "application/json" });
        res.end(JSON.stringify({
            running: true,
            connected: !!tiktokConnection,
            username: currentUsername,
            live_id: currentLiveId
        }));
        return;
    }

    if (req.url === "/check-now" || req.url === "/trigger") {
        checkActiveLives();
        res.writeHead(200, { "Content-Type": "application/json" });
        res.end(JSON.stringify({ success: true, message: "Checagem executada" }));
        return;
    }

    res.writeHead(404);
    res.end();
});

localServer.listen(3002, "127.0.0.1", () => {
    console.log("[Servidor Local] Pronto para comandos do painel em http://127.0.0.1:3002");
});

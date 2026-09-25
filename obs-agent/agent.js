/**
 * sacolinhas-obs-agent
 *
 * Roda no PC A (que tem o OBS Studio).
 * Faz polling no servidor web e executa comandos OBS via WebSocket local.
 *
 * Configuração: edite as variáveis abaixo ou use variáveis de ambiente.
 *
 * Uso:
 *   npm install
 *   node agent.js
 *
 * Variáveis de ambiente (opcional):
 *   SERVER_URL   → URL base do servidor (ex: https://vps.sacolinhas.com.br)
 *   SERVER_TOKEN → Token de autenticação (igual ao SANCTUM_TOKEN ou session cookie)
 *   OBS_HOST     → Host do OBS WebSocket (padrão: 127.0.0.1)
 *   OBS_PORT     → Porta do OBS WebSocket (padrão: 4455)
 *   OBS_PASSWORD → Senha do OBS WebSocket
 *   POLL_MS      → Intervalo de polling em ms (padrão: 1500)
 */

import OBSWebSocket from 'obs-websocket-js';
import fetch from 'node-fetch';
import { randomUUID } from 'crypto';
import { config as dotenvConfig } from 'dotenv';
dotenvConfig(); // carrega .env se existir

// ─── CONFIGURAÇÃO ───────────────────────────────────────────────────────────
const SERVER_URL   = process.env.SERVER_URL   || 'https://vps55549.publiccloud.com.br';
const SERVER_TOKEN = process.env.SERVER_TOKEN || '';   // Preencha com um token de API
const OBS_HOST     = process.env.OBS_HOST     || '127.0.0.1';
const OBS_PORT     = process.env.OBS_PORT     || '4455';
const OBS_PASSWORD = process.env.OBS_PASSWORD || '';
const POLL_MS      = parseInt(process.env.POLL_MS || '1500');
const AGENT_ID     = process.env.AGENT_ID     || 'agent-' + randomUUID().slice(0, 8);
// ─────────────────────────────────────────────────────────────────────────────

const obs = new OBSWebSocket();
let obsConnected = false;
let obsReconnectTimer = null;

// ─── OBS CONNECTION ──────────────────────────────────────────────────────────

async function connectObs() {
    try {
        const url = `ws://${OBS_HOST}:${OBS_PORT}`;
        await obs.connect(url, OBS_PASSWORD);
        obsConnected = true;
        console.log(`[OBS] ✅ Conectado em ${url}`);
    } catch (e) {
        obsConnected = false;
        console.warn('[OBS] ⚠️  Falha ao conectar:', e.message);
        scheduleReconnect();
    }
}

function scheduleReconnect() {
    if (obsReconnectTimer) return;
    obsReconnectTimer = setTimeout(async () => {
        obsReconnectTimer = null;
        console.log('[OBS] Tentando reconectar...');
        await connectObs();
    }, 5000);
}

obs.on('ConnectionClosed', () => {
    if (obsConnected) {
        console.warn('[OBS] Conexão encerrada. Reconectando...');
        obsConnected = false;
        scheduleReconnect();
    }
});

obs.on('ConnectionError', () => {
    obsConnected = false;
    scheduleReconnect();
});

// ─── SERVER HTTP ─────────────────────────────────────────────────────────────

function buildHeaders() {
    const h = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Agent-ID': AGENT_ID,
    };
    if (SERVER_TOKEN) {
        h['Authorization'] = 'Bearer ' + SERVER_TOKEN;
    }
    return h;
}

async function serverGet(path) {
    const res = await fetch(SERVER_URL + path, {
        method: 'GET',
        headers: buildHeaders(),
    });
    if (!res.ok) throw new Error(`HTTP ${res.status} em GET ${path}`);
    return res.json();
}

async function serverPost(path, body) {
    const res = await fetch(SERVER_URL + path, {
        method: 'POST',
        headers: buildHeaders(),
        body: JSON.stringify(body),
    });
    if (!res.ok) throw new Error(`HTTP ${res.status} em POST ${path}`);
    return res.json();
}

// ─── EXECUÇÃO DE COMANDOS ────────────────────────────────────────────────────

async function executeCommand(cmd) {
    const { id, command_type, payload } = cmd;
    console.log(`[CMD] Executando #${id}: ${command_type}`, payload || '');

    if (!obsConnected) {
        console.warn(`[CMD] OBS não conectado. Reportando erro para #${id}`);
        await serverPost(`/admin/obs-relay/${id}/done`, {
            error: true,
            result: { error: 'OBS não conectado no agente' },
        });
        return;
    }

    try {
        let result = {};

        if (command_type === 'StartRecord') {
            await obs.call('StartRecord');
            result = { started: true };
            console.log(`[CMD] ✅ StartRecord OK`);
        } else if (command_type === 'StopRecord') {
            const res = await obs.call('StopRecord');
            result = { outputPath: res?.outputPath || null };
            console.log(`[CMD] ✅ StopRecord OK — arquivo:`, result.outputPath);
        } else if (command_type === 'GetRecordStatus') {
            const res = await obs.call('GetRecordStatus');
            result = res;
        } else if (command_type === 'GetVersion') {
            const res = await obs.call('GetVersion');
            result = res;
        } else {
            throw new Error('Comando desconhecido: ' + command_type);
        }

        await serverPost(`/admin/obs-relay/${id}/done`, { error: false, result });
    } catch (e) {
        console.error(`[CMD] ❌ Erro ao executar #${id}:`, e.message);
        await serverPost(`/admin/obs-relay/${id}/done`, {
            error: true,
            result: { error: e.message },
        });
    }
}

// ─── POLLING LOOP ─────────────────────────────────────────────────────────────

async function pollLoop() {
    try {
        const data = await serverGet('/admin/obs-relay/pending');
        if (data.commands && data.commands.length > 0) {
            // Executa os comandos em sequência (não paralelo, para preservar ordem)
            for (const cmd of data.commands) {
                await executeCommand(cmd);
            }
        }
    } catch (e) {
        console.warn('[Poll] Erro ao consultar servidor:', e.message);
    }

    setTimeout(pollLoop, POLL_MS);
}

// ─── MAIN ─────────────────────────────────────────────────────────────────────

console.log('');
console.log('╔══════════════════════════════════════════════╗');
console.log('║      Sacolinhas OBS Agent  v1.0.0           ║');
console.log('╚══════════════════════════════════════════════╝');
console.log('');
console.log(`  Servidor : ${SERVER_URL}`);
console.log(`  OBS      : ws://${OBS_HOST}:${OBS_PORT}`);
console.log(`  Agent ID : ${AGENT_ID}`);
console.log(`  Polling  : ${POLL_MS}ms`);
console.log('');

(async () => {
    await connectObs();
    console.log('[Agent] Iniciando polling...');
    setTimeout(pollLoop, 500);
})();

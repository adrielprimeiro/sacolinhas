<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Inventário Scanner</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:   #6c63ff;
            --success:   #22c55e;
            --danger:    #ef4444;
            --warning:   #f59e0b;
            --bg:        #0f0f13;
            --surface:   #1a1a24;
            --surface2:  #252533;
            --text:      #f1f1f5;
            --muted:     #8b8ba7;
            --radius:    14px;
        }

        html, body {
            height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            overflow: hidden;
        }

        /* ── SETUP SCREEN ── */
        #setup-screen {
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow-y: auto;
            padding: 24px 20px 40px;
            gap: 20px;
        }

        .setup-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .setup-header a {
            color: var(--muted);
            text-decoration: none;
            font-size: 20px;
            line-height: 1;
        }
        .setup-header h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }
        .setup-header h1 span { color: var(--primary); }

        .card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 20px;
        }
        .card h2 {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--muted);
            margin-bottom: 14px;
        }

        .field { display: flex; flex-direction: column; gap: 6px; }
        .field label { font-size: 14px; font-weight: 500; color: var(--text); }
        .field select, .field input {
            background: var(--surface2);
            border: 1.5px solid transparent;
            border-radius: 10px;
            padding: 12px 14px;
            color: var(--text);
            font-family: inherit;
            font-size: 15px;
            outline: none;
            transition: border-color .2s;
            -webkit-appearance: none;
        }
        .field select:focus, .field input:focus {
            border-color: var(--primary);
        }
        .field select option { background: #1a1a24; }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: none;
            border-radius: var(--radius);
            padding: 16px 20px;
            font-family: inherit;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .15s, transform .1s;
            text-decoration: none;
            width: 100%;
        }
        .btn:active { transform: scale(0.97); opacity: .9; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-surface { background: var(--surface2); color: var(--text); }
        .btn-danger  { background: var(--danger);  color: #fff; }
        .btn-success { background: var(--success); color: #fff; }

        /* ── SCANNER SCREEN ── */
        #scanner-screen {
            display: none;
            position: fixed;
            inset: 0;
            background: #000;
            z-index: 100;
            flex-direction: column;
        }

        #video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Finder overlay */
        .finder-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }

        .finder-box {
            width: min(280px, 72vw);
            height: min(280px, 72vw);
            position: relative;
        }

        .finder-box::before, .finder-box::after,
        .finder-box .corner-tr, .finder-box .corner-bl, .finder-box .corner-br {
            content: '';
            position: absolute;
            width: 28px;
            height: 28px;
            border-color: #fff;
            border-style: solid;
        }
        /* TL */ .finder-box::before  { top:0; left:0;  border-width: 4px 0 0 4px; border-radius: 4px 0 0 0; }
        /* TR */ .finder-box::after   { top:0; right:0; border-width: 4px 4px 0 0; border-radius: 0 4px 0 0; }
        .finder-box .corner-bl        { bottom:0; left:0;  border-width: 0 0 4px 4px; border-radius: 0 0 0 4px; }
        .finder-box .corner-br        { bottom:0; right:0; border-width: 0 4px 4px 0; border-radius: 0 0 4px 0; }

        .scan-line {
            position: absolute;
            left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary) 30%, #a78bfa 70%, transparent);
            animation: scanLine 1.8s ease-in-out infinite;
            border-radius: 2px;
            box-shadow: 0 0 8px var(--primary);
        }
        @keyframes scanLine {
            0%   { top: 0%;   opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }

        .finder-hint {
            margin-top: 24px;
            font-size: 14px;
            color: rgba(255,255,255,.7);
            font-weight: 500;
            text-align: center;
        }

        /* Top bar */
        .scanner-topbar {
            position: absolute;
            top: 0; left: 0; right: 0;
            padding: env(safe-area-inset-top, 16px) 16px 16px;
            padding-top: max(env(safe-area-inset-top), 16px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(to bottom, rgba(0,0,0,.7), transparent);
            z-index: 10;
        }

        .scanner-topbar .close-btn {
            background: rgba(255,255,255,.15);
            border: none;
            color: #fff;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            backdrop-filter: blur(8px);
        }

        .scanner-count {
            background: rgba(255,255,255,.15);
            backdrop-filter: blur(8px);
            border-radius: 20px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
        }

        /* Flash feedback */
        #flash-overlay {
            position: absolute;
            inset: 0;
            background: var(--success);
            opacity: 0;
            pointer-events: none;
            z-index: 50;
            transition: opacity .05s;
        }

        /* Bottom pill */
        .scanner-bottom {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            padding: 16px;
            padding-bottom: max(env(safe-area-inset-bottom), 24px);
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: linear-gradient(to top, rgba(0,0,0,.8) 60%, transparent);
            z-index: 10;
        }

        #last-scan {
            text-align: center;
            font-size: 13px;
            color: rgba(255,255,255,.75);
            font-weight: 500;
            min-height: 20px;
        }
        #last-scan b { color: #fff; }

        .scanner-bottom .btn-danger {
            max-width: 340px;
            margin: 0 auto;
        }

        /* ── REVIEW SCREEN ── */
        #review-screen {
            display: none;
            flex-direction: column;
            height: 100%;
        }

        .review-header {
            padding: 20px 20px 0;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .review-header h2 { font-size: 20px; font-weight: 700; }
        .review-header p  { font-size: 14px; color: var(--muted); }

        .scanned-list {
            flex: 1;
            overflow-y: auto;
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .scanned-item {
            background: var(--surface);
            border-radius: 10px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: fadeIn .2s ease;
        }
        @keyframes fadeIn { from { opacity:0; transform: translateY(4px); } to { opacity:1; transform: translateY(0); } }

        .scanned-item .code { font-size: 15px; font-weight: 600; font-family: monospace; }
        .scanned-item .dup  { font-size: 11px; color: var(--warning); font-weight: 600; margin-top: 2px; }
        .scanned-item .rm-btn {
            background: none; border: none;
            color: var(--muted); font-size: 18px;
            cursor: pointer; padding: 4px 8px;
        }
        .scanned-item .rm-btn:hover { color: var(--danger); }

        .empty-list {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: var(--muted);
        }
        .empty-list i { font-size: 48px; opacity: .3; }

        .review-footer {
            padding: 16px 20px;
            padding-bottom: max(env(safe-area-inset-bottom), 20px);
            display: flex;
            flex-direction: column;
            gap: 10px;
            border-top: 1px solid var(--surface2);
        }

        /* Flash badge (top toast) */
        #toast {
            position: fixed;
            top: 0; left: 50%;
            transform: translateX(-50%) translateY(-120%);
            background: var(--success);
            color: #fff;
            padding: 10px 24px;
            border-radius: 0 0 16px 16px;
            font-size: 14px;
            font-weight: 600;
            z-index: 200;
            transition: transform .25s cubic-bezier(.34,1.56,.64,1);
            white-space: nowrap;
            box-shadow: 0 4px 20px rgba(34,197,94,.4);
        }
        #toast.show { transform: translateX(-50%) translateY(0); }

        /* ── SESSION ALERT ── */
        .session-alert {
            background: var(--success);
            color: #fff;
            border-radius: var(--radius);
            padding: 14px 18px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .session-alert i { flex-shrink: 0; margin-top: 2px; }

        /* Badge counters */
        .chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--surface2);
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 13px;
            font-weight: 600;
        }
        .chip .dot {
            width: 8px; height: 8px;
            border-radius: 50%;
        }
    </style>
</head>
<body>

{{-- ───────────────────────────── SETUP ───────────────────────────── --}}
<div id="setup-screen">
    {{-- Header --}}
    <div class="setup-header">
        <a href="{{ route('inventario') }}" title="Voltar ao Inventário"><i class="fas fa-arrow-left"></i></a>
        <h1>Inventário <span>Scanner</span></h1>
    </div>

    {{-- Session alerts --}}
    @if(session('success'))
        <div class="session-alert">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('warning'))
        <div class="session-alert" style="background: var(--warning); color: #000;">
            <i class="fas fa-exclamation-triangle"></i>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    {{-- Config card --}}
    <div class="card">
        <h2>Configuração da Leitura</h2>
        <div style="display:flex; flex-direction:column; gap:14px;">
            <div class="field">
                <label>Novo Status <span style="color:var(--muted); font-weight:400;">(opcional)</span></label>
                <select id="cfg-status">
                    <option value="">— Apenas registrar código —</option>
                    <option value="indisponivel" {{ ($defaultStatus ?? '') == 'indisponivel' ? 'selected' : '' }}>Indisponível</option>
                    <option value="disponivel" {{ ($defaultStatus ?? '') == 'disponivel' ? 'selected' : '' }}>Disponível</option>
                    <option value="reservado" {{ ($defaultStatus ?? '') == 'reservado' ? 'selected' : '' }}>Reservado</option>
                    <option value="vendido" {{ ($defaultStatus ?? '') == 'vendido' ? 'selected' : '' }}>Vendido</option>
                    <option value="em_sacolinha" {{ ($defaultStatus ?? '') == 'em_sacolinha' ? 'selected' : '' }}>Em Sacolinha</option>
                    <option value="loja" {{ ($defaultStatus ?? '') == 'loja' ? 'selected' : '' }}>Loja</option>
                    <option value="estoque" {{ ($defaultStatus ?? '') == 'estoque' || empty($defaultStatus) && !empty($defaultLocal) ? 'selected' : '' }}>Estoque</option>
                    <option value="live" {{ ($defaultStatus ?? '') == 'live' ? 'selected' : '' }}>Live</option>
                </select>
            </div>
            <div class="field">
                <label>Localização <span style="color:var(--muted); font-weight:400;">(opcional)</span></label>
                <input type="text" id="cfg-local" value="{{ $defaultLocal ?? '' }}" placeholder="Ex: Prateleira A3, Caixa 07…" autocomplete="off">
            </div>
            <div class="field">
                <label>Cor <span style="color:var(--muted); font-weight:400;">(opcional)</span></label>
                @if(!empty($coresDisponiveis) && count($coresDisponiveis) > 0)
                    <select id="cfg-cor">
                        <option value="">— Todas as Cores do Setor —</option>
                        @foreach($coresDisponiveis as $c)
                            <option value="{{ $c }}" {{ ($defaultCor ?? '') == $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="text" id="cfg-cor" value="{{ $defaultCor ?? '' }}" placeholder="Ex: Azul, Verde..." autocomplete="off">
                @endif
            </div>
        </div>
    </div>

    {{-- Start button --}}
    <button class="btn btn-primary" id="btn-open-scanner" style="min-height:64px; font-size:18px;">
        <i class="fas fa-qrcode"></i>
        Abrir Scanner
    </button>

    {{-- Review scanned list --}}
    <div class="card" id="card-review" style="display:none;">
        <h2>Leituras desta sessão</h2>
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 16px;">
            <div id="chips" style="display:flex; gap:8px; flex-wrap: wrap;"></div>
            <button class="btn btn-danger" id="btn-clear-list" style="width:auto; padding: 8px 14px; font-size: 13px;">
                <i class="fas fa-trash"></i> Limpar
            </button>
        </div>
        <div id="list-preview" style="display:flex; flex-direction:column; gap:8px; max-height:220px; overflow-y:auto;"></div>
        <button class="btn btn-success" id="btn-apply" style="margin-top:16px; min-height: 56px; font-size: 16px;">
            <i class="fas fa-check"></i>
            Aplicar alterações (<span id="apply-count">0</span> itens)
        </button>
    </div>

    {{-- Hidden form --}}
    <form id="form-processar" method="POST" action="{{ route('inventario.processar') }}" style="display:none;">
        @csrf
        <input type="hidden" name="status"      id="form-status">
        <input type="hidden" name="localizacao" id="form-local">
        <input type="hidden" name="cor"         id="form-cor">
        <div id="form-codigos"></div>
    </form>
</div>

{{-- ───────────────────────────── SCANNER ───────────────────────────── --}}
<div id="scanner-screen">
    <video id="video" autoplay muted playsinline></video>

    <div id="flash-overlay"></div>

    <!-- Finder -->
    <div class="finder-overlay">
        <div class="finder-box">
            <div class="corner-bl"></div>
            <div class="corner-br"></div>
            <div class="scan-line"></div>
        </div>
        <p class="finder-hint">Aponte a câmera para o QR Code</p>
    </div>

    <!-- Top bar -->
    <div class="scanner-topbar">
        <button class="close-btn" id="btn-close-scanner">
            <i class="fas fa-times"></i>
        </button>
        <div class="scanner-count">
            <i class="fas fa-check" style="color:var(--success);"></i>
            <span id="scan-count">0</span> lidos
        </div>
    </div>

    <!-- Bottom -->
    <div class="scanner-bottom">
        <div id="last-scan">Aguardando leitura…</div>
        <div style="display:flex; gap:8px; width:100%; max-width:340px; margin: 0 auto 10px;">
            <input type="text" id="manual-scan-input" placeholder="Ou digite o código..." style="flex:1; padding:10px 14px; border-radius:10px; border:1px solid rgba(255,255,255,0.3); background:rgba(0,0,0,0.5); color:#fff; font-size:14px; outline:none;" autocomplete="off">
            <button type="button" id="btn-manual-scan-add" class="btn btn-primary" style="width:auto; padding:10px 14px; min-height:42px; font-size:13px;">+ Add</button>
        </div>
        <button class="btn btn-danger" id="btn-done-scanning" style="max-width:340px; margin: 0 auto; min-height:52px;">
            <i class="fas fa-stop-circle"></i>
            Fechar e revisar
        </button>
    </div>
</div>

{{-- Toast --}}
<div id="toast"><i class="fas fa-check-circle"></i> <span id="toast-msg"></span></div>

{{-- ZXing --}}
<script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.18.6/umd/index.min.js"></script>
<script>
// ── Estado Global ──
const state = {
    scanned: [],   // { codigo, at }
    scanning: false,
    reader: null,
    stream: null,
};

// Debounce de código duplicado (ms)
const DEBOUNCE_MS = 2500;
let lastScanned = null;
let lastScannedAt = 0;

// ── DOM ──
const setupScreen   = document.getElementById('setup-screen');
const scannerScreen = document.getElementById('scanner-screen');
const video         = document.getElementById('video');
const flashOverlay  = document.getElementById('flash-overlay');
const scanCount     = document.getElementById('scan-count');
const lastScanEl    = document.getElementById('last-scan');
const cardReview    = document.getElementById('card-review');
const listPreview   = document.getElementById('list-preview');
const applyCountEl  = document.getElementById('apply-count');
const chipsEl       = document.getElementById('chips');

// ── Abrir Scanner ──
document.getElementById('btn-open-scanner').addEventListener('click', openScanner);

async function openScanner() {
    // PASSO 1: Desbloquear áudio DENTRO do gesto do usuário (obrigatório iOS)
    await unlockAudio();

    // PASSO 2: Abrir câmera
    try {
        state.stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: { ideal: 'environment' },
                width:  { ideal: 1920 },
                height: { ideal: 1080 },
                advanced: [{ focusMode: 'continuous' }]
            },
            audio: false
        });

        video.srcObject = state.stream;
        await video.play();

        setupScreen.style.display   = 'none';
        scannerScreen.style.display = 'flex';

        startDecoding();
    } catch (err) {
        alert('❌ Não foi possível acessar a câmera.\n' + err.message);
    }
}

// ── Decodificação contínua (Native BarcodeDetector + Fallback ZXing MultiFormat) ──
function startDecoding() {
    state.scanning = true;

    // 1. Tentar BarcodeDetector Nativo (Aceleração por Hardware / 60 FPS)
    if ('BarcodeDetector' in window) {
        try {
            const detector = new BarcodeDetector({
                formats: ['qr_code', 'code_128', 'code_39', 'code_93', 'ean_13', 'ean_8', 'itf', 'data_matrix']
            });

            const nativeScanLoop = async () => {
                if (!state.scanning) return;
                try {
                    const barcodes = await detector.detect(video);
                    if (barcodes && barcodes.length > 0) {
                        for (const b of barcodes) {
                            if (b.rawValue) handleScan(b.rawValue);
                        }
                    }
                } catch (e) {}
                if (state.scanning) {
                    requestAnimationFrame(nativeScanLoop);
                }
            };
            nativeScanLoop();
            return;
        } catch (e) {
            console.warn('BarcodeDetector nativo falhou, usando fallback ZXing:', e);
        }
    }

    // 2. Fallback ZXing com formatos explícitos de código de barras e QR Code
    if (!window.ZXing) { console.error('ZXing não carregado'); return; }

    try {
        const hints = new Map();
        if (ZXing.DecodeHintType && ZXing.BarcodeFormat) {
            hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [
                ZXing.BarcodeFormat.QR_CODE,
                ZXing.BarcodeFormat.CODE_128,
                ZXing.BarcodeFormat.CODE_39,
                ZXing.BarcodeFormat.EAN_13,
                ZXing.BarcodeFormat.EAN_8,
                ZXing.BarcodeFormat.ITF,
                ZXing.BarcodeFormat.DATA_MATRIX
            ]);
            hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
        }

        state.reader = new ZXing.BrowserMultiFormatReader(hints);
        state.reader.decodeFromStream(state.stream, video, (result, err) => {
            if (!result) return;
            handleScan(result.getText());
        });
    } catch(e) {
        console.error('Erro ao iniciar ZXing:', e);
    }
}

// ── Sistema de Áudio via HTML Audio + WAV gerado em memória ──
let sndOk  = null;   // beep de confirmação
let sndDup = null;   // beep de duplicado (dois pulsos graves)

// Gera um WAV PCM 16-bit mono como Blob URL
function makeBeepUrl(freqs, durations, sr = 22050) {
    // freqs e durations são arrays paralelos de segmentos
    const segments = freqs.map((f, i) => ({ f, d: durations[i] }));
    const totalSamples = segments.reduce((s, seg) => s + Math.floor(sr * seg.d), 0);
    const buf  = new ArrayBuffer(44 + totalSamples * 2);
    const view = new DataView(buf);
    const str  = (off, s) => { for (let i = 0; i < s.length; i++) view.setUint8(off + i, s.charCodeAt(i)); };

    str(0,  'RIFF');  view.setUint32(4,  36 + totalSamples * 2, true);
    str(8,  'WAVE');  str(12, 'fmt ');
    view.setUint32(16, 16, true);
    view.setUint16(20,  1, true);   // PCM
    view.setUint16(22,  1, true);   // mono
    view.setUint32(24, sr, true);   // sample rate
    view.setUint32(28, sr * 2, true); // byte rate
    view.setUint16(32,  2, true);   // block align
    view.setUint16(34, 16, true);   // bits/sample
    str(36, 'data'); view.setUint32(40, totalSamples * 2, true);

    let offset = 44;
    let globalT = 0;
    for (const seg of segments) {
        const n = Math.floor(sr * seg.d);
        for (let i = 0; i < n; i++) {
            const t   = i / sr;
            const env = seg.f > 0 ? (1 - t / seg.d) : 0;   // fade-out linear
            const v   = seg.f > 0
                ? Math.sin(2 * Math.PI * seg.f * (globalT + t)) * env * 0.5
                : 0;
            view.setInt16(offset, Math.round(v * 32767), true);
            offset += 2;
        }
        globalT += seg.d;
    }

    return URL.createObjectURL(new Blob([buf], { type: 'audio/wav' }));
}

async function unlockAudio() {
    try {
        sndOk  = new Audio(makeBeepUrl([880],        [0.12]));
        sndDup = new Audio(makeBeepUrl([380, 0, 300], [0.10, 0.06, 0.12]));

        // Play + pause imediato dentro do gesto — desbloqueia iOS
        for (const snd of [sndOk, sndDup]) {
            snd.volume = 1.0;
            await snd.play();
            snd.pause();
            snd.currentTime = 0;
        }
    } catch(e) { console.warn('Audio unlock:', e); }
}

function playSound(snd) {
    if (!snd) return;
    try { snd.currentTime = 0; snd.play().catch(() => {}); } catch(e) {}
}

// ── Vibração (Android only) ──
function vibrate(ms) {
    try { if (navigator.vibrate) navigator.vibrate(ms); } catch(e) {}
}

// ── Limpeza e Normalização de Código (Extrai código limpo de URLs) ──
function cleanCode(rawCode) {
    if (!rawCode) return '';
    let code = rawCode.trim();

    if (code.startsWith('http://') || code.startsWith('https://')) {
        try {
            const url = new URL(code);
            const param = url.searchParams.get('codigo') || url.searchParams.get('c') || url.searchParams.get('code') || url.searchParams.get('item');
            if (param) return param.trim();

            const segs = url.pathname.split('/').filter(Boolean);
            if (segs.length > 0) {
                return segs[segs.length - 1].trim();
            }
        } catch(e) {}
    }
    return code;
}

// ── Entrada Manual no Scanner ──
const btnManualAdd = document.getElementById('btn-manual-scan-add');
const inputManual  = document.getElementById('manual-scan-input');
if (btnManualAdd && inputManual) {
    btnManualAdd.addEventListener('click', addManualScanCode);
    inputManual.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addManualScanCode();
        }
    });
}

function addManualScanCode() {
    if (!inputManual) return;
    const val = inputManual.value.trim();
    if (val) {
        handleScan(val);
        inputManual.value = '';
        inputManual.focus();
    }
}

// ── Processar código lido ──
function handleScan(rawCode) {
    const code = cleanCode(rawCode);
    if (!code) return;

    const now = Date.now();

    // Debounce: evita re-leitura do mesmo código rápido
    if (code === lastScanned && (now - lastScannedAt) < DEBOUNCE_MS) return;

    lastScanned   = code;
    lastScannedAt = now;

    // Adiciona à lista
    const isDup = state.scanned.some(s => s.codigo === code);
    state.scanned.push({ codigo: code, at: now });

    if (isDup) {
        playSound(sndDup);
        vibrate([80, 60, 120]);
    } else {
        playSound(sndOk);
        vibrate(70);

    }

    // Feedback visual
    flashGreen();
    showToast(code);

    // Atualiza UI do scanner
    scanCount.textContent = state.scanned.length;
    lastScanEl.innerHTML  = isDup
        ? `⚠️ Repetido: <b>${code}</b>`
        : `✅ <b>${code}</b>`;
}


// ── Flash verde ──
function flashGreen() {
    flashOverlay.style.opacity = '0.55';
    setTimeout(() => { flashOverlay.style.opacity = '0'; }, 180);
}

// ── Toast ──
const toast    = document.getElementById('toast');
const toastMsg = document.getElementById('toast-msg');
let toastTimer = null;
function showToast(code) {
    toastMsg.textContent = code;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 1400);
}

// ── Fechar Scanner ──
document.getElementById('btn-close-scanner').addEventListener('click', closeScanner);
document.getElementById('btn-done-scanning').addEventListener('click', closeScanner);

function closeScanner() {
    state.scanning = false;

    if (state.reader) {
        try { state.reader.reset(); } catch(e) {}
        state.reader = null;
    }
    if (state.stream) {
        state.stream.getTracks().forEach(t => t.stop());
        state.stream = null;
    }

    video.srcObject = null;
    scannerScreen.style.display = 'none';
    setupScreen.style.display   = 'flex';

    renderReview();
}

// ── Renderizar lista de revisão ──
function renderReview() {
    if (state.scanned.length === 0) {
        cardReview.style.display = 'none';
        return;
    }

    cardReview.style.display = 'block';

    // Contadores
    const total   = state.scanned.length;
    const uniques = [...new Set(state.scanned.map(s => s.codigo))].length;
    const dups    = total - uniques;

    chipsEl.innerHTML = `
        <div class="chip"><span class="dot" style="background:var(--primary)"></span>${total} leitura(s)</div>
        <div class="chip"><span class="dot" style="background:var(--success)"></span>${uniques} único(s)</div>
        ${dups > 0 ? `<div class="chip"><span class="dot" style="background:var(--warning)"></span>${dups} repetido(s)</div>` : ''}
    `;
    applyCountEl.textContent = uniques;

    // Lista (agrupada por código único)
    const seen = {};
    state.scanned.forEach(s => {
        seen[s.codigo] = (seen[s.codigo] || 0) + 1;
    });

    listPreview.innerHTML = Object.entries(seen).map(([code, count]) => `
        <div class="scanned-item">
            <div>
                <div class="code">${escHtml(code)}</div>
                ${count > 1 ? `<div class="dup">Lido ${count}x — será enviado 1x</div>` : ''}
            </div>
            <button class="rm-btn" onclick="removeCode('${escHtml(code)}')" title="Remover">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');
}

function removeCode(code) {
    state.scanned = state.scanned.filter(s => s.codigo !== code);
    renderReview();
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Limpar lista ──
document.getElementById('btn-clear-list').addEventListener('click', () => {
    if (!confirm('Deseja limpar todas as leituras desta sessão?')) return;
    state.scanned = [];
    renderReview();
});

// ── Aplicar ──
document.getElementById('btn-apply').addEventListener('click', () => {
    if (state.scanned.length === 0) return;

    const uniques = [...new Set(state.scanned.map(s => s.codigo))];

    // Preencher form
    document.getElementById('form-status').value = document.getElementById('cfg-status').value;
    document.getElementById('form-local').value  = document.getElementById('cfg-local').value;
    document.getElementById('form-cor').value    = document.getElementById('cfg-cor').value;

    const container = document.getElementById('form-codigos');
    container.innerHTML = '';
    uniques.forEach(code => {
        const inp = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = 'codigos[]';
        inp.value = code;
        container.appendChild(inp);
    });

    document.getElementById('form-processar').submit();
});

// Inicializa review se houver itens (ex: volta após erro)
renderReview();
</script>
</body>
</html>
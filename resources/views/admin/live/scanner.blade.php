<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scanner - Movimentação Live</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg: #f8fafc;
            --surface: #ffffff;
            --surface2: #f1f5f9;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --radius: 16px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg); color: var(--text); -webkit-tap-highlight-color: transparent; }

        #setup-screen { padding: 16px; max-width: 600px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px; min-height: 100vh; }
        
        .setup-header { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
        .setup-header a { color: var(--text); text-decoration: none; font-size: 20px; background: var(--surface2); width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
        .setup-header h1 { font-size: 22px; font-weight: 800; letter-spacing: -0.5px; }
        .setup-header h1 span { color: var(--primary); }

        .card { background: var(--surface); border-radius: var(--radius); padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05); border: 1px solid var(--border); }
        .card h2 { font-size: 16px; font-weight: 700; margin-bottom: 16px; color: var(--text); display: flex; align-items: center; gap: 8px; }

        .tabs { display: flex; background: var(--surface2); border-radius: 12px; padding: 4px; margin-bottom: 20px; }
        .tab { flex: 1; text-align: center; padding: 10px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; color: var(--muted); transition: 0.2s; }
        .tab.active { background: var(--surface); color: var(--primary); box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

        .field { display: flex; flex-direction: column; gap: 6px; }
        .field label { font-size: 13px; font-weight: 600; color: var(--text); }
        .field input, .field select { width: 100%; padding: 12px 14px; border: 2px solid var(--surface2); border-radius: 10px; background: var(--surface2); font-size: 15px; color: var(--text); font-weight: 500; transition: border-color 0.2s; outline: none; }
        .field input:focus, .field select:focus { border-color: var(--primary); background: var(--surface); }

        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 20px; border-radius: 12px; font-weight: 700; border: none; cursor: pointer; transition: 0.2s; width: 100%; text-decoration: none; }
        .btn-primary { background: var(--primary); color: #fff; box-shadow: 0 4px 12px rgba(79,70,229,0.2); }
        .btn-success { background: var(--success); color: #fff; box-shadow: 0 4px 12px rgba(16,185,129,0.2); }
        .btn-danger { background: var(--danger); color: #fff; box-shadow: 0 4px 12px rgba(239,68,68,0.2); }
        .btn-outline { background: transparent; color: var(--text); border: 2px solid var(--border); }
        
        .session-alert { background: var(--success); color: #fff; border-radius: var(--radius); padding: 14px 18px; font-size: 14px; font-weight: 500; display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px; }

        .item-list { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; max-height: 250px; overflow-y: auto; }
        .item-row { display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--surface2); border-radius: 8px; font-size: 13px; }
        .item-row strong { color: var(--text); }
        .item-row span { color: var(--muted); }

        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
        .stat-box { background: var(--surface2); border-radius: 12px; padding: 12px; text-align: center; }
        .stat-box .num { font-size: 24px; font-weight: 800; color: var(--primary); }
        .stat-box .label { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; margin-top: 4px; }

        /* Scanner Screen */
        #scanner-screen { position: fixed; inset: 0; background: #000; z-index: 1000; display: none; }
        #video { width: 100%; height: 100%; object-fit: cover; }
        .finder-overlay { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; background: rgba(0,0,0,0.4); pointer-events: none; }
        .finder-box { width: 220px; height: 220px; border: 2px solid rgba(255,255,255,0.3); border-radius: 20px; position: relative; box-shadow: 0 0 0 4000px rgba(0,0,0,0.5); }
        .scan-line { position: absolute; left: 0; right: 0; height: 2px; background: var(--primary); animation: scanLine 1.8s ease-in-out infinite; box-shadow: 0 0 8px var(--primary); }
        @keyframes scanLine { 0% { top: 0%; opacity: 0; } 10% { opacity: 1; } 90% { opacity: 1; } 100% { top: 100%; opacity: 0; } }
        
        .scanner-topbar { position: absolute; top: 0; left: 0; right: 0; padding: max(env(safe-area-inset-top), 16px) 16px 16px; display: flex; justify-content: space-between; z-index: 10; }
        .close-btn { width: 42px; height: 42px; background: rgba(255,255,255,0.2); border: none; border-radius: 50%; color: #fff; font-size: 18px; backdrop-filter: blur(8px); cursor: pointer; }
        .scanner-count { background: rgba(255,255,255,0.2); backdrop-filter: blur(8px); border-radius: 20px; padding: 8px 16px; color: #fff; font-weight: 600; display: flex; align-items: center; gap: 8px; }

        .scanner-bottom { position: absolute; bottom: 0; left: 0; right: 0; padding: 16px; padding-bottom: max(env(safe-area-inset-bottom), 24px); background: linear-gradient(transparent, rgba(0,0,0,0.8)); display: flex; flex-direction: column; gap: 10px; z-index: 10; }
        #last-scan { color: #fff; text-align: center; font-size: 14px; font-weight: 500; margin-bottom: 10px; min-height: 20px; }

        #toast { position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); background: var(--text); color: #fff; padding: 12px 20px; border-radius: 30px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); z-index: 2000; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        #toast.show { bottom: max(env(safe-area-inset-bottom), 24px); }
    </style>
</head>
<body>

<div id="setup-screen">
    <div class="setup-header">
        <a href="{{ route('dashboard') }}"><i class="fas fa-arrow-left"></i></a>
        <h1>Movimentação <span>Live</span></h1>
    </div>

    @if(session('success'))
        <div class="session-alert">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="card">
        <form id="live-select-form" method="GET" action="{{ route('live.scanner') }}">
            <div class="field">
                <label>Selecione a Live Atual</label>
                <select name="live_id" id="cfg-live-id" onchange="document.getElementById('live-select-form').submit()">
                    <option value="">-- Escolha uma Live --</option>
                    @foreach($lives as $live)
                        <option value="{{ $live->id }}" {{ ($liveAtual && $liveAtual->id == $live->id) ? 'selected' : '' }}>
                            {{ $live->data->format('d/m/Y') }} - {{ $live->tipo_live_formatado }} (ID: {{ $live->id }})
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if($liveAtual)
    <div class="tabs">
        <div class="tab active" onclick="setMode('ida')" id="tab-ida">Ida para Live</div>
        <div class="tab" onclick="setMode('volta')" id="tab-volta">Volta ao Estoque</div>
    </div>

    <div class="card" id="card-ida">
        <h2><i class="fas fa-sign-out-alt text-primary"></i> Enviar itens para a Live</h2>
        <p style="font-size: 13px; color: var(--muted); margin-bottom: 16px;">
            Ao bipar, os itens entrarão no dossiê desta Live.
        </p>
        <button class="btn btn-primary" onclick="openScanner('ida')" style="font-size:16px; padding: 16px;">
            <i class="fas fa-qrcode"></i> Abrir Scanner (Ida)
        </button>
    </div>

    <div class="card" id="card-volta" style="display:none;">
        <h2><i class="fas fa-sign-in-alt text-success"></i> Retornar itens ao Estoque</h2>
        <p style="font-size: 13px; color: var(--muted); margin-bottom: 16px;">
            O sistema devolverá a peça para sua <strong>localização anterior</strong>. Ou você pode definir um destino fixo abaixo:
        </p>
        <div class="field" style="margin-bottom: 16px;">
            <label>Destino Manual <span style="color:var(--muted); font-weight:400;">(opcional)</span></label>
            <input type="text" id="cfg-local-volta" placeholder="Ex: Deixar em branco para usar a origem original" autocomplete="off">
        </div>
        <button class="btn btn-success" onclick="openScanner('volta')" style="font-size:16px; padding: 16px;">
            <i class="fas fa-qrcode"></i> Abrir Scanner (Volta)
        </button>
    </div>

    {{-- Review scanned list --}}
    <div class="card" id="card-review" style="display:none;">
        <h2>Leituras desta sessão (<span id="mode-label"></span>)</h2>
        <div id="list-preview" style="display:flex; flex-direction:column; gap:8px; max-height:220px; overflow-y:auto; margin-bottom: 16px;"></div>
        
        <div style="display:flex; gap: 10px;">
            <button class="btn btn-danger" onclick="clearList()" style="flex:1;">Limpar</button>
            <button class="btn btn-success" onclick="submitList()" style="flex:2;">Confirmar (<span id="apply-count">0</span>)</button>
        </div>
    </div>

    <div class="card" id="card-relatorio">
        <h2><i class="fas fa-chart-pie"></i> Dossiê da Live</h2>
        
        <div class="stats-grid">
            <div class="stat-box">
                <div class="num">{{ $itensEnviados->count() }}</div>
                <div class="label">Total Oferecido</div>
            </div>
            <div class="stat-box">
                <div class="num" style="color:var(--success);">{{ $itensVendidos->count() }}</div>
                <div class="label">Total Vendido</div>
            </div>
            <div class="stat-box">
                <div class="num" style="color:var(--muted);">{{ $itensRetornados->count() }}</div>
                <div class="label">Devolvido ao Estoque</div>
            </div>
            <div class="stat-box">
                <div class="num" style="color:var(--danger);">{{ $itensPerdidos->count() }}</div>
                <div class="label">Pendente (No Cenário)</div>
            </div>
        </div>
        
        @if($itensPerdidos->count() > 0)
        <h3 style="font-size: 14px; margin-top: 20px; margin-bottom: 10px; color: var(--danger);">Pendentes (Ainda não voltaram)</h3>
        <div class="item-list">
            @foreach($itensPerdidos as $item)
            <div class="item-row" style="border-left: 3px solid var(--danger);">
                <div>
                    <strong>{{ $item->codigo }}</strong><br>
                    <span style="font-size:11px;">{{ Str::limit($item->nome_do_produto, 25) }}</span>
                </div>
                <div style="text-align:right;">
                    <span style="font-size:11px; display:block;">Origem: {{ $item->localizacao_origem ?? '?' }}</span>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        @if($itensVendidos->count() > 0)
        <h3 style="font-size: 14px; margin-top: 20px; margin-bottom: 10px; color: var(--success);">Vendidos</h3>
        <div class="item-list">
            @foreach($itensVendidos as $item)
            <div class="item-row" style="border-left: 3px solid var(--success);">
                <div>
                    <strong>{{ $item->codigo }}</strong><br>
                    <span style="font-size:11px;">{{ Str::limit($item->nome_do_produto, 25) }}</span>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <form id="form-ida" method="POST" action="{{ route('live.scanner.ida') }}" style="display:none;">
        @csrf
        <input type="hidden" name="live_id" value="{{ $liveAtual->id }}">
        <div id="form-ida-codigos"></div>
    </form>
    
    <form id="form-volta" method="POST" action="{{ route('live.scanner.volta') }}" style="display:none;">
        @csrf
        <input type="hidden" name="live_id" value="{{ $liveAtual->id }}">
        <input type="hidden" name="local_destino" id="form-local-destino">
        <div id="form-volta-codigos"></div>
    </form>
    @else
    <div class="card" style="text-align:center; padding: 40px 20px;">
        <i class="fas fa-video" style="font-size: 40px; color: var(--muted); margin-bottom: 16px;"></i>
        <h3 style="color: var(--muted);">Selecione uma Live acima para começar a movimentação e gerar o dossiê.</h3>
    </div>
    @endif
</div>

<div id="scanner-screen">
    <video id="video" autoplay muted playsinline></video>
    <div class="finder-overlay">
        <div class="finder-box">
            <div class="scan-line"></div>
        </div>
    </div>
    <div class="scanner-topbar">
        <button class="close-btn" onclick="closeScanner()"><i class="fas fa-times"></i></button>
        <div class="scanner-count"><i class="fas fa-check"></i> <span id="scan-count">0</span></div>
    </div>
    <div class="scanner-bottom">
        <div id="last-scan">Aguardando leitura…</div>
        <button class="btn btn-primary" onclick="closeScanner()" style="min-height:52px;"><i class="fas fa-stop-circle"></i> Fechar e revisar</button>
    </div>
</div>

<div id="toast"><i class="fas fa-info-circle"></i> <span id="toast-msg"></span></div>

<script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.18.6/umd/index.min.js"></script>
<script>
let currentMode = 'ida'; // ida | volta
let scannedCodes = [];
let scanning = false;
let reader = null;
let debounceTimer = 0;

function setMode(mode) {
    currentMode = mode;
    document.getElementById('tab-ida').classList.remove('active');
    document.getElementById('tab-volta').classList.remove('active');
    document.getElementById('tab-' + mode).classList.add('active');
    
    document.getElementById('card-ida').style.display = mode === 'ida' ? 'block' : 'none';
    document.getElementById('card-volta').style.display = mode === 'volta' ? 'block' : 'none';
    
    clearList();
}

function cleanCode(rawCode) {
    if (!rawCode) return '';
    let code = rawCode.trim();
    if (code.startsWith('http://') || code.startsWith('https://')) {
        try {
            const url = new URL(code);
            const param = url.searchParams.get('codigo') || url.searchParams.get('c') || url.searchParams.get('code') || url.searchParams.get('item');
            if (param) return param.trim();
            const segs = url.pathname.split('/').filter(Boolean);
            if (segs.length > 0) return segs[segs.length - 1].trim();
        } catch(e) {}
    }
    return code;
}

let isScanningLive = false;

function openScanner(mode) {
    currentMode = mode;
    scannedCodes = [];
    updatePreview();
    isScanningLive = true;
    
    document.getElementById('setup-screen').style.display = 'none';
    document.getElementById('scanner-screen').style.display = 'block';
    
    const onCodeFound = (rawText) => {
        const code = cleanCode(rawText);
        if (!code) return;
        const now = Date.now();
        if (now - debounceTimer > 2000 || !scannedCodes.includes(code)) {
            debounceTimer = now;
            if (!scannedCodes.includes(code)) {
                scannedCodes.push(code);
                document.getElementById('scan-count').innerText = scannedCodes.length;
                document.getElementById('last-scan').innerText = "✅ Lido: " + code;
                showToast("Lido: " + code);
                if (navigator.vibrate) navigator.vibrate(50);
            }
        }
    };

    // 1. Tentar BarcodeDetector Nativo (Hardware 60 FPS)
    if ('BarcodeDetector' in window) {
        try {
            const videoEl = document.getElementById('video');
            const detector = new BarcodeDetector({
                formats: ['qr_code', 'code_128', 'code_39', 'code_93', 'ean_13', 'ean_8', 'itf', 'data_matrix']
            });
            const nativeLoop = async () => {
                if (!isScanningLive) return;
                try {
                    const barcodes = await detector.detect(videoEl);
                    if (barcodes && barcodes.length > 0) {
                        for (const b of barcodes) {
                            if (b.rawValue) onCodeFound(b.rawValue);
                        }
                    }
                } catch(e) {}
                if (isScanningLive) requestAnimationFrame(nativeLoop);
            };
            navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: "environment" }, width: { ideal: 1920 }, height: { ideal: 1080 } }
            }).then(stream => {
                videoEl.srcObject = stream;
                videoEl.play();
                nativeLoop();
            }).catch(e => {
                console.warn('Câmera nativa erro:', e);
            });
            return;
        } catch (e) {}
    }

    // 2. Fallback ZXing
    if (!reader && window.ZXing) {
        const hints = new Map();
        if (ZXing.DecodeHintType && ZXing.BarcodeFormat) {
            hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [
                ZXing.BarcodeFormat.QR_CODE, ZXing.BarcodeFormat.CODE_128,
                ZXing.BarcodeFormat.CODE_39, ZXing.BarcodeFormat.EAN_13,
                ZXing.BarcodeFormat.EAN_8, ZXing.BarcodeFormat.ITF
            ]);
            hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
        }
        reader = new ZXing.BrowserMultiFormatReader(hints);
    }
    
    if (reader) {
        reader.decodeFromConstraints({ video: { facingMode: { ideal: "environment" } } }, 'video', (result, err) => {
            if (result) onCodeFound(result.getText());
        });
    }
}

function closeScanner() {
    isScanningLive = false;
    if (reader) reader.reset();
    document.getElementById('scanner-screen').style.display = 'none';
    document.getElementById('setup-screen').style.display = 'flex';
    
    updatePreview();
}

function updatePreview() {
    const list = document.getElementById('list-preview');
    list.innerHTML = '';
    
    if(scannedCodes.length > 0) {
        document.getElementById('card-review').style.display = 'block';
        document.getElementById('mode-label').innerText = currentMode === 'ida' ? 'Ida para Live' : 'Volta para Estoque';
        document.getElementById('apply-count').innerText = scannedCodes.length;
        
        scannedCodes.forEach(code => {
            const div = document.createElement('div');
            div.className = 'item-row';
            div.innerHTML = `<strong>${code}</strong> <span>Pendente</span>`;
            list.appendChild(div);
        });
    } else {
        document.getElementById('card-review').style.display = 'none';
    }
}

function clearList() {
    scannedCodes = [];
    updatePreview();
}

function submitList() {
    if(scannedCodes.length === 0) return;
    
    const formId = currentMode === 'ida' ? 'form-ida' : 'form-volta';
    const containerId = currentMode === 'ida' ? 'form-ida-codigos' : 'form-volta-codigos';
    
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    
    scannedCodes.forEach(code => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'codigos[]';
        input.value = code;
        container.appendChild(input);
    });
    
    if(currentMode === 'volta') {
        document.getElementById('form-local-destino').value = document.getElementById('cfg-local-volta').value;
    }
    
    document.getElementById(formId).submit();
}

function showToast(msg) {
    const toast = document.getElementById('toast');
    document.getElementById('toast-msg').innerText = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2000);
}
</script>
</body>
</html>
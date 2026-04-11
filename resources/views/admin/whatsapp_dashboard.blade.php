<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Live WhatsApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { background:#f4f7f6; }
        .card { border:none; border-radius:12px; }
        .table-container { background:#fff; border-radius:12px; padding:16px; box-shadow:0 2px 10px rgba(0,0,0,.05); }
        .badge-status { font-size: .85rem; cursor: help; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
        .msg-preview { font-size: 0.85rem; color: #666; }
        .msg-status { font-size: 0.75rem; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Dashboard - Live #<span class="mono" id="liveIdLabel"></span></h4>
            <small class="text-muted">Foco: 1ª mensagem (revisar/confirmar)</small>
        </div>
        <button class="btn btn-outline-primary btn-sm" onclick="loadStats()" id="refreshBtn">
            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
            Atualizar
        </button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card p-3">
                <small class="text-muted fw-bold">TOTAL SACOLINHAS</small>
                <div class="fs-3 fw-bold" id="c1">0</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 bg-success text-white">
                <small class="opacity-75 fw-bold">1ª MSG ENTREGUES</small>
                <div class="fs-3 fw-bold" id="c2">0</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 bg-primary text-white">
                <small class="opacity-75 fw-bold">1ª MSG RESPONDIDA</small>
                <div class="fs-3 fw-bold" id="c3">0</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 bg-danger text-white">
                <small class="opacity-75 fw-bold">FALHAS (1ª MSG)</small>
                <div class="fs-3 fw-bold" id="c4">0</div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Status por cliente (Msg1, Msg2, Msg3)</h6>
            <small class="text-muted">Msg1: template | Msg2: pedido com anexo | Msg3: primeira inbound ≠ "Revisar e Confirmar"</small>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Cliente</th>
                        <th>WhatsApp</th>
                        <th>Msg 1 (Template)</th>
                        <th>Msg 2 (Pedido)</th>
                        <th>Msg 3 (Fora do Padrão)</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="tbody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
const LIVE_ID = {{ (int)($liveId ?? 0) }};
let autoRefreshInterval = null;

function badgeForStatus(st) {
    st = (st || '').toLowerCase();
    const tooltips = {
        'read': 'Cliente visualizou a mensagem',
        'delivered': 'Mensagem entregue no dispositivo',
        'sent': 'Mensagem enviada pelo Twilio',
        'queued': 'Mensagem na fila de envio',
        'failed': 'Falha no envio',
        'undelivered': 'Não foi possível entregar',
        'received': 'Mensagem recebida (inbound)'
    };
    
    const tooltip = tooltips[st] || 'Status desconhecido';
    const badgeClass = st === 'read' ? 'bg-primary' :
                      st === 'delivered' ? 'bg-success' :
                      st === 'sent' || st === 'queued' ? 'bg-secondary' :
                      st === 'failed' || st === 'undelivered' ? 'bg-danger' :
                      st === 'received' ? 'bg-info' : 'bg-light text-dark';
    
    const label = st === 'read' ? 'Lida' :
                  st === 'delivered' ? 'Entregue' :
                  st === 'sent' ? 'Enviada' :
                  st === 'queued' ? 'Enviando' :
                  st === 'failed' ? 'Falhou' :
                  st === 'undelivered' ? 'Não entregue' :
                  st === 'received' ? 'Recebida' : st || '-';
    
    return `<span class="badge ${badgeClass} badge-status" title="${tooltip}">${label}</span>`;
}

async function loadStats() {
    if (!LIVE_ID) {
        alert('Abra com ?live_id=123');
        return;
    }

    const btn = document.getElementById('refreshBtn');
    const spinner = btn.querySelector('.spinner-border');
    
    spinner.classList.remove('d-none');
    btn.disabled = true;

    try {
        document.getElementById('liveIdLabel').textContent = LIVE_ID;

        const res = await fetch(`/admin/whatsapp-dashboard/api/stats?live_id=${LIVE_ID}`);
        const data = await res.json();

        if (!res.ok) {
            alert(data?.error || 'Erro ao carregar stats');
            return;
        }

        document.getElementById('c1').textContent = data.cards.total_sacolinhas ?? 0;
        document.getElementById('c2').textContent = data.cards.primeira_entregues ?? 0;
        document.getElementById('c3').textContent = data.cards.primeira_respondidas ?? 0;
        document.getElementById('c4').textContent = data.cards.primeira_falhas ?? 0;

        const tbody = document.getElementById('tbody');
        tbody.innerHTML = '';

        (data.table || []).forEach(r => {
            const msg1Html = r.msg1_preview ? `
                <div class="msg-preview">${escapeHtml(r.msg1_preview)}${r.msg1_preview.length >= 30 ? '...' : ''}</div>
                <div class="msg-status">${badgeForStatus(r.msg1_status)}</div>
                <small class="text-muted">${formatDateTime(r.msg1_at)}</small>
            ` : '-';

            const msg2Html = r.msg2_preview.startsWith('ERRO:') ? 
                `<div class="text-danger fw-bold">${escapeHtml(r.msg2_preview)}</div>` : 
                (r.msg2_preview ? `
                    <div class="msg-preview">${escapeHtml(r.msg2_preview)}${r.msg2_preview.length >= 30 ? '...' : ''}</div>
                    <div class="msg-status">${badgeForStatus(r.msg2_status)}</div>
                    <small class="text-muted">${formatDateTime(r.msg2_at)}</small>
                ` : '-');

            const msg3Html = r.msg3_preview ? `
                <div class="msg-preview">${escapeHtml(r.msg3_preview)}${r.msg3_preview.length >= 30 ? '...' : ''}</div>
                <small class="text-muted">${formatDateTime(r.msg3_at)}</small>
            ` : '-';

            const isConflictRow = (r.user_id === null) && (String(r.user_name || '').startsWith('CONFLITO #'));
            const conflictId = isConflictRow ? parseInt(String(r.user_name).replace('CONFLITO #',''), 10) : null;

            const actionHtml = isConflictRow
                ? `<button class="btn btn-sm btn-warning" onclick="sendMsg2FromConflict(${conflictId})">Enviar Msg2</button>`
                : '-';

            tbody.innerHTML += `
                <tr>
                    <td>${escapeHtml(r.user_name || ('#' + r.user_id))}</td>
                    <td class="mono">${escapeHtml(r.user_whatsapp || '')}</td>
                    <td>${msg1Html}</td>
                    <td>${msg2Html}</td>
                    <td>${msg3Html}</td>
                    <td>${actionHtml}</td>
                </tr>
            `;
        });
    } finally {
        spinner.classList.add('d-none');
        btn.disabled = false;
    }
}

async function sendMsg2FromConflict(conflictId) {
    if (!conflictId) return;

    try {
        const res = await fetch(`/admin/whatsapp-dashboard/api/conflicts/${conflictId}/send-msg2`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ live_id: LIVE_ID })
        });

        const data = await res.json().catch(() => null);

        if (!res.ok || data?.success === false) {
            alert(data?.error || `Falha ao enviar Msg2 (HTTP ${res.status})`);
            return;
        }

        alert('Msg2 enviada com sucesso.');
        loadStats();
    } catch (e) {
        alert('Erro de rede ao enviar Msg2.');
        console.error(e);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

function formatDateTime(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function startAutoRefresh() {
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
    autoRefreshInterval = setInterval(loadStats, 30000); // 30 segundos
}

document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    startAutoRefresh();
});
</script>
</body>
</html>
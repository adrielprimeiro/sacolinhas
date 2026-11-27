@extends('layouts.app')

@section('title', 'Gerenciar Sacolinhas - Da Live')
@section('brand_route', 'admin.sacolinhas.index')
@section('brand_icon', 'fas fa-broadcast-tower')

@section('content')
@php
    $isMatriz = empty(auth()->user()->brecho_id) || (int) auth()->user()->brecho_id === 1;
@endphp
<div class="space-y-6">
    {{-- Cabeçalho da Página --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex justify-between items-center flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-black text-gray-800 flex items-center gap-2">
                <i class="fas fa-shopping-bag text-blue-600"></i> Gerenciar Sacolinhas - Da Live
            </h1>
            <p class="text-sm text-gray-500 mt-1">Visualize e altere o status de itens comprados por live e envie notificações para o Portal do Cliente.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="carregarTodasAsLives()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold hover:bg-gray-50 transition flex items-center gap-1">
                <i class="fas fa-sync-alt"></i> Recarregar
            </button>
            <button onclick="mostrarTodasAsLives()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition flex items-center gap-1">
                <i class="fas fa-list"></i> Ver Todas (<span id="total-lives-count">0</span>)
            </button>
        </div>
    </div>

    {{-- Layout em Duas Colunas --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- Coluna Esquerda: Listagem de Lives --}}
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fas fa-broadcast-tower text-gray-400"></i> Últimas Lives
                </h3>

                {{-- Filtro de Lives --}}
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Filtrar Lives</label>
                    <input type="text" id="search-live" oninput="filtrarLives()" placeholder="Tipo de live, plataforma, ID..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                {{-- Tabela de Lives --}}
                <div class="overflow-x-auto border border-gray-200 rounded-lg max-h-[400px] overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-250">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tipo</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Plataformas</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="lives-table-body" class="bg-white divide-y divide-gray-150">
                            <tr>
                                <td colspan="5" class="text-center py-6 text-gray-500 text-sm">
                                    <i class="fas fa-spinner fa-spin mr-1"></i> Carregando lives...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Coluna Direita: Sacolas da Live Selecionada --}}
        <div class="lg:col-span-7 space-y-6">
            @if($isMatriz)
            {{-- Painel de Controle / Teste do Severino no WhatsApp (Evolution API) --}}
            <div class="bg-indigo-50 rounded-xl shadow-sm border border-indigo-200 p-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold shadow-sm">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-indigo-950 uppercase tracking-wide flex items-center gap-1.5">
                            Fluxo WhatsApp Severino (Chip Atual / Evolution)
                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-200 text-amber-900 font-extrabold">Twilio Mantida em Paralelo</span>
                        </h4>
                        <p class="text-[11px] text-indigo-800">2 balões na 1ª msg da live, PDF + fechamento cadenciado (5–55s) e ganchos pós-fechamento.</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-1.5 bg-white px-2.5 py-1.5 rounded-lg border border-indigo-200">
                        <span class="text-[10px] font-bold text-gray-600 uppercase">Tel Teste:</span>
                        <input type="text" id="severino-test-phone" value="5548984136870" 
                               onchange="salvarConfigSeverino()"
                               class="w-28 text-xs font-extrabold text-gray-900 bg-transparent focus:outline-none" 
                               title="Número que receberá as mensagens de teste do Severino">
                    </div>
                    <select id="severino-live-mode" onchange="salvarConfigSeverino()" 
                            class="text-xs font-bold text-gray-900 bg-white border border-indigo-300 rounded-lg px-2.5 py-1.5 focus:outline-none">
                        <option value="test">🧪 Clique na Live: Enviar p/ Tel Teste</option>
                        <option value="live">🟢 Clique na Live: Enviar p/ Cliente Real</option>
                        <option value="off">⚪ Clique na Live: Desativado</option>
                    </select>
                    <a href="/admin/chat-evolution" target="_blank" 
                       class="bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold px-3 py-1.5 rounded-lg text-xs flex items-center gap-1.5 shadow-sm transition">
                        <i class="fas fa-comments"></i> Supervisionar Chat
                    </a>
                </div>
            </div>

            {{-- Painel da Fila Cadenciada de Fechamento da Live (5s–55s + PDF + Anti-Ban) --}}
            <div id="closing-queue-panel" class="hidden bg-white rounded-xl shadow-sm p-4 space-y-3" style="border: 2px solid #86efac;">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h4 class="text-xs font-black text-gray-900 uppercase tracking-wide flex items-center gap-2">
                            <i class="fas fa-paper-plane" style="color:#16a34a;"></i>
                            Fechamento Cadenciado da Live (PDF)
                            <span id="cq-status-badge" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold" style="background-color:#f3f4f6; color:#1f2937;">Pronto</span>
                        </h4>
                        <p class="text-[11px] text-gray-600 mt-0.5">
                            Envia com intervalo humano aleatório de <strong>5s a 55s</strong>, priorizando conversas quentes.
                        </p>
                        <label class="inline-flex items-center gap-2 mt-2 px-2.5 py-1 rounded-lg cursor-pointer select-none" style="background-color:#eff6ff; border:1px solid #bfdbfe; color:#1e3a8a;">
                            <input type="checkbox" id="cq-pdf-only" checked class="rounded" style="accent-color:#1d4ed8;">
                            <span class="text-[11px] font-extrabold">Apenas PDF ("Fechamento da Live #XXX", sem texto/sequência do Severino)</span>
                        </label>
                    </div>
                    <div class="flex flex-wrap items-center gap-2" id="cq-controls">
                        <select id="cq-test-limit" class="text-xs font-bold text-gray-900 bg-gray-50 border border-gray-300 rounded-lg px-2 py-1.5" title="Limite de clientes quando em Modo Teste">
                            <option value="3">Teste: 3 clientes</option>
                            <option value="5">Teste: 5 clientes</option>
                            <option value="">Todas as clientes</option>
                        </select>
                        <button type="button" id="btn-cq-start" onclick="iniciarFilaFechamento()"
                                class="bg-green-600 hover:bg-green-700 text-white font-extrabold px-3.5 py-2 rounded-lg text-xs flex items-center gap-1.5 shadow-sm transition cursor-pointer"
                                style="background-color:#16a34a; color:#ffffff; border:1px solid #15803d;">
                            <i class="fas fa-rocket"></i> Iniciar Fechamento Cadenciado
                        </button>
                        <button type="button" id="btn-cq-pause" onclick="pausarFilaFechamento()"
                                class="hidden font-extrabold px-3 py-2 rounded-lg text-xs flex items-center gap-1 shadow-sm transition cursor-pointer"
                                style="background-color:#f59e0b; color:#111827; border:1px solid #d97706;">
                            <i class="fas fa-pause"></i> Pausar
                        </button>
                        <button type="button" id="btn-cq-resume" onclick="retomarFilaFechamento()"
                                class="hidden font-extrabold px-3 py-2 rounded-lg text-xs flex items-center gap-1 shadow-sm transition cursor-pointer"
                                style="background-color:#16a34a; color:#ffffff; border:1px solid #15803d;">
                            <i class="fas fa-play"></i> Retomar
                        </button>
                        <button type="button" id="btn-cq-cancel" onclick="cancelarFilaFechamento()"
                                class="hidden bg-red-600 hover:bg-red-700 text-white font-extrabold px-3 py-2 rounded-lg text-xs flex items-center gap-1 shadow-sm transition cursor-pointer"
                                style="background-color:#dc2626; color:#ffffff; border:1px solid #b91c1c;">
                            <i class="fas fa-stop"></i> Cancelar
                        </button>
                    </div>
                </div>

                {{-- Progresso ao vivo --}}
                <div id="cq-progress-wrap" class="hidden space-y-2 pt-2 border-t border-gray-100">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div class="flex flex-wrap items-center gap-2 font-bold">
                            <span class="px-2 py-0.5 rounded" style="background-color:#dcfce7; color:#14532d;">Enviados: <span id="cq-sent">0</span>/<span id="cq-total">0</span></span>
                            <span class="px-2 py-0.5 rounded" style="background-color:#dbeafe; color:#1e3a8a;">PDFs: <span id="cq-pdfs">0</span></span>
                            <span class="px-2 py-0.5 rounded" style="background-color:#f3f4f6; color:#1f2937;">Na Fila: <span id="cq-pending">0</span></span>
                            <span class="px-2 py-0.5 rounded" style="background-color:#fee2e2; color:#991b1b;">Falhas: <span id="cq-failed">0</span></span>
                        </div>
                        <div id="cq-next-info" class="text-[11px] font-bold text-indigo-800"></div>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div id="cq-progress-bar" class="h-2.5 transition-all duration-500" style="width: 0%; background-color:#16a34a;"></div>
                    </div>
                    <details class="text-xs">
                        <summary class="cursor-pointer font-bold text-gray-700 hover:text-indigo-700 select-none">Ver fila de clientes e ordem de prioridade</summary>
                        <div id="cq-items-list" class="mt-2 max-h-44 overflow-y-auto divide-y divide-gray-100 border border-gray-200 rounded-lg bg-gray-50"></div>
                    </details>
                </div>
            </div>
            @endif

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                <div class="flex justify-between items-center border-b border-gray-100 pb-3 flex-wrap gap-2">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                        <i class="fas fa-shopping-bag text-gray-400"></i> Sacolas da Live Selecionada <span id="selected-live-info" class="text-blue-600 font-extrabold ml-1"></span>
                    </h3>
                    {{-- Filtro de Clientes --}}
                    <div class="w-full sm:w-64">
                        <input type="text" id="search-client" oninput="filtrarSacolas()" placeholder="Filtrar por nome do cliente..." 
                               class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                {{-- Display de Sacolas --}}
                <div id="selected-live-bags-display" class="space-y-4 min-h-[300px]">
                    <div class="text-center text-gray-400 py-16">
                        <i class="fas fa-hand-pointer text-4xl mb-3 opacity-40"></i>
                        <p class="text-sm font-bold">Selecione uma live ao lado para ver suas sacolas.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Modal Confirmar Remoção (Tailwind pura) --}}
<div id="modalConfirmarRemocao" class="fixed inset-0 z-50 items-center justify-center bg-black/40 hidden">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4 mx-4">
        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
            <i class="fas fa-exclamation-triangle text-red-500"></i> Confirmar Remoção
        </h3>
        <p class="text-sm text-gray-600">
            Deseja realmente remover o item <span id="remove_item_name" class="font-extrabold text-gray-800"></span> no valor de <span id="remove_item_price" class="font-extrabold text-green-600"></span>?
        </p>
        <p class="text-xs text-gray-500 bg-gray-50 p-2.5 rounded border border-gray-150">
            Escolha se deseja descontar os pontos de jogo que o cliente ganhou por comprar este item.
        </p>
        <div class="flex justify-end gap-2 pt-2 text-xs">
            <button onclick="fecharModalRemocao()" class="px-4 py-2 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition">
                Cancelar
            </button>
            <button id="btnConfirmarSemDesconto" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold rounded-lg transition">
                Manter Pontos
            </button>
            <button id="btnConfirmarComDesconto" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                Descontar Pontos
            </button>
        </div>
    </div>
</div>

{{-- Scripts da Página --}}
<script>
    let allLives = [];
    let selectedLiveId = null;
    let showingAllLives = false;
    let itemParaRemover = null;

    document.addEventListener('DOMContentLoaded', () => {
        carregarTodasAsLives();
        carregarConfigSeverino();
    });

    async function carregarConfigSeverino() {
        try {
            const res = await fetch('/admin/chat-evolution/api/severino-config');
            const data = await res.json();
            if (data.success && data.config) {
                const phoneInput = document.getElementById('severino-test-phone');
                const modeSelect = document.getElementById('severino-live-mode');
                if (phoneInput && data.config.test_phone) phoneInput.value = data.config.test_phone;
                if (modeSelect && data.config.mode) modeSelect.value = data.config.mode;
            }
        } catch (e) {
            console.warn('Erro ao carregar config do Severino:', e);
        }
    }

    async function salvarConfigSeverino() {
        const phoneInput = document.getElementById('severino-test-phone');
        const modeSelect = document.getElementById('severino-live-mode');
        try {
            await fetch('/admin/chat-evolution/api/severino-config', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    test_phone: phoneInput ? phoneInput.value : '5548984136870',
                    mode: modeSelect ? modeSelect.value : 'test'
                })
            });
        } catch (e) {
            console.warn('Erro ao salvar config do Severino:', e);
        }
    }

    async function testarPecaLiveSeverino(liveId, userId, btn) {
        const phoneInput = document.getElementById('severino-test-phone');
        const modeSelect = document.getElementById('severino-live-mode');
        const isTest = !modeSelect || modeSelect.value !== 'live';
        const targetPhone = isTest ? (phoneInput?.value || '5548984136870') : '';

        const msgConfirm = isTest
            ? `Enviar notificação de "Peça Ganha na Live" desta cliente pelo Severino (Evolution API) para o número de teste (${targetPhone})?`
            : `Enviar notificação de "Peça Ganha na Live" pelo Severino para o WhatsApp REAL desta cliente?`;

        if (!confirm(msgConfirm)) return;

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Enviando...';

        try {
            const res = await fetch('/admin/chat-evolution/api/send-live-item-test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    live_id: liveId,
                    user_id: userId,
                    target_phone: isTest ? targetPhone : null
                })
            });
            const data = await res.json();
            alert(data.message || (data.success ? 'Enviado com sucesso!' : 'Erro ao enviar.'));
        } catch (e) {
            alert('Erro de conexão ao enviar mensagem do Severino.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }

    async function enviarFechamentoSeverino(liveId, userId, btn) {
        const phoneInput = document.getElementById('severino-test-phone');
        const modeSelect = document.getElementById('severino-live-mode');
        const pdfOnlyChk = document.getElementById('cq-pdf-only');
        const isTest = !modeSelect || modeSelect.value !== 'live';
        const pdfOnly = pdfOnlyChk ? pdfOnlyChk.checked : true;
        const targetPhone = isTest ? (phoneInput?.value || '5548984136870') : '';

        const tipoTxt = pdfOnly
            ? `APENAS o PDF ("Fechamento da Live #${liveId}", sem texto/sequência do Severino)`
            : `PDF + Fechamento Completo com Severino`;

        const msgConfirm = isTest
            ? `Deseja enviar ${tipoTxt} desta cliente para o número de teste (${targetPhone})?`
            : `Deseja enviar ${tipoTxt} para o WhatsApp REAL desta cliente?`;

        if (!confirm(msgConfirm)) return;

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Enviando...';

        try {
            const res = await fetch('/admin/chat-evolution/api/send-live-closing', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    live_id: liveId,
                    user_id: userId,
                    use_test_phone: isTest,
                    target_phone: isTest ? targetPhone : null,
                    pdf_only: pdfOnly
                })
            });
            const data = await res.json();
            alert(data.message || (data.success ? 'Fechamento enviado com sucesso!' : 'Erro ao enviar.'));
        } catch (e) {
            alert('Erro de conexão ao enviar fechamento pelo Severino.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }

    async function carregarTodasAsLives() {
        const body = document.getElementById('lives-table-body');
        body.innerHTML = '<tr><td colspan="5" class="text-center py-6 text-gray-500 text-sm"><i class="fas fa-spinner fa-spin mr-1"></i> Carregando...</td></tr>';
        
        try {
            const res = await fetch('/api/lives/all');
            const data = await res.json();
            if (data.success) {
                allLives = data.data || data.lives || [];
                document.getElementById('total-lives-count').textContent = allLives.length;
                renderLivesTable(allLives);
            }
        } catch (e) {
            body.innerHTML = '<tr><td colspan="5" class="text-center py-6 text-red-500 text-sm"><i class="fas fa-exclamation-circle mr-1"></i> Erro ao carregar lives</td></tr>';
        }
    }

    function renderLivesTable(lives) {
        const body = document.getElementById('lives-table-body');
        const filterVal = document.getElementById('search-live').value.toLowerCase().trim();
        
        // Filtra se houver valor
        let filtered = lives;
        if (filterVal) {
            filtered = lives.filter(l => 
                String(l.id).includes(filterVal) || 
                (l.tipo_live || '').toLowerCase().includes(filterVal) || 
                (l.plataformas || '').toLowerCase().includes(filterVal)
            );
        }

        const display = showingAllLives ? filtered : filtered.slice(0, 5);
        
        if (display.length === 0) {
            body.innerHTML = '<tr><td colspan="5" class="text-center py-6 text-gray-400 text-xs">Nenhuma live encontrada</td></tr>';
            return;
        }

        body.innerHTML = display.map(live => {
            const status = live.ativo ? 'ativa' : 'encerrada';
            const statusClass = status === 'ativa' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            const date = new Date(live.created_at).toLocaleDateString('pt-BR');
            const isSelected = selectedLiveId == live.id ? 'bg-blue-50 border-l-4 border-blue-500 font-bold' : 'hover:bg-gray-50';
            
            return `
                <tr class="live-row cursor-pointer transition ${isSelected}" data-live-id="${live.id}" onclick="selectLive(${live.id})">
                    <td class="px-4 py-3 text-sm text-gray-900 font-semibold">${live.id}</td>
                    <td class="px-4 py-3 text-sm text-gray-700 font-medium">${(live.tipo_live || '').toUpperCase()}</td>
                    <td class="px-4 py-3 text-sm text-gray-500">${live.plataformas || 'N/A'}</td>
                    <td class="px-4 py-3 text-sm">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide ${statusClass}">${status}</span>
                    </td>
                    <td class="px-4 py-3 text-center text-sm">
                        <button onclick="enviarPortalNotification(${live.id}, event)" 
                                class="bg-green-600 hover:bg-green-700 text-white font-extrabold px-2 py-1 rounded-lg text-[10px] uppercase tracking-wide flex items-center gap-1 mx-auto transition shadow-sm"
                                title="Notificar link do Portal via WhatsApp">
                            <i class="fas fa-paper-plane text-[9px]"></i> Portal
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function filtrarLives() {
        renderLivesTable(allLives);
    }

    function mostrarTodasAsLives() {
        showingAllLives = !showingAllLives;
        renderLivesTable(allLives);
    }

    const IS_MATRIZ = @json($isMatriz);
    let closingQueuePollTimer = null;

    function selectLive(id) {
        selectedLiveId = id;
        document.querySelectorAll('.live-row').forEach(r => {
            r.classList.remove('bg-blue-50', 'border-l-4', 'border-blue-500', 'font-bold');
        });
        const row = document.querySelector(`.live-row[data-live-id="${id}"]`);
        if (row) row.classList.add('bg-blue-50', 'border-l-4', 'border-blue-500', 'font-bold');
        document.getElementById('selected-live-info').textContent = `(ID: ${id})`;
        carregarSacolas(id);

        if (IS_MATRIZ) {
            const panel = document.getElementById('closing-queue-panel');
            if (panel) panel.classList.remove('hidden');
            carregarStatusFilaFechamento(id);
        }
    }

    async function carregarStatusFilaFechamento(liveId) {
        if (!IS_MATRIZ || !liveId) return;
        if (closingQueuePollTimer) {
            clearTimeout(closingQueuePollTimer);
            closingQueuePollTimer = null;
        }
        try {
            const res = await fetch(`/admin/chat-evolution/api/closing-queue/status/${liveId}`);
            const data = await res.json();
            if (data.success && data.state) {
                renderizarFilaFechamento(data.state);
                if (data.state.status === 'running' && selectedLiveId == liveId) {
                    closingQueuePollTimer = setTimeout(() => carregarStatusFilaFechamento(liveId), 4000);
                }
            }
        } catch (e) {
            console.warn('Erro ao consultar fila de fechamento:', e);
        }
    }

    function renderizarFilaFechamento(st) {
        const badge = document.getElementById('cq-status-badge');
        const btnStart = document.getElementById('btn-cq-start');
        const btnPause = document.getElementById('btn-cq-pause');
        const btnResume = document.getElementById('btn-cq-resume');
        const btnCancel = document.getElementById('btn-cq-cancel');
        const testLimitSel = document.getElementById('cq-test-limit');
        const progWrap = document.getElementById('cq-progress-wrap');
        if (!badge) return;

        const statusMap = {
            idle: { text: 'Pronto', bg: '#f3f4f6', fg: '#1f2937' },
            running: { text: '🚀 Enviando (5–55s)', bg: '#dcfce7', fg: '#14532d' },
            paused: { text: '⏸️ Pausado', bg: '#fef3c7', fg: '#78350f' },
            completed: { text: '✅ Concluído', bg: '#dbeafe', fg: '#1e3a8a' },
            cancelled: { text: '🛑 Cancelado', bg: '#fee2e2', fg: '#991b1b' },
        };
        const sm = statusMap[st.status] || statusMap.idle;
        badge.textContent = sm.text;
        badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-extrabold';
        badge.style.backgroundColor = sm.bg;
        badge.style.color = sm.fg;

        const isRunning = st.status === 'running';
        const isPaused = st.status === 'paused';
        const hasProgress = st.status !== 'idle' && (st.total || 0) > 0;

        btnStart.classList.toggle('hidden', isRunning || isPaused);
        if (testLimitSel) testLimitSel.classList.toggle('hidden', isRunning || isPaused);
        btnPause.classList.toggle('hidden', !isRunning);
        btnResume.classList.toggle('hidden', !isPaused);
        btnCancel.classList.toggle('hidden', !isRunning && !isPaused);
        progWrap.classList.toggle('hidden', !hasProgress);

        if (!hasProgress) return;

        document.getElementById('cq-sent').textContent = st.sent ?? 0;
        document.getElementById('cq-total').textContent = st.total ?? 0;
        document.getElementById('cq-pdfs').textContent = st.pdfs_sent ?? 0;
        document.getElementById('cq-pending').textContent = st.pending ?? 0;
        document.getElementById('cq-failed').textContent = st.failed ?? 0;
        document.getElementById('cq-progress-bar').style.width = `${st.progress_percent ?? 0}%`;

        const nextInfo = document.getElementById('cq-next-info');
        const modeDesc = st.pdf_only ? 'Apenas PDF' : 'PDF + Severino';
        if (isRunning) {
            if (st.current_client) {
                nextInfo.textContent = `Enviando (${modeDesc}) para ${st.current_client}...`;
            } else if (st.next_in_seconds !== null && st.next_in_seconds !== undefined) {
                nextInfo.textContent = `Próximo envio em ~${st.next_in_seconds}s (${modeDesc} • ${st.mode === 'test' ? '🧪 Teste ' + st.test_phone : '🟢 Real'})`;
            } else {
                nextInfo.textContent = 'Processando próximo item da fila...';
            }
        } else if (isPaused) {
            nextInfo.textContent = 'Fila pausada pelo operador. Clique em Retomar para continuar.';
        } else if (st.status === 'completed') {
            nextInfo.textContent = 'Todos os envios cadenciados desta live foram finalizados!';
        } else {
            nextInfo.textContent = '';
        }

        const listEl = document.getElementById('cq-items-list');
        if (listEl && Array.isArray(st.items)) {
            listEl.innerHTML = st.items.map(it => {
                const stBadge = it.status === 'sent'
                    ? `<span class="px-1.5 py-0.5 rounded font-bold" style="background-color:#dcfce7; color:#14532d;">✅ Enviado${it.pdf_sent ? ' + PDF' : ''} (${it.sent_at || ''})</span>`
                    : (it.status === 'failed'
                        ? `<span class="px-1.5 py-0.5 rounded font-bold" style="background-color:#fee2e2; color:#991b1b;">❌ Falha: ${it.error || ''}</span>`
                        : (it.status === 'skipped'
                            ? `<span class="px-1.5 py-0.5 rounded font-bold" style="background-color:#e5e7eb; color:#1f2937;">⏭️ ${it.error || 'Pulado'}</span>`
                            : `<span class="px-1.5 py-0.5 rounded font-bold" style="background-color:#e0e7ff; color:#1e1b4b;">⏳ Na fila</span>`));
                return `
                    <div class="px-3 py-1.5 flex items-center justify-between gap-2 text-[11px] text-gray-900">
                        <div>
                            <strong>${it.name}</strong>
                            <span class="ml-1.5 text-[10px] text-gray-600">${it.priority_label || ''}</span>
                        </div>
                        <div>${stBadge}</div>
                    </div>
                `;
            }).join('');
        }
    }

    async function iniciarFilaFechamento() {
        if (!selectedLiveId) return;
        const phoneInput = document.getElementById('severino-test-phone');
        const modeSelect = document.getElementById('severino-live-mode');
        const limitSel = document.getElementById('cq-test-limit');
        const pdfOnlyChk = document.getElementById('cq-pdf-only');
        const isTest = !modeSelect || modeSelect.value !== 'live';
        const pdfOnly = pdfOnlyChk ? pdfOnlyChk.checked : true;
        const targetPhone = isTest ? (phoneInput?.value || '5548984136870') : '';
        const limitVal = (isTest && limitSel && limitSel.value) ? parseInt(limitSel.value, 10) : null;

        const descTipo = pdfOnly
            ? `APENAS PDF ("Fechamento da Live #${selectedLiveId}", sem texto/sequência do Severino)`
            : `PDF + Fechamento Completo (Severino)`;

        const msg = isTest
            ? `Iniciar FECHAMENTO CADENCIADO (${descTipo}, intervalo 5s a 55s) em MODO TESTE?\n\nDestino: ${targetPhone}\nQuantidade: ${limitVal ? limitVal + ' primeiras clientes (por prioridade)' : 'Todas as clientes da live'}`
            : `ATENÇÃO: Iniciar FECHAMENTO CADENCIADO (${descTipo}, intervalo 5s a 55s) para o WhatsApp REAL de todas as clientes da Live #${selectedLiveId}?\n\nVocê pode pausar a fila a qualquer momento.`;

        if (!confirm(msg)) return;

        try {
            const res = await fetch('/admin/chat-evolution/api/closing-queue/start', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    live_id: selectedLiveId,
                    use_test_phone: isTest,
                    target_phone: isTest ? targetPhone : null,
                    min_delay: 5,
                    max_delay: 55,
                    limit_test: limitVal,
                    pdf_only: pdfOnly
                })
            });
            const data = await res.json();
            if (!data.success) {
                alert(data.error || 'Não foi possível iniciar a fila de fechamento.');
                return;
            }
            if (data.state) {
                renderizarFilaFechamento(data.state);
                closingQueuePollTimer = setTimeout(() => carregarStatusFilaFechamento(selectedLiveId), 3000);
            }
        } catch (e) {
            alert('Erro ao iniciar fila cadenciada de fechamento.');
        }
    }

    async function pausarFilaFechamento() {
        if (!selectedLiveId) return;
        const res = await fetch('/admin/chat-evolution/api/closing-queue/pause', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ live_id: selectedLiveId })
        });
        const data = await res.json();
        if (data.state) renderizarFilaFechamento(data.state);
    }

    async function retomarFilaFechamento() {
        if (!selectedLiveId) return;
        const res = await fetch('/admin/chat-evolution/api/closing-queue/resume', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ live_id: selectedLiveId })
        });
        const data = await res.json();
        if (data.state) {
            renderizarFilaFechamento(data.state);
            closingQueuePollTimer = setTimeout(() => carregarStatusFilaFechamento(selectedLiveId), 3000);
        }
    }

    async function cancelarFilaFechamento() {
        if (!selectedLiveId) return;
        if (!confirm('Deseja cancelar os envios restantes da fila de fechamento desta live?')) return;
        const res = await fetch('/admin/chat-evolution/api/closing-queue/cancel', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ live_id: selectedLiveId })
        });
        const data = await res.json();
        if (data.state) renderizarFilaFechamento(data.state);
    }

    async function carregarSacolas(liveId) {
        const container = document.getElementById('selected-live-bags-display');
        container.innerHTML = '<div class="text-center py-16 text-gray-500"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

        try {
            const res = await fetch(`/api/sacolinhas/live/${liveId}`);
            const data = await res.json();
            if (data.success) {
                exibirSacolas(data.data, container, liveId);
            } else {
                container.innerHTML = `<div class="text-center py-16 text-red-500 font-semibold">${data.message}</div>`;
            }
        } catch (e) {
            container.innerHTML = '<div class="text-center py-16 text-red-500 font-semibold">Erro ao carregar sacolas.</div>';
        }
    }

    // Cache local de sacolas para busca em tempo real
    let currentLiveBags = [];
    let currentLiveId = null;
    function exibirSacolas(bags, container, liveId) {
        currentLiveBags = bags || [];
        currentLiveId = liveId;
        filtrarSacolas();
    }

    function filtrarSacolas() {
        const container = document.getElementById('selected-live-bags-display');
        const filterVal = document.getElementById('search-client').value.toLowerCase().trim();

        let filtered = currentLiveBags;
        if (filterVal) {
            filtered = currentLiveBags.filter(b => 
                (b.client.name || '').toLowerCase().includes(filterVal) ||
                String(b.client.id).includes(filterVal)
            );
        }

        if (!filtered || filtered.length === 0) {
            container.innerHTML = '<div class="text-center py-16 text-gray-400 text-sm">Nenhuma sacola encontrada.</div>';
            return;
        }

        container.innerHTML = filtered.map(bag => `
            <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm bg-white card-bag">
                <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center flex-wrap gap-2">
                    <div class="flex items-center gap-3">
                        <img src="${bag.client.avatar_url}" class="rounded-full w-8 h-8 object-cover border border-gray-300">
                        <div>
                            <strong class="text-gray-800 text-sm">${bag.client.name}</strong> 
                            <span class="text-xs text-gray-450 block">ID: ${bag.client.id}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <button onclick="enviarMensagemInicial(${currentLiveId}, ${bag.client.id}, this)" 
                                class="bg-green-600 hover:bg-green-700 text-white font-extrabold px-2.5 py-1.5 rounded-lg text-[9px] uppercase tracking-wide flex items-center gap-1 transition shadow-sm"
                                title="Testar Msg Inicial via Twilio (Portal)">
                            <i class="fab fa-whatsapp text-xs"></i> Msg Inicial (Twilio)
                        </button>
                        ${IS_MATRIZ ? `
                        <button onclick="testarPecaLiveSeverino(${currentLiveId}, ${bag.client.id}, this)" 
                                class="bg-purple-600 hover:bg-purple-700 text-white font-extrabold px-2.5 py-1.5 rounded-lg text-[9px] uppercase tracking-wide flex items-center gap-1 transition shadow-sm"
                                title="Simular clique na cliente durante a live (Severino avisa a peça ganha em 2 balões na 1ª msg ou 1 balão curto nas demais)">
                            <i class="fas fa-gift text-xs"></i> Peça Live (Severino)
                        </button>
                        <button onclick="enviarFechamentoSeverino(${currentLiveId}, ${bag.client.id}, this)" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold px-2.5 py-1.5 rounded-lg text-[9px] uppercase tracking-wide flex items-center gap-1 transition shadow-sm"
                                title="Enviar PDF + Fechamento Completo no WhatsApp com o Severino (Evolution API)">
                            <i class="fas fa-file-pdf text-xs"></i> Fechamento + PDF (Severino)
                        </button>
                        ` : ''}
                        <div class="text-right ml-1">
                            <span class="px-2.5 py-0.5 bg-blue-100 text-blue-800 rounded-full text-[10px] font-bold uppercase tracking-wide">Itens: ${bag.total_items}</span>
                            <div class="font-black text-green-600 text-sm mt-0.5">${bag.formatted_total}</div>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-150 text-xs">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-500 uppercase">Cód</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-500 uppercase">Item</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-500 uppercase">Detalhes</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-500 uppercase">Preço</th>
                                <th class="px-4 py-2 text-center font-semibold text-gray-500 uppercase" width="130">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            ${bag.items.map(item => {
                                const itemStatus = (item.item_status || 'pendente').toLowerCase();
                                let statusBadgeClass = 'bg-gray-100 text-gray-700';
                                if (itemStatus === 'reservado') statusBadgeClass = 'bg-yellow-100 text-yellow-800 border border-yellow-250';
                                if (itemStatus === 'sacolinha') statusBadgeClass = 'bg-green-100 text-green-800 border border-green-250';
                                if (itemStatus === 'vendido') statusBadgeClass = 'bg-blue-100 text-blue-850';
                                
                                return `
                                    <tr class="hover:bg-gray-50/30">
                                        <td class="px-4 py-2 text-gray-400 font-mono">${item.item_sku || '---'}</td>
                                        <td class="px-4 py-2 font-bold text-gray-800">${item.item_name}</td>
                                        <td class="px-4 py-2 text-gray-500">${item.item_brand || ''} • ${item.item_color || ''} • ${item.item_size || ''}</td>
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase ${statusBadgeClass}">${itemStatus}</span>
                                        </td>
                                        <td class="px-4 py-2 text-right font-bold text-gray-900">${item.formatted_total_price}</td>
                                        <td class="px-4 py-2 text-center">
                                            <div class="inline-flex rounded-md shadow-sm text-[10px]" role="group">
                                                <button class="px-2.5 py-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded-l-md font-bold transition disabled:opacity-50 disabled:pointer-events-none" 
                                                        onclick="alterarStatus(${item.item_id}, 'reservado', this)" 
                                                        ${item.item_status === 'reservado' ? 'disabled' : ''}>R</button>
                                                <button class="px-2.5 py-1 bg-green-600 hover:bg-green-700 text-white font-bold transition disabled:opacity-50 disabled:pointer-events-none" 
                                                        onclick="alterarStatus(${item.item_id}, 'sacolinha', this)" 
                                                        ${item.item_status === 'sacolinha' ? 'disabled' : ''}>S</button>
                                                <button class="px-2.5 py-1 border border-red-200 text-red-600 hover:bg-red-50 rounded-r-md font-bold transition" 
                                                        onclick="abrirModalRemocao(${item.item_id}, ${bag.client.id}, '${item.item_name.replace(/'/g, "\\'")}', '${item.formatted_total_price}')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `).join('');
    }

    async function alterarStatus(itemId, status, btn) {
        btn.disabled = true;
        try {
            const res = await fetch(`/api/items/${itemId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({status})
            });
            const data = await res.json();
            if (data.success) carregarSacolas(selectedLiveId);
        } catch (e) { alert('Erro ao alterar status'); }
        btn.disabled = false;
    }

    function abrirModalRemocao(itemId, userId, name, price) {
        itemParaRemover = { itemId, userId };
        document.getElementById('remove_item_name').textContent = name;
        document.getElementById('remove_item_price').textContent = price;
        const modal = document.getElementById('modalConfirmarRemocao');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function fecharModalRemocao() {
        const modal = document.getElementById('modalConfirmarRemocao');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.getElementById('btnConfirmarComDesconto').onclick = () => executarRemocao(true);
    document.getElementById('btnConfirmarSemDesconto').onclick = () => executarRemocao(false);

    async function executarRemocao(descontar) {
        const { itemId, userId } = itemParaRemover;
        try {
            const res = await fetch('/api/sacolinhas/remove', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ item_id: itemId, user_id: userId, live_id: selectedLiveId, descontar_pontos: descontar })
            });
            const data = await res.json();
            if (data.success) {
                fecharModalRemocao();
                carregarSacolas(selectedLiveId);
            }
        } catch (e) { alert('Erro ao remover item'); }
    }

    async function enviarPortalNotification(liveId, event) {
        event.stopPropagation(); // Impede seleção da linha
        
        if (!confirm('Deseja realmente enviar a notificação com link seguro do Portal para todos os clientes desta live?')) {
            return;
        }

        try {
            const res = await fetch(`/admin/live/${liveId}/send-portal-notifications`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
            } else {
                alert(data.message || 'Erro ao enviar notificações.');
            }
        } catch (e) {
            alert('Erro ao enviar notificações. Verifique a conexão.');
        }
    }

    async function enviarMensagemInicial(liveId, userId, btn) {
        if (!confirm('Deseja enviar a mensagem desta cliente (via Twilio) para o seu WhatsApp pessoal (5548984136870) para teste?')) {
            return;
        }

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Enviando...';

        try {
            const res = await fetch(`/lives/${liveId}/sacolas/${userId}/whatsapp/first`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message || 'Mensagem enfileirada com sucesso!');
            } else {
                alert(data.message || 'Erro ao enviar a mensagem.');
            }
        } catch (e) {
            alert('Erro de conexão ao enviar a mensagem.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }
</script>
@endsection

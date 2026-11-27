<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Chat WhatsApp (Evolution API)</title>
	<link rel="icon" href="{{ asset('favicon.ico') }}">

	<meta name="csrf-token" content="{{ csrf_token() }}">

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

	<style>
		body { background-color:#f0f0f0; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin:0; height:100vh; height:100dvh; }
		.chat-layout { width:100%; height:100vh; height:100dvh; display:flex; background:#fff; position:relative; overflow:hidden; }

		.chat-drawer {
			width: 360px;
			background: #f8f9fa;
			border-right: 1px solid #ddd;
			display: flex;
			flex-direction: column;
			z-index: 1;
		}

		@media (max-width: 768px) {
			.chat-drawer { width: 100vw; position: fixed; top: 0; left: 0; height: 100vh; height: 100dvh; z-index: 1000; }
			.chat-drawer.hidden-mobile { display: none !important; }
			.message-bubble { max-width: 88% !important; }
			.chat-header { padding: 10px 12px !important; flex-wrap: wrap; }
			.chat-messages { padding: 12px !important; }
		}

		.sidebar-header { padding:14px 16px; background:#128c7e; color:#fff; font-weight:600; }
		.conversation-list { flex:1; overflow-y:auto; }

		.conversation-search { padding:10px 12px; border-bottom:1px solid #e6e6e6; background:#f8f9fa; }
		.conversation-search .form-control { border-radius:999px; padding-left:38px; }
		.conversation-search-wrap { position:relative; }
		.conversation-search .search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#777; }

		.conversation-item { position:relative; display:flex; gap:12px; align-items:center; padding:12px 14px; border-bottom:1px solid #eee; cursor:pointer; }
		.conversation-item:hover { background:#eef2f5; }
		.conversation-item.active { background:#dcf8c6; }
		.conversation-avatar { width:44px; height:44px; border-radius:50%; background:#128c7e; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; flex:0 0 auto; }
		.conversation-info { flex:1; min-width:0; }
		.conversation-name { font-weight:700; margin:0; }
		.conversation-last-message { font-size:.85em; color:#666; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
		.conversation-time { font-size:.75em; color:#999; flex:0 0 auto; }
		.unread-badge { position:absolute; top:8px; right:12px; background-color:#25d366; color:white; font-size:.7rem; font-weight:bold; min-width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center; padding:0 4px; }

		.chat-main { flex:1; display:flex; flex-direction:column; min-width:0; }
		.chat-header { background:#128c7e; color:#fff; padding:14px 18px; border-bottom:1px solid #ddd; display:flex; align-items:center; justify-content:space-between; gap:10px; }
		.chat-messages { flex:1; padding:18px; overflow-y:auto; background:#f0f0f0; display:flex; flex-direction:column; gap:10px; }
		.message-row { display:flex; width:100%; }
		.message-bubble { max-width:70%; padding:10px 12px; border-radius:8px; box-shadow:0 1px 2px rgba(0,0,0,.08); font-size:.95em; background:#fff; }
		.sent-message { background:#dcf8c6; margin-left:auto; }
		.received-message { background:#fff; margin-right:auto; }
		.message-content { white-space:pre-wrap; }
		.timestamp { font-size:.75em; color:#777; text-align:right; display:flex; justify-content:flex-end; gap:6px; align-items:center; margin-top:6px; }

		.chat-input { padding: 12px 14px; border-top: 1px solid #ddd; background: #f0f0f0; display:flex; gap:10px; align-items:center; }
		.chat-input textarea { resize:none; border-radius:20px; }

		.badge-status-online { background-color: #25d366; color: #fff; font-size: 0.75rem; padding: 4px 8px; border-radius: 12px; }
		.badge-status-offline { background-color: #dc3545; color: #fff; font-size: 0.75rem; padding: 4px 8px; border-radius: 12px; }
	</style>
</head>
<body>
	<div class="chat-layout">
		<!-- Sidebar de conversas -->
		<div class="chat-drawer" id="chatDrawer">
			<div class="sidebar-header d-flex justify-content-between align-items-center gap-2">
				<div class="d-flex align-items-center gap-2">
					<a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-light py-0 px-2" style="font-size:0.75rem;" title="Voltar ao Painel">
						<i class="bi bi-arrow-left"></i>
					</a>
					<i class="bi bi-whatsapp fs-5"></i>
					<span style="font-size:0.95rem;">Chat Evolution</span>
				</div>
				<button class="btn btn-sm btn-outline-light" id="btnQrModal" data-bs-toggle="modal" data-bs-target="#qrModal" title="Conexão WhatsApp">
					<i class="bi bi-qr-code"></i> Conectar
				</button>
			</div>

			@if(auth()->user() && auth()->user()->is_admin == 1 && auth()->user()->role === 'admin_master')
			<!-- Controle Global do Severino (sincronizado com a Live) -->
			<div style="background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:8px 12px;">
				<div class="d-flex align-items-center justify-content-between gap-2 mb-1">
					<span style="font-size:0.7rem; font-weight:800; color:#334155; text-transform:uppercase; letter-spacing:0.03em;">
						🤖 Severino (Global)
					</span>
					<button type="button" id="btnGlobalAutoReply" onclick="toggleGlobalAutoReply()"
							class="btn btn-sm py-0 px-2 fw-bold"
							style="font-size:0.68rem; border-radius:999px; background:#fef3c7; color:#78350f; border:1px solid #fcd34d;"
							title="ON = novas conversas são atribuídas automaticamente ao Severino. OFF = Severino só responde nas conversas atribuídas a ele!">
						⏸️ Auto-IA: OFF
					</button>
				</div>
				<select id="globalSeverinoMode" onchange="saveGlobalSeverinoConfig()"
						class="form-select form-select-sm fw-bold mb-1"
						style="font-size:0.74rem; background:#ffffff; color:#0f172a; border-color:#cbd5e1;"
						title="Define o destino dos disparos ao clicar na cliente na Live">
					<option value="test">🧪 Live: Enviar p/ Tel Teste</option>
					<option value="live">🟢 Live: Enviar p/ Cliente Real</option>
					<option value="off">⚪ Live: Disparo Desativado</option>
				</select>
				<select id="filterAssigneeSelector" onchange="renderConversations()"
						class="form-select form-select-sm fw-semibold"
						style="font-size:0.74rem; background:#ffffff; color:#0f172a; border-color:#cbd5e1;"
						title="Filtrar conversas por atendente atribuído">
					<option value="">👥 Todas as conversas</option>
					<option value="unassigned">⚪ Não atribuídas</option>
					<option value="severino">🤖 Severino (IA)</option>
				</select>
			</div>
			@endif

			<div class="conversation-search">
				<div class="conversation-search-wrap">
					<i class="bi bi-search search-icon"></i>
					<input type="text" class="form-control" id="searchContact" placeholder="Buscar cliente...">
				</div>
			</div>

			<div class="conversation-list" id="conversationList">
				<div class="text-center p-4 text-muted">Carregando conversas...</div>
			</div>
		</div>

		<!-- Painel do Chat -->
		<div class="chat-main" id="chatMain">
			<div class="chat-header">
				<div class="d-flex align-items-center gap-2">
					<button class="btn btn-sm text-white d-md-none" id="btnBackToList"><i class="bi bi-arrow-left fs-5"></i></button>
					<div>
						<h6 class="mb-0" id="currentUserName">Selecione uma conversa</h6>
						<small id="currentUserPhone" class="opacity-75"></small>
					</div>
				</div>
				<div class="d-flex align-items-center gap-2 flex-wrap">
					@if(auth()->user() && auth()->user()->is_admin == 1 && auth()->user()->role === 'admin_master')
					<div id="assignmentControls" style="display:none; align-items:center; gap:6px;">
						<label for="conversationAssigneeSelector" class="mb-0 d-none d-sm-inline" style="font-size:0.72rem; font-weight:700; color:#e2e8f0;">Atribuído:</label>
						<select id="conversationAssigneeSelector" onchange="assignEvolutionConversation()"
								class="form-select form-select-sm fw-bold"
								style="font-size:0.76rem; background:#ffffff; color:#0f172a; border:1px solid #cbd5e1; max-width:190px; border-radius:999px; padding-left:10px;"
								title="Atribua a conversa a um atendente ou ao Severino (IA) para ele responder automaticamente">
							<option value="">⚪ Ninguém</option>
							<option value="severino">🤖 Severino (IA)</option>
						</select>
					</div>
					<button type="button" id="btnToggleSimulator" onclick="toggleSimulatorBar()"
							class="btn btn-sm fw-bold" style="display:none; border-radius:999px; font-size:0.75rem; background:#ffffff; color:#312e81; border:1px solid #c7d2fe;"
							title="Abrir/fechar barra para simular resposta da cliente (testes)">
						🧪 Simular
					</button>
					@endif
					<div id="connectionStatusIndicator">
						<span class="badge bg-secondary">Checando...</span>
					</div>
				</div>
			</div>

			<div class="chat-messages" id="messagesContainer">
				<div class="text-center my-auto text-muted">
					<i class="bi bi-chat-dots fs-1 mb-2"></i>
					<p>Selecione um cliente ao lado para ver o histórico e responder sem restrição de 24h.</p>
				</div>
			</div>

			<!-- Barra de Simulação Rápida para Testar Respostas da Cliente com o Severino -->
			<div id="simulatorArea" style="display:none; background:#eef2ff; border-top:1px solid #c7d2fe; padding:8px 14px;">
				<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
					<span class="fw-bold" style="font-size:0.75rem; color:#312e81;">
						<i class="bi bi-magic me-1"></i>Simular Resposta da Cliente (Testar Severino IA):
					</span>
					<div class="d-flex flex-wrap gap-1">
						<button type="button" class="btn btn-sm py-0 px-2 fw-semibold" style="font-size:0.72rem; background:#4f46e5; color:#fff;" onclick="sendSimulatedReply('Quero adicionar à sacolinha e pagar no Pix!')">
							1️⃣ Guardar na Sacolinha (Pix)
						</button>
						<button type="button" class="btn btn-sm py-0 px-2 fw-semibold" style="font-size:0.72rem; background:#7c3aed; color:#fff;" onclick="sendSimulatedReply('Quero cancelar algum item da live de hoje, quais peças estão aí?')">
							2️⃣ Cancelar algum item
						</button>
						<button type="button" class="btn btn-sm py-0 px-2 fw-semibold" style="font-size:0.72rem; background:#059669; color:#fff;" onclick="sendSimulatedReply('Quanto fica o frete pra enviar tudo pra minha casa?')">
							3️⃣ Cotar Frete / Envio
						</button>
						<button type="button" class="btn btn-sm py-0 px-2 fw-semibold" style="font-size:0.72rem; background:#d97706; color:#fff;" onclick="sendSimulatedReply('Preciso falar com uma atendente humana por favor')">
							4️⃣ Chamar Atendente
						</button>
					</div>
				</div>
				<div class="d-flex gap-2 align-items-center">
					<input type="text" id="simulatedClientInput" class="form-control form-control-sm" 
						   style="font-size:0.8rem; background:#fff; color:#111827; border-color:#a5b4fc;"
						   placeholder="Ou digite aqui como se fosse a cliente respondendo no WhatsApp (ex: Cancela a primeira peça)..."
						   onkeydown="if(event.key==='Enter'){ event.preventDefault(); sendSimulatedReply(this.value); }">
					<button type="button" id="btnSimulateReply" class="btn btn-sm fw-bold" style="background:#3730a3; color:#fff; white-space:nowrap;" onclick="sendSimulatedReply(document.getElementById('simulatedClientInput').value)">
						Simular Cliente
					</button>
				</div>
			</div>

			<div class="chat-input" id="chatInputArea" style="display:none;">
				<input type="file" id="fileInput" style="display:none;">
				<button class="btn btn-light rounded-circle" type="button" onclick="document.getElementById('fileInput').click()" title="Anexar arquivo">
					<i class="bi bi-paperclip fs-5 text-secondary"></i>
				</button>
				<textarea class="form-control" id="messageInput" rows="1" placeholder="Digite como Atendente (pausa o Severino automaticamente)..."></textarea>
				<button class="btn btn-success rounded-circle" id="btnSendMessage" title="Enviar">
					<i class="bi bi-send-fill fs-5"></i>
				</button>
			</div>
		</div>
	</div>

	<!-- Modal de QR Code -->
	<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header bg-success text-white">
					<h5 class="modal-title"><i class="bi bi-qr-code-scan me-2"></i>Conectar WhatsApp (Evolution API)</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body text-center p-4">
					<div id="qrLoading">
						<div class="spinner-border text-success mb-2" role="status"></div>
						<p class="text-muted">Obtendo status da instância...</p>
					</div>
					<div id="qrConnected" style="display:none;">
						<i class="bi bi-check-circle-fill text-success" style="font-size: 3.5rem;"></i>
						<h5 class="mt-2 text-success">WhatsApp Conectado!</h5>
						<p class="text-muted small">O número está pareado e pronto para envio e recebimento de mensagens.</p>
						<button class="btn btn-outline-danger btn-sm mt-2" id="btnLogoutInstance">
							<i class="bi bi-box-arrow-right"></i> Desconectar WhatsApp
						</button>
					</div>
					<div id="qrContainer" style="display:none;">
						<p class="mb-2">Abra o WhatsApp no celular &rarr; <b>Aparelhos Conectados</b> &rarr; <b>Conectar um aparelho</b>:</p>
						<div id="qrLoadingInner" style="display:none;" class="my-3">
							<div class="spinner-border text-success" role="status"></div>
							<div class="text-muted small mt-1">Gerando novo QR Code...</div>
						</div>
						<img id="qrImage" src="" alt="QR Code WhatsApp" class="img-fluid border p-2 rounded mb-3" style="max-width: 250px;">
						<div>
							<button class="btn btn-sm btn-outline-secondary" id="btnRefreshQr"><i class="bi bi-arrow-clockwise"></i> Atualizar QR Code</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
		let activeConv = {
			userId: null,
			remoteJid: null,
			name: '',
			phone: '',
			supervisionMode: 'severino'
		};

		function updateSupervisionButtonUI(mode) {
			activeConv.supervisionMode = mode || 'severino';
			const btn = document.getElementById('btnToggleSupervision');
			if (!btn) return;

			if (!activeConv.remoteJid && !activeConv.userId) {
				btn.style.display = 'none';
				return;
			}

			btn.style.display = 'inline-flex';
			if (activeConv.supervisionMode === 'human') {
				btn.style.backgroundColor = '#f59e0b';
				btn.style.color = '#111827';
				btn.style.border = '1px solid #d97706';
				btn.innerHTML = '👩‍💻 Humano no Comando (Clique p/ Ativar Severino)';
			} else {
				btn.style.backgroundColor = '#e0e7ff';
				btn.style.color = '#1e1b4b';
				btn.style.border = '1px solid #818cf8';
				btn.innerHTML = '🤖 Severino Ativo (Clique p/ Assumir)';
			}
		}

		async function toggleConversationSupervision() {
			if (!activeConv.remoteJid && !activeConv.userId) return;
			const nextMode = activeConv.supervisionMode === 'severino' ? 'human' : 'severino';
			try {
				const res = await fetch('/admin/chat-evolution/api/supervision', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': csrfToken
					},
					body: JSON.stringify({
						remote_jid: activeConv.remoteJid,
						user_id: activeConv.userId,
						mode: nextMode
					})
				});
				const data = await res.json();
				if (data.success) {
					updateSupervisionButtonUI(data.supervision_mode);
					loadConversations();
				}
			} catch (e) {
				console.error(e);
			}
		}

		async function sendSimulatedReply(text) {
			const cleanText = (text || '').trim();
			if (!cleanText) return;
			if (!activeConv.remoteJid && !activeConv.userId) {
				alert('Selecione uma conversa ao lado primeiro.');
				return;
			}

			const btnSim = document.getElementById('btnSimulateReply');
			const inputSim = document.getElementById('simulatedClientInput');
			if (btnSim) {
				btnSim.disabled = true;
				btnSim.innerText = 'Pensando...';
			}

			try {
				const res = await fetch('/admin/chat-evolution/api/simulate-client-reply', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': csrfToken
					},
					body: JSON.stringify({
						remote_jid: activeConv.remoteJid,
						user_id: activeConv.userId,
						body: cleanText
					})
				});
				const data = await res.json();
				if (data.success) {
					if (inputSim) inputSim.value = '';
					updateSupervisionButtonUI('severino');
					await loadMessages(true);
					await loadConversations();
				} else {
					alert('Erro ao simular resposta.');
				}
			} catch (e) {
				console.error(e);
				alert('Erro na comunicação ao simular resposta.');
			} finally {
				if (btnSim) {
					btnSim.disabled = false;
					btnSim.innerText = 'Simular Cliente';
				}
			}
		}

		// Checa status de conexão
		async function checkStatus() {
			try {
				const res = await fetch('/admin/chat-evolution/api/status');
				const data = await res.json();
				const state = data.state;

				const indicator = document.getElementById('connectionStatusIndicator');
				const qrLoading = document.getElementById('qrLoading');
				const qrConnected = document.getElementById('qrConnected');
				const qrContainer = document.getElementById('qrContainer');
				const qrImg = document.getElementById('qrImage');

				qrLoading.style.display = 'none';

				if (state === 'open' || state === 'connected') {
					indicator.innerHTML = '<span class="badge-status-online"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i> Online (Conectado)</span>';
					qrConnected.style.display = 'block';
					qrContainer.style.display = 'none';
				} else {
					indicator.innerHTML = '<span class="badge-status-offline"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i> Desconectado</span>';
					qrConnected.style.display = 'none';
					qrContainer.style.display = 'block';

					const qrLoadingInner = document.getElementById('qrLoadingInner');
					if (data.qr && data.qr.base64) {
						qrImg.src = data.qr.base64;
						qrImg.style.display = 'inline-block';
						if (qrLoadingInner) qrLoadingInner.style.display = 'none';
					} else {
						qrImg.style.display = 'none';
						if (qrLoadingInner) qrLoadingInner.style.display = 'block';
					}
				}
			} catch (e) {
				console.error(e);
			}
		}

		function renderStatusTick(status) {
			if (status === 'read') {
				return '<i class="bi bi-check2-all" style="color:#0284c7; font-size:1.1em;" title="Lida"></i>';
			}
			if (status === 'delivered') {
				return '<i class="bi bi-check2-all text-secondary" style="font-size:1.1em;" title="Recebida"></i>';
			}
			if (status === 'failed') {
				return '<i class="bi bi-exclamation-circle text-danger" title="Não enviada"></i>';
			}
			return '<i class="bi bi-check2 text-secondary" title="Enviada"></i>';
		}

		const IS_MASTER = @json(auth()->user() && auth()->user()->is_admin == 1 && auth()->user()->role === 'admin_master');
		let allEvolutionConvs = [];
		let evolutionAdmins = [];

		async function loadEvolutionAdmins() {
			if (!IS_MASTER) return;
			try {
				const res = await fetch('/admin/chat-evolution/api/admins');
				evolutionAdmins = await res.json();

				const filterSel = document.getElementById('filterAssigneeSelector');
				const headerSel = document.getElementById('conversationAssigneeSelector');

				if (filterSel) {
					const curFilter = filterSel.value;
					filterSel.innerHTML = '<option value="">👥 Todas as conversas</option><option value="unassigned">⚪ Não atribuídas</option>';
					evolutionAdmins.forEach(a => {
						const label = a.is_severino ? a.name : ('👤 ' + a.name.split(' ')[0]);
						filterSel.innerHTML += `<option value="${escapeHtml(String(a.id))}">${escapeHtml(label)}</option>`;
					});
					filterSel.value = curFilter;
				}

				if (headerSel) {
					const curVal = activeConv.assignedValue || '';
					headerSel.innerHTML = '<option value="">⚪ Ninguém</option>';
					evolutionAdmins.forEach(a => {
						const label = a.is_severino ? a.name : ('👤 ' + a.name.split(' ')[0]);
						headerSel.innerHTML += `<option value="${escapeHtml(String(a.id))}">${escapeHtml(label)}</option>`;
					});
					headerSel.value = curVal;
				}
			} catch (e) {
				console.warn('Erro ao carregar atendentes:', e);
			}
		}

		function updateAssignmentUI(assignedValue) {
			activeConv.assignedValue = assignedValue || '';
			const wrap = document.getElementById('assignmentControls');
			const sel = document.getElementById('conversationAssigneeSelector');
			if (!wrap || !sel) return;
			if (!activeConv.remoteJid && !activeConv.userId) {
				wrap.style.display = 'none';
				return;
			}
			wrap.style.display = 'inline-flex';
			sel.value = activeConv.assignedValue;
			if (activeConv.assignedValue === 'severino') {
				sel.style.background = '#e0e7ff';
				sel.style.color = '#1e1b4b';
				sel.style.borderColor = '#818cf8';
			} else if (activeConv.assignedValue !== '') {
				sel.style.background = '#fef3c7';
				sel.style.color = '#78350f';
				sel.style.borderColor = '#f59e0b';
			} else {
				sel.style.background = '#ffffff';
				sel.style.color = '#0f172a';
				sel.style.borderColor = '#cbd5e1';
			}
		}

		async function assignEvolutionConversation() {
			if (!IS_MASTER || !activeConv.remoteJid) return;
			const sel = document.getElementById('conversationAssigneeSelector');
			const assignee = sel ? sel.value : '';
			try {
				const res = await fetch('/admin/chat-evolution/api/assign', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': csrfToken
					},
					body: JSON.stringify({
						remote_jid: activeConv.remoteJid,
						user_id: activeConv.userId,
						assignee: assignee
					})
				});
				const data = await res.json();
				if (data.success) {
					updateAssignmentUI(data.assignment ? data.assignment.assigned_value : assignee);
					await loadConversations();
				} else {
					alert(data.error || 'Erro ao atribuir conversa.');
				}
			} catch (e) {
				alert('Erro de comunicação ao atribuir conversa.');
			}
		}

		// Carrega conversas
		async function loadConversations() {
			try {
				const res = await fetch('/admin/chat-evolution/api/conversations');
				const convs = await res.json();
				allEvolutionConvs = Array.isArray(convs) ? convs : [];
				renderConversations();
			} catch (e) {
				console.error(e);
			}
		}

		function renderConversations() {
			const list = document.getElementById('conversationList');
			const prevListScroll = list.scrollTop;

			if (!allEvolutionConvs || !allEvolutionConvs.length) {
				list.innerHTML = '<div class="text-center p-4 text-muted">Nenhuma conversa encontrada.</div>';
				return;
			}

			const searchInput = document.getElementById('searchContact');
			const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
			const filterSel = document.getElementById('filterAssigneeSelector');
			const filterVal = filterSel ? filterSel.value : '';

			const filtered = allEvolutionConvs.filter(c => {
				const name = (c.display_name || 'Contato').toLowerCase();
				const phone = (c.display_phone || '').toLowerCase();
				const assignedName = (c.assigned_admin_name || '').toLowerCase();
				const assignedVal = String(c.assigned_value || '');

				if (filterVal === 'unassigned' && assignedVal !== '') return false;
				if (filterVal && filterVal !== 'unassigned' && assignedVal !== filterVal) return false;
				if (searchTerm && !name.includes(searchTerm) && !phone.includes(searchTerm) && !assignedName.includes(searchTerm)) return false;
				return true;
			});

			if (!filtered.length) {
				list.innerHTML = '<div class="text-center p-4 text-muted small">Nenhuma conversa neste filtro.</div>';
				return;
			}

			list.innerHTML = filtered.map(c => {
				const isSelected = (activeConv.remoteJid && activeConv.remoteJid === c.remote_jid) 
					|| (activeConv.userId && activeConv.userId === c.user_id);
				const name = c.display_name || 'Contato';
				const initial = name.charAt(0).toUpperCase() || '?';
				const phone = c.display_phone || '';
				const time = c.last_message_at ? new Date(c.last_message_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}) : '';
				const unread = c.unread_count > 0 ? `<span class="unread-badge">${c.unread_count}</span>` : '';
				const assignedType = c.assigned_type || 'none';
				const assignedVal = String(c.assigned_value || '');
				const assignedName = c.assigned_admin_name || '';

				let assignBadge = '';
				if (assignedType === 'severino') {
					assignBadge = `<span style="background:#e0e7ff; color:#1e1b4b; border:1px solid #818cf8; font-size:0.64rem; font-weight:700; padding:1px 6px; border-radius:999px; white-space:nowrap; flex-shrink:0;">🤖 Severino</span>`;
				} else if (assignedType === 'admin' && assignedName) {
					const firstAdmin = assignedName.split(' ')[0];
					assignBadge = `<span style="background:#fef3c7; color:#92400e; border:1px solid #f59e0b; font-size:0.64rem; font-weight:700; padding:1px 6px; border-radius:999px; white-space:nowrap; flex-shrink:0;">👤 ${escapeHtml(firstAdmin)}</span>`;
				} else {
					assignBadge = `<span style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; font-size:0.62rem; font-weight:600; padding:1px 6px; border-radius:999px; white-space:nowrap; flex-shrink:0;">Sem atribuição</span>`;
				}

				if (isSelected) {
					updateAssignmentUI(assignedVal);
				}

				return `
					<div class="conversation-item ${isSelected ? 'active' : ''}" 
						 data-user-id="${c.user_id || ''}" 
						 data-remote-jid="${escapeHtml(c.remote_jid || '')}" 
						 data-name="${escapeHtml(name)}" 
						 data-phone="${escapeHtml(phone)}"
						 data-assigned-value="${escapeHtml(assignedVal)}"
						 onclick="handleConvClick(this)">
						<div class="conversation-avatar">${initial}</div>
						<div class="conversation-info">
							<div class="d-flex justify-content-between align-items-center">
								<h6 class="conversation-name text-truncate">${escapeHtml(name)}</h6>
								<span class="conversation-time">${time}</span>
							</div>
							<div class="d-flex justify-content-between align-items-center mt-1 gap-1">
								<div class="conversation-last-message">
									${c.last_message_direction === 'outbound' ? renderStatusTick(c.last_message_status) + ' ' : ''}
									${c.last_message_has_media ? '<i class="bi bi-paperclip"></i> Anexo' : escapeHtml(c.last_message_body || '')}
								</div>
								${assignBadge}
							</div>
						</div>
						${unread}
					</div>
				`;
			}).join('');

			list.scrollTop = prevListScroll;
		}

		function handleConvClick(el) {
			const userId = el.getAttribute('data-user-id') ? parseInt(el.getAttribute('data-user-id')) : null;
			const remoteJid = el.getAttribute('data-remote-jid') || null;
			const name = el.getAttribute('data-name') || '';
			const phone = el.getAttribute('data-phone') || '';
			const assignedValue = el.getAttribute('data-assigned-value') || '';
			selectConversation(userId, remoteJid, name, phone, assignedValue);
		}

		let globalSeverinoConfig = { mode: 'test', auto_reply: false };

		async function loadGlobalSeverinoConfig() {
			try {
				const res = await fetch('/admin/chat-evolution/api/severino-config');
				const data = await res.json();
				if (data.success && data.config) {
					globalSeverinoConfig = data.config;
					renderGlobalSeverinoUI();
				}
			} catch (e) {
				console.warn('Erro ao carregar config global Severino:', e);
			}
		}

		function renderGlobalSeverinoUI() {
			const sel = document.getElementById('globalSeverinoMode');
			const btn = document.getElementById('btnGlobalAutoReply');
			if (sel && globalSeverinoConfig.mode) {
				sel.value = globalSeverinoConfig.mode;
			}
			if (btn) {
				if (globalSeverinoConfig.auto_reply) {
					btn.style.background = '#dcfce7';
					btn.style.color = '#14532d';
					btn.style.borderColor = '#86efac';
					btn.innerText = '🤖 Auto-IA: ON';
				} else {
					btn.style.background = '#fef3c7';
					btn.style.color = '#78350f';
					btn.style.borderColor = '#fcd34d';
					btn.innerText = '⏸️ Auto-IA: OFF';
				}
			}
		}

		async function saveGlobalSeverinoConfig(overridePatch = {}) {
			const sel = document.getElementById('globalSeverinoMode');
			const payload = {
				mode: overridePatch.mode !== undefined ? overridePatch.mode : (sel ? sel.value : globalSeverinoConfig.mode),
				auto_reply: overridePatch.auto_reply !== undefined ? overridePatch.auto_reply : globalSeverinoConfig.auto_reply,
			};
			try {
				const res = await fetch('/admin/chat-evolution/api/severino-config', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
					body: JSON.stringify(payload)
				});
				const data = await res.json();
				if (data.success && data.config) {
					globalSeverinoConfig = data.config;
					renderGlobalSeverinoUI();
				}
			} catch (e) {
				alert('Erro ao salvar configuração do Severino.');
			}
		}

		function toggleGlobalAutoReply() {
			const nextState = !globalSeverinoConfig.auto_reply;
			saveGlobalSeverinoConfig({ auto_reply: nextState });
		}

		function toggleSimulatorBar() {
			const simArea = document.getElementById('simulatorArea');
			if (!simArea) return;
			simArea.style.display = (simArea.style.display === 'none' || simArea.style.display === '') ? 'block' : 'none';
		}

		let lastMessagesRenderSignature = '';

		// Seleciona conversa
		async function selectConversation(userId, remoteJid, name, phone, assignedValue = '') {
			activeConv = { userId, remoteJid, name, phone, assignedValue };
			lastMessagesRenderSignature = '';
			document.getElementById('currentUserName').innerText = name;
			document.getElementById('currentUserPhone').innerText = phone;
			document.getElementById('chatInputArea').style.display = 'flex';
			const btnSim = document.getElementById('btnToggleSimulator');
			if (btnSim) btnSim.style.display = 'inline-block';
			const simArea = document.getElementById('simulatorArea');
			if (simArea) simArea.style.display = 'none';
			updateAssignmentUI(assignedValue);

			// No celular, esconde a lista lateral para exibir a conversa aberta
			const drawer = document.getElementById('chatDrawer');
			if (drawer) drawer.classList.add('hidden-mobile');

			loadMessages(true);
			loadConversations();
		}

		// Carrega mensagens (preserva a posição do scroll quando o usuário sobe para ler mensagens antigas)
		async function loadMessages(forceScrollToBottom = false) {
			if (!activeConv.remoteJid && !activeConv.userId) return;
			const reqConvKey = `${activeConv.remoteJid || ''}|${activeConv.userId || ''}`;
			try {
				const params = new URLSearchParams();
				if (activeConv.remoteJid) params.append('remote_jid', activeConv.remoteJid);
				if (activeConv.userId) params.append('user_id', activeConv.userId);

				const res = await fetch(`/admin/chat-evolution/api/messages?${params.toString()}`);
				const msgs = await res.json();

				// Ignora resposta atrasada caso o usuário já tenha trocado de conversa
				const currentConvKey = `${activeConv.remoteJid || ''}|${activeConv.userId || ''}`;
				if (reqConvKey !== currentConvKey) return;

				const container = document.getElementById('messagesContainer');

				if (!msgs || !msgs.length) {
					const emptySig = `${reqConvKey}:empty`;
					if (lastMessagesRenderSignature !== emptySig) {
						lastMessagesRenderSignature = emptySig;
						container.innerHTML = '<div class="text-center my-auto text-muted"><p>Nenhuma mensagem nesta conversa ainda.</p></div>';
					}
					return;
				}

				const renderSig = reqConvKey + ':' + msgs.map(m => `${m.id}_${m.status || ''}`).join(',');
				if (renderSig === lastMessagesRenderSignature && !forceScrollToBottom) {
					// Nada mudou: não re-renderiza nem mexe no scroll
					return;
				}

				const prevScrollTop = container.scrollTop;
				const distanceFromBottom = container.scrollHeight - container.scrollTop - container.clientHeight;
				const wasNearBottom = distanceFromBottom <= 80;

				lastMessagesRenderSignature = renderSig;

				container.innerHTML = msgs.map(m => {
					const isSeverino = m.from === 'severino';
					const senderBadge = m.direction === 'outbound'
						? (isSeverino
							? `<div style="font-size:0.7rem; font-weight:700; color:#3730a3; margin-bottom:3px;">🤖 Severino (IA)</div>`
							: `<div style="font-size:0.7rem; font-weight:700; color:#065f46; margin-bottom:3px;">👩‍💻 ${escapeHtml(m.admin_name || 'Atendente')}</div>`)
						: '';

					let failNotice = '';
					if (m.status === 'failed') {
						const rawErr = String(m.failed_reason || '');
						const friendlyErr = rawErr.includes('"exists":false')
							? 'Número não encontrado no WhatsApp (confira DDD/DDI)'
							: (rawErr ? rawErr.substring(0, 120) : 'Falha no envio');
						failNotice = `<div style="margin-top:6px; padding:3px 7px; border-radius:6px; background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; font-size:0.7rem; font-weight:700;">⚠️ Não enviada: ${escapeHtml(friendlyErr)}</div>`;
					}

					return `
						<div class="message-row">
							<div class="message-bubble ${m.direction === 'outbound' ? 'sent-message' : 'received-message'}">
								${senderBadge}
								${m.media_url ? `<div class="mb-2"><a href="${m.media_url}" target="_blank" class="btn btn-sm bg-white text-dark border border-secondary-subtle fw-semibold shadow-sm"><i class="bi ${String(m.media_url).toLowerCase().endsWith('.pdf') ? 'bi-file-earmark-pdf-fill text-danger' : 'bi-file-earmark'} me-1"></i>${String(m.media_url).toLowerCase().endsWith('.pdf') ? 'Abrir PDF' : 'Ver anexo'}</a></div>` : ''}
								<div class="message-content">${m.body ? escapeHtml(m.body) : ''}</div>
								${failNotice}
								<div class="timestamp">
									<span>${new Date(m.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</span>
									${m.direction === 'outbound' ? renderStatusTick(m.status) : ''}
								</div>
							</div>
						</div>
					`;
				}).join('');

				if (forceScrollToBottom || wasNearBottom) {
					container.scrollTop = container.scrollHeight;
				} else {
					container.scrollTop = prevScrollTop;
				}
			} catch (e) {
				console.error(e);
			}
		}

		// Envia mensagem
		async function sendMessage() {
			const input = document.getElementById('messageInput');
			const fileInput = document.getElementById('fileInput');
			const btnSend = document.getElementById('btnSendMessage');
			const body = input.value.trim();

			if (!body && !fileInput.files.length) return;
			if (!activeConv.remoteJid && !activeConv.userId) return;

			btnSend.disabled = true;
			btnSend.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

			const formData = new FormData();
			if (activeConv.userId) formData.append('user_id', activeConv.userId);
			if (activeConv.remoteJid) formData.append('remote_jid', activeConv.remoteJid);
			formData.append('body', body);
			if (fileInput.files.length) {
				formData.append('file', fileInput.files[0]);
			}

			try {
				const res = await fetch('/admin/chat-evolution/api/send', {
					method: 'POST',
					headers: { 'X-CSRF-TOKEN': csrfToken },
					body: formData
				});
				const data = await res.json();
				if (data.success) {
					input.value = '';
					fileInput.value = '';
					loadMessages(true);
					loadConversations();
				} else {
					alert('Erro ao enviar: ' + (data.error || 'Verifique a conexão do WhatsApp'));
				}
			} catch (e) {
				console.error(e);
				alert('Falha na comunicação com o servidor.');
			} finally {
				btnSend.disabled = false;
				btnSend.innerHTML = '<i class="bi bi-send-fill fs-5"></i>';
			}
		}

		function escapeHtml(text) {
			if (!text) return '';
			return text.replace(/[&<>"']/g, function(m) {
				return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
			});
		}

		document.getElementById('searchContact').addEventListener('input', function() {
			renderConversations();
		});

		document.getElementById('btnBackToList')?.addEventListener('click', () => {
			const drawer = document.getElementById('chatDrawer');
			if (drawer) drawer.classList.remove('hidden-mobile');
		});

		document.getElementById('btnSendMessage').addEventListener('click', sendMessage);
		document.getElementById('messageInput').addEventListener('keydown', (e) => {
			if (e.key === 'Enter' && !e.shiftKey) {
				e.preventDefault();
				sendMessage();
			}
		});

		document.getElementById('btnLogoutInstance').addEventListener('click', async () => {
			if (confirm('Deseja realmente desconectar o WhatsApp desta sessão?')) {
				await fetch('/admin/chat-evolution/api/logout', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken } });
				checkStatus();
			}
		});

		document.getElementById('btnRefreshQr').addEventListener('click', checkStatus);

		// Inicialização
		checkStatus();
		loadEvolutionAdmins();
		loadGlobalSeverinoConfig();
		loadConversations();
		setInterval(checkStatus, 15000);
		setInterval(loadGlobalSeverinoConfig, 15000);
		setInterval(() => {
			loadConversations();
			if (activeConv.remoteJid || activeConv.userId) loadMessages();
		}, 4000);
	</script>
</body>
</html>

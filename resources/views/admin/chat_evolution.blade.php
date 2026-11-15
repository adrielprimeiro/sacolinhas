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
		body { background-color:#f0f0f0; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin:0; height:100vh; }
		.chat-layout { width:100%; height:100vh; display:flex; background:#fff; position:relative; overflow:hidden; }

		.chat-drawer {
			width: 360px;
			background: #f8f9fa;
			border-right: 1px solid #ddd;
			display: flex;
			flex-direction: column;
			z-index: 1;
		}

		@media (max-width: 768px) {
			.chat-drawer { width: 100vw; position: fixed; top: 0; left: 0; height: 100vh; z-index: 1000; }
			.chat-drawer.hidden-mobile { display: none !important; }
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
			<div class="sidebar-header d-flex justify-content-between align-items-center">
				<div class="d-flex align-items-center gap-2">
					<i class="bi bi-whatsapp fs-5"></i>
					<span>Chat Evolution</span>
				</div>
				<button class="btn btn-sm btn-outline-light" id="btnQrModal" data-bs-toggle="modal" data-bs-target="#qrModal" title="Conexão WhatsApp">
					<i class="bi bi-qr-code"></i> Conectar
				</button>
			</div>

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
				<div id="connectionStatusIndicator">
					<span class="badge bg-secondary">Checando...</span>
				</div>
			</div>

			<div class="chat-messages" id="messagesContainer">
				<div class="text-center my-auto text-muted">
					<i class="bi bi-chat-dots fs-1 mb-2"></i>
					<p>Selecione um cliente ao lado para ver o histórico e responder sem restrição de 24h.</p>
				</div>
			</div>

			<div class="chat-input" id="chatInputArea" style="display:none;">
				<input type="file" id="fileInput" style="display:none;">
				<button class="btn btn-light rounded-circle" type="button" onclick="document.getElementById('fileInput').click()" title="Anexar arquivo">
					<i class="bi bi-paperclip fs-5 text-secondary"></i>
				</button>
				<textarea class="form-control" id="messageInput" rows="1" placeholder="Digite uma mensagem..."></textarea>
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
						<p class="mb-2">Abra o WhatsApp no celular $\rightarrow$ <b>Aparelhos Conectados</b> $\rightarrow$ <b>Conectar um aparelho</b>:</p>
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
		let activeUserId = null;
		let pollInterval = null;

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

					if (data.qr && data.qr.base64) {
						qrImg.src = data.qr.base64;
					}
				}
			} catch (e) {
				console.error(e);
			}
		}

		// Carrega conversas
		async function loadConversations() {
			try {
				const res = await fetch('/admin/chat-evolution/api/conversations');
				const convs = await res.json();
				const list = document.getElementById('conversationList');

				if (!convs.length) {
					list.innerHTML = '<div class="text-center p-4 text-muted">Nenhuma conversa encontrada.</div>';
					return;
				}

				list.innerHTML = convs.map(c => `
					<div class="conversation-item ${activeUserId === c.user_id ? 'active' : ''}" onclick="selectConversation(${c.user_id}, '${c.user_name.replace(/'/g, "\\'")}', '${c.user_whatsapp || ''}')">
						<div class="conversation-avatar">${c.user_name.charAt(0).toUpperCase()}</div>
						<div class="conversation-info">
							<div class="d-flex justify-content-between align-items-center">
								<h6 class="conversation-name">${c.user_name}</h6>
								<span class="conversation-time">${c.last_message_at ? new Date(c.last_message_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}) : ''}</span>
							</div>
							<div class="conversation-last-message">
								${c.last_message_direction === 'outbound' ? '<i class="bi bi-check2 text-muted"></i> ' : ''}
								${c.last_message_has_media ? '<i class="bi bi-paperclip"></i> Anexo' : (c.last_message_body || '')}
							</div>
						</div>
						${c.unread_count > 0 ? `<span class="unread-badge">${c.unread_count}</span>` : ''}
					</div>
				`).join('');
			} catch (e) {
				console.error(e);
			}
		}

		// Seleciona conversa
		async function selectConversation(userId, userName, userPhone) {
			activeUserId = userId;
			document.getElementById('currentUserName').innerText = userName;
			document.getElementById('currentUserPhone').innerText = userPhone;
			document.getElementById('chatInputArea').style.display = 'flex';

			loadMessages();
			loadConversations();
		}

		// Carrega mensagens
		async function loadMessages() {
			if (!activeUserId) return;
			try {
				const res = await fetch(`/admin/chat-evolution/api/messages/${activeUserId}`);
				const msgs = await res.json();
				const container = document.getElementById('messagesContainer');

				container.innerHTML = msgs.map(m => `
					<div class="message-row">
						<div class="message-bubble ${m.direction === 'outbound' ? 'sent-message' : 'received-message'}">
							${m.media_url ? `<div class="mb-2"><a href="${m.media_url}" target="_blank" class="btn btn-sm btn-outline-dark"><i class="bi bi-file-earmark"></i> Ver anexo</a></div>` : ''}
							<div class="message-content">${m.body ? escapeHtml(m.body) : ''}</div>
							<div class="timestamp">
								<span>${new Date(m.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</span>
								${m.direction === 'outbound' ? `<i class="bi ${m.status === 'read' ? 'bi-check2-all text-primary' : (m.status === 'failed' ? 'bi-exclamation-circle text-danger' : 'bi-check2')}"></i>` : ''}
							</div>
						</div>
					</div>
				`).join('');

				container.scrollTop = container.scrollHeight;
			} catch (e) {
				console.error(e);
			}
		}

		// Envia mensagem
		async function sendMessage() {
			const input = document.getElementById('messageInput');
			const fileInput = document.getElementById('fileInput');
			const body = input.value.trim();

			if (!body && !fileInput.files.length) return;
			if (!activeUserId) return;

			const formData = new FormData();
			formData.append('user_id', activeUserId);
			formData.append('body', body);
			if (fileInput.files.length) {
				formData.append('file', fileInput.files[0]);
			}

			input.value = '';
			fileInput.value = '';

			try {
				const res = await fetch('/admin/chat-evolution/api/send', {
					method: 'POST',
					headers: { 'X-CSRF-TOKEN': csrfToken },
					body: formData
				});
				const data = await res.json();
				if (data.success) {
					loadMessages();
					loadConversations();
				} else {
					alert('Erro ao enviar: ' + (data.error || 'Verifique a conexão do WhatsApp'));
				}
			} catch (e) {
				console.error(e);
			}
		}

		function escapeHtml(text) {
			return text.replace(/[&<>"']/g, function(m) {
				return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
			});
		}

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
		loadConversations();
		setInterval(checkStatus, 15000);
		setInterval(() => {
			loadConversations();
			if (activeUserId) loadMessages();
		}, 5000);
	</script>
</body>
</html>

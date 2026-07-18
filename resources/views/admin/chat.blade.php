<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Chat WhatsApp</title>
	<link rel="icon" href="{{ asset('favicon.ico') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
	<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

	<meta name="csrf-token" content="{{ csrf_token() }}">

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

	<style>
		body { background-color:#f0f0f0; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin:0; height:100vh; }

		.chat-layout { width:100%; height:100vh; display:flex; background:#fff; position:relative; overflow:hidden; }

		/* ===== Conversas: drawer + overlay ===== */
		.hidden { display:none !important; }

		.chat-overlay{
			position: fixed;
			inset: 0;
			background: rgba(0,0,0,.40);
		}

		.chat-drawer{
			position: fixed;
			top: 0;
			left: 0;
			height: 100vh;
			width: 360px;
			background: #f8f9fa;
			box-shadow: 0 10px 30px rgba(0,0,0,.25);
			display:flex;
			flex-direction:column;
			transition: transform 0.3s ease;
		}

		@media (max-width: 768px){
			.chat-drawer{ width: 92vw; max-width: 360px; }
		}

		/* No desktop, o drawer fica fixo na esquerda como uma sidebar */
		@media (min-width: 769px){
			.chat-drawer {
				position: static !important;
				box-shadow: none !important;
				border-right: 1px solid #ddd;
				display: flex !important;
				z-index: 1 !important;
			}
			.chat-overlay {
				display: none !important;
			}
			#chatMenuBtn {
				display: none !important;
			}
			#chatMenuCloseBtn {
				display: none !important;
			}
		}

		.sidebar-header { padding:14px 16px; background:#075e54; color:#fff; font-weight:600; }
		.conversation-list { flex:1; overflow-y:auto; }

		/* ===== Barra de busca no drawer ===== */
		.conversation-search{
			padding:10px 12px;
			border-bottom:1px solid #e6e6e6;
			background:#f8f9fa;
		}
		.conversation-search .form-control{
			border-radius:999px;
			padding-left:38px;
		}
		.conversation-search .search-icon{
			position:absolute;
			left:14px;
			top:50%;
			transform:translateY(-50%);
			color:#777;
			font-size:1rem;
			pointer-events:none;
		}
		.conversation-search-wrap{
			position:relative;
		}

		.conversation-item { position:relative; display:flex; gap:12px; align-items:center; padding:12px 14px; border-bottom:1px solid #eee; cursor:pointer; }
		.conversation-item:hover { background:#eef2f5; }
		.conversation-item.active { background:#dcf8c6; }
		.conversation-avatar { width:44px; height:44px; border-radius:50%; background:#075e54; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; flex:0 0 auto; }
		.conversation-info { flex:1; min-width:0; }
		.conversation-name { font-weight:700; margin:0; }
		.conversation-last-message { font-size:.85em; color:#666; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
		.conversation-time { font-size:.75em; color:#999; flex:0 0 auto; }
		.unread-badge { position:absolute; top:8px; right:12px; background-color:#25d366; color:white; font-size:.7rem; font-weight:bold; min-width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center; padding:0 4px; box-shadow:0 1px 3px rgba(0,0,0,0.3); }

		/* ===== Chat principal (inalterado) ===== */
		.chat-main { flex:1; display:flex; flex-direction:column; min-width:0; }
		.chat-header { background:#075e54; color:#fff; padding:14px 18px; border-bottom:1px solid #ddd; display:flex; align-items:center; justify-content:space-between; gap:10px; }
		.chat-messages { flex:1; padding:18px; overflow-y:auto; background:#f0f0f0; display:flex; flex-direction:column; gap:10px; }
		.message-row { display:flex; width:100%; }
		.message-bubble { max-width:70%; padding:10px 12px; border-radius:8px; box-shadow:0 1px 2px rgba(0,0,0,.08); font-size:.95em; background:#fff; }
		.sent-message { background:#dcf8c6; margin-left:auto; }
		.received-message { background:#fff; margin-right:auto; }
		.message-content { white-space:pre-wrap; }
		.timestamp { font-size:.75em; color:#777; text-align:right; display:flex; justify-content:flex-end; gap:6px; align-items:center; margin-top:6px; }

		.attachment-box { margin-top:8px; }
		.attachment-link { display:inline-flex; gap:8px; align-items:center; text-decoration:none; }
		.attachment-link .bi { font-size:1.05rem; }

		.chat-input {
			padding: 12px 14px;
			border-top: 1px solid #ddd;
			background: #f0f0f0;
			display: flex;
			align-items: flex-end;
			gap: 10px;
			flex-wrap: nowrap;
		}

		.chat-input-left {
			display: flex;
			flex-direction: column;
			gap: 6px;
			flex: 1 1 auto;
			min-width: 0;
		}

		.file-pill {
			display: none;
			align-items: center;
			gap: 8px;
			background: #fff;
			border: 1px solid #ddd;
			border-radius: 999px;
			padding: 6px 10px;
			font-size: .85rem;
			width: fit-content;
			max-width: 100%;
		}

		.file-pill .name {
			max-width: 520px;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}

		.file-pill button {
			border: none;
			background: transparent;
			color: #b00020;
			font-weight: 700;
		}

		#messageInput {
			resize: none;
			border-radius: 18px;
			overflow-y: auto;
			line-height: 1.3;
			width: 100%;
		}

		.icon-btn {
			border: none;
			background: #ffffff;
			width: 44px;
			height: 44px;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			box-shadow: 0 1px 2px rgba(0,0,0,.08);
			flex: 0 0 auto;
		}

		.icon-btn:hover { background:#f5f5f5; }

		#sendButton { background:#075e54; color:#fff; }
		#sendButton:hover { filter:brightness(0.95); }

		.notice { padding:10px 12px; background:#fff3cd; border:1px solid #ffeeba; color:#856404; border-radius:8px; margin:12px 18px 0 18px; display:none; }
		.small-muted { font-size:.8rem; color:#e6e6e6; }

		.window-timer {
			font-size: 0.75em;
			font-weight: 600;
			color: #aeffc5;
			margin-top: 4px;
		}

		.assignment-controls {
			display: flex;
			gap: 10px;
			align-items: center;
		}

		.assignment-controls select {
			font-size: 0.8rem;
			padding: 2px 5px;
		}

		.assigned-info {
			font-size: 0.75em;
			color: #007bff;
			font-weight: 600;
		}
	</style>
</head>

<body>
<div class="chat-layout">
	<!-- Overlay + Drawer (conversas) -->
	<div id="chatOverlay" class="chat-overlay hidden" style="z-index: 9998;"></div>

	<aside id="chatDrawer" class="chat-drawer hidden" style="z-index: 9999;">
		<div class="sidebar-header d-flex align-items-center justify-content-between">
			<span>Conversas</span>
			<button id="chatMenuCloseBtn" type="button" class="icon-btn" title="Fechar" aria-label="Fechar menu">
				<i class="bi bi-x-lg"></i>
			</button>
		</div>

		<!-- Barra de busca com Autocomplete -->
		<div class="conversation-search" style="overflow: visible;">
			<div class="conversation-search-wrap" style="position: relative; z-index: 50;" x-data="{ 
				open: false, 
				search: '',
				users: [],
				loading: false,
				focusedIndex: -1,
				timeout: null,
				handleInput() {
					this.focusedIndex = -1;
					this.open = true;
					if (this.search.length < 2) {
						this.users = [];
						return;
					}
					this.loading = true;
					clearTimeout(this.timeout);
					this.timeout = setTimeout(() => {
						this.fetchUsers();
					}, 300);
					
					// Disparar o filtro local também
					if(typeof renderConversations === 'function') renderConversations();
				},
				fetchUsers() {
					fetch('/api/users/search?q=' + encodeURIComponent(this.search))
						.then(res => res.json())
						.then(res => {
							if (res.success) {
								this.users = res.data.map(u => {
									let extraInfo = [u.email, u.instagram, u.tiktok, u.apelido].filter(Boolean).join(' • ');
									return { id: String(u.id), name: u.name, info: extraInfo };
								});
								this.focusedIndex = this.users.length > 0 ? 0 : -1;
							} else {
								this.users = [];
								this.focusedIndex = -1;
							}
							this.loading = false;
						})
						.catch(() => {
							this.users = [];
							this.focusedIndex = -1;
							this.loading = false;
						});
				},
				selectUser(user) {
					this.search = '';
					this.open = false;
					// Chamar função do chat para abrir conversa
					if(typeof selectConversation === 'function') {
						// Tentar achar na lista
						const conv = conversations.find(c => String(c.user_id) === String(user.id));
						if (!conv) {
							// Adiciona mock para renderizar corretamente o titulo se nao houver
							conversations.push({
								user_id: user.id,
								user_name: user.name,
								last_message_body: '...'
							});
						}
						selectConversation(user.id);
					}
				},
				onKeyDown(e) {
					if (e.key === 'Enter') {
						if (this.open && this.focusedIndex >= 0 && this.focusedIndex < this.users.length) {
							e.preventDefault();
							this.selectUser(this.users[this.focusedIndex]);
						}
						return;
					}
					
					if (!this.open || this.users.length === 0) return;
					
					if (e.key === 'ArrowDown') {
						e.preventDefault();
						this.focusedIndex = this.focusedIndex < this.users.length - 1 ? this.focusedIndex + 1 : 0;
					} else if (e.key === 'ArrowUp') {
						e.preventDefault();
						this.focusedIndex = this.focusedIndex > 0 ? this.focusedIndex - 1 : this.users.length - 1;
					}
				}
			}">
				<i class="bi bi-search search-icon" style="position: absolute; left: 24px; top: 12px; color: #aaa;"></i>
				<input type="text" id="conversationSearch" class="form-control form-control-sm" style="padding-left: 32px;"
					   placeholder="Buscar ou iniciar conversa..." autocomplete="off"
					   x-model="search"
					   @input="handleInput()"
					   @click="open = true"
					   @click.away="open = false"
					   @keydown.escape="open = false"
					   @keydown="onKeyDown($event)">
				
				<!-- Dropdown de Sugestões -->
				<div x-show="open && search.length >= 2" 
					 x-transition
					 class="dropdown-menu show"
					 style="position: absolute; top: 100%; left: 16px; right: 16px; max-height: 250px; overflow-y: auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border-radius: 8px; border: 1px solid #eee; padding: 0;">
					
					<template x-for="(user, index) in users" :key="user.id">
						<div @click="selectUser(user)"
							 class="dropdown-item"
							 style="padding: 10px 12px; cursor: pointer; border-bottom: 1px solid #f5f5f5;"
							 :style="focusedIndex === index ? 'background-color: #f8f9fa;' : ''"
							 @mouseover="focusedIndex = index">
							<div style="font-weight: 600; font-size: 0.9rem; color: #333;" x-text="user.name"></div>
							<div style="font-size: 0.75rem; color: #888; margin-top: 2px;" x-text="user.info" x-show="user.info"></div>
						</div>
					</template>
					
					<div x-show="users.length === 0 && search.length >= 2 && !loading" style="padding: 12px; font-size: 0.85rem; color: #888; text-align: center;">
						Nenhum cliente encontrado.
					</div>
					<div x-show="loading" style="padding: 12px; font-size: 0.85rem; color: #888; text-align: center;">
						Buscando...
					</div>
				</div>
			</div>
		</div>

		<div class="conversation-list" id="conversationList"></div>
	</aside>

	<main class="chat-main">
		<div class="chat-header">
			<button id="chatMenuBtn" type="button" class="icon-btn" title="Conversas" aria-label="Abrir conversas">
				<i class="bi bi-list" style="font-size:1.35rem;"></i>
			</button>

			<h4 id="activeChatTitle" class="m-0" style="font-size:1.05rem;">Selecione uma conversa</h4>

			<div id="activeChatMeta" class="small-muted"></div>

			<div id="assignmentControls" class="assignment-controls" style="display:none;">
				<select id="adminSelector" class="form-select form-select-sm">
					<option value="">Ninguém</option>
				</select>
				<button id="assignButton" class="btn btn-sm btn-light">Atribuir</button>
			</div>
		</div>

		<div class="notice" id="sendErrorBox"></div>
		<div class="chat-messages" id="chatMessages"></div>

		<div class="chat-input" id="chatInput" style="display:none;">
			<input type="file" id="fileInput" style="display:none;" />

			<button type="button" class="icon-btn" id="attachButton" title="Anexar arquivo">
				<i class="bi bi-paperclip"></i>
			</button>

			<div class="chat-input-left">
				<div class="file-pill" id="filePill">
					<i class="bi bi-paperclip"></i>
					<span class="name" id="fileName">arquivo</span>
					<button type="button" id="removeFileBtn" title="Remover">×</button>
				</div>

				<textarea id="messageInput" class="form-control" rows="1" placeholder="Digite uma mensagem..."></textarea>
			</div>

			<button type="button" class="icon-btn" id="sendButton" title="Enviar">
				<i class="bi bi-send-fill"></i>
			</button>
		</div>
	</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
let activeUserId = null;
let conversations = [];
let messagePollingInterval = null;
let sendSetupDone = false;

let admins = [];
let timerInterval = null;
const authUser = @json(auth()->user());
const isMaster = authUser && authUser.is_admin == 1 && authUser.role === 'admin_master';

/* ===== Drawer ===== */
function setupChatDrawer(){
	const btn = document.getElementById('chatMenuBtn');
	const closeBtn = document.getElementById('chatMenuCloseBtn');
	const drawer = document.getElementById('chatDrawer');
	const overlay = document.getElementById('chatOverlay');

	function openDrawer(){
		if (!drawer || !overlay) return;
		drawer.classList.remove('hidden');
		overlay.classList.remove('hidden');

		// foca na busca ao abrir
		const search = document.getElementById('conversationSearch');
		if (search) setTimeout(() => search.focus(), 0);
	}

	function closeDrawer(){
		if (!drawer || !overlay) return;
		drawer.classList.add('hidden');
		overlay.classList.add('hidden');
	}

	if (btn) btn.addEventListener('click', openDrawer);
	if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
	if (overlay) overlay.addEventListener('click', closeDrawer);

	document.addEventListener('keydown', function(e){
		if (e.key === 'Escape') closeDrawer();
	});

	window.__closeChatDrawer = closeDrawer;
}

function setupConversationSearch(){
	const input = document.getElementById('conversationSearch');
	if (!input) return;

	input.addEventListener('input', function(){
		renderConversations(); // re-render filtrando
	});

	// Esc limpa a busca quando o drawer estiver aberto
	input.addEventListener('keydown', function(e){
		if (e.key === 'Escape') {
			input.value = '';
			renderConversations();
		}
	});
}

function getConversationSearchTerm(){
	const input = document.getElementById('conversationSearch');
	return (input ? String(input.value || '').trim().toLowerCase() : '');
}

async function initialize() {
	setupChatDrawer();
	setupConversationSearch();

	if (isMaster) {
		await loadAdmins();
	}
	await loadConversations();
	setupSendMessage();

	if (isMaster) {
		document.getElementById('assignButton').addEventListener('click', assignConversation);
	}
}

document.addEventListener('DOMContentLoaded', function() {
	initialize();
});

async function loadConversations() {
	try {
		const resp = await fetch('/admin/chat/api/conversations', { headers: { 'Accept': 'application/json' } });
		const data = await resp.json();
		conversations = Array.isArray(data) ? data : [];
		renderConversations();
	} catch (e) {
		console.error(e);
	}
}

async function loadAdmins() {
	try {
		const resp = await fetch('/admin/chat/api/admins');
		admins = await resp.json();
		const selector = document.getElementById('adminSelector');
		selector.innerHTML = '<option value="">Ninguém</option>';
		admins.forEach(admin => {
			selector.innerHTML += `<option value="${admin.id}">${admin.name} (${admin.role})</option>`;
		});
	} catch (e) {
		console.error('Erro ao carregar admins:', e);
	}
}

async function assignConversation() {
	if (!activeUserId) return;
	const assignedAdminId = document.getElementById('adminSelector').value;
	const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

	try {
		const resp = await fetch('/admin/chat/api/assign', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
			body: JSON.stringify({ user_id: activeUserId, assigned_admin_id: assignedAdminId || null })
		});
		const data = await resp.json();
		if (data.success) {
			await loadConversations();
		} else {
			alert(data.error || 'Erro ao atribuir.');
		}
	} catch (e) {
		console.error('Erro ao atribuir:', e);
	}
}

setInterval(() => {
	document.querySelectorAll('.window-countdown').forEach(el => {
		if (!el.dataset.expires || el.dataset.expires === 'null') {
			el.textContent = 'Fechada';
			return;
		}
		const now = new Date();
		const expiry = new Date(el.dataset.expires);
		const diff = expiry.getTime() - now.getTime();
		
		if (diff <= 0) {
			el.textContent = 'Fechada';
		} else {
			const hours = Math.floor(diff / (1000 * 60 * 60));
			const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
			const seconds = Math.floor((diff % (1000 * 60)) / 1000);
			el.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
		}
	});
}, 1000);

let lastConversationsJson = '';
function renderConversations() {
	const list = document.getElementById('conversationList');

	const term = getConversationSearchTerm();

	const filtered = term
		? conversations.filter(conv => {
			const name = String(conv.user_name || ('Usuário #' + conv.user_id)).toLowerCase();
			const whatsapp = String(conv.user_whatsapp || '').toLowerCase();
			const lastText = String(conv.last_message_body || '').toLowerCase();
			const assigned = String(conv.assigned_admin_name || '').toLowerCase();
			return name.includes(term) || whatsapp.includes(term) || lastText.includes(term) || assigned.includes(term);
		})
		: conversations;
	const currentJson = JSON.stringify({ filtered, activeUserId });
	if (currentJson === lastConversationsJson) return;
	lastConversationsJson = currentJson;

	list.innerHTML = '';

	filtered.forEach(conv => {
		const item = document.createElement('div');
		item.className = 'conversation-item' + (String(conv.user_id) === String(activeUserId) ? ' active' : '');
		item.onclick = () => selectConversation(conv.user_id);

		const avatar = document.createElement('div');
		avatar.className = 'conversation-avatar';
		avatar.textContent = (conv.user_name || '?').trim().slice(0,1).toUpperCase();

		const info = document.createElement('div');
		info.className = 'conversation-info';

		const name = document.createElement('p');
		name.className = 'conversation-name';
		name.textContent = conv.user_name || ('Usuário #' + conv.user_id);

		const lastMsg = document.createElement('div');
		lastMsg.className = 'conversation-last-message';

		const lastText = conv.last_message_body ? String(conv.last_message_body) : '';
		const lastAttachment = conv.last_message_has_media ? '📎 Anexo' : '';
		lastMsg.textContent = lastText || lastAttachment || '';

		info.appendChild(name);
		info.appendChild(lastMsg);

		if (conv.assigned_admin_name) {
			const assignedInfo = document.createElement('div');
			assignedInfo.className = 'assigned-info';
			assignedInfo.textContent = 'Atribuído a: ' + conv.assigned_admin_name;
			info.appendChild(assignedInfo);
		}

		const time = document.createElement('div');
		time.className = 'conversation-time window-countdown';
		time.style.fontWeight = 'bold';
		time.style.color = '#007bff';
		if (conv.window_expires_at) {
			time.dataset.expires = conv.window_expires_at;
		} else {
			time.dataset.expires = 'null';
		}

		item.appendChild(avatar);
		item.appendChild(info);
		item.appendChild(time);

		if (conv.unread_count && Number(conv.unread_count) > 0) {
			const badge = document.createElement('div');
			badge.className = 'unread-badge';
			badge.textContent = String(conv.unread_count);
			item.appendChild(badge);
		}

		list.appendChild(item);
	});
}

async function selectConversation(userId) {
	activeUserId = userId;

	const conv = conversations.find(c => String(c.user_id) === String(userId));
	document.getElementById('activeChatTitle').textContent = conv?.user_name ? conv.user_name : ('Usuário #' + userId);

	if (isMaster) {
		document.getElementById('assignmentControls').style.display = 'flex';
		document.getElementById('adminSelector').value = conv?.assigned_admin_id || '';
	}



	document.getElementById('activeChatMeta').textContent = conv?.user_whatsapp ? conv.user_whatsapp : '';
	document.getElementById('chatInput').style.display = 'flex';

	renderConversations();
	await loadMessages(userId, true);
	await markMessagesAsRead(userId);
	startPolling(userId);

	if (window.__closeChatDrawer) window.__closeChatDrawer();
}

function startPolling(userId) {
	if (messagePollingInterval) clearInterval(messagePollingInterval);

	messagePollingInterval = setInterval(async () => {
		if (!activeUserId) return;
		await loadConversations();
		await loadMessages(activeUserId, false);
	}, 2500);
}

async function loadMessages(userId, scrollToBottom = true) {
	try {
		const resp = await fetch(`/admin/chat/api/messages/${userId}`, { headers: { 'Accept': 'application/json' } });
		const messages = await resp.json();
		renderMessages(Array.isArray(messages) ? messages : [], scrollToBottom);
	} catch (e) {
		console.error(e);
	}
}

let lastMessagesJson = '';
function renderMessages(messages, scrollToBottom = true) {
	const currentJson = JSON.stringify({ messages, activeUserId });
	if (currentJson === lastMessagesJson) return;
	lastMessagesJson = currentJson;

	const box = document.getElementById('chatMessages');
	const wasAtBottom = box.scrollHeight - box.scrollTop <= box.clientHeight + 20;

	box.innerHTML = '';

	messages.forEach(msg => {
		const row = document.createElement('div');
		row.className = 'message-row';

		const bubble = document.createElement('div');
		const isOutbound = String(msg.direction) === 'outbound';
		bubble.className = 'message-bubble ' + (isOutbound ? 'sent-message' : 'received-message');

		const content = document.createElement('div');
		content.className = 'message-content';
		content.innerHTML = escapeHtml(msg.body || '');

		bubble.appendChild(content);

		if (msg.has_media && msg.download_url) {
			const attach = document.createElement('div');
			attach.className = 'attachment-box';

			const isImage = (msg.media_content_type && msg.media_content_type.startsWith('image/')) ||
			                (msg.media_url && /\.(jpg|jpeg|png|gif|webp)($|\?)/i.test(msg.media_url));

			if (isImage) {
				const link = document.createElement('a');
				link.href = '#';
				link.onclick = (e) => {
					e.preventDefault();
					openMediaViewer(msg.download_url, msg.media_content_type, 'anexo-' + msg.id);
				};

				const img = document.createElement('img');
				img.src = msg.download_url;
				img.alt = 'Anexo';
				img.className = 'img-thumbnail mt-2';
				img.style.maxHeight = '150px';
				img.style.maxWidth = '200px';
				img.style.cursor = 'pointer';
				img.style.objectFit = 'cover';

				link.appendChild(img);
				attach.appendChild(link);
			} else {
				const link = document.createElement('a');
				link.className = 'attachment-link';
				link.href = '#';
				link.onclick = (e) => {
					e.preventDefault();
					openMediaViewer(msg.download_url, msg.media_content_type, 'anexo-' + msg.id);
				};

				const icon = document.createElement('i');
				icon.className = 'bi bi-paperclip';

				const label = document.createElement('span');
				label.textContent = 'Baixar anexo';

				link.appendChild(icon);
				link.appendChild(label);
				attach.appendChild(link);
			}

			bubble.appendChild(attach);
		}

		const ts = document.createElement('div');
		ts.className = 'timestamp';
		ts.innerHTML = `<span>${msg.created_at ? formatTime(msg.created_at) : ''}</span>${isOutbound ? renderTicks(msg) : ''}`;

		bubble.appendChild(ts);

		row.appendChild(bubble);
		box.appendChild(row);
	});

	if (scrollToBottom && wasAtBottom) {
		box.scrollTop = box.scrollHeight;
	}
}

function renderTicks(message) {
	const status = String(message.status || '').toLowerCase();
	if (!status) return '';
	if (status === 'read') return `<span title="Lida">✓✓</span>`;
	if (status === 'delivered') return `<span title="Entregue">✓✓</span>`;
	if (status === 'sent' || status === 'queued') return `<span title="Enviada">✓</span>`;
	if (status === 'failed' || status === 'undelivered') return `<span title="Falhou">!</span>`;
	return `<span title="${escapeHtml(status)}">✓</span>`;
}

function setupSendMessage() {
	if (sendSetupDone) return;
	sendSetupDone = true;

	const input = document.getElementById('messageInput');
	const sendBtn = document.getElementById('sendButton');
	const attachBtn = document.getElementById('attachButton');
	const fileInput = document.getElementById('fileInput');
	const filePill = document.getElementById('filePill');
	const fileName = document.getElementById('fileName');
	const removeFileBtn = document.getElementById('removeFileBtn');

	const maxLines = 4;
	function autoResizeTextarea() {
		input.style.height = 'auto';
		const computed = window.getComputedStyle(input);
		const lineHeight = parseFloat(computed.lineHeight) || 18;
		const paddingTop = parseFloat(computed.paddingTop) || 0;
		const paddingBottom = parseFloat(computed.paddingBottom) || 0;
		const maxHeight = (lineHeight * maxLines) + paddingTop + paddingBottom;

		const newHeight = Math.min(input.scrollHeight, maxHeight);
		input.style.height = newHeight + 'px';
		input.style.overflowY = (input.scrollHeight > maxHeight) ? 'auto' : 'hidden';
	}

	input.addEventListener('input', autoResizeTextarea);
	input.addEventListener('paste', () => setTimeout(autoResizeTextarea, 0));

	attachBtn.addEventListener('click', () => fileInput.click());

	fileInput.addEventListener('change', () => {
		const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
		if (!file) {
			filePill.style.display = 'none';
			return;
		}
		fileName.textContent = file.name;
		filePill.style.display = 'inline-flex';
		autoResizeTextarea();
	});

	removeFileBtn.addEventListener('click', () => {
		fileInput.value = '';
		filePill.style.display = 'none';
	});

	input.addEventListener('keydown', async (e) => {
		if (e.key === 'Enter' && !e.shiftKey) {
			e.preventDefault();
			await doSend();
		}
	});

	sendBtn.addEventListener('click', async () => doSend());

	async function doSend() {
		hideSendError();
		if (!activeUserId) return;

		const body = input.value.trim();
		const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;

		if (!body && !file) return;

		const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
		const form = new FormData();
		form.append('user_id', String(activeUserId));
		form.append('body', body);
		if (file) form.append('file', file);

		sendBtn.disabled = true;
		attachBtn.disabled = true;

		try {
			const resp = await fetch('/admin/chat/api/send', {
				method: 'POST',
				headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
				body: form
			});

			const data = await resp.json().catch(() => ({}));

			if (!resp.ok || !data.success) {
				showSendError(data?.error || 'Erro ao enviar.');
				return;
			}

			input.value = '';
			fileInput.value = '';
			filePill.style.display = 'none';
			autoResizeTextarea();

			await loadMessages(activeUserId, true);
			await loadConversations();
		} catch (e) {
			console.error(e);
			showSendError('Falha de rede ao enviar.');
		} finally {
			sendBtn.disabled = false;
			attachBtn.disabled = false;
		}
	}
}

function showSendError(msg) {
	const box = document.getElementById('sendErrorBox');
	box.style.display = 'block';
	box.textContent = msg;
}

function hideSendError() {
	const box = document.getElementById('sendErrorBox');
	box.style.display = 'none';
	box.textContent = '';
}

function formatTime(isoString) {
	try {
		const d = new Date(isoString);
		return d.toLocaleString('pt-BR', { day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit' });
	} catch {
		return '';
	}
}

function escapeHtml(text) {
	return String(text)
		.replaceAll('&', '&amp;')
		.replaceAll('<', '&lt;')
		.replaceAll('>', '&gt;')
		.replaceAll('"', '&quot;')
		.replaceAll("'", '&#039;');
}

async function markMessagesAsRead(userId) {
	try {
		const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
		await fetch(`/admin/chat/api/mark-read/${userId}`, {
			method: 'POST',
			headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
		});
	} catch (e) {}
}

window.addEventListener('beforeunload', () => {
	if (messagePollingInterval) clearInterval(messagePollingInterval);
	if (timerInterval) clearInterval(timerInterval);
});

/* ===== MEDIA VIEWER LIGHTBOX ===== */
function openMediaViewer(url, mimeType, filename) {
	const modal = document.getElementById('mediaViewerModal');
	const content = document.getElementById('mediaViewerContent');
	const downloadBtn = document.getElementById('downloadMediaBtn');
	if (!modal || !content) return;

	content.innerHTML = ''; // Limpa anterior
	
	const type = String(mimeType || '').toLowerCase();
	
	if (downloadBtn) {
		downloadBtn.href = url;
		downloadBtn.setAttribute('download', filename || 'anexo');
	}
	
	if (type.startsWith('image/') || /\.(jpg|jpeg|png|gif|webp)($|\?)/i.test(url)) {
		const img = document.createElement('img');
		img.src = url;
		img.className = 'img-fluid';
		img.style.maxHeight = '85vh';
		img.style.maxWidth = '90%';
		img.style.objectFit = 'contain';
		content.appendChild(img);
	} else if (type === 'application/pdf' || /\.pdf($|\?)/i.test(url)) {
		const iframe = document.createElement('iframe');
		iframe.src = url;
		iframe.style.width = '90%';
		iframe.style.height = '85vh';
		iframe.style.border = 'none';
		iframe.style.borderRadius = '8px';
		iframe.style.background = '#fff';
		content.appendChild(iframe);
	} else if (type.startsWith('video/') || /\.(mp4|webm|ogg)($|\?)/i.test(url)) {
		const video = document.createElement('video');
		video.src = url;
		video.controls = true;
		video.style.maxHeight = '85vh';
		video.style.maxWidth = '90%';
		content.appendChild(video);
	} else if (type.startsWith('audio/') || /\.(mp3|wav|ogg)($|\?)/i.test(url)) {
		const audio = document.createElement('audio');
		audio.src = url;
		audio.controls = true;
		content.appendChild(audio);
	} else {
		window.open(url, '_blank');
		return;
	}

	modal.classList.remove('hidden');
	modal.style.display = 'flex';
}

function closeMediaViewer() {
	const modal = document.getElementById('mediaViewerModal');
	const content = document.getElementById('mediaViewerContent');
	if (modal) {
		modal.classList.add('hidden');
		modal.style.display = 'none';
	}
	if (content) content.innerHTML = '';
}

document.addEventListener('DOMContentLoaded', function() {
	const closeBtn = document.getElementById('closeMediaViewer');
	const modal = document.getElementById('mediaViewerModal');
	if (closeBtn) closeBtn.addEventListener('click', closeMediaViewer);
	if (modal) {
		modal.addEventListener('click', function(e) {
			if (e.target === this) {
				closeMediaViewer();
			}
		});
	}
	document.addEventListener('keydown', function(e){
		if (e.key === 'Escape') {
			closeMediaViewer();
		}
	});
});
</script>

<!-- Lightbox Modal para Anexos -->
<div id="mediaViewerModal" class="fixed inset-0 z-[10000] hidden flex items-center justify-center p-4 bg-black/85" style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: none; align-items: center; justify-content: center; z-index: 10000; padding: 20px;">
	<div class="position-relative w-100 h-100 d-flex flex-column align-items-center justify-content-center">
		<!-- Botão de Download -->
		<a id="downloadMediaBtn" href="#" target="_blank" class="btn btn-dark rounded-circle position-absolute" style="top: 10px; right: 64px; z-index: 10001; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;" title="Baixar arquivo">
			<i class="bi bi-download text-white"></i>
		</a>
		<!-- Botão de Fechar -->
		<button id="closeMediaViewer" type="button" class="btn btn-dark rounded-circle position-absolute" style="top: 10px; right: 10px; z-index: 10001; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
			<i class="bi bi-x-lg text-white"></i>
		</button>
		<!-- Conteúdo dinâmico -->
		<div id="mediaViewerContent" class="w-100 h-100 d-flex align-items-center justify-content-center" style="max-height: 90vh;"></div>
	</div>
</div>

</body>
</html>
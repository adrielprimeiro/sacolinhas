<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat WhatsApp Style</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color:#f0f0f0; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin:0; height:100vh; }
        .chat-layout { width:100%; height:100vh; display:flex; background:#fff; }
        .chat-sidebar { width:360px; border-right:1px solid #ddd; background:#f8f9fa; display:flex; flex-direction:column; }
        .sidebar-header { padding:14px 16px; background:#075e54; color:#fff; font-weight:600; }
        .conversation-list { flex:1; overflow-y:auto; }
        .conversation-item { display:flex; gap:12px; align-items:center; padding:12px 14px; border-bottom:1px solid #eee; cursor:pointer; position:relative; }
        .conversation-item:hover { background:#eef2f5; }
        .conversation-item.active { background:#dcf8c6; }
        .conversation-avatar { width:44px; height:44px; border-radius:50%; background:#075e54; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; }
        .conversation-info { flex:1; min-width:0; }
        .conversation-name { font-weight:700; margin:0; }
        .conversation-last-message { font-size:.85em; color:#666; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .conversation-time { font-size:.75em; color:#999; }
        .unread-badge { background:#ff0000; color:#fff; border-radius:50%; min-width:20px; height:20px; display:flex; align-items:center; justify-content:center; font-size:.75em; font-weight:700; position:absolute; right:14px; top:12px; border:2px solid #fff; }
        .unread-badge.large { min-width:24px; height:24px; font-size:.8em; }

        .chat-main { flex:1; display:flex; flex-direction:column; }
        .chat-header { background:#075e54; color:#fff; padding:14px 18px; border-bottom:1px solid #ddd; }
        .chat-messages { flex:1; padding:18px; overflow-y:auto; background:#f0f0f0; display:flex; flex-direction:column; gap:10px; }
        .message-row { display:flex; width:100%; position:relative; }
        .message-bubble { max-width:70%; padding:10px 12px; border-radius:8px; box-shadow:0 1px 2px rgba(0,0,0,.08); font-size:.95em; background:#fff; position:relative; }
        .sent-message { background:#dcf8c6; margin-left:auto; }
        .received-message { background:#fff; margin-right:auto; }
        .message-content { white-space:pre-wrap; }
        .timestamp { font-size:.75em; color:#777; text-align:right; display:flex; justify-content:flex-end; gap:4px; align-items:center; margin-top:4px; }
        .copy-button { opacity:0; position:absolute; top:5px; right:5px; background:#fff; border:none; border-radius:50%; width:24px; height:24px; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:opacity 0.2s; }
        .message-bubble:hover .copy-button { opacity:1; }
        .sent-message .copy-button { right:5px; }
        .received-message .copy-button { left:5px; }

        .chat-input { padding:12px 14px; border-top:1px solid #ddd; background:#f0f0f0; display:flex; gap:10px; align-items:flex-end; }
        #messageInput { resize:none; border-radius:18px; }
        #sendButton { border-radius:50%; width:44px; height:44px; display:flex; align-items:center; justify-content:center; background:#075e54; color:#fff; border:none; }

        .notice { padding:10px 12px; background:#fff3cd; border:1px solid #ffeeba; color:#856404; border-radius:8px; margin:12px 18px 0 18px; display:none; }
    </style>
</head>
<body>
    <div class="chat-layout">
        <div class="chat-sidebar">
            <div class="sidebar-header">Conversas</div>
            <div class="conversation-list" id="conversationList"></div>
        </div>

        <main class="chat-main">
            <div class="chat-header">
                <h4 id="activeChatTitle" class="m-0" style="font-size:1.05rem;">Selecione uma conversa</h4>
            </div>

            <div class="notice" id="sendErrorBox"></div>

            <div class="chat-messages" id="chatMessages"></div>

            <div class="chat-input" id="chatInput" style="display:none;">
                <textarea id="messageInput" class="form-control" rows="1" placeholder="Digite uma mensagem..."></textarea>
                <button type="button" id="sendButton"><i class="bi bi-send-fill"></i></button>
            </div>
        </main>
    </div>

    <script>
        // Dados de exemplo das conversas
        const conversations = [
            { id: 1, name: 'João Silva', avatar: 'J', lastMessage: 'Oi, tudo bem?', timestamp: Date.now() - 1000 * 60 * 5, unreadCount: 3, messages: [
                { text: 'Oi, tudo bem?', sender: 'received', timestamp: Date.now() - 1000 * 60 * 10 },
                { text: 'Oi João! Tudo ótimo, e você?', sender: 'sent', timestamp: Date.now() - 1000 * 60 * 9 },
                { text: 'Também estou bem, obrigado!', sender: 'received', timestamp: Date.now() - 1000 * 60 * 8 },
                { text: 'Que bom! O que você tem feito?', sender: 'sent', timestamp: Date.now() - 1000 * 60 * 7 },
                { text: 'Trabalhando bastante, e você?', sender: 'received', timestamp: Date.now() - 1000 * 60 * 6 },
                { text: 'Igual, mas vamos marcar algo?', sender: 'sent', timestamp: Date.now() - 1000 * 60 * 5 }
            ]},
            { id: 2, name: 'Maria Santos', avatar: 'M', lastMessage: 'Obrigada pelo café!', timestamp: Date.now() - 1000 * 60 * 30, unreadCount: 1, messages: [
                { text: 'Oi Maria, como foi o encontro?', sender: 'sent', timestamp: Date.now() - 1000 * 60 * 60 },
                { text: 'Foi ótimo! Obrigada pelo café!', sender: 'received', timestamp: Date.now() - 1000 * 60 * 30 }
            ]},
            { id: 3, name: 'Pedro Costa', avatar: 'P', lastMessage: 'Vamos no cinema hoje?', timestamp: Date.now() - 1000 * 60 * 60 * 2, unreadCount: 0, messages: [
                { text: 'Oi Pedro!', sender: 'sent', timestamp: Date.now() - 1000 * 60 * 60 * 24 },
                { text: 'Oi! Vamos no cinema hoje?', sender: 'received', timestamp: Date.now() - 1000 * 60 * 60 * 2 }
            ]},
            { id: 4, name: 'Ana Oliveira', avatar: 'A', lastMessage: 'Te vejo amanhã então', timestamp: Date.now() - 1000 * 60 * 60 * 24, unreadCount: 2, messages: [
                { text: 'Oi Ana, reunião amanhã?', sender: 'sent', timestamp: Date.now() - 1000 * 60 * 60 * 24 * 2 },
                { text: 'Sim, às 10h. Te vejo amanhã então', sender: 'received', timestamp: Date.now() - 1000 * 60 * 60 * 24 }
            ]},
            { id: 5, name: 'Carlos Mendes', avatar: 'C', lastMessage: 'Projeto aprovado!', timestamp: Date.now() - 1000 * 60 * 60 * 24 * 3, unreadCount: 0, messages: [
                { text: 'E aí Carlos, novidades?', sender: 'sent', timestamp: Date.now() - 1000 * 60 * 60 * 24 * 4 },
                { text: 'Sim! Projeto aprovado!', sender: 'received', timestamp: Date.now() - 1000 * 60 * 60 * 24 * 3 }
            ]}
        ];

        let activeConversation = null;

        // Função para formatar timestamp
        function formatTimestamp(timestamp) {
            const now = new Date();
            const messageDate = new Date(timestamp);
            const diffInDays = Math.floor((now - messageDate) / (1000 * 60 * 60 * 24));

            if (diffInDays === 0) return 'Hoje';
            if (diffInDays === 1) return 'Ontem';
            return messageDate.toLocaleDateString('pt-BR');
        }

        // Função para renderizar lista de conversas
        function renderConversations() {
            const conversationList = document.getElementById('conversationList');
            conversationList.innerHTML = '';

            // Ordena conversas: primeiro por mensagens não lidas (desc), depois por timestamp (desc)
            conversations.sort((a, b) => {
                if (a.unreadCount !== b.unreadCount) return b.unreadCount - a.unreadCount;
                return b.timestamp - a.timestamp;
            });

            conversations.forEach(conv => {
                const item = document.createElement('div');
                item.className = `conversation-item ${activeConversation && activeConversation.id === conv.id ? 'active' : ''}`;
                item.onclick = () => selectConversation(conv);

                item.innerHTML = `
                    <div class="conversation-avatar">${conv.avatar}</div>
                    <div class="conversation-info">
                        <div class="conversation-name">${conv.name}</div>
                        <div class="conversation-last-message">${conv.lastMessage}</div>
                    </div>
                    <div class="conversation-time">${formatTimestamp(conv.timestamp)}</div>
                    ${conv.unreadCount > 0 ? `<div class="unread-badge ${conv.unreadCount > 9 ? 'large' : ''}">${conv.unreadCount > 99 ? '99+' : conv.unreadCount}</div>` : ''}
                `;

                conversationList.appendChild(item);
            });
        }

        // Função para selecionar conversa
        function selectConversation(conv) {
            activeConversation = conv;
            document.getElementById('activeChatTitle').textContent = conv.name;
            document.getElementById('chatInput').style.display = 'flex';
            renderConversations();
            renderMessages();
            conv.unreadCount = 0; // Marca como lida
            renderConversations(); // Atualiza a lista
        }

        // Função para renderizar mensagens
        function renderMessages() {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.innerHTML = '';

            if (!activeConversation) return;

            activeConversation.messages.forEach(msg => {
                const messageRow = document.createElement('div');
                messageRow.className = 'message-row';

                const messageBubble = document.createElement('div');
                messageBubble.className = `message-bubble ${msg.sender === 'sent' ? 'sent-message' : 'received-message'}`;

                messageBubble.innerHTML = `
                    <div class="message-content">${msg.text}</div>
                    <div class="timestamp">
                        <span>${new Date(msg.timestamp).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}</span>
                        <button class="copy-button" onclick="copyMessage('${msg.text.replace(/'/g, "\'")}')" title="Copiar mensagem">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                `;

                messageRow.appendChild(messageBubble);
                chatMessages.appendChild(messageRow);
            });

            // Scroll para a última mensagem
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Função para copiar mensagem
        function copyMessage(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Feedback visual opcional
                console.log('Mensagem copiada!');
            }).catch(err => {
                console.error('Erro ao copiar:', err);
            });
        }

        // Função para enviar mensagem
        function sendMessage() {
            const input = document.getElementById('messageInput');
            const text = input.value.trim();
            if (!text || !activeConversation) return;

            const newMessage = {
                text: text,
                sender: 'sent',
                timestamp: Date.now()
            };

            activeConversation.messages.push(newMessage);
            activeConversation.lastMessage = text;
            activeConversation.timestamp = Date.now();

            input.value = '';
            renderMessages();
            renderConversations();
        }

        // Event listeners
        document.getElementById('sendButton').onclick = sendMessage;
        document.getElementById('messageInput').onkeydown = function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        };

        // Inicialização
        renderConversations();
    </script>
</body>
</html>
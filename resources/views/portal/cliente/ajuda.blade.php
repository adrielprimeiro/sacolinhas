@extends('layouts.portal-cliente')

@section('title', 'Assistente Virtual & Ajuda - Portal do Cliente')

@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl p-6 text-white shadow-lg flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center justify-center p-2 bg-white/20 rounded-lg backdrop-blur-md">
                    <i class="fas fa-robot text-xl"></i>
                </span>
                <h1 class="text-2xl font-bold">Assistente Virtual RAG</h1>
            </div>
            <p class="text-purple-100 text-sm">Tire suas dúvidas sobre sacolinhas, prazos, frete, pedidos e regras da loja em tempo real!</p>
        </div>
        <div class="hidden sm:block">
            <span class="px-3 py-1 bg-green-500/30 text-green-200 border border-green-400/40 rounded-full text-xs font-semibold flex items-center gap-1.5 backdrop-blur-md">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span> IA Conectada
            </span>
        </div>
    </div>

    <!-- Interface Principal do Chat -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-[600px] overflow-hidden" id="chatApp">
        <!-- ÁREA DE MENSAGENS -->
        <div id="messagesContainer" class="flex-1 p-4 md:p-6 overflow-y-auto space-y-4 bg-slate-50/50">
            <!-- Mensagem de Boas-vindas Inicial -->
            <div class="flex gap-3 max-w-[85%]">
                <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center flex-shrink-0 shadow-md">
                    <i class="fas fa-robot text-sm"></i>
                </div>
                <div class="bg-white border border-gray-200/80 rounded-2xl rounded-tl-none p-4 shadow-sm text-gray-800 text-sm leading-relaxed space-y-2">
                    <p class="font-semibold text-purple-700">Olá! Eu sou a Mel, sua assistente virtual 🤖✨</p>
                    <p>Posso te ajudar a consultar a situação das suas <strong>sacolinhas</strong>, conferir <strong>pedidos</strong>, tirar dúvidas sobre <strong>frete, pagamentos e prazos</strong>!</p>
                    <p class="text-xs text-gray-500">Como posso te ajudar hoje?</p>
                </div>
            </div>
        </div>

        <!-- SUGESTÕES RÁPIDAS -->
        <div class="px-4 py-2 bg-white border-t border-gray-100 overflow-x-auto flex gap-2 no-scrollbar">
            <button onclick="sendQuickQuery('Qual o prazo da minha sacolinha?')" class="text-xs px-3 py-1.5 rounded-full bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 font-medium whitespace-nowrap transition-colors flex items-center gap-1">
                <i class="fas fa-shopping-bag text-purple-500"></i> Qual o prazo da minha sacolinha?
            </button>
            <button onclick="sendQuickQuery('Quais são as formas de pagamento aceitas?')" class="text-xs px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 font-medium whitespace-nowrap transition-colors flex items-center gap-1">
                <i class="fas fa-credit-card text-indigo-500"></i> Formas de Pagamento
            </button>
            <button onclick="sendQuickQuery('Como funciona o envio e cálculo de frete?')" class="text-xs px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 font-medium whitespace-nowrap transition-colors flex items-center gap-1">
                <i class="fas fa-truck text-blue-500"></i> Como funciona o frete?
            </button>
            <button onclick="sendQuickQuery('Como funciona a devolução de produtos?')" class="text-xs px-3 py-1.5 rounded-full bg-pink-50 text-pink-700 hover:bg-pink-100 border border-pink-200 font-medium whitespace-nowrap transition-colors flex items-center gap-1">
                <i class="fas fa-undo text-pink-500"></i> Trocas e Devoluções
            </button>
        </div>

        <!-- CAMPO DE ENTRADA -->
        <div class="p-3 md:p-4 bg-white border-t border-gray-100">
            <form id="chatForm" onsubmit="handleChatSubmit(event)" class="flex gap-2 items-center">
                <input 
                    type="text" 
                    id="userInput"
                    placeholder="Digite sua dúvida aqui (ex: meus pedidos, prazos, frete...)"
                    class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all text-gray-800"
                    autocomplete="off"
                    required
                />
                <button 
                    type="submit" 
                    id="sendBtn"
                    class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-medium px-5 py-3 rounded-xl hover:opacity-95 transition-all shadow-md shadow-purple-500/20 flex items-center gap-2 text-sm disabled:opacity-50"
                >
                    <span>Enviar</span>
                    <i class="fas fa-paper-plane text-xs"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    let sessionId = localStorage.getItem('ai_chat_session_id') || '';

    document.addEventListener('DOMContentLoaded', () => {
        if (sessionId) {
            loadHistory();
        }
    });

    async function loadHistory() {
        try {
            const res = await fetch(`/api/chat-ia/history?session_id=${sessionId}`);
            const data = await res.json();
            if (data.success && data.messages.length > 0) {
                const container = document.getElementById('messagesContainer');
                container.innerHTML = ''; // limpa inicial
                data.messages.forEach(msg => {
                    appendMessage(msg.role, msg.message, msg.sources);
                });
                scrollToBottom();
            }
        } catch (e) {
            console.error('Erro ao carregar histórico', e);
        }
    }

    function sendQuickQuery(query) {
        document.getElementById('userInput').value = query;
        handleChatSubmit(new Event('submit'));
    }

    async function handleChatSubmit(e) {
        e.preventDefault();
        const input = document.getElementById('userInput');
        const sendBtn = document.getElementById('sendBtn');
        const userMsg = input.value.trim();

        if (!userMsg) return;

        // Limpa input e desabilita botão
        input.value = '';
        input.disabled = true;
        sendBtn.disabled = true;

        // Adiciona mensagem do usuário na tela
        appendMessage('user', userMsg);
        scrollToBottom();

        // Adiciona indicador de digitação da IA
        const typingId = appendTypingIndicator();
        scrollToBottom();

        try {
            const response = await fetch('/api/chat-ia/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    message: userMsg,
                    session_id: sessionId
                })
            });

            const data = await response.json();
            removeTypingIndicator(typingId);

            if (data.success) {
                if (data.session_id) {
                    sessionId = data.session_id;
                    localStorage.setItem('ai_chat_session_id', sessionId);
                }
                appendMessage('assistant', data.answer, data.sources);
            } else {
                appendMessage('assistant', 'Desculpe, ocorreu um erro ao processar sua pergunta. Tente novamente.');
            }
        } catch (err) {
            removeTypingIndicator(typingId);
            appendMessage('assistant', 'Não foi possível conectar ao servidor de IA no momento.');
        } finally {
            input.disabled = false;
            sendBtn.disabled = false;
            input.focus();
            scrollToBottom();
        }
    }

    function appendMessage(role, text, sources = []) {
        const container = document.getElementById('messagesContainer');
        const isUser = role === 'user';

        const wrapper = document.createElement('div');
        wrapper.className = `flex gap-3 max-w-[85%] ${isUser ? 'ml-auto flex-row-reverse' : ''}`;

        let avatarHtml = isUser 
            ? `<div class="w-9 h-9 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center flex-shrink-0 text-sm font-bold"><i class="fas fa-user"></i></div>`
            : `<div class="w-9 h-9 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center flex-shrink-0 shadow-md text-sm"><i class="fas fa-robot"></i></div>`;

        let sourcesBadgeHtml = '';
        if (!isUser && sources && sources.length > 0) {
            sourcesBadgeHtml = `<div class="mt-2 pt-2 border-t border-gray-100 text-[11px] text-gray-500 flex flex-wrap gap-1 items-center">
                <span class="font-semibold text-purple-600"><i class="fas fa-book-open text-[10px]"></i> Fontes consultadas:</span>
                ${sources.map(s => `<span class="bg-purple-50 text-purple-700 px-2 py-0.5 rounded border border-purple-100">${s}</span>`).join('')}
            </div>`;
        }

        // Formatação simples de quebras de linha e negritos
        let formattedText = text
            .replace(/\n/g, '<br>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

        wrapper.innerHTML = `
            ${avatarHtml}
            <div class="${isUser ? 'bg-purple-600 text-white rounded-2xl rounded-tr-none' : 'bg-white border border-gray-200/80 text-gray-800 rounded-2xl rounded-tl-none'} p-4 shadow-sm text-sm leading-relaxed">
                ${formattedText}
                ${sourcesBadgeHtml}
            </div>
        `;

        container.appendChild(wrapper);
    }

    function appendTypingIndicator() {
        const container = document.getElementById('messagesContainer');
        const id = 'typing_' + Date.now();
        const wrapper = document.createElement('div');
        wrapper.id = id;
        wrapper.className = 'flex gap-3 max-w-[85%]';
        wrapper.innerHTML = `
            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center flex-shrink-0 shadow-md text-sm">
                <i class="fas fa-robot"></i>
            </div>
            <div class="bg-white border border-gray-200/80 rounded-2xl rounded-tl-none p-4 shadow-sm flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-purple-500 animate-bounce"></span>
                <span class="w-2 h-2 rounded-full bg-purple-500 animate-bounce [animation-delay:0.2s]"></span>
                <span class="w-2 h-2 rounded-full bg-purple-500 animate-bounce [animation-delay:0.4s]"></span>
            </div>
        `;
        container.appendChild(wrapper);
        return id;
    }

    function removeTypingIndicator(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    function scrollToBottom() {
        const container = document.getElementById('messagesContainer');
        container.scrollTop = container.scrollHeight;
    }
</script>
@endsection

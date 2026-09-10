@extends('layouts.app')

@section('title', 'Severino AI')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="severinoChat()" x-init="init()">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-robot text-indigo-600 mr-3"></i> Severino AI
        </h2>
        <p class="text-gray-500 mt-1">Seu assistente administrativo integrado ao banco de dados.</p>
    </div>

    <div class="max-w-5xl mx-auto">
        <div class="bg-white rounded-xl shadow-md flex flex-col overflow-hidden border border-gray-200" style="height: 700px;">
            <!-- Chat Messages -->
            <div class="flex-1 overflow-y-auto bg-gray-50 p-6 space-y-6" id="chat-box">
                <template x-for="(msg, index) in messages" :key="index">
                    <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                        
                        <!-- Avatar Severino -->
                        <div x-show="msg.role === 'assistant'" class="flex-shrink-0 mr-3 mt-1">
                            <div class="bg-indigo-600 text-white rounded-full flex items-center justify-center shadow-sm w-10 h-10">
                                <i class="fas fa-robot"></i>
                            </div>
                        </div>
                        
                        <!-- Bubble -->
                        <div :class="msg.role === 'user' 
                                ? 'bg-indigo-600 text-white p-4 rounded-2xl rounded-tr-none shadow-sm max-w-[85%]' 
                                : 'bg-white text-gray-800 p-4 rounded-2xl rounded-tl-none shadow-sm border border-gray-200 max-w-[95%] w-fit overflow-x-auto'" 
                             style="word-wrap: break-word;">
                            <div class="prose prose-sm max-w-none" :class="msg.role === 'user' ? 'prose-invert' : ''" x-html="msg.html || formatMessage(msg.text)"></div>
                        </div>
                        
                        <!-- Avatar User -->
                        <div x-show="msg.role === 'user'" class="flex-shrink-0 ml-3 mt-1">
                            <div class="bg-gray-500 text-white rounded-full flex items-center justify-center shadow-sm w-10 h-10">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                    </div>
                </template>
                
                <div x-show="loading" class="flex justify-start">
                    <div class="flex-shrink-0 mr-3 mt-1">
                        <div class="bg-indigo-600 text-white rounded-full flex items-center justify-center shadow-sm w-10 h-10">
                            <i class="fas fa-robot"></i>
                        </div>
                    </div>
                    <div class="bg-white text-gray-500 p-4 rounded-2xl rounded-tl-none shadow-sm border border-gray-200 flex items-center">
                        <i class="fas fa-circle-notch fa-spin mr-3 text-indigo-500"></i> Consultando dados no sistema...
                    </div>
                </div>
            </div>

            <!-- Chat Input -->
            <div class="bg-white border-t border-gray-200 p-4">
                <form @submit.prevent="sendMessage" class="flex items-center gap-3">
                    <input type="text" id="severino-input" x-model="input" 
                           class="flex-1 rounded-full border border-gray-300 bg-gray-50 px-6 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:bg-gray-100 disabled:text-gray-400" 
                           placeholder="Pergunte ao Severino..." 
                           :disabled="loading" autofocus>
                    
                    <button type="submit" 
                            class="bg-indigo-600 text-white rounded-full w-12 h-12 flex items-center justify-center shadow-md hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed" 
                            :disabled="loading || input.trim() === ''">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
function severinoChat() {
    return {
        messages: [
            { 
                role: 'assistant', 
                text: 'Olá, chefe! Sou o Severino. Você pode me perguntar coisas como:\n\n- "Busque a cliente Maria Silva"\n- "Qual o saldo da cliente ID 123?"\n- "Temos quantas peças em loja hoje?"',
                html: ''
            }
        ],
        input: '',
        loading: false,

        init() {
            this.messages.forEach(m => {
                if (!m.html) {
                    m.html = this.formatMessage(m.text);
                }
            });
        },

        formatMessage(text) {
            if (!text) return '';
            try {
                if (typeof marked !== 'undefined' && typeof marked.parse === 'function') {
                    return marked.parse(text);
                }
            } catch (e) {
                console.error("Erro ao renderizar markdown:", e);
            }
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML.replace(/\n/g, '<br>');
        },

        addMessage(role, text) {
            const html = this.formatMessage(text);
            this.messages.push({ role, text, html });
            this.scrollToBottom();
        },

        scrollToBottom() {
            setTimeout(() => {
                const box = document.getElementById('chat-box');
                if (box) {
                    box.scrollTop = box.scrollHeight;
                }
            }, 50);
        },

        async sendMessage() {
            if (this.input.trim() === '' || this.loading) return;

            const userText = this.input.trim();
            this.input = '';
            this.addMessage('user', userText);
            this.loading = true;

            const history = this.messages.slice(-10).map(m => ({ role: m.role, text: m.text }));

            // Timeout de segurança no cliente (60 segundos)
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 60000);

            try {
                const response = await fetch('{{ route("severino.ask") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ message: userText, history: history.slice(0, -1) }),
                    signal: controller.signal
                });

                clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error('Servidor retornou status ' + response.status);
                }

                const data = await response.json();

                if (data.answer) {
                    this.addMessage('assistant', data.answer);
                } else {
                    this.addMessage('assistant', 'Ops, deu um erro: ' + (data.error || 'Erro desconhecido'));
                }
            } catch (error) {
                clearTimeout(timeoutId);
                if (error.name === 'AbortError') {
                    this.addMessage('assistant', '⏱️ A consulta demorou mais que o esperado. Por favor, digite **continue** para eu retomar.');
                } else {
                    this.addMessage('assistant', '⚠️ Erro de conexão com o servidor. Por favor, tente novamente em instantes.');
                }
            } finally {
                this.loading = false;
                this.scrollToBottom();
                setTimeout(() => {
                    const inputEl = document.getElementById('severino-input');
                    if (inputEl) inputEl.focus();
                }, 100);
            }
        }
    }
}
</script>
<style>
/* Adjusts standard markdown tags inside the chat bubbles to look good */
.prose p:last-child { margin-bottom: 0; }
.prose ul { margin-bottom: 0.5em; padding-left: 1.5em; list-style-type: disc; }
.prose ol { margin-bottom: 0.5em; padding-left: 1.5em; list-style-type: decimal; }
.prose code { background-color: rgba(0,0,0,0.05); padding: 0.2em 0.4em; border-radius: 0.25rem; font-size: 0.875em; color: #db2777; }
.prose-invert code { background-color: rgba(255,255,255,0.2); color: #fff; }

/* Beautiful Markdown Tables */
.prose table {
    width: 100%;
    margin-top: 0.75rem;
    margin-bottom: 0.75rem;
    border-collapse: collapse;
    font-size: 0.875rem;
    text-align: left;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
}
.prose thead th {
    background-color: #f3f4f6;
    color: #374151;
    font-weight: 600;
    padding: 0.6rem 0.85rem;
    border-bottom: 2px solid #e5e7eb;
    border-right: 1px solid #e5e7eb;
    white-space: nowrap;
}
.prose thead th:last-child {
    border-right: none;
}
.prose tbody tr {
    border-bottom: 1px solid #e5e7eb;
    transition: background-color 0.15s;
}
.prose tbody tr:nth-child(even) {
    background-color: #fafafa;
}
.prose tbody tr:hover {
    background-color: #f3f4f6;
}
.prose tbody td {
    padding: 0.5rem 0.85rem;
    border-right: 1px solid #e5e7eb;
    color: #1f2937;
    white-space: nowrap;
}
.prose tbody td:last-child {
    border-right: none;
}
</style>
@endsection


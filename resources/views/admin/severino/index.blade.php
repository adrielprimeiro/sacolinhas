@extends('layouts.app')

@section('title', 'Severino AI')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="severinoChat()">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-robot text-indigo-600 mr-3"></i> Severino AI
        </h2>
        <p class="text-gray-500 mt-1">Seu assistente administrativo integrado ao banco de dados.</p>
    </div>

    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-md flex flex-col overflow-hidden border border-gray-200" style="height: 650px;">
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
                                ? 'bg-indigo-600 text-white p-4 rounded-2xl rounded-tr-none shadow-sm max-w-[80%]' 
                                : 'bg-white text-gray-800 p-4 rounded-2xl rounded-tl-none shadow-sm border border-gray-200 max-w-[80%]'" 
                             style="word-wrap: break-word;">
                            <div class="prose prose-sm max-w-none" :class="msg.role === 'user' ? 'prose-invert' : ''" x-html="formatMessage(msg.text)"></div>
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
                    <input type="text" x-model="input" 
                           class="flex-1 rounded-full border border-gray-300 bg-gray-50 px-6 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" 
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
            { role: 'assistant', text: 'Olá, chefe! Sou o Severino. Você pode me perguntar coisas como:\n\n- "Busque a cliente Maria Silva"\n- "Qual o saldo da cliente ID 123?"\n- "Temos quantas peças em loja hoje?"' }
        ],
        input: '',
        loading: false,

        formatMessage(text) {
            if(!text) return '';
            return marked.parse(text);
        },

        scrollToBottom() {
            setTimeout(() => {
                const box = document.getElementById('chat-box');
                box.scrollTop = box.scrollHeight;
            }, 100);
        },

        async sendMessage() {
            if (this.input.trim() === '') return;

            const userText = this.input;
            this.messages.push({ role: 'user', text: userText });
            this.input = '';
            this.loading = true;
            this.scrollToBottom();

            const history = this.messages.slice(-10).map(m => ({ role: m.role, text: m.text }));

            try {
                const response = await fetch('{{ route("severino.ask") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ message: userText, history: history.slice(0, -1) })
                });

                const data = await response.json();

                if (data.answer) {
                    this.messages.push({ role: 'assistant', text: data.answer });
                } else {
                    this.messages.push({ role: 'assistant', text: 'Ops, deu um erro: ' + (data.error || 'Erro desconhecido') });
                }
            } catch (error) {
                this.messages.push({ role: 'assistant', text: 'Erro de conexão com o servidor.' });
            } finally {
                this.loading = false;
                this.scrollToBottom();
            }
        }
    }
}
</script>
<style>
/* Adjusts standard markdown tags inside the chat bubbles to look good */
.prose p:last-child { margin-bottom: 0; }
.prose ul { margin-bottom: 0; padding-left: 1.5em; list-style-type: disc; }
.prose code { background-color: rgba(0,0,0,0.05); padding: 0.2em 0.4em; border-radius: 0.25rem; font-size: 0.875em; color: #db2777; }
.prose-invert code { background-color: rgba(255,255,255,0.2); color: #fff; }
</style>
@endsection

@extends('layouts.app')

@section('title', 'Severino AI')

@section('content')
<div class="container-fluid" x-data="severinoChat()">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0"><i class="fas fa-robot text-primary me-2"></i>Severino AI</h2>
            <p class="text-muted">Seu assistente administrativo integrado ao banco de dados.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-md-8 mx-auto">
            <div class="card shadow-sm border-0 d-flex flex-column" style="height: 600px;">
                <!-- Chat Messages -->
                <div class="card-body overflow-auto bg-light p-4" id="chat-box" style="flex: 1;">
                    <template x-for="(msg, index) in messages" :key="index">
                        <div :class="msg.role === 'user' ? 'd-flex justify-content-end mb-3' : 'd-flex justify-content-start mb-3'">
                            <!-- Avatar Severino -->
                            <div x-show="msg.role === 'assistant'" class="me-2 mt-1">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 35px; height: 35px; font-size: 14px;">
                                    <i class="fas fa-robot"></i>
                                </div>
                            </div>
                            
                            <!-- Bubble -->
                            <div :class="msg.role === 'user' ? 'bg-primary text-white p-3 rounded-4 shadow-sm' : 'bg-white text-dark p-3 rounded-4 shadow-sm border'" style="max-width: 80%; border-bottom-right-radius: 4px;">
                                <div x-html="formatMessage(msg.text)" style="word-wrap: break-word; font-size: 14px;"></div>
                            </div>
                            
                            <!-- Avatar User -->
                            <div x-show="msg.role === 'user'" class="ms-2 mt-1">
                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 35px; height: 35px; font-size: 14px;">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                        </div>
                    </template>
                    
                    <div x-show="loading" class="d-flex justify-content-start mb-3">
                        <div class="me-2 mt-1">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 35px; height: 35px; font-size: 14px;">
                                <i class="fas fa-robot"></i>
                            </div>
                        </div>
                        <div class="bg-white text-dark p-3 rounded-4 shadow-sm border text-muted">
                            <i class="fas fa-circle-notch fa-spin me-2"></i> Consultando dados no sistema...
                        </div>
                    </div>
                </div>

                <!-- Chat Input -->
                <div class="card-footer bg-white border-top-0 p-3">
                    <form @submit.prevent="sendMessage" class="d-flex align-items-center">
                        <input type="text" x-model="input" class="form-control rounded-pill border-0 bg-light me-2 px-4 py-2" placeholder="Pergunte ao Severino..." :disabled="loading" autofocus>
                        <button type="submit" class="btn btn-primary rounded-circle shadow-sm" style="width: 45px; height: 45px;" :disabled="loading || input.trim() === ''">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Usando Marked.js para renderizar Markdown do Gemini de forma segura e rápida -->
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
            // Converte Markdown para HTML
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

            // Pega as últimas 10 mensagens para contexto
            const history = this.messages.slice(-10).map(m => ({ role: m.role, text: m.text }));

            try {
                const response = await fetch('{{ route("severino.ask") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ message: userText, history: history.slice(0, -1) }) // exclui a última que é o prompt atual
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
/* Ajustes para o Marked renderizar bonitinho dentro do bubble */
#chat-box p:last-child { margin-bottom: 0; }
#chat-box ul { margin-bottom: 0; padding-left: 20px; }
#chat-box code { background-color: #f8f9fa; padding: 2px 4px; border-radius: 4px; color: #d63384; }
</style>
@endsection

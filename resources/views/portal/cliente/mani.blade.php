@extends('layouts.portal-cliente')
@section('title', 'Mani - Consultora de Moda')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    
    <div class="bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl p-6 text-white shadow-lg flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold flex items-center gap-3">
                <i class="fas fa-magic"></i> Consultora Mani
            </h1>
            <p class="mt-2 text-pink-100 max-w-xl">
                Olá, eu sou a Mani! 💅 Sou a capivara mascote da Mania de Melissa e sua Consultora de Moda. Peça dicas de looks combinando com o que você já tem na sacolinha!
            </p>
        </div>
        <div class="hidden sm:block">
            <!-- Espaço para colocar a imagem gerada da Mani depois -->
            <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center text-4xl shadow-inner">
                🐹
            </div>
        </div>
    </div>

    <!-- Chat Box -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 flex flex-col h-[600px]" x-data="chatMani()">
        
        <!-- Messages Area -->
        <div id="mani-chat-messages" class="flex-1 p-4 overflow-y-auto space-y-4 bg-gray-50 rounded-t-xl">
            <!-- Initial Message -->
            <div class="flex items-end gap-2">
                <div class="w-8 h-8 rounded-full bg-pink-100 flex items-center justify-center text-xl flex-shrink-0">
                    🐹
                </div>
                <div class="bg-white px-4 py-3 rounded-2xl rounded-bl-none shadow-sm border border-gray-100 max-w-[80%] text-gray-700 text-sm">
                    Oie {{ $user->name }}! ✨ Acabei de dar uma espiadinha na sua sacolinha. Como posso te ajudar a montar um look incrível hoje?
                </div>
            </div>
            
            <!-- Dynamic Messages -->
            <template x-for="(msg, index) in messages" :key="index">
                <div class="flex items-end gap-2" :class="msg.role === 'user' ? 'flex-row-reverse' : ''">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xl flex-shrink-0"
                         :class="msg.role === 'user' ? 'bg-indigo-100' : 'bg-pink-100'">
                        <span x-text="msg.role === 'user' ? '👤' : '🐹'"></span>
                    </div>
                    <div class="px-4 py-3 rounded-2xl shadow-sm text-sm"
                         :class="msg.role === 'user' ? 'bg-indigo-600 text-white rounded-br-none' : 'bg-white border border-gray-100 text-gray-700 rounded-bl-none max-w-[80%]'"
                         x-html="formatMessage(msg.text)">
                    </div>
                </div>
            </template>
            
            <!-- Loading Indicator -->
            <div x-show="loading" class="flex items-end gap-2" x-cloak>
                <div class="w-8 h-8 rounded-full bg-pink-100 flex items-center justify-center text-xl">🐹</div>
                <div class="bg-white px-4 py-3 rounded-2xl rounded-bl-none shadow-sm border border-gray-100">
                    <div class="flex gap-1">
                        <span class="w-2 h-2 bg-pink-400 rounded-full animate-bounce"></span>
                        <span class="w-2 h-2 bg-pink-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></span>
                        <span class="w-2 h-2 bg-pink-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-4 border-t border-gray-100 bg-white rounded-b-xl">
            <form @submit.prevent="sendMessage" class="flex gap-2">
                <input type="text" x-model="input" placeholder="Digite sua mensagem para a Mani..." 
                       class="flex-1 rounded-full border-gray-300 focus:border-pink-500 focus:ring-pink-500 px-4"
                       :disabled="loading">
                <button type="submit" class="bg-pink-600 text-white px-6 rounded-full hover:bg-pink-700 transition font-bold"
                        :disabled="loading || input.trim() === ''">
                    Enviar <i class="fas fa-paper-plane ml-1"></i>
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.x/dist/cdn.min.js"></script>
<script>
    function formatText(text) {
        if (!text) return '';
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/\n/g, '<br>');
        return text;
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('chatMani', () => ({
            input: '',
            loading: false,
            messages: [],
            
            formatMessage(text) {
                return formatText(text);
            },

            async sendMessage() {
                if(this.input.trim() === '') return;
                
                const userText = this.input;
                this.messages.push({ role: 'user', text: userText });
                this.input = '';
                this.loading = true;
                this.scrollToBottom();

                try {
                    const response = await fetch('/api/chat-ia/send', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ 
                            message: userText,
                            mani_mode: true 
                        })
                    });

                    const data = await response.json();
                    
                    if(data.answer) {
                        this.messages.push({ role: 'assistant', text: data.answer });
                    } else {
                        this.messages.push({ role: 'assistant', text: 'Ops, tive um probleminha para pensar agora. Tente de novo!' });
                    }
                } catch (e) {
                    this.messages.push({ role: 'assistant', text: 'Ops, minha internet capivara caiu! Tente novamente.' });
                }

                this.loading = false;
                this.scrollToBottom();
            },

            scrollToBottom() {
                setTimeout(() => {
                    const container = document.getElementById('mani-chat-messages');
                    container.scrollTop = container.scrollHeight;
                }, 100);
            }
        }));
    });
</script>
@endpush
@endsection

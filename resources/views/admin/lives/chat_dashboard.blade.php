@extends('layouts.app')

@section('title', 'Painel de Captura de Live')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-comments text-indigo-600"></i>
                <span>Painel de Captura de Live</span>
            </h1>
            <p class="text-gray-500 mt-1">Gerencie a fila de pedidos, identifique códigos de produtos e integre o chat do Instagram/TikTok.</p>
        </div>
        
        <!-- Seleção de Live -->
        <div class="flex items-center gap-3 bg-white p-3 rounded-xl shadow-sm border border-gray-200">
            <label for="live-select" class="text-sm font-semibold text-gray-600">Live Ativa:</label>
            <form action="{{ route('admin.live-chat.dashboard') }}" method="GET" class="flex gap-2">
                <select name="live_id" id="live-select" onchange="this.form.submit()" class="text-sm rounded-lg border border-gray-300 bg-gray-50 p-2 text-gray-900 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Selecione uma Live...</option>
                    @foreach($lives as $l)
                        <option value="{{ $l->id }}" {{ ($activeLive && $activeLive->id === $l->id) ? 'selected' : '' }}>
                            #{{ $l->id }} - {{ $l->tipo_live_formatado }} ({{ $l->data->format('d/m/Y') }}) {{ $l->ativo ? '[ATIVA]' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    @if(!$activeLive)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-xl shadow-sm mb-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 text-yellow-500 mr-3">
                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-yellow-800">Nenhuma Live Selecionada ou Ativa</h3>
                    <p class="text-sm text-yellow-700 mt-1">Selecione uma live existente no canto superior direito para começar a capturar e visualizar os dados do chat.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Dashboard Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- COLUNA 1: FILA DE CÓDIGOS SOLICITADOS (LARGURA 5) - OCULTO POR ENQUANTO -->
        <div class="hidden lg:col-span-5 flex flex-col bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden" style="height: 75vh;">
            <div class="bg-indigo-600 px-5 py-4 flex items-center justify-between text-white">
                <div class="flex items-center gap-2">
                    <i class="fas fa-list-ol"></i>
                    <h2 class="font-bold text-lg">Fila de Códigos (Ordem de Chegada)</h2>
                </div>
                <span id="code-queue-count" class="bg-indigo-800 text-xs px-2.5 py-1 rounded-full font-bold">0 itens</span>
            </div>
            
            <div id="code-requests-container" class="flex-1 p-4 overflow-y-auto space-y-4 bg-gray-50">
                @if(!$activeLive)
                    <div class="flex flex-col items-center justify-center h-full text-gray-400">
                        <i class="fas fa-exclamation-circle text-4xl mb-2"></i>
                        <p class="text-sm text-center">Selecione uma live no topo da página para ver a fila de códigos.</p>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-full text-gray-400">
                        <i class="fas fa-box-open text-4xl mb-2"></i>
                        <p class="text-sm">Aguardando códigos detectados no chat...</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- COLUNA 1: CHAT EM TEMPO REAL (ESQUERDA - LARGURA 4) -->
        <div class="lg:col-span-4 flex flex-col bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden" style="height: 78vh;">
            <div class="bg-gray-800 px-4 py-3.5 flex items-center justify-between text-white">
                <div class="flex items-center gap-2">
                    <i class="fas fa-comment-alt text-indigo-400"></i>
                    <h2 class="font-bold text-sm">Chat da Transmissão</h2>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5 bg-gray-900/60 px-2 py-1 rounded-lg">
                        <span class="inline-block w-2 h-2 bg-green-500 rounded-full animate-ping" id="chat-ping-dot"></span>
                        <span class="text-[11px] text-gray-300 font-medium" id="chat-status-text">Capturando</span>
                    </div>
                </div>
            </div>
            
            <div id="chat-messages-container" class="flex-1 p-3 overflow-y-auto space-y-2.5 bg-gray-900 text-gray-100 font-sans">
                @if(!$activeLive)
                    <div class="flex flex-col items-center justify-center h-full text-gray-500">
                        <i class="fas fa-video-slash text-3xl mb-2"></i>
                        <p class="text-xs text-center">Selecione uma live para ativar o chat.</p>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-full text-gray-500">
                        <i class="fas fa-plug text-3xl mb-2"></i>
                        <p class="text-xs text-center">Aguardando mensagens do chat...</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- COLUNA 2: TRANSCRIÇÃO DE ÁUDIO / VOZ DA LIVE (MEIO - LARGURA 4) -->
        <div class="lg:col-span-4 flex flex-col rounded-2xl shadow-md border border-gray-200 overflow-hidden" style="height: 78vh; background-color: #0f172a;">
            <!-- Header da Coluna com Cores Vivas e Alto Contraste -->
            <div class="px-4 py-3.5 flex items-center justify-between text-white shadow-sm" style="background: linear-gradient(135deg, #065f46 0%, #047857 100%); border-bottom: 1px solid #059669;">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white" style="background-color: rgba(255, 255, 255, 0.2);">
                        <i class="fas fa-microphone-alt text-base text-white"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-sm leading-tight text-white" style="color: #ffffff;">Áudio & Voz da Live</h2>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span id="audio-pulse-dot" class="w-2 h-2 rounded-full inline-block" style="background-color: #9ca3af;"></span>
                            <span id="audio-status-label" class="text-[11px] font-medium" style="color: #d1fae5;">Microfone Desligado</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btn-toggle-audio-rec" onclick="toggleAudioRecording()" class="text-xs font-bold px-3.5 py-1.5 rounded-xl shadow transition duration-150 flex items-center gap-1.5 cursor-pointer active:scale-95" style="background-color: #10b981; color: #ffffff; border: 1px solid #34d399;">
                        <i class="fas fa-play" id="btn-audio-icon" style="color: #ffffff;"></i>
                        <span id="btn-audio-text" style="color: #ffffff;">Gravar</span>
                    </button>
                    <button type="button" onclick="clearAudioTranscripts()" title="Limpar Transcrição" class="p-1.5 rounded-lg transition text-white hover:bg-emerald-700 cursor-pointer" style="background-color: rgba(255,255,255,0.15); color: #ffffff;">
                        <i class="fas fa-trash-alt text-xs" style="color: #ffffff;"></i>
                    </button>
                </div>
            </div>
            
            <!-- Live Interim / Speech Feed -->
            <div class="flex-1 flex flex-col p-3 overflow-hidden" style="background-color: #0f172a; color: #f8fafc;">
                <!-- Preview fala em tempo real (Interim) -->
                <div id="audio-interim-box" class="p-3 mb-2.5 rounded-xl shrink-0 min-h-[52px] flex items-center gap-2.5 transition-all shadow-inner" style="background-color: #1e293b; border: 1px solid #334155;">
                    <div class="w-2.5 h-2.5 rounded-full shrink-0 hidden" id="audio-speaking-indicator" style="background-color: #10b981;"></div>
                    <div class="flex-1 overflow-hidden">
                        <p id="audio-interim-text" class="text-xs font-mono truncate" style="color: #94a3b8; font-style: italic;">Clique em "Gravar" e fale no microfone...</p>
                    </div>
                </div>

                <!-- Histórico de Frases Transcritas (Apenas Matches de Venda) -->
                <div id="audio-transcripts-stream" class="flex-1 overflow-y-auto space-y-2 pr-1">
                    <div id="audio-empty-placeholder" class="flex flex-col items-center justify-center h-full py-10" style="color: #64748b;">
                        <i class="fas fa-shopping-bag text-3xl mb-2" style="color: #475569;"></i>
                        <p class="text-xs text-center font-medium" style="color: #94a3b8;">Aguardando vendas faladas na live...</p>
                        <p class="text-[11px] mt-1.5 font-semibold text-center" style="color: #34d399;">Fale: <em>"Saiu para [Nome]"</em>, <em>"Foi para [Nome]"</em> ou <em>"Foi pra [Nome]"</em></p>
                    </div>
                </div>
            </div>

            <!-- Footer Informativo / Dica -->
            <div class="px-3.5 py-2.5 text-[11px] flex items-center justify-between shadow-inner" style="background-color: #020617; border-top: 1px solid #1e293b; color: #94a3b8;">
                <span class="flex items-center gap-1.5 font-medium" style="color: #34d399;">
                    <i class="fas fa-check-double text-xs"></i> Filtro: <em>Apenas Vendas Detectadas</em>
                </span>
                <span id="audio-sentences-count" class="text-[10px] font-mono" style="color: #64748b;">0 vendas</span>
            </div>
        </div>

        <!-- COLUNA 3: INTEGRAÇÃO / PARTICIPANTES (DIREITA - LARGURA 4) -->
        <div class="lg:col-span-4 flex flex-col bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden" style="height: 78vh;">
            <!-- Tabs -->
            <div class="flex border-b border-gray-200 bg-gray-50">
                <button id="tab-btn-bookmarklet" onclick="switchTab('bookmarklet')" class="flex-1 py-3 px-3 text-center font-semibold text-xs border-b-2 border-indigo-600 text-indigo-600">
                    Conectar Lives
                </button>
                <button id="tab-btn-online" onclick="switchTab('online')" class="flex-1 py-3 px-3 text-center font-semibold text-xs border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Pessoas Online
                </button>
            </div>

            <!-- Tab content container -->
            <div class="flex-1 p-4 overflow-y-auto">
                <!-- Tab: Conectar Lives (Gravação via Social Stream) -->
                <div id="tab-content-bookmarklet" class="space-y-4">
                    <!-- O bloco do Social Stream Ninja foi removido para deixar só o necessário -->

                    <!-- 1.5. Extensão Oficial (Alternativa 100% Silenciosa) -->
                    <div class="bg-blue-50 rounded-xl p-4 border border-blue-200 shadow-sm mt-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-blue-900 text-sm flex items-center gap-1.5">
                                <i class="fas fa-puzzle-piece text-blue-600 text-base"></i>
                                Extensão Oficial Minha Mania (Alternativa)
                            </h3>
                            <span class="bg-blue-200 text-blue-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
                                <i class="fas fa-ghost"></i> 100% Invisível
                            </span>
                        </div>
                        <p class="text-xs text-blue-800 mt-2 leading-relaxed">
                            Se você <strong>não tiver</strong> o Social Stream Ninja, use nossa Extensão Oficial. Ela foi atualizada para rodar de forma <strong>completamente silenciosa</strong> no fundo. Nenhuma janela vai abrir no seu Instagram! Apenas instale e use os botões de Iniciar Gravação abaixo.
                        </p>
                        
                        <div class="mt-3 flex gap-2 items-center bg-white p-2 rounded-lg border border-blue-300 shadow-inner justify-between">
                            <span class="text-[11px] font-bold text-gray-700">Capturador Silencioso v1.4:</span>
                            <a href="/extensao-capturador-minhamania.zip" download class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-[10px] font-bold transition flex items-center gap-1 shadow">
                                <i class="fas fa-download"></i> Baixar Extensão (.ZIP)
                            </a>
                        </div>
                    </div>

                    <!-- 2. Controle de Gravação Instagram -->
                    <div class="bg-purple-50 rounded-xl p-4 border border-purple-200 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-purple-900 text-sm flex items-center gap-1.5">
                                <i class="fab fa-instagram text-purple-600 text-base"></i>
                                Gravação Instagram
                            </h3>
                            <span id="insta-badge" class="bg-purple-200 text-purple-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 transition-all">
                                <span id="insta-status-dot" class="hidden w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                <span id="insta-status-text">Inativo</span>
                            </span>
                        </div>
                        <p class="text-[11px] text-purple-800 mt-1.5 leading-relaxed">
                            Clique em Iniciar para que o servidor permita salvar os comentários do Instagram enviados pelo Social Stream.
                        </p>
                        <div class="mt-3 flex gap-2">
                            <button type="button" onclick="toggleInstagramCapture()" id="insta-toggle-btn" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 rounded-lg text-xs transition duration-150 shadow-sm flex items-center justify-center gap-1.5">
                                <i class="fas fa-play"></i> Iniciar Gravação Instagram
                            </button>
                        </div>
                    </div>

                    <!-- 3. Controle de Gravação TikTok -->
                    <div class="bg-pink-50 rounded-xl p-4 border border-pink-200 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-pink-900 text-sm flex items-center gap-1.5">
                                <i class="fab fa-tiktok text-pink-600 text-base"></i>
                                Gravação TikTok
                            </h3>
                            <span id="tiktok-backend-badge" class="bg-gray-200 text-gray-700 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 transition-all">
                                <span id="tiktok-status-dot" class="w-1.5 h-1.5 rounded-full bg-gray-500"></span>
                                <span id="tiktok-status-text">Inativo</span>
                            </span>
                        </div>
                        <p class="text-[11px] text-pink-800 mt-1.5 leading-relaxed">
                            Clique em Iniciar para que o servidor permita salvar os comentários do TikTok enviados pelo Social Stream.
                        </p>
                        <div class="mt-3 flex gap-2">
                            <button type="button" onclick="toggleTikTokBackend()" id="tiktok-toggle-btn" class="w-full bg-pink-600 hover:bg-pink-700 text-white font-bold py-2 rounded-lg text-xs transition duration-150 shadow-sm flex items-center justify-center gap-1.5">
                                <i class="fas fa-play"></i> Iniciar Gravação TikTok
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tab: Pessoas Online -->
                <div id="tab-content-online" class="hidden flex flex-col h-full">
                    <!-- Busca de Cliente Avulso -->
                    <div class="relative z-20 shrink-0 mb-4">
                        <div class="relative">
                            <input type="text" id="avulso-search-input" placeholder="Buscar cliente por nome, apelido, cel..." class="w-full p-3 pl-10 rounded-xl border-2 border-indigo-100 bg-indigo-50/30 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 text-sm font-semibold text-gray-700 placeholder-indigo-300 transition-all">
                            <i class="fas fa-search absolute left-3.5 top-3.5 text-indigo-400"></i>
                        </div>
                        <div id="avulso-search-results" class="absolute w-full mt-1 max-h-64 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-2xl hidden flex flex-col">
                        </div>
                    </div>

                    <div class="flex items-center justify-between mb-3 shrink-0">
                        <h3 class="font-bold text-gray-700 text-sm">Participantes na Transmissão</h3>
                        <span id="online-users-count" class="bg-gray-200 text-gray-700 text-xs px-2 py-0.5 rounded-full font-bold">0</span>
                    </div>
                    
                    <div id="online-users-list" class="space-y-2.5 flex-1 overflow-y-auto relative z-10 pb-2">
                        <!-- Gerado dinamicamente -->
                        <p class="text-xs text-gray-400 text-center py-6">Nenhum participante detectado ainda.</p>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- MODAL VINCULAR CLIENTE -->
<div id="link-user-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 backdrop-blur-sm z-50 flex items-center justify-center hidden" style="z-index: 99999;">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 max-w-md w-full p-6 mx-4 transform transition-all duration-300">
        <div class="flex justify-between items-start border-b border-gray-100 pb-3 mb-4">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-link text-indigo-600"></i>
                <span>Vincular Usuário da Live</span>
            </h3>
            <button onclick="closeLinkModal()" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        </div>
        
        <p class="text-xs text-gray-500 mb-4 leading-relaxed">
            Associe o username <strong id="modal-display-username" class="text-indigo-600">@usuario</strong> da plataforma <strong id="modal-display-platform" class="text-gray-800">plataforma</strong> a um cliente cadastrado no sistema para salvar os pedidos dele na sacolinha dele.
        </p>
        
        <input type="hidden" id="modal-input-username">
        <input type="hidden" id="modal-input-platform">

        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-700 mb-1">Buscar Cliente por Nome, Apelido ou Celular:</label>
            <input type="text" id="modal-search-input" onkeyup="searchClients(this.value)" placeholder="Digite para buscar..." class="w-full p-2.5 rounded-lg border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
        </div>

        <div id="modal-search-results" class="max-h-48 overflow-y-auto space-y-2 border border-gray-100 rounded-lg p-2 bg-gray-50">
            <!-- Resultados Ajax -->
            <p class="text-xs text-gray-400 text-center py-4">Comece a digitar para pesquisar clientes.</p>
        </div>
        
        <div class="mt-5 flex justify-end gap-3 border-t border-gray-100 pt-4">
            <button onclick="closeLinkModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-50">Cancelar</button>
        </div>
    </div>
</div>

<!-- MODAL LEITOR QR CODE PARA PESSOA ONLINE (TELA INTEIRA / FULLSCREEN) -->
<div id="online-qr-modal" class="fixed inset-0 bg-gray-900 z-50 flex flex-col hidden overflow-hidden" style="z-index: 99999;">
    <!-- Cabeçalho Fullscreen Elegante -->
    <div class="bg-gray-900 border-b border-gray-800 px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 shrink-0 shadow-2xl">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-100 border border-indigo-200 flex items-center justify-center shrink-0 shadow-inner">
                <i class="fas fa-qrcode text-indigo-400 text-2xl animate-pulse"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-lg sm:text-xl font-extrabold text-white tracking-wide">
                        Leitor de Etiqueta / QRCode
                    </h3>
                    <span class="bg-indigo-100 text-indigo-600 border border-indigo-200 px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider" style="font-size: 11px;">Tela Inteira</span>
                </div>
                <p class="text-xs sm:text-sm text-gray-300 mt-0.5">Adicionando itens na sacola de: <strong id="online-qr-client-name" class="text-indigo-400 font-extrabold text-sm sm:text-base bg-gray-800 px-2.5 py-0.5 rounded-md border border-gray-700">@usuario</strong></p>
            </div>
        </div>

        <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
            <button onclick="closeOnlineQrModal()" class="bg-red-600 hover:bg-red-500 text-white font-bold px-6 py-2.5 rounded-xl text-sm transition-all duration-200 flex items-center gap-2 shadow-lg hover:shadow-red-500/20 active:scale-95">
                <i class="fas fa-times text-base"></i> Concluir e Voltar
            </button>
        </div>
    </div>

    <!-- Corpo / Área da Câmera em Tela Inteira -->
    <div class="flex-1 flex flex-col lg:flex-row gap-6 p-4 sm:p-6 overflow-hidden mx-auto w-full" style="max-width: 1600px;">
        <!-- Container da Câmera (Ocupa a maior parte da tela inteira) -->
        <div class="flex-1 flex flex-col bg-black rounded-3xl overflow-hidden relative border-2 border-indigo-500 shadow-2xl lg:min-h-0" style="min-height: 45vh;">
            <div id="online-qr-reader" class="w-full h-full flex-1"></div>
            
            <div class="absolute bottom-4 left-0 right-0 flex justify-center pointer-events-none z-10">
                <div class="bg-gray-900 backdrop-blur-md px-5 py-2.5 rounded-full border border-gray-700 text-gray-200 text-xs sm:text-sm font-semibold flex items-center gap-2.5 shadow-xl">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                    <i class="fas fa-camera text-indigo-400"></i>
                    <span>Aponte a câmera para o QR Code ou Código de Barras da etiqueta</span>
                </div>
            </div>
        </div>

        <!-- Painel Lateral de Controles e Entrada Manual / Feedback (Largura fixa em telas grandes) -->
        <div class="w-full flex flex-col gap-4 shrink-0 overflow-y-auto" style="width: 100%; max-width: 420px;">
            <!-- Box de Entrada Manual / Leitor USB -->
            <div class="bg-gray-900 rounded-3xl p-6 border border-gray-800 shadow-xl flex flex-col gap-3">
                <h4 class="text-sm font-extrabold text-white flex items-center gap-2">
                    <i class="fas fa-keyboard text-indigo-400 text-base"></i>
                    <span>Bipador USB / Entrada Manual</span>
                </h4>
                <p class="text-xs text-gray-300 leading-relaxed">
                    Se estiver usando leitor USB de código de barras ou quiser digitar o SKU manualmente, use o campo abaixo:
                </p>
                <div class="flex gap-2 mt-1">
                    <input type="text" id="online-qr-manual-input" onkeydown="if(event.key==='Enter') handleOnlineQrScan(this.value)" placeholder="Ex: VEST-01 ou 12345..." class="flex-1 px-4 py-3.5 rounded-xl border border-gray-700 bg-gray-800 text-white placeholder-gray-400 text-sm font-bold focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none shadow-inner">
                    <button type="button" onclick="handleOnlineQrScan(document.getElementById('online-qr-manual-input').value)" class="bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold px-5 py-3.5 rounded-xl text-sm shadow-lg hover:shadow-indigo-500/25 transition-all duration-150 flex items-center justify-center gap-1.5 shrink-0 active:scale-95">
                        <i class="fas fa-plus"></i> Adicionar
                    </button>
                </div>
            </div>

            <!-- Feedback Visual em Tempo Real -->
            <div id="online-qr-feedback" class="p-4 sm:p-5 rounded-3xl text-sm font-bold hidden transition duration-200 border shadow-xl leading-relaxed"></div>

            <!-- Instruções Rápidas -->
            <div class="bg-gray-900 rounded-3xl p-5 border border-gray-800 text-xs text-gray-300 space-y-2.5 flex-1">
                <p class="font-bold text-white flex items-center gap-2 text-sm"><i class="fas fa-info-circle text-indigo-400"></i> Como funciona em Tela Inteira:</p>
                <ul class="list-disc pl-4 space-y-1.5 text-gray-300 leading-normal">
                    <li>A câmera permanece aberta continuamente em tela cheia para você bipar vários itens seguidos sem fechar a janela.</li>
                    <li>Cada leitura bem-sucedida adiciona o produto automaticamente à sacola da cliente.</li>
                    <li>O feedback de sucesso ou erro aparecerá em destaque logo acima.</li>
                    <li>Ao finalizar todos os produtos da cliente, clique em <strong class="text-white">Concluir e Voltar</strong> para retornar à lista.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE LOGIN AUTOMÁTICO DO INSTAGRAM NO SERVIDOR (VPS) -->
<div id="insta-login-modal" class="fixed inset-0 bg-gray-900/70 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl relative border border-gray-100 flex flex-col">
        <div class="flex justify-between items-center pb-3 border-b border-gray-100 mb-4">
            <h3 class="text-base font-bold text-purple-800 flex items-center gap-2">
                <i class="fas fa-key text-purple-600 text-xl"></i>
                <span>Login Direto no Servidor (VPS)</span>
            </h3>
            <button type="button" onclick="closeInstaLoginModal()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold p-1 leading-none">&times;</button>
        </div>

        <p class="text-xs text-gray-600 mb-4 leading-relaxed">
            Faça login na sua conta (ou em um perfil secundário/anônimo, ex: <strong>@sacolinhas_captura</strong>) diretamente dentro do navegador do servidor. Isso gera uma sessão definitiva na VPS e evita que o Instagram deslogue por troca de IP!
        </p>

        <div id="insta-login-form-step">
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Usuário do Instagram (@usuario):</label>
                    <input type="text" id="vps-insta-user" placeholder="Ex: sacolinhas_captura ou de_minha_mania" class="w-full p-2.5 rounded-xl border border-gray-300 text-sm font-semibold focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Senha:</label>
                    <input type="password" id="vps-insta-pass" placeholder="Sua senha do Instagram" class="w-full p-2.5 rounded-xl border border-gray-300 text-sm font-semibold focus:ring-purple-500 focus:border-purple-500">
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-3 border-t border-gray-100 pt-4">
                <button type="button" onclick="closeInstaLoginModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl text-xs transition duration-150">Cancelar</button>
                <button type="button" id="vps-login-submit-btn" onclick="submitVpsInstaLogin()" class="bg-purple-600 hover:bg-purple-700 text-white font-bold px-5 py-2 rounded-xl text-xs shadow-sm transition duration-150 flex items-center gap-2">
                    <i class="fas fa-sign-in-alt"></i> Fazer Login e Salvar Sessão
                </button>
            </div>
        </div>

        <!-- Passo 2FA / Desafio -->
        <div id="insta-login-2fa-step" class="hidden space-y-4">
            <div class="bg-amber-50 border border-amber-300 p-3 rounded-xl text-xs text-amber-800 font-medium">
                <p class="font-bold mb-1"><i class="fas fa-shield-alt"></i> Verificação de Segurança (2FA / Desafio)</p>
                <p id="vps-2fa-msg">O Instagram enviou um código para seu e-mail, SMS ou aplicativo autenticador. Digite o código abaixo:</p>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Código de Verificação (6 dígitos):</label>
                <input type="text" id="vps-insta-code" placeholder="Ex: 123456" class="w-full p-2.5 rounded-xl border border-gray-300 text-sm font-bold text-center tracking-widest focus:ring-purple-500 focus:border-purple-500">
            </div>
            <div class="flex justify-end gap-3 border-t border-gray-100 pt-3">
                <button type="button" onclick="closeInstaLoginModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl text-xs">Cancelar</button>
                <button type="button" id="vps-2fa-submit-btn" onclick="submitVpsInstaCode()" class="bg-purple-600 hover:bg-purple-700 text-white font-bold px-5 py-2 rounded-xl text-xs shadow-sm flex items-center gap-2">
                    <i class="fas fa-check"></i> Confirmar Código
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Parâmetros Globais
    const liveId = "{{ $activeLive ? $activeLive->id : '' }}";
    const serverOrigin = window.location.origin;
    let currentTab = 'bookmarklet';
    let lastMessageId = 0;
    let pollingInterval = null;

    document.addEventListener("DOMContentLoaded", function() {
        if (liveId) {
            // Iniciar Polling de dados (a cada 3 segundos)
            fetchChatData();
            pollingInterval = setInterval(fetchChatData, 3000);
        }
    });

    function getBackendUrl(port, path) {
        const isLocal = window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1";
        if (isLocal) {
            return `http://localhost:${port}${path}`;
        }
        const prefix = port === 3001 ? "/tiktok-api" : "/insta-api";
        return `${prefix}${path}`;
    }
    // Controle Simplificado de Gravação (Social Stream Webhooks)
    function copyWebhookUrl() {
        const input = document.getElementById("webhook-url-input");
        input.select();
        input.setSelectionRange(0, 99999);
        document.execCommand("copy");
        showToast("URL do Webhook copiada para a área de transferência!");
    }

    function updateInstagramState(isRecording) {
        const badge = document.getElementById("insta-badge");
        const dot = document.getElementById("insta-status-dot");
        const text = document.getElementById("insta-status-text");
        const btn = document.getElementById("insta-toggle-btn");
        if (!badge || !btn) return;

        if (isRecording) {
            badge.className = "bg-green-100 text-green-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 border border-green-300 transition-all";
            if (dot) {
                dot.className = "w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse";
                dot.classList.remove("hidden");
            }
            if (text) text.textContent = "Gravando";
            btn.className = "w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded-lg text-xs transition duration-150 shadow-sm flex items-center justify-center gap-1.5";
            btn.innerHTML = `<i class="fas fa-stop"></i> Parar Gravação Instagram`;
        } else {
            badge.className = "bg-purple-200 text-purple-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 transition-all";
            if (dot) dot.classList.add("hidden");
            if (text) text.textContent = "Inativo";
            btn.className = "w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 rounded-lg text-xs transition duration-150 shadow-sm flex items-center justify-center gap-1.5";
            btn.innerHTML = `<i class="fas fa-play"></i> Iniciar Gravação Instagram`;
        }
    }

    async function toggleInstagramCapture() {
        const btn = document.getElementById("insta-toggle-btn");
        const isCurrentlyActive = btn && btn.textContent.includes("Parar");
        const action = isCurrentlyActive ? "stop" : "start";

        try {
            const res = await fetch("/admin/live-chat/toggle-instagram", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
                },
                body: JSON.stringify({ action: action })
            });
            const data = await res.json();
            if (data.success) {
                updateInstagramState(data.insta_active);
                showToast(data.insta_active ? "Gravação do Instagram Iniciada!" : "Gravação do Instagram Parada!");
            }
        } catch (e) {
            console.error("Erro ao alternar captura do Instagram:", e);
        }
    }

    function updateTikTokState(isRecording) {
        const badge = document.getElementById("tiktok-backend-badge");
        const dot = document.getElementById("tiktok-status-dot");
        const text = document.getElementById("tiktok-status-text");
        const btn = document.getElementById("tiktok-toggle-btn");
        if (!badge || !btn) return;

        if (isRecording) {
            badge.className = "bg-green-100 text-green-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 border border-green-300 transition-all";
            if (dot) {
                dot.className = "w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse";
                dot.classList.remove("hidden");
            }
            if (text) text.textContent = "Gravando";
            btn.className = "w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded-lg text-xs transition duration-150 shadow-sm flex items-center justify-center gap-1.5";
            btn.innerHTML = `<i class="fas fa-stop"></i> Parar Gravação TikTok`;
        } else {
            badge.className = "bg-gray-200 text-gray-700 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 transition-all";
            if (dot) {
                dot.className = "w-1.5 h-1.5 rounded-full bg-gray-500";
                dot.classList.remove("hidden");
            }
            if (text) text.textContent = "Inativo";
            btn.className = "w-full bg-pink-600 hover:bg-pink-700 text-white font-bold py-2 rounded-lg text-xs transition duration-150 shadow-sm flex items-center justify-center gap-1.5";
            btn.innerHTML = `<i class="fas fa-play"></i> Iniciar Gravação TikTok`;
        }
    }

    async function toggleTikTokBackend() {
        const btn = document.getElementById("tiktok-toggle-btn");
        const isCurrentlyActive = btn && btn.textContent.includes("Parar");
        const action = isCurrentlyActive ? "stop" : "start";

        try {
            // Reaproveitamos o mesmo estilo de endpoint do Instagram
            const res = await fetch("/admin/live-chat/toggle-tiktok", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
                },
                body: JSON.stringify({ action: action })
            });
            const data = await res.json();
            if (data.success) {
                updateTikTokState(data.tiktok_active);
                showToast(data.tiktok_active ? "Gravação do TikTok Iniciada!" : "Gravação do TikTok Parada!");
                // Notificar listener local instantaneamente
                fetch("http://127.0.0.1:3002/check-now", { mode: "cors" }).catch(() => {});
            }
        } catch (e) {
            console.error("Erro ao alternar captura do TikTok:", e);
        }
    }

    // Alternar abas da barra lateral
    function switchTab(tab) {
        currentTab = tab;
        const btnBookmarklet = document.getElementById("tab-btn-bookmarklet");
        const btnOnline = document.getElementById("tab-btn-online");
        const divBookmarklet = document.getElementById("tab-content-bookmarklet");
        const divOnline = document.getElementById("tab-content-online");

        if (tab === 'bookmarklet') {
            btnBookmarklet.className = "flex-1 py-3 px-4 text-center font-semibold text-sm border-b-2 border-indigo-600 text-indigo-600";
            btnOnline.className = "flex-1 py-3 px-4 text-center font-semibold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300";
            divBookmarklet.classList.remove("hidden");
            divOnline.classList.add("hidden");
        } else {
            btnOnline.className = "flex-1 py-3 px-4 text-center font-semibold text-sm border-b-2 border-indigo-600 text-indigo-600";
            btnBookmarklet.className = "flex-1 py-3 px-4 text-center font-semibold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300";
            divOnline.classList.remove("hidden");
            divBookmarklet.classList.add("hidden");
        }
    }

    // Buscar dados do chat da live via AJAX
    function fetchChatData() {
        if (!liveId) return;
        
        fetch(`/admin/lives/${liveId}/chat-data`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (typeof updatePauseState === 'function') updatePauseState(data.is_paused);
                    updateInstagramState(data.insta_active);
                    updateTikTokState(data.tiktok_active);
                    renderChatMessages(data.messages);
                    renderOnlineUsers(data.online_users);
                    renderCodeRequests(data.code_requests);
                }
            })
            .catch(err => console.error("Erro no polling da live:", err));
    }

    async function toggleMasterPause() {
        try {
            const res = await fetch("/admin/live-chat/toggle-pause", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
                }
            });
            const data = await res.json();
            if (data.success) updatePauseState(data.is_paused);
        } catch (e) {
            console.error("Erro ao alternar pausa");
        }
    }

    function updatePauseState(isPaused) {
        const btn = document.getElementById("btn-master-pause");
        const dot = document.getElementById("chat-ping-dot");
        const text = document.getElementById("chat-status-text");

        if (btn) {
            if (isPaused) {
                btn.className = "bg-green-600 hover:bg-green-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition duration-150 shadow-sm flex items-center gap-1.5";
                btn.innerHTML = `<i class="fas fa-play text-[10px]"></i> Retomar Captura`;
            } else {
                btn.className = "bg-red-600 hover:bg-red-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition duration-150 shadow-sm flex items-center gap-1.5";
                btn.innerHTML = `<i class="fas fa-pause text-[10px]"></i> Pausar Captura`;
            }
        }

        if (isPaused) {
            if (dot) dot.className = "inline-block w-2.5 h-2.5 bg-yellow-500 rounded-full";
            if (text) text.textContent = "Pausado no Sistema";
        } else {
            if (dot) dot.className = "inline-block w-2.5 h-2.5 bg-green-500 rounded-full animate-ping";
            if (text) text.textContent = "Capturando";
        }
    }

    // Renderizar mensagens de chat no terminal
    function renderChatMessages(messages) {
        const container = document.getElementById("chat-messages-container");
        if (messages.length === 0) return;

        let html = '';
        messages.forEach(msg => {
            const isTikTok = msg.plataforma === 'tiktok';
            const icon = isTikTok ? '<i class="fab fa-tiktok text-pink-500"></i>' : '<i class="fab fa-instagram text-purple-500"></i>';
            const time = new Date(msg.created_at).toLocaleTimeString();
            const initials = msg.username.slice(0,2).toUpperCase();
            const avatarHtml = msg.avatar_url
                ? `<img src="${escapeHtml(msg.avatar_url)}" onerror="this.onerror=null;this.src=''" class="w-6 h-6 rounded-full object-cover shrink-0" />`
                : `<div class="w-6 h-6 rounded-full bg-indigo-900 flex items-center justify-center font-bold text-[9px] text-indigo-300 shrink-0">${initials}</div>`;
            
            html += `
                <div class="hover:bg-gray-800/50 p-1.5 rounded transition duration-150">
                    <div class="flex items-center justify-between mb-0.5 gap-1.5">
                        <div class="flex items-center gap-1.5 min-w-0">
                            ${avatarHtml}
                            <span class="font-bold text-xs text-indigo-400 flex items-center gap-1 truncate">
                                ${icon} @${msg.username}
                            </span>
                        </div>
                        <span class="text-[9px] text-gray-600 font-mono shrink-0">${time}</span>
                    </div>
                    <p class="text-sm text-gray-200 leading-normal pl-7">${escapeHtml(msg.message)}</p>
                </div>
            `;
        });

        const shouldScroll = container.scrollTop + container.clientHeight >= container.scrollHeight - 100;
        container.innerHTML = html;
        if (shouldScroll) {
            container.scrollTop = container.scrollHeight;
        }
    }

    // Renderizar usuários online
    function renderOnlineUsers(users) {
        const list = document.getElementById("online-users-list");
        const count = document.getElementById("online-users-count");
        count.textContent = users.length;

        if (users.length === 0) {
            list.innerHTML = `<p class="text-xs text-gray-400 text-center py-6">Nenhum participante detectado ainda.</p>`;
            return;
        }

        let html = '';
        users.forEach(u => {
            const isTikTok = u.plataforma === 'tiktok';
            const icon = isTikTok ? '<i class="fab fa-tiktok text-pink-500 text-xs"></i>' : '<i class="fab fa-instagram text-purple-500 text-xs"></i>';
            const initials = u.username.slice(0,2).toUpperCase();

            // Avatar: foto de perfil se disponível, fallback para iniciais
            const avatarHtml = u.avatar_url
                ? `<img src="${escapeHtml(u.avatar_url)}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" class="w-9 h-9 rounded-full object-cover border-2 border-white shadow-sm" /><div class="w-9 h-9 rounded-full bg-indigo-100 items-center justify-center font-bold text-xs text-indigo-700 hidden">${initials}</div>`
                : `<div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center font-bold text-xs text-indigo-700">${initials}</div>`;
            
            let displayName = escapeHtml(u.user_name || '');
            if (u.user_apelido) {
                displayName = displayName ? `${displayName} (${escapeHtml(u.user_apelido)})` : escapeHtml(u.user_apelido);
            }

            let subtitle = '';
            if (u.user_id) {
                subtitle = displayName ? `<span class="text-[8.5px] font-normal text-green-700 flex items-center gap-1 mt-0.5"><i class="fas fa-user text-[7.5px]"></i> ${displayName}</span>` : '';
            } else {
                subtitle = `<span class="text-[9.5px] text-gray-400">Visto às ${u.last_seen}</span>`;
            }

            const clientName = escapeHtml(u.user_name || u.user_apelido || '');
            html += `
                <div ${u.user_id ? `onclick="openOnlineQrModal('${u.user_id}', '${escapeHtml(u.username)}', '${clientName}')"` : `onclick="openLinkModal('${escapeHtml(u.username)}', '${escapeHtml(u.plataforma)}')"`} 
                     class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 border border-gray-150 hover:bg-indigo-50/70 hover:border-indigo-300 cursor-pointer transition duration-150 shadow-xs">
                    <div class="flex items-center gap-2">
                        <div class="shrink-0 relative">
                            ${avatarHtml}
                            <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full bg-white flex items-center justify-center text-[7px] shadow">${icon}</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-1 text-xs font-semibold text-gray-800">
                                @${escapeHtml(u.username)}
                            </div>
                            ${subtitle}
                        </div>
                    </div>
                    <div>
                        ${
                            u.user_id ? 
                            `<button type="button" onclick="event.stopPropagation(); openOnlineQrModal('${u.user_id}', '${escapeHtml(u.username)}', '${clientName}')" class="bg-teal-600 hover:bg-teal-700 text-white font-bold px-3 py-1.5 rounded-xl text-xs transition duration-150 shadow-sm flex items-center gap-1.5"><i class="fas fa-qrcode text-sm"></i> Ler QRCode</button>`
                            :
                            `<button type="button" onclick="event.stopPropagation(); openLinkModal('${escapeHtml(u.username)}', '${escapeHtml(u.plataforma)}')" class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-2.5 py-1 rounded-xl text-[11px] transition duration-150 shadow-sm flex items-center gap-1"><i class="fas fa-link"></i> Vincular & QR</button>`
                        }
                    </div>
                </div>
            `;
        });

        list.innerHTML = html;
    }

    // Renderizar fila de pedidos por códigos
    function renderCodeRequests(requests) {
        const container = document.getElementById("code-requests-container");
        const count = document.getElementById("code-queue-count");
        count.textContent = `${requests.length} códigos`;

        if (requests.length === 0) {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full text-gray-400 py-12">
                    <i class="fas fa-box-open text-4xl mb-2"></i>
                    <p class="text-sm">Aguardando códigos detectados no chat...</p>
                </div>
            `;
            return;
        }

        let html = '';
        requests.forEach(req => {
            let queueHtml = '';
            req.queue.forEach((qItem, index) => {
                const isFirst = index === 0;
                let userBlock = '';
                let actionBtn = '';

                if (qItem.user_id) {
                    userBlock = `
                        <span class="text-xs font-bold text-green-700 flex items-center gap-1.5">
                            <i class="fas fa-user-circle"></i> @${qItem.username} <span class="text-[9.5px] font-normal text-green-600">(${escapeHtml(qItem.user_name)})</span>
                        </span>
                    `;
                    actionBtn = `
                        <button onclick="addToBag(${qItem.id}, ${qItem.user_id}, ${req.item_id})" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-3 py-1 rounded-lg text-xs transition duration-150 shadow-sm">
                            <i class="fas fa-cart-plus mr-1"></i> Sacola
                        </button>
                    `;
                } else {
                    userBlock = `
                        <span class="text-xs font-bold text-amber-700 flex items-center gap-1">
                            <i class="fas fa-question-circle"></i> @${qItem.username} (Sem cadastro)
                        </span>
                    `;
                    actionBtn = `
                        <button onclick="openLinkModal('${escapeHtml(qItem.username)}', 'instagram')" class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-3 py-1 rounded-lg text-xs transition duration-150 shadow-sm">
                            <i class="fas fa-link mr-1"></i> Vincular
                        </button>
                    `;
                }

                queueHtml += `
                    <div class="flex items-center justify-between p-2.5 rounded-xl border ${isFirst ? 'bg-indigo-50 border-indigo-200' : 'bg-white border-gray-100'} shadow-sm hover:shadow transition duration-150">
                        <div class="flex items-center gap-3">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold ${isFirst ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'}">${index + 1}º</span>
                            <div>
                                ${userBlock}
                                <span class="text-[10px] text-gray-400 block">Texto: "${escapeHtml(qItem.message_text)}" às ${qItem.created_at}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            ${actionBtn}
                            <button onclick="ignoreRequest(${qItem.id})" class="text-gray-400 hover:text-red-500 px-2 py-1 rounded transition duration-150" title="Ignorar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            html += `
                <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm space-y-3">
                    <div class="flex items-start justify-between pb-3 border-b border-gray-100">
                        <div>
                            <span class="inline-block bg-indigo-100 text-indigo-800 font-extrabold text-sm px-3 py-1 rounded-full uppercase tracking-wider mb-1">
                                Cód: ${req.codigo}
                            </span>
                            <h3 class="font-bold text-gray-800 text-sm">${escapeHtml(req.item_nome)}</h3>
                        </div>
                        <div class="text-right">
                            <span class="font-extrabold text-lg text-indigo-600">R$ ${parseFloat(req.item_preco).toFixed(2).replace('.', ',')}</span>
                            <span class="block text-[10px] text-gray-400 uppercase font-semibold">Status: ${req.item_status}</span>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Fila de Espera:</h4>
                        ${queueHtml}
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // Adicionar item à sacola
    function addToBag(requestId, userId, itemId) {
        if (!confirm("Confirmar adição deste item à sacola do cliente?")) return;

        fetch('/admin/live-chat/add-to-bag', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                code_request_id: requestId,
                user_id: userId,
                item_id: itemId,
                live_id: liveId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast("Item adicionado com sucesso!");
                fetchChatData();
            } else {
                alert("Erro ao adicionar: " + data.message);
            }
        })
        .catch(err => console.error("Erro ao adicionar à sacola:", err));
    }

    // Ignorar solicitação de código
    function ignoreRequest(requestId) {
        if (!confirm("Deseja ignorar esta solicitação de código?")) return;

        fetch('/admin/live-chat/ignore', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                code_request_id: requestId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                fetchChatData();
            }
        })
        .catch(err => console.error(err));
    }

    // Controladores do Modal de Vincular Usuário
    function openLinkModal(username, platform) {
        document.getElementById("modal-display-username").textContent = `@${username}`;
        document.getElementById("modal-display-platform").textContent = platform.toUpperCase();
        document.getElementById("modal-input-username").value = username;
        document.getElementById("modal-input-platform").value = platform;
        document.getElementById("modal-search-input").value = "";
        document.getElementById("modal-search-results").innerHTML = `<p class="text-xs text-gray-400 text-center py-4">Comece a digitar para pesquisar clientes.</p>`;
        
        document.getElementById("link-user-modal").classList.remove("hidden");
        document.getElementById("modal-search-input").focus();
    }

    function closeLinkModal() {
        document.getElementById("link-user-modal").classList.add("hidden");
    }

    // Buscar clientes via AJAX para vinculação
    function searchClients(query) {
        const resultsContainer = document.getElementById("modal-search-results");
        if (query.trim().length < 2) {
            resultsContainer.innerHTML = `<p class="text-xs text-gray-400 text-center py-4">Digite pelo menos 2 caracteres.</p>`;
            return;
        }

        fetch(`/api/users/search?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    let html = '';
                    data.data.forEach(user => {
                        html += `
                            <div onclick="linkUserToProfile('${user.id}')" class="p-2.5 rounded-lg border border-gray-200 bg-white hover:bg-indigo-50 hover:border-indigo-300 transition duration-150 cursor-pointer flex justify-between items-center">
                                <div>
                                    <h4 class="font-bold text-xs text-gray-800">${escapeHtml(user.name)}</h4>
                                    <span class="text-[10px] text-gray-500">${user.whatsapp ? 'WhatsApp: ' + escapeHtml(user.whatsapp) : 'Sem número'}</span>
                                    ${user.apelido ? `<span class="text-[10px] text-indigo-600 block">Apelido: ${escapeHtml(user.apelido)}</span>` : ''}
                                </div>
                                <i class="fas fa-plus text-indigo-500 text-xs"></i>
                            </div>
                        `;
                    });
                    resultsContainer.innerHTML = html;
                } else {
                    resultsContainer.innerHTML = `<p class="text-xs text-gray-400 text-center py-4">Nenhum cliente cadastrado encontrado.</p>`;
                }
            })
            .catch(err => console.error("Erro na busca de usuários:", err));
    }

    let currentAvulsoFocus = -1;

    // Busca de Clientes Avulso (na aba Pessoas Online)
    function searchAvulsoClients(query) {
        const resultsContainer = document.getElementById("avulso-search-results");
        if (query.trim().length < 2) {
            resultsContainer.classList.add("hidden");
            return;
        }

        resultsContainer.classList.remove("hidden");
        resultsContainer.innerHTML = `<p class="text-xs text-gray-400 text-center py-4"><i class="fas fa-spinner fa-spin mr-1"></i> Buscando...</p>`;

        fetch(`/api/users/search?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    currentAvulsoFocus = -1; // Reset focus na nova busca
                    let html = '';
                    data.data.forEach(user => {
                        const identifier = user.instagram || user.tiktok || user.whatsapp || user.id;
                        html += `
                            <div onclick="selectAvulsoClient('${user.id}', '${escapeHtml(user.name)}', '${escapeHtml(user.instagram || user.tiktok || '')}')" class="avulso-search-item p-3 border-b border-gray-100 bg-white hover:bg-indigo-50 cursor-pointer transition flex justify-between items-center group">
                                <div>
                                    <h4 class="font-bold text-sm text-gray-800 group-hover:text-indigo-700">${escapeHtml(user.name)}</h4>
                                    <div class="text-[11px] text-gray-500 mt-1 flex flex-wrap gap-2">
                                        ${user.instagram ? `<span class="text-pink-600"><i class="fab fa-instagram"></i> @${escapeHtml(user.instagram)}</span>` : ''}
                                        ${user.tiktok ? `<span class="text-black"><i class="fab fa-tiktok"></i> @${escapeHtml(user.tiktok)}</span>` : ''}
                                        ${user.whatsapp ? `<span class="text-green-600"><i class="fab fa-whatsapp"></i> ${escapeHtml(user.whatsapp)}</span>` : ''}
                                        ${user.apelido ? `<span class="text-indigo-600"><i class="fas fa-tag"></i> ${escapeHtml(user.apelido)}</span>` : ''}
                                    </div>
                                </div>
                                <button type="button" class="bg-indigo-100 text-indigo-700 w-8 h-8 rounded-lg opacity-0 group-hover:opacity-100 transition shadow-sm flex items-center justify-center shrink-0">
                                    <i class="fas fa-camera text-sm"></i>
                                </button>
                            </div>
                        `;
                    });
                    resultsContainer.innerHTML = html;
                } else {
                    resultsContainer.innerHTML = `<p class="text-xs text-gray-400 text-center py-4">Nenhum cliente cadastrado encontrado.</p>`;
                }
            })
            .catch(err => {
                resultsContainer.innerHTML = `<p class="text-xs text-red-400 text-center py-4">Erro na busca de clientes.</p>`;
                console.error("Erro na busca avulsa:", err);
            });
    }

    function selectAvulsoClient(id, name, usernameFallback) {
        const resultsContainer = document.getElementById("avulso-search-results");
        const input = document.getElementById("avulso-search-input");
        if (resultsContainer) resultsContainer.classList.add("hidden");
        if (input) input.value = "";
        
        // Abre o modal de bipagem exatamente igual a clicar na lista
        openOnlineQrModal(id, usernameFallback || name, name);
    }
    
    // Esconder resultados ao clicar fora
    document.addEventListener('click', function(e) {
        const results = document.getElementById("avulso-search-results");
        const input = document.getElementById("avulso-search-input");
        if (results && input && !results.contains(e.target) && e.target !== input) {
            results.classList.add("hidden");
        }
    });

    // Executar vinculação do cliente
    function linkUserToProfile(userId) {
        const username = document.getElementById("modal-input-username").value;
        const platform = document.getElementById("modal-input-platform").value;

        fetch('/admin/live-chat/link-user', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                username: username,
                platform: platform,
                user_id: userId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast("Vinculação realizada com sucesso!");
                closeLinkModal();
                fetchChatData();
                // Abre o leitor de QR Code para esse usuário que acabou de ser vinculado
                openOnlineQrModal(userId, username, '');
            } else {
                alert("Erro ao vincular: " + data.message);
            }
        })
        .catch(err => console.error("Erro ao vincular usuário:", err));
    }

    // ==========================================
    // LEITOR DE QRCODE / ETIQUETAS PARA PESSOAS ONLINE
    // ==========================================
    let onlineQrScannerInstance = null;
    let currentOnlineQrUser = null;

    function openOnlineQrModal(userId, username, clientName) {
        if (!userId || userId === 'null' || userId === 'undefined') {
            openLinkModal(username, 'instagram');
            return;
        }

        currentOnlineQrUser = {
            userId: userId,
            username: username,
            clientName: clientName || username
        };

        const clientNameEl = document.getElementById("online-qr-client-name");
        if (clientNameEl) {
            clientNameEl.textContent = `@${username} (${clientName || 'Cliente'})`;
        }

        const manualInput = document.getElementById("online-qr-manual-input");
        if (manualInput) manualInput.value = "";

        const feedback = document.getElementById("online-qr-feedback");
        if (feedback) {
            feedback.className = "mt-3 p-3 rounded-xl text-xs font-bold hidden transition duration-200";
            feedback.textContent = "";
        }

        document.getElementById("online-qr-modal").classList.remove("hidden");
        if (manualInput) manualInput.focus();

        // Inicializar câmera HTML5-QRCode
        if (typeof Html5Qrcode === 'undefined') {
            const script = document.createElement('script');
            script.src = "https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js";
            script.onload = () => startOnlineQrCamera();
            document.head.appendChild(script);
        } else {
            startOnlineQrCamera();
        }
    }

    async function startOnlineQrCamera() {
        if (!onlineQrScannerInstance) {
            let formats = [0, 9, 5]; // QR_CODE, EAN_13, CODE_128
            if (typeof Html5QrcodeSupportedFormats !== 'undefined') {
                formats = [
                    Html5QrcodeSupportedFormats.QR_CODE,
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.CODE_128
                ];
            }
            onlineQrScannerInstance = new Html5Qrcode("online-qr-reader", { formatsToSupport: formats });
        }

        try {
            const config = {
                fps: 15,
                qrbox: function(width, height) {
                    const minEdge = Math.min(width, height);
                    const size = Math.min(Math.floor(minEdge * 0.8), 650);
                    return { width: size, height: size };
                },
                disableFlip: true
            };

            await onlineQrScannerInstance.start(
                { facingMode: "environment" },
                config,
                async (decodedText) => {
                    console.log("QRCode da etiqueta lido para usuário online:", decodedText);
                    await handleOnlineQrScan(decodedText);
                }
            );
        } catch (err) {
            console.warn("Não foi possível iniciar a câmera ou permissão negada:", err);
            const feedback = document.getElementById("online-qr-feedback");
            if (feedback) {
                feedback.className = "p-4 sm:p-5 rounded-3xl text-xs sm:text-sm font-bold bg-amber-500/20 text-amber-200 border border-amber-500/40 block shadow-lg";
                feedback.innerHTML = `<i class="fas fa-exclamation-triangle text-amber-400 mr-1.5"></i> Câmera não acessível (${err.message || 'Sem permissão'}). Digite ou bipe o código com leitor USB no campo acima!`;
            }
        }
    }

    async function closeOnlineQrModal() {
        if (onlineQrScannerInstance) {
            try {
                await onlineQrScannerInstance.stop();
            } catch (e) {}
        }
        document.getElementById("online-qr-modal").classList.add("hidden");
        fetchChatData();
    }

    async function handleOnlineQrScan(decodedText) {
        if (!decodedText || !decodedText.trim() || !currentOnlineQrUser) return;
        
        let code = decodedText.trim();

        // Extrair código limpo se for URL
        if (code.startsWith('http://') || code.startsWith('https://')) {
            try {
                const url = new URL(code);
                const p = url.searchParams.get('codigo') || url.searchParams.get('c') || url.searchParams.get('code') || url.searchParams.get('item');
                if (p) {
                    code = p.trim();
                } else {
                    const segs = url.pathname.split('/').filter(Boolean);
                    if (segs.length > 0) {
                        code = segs[segs.length - 1].trim();
                    }
                }
            } catch(e) {}
        }

        const manualInput = document.getElementById("online-qr-manual-input");
        if (manualInput) manualInput.value = "";

        const feedback = document.getElementById("online-qr-feedback");
        if (feedback) {
            feedback.className = "p-4 sm:p-5 rounded-3xl text-sm font-bold bg-blue-500/20 text-blue-200 border border-blue-500/40 block animate-pulse shadow-lg";
            feedback.innerHTML = `<div class="flex items-center gap-3"><i class="fas fa-spinner fa-spin text-blue-400 text-xl"></i><div><b>Buscando produto...</b><br><span class="text-xs text-blue-300 font-normal">Etiqueta/SKU: ${escapeHtml(code)}</span></div></div>`;
        }

        try {
            const response = await fetch(`/api/items/search?q=${encodeURIComponent(code)}`);
            const data = await response.json();

            if (!data.success || !data.data || data.data.length === 0) {
                if (feedback) {
                    feedback.className = "p-4 sm:p-5 rounded-3xl text-sm font-bold bg-red-500/20 text-red-200 border border-red-500/40 block shadow-lg animate-shake";
                    feedback.innerHTML = `<div class="flex items-start gap-3"><i class="fas fa-times-circle text-red-400 text-xl mt-0.5"></i><div><b>Produto não encontrado!</b><br><span class="text-xs text-red-300 font-normal">Nenhum produto cadastrado com o SKU ou código de barras <b>"${escapeHtml(code)}"</b>. Verifique o código e tente novamente.</span></div></div>`;
                }
                return;
            }

            let matchedItem = data.data.find(item => 
                (item.sku && item.sku.toLowerCase() === code.toLowerCase()) || 
                (item.codigo && item.codigo.toLowerCase() === code.toLowerCase()) || 
                String(item.id) === code
            ) || data.data[0];

            const addResponse = await fetch('/admin/live-chat/add-to-bag', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    code_request_id: null,
                    user_id: currentOnlineQrUser.userId,
                    item_id: matchedItem.id,
                    live_id: liveId
                })
            });

            const addData = await addResponse.json();

            if (addData.success) {
                showToast(`🎉 ${matchedItem.name} adicionado à sacola de @${currentOnlineQrUser.username}!`);
                if (feedback) {
                    feedback.className = "p-4 sm:p-5 rounded-3xl text-sm font-bold bg-emerald-500/20 text-emerald-200 border border-emerald-500/40 block shadow-xl";
                    feedback.innerHTML = `<div class="flex items-start gap-3"><div class="w-10 h-10 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center shrink-0"><i class="fas fa-check text-emerald-400 text-xl"></i></div><div><div class="text-emerald-300 text-xs uppercase tracking-wider font-extrabold">Adicionado à sacola!</div><div class="text-white text-base font-extrabold mt-0.5">${escapeHtml(matchedItem.name)}</div><div class="text-emerald-400 font-bold text-sm mt-0.5">${matchedItem.formatted_price || 'R$ ' + matchedItem.price} &bull; <span class="text-gray-300 font-normal">Para @${escapeHtml(currentOnlineQrUser.username)}</span></div><div class="text-xs text-emerald-300/80 mt-2 font-normal"><i class="fas fa-camera text-emerald-400 mr-1"></i> Pronto para o próximo item! Aponte a câmera ou bipe agora.</div></div></div>`;
                }
            } else {
                if (feedback) {
                    feedback.className = "p-4 sm:p-5 rounded-3xl text-sm font-bold bg-amber-500/20 text-amber-200 border border-amber-500/40 block shadow-lg";
                    feedback.innerHTML = `<div class="flex items-start gap-3"><i class="fas fa-exclamation-triangle text-amber-400 text-xl mt-0.5"></i><div><b>Erro ao adicionar:</b><br><span class="text-xs text-amber-300 font-normal">${escapeHtml(addData.message || 'Falha ao incluir na sacola. Tente novamente.')}</span></div></div>`;
                }
            }
        } catch (err) {
            console.error("Erro na leitura/adição por QR Code:", err);
            if (feedback) {
                feedback.className = "p-4 sm:p-5 rounded-3xl text-sm font-bold bg-red-500/20 text-red-200 border border-red-500/40 block shadow-lg";
                feedback.innerHTML = `<div class="flex items-start gap-3"><i class="fas fa-exclamation-circle text-red-400 text-xl mt-0.5"></i><div><b>Erro de comunicação:</b><br><span class="text-xs text-red-300 font-normal">Falha ao se comunicar com o servidor ao processar "${escapeHtml(code)}".</span></div></div>`;
            }
        }
    }

    // Utilitários de escape de HTML
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Mostrar mensagem Toast na tela
    function showToast(message) {
        const toast = document.createElement("div");
        toast.className = "fixed bottom-5 right-5 bg-green-600 text-white px-5 py-3 rounded-xl shadow-lg z-50 transition-all duration-300 translate-y-5 opacity-0 text-sm font-semibold flex items-center gap-2";
        toast.innerHTML = `<i class="fas fa-check-circle"></i> <span>${message}</span>`;
        document.body.appendChild(toast);
        
        // Animates in
        setTimeout(() => {
            toast.style.transform = "translateY(0)";
            toast.style.opacity = "1";
        }, 100);

        // Animates out and removes
        setTimeout(() => {
            toast.style.transform = "translateY(5px)";
            toast.style.opacity = "0";
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    // Controle do Modal de Login Direto na VPS do Instagram
    function openInstaLoginModal() {
        document.getElementById("insta-login-modal").classList.remove("hidden");
        document.getElementById("insta-login-form-step").classList.remove("hidden");
        document.getElementById("insta-login-2fa-step").classList.add("hidden");
    }

    function closeInstaLoginModal() {
        document.getElementById("insta-login-modal").classList.add("hidden");
    }

    async function submitVpsInstaLogin() {
        const usr = document.getElementById("vps-insta-user").value.trim();
        const pwd = document.getElementById("vps-insta-pass").value.trim();
        if (!usr || !pwd) {
            alert("Preencha o usuário e senha do Instagram.");
            return;
        }

        const btn = document.getElementById("vps-login-submit-btn");
        const oldText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Autenticando na VPS...`;

        try {
            const res = await fetch(getBackendUrl(3002, "/login"), {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ username: usr, password: pwd })
            });
            const data = await res.json();
            btn.disabled = false;
            btn.innerHTML = oldText;

            if (data.success) {
                showToast(data.message);
                closeInstaLoginModal();
                const input = document.getElementById("insta-username-input");
                if (input) input.value = usr.replace(/^@/, '');
                checkInstagramBackendStatus();
            } else if (data.status === 'challenge') {
                document.getElementById("insta-login-form-step").classList.add("hidden");
                document.getElementById("insta-login-2fa-step").classList.remove("hidden");
                if (data.message) document.getElementById("vps-2fa-msg").textContent = data.message;
            } else {
                alert("Atenção: " + (data.message || "Verifique suas credenciais."));
            }
        } catch(e) {
            btn.disabled = false;
            btn.innerHTML = oldText;
            alert("Erro ao comunicar com a VPS: " + e.message);
        }
    }

    async function submitVpsInstaCode() {
        const code = document.getElementById("vps-insta-code").value.trim();
        if (!code) {
            alert("Digite o código de 6 dígitos recebido.");
            return;
        }

        const btn = document.getElementById("vps-2fa-submit-btn");
        const oldText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Confirmando...`;

        try {
            const res = await fetch(getBackendUrl(3002, "/login-code"), {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ code: code })
            });
            const data = await res.json();
            btn.disabled = false;
            btn.innerHTML = oldText;

            if (data.success) {
                showToast(data.message);
                closeInstaLoginModal();
                checkInstagramBackendStatus();
            } else {
                alert("Erro: " + (data.message || "Código inválido ou expirado."));
            }
        } catch(e) {
            btn.disabled = false;
            btn.innerHTML = oldText;
            alert("Erro na requisição: " + e.message);
        }
    }

    // Busca de clientes (ignora setas e enter para não sobrepor a navegação)
    let lastAvulsoQuery = "";
    document.getElementById("avulso-search-input").addEventListener("keyup", function(e) {
        if ([38, 40, 13].includes(e.keyCode)) return;
        if (this.value === lastAvulsoQuery) return;
        lastAvulsoQuery = this.value;
        searchAvulsoClients(this.value);
    });

    // Navegação por teclado no input Avulso
    document.getElementById("avulso-search-input").addEventListener("keydown", function(e) {
        let x = document.getElementById("avulso-search-results");
        if (!x || x.classList.contains('hidden')) return;
        let items = x.querySelectorAll('.avulso-search-item');
        if (!items || items.length === 0) return;

        if (e.keyCode == 40) { // Seta para baixo
            currentAvulsoFocus++;
            addActiveAvulso(items);
            e.preventDefault();
        } else if (e.keyCode == 38) { // Seta para cima
            currentAvulsoFocus--;
            addActiveAvulso(items);
            e.preventDefault();
        } else if (e.keyCode == 13) { // Enter
            e.preventDefault();
            if (currentAvulsoFocus > -1) {
                if (items[currentAvulsoFocus]) {
                    items[currentAvulsoFocus].click();
                }
            } else if (items.length > 0) {
                items[0].click();
            }
        }
    });

    function addActiveAvulso(x) {
        if (!x) return false;
        removeActiveAvulso(x);
        if (currentAvulsoFocus >= x.length) currentAvulsoFocus = 0;
        if (currentAvulsoFocus < 0) currentAvulsoFocus = (x.length - 1);
        x[currentAvulsoFocus].classList.add("bg-indigo-100", "border-l-4", "border-indigo-500");
        x[currentAvulsoFocus].classList.remove("bg-white");
        x[currentAvulsoFocus].scrollIntoView({ block: "nearest", behavior: "smooth" });
    }

    function removeActiveAvulso(x) {
        for (var i = 0; i < x.length; i++) {
            x[i].classList.remove("bg-indigo-100", "border-l-4", "border-indigo-500");
            x[i].classList.add("bg-white");
        }
    }
    // ==========================================
    // RECONHECIMENTO DE VOZ / ÁUDIO DA LIVE
    // ==========================================
    let speechRecognizer = null;
    let isAudioRecording = false;
    let shouldKeepAudioRecording = false;
    let audioSentencesCount = 0;

    function initAudioSpeechRecognition() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            alert("Seu navegador não possui suporte ao reconhecimento de voz nativo. Por favor, utilize o Google Chrome ou Microsoft Edge no computador.");
            return null;
        }

        const recognizer = new SpeechRecognition();
        recognizer.continuous = true;
        recognizer.interimResults = true;
        recognizer.lang = 'pt-BR';
        recognizer.maxAlternatives = 1;

        recognizer.onstart = function() {
            isAudioRecording = true;
            updateAudioRecUI(true);
        };

        recognizer.onresult = function(event) {
            let interimText = '';
            let finalText = '';

            for (let i = event.resultIndex; i < event.results.length; ++i) {
                const transcript = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    finalText += transcript;
                } else {
                    interimText += transcript;
                }
            }

            const interimBox = document.getElementById("audio-interim-text");
            const speakingDot = document.getElementById("audio-speaking-indicator");

            if (interimText.trim().length > 0) {
                interimBox.textContent = `"${interimText.trim()}"`;
                interimBox.classList.remove("italic", "text-slate-300");
                interimBox.classList.add("text-emerald-300", "font-medium");
                speakingDot.classList.remove("hidden");
            }

            if (finalText.trim().length > 0) {
                handleFinalTranscribedSentence(finalText.trim());
                interimBox.textContent = 'Ouvindo microfone...';
                interimBox.classList.add("italic", "text-slate-300");
                interimBox.classList.remove("text-emerald-300", "font-medium");
                speakingDot.classList.add("hidden");
            }
        };

        recognizer.onerror = function(event) {
            console.warn("[Voz Live] Erro de reconhecimento:", event.error);
            if (event.error === 'not-allowed') {
                alert("Permissão de microfone negada. Clique no ícone de cadeado na barra de endereços do navegador e permita o microfone.");
                shouldKeepAudioRecording = false;
                stopAudioRecording();
            }
        };

        recognizer.onend = function() {
            if (shouldKeepAudioRecording) {
                // Auto-reiniciar imediatamente caso o Chrome pause após silêncio
                setTimeout(() => {
                    if (shouldKeepAudioRecording) {
                        try {
                            recognizer.start();
                        } catch (e) {
                            console.log("[Voz Live] Reiniciando escuta...");
                        }
                    }
                }, 250);
            } else {
                isAudioRecording = false;
                updateAudioRecUI(false);
            }
        };

        return recognizer;
    }

    function toggleAudioRecording() {
        if (!speechRecognizer) {
            speechRecognizer = initAudioSpeechRecognition();
            if (!speechRecognizer) return;
        }

        if (!isAudioRecording) {
            startAudioRecording();
        } else {
            stopAudioRecording();
        }
    }

    function startAudioRecording() {
        try {
            shouldKeepAudioRecording = true;
            speechRecognizer.start();
        } catch (e) {
            console.warn("[Voz Live] Falha ao iniciar:", e);
        }
    }

    function stopAudioRecording() {
        shouldKeepAudioRecording = false;
        if (speechRecognizer) {
            try {
                speechRecognizer.stop();
            } catch(e) {}
        }
        isAudioRecording = false;
        updateAudioRecUI(false);
    }

    function updateAudioRecUI(recording) {
        const btn = document.getElementById("btn-toggle-audio-rec");
        const btnText = document.getElementById("btn-audio-text");
        const btnIcon = document.getElementById("btn-audio-icon");
        const pulseDot = document.getElementById("audio-pulse-dot");
        const statusLabel = document.getElementById("audio-status-label");
        const interimBox = document.getElementById("audio-interim-text");
        const speakingDot = document.getElementById("audio-speaking-indicator");

        if (recording) {
            btn.style.backgroundColor = "#dc2626";
            btn.style.borderColor = "#ef4444";
            btn.style.color = "#ffffff";
            btn.classList.add("animate-pulse");
            btnText.textContent = "Parar";
            btnText.style.color = "#ffffff";
            btnIcon.className = "fas fa-stop";
            btnIcon.style.color = "#ffffff";

            pulseDot.style.backgroundColor = "#10b981";
            pulseDot.className = "w-2 h-2 rounded-full inline-block animate-ping";
            statusLabel.textContent = "Gravando & Ouvindo...";
            statusLabel.style.color = "#a7f3d0";
            statusLabel.style.fontWeight = "bold";
            interimBox.textContent = "Ouvindo microfone... Fale algo!";
            interimBox.style.color = "#34d399";
        } else {
            btn.style.backgroundColor = "#10b981";
            btn.style.borderColor = "#34d399";
            btn.style.color = "#ffffff";
            btn.classList.remove("animate-pulse");
            btnText.textContent = "Gravar";
            btnText.style.color = "#ffffff";
            btnIcon.className = "fas fa-play";
            btnIcon.style.color = "#ffffff";

            pulseDot.style.backgroundColor = "#9ca3af";
            pulseDot.className = "w-2 h-2 rounded-full inline-block";
            statusLabel.textContent = "Microfone Desligado";
            statusLabel.style.color = "#d1fae5";
            statusLabel.style.fontWeight = "normal";
            interimBox.textContent = "Clique em \"Gravar\" e fale no microfone...";
            interimBox.style.color = "#94a3b8";
            speakingDot.classList.add("hidden");
        }
    }

    function clearAudioTranscripts() {
        const stream = document.getElementById("audio-transcripts-stream");
        stream.innerHTML = `
            <div id="audio-empty-placeholder" class="flex flex-col items-center justify-center h-full py-10" style="color: #64748b;">
                <i class="fas fa-broadcast-tower text-3xl mb-2" style="color: #475569;"></i>
                <p class="text-xs text-center" style="color: #94a3b8;">As frases faladas aparecerão aqui em tempo real.</p>
                <p class="text-[11px] mt-1.5 font-semibold" style="color: #34d399;">Exemplo: <em>"Ficou para Claudia"</em></p>
            </div>
        `;
        audioSentencesCount = 0;
        document.getElementById("audio-sentences-count").textContent = "0 frases";
    }

    function handleFinalTranscribedSentence(sentence) {
        if (!sentence || sentence.trim().length === 0) return;

        const cleanSentence = sentence.trim();
        const lower = cleanSentence.toLowerCase();

        // Gatilhos de Venda Prioritários: "saiu para", "saiu pra", "foi para", "foi pra", e variações
        const matchTriggers = [
            'saiu para', 'saiu pra', 'saiu pro',
            'foi para', 'foi pra', 'foi pro',
            'ficou para', 'ficou pra', 'ficou pro',
            'vai para', 'vai pra', 'vai pro',
            'vendido para', 'vendido pra', 'vendida para', 'vendida pra',
            'marca para', 'marca pra', 'anota para', 'anota pra'
        ];

        let hasMatch = false;
        let triggerFound = '';
        let detectedTarget = '';

        for (let t of matchTriggers) {
            const idx = lower.indexOf(t);
            if (idx !== -1) {
                hasMatch = true;
                triggerFound = t;
                // Extrai o nome/texto após o gatilho
                const after = cleanSentence.substring(idx + t.length).trim();
                detectedTarget = after.replace(/^(?:a|o|as|os|da|do|de|uma|um)\s+/i, '').replace(/[.,\/#!$%\^&\*;:{}=\-_`~()?!]/g, '').trim();
                break;
            }
        }

        // Se não tiver o gatilho, ignora a fala comum
        if (!hasMatch) {
            console.log("[Voz Live - Ignorado (sem gatilho)]:", cleanSentence);
            return;
        }

        const stream = document.getElementById("audio-transcripts-stream");
        const emptyPlaceholder = document.getElementById("audio-empty-placeholder");
        if (emptyPlaceholder) {
            emptyPlaceholder.remove();
        }

        audioSentencesCount++;
        document.getElementById("audio-sentences-count").textContent = `${audioSentencesCount} venda${audioSentencesCount > 1 ? 's' : ''}`;

        const time = new Date().toLocaleTimeString();

        // Destacar o gatilho ("foi para", "foi pra") dentro da frase
        let formattedSentence = escapeHtml(cleanSentence);
        if (triggerFound) {
            const regExp = new RegExp(`(${triggerFound})`, 'gi');
            formattedSentence = formattedSentence.replace(regExp, '<span style="color: #34d399; font-weight: 800; text-decoration: underline;">$1</span>');
        }

        // Criar card de venda detectada com a frase inteira
        const card = document.createElement("div");
        card.style.padding = "12px";
        card.style.borderRadius = "12px";
        card.style.marginBottom = "10px";
        card.style.backgroundColor = "#064e3b";
        card.style.border = "1.5px solid #10b981";
        card.style.boxShadow = "0 4px 12px rgba(16, 185, 129, 0.25)";
        card.style.transition = "all 0.2s ease";

        let searchButtonHtml = '';
        if (detectedTarget && detectedTarget.length >= 2) {
            searchButtonHtml = `
                <button type="button" onclick="triggerVoiceQuickSearch('${escapeHtml(detectedTarget)}')" style="background-color: #10b981; color: #022c22; font-weight: 800; font-size: 11px; padding: 5px 12px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; gap: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.2); shrink-0;">
                    <i class="fas fa-search"></i> Buscar Cliente
                </button>
            `;
        }

        card.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 10px; color: #a7f3d0; margin-bottom: 6px; font-family: monospace;">
                <span style="display: flex; align-items: center; gap: 5px; color: #34d399; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    <i class="fas fa-check-circle text-emerald-400"></i> Venda Detectada
                </span>
                <span style="color: #94a3b8;">${time}</span>
            </div>
            
            <p style="font-size: 14px; color: #f8fafc; line-height: 1.5; margin: 0 0 10px 0; font-weight: 500;">
                "${formattedSentence}"
            </p>

            <div style="padding-top: 8px; border-top: 1px solid rgba(16,185,129,0.3); display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 6px; overflow: hidden;">
                    <span style="font-size: 13px; font-weight: 800; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        👤 ${detectedTarget ? escapeHtml(detectedTarget) : 'Cliente'}
                    </span>
                </div>
                ${searchButtonHtml}
            </div>
        `;

        stream.appendChild(card);
        stream.scrollTop = stream.scrollHeight;
    }

    // Atalho quando clica no botão "Buscar" do áudio detectado
    function triggerVoiceQuickSearch(targetName) {
        switchTab('online');
        const input = document.getElementById("avulso-search-input");
        if (input) {
            input.value = targetName;
            input.focus();
            searchAvulsoClients(targetName);
        }
    }
</script>
@endpush

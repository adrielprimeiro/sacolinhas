@extends('layouts.app')

@section('title', 'Comentários dos Cortes & Shorts')

@section('content')
<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

    <!-- Topo: Título, Métricas Rápidas e Botão de Sincronização -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-red-600 to-pink-600 text-white flex items-center justify-center text-xl shadow-md">
                    <i class="fas fa-comments"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-gray-900 dark:text-gray-100 tracking-tight">
                        Comentários dos Cortes & Shorts
                    </h1>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Gerencie pedidos de <strong>"QUERO"</strong> e comentários dos vídeos publicados nas redes sociais, vinculados às suas peças.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <button type="button" onclick="syncComments()" id="sync-btn" class="px-4 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-bold text-xs sm:text-sm shadow-md transition flex items-center gap-2 cursor-pointer">
                <i class="fas fa-sync-alt" id="sync-icon"></i>
                <span id="sync-text">Sincronizar Comentários</span>
            </button>
            <a href="{{ route('admin.lives.cortes.social') }}" class="px-4 py-2.5 rounded-2xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-bold text-xs sm:text-sm transition flex items-center gap-2">
                <i class="fas fa-film text-pink-500"></i>
                <span>Ver Cortes</span>
            </a>
        </div>
    </div>

    <!-- Cards de Métricas e Intenções de Compra -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-200 dark:border-gray-700 shadow-xs">
            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400 text-xs font-bold">
                <span>Total de Comentários</span>
                <i class="fas fa-comment-dots text-indigo-500 text-base"></i>
            </div>
            <div class="text-2xl font-black text-gray-900 dark:text-gray-100 mt-1">
                {{ number_format($stats['total_all']) }}
            </div>
            <p class="text-[11px] text-gray-400 mt-0.5">Em todas as redes ativas</p>
        </div>

        <div class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950/30 dark:to-orange-950/30 rounded-2xl p-4 border border-amber-200 dark:border-amber-800 shadow-xs">
            <div class="flex items-center justify-between text-amber-800 dark:text-amber-300 text-xs font-bold">
                <span>Pedidos "QUERO"</span>
                <i class="fas fa-fire text-amber-500 text-base"></i>
            </div>
            <div class="text-2xl font-black text-amber-900 dark:text-amber-200 mt-1">
                {{ number_format($stats['total_intent']) }}
            </div>
            <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-0.5">Intenções de compra detectadas</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-200 dark:border-gray-700 shadow-xs">
            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400 text-xs font-bold">
                <span>Aguardando Resposta</span>
                <i class="fas fa-clock text-amber-500 text-base"></i>
            </div>
            <div class="text-2xl font-black {{ $stats['total_pending_reply'] > 0 ? 'text-amber-600' : 'text-gray-700 dark:text-gray-300' }} mt-1">
                {{ number_format($stats['total_pending_reply']) }}
            </div>
            <p class="text-[11px] text-gray-400 mt-0.5">Clientes sem resposta no vídeo</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-200 dark:border-gray-700 shadow-xs">
            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400 text-xs font-bold">
                <span>Respostas Enviadas</span>
                <i class="fas fa-check-double text-emerald-500 text-base"></i>
            </div>
            <div class="text-2xl font-black text-emerald-700 dark:text-emerald-400 mt-1">
                {{ number_format($stats['total_replied']) }}
            </div>
            <p class="text-[11px] text-gray-400 mt-0.5">Links de WhatsApp enviados</p>
        </div>
    </div>

    <!-- SELETOR DAS REDES SOCIAIS (TABS GRANDES E INTERATIVAS) -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-3 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-2 pt-1 pb-2">
            Selecione a Rede Social para Visualizar:
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
            <!-- Todas as Redes -->
            <a href="{{ request()->fullUrlWithQuery(['platform' => 'all', 'page' => 1]) }}"
               class="p-3.5 rounded-2xl border transition flex items-center justify-between {{ $platform === 'all' ? 'bg-indigo-600 text-white border-indigo-600 shadow-md ring-2 ring-indigo-300 dark:ring-indigo-800' : 'bg-gray-50 dark:bg-gray-750 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl {{ $platform === 'all' ? 'bg-white/20 text-white' : 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/60 dark:text-indigo-400' }} flex items-center justify-center text-base shrink-0 font-bold">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div>
                        <div class="text-xs sm:text-sm font-black leading-tight">Todas as Redes</div>
                        <div class="text-[10px] {{ $platform === 'all' ? 'text-indigo-100' : 'text-gray-400' }}">Visão Geral</div>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-xl text-xs font-black {{ $platform === 'all' ? 'bg-white text-indigo-700' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200' }}">
                    {{ $stats['total_all'] }}
                </span>
            </a>

            <!-- YouTube Shorts -->
            <a href="{{ request()->fullUrlWithQuery(['platform' => 'youtube', 'page' => 1]) }}"
               class="p-3.5 rounded-2xl border transition flex items-center justify-between {{ $platform === 'youtube' ? 'bg-red-600 text-white border-red-600 shadow-md ring-2 ring-red-300 dark:ring-red-800' : 'bg-gray-50 dark:bg-gray-750 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-700 hover:bg-red-50/50 dark:hover:bg-red-950/20' }}">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl {{ $platform === 'youtube' ? 'bg-white/20 text-white' : 'bg-red-100 text-red-600 dark:bg-red-900/60 dark:text-red-400' }} flex items-center justify-center text-base shrink-0 font-bold">
                        <i class="fab fa-youtube"></i>
                    </div>
                    <div>
                        <div class="text-xs sm:text-sm font-black leading-tight">YouTube Shorts</div>
                        <div class="text-[10px] {{ $platform === 'youtube' ? 'text-red-100' : 'text-gray-400' }}">Canal Minha Mania</div>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-xl text-xs font-black {{ $platform === 'youtube' ? 'bg-white text-red-700' : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' }}">
                    {{ $stats['total_youtube'] }}
                </span>
            </a>

            <!-- Instagram Reels -->
            <a href="{{ request()->fullUrlWithQuery(['platform' => 'instagram', 'page' => 1]) }}"
               class="p-3.5 rounded-2xl border transition flex items-center justify-between {{ $platform === 'instagram' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white border-pink-600 shadow-md ring-2 ring-pink-300 dark:ring-pink-800' : 'bg-gray-50 dark:bg-gray-750 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-700 hover:bg-pink-50/50 dark:hover:bg-pink-950/20' }}">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl {{ $platform === 'instagram' ? 'bg-white/20 text-white' : 'bg-pink-100 text-pink-600 dark:bg-pink-900/60 dark:text-pink-400' }} flex items-center justify-center text-base shrink-0 font-bold">
                        <i class="fab fa-instagram"></i>
                    </div>
                    <div>
                        <div class="text-xs sm:text-sm font-black leading-tight">Instagram Reels</div>
                        <div class="text-[10px] {{ $platform === 'instagram' ? 'text-pink-100' : 'text-gray-400' }}">@de_minha_mania</div>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-xl text-xs font-black {{ $platform === 'instagram' ? 'bg-white text-pink-700' : 'bg-pink-100 text-pink-800 dark:bg-pink-900/40 dark:text-pink-300' }}">
                    {{ $stats['total_instagram'] }}
                </span>
            </a>

            <!-- TikTok -->
            <a href="{{ request()->fullUrlWithQuery(['platform' => 'tiktok', 'page' => 1]) }}"
               class="p-3.5 rounded-2xl border transition flex items-center justify-between {{ $platform === 'tiktok' ? 'bg-black text-white border-black shadow-md ring-2 ring-gray-400' : 'bg-gray-50 dark:bg-gray-750 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl {{ $platform === 'tiktok' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }} flex items-center justify-center text-base shrink-0 font-bold">
                        <i class="fab fa-tiktok"></i>
                    </div>
                    <div>
                        <div class="text-xs sm:text-sm font-black leading-tight">TikTok</div>
                        <div class="text-[10px] {{ $platform === 'tiktok' ? 'text-gray-200' : 'text-gray-400' }}">Vídeos Curtos</div>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-xl text-xs font-black {{ $platform === 'tiktok' ? 'bg-white text-black' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200' }}">
                    {{ $stats['total_tiktok'] }}
                </span>
            </a>
        </div>
    </div>

    <!-- Barra de Filtros Secundários, Busca e Filtro por Live -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
        <!-- Pílulas de filtro -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ request()->fullUrlWithQuery(['filter' => 'all', 'page' => 1]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $filter === 'all' ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 shadow-xs' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                Todos
            </a>
            <a href="{{ request()->fullUrlWithQuery(['filter' => 'intent', 'page' => 1]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $filter === 'intent' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800 hover:bg-amber-100' }}">
                <i class="fas fa-fire text-amber-500"></i>
                <span>Apenas Pedidos "QUERO" ({{ $stats['total_intent'] }})</span>
            </a>
            <a href="{{ request()->fullUrlWithQuery(['filter' => 'pending_reply', 'page' => 1]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $filter === 'pending_reply' ? 'bg-orange-600 text-white shadow-xs' : 'bg-orange-50 text-orange-800 dark:bg-orange-950/40 dark:text-orange-300 border border-orange-200 dark:border-orange-800 hover:bg-orange-100' }}">
                <i class="fas fa-clock text-orange-500"></i>
                <span>Aguardando Resposta ({{ $stats['total_pending_reply'] }})</span>
            </a>
            <a href="{{ request()->fullUrlWithQuery(['filter' => 'replied', 'page' => 1]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $filter === 'replied' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100' }}">
                <i class="fas fa-check-double text-emerald-500"></i>
                <span>Já Respondidos ({{ $stats['total_replied'] }})</span>
            </a>
        </div>

        <!-- Busca e Seleção de Live -->
        <form method="GET" action="{{ route('admin.lives.cortes.comentarios') }}" class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            <input type="hidden" name="platform" value="{{ $platform }}">
            <input type="hidden" name="filter" value="{{ $filter }}">

            <!-- Dropdown de Lives -->
            <select name="live_id" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl text-xs font-semibold border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500">
                <option value="">Todas as Lives</option>
                @foreach($lives as $l)
                    <option value="{{ $l->id }}" {{ $liveId == $l->id ? 'selected' : '' }}>
                        Live #{{ $l->id }} ({{ $l->data ? \Carbon\Carbon::parse($l->data)->format('d/m/Y') : 'Data n/d' }})
                    </option>
                @endforeach
            </select>

            <!-- Campo de Busca -->
            <div class="relative flex-1 sm:w-64">
                <input type="text" name="search" value="{{ $search }}" placeholder="Buscar cliente, comentário ou #código..." class="w-full pl-8 pr-3 py-1.5 rounded-xl text-xs font-semibold border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500">
                <i class="fas fa-search absolute left-2.5 top-2.5 text-gray-400 text-xs"></i>
            </div>

            @if($search || $liveId)
                <a href="{{ route('admin.lives.cortes.comentarios', ['platform' => $platform, 'filter' => $filter]) }}" class="px-2.5 py-1.5 rounded-xl bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold hover:bg-gray-300" title="Limpar filtros">
                    <i class="fas fa-times"></i>
                </a>
            @endif
        </form>
    </div>

    <!-- LISTAGEM DOS COMENTÁRIOS E PEÇAS / VÍDEOS RELACIONADOS -->
    @if($comments->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-12 text-center border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-400 flex items-center justify-center text-2xl mx-auto mb-3">
                <i class="fas fa-comment-slash"></i>
            </div>
            <h3 class="text-base font-bold text-gray-800 dark:text-gray-200">Nenhum comentário encontrado</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-md mx-auto">
                Não há comentários gravados para os filtros selecionados. Clique em "Sincronizar Comentários" para buscar as interações mais recentes do seu canal e redes.
            </p>
            <button type="button" onclick="syncComments()" class="mt-4 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow transition inline-flex items-center gap-2 cursor-pointer">
                <i class="fas fa-sync-alt"></i> Sincronizar Agora
            </button>
        </div>
    @else
        <div class="space-y-4">
            @foreach($comments as $comment)
                @php
                    $isYT = $comment->platform === 'youtube';
                    $isInsta = $comment->platform === 'instagram';
                    $isTikTok = $comment->platform === 'tiktok';

                    $liveItem = $comment->liveItem;
                    $item = $liveItem?->item;
                    $isSold = $liveItem && (!empty($liveItem->buyer_username) || !empty($liveItem->user_id));

                    // Thumbnail do corte ou imagem padrão
                    $thumbnailUrl = null;
                    if ($liveItem) {
                        $rawThumbs = $liveItem->thumbnail_candidates;
                        $thumbs = is_array($rawThumbs) ? $rawThumbs : (is_string($rawThumbs) ? json_decode($rawThumbs, true) : []);
                        if (is_array($thumbs) && count($thumbs) > 0) {
                            $thumbnailUrl = asset('storage/' . $thumbs[0]);
                        }
                        if (!$thumbnailUrl && !empty($liveItem->video_cut_path)) {
                            // Tentar miniatura com base no nome
                            $thumbName = str_replace('.mp4', '_thumb.jpg', $liveItem->video_cut_filename);
                            $thumbnailUrl = asset('storage/cuts/' . $thumbName);
                        }
                    }

                    // Link externo do vídeo
                    $externalVideoUrl = null;
                    if ($isYT && $comment->video_id) {
                        $externalVideoUrl = "https://youtube.com/shorts/{$comment->video_id}";
                    } elseif ($isInsta && $liveItem?->instagram_url) {
                        $externalVideoUrl = $liveItem->instagram_url;
                    } elseif ($isTikTok && $liveItem?->tiktok_url) {
                        $externalVideoUrl = $liveItem->tiktok_url;
                    }
                @endphp

                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border {{ $comment->is_purchase_intent ? 'border-amber-300 dark:border-amber-700 shadow-md ring-1 ring-amber-200 dark:ring-amber-900/40' : 'border-gray-200 dark:border-gray-700 shadow-xs' }} transition hover:shadow-md">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                        
                        <!-- LADO ESQUERDO: DADOS DO COMENTÁRIO DA CLIENTE (8 Colunas) -->
                        <div class="lg:col-span-7 flex flex-col justify-between space-y-3">
                            <!-- Cabeçalho do Comentário -->
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <!-- Avatar -->
                                    <div class="relative shrink-0">
                                        @if($comment->author_profile_image)
                                            <img src="{{ $comment->author_profile_image }}" alt="{{ $comment->author_name }}" referrerpolicy="no-referrer" class="w-10 h-10 rounded-full object-cover border border-gray-200 dark:border-gray-700 shadow-xs">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 text-white font-black text-xs flex items-center justify-center shadow-xs">
                                                {{ strtoupper(substr($comment->author_name, 0, 2)) }}
                                            </div>
                                        @endif
                                        
                                        <!-- Badge da Plataforma sobre o avatar -->
                                        <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center text-[10px] text-white shadow-xs {{ $isYT ? 'bg-red-600' : ($isInsta ? 'bg-pink-600' : 'bg-black') }}">
                                            <i class="{{ $isYT ? 'fab fa-youtube' : ($isInsta ? 'fab fa-instagram' : 'fab fa-tiktok') }}"></i>
                                        </div>
                                    </div>

                                    <div class="truncate">
                                        <div class="text-xs sm:text-sm font-black text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                            <span class="truncate">{{ $comment->author_name }}</span>
                                            @if($isYT)
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300 shrink-0">YouTube</span>
                                            @elseif($isInsta)
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-pink-100 text-pink-700 dark:bg-pink-950/60 dark:text-pink-300 shrink-0">Instagram</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 shrink-0">TikTok</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-gray-400 dark:text-gray-500 flex items-center gap-2 mt-0.5">
                                            <i class="far fa-clock"></i>
                                            <span>{{ $comment->published_at ? $comment->published_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') : $comment->created_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Selo de Intenção de Compra -->
                                @if($comment->is_purchase_intent)
                                    <div class="shrink-0 px-2.5 py-1 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 text-white font-black text-[11px] shadow-sm flex items-center gap-1.5 animate-pulse">
                                        <i class="fas fa-fire"></i>
                                        <span>QUERO DETECTADO</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Texto do Comentário -->
                            <div class="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-750/70 border border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-bold text-gray-900 dark:text-gray-100 leading-relaxed break-words">
                                    "{!! nl2br(e($comment->comment_text)) !!}"
                                </p>
                                @if($comment->detected_keyword)
                                    <div class="mt-2 text-[11px] font-semibold text-amber-700 dark:text-amber-400 flex items-center gap-1">
                                        <i class="fas fa-tag text-[10px]"></i>
                                        <span>Gatilho de compra identificado: <strong>"{{ $comment->detected_keyword }}"</strong></span>
                                    </div>
                                @endif
                            </div>

                            <!-- Status de Resposta Enviada -->
                            @if($comment->reply_sent)
                                <div class="p-3 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 flex items-start gap-2.5 text-xs">
                                    <i class="fas fa-check-circle text-emerald-600 dark:text-emerald-400 mt-0.5 shrink-0"></i>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between text-emerald-800 dark:text-emerald-300 font-bold">
                                            <span>Resposta Oficial Enviada</span>
                                            <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-normal">
                                                {{ $comment->reply_sent_at ? $comment->reply_sent_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') : '' }}
                                            </span>
                                        </div>
                                        <p class="text-emerald-900 dark:text-emerald-200 mt-1 italic leading-relaxed break-words">
                                            "{{ $comment->reply_text }}"
                                        </p>
                                    </div>
                                </div>
                            @endif

                            <!-- Ações Rápidas do Comentário -->
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <!-- Botão de Resposta no YouTube -->
                                @if($isYT)
                                    <button type="button" onclick="openReplyModal({{ $comment->id }}, '{{ addslashes($comment->author_name) }}', '{{ $comment->reply_sent ? addslashes($comment->reply_text) : '' }}')" class="px-3 py-1.5 rounded-xl bg-red-50 hover:bg-red-100 text-red-700 dark:bg-red-950/40 dark:hover:bg-red-900/60 dark:text-red-300 border border-red-200 dark:border-red-800 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                                        <i class="fab fa-youtube"></i>
                                        <span>{{ $comment->reply_sent ? 'Reenviar Resposta' : 'Responder no YouTube' }}</span>
                                    </button>
                                @endif

                                <!-- Botão de WhatsApp Direto se tiver número -->
                                @php
                                    $buyerUser = $liveItem?->user;
                                    $whatsPhone = $buyerUser?->whatsapp ?: $buyerUser?->phone;
                                @endphp
                                @if($whatsPhone)
                                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsPhone) }}?text={{ urlencode('Olá ' . $comment->author_name . '! Vi seu comentário no vídeo da peça #' . ($liveItem?->codigo_live ?: $item?->codigo) . '!') }}" target="_blank" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                                        <i class="fab fa-whatsapp"></i>
                                        <span>Conversar no WhatsApp</span>
                                    </a>
                                @endif

                                <!-- Botão Arrematar / Vincular Peça -->
                                @if($liveItem && !$isSold)
                                    <button type="button" onclick="openArrematarModal({{ $comment->id }}, '{{ addslashes($comment->author_name) }}', '{{ $liveItem->codigo_live ?: $item?->codigo }}', '{{ addslashes($item?->nome_do_produto ?? '') }}', '{{ $item?->preco ?? 0 }}')" class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                                        <i class="fas fa-shopping-bag"></i>
                                        <span>Colocar na Sacolinha</span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- LADO DIREITO: A PEÇA E O VÍDEO RELACIONADOS (4 Colunas) -->
                        <div class="lg:col-span-5 bg-gray-50 dark:bg-gray-750 rounded-2xl p-4 border border-gray-200 dark:border-gray-700 flex flex-col justify-between space-y-3">
                            <div class="text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center justify-between">
                                <span>Peça & Vídeo do Corte</span>
                                @if($liveItem)
                                    <span class="text-indigo-600 dark:text-indigo-400">Live #{{ $liveItem->live_id }}</span>
                                @endif
                            </div>

                            @if($liveItem && $item)
                                <div class="flex items-start gap-3">
                                    <!-- Miniatura do Vídeo -->
                                    <div class="relative w-20 h-28 rounded-xl bg-gray-900 overflow-hidden shrink-0 border border-gray-300 dark:border-gray-600 shadow-sm flex items-center justify-center">
                                        @if($thumbnailUrl)
                                            <img src="{{ $thumbnailUrl }}" alt="Miniatura da Peça" class="w-full h-full object-cover">
                                        @else
                                            <i class="fas fa-video text-gray-500 text-xl"></i>
                                        @endif
                                        @if($externalVideoUrl)
                                            <a href="{{ $externalVideoUrl }}" target="_blank" class="absolute inset-0 bg-black/30 hover:bg-black/10 flex items-center justify-center text-white transition group" title="Assistir Vídeo">
                                                <i class="fas fa-play-circle text-2xl group-hover:scale-110 transition shadow-sm"></i>
                                            </a>
                                        @endif
                                    </div>

                                    <!-- Detalhes do Produto -->
                                    <div class="flex-1 min-w-0">
                                        <!-- Código da Live e Código do Catálogo -->
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            @if($liveItem->codigo_live)
                                                <span class="px-2 py-0.5 rounded-lg text-xs font-black bg-indigo-600 text-white shadow-xs">
                                                    Live #{{ $liveItem->codigo_live }}
                                                </span>
                                            @endif
                                            @if($item->codigo)
                                                <span class="px-2 py-0.5 rounded-lg text-xs font-black bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                                    Ref: {{ $item->codigo }}
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Nome da Peça -->
                                        <h4 class="text-xs sm:text-sm font-extrabold text-gray-900 dark:text-gray-100 truncate mt-1" title="{{ $item->nome_do_produto }}">
                                            {{ $item->nome_do_produto }}
                                        </h4>

                                        <!-- Atributos: Tamanho, Marca, Valor -->
                                        <div class="text-[11px] text-gray-600 dark:text-gray-300 mt-1 space-y-0.5">
                                            @if($item->tamanho)
                                                <div>Tam: <strong>{{ $item->tamanho }}</strong> {{ $item->marca ? '&bull; ' . $item->marca : '' }}</div>
                                            @endif
                                            <div class="text-sm font-black text-emerald-700 dark:text-emerald-400">
                                                R$ {{ number_format($item->preco, 2, ',', '.') }}
                                            </div>
                                        </div>

                                        <!-- Status da Peça -->
                                        <div class="mt-2">
                                            @if($isSold)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10px] font-black bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300 border border-red-200 dark:border-red-800">
                                                    <i class="fas fa-check"></i>
                                                    <span>Vendido para @ {{ $liveItem->buyer_username }}</span>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                    <i class="fas fa-store"></i>
                                                    <span>Peça Disponível</span>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="py-6 text-center text-gray-400 text-xs">
                                    <i class="fas fa-unlink text-xl mb-1"></i>
                                    <p>Nenhuma peça específica vinculada ao vídeo deste comentário.</p>
                                </div>
                            @endif

                            <!-- Link Externo para Assistir o Corte na Rede -->
                            @if($externalVideoUrl)
                                <a href="{{ $externalVideoUrl }}" target="_blank" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-xs font-bold border border-gray-200 dark:border-gray-600 flex items-center justify-center gap-2 transition shadow-2xs">
                                    <i class="{{ $isYT ? 'fab fa-youtube text-red-600' : ($isInsta ? 'fab fa-instagram text-pink-600' : 'fab fa-tiktok') }}"></i>
                                    <span>Ver Vídeo no {{ $isYT ? 'YouTube Shorts' : ($isInsta ? 'Instagram' : 'TikTok') }}</span>
                                    <i class="fas fa-external-link-alt text-[10px] text-gray-400"></i>
                                </a>
                            @endif
                        </div>

                    </div>
                </div>
            @endforeach

            <!-- Paginação -->
            <div class="pt-4">
                {{ $comments->links() }}
            </div>
        </div>
    @endif

</div>

<!-- MODAL DE RESPOSTA NO YOUTUBE / REDE SOCIAL -->
<div id="reply-modal" class="fixed inset-0 bg-gray-900/75 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-700 max-w-lg w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
            <h3 class="text-base font-black text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <i class="fas fa-reply text-indigo-600"></i>
                <span>Enviar Resposta ao Comentário</span>
            </h3>
            <button onclick="closeReplyModal()" class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <p class="text-xs text-gray-600 dark:text-gray-300">
            Respondendo ao cliente <strong id="modal-reply-author" class="text-indigo-600 dark:text-indigo-400">@cliente</strong>:
        </p>

        <input type="hidden" id="modal-reply-comment-id">

        <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Texto da Resposta:</label>
            <textarea id="modal-reply-text" rows="4" class="w-full p-3 rounded-2xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-xs font-semibold text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 leading-relaxed" placeholder="Deixe em branco para usar a resposta padrão com link automático do WhatsApp..."></textarea>
            <p class="text-[11px] text-gray-400 mt-1">Dica: Se deixar vazio, o sistema montará o texto padrão com o link do WhatsApp já preenchido com a peça.</p>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
            <button type="button" onclick="closeReplyModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer">
                Cancelar
            </button>
            <button type="button" onclick="submitReply()" id="modal-reply-submit-btn" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-paper-plane"></i>
                <span>Enviar Resposta</span>
            </button>
        </div>
    </div>
</div>

<!-- MODAL DE ARREMATAR / VINCULAR PEÇA DO CORTE -->
<div id="arrematar-modal" class="fixed inset-0 bg-gray-900/75 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-700 max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
            <h3 class="text-base font-black text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <i class="fas fa-shopping-bag text-emerald-600"></i>
                <span>Colocar Peça na Sacolinha</span>
            </h3>
            <button onclick="closeArrematarModal()" class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <div class="p-3 bg-indigo-50 dark:bg-indigo-950/40 rounded-2xl border border-indigo-200 dark:border-indigo-800 text-xs">
            <div class="font-black text-indigo-900 dark:text-indigo-200" id="arrematar-item-title">Peça #15 - Vestido Floral</div>
            <div class="text-indigo-700 dark:text-indigo-300 font-bold mt-0.5" id="arrematar-item-price">Valor: R$ 0,00</div>
        </div>

        <input type="hidden" id="arrematar-comment-id">

        <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nome / Usuário da Cliente:</label>
            <input type="text" id="arrematar-username" class="w-full p-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-xs font-semibold text-gray-900 dark:text-gray-100">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Telefone / WhatsApp (opcional para criar sacola):</label>
            <input type="text" id="arrematar-phone" placeholder="(DDD) 99999-9999" class="w-full p-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-xs font-semibold text-gray-900 dark:text-gray-100">
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
            <button type="button" onclick="closeArrematarModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer">
                Cancelar
            </button>
            <button type="button" onclick="submitArrematar()" id="arrematar-submit-btn" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-check"></i>
                <span>Confirmar e Adicionar</span>
            </button>
        </div>
    </div>
</div>

<script>
    // Sincronizar Comentários em Background
    async function syncComments() {
        const btn = document.getElementById('sync-btn');
        const icon = document.getElementById('sync-icon');
        const text = document.getElementById('sync-text');

        btn.disabled = true;
        icon.classList.add('fa-spin');
        text.textContent = 'Sincronizando...';

        try {
            const res = await fetch("{{ route('admin.lives.cortes.comentarios.sync') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                }
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message || 'Comentários sincronizados!');
                window.location.reload();
            } else {
                alert('Aviso: ' + (data.message || 'Erro ao sincronizar.'));
            }
        } catch (e) {
            console.error('Erro na sincronização:', e);
            alert('Falha ao conectar com o servidor para sincronização.');
        } finally {
            btn.disabled = false;
            icon.classList.remove('fa-spin');
            text.textContent = 'Sincronizar Comentários';
        }
    }

    // Modal de Resposta
    function openReplyModal(commentId, authorName, existingText) {
        document.getElementById('modal-reply-comment-id').value = commentId;
        document.getElementById('modal-reply-author').textContent = authorName;
        document.getElementById('modal-reply-text').value = existingText || '';
        document.getElementById('reply-modal').classList.remove('hidden');
    }

    function closeReplyModal() {
        document.getElementById('reply-modal').classList.add('hidden');
    }

    async function submitReply() {
        const commentId = document.getElementById('modal-reply-comment-id').value;
        const text = document.getElementById('modal-reply-text').value;
        const btn = document.getElementById('modal-reply-submit-btn');

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        try {
            const res = await fetch(`/admin/lives/cortes/comentarios/${commentId}/reply`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ reply_text: text })
            });

            const data = await res.json();
            if (data.success) {
                alert(data.message || 'Resposta enviada!');
                window.location.reload();
            } else {
                alert('Erro: ' + (data.message || 'Não foi possível enviar a resposta.'));
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Resposta';
            }
        } catch (e) {
            console.error('Erro ao enviar resposta:', e);
            alert('Falha de conexão ao enviar resposta.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Resposta';
        }
    }

    // Modal de Arrematar Peça
    function openArrematarModal(commentId, authorName, itemCode, itemTitle, itemPrice) {
        document.getElementById('arrematar-comment-id').value = commentId;
        document.getElementById('arrematar-username').value = authorName;
        document.getElementById('arrematar-phone').value = '';
        document.getElementById('arrematar-item-title').textContent = `Peça #${itemCode} - ${itemTitle}`;
        const formattedPrice = parseFloat(itemPrice || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        document.getElementById('arrematar-item-price').textContent = `Valor: ${formattedPrice}`;
        document.getElementById('arrematar-modal').classList.remove('hidden');
    }

    function closeArrematarModal() {
        document.getElementById('arrematar-modal').classList.add('hidden');
    }

    async function submitArrematar() {
        const commentId = document.getElementById('arrematar-comment-id').value;
        const username = document.getElementById('arrematar-username').value;
        const phone = document.getElementById('arrematar-phone').value;
        const btn = document.getElementById('arrematar-submit-btn');

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Vinculando...';

        try {
            const res = await fetch(`/admin/lives/cortes/comentarios/${commentId}/arrematar`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    username: username,
                    phone: phone
                })
            });

            const data = await res.json();
            if (data.success) {
                alert(data.message || 'Peça vinculada com sucesso!');
                window.location.reload();
            } else {
                alert('Erro: ' + (data.message || 'Falha ao vincular peça.'));
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Confirmar e Adicionar';
            }
        } catch (e) {
            console.error('Erro ao vincular peça:', e);
            alert('Falha de comunicação com o servidor.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Confirmar e Adicionar';
        }
    }
</script>
@endsection

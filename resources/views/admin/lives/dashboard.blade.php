@extends('layouts.app')

@section('title', 'Dashboard das Lives - Minha Mania')

@section('content')
<div class="container mx-auto px-3 sm:px-6 py-4 space-y-5 max-w-7xl">

    <!-- Topo / Header & Seletor de Live -->
    <div class="bg-slate-900 text-white p-4 sm:p-5 rounded-3xl shadow-xl border border-slate-800 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 text-white flex items-center justify-center text-xl shadow-lg shrink-0">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-lg sm:text-xl font-black text-white tracking-tight">Dashboard de Performance de Lives</h1>
                    @if($selectedLive)
                        @if($selectedLive->ativo)
                            <span class="bg-red-600 text-white text-[10px] font-black px-2.5 py-0.5 rounded-full flex items-center gap-1 shadow animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span> AO VIVO
                            </span>
                        @else
                            <span class="bg-slate-800 text-slate-300 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-slate-700">
                                Transmissão Concluída
                            </span>
                        @endif
                    @endif
                </div>
                <p class="text-xs text-slate-300 mt-0.5">
                    @if($selectedLive)
                        Live #{{ $selectedLive->id }} &bull; {{ $selectedLive->tipo_live_formatado }} &bull; Data: {{ $selectedLive->data ? $selectedLive->data->format('d/m/Y') : '' }} &bull; Duração: {{ $kpis['duracao_formatada'] ?? 'N/A' }}
                    @else
                        Selecione uma transmissão para visualizar o relatório analítico.
                    @endif
                </p>
            </div>
        </div>

        <!-- Seletor de Live & Atalhos -->
        <div class="flex flex-wrap items-center gap-2">
            @if($selectedLive)
                <a href="{{ route('admin.live-chat.feed', ['live_id' => $selectedLive->id]) }}" class="bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold text-xs px-3.5 py-2 rounded-xl transition shadow flex items-center gap-1.5 active:scale-95 cursor-pointer">
                    <i class="fas fa-comments text-indigo-200"></i> Ver Chat
                </a>
                <a href="{{ route('admin.lives.cortes', ['liveId' => $selectedLive->id]) }}" class="bg-slate-800 hover:bg-slate-700 text-pink-300 font-extrabold text-xs px-3 py-2 rounded-xl border border-slate-700 transition flex items-center gap-1.5 cursor-pointer shadow-sm">
                    <i class="fas fa-film text-pink-400"></i> Cortes
                </a>
            @endif

            <form action="{{ route('admin.lives.dashboard') }}" method="GET" class="m-0">
                <select name="live_id" onchange="this.form.submit()" class="text-xs font-bold rounded-xl border-2 border-slate-700 bg-slate-800 text-white focus:border-emerald-500 focus:ring-0 p-2 px-3 cursor-pointer shadow-inner">
                    @foreach($livesList as $l)
                        <option value="{{ $l->id }}" {{ ($selectedLive && $selectedLive->id === $l->id) ? 'selected' : '' }}>
                            #{{ $l->id }} - {{ $l->tipo_live_formatado }} ({{ $l->data ? $l->data->format('d/m/Y') : '' }}) {{ $l->ativo ? '[AO VIVO]' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    @if(!$selectedLive)
        <div class="bg-white rounded-3xl p-12 text-center border border-slate-200 shadow-sm">
            <i class="fas fa-broadcast-tower text-4xl text-slate-300 mb-3"></i>
            <h3 class="text-base font-black text-slate-800">Nenhuma Live encontrada</h3>
            <p class="text-xs text-slate-500 mt-1">Crie ou abra uma nova transmissão para visualizar o dashboard de métricas.</p>
        </div>
    @else

        <!-- 6 CARDS DE KPIS PRINCIPAIS -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
            <!-- 1. Faturamento Total -->
            <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Faturamento</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-black">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <strong class="text-lg sm:text-xl font-black text-slate-900 block tracking-tight">
                        R$ {{ number_format($kpis['faturamento'], 2, ',', '.') }}
                    </strong>
                    <span class="text-[11px] font-bold text-emerald-700 mt-0.5 block">
                        R$ {{ number_format($kpis['preco_medio_peca'], 2, ',', '.') }} / peça
                    </span>
                </div>
            </div>

            <!-- 2. Peças Sacoladas -->
            <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Peças Vendidas</span>
                    <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-black">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <strong class="text-lg sm:text-xl font-black text-slate-900 block tracking-tight">
                        {{ $kpis['total_pecas_sacoladas'] }} <span class="text-xs text-slate-500 font-semibold">unid.</span>
                    </strong>
                    <span class="text-[11px] font-bold text-indigo-700 mt-0.5 block">
                        {{ $kpis['pecas_por_cliente'] }} peças / cliente
                    </span>
                </div>
            </div>

            <!-- 3. Clientes Compradoras -->
            <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Compradoras</span>
                    <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-sm font-black">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <strong class="text-lg sm:text-xl font-black text-slate-900 block tracking-tight">
                        {{ $kpis['total_compradoras'] }} <span class="text-xs text-slate-500 font-semibold">clientes</span>
                    </strong>
                    <span class="text-[11px] font-bold text-purple-700 mt-0.5 block">
                        {{ $kpis['total_participantes_chat'] > 0 ? round(($kpis['total_compradoras'] / $kpis['total_participantes_chat']) * 100, 1) : 0 }}% do chat comprou
                    </span>
                </div>
            </div>

            <!-- 4. Ticket Médio -->
            <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Ticket Médio</span>
                    <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-sm font-black">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <strong class="text-lg sm:text-xl font-black text-slate-900 block tracking-tight">
                        R$ {{ number_format($kpis['ticket_medio'], 2, ',', '.') }}
                    </strong>
                    <span class="text-[11px] font-bold text-slate-600 mt-0.5 block">
                        Por cliente compradora
                    </span>
                </div>
            </div>

            <!-- 5. Peças Bipadas vs Vendidas -->
            <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Conversão Peças</span>
                    <div class="w-8 h-8 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center text-sm font-black">
                        <i class="fas fa-barcode"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <strong class="text-lg sm:text-xl font-black text-slate-900 block tracking-tight">
                        {{ $kpis['taxa_conversao_itens'] }}%
                    </strong>
                    <span class="text-[11px] font-bold text-teal-800 mt-0.5 block">
                        {{ $kpis['total_pecas_sacoladas'] }} de {{ $kpis['total_itens_apresentados'] }} bipadas
                    </span>
                </div>
            </div>

            <!-- 6. Engajamento no Chat -->
            <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Mensagens Chat</span>
                    <div class="w-8 h-8 rounded-xl bg-pink-100 text-pink-700 flex items-center justify-center text-sm font-black">
                        <i class="fas fa-comments"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <strong class="text-lg sm:text-xl font-black text-slate-900 block tracking-tight">
                        {{ number_format($kpis['total_mensagens'], 0, '', '.') }} <span class="text-xs text-slate-500 font-semibold">msgs</span>
                    </strong>
                    <span class="text-[11px] font-bold text-pink-700 mt-0.5 block">
                        {{ $kpis['total_participantes_chat'] }} participantes
                    </span>
                </div>
            </div>
        </div>

        <!-- DIVISÃO POR CANAIS (INSTAGRAM VS TIKTOK) & STATUS DAS SACOLAS -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Canal / Plataforma Chat -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
                <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                    <i class="fas fa-share-alt text-indigo-500"></i> Divisão de Mensagens por Plataforma
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Engajamento comparativo entre as transmissões do Instagram e TikTok</p>

                <div class="mt-4 space-y-3">
                    <!-- Barra Visual Proporcional -->
                    <div class="w-full bg-slate-100 rounded-full h-3.5 flex overflow-hidden border border-slate-200">
                        <div class="bg-gradient-to-r from-purple-600 to-pink-600 h-full transition-all duration-500" style="width: {{ $channelStats['instagram_share'] }}%" title="Instagram: {{ $channelStats['instagram_messages'] }} msgs"></div>
                        <div class="bg-slate-900 h-full transition-all duration-500" style="width: {{ $channelStats['tiktok_share'] }}%" title="TikTok: {{ $channelStats['tiktok_messages'] }} msgs"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <!-- Card Instagram -->
                        <div class="p-3 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-50 border border-pink-200">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-pink-800 flex items-center gap-1.5">
                                    <i class="fab fa-instagram text-pink-600 text-sm"></i> Instagram
                                </span>
                                <span class="text-xs font-extrabold text-pink-900">{{ $channelStats['instagram_share'] }}%</span>
                            </div>
                            <strong class="text-lg font-black text-slate-900 mt-1 block">{{ $channelStats['instagram_messages'] }} <span class="text-xs font-semibold text-slate-500">mensagens</span></strong>
                        </div>

                        <!-- Card TikTok -->
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-slate-900 flex items-center gap-1.5">
                                    <i class="fab fa-tiktok text-slate-800 text-sm"></i> TikTok
                                </span>
                                <span class="text-xs font-extrabold text-slate-900">{{ $channelStats['tiktok_share'] }}%</span>
                            </div>
                            <strong class="text-lg font-black text-slate-900 mt-1 block">{{ $channelStats['tiktok_messages'] }} <span class="text-xs font-semibold text-slate-500">mensagens</span></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status do Processamento das Sacolinhas -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                        <i class="fas fa-tasks text-emerald-600"></i> Status das Sacolinhas da Live
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Situação dos pedidos gerados nesta transmissão</p>

                    <div class="grid grid-cols-3 gap-2.5 mt-4">
                        <div class="p-3 rounded-2xl bg-indigo-50 border border-indigo-200 text-center">
                            <span class="text-[10px] font-black uppercase text-indigo-700 block">Abertas</span>
                            <strong class="text-base font-black text-indigo-950 mt-0.5 block">
                                {{ $statusCounts['aberta']->count ?? 0 }}
                            </strong>
                            <small class="text-[10px] font-bold text-indigo-600">R$ {{ number_format($statusCounts['aberta']->total_val ?? 0, 2, ',', '.') }}</small>
                        </div>

                        <div class="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-center">
                            <span class="text-[10px] font-black uppercase text-emerald-700 block">Pagas / Fechadas</span>
                            <strong class="text-base font-black text-emerald-950 mt-0.5 block">
                                {{ ($statusCounts['fechada']->count ?? 0) + ($statusCounts['paga']->count ?? 0) }}
                            </strong>
                            <small class="text-[10px] font-bold text-emerald-700">R$ {{ number_format(($statusCounts['fechada']->total_val ?? 0) + ($statusCounts['paga']->total_val ?? 0), 2, ',', '.') }}</small>
                        </div>

                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 text-center">
                            <span class="text-[10px] font-black uppercase text-slate-600 block">Enviadas</span>
                            <strong class="text-base font-black text-slate-900 mt-0.5 block">
                                {{ $statusCounts['enviada']->count ?? 0 }}
                            </strong>
                            <small class="text-[10px] font-bold text-slate-500">R$ {{ number_format($statusCounts['enviada']->total_val ?? 0, 2, ',', '.') }}</small>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600 mt-3">
                    <span>Taxa de conclusão da live:</span>
                    <strong class="text-emerald-700 font-black">
                        {{ $kpis['faturamento'] > 0 ? round(((($statusCounts['fechada']->total_val ?? 0) + ($statusCounts['paga']->total_val ?? 0) + ($statusCounts['enviada']->total_val ?? 0)) / $kpis['faturamento']) * 100, 1) : 0 }}% faturado
                    </strong>
                </div>
            </div>
        </div>

        <!-- DUAS COLUNAS: TOP COMPRADORAS & PEÇAS SACOLADAS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <!-- Ranking: Maiores Compradoras -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                <div class="p-4 sm:p-5 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm sm:text-base font-black text-slate-900 flex items-center gap-2">
                            <i class="fas fa-crown text-amber-500"></i> Top Compradoras da Live
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Ranking das clientes que mais investiram nesta live</p>
                    </div>
                    <span class="text-xs font-black text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-xl border border-indigo-200">
                        {{ $topBuyers->count() }} clientes
                    </span>
                </div>

                <div class="divide-y divide-slate-100 max-h-[460px] overflow-y-auto">
                    @forelse($topBuyers as $index => $buyer)
                        <div class="p-3.5 sm:p-4 hover:bg-slate-50 transition flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-xs shrink-0 shadow-sm {{ $index === 0 ? 'bg-amber-400 text-amber-950 font-black ring-2 ring-amber-300' : ($index === 1 ? 'bg-slate-300 text-slate-800' : ($index === 2 ? 'bg-amber-700 text-white' : 'bg-slate-100 text-slate-700')) }}">
                                    {{ $index + 1 }}º
                                </div>
                                <div class="min-w-0">
                                    <strong class="text-xs sm:text-sm font-black text-slate-900 block truncate">
                                        {{ $buyer->client_name }}
                                    </strong>
                                    <div class="text-[11px] text-slate-500 flex items-center gap-2 truncate mt-0.5">
                                        @if($buyer->instagram)
                                            <span class="text-pink-700 font-semibold"><i class="fab fa-instagram"></i> {{ $buyer->instagram }}</span>
                                        @endif
                                        @if($buyer->tiktok)
                                            <span class="text-slate-800 font-semibold"><i class="fab fa-tiktok"></i> {{ $buyer->tiktok }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                <div class="text-right">
                                    <strong class="text-xs sm:text-sm font-black text-emerald-700 block">
                                        R$ {{ number_format($buyer->total_spent, 2, ',', '.') }}
                                    </strong>
                                    <span class="text-[10px] font-bold text-slate-500 block">
                                        {{ $buyer->total_items }} {{ $buyer->total_items == 1 ? 'peça' : 'peças' }}
                                    </span>
                                </div>

                                @if(!empty($buyer->clean_phone))
                                    <a href="https://wa.me/55{{ $buyer->clean_phone }}" target="_blank" class="w-8 h-8 rounded-xl bg-emerald-100 hover:bg-emerald-200 text-emerald-800 flex items-center justify-center transition shadow-xs" title="Conversar no WhatsApp">
                                        <i class="fab fa-whatsapp text-sm"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-slate-400">
                            <i class="fas fa-shopping-basket text-3xl mb-2 text-slate-300"></i>
                            <p class="text-xs font-bold text-slate-500">Nenhuma compra registrada nesta live.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Tabela: Peças Sacoladas na Live -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                <div class="p-4 sm:p-5 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm sm:text-base font-black text-slate-900 flex items-center gap-2">
                            <i class="fas fa-tags text-indigo-500"></i> Peças Sacoladas na Live
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Últimos itens bipados e adicionados às sacolinhas</p>
                    </div>
                    <span class="text-xs font-black text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-xl border border-emerald-200">
                        {{ $baggedItems->count() }} itens
                    </span>
                </div>

                <div class="divide-y divide-slate-100 max-h-[460px] overflow-y-auto">
                    @forelse($baggedItems as $item)
                        <div class="p-3.5 sm:p-4 hover:bg-slate-50 transition flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-[11px] font-black font-mono text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-200">
                                        #{{ $item->item_code }}
                                    </span>
                                    <strong class="text-xs font-bold text-slate-900 truncate">
                                        {{ $item->item_name }}
                                    </strong>
                                </div>
                                <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-2 flex-wrap">
                                    @if($item->item_size)
                                        <span class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-bold text-[10px]">Tam: {{ $item->item_size }}</span>
                                    @endif
                                    @if($item->item_brand)
                                        <span class="text-slate-600 font-semibold">{{ $item->item_brand }}</span>
                                    @endif
                                    <span class="text-slate-400">&bull;</span>
                                    <span class="text-slate-700 font-semibold">Cliente: <strong>{{ $item->buyer_name }}</strong></span>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <strong class="text-xs sm:text-sm font-black text-slate-900 block">
                                    R$ {{ number_format($item->sacolinha_price, 2, ',', '.') }}
                                </strong>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md inline-block mt-0.5 {{ $item->sacolinha_status === 'fechada' || $item->sacolinha_status === 'paga' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ ucfirst($item->sacolinha_status) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-slate-400">
                            <i class="fas fa-barcode text-3xl mb-2 text-slate-300"></i>
                            <p class="text-xs font-bold text-slate-500">Nenhum item sacolado nesta live ainda.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- TABELA: HISTÓRICO COMPARATIVO DAS ÚLTIMAS 15 LIVES -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm sm:text-base font-black text-slate-900 flex items-center gap-2">
                        <i class="fas fa-history text-indigo-600"></i> Comparativo Histórico de Lives Recentes
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Visão consolidada das últimas 15 transmissões realizadas</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-black uppercase text-[10px] tracking-wider">
                            <th class="p-3 pl-4">Live</th>
                            <th class="p-3">Data</th>
                            <th class="p-3">Tipo</th>
                            <th class="p-3">Duração</th>
                            <th class="p-3 text-right">Faturamento</th>
                            <th class="p-3 text-center">Peças</th>
                            <th class="p-3 text-center">Clientes</th>
                            <th class="p-3 text-right">Ticket Médio</th>
                            <th class="p-3 text-center">Itens Apres.</th>
                            <th class="p-3 text-center">Conversão</th>
                            <th class="p-3 text-center">Msgs Chat</th>
                            <th class="p-3 pr-4 text-center">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                        @foreach($recentLivesComparison as $rl)
                            <tr class="hover:bg-slate-50/80 transition {{ ($selectedLive && $selectedLive->id === $rl->id) ? 'bg-indigo-50/40 font-bold' : '' }}">
                                <td class="p-3 pl-4 font-black text-slate-900">
                                    <span class="flex items-center gap-1.5">
                                        #{{ $rl->id }}
                                        @if($rl->ativo)
                                            <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse" title="Ao Vivo"></span>
                                        @endif
                                    </span>
                                </td>
                                <td class="p-3 font-semibold text-slate-600 whitespace-nowrap">{{ $rl->data }}</td>
                                <td class="p-3">
                                    <span class="bg-slate-100 text-slate-800 px-2 py-0.5 rounded-lg text-[10px] font-bold border border-slate-200 whitespace-nowrap">
                                        {{ $rl->tipo_formatado }}
                                    </span>
                                </td>
                                <td class="p-3 text-slate-600 whitespace-nowrap">{{ $rl->duracao }}</td>
                                <td class="p-3 text-right font-black text-emerald-700 whitespace-nowrap">
                                    R$ {{ number_format($rl->faturamento, 2, ',', '.') }}
                                </td>
                                <td class="p-3 text-center font-bold text-slate-900">{{ $rl->pecas_vendidas }}</td>
                                <td class="p-3 text-center font-bold text-indigo-700">{{ $rl->compradoras }}</td>
                                <td class="p-3 text-right font-semibold text-slate-700 whitespace-nowrap">
                                    R$ {{ number_format($rl->ticket_medio, 2, ',', '.') }}
                                </td>
                                <td class="p-3 text-center text-slate-600">{{ $rl->itens_apresentados ?: '-' }}</td>
                                <td class="p-3 text-center">
                                    @if($rl->conversao > 0)
                                        <span class="text-teal-800 font-bold bg-teal-50 px-1.5 py-0.5 rounded text-[10px]">{{ $rl->conversao }}%</span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center font-semibold text-slate-600">{{ number_format($rl->mensagens, 0, '', '.') }}</td>
                                <td class="p-3 pr-4 text-center whitespace-nowrap">
                                    <a href="{{ route('admin.lives.dashboard', ['live_id' => $rl->id]) }}" class="bg-slate-100 hover:bg-indigo-600 hover:text-white text-slate-700 font-bold text-[11px] px-2.5 py-1 rounded-lg transition border border-slate-200 inline-flex items-center gap-1 cursor-pointer">
                                        <span>Analisar</span> <i class="fas fa-chevron-right text-[9px]"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @endif

</div>
@endsection

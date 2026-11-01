@extends('layouts.app')

@section('title', 'Dashboard das Lives - Minha Mania')

@section('content')
<div class="container mx-auto px-3 sm:px-6 py-4 space-y-5 max-w-7xl">

    <!-- Topo / Header & Seletor de Live -->
    <div style="background-color: #0f172a !important; color: #ffffff !important; border: 1px solid #1e293b;" class="p-4 sm:p-5 rounded-3xl shadow-xl flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div style="background: linear-gradient(135deg, #059669, #0d9488); color: #ffffff;" class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-lg shrink-0">
                <i class="fas fa-chart-line" style="color: #ffffff !important;"></i>
            </div>
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 style="color: #ffffff !important;" class="text-lg sm:text-xl font-black tracking-tight">Dashboard de Performance de Lives</h1>
                    @if($selectedLive)
                        @if($selectedLive->ativo)
                            <span style="background-color: #dc2626 !important; color: #ffffff !important;" class="text-[10px] font-black px-2.5 py-0.5 rounded-full flex items-center gap-1 shadow animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span> AO VIVO
                            </span>
                        @else
                            <span style="background-color: #1e293b !important; color: #e2e8f0 !important; border: 1px solid #334155;" class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full">
                                Transmissão Concluída
                            </span>
                        @endif
                    @endif
                </div>
                <p style="color: #cbd5e1 !important;" class="text-xs mt-0.5">
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
                <a href="{{ route('admin.live-chat.feed', ['live_id' => $selectedLive->id]) }}" style="background-color: #4f46e5 !important; color: #ffffff !important;" class="hover:opacity-90 font-extrabold text-xs px-3.5 py-2 rounded-xl transition shadow flex items-center gap-1.5 active:scale-95 cursor-pointer">
                    <i class="fas fa-comments" style="color: #c7d2fe !important;"></i> Ver Chat
                </a>
                <a href="{{ route('admin.lives.cortes', ['liveId' => $selectedLive->id]) }}" style="background-color: #1e293b !important; color: #f472b6 !important; border: 1px solid #374151;" class="hover:bg-gray-800 font-extrabold text-xs px-3 py-2 rounded-xl transition flex items-center gap-1.5 cursor-pointer shadow-sm">
                    <i class="fas fa-film" style="color: #f472b6 !important;"></i> Cortes
                </a>
            @endif

            <form action="{{ route('admin.lives.dashboard') }}" method="GET" class="m-0">
                <select name="live_id" onchange="this.form.submit()" style="background-color: #1e293b !important; color: #ffffff !important; border: 2px solid #334155 !important;" class="text-xs font-bold rounded-xl focus:border-green-500 focus:ring-0 p-2 px-3 cursor-pointer shadow-inner">
                    @foreach($livesList as $l)
                        <option value="{{ $l->id }}" {{ ($selectedLive && $selectedLive->id === $l->id) ? 'selected' : '' }} style="background-color: #1e293b; color: #ffffff;">
                            #{{ $l->id }} - {{ $l->tipo_live_formatado }} ({{ $l->data ? $l->data->format('d/m/Y') : '' }}) {{ $l->ativo ? '[AO VIVO]' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    @if(!$selectedLive)
        <div style="background-color: #ffffff; border: 1px solid #e2e8f0;" class="rounded-3xl p-12 text-center shadow-sm">
            <i class="fas fa-broadcast-tower text-4xl text-gray-300 mb-3"></i>
            <h3 style="color: #0f172a;" class="text-base font-black">Nenhuma Live encontrada</h3>
            <p style="color: #64748b;" class="text-xs mt-1">Crie ou abra uma nova transmissão para visualizar o dashboard de métricas.</p>
        </div>
    @else

        <!-- 6 CARDS DE KPIS PRINCIPAIS -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
            <!-- 1. Faturamento Total -->
            <div style="background-color: #ffffff !important; border: 1px solid #e2e8f0;" class="p-4 rounded-3xl shadow-sm flex flex-col justify-between hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span style="color: #64748b !important; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;">Faturamento</span>
                    <div style="background-color: #d1fae5; color: #047857;" class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-black">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <strong style="color: #0f172a !important;" class="text-lg sm:text-xl font-black block tracking-tight">
                        R$ {{ number_format($kpis['faturamento'], 2, ',', '.') }}
                    </strong>
                    <span style="color: #047857 !important;" class="text-[11px] font-bold mt-0.5 block">
                        R$ {{ number_format($kpis['preco_medio_peca'], 2, ',', '.') }} / peça
                    </span>
                </div>
            </div>

            <!-- 2. Peças Sacoladas -->
            <div style="background-color: #ffffff !important; border: 1px solid #e2e8f0;" class="p-4 rounded-3xl shadow-sm flex flex-col justify-between hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span style="color: #64748b !important; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;">Peças Vendidas</span>
                    <div style="background-color: #e0e7ff; color: #4338ca;" class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-black">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <strong style="color: #0f172a !important;" class="text-lg sm:text-xl font-black block tracking-tight">
                        {{ $kpis['total_pecas_sacoladas'] }} <span style="color: #64748b;" class="text-xs font-semibold">unid.</span>
                    </strong>
                    <span style="color: #4338ca !important;" class="text-[11px] font-bold mt-0.5 block">
                        {{ $kpis['pecas_por_cliente'] }} peças / cliente
                    </span>
                </div>
            </div>

            <!-- 3. Clientes Compradoras -->
            <div style="background-color: #ffffff !important; border: 1px solid #e2e8f0;" class="p-4 rounded-3xl shadow-sm flex flex-col justify-between hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span style="color: #64748b !important; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;">Compradoras</span>
                    <div style="background-color: #ede9fe; color: #6d28d9;" class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-black">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <strong style="color: #0f172a !important;" class="text-lg sm:text-xl font-black block tracking-tight">
                        {{ $kpis['total_compradoras'] }} <span style="color: #64748b;" class="text-xs font-semibold">clientes</span>
                    </strong>
                    <span style="color: #6d28d9 !important;" class="text-[11px] font-bold mt-0.5 block">
                        {{ $kpis['total_participantes_chat'] > 0 ? round(($kpis['total_compradoras'] / $kpis['total_participantes_chat']) * 100, 1) : 0 }}% do chat comprou
                    </span>
                </div>
            </div>

            <!-- 4. Ticket Médio -->
            <div style="background-color: #ffffff !important; border: 1px solid #e2e8f0;" class="p-4 rounded-3xl shadow-sm flex flex-col justify-between hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span style="color: #64748b !important; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;">Ticket Médio</span>
                    <div style="background-color: #fef3c7; color: #b45309;" class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-black">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <strong style="color: #0f172a !important;" class="text-lg sm:text-xl font-black block tracking-tight">
                        R$ {{ number_format($kpis['ticket_medio'], 2, ',', '.') }}
                    </strong>
                    <span style="color: #475569 !important;" class="text-[11px] font-bold mt-0.5 block">
                        Por cliente compradora
                    </span>
                </div>
            </div>

            <!-- 5. Peças Bipadas vs Vendidas -->
            <div style="background-color: #ffffff !important; border: 1px solid #e2e8f0;" class="p-4 rounded-3xl shadow-sm flex flex-col justify-between hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span style="color: #64748b !important; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;">Conversão Peças</span>
                    <div style="background-color: #ccfbf1; color: #0f766e;" class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-black">
                        <i class="fas fa-barcode"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <strong style="color: #0f172a !important;" class="text-lg sm:text-xl font-black block tracking-tight">
                        {{ $kpis['taxa_conversao_itens'] }}%
                    </strong>
                    <span style="color: #0f766e !important;" class="text-[11px] font-bold mt-0.5 block">
                        {{ $kpis['total_pecas_sacoladas'] }} de {{ $kpis['total_itens_apresentados'] }} bipadas
                    </span>
                </div>
            </div>

            <!-- 6. Engajamento no Chat -->
            <div style="background-color: #ffffff !important; border: 1px solid #e2e8f0;" class="p-4 rounded-3xl shadow-sm flex flex-col justify-between hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <span style="color: #64748b !important; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;">Mensagens Chat</span>
                    <div style="background-color: #fce7f3; color: #be185d;" class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-black">
                        <i class="fas fa-comments"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <strong style="color: #0f172a !important;" class="text-lg sm:text-xl font-black block tracking-tight">
                        {{ number_format($kpis['total_mensagens'], 0, '', '.') }} <span style="color: #64748b;" class="text-xs font-semibold">msgs</span>
                    </strong>
                    <span style="color: #be185d !important;" class="text-[11px] font-bold mt-0.5 block">
                        {{ $kpis['total_participantes_chat'] }} participantes
                    </span>
                </div>
            </div>
        </div>

        <!-- DIVISÃO POR CANAIS (INSTAGRAM VS TIKTOK) & STATUS DAS SACOLAS -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Canal / Plataforma Chat -->
            <div style="background-color: #ffffff; border: 1px solid #e2e8f0;" class="p-5 rounded-3xl shadow-sm">
                <h3 style="color: #0f172a !important;" class="text-sm font-black flex items-center gap-2">
                    <i class="fas fa-share-alt text-indigo-500"></i> Divisão de Mensagens por Plataforma
                </h3>
                <p style="color: #64748b;" class="text-xs mt-0.5">Engajamento comparativo entre as transmissões do Instagram e TikTok</p>

                <div class="mt-4 space-y-3">
                    <!-- Barra Visual Proporcional -->
                    <div style="background-color: #f1f5f9; border: 1px solid #e2e8f0;" class="w-full rounded-full h-3.5 flex overflow-hidden">
                        <div style="background: linear-gradient(to right, #7c3aed, #db2777); width: {{ $channelStats['instagram_share'] }}%;" class="h-full transition-all duration-500" title="Instagram: {{ $channelStats['instagram_messages'] }} msgs"></div>
                        <div style="background-color: #0f172a; width: {{ $channelStats['tiktok_share'] }}%;" class="h-full transition-all duration-500" title="TikTok: {{ $channelStats['tiktok_messages'] }} msgs"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <!-- Card Instagram -->
                        <div style="background: linear-gradient(to bottom right, #fdf2f8, #f5f3ff); border: 1px solid #fbcfe8;" class="p-3.5 rounded-2xl">
                            <div class="flex items-center justify-between">
                                <span style="color: #9d174d !important;" class="text-xs font-black flex items-center gap-1.5">
                                    <i class="fab fa-instagram text-pink-600 text-sm"></i> Instagram
                                </span>
                                <span style="color: #831843 !important;" class="text-xs font-black">{{ $channelStats['instagram_share'] }}%</span>
                            </div>
                            <strong style="color: #0f172a !important;" class="text-lg font-black mt-1.5 block">
                                {{ $channelStats['instagram_messages'] }} <span style="color: #64748b;" class="text-xs font-semibold">mensagens</span>
                            </strong>
                        </div>

                        <!-- Card TikTok -->
                        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0;" class="p-3.5 rounded-2xl">
                            <div class="flex items-center justify-between">
                                <span style="color: #0f172a !important;" class="text-xs font-black flex items-center gap-1.5">
                                    <i class="fab fa-tiktok text-gray-900 text-sm"></i> TikTok
                                </span>
                                <span style="color: #0f172a !important;" class="text-xs font-black">{{ $channelStats['tiktok_share'] }}%</span>
                            </div>
                            <strong style="color: #0f172a !important;" class="text-lg font-black mt-1.5 block">
                                {{ $channelStats['tiktok_messages'] }} <span style="color: #64748b;" class="text-xs font-semibold">mensagens</span>
                            </strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status do Processamento das Sacolinhas -->
            <div style="background-color: #ffffff; border: 1px solid #e2e8f0;" class="p-5 rounded-3xl shadow-sm flex flex-col justify-between">
                <div>
                    <h3 style="color: #0f172a !important;" class="text-sm font-black flex items-center gap-2">
                        <i class="fas fa-tasks text-green-600"></i> Status das Sacolinhas da Live
                    </h3>
                    <p style="color: #64748b;" class="text-xs mt-0.5">Situação dos pedidos gerados nesta transmissão</p>

                    <div class="grid grid-cols-3 gap-2.5 mt-4">
                        <div style="background-color: #eef2ff; border: 1px solid #c7d2fe;" class="p-3 rounded-2xl text-center">
                            <span style="color: #3730a3 !important; font-size: 10px; font-weight: 900; text-transform: uppercase;" class="block">Abertas</span>
                            <strong style="color: #1e1b4b !important;" class="text-base font-black mt-0.5 block">
                                {{ $statusCounts['aberta']->count ?? 0 }}
                            </strong>
                            <small style="color: #4338ca !important; font-weight: 800; font-size: 10px;">R$ {{ number_format($statusCounts['aberta']->total_val ?? 0, 2, ',', '.') }}</small>
                        </div>

                        <div style="background-color: #ecfdf5; border: 1px solid #a7f3d0;" class="p-3 rounded-2xl text-center">
                            <span style="color: #065f46 !important; font-size: 10px; font-weight: 900; text-transform: uppercase;" class="block">Pagas / Fechadas</span>
                            <strong style="color: #064e3b !important;" class="text-base font-black mt-0.5 block">
                                {{ ($statusCounts['fechada']->count ?? 0) + ($statusCounts['paga']->count ?? 0) }}
                            </strong>
                            <small style="color: #047857 !important; font-weight: 800; font-size: 10px;">R$ {{ number_format(($statusCounts['fechada']->total_val ?? 0) + ($statusCounts['paga']->total_val ?? 0), 2, ',', '.') }}</small>
                        </div>

                        <div style="background-color: #f1f5f9; border: 1px solid #cbd5e1;" class="p-3 rounded-2xl text-center">
                            <span style="color: #334155 !important; font-size: 10px; font-weight: 900; text-transform: uppercase;" class="block">Enviadas</span>
                            <strong style="color: #0f172a !important;" class="text-base font-black mt-0.5 block">
                                {{ $statusCounts['enviada']->count ?? 0 }}
                            </strong>
                            <small style="color: #475569 !important; font-weight: 800; font-size: 10px;">R$ {{ number_format($statusCounts['enviada']->total_val ?? 0, 2, ',', '.') }}</small>
                        </div>
                    </div>
                </div>

                <div style="border-top: 1px solid #f1f5f9; color: #475569;" class="pt-3 flex items-center justify-between text-xs mt-3">
                    <span>Taxa de conclusão da live:</span>
                    <strong style="color: #047857 !important; font-weight: 900;">
                        {{ $kpis['faturamento'] > 0 ? round(((($statusCounts['fechada']->total_val ?? 0) + ($statusCounts['paga']->total_val ?? 0) + ($statusCounts['enviada']->total_val ?? 0)) / $kpis['faturamento']) * 100, 1) : 0 }}% faturado
                    </strong>
                </div>
            </div>
        </div>

        <!-- DUAS COLUNAS: TOP COMPRADORAS & PEÇAS SACOLADAS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <!-- Ranking: Maiores Compradoras -->
            <div style="background-color: #ffffff; border: 1px solid #e2e8f0;" class="rounded-3xl shadow-sm overflow-hidden flex flex-col">
                <div style="border-bottom: 1px solid #e2e8f0;" class="p-4 sm:p-5 flex items-center justify-between">
                    <div>
                        <h3 style="color: #0f172a !important;" class="text-sm sm:text-base font-black flex items-center gap-2">
                            <i class="fas fa-crown text-yellow-500"></i> Top Compradoras da Live
                        </h3>
                        <p style="color: #64748b;" class="text-xs mt-0.5">Ranking das clientes que mais investiram nesta live</p>
                    </div>
                    <span style="background-color: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe;" class="text-xs font-black px-2.5 py-1 rounded-xl">
                        {{ $topBuyers->count() }} clientes
                    </span>
                </div>

                <div class="divide-y divide-gray-100 max-h-[460px] overflow-y-auto">
                    @forelse($topBuyers as $index => $buyer)
                        <div class="p-3.5 sm:p-4 hover:bg-gray-50 transition flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-xs shrink-0 shadow-sm {{ $index === 0 ? 'bg-yellow-400 text-yellow-950 font-black ring-2 ring-yellow-300' : ($index === 1 ? 'bg-gray-300 text-gray-800' : ($index === 2 ? 'bg-yellow-700 text-white' : 'bg-gray-100 text-gray-700')) }}">
                                    {{ $index + 1 }}º
                                </div>
                                <div class="min-w-0">
                                    <strong style="color: #0f172a !important;" class="text-xs sm:text-sm font-black block truncate">
                                        {{ $buyer->client_name }}
                                    </strong>
                                    <div class="text-[11px] flex items-center gap-2 truncate mt-0.5">
                                        @if($buyer->instagram)
                                            <span style="color: #be185d;" class="font-bold"><i class="fab fa-instagram"></i> {{ $buyer->instagram }}</span>
                                        @endif
                                        @if($buyer->tiktok)
                                            <span style="color: #0f172a;" class="font-bold"><i class="fab fa-tiktok"></i> {{ $buyer->tiktok }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                <div class="text-right">
                                    <strong style="color: #047857 !important;" class="text-xs sm:text-sm font-black block">
                                        R$ {{ number_format($buyer->total_spent, 2, ',', '.') }}
                                    </strong>
                                    <span style="color: #64748b;" class="text-[10px] font-bold block">
                                        {{ $buyer->total_items }} {{ $buyer->total_items == 1 ? 'peça' : 'peças' }}
                                    </span>
                                </div>

                                @if(!empty($buyer->clean_phone))
                                    <a href="https://wa.me/55{{ $buyer->clean_phone }}" target="_blank" style="background-color: #d1fae5; color: #065f46;" class="w-8 h-8 rounded-xl hover:bg-green-200 flex items-center justify-center transition shadow-xs cursor-pointer" title="Conversar no WhatsApp">
                                        <i class="fab fa-whatsapp text-sm"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-gray-400">
                            <i class="fas fa-shopping-basket text-3xl mb-2 text-gray-300"></i>
                            <p style="color: #64748b;" class="text-xs font-bold">Nenhuma compra registrada nesta live.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Tabela: Peças Sacoladas na Live -->
            <div style="background-color: #ffffff; border: 1px solid #e2e8f0;" class="rounded-3xl shadow-sm overflow-hidden flex flex-col">
                <div style="border-bottom: 1px solid #e2e8f0;" class="p-4 sm:p-5 flex items-center justify-between">
                    <div>
                        <h3 style="color: #0f172a !important;" class="text-sm sm:text-base font-black flex items-center gap-2">
                            <i class="fas fa-tags text-indigo-500"></i> Peças Sacoladas na Live
                        </h3>
                        <p style="color: #64748b;" class="text-xs mt-0.5">Últimos itens bipados e adicionados às sacolinhas</p>
                    </div>
                    <span style="background-color: #d1fae5; color: #047857; border: 1px solid #a7f3d0;" class="text-xs font-black px-2.5 py-1 rounded-xl">
                        {{ $baggedItems->count() }} itens
                    </span>
                </div>

                <div class="divide-y divide-gray-100 max-h-[460px] overflow-y-auto">
                    @forelse($baggedItems as $item)
                        <div class="p-3.5 sm:p-4 hover:bg-gray-50 transition flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span style="background-color: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe;" class="text-[11px] font-black font-mono px-2 py-0.5 rounded-lg">
                                        #{{ $item->item_code }}
                                    </span>
                                    <strong style="color: #0f172a !important;" class="text-xs font-bold truncate">
                                        {{ $item->item_name }}
                                    </strong>
                                </div>
                                <div style="color: #64748b;" class="text-[11px] mt-1 flex items-center gap-2 flex-wrap">
                                    @if($item->item_size)
                                        <span style="background-color: #f1f5f9; color: #1e293b;" class="px-1.5 py-0.5 rounded font-bold text-[10px]">Tam: {{ $item->item_size }}</span>
                                    @endif
                                    @if($item->item_brand)
                                        <span style="color: #475569;" class="font-semibold">{{ $item->item_brand }}</span>
                                    @endif
                                    <span class="text-gray-300">&bull;</span>
                                    <span style="color: #1e293b;" class="font-semibold">Cliente: <strong style="color: #0f172a;">{{ $item->buyer_name }}</strong></span>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <strong style="color: #0f172a !important;" class="text-xs sm:text-sm font-black block">
                                    R$ {{ number_format($item->sacolinha_price, 2, ',', '.') }}
                                </strong>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md inline-block mt-0.5 {{ $item->sacolinha_status === 'fechada' || $item->sacolinha_status === 'paga' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($item->sacolinha_status) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-gray-400">
                            <i class="fas fa-barcode text-3xl mb-2 text-gray-300"></i>
                            <p style="color: #64748b;" class="text-xs font-bold">Nenhum item sacolado nesta live ainda.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- TABELA: HISTÓRICO COMPARATIVO DAS ÚLTIMAS 15 LIVES -->
        <div style="background-color: #ffffff; border: 1px solid #e2e8f0;" class="rounded-3xl shadow-sm overflow-hidden">
            <div style="border-bottom: 1px solid #e2e8f0;" class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h3 style="color: #0f172a !important;" class="text-sm sm:text-base font-black flex items-center gap-2">
                        <i class="fas fa-history text-indigo-600"></i> Comparativo Histórico de Lives Recentes
                    </h3>
                    <p style="color: #64748b;" class="text-xs mt-0.5">Visão consolidada das últimas 15 transmissões realizadas</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569;" class="font-black uppercase text-[10px] tracking-wider">
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
                    <tbody class="divide-y divide-gray-100 font-medium" style="color: #0f172a;">
                        @foreach($recentLivesComparison as $rl)
                            <tr class="hover:bg-gray-50 transition {{ ($selectedLive && $selectedLive->id === $rl->id) ? 'bg-indigo-50/50 font-bold' : '' }}">
                                <td class="p-3 pl-4 font-black" style="color: #0f172a !important;">
                                    <span class="flex items-center gap-1.5">
                                        #{{ $rl->id }}
                                        @if($rl->ativo)
                                            <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse" title="Ao Vivo"></span>
                                        @endif
                                    </span>
                                </td>
                                <td class="p-3 font-semibold whitespace-nowrap" style="color: #475569;">{{ $rl->data }}</td>
                                <td class="p-3">
                                    <span style="background-color: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1;" class="px-2 py-0.5 rounded-lg text-[10px] font-bold whitespace-nowrap">
                                        {{ $rl->tipo_formatado }}
                                    </span>
                                </td>
                                <td class="p-3 whitespace-nowrap" style="color: #475569;">{{ $rl->duracao }}</td>
                                <td class="p-3 text-right font-black whitespace-nowrap" style="color: #047857 !important;">
                                    R$ {{ number_format($rl->faturamento, 2, ',', '.') }}
                                </td>
                                <td class="p-3 text-center font-bold" style="color: #0f172a !important;">{{ $rl->pecas_vendidas }}</td>
                                <td class="p-3 text-center font-bold" style="color: #4338ca !important;">{{ $rl->compradoras }}</td>
                                <td class="p-3 text-right font-semibold whitespace-nowrap" style="color: #334155;">
                                    R$ {{ number_format($rl->ticket_medio, 2, ',', '.') }}
                                </td>
                                <td class="p-3 text-center" style="color: #475569;">{{ $rl->itens_apresentados ?: '-' }}</td>
                                <td class="p-3 text-center">
                                    @if($rl->conversao > 0)
                                        <span style="background-color: #f0fdfa; color: #0f766e; border: 1px solid #ccfbf1;" class="font-bold px-1.5 py-0.5 rounded text-[10px]">{{ $rl->conversao }}%</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center font-semibold" style="color: #475569;">{{ number_format($rl->mensagens, 0, '', '.') }}</td>
                                <td class="p-3 pr-4 text-center whitespace-nowrap">
                                    <a href="{{ route('admin.lives.dashboard', ['live_id' => $rl->id]) }}" style="background-color: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe;" class="hover:bg-indigo-600 hover:text-white font-bold text-[11px] px-2.5 py-1 rounded-lg transition inline-flex items-center gap-1 cursor-pointer">
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

@extends('layouts.app')

@section('title', 'Dashboard das Lives - Minha Mania')

@section('content')
<div class="container mx-auto px-3 sm:px-6 py-4 space-y-6 max-w-7xl">

    <!-- ===================================================================== -->
    <!-- CABEÇALHO PRINCIPAL COM SELETORES (MÊS & LIVE)                        -->
    <!-- ===================================================================== -->
    <div style="background-color: #0f172a !important; color: #ffffff !important; border: 1px solid #1e293b;" class="p-4 sm:p-5 rounded-3xl shadow-xl flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div style="background: linear-gradient(135deg, #059669, #0d9488); color: #ffffff;" class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-lg shrink-0">
                <i class="fas fa-chart-line" style="color: #ffffff !important;"></i>
            </div>
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 style="color: #ffffff !important;" class="text-lg sm:text-xl font-black tracking-tight">Dashboard de Performance de Lives</h1>
                    <span style="background-color: #065f46; color: #a7f3d0; border: 1px solid #059669;" class="text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                        Base Mensal &bull; {{ $selectedMonthLabel }}
                    </span>
                    @if($selectedLive && $selectedLive->ativo)
                        <span style="background-color: #dc2626 !important; color: #ffffff !important;" class="text-[10px] font-black px-2.5 py-0.5 rounded-full flex items-center gap-1 shadow animate-pulse">
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span> AO VIVO
                        </span>
                    @endif
                </div>
                <p style="color: #cbd5e1 !important;" class="text-xs mt-0.5">
                    Análise consolidada por mês com isolamento de conversão para transmissões com e sem bipagem.
                </p>
            </div>
        </div>

        <!-- Seletores de Mês, Live e Ações -->
        <div class="flex flex-wrap items-center gap-2">
            @if($selectedLive)
                <a href="{{ route('admin.live-chat.feed', ['live_id' => $selectedLive->id]) }}" style="background-color: #4f46e5 !important; color: #ffffff !important;" class="hover:opacity-90 font-extrabold text-xs px-3 py-2 rounded-xl transition shadow flex items-center gap-1.5 active:scale-95 cursor-pointer">
                    <i class="fas fa-comments" style="color: #c7d2fe !important;"></i> Ver Chat
                </a>
                <a href="{{ route('admin.lives.cortes', ['liveId' => $selectedLive->id]) }}" style="background-color: #1e293b !important; color: #f472b6 !important; border: 1px solid #374151;" class="hover:bg-gray-800 font-extrabold text-xs px-3 py-2 rounded-xl transition flex items-center gap-1.5 cursor-pointer shadow-sm">
                    <i class="fas fa-film" style="color: #f472b6 !important;"></i> Cortes
                </a>
            @endif

            <!-- Filtro de Mês -->
            <form action="{{ route('admin.lives.dashboard') }}" method="GET" class="m-0 flex items-center gap-2">
                <select name="mes" onchange="this.form.submit()" style="background-color: #1e293b !important; color: #ffffff !important; border: 2px solid #059669 !important;" class="text-xs font-bold rounded-xl p-2 px-3 cursor-pointer shadow-inner" title="Selecione o mês desejado">
                    @foreach($availableMonths as $am)
                        <option value="{{ $am->mes }}" {{ $selectedMonth === $am->mes ? 'selected' : '' }} style="background-color: #1e293b; color: #ffffff;">
                            📅 {{ $am->label }}
                        </option>
                    @endforeach
                </select>
                @if($selectedLive)
                    <input type="hidden" name="live_id" value="{{ $selectedLive->id }}">
                @endif
            </form>
        </div>
    </div>

    @if(!$monthKpis || $livesMonthlyList->isEmpty())
        <div style="background-color: #ffffff; border: 1px solid #e2e8f0;" class="rounded-3xl p-12 text-center shadow-sm">
            <i class="fas fa-calendar-times text-4xl text-gray-300 mb-3"></i>
            <h3 style="color: #0f172a;" class="text-base font-black">Nenhuma Live encontrada para {{ $selectedMonthLabel }}</h3>
            <p style="color: #64748b;" class="text-xs mt-1">Selecione outro mês no seletor acima para visualizar as métricas analíticas.</p>
        </div>
    @else

        <!-- ===================================================================== -->
        <!-- SEÇÃO 1: CONSOLIDADO MENSAL (BASE MENSAL)                             -->
        <!-- ===================================================================== -->
        <div class="space-y-3">
            <div class="flex items-center justify-between flex-wrap gap-2 px-1">
                <div>
                    <h2 style="color: #0f172a !important;" class="text-base sm:text-lg font-black tracking-tight flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-emerald-600"></i> Desempenho Mensal Consolidado &bull; {{ $selectedMonthLabel }}
                    </h2>
                    <p style="color: #64748b;" class="text-xs">
                        Métricas consolidadas de todas as {{ $monthKpis['total_lives'] }} transmissões realizadas neste período.
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap text-xs">
                    <span style="background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;" class="font-extrabold px-2.5 py-1 rounded-xl flex items-center gap-1.5 shadow-2xs">
                        <i class="fas fa-barcode text-emerald-600"></i> {{ $monthKpis['total_lives_com_bipagem'] }} com Bipagem
                    </span>
                    <span style="background-color: #f8fafc; color: #475569; border: 1px solid #cbd5e1;" class="font-extrabold px-2.5 py-1 rounded-xl flex items-center gap-1.5 shadow-2xs">
                        <i class="fas fa-shopping-bag text-gray-500"></i> {{ $monthKpis['total_lives_sem_bipagem'] }} sem Bipagem (Venda Direta)
                    </span>
                </div>
            </div>

            <!-- 6 CARDS KPIS DO MÊS -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
                <!-- 1. Faturamento do Mês -->
                <div style="background-color: #ffffff !important; border: 1px solid #e2e8f0;" class="p-4 rounded-3xl shadow-sm flex flex-col justify-between hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span style="color: #64748b !important; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;">Faturamento Mês</span>
                        <div style="background-color: #d1fae5; color: #047857;" class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-black">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <strong style="color: #0f172a !important;" class="text-lg sm:text-xl font-black block tracking-tight">
                            R$ {{ number_format($monthKpis['faturamento'], 2, ',', '.') }}
                        </strong>
                        <span style="color: #047857 !important;" class="text-[11px] font-bold mt-0.5 block">
                            R$ {{ number_format($monthKpis['preco_medio_peca'], 2, ',', '.') }} / peça
                        </span>
                    </div>
                </div>

                <!-- 2. Peças Vendidas no Mês -->
                <div style="background-color: #ffffff !important; border: 1px solid #e2e8f0;" class="p-4 rounded-3xl shadow-sm flex flex-col justify-between hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span style="color: #64748b !important; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;">Peças no Mês</span>
                        <div style="background-color: #e0e7ff; color: #4338ca;" class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-black">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <strong style="color: #0f172a !important;" class="text-lg sm:text-xl font-black block tracking-tight">
                            {{ number_format($monthKpis['total_pecas'], 0, '', '.') }} <span style="color: #64748b;" class="text-xs font-semibold">unid.</span>
                        </strong>
                        <span style="color: #4338ca !important;" class="text-[11px] font-bold mt-0.5 block">
                            {{ $monthKpis['total_lives'] > 0 ? round($monthKpis['total_pecas'] / $monthKpis['total_lives'], 1) : 0 }} peças / live
                        </span>
                    </div>
                </div>

                <!-- 3. Compradoras Únicas no Mês -->
                <div style="background-color: #ffffff !important; border: 1px solid #e2e8f0;" class="p-4 rounded-3xl shadow-sm flex flex-col justify-between hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span style="color: #64748b !important; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;">Clientes Mês</span>
                        <div style="background-color: #ede9fe; color: #6d28d9;" class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-black">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <strong style="color: #0f172a !important;" class="text-lg sm:text-xl font-black block tracking-tight">
                            {{ number_format($monthKpis['compradoras_unicas'], 0, '', '.') }} <span style="color: #64748b;" class="text-xs font-semibold">únicas</span>
                        </strong>
                        <span style="color: #6d28d9 !important;" class="text-[11px] font-bold mt-0.5 block">
                            {{ $monthKpis['compradoras_unicas'] > 0 ? round($monthKpis['total_pecas'] / $monthKpis['compradoras_unicas'], 1) : 0 }} peças / cliente
                        </span>
                    </div>
                </div>

                <!-- 4. Ticket Médio do Mês -->
                <div style="background-color: #ffffff !important; border: 1px solid #e2e8f0;" class="p-4 rounded-3xl shadow-sm flex flex-col justify-between hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span style="color: #64748b !important; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;">Ticket Médio</span>
                        <div style="background-color: #fef3c7; color: #b45309;" class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-black">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <strong style="color: #0f172a !important;" class="text-lg sm:text-xl font-black block tracking-tight">
                            R$ {{ number_format($monthKpis['ticket_medio'], 2, ',', '.') }}
                        </strong>
                        <span style="color: #b45309 !important;" class="text-[11px] font-bold mt-0.5 block">
                            Por cliente no mês
                        </span>
                    </div>
                </div>

                <!-- 5. Conversão Média das Lives Bipadas (SEM CONTAMINAÇÃO) -->
                <div style="background-color: #ffffff !important; border: 2px solid #0d9488;" class="p-4 rounded-3xl shadow-sm flex flex-col justify-between hover:shadow-md transition relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span style="color: #0f766e !important; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;">Conversão (Bipadas)</span>
                        <div style="background-color: #ccfbf1; color: #0f766e;" class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-black">
                            <i class="fas fa-barcode"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        @if($monthKpis['total_lives_com_bipagem'] > 0 && $monthKpis['total_itens_bipados'] > 0)
                            <strong style="color: #0f766e !important;" class="text-lg sm:text-xl font-black block tracking-tight">
                                {{ $monthKpis['taxa_conversao_bipadas'] }}%
                            </strong>
                            <span style="color: #0d9488 !important; font-size: 10.5px; font-weight: 800; display: block; margin-top: 2px;">
                                {{ $monthKpis['total_pecas_bipadas_vendidas'] }} de {{ $monthKpis['total_itens_bipados'] }} bipadas
                            </span>
                            <span style="background-color: #f0fdfa; color: #115e59; font-size: 9px; font-weight: 800; padding: 2px 4px; border-radius: 4px; display: inline-block; margin-top: 3px;" title="Cálculo purificado: {{ $monthKpis['total_lives_sem_bipagem'] }} lives sem bipagem foram excluídas desta métrica para não distorcer os dados">
                                🛡️ Sem contaminação ({{ $monthKpis['total_lives_com_bipagem'] }} lives)
                            </span>
                        @else
                            <strong style="color: #64748b !important;" class="text-base font-black block">
                                N/A
                            </strong>
                            <span style="color: #94a3b8; font-size: 10.5px; font-weight: 600; display: block; margin-top: 2px;">
                                Nenhuma live com bipagem
                            </span>
                        @endif
                    </div>
                </div>

                <!-- 6. Total de Lives & Engajamento do Mês -->
                <div style="background-color: #ffffff !important; border: 1px solid #e2e8f0;" class="p-4 rounded-3xl shadow-sm flex flex-col justify-between hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span style="color: #64748b !important; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;">Transmissões</span>
                        <div style="background-color: #fce7f3; color: #be185d;" class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-black">
                            <i class="fas fa-video"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <strong style="color: #0f172a !important;" class="text-lg sm:text-xl font-black block tracking-tight">
                            {{ $monthKpis['total_lives'] }} <span style="color: #64748b;" class="text-xs font-semibold">lives</span>
                        </strong>
                        <span style="color: #be185d !important;" class="text-[11px] font-bold mt-0.5 block">
                            {{ number_format($monthKpis['total_mensagens'], 0, '', '.') }} msgs no chat
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- SEÇÃO 2: TABELA DE LIVES DO MÊS COM FILTRO DE SEGREGAMENTO            -->
        <!-- ===================================================================== -->
        <div style="background-color: #ffffff; border: 1px solid #e2e8f0;" class="rounded-3xl shadow-sm overflow-hidden">
            <!-- Cabeçalho da Tabela com Filtros de Isolamento -->
            <div style="border-bottom: 1px solid #e2e8f0; background-color: #f8fafc;" class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h3 style="color: #0f172a !important;" class="text-sm sm:text-base font-black flex items-center gap-2">
                        <i class="fas fa-list-ol text-indigo-600"></i> Transmissões do Mês &bull; {{ $selectedMonthLabel }}
                    </h3>
                    <p style="color: #64748b;" class="text-xs mt-0.5">
                        Identificação clara de lives com leitor contínuo (conversão ativa) e sem bipagem (conversão desconsiderada).
                    </p>
                </div>

                <!-- Botões de Filtragem Rápida na Tabela -->
                <div class="flex items-center gap-1.5 flex-wrap">
                    <button type="button" onclick="filterLiveTable('all')" id="tab-filter-all" 
                        style="background-color: #0f172a; color: #ffffff; border: 1px solid #0f172a;" 
                        class="px-3 py-1.5 rounded-xl font-extrabold text-xs transition cursor-pointer shadow-sm">
                        Todas as Lives ({{ $livesMonthlyList->count() }})
                    </button>
                    <button type="button" onclick="filterLiveTable('bipadas')" id="tab-filter-bipadas" 
                        style="background-color: #ffffff; color: #047857; border: 1.5px solid #a7f3d0;" 
                        class="px-3 py-1.5 rounded-xl font-extrabold text-xs transition cursor-pointer hover:bg-emerald-50 shadow-sm flex items-center gap-1">
                        <i class="fas fa-barcode text-emerald-600"></i> Com Bipagem ({{ $monthKpis['total_lives_com_bipagem'] }})
                    </button>
                    <button type="button" onclick="filterLiveTable('sem_bipagem')" id="tab-filter-sem-bipagem" 
                        style="background-color: #ffffff; color: #475569; border: 1.5px solid #cbd5e1;" 
                        class="px-3 py-1.5 rounded-xl font-extrabold text-xs transition cursor-pointer hover:bg-gray-100 shadow-sm flex items-center gap-1">
                        <i class="fas fa-slash text-gray-500"></i> Sem Bipagem ({{ $monthKpis['total_lives_sem_bipagem'] }})
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs" id="table-monthly-lives">
                    <thead>
                        <tr style="background-color: #ffffff; border-bottom: 2px solid #e2e8f0; color: #475569;" class="font-black uppercase text-[10px] tracking-wider">
                            <th class="p-3 pl-4">Live</th>
                            <th class="p-3">Data</th>
                            <th class="p-3">Tipo</th>
                            <th class="p-3">Modo Bipagem</th>
                            <th class="p-3 text-right">Faturamento</th>
                            <th class="p-3 text-center">Peças</th>
                            <th class="p-3 text-center">Clientes</th>
                            <th class="p-3 text-right">Ticket Médio</th>
                            <th class="p-3 text-center">Itens Bipados</th>
                            <th class="p-3 text-center">Taxa Conversão</th>
                            <th class="p-3 text-center">Msgs Chat</th>
                            <th class="p-3 pr-4 text-center">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-medium" style="color: #0f172a;">
                        @foreach($livesMonthlyList as $rl)
                            <tr class="hover:bg-gray-50 transition live-row {{ $rl->has_bipagem ? 'row-bipada' : 'row-sem-bipagem' }} {{ ($selectedLive && $selectedLive->id === $rl->id) ? 'bg-indigo-50/70 border-l-4 border-indigo-600' : '' }}" data-has-bipagem="{{ $rl->has_bipagem ? '1' : '0' }}">
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
                                <td class="p-3 whitespace-nowrap">
                                    @if($rl->has_bipagem)
                                        <span style="background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;" class="px-2 py-0.5 rounded-lg text-[10px] font-extrabold inline-flex items-center gap-1 shadow-2xs">
                                            <i class="fas fa-barcode text-emerald-600 text-[9px]"></i> Com Bipagem
                                        </span>
                                    @else
                                        <span style="background-color: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;" class="px-2 py-0.5 rounded-lg text-[10px] font-bold inline-flex items-center gap-1" title="Esta live não utilizou leitor de código de barras contínuo">
                                            <i class="fas fa-slash text-[8px] text-gray-400"></i> Sem Bipagem
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 text-right font-black whitespace-nowrap" style="color: #047857 !important;">
                                    R$ {{ number_format($rl->faturamento, 2, ',', '.') }}
                                </td>
                                <td class="p-3 text-center font-bold" style="color: #0f172a !important;">{{ $rl->pecas_vendidas }}</td>
                                <td class="p-3 text-center font-bold" style="color: #4338ca !important;">{{ $rl->compradoras }}</td>
                                <td class="p-3 text-right font-semibold whitespace-nowrap" style="color: #334155;">
                                    R$ {{ number_format($rl->ticket_medio, 2, ',', '.') }}
                                </td>
                                <td class="p-3 text-center">
                                    @if($rl->has_bipagem)
                                        <strong style="color: #065f46;">{{ $rl->itens_bipados }}</strong>
                                    @else
                                        <span style="color: #94a3b8;" title="Sem contaminação: Itens não foram bipados via sistema">—</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    @if($rl->has_bipagem && $rl->conversao !== null)
                                        <span style="background-color: #ccfbf1; color: #0f766e; border: 1px solid #99f6e4;" class="font-extrabold px-2 py-0.5 rounded-lg text-[10px] shadow-2xs">
                                            {{ $rl->conversao }}%
                                        </span>
                                    @else
                                        <span style="color: #94a3b8; font-weight: 700; font-size: 11px;" title="Conversão desconsiderada para não contaminar as estatísticas">
                                            —
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 text-center font-semibold" style="color: #475569;">{{ number_format($rl->mensagens, 0, '', '.') }}</td>
                                <td class="p-3 pr-4 text-center whitespace-nowrap">
                                    <a href="{{ route('admin.lives.dashboard', ['mes' => $selectedMonth, 'live_id' => $rl->id]) }}#detalhe-live" 
                                        style="background-color: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe;" 
                                        class="hover:bg-indigo-600 hover:text-white font-bold text-[11px] px-2.5 py-1 rounded-lg transition inline-flex items-center gap-1 cursor-pointer">
                                        <span>Analisar</span> <i class="fas fa-chevron-right text-[9px]"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- SEÇÃO 3: ANÁLISE DETALHADA DA LIVE SELECIONADA                        -->
        <!-- ===================================================================== -->
        @if($selectedLive)
            <div id="detalhe-live" class="space-y-4 pt-3">
                <!-- Header da Live Selecionada -->
                <div style="background-color: #ffffff; border: 1.5px solid #cbd5e1;" class="p-4 sm:p-5 rounded-3xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 style="color: #0f172a !important;" class="text-base sm:text-lg font-black tracking-tight flex items-center gap-2">
                                <i class="fas fa-search-plus text-indigo-600"></i> Análise Detalhada &bull; Live #{{ $selectedLive->id }}
                            </h2>
                            <span style="background-color: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe;" class="text-xs font-black px-2.5 py-0.5 rounded-lg">
                                {{ $selectedLive->tipo_live_formatado }}
                            </span>
                            @if($kpis['has_bipagem'])
                                <span style="background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;" class="text-xs font-black px-2.5 py-0.5 rounded-lg flex items-center gap-1">
                                    <i class="fas fa-barcode"></i> Modo Bipagem Ativo ({{ $kpis['total_itens_apresentados'] }} peças)
                                </span>
                            @else
                                <span style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;" class="text-xs font-bold px-2.5 py-0.5 rounded-lg flex items-center gap-1">
                                    <i class="fas fa-slash text-gray-400"></i> Live sem Bipagem (Venda Direta)
                                </span>
                            @endif
                        </div>
                        <p style="color: #64748b;" class="text-xs mt-1">
                            Data: <strong>{{ $selectedLive->data ? $selectedLive->data->format('d/m/Y') : ($selectedLive->created_at ? $selectedLive->created_at->format('d/m/Y') : '') }}</strong> 
                            &bull; Duração: <strong>{{ $kpis['duracao_formatada'] }}</strong>
                            &bull; Brechó: <strong>Minha Mania (Matriz)</strong>
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.live-chat.feed', ['live_id' => $selectedLive->id]) }}" style="background-color: #4f46e5; color: #ffffff;" class="hover:bg-indigo-700 font-extrabold text-xs px-3.5 py-2 rounded-xl transition shadow flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-play text-xs text-indigo-200"></i> Ir para Chat da Live
                        </a>
                    </div>
                </div>

                <!-- 6 CARDS KPIS DA LIVE SELECIONADA -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
                    <!-- 1. Faturamento -->
                    <div style="background-color: #ffffff !important; border: 1px solid #e2e8f0;" class="p-4 rounded-3xl shadow-sm flex flex-col justify-between hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <span style="color: #64748b !important; font-size: 10px; font-weight: 900; text-transform: uppercase;">Faturamento Live</span>
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

                    <!-- 2. Peças Vendidas -->
                    <div style="background-color: #ffffff !important; border: 1px solid #e2e8f0;" class="p-4 rounded-3xl shadow-sm flex flex-col justify-between hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <span style="color: #64748b !important; font-size: 10px; font-weight: 900; text-transform: uppercase;">Peças Vendidas</span>
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

                    <!-- 3. Compradoras -->
                    <div style="background-color: #ffffff !important; border: 1px solid #e2e8f0;" class="p-4 rounded-3xl shadow-sm flex flex-col justify-between hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <span style="color: #64748b !important; font-size: 10px; font-weight: 900; text-transform: uppercase;">Compradoras</span>
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
                            <span style="color: #64748b !important; font-size: 10px; font-weight: 900; text-transform: uppercase;">Ticket Médio</span>
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

                    <!-- 5. Peças Bipadas vs Vendidas (Conversão Isolada) -->
                    <div style="background-color: #ffffff !important; border: 1px solid #e2e8f0;" class="p-4 rounded-3xl shadow-sm flex flex-col justify-between hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <span style="color: #64748b !important; font-size: 10px; font-weight: 900; text-transform: uppercase;">Conversão Peças</span>
                            <div style="background-color: #ccfbf1; color: #0f766e;" class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-black">
                                <i class="fas fa-barcode"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            @if($kpis['has_bipagem'] && $kpis['taxa_conversao_itens'] !== null)
                                <strong style="color: #0f766e !important;" class="text-lg sm:text-xl font-black block tracking-tight">
                                    {{ $kpis['taxa_conversao_itens'] }}%
                                </strong>
                                <span style="color: #0d9488 !important;" class="text-[11px] font-bold mt-0.5 block">
                                    {{ $kpis['total_pecas_sacoladas'] }} de {{ $kpis['total_itens_apresentados'] }} bipadas
                                </span>
                            @else
                                <strong style="color: #64748b !important;" class="text-base font-black block tracking-tight" title="Live realizada sem uso de código de barras contínuo">
                                    Sem Bipagem
                                </strong>
                                <span style="color: #94a3b8 !important;" class="text-[10px] font-bold mt-0.5 block">
                                    Venda direta / Não contamina
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- 6. Engajamento no Chat -->
                    <div style="background-color: #ffffff !important; border: 1px solid #e2e8f0;" class="p-4 rounded-3xl shadow-sm flex flex-col justify-between hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <span style="color: #64748b !important; font-size: 10px; font-weight: 900; text-transform: uppercase;">Mensagens Chat</span>
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

                <!-- DIVISÃO POR CANAIS & STATUS DAS SACOLAS DA LIVE -->
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

                        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs" style="color: #64748b;">
                            <span>Taxa de conclusão da live:</span>
                            <strong style="color: #0f172a;">
                                @php
                                    $totGeral = $kpis['faturamento'] ?? 0;
                                    $totPago = (($statusCounts['fechada']->total_val ?? 0) + ($statusCounts['paga']->total_val ?? 0));
                                    $pctPago = $totGeral > 0 ? round(($totPago / $totGeral) * 100, 1) : 0;
                                @endphp
                                {{ $pctPago }}% faturado
                            </strong>
                        </div>
                    </div>
                </div>

                <!-- TOP COMPRADORAS & PEÇAS SACOLADAS -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Top Compradoras -->
                    <div style="background-color: #ffffff; border: 1px solid #e2e8f0;" class="rounded-3xl shadow-sm overflow-hidden flex flex-col justify-between">
                        <div>
                            <div style="border-bottom: 1px solid #e2e8f0;" class="p-4 sm:p-5 flex items-center justify-between">
                                <div>
                                    <h3 style="color: #0f172a !important;" class="text-sm sm:text-base font-black flex items-center gap-2">
                                        <i class="fas fa-crown text-yellow-500"></i> Top Compradoras da Live
                                    </h3>
                                    <p style="color: #64748b;" class="text-xs mt-0.5">Ranking das clientes que mais investiram nesta live</p>
                                </div>
                                <span style="background-color: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe;" class="text-xs font-black px-2.5 py-1 rounded-xl">
                                    {{ $topBuyers->count() }} clientes
                                </span>
                            </div>

                            <div class="divide-y divide-gray-100 max-h-[460px] overflow-y-auto">
                                @forelse($topBuyers as $idx => $buyer)
                                    <div class="p-3.5 sm:p-4 hover:bg-gray-50 transition flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div style="background-color: #f1f5f9; color: #334155;" class="w-7 h-7 rounded-xl flex items-center justify-center font-black text-xs shrink-0">
                                                {{ $idx + 1 }}º
                                            </div>
                                            <div class="min-w-0">
                                                <strong style="color: #0f172a !important;" class="text-xs sm:text-sm font-bold block truncate">
                                                    {{ $buyer->client_name ?: 'Cliente Avulsa' }}
                                                </strong>
                                                <div style="color: #64748b;" class="text-[11px] mt-0.5 flex items-center gap-2 truncate">
                                                    @if($buyer->instagram)
                                                        <span class="flex items-center gap-1"><i class="fab fa-instagram text-pink-600"></i> {{ $buyer->instagram }}</span>
                                                    @endif
                                                    @if($buyer->tiktok)
                                                        <span class="flex items-center gap-1"><i class="fab fa-tiktok text-gray-800"></i> {{ $buyer->tiktok }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="text-right shrink-0 flex items-center gap-3">
                                            <div>
                                                <strong style="color: #0f172a !important;" class="text-xs sm:text-sm font-black block">
                                                    R$ {{ number_format($buyer->total_spent, 2, ',', '.') }}
                                                </strong>
                                                <span style="color: #475569;" class="text-[11px] font-semibold">
                                                    {{ $buyer->total_items }} {{ $buyer->total_items == 1 ? 'peça' : 'peças' }}
                                                </span>
                                            </div>
                                            @if($buyer->clean_phone)
                                                <a href="https://wa.me/55{{ $buyer->clean_phone }}" target="_blank" style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;" class="w-8 h-8 rounded-xl flex items-center justify-center hover:bg-emerald-600 hover:text-white transition shadow-sm" title="Abrir WhatsApp">
                                                    <i class="fab fa-whatsapp text-sm"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="py-12 text-center text-gray-400">
                                        <i class="fas fa-users-slash text-3xl mb-2 text-gray-300"></i>
                                        <p style="color: #64748b;" class="text-xs font-bold">Nenhuma compradora registrada nesta transmissão ainda.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Peças Sacoladas na Live -->
                    <div style="background-color: #ffffff; border: 1px solid #e2e8f0;" class="rounded-3xl shadow-sm overflow-hidden flex flex-col justify-between">
                        <div>
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
                </div>
            </div>
        @endif

    @endif

</div>

<!-- Script de Filtragem Rápida na Tabela de Lives do Mês -->
<script>
    function filterLiveTable(mode) {
        const rows = document.querySelectorAll('.live-row');
        const btnAll = document.getElementById('tab-filter-all');
        const btnBipadas = document.getElementById('tab-filter-bipadas');
        const btnSemBipagem = document.getElementById('tab-filter-sem-bipagem');

        // Resetar estilos dos botões
        if (btnAll) {
            btnAll.style.backgroundColor = '#ffffff';
            btnAll.style.color = '#0f172a';
            btnAll.style.borderColor = '#cbd5e1';
        }
        if (btnBipadas) {
            btnBipadas.style.backgroundColor = '#ffffff';
            btnBipadas.style.color = '#047857';
            btnBipadas.style.borderColor = '#a7f3d0';
        }
        if (btnSemBipagem) {
            btnSemBipagem.style.backgroundColor = '#ffffff';
            btnSemBipagem.style.color = '#475569';
            btnSemBipagem.style.borderColor = '#cbd5e1';
        }

        // Ativar botão selecionado
        if (mode === 'all') {
            if (btnAll) {
                btnAll.style.backgroundColor = '#0f172a';
                btnAll.style.color = '#ffffff';
                btnAll.style.borderColor = '#0f172a';
            }
        } else if (mode === 'bipadas') {
            if (btnBipadas) {
                btnBipadas.style.backgroundColor = '#047857';
                btnBipadas.style.color = '#ffffff';
                btnBipadas.style.borderColor = '#047857';
            }
        } else if (mode === 'sem_bipagem') {
            if (btnSemBipagem) {
                btnSemBipagem.style.backgroundColor = '#475569';
                btnSemBipagem.style.color = '#ffffff';
                btnSemBipagem.style.borderColor = '#475569';
            }
        }

        // Filtrar as linhas
        rows.forEach(row => {
            const hasBipagem = row.getAttribute('data-has-bipagem') === '1';
            if (mode === 'all') {
                row.style.display = '';
            } else if (mode === 'bipadas') {
                row.style.display = hasBipagem ? '' : 'none';
            } else if (mode === 'sem_bipagem') {
                row.style.display = !hasBipagem ? '' : 'none';
            }
        });
    }
</script>
@endsection

@extends('layouts.app')

@section('title', 'Sacolas Vencidas')
@section('brand_route', 'admin.vencimentos')
@section('brand_icon', 'fas fa-triangle-exclamation')

@section('content')
<div class="space-y-6">

    {{-- Top Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-5 rounded-xl shadow-sm border border-gray-200">
        <div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-red-100 text-red-600 shadow-xs">
                    <i class="fas fa-triangle-exclamation text-lg"></i>
                </span>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 leading-tight">Sacolas Vencidas</h1>
                    <p class="text-sm text-gray-500">
                        Regra: sacolas armazenadas há mais de <strong class="text-red-600 font-semibold">31 dias</strong> • Exibindo clientes com peças vencidas
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <a href="{{ route('admin.sacolinha.gestao') }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 text-sm font-semibold transition duration-150">
                <i class="fas fa-shopping-bag text-indigo-500"></i>
                <span>Gestão de Sacolas</span>
            </a>
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 text-sm font-medium transition duration-150">
                <i class="fas fa-arrow-left text-gray-500"></i>
                <span>Dashboard</span>
            </a>
        </div>
    </div>

    {{-- 4 KPI Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Clientes com Vencidos --}}
        <div class="bg-white rounded-xl p-5 shadow-sm border border-red-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Clientes com Vencidos</p>
                <h3 class="text-2xl font-extrabold text-red-600 mt-1">
                    {{ $totais->total_clientes_com_vencidos ?? 0 }}
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">precisam de contato</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-500 flex items-center justify-center text-xl">
                <i class="fas fa-users"></i>
            </div>
        </div>

        {{-- Card 2: Total Peças Vencidas --}}
        <div class="bg-white rounded-xl p-5 shadow-sm border border-amber-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Peças Vencidas</p>
                <h3 class="text-2xl font-extrabold text-amber-600 mt-1">
                    {{ $totais->total_itens_vencidos ?? 0 }}
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">unidades em estoque</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center text-xl">
                <i class="fas fa-box-open"></i>
            </div>
        </div>

        {{-- Card 3: Linhas / Registros --}}
        <div class="bg-white rounded-xl p-5 shadow-sm border border-indigo-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Linhas de Sacola</p>
                <h3 class="text-2xl font-extrabold text-indigo-600 mt-1">
                    {{ $totais->total_linhas_vencidas ?? 0 }}
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">registros vencidos</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center text-xl">
                <i class="fas fa-tags"></i>
            </div>
        </div>

        {{-- Card 4: Valor Total Vencido --}}
        <div class="bg-white rounded-xl p-5 shadow-sm border border-red-200 flex items-center justify-between bg-gradient-to-br from-white to-red-50">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-600">Valor Total Vencido</p>
                <h3 class="text-2xl font-black text-red-700 mt-1">
                    R$ {{ number_format($totais->valor_total_vencido ?? 0, 2, ',', '.') }}
                </h3>
                <p class="text-xs text-red-600 font-medium mt-0.5">a regularizar</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-100 text-red-700 flex items-center justify-center text-xl shadow-xs">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
        </div>
    </div>

    {{-- Search / Filter Form --}}
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
        <form method="GET" action="{{ route('admin.vencimentos') }}" class="flex flex-col md:flex-row gap-3 items-stretch md:items-center">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                    <i class="fas fa-magnifying-glass"></i>
                </span>
                <input type="text"
                       name="q"
                       value="{{ $busca ?? '' }}"
                       placeholder="Buscar por nome do cliente, CPF, código ou e-mail..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition">
            </div>

            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-semibold shadow-xs transition duration-150">
                <i class="fas fa-filter"></i>
                <span>Filtrar</span>
            </button>

            @if(!empty($busca))
                <a href="{{ route('admin.vencimentos') }}"
                   class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium transition duration-150"
                   title="Limpar busca">
                    <i class="fas fa-times"></i>
                    <span>Limpar</span>
                </a>
            @endif
        </form>

        @if(!empty($busca))
            <div class="mt-2 text-xs text-gray-500 flex items-center gap-1.5">
                <i class="fas fa-info-circle text-blue-500"></i>
                <span>Filtrando por: <strong class="text-gray-800">"{{ $busca }}"</strong> ({{ $clientes->total() }} clientes encontrados)</span>
            </div>
        @endif
    </div>

    {{-- Clients List --}}
    <div class="space-y-4">
        @if($clientes->count() === 0)
            <div class="bg-white rounded-xl p-12 text-center shadow-sm border border-gray-200">
                <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-1">Nenhum vencimento encontrado!</h3>
                <p class="text-sm text-gray-500 max-w-md mx-auto">
                    Não há sacolinhas com mais de 31 dias de armazenamento para os critérios informados.
                </p>
                @if(!empty($busca))
                    <div class="mt-4">
                        <a href="{{ route('admin.vencimentos') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium">
                            <i class="fas fa-arrow-left"></i> Limpar filtros de busca
                        </a>
                    </div>
                @endif
            </div>
        @else
            @foreach($clientes as $c)
                @php
                    $lista = $itens[$c->user_id] ?? collect();
                    $iniciais = collect(explode(' ', trim($c->cliente_nome)))
                        ->filter()
                        ->take(2)
                        ->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))
                        ->implode('');
                    if (empty($iniciais)) $iniciais = 'CL';

                    $whatsDigits = $c->whatsapp ? preg_replace('/\D+/', '', $c->whatsapp) : '';
                    $whatsFormatado = $c->whatsapp;
                    if (strlen($whatsDigits) === 11) {
                        $whatsFormatado = sprintf('(%s) %s-%s', substr($whatsDigits, 0, 2), substr($whatsDigits, 2, 5), substr($whatsDigits, 7));
                    } elseif (strlen($whatsDigits) === 10) {
                        $whatsFormatado = sprintf('(%s) %s-%s', substr($whatsDigits, 0, 2), substr($whatsDigits, 2, 4), substr($whatsDigits, 6));
                    }
                    $whatsClean = $whatsDigits;
                    if ($whatsClean !== '' && strlen($whatsClean) <= 11 && !str_starts_with($whatsClean, '55')) {
                        $whatsClean = '55' . $whatsClean;
                    }
                @endphp

                <div x-data="{ open: true }" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition hover:shadow-md">
                    {{-- Client Card Header --}}
                    <div class="p-4 bg-gray-50/75 border-b border-gray-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="flex items-start sm:items-center gap-3">
                            {{-- Avatar Circle --}}
                            <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-red-500 to-rose-600 text-white font-bold flex items-center justify-center shadow-xs flex-shrink-0 text-sm">
                                {{ $iniciais }}
                            </div>

                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h2 class="text-base font-bold text-gray-900 leading-tight">
                                        {{ $c->cliente_nome }}
                                    </h2>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-200 text-gray-700">
                                        #{{ $c->codigo_cliente ?: $c->user_id }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-3 text-xs text-gray-500 mt-1 flex-wrap">
                                    @if(!empty($c->cpf))
                                        <span class="inline-flex items-center gap-1">
                                            <i class="far fa-id-card text-gray-400"></i> {{ $c->cpf }}
                                        </span>
                                    @endif

                                    @if(!empty($c->whatsapp))
                                        <a href="https://wa.me/{{ $whatsClean }}" target="_blank"
                                           class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-700 font-medium">
                                            <i class="fab fa-whatsapp"></i> {{ $whatsFormatado }}
                                        </a>
                                    @endif

                                    @if(!empty($c->primeiro_vencimento))
                                        <span class="inline-flex items-center gap-1 text-red-600 font-medium bg-red-50 px-2 py-0.5 rounded border border-red-100">
                                            <i class="far fa-calendar-times"></i>
                                            Vencido desde {{ \Carbon\Carbon::parse($c->primeiro_vencimento)->format('d/m/Y') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Right Side Badges & Actions --}}
                        <div class="flex items-center gap-3 flex-wrap justify-between lg:justify-end">
                            <div class="text-left lg:text-right mr-2">
                                <div class="text-xs text-gray-500">
                                    <strong class="text-gray-800 font-bold">{{ (int)($c->total_itens_vencidos ?? 0) }}</strong> peça(s) vencida(s)
                                </div>
                                <div class="text-lg font-black text-red-600">
                                    R$ {{ number_format($c->valor_total_vencido ?? 0, 2, ',', '.') }}
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                {{-- Enviar WhatsApp --}}
                                <form method="POST" action="{{ route('admin.vencimentos.whatsapp.send', $c->user_id) }}" class="inline"
                                      onsubmit="return confirm('Deseja enviar a notificação com o relatório PDF de vencimento via WhatsApp para este cliente?');">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition duration-150"
                                            title="Enviar PDF e aviso no WhatsApp">
                                        <i class="fab fa-whatsapp text-sm"></i>
                                        <span class="hidden sm:inline">Avisar WhatsApp</span>
                                    </button>
                                </form>

                                {{-- Ver Sacola Completa --}}
                                <a href="{{ route('admin.sacolinha.show', $c->user_id) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-xs transition duration-150"
                                   title="Abrir detalhes da sacola do cliente">
                                    <i class="fas fa-shopping-bag text-sm"></i>
                                    <span class="hidden sm:inline">Ver Sacola</span>
                                </a>

                                {{-- Expand / Collapse Button --}}
                                <button type="button"
                                        @click="open = !open"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white border border-gray-300 text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition"
                                        :title="open ? 'Recolher itens' : 'Expandir itens'">
                                    <i class="fas fa-chevron-down text-xs transform transition-transform duration-200"
                                       :class="{ 'rotate-180': open }"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Items Table (Collapsible) --}}
                    <div x-show="open" x-transition.opacity.duration.200ms class="border-t border-gray-100">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-gray-600">
                                <thead class="bg-gray-100/75 text-xs uppercase font-semibold text-gray-500 tracking-wider">
                                    <tr>
                                        <th class="px-4 py-2.5">Item / Produto</th>
                                        <th class="px-4 py-2.5">Detalhes</th>
                                        <th class="px-4 py-2.5">Adicionado Em</th>
                                        <th class="px-4 py-2.5">Vencimento</th>
                                        <th class="px-4 py-2.5 text-right">Preço</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @forelse($lista as $l)
                                        @php
                                            $detalhes = [];
                                            if (!empty($l->item_sku)) $detalhes[] = 'SKU: ' . $l->item_sku;
                                            if (!empty($l->item_brand)) $detalhes[] = 'Marca: ' . $l->item_brand;
                                            if (!empty($l->item_color)) $detalhes[] = 'Cor: ' . $l->item_color;
                                            if (!empty($l->item_size)) $detalhes[] = 'Tam: ' . $l->item_size;
                                            if (!empty($l->item_estado)) $detalhes[] = 'Estado: ' . $l->item_estado;

                                            $imgSrc = !empty($l->item_image) ? asset('storage/' . ltrim($l->item_image, '/')) : null;
                                            $vencimentoDate = !empty($l->vencimento) ? \Carbon\Carbon::parse($l->vencimento) : null;
                                            $diasAtraso = $vencimentoDate ? (int) $vencimentoDate->diffInDays(now(), false) : 0;
                                        @endphp
                                        <tr class="hover:bg-gray-50/80 transition duration-150">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    @if($imgSrc)
                                                        <img src="{{ $imgSrc }}"
                                                             alt="{{ $l->item_name }}"
                                                             class="w-10 h-10 rounded-lg object-cover border border-gray-200 flex-shrink-0"
                                                             onerror="this.style.display='none'">
                                                    @else
                                                        <div class="w-10 h-10 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400 flex-shrink-0">
                                                            <i class="fas fa-shirt text-sm"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <div class="font-semibold text-gray-900 text-sm leading-tight">
                                                            {{ $l->item_name ?? ('Item #' . $l->item_id) }}
                                                        </div>
                                                        <div class="text-xs text-gray-400 mt-0.5">
                                                            ID Sacola: #{{ $l->sacolinha_id }}
                                                            @if(!empty($l->item_id)) • Item ID: #{{ $l->item_id }} @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="text-xs text-gray-600">
                                                    {{ count($detalhes) ? implode(' • ', $detalhes) : 'Sem detalhes adicionais' }}
                                                </div>
                                                @if(!empty($l->obs))
                                                    <div class="mt-1 text-xs text-amber-700 bg-amber-50 px-2 py-0.5 rounded inline-block border border-amber-200">
                                                        <i class="far fa-sticky-note mr-1"></i>{{ $l->obs }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                                                {{ !empty($l->add_at) ? \Carbon\Carbon::parse($l->add_at)->format('d/m/Y H:i') : '-' }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                @if($vencimentoDate)
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                                        <i class="far fa-clock"></i>
                                                        {{ $vencimentoDate->format('d/m/Y') }}
                                                        @if($diasAtraso > 0)
                                                            <span class="text-[10px] opacity-80">({{ $diasAtraso }}d atrasado)</span>
                                                        @endif
                                                    </span>
                                                @else
                                                    <span class="text-xs text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                                <div class="text-sm font-bold text-red-600">
                                                    R$ {{ number_format(($l->quantity ?? 1) * ($l->price ?? 0), 2, ',', '.') }}
                                                </div>
                                                @if(($l->quantity ?? 1) > 1)
                                                    <div class="text-[11px] text-gray-400">
                                                        {{ $l->quantity }}x R$ {{ number_format($l->price ?? 0, 2, ',', '.') }}
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-6 text-center text-gray-400 text-sm">
                                                Nenhum item individual encontrado.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Pagination Links --}}
            <div class="pt-2">
                {{ $clientes->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

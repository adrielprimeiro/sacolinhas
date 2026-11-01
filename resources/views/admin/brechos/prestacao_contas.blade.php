@extends('layouts.app')

@section('title', 'Prestação de Contas - ' . $brecho->nome)
@section('brand_icon', 'fas fa-file-invoice-dollar')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ activeTab: 'pecas', repasseModalOpen: false }">
    {{-- Mensagens Flash --}}
    @if(session('success'))
        <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded-md shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-600 mr-3 text-lg"></i>
                <p class="text-sm text-green-800 font-semibold">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-600 mr-3 text-lg"></i>
                <p class="text-sm text-red-800 font-semibold">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    @if(session('info'))
        <div class="mb-4 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-md shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-600 mr-3 text-lg"></i>
                <p class="text-sm text-blue-800 font-semibold">{{ session('info') }}</p>
            </div>
        </div>
    @endif

    {{-- Breadcrumb e Top Header --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <a href="{{ route('admin.brechos.index') }}" class="hover:text-indigo-600 transition flex items-center gap-1 font-medium">
                    <i class="fas fa-store"></i> Gerenciar Brechós
                </a>
                <span>&rsaquo;</span>
                <span class="text-gray-700 font-semibold">Prestação de Contas</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 flex items-center gap-3">
                <i class="fas fa-handshake text-indigo-600"></i>
                {{ $brecho->nome }}
                <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-2.5 py-1 rounded-full border border-indigo-200">
                    SKU: {{ $brecho->prefixo_codigo ?: 'PAR' }}
                </span>
            </h1>
            <p class="text-xs sm:text-sm text-gray-600 mt-1">
                Painel financeiro de consignação, acompanhamento de peças vendidas e controle de repasses via carteira.
            </p>
        </div>

        {{-- Ações Rápidas --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- Sincronizar Vendas na Carteira --}}
            <form action="{{ route('admin.brechos.sincronizar_vendas', $brecho) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center px-3.5 py-2 bg-white text-indigo-700 hover:text-indigo-900 border border-indigo-300 rounded-lg text-xs font-bold shadow-xs hover:bg-indigo-50 transition" title="Verifica se há vendas pagas ainda não creditadas na carteira">
                    <i class="fas fa-sync-alt mr-2 text-indigo-500"></i> Sincronizar Vendas
                </button>
            </form>

            {{-- Registrar Repasse / Pix Pago --}}
            <button type="button" @click="repasseModalOpen = true" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg text-xs font-bold shadow-sm transition">
                <i class="fas fa-money-bill-wave mr-2"></i> Registrar Repasse (Pix)
            </button>

            {{-- Imprimir --}}
            <button type="button" onclick="window.print()" class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg text-xs font-bold transition">
                <i class="fas fa-print mr-1.5"></i> Imprimir
            </button>
        </div>
    </div>

    {{-- Card com Informações Comerciais do Parceiro --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6 shadow-xs flex flex-wrap items-center justify-between gap-4 text-xs">
        <div class="flex flex-wrap items-center gap-6">
            <div>
                <span class="text-gray-500 block">Fornecedor Vinculado (Mania):</span>
                <span class="font-bold text-gray-900 flex items-center gap-1.5 mt-0.5">
                    <i class="fas fa-user-circle text-indigo-500"></i>
                    {{ $brecho->fornecedor_nome ?: ($user ? $user->name : 'Não vinculado') }}
                    @if($user)
                        <span class="text-gray-400 font-mono text-[10px]">#{{ $user->id }}</span>
                    @endif
                </span>
            </div>
            <div>
                <span class="text-gray-500 block">Chave Pix para Repasse:</span>
                <span class="font-bold text-emerald-800 flex items-center gap-1.5 mt-0.5">
                    <i class="fas fa-qrcode text-emerald-600"></i>
                    {{ $brecho->chave_pix ?: 'Chave não cadastrada' }}
                    @if($brecho->tipo_chave_pix)
                        <span class="text-gray-400 uppercase text-[10px]">({{ $brecho->tipo_chave_pix }})</span>
                    @endif
                </span>
            </div>
            <div>
                <span class="text-gray-500 block">Comissão da Mania:</span>
                <span class="font-bold text-gray-900 mt-0.5 block">
                    {{ number_format((float)($brecho->comissao_porcentagem ?: 20), 1, ',', '.') }}% sobre a venda
                </span>
            </div>
            <div>
                <span class="text-gray-500 block">Taxa de Cadastro / Peça:</span>
                <span class="font-bold text-gray-900 mt-0.5 block">
                    R$ {{ number_format((float)($brecho->taxa_cadastro_peca ?: 1.80), 2, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-gray-500">Status Parceiro:</span>
            @if($brecho->ativo)
                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full font-bold text-[11px]">Ativo</span>
            @else
                <span class="px-2 py-0.5 bg-red-100 text-red-800 rounded-full font-bold text-[11px]">Inativo</span>
            @endif
        </div>
    </div>

    {{-- Cards de Indicadores Financeiros (KPIs) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Card 1: Saldo a Repassar (Destaque Carteira) --}}
        <div class="bg-gradient-to-br from-indigo-900 to-indigo-950 rounded-xl p-5 text-white shadow-md relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-white/10 text-7xl font-bold">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="text-xs uppercase font-bold text-indigo-300 tracking-wider flex items-center gap-1.5 mb-1">
                <i class="fas fa-wallet text-indigo-400"></i> Saldo Atual na Carteira
            </div>
            <div class="text-3xl font-black text-white mt-1">
                R$ {{ number_format($saldoCarteira, 2, ',', '.') }}
            </div>
            <div class="mt-2 text-[11px] text-indigo-200 flex items-center justify-between">
                <span>Disponível para repasse</span>
                <button type="button" @click="repasseModalOpen = true" class="underline text-emerald-300 font-bold hover:text-emerald-200">
                    Pagar Saldo &rarr;
                </button>
            </div>
        </div>

        {{-- Card 2: Total Vendido (Bruto) --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-xs">
            <div class="text-xs uppercase font-bold text-gray-500 tracking-wider flex items-center gap-1.5 mb-1">
                <i class="fas fa-chart-line text-emerald-600"></i> Total Bruto Vendido
            </div>
            <div class="text-2xl font-black text-gray-900 mt-1">
                R$ {{ number_format($valorBrutoVendido, 2, ',', '.') }}
            </div>
            <div class="mt-2 text-xs text-gray-500 flex items-center gap-1.5">
                <span class="font-bold text-emerald-700">{{ $pecasVendidas }} peças</span> vendidas
                <span class="text-gray-300">|</span>
                <span>{{ $totalPecas > 0 ? number_format(($pecasVendidas / $totalPecas) * 100, 1) : 0 }}% do lote</span>
            </div>
        </div>

        {{-- Card 3: Repasse Líquido Gerado --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-xs">
            <div class="text-xs uppercase font-bold text-gray-500 tracking-wider flex items-center gap-1.5 mb-1">
                <i class="fas fa-coins text-amber-600"></i> Repasse Líquido Gerado
            </div>
            <div class="text-2xl font-black text-emerald-700 mt-1">
                R$ {{ number_format($valorRepasseVendido, 2, ',', '.') }}
            </div>
            <div class="mt-2 text-xs text-gray-500 flex items-center gap-1">
                <span>Comissão retida Mania:</span>
                <strong class="text-gray-800">R$ {{ number_format($comissaoManiaVendido, 2, ',', '.') }}</strong>
            </div>
        </div>

        {{-- Card 4: Total Já Pago / Repassado --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-xs">
            <div class="text-xs uppercase font-bold text-gray-500 tracking-wider flex items-center gap-1.5 mb-1">
                <i class="fas fa-check-double text-blue-600"></i> Total Já Repassado
            </div>
            <div class="text-2xl font-black text-gray-900 mt-1">
                R$ {{ number_format($totalRepassadoPago, 2, ',', '.') }}
            </div>
            <div class="mt-2 text-xs text-gray-500">
                Total quitado via Pix/transferências
            </div>
        </div>
    </div>

    {{-- Mini Cards de Peças e Estoque --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6 text-xs">
        <div class="bg-white rounded-lg border border-gray-200 p-3 flex items-center justify-between">
            <span class="text-gray-600 font-medium">Total de Peças:</span>
            <span class="font-bold text-gray-900 text-sm">{{ $totalPecas }}</span>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-3 flex items-center justify-between">
            <span class="text-gray-600 font-medium">Em Estoque (Mania):</span>
            <span class="font-bold text-indigo-700 text-sm">{{ $pecasEstoque }}</span>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-3 flex items-center justify-between">
            <span class="text-gray-600 font-medium">Em Sacolinhas:</span>
            <span class="font-bold text-amber-600 text-sm">{{ $pecasSacolinha }}</span>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-3 flex items-center justify-between">
            <span class="text-gray-600 font-medium">Peças Vendidas:</span>
            <span class="font-bold text-emerald-700 text-sm">{{ $pecasVendidas }}</span>
        </div>
    </div>

    {{-- Abas de Navegação --}}
    <div class="bg-white shadow rounded-xl border border-gray-200 overflow-hidden mb-8">
        <div class="border-b border-gray-200 bg-gray-50 px-4 pt-3 flex gap-2">
            <button type="button" @click="activeTab = 'pecas'" 
                    :class="activeTab === 'pecas' ? 'bg-white text-indigo-700 border-indigo-500 border-b-2 font-bold shadow-xs' : 'text-gray-600 hover:text-gray-900 font-medium'"
                    class="px-4 py-2.5 text-xs rounded-t-lg transition flex items-center gap-2">
                <i class="fas fa-boxes"></i>
                Peças em Consignação
                <span class="bg-gray-200 text-gray-800 text-[10px] px-2 py-0.5 rounded-full font-bold">{{ $totalPecas }}</span>
            </button>
            <button type="button" @click="activeTab = 'extrato'" 
                    :class="activeTab === 'extrato' ? 'bg-white text-indigo-700 border-indigo-500 border-b-2 font-bold shadow-xs' : 'text-gray-600 hover:text-gray-900 font-medium'"
                    class="px-4 py-2.5 text-xs rounded-t-lg transition flex items-center gap-2">
                <i class="fas fa-history"></i>
                Extrato da Carteira (Repasses & Vendas)
                <span class="bg-emerald-100 text-emerald-800 text-[10px] px-2 py-0.5 rounded-full font-bold">{{ $carteiraMovimentacoes->count() }}</span>
            </button>
        </div>

        {{-- CONTEÚDO DA ABA 1: Peças em Consignação --}}
        <div x-show="activeTab === 'pecas'" class="p-4">
            {{-- Filtros da Tabela de Peças --}}
            <form method="GET" action="{{ route('admin.brechos.prestacao_contas', $brecho) }}" class="flex flex-col sm:flex-row items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-1.5 w-full sm:w-auto">
                    <a href="{{ route('admin.brechos.prestacao_contas', ['brecho' => $brecho->id, 'status' => 'todos']) }}" 
                       class="px-3 py-1.5 text-xs rounded-lg font-bold border transition {{ $statusFiltro === 'todos' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                        Todas ({{ $totalPecas }})
                    </a>
                    <a href="{{ route('admin.brechos.prestacao_contas', ['brecho' => $brecho->id, 'status' => 'disponivel']) }}" 
                       class="px-3 py-1.5 text-xs rounded-lg font-bold border transition {{ $statusFiltro === 'disponivel' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                        Em Estoque ({{ $pecasEstoque }})
                    </a>
                    <a href="{{ route('admin.brechos.prestacao_contas', ['brecho' => $brecho->id, 'status' => 'em_sacolinha']) }}" 
                       class="px-3 py-1.5 text-xs rounded-lg font-bold border transition {{ $statusFiltro === 'em_sacolinha' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                        Sacolinhas ({{ $pecasSacolinha }})
                    </a>
                    <a href="{{ route('admin.brechos.prestacao_contas', ['brecho' => $brecho->id, 'status' => 'vendido']) }}" 
                       class="px-3 py-1.5 text-xs rounded-lg font-bold border transition {{ $statusFiltro === 'vendido' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                        Vendidas ({{ $pecasVendidas }})
                    </a>
                </div>

                <div class="relative w-full sm:w-64">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por código SKU ou nome..." 
                           class="w-full text-xs rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pl-8 py-1.5">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                        <i class="fas fa-search text-xs"></i>
                    </div>
                </div>
            </form>

            {{-- Tabela de Peças --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left">SKU / Código</th>
                            <th scope="col" class="px-4 py-3 text-left">Produto</th>
                            <th scope="col" class="px-3 py-3 text-center">Tamanho</th>
                            <th scope="col" class="px-3 py-3 text-right">Preço Venda</th>
                            <th scope="col" class="px-3 py-3 text-right">Comissão Mania</th>
                            <th scope="col" class="px-3 py-3 text-right">Repasse Parceiro</th>
                            <th scope="col" class="px-3 py-3 text-center">Status</th>
                            <th scope="col" class="px-4 py-3 text-left">Venda / Destino</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($items as $item)
                        @php
                            $precoVenda = (float) $item->preco;
                            $repasseLiquido = (float) ($item->custo > 0 ? $item->custo : ($precoVenda * 0.8));
                            $comissaoMania = max(0, $precoVenda - $repasseLiquido);
                        @endphp
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="font-mono font-bold text-xs bg-indigo-50 text-indigo-800 px-2 py-0.5 rounded border border-indigo-200">
                                    {{ $item->codigo }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-bold text-gray-900 text-xs">{{ $item->nome_do_produto }}</div>
                                <div class="text-[11px] text-gray-500">{{ $item->marca ?: 'Sem marca' }} &bull; {{ $item->estado ?: 'Seminovo' }}</div>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-center text-xs font-semibold text-gray-700">
                                {{ $item->tamanho ?: '-' }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-right text-xs font-bold text-gray-900">
                                R$ {{ number_format($precoVenda, 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-right text-xs font-medium text-gray-500">
                                R$ {{ number_format($comissaoMania, 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-right text-xs font-bold text-emerald-700">
                                R$ {{ number_format($repasseLiquido, 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-center">
                                @if($item->status === 'disponivel')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                        <i class="fas fa-check-circle mr-1"></i> Em Estoque
                                    </span>
                                @elseif($item->status === 'em_sacolinha')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                        <i class="fas fa-shopping-bag mr-1"></i> Na Sacolinha
                                    </span>
                                @elseif($item->status === 'vendido')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        <i class="fas fa-check-double mr-1"></i> Vendido
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if($item->pedido_info)
                                    <div class="font-bold text-emerald-800 flex items-center gap-1">
                                        <i class="fas fa-receipt text-[11px]"></i> Pedido #{{ $item->pedido_info->pedido_id }}
                                    </div>
                                    <div class="text-[10px] text-gray-400">
                                        Pago em {{ \Carbon\Carbon::parse($item->pedido_info->data_pedido)->format('d/m/Y') }}
                                    </div>
                                @elseif($item->sacolinha_info)
                                    <div class="text-amber-800 font-semibold truncate max-w-[180px]">
                                        {{ $item->sacolinha_info->cliente_nome }}
                                    </div>
                                    @if($item->sacolinha_info->live_tema)
                                        <div class="text-[10px] text-gray-400 truncate max-w-[180px]">
                                            Live: {{ $item->sacolinha_info->live_tema }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-gray-400 italic">Disponível para venda</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-sm text-gray-500">
                                Nenhuma peça encontrada para os filtros selecionados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $items->links() }}
            </div>
        </div>

        {{-- CONTEÚDO DA ABA 2: Extrato da Carteira do Parceiro --}}
        <div x-show="activeTab === 'extrato'" x-cloak class="p-4">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-wallet text-indigo-600"></i> Movimentações da Carteira
                    </h3>
                    <p class="text-xs text-gray-500">Histórico de créditos gerados por peças vendidas e baixas de pagamentos/Pix realizados.</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-gray-500 block">Saldo Atual:</span>
                    <span class="text-lg font-black {{ $saldoCarteira > 0 ? 'text-emerald-700' : 'text-gray-700' }}">
                        R$ {{ number_format($saldoCarteira, 2, ',', '.') }}
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left">Data</th>
                            <th scope="col" class="px-3 py-3 text-center">Tipo</th>
                            <th scope="col" class="px-4 py-3 text-left">Descrição</th>
                            <th scope="col" class="px-4 py-3 text-right">Valor</th>
                            <th scope="col" class="px-4 py-3 text-right">Saldo Resultante</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($carteiraMovimentacoes as $mov)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600">
                                {{ \Carbon\Carbon::parse($mov->data_movimentacao)->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-center">
                                @if($mov->tipo_movimentacao === 'credito')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        <i class="fas fa-arrow-down mr-0.5"></i> Crédito
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">
                                        <i class="fas fa-arrow-up mr-0.5"></i> Repasse Pago
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-800 font-medium">
                                {{ $mov->descricao }}
                                @if($mov->observacoes)
                                    <div class="text-[11px] text-gray-400 mt-0.5 font-normal">{{ $mov->observacoes }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-xs font-bold {{ $mov->tipo_movimentacao === 'credito' ? 'text-emerald-700' : 'text-red-700' }}">
                                {{ $mov->tipo_movimentacao === 'credito' ? '+' : '-' }} R$ {{ number_format((float)$mov->valor, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-xs font-black text-gray-900">
                                R$ {{ number_format((float)$mov->saldo_atual, 2, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500">
                                Nenhuma movimentação registrada na carteira deste parceiro.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MODAL: Registrar Repasse / Pix Pago --}}
    <div x-show="repasseModalOpen" x-cloak class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div @click="repasseModalOpen = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('admin.brechos.registrar_repasse', $brecho) }}" method="POST">
                    @csrf
                    <div class="bg-white px-6 pt-5 pb-4 sm:p-6">
                        <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100">
                            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                <i class="fas fa-money-bill-wave text-emerald-600"></i> Registrar Repasse (Pix)
                            </h3>
                            <button type="button" @click="repasseModalOpen = false" class="text-gray-400 hover:text-gray-600 text-sm">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        {{-- Box de Conferência da Chave Pix --}}
                        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3.5 mb-4 text-xs">
                            <div class="text-emerald-900 font-bold flex items-center gap-1.5 mb-1">
                                <i class="fas fa-qrcode text-emerald-600"></i> Dados Bancários / Pix do Parceiro:
                            </div>
                            <div class="text-emerald-950 font-black text-sm">
                                {{ $brecho->chave_pix ?: 'Nenhuma chave Pix informada no cadastro!' }}
                            </div>
                            <div class="text-emerald-800 text-[11px] mt-0.5">
                                Favorecido: <strong>{{ $brecho->nome }}</strong> &bull; Saldo a pagar: <strong>R$ {{ number_format($saldoCarteira, 2, ',', '.') }}</strong>
                            </div>
                        </div>

                        <div class="space-y-4 text-sm">
                            <div>
                                <label class="block font-medium text-gray-700 text-xs mb-1">Valor do Repasse (R$) *</label>
                                <input type="number" step="0.01" min="0.01" name="valor" 
                                       value="{{ $saldoCarteira > 0 ? number_format($saldoCarteira, 2, '.', '') : '' }}" required
                                       class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 text-sm py-2 px-3 font-bold text-emerald-800">
                                <span class="text-[10px] text-gray-500">Este valor será debitado da carteira do parceiro baixando o saldo pendente.</span>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-medium text-gray-700 text-xs mb-1">Data do Pagamento *</label>
                                    <input type="date" name="data_pagamento" value="{{ date('Y-m-d') }}" required
                                           class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 text-xs py-2 px-3">
                                </div>
                                <div>
                                    <label class="block font-medium text-gray-700 text-xs mb-1">Forma de Pagamento *</label>
                                    <select name="forma_pagamento" required class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 text-xs py-2 px-3">
                                        <option value="Pix" selected>Pix</option>
                                        <option value="Transferência">Transferência Bancária</option>
                                        <option value="Dinheiro">Dinheiro</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-700 text-xs mb-1">ID da Transação / Comprovante (Opcional)</label>
                                <input type="text" name="comprovante_transacao" placeholder="Ex: E202611011234567890 ou código do Pix"
                                       class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 text-xs py-2 px-3">
                            </div>

                            <div>
                                <label class="block font-medium text-gray-700 text-xs mb-1">Observações do Repasse (Opcional)</label>
                                <textarea name="observacoes" rows="2" placeholder="Detalhes adicionais do pagamento..."
                                          class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 text-xs py-2 px-3"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-3 flex justify-end gap-2">
                        <button type="button" @click="repasseModalOpen = false" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-100">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-xs font-bold hover:bg-emerald-700 shadow-sm flex items-center gap-1.5">
                            <i class="fas fa-check"></i> Confirmar Baixa na Carteira
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

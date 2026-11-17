@extends('layouts.app')

@section('title', 'Relatório ODS - Novos Clientes e Faturamento')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl" x-data="{ filtroTabela: 'todos', buscaCliente: '' }">
    <!-- Cabeçalho -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-bullseye text-indigo-600"></i>
                Relatório ODS — Novos Clientes & Faturamento
            </h1>
            <p class="text-sm text-gray-600 mt-1">
                Acompanhe quantos novos clientes entraram no intervalo selecionado e quanto faturamento foi gerado exclusivamente por eles.
            </p>
        </div>

        <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-900 text-xs font-bold">
            <i class="far fa-calendar-alt text-indigo-600"></i>
            <span>Período: {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} até {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}</span>
        </div>
    </div>

    <!-- 1. SELETOR DE INTERVALO DE TEMPO -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <form action="{{ route('admin.relatorios.ods') }}" method="GET" id="odsFilterForm">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-3">
                    <label for="data_inicio" class="block text-xs font-bold text-gray-700 uppercase mb-1">
                        Data Inicial
                    </label>
                    <input type="date"
                           id="data_inicio"
                           name="data_inicio"
                           value="{{ $dataInicio }}"
                           class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm text-gray-900 bg-white focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div class="md:col-span-3">
                    <label for="data_fim" class="block text-xs font-bold text-gray-700 uppercase mb-1">
                        Data Final
                    </label>
                    <input type="date"
                           id="data_fim"
                           name="data_fim"
                           value="{{ $dataFim }}"
                           class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm text-gray-900 bg-white focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div class="md:col-span-4">
                    <label for="escopo_vendas" class="block text-xs font-bold text-gray-700 uppercase mb-1">
                        Cálculo do Faturamento dos Novos Clientes
                    </label>
                    <select id="escopo_vendas"
                            name="escopo_vendas"
                            class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm text-gray-900 bg-white focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="periodo" {{ $escopoVendas === 'periodo' ? 'selected' : '' }}>
                            Compras realizadas dentro deste mesmo período
                        </option>
                        <option value="total" {{ $escopoVendas === 'total' ? 'selected' : '' }}>
                            Todas as compras acumuladas desses novos clientes
                        </option>
                    </select>
                </div>

                <div class="md:col-span-2 flex gap-2">
                    <button type="submit"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow transition text-sm flex items-center justify-center gap-1.5">
                        <i class="fas fa-filter"></i>
                        <span>Filtrar</span>
                    </button>
                </div>
            </div>

            <!-- Botões Rápidos de Período -->
            <div class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold text-gray-500 uppercase mr-1">Atalhos de Período:</span>
                @foreach($atalhosPeriodo as $key => $atalho)
                    @php
                        $isActiveAtalho = ($dataInicio === $atalho['inicio'] && $dataFim === $atalho['fim']);
                    @endphp
                    <a href="{{ route('admin.relatorios.ods', ['data_inicio' => $atalho['inicio'], 'data_fim' => $atalho['fim'], 'escopo_vendas' => $escopoVendas]) }}"
                       class="px-3 py-1 rounded-full text-xs font-semibold border transition {{ $isActiveAtalho ? 'bg-indigo-600 text-white border-indigo-700 shadow-sm' : 'bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200' }}">
                        {{ $atalho['label'] }}
                    </a>
                @endforeach
            </div>
        </form>
    </div>

    <!-- 2 & 3. CARDS PRINCIPAIS DE INDICADORES (NOVOS CLIENTES + FATURAMENTO DELES) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Card 1: Novos Clientes no Intervalo -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Novos Clientes no Período</span>
                    <div class="text-3xl font-extrabold text-gray-900 mt-2">
                        {{ number_format($totalNovosClientes, 0, ',', '.') }}
                    </div>
                </div>
                <div class="w-11 h-11 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-lg font-bold">
                    <i class="fas fa-user-plus"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                <span class="text-gray-600">
                    Compradores ativos: <strong class="text-gray-900">{{ $novosClientesCompradores }}</strong>
                </span>
                <span class="px-2 py-0.5 rounded-full font-bold bg-indigo-100 text-indigo-800">
                    {{ number_format($taxaConversaoNovos, 1, ',', '.') }}% compraram
                </span>
            </div>
        </div>

        <!-- Card 2: Faturamento Total desses Clientes Novos -->
        <div class="bg-white rounded-xl shadow-sm border-2 border-green-500 p-5 flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-green-800">Faturamento dos Novos Clientes</span>
                    <div class="text-3xl font-extrabold text-green-700 mt-2">
                        R$ {{ number_format($faturamentoTotalNovosClientes, 2, ',', '.') }}
                    </div>
                </div>
                <div class="w-11 h-11 rounded-lg bg-green-100 text-green-700 flex items-center justify-center text-lg font-bold">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-600">
                <span>Pedidos + Sacolinhas Ativas</span>
                <span class="font-bold text-green-800">100% Clientes Novos</span>
            </div>
        </div>

        <!-- Card 3: Faturamento em Pedidos Fechados -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Em Pedidos Fechados</span>
                    <div class="text-2xl font-extrabold text-gray-900 mt-2">
                        R$ {{ number_format($totalPedidosValor, 2, ',', '.') }}
                    </div>
                </div>
                <div class="w-11 h-11 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-lg font-bold">
                    <i class="fas fa-receipt"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 text-xs text-gray-600 space-y-1">
                <div class="flex justify-between">
                    <span>Qtd. Pedidos: <strong>{{ $totalPedidosQtd }}</strong></span>
                    <span class="text-green-700 font-semibold">Pagos: R$ {{ number_format($totalPedidosAprovadosValor, 2, ',', '.') }}</span>
                </div>
                @if($totalPedidosPendentesValor > 0)
                    <div class="flex justify-between text-yellow-800">
                        <span>Aguardando Pagamento:</span>
                        <span class="font-semibold">R$ {{ number_format($totalPedidosPendentesValor, 2, ',', '.') }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Card 4: Em Sacolinhas Abertas (Lives) + Ticket Médio -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Em Sacolinhas Ativas (Lives)</span>
                    <div class="text-2xl font-extrabold text-gray-900 mt-2">
                        R$ {{ number_format($totalSacolinhasValor, 2, ',', '.') }}
                    </div>
                </div>
                <div class="w-11 h-11 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center text-lg font-bold">
                    <i class="fas fa-shopping-bag"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-600">
                <span>Peças em sacola: <strong class="text-gray-900">{{ $totalSacolinhasPecas }}</strong></span>
                <span>Ticket Médio: <strong class="text-indigo-700">R$ {{ number_format($ticketMedioPorComprador, 2, ',', '.') }}</strong></span>
            </div>
        </div>
    </div>

    <!-- LISTA DETALHADA DOS NOVOS CLIENTES DO INTERVALO -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-4">
            <div>
                <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-users text-indigo-600"></i>
                    Detalhamento dos Novos Clientes no Período ({{ $totalNovosClientes }})
                </h2>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button"
                        @click="filtroTabela = 'todos'"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition"
                        :class="filtroTabela === 'todos' ? 'bg-gray-800 text-white border-gray-900' : 'bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200'">
                    Todos ({{ $totalNovosClientes }})
                </button>
                <button type="button"
                        @click="filtroTabela = 'compradores'"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition"
                        :class="filtroTabela === 'compradores' ? 'bg-green-700 text-white border-green-800' : 'bg-green-50 text-green-800 border-green-300 hover:bg-green-100'">
                    Com Faturamento ({{ $novosClientesCompradores }})
                </button>
                <button type="button"
                        @click="filtroTabela = 'sem_compra'"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition"
                        :class="filtroTabela === 'sem_compra' ? 'bg-yellow-600 text-white border-yellow-700' : 'bg-yellow-50 text-yellow-800 border-yellow-300 hover:bg-yellow-100'">
                    Sem Compra ({{ $totalNovosClientes - $novosClientesCompradores }})
                </button>

                <input type="text"
                       x-model="buscaCliente"
                       placeholder="Filtrar cliente na lista..."
                       class="border border-gray-300 rounded-lg py-1.5 px-3 text-xs text-gray-900 bg-white focus:ring-indigo-500 focus:border-indigo-500 w-52">
            </div>
        </div>

        @php
            $canVerCliente  = auth()->check() && auth()->user()->canAccessModule('clientes');
            $canVerSacola   = auth()->check() && auth()->user()->canAccessModule('sacolinhas');
            $mostrarColAcoes = $canVerCliente || $canVerSacola;
        @endphp
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Cliente</th>
                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Contato / Redes</th>
                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Data de Cadastro</th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Pedidos Fechados</th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Sacolinha Ativa</th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Faturamento Total</th>
                        @if($mostrarColAcoes)
                        <th class="px-5 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Ações</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($clientesDetalhados as $cli)
                        @php
                            $searchBlob = strtolower(
                                ($cli->name ?? '') . ' ' .
                                ($cli->apelido ?? '') . ' ' .
                                ($cli->instagram ?? '') . ' ' .
                                ($cli->tiktok ?? '') . ' ' .
                                ($cli->email ?? '') . ' ' .
                                ($cli->whatsapp ?? '') . ' ' .
                                ($cli->phone ?? '')
                            );
                        @endphp
                        <tr x-show="(filtroTabela === 'todos' || (filtroTabela === 'compradores' && {{ $cli->comprou ? 'true' : 'false' }}) || (filtroTabela === 'sem_compra' && {{ !$cli->comprou ? 'true' : 'false' }})) && (!buscaCliente || {{ json_encode($searchBlob) }}.includes(buscaCliente.toLowerCase()))"
                            class="hover:bg-gray-50 transition">
                            <!-- Nome -->
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-mono text-gray-400">#{{ $cli->id }}</span>
                                    <div>
                                        <div class="font-bold text-sm text-gray-900">{{ $cli->name }}</div>
                                        @if($cli->cidade || $cli->estado)
                                            <div class="text-xs text-gray-500">{{ $cli->cidade }}{{ $cli->estado ? '/' . $cli->estado : '' }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Contato / Redes -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs text-gray-700">
                                @if($cli->whatsapp || $cli->phone)
                                    <div><i class="fab fa-whatsapp text-green-600 mr-1"></i>{{ $cli->whatsapp ?: $cli->phone }}</div>
                                @endif
                                @if($cli->instagram)
                                    <div class="text-pink-700"><i class="fab fa-instagram mr-1"></i>{{ '@' . ltrim($cli->instagram, '@') }}</div>
                                @endif
                                @if($cli->tiktok)
                                    <div class="text-gray-800"><i class="fab fa-tiktok mr-1"></i>{{ '@' . ltrim($cli->tiktok, '@') }}</div>
                                @endif
                                @if(!$cli->whatsapp && !$cli->phone && !$cli->instagram && !$cli->tiktok)
                                    <span class="text-gray-400">{{ $cli->email }}</span>
                                @endif
                            </td>

                            <!-- Data Cadastro -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs text-gray-700">
                                {{ \Carbon\Carbon::parse($cli->data_entrada)->format('d/m/Y H:i') }}
                            </td>

                            <!-- Pedidos Fechados -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-right text-sm">
                                @if($cli->valor_pedidos > 0)
                                    <div class="font-bold text-gray-900">R$ {{ number_format($cli->valor_pedidos, 2, ',', '.') }}</div>
                                    <div class="text-xs text-blue-700">{{ $cli->qtd_pedidos }} {{ $cli->qtd_pedidos == 1 ? 'pedido' : 'pedidos' }}</div>
                                @else
                                    <span class="text-xs text-gray-400">R$ 0,00</span>
                                @endif
                            </td>

                            <!-- Sacolinha Ativa -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-right text-sm">
                                @if($cli->valor_sacola > 0)
                                    <div class="font-bold text-gray-900">R$ {{ number_format($cli->valor_sacola, 2, ',', '.') }}</div>
                                    <div class="text-xs text-purple-700">{{ $cli->qtd_sacola }} {{ $cli->qtd_sacola == 1 ? 'peça' : 'peças' }}</div>
                                @else
                                    <span class="text-xs text-gray-400">R$ 0,00</span>
                                @endif
                            </td>

                            <!-- Faturamento Total -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-right">
                                @if($cli->faturamento_total > 0)
                                    <span class="px-2.5 py-1 rounded-md bg-green-100 text-green-800 font-extrabold text-sm border border-green-300">
                                        R$ {{ number_format($cli->faturamento_total, 2, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-xs font-medium text-gray-400">Sem compras</span>
                                @endif
                            </td>

                            <!-- Ações -->
                            @if($mostrarColAcoes)
                            <td class="px-5 py-3.5 whitespace-nowrap text-center text-xs">
                                <div class="inline-flex items-center gap-1.5">
                                    @if($canVerCliente)
                                    <a href="{{ route('admin.clientes.show', $cli->id) }}"
                                       class="px-2.5 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold border border-gray-300"
                                       title="Ver Ficha do Cliente">
                                        <i class="fas fa-user mr-1"></i> Ficha
                                    </a>
                                    @endif
                                    @if($canVerSacola && $cli->qtd_sacola > 0)
                                        <a href="{{ route('admin.sacolinha.show', $cli->id) }}"
                                           class="px-2.5 py-1 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold border border-indigo-200"
                                           title="Ver Sacolinha">
                                            <i class="fas fa-shopping-bag mr-1"></i> Sacola
                                        </a>
                                    @endif
                                </div>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $mostrarColAcoes ? 7 : 6 }}" class="px-6 py-10 text-center text-gray-500">
                                <i class="fas fa-calendar-times text-2xl text-gray-400 mb-2 block"></i>
                                Nenhum novo cliente cadastrado entre <strong>{{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }}</strong> e <strong>{{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}</strong>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

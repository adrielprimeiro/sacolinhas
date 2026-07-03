@extends('layouts.app')

@section('title', 'Relatório Gerencial')
@section('brand_route', 'financeiro.dashboard')
@section('brand_icon', 'fas fa-chart-line')

@section('content')

{{-- ===== SUB-NAV FINANCEIRO ===== --}}
@include('admin.financeiro._subnav')

<!-- Script do Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-6">
    
    <!-- Filtro de Período Superior -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
        <form method="GET" action="{{ route('financeiro.relatoriogerencial') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl">
                    <i class="fas fa-chart-pie text-lg"></i>
                </div>
                <div>
                    <h2 class="text-lg font-black text-gray-800">Relatório Gerencial de Faturamento</h2>
                    <p class="text-xs text-gray-400">Visão integrada de faturamento comercial, meios de liquidação e investimentos em estoque.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2 self-end md:self-auto">
                <div class="flex items-center gap-1 bg-gray-50 p-1.5 rounded-xl border border-gray-100">
                    <input type="month" name="periodo" value="{{ $periodo->format('Y-m') }}" 
                           class="bg-transparent border-0 font-bold text-sm text-gray-700 focus:outline-none focus:ring-0 cursor-pointer">
                </div>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black text-sm px-6 py-2.5 rounded-xl shadow-md transition-all">
                    Visualizar
                </button>
            </div>
        </form>
    </div>

    <!-- KPI Cards Principais -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Faturamento Comercial -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 text-indigo-500 text-9xl group-hover:scale-110 transition-transform duration-500" style="opacity: 0.05;">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-indigo-600 text-white rounded-2xl flex items-center justify-center shadow-md shadow-indigo-600/20">
                    <i class="fas fa-receipt text-lg"></i>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600 bg-indigo-100 px-3 py-1 rounded-full">{{ $pedidosCount }} Pedidos</span>
            </div>
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Faturamento Líquido Comercial</h3>
            <p class="text-3xl font-black text-gray-800 mt-1">R$ {{ number_format($faturamentoLiquido, 2, ',', '.') }}</p>
            <div class="text-[10px] text-gray-400 mt-2 font-semibold flex flex-wrap gap-x-2">
                <span>Bruto: R$ {{ number_format($faturamentoBruto, 2, ',', '.') }}</span>
                <span>•</span>
                <span class="text-amber-600">Devolvido: R$ {{ number_format($creditosDevolucao, 2, ',', '.') }}</span>
            </div>
        </div>

        <!-- Investimento em Estoque -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 text-purple-500 text-9xl group-hover:scale-110 transition-transform duration-500" style="opacity: 0.05;">
                <i class="fas fa-box-open"></i>
            </div>
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-600 text-white rounded-2xl flex items-center justify-center shadow-md shadow-purple-600/20">
                    <i class="fas fa-box-open text-lg"></i>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-purple-600 bg-purple-100 px-3 py-1 rounded-full">Estoque</span>
            </div>
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Investimento Total em Estoque</h3>
            <p class="text-3xl font-black text-gray-800 mt-1">R$ {{ number_format($investimentoTotalEstoque, 2, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400 mt-2">Soma de compras de fornecedores (Real) e desapegos (Virtual).</p>
        </div>

        <!-- Margem Bruta Comercial -->
        @php
            $isPositivo = $margemBruta >= 0;
        @endphp
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 relative overflow-hidden group">
            @if($isPositivo)
                <div class="absolute -right-4 -bottom-4 text-9xl group-hover:scale-110 transition-transform duration-500" style="color: #10b981; opacity: 0.05;">
                    <i class="fas fa-scale-balanced"></i>
                </div>
            @else
                <div class="absolute -right-4 -bottom-4 text-9xl group-hover:scale-110 transition-transform duration-500" style="color: #ef4444; opacity: 0.05;">
                    <i class="fas fa-scale-balanced"></i>
                </div>
            @endif
            
            <div class="flex items-center justify-between mb-4">
                @if($isPositivo)
                    <div class="w-12 h-12 text-white rounded-2xl flex items-center justify-center" style="background-color: #10b981; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2), 0 2px 4px -2px rgba(16, 185, 129, 0.2);">
                        <i class="fas fa-scale-balanced text-lg"></i>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full" style="color: #059669; background-color: #d1fae5;">{{ $lucratividadePercentual }}% Margem</span>
                @else
                    <div class="w-12 h-12 text-white rounded-2xl flex items-center justify-center" style="background-color: #ef4444; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2), 0 2px 4px -2px rgba(239, 68, 68, 0.2);">
                        <i class="fas fa-scale-balanced text-lg"></i>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full" style="color: #dc2626; background-color: #fee2e2;">{{ $lucratividadePercentual }}% Margem</span>
                @endif
            </div>
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Margem de Operação</h3>
            @if($isPositivo)
                <p class="text-3xl font-black mt-1" style="color: #059669;">R$ {{ number_format($margemBruta, 2, ',', '.') }}</p>
            @else
                <p class="text-3xl font-black mt-1" style="color: #dc2626;">R$ {{ number_format($margemBruta, 2, ',', '.') }}</p>
            @endif
            <p class="text-[10px] text-gray-400 mt-2">Diferença entre faturamento líquido e investimento em estoque.</p>
        </div>

    </div>

    <!-- Detalhamento Faturamento x Estoque -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Bloco Faturamento e Meios de Liquidação -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest mb-6 flex items-center gap-2">
                    <i class="fas fa-money-bill-wave text-indigo-600"></i>
                    <span>Faturamento e Meios de Liquidação</span>
                </h3>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3.5 border border-gray-50 rounded-2xl hover:bg-gray-50/50 transition-all">
                        <div>
                            <span class="text-sm font-bold text-gray-800 block">Dinheiro / Pix Direto</span>
                            <span class="text-xs text-gray-400">Novos recebimentos associados aos pedidos.</span>
                        </div>
                        <span class="text-sm font-black text-indigo-600">R$ {{ number_format($liquidoDireto, 2, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between items-center p-3.5 border border-gray-50 rounded-2xl hover:bg-gray-50/50 transition-all">
                        <div>
                            <span class="text-sm font-bold text-gray-800 block">Saldo de Carteira (Virtual)</span>
                            <span class="text-xs text-gray-400">Liquidação utilizando saldo acumulado das clientes.</span>
                        </div>
                        <span class="text-sm font-black text-purple-600">R$ {{ number_format($saldoUtilizado, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Donut Faturamento -->
            <div class="mt-6 flex items-center justify-center" style="height: 180px;">
                @if($faturamentoBruto > 0)
                    <canvas id="faturamentoChart"></canvas>
                @else
                    <p class="text-xs text-gray-400 italic">Sem dados de faturamento para o gráfico.</p>
                @endif
            </div>
        </div>

        <!-- Bloco Detalhamento Carteira Cliente -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest mb-6 flex items-center gap-2">
                    <i class="fas fa-wallet text-purple-600"></i>
                    <span>Movimentação e Créditos Emitidos (Carteira)</span>
                </h3>
                
                <div class="space-y-4">
                    <!-- Aportes reais -->
                    <div class="flex justify-between items-center p-3.5 border border-gray-50 rounded-2xl hover:bg-gray-50/50 transition-all">
                        <div>
                            <span class="text-sm font-bold text-gray-800 block">Aportes / Recargas de Caixa</span>
                            <span class="text-xs text-gray-400">Créditos adquiridos por depósitos em dinheiro real (Pix).</span>
                        </div>
                        <span class="text-sm font-black text-green-600">R$ {{ number_format($creditosAporte, 2, ',', '.') }}</span>
                    </div>

                    <!-- Créditos avaliação -->
                    <div class="flex justify-between items-center p-3.5 border border-gray-50 rounded-2xl hover:bg-gray-50/50 transition-all">
                        <div>
                            <span class="text-sm font-bold text-gray-800 block">Créditos de Avaliação</span>
                            <span class="text-xs text-gray-400">Créditos virtuais gerados na aquisição de desapegos.</span>
                        </div>
                        <span class="text-sm font-black text-purple-600">R$ {{ number_format($creditosAvaliacao, 2, ',', '.') }}</span>
                    </div>

                    <!-- Devoluções e Ajustes -->
                    <div class="flex justify-between items-center p-3.5 border border-gray-50 rounded-2xl hover:bg-gray-50/50 transition-all">
                        <div>
                            <span class="text-sm font-bold text-gray-800 block">Devoluções / Ajustes Manuais</span>
                            <span class="text-xs text-gray-400">Créditos emitidos por trocas, devoluções ou acertos.</span>
                        </div>
                        <span class="text-sm font-black text-amber-600">R$ {{ number_format($creditosDevolucao, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Rodapé informativo -->
            <div class="mt-6 pt-4 border-t border-gray-50 flex justify-between items-center text-xs font-bold text-gray-500">
                <span>Total de Créditos Gerados:</span>
                <span class="text-sm font-black text-gray-700">R$ {{ number_format($totalCreditosGerados, 2, ',', '.') }}</span>
            </div>
        </div>

    </div>

    <!-- Investimento em Estoque (Real x Virtual) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Tabela Detalhada Estoque -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest mb-6 flex items-center gap-2">
                    <i class="fas fa-boxes text-purple-600"></i>
                    <span>Gastos e Aquisições de Estoque</span>
                </h3>

                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3.5 border border-gray-50 rounded-2xl hover:bg-gray-50/50 transition-all">
                        <div>
                            <span class="text-sm font-bold text-gray-800 block">Compras com Fornecedores (Dinheiro Real)</span>
                            <span class="text-xs text-gray-400">Pagamentos a fornecedores de lotes/fardos em dinheiro.</span>
                        </div>
                        <span class="text-sm font-black text-indigo-600">R$ {{ number_format($custoFornecedorReal, 2, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between items-center p-3.5 border border-gray-50 rounded-2xl hover:bg-gray-50/50 transition-all">
                        <div>
                            <span class="text-sm font-bold text-gray-800 block">Compras por Avaliação (Crédito Virtual)</span>
                            <span class="text-xs text-gray-400">Estoque circular pago com crédito virtual emitido.</span>
                        </div>
                        <span class="text-sm font-black text-purple-600">R$ {{ number_format($custoDesapegoVirtual, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Totalização de Estoque -->
            <div class="mt-6 pt-4 border-t border-gray-50 flex justify-between items-center text-xs font-bold text-gray-500">
                <span>Total Investimento:</span>
                <span class="text-sm font-black text-gray-700">R$ {{ number_format($investimentoTotalEstoque, 2, ',', '.') }}</span>
            </div>
        </div>

        <!-- Gráfico de Donut Estoque -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest mb-6 flex items-center gap-2">
                    <i class="fas fa-chart-donut text-purple-600"></i>
                    <span>Proporção de Investimento de Estoque</span>
                </h3>
            </div>
            
            <div class="flex items-center justify-center" style="height: 180px;">
                @if($investimentoTotalEstoque > 0)
                    <canvas id="estoqueChart"></canvas>
                @else
                    <p class="text-xs text-gray-400 italic">Sem dados de estoque para o gráfico.</p>
                @endif
            </div>
        </div>

    </div>

</div>

<!-- Configurações do Chart.js -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // 1. Gráfico de Faturamento
        @if($faturamentoBruto > 0)
            const fatCtx = document.getElementById('faturamentoChart').getContext('2d');
            new Chart(fatCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Dinheiro/Pix Direto', 'Saldo de Carteira'],
                    datasets: [{
                        data: [{{ $liquidoDireto }}, {{ $saldoUtilizado }}],
                        backgroundColor: ['#6366f1', '#a855f7'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                font: { size: 10, weight: 'bold' }
                            }
                        }
                    }
                }
            });
        @endif

        // 2. Gráfico de Estoque
        @if($investimentoTotalEstoque > 0)
            const estCtx = document.getElementById('estoqueChart').getContext('2d');
            new Chart(estCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Fornecedores (Real)', 'Desapegos (Virtual)'],
                    datasets: [{
                        data: [{{ $custoFornecedorReal }}, {{ $custoDesapegoVirtual }}],
                        backgroundColor: ['#6366f1', '#a855f7'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                font: { size: 10, weight: 'bold' }
                            }
                        }
                    }
                }
            });
        @endif
    });
</script>

@endsection

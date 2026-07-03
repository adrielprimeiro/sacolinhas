@extends('layouts.app')

@section('title', 'Fluxo de Caixa')
@section('brand_route', 'financeiro.dashboard')
@section('brand_icon', 'fas fa-chart-line')

@section('content')

{{-- ===== SUB-NAV FINANCEIRO ===== --}}
@include('admin.financeiro._subnav')

<!-- Script do Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-6" x-data="{ showFilters: false }">
    
    <!-- Seção de Filtros Superior -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
        <form method="GET" action="{{ route('financeiro.fluxodecaixa') }}" class="space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl">
                        <i class="fas fa-filter text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-gray-800">Filtros de Período e Contas</h2>
                        <p class="text-xs text-gray-400">Selecione o mês e as contas bancárias reais para visualizar o caixa físico.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="showFilters = !showFilters" class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-bold text-gray-500 hover:bg-gray-50 flex items-center gap-2 transition-all">
                        <i class="fas fa-sliders-h"></i>
                        <span>Filtro de Contas</span>
                        <i class="fas text-xs transition-transform duration-200" :class="showFilters ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                    
                    <div class="flex items-center gap-1 bg-gray-50 p-1.5 rounded-xl border border-gray-100">
                        <input type="month" name="periodo" value="{{ $periodo->format('Y-m') }}" 
                               class="bg-transparent border-0 font-bold text-sm text-gray-700 focus:outline-none focus:ring-0 cursor-pointer">
                    </div>
                    
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black text-sm px-6 py-2.5 rounded-xl shadow-md transition-all">
                        Filtrar
                    </button>
                </div>
            </div>

            <!-- Filtros Expandíveis de Contas Bancárias -->
            <div x-show="showFilters" x-cloak x-transition class="pt-4 border-t border-gray-50">
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Incluir nas Contas do Caixa</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach ($contas as $conta)
                        @php
                            $isVirtual = str_contains(strtolower($conta->nome), 'carteira');
                            $isSelected = in_array($conta->id, $contasSelecionadas);
                        @endphp
                        <label class="flex items-center gap-3 p-3.5 border rounded-2xl cursor-pointer hover:bg-gray-50 transition-all {{ $isSelected ? 'border-indigo-200 bg-indigo-50/20' : 'border-gray-100' }}">
                            <input type="checkbox" name="contas[]" value="{{ $conta->id }}" {{ $isSelected ? 'checked' : '' }}
                                   class="rounded text-indigo-600 focus:ring-indigo-500 border-gray-300 w-4 h-4">
                            <div>
                                <span class="text-sm font-bold text-gray-800 block">{{ $conta->nome }}</span>
                                <span class="text-[10px] uppercase font-black tracking-widest {{ $isVirtual ? 'text-purple-500' : 'text-green-500' }}">
                                    {{ $isVirtual ? 'Conta Virtual (Carteira)' : 'Conta Real' }}
                                </span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        </form>
    </div>

    <!-- KPIs - Cartões de Entrada e Saída -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Cartão Entradas -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 text-indigo-500 text-9xl group-hover:scale-110 transition-transform duration-500" style="opacity: 0.05;">
                <i class="fas fa-arrow-trend-up"></i>
            </div>
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-indigo-600 text-white rounded-2xl flex items-center justify-center shadow-md shadow-indigo-600/20">
                    <i class="fas fa-arrow-up text-lg"></i>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600 bg-indigo-100 px-3 py-1 rounded-full">Entradas</span>
            </div>
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Total Recebido (Caixa)</h3>
            <p class="text-3xl font-black mt-1" style="color: #059669;">R$ {{ number_format($totalEntradas, 2, ',', '.') }}</p>
        </div>

        <!-- Cartão Saídas -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 text-indigo-500 text-9xl group-hover:scale-110 transition-transform duration-500" style="opacity: 0.05;">
                <i class="fas fa-arrow-trend-down"></i>
            </div>
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-indigo-600 text-white rounded-2xl flex items-center justify-center shadow-md shadow-indigo-600/20">
                    <i class="fas fa-arrow-down text-lg"></i>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600 bg-indigo-100 px-3 py-1 rounded-full">Saídas</span>
            </div>
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Total Pago (Caixa)</h3>
            <p class="text-3xl font-black mt-1" style="color: #dc2626;">R$ {{ number_format($totalSaidas, 2, ',', '.') }}</p>
        </div>

        <!-- Cartão Saldo Líquido -->
        @php
            $isPositivo = $saldoLiquido >= 0;
        @endphp
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 relative overflow-hidden group">
            @if($isPositivo)
                <div class="absolute -right-4 -bottom-4 text-indigo-500 text-9xl group-hover:scale-110 transition-transform duration-500" style="opacity: 0.05;">
                    <i class="fas fa-scale-balanced"></i>
                </div>
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-indigo-600 text-white rounded-2xl flex items-center justify-center shadow-md shadow-indigo-600/20">
                        <i class="fas fa-scale-balanced text-lg"></i>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600 bg-indigo-100 px-3 py-1 rounded-full font-bold">Saldo Líquido</span>
                </div>
                <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Resultado do Mês</h3>
                <p class="text-3xl font-black text-indigo-600 mt-1">R$ {{ number_format($saldoLiquido, 2, ',', '.') }}</p>
            @else
                <div class="absolute -right-4 -bottom-4 text-red-500 text-9xl group-hover:scale-110 transition-transform duration-500" style="opacity: 0.05;">
                    <i class="fas fa-scale-balanced"></i>
                </div>
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-red-600 text-white rounded-2xl flex items-center justify-center shadow-md shadow-red-600/20">
                        <i class="fas fa-scale-balanced text-lg"></i>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-red-600 bg-red-100 px-3 py-1 rounded-full font-bold">Saldo Líquido</span>
                </div>
                <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Resultado do Mês</h3>
                <p class="text-3xl font-black text-red-600 mt-1">R$ {{ number_format($saldoLiquido, 2, ',', '.') }}</p>
            @endif
        </div>

    </div>

    <!-- Gráfico de Evolução Diária -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
        <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest mb-4">Evolução do Caixa no Período</h3>
        <div class="relative w-full overflow-hidden">
            <canvas id="fluxoCaixaChart" style="max-height: 350px; min-height: 250px;"></canvas>
        </div>
    </div>

    <!-- Detalhamento por Categoria (Receitas x Despesas) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Bloco Receitas -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-50 flex items-center justify-between bg-emerald-500/5">
                <h3 class="text-sm font-black text-emerald-800 uppercase tracking-widest flex items-center gap-2">
                    <i class="fas fa-arrow-trend-up"></i>
                    <span>Detalhamento das Entradas</span>
                </h3>
                <span class="text-xs font-black text-emerald-700 bg-emerald-100 px-3 py-1 rounded-full">
                    R$ {{ number_format($totalEntradas, 2, ',', '.') }}
                </span>
            </div>
            <div class="p-4 space-y-1">
                @forelse ($treeReceitas as $linha)
                    @include('admin.financeiro.fluxo_caixa._node', ['node' => $linha, 'nivel' => 0])
                @empty
                    <div class="text-center py-12 text-sm text-gray-400 italic">
                        Nenhuma entrada de caixa registrada no período.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Bloco Despesas -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-50 flex items-center justify-between bg-rose-500/5">
                <h3 class="text-sm font-black text-rose-800 uppercase tracking-widest flex items-center gap-2">
                    <i class="fas fa-arrow-trend-down"></i>
                    <span>Detalhamento das Saídas</span>
                </h3>
                <span class="text-xs font-black text-rose-700 bg-rose-100 px-3 py-1 rounded-full">
                    R$ {{ number_format($totalSaidas, 2, ',', '.') }}
                </span>
            </div>
            <div class="p-4 space-y-1">
                @forelse ($treeDespesas as $linha)
                    @include('admin.financeiro.fluxo_caixa._node', ['node' => $linha, 'nivel' => 0])
                @empty
                    <div class="text-center py-12 text-sm text-gray-400 italic">
                        Nenhuma saída de caixa registrada no período.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>

<!-- Configuração e Renderização do Chart.js -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const ctx = document.getElementById('fluxoCaixaChart').getContext('2d');
        
        const chartData = @json($chartData);
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Entradas (R$)',
                        data: chartData.entradas,
                        backgroundColor: '#10b981', // emerald-500
                        borderRadius: 6,
                        maxBarThickness: 15,
                        order: 2
                    },
                    {
                        label: 'Saídas (R$)',
                        data: chartData.saidas,
                        backgroundColor: '#f43f5e', // rose-500
                        borderRadius: 6,
                        maxBarThickness: 15,
                        order: 2
                    },
                    {
                        label: 'Saldo Acumulado (R$)',
                        data: chartData.acumulado,
                        type: 'line',
                        borderColor: '#6366f1', // indigo-500
                        borderWidth: 3,
                        pointBackgroundColor: '#6366f1',
                        pointHoverRadius: 6,
                        fill: false,
                        tension: 0.3,
                        order: 1,
                        yAxisID: 'yAcumulado'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 11,
                                weight: 'bold'
                            },
                            color: '#4b5563'
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        padding: 12,
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#1f2937',
                        titleFont: { weight: 'bold' },
                        bodyColor: '#4b5563',
                        borderColor: '#e5e7eb',
                        borderWidth: 1,
                        callbacks: {
                            label: function (context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 10, weight: 'bold' },
                            color: '#9ca3af'
                        }
                    },
                    y: {
                        position: 'left',
                        grid: {
                            color: '#f3f4f6'
                        },
                        ticks: {
                            font: { size: 10 },
                            color: '#9ca3af',
                            callback: function(value) {
                                return 'R$ ' + value;
                            }
                        }
                    },
                    yAcumulado: {
                        position: 'right',
                        grid: {
                            drawOnChartArea: false // evita linhas sobrepostas
                        },
                        ticks: {
                            font: { size: 10, weight: 'bold' },
                            color: '#6366f1',
                            callback: function(value) {
                                return 'R$ ' + value;
                            }
                        }
                    }
                }
            }
        });
    });
</script>

@endsection

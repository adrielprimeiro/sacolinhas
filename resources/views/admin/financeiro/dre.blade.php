@extends('layouts.app')

@section('title', 'DRE')
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
        <form method="GET" action="{{ route('financeiro.dre') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl">
                    <i class="fas fa-file-invoice text-lg"></i>
                </div>
                <div>
                    <h2 class="text-lg font-black text-gray-800">DRE - Demonstração do Resultado do Exercício</h2>
                    <p class="text-xs text-gray-400">Regime de Competência — Faturamento de vendas, dedução de custos de estoque vendidos e despesas operacionais.</p>
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
        
        <!-- Receita Líquida -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 text-indigo-500 text-9xl group-hover:scale-110 transition-transform duration-500" style="opacity: 0.05;">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-indigo-600 text-white rounded-2xl flex items-center justify-center shadow-md shadow-indigo-600/20">
                    <i class="fas fa-receipt text-lg"></i>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600 bg-indigo-100 px-3 py-1 rounded-full">Receita Líq.</span>
            </div>
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Receita Líquida</h3>
            <p class="text-3xl font-black text-gray-800 mt-1">R$ {{ number_format($receitaLiquida, 2, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400 mt-2">Receita bruta descontando devoluções e descontos concedidos.</p>
        </div>

        <!-- Lucro Bruto -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 text-indigo-500 text-9xl group-hover:scale-110 transition-transform duration-500" style="opacity: 0.05;">
                <i class="fas fa-scale-balanced"></i>
            </div>
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-indigo-600 text-white rounded-2xl flex items-center justify-center shadow-md shadow-indigo-600/20">
                    <i class="fas fa-scale-balanced text-lg"></i>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600 bg-indigo-100 px-3 py-1 rounded-full">{{ $margemBrutaPercentual }}% Margem</span>
            </div>
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Lucro Bruto</h3>
            <p class="text-3xl font-black text-gray-800 mt-1">R$ {{ number_format($lucroBruto, 2, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400 mt-2">Diferença direta entre receita líquida e o CMV (custo de estoque).</p>
        </div>

        <!-- Lucro Líquido (LLE) -->
        @php
            $isPositivo = $lucroLiquido >= 0;
            $colorClass = $isPositivo ? 'text-emerald-600' : 'text-red-600';
        @endphp
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 text-indigo-500 text-9xl group-hover:scale-110 transition-transform duration-500" style="opacity: 0.05;">
                <i class="fas fa-crown"></i>
            </div>
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-indigo-600 text-white rounded-2xl flex items-center justify-center shadow-md shadow-indigo-600/20">
                    <i class="fas fa-crown text-lg"></i>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600 bg-indigo-100 px-3 py-1 rounded-full">{{ $margemLiquidaPercentual }}% Margem</span>
            </div>
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Lucro Líquido (LLE)</h3>
            @if($isPositivo)
                <p class="text-3xl font-black mt-1" style="color: #059669;">R$ {{ number_format($lucroLiquido, 2, ',', '.') }}</p>
            @else
                <p class="text-3xl font-black mt-1" style="color: #dc2626;">R$ {{ number_format($lucroLiquido, 2, ',', '.') }}</p>
            @endif
            <p class="text-[10px] text-gray-400 mt-2">Lucro final descontando todas as despesas operacionais.</p>
        </div>

    </div>

    <!-- Estrutura DRE Completa -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Demonstração Detalhada (DRE) -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 lg:col-span-2">
            <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest mb-6 flex items-center gap-2">
                <i class="fas fa-list-check text-indigo-600"></i>
                <span>Demonstrativo do Resultado</span>
            </h3>

            <div class="divide-y divide-gray-100 text-sm">
                
                <!-- Receita Bruta -->
                <div class="py-3 flex justify-between items-center font-bold text-gray-800">
                    <span>(=) RECEITA BRUTA DE VENDAS</span>
                    <span>R$ {{ number_format($receitaBruta, 2, ',', '.') }}</span>
                </div>
                <div class="py-2.5 pl-6 flex justify-between items-center text-xs text-gray-500">
                    <span>(+) Faturamento de Vendas (Pedidos)</span>
                    <span>R$ {{ number_format($receitaVendas, 2, ',', '.') }}</span>
                </div>
                <div class="py-2.5 pl-6 flex justify-between items-center text-xs text-gray-500">
                    <span>(+) Outras Receitas Operacionais</span>
                    <span>R$ {{ number_format($outrasReceitas, 2, ',', '.') }}</span>
                </div>

                <!-- Deduções -->
                <div class="py-3 flex justify-between items-center font-bold text-gray-800">
                    <span>(-) DEDUÇÕES DA RECEITA BRUTA</span>
                    <span class="text-red-600">R$ ({{ number_format($totalDeducoes, 2, ',', '.') }})</span>
                </div>
                <div class="py-2.5 pl-6 flex justify-between items-center text-xs text-gray-500">
                    <span>(-) Descontos Concedidos nos Pedidos</span>
                    <span>R$ ({{ number_format($descontosConcedidos, 2, ',', '.') }})</span>
                </div>
                <div class="py-2.5 pl-6 flex justify-between items-center text-xs text-gray-500">
                    <span>(-) Devoluções de Roupas / Vendas</span>
                    <span>R$ ({{ number_format($devolucoesVendas, 2, ',', '.') }})</span>
                </div>

                <!-- Receita Líquida -->
                <div class="py-3.5 flex justify-between items-center font-black text-gray-900 bg-gray-50/50 px-3 rounded-xl">
                    <span>(=) RECEITA LÍQUIDA</span>
                    <span>R$ {{ number_format($receitaLiquida, 2, ',', '.') }}</span>
                </div>

                <!-- CMV -->
                <div class="py-3.5 flex justify-between items-center font-bold text-gray-800">
                    <span>(-) CUSTO DAS MERCADORIAS VENDIDAS (CMV)</span>
                    <span class="text-red-600">R$ ({{ number_format($cmv, 2, ',', '.') }})</span>
                </div>

                <!-- Lucro Bruto -->
                <div class="py-3.5 flex justify-between items-center font-black text-gray-900 bg-purple-50/20 px-3 rounded-xl">
                    <span>(=) LUCRO BRUTO</span>
                    <span>R$ {{ number_format($lucroBruto, 2, ',', '.') }}</span>
                </div>

                <!-- Despesas Operacionais -->
                <div class="py-3 flex justify-between items-center font-bold text-gray-800">
                    <span>(-) DESPESAS OPERACIONAIS (Despesas de Apoio)</span>
                    <span class="text-red-600">R$ ({{ number_format($totalDespesasOperacionais, 2, ',', '.') }})</span>
                </div>
                
                @forelse ($despesasPorCategoria as $categoria => $valor)
                    <div class="py-2.5 pl-6 flex justify-between items-center text-xs text-gray-500">
                        <span>(-) {{ $categoria }}</span>
                        <span>R$ ({{ number_format($valor, 2, ',', '.') }})</span>
                    </div>
                @empty
                    <div class="py-2.5 pl-6 text-xs text-gray-400 italic">Nenhuma despesa operacional cadastrada.</div>
                @endforelse

                <!-- Lucro Líquido -->
                @php
                    $finalBg = $isPositivo ? 'bg-emerald-50/30' : 'bg-red-50/30';
                @endphp
                <div class="py-4 flex justify-between items-center font-black text-base text-gray-900 {{ $finalBg }} px-3 rounded-xl mt-2 border border-gray-100">
                    <span>(=) LUCRO LÍQUIDO DO EXERCÍCIO (LLE)</span>
                    <span class="{{ $colorClass }}">R$ {{ number_format($lucroLiquido, 2, ',', '.') }}</span>
                </div>

            </div>
        </div>

        <!-- Distribuição de Despesas Operacionais -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest mb-6 flex items-center gap-2">
                    <i class="fas fa-chart-pie text-indigo-600"></i>
                    <span>Distribuição de Despesas</span>
                </h3>
            </div>

            <div class="flex items-center justify-center" style="height: 240px;">
                @if($totalDespesasOperacionais > 0)
                    <canvas id="despesasChart"></canvas>
                @else
                    <p class="text-xs text-gray-400 italic">Sem despesas operacionais no período para o gráfico.</p>
                @endif
            </div>

            <div class="text-[10px] text-gray-400 mt-6 pt-4 border-t border-gray-50 text-center">
                Investimentos em compra de estoque (Capitalizado) não entram como despesas operacionais na DRE.
            </div>
        </div>

    </div>

</div>

<!-- Configuração do Chart.js para Despesas -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        @if($totalDespesasOperacionais > 0)
            const ctx = document.getElementById('despesasChart').getContext('2d');
            
            const categories = @json(array_keys($despesasPorCategoria));
            const values = @json(array_values($despesasPorCategoria));
            
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: categories,
                    datasets: [{
                        data: values,
                        backgroundColor: [
                            '#6366f1', // indigo
                            '#3b82f6', // blue
                            '#a855f7', // purple
                            '#f43f5e', // rose
                            '#eab308', // yellow
                            '#14b8a6', // teal
                            '#f97316'  // orange
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: { size: 9, weight: 'bold' }
                            }
                        }
                    }
                }
            });
        @endif
    });
</script>

@endsection

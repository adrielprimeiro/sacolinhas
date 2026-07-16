@extends('layouts.portal-cliente')

@section('title', 'Minha Carteira - Portal do Cliente')

@section('content')
<div class="space-y-6">

    <!-- Card de Saldo Atual -->
    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-lg p-6 text-white relative overflow-hidden">
        <!-- Detalhe decorativo no fundo -->
        <div class="absolute right-0 bottom-0 opacity-10 translate-x-10 translate-y-10">
            <i class="fas fa-wallet text-[150px]"></i>
        </div>
        
        <div class="relative z-10 flex flex-col justify-between h-full gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider opacity-80">Saldo Disponível</p>
                <h1 class="text-3xl sm:text-4xl font-extrabold mt-1">
                    R$ {{ number_format($saldo, 2, ',', '.') }}
                </h1>
            </div>
            
            <div class="border-t border-white/20 pt-4 mt-2">
                <p class="text-xs opacity-90 leading-relaxed">
                    *Este saldo representa seus créditos acumulados no sistema (por devoluções, avaliações premiadas ou recargas) que podem ser usados para abater o valor de suas próximas compras.
                </p>
            </div>
        </div>
    </div>

    <!-- Título da Seção -->
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-history text-indigo-500"></i> Histórico de Movimentações
        </h2>
    </div>

    <!-- Histórico Listagem -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-150 overflow-hidden">
        @if($movimentacoes->count() > 0)
            <div class="divide-y divide-gray-100">
                @foreach($movimentacoes as $movimentacao)
                    @php
                        $isCredito = $movimentacao->tipo_movimentacao === 'credito';
                        
                        // Ícone customizado baseado na descrição
                        $icon = 'fas fa-exchange-alt text-gray-400';
                        $bgIcon = 'bg-gray-50';
                        
                        $descLower = strtolower($movimentacao->descricao);
                        if (str_contains($descLower, 'pedido')) {
                            $icon = 'fas fa-receipt text-red-500';
                            $bgIcon = 'bg-red-50';
                        } elseif (str_contains($descLower, 'avaliação') || str_contains($descLower, 'premio') || str_contains($descLower, 'recompensa')) {
                            $icon = 'fas fa-star text-yellow-500';
                            $bgIcon = 'bg-yellow-50';
                        } elseif (str_contains($descLower, 'estorno') || str_contains($descLower, 'devolução')) {
                            $icon = 'fas fa-undo text-green-500';
                            $bgIcon = 'bg-green-50';
                        } elseif ($isCredito) {
                            $icon = 'fas fa-plus-circle text-blue-500';
                            $bgIcon = 'bg-blue-50';
                        } else {
                            $icon = 'fas fa-minus-circle text-red-500';
                            $bgIcon = 'bg-red-50';
                        }
                    @endphp
                    <div class="p-4 flex items-center justify-between gap-3 hover:bg-gray-50/50 transition duration-200">
                        <div class="flex items-center gap-3 min-w-0">
                            <!-- Ícone -->
                            <div class="w-10 h-10 rounded-full {{ $bgIcon }} flex items-center justify-center flex-shrink-0 border border-white shadow-sm">
                                <i class="{{ $icon }} text-sm"></i>
                            </div>
                            <!-- Descrição & Data -->
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 leading-snug">
                                    {{ $movimentacao->descricao }}
                                </p>
                                <p class="text-[11px] text-gray-400 font-medium mt-0.5">
                                    {{ \Carbon\Carbon::parse($movimentacao->data_movimentacao)->format('d/m/Y \à\s H:i') }}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Valor & Saldo Resultante -->
                        <div class="text-right flex-shrink-0">
                            <p class="text-sm font-black {{ $isCredito ? 'text-blue-600' : 'text-red-600' }}">
                                {{ $isCredito ? '+' : '-' }} R$ {{ number_format($movimentacao->valor, 2, ',', '.') }}
                            </p>
                            <p class="text-[10px] text-gray-400 font-medium mt-0.5">
                                Saldo: <span class="font-bold text-gray-500">R$ {{ number_format($movimentacao->saldo_atual, 2, ',', '.') }}</span>
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Estado Vazio -->
            <div class="p-12 text-center">
                <div class="w-16 h-16 rounded-full bg-gray-50 border border-gray-150 flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-wallet text-gray-300 text-2xl"></i>
                </div>
                <h3 class="text-sm font-bold text-gray-800">Nenhuma movimentação</h3>
                <p class="text-xs text-gray-500 mt-1 max-w-xs mx-auto">
                    Você ainda não possui histórico de créditos ou débitos em sua carteira virtual.
                </p>
            </div>
        @endif
    </div>

</div>
@endsection

@extends('layouts.portal-cliente')

@section('title', 'Minhas Avaliações - Portal do Cliente | Mania')

@section('content')
<style>
:root {
  --pink: #F5148C;
  --pink-dark: #C90E72;
  --pink-soft: #FDE7F3;
  --radius: 20px;
}
</style>

<div class="space-y-6">
    <!-- Cabeçalho -->
    <div class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-star text-pink-600" style="color: var(--pink);"></i> Minhas Avaliações
            </h1>
            <p class="text-gray-600 text-sm">Acompanhe seus lotes e desapegos enviados para avaliação</p>
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-500">Total de lotes</p>
            <p class="text-lg font-semibold text-gray-800">{{ $avaliacoes->count() }}</p>
        </div>
    </div>

    <!-- Lista -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-800">Histórico de Lotes</h2>
            <a href="{{ route('portal.dashboard') }}" class="text-sm text-blue-600 hover:text-blue-700">
                Voltar ao Dashboard
            </a>
        </div>

        @if($avaliacoes->isEmpty())
            <div class="p-6 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                    <i class="fas fa-box-open text-gray-400"></i>
                </div>
                <p class="mt-3 text-sm font-semibold text-gray-800">Você ainda não tem avaliações</p>
                <p class="text-sm text-gray-600 mt-1">Seus lotes de desapego enviados aparecerão aqui.</p>
            </div>
        @else
            <!-- Tabela (desktop) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Lote</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Data</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Qtd Itens</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total Repasse</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Pagamento</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($avaliacoes as $av)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-semibold text-gray-800">
                                    Lote #{{ str_pad($av->id, 5, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ \Carbon\Carbon::parse($av->data_avaliacao)->format('d/m/y') }}
                                </td>
                                <td class="px-4 py-3 text-center text-sm font-semibold text-gray-800">
                                    {{ $av->qtd_itens }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($av->status === 'finalizada')
                                        <span class="font-extrabold text-pink-600" style="color: var(--pink);">
                                            R$ {{ number_format($av->total_payout, 2, ',', '.') }}
                                        </span>
                                    @else
                                        <div class="text-xs space-y-0.5">
                                            <div class="text-purple-700 font-bold">
                                                Crédito: R$ {{ number_format($av->itens->sum('payout_credito'), 2, ',', '.') }}
                                            </div>
                                            <div class="text-blue-700 font-bold">
                                                Dinheiro: R$ {{ number_format($av->itens->sum('payout_dinheiro'), 2, ',', '.') }}
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-sm">
                                    @if($av->status === 'finalizada')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $av->pagamento_escolhido === 'credito' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                            {{ $av->pagamento_escolhido === 'credito' ? 'Créditos' : 'Dinheiro/PIX' }}
                                        </span>
                                    @elseif($av->status === 'cancelada')
                                        <span class="text-gray-400">-</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                            Pendente
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-sm">
                                    @if($av->status === 'finalizada')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                            Finalizada
                                        </span>
                                    @elseif($av->status === 'cancelada')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                            Cancelada
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">
                                            Rascunho
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button id="btn-desktop-{{ $av->id }}" onclick="toggleDetalhes({{ $av->id }}, 'desktop')"
                                            class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white text-xs px-3 py-2 rounded-md transition duration-200">
                                        Detalhes
                                    </button>
                                </td>
                            </tr>

                            <!-- Detalhes Collapsible (desktop) -->
                            <tr id="detalhes-desktop-{{ $av->id }}" class="hidden bg-gray-50">
                                <td colspan="7" class="px-4 py-4">
                                    <div class="space-y-3">
                                        <h4 class="text-sm font-semibold text-gray-850">Itens do Lote</h4>
                                        <div class="space-y-2">
                                            @forelse($av->itens as $item)
                                                <div class="flex items-center justify-between bg-white p-3 rounded-md border border-gray-200">
                                                    <div>
                                                        <p class="text-sm font-semibold text-gray-850">{{ $item->nome }}</p>
                                                        <p class="text-xs text-gray-500">
                                                            Marca: {{ $item->marca ?? '-' }} • Estado: {{ $item->estado ?? '-' }}
                                                        </p>
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-sm text-gray-600">Nenhum item encontrado neste lote.</p>
                                            @endforelse
                                        </div>

                                        <div class="mt-4 pt-3 border-t border-gray-200 max-w-xs ml-auto text-right space-y-1">
                                            @if($av->status === 'finalizada')
                                                <div class="flex justify-between text-sm font-bold text-gray-900">
                                                    <span>Total Repasse ({{ $av->pagamento_escolhido === 'credito' ? 'Créditos' : 'Dinheiro/PIX' }}):</span>
                                                    <span class="text-pink-650" style="color: var(--pink);">R$ {{ number_format($av->total_payout, 2, ',', '.') }}</span>
                                                </div>
                                            @else
                                                <div class="flex justify-between text-xs text-gray-500">
                                                    <span>Repasse em Crédito:</span>
                                                    <span class="font-bold text-purple-700">R$ {{ number_format($av->itens->sum('payout_credito'), 2, ',', '.') }}</span>
                                                </div>
                                                <div class="flex justify-between text-xs text-gray-500">
                                                    <span>Repasse em Dinheiro/PIX:</span>
                                                    <span class="font-bold text-blue-700">R$ {{ number_format($av->itens->sum('payout_dinheiro'), 2, ',', '.') }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Cards (mobile) -->
            <div class="md:hidden divide-y divide-gray-200">
                @foreach($avaliacoes as $av)
                    <div class="p-4 space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Lote #{{ str_pad($av->id, 5, '0', STR_PAD_LEFT) }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Data: {{ \Carbon\Carbon::parse($av->data_avaliacao)->format('d/m/y') }}
                                </p>
                            </div>
                            <div class="text-right">
                                @if($av->status === 'finalizada')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                        Finalizada
                                    </span>
                                @elseif($av->status === 'cancelada')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                        Cancelada
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">
                                        Rascunho
                                    </span>
                                @endif
                                @if($av->status === 'finalizada')
                                    <p class="text-base font-extrabold text-pink-600 mt-1" style="color: var(--pink);">
                                        R$ {{ number_format($av->total_payout, 2, ',', '.') }}
                                    </p>
                                @else
                                    <div class="text-xs space-y-0.5 mt-1 text-right">
                                        <div class="text-purple-700 font-bold">
                                            Crédito: R$ {{ number_format($av->itens->sum('payout_credito'), 2, ',', '.') }}
                                        </div>
                                        <div class="text-blue-700 font-bold">
                                            Dinheiro: R$ {{ number_format($av->itens->sum('payout_dinheiro'), 2, ',', '.') }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500 text-xs">Qtd Itens:</span>
                            <span class="font-semibold text-gray-800 text-xs">{{ $av->qtd_itens }}</span>
                        </div>

                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500 text-xs">Pagamento:</span>
                            @if($av->status === 'finalizada')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $av->pagamento_escolhido === 'credito' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ $av->pagamento_escolhido === 'credito' ? 'Créditos' : 'Dinheiro/PIX' }}
                                </span>
                            @elseif($av->status === 'cancelada')
                                <span class="text-gray-400 text-xs">-</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-600">
                                    Pendente
                                </span>
                            @endif
                        </div>

                        <div>
                            <button id="btn-mobile-{{ $av->id }}" onclick="toggleDetalhes({{ $av->id }}, 'mobile')"
                                    class="w-full bg-blue-500 hover:bg-blue-600 text-white text-xs py-2 rounded-md transition duration-200">
                                Ver Detalhes
                            </button>
                        </div>

                        <!-- Detalhes expansíveis (mobile) -->
                        <div id="detalhes-mobile-{{ $av->id }}" class="hidden mt-3 space-y-2">
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Itens do Lote</h4>
                            <div class="space-y-2">
                                @forelse($av->itens as $item)
                                    <div class="bg-gray-50 p-3 rounded-md border border-gray-200">
                                        <p class="text-sm font-semibold text-gray-800">{{ $item->nome }}</p>
                                        <p class="text-xs text-gray-500">
                                            Marca: {{ $item->marca ?? '-' }} • Estado: {{ $item->estado ?? '-' }}
                                        </p>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-500">Nenhum item encontrado neste lote.</p>
                                @endforelse
                            </div>

                            <div class="mt-3 pt-3 border-t border-gray-200 space-y-1 text-right">
                                @if($av->status === 'finalizada')
                                    <div class="flex justify-between text-xs font-bold text-gray-900">
                                        <span>Total Repasse ({{ $av->pagamento_escolhido === 'credito' ? 'Créditos' : 'Dinheiro/PIX' }}):</span>
                                        <span class="text-pink-650" style="color: var(--pink);">R$ {{ number_format($av->total_payout, 2, ',', '.') }}</span>
                                    </div>
                                @else
                                    <div class="flex justify-between text-xs text-gray-500">
                                        <span>Total Repasse (Crédito):</span>
                                        <span class="font-bold text-purple-700">R$ {{ number_format($av->itens->sum('payout_credito'), 2, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-500">
                                        <span>Total Repasse (Dinheiro/PIX):</span>
                                        <span class="font-bold text-blue-700">R$ {{ number_format($av->itens->sum('payout_dinheiro'), 2, ',', '.') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<script>
    function toggleDetalhes(id, device) {
        const el = document.getElementById(`detalhes-${device}-${id}`);
        const btn = document.getElementById(`btn-${device}-${id}`);
        
        if (el.classList.contains('hidden')) {
            el.classList.remove('hidden');
            if (device === 'desktop') {
                btn.innerHTML = 'Ocultar';
                btn.classList.replace('bg-blue-500', 'bg-gray-500');
                btn.classList.replace('hover:bg-blue-600', 'hover:bg-gray-600');
            } else {
                btn.innerText = 'Ocultar Detalhes';
                btn.classList.replace('bg-blue-500', 'bg-gray-500');
                btn.classList.replace('hover:bg-blue-600', 'hover:bg-gray-600');
            }
        } else {
            el.classList.add('hidden');
            if (device === 'desktop') {
                btn.innerHTML = 'Detalhes';
                btn.classList.replace('bg-gray-500', 'bg-blue-500');
                btn.classList.replace('hover:bg-gray-600', 'hover:bg-blue-600');
            } else {
                btn.innerText = 'Ver Detalhes';
                btn.classList.replace('bg-gray-500', 'bg-blue-500');
                btn.classList.replace('hover:bg-gray-600', 'hover:bg-blue-600');
            }
        }
    }
</script>
@endsection

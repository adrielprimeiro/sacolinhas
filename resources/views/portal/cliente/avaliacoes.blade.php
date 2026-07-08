@extends('layouts.portal-cliente')

@section('title', 'Minhas Avaliações - Portal do Cliente | Mania')

@section('content')
<style>
:root {
  --pink: #F5148C;
  --pink-dark: #C90E72;
  --pink-soft: #FDE7F3;
  --ink: #141414;
  --radius: 20px;
  --shadow-card: 0 4px 24px -6px rgba(20, 20, 20, 0.08);
}
.av-card {
  background: #fff;
  border-radius: var(--radius);
  box-shadow: var(--shadow-card);
  border: 1px solid #fce7f3;
  overflow: hidden;
}
</style>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
            <i class="fas fa-star text-pink-600" style="color: var(--pink);"></i> Minhas Avaliações
        </h1>
        <p class="text-sm text-gray-500 mt-1">Acompanhe aqui o histórico de lotes e desapegos que você enviou para avaliação.</p>
    </div>

    <div class="av-card">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-pink-50/40">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Lote</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Data</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Qtd Itens</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Total Repasse</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Ação</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($avaliacoes as $av)
                        <!-- Linha Principal -->
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            {{-- Lote --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-850">
                                Lote #{{ str_pad($av->id, 5, '0', STR_PAD_LEFT) }}
                            </td>
                            {{-- Data (Formato d/m/y) --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ \Carbon\Carbon::parse($av->data_avaliacao)->format('d/m/y') }}
                            </td>
                            {{-- Qtd Itens --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-semibold text-gray-800">
                                {{ $av->qtd_itens }}
                            </td>
                            {{-- Total Repasse --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-extrabold text-pink-600" style="color: var(--pink);">
                                R$ {{ number_format($av->total_payout, 2, ',', '.') }}
                            </td>
                            {{-- Status --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($av->status === 'finalizada')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200">
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-green-500"></span> Finalizada
                                    </span>
                                @elseif($av->status === 'cancelada')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-red-500"></span> Cancelada
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-50 text-yellow-700 border border-yellow-200">
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-yellow-500"></span> Rascunho
                                    </span>
                                @endif
                            </td>
                            {{-- Ação (Detalhes) --}}
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <button id="btn-{{ $av->id }}" onclick="toggleDetails({{ $av->id }})" 
                                        class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-lg transition duration-200">
                                    <i class="fas fa-eye mr-1"></i> Detalhes
                                </button>
                            </td>
                        </tr>

                        <!-- Linha de Detalhes Collapsible -->
                        <tr id="details-{{ $av->id }}" class="hidden bg-gray-50/50">
                            <td colspan="6" class="px-6 py-4">
                                <div class="border rounded-2xl bg-white p-4 shadow-sm space-y-3">
                                    <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                        <i class="fas fa-list text-pink-650" style="color: var(--pink);"></i> Itens do Lote #{{ str_pad($av->id, 5, '0', STR_PAD_LEFT) }}
                                    </h4>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-100 text-xs">
                                            <thead>
                                                <tr class="text-gray-500 font-bold">
                                                    <th class="py-2 text-left">Item</th>
                                                    <th class="py-2 text-left">Marca</th>
                                                    <th class="py-2 text-center">Estado</th>
                                                    <th class="py-2 text-right">Repasse Crédito</th>
                                                    <th class="py-2 text-right">Repasse Dinheiro</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @forelse($av->itens as $item)
                                                    <tr class="hover:bg-gray-50/20">
                                                        <td class="py-2.5 font-semibold text-gray-800">{{ $item->nome }}</td>
                                                        <td class="py-2.5 text-gray-600">{{ $item->marca ?? '-' }}</td>
                                                        <td class="py-2.5 text-center">
                                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-pink-50 text-pink-700">
                                                                {{ $item->estado ?? '-' }}
                                                            </span>
                                                        </td>
                                                        <td class="py-2.5 text-right text-purple-700 font-bold">R$ {{ number_format($item->payout_credito, 2, ',', '.') }}</td>
                                                        <td class="py-2.5 text-right text-blue-700 font-bold">R$ {{ number_format($item->payout_dinheiro, 2, ',', '.') }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="py-4 text-center text-gray-400">Nenhum item cadastrado neste lote.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="text-3xl mb-3"><i class="fas fa-box-open text-gray-300"></i></div>
                                <p class="text-sm font-medium">Você ainda não tem avaliações ou lotes de desapego registrados.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function toggleDetails(id) {
        const el = document.getElementById(`details-${id}`);
        const btn = document.getElementById(`btn-${id}`);
        
        if (el.classList.contains('hidden')) {
            el.classList.remove('hidden');
            btn.innerHTML = '<i class="fas fa-eye-slash mr-1"></i> Ocultar';
            btn.classList.replace('bg-gray-100', 'bg-pink-100');
            btn.classList.replace('hover:bg-gray-200', 'hover:bg-pink-200');
            btn.classList.replace('text-gray-700', 'text-pink-750');
            btn.style.color = 'var(--pink)';
        } else {
            el.classList.add('hidden');
            btn.innerHTML = '<i class="fas fa-eye mr-1"></i> Detalhes';
            btn.classList.replace('bg-pink-100', 'bg-gray-100');
            btn.classList.replace('hover:bg-pink-200', 'hover:bg-gray-200');
            btn.classList.replace('text-pink-750', 'text-gray-700');
            btn.style.color = '';
        }
    }
</script>
@endsection

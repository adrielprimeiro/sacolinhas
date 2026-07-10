@extends('layouts.app')

@section('title', 'Avaliação de Desapegos')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ activeFinalizeId: null, activeFinalizeClube: false }">

    {{-- Cabeçalho --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Avaliação de Desapegos</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Módulo de entrada de mercadorias, precificação e curadoria de peças usadas.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('admin.avaliacoes.create') }}"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm py-2.5 px-4 rounded-lg transition-colors shadow-sm"
            >
                <i class="fas fa-plus"></i>
                Nova Avaliação (Entrada)
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm shadow-sm">
            <i class="fas fa-check-circle text-green-500"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm shadow-sm">
            <i class="fas fa-exclamation-circle text-red-500"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <form action="{{ route('admin.avaliacoes.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
            {{-- Fornecedor --}}
            <div>
                <label for="user_id" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Fornecedor / Cliente</label>
                <select name="user_id" id="user_id" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">Todos</option>
                    @foreach ($clientes as $c)
                        <option value="{{ $c->id }}" {{ request('user_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->apelido ?: 'Sem apelido' }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div>
                <label for="status" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Status</label>
                <select name="status" id="status" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">Todos</option>
                    <option value="rascunho" {{ request('status') === 'rascunho' ? 'selected' : '' }}>Rascunho</option>
                    <option value="finalizada" {{ request('status') === 'finalizada' ? 'selected' : '' }}>Finalizada</option>
                    <option value="cancelada" {{ request('status') === 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                </select>
            </div>

            {{-- Tipo de Compra --}}
            <div>
                <label for="tipo_compra" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Tipo de Compra</label>
                <select name="tipo_compra" id="tipo_compra" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">Todos</option>
                    <option value="avaliados" {{ request('tipo_compra') === 'avaliados' ? 'selected' : '' }}>Avaliados (Brechó/Desapego)</option>
                    <option value="direta" {{ request('tipo_compra') === 'direta' ? 'selected' : '' }}>Compra Direta (Estoque)</option>
                </select>
            </div>

            {{-- Ações de Filtro --}}
            <div class="flex gap-2">
                <button type="submit" class="flex-1 inline-flex justify-center items-center gap-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-sm py-2.5 px-4 rounded-lg transition-colors border border-gray-300">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="{{ route('admin.avaliacoes.index') }}" class="inline-flex items-center justify-center bg-white hover:bg-gray-50 text-gray-500 font-semibold text-sm py-2.5 px-3 rounded-lg transition-colors border border-gray-300" title="Limpar Filtros">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- Tabela de Avaliações --}}
    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Lote / Data</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fornecedor</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo / Regime</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Venda</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Repasse</th>
                        <th class="px-6 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($avaliacoes as $av)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">Lote #{{ str_pad($av->id, 5, '0', STR_PAD_LEFT) }}</div>
                                <div class="text-xs text-gray-400 mt-0.5"><i class="far fa-calendar-alt mr-1"></i>{{ $av->formatted_data_avaliacao }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">{{ $av->user->name ?? 'Fornecedor Desconhecido' }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $av->tipo_cliente === 'clube' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $av->tipo_cliente === 'clube' ? 'Membro Clube' : 'Fora do Clube' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ $av->tipo_compra === 'avaliados' ? 'Avaliados (Brechó)' : 'Compra Direta' }}
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">
                                    @if ($av->status === 'finalizada')
                                        Pág: <span class="capitalize font-semibold text-gray-700">{{ $av->pagamento_escolhido === 'credito' ? 'Créditos' : 'Dinheiro/PIX' }}</span>
                                    @else
                                        Pág: <span class="text-gray-400 font-medium">Pendente</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-950">
                                {{ $av->formatted_total_venda }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if ($av->status === 'finalizada')
                                    <span class="font-black text-indigo-600">{{ $av->formatted_total_payout }}</span>
                                @else
                                    <div class="text-xs space-y-0.5">
                                        <div class="text-purple-700 font-bold">
                                            Créd: R$ {{ number_format($av->items->sum('payout_credito'), 2, ',', '.') }}
                                        </div>
                                        <div class="text-blue-700 font-bold">
                                            Din: R$ {{ number_format($av->items->sum('payout_dinheiro'), 2, ',', '.') }}
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if ($av->status === 'finalizada')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-green-500"></span> Finalizada
                                    </span>
                                @elseif ($av->status === 'cancelada')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-200">
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-red-500"></span> Cancelada
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 border border-yellow-200">
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-yellow-500"></span> Rascunho
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- Enviar por WhatsApp --}}
                                    <form action="{{ route('admin.avaliacoes.send-whatsapp', $av) }}" method="POST" class="inline" onsubmit="return confirm('Deseja realmente enviar esta avaliação via WhatsApp para o cliente?')">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-green-50 text-green-600 transition-colors border border-gray-200" title="Enviar por WhatsApp">
                                            <i class="fab fa-whatsapp text-sm font-bold"></i>
                                        </button>
                                    </form>

                                    {{-- Visualizar recibo --}}
                                    <a href="{{ route('admin.avaliacoes.show', $av) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-gray-100 text-gray-600 transition-colors border border-gray-200" title="Ver Detalhes / Recibo">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>

                                    @if ($av->status === 'rascunho')
                                        {{-- Editar --}}
                                        <a href="{{ route('admin.avaliacoes.edit', $av) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-gray-100 text-blue-600 transition-colors border border-gray-200" title="Editar Rascunho">
                                            <i class="fas fa-edit text-xs"></i>
                                        </a>

                                        {{-- Botão para Efetivar/Finalizar --}}
                                        <button 
                                            type="button" 
                                            @click="activeFinalizeId = {{ $av->id }}; activeFinalizeClube = {{ $av->tipo_cliente === 'clube' ? 'true' : 'false' }};"
                                            class="inline-flex items-center justify-center px-2 py-1 h-8 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-semibold text-xs transition-colors"
                                            title="Finalizar e Lançar no Estoque"
                                        >
                                            <i class="fas fa-check mr-1.5 text-[10px]"></i> Efetivar
                                        </button>

                                        {{-- Deletar --}}
                                        <form action="{{ route('admin.avaliacoes.destroy', $av) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja remover este rascunho de avaliação?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-red-50 text-red-600 transition-colors border border-gray-200" title="Excluir Rascunho">
                                                <i class="far fa-trash-alt text-xs"></i>
                                            </button>
                                        </form>
                                    @endif

                                    @if ($av->status === 'finalizada')
                                        {{-- Cancelar/Estornar --}}
                                        <form action="{{ route('admin.avaliacoes.cancel', $av) }}" method="POST" class="inline" onsubmit="return confirm('ATENÇÃO: Cancelar esta avaliação removerá as peças do estoque e reverterá o crédito/lançamento financeiro. Deseja prosseguir?')">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center justify-center px-2.5 py-1 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-semibold text-xs transition-colors" title="Cancelar e Estornar Lote">
                                                <i class="fas fa-ban mr-1.5 text-[10px]"></i> Cancelar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fas fa-hand-holding-usd text-4xl mb-3 text-gray-300"></i>
                                    <p class="text-sm font-medium">Nenhuma avaliação encontrada</p>
                                    <a href="{{ route('admin.avaliacoes.create') }}" class="mt-3 text-sm text-blue-600 hover:underline">
                                        Criar nova avaliação agora
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Paginação --}}
        @if ($avaliacoes->hasPages())
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                {{ $avaliacoes->links() }}
            </div>
        @endif
    </div>

    {{-- Modal flutuante de confirmação de finalização usando AlpineJS (movido para fora da tabela) --}}
    <div 
        x-show="activeFinalizeId !== null" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black bg-opacity-50"
        @keydown.escape.window="activeFinalizeId = null"
    >
        <div class="relative bg-white rounded-xl shadow-lg max-w-md w-full p-6 mx-4 border border-gray-200" @click.away="activeFinalizeId = null">
            <h3 class="text-lg font-bold text-gray-900 mb-2">Finalizar Avaliação</h3>
            <p class="text-sm text-gray-500 mb-4">
                Escolha a forma de pagamento do repasse para este lote. Isso irá cadastrar as peças no estoque e creditar o fornecedor.
            </p>

            <form :action="`/admin/avaliacoes/${activeFinalizeId}/finalizar`" method="POST">
                @csrf
                <div class="mb-5">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Opção de Pagamento</label>
                    
                    <div class="space-y-3">
                        <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="pagamento_escolhido" value="credito" checked class="text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="block text-sm font-semibold text-gray-900">
                                    Créditos na Loja (<span x-text="activeFinalizeClube ? '60%' : '50%'"></span> do valor de venda)
                                </span>
                                <span class="block text-xs text-gray-500">Creditado automaticamente na carteira digital do cliente.</span>
                            </div>
                        </label>
                        
                        <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="pagamento_escolhido" value="dinheiro" class="text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="block text-sm font-semibold text-gray-900">
                                    Dinheiro/PIX imediato (<span x-text="activeFinalizeClube ? '40%' : '30%'"></span> do valor de venda)
                                </span>
                                <span class="block text-xs text-gray-500">Lança uma despesa paga no caixa do sistema financeiro.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button 
                        type="button" 
                        @click="activeFinalizeId = null" 
                        class="bg-white py-2 px-4 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none"
                    >
                        Cancelar
                    </button>
                    <button 
                        type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm py-2 px-4 rounded-lg shadow-sm transition-colors"
                    >
                        Confirmar e Efetivar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

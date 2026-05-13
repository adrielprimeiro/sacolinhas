@extends('layouts.app')

@section('title', 'Conciliação Financeira')
@section('brand_route', 'financeiro.dashboard')
@section('brand_icon', 'fas fa-balance-scale')

@section('content')

{{-- ===== SUB-NAV FINANCEIRO ===== --}}
<div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-3">
    <a href="{{ route('financeiro.dashboard') }}"
       class="px-4 py-2 rounded-t text-sm font-semibold text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 transition">
        <i class="fas fa-home mr-1"></i> Dashboard
    </a>
    <a href="{{ route('financeiro.lancamentos.index') }}"
       class="px-4 py-2 rounded-t text-sm font-semibold text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 transition">
        <i class="fas fa-file-invoice-dollar mr-1"></i> Lançamentos
    </a>
    <a href="{{ route('financeiro.contas.index') }}"
       class="px-4 py-2 rounded-t text-sm font-semibold text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 transition">
        <i class="fas fa-university mr-1"></i> Contas
    </a>
    <a href="{{ route('financeiro.orcamento.index') }}"
       class="px-4 py-2 rounded-t text-sm font-semibold text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 transition">
        <i class="fas fa-chart-bar mr-1"></i> Orçamento
    </a>
    <a href="{{ route('financeiro.conciliacao.index') }}"
       class="px-4 py-2 rounded-t text-sm font-semibold bg-indigo-600 text-white shadow-sm">
        <i class="fas fa-balance-scale mr-1"></i> Conciliação
    </a>
    <a href="{{ route('financeiro.pessoas.index') }}"
       class="px-4 py-2 rounded-t text-sm font-semibold text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 transition">
        <i class="fas fa-users mr-1"></i> Contatos
    </a>
</div>

<div x-data="conciliacaoApp()">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-black text-gray-800">Conciliação Financeira</h2>
        <div class="flex gap-2">
            <button @click="showModalOfx = true" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-xl text-sm font-bold hover:bg-gray-50 transition shadow-sm">
                <i class="fas fa-file-upload mr-2"></i>Upload OFX
            </button>
            <button @click="showModalMp = true" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-indigo-700 transition shadow-md">
                <i class="fab fa-amazon-pay mr-2"></i>Sincronizar Mercado Pago
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r shadow-sm" role="alert">
            <p class="font-bold">Sucesso!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r shadow-sm" role="alert">
            <p class="font-bold">Erro!</p>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-24">
        <!-- Coluna Esquerda: Extrato -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-[600px]">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                <h5 class="font-bold text-gray-700">Extrato (Banco/MP)</h5>
                <span class="bg-gray-200 text-gray-700 text-xs font-bold px-2 py-1 rounded-full">{{ count($extrato) }} pendentes</span>
            </div>
            <div class="overflow-y-auto flex-grow">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-white shadow-sm">
                        <tr>
                            <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Data</th>
                            <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Descrição</th>
                            <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Valor</th>
                            <th class="px-5 py-3 w-20"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($extrato as $t)
                            <tr class="cursor-pointer transition hover:bg-indigo-50 group" 
                                :class="selectedExtrato === {{ $t->id }} ? 'bg-indigo-50 border-l-4 border-indigo-600' : 'border-l-4 border-transparent'"
                                @click="selectExtrato({{ $t->id }}, {{ $t->valor }}, '{{ $t->tipo }}', '{{ $t->getPedidoId() }}')">
                                <td class="px-5 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $t->data->format('d/m/Y') }}</td>
                                <td class="px-5 py-4">
                                    <div class="text-sm font-bold text-gray-800">{{ $t->descricao }}</div>
                                    <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ $t->origem }}</div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <span class="text-sm font-black {{ $t->tipo === 'entrada' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $t->tipo === 'entrada' ? '+' : '-' }} R$ {{ number_format($t->valor, 2, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition">
                                        <button @click.stop="openQuickCreate({{ $t->id }}, '{{ $t->descricao }}', {{ $t->valor }}, '{{ $t->tipo }}')" 
                                                class="p-1.5 text-indigo-600 hover:bg-indigo-100 rounded-lg transition" title="Lançamento Rápido">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <form action="{{ route('financeiro.conciliacao.ignorar', $t->id) }}" method="POST" onsubmit="return confirm('Ignorar esta transação?')">
                                            @csrf
                                            <button type="submit" class="p-1.5 text-red-500 hover:bg-red-100 rounded-lg transition" title="Ignorar">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Coluna Direita: Sistema -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-[600px]">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                <div class="flex items-center justify-between mb-2">
                    <h5 class="font-bold text-gray-700">Lançamentos no Sistema</h5>
                    <span class="bg-gray-200 text-gray-700 text-xs font-bold px-2 py-1 rounded-full">{{ count($lancamentos) }} registros</span>
                </div>
                <div class="relative">
                    <input type="text" x-model="searchSistema" placeholder="Filtrar por pedido ou descrição..." 
                           class="w-full text-xs border border-gray-200 rounded-lg pl-8 pr-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-[10px]"></i>
                    <button x-show="searchSistema" @click="searchSistema = ''" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
            </div>
            <div class="overflow-y-auto flex-grow">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-white shadow-sm">
                        <tr>
                            <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Vencimento</th>
                            <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Descrição / Pessoa</th>
                            <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($lancamentos as $l)
                            <tr x-show="shouldShowSistema('{{ str_replace("'", "", $l->descricao) }}', '{{ $l->referencia_id }}')"
                                class="cursor-pointer transition hover:bg-indigo-50" 
                                :class="selectedSistema === {{ $l->id }} ? 'bg-indigo-50 border-l-4 border-indigo-600' : 'border-l-4 border-transparent'"
                                @click="selectSistema({{ $l->id }}, {{ $l->valor_total }}, '{{ $l->tipo === 'receita' ? 'entrada' : 'saida' }}')">
                                <td class="px-5 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $l->data_vencimento->format('d/m/Y') }}</td>
                                <td class="px-5 py-4">
                                    <div class="text-sm font-bold text-gray-800">{{ $l->descricao }}</div>
                                    <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ $l->pessoa->nome ?? '---' }}</div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <span class="text-sm font-black text-gray-700">
                                        R$ {{ number_format($l->valor_total, 2, ',', '.') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Barra de Ações Fixa -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 shadow-2xl z-50">
        <div class="container mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-2">
                <template x-if="selectedExtrato">
                    <span class="bg-indigo-100 text-indigo-700 text-xs font-black px-3 py-1.5 rounded-full border border-indigo-200 flex items-center gap-2">
                        <i class="fas fa-file-invoice"></i> EXTRATO SELECIONADO
                    </span>
                </template>
                <template x-if="selectedSistema">
                    <span class="bg-purple-100 text-purple-700 text-xs font-black px-3 py-1.5 rounded-full border border-purple-200 flex items-center gap-2">
                        <i class="fas fa-desktop"></i> SISTEMA SELECIONADO
                    </span>
                </template>
                <template x-if="!selectedExtrato && !selectedSistema">
                    <span class="text-sm font-medium text-gray-400">Selecione um item de cada lado para conciliar</span>
                </template>
            </div>

            <div class="flex items-center gap-6">
                <template x-if="selectedExtrato && selectedSistema">
                    <div class="text-right">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Diferença</p>
                        <p class="text-lg font-black" :class="valorExtrato === valorSistema ? 'text-green-600' : 'text-red-600'">
                            R$ <span x-text="formatMoney(Math.abs(valorExtrato - valorSistema))"></span>
                        </p>
                    </div>
                </template>

                <form action="{{ route('financeiro.conciliacao.vincular') }}" method="POST">
                    @csrf
                    <input type="hidden" name="transacao_id" :value="selectedExtrato">
                    <input type="hidden" name="lancamento_id" :value="selectedSistema">
                    <button type="submit" 
                            class="bg-green-600 text-white px-8 py-3 rounded-2xl font-black text-sm uppercase tracking-wider hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-lg flex items-center gap-2" 
                            :disabled="!canConciliar()">
                        <i class="fas fa-link"></i> Vincular (Match)
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal OFX -->
    <div x-show="showModalOfx" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50" x-cloak>
        <form action="{{ route('financeiro.conciliacao.importar') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl">
            @csrf
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-black text-gray-800">Importar Arquivo OFX</h3>
                <button type="button" @click="showModalOfx = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Arquivo (.ofx)</label>
                    <input type="file" name="arquivo_ofx" class="w-full text-sm border border-gray-200 rounded-xl p-2" accept=".ofx" required>
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Conta Bancária Destino</label>
                    <select name="conta_bancaria_id" class="w-full text-sm border border-gray-200 rounded-xl p-2 bg-white">
                        <option value="1">Caixa Principal</option>
                    </select>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2">
                <button type="button" @click="showModalOfx = false" class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-700">Cancelar</button>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-xl text-sm font-black hover:bg-indigo-700 shadow-md">Processar</button>
            </div>
        </form>
    </div>

    <!-- Modal Mercado Pago -->
    <div x-show="showModalMp" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50" x-cloak>
        <form action="{{ route('financeiro.conciliacao.sincronizar-mp') }}" method="POST" class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl">
            @csrf
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-black text-gray-800">Sincronizar Mercado Pago</h3>
                <button type="button" @click="showModalMp = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Início</label>
                        <input type="date" name="start_date" class="w-full text-sm border border-gray-200 rounded-xl p-2" value="{{ date('Y-m-d', strtotime('-7 days')) }}" required>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Fim</label>
                        <input type="date" name="end_date" class="w-full text-sm border border-gray-200 rounded-xl p-2" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-100 p-3 rounded-xl flex gap-3">
                    <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                    <p class="text-[11px] text-blue-700 font-medium leading-relaxed">
                        Isso buscará todas as transações aprovadas na conta do Mercado Pago para o período selecionado.
                    </p>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2">
                <button type="button" @click="showModalMp = false" class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-700">Cancelar</button>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-xl text-sm font-black hover:bg-indigo-700 shadow-md">Sincronizar Agora</button>
            </div>
        </form>
    </div>

    <!-- Modal Quick Create -->
    <div x-show="showModalQuick" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50" x-cloak>
        <form action="{{ route('financeiro.conciliacao.criar-rapido') }}" method="POST" class="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl">
            @csrf
            <input type="hidden" name="transacao_id" x-model="quickData.id">
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-black text-gray-800">Lançamento Rápido</h3>
                <button type="button" @click="showModalQuick = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Descrição</label>
                    <input type="text" class="w-full text-sm border border-gray-100 bg-gray-50 rounded-xl p-2 font-bold" x-model="quickData.descricao" readonly>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Valor</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-sm font-black text-gray-400">R$</span>
                            <input type="text" class="w-full text-sm border border-gray-100 bg-gray-50 rounded-xl p-2 pl-9 font-black" x-model="quickData.valor" readonly>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Tipo</label>
                        <input type="text" class="w-full text-sm border border-gray-100 bg-gray-50 rounded-xl p-2 font-black uppercase tracking-wider" x-model="quickData.tipo" readonly>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Classificação Financeira</label>
                    <select name="classificacao_financeira_id" class="w-full text-sm border border-gray-200 rounded-xl p-2 bg-white font-bold" required>
                        <option value="">Selecione uma categoria...</option>
                        @foreach($classificacoes as $c)
                            <option value="{{ $c->id }}">{{ $c->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Pessoa (Opcional)</label>
                    <select name="pessoa_id" class="w-full text-sm border border-gray-200 rounded-xl p-2 bg-white font-bold">
                        <option value="">Não identificar (Avulso)</option>
                        @foreach($pessoas as $p)
                            <option value="{{ $p->id }}">{{ $p->nome }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2">
                <button type="button" @click="showModalQuick = false" class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-700">Cancelar</button>
                <button type="submit" class="bg-green-600 text-white px-8 py-2 rounded-xl text-sm font-black hover:bg-green-700 shadow-md">Salvar e Conciliar</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function conciliacaoApp() {
        return {
            selectedExtrato: null,
            selectedSistema: null,
            valorExtrato: 0,
            valorSistema: 0,
            tipoExtrato: '',
            tipoSistema: '',
            quickData: { id: '', descricao: '', valor: '', tipo: '' },
            showModalOfx: false,
            showModalMp: false,
            showModalQuick: false,
            searchSistema: '',

            selectExtrato(id, valor, tipo, suggestion) {
                if (this.selectedExtrato === id) {
                    this.selectedExtrato = null;
                    this.valorExtrato = 0;
                    this.tipoExtrato = '';
                } else {
                    this.selectedExtrato = id;
                    this.valorExtrato = parseFloat(valor);
                    this.tipoExtrato = tipo;
                    // Se houver uma sugestão (ID do pedido), aplica o filtro automaticamente
                    if (suggestion) {
                        this.searchSistema = suggestion;
                    }
                }
            },

            selectSistema(id, valor, tipo) {
                if (this.selectedSistema === id) {
                    this.selectedSistema = null;
                    this.valorSistema = 0;
                    this.tipoSistema = '';
                } else {
                    this.selectedSistema = id;
                    this.valorSistema = parseFloat(valor);
                    this.tipoSistema = tipo;
                }
            },

            canConciliar() {
                return this.selectedExtrato && this.selectedSistema && (this.tipoExtrato === this.tipoSistema);
            },

            formatMoney(v) {
                return v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            openQuickCreate(id, desc, valor, tipo) {
                this.quickData = { 
                    id, 
                    descricao: desc, 
                    valor: this.formatMoney(valor), 
                    tipo: tipo === 'entrada' ? 'RECEITA' : 'DESPESA' 
                };
                this.showModalQuick = true;
            },

            shouldShowSistema(descricao, refId) {
                if (!this.searchSistema) return true;
                const search = this.searchSistema.toLowerCase();
                return descricao.toLowerCase().includes(search) || 
                       (refId && refId.toString().includes(search));
            },

            formatMoney(v) {
                if (!v) return '0,00';
                return v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }
    }
</script>
@endpush

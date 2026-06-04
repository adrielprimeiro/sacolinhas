@extends('layouts.app')

@section('title', 'Movimentações')
@section('brand_route', 'financeiro.dashboard')
@section('brand_icon', 'fas fa-balance-scale')

@section('content')

@php
$currentRoute = Route::currentRouteName();
@endphp
<div x-data="{ 
    showModalTransfer: false, 
    showModalEdit: false,
    editData: { id: '', conta_id: '', data: '', valor: '', forma: '', descricao: '', tipo: 'receita', classificacao_financeira_id: '', pessoa_id: '', has_lancamento: false },
    openEdit(mov) {
        this.editData = {
            id: mov.id,
            conta_id: mov.conta_bancaria_id,
            data: mov.data_pagamento.split('T')[0],
            valor: mov.valor_pago,
            forma: mov.forma_pagamento,
            descricao: mov.lancamento ? mov.lancamento.descricao : '',
            tipo: mov.lancamento ? mov.lancamento.tipo : 'receita',
            classificacao_financeira_id: mov.lancamento ? (mov.lancamento.classificacao_financeira_id || '') : '',
            pessoa_id: mov.lancamento ? (mov.lancamento.pessoa_id || '') : '',
            has_lancamento: mov.lancamento ? true : false
        };
        this.showModalEdit = true;
    }
}" class="container mx-auto">
<div class="flex flex-wrap gap-1 mb-6 border-b border-gray-200 pb-3">
    <a href="{{ route('financeiro.dashboard') }}"
       class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'dashboard') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-home mr-1"></i> Dashboard
    </a>
    <a href="{{ route('financeiro.conciliacao.index') }}"
       class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'conciliacao') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-balance-scale mr-1"></i> Conciliação
    </a>
    <a href="{{ route('financeiro.movimentacoes.index') }}"
       class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'movimentacoes') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-exchange-alt mr-1"></i> Movimentações
    </a>
    <a href="{{ route('financeiro.lancamentos.index') }}"
       class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'lancamentos') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-file-invoice-dollar mr-1"></i> Lançamentos
    </a>
    <a href="{{ route('financeiro.contas.index') }}"
       class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'contas') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-university mr-1"></i> Contas
    </a>
    <a href="{{ route('financeiro.orcamento.index') }}"
       class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'orcamento') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-chart-bar mr-1"></i> Orçamento
    </a>
    <a href="{{ route('financeiro.pessoas.index') }}"
       class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'pessoas') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-users mr-1"></i> Contatos
    </a>
</div>

@if($contaSelecionada)
<div class="mb-4 bg-indigo-50 border border-indigo-200 rounded-xl p-3 flex items-center justify-between">
    <div>
        <p class="text-xs font-black text-indigo-600 uppercase tracking-widest">Conta Selecionada</p>
        <p class="text-sm font-bold text-indigo-800">{{ $contaSelecionada->nome }}</p>
    </div>
    <div class="text-right">
        <p class="text-xs font-black text-indigo-400 uppercase">Saldo Atual</p>
        <p class="text-lg font-black text-indigo-700">R$ {{ number_format($contaSelecionada->saldo_atual, 2, ',', '.') }}</p>
    </div>
</div>
@endif

<div class="mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
            <p class="text-xs font-black text-green-600 uppercase tracking-widest">Total Entradas</p>
            <p class="text-2xl font-black text-green-700">R$ {{ number_format($totalEntradas, 2, ',', '.') }}</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
            <p class="text-xs font-black text-red-600 uppercase tracking-widest">Total Saídas</p>
            <p class="text-2xl font-black text-red-700">R$ {{ number_format($totalSaidas, 2, ',', '.') }}</p>
        </div>
        <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4">
            <p class="text-xs font-black text-indigo-600 uppercase tracking-widest">Saldo no Período</p>
            <p class="text-2xl font-black {{ $totalEntradas - $totalSaidas >= 0 ? 'text-green-700' : 'text-red-700' }}">
                R$ {{ number_format($totalEntradas - $totalSaidas, 2, ',', '.') }}
            </p>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 bg-gray-50">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="min-w-[180px]">
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Conta</label>
                <select name="conta_bancaria_id" onchange="this.form.submit()" class="border border-gray-200 rounded-xl p-2 text-sm bg-white w-full">
                    <option value="">Todas as Contas</option>
                    @foreach($contas as $conta)
                        <option value="{{ $conta->id }}" {{ request('conta_bancaria_id') == $conta->id ? 'selected' : '' }}>
                            {{ $conta->nome }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Data Início</label>
                <input type="date" name="data_inicio" value="{{ request('data_inicio') }}" onchange="this.form.submit()" class="border border-gray-200 rounded-xl p-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Data Fim</label>
                <input type="date" name="data_fim" value="{{ request('data_fim') }}" onchange="this.form.submit()" class="border border-gray-200 rounded-xl p-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Forma</label>
                <select name="forma_pagamento" onchange="this.form.submit()" class="border border-gray-200 rounded-xl p-2 text-sm bg-white">
                    <option value="">Todas</option>
                    <option value="pix" {{ request('forma_pagamento') == 'pix' ? 'selected' : '' }}>PIX</option>
                    <option value="transferencia" {{ request('forma_pagamento') == 'transferencia' ? 'selected' : '' }}>Transferência</option>
                    <option value="dinheiro" {{ request('forma_pagamento') == 'dinheiro' ? 'selected' : '' }}>Dinheiro</option>
                    <option value="boleto" {{ request('forma_pagamento') == 'boleto' ? 'selected' : '' }}>Boleto</option>
                    <option value="cartao" {{ request('forma_pagamento') == 'cartao' ? 'selected' : '' }}>Cartão</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Tipo</label>
                <select name="tipo" onchange="this.form.submit()" class="border border-gray-200 rounded-xl p-2 text-sm bg-white">
                    <option value="">Todos</option>
                    <option value="receita" {{ request('tipo') == 'receita' ? 'selected' : '' }}>Receita</option>
                    <option value="despesa" {{ request('tipo') == 'despesa' ? 'selected' : '' }}>Despesa</option>
                </select>
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Categoria</label>
                <select name="classificacao_financeira_id" onchange="this.form.submit()" class="border border-gray-200 rounded-xl p-2 text-sm bg-white w-full">
                    <option value="">Todas</option>
                    @foreach($classificacoes as $class)
                        <option value="{{ $class->id }}" {{ request('classificacao_financeira_id') == $class->id ? 'selected' : '' }}>
                            {{ $class->nome }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[180px]">
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Pessoa / Contato</label>
                <select name="pessoa_id" onchange="this.form.submit()" class="border border-gray-200 rounded-xl p-2 text-sm bg-white w-full">
                    <option value="">Todas</option>
                    @foreach($pessoas as $p)
                        <option value="{{ $p->id }}" {{ request('pessoa_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->nome }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-xl text-sm font-bold hover:bg-indigo-200">
                <i class="fas fa-filter mr-1"></i> Filtrar
            </button>
            <button type="button" @click="showModalTransfer = true" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-indigo-700">
                <i class="fas fa-exchange-alt mr-1"></i> Nova Transferência
            </button>
            <a href="{{ route('financeiro.movimentacoes.index') }}" class="text-gray-500 text-sm hover:text-gray-700 px-2">
                Limpar
            </a>
        </form>
    </div>

    <table class="w-full text-left">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase">Data</th>
                <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase">Tipo</th>
                <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase">Categoria</th>
                <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase">Lançamento</th>
                <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase">Pessoa</th>
                <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase">Forma</th>
                <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Conta</th>
                <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Valor</th>
                <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider text-center">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($movimentacoes as $m)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-4 text-sm text-gray-500">{{ $m->data_pagamento->format('d/m/Y') }}</td>
                    <td class="px-5 py-4 text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ ($m->lancamento->tipo ?? 'receita') === 'receita' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                            {{ ($m->lancamento->tipo ?? 'receita') === 'receita' ? 'Receita' : 'Despesa' }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600">
                        {{ $m->lancamento->classificacaoFinanceira->nome ?? '---' }}
                    </td>
                    <td class="px-5 py-4">
                        <div class="text-sm font-bold text-gray-800">{{ $m->lancamento->descricao ?? '---' }}</div>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ $m->lancamento->pessoa->nome ?? '---' }}</td>
                    <td class="px-5 py-4">
                        <span class="text-xs font-bold uppercase px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                            {{ $m->forma_pagamento }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-500">{{ $m->contaBancaria->nome ?? '---' }}</td>
                    <td class="px-5 py-4 text-right">
                        <span class="text-sm font-black {{ ($m->lancamento->tipo ?? 'receita') === 'receita' ? 'text-green-600' : 'text-red-600' }}">
                            {{ ($m->lancamento->tipo ?? 'receita') === 'receita' ? '+' : '-' }} R$ {{ number_format($m->valor_pago, 2, ',', '.') }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button @click='openEdit(@json($m))' class="text-indigo-600 hover:text-indigo-900 transition p-1">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('financeiro.movimentacoes.destroy', $m->id) }}" method="POST" onsubmit="return confirm('Excluir esta movimentação permanentemente?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-600 transition p-1">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-5 py-8 text-center text-gray-500">
                        Nenhuma movimentação encontrada.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="p-4 border-t border-gray-100">
        {{ $movimentacoes->links() }}
    </div>
</div>

    <!-- Modal Transferência -->
    <div x-show="showModalTransfer" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50" x-cloak>
        <form action="{{ route('financeiro.movimentacoes.transferir') }}" method="POST" class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl">
            @csrf
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-black text-gray-800 uppercase tracking-widest text-sm">Transferência entre Contas</h3>
                <button type="button" @click="showModalTransfer = false" class="text-gray-400 hover:text-gray-600 transition"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Conta de Origem (Saída)</label>
                        <select name="conta_origem_id" class="w-full text-sm border border-gray-200 rounded-xl p-3 bg-white font-bold" required>
                            <option value="">Selecione a conta de origem...</option>
                            @foreach($contas as $conta)
                                <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Conta de Destino (Entrada)</label>
                        <select name="conta_destino_id" class="w-full text-sm border border-gray-200 rounded-xl p-3 bg-white font-bold" required>
                            <option value="">Selecione a conta de destino...</option>
                            @foreach($contas as $conta)
                                <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Valor</label>
                        <div class="relative">
                            <span class="absolute left-3 top-3.5 text-xs font-black text-gray-400">R$</span>
                            <input type="number" name="valor" step="0.01" min="0.01" class="w-full text-sm border border-gray-200 rounded-xl p-3 pl-9 font-black" placeholder="0,00" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Data</label>
                        <input type="date" name="data_pagamento" class="w-full text-sm border border-gray-200 rounded-xl p-3 font-bold" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Descrição / Observação</label>
                    <input type="text" name="descricao" class="w-full text-sm border border-gray-200 rounded-xl p-3" placeholder="Ex: Ajuste de saldo, Transferência mensal...">
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2 border-t border-gray-100">
                <button type="button" @click="showModalTransfer = false" class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-700">Cancelar</button>
                <button type="submit" class="bg-indigo-600 text-white px-8 py-2 rounded-xl text-sm font-black hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all uppercase tracking-wider">
                    Executar Transferência
                </button>
            </div>
        </form>
    </div>
    <!-- Modal Editar Movimentação -->
    <div x-show="showModalEdit" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50" x-cloak>
        <form :action="'{{ url('admin/financeiro/movimentacoes') }}/' + editData.id" method="POST" class="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl">
            @csrf @method('PUT')
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-black text-gray-800 uppercase tracking-widest text-sm">Editar Movimentação</h3>
                <button type="button" @click="showModalEdit = false" class="text-gray-400 hover:text-gray-600 transition"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <div x-show="editData.has_lancamento" class="space-y-4 border-b border-gray-100 pb-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tipo</label>
                            <select name="tipo" x-model="editData.tipo" :required="editData.has_lancamento" class="w-full text-sm border border-gray-200 rounded-xl p-3 bg-white font-bold">
                                <option value="receita">Receita</option>
                                <option value="despesa">Despesa</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Categoria</label>
                            <select name="classificacao_financeira_id" x-model="editData.classificacao_financeira_id" :required="editData.has_lancamento" class="w-full text-sm border border-gray-200 rounded-xl p-3 bg-white font-bold">
                                <option value="">Selecione...</option>
                                @foreach($classificacoes as $class)
                                    <option value="{{ $class->id }}">{{ $class->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Descrição do Lançamento</label>
                        <input type="text" name="descricao" x-model="editData.descricao" :required="editData.has_lancamento" class="w-full text-sm border border-gray-200 rounded-xl p-3 font-bold" placeholder="Descrição do lançamento">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Pessoa / Contato</label>
                        <select name="pessoa_id" x-model="editData.pessoa_id" :required="editData.has_lancamento" class="w-full text-sm border border-gray-200 rounded-xl p-3 bg-white font-bold">
                            <option value="">Selecione...</option>
                            @foreach($pessoas as $p)
                                <option value="{{ $p->id }}">{{ $p->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Conta Bancária</label>
                        <select name="conta_bancaria_id" x-model="editData.conta_id" class="w-full text-sm border border-gray-200 rounded-xl p-3 bg-white font-bold" required>
                            @foreach($contas as $conta)
                                <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Forma de Pagamento</label>
                        <select name="forma_pagamento" x-model="editData.forma" class="w-full text-sm border border-gray-200 rounded-xl p-3 bg-white font-bold" required>
                            <option value="pix">PIX</option>
                            <option value="transferencia">Transferência</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="boleto">Boleto</option>
                            <option value="cartao">Cartão</option>
                            <option value="cartao_credito">Cartão de Crédito</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Data do Pagamento</label>
                        <input type="date" name="data_pagamento" x-model="editData.data" class="w-full text-sm border border-gray-200 rounded-xl p-3 font-bold" required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Valor Pago</label>
                        <input type="number" name="valor_pago" x-model="editData.valor" step="0.01" min="0" class="w-full text-sm border border-gray-200 rounded-xl p-3 font-black" required>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2 border-t border-gray-100">
                <button type="button" @click="showModalEdit = false" class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-700">Cancelar</button>
                <button type="submit" class="bg-indigo-600 text-white px-8 py-2 rounded-xl text-sm font-black hover:bg-indigo-700 shadow-lg transition-all uppercase tracking-wider">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
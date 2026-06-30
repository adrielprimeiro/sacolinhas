@extends('layouts.app')

@section('title', 'Auditoria de Conciliação')
@section('brand_route', 'financeiro.dashboard')
@section('brand_icon', 'fas fa-shield-alt')

@section('content')

@include('admin.financeiro._subnav')

<div class="mb-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-black text-gray-800">Auditoria & Diagnóstico de Conciliação</h2>
        <p class="text-sm text-gray-500 mt-1">Verifique a saúde de suas contas integradas e resolva diferenças de saldo.</p>
    </div>
    
    <form action="{{ route('financeiro.conciliacao.auditoria') }}" method="GET" id="auditoria_form" class="flex flex-wrap items-center gap-3">
        <div class="bg-white p-2 rounded-xl border border-gray-200 shadow-sm flex items-center gap-2">
            <label for="conta_select" class="text-xs font-bold text-gray-400 uppercase pl-2">Conta:</label>
            <select name="conta_bancaria_id" id="conta_select" onchange="document.getElementById('auditoria_form').submit()" 
                    class="border-0 bg-transparent text-gray-800 text-sm font-black focus:ring-0 focus:outline-none pr-8 cursor-pointer">
                @foreach($contas as $c)
                    @if(!str_contains(strtolower($c->nome), 'carteira'))
                        <option value="{{ $c->id }}" {{ $conta->id == $c->id ? 'selected' : '' }}>
                            {{ $c->nome }}
                        </option>
                    @endif
                @endforeach
            </select>
        </div>

        <div class="bg-white p-2 rounded-xl border border-gray-200 shadow-sm flex items-center gap-2">
            <span class="text-xs font-bold text-gray-400 uppercase pl-2">Período:</span>
            <input type="date" name="data_inicio" value="{{ $dataInicio }}" class="border-0 bg-transparent text-gray-800 text-sm font-bold focus:ring-0 focus:outline-none p-0 w-32">
            <span class="text-xs text-gray-305">até</span>
            <input type="date" name="data_fim" value="{{ $dataFim }}" class="border-0 bg-transparent text-gray-800 text-sm font-bold focus:ring-0 focus:outline-none p-0 w-32">
        </div>

        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold shadow-sm transition">
            <i class="fas fa-filter mr-1"></i> Filtrar
        </button>
        
        <a href="{{ route('financeiro.conciliacao.auditoria', ['conta_bancaria_id' => $conta->id, 'data_inicio' => '', 'data_fim' => '']) }}" 
           class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2.5 rounded-xl text-xs font-bold shadow-sm transition">
            Tudo (Sem Limite)
        </a>
    </form>
</div>

{{-- METRICAS DE SALDO --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-sm">
        <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Saldo do Sistema</span>
        <h3 class="text-2xl font-black text-gray-800 mt-1.5">R$ {{ number_format($saldoSistema, 2, ',', '.') }}</h3>
        <p class="text-xs text-gray-400 mt-1">Soma das movimentações lançadas</p>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-sm">
        <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Saldo Extrato Conciliado</span>
        <h3 class="text-2xl font-black text-gray-800 mt-1.5">R$ {{ number_format($saldoExtratoConciliado, 2, ',', '.') }}</h3>
        <p class="text-xs text-gray-400 mt-1">Apenas transações conciliadas</p>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-sm">
        <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Saldo Total Importado</span>
        <h3 class="text-2xl font-black text-gray-800 mt-1.5">R$ {{ number_format($saldoExtratoTotal, 2, ',', '.') }}</h3>
        <p class="text-xs text-gray-400 mt-1">Conciliadas + Pendentes</p>
    </div>

    @php
    $diferenca = abs($saldoSistema - $saldoExtratoConciliado);
    $temDiferenca = $diferenca >= 0.01;
    @endphp
    <div class="p-5 rounded-2xl border shadow-sm {{ $temDiferenca ? 'bg-red-50/50 border-red-200' : 'bg-green-50/50 border-green-200' }}">
        <span class="text-[10px] font-black uppercase tracking-wider {{ $temDiferenca ? 'text-red-500' : 'text-green-600' }}">
            Diferença (Sistema vs Extrato)
        </span>
        <h3 class="text-2xl font-black mt-1.5 {{ $temDiferenca ? 'text-red-700' : 'text-green-700' }}">
            R$ {{ number_format($saldoSistema - $saldoExtratoConciliado, 2, ',', '.') }}
        </h3>
        <p class="text-xs mt-1 {{ $temDiferenca ? 'text-red-500' : 'text-green-600' }}">
            {!! $temDiferenca ? '<i class="fas fa-exclamation-triangle mr-1"></i> Precisa de ajustes' : '<i class="fas fa-check-circle mr-1"></i> Saldos batendo!' !!}
        </p>
    </div>
</div>

{{-- SEÇÃO DE DIVERGÊNCIAS (TABS) --}}
<div x-data="{ tab: 'orfas' }" class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden mb-12">
    {{-- Tabs Header --}}
    <div class="bg-gray-50 border-b border-gray-100 flex flex-wrap">
        <button @click="tab = 'orfas'" 
                :class="tab === 'orfas' ? 'border-indigo-600 text-indigo-600 bg-white font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 bg-transparent'"
                class="px-5 py-4 border-b-2 text-sm transition-all flex items-center gap-2">
            <i class="fas fa-unlink text-xs"></i> Transações Órfãs
            <span :class="tab === 'orfas' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-200 text-gray-700'" 
                  class="text-[10px] px-2 py-0.5 rounded-full font-black">
                {{ $transacoesOrfas->count() }}
            </span>
        </button>
        <button @click="tab = 'valores'" 
                :class="tab === 'valores' ? 'border-indigo-600 text-indigo-600 bg-white font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 bg-transparent'"
                class="px-5 py-4 border-b-2 text-sm transition-all flex items-center gap-2">
            <i class="fas fa-exclamation-circle text-xs"></i> Divergência de Valores
            <span :class="tab === 'valores' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-200 text-gray-700'" 
                  class="text-[10px] px-2 py-0.5 rounded-full font-black">
                {{ count($divergenciasValores) }}
            </span>
        </button>
        <button @click="tab = 'manuais'" 
                :class="tab === 'manuais' ? 'border-indigo-600 text-indigo-600 bg-white font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 bg-transparent'"
                class="px-5 py-4 border-b-2 text-sm transition-all flex items-center gap-2">
            <i class="fas fa-keyboard text-xs"></i> Movimentações Manuais
            <span :class="tab === 'manuais' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-200 text-gray-700'" 
                  class="text-[10px] px-2 py-0.5 rounded-full font-black">
                {{ $movimentacoesManuais->count() }}
            </span>
        </button>
        <button @click="tab = 'pendentes'" 
                :class="tab === 'pendentes' ? 'border-indigo-600 text-indigo-600 bg-white font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 bg-transparent'"
                class="px-5 py-4 border-b-2 text-sm transition-all flex items-center gap-2">
            <i class="fas fa-clock text-xs"></i> Extrato Pendente
            <span :class="tab === 'pendentes' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-200 text-gray-700'" 
                  class="text-[10px] px-2 py-0.5 rounded-full font-black">
                {{ $transacoesPendentes->count() }}
            </span>
        </button>
    </div>

    {{-- Tabs Content --}}
    <div class="p-6">
        
        {{-- TAB: TRANSAÇÕES ÓRFÃS --}}
        <div x-show="tab === 'orfas'" class="space-y-4">
            <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-xl text-xs leading-relaxed flex items-start gap-2.5">
                <i class="fas fa-info-circle mt-0.5 text-sm"></i>
                <div>
                    <strong>O que são transações órfãs?</strong> Estas transações constam no banco de dados como "conciliadas", mas o registro de fluxo de caixa (Movimentação) associado a elas não existe (provavelmente foi deletado manualmente). Isso deixa o saldo do extrato menor/maior do que o saldo do sistema.
                    <br><strong class="mt-1 block">Solução:</strong> Use o botão **Desvincular e Corrigir** para redefinir o status da transação para "pendente", permitindo que você a concilie corretamente de novo.
                </div>
            </div>

            @if($transacoesOrfas->isEmpty())
                <div class="text-center py-12">
                    <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-check text-lg"></i>
                    </div>
                    <h4 class="text-sm font-black text-gray-800">Parabéns!</h4>
                    <p class="text-xs text-gray-400 mt-1">Nenhuma transação órfã encontrada para esta conta.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-gray-100 text-gray-400 uppercase font-black tracking-wider">
                                <th class="py-3 px-4">Data</th>
                                <th class="py-3 px-4">Descrição no Extrato</th>
                                <th class="py-3 px-4 text-right">Valor</th>
                                <th class="py-3 px-4 text-center">Tipo</th>
                                <th class="py-3 px-4 text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($transacoesOrfas as $t)
                                <tr>
                                    <td class="py-3.5 px-4 font-bold text-gray-500">{{ $t->data->format('d/m/Y') }}</td>
                                    <td class="py-3.5 px-4 font-bold text-gray-800">{{ $t->descricao }}</td>
                                    <td class="py-3.5 px-4 text-right font-black text-gray-800">R$ {{ number_format($t->valor, 2, ',', '.') }}</td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase {{ $t->tipo === 'entrada' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $t->tipo }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <form action="{{ route('financeiro.conciliacao.auditoria.desvincular', $t->id) }}" method="POST" onsubmit="return confirm('Deseja desvincular esta transação?')">
                                            @csrf
                                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-black px-3.5 py-1.5 rounded-lg transition shadow-sm">
                                                Desvincular e Corrigir
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- TAB: DIVERGÊNCIA DE VALORES --}}
        <div x-show="tab === 'valores'" class="space-y-4">
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-xl text-xs leading-relaxed flex items-start gap-2.5">
                <i class="fas fa-exclamation-triangle mt-0.5 text-sm"></i>
                <div>
                    <strong>O que são divergências de valores?</strong> Acontece quando uma transação bancária foi vinculada a um lançamento, mas a movimentação criada no sistema possui um valor pago diferente do valor real debitado/creditado no banco.
                    <br><strong class="mt-1 block">Solução:</strong> Use o botão **Desvincular e Corrigir**. Isso excluirá a movimentação incorreta e voltará tanto o lançamento quanto a transação para o status de "pendente" para que a conciliação seja refeita com os valores corretos.
                </div>
            </div>

            @if(count($divergenciasValores) === 0)
                <div class="text-center py-12">
                    <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-check text-lg"></i>
                    </div>
                    <h4 class="text-sm font-black text-gray-800">Tudo correto!</h4>
                    <p class="text-xs text-gray-400 mt-1">Nenhuma divergência de valores encontrada nesta conta.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-gray-100 text-gray-400 uppercase font-black tracking-wider">
                                <th class="py-3 px-4">Data</th>
                                <th class="py-3 px-4">Descrição</th>
                                <th class="py-3 px-4 text-right">Valor Extrato</th>
                                <th class="py-3 px-4 text-right">Valor Movimentado</th>
                                <th class="py-3 px-4 text-right">Diferença</th>
                                <th class="py-3 px-4 text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($divergenciasValores as $t)
                                <tr>
                                    <td class="py-3.5 px-4 font-bold text-gray-500">{{ $t->data->format('d/m/Y') }}</td>
                                    <td class="py-3.5 px-4 font-bold text-gray-800">
                                        {{ $t->descricao }}
                                        <div class="text-[9px] text-gray-400 font-normal mt-0.5">
                                            Movimentação #{{ $t->movimentacao->id }} | Lançamento #{{ $t->movimentacao->lancamento_id }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black text-gray-800">R$ {{ number_format($t->valor, 2, ',', '.') }}</td>
                                    <td class="py-3.5 px-4 text-right font-black text-red-600">R$ {{ number_format($t->movimentacao->valor_pago, 2, ',', '.') }}</td>
                                    <td class="py-3.5 px-4 text-right font-black text-amber-600">
                                        R$ {{ number_format($t->valor - $t->movimentacao->valor_pago, 2, ',', '.') }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <form action="{{ route('financeiro.conciliacao.auditoria.desvincular', $t->id) }}" method="POST" onsubmit="return confirm('Excluir movimentação e desvincular para corrigir?')">
                                            @csrf
                                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-black px-3.5 py-1.5 rounded-lg transition shadow-sm">
                                                Desvincular e Corrigir
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- TAB: MOVIMENTAÇÕES MANUAIS --}}
        <div x-show="tab === 'manuais'" class="space-y-4">
            <div class="bg-gray-50 border border-gray-200 text-gray-600 p-4 rounded-xl text-xs leading-relaxed flex items-start gap-2.5">
                <i class="fas fa-info-circle mt-0.5 text-sm text-gray-500"></i>
                <div>
                    <strong>O que são movimentações manuais?</strong> São baixas de lançamentos criadas diretamente no sistema que estão vinculadas a esta conta bancária, mas que não possuem nenhuma transação correspondente do extrato bancário anexada.
                    <br><strong class="mt-1 block">Atenção:</strong> Isso pode estar correto (se o extrato daquela data ainda não foi importado, ou se foi um ajuste interno). Mas se houver movimentações duplicadas aqui, elas precisam ser corrigidas/excluídas na tela de **Movimentações** para ajustar o saldo.
                </div>
            </div>

            @if($movimentacoesManuais->isEmpty())
                <div class="text-center py-12">
                    <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-check text-lg"></i>
                    </div>
                    <h4 class="text-sm font-black text-gray-800">Tudo limpo!</h4>
                    <p class="text-xs text-gray-400 mt-1">Nenhuma movimentação manual sem extrato encontrada nesta conta.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-gray-100 text-gray-400 uppercase font-black tracking-wider">
                                <th class="py-3 px-4">ID</th>
                                <th class="py-3 px-4">Data Pagamento</th>
                                <th class="py-3 px-4">Pessoa / Categoria</th>
                                <th class="py-3 px-4">Descrição Lançamento</th>
                                <th class="py-3 px-4 text-right">Valor Pago</th>
                                <th class="py-3 px-4 text-center">Tipo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($movimentacoesManuais as $m)
                                <tr>
                                    <td class="py-3.5 px-4 font-bold text-gray-400">#{{ $m->id }}</td>
                                    <td class="py-3.5 px-4 font-bold text-gray-500">{{ $m->data_pagamento->format('d/m/Y') }}</td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-gray-850">{{ $m->lancamento->pessoa->nome ?? 'Sem Contato' }}</div>
                                        <div class="text-[9px] text-gray-400 font-bold uppercase">{{ $m->lancamento->classificacaoFinanceira->nome ?? 'Sem Categoria' }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-700">{{ $m->lancamento->descricao ?? 'Sem Descrição' }}</td>
                                    <td class="py-3.5 px-4 text-right font-black text-gray-850">R$ {{ number_format($m->valor_pago, 2, ',', '.') }}</td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase {{ $m->lancamento->tipo === 'receita' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $m->lancamento->tipo === 'receita' ? 'entrada' : 'saida' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- TAB: TRANSAÇÕES PENDENTES --}}
        <div x-show="tab === 'pendentes'" class="space-y-4">
            <div class="bg-gray-50 border border-gray-200 text-gray-600 p-4 rounded-xl text-xs leading-relaxed flex items-start gap-2.5">
                <i class="fas fa-info-circle mt-0.5 text-sm text-indigo-500"></i>
                <div>
                    <strong>Transações Pendentes no Extrato:</strong> Estas transações foram importadas via OFX/API mas ainda não foram conciliadas no sistema. O saldo do banco físico as inclui, mas o saldo atual do sistema não.
                    <br>Você pode conciliá-las acessando a tela de **Conciliação** principal.
                </div>
            </div>

            @if($transacoesPendentes->isEmpty())
                <div class="text-center py-12">
                    <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-check text-lg"></i>
                    </div>
                    <h4 class="text-sm font-black text-gray-800">Sem pendências!</h4>
                    <p class="text-xs text-gray-400 mt-1">Todas as transações importadas desta conta foram conciliadas.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-gray-100 text-gray-400 uppercase font-black tracking-wider">
                                <th class="py-3 px-4">Data</th>
                                <th class="py-3 px-4">Descrição no Extrato</th>
                                <th class="py-3 px-4 text-right">Valor</th>
                                <th class="py-3 px-4 text-center">Tipo</th>
                                <th class="py-3 px-4 text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($transacoesPendentes as $t)
                                <tr>
                                    <td class="py-3.5 px-4 font-bold text-gray-500">{{ $t->data->format('d/m/Y') }}</td>
                                    <td class="py-3.5 px-4 font-bold text-gray-800">{{ $t->descricao }}</td>
                                    <td class="py-3.5 px-4 text-right font-black text-gray-800">R$ {{ number_format($t->valor, 2, ',', '.') }}</td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-0.5 rounded-full font-black text-[9px] uppercase {{ $t->tipo === 'entrada' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $t->tipo }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <a href="{{ route('financeiro.conciliacao.index') }}" class="bg-indigo-50 border border-indigo-200 text-indigo-700 hover:bg-indigo-100 text-[10px] font-black px-3.5 py-1.5 rounded-lg transition shadow-sm inline-block">
                                            Ir Conciliar
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</div>

@endsection

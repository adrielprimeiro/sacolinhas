@extends('layouts.app')

@section('title', 'Conciliação Financeira')
@section('brand_route', 'financeiro.dashboard')
@section('brand_icon', 'fas fa-balance-scale')

@section('content')

@include('admin.financeiro._subnav')

<div x-data="conciliacaoApp()">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-black text-gray-800">Conciliação Financeira</h2>
        <div class="flex gap-2 flex-wrap">
            <button @click="showModalRegras = true" class="text-white px-4 py-2 rounded-xl text-sm font-bold shadow-md transition hover:opacity-90 flex items-center gap-2" style="background-color: #d97706;">
                <i class="fas fa-cog"></i>Regras Padrão
            </button>
            <button @click="showModalOfx = true" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-xl text-sm font-bold hover:bg-gray-50 transition shadow-sm">
                <i class="fas fa-file-upload mr-2"></i>Importar Extrato (OFX/CSV)
            </button>
            <button @click="showModalMp = true" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-indigo-700 transition shadow-md">
                <i class="fab fa-amazon-pay mr-2"></i>Sincronizar Mercado Pago
            </button>
            <button @click="showModalInter = true" class="text-white px-4 py-2 rounded-xl text-sm font-bold shadow-md transition hover:opacity-90 flex items-center gap-2" style="background-color: #FF6200;">
                <i class="fas fa-university"></i>Sincronizar Banco Inter
            </button>
        </div>
    </div>

    <!-- Alertas globais gerenciados pelo layout -->

    <div class="space-y-6 mb-24">
        @forelse($extratoComSugestoes as $item)
            @php
                $t = $item['transacao'];
                $sugestoes = $item['sugestoes'];
                $isEntrada = $t->tipo === 'entrada';
            @endphp
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition duration-200">
                {{-- Transaction Header Row --}}
                <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/70 border-b border-gray-100 rounded-t-2xl">
                    <div class="flex items-start gap-3.5">
                        {{-- Icon Indicator --}}
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 {{ $isEntrada ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                            <i class="fas {{ $isEntrada ? 'fa-arrow-down' : 'fa-arrow-up' }} text-sm"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-bold text-gray-400">{{ $t->data->format('d/m/Y') }}</span>
                                @if($t->origem === 'bancointer')
                                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full" style="background-color: #FFEADB; color: #D15200;">
                                        banco inter
                                    </span>
                                @else
                                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full {{ $t->origem === 'mercadopago' ? 'bg-blue-100 text-blue-800' : 'bg-gray-200 text-gray-700' }}">
                                        {{ $t->origem }}
                                    </span>
                                @endif
                            </div>
                            <h4 class="text-base font-bold text-gray-800 mt-0.5 leading-snug">{{ $t->descricao }}</h4>
                        </div>
                    </div>

                    {{-- Right Area: Value and Action Buttons --}}
                    <div class="flex items-center justify-between md:justify-end gap-6 border-t md:border-t-0 pt-3 md:pt-0 border-gray-100">
                        {{-- Transaction Value --}}
                        <div class="text-left md:text-right">
                            @if($t->valor_taxa > 0)
                                <div class="flex flex-col items-start md:items-end">
                                    <span class="text-[10px] text-gray-400 line-through" title="Valor Bruto">
                                        R$ {{ number_format($t->valor_bruto ?? $t->valor, 2, ',', '.') }}
                                    </span>
                                    <span class="text-[9px] text-red-500 font-bold" title="Taxa Mercado Pago">
                                        - R$ {{ number_format($t->valor_taxa, 2, ',', '.') }}
                                    </span>
                                    <span class="text-lg font-black {{ $isEntrada ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $isEntrada ? '+' : '-' }} R$ {{ number_format($t->valor_liquido ?? $t->valor, 2, ',', '.') }}
                                    </span>
                                </div>
                            @else
                                <span class="text-lg font-black {{ $isEntrada ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $isEntrada ? '+' : '-' }} R$ {{ number_format($t->valor, 2, ',', '.') }}
                                </span>
                            @endif
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center gap-1.5">
                            <button @click='openQuickCreate({{ $t->id }}, {{ json_encode($t->descricao) }}, {{ $t->valor }}, "{{ $t->tipo }}", "{{ $t->origem }}")' 
                                    class="bg-white border border-gray-200 text-indigo-600 hover:bg-indigo-50 p-2.5 rounded-xl transition shadow-sm text-xs font-bold flex items-center gap-1.5"
                                    title="Criar lançamento rápido e conciliar">
                                <i class="fas fa-plus"></i> <span class="hidden sm:inline">Criar Novo</span>
                            </button>
                            <form action="{{ route('financeiro.conciliacao.ignorar', $t->id) }}" method="POST" onsubmit="return confirm('Ignorar esta transação?')">
                                @csrf
                                <button type="submit" 
                                        class="bg-white border border-gray-200 text-red-500 hover:bg-red-50 p-2.5 rounded-xl transition shadow-sm text-xs font-bold flex items-center gap-1.5"
                                        title="Ignorar transação do extrato">
                                    <i class="fas fa-times text-sm"></i> <span class="hidden sm:inline">Ignorar</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Suggestions Section --}}
                <div class="p-5 space-y-4">
                    <div>
                        <h5 class="text-xs font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2 mb-3">
                            <i class="fas fa-magic text-indigo-500"></i> Sugestões do Sistema
                        </h5>
                        
                        @if($sugestoes->isEmpty())
                            <p class="text-xs text-gray-400 italic bg-gray-50/50 p-3 rounded-xl border border-dashed border-gray-200">
                                Nenhuma sugestão automática encontrada. Utilize o seletor manual abaixo para vincular a um lançamento existente.
                            </p>
                        @else
                            <div class="space-y-2.5">
                                @foreach($sugestoes as $s)
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 bg-indigo-50/10 rounded-xl border border-indigo-100/20 hover:bg-indigo-50/30 transition">
                                        <div class="space-y-1 flex-grow">
                                            {{-- Primeira Linha: Apenas o Lançamento (em cinza, com negrito) --}}
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-gray-400">{{ $s->descricao }}</span>
                                            </div>
                                            
                                            {{-- Segunda Linha: Detalhes (em preto, sem negrito), com destaque na classificação (em negrito) --}}
                                            <div class="text-[10px] text-gray-900 font-normal flex items-center flex-wrap gap-x-3 gap-y-1">
                                                <span class="text-gray-900"><i class="far fa-user mr-1 text-gray-500"></i>{{ $s->pessoa->nome ?? 'Sem Contato' }}</span>
                                                
                                                {{-- Classificação com Destaque (Negrito) --}}
                                                <span class="bg-indigo-50 text-indigo-700 text-[9px] font-bold px-2 py-0.5 rounded-md border border-indigo-100 flex items-center gap-1 shadow-sm">
                                                    <i class="far fa-folder text-indigo-500"></i>
                                                    {{ $s->classificacaoFinanceira->nome ?? 'Sem Categoria' }}
                                                </span>

                                                @if(empty($s->is_virtual))
                                                    <span class="text-gray-900"><i class="far fa-calendar-alt text-gray-500 mr-1"></i>Venc. {{ $s->data_vencimento->format('d/m/Y') }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between sm:justify-end gap-4 border-t sm:border-t-0 pt-2.5 sm:pt-0 border-indigo-100/40 flex-wrap sm:flex-nowrap">
                                            @if(!empty($s->is_virtual))
                                                <span class="text-sm font-black text-gray-700 sm:text-right">
                                                    R$ {{ number_format($t->valor, 2, ',', '.') }}
                                                </span>
                                                <div class="flex items-center gap-2">
                                                    @php
                                                        $isDefault = $item['regra_correspondente'] && $item['regra_correspondente']['classificacao_financeira_id'] == $s->classificacao_financeira_id && $item['regra_correspondente']['pessoa_id'] == $s->pessoa_id;
                                                    @endphp
                                                    @if($isDefault)
                                                        <span class="bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold px-2.5 py-1.5 rounded-xl flex items-center gap-1" title="Esta é a regra padrão configurada para esta descrição">
                                                            <i class="fas fa-star text-amber-500"></i> Padrão
                                                        </span>
                                                    @else
                                                        <form action="{{ route('financeiro.conciliacao.regras.salvar') }}" method="POST" class="inline">
                                                            @csrf
                                                            <input type="hidden" name="descricao_banco" value="{{ $t->descricao }}">
                                                            <input type="hidden" name="classificacao_financeira_id" value="{{ $s->classificacao_financeira_id }}">
                                                            <input type="hidden" name="pessoa_id" value="{{ $s->pessoa_id }}">
                                                            <button type="submit" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-600 text-xs font-bold px-2.5 py-1.5 rounded-xl transition shadow-sm flex items-center gap-1" title="Definir esta classificação e contato como regra padrão para esta descrição de extrato">
                                                                <i class="far fa-star text-gray-400"></i> Tornar Padrão
                                                            </button>
                                                        </form>
                                                    @endif

                                                    <form action="{{ route('financeiro.conciliacao.criar-rapido') }}" method="POST" onsubmit="const btn = this.querySelector('button[type=submit]'); btn.disabled=true; btn.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i>';">
                                                        @csrf
                                                        <input type="hidden" name="transacao_id" value="{{ $t->id }}">
                                                        <input type="hidden" name="classificacao_financeira_id" value="{{ $s->classificacao_financeira_id }}">
                                                        <input type="hidden" name="pessoa_id" value="{{ $s->pessoa_id }}">
                                                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black px-4 py-2 rounded-xl transition shadow-sm flex items-center gap-1.5">
                                                            <i class="fas fa-plus"></i> Criar e Conciliar
                                                        </button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="text-sm font-black text-gray-700 sm:text-right">
                                                    R$ {{ number_format($s->valor_total, 2, ',', '.') }}
                                                </span>
                                                <form action="{{ route('financeiro.conciliacao.vincular') }}" method="POST" onsubmit="const btn = this.querySelector('button[type=submit]'); btn.disabled=true; btn.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i>';">
                                                    @csrf
                                                    <input type="hidden" name="transacao_id" value="{{ $t->id }}">
                                                    <input type="hidden" name="lancamento_id" value="{{ $s->id }}">
                                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-xs font-black px-4 py-2 rounded-xl transition shadow-sm flex items-center gap-1.5">
                                                        <i class="fas fa-link"></i> Vincular
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Manual Selection Autocomplete Search --}}
                    <div class="pt-4 border-t border-gray-100 flex flex-col gap-4">
                        <div class="w-full" x-data="manualLancamentoSearch('{{ $isEntrada ? 'receita' : 'despesa' }}', {{ $t->valor_bruto ?? $t->valor }})">
                            <form action="{{ route('financeiro.conciliacao.vincular-multiplos') }}" method="POST" class="space-y-4" onsubmit="const btn = this.querySelector('button[type=submit]'); btn.disabled=true; btn.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Processando...';">
                                @csrf
                                <input type="hidden" name="transacao_id" value="{{ $t->id }}">
                                
                                <div class="relative w-full md:max-w-2xl">
                                    <input type="text" 
                                           x-model="search" 
                                           @input.debounce.300ms="buscar()"
                                           @focus="buscar()"
                                           placeholder="Buscar lançamentos para vincular..."
                                           class="w-full text-xs border border-gray-200 rounded-xl p-2.5 bg-white font-bold text-gray-700 transition-all focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                                    
                                    <div x-show="loading" class="absolute right-3 top-3">
                                        <i class="fas fa-spinner fa-spin text-gray-400 text-xs"></i>
                                    </div>
                                    
                                    <div x-show="results.length > 0" 
                                         class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-auto">
                                        <template x-for="item in results" :key="item.id">
                                            <div @click="selecionar(item)" 
                                                 class="p-3 hover:bg-indigo-50 cursor-pointer border-b border-gray-100 last:border-0">
                                                <div class="font-bold text-xs text-gray-800" x-text="item.text"></div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                
                                {{-- Lista de Vínculos --}}
                                <template x-if="vinculos.length > 0">
                                    <div class="space-y-2 max-w-3xl">
                                        <h6 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Lançamentos Selecionados para Vínculo</h6>
                                        <div class="bg-gray-50/50 border border-gray-100 rounded-2xl p-3 space-y-2">
                                            <template x-for="(v, index) in vinculos" :key="v.id">
                                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 bg-white rounded-xl border border-gray-200/60 shadow-sm">
                                                    <div class="flex-grow">
                                                        <span class="text-xs font-bold text-gray-700" x-text="v.text"></span>
                                                    </div>
                                                    <div class="flex items-center gap-2 flex-shrink-0">
                                                        <div class="text-[10px] text-gray-400 font-bold mr-1">R$</div>
                                                        <input type="number" 
                                                               step="0.01" 
                                                               x-model="v.valor_vinculo" 
                                                               class="w-24 text-xs border border-gray-200 rounded-lg p-1.5 font-black text-right text-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-400"
                                                               required>
                                                        
                                                        {{-- Hidden Inputs para submissão do array no Form --}}
                                                        <input type="hidden" :name="'vinculos['+index+'][lancamento_id]'" :value="v.id">
                                                        <input type="hidden" :name="'vinculos['+index+'][valor_vinculo]'" :value="v.valor_vinculo">
                                                        
                                                        <button type="button" @click="remover(index)" class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-700 transition flex items-center justify-center">
                                                            <i class="fas fa-trash text-xs"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>
                                            
                                            {{-- Resumo financeiro do vínculo --}}
                                            <div class="pt-3 mt-2 border-t border-dashed border-gray-200/80 flex flex-wrap items-center justify-between gap-4 text-xs font-bold">
                                                <div class="space-x-4">
                                                    <span class="text-gray-500">Valor da Transação: <span class="text-gray-800" x-text="'R$ ' + valorTransacao.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span></span>
                                                    <span class="text-gray-500">Total Vinculado: <span class="text-indigo-600" x-text="'R$ ' + totalVinculado.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span></span>
                                                </div>
                                                <div>
                                                    <span :class="valorRestante < -0.01 ? 'text-red-600' : (Math.abs(valorRestante) < 0.01 ? 'text-green-600' : 'text-gray-500')">
                                                        <span x-text="valorRestante < -0.01 ? 'Valor Excedido por: R$ ' + Math.abs(valorRestante).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : 'Restante: R$ ' + Math.abs(valorRestante).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                
                                <div class="flex items-center gap-2">
                                    <button type="submit" 
                                            class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-xs font-black px-4 py-2.5 rounded-xl transition shadow-sm flex items-center justify-center gap-1.5" 
                                            :disabled="!podeVincular">
                                        <i class="fas fa-link"></i> Reconciliar com Lançamentos Selecionados
                                    </button>
                                    <template x-if="vinculos.length > 0">
                                        <button type="button" @click="limpar()" class="px-4 py-2.5 border border-gray-200 rounded-xl text-gray-500 hover:bg-gray-50 text-xs font-bold transition">
                                            Limpar Seleção
                                        </button>
                                    </template>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-12 text-center max-w-lg mx-auto">
                <div class="w-16 h-16 rounded-2xl bg-green-50 text-green-500 flex items-center justify-center mx-auto mb-4 border border-green-100 shadow-sm">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
                <h3 class="text-lg font-black text-gray-800">Tudo Conciliado!</h3>
                <p class="text-sm text-gray-400 mt-1.5 leading-relaxed">Nenhuma transação de extrato pendente para conciliação. Excelente trabalho!</p>
            </div>
        @endforelse
    </div>

    <!-- Modal Regras de Conciliação Padrão -->
    <div x-show="showModalRegras" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50" x-cloak>
        <div class="bg-white rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50 flex-shrink-0">
                <h3 class="font-black text-gray-800 flex items-center gap-2">
                    <i class="fas fa-cog text-amber-500"></i> Regras de Conciliação Padrão
                </h3>
                <button type="button" @click="showModalRegras = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="p-6 overflow-y-auto space-y-6 flex-grow">
                <p class="text-xs text-gray-500 leading-relaxed font-semibold">
                    Aqui você pode gerenciar os padrões de conciliação. Quando uma transação do extrato contiver o termo pesquisado na descrição, o sistema sugerirá automaticamente o Contato e a Classificação configurada no topo da lista.
                </p>

                <!-- Nova Regra Form -->
                <form action="{{ route('financeiro.conciliacao.regras.salvar') }}" method="POST" class="bg-gray-50 p-4 rounded-2xl border border-gray-150 space-y-3">
                    @csrf
                    <h4 class="text-xs font-black text-gray-700 uppercase tracking-wider">Adicionar Nova Regra</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Descrição Procurada (banco)</label>
                            <input type="text" name="descricao_banco" placeholder="Ex: VINDI PAGAMENTOS" class="w-full text-xs border border-gray-200 rounded-lg p-2 bg-white" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Contato Padrão</label>
                            <select name="pessoa_id" class="w-full text-xs border border-gray-200 rounded-lg p-2 bg-white" required>
                                <option value="">Selecione...</option>
                                @foreach($pessoas as $p)
                                    <option value="{{ $p->id }}">{{ $p->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Classificação Padrão</label>
                            <select name="classificacao_financeira_id" class="w-full text-xs border border-gray-200 rounded-lg p-2 bg-white" required>
                                <option value="">Selecione...</option>
                                @foreach($classificacoes as $c)
                                    <option value="{{ $c->id }}">{{ $c->codigo }} - {{ $c->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2 rounded-xl transition shadow-sm">
                            <i class="fas fa-plus mr-1"></i> Salvar Regra
                        </button>
                    </div>
                </form>

                <!-- Listagem das Regras -->
                <div class="space-y-2">
                    <h4 class="text-xs font-black text-gray-700 uppercase tracking-wider">Regras Ativas</h4>
                    
                    @php
                        $regrasAtivas = json_decode(\DB::table('configuracoes')->where('chave', 'regras_conciliacao')->value('valor'), true) ?: [];
                    @endphp

                    @if(empty($regrasAtivas))
                        <p class="text-xs text-gray-400 italic bg-gray-50 p-4 rounded-2xl text-center">Nenhuma regra cadastrada ainda. Use o formulário acima ou clique em "Tornar Padrão" nas sugestões automáticas do extrato.</p>
                    @else
                        <div class="border border-gray-100 rounded-2xl overflow-hidden divide-y divide-gray-100">
                            @foreach($regrasAtivas as $regra)
                                @php
                                    $pModel = \App\Models\Pessoa::find($regra['pessoa_id']);
                                    $cModel = \App\Models\ClassificacaoFinanceira::find($regra['classificacao_financeira_id']);
                                @endphp
                                <div class="p-3 bg-white hover:bg-gray-50 flex items-center justify-between gap-4 transition">
                                    <div class="space-y-1">
                                        <div class="text-xs font-bold text-gray-805">
                                            Termo: <span class="bg-gray-100 text-gray-700 font-mono px-1.5 py-0.5 rounded border border-gray-200 text-[10px]">"{{ $regra['descricao_banco'] }}"</span>
                                        </div>
                                        <div class="text-[10px] text-gray-500 flex items-center gap-3">
                                            <span><i class="far fa-user mr-1 text-gray-400"></i>{{ $pModel->nome ?? 'Desconhecido' }}</span>
                                            <span class="bg-indigo-50 text-indigo-700 font-semibold px-2 py-0.5 rounded-md border border-indigo-100 flex items-center gap-1">
                                                <i class="far fa-folder text-indigo-500"></i>
                                                {{ $cModel->nome ?? 'Sem Categoria' }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <form action="{{ route('financeiro.conciliacao.regras.excluir', $regra['id']) }}" method="POST" onsubmit="return confirm('Deseja realmente excluir esta regra padrão?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 p-2 hover:bg-red-55 rounded-lg transition" title="Excluir Regra">
                                            <i class="far fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 flex justify-end flex-shrink-0">
                <button type="button" @click="showModalRegras = false" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-5 py-2 rounded-xl text-sm font-bold transition">Fechar</button>
            </div>
        </div>
    </div>

    <!-- Modal OFX -->
    <div x-show="showModalOfx" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50" x-cloak>
        <form action="{{ route('financeiro.conciliacao.importar') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl" onsubmit="const btn = this.querySelector('button[type=submit]'); btn.disabled=true; btn.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Processando...';">
            @csrf
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-black text-gray-800">Importar Extrato Bancário</h3>
                <button type="button" @click="showModalOfx = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Arquivo (.ofx ou .csv do Mercado Pago)</label>
                    <input type="file" name="arquivo_ofx" class="w-full text-sm border border-gray-200 rounded-xl p-2" accept=".ofx,.csv" required>
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
        <form action="{{ route('financeiro.conciliacao.sincronizar-mp') }}" method="POST" class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl" onsubmit="const btn = this.querySelector('button[type=submit]'); btn.disabled=true; btn.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Processando...';">
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

            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2">
                <button type="button" @click="showModalMp = false" class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-700">Cancelar</button>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-xl text-sm font-black hover:bg-indigo-700 shadow-md">Sincronizar Agora</button>
            </div>
        </form>
    </div>

    <!-- Modal Banco Inter -->
    <div x-show="showModalInter" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50" x-cloak>
        <form action="{{ route('financeiro.conciliacao.sincronizar-inter') }}" method="POST" class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl" onsubmit="const btn = this.querySelector('button[type=submit]'); btn.disabled=true; btn.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Processando...';">
            @csrf
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-black text-gray-800">Sincronizar Banco Inter</h3>
                <button type="button" @click="showModalInter = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
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
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2">
                <button type="button" @click="showModalInter = false" class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-700">Cancelar</button>
                <button type="submit" class="text-white px-6 py-2 rounded-xl text-sm font-black shadow-md transition hover:opacity-90" style="background-color: #FF6200;">Sincronizar Agora</button>
            </div>
        </form>
    </div>

    <!-- Modal Quick Create -->
    <div x-show="showModalQuick" 
         x-effect="if (showModalQuick) { $nextTick(() => { document.getElementById('quick-classificacao-input')?.focus(); }); }"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50" x-cloak>
        <form action="{{ route('financeiro.conciliacao.criar-rapido') }}" method="POST" class="bg-white rounded-3xl w-full max-w-lg overflow-visible shadow-2xl" onsubmit="const btn = this.querySelector('button[type=submit]'); btn.disabled=true; btn.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Processando...';">
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
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Conta Bancária <span class="text-red-500">*</span></label>
                    <select name="conta_bancaria_id" x-model="quickData.conta_id" class="w-full text-sm border border-gray-200 rounded-xl p-2 bg-white font-bold" required>
                        <option value="">Selecione a conta de destino...</option>
                        @foreach($contas as $conta)
                            <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-data="classificacaoSearch()">
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Classificação Financeira <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" id="quick-classificacao-input" x-model="search" @input.debounce.300ms="buscar()" @focus="buscar()"
                               @keydown.down="nextItem"
                               @keydown.up="prevItem"
                               @keydown.enter="selectHighlighted"
                               placeholder="Buscar categoria (Ex: Venda, Aluguel...)"
                               class="w-full text-sm border border-gray-200 rounded-xl p-2 bg-white font-bold transition-all"
                               :class="selected ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : ''" 
                               :readonly="selected"
                               required>
                        <input type="hidden" name="classificacao_financeira_id" :value="selectedId" required>
                        
                        <div x-show="loading" class="absolute right-9 top-3"><i class="fas fa-spinner fa-spin text-gray-400"></i></div>
                        
                        <div x-show="results.length > 0" 
                             class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-auto">
                            <template x-for="(item, index) in results" :key="item.id">
                                <div @click="selecionar(item)" 
                                     class="p-3 cursor-pointer border-b border-gray-100 last:border-0"
                                     :class="highlightedIndex === index ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'hover:bg-indigo-50'"
                                >
                                    <div class="font-bold text-sm" :class="highlightedIndex === index ? 'text-white' : 'text-gray-800'" x-text="item.text"></div>
                                </div>
                            </template>
                        </div>
                        <div x-show="selected" @click="limpar()" class="absolute right-3 top-3 cursor-pointer text-gray-400 hover:text-red-500">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>
                <div x-data="pessoaSearch()">
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Pessoa (Opcional)</label>
                    <div class="relative">
                        <input type="text" 
                               id="quick-pessoa-input"
                               x-model="search" 
                               @input.debounce.300ms="buscar()"
                               @focus="buscar()"
                               @keydown.down="nextItem"
                               @keydown.up="prevItem"
                               @keydown.enter="selectHighlighted"
                               placeholder="Buscar por nome, CPF ou email..."
                               class="w-full text-sm border border-gray-200 rounded-xl p-2 bg-white font-bold transition-all"
                               :class="selected ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : ''"
                               :readonly="selected">
                        <input type="hidden" name="pessoa_id" :value="selectedId">
                        <div x-show="loading" class="absolute right-9 top-3">
                            <i class="fas fa-spinner fa-spin text-gray-400"></i>
                        </div>
                        <div x-show="results.length > 0" 
                             class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-auto">
                            <template x-for="(pessoa, index) in results" :key="pessoa.id">
                                <div @click="selecionar(pessoa)" 
                                     class="p-3 cursor-pointer border-b border-gray-100 last:border-0"
                                     :class="highlightedIndex === index ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'hover:bg-indigo-50'"
                                >
                                    <div class="font-bold text-sm" :class="highlightedIndex === index ? 'text-white' : 'text-gray-800'" x-text="pessoa.nome"></div>
                                    <div class="text-xs" :class="highlightedIndex === index ? 'text-indigo-200' : 'text-gray-500'" x-text="pessoa.info || ''"></div>
                                </div>
                            </template>
                        </div>
                        <div x-show="selected" @click="limpar()" class="absolute right-3 top-3 cursor-pointer text-gray-400 hover:text-red-500">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2">
                <button type="button" @click="showModalQuick = false" class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-700">Cancelar</button>
                <button type="submit" id="quick-submit-button" class="bg-green-600 text-white px-8 py-2 rounded-xl text-sm font-black hover:bg-green-700 shadow-md">Salvar e Conciliar</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function conciliacaoApp() {
        return {
            quickData: { id: '', descricao: '', valor: '', tipo: '', conta_id: 1 },
            showModalOfx: false,
            showModalRegras: false,
            showModalMp: false,
            showModalInter: false,
            showModalQuick: false,

            openQuickCreate(id, desc, valor, tipo, origem) {
                let defaultConta = 1;
                if (origem === 'mercadopago') {
                    defaultConta = 2;
                } else if (origem === 'bancointer') {
                    @php
                        $contaInterId = \App\Models\ContaBancaria::where('nome', 'like', '%Inter%')->first()?->id ?? 1;
                    @endphp
                    defaultConta = {{ $contaInterId }};
                }

                this.quickData = { 
                    id, 
                    descricao: desc, 
                    valor: this.formatMoney(valor), 
                    tipo: tipo === 'entrada' ? 'RECEITA' : 'DESPESA',
                    conta_id: defaultConta
                };
                this.suggestion = null;
                this.showModalQuick = true;

                // Buscar sugestão de pessoa (se existir pedido vinculado)
                fetch(`{{ url('admin/financeiro/conciliacao/get-sugestao-pessoa') }}/${id}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.suggestion = data.pessoa;
                            // Disparar evento para o componente de busca de pessoa
                            window.dispatchEvent(new CustomEvent('pessoa-sugestionada', { detail: data.pessoa }));
                        }
                    });
            },

            formatMoney(v) {
                if (!v) return '0,00';
                return v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }
    }

    function pessoaSearch() {
        return {
            search: '',
            results: [],
            selected: null,
            selectedId: '',
            selectedNome: '',
            loading: false,
            timeout: null,
            highlightedIndex: 0,
            init() {
                window.addEventListener('pessoa-sugestionada', (e) => {
                    this.selecionar(e.detail);
                });
            },
            buscar() {
                // Se o que está no campo é exatamente o que foi selecionado, não busca
                if (this.selected && this.search === this.selectedNome) {
                    this.results = [];
                    return;
                }

                if (this.search.length < 2) {
                    this.results = [];
                    return;
                }
                
                this.loading = true;
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => {
                    fetch('{{ route("financeiro.conciliacao.buscar-pessoas") }}?q=' + encodeURIComponent(this.search))
                        .then(r => r.json())
                        .then(data => {
                            this.results = data.map(p => ({
                                id: p.id,
                                nome: p.nome,
                                info: p.documento || p.cpf || ''
                            }));
                            this.highlightedIndex = 0;
                            this.loading = false;
                        });
                }, 300);
            },
            selecionar(pessoa) {
                this.selected = pessoa;
                this.selectedId = pessoa.id;
                this.selectedNome = pessoa.nome || pessoa.text;
                this.search = this.selectedNome; // Define o texto no input
                this.results = [];
                setTimeout(() => {
                    document.getElementById('quick-submit-button')?.focus();
                }, 50);
            },
            limpar() {
                this.selected = null;
                this.selectedId = '';
                this.selectedNome = '';
                this.search = '';
                this.results = [];
                this.highlightedIndex = 0;
            },
            nextItem(e) {
                if (this.results.length === 0) return;
                e.preventDefault();
                this.highlightedIndex = (this.highlightedIndex + 1) % this.results.length;
            },
            prevItem(e) {
                if (this.results.length === 0) return;
                e.preventDefault();
                this.highlightedIndex = (this.highlightedIndex - 1 + this.results.length) % this.results.length;
            },
            selectHighlighted(e) {
                if (this.results.length > 0) {
                    e.preventDefault();
                    if (this.results[this.highlightedIndex]) {
                        this.selecionar(this.results[this.highlightedIndex]);
                    }
                }
            }
        }
    }

    function classificacaoSearch() {
        return {
            search: '',
            results: [],
            selected: null,
            selectedId: '',
            selectedNome: '',
            loading: false,
            highlightedIndex: 0,
            buscar() {
                // Se o que está no campo é exatamente o que foi selecionado, não busca
                if (this.selected && this.search === this.selectedNome) {
                    this.results = [];
                    return;
                }

                if (this.search.length < 2) {
                    this.results = [];
                    return;
                }
                
                this.loading = true;
                fetch('{{ route("financeiro.search.classificacoes") }}?q=' + encodeURIComponent(this.search))
                    .then(r => r.json())
                    .then(data => {
                        this.results = data;
                        this.highlightedIndex = 0;
                        this.loading = false;
                    });
            },
            selecionar(item) {
                this.selected = item;
                this.selectedId = item.id;
                this.selectedNome = item.text;
                this.search = this.selectedNome; // Define o texto no input
                this.results = [];
                setTimeout(() => {
                    document.getElementById('quick-pessoa-input')?.focus();
                }, 50);
            },
            limpar() {
                this.selected = null;
                this.selectedId = '';
                this.selectedNome = '';
                this.search = '';
                this.results = [];
                this.highlightedIndex = 0;
            },
            nextItem(e) {
                if (this.results.length === 0) return;
                e.preventDefault();
                this.highlightedIndex = (this.highlightedIndex + 1) % this.results.length;
            },
            prevItem(e) {
                if (this.results.length === 0) return;
                e.preventDefault();
                this.highlightedIndex = (this.highlightedIndex - 1 + this.results.length) % this.results.length;
            },
            selectHighlighted(e) {
                if (this.results.length > 0) {
                    e.preventDefault();
                    if (this.results[this.highlightedIndex]) {
                        this.selecionar(this.results[this.highlightedIndex]);
                    }
                }
            }
        }
    }

    function manualLancamentoSearch(tipo, valorTransacao) {
        return {
            search: '',
            results: [],
            vinculos: [],
            loading: false,
            timeout: null,
            valorTransacao: parseFloat(valorTransacao),
            
            get totalVinculado() {
                return this.vinculos.reduce((sum, v) => sum + (parseFloat(v.valor_vinculo) || 0), 0);
            },
            
            get valorRestante() {
                return this.valorTransacao - this.totalVinculado;
            },
            
            get podeVincular() {
                return this.vinculos.length > 0 && this.valorRestante >= -0.01;
            },
            
            buscar() {
                if (!this.search || this.search.trim().length === 0) {
                    this.results = [];
                    return;
                }
                this.loading = true;
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => {
                    fetch('{{ route("financeiro.conciliacao.buscar-lancamentos") }}?tipo=' + tipo + '&q=' + encodeURIComponent(this.search))
                        .then(r => r.json())
                        .then(data => {
                            this.results = data;
                            this.loading = false;
                        })
                        .catch(() => {
                            this.loading = false;
                        });
                }, 300);
            },
            
            selecionar(item) {
                // Evita duplicados
                if (this.vinculos.some(v => v.id === item.id)) {
                    this.search = '';
                    this.results = [];
                    return;
                }
                
                // Calcula o valor sugerido para o vínculo (mínimo entre o saldo restante do lançamento e o valor que falta alocar da transação)
                const sugerido = Math.min(parseFloat(item.saldo_restante), Math.max(0, this.valorRestante));
                
                this.vinculos.push({
                    id: item.id,
                    text: item.text,
                    saldo_restante: parseFloat(item.saldo_restante),
                    valor_vinculo: sugerido.toFixed(2)
                });
                
                this.search = '';
                this.results = [];
            },
            
            remover(index) {
                this.vinculos.splice(index, 1);
            },
            
            limpar() {
                this.vinculos = [];
                this.search = '';
                this.results = [];
            }
        }
    }
</script>
@endpush

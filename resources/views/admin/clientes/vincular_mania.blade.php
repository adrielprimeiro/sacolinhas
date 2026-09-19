@extends('layouts.app')
@section('title', 'Vincular Clientes da Mania')
@section('brand_route', 'admin.clientes.index')
@section('brand_icon', 'fas fa-magic')

@section('content')
<div class="max-w-7xl mx-auto pb-12">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('admin.clientes.index') }}" class="hover:text-indigo-600 transition">Clientes</a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-gray-700 font-medium">Vincular com a Mania</span>
            </div>
            <h1 class="text-2xl font-black text-gray-800 flex items-center gap-2">
                <i class="fas fa-magic text-pink-500"></i>
                Vincular Clientes da Minha Mania
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Associe os clientes cadastrados rapidamente no <strong class="text-gray-700">{{ $brecho->nome }}</strong> aos cadastros completos da Mania.
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if(!$isParceiro && isset($todosBrechos) && $todosBrechos->count() > 1)
                <form method="GET" action="{{ route('admin.clientes.vincular_mania') }}" class="flex items-center gap-2">
                    <label class="text-xs font-bold text-gray-500 uppercase">Brechó:</label>
                    <select name="brecho_id" onchange="this.form.submit()" class="text-sm font-semibold border-gray-200 rounded-xl px-3 py-2 bg-white shadow-xs focus:ring-pink-500 focus:border-pink-500">
                        @foreach($todosBrechos as $b)
                            @if($b->id != 1)
                                <option value="{{ $b->id }}" {{ $brechoId == $b->id ? 'selected' : '' }}>{{ $b->nome }}</option>
                            @endif
                        @endforeach
                    </select>
                </form>
            @endif

            <a href="{{ route('admin.clientes.index') }}" 
               class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2.5 rounded-xl transition">
                <i class="fas fa-arrow-left"></i> Voltar para Clientes
            </a>
        </div>
    </div>

    {{-- Explicação Rápida --}}
    <div class="bg-gradient-to-r from-pink-50 via-rose-50 to-indigo-50 border border-pink-100 rounded-2xl p-5 mb-8 shadow-xs">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-pink-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                <i class="fas fa-info-circle text-lg"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-800">Como funciona a transferência e vinculação?</h2>
                <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                    Quando clientes da Mania compram numa live do <strong>{{ $brecho->nome }}</strong> e foram anotados apenas pelo nome, o sistema cria um registro provisório. 
                    Ao clicar em <strong>"Transferir e Vincular"</strong>, o sistema vincula o cadastro oficial da cliente (com WhatsApp, endereço e CPF) ao seu brechó e move automaticamente todas as sacolinhas da live para ela, limpando a duplicata.
                </p>
            </div>
        </div>
    </div>

    {{-- Abas / Seções --}}
    <div x-data="{ activeTab: 'pendentes' }" class="space-y-6">
        <div class="flex border-b border-gray-200">
            <button @click="activeTab = 'pendentes'" 
                    :class="activeTab === 'pendentes' ? 'border-pink-500 text-pink-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 font-medium'"
                    class="py-3 px-6 border-b-2 text-sm flex items-center gap-2 transition">
                <i class="fas fa-user-clock"></i>
                Cadastros do Brechó (Live/Rápido)
                <span class="bg-pink-100 text-pink-700 text-xs px-2 py-0.5 rounded-full font-bold">
                    {{ $clientesComSugestoes->count() }}
                </span>
            </button>

            <button @click="activeTab = 'importar'" 
                    :class="activeTab === 'importar' ? 'border-pink-500 text-pink-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 font-medium'"
                    class="py-3 px-6 border-b-2 text-sm flex items-center gap-2 transition">
                <i class="fas fa-search-plus"></i>
                Importar Novo Cliente da Mania
            </button>
        </div>

        {{-- ABA 1: Clientes Pendentes do Brechó --}}
        <div x-show="activeTab === 'pendentes'" class="space-y-4">
            @if($clientesComSugestoes->isEmpty())
                <div class="bg-white rounded-2xl p-12 text-center border border-gray-100 shadow-sm">
                    <div class="w-16 h-16 bg-green-50 text-green-500 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-800">Tudo em ordem!</h3>
                    <p class="text-sm text-gray-500 mt-1">Nenhum cliente cadastrado exclusivamente neste brechó no momento.</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4">
                    @foreach($clientesComSugestoes as $cliente)
                        <div x-data="clienteCardHandler({
                            dummyId: {{ $cliente->id }},
                            dummyName: '{{ addslashes($cliente->name) }}',
                            brechoId: {{ $brechoId }},
                            sugestoesIniciais: @js($cliente->sugestoes)
                        })" 
                        :id="'card-cliente-' + dummyId"
                        class="bg-white rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md transition p-5">
                            
                            <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-6">
                                {{-- LADO ESQUERDO: Dados Provisórios do Brechó --}}
                                <div class="lg:w-1/3 border-b lg:border-b-0 lg:border-r border-gray-100 pb-4 lg:pb-0 lg:pr-6">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs font-bold rounded-md">ID {{ $cliente->id }}</span>
                                        @if($cliente->sacolinhas_brecho_count > 0)
                                            <span class="px-2 py-0.5 bg-pink-100 text-pink-700 text-xs font-bold rounded-md flex items-center gap-1">
                                                <i class="fas fa-shopping-bag text-xs"></i>
                                                {{ $cliente->sacolinhas_brecho_count }} peça{{ $cliente->sacolinhas_brecho_count > 1 ? 's' : '' }} na sacola
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 bg-gray-50 text-gray-400 text-xs font-medium rounded-md">0 sacolas</span>
                                        @endif

                                        @if($cliente->is_incompleto)
                                            <span class="px-2 py-0.5 bg-amber-50 text-amber-600 text-xs font-bold rounded-md" title="Sem WhatsApp ou cadastrado apenas com nome">
                                                Cadastro Rápido
                                            </span>
                                        @endif
                                    </div>

                                    <h3 class="text-lg font-black text-gray-900">{{ $cliente->name }}</h3>
                                    
                                    <div class="mt-2 space-y-1 text-xs text-gray-500">
                                        <p class="flex items-center gap-1.5">
                                            <i class="far fa-envelope text-gray-400 w-4"></i>
                                            <span class="truncate">{{ $cliente->email }}</span>
                                        </p>
                                        <p class="flex items-center gap-1.5">
                                            <i class="fab fa-whatsapp text-gray-400 w-4"></i>
                                            <span>{{ $cliente->whatsapp ?: 'Não informado' }}</span>
                                        </p>
                                        @if($cliente->instagram)
                                            <p class="flex items-center gap-1.5">
                                                <i class="fab fa-instagram text-pink-400 w-4"></i>
                                                <span>{{ '@' . ltrim($cliente->instagram, '@') }}</span>
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                {{-- LADO DIREITO: Sugestões e Busca na Mania --}}
                                <div class="lg:w-2/3 flex-1">
                                    <div class="flex items-center justify-between gap-2 mb-3">
                                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400">
                                            Correspondente na Minha Mania:
                                        </span>
                                        <div class="relative w-64">
                                            <input type="text" 
                                                   x-model="searchTerm"
                                                   @input.debounce.300ms="searchMania()"
                                                   placeholder="Digitar outro nome/tel..." 
                                                   class="w-full text-xs rounded-xl border-gray-200 pl-8 pr-3 py-1.5 focus:ring-pink-500 focus:border-pink-500">
                                            <i class="fas fa-search absolute left-2.5 top-2 text-gray-400 text-xs"></i>
                                        </div>
                                    </div>

                                    {{-- Loading --}}
                                    <div x-show="loading" class="py-4 text-center text-gray-400 text-xs">
                                        <i class="fas fa-spinner fa-spin mr-1"></i> Procurando na Mania...
                                    </div>

                                    {{-- Lista de Opções da Mania --}}
                                    <div x-show="!loading" class="space-y-2">
                                        <template x-if="candidates.length === 0">
                                            <div class="bg-gray-50 rounded-xl p-4 text-center text-xs text-gray-500">
                                                Nenhum cliente encontrado na base da Mania com este termo. Use a barra de busca acima para tentar outro nome ou WhatsApp.
                                            </div>
                                        </template>

                                        <template x-for="cand in candidates" :key="cand.id">
                                            <label :class="selectedCandidateId === cand.id ? 'border-pink-500 bg-pink-50/50 ring-2 ring-pink-500/20' : 'border-gray-200 hover:border-pink-200 bg-white'"
                                                   class="flex items-center justify-between p-3 rounded-xl border cursor-pointer transition">
                                                <div class="flex items-center gap-3">
                                                    <input type="radio" 
                                                           :name="'cand_' + dummyId" 
                                                           :value="cand.id" 
                                                           x-model="selectedCandidateId"
                                                           class="text-pink-600 focus:ring-pink-500 h-4 w-4">
                                                    <div>
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-sm font-bold text-gray-800" x-text="cand.name"></span>
                                                            <template x-if="cand.apelido">
                                                                <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded" x-text="'(' + cand.apelido + ')'"></span>
                                                            </template>
                                                        </div>
                                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-xs text-gray-500">
                                                            <span x-show="cand.whatsapp" class="flex items-center gap-1 text-green-700 font-medium">
                                                                <i class="fab fa-whatsapp"></i> <span x-text="cand.whatsapp"></span>
                                                            </span>
                                                            <span x-show="cand.cidade" class="flex items-center gap-1">
                                                                <i class="fas fa-map-marker-alt text-gray-400"></i> 
                                                                <span x-text="cand.cidade + (cand.estado ? '-' + cand.estado : '')"></span>
                                                            </span>
                                                            <span x-show="cand.instagram" class="flex items-center gap-1 text-pink-600">
                                                                <i class="fab fa-instagram"></i> <span x-text="'@' + cand.instagram.replace('@', '')"></span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="text-right shrink-0">
                                                    <span class="text-xs font-mono font-bold text-gray-400" x-text="'ID ' + cand.id"></span>
                                                </div>
                                            </label>
                                        </template>
                                    </div>

                                    {{-- Botão de Ação --}}
                                    <div class="mt-4 flex items-center justify-between gap-4 pt-3 border-t border-gray-100">
                                        <span class="text-xs text-gray-400" x-text="statusMsg"></span>

                                        <button type="button" 
                                                @click="transferir()"
                                                :disabled="!selectedCandidateId || executing"
                                                :class="selectedCandidateId && !executing ? 'bg-gradient-to-r from-pink-500 to-rose-600 hover:from-pink-600 hover:to-rose-700 text-white shadow-sm' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                                                class="inline-flex items-center gap-2 text-xs font-bold px-4 py-2 rounded-xl transition">
                                            <template x-if="executing">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </template>
                                            <template x-if="!executing">
                                                <i class="fas fa-check"></i>
                                            </template>
                                            Transferir Dados e Vincular
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ABA 2: Importar Novo Cliente da Mania --}}
        <div x-show="activeTab === 'importar'" x-data="importadorManiaHandler({{ $brechoId }})" class="space-y-4">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h3 class="text-base font-black text-gray-800 mb-1">Buscar Cliente na Base Minha Mania</h3>
                <p class="text-xs text-gray-500 mb-4">
                    Encontre qualquer cliente já cadastrada na Mania e vincule-a imediatamente ao <strong>{{ $brecho->nome }}</strong>, sem precisar digitar tudo de novo.
                </p>

                <div class="relative max-w-xl">
                    <input type="text" 
                           x-model="buscaGeral" 
                           @input.debounce.300ms="fazerBuscaGeral()"
                           placeholder="Digite Nome, @Instagram, WhatsApp, CPF ou Cidade..." 
                           class="w-full rounded-xl border-gray-200 pl-10 pr-4 py-2.5 text-sm focus:ring-pink-500 focus:border-pink-500">
                    <i class="fas fa-search absolute left-3.5 top-3.5 text-gray-400 text-sm"></i>
                </div>

                {{-- Loading --}}
                <div x-show="carregando" class="py-8 text-center text-gray-400 text-sm">
                    <i class="fas fa-spinner fa-spin mr-1"></i> Buscando na base da Mania...
                </div>

                {{-- Resultados da Busca Geral --}}
                <div x-show="!carregando" class="mt-6 space-y-3">
                    <template x-if="resultados.length === 0 && buscaGeral.length >= 2">
                        <div class="text-center py-6 text-xs text-gray-400">
                            Nenhuma cliente encontrada com o termo pesquisado.
                        </div>
                    </template>

                    <template x-for="item in resultados" :key="item.id">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl border border-gray-200/80 bg-white hover:border-pink-200 transition">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-gray-800" x-text="item.name"></span>
                                    <span class="text-xs text-gray-400 font-mono" x-text="'(ID ' + item.id + ')'"></span>
                                    <template x-if="item.ja_vinculado">
                                        <span class="text-xs font-bold bg-green-100 text-green-700 px-2 py-0.5 rounded-full">
                                            Já vinculado
                                        </span>
                                    </template>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-xs text-gray-500">
                                    <span x-show="item.whatsapp" class="flex items-center gap-1 text-green-700">
                                        <i class="fab fa-whatsapp"></i> <span x-text="item.whatsapp"></span>
                                    </span>
                                    <span x-show="item.cidade" class="flex items-center gap-1">
                                        <i class="fas fa-map-marker-alt text-gray-400"></i> <span x-text="item.cidade + (item.estado ? '-' + item.estado : '')"></span>
                                    </span>
                                    <span x-show="item.instagram" class="flex items-center gap-1 text-pink-600">
                                        <i class="fab fa-instagram"></i> <span x-text="'@' + item.instagram.replace('@', '')"></span>
                                    </span>
                                </div>
                            </div>

                            <button type="button" 
                                    @click="vincularNovo(item)"
                                    :disabled="item.ja_vinculado || item.vinculando"
                                    :class="item.ja_vinculado ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-pink-600 hover:bg-pink-700 text-white shadow-xs'"
                                    class="inline-flex items-center gap-2 text-xs font-bold px-4 py-2 rounded-xl transition shrink-0">
                                <template x-if="item.vinculando">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </template>
                                <template x-if="!item.vinculando && !item.ja_vinculado">
                                    <i class="fas fa-link"></i>
                                </template>
                                <template x-if="item.ja_vinculado">
                                    <i class="fas fa-check"></i>
                                </template>
                                <span x-text="item.ja_vinculado ? 'Vinculado' : 'Vincular ao Brechó'"></span>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function clienteCardHandler(config) {
    return {
        dummyId: config.dummyId,
        dummyName: config.dummyName,
        brechoId: config.brechoId,
        searchTerm: '',
        candidates: config.sugestoesIniciais || [],
        selectedCandidateId: (config.sugestoesIniciais && config.sugestoesIniciais.length > 0) ? config.sugestoesIniciais[0].id : null,
        loading: false,
        executing: false,
        statusMsg: '',

        searchMania() {
            if (this.searchTerm.length < 2) return;
            this.loading = true;
            fetch('{{ route('admin.clientes.buscar_mania') }}?q=' + encodeURIComponent(this.searchTerm) + '&brecho_id=' + this.brechoId)
                .then(res => res.json())
                .then(res => {
                    this.loading = false;
                    if (res.success) {
                        this.candidates = res.data;
                        if (res.data.length > 0) {
                            this.selectedCandidateId = res.data[0].id;
                        }
                    }
                })
                .catch(() => {
                    this.loading = false;
                });
        },

        transferir() {
            if (!this.selectedCandidateId || this.executing) return;

            if (!confirm('Deseja transferir as sacolinhas e vincular este cadastro da Minha Mania?')) {
                return;
            }

            this.executing = true;
            this.statusMsg = 'Transferindo...';

            fetch('{{ route('admin.clientes.transferir_dados_mania') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    dummy_user_id: this.dummyId,
                    mania_user_id: this.selectedCandidateId,
                    brecho_id: this.brechoId
                })
            })
            .then(res => res.json())
            .then(res => {
                this.executing = false;
                if (res.success) {
                    const card = document.getElementById('card-cliente-' + this.dummyId);
                    if (card) {
                        card.classList.remove('hover:shadow-md');
                        card.classList.add('bg-green-50/60', 'border-green-300');
                        card.innerHTML = `
                            <div class="flex items-center justify-between p-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-green-500 text-white flex items-center justify-center shrink-0">
                                        <i class="fas fa-check text-lg"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-900">${res.cliente.name} vinculado com sucesso!</h4>
                                        <p class="text-xs text-gray-600 mt-0.5">
                                            Sacolas transferidas. WhatsApp: <strong>${res.cliente.whatsapp || 'N/A'}</strong> | Cidade: <strong>${res.cliente.cidade || 'N/A'}-${res.cliente.estado || ''}</strong>
                                        </p>
                                    </div>
                                </div>
                                <span class="text-xs font-bold bg-green-200 text-green-800 px-3 py-1 rounded-full">Transferido</span>
                            </div>
                        `;
                    }
                } else {
                    alert('Erro ao transferir: ' + (res.message || 'Tente novamente.'));
                    this.statusMsg = 'Erro na transferência';
                }
            })
            .catch(err => {
                this.executing = false;
                alert('Erro de comunicação: ' + err.message);
                this.statusMsg = 'Erro de comunicação';
            });
        }
    };
}

function importadorManiaHandler(brechoId) {
    return {
        brechoId: brechoId,
        buscaGeral: '',
        resultados: [],
        carregando: false,

        fazerBuscaGeral() {
            if (this.buscaGeral.length < 2) {
                this.resultados = [];
                return;
            }
            this.carregando = true;
            fetch('{{ route('admin.clientes.buscar_mania') }}?q=' + encodeURIComponent(this.buscaGeral) + '&brecho_id=' + this.brechoId)
                .then(res => res.json())
                .then(res => {
                    this.carregando = false;
                    if (res.success) {
                        this.resultados = res.data;
                    }
                })
                .catch(() => {
                    this.carregando = false;
                });
        },

        vincularNovo(item) {
            item.vinculando = true;
            fetch('{{ route('admin.clientes.importar_mania') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    mania_user_id: item.id,
                    brecho_id: this.brechoId
                })
            })
            .then(res => res.json())
            .then(res => {
                item.vinculando = false;
                if (res.success) {
                    item.ja_vinculado = true;
                } else {
                    alert('Erro: ' + (res.message || 'Erro ao vincular.'));
                }
            })
            .catch(err => {
                item.vinculando = false;
                alert('Erro: ' + err.message);
            });
        }
    };
}
</script>
@endsection

@extends('layouts.app')

@section('title', 'Brechós Parceiros')
@section('brand_icon', 'fas fa-store')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
    createModalOpen: false,
    editModalOpen: false,
    editBrecho: {},
    operatorModalOpen: false,
    selectedBrecho: {},
    relatorioModalOpen: false,
    selectAllBrechos: true,
    selectedBrechos: {{ json_encode($brechos->where('id', '>', 1)->pluck('id')->values()) }},
    toggleAllBrechos() {
        if (this.selectAllBrechos) {
            this.selectedBrechos = {{ json_encode($brechos->where('id', '>', 1)->pluck('id')->values()) }};
        } else {
            this.selectedBrechos = [];
        }
    }
}">
    @if(session('success'))
        <div class="mb-4 bg-green-50 border-l-4 border-green-400 p-4 rounded-md shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4 rounded-md shadow-sm">
            <div class="flex items-center mb-1">
                <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                <p class="text-sm text-red-700 font-semibold">Atenção:</p>
            </div>
            <ul class="list-disc list-inside text-xs text-red-600 pl-4">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Gerenciar Brechós</h1>
            <p class="text-sm text-gray-500">Cadastre brechós parceiros e configure os operadores que terão acesso à plataforma.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" @click="relatorioModalOpen = true" class="inline-flex items-center px-4 py-2 bg-white text-indigo-700 hover:text-indigo-900 hover:bg-indigo-50 border-2 border-indigo-600 rounded-lg font-bold text-xs uppercase tracking-wider shadow-sm transition">
                <i class="fas fa-file-invoice-dollar text-indigo-600 mr-2 text-sm"></i> Relatório de Peças Cadastradas
            </button>
            <button @click="createModalOpen = true" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 transition shadow-sm">
                <i class="fas fa-plus mr-2"></i> Novo Brechó Parceiro
            </button>
        </div>
    </div>

    <!-- Tabela de Brechós com Colunas Ajustadas e Visíveis -->
    <div class="bg-white shadow sm:rounded-xl border border-gray-200">
        <div class="overflow-x-auto w-full">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="px-3 py-3 text-left w-12">ID</th>
                        <th scope="col" class="px-4 py-3 text-left">Nome do Brechó</th>
                        <th scope="col" class="px-4 py-3 text-left">Regras de Consignação</th>
                        <th scope="col" class="px-4 py-3 text-left">Contato / Pix</th>
                        <th scope="col" class="px-3 py-3 text-left">Estoque</th>
                        <th scope="col" class="px-4 py-3 text-left">Operadores (Login)</th>
                        <th scope="col" class="px-3 py-3 text-center">Status</th>
                        <th scope="col" class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($brechos as $b)
                    <tr class="hover:bg-gray-50/70 transition-colors">
                        <td class="px-3 py-4 whitespace-nowrap text-xs font-bold text-gray-400">#{{ $b->id }}</td>
                        <td class="px-4 py-4">
                            <div class="text-sm font-bold text-gray-900 flex items-center gap-2">
                                <i class="fas fa-store text-indigo-500"></i>
                                {{ $b->nome }}
                                @if($b->id === 1)
                                    <span class="bg-indigo-100 text-indigo-800 text-[10px] font-bold px-2 py-0.5 rounded-full">Matriz</span>
                                @else
                                    <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-emerald-200">Parceiro</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">slug: {{ $b->slug }} | {{ $b->lives_count }} lives</div>
                        </td>
                        <td class="px-4 py-4 text-xs">
                            @if($b->id === 1)
                                <span class="inline-flex items-center text-[11px] font-medium text-gray-500 bg-gray-100 px-2.5 py-1 rounded-md">
                                    <i class="fas fa-warehouse mr-1.5 text-gray-400"></i> Estoque Próprio
                                </span>
                            @else
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono font-bold text-xs bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded border border-indigo-200">
                                            SKU: {{ $b->prefixo_codigo ?: 'PAR' }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-gray-600">
                                        Taxa: <strong class="text-gray-900">R$ {{ number_format((float)($b->taxa_cadastro_peca ?: 1.80), 2, ',', '.') }}</strong> / peça
                                    </div>
                                    <div class="text-[11px] text-gray-600">
                                        Comissão: <strong class="text-emerald-700">{{ number_format((float)($b->comissao_porcentagem ?: 20.00), 1, ',', '.') }}%</strong> Mania
                                    </div>
                                    @if($b->fornecedor_id)
                                        <div class="text-[11px] text-indigo-900 font-semibold flex items-center gap-1 mt-1 bg-indigo-50/90 px-1.5 py-0.5 rounded border border-indigo-200 max-w-[200px] truncate" title="{{ $b->fornecedor_nome ?: $b->fornecedor_id }}">
                                            <i class="fas fa-link text-[10px] text-indigo-600 flex-shrink-0"></i>
                                            <span class="truncate">Forn: {{ $b->fornecedor_nome ?: $b->fornecedor_id }}</span>
                                        </div>
                                    @else
                                        <div class="text-[10px] text-gray-400 mt-1 italic flex items-center gap-1">
                                            <i class="fas fa-unlink text-[10px]"></i> Sem forn. vinculado
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-xs text-gray-500">
                            <div class="font-medium text-gray-800">{{ $b->whatsapp ?: ($b->telefone ?: 'Não informado') }}</div>
                            @if($b->chave_pix)
                                <div class="text-[11px] text-emerald-700 mt-0.5 flex items-center gap-1">
                                    <i class="fas fa-money-bill-wave text-[10px]"></i> Pix: {{ $b->chave_pix }}
                                </div>
                            @endif
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-700 font-semibold">
                            <a href="{{ route('items.index', ['brecho_id' => $b->id]) }}" class="text-indigo-600 hover:text-indigo-800 font-bold hover:underline inline-flex items-center gap-1.5" title="Ver itens deste brechó">
                                <i class="fas fa-tshirt text-xs text-indigo-400"></i>
                                {{ $b->items_count }} itens
                                <i class="fas fa-external-link-alt text-[10px] opacity-60"></i>
                            </a>
                        </td>
                        <td class="px-4 py-4 text-xs">
                            @php
                                $operadores = $b->operadores();
                            @endphp
                            @if($operadores->count() > 0)
                                <div class="space-y-1 max-w-[210px]">
                                    @foreach($operadores->take(2) as $u)
                                        <div class="flex items-center gap-1.5 text-gray-700 truncate" title="{{ $u->name }} ({{ $u->email }})">
                                            <i class="fas fa-user-circle text-indigo-500 text-xs flex-shrink-0"></i>
                                            <span class="font-semibold text-xs truncate">{{ $u->name }}</span>
                                        </div>
                                    @endforeach
                                    @if($operadores->count() > 2)
                                        <div class="inline-flex items-center gap-1 text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200" title="{{ $operadores->pluck('name')->join(', ') }}">
                                            <i class="fas fa-users text-[10px]"></i> +{{ $operadores->count() - 2 }} outros operadores
                                        </div>
                                    @endif
                                </div>
                            @else
                                <span class="inline-flex items-center text-[11px] text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                    <i class="fas fa-exclamation-circle mr-1"></i> Sem operador
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-center">
                            @if($b->ativo)
                                <span class="px-2.5 py-1 inline-flex text-[11px] font-bold rounded-full bg-emerald-100 text-emerald-800">Ativo</span>
                            @else
                                <span class="px-2.5 py-1 inline-flex text-[11px] font-bold rounded-full bg-red-100 text-red-800">Inativo</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-right text-xs font-medium space-x-1">
                            @if($b->id !== 1)
                                <a href="{{ route('admin.brechos.prestacao_contas', $b) }}" class="text-emerald-800 hover:text-emerald-950 bg-emerald-100 px-2.5 py-1.5 rounded-lg hover:bg-emerald-200 transition font-bold inline-flex items-center border border-emerald-300 shadow-xs" title="Prestação de Contas, Vendas e Carteira">
                                    <i class="fas fa-file-invoice-dollar mr-1.5 text-emerald-700"></i> Prestação de Contas
                                </a>
                            @endif
                            <a href="{{ route('items.index', ['brecho_id' => $b->id]) }}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-2.5 py-1.5 rounded-lg hover:bg-indigo-100 transition font-semibold inline-flex items-center" title="Ver itens deste brechó">
                                <i class="fas fa-boxes mr-1"></i> Itens
                            </a>
                            <button @click="selectedBrecho = Object.assign({}, {{ json_encode($b) }}); operatorModalOpen = true;" class="text-emerald-700 hover:text-emerald-900 bg-emerald-50 px-2.5 py-1.5 rounded-lg hover:bg-emerald-100 transition font-semibold inline-flex items-center" title="Criar login de operador">
                                <i class="fas fa-user-plus mr-1"></i> + Operador
                            </button>
                            <button @click="editBrecho = Object.assign({}, {{ json_encode($b) }}); editModalOpen = true;" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-2.5 py-1.5 rounded-lg hover:bg-indigo-100 transition font-semibold inline-flex items-center">
                                <i class="fas fa-edit mr-1"></i> Editar
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-8 text-center text-sm text-gray-500">Nenhum brechó cadastrado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Criar Brechó -->
    <div x-show="createModalOpen" x-cloak class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div @click="createModalOpen = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
                <form action="{{ route('admin.brechos.store') }}" method="POST">
                    @csrf
                    <div class="bg-white px-6 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-store text-indigo-600"></i> Cadastrar Brechó Parceiro
                        </h3>
                        <div class="space-y-4 text-sm">
                            <div>
                                <label class="block font-medium text-gray-700">Nome do Brechó *</label>
                                <input type="text" name="nome" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-medium text-gray-700">CNPJ / CPF</label>
                                    <input type="text" name="documento" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block font-medium text-gray-700">WhatsApp / Telefone</label>
                                    <input type="text" name="whatsapp" placeholder="(99) 99999-9999" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-medium text-gray-700">Chave Pix</label>
                                    <input type="text" name="chave_pix" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block font-medium text-gray-700">Tipo de Chave</label>
                                    <select name="tipo_chave_pix" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Selecione...</option>
                                        <option value="cpf">CPF</option>
                                        <option value="cnpj">CNPJ</option>
                                        <option value="email">E-mail</option>
                                        <option value="telefone">Telefone</option>
                                        <option value="aleatoria">Chave Aleatória</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Card Configuração da Parceria & Consignação --}}
                            <div class="bg-indigo-50/70 p-4 rounded-xl border border-indigo-200 space-y-3" x-data="fornecedorPicker()">
                                <h4 class="text-xs font-bold text-indigo-900 uppercase tracking-wider flex items-center gap-1.5">
                                    <i class="fas fa-handshake text-indigo-600"></i> Configuração da Parceria & Consignação
                                </h4>
                                <p class="text-xs text-indigo-700">Valores padrão aplicados automaticamente nas avaliações e lives deste parceiro.</p>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Prefixo SKU</label>
                                        <input type="text" name="prefixo_codigo" maxlength="6" placeholder="Ex: TCB, VAL" class="block w-full border-gray-300 rounded-lg shadow-sm font-bold uppercase focus:ring-indigo-500 focus:border-indigo-500 text-xs py-2 px-2.5 bg-white">
                                        <span class="text-[10px] text-gray-500">Ex: TCB-00089-001</span>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Taxa Cadastro / Peça (R$)</label>
                                        <input type="number" step="0.10" min="0" name="taxa_cadastro_peca" value="1.80" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs py-2 px-2.5 bg-white font-medium">
                                        <span class="text-[10px] text-gray-500">Padrão: R$ 1,80</span>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Comissão Mania (%)</label>
                                        <input type="number" step="0.5" min="0" max="100" name="comissao_porcentagem" value="20.00" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs py-2 px-2.5 bg-white font-medium">
                                        <span class="text-[10px] text-gray-500">Padrão: 20%</span>
                                    </div>
                                </div>

                                {{-- Vínculo Fornecedor Mania --}}
                                <div class="pt-2 border-t border-indigo-200/60 relative">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                                        Fornecedor Vinculado (Minha Mania)
                                        <span class="text-[10px] text-gray-500 font-normal ml-1">— Trava as regras comerciais na Nova Avaliação</span>
                                    </label>
                                    <div class="relative">
                                        <div class="relative flex items-center">
                                            <input type="text" x-model="searchQuery" @input.debounce.250ms="search()" @focus="if(results.length > 0) showDropdown = true"
                                                   placeholder="Buscar fornecedor/cliente (ex: Taco balaio, Val...)"
                                                   class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs py-2 pl-3 pr-8 bg-white">
                                            <button type="button" x-show="selectedId" @click="clear()" class="absolute right-2 text-gray-400 hover:text-red-500 text-xs" title="Remover vínculo">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        </div>
                                        <input type="hidden" name="fornecedor_id" :value="selectedId">
                                        <input type="hidden" name="fornecedor_nome" :value="selectedNome">
                                        
                                        <div x-show="selectedId" x-cloak class="mt-1 flex items-center justify-between text-[11px] text-indigo-900 bg-white px-2 py-1 rounded border border-indigo-200 shadow-xs">
                                            <div class="flex items-center gap-1.5 truncate">
                                                <i class="fas fa-check-circle text-emerald-600 text-xs"></i>
                                                <span class="truncate">Vinculado a: <strong class="text-indigo-950 font-bold" x-text="selectedNome"></strong> (<span class="font-mono text-[10px] text-gray-500" x-text="selectedId"></span>)</span>
                                            </div>
                                            <button type="button" @click="clear()" class="text-gray-400 hover:text-red-600 ml-2 text-[10px] font-semibold">Desvincular</button>
                                        </div>

                                        <div x-show="showDropdown && results.length > 0" x-cloak @click.away="showDropdown = false" class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-44 overflow-y-auto">
                                            <template x-for="r in results" :key="r.id">
                                                <button type="button" @click="select(r)" class="w-full text-left px-3 py-2 text-xs hover:bg-indigo-50 border-b border-gray-100 flex items-center justify-between text-gray-900 transition-colors">
                                                    <div>
                                                        <span class="font-medium text-gray-900" x-text="r.text"></span>
                                                    </div>
                                                    <span class="text-[10px] text-gray-500 font-mono flex-shrink-0 ml-2" x-text="r.id"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="border-gray-200 my-4">
                            <div class="bg-indigo-50/60 p-4 rounded-xl border border-indigo-100">
                                <h4 class="text-xs font-bold text-indigo-900 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                    <i class="fas fa-user-lock text-indigo-600"></i> Acesso do Operador (Login Inicial)
                                </h4>
                                <p class="text-xs text-gray-500 mb-3">Defina o e-mail e a senha para o brechó acessar a plataforma.</p>
                                
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Nome do Operador / Responsável</label>
                                        <input type="text" name="operador_nome" placeholder="Ex: Maria (opcional, padrão é o nome do brechó)" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-xs">
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">E-mail de Login *</label>
                                            <input type="email" name="operador_email" placeholder="login@brecho.com" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-xs">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">Senha de Acesso *</label>
                                            <input type="password" name="operador_password" minlength="6" placeholder="Mínimo 6 caracteres" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-xs">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 flex justify-end gap-2">
                        <button type="button" @click="createModalOpen = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-100">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Salvar Brechó</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Brechó -->
    <div x-show="editModalOpen" x-cloak class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div @click="editModalOpen = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
                <form :action="'/admin/brechos/' + editBrecho.id" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="bg-white px-6 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-edit text-indigo-600"></i> Editar Brechó
                        </h3>
                        <div class="space-y-4 text-sm">
                            <div>
                                <label class="block font-medium text-gray-700">Nome do Brechó *</label>
                                <input type="text" name="nome" x-model="editBrecho.nome" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-medium text-gray-700">CNPJ / CPF</label>
                                    <input type="text" name="documento" x-model="editBrecho.documento" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block font-medium text-gray-700">WhatsApp / Telefone</label>
                                    <input type="text" name="whatsapp" x-model="editBrecho.whatsapp" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-medium text-gray-700">Chave Pix</label>
                                    <input type="text" name="chave_pix" x-model="editBrecho.chave_pix" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block font-medium text-gray-700">Tipo de Chave</label>
                                    <select name="tipo_chave_pix" x-model="editBrecho.tipo_chave_pix" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Selecione...</option>
                                        <option value="cpf">CPF</option>
                                        <option value="cnpj">CNPJ</option>
                                        <option value="email">E-mail</option>
                                        <option value="telefone">Telefone</option>
                                        <option value="aleatoria">Chave Aleatória</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Card Configuração da Parceria & Consignação --}}
                            <div class="bg-indigo-50/70 p-4 rounded-xl border border-indigo-200 space-y-3" x-show="editBrecho.id != 1" x-data="fornecedorPicker()">
                                <h4 class="text-xs font-bold text-indigo-900 uppercase tracking-wider flex items-center gap-1.5">
                                    <i class="fas fa-handshake text-indigo-600"></i> Configuração da Parceria & Consignação
                                </h4>
                                <p class="text-xs text-indigo-700">Valores padrão aplicados automaticamente nas avaliações e lives deste parceiro.</p>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Prefixo SKU</label>
                                        <input type="text" name="prefixo_codigo" x-model="editBrecho.prefixo_codigo" maxlength="6" placeholder="Ex: TCB, VAL" class="block w-full border-gray-300 rounded-lg shadow-sm font-bold uppercase focus:ring-indigo-500 focus:border-indigo-500 text-xs py-2 px-2.5 bg-white">
                                        <span class="text-[10px] text-gray-500">Ex: TCB-00089-001</span>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Taxa Cadastro / Peça (R$)</label>
                                        <input type="number" step="0.10" min="0" name="taxa_cadastro_peca" x-model="editBrecho.taxa_cadastro_peca" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs py-2 px-2.5 bg-white font-medium">
                                        <span class="text-[10px] text-gray-500">Padrão: R$ 1,80</span>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Comissão Mania (%)</label>
                                        <input type="number" step="0.5" min="0" max="100" name="comissao_porcentagem" x-model="editBrecho.comissao_porcentagem" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs py-2 px-2.5 bg-white font-medium">
                                        <span class="text-[10px] text-gray-500">Padrão: 20%</span>
                                    </div>
                                </div>

                                {{-- Vínculo Fornecedor Mania --}}
                                <div class="pt-2 border-t border-indigo-200/60 relative">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                                        Fornecedor Vinculado (Minha Mania)
                                        <span class="text-[10px] text-gray-500 font-normal ml-1">— Trava as regras comerciais na Nova Avaliação</span>
                                    </label>
                                    <div class="relative">
                                        <div class="relative flex items-center">
                                            <input type="text" x-model="searchQuery" @input.debounce.250ms="search()" @focus="if(results.length > 0) showDropdown = true"
                                                   placeholder="Buscar fornecedor/cliente (ex: Taco balaio, Val...)"
                                                   class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs py-2 pl-3 pr-8 bg-white">
                                            <button type="button" x-show="selectedId" @click="clear()" class="absolute right-2 text-gray-400 hover:text-red-500 text-xs" title="Remover vínculo">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        </div>
                                        <input type="hidden" name="fornecedor_id" :value="selectedId">
                                        <input type="hidden" name="fornecedor_nome" :value="selectedNome">
                                        
                                        <div x-show="selectedId" x-cloak class="mt-1 flex items-center justify-between text-[11px] text-indigo-900 bg-white px-2 py-1 rounded border border-indigo-200 shadow-xs">
                                            <div class="flex items-center gap-1.5 truncate">
                                                <i class="fas fa-check-circle text-emerald-600 text-xs"></i>
                                                <span class="truncate">Vinculado a: <strong class="text-indigo-950 font-bold" x-text="selectedNome"></strong> (<span class="font-mono text-[10px] text-gray-500" x-text="selectedId"></span>)</span>
                                            </div>
                                            <button type="button" @click="clear()" class="text-gray-400 hover:text-red-600 ml-2 text-[10px] font-semibold">Desvincular</button>
                                        </div>

                                        <div x-show="showDropdown && results.length > 0" x-cloak @click.away="showDropdown = false" class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-44 overflow-y-auto">
                                            <template x-for="r in results" :key="r.id">
                                                <button type="button" @click="select(r)" class="w-full text-left px-3 py-2 text-xs hover:bg-indigo-50 border-b border-gray-100 flex items-center justify-between text-gray-900 transition-colors">
                                                    <div>
                                                        <span class="font-medium text-gray-900" x-text="r.text"></span>
                                                    </div>
                                                    <span class="text-[10px] text-gray-500 font-mono flex-shrink-0 ml-2" x-text="r.id"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-700">Status</label>
                                <select name="ativo" x-model="editBrecho.ativo" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option :value="1">Ativo</option>
                                    <option :value="0">Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 flex justify-end gap-2">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-100">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Atualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Criar Operador -->
    <div x-show="operatorModalOpen" x-cloak class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div @click="operatorModalOpen = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form :action="'/admin/brechos/' + selectedBrecho.id + '/operador'" method="POST">
                    @csrf
                    <div class="bg-white px-6 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center gap-2">
                            <i class="fas fa-user-plus text-emerald-600"></i> Criar Operador & Acesso
                        </h3>
                        <p class="text-xs text-gray-500 mb-4">
                            Cadastre um usuário com login e senha para o brechó <strong class="text-gray-800" x-text="selectedBrecho.nome"></strong>. Ao fazer login, este operador terá acesso exclusivo aos produtos, lives e clientes da sua loja.
                        </p>
                        <div class="space-y-4 text-sm">
                            <div>
                                <label class="block font-medium text-gray-700">Nome do Operador / Responsável *</label>
                                <input type="text" name="name" required placeholder="Ex: Maria - Brechó" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block font-medium text-gray-700">E-mail de Login *</label>
                                <input type="email" name="email" required placeholder="exemplo@brecho.com" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block font-medium text-gray-700">Senha de Acesso *</label>
                                <input type="password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block font-medium text-gray-700">WhatsApp (Opcional)</label>
                                <input type="text" name="whatsapp" placeholder="(99) 99999-9999" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 flex justify-end gap-2">
                        <button type="button" @click="operatorModalOpen = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-100">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700">Criar Usuário</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Relatório de Peças Cadastradas por Parceiro -->
    <div x-show="relatorioModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="relatorioModalOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200">
                <form action="{{ route('admin.brechos.relatorio_pecas_pdf') }}" method="GET" target="_blank" @submit="setTimeout(() => { relatorioModalOpen = false; }, 500)">
                    <div class="bg-indigo-700 px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-file-invoice-dollar text-white text-lg"></i>
                            <h3 class="text-base font-bold text-white">Relatório de Peças Cadastradas</h3>
                        </div>
                        <button type="button" @click="relatorioModalOpen = false" class="text-indigo-200 hover:text-white transition">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-5 bg-white">
                        <p class="text-xs text-gray-600">
                            Selecione o período e os brechós parceiros desejados para gerar o relatório em PDF com o cálculo do valor a ser pago (<strong>Quantidade Total &times; R$ 1,00</strong>).
                        </p>

                        <!-- Período -->
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-3">
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                <i class="fas fa-calendar-alt text-indigo-600"></i> Período de Cadastro
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Data Início</label>
                                    <input type="date" name="data_inicio" value="{{ now()->startOfMonth()->format('Y-m-d') }}" required class="w-full text-xs font-medium text-gray-900 border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Data Fim</label>
                                    <input type="date" name="data_fim" value="{{ now()->format('Y-m-d') }}" required class="w-full text-xs font-medium text-gray-900 border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                </div>
                            </div>
                        </div>

                        <!-- Seleção de Brechós -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Brechós Parceiros
                                </label>
                                <label class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-700 cursor-pointer hover:text-indigo-900">
                                    <input type="checkbox" x-model="selectAllBrechos" @change="toggleAllBrechos" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>Selecionar Todos</span>
                                </label>
                            </div>

                            <div class="max-h-52 overflow-y-auto divide-y divide-gray-100 border border-gray-200 rounded-xl bg-white p-2">
                                @foreach($brechos->where('id', '>', 1) as $p)
                                    <label class="flex items-center justify-between p-2.5 hover:bg-gray-50 rounded-lg cursor-pointer transition">
                                        <div class="flex items-center gap-2.5">
                                            <input type="checkbox" name="brechos[]" value="{{ $p->id }}" x-model="selectedBrechos" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <div>
                                                <span class="text-xs font-bold text-gray-900">{{ $p->nome }}</span>
                                                @if($p->prefixo_codigo)
                                                    <span class="ml-1 text-[10px] bg-indigo-50 text-indigo-700 font-bold px-1.5 py-0.5 rounded border border-indigo-200">SKU: {{ $p->prefixo_codigo }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        @if($p->chave_pix)
                                            <span class="text-[11px] text-gray-500 font-mono">Pix: {{ Str::limit($p->chave_pix, 16) }}</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Info do Valor -->
                        <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-3 flex items-start gap-2.5">
                            <i class="fas fa-info-circle text-indigo-600 mt-0.5"></i>
                            <div class="text-xs text-indigo-950">
                                <span class="font-bold">Custo de Cadastro:</span> R$ 1,00 por peça cadastrada no período.<br>
                                O PDF conterá a listagem individual, total de peças e o cálculo do repasse a pagar.
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-3.5 flex justify-end gap-2 border-t border-gray-200">
                        <button type="button" @click="relatorioModalOpen = false" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-100 transition">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold uppercase tracking-wider hover:bg-indigo-700 active:bg-indigo-800 transition shadow-sm flex items-center gap-1.5">
                            <i class="fas fa-file-pdf"></i> Gerar Relatório em PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function fornecedorPicker(initialId = '', initialNome = '') {
    return {
        selectedId: initialId || '',
        selectedNome: initialNome || '',
        searchQuery: initialNome || '',
        results: [],
        showDropdown: false,
        loading: false,
        init() {
            this.$watch('$data.editBrecho', (b) => {
                if (b && typeof b === 'object') {
                    this.selectedId = b.fornecedor_id || '';
                    this.selectedNome = b.fornecedor_nome || '';
                    this.searchQuery = b.fornecedor_nome || '';
                }
            });
        },
        search() {
            if (!this.searchQuery || this.searchQuery.trim().length < 2) {
                this.results = [];
                this.showDropdown = false;
                return;
            }
            this.loading = true;
            fetch('/admin/avaliacoes/search-fornecedor-cliente?q=' + encodeURIComponent(this.searchQuery))
                .then(r => r.json())
                .then(data => {
                    this.results = Array.isArray(data) ? data : (data.data || []);
                    this.showDropdown = this.results.length > 0;
                })
                .catch(() => { this.results = []; })
                .finally(() => { this.loading = false; });
        },
        select(item) {
            this.selectedId = item.id;
            this.selectedNome = item.text ? item.text.replace(/ ✦ Brechó Parceiro: .*/, '').replace(/ \(Fornecedor\)/, '') : item.id;
            this.searchQuery = this.selectedNome;
            this.showDropdown = false;
        },
        clear() {
            this.selectedId = '';
            this.selectedNome = '';
            this.searchQuery = '';
            this.results = [];
            this.showDropdown = false;
        }
    };
}
</script>
@endsection
